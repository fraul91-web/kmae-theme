<?php
/**
 * Böngészés v2 kártyaképek: kmae_card_s/m/l méretek + ambient szín, hangulat és
 * elmosott előkép (LQIP) minden hirdetésfotóhoz, valamint a meglévő képek
 * kötegelt, újrafuttatható utólagos feldolgozása (cron + admin gomb).
 *
 * A méreteket NEM `add_image_size()` regisztrálja: az minden WordPress-feltöltésre
 * (más oldal-képekre is) legyártatná őket, és a kész, már tömörített mesterből.
 * Itt a feltöltéskor a KMAE_Images már betöltött szerkesztője adja a pixeleket,
 * így minden kártyaméret egyetlen tömörítéssel készül a forrásból.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Image_Cards {

	/** Méret-kulcs => szélesség (px), nagytól kicsi felé: a pixeleken lánc-kicsinyítés megy, nem újratömörítés. */
	const SIZES = array(
		'kmae_card_l' => 1200,
		'kmae_card_m' => 800,
		'kmae_card_s' => 480,
	);

	/** Attachment meta: kész-jelző (a feldolgozás verziója) és a sikertelen próbálkozások száma. */
	const META_VERSION = '_kmae_cards_v';
	const META_FAIL    = '_kmae_cards_fail';
	const META_AMBIENT = '_kmae_ambient';
	const META_TONE    = '_kmae_tone';
	const META_LQIP    = '_kmae_lqip';

	/** A feldolgozás verziója: emelve minden kép újra sorra kerül. */
	const VERSION = 1;

	const BATCH       = 20;
	const MAX_TRIES   = 3;
	const TIME_BUDGET = 20;
	const LOCK        = 'kmae_cards_lock';
	const HOOK        = 'kmae_cards_backfill';

	/** Az LQIP data URI egésze (előtaggal együtt) ennél nem lehet hosszabb. */
	const LQIP_MAX_BYTES = 600;
	const LQIP_PREFIX    = 'data:image/webp;base64,';

	/** Az elemzéshez használt kis kép szélessége (px). */
	const ANALYSIS_WIDTH = 32;

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'cron_run' ), 10, 1 );
		add_action(
			'init',
			static function () {
				if ( ! wp_next_scheduled( self::HOOK ) ) {
					wp_schedule_event( time() + 300, 'hourly', self::HOOK );
				}
			}
		);
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 22 );
		add_action( 'admin_post_kmae_cards_run', array( __CLASS__, 'handle_run' ) );
		add_action( 'wp_ajax_kmae_subject_pending', array( __CLASS__, 'ajax_subject_pending' ) );
		add_action( 'wp_ajax_kmae_subject_save', array( __CLASS__, 'ajax_subject_save' ) );
	}

	/* ------------------------------------------------------------------
	   Autó-felismerés a régi fotókon (az admin böngészője futtatja,
	   photo-subject.js; az új fotóknál a feltöltő böngészője teszi meg)
	   ------------------------------------------------------------------ */

	/** Doboz nélküli hirdetésfotók (a „none” = megnézve, nincs rajta autó). */
	private static function subject_sql( $count ) {
		global $wpdb;
		$select = $count ? 'COUNT(*)' : 'p.ID';
		$sql    = $wpdb->prepare(
			"SELECT {$select} FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->posts} l ON l.ID = p.post_parent AND l.post_type = 'kmae_listing'
			LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
			WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/webp','image/jpeg','image/png') AND s.meta_id IS NULL",
			KMAE_Images::META_SUBJECT
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $count ? $sql : $sql . ' ORDER BY p.ID DESC LIMIT 300';
	}

	public static function subject_pending_count() {
		global $wpdb;
		return (int) $wpdb->get_var( self::subject_sql( true ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function ajax_subject_pending() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'kmae_subject', 'nonce', false ) ) {
			wp_send_json_error( null, 403 );
		}
		global $wpdb;
		$items = array();
		foreach ( (array) $wpdb->get_col( self::subject_sql( false ) ) as $id ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$src = KMAE_Images::card_sources( (int) $id );
			$url = '' !== $src['m'] ? $src['m'] : $src['l'];
			if ( '' !== $url ) {
				$items[] = array(
					'id'  => (int) $id,
					'url' => $url,
				);
			}
		}
		wp_send_json_success( $items );
	}

	public static function ajax_subject_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'kmae_subject', 'nonce', false ) ) {
			wp_send_json_error( null, 403 );
		}
		$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$box = isset( $_POST['box'] ) ? json_decode( wp_unslash( (string) $_POST['box'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parse_subject számokra szűr.
		if ( ! $id || 'attachment' !== get_post_type( $id ) ) {
			wp_send_json_error( null, 400 );
		}
		$box = KMAE_Images::parse_subject( is_array( $box ) ? $box : '' );
		update_post_meta( $id, KMAE_Images::META_SUBJECT, $box ? implode( ',', $box ) : 'none' );
		wp_send_json_success();
	}

	/* ------------------------------------------------------------------
	   Egy kép feldolgozása
	   ------------------------------------------------------------------ */

	/**
	 * Kártyaméretek + elemzés a már betöltött szerkesztőből. A szerkesztő képe
	 * lépésenként kisebb lesz (1200 → 800 → 480 → 32 → 20 px), a pixeleket nem
	 * töltjük be újra és nem tömörítjük újra; mindegyik fájl egyszer tömörül.
	 *
	 * @param WP_Image_Editor $editor   Betöltött szerkesztő, a mester méretű képpel.
	 * @param string          $dir      Célmappa (a mester mappája).
	 * @param string          $stem     Fájlnév-alap (a mester neve kiterjesztés nélkül).
	 * @param string          $out_mime Kimeneti MIME (image/webp, ha a tárhely tud WebP-t).
	 * @return array|null {sizes: array<string,array>, files: string[], ambient: string, tone: string, lqip: string}
	 */
	public static function build_from_editor( $editor, $dir, $stem, $out_mime ) {
		if ( ! is_object( $editor ) || ! is_dir( $dir ) ) {
			return null;
		}
		$ext    = self::extension_for( $out_mime );
		$result = array(
			'sizes'   => array(),
			'files'   => array(),
			'ambient' => '',
			'tone'    => '',
			'lqip'    => '',
		);

		try {
			$editor->set_quality( KMAE_Images::WEBP_QUALITY );

			foreach ( self::SIZES as $key => $width ) {
				/* Kisebb forrásnál a WordPress nem nagyít (hibát ad): az a méret kimarad. */
				$resized = $editor->resize( $width, 0, false );
				if ( is_wp_error( $resized ) ) {
					continue;
				}
				$name  = wp_unique_filename( $dir, $stem . '-' . $width . 'w.' . $ext );
				$saved = $editor->save( $dir . '/' . $name, $out_mime );
				if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
					continue;
				}
				$result['files'][]        = $saved['path'];
				$result['sizes'][ $key ] = array(
					'file'      => wp_basename( $saved['path'] ),
					'width'     => (int) $saved['width'],
					'height'    => (int) $saved['height'],
					'mime-type' => $saved['mime-type'],
					'filesize'  => isset( $saved['filesize'] ) ? (int) $saved['filesize'] : (int) @filesize( $saved['path'] ),
				);
			}

			/* Elemzés: kis PNG (veszteségmentes), ebből olvassuk a pixeleket. */
			$editor->resize( self::ANALYSIS_WIDTH, 0, false );
			$png = $editor->save( $dir . '/' . $stem . '-ambient.png', 'image/png' );
			if ( ! is_wp_error( $png ) && ! empty( $png['path'] ) ) {
				$grid = self::read_pixels( $png['path'] );
				wp_delete_file( $png['path'] );
				if ( $grid ) {
					$found = KMAE_Image_Ambient::analyze( $grid[0], $grid[1], $grid[2] );
					if ( $found ) {
						$result['ambient'] = $found['ambient'];
						$result['tone']    = $found['tone'];
					}
				}
			}

			$result['lqip'] = self::make_lqip( $editor, $dir, $stem );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			/* A kártyakép járulékos: hiba esetén a feltöltés/köteg a már kész részekkel megy tovább. */
		}

		return empty( $result['sizes'] ) && '' === $result['ambient'] ? null : $result;
	}

	/**
	 * Elmosott, pár száz bájtos előkép. Alacsony minőség + 20 (majd 16) px:
	 * a böngésző felnagyítva simítja, ezért nem kell külön blur.
	 *
	 * @return string data URI vagy '' (ha nincs WebP vagy nem fér a keretbe).
	 */
	private static function make_lqip( $editor, $dir, $stem ) {
		if ( ! KMAE_Images::supports_webp() ) {
			return '';
		}
		$passes = array(
			array( 20, array( 50, 35, 25, 15 ) ),
			array( 16, array( 25, 15, 8 ) ),
		);
		foreach ( $passes as $pass ) {
			$editor->resize( $pass[0], 0, false );
			foreach ( $pass[1] as $quality ) {
				$editor->set_quality( $quality );
				$saved = $editor->save( $dir . '/' . $stem . '-lqip.webp', 'image/webp' );
				if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
					return '';
				}
				$bytes = (string) file_get_contents( $saved['path'] );
				wp_delete_file( $saved['path'] );
				$uri = self::LQIP_PREFIX . base64_encode( $bytes );
				if ( '' !== $bytes && strlen( $uri ) <= self::LQIP_MAX_BYTES ) {
					return $uri;
				}
			}
		}
		return '';
	}

	/**
	 * Az elemzéshez szükséges pixelek egy kis PNG-ből.
	 *
	 * @return array{0:int,1:int,2:int[]}|null [szélesség, magasság, lapos RGB]
	 */
	private static function read_pixels( $png_path ) {
		if ( function_exists( 'imagecreatefrompng' ) ) {
			$im = @imagecreatefrompng( $png_path );
			if ( $im ) {
				if ( ! imageistruecolor( $im ) ) {
					imagepalettetotruecolor( $im );
				}
				$w  = imagesx( $im );
				$h  = imagesy( $im );
				$px = array();
				for ( $y = 0; $y < $h; $y++ ) {
					for ( $x = 0; $x < $w; $x++ ) {
						$c     = imagecolorat( $im, $x, $y );
						$alpha = ( $c >> 24 ) & 0x7F;
						$r     = ( $c >> 16 ) & 0xFF;
						$g     = ( $c >> 8 ) & 0xFF;
						$b     = $c & 0xFF;
						if ( $alpha > 0 ) {
							/* Átlátszó rész fehér háttéren (a kliens is fehérre festi). */
							$f = $alpha / 127;
							$r = (int) round( $r * ( 1 - $f ) + 255 * $f );
							$g = (int) round( $g * ( 1 - $f ) + 255 * $f );
							$b = (int) round( $b * ( 1 - $f ) + 255 * $f );
						}
						array_push( $px, $r, $g, $b );
					}
				}
				if ( PHP_VERSION_ID < 80000 ) {
					imagedestroy( $im );
				}
				return array( $w, $h, $px );
			}
		}
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			try {
				$im = new Imagick( $png_path );
				$im->setImageBackgroundColor( 'white' );
				$im = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
				$w  = $im->getImageWidth();
				$h  = $im->getImageHeight();
				$px = $im->exportImagePixels( 0, 0, $w, $h, 'RGB', Imagick::PIXEL_CHAR );
				$im->clear();
				return array( $w, $h, array_map( 'intval', $px ) );
			} catch ( \Throwable $e ) {
				return null;
			}
		}
		return null;
	}

	/**
	 * A kártyaméretek bejegyzése a metaadatba (a meglévő méretek mellé).
	 *
	 * @param array $metadata wp_get_attachment_metadata() / wp_generate_attachment_metadata() eredménye.
	 * @param array $cards    build_from_editor() eredménye.
	 */
	public static function merge( array &$metadata, array $cards ) {
		if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
			$metadata['sizes'] = array();
		}
		foreach ( $cards['sizes'] as $key => $data ) {
			$metadata['sizes'][ $key ] = $data;
		}
	}

	/** Ambient / hangulat / LQIP és a kész-jelző mentése. */
	public static function save_meta( $attachment_id, array $cards ) {
		$attachment_id = (int) $attachment_id;
		foreach ( array(
			self::META_AMBIENT => $cards['ambient'],
			self::META_TONE    => $cards['tone'],
			self::META_LQIP    => $cards['lqip'],
		) as $meta_key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( $attachment_id, $meta_key, $value );
			} else {
				delete_post_meta( $attachment_id, $meta_key );
			}
		}
		update_post_meta( $attachment_id, self::META_VERSION, self::VERSION );
		delete_post_meta( $attachment_id, self::META_FAIL );
	}

	/** Egy metaadatban rögzített régi kártya-fájlok törlése (újrafeldolgozásnál ne maradjon árva). */
	public static function delete_recorded( $metadata, $dir ) {
		if ( ! is_array( $metadata ) || empty( $metadata['sizes'] ) ) {
			return;
		}
		foreach ( array_keys( self::SIZES ) as $key ) {
			if ( ! empty( $metadata['sizes'][ $key ]['file'] ) ) {
				wp_delete_file( $dir . '/' . wp_basename( $metadata['sizes'][ $key ]['file'] ) );
			}
		}
	}

	/** @param string[] $files Fájlok teljes útja. */
	public static function delete_files( array $files ) {
		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}
	}

	public static function extension_for( $mime ) {
		switch ( $mime ) {
			case 'image/webp':
				return 'webp';
			case 'image/png':
				return 'png';
			default:
				return 'jpg';
		}
	}

	/**
	 * Kártyaméretek utólag egy már feldolgozott képhez (a mesterből).
	 *
	 * @return bool Sikerült-e (a kész-jelzőt a hívó állítja be).
	 */
	public static function process_existing( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$file          = get_attached_file( $attachment_id );
		$mime          = (string) get_post_mime_type( $attachment_id );
		if ( ! $file || ! file_exists( $file ) || ! in_array( $mime, array( 'image/webp', 'image/jpeg', 'image/png' ), true ) ) {
			return false;
		}
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_array( $metadata ) || empty( $metadata ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_raise_memory_limit( 'image' );
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$dir   = dirname( $file );
		$stem  = preg_replace( '/\.[^.]+$/', '', wp_basename( $file ) );
		$cards = self::build_from_editor( $editor, $dir, $stem, KMAE_Images::supports_webp() ? 'image/webp' : $mime );
		unset( $editor );
		if ( ! $cards ) {
			return false;
		}

		self::delete_recorded( $metadata, $dir );
		self::merge( $metadata, $cards );
		wp_update_attachment_metadata( $attachment_id, $metadata );
		self::save_meta( $attachment_id, $cards );
		return true;
	}

	/* ------------------------------------------------------------------
	   Köteg
	   ------------------------------------------------------------------ */

	private static function is_done( $attachment_id ) {
		return (int) get_post_meta( (int) $attachment_id, self::META_VERSION, true ) >= self::VERSION;
	}

	/**
	 * A kártyás feldolgozás hatóköre: a pluginnal feldolgozott (`_kmae_orientation`),
	 * hirdetéshez tartozó (post_parent > 0; a chat-képeknél 0) állóképek.
	 *
	 * @param string $mode total | done | failed | pending
	 * @param int    $limit Csak pending: legfeljebb ennyi azonosító.
	 * @return string Előkészített SQL.
	 */
	private static function sql( $mode, $limit = 0 ) {
		global $wpdb;
		$from = "FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} o ON o.post_id = p.ID AND o.meta_key = '_kmae_orientation' ";
		$base = "p.post_type = 'attachment' AND p.post_parent > 0 AND p.post_mime_type IN ('image/webp','image/jpeg','image/png')";
		$v    = (int) self::VERSION;
		$t    = (int) self::MAX_TRIES;

		$d_join = $wpdb->prepare( "LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s ", self::META_VERSION );
		$f_join = $wpdb->prepare( "LEFT JOIN {$wpdb->postmeta} f ON f.post_id = p.ID AND f.meta_key = %s ", self::META_FAIL );
		$not_done = "(d.meta_id IS NULL OR CAST(d.meta_value AS UNSIGNED) < {$v})";

		switch ( $mode ) {
			case 'done':
				return "SELECT COUNT(DISTINCT p.ID) {$from} {$d_join} WHERE {$base} AND d.meta_id IS NOT NULL AND CAST(d.meta_value AS UNSIGNED) >= {$v}";
			case 'failed':
				return "SELECT COUNT(DISTINCT p.ID) {$from} {$d_join} {$f_join} WHERE {$base} AND {$not_done} AND f.meta_id IS NOT NULL AND CAST(f.meta_value AS UNSIGNED) >= {$t}";
			case 'pending':
				$sql = "SELECT DISTINCT p.ID {$from} {$d_join} {$f_join} WHERE {$base} AND {$not_done} AND (f.meta_id IS NULL OR CAST(f.meta_value AS UNSIGNED) < {$t})";
				return 0 < $limit ? $sql . ' ORDER BY p.ID DESC LIMIT ' . (int) $limit : "SELECT COUNT(*) FROM ({$sql}) pending_ids";
			default:
				return "SELECT COUNT(DISTINCT p.ID) {$from} WHERE {$base}";
		}
	}

	/** @return int[] */
	public static function pending_ids( $limit = self::BATCH ) {
		global $wpdb;
		return array_map( 'intval', (array) $wpdb->get_col( self::sql( 'pending', max( 1, (int) $limit ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array{total:int,done:int,failed:int,pending:int} */
	public static function counts() {
		global $wpdb;
		$count = static function ( $mode ) use ( $wpdb ) {
			return (int) $wpdb->get_var( self::sql( $mode ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		};
		return array(
			'total'   => $count( 'total' ),
			'done'    => $count( 'done' ),
			'failed'  => $count( 'failed' ),
			'pending' => $count( 'pending' ),
		);
	}

	/**
	 * Egy adag feldolgozása. Újrafuttatható: csak a kész-jelző nélküli képekkel
	 * foglalkozik (pending_ids), és minden kép előtt újra ellenőrzi, nem
	 * készítette-e el közben egy másik futás. Egyszerre egy köteg fut (zár).
	 *
	 * @param int           $limit     Legfeljebb ennyi kép.
	 * @param callable|null $processor Egy képet dolgozó függvény (teszthez cserélhető).
	 * @return array{done:int,failed:int,skipped:int,left:int,locked:bool}
	 */
	public static function run_batch( $limit = self::BATCH, $processor = null ) {
		$out = array(
			'done'    => 0,
			'failed'  => 0,
			'skipped' => 0,
			'left'    => 0,
			'locked'  => false,
		);
		if ( get_transient( self::LOCK ) ) {
			$out['locked'] = true;
			$out['left']   = self::counts()['pending'];
			return $out;
		}
		set_transient( self::LOCK, 1, 5 * MINUTE_IN_SECONDS );

		$processor = $processor ? $processor : array( __CLASS__, 'process_existing' );
		$started   = microtime( true );
		foreach ( self::pending_ids( $limit ) as $id ) {
			if ( microtime( true ) - $started > self::TIME_BUDGET ) {
				break;
			}
			if ( self::is_done( $id ) ) {
				++$out['skipped'];
				continue;
			}
			$ok = false;
			try {
				$ok = (bool) call_user_func( $processor, $id );
			} catch ( \Throwable $e ) {
				$ok = false;
			}
			if ( $ok ) {
				update_post_meta( $id, self::META_VERSION, self::VERSION );
				delete_post_meta( $id, self::META_FAIL );
				++$out['done'];
			} else {
				update_post_meta( $id, self::META_FAIL, (int) get_post_meta( $id, self::META_FAIL, true ) + 1 );
				++$out['failed'];
			}
		}

		delete_transient( self::LOCK );
		$out['left'] = self::counts()['pending'];
		return $out;
	}

	/**
	 * WP-Cron: óránként egy adag; ha maradt, két perc múlva újabb (egyszeri esemény),
	 * így a tárhelyet nem terheli, a kupac mégis hamar elfogy.
	 *
	 * @param string $follow 'follow' az utánkövető egyszeri eseménynél.
	 */
	public static function cron_run( $follow = '' ) {
		$result = self::run_batch( self::BATCH );
		if ( ! $result['locked'] && $result['left'] > 0 && ! wp_next_scheduled( self::HOOK, array( 'follow' ) ) ) {
			wp_schedule_single_event( time() + 120, self::HOOK, array( 'follow' ) );
		}
	}

	/* ------------------------------------------------------------------
	   Admin
	   ------------------------------------------------------------------ */

	public static function admin_menu() {
		add_submenu_page(
			'kmae-dashboard',
			__( 'Kártyaképek', 'ki-mit-adna-erte' ),
			__( 'Kártyaképek', 'ki-mit-adna-erte' ),
			'manage_options',
			'kmae-cards',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function handle_run() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kmae_cards_run' ) ) {
			wp_die( esc_html__( 'Nincs jogosultság.', 'ki-mit-adna-erte' ) );
		}
		$result = self::run_batch( self::BATCH );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'kmae-cards',
					'kmae_run' => $result['locked'] ? 'locked' : $result['done'] . '-' . $result['failed'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}
		$counts = self::counts();
		$pct    = $counts['total'] > 0 ? (int) floor( $counts['done'] / $counts['total'] * 100 ) : 100;
		$next   = wp_next_scheduled( self::HOOK );
		$ran    = isset( $_GET['kmae_run'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_run'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Kártyaképek (böngészés v2)', 'ki-mit-adna-erte' ); ?></h1>
			<?php if ( 'locked' === $ran ) : ?>
				<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Már fut egy feldolgozás (legfeljebb 5 percig tart a zár). Próbáld újra kicsit később.', 'ki-mit-adna-erte' ); ?></p></div>
			<?php elseif ( preg_match( '/^(\d+)-(\d+)$/', $ran, $m ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					/* translators: 1: processed count, 2: failed count */
					echo esc_html( sprintf( __( 'Feldolgozva: %1$d kép, hibás: %2$d.', 'ki-mit-adna-erte' ), (int) $m[1], (int) $m[2] ) );
					?>
				</p></div>
			<?php endif; ?>

			<p style="max-width:760px;"><?php esc_html_e( 'A böngészés v2 kártyáihoz minden hirdetésfotóhoz 3 kisebb méret (480 / 800 / 1200 px), háttérszín, hangulat és elmosott előkép készül. Az új fotókhoz feltöltéskor, a régiekhez itt és óránként a háttérben, adagonként 20 kép.', 'ki-mit-adna-erte' ); ?></p>

			<table class="widefat striped" style="max-width:520px;">
				<tbody>
					<tr><td><?php esc_html_e( 'Kész / összes kép', 'ki-mit-adna-erte' ); ?></td><td><strong><?php echo (int) $counts['done']; ?> / <?php echo (int) $counts['total']; ?></strong> (<?php echo (int) $pct; ?>%)</td></tr>
					<tr><td><?php esc_html_e( 'Még hátra van', 'ki-mit-adna-erte' ); ?></td><td><?php echo (int) $counts['pending']; ?></td></tr>
					<tr><td><?php esc_html_e( 'Hibás (3 próba után kihagyva)', 'ki-mit-adna-erte' ); ?></td><td><?php echo (int) $counts['failed']; ?></td></tr>
					<tr><td><?php esc_html_e( 'Következő automatikus futás', 'ki-mit-adna-erte' ); ?></td><td><?php echo $next ? esc_html( wp_date( 'Y.m.d. H:i', $next ) ) : '—'; ?></td></tr>
				</tbody>
			</table>
			<div style="max-width:520px;height:10px;margin:12px 0;background:#dcdcde;border-radius:5px;overflow:hidden;"><div style="width:<?php echo (int) $pct; ?>%;height:100%;background:#2271b1;"></div></div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kmae_cards_run' ); ?>
				<input type="hidden" name="action" value="kmae_cards_run" />
				<button type="submit" class="button button-primary" <?php disabled( 0 === $counts['pending'] ); ?>><?php esc_html_e( 'Következő 20 kép feldolgozása most', 'ki-mit-adna-erte' ); ?></button>
			</form>

			<?php $subject_left = self::subject_pending_count(); ?>
			<h2 style="margin-top:32px;"><?php esc_html_e( 'Autó-felismerés a régi fotókon', 'ki-mit-adna-erte' ); ?></h2>
			<p style="max-width:760px;"><?php esc_html_e( 'A böngésző kártyája ebből tudja, hol van az autó a fotón, így nagyításkor és a szélek beolvasztásakor nem vágja le. Az új fotóknál feltöltéskor magától megtörténik; a régieknél ez a gomb itt, a te böngésződben futtatja (az oldalt hagyd nyitva, amíg kész).', 'ki-mit-adna-erte' ); ?></p>
			<p><?php esc_html_e( 'Felismerésre vár:', 'ki-mit-adna-erte' ); ?> <strong data-kmae-subject-left><?php echo (int) $subject_left; ?></strong> <?php esc_html_e( 'kép', 'ki-mit-adna-erte' ); ?></p>
			<button type="button" class="button button-primary" data-kmae-subject-run <?php disabled( 0 === $subject_left ); ?>><?php esc_html_e( 'Autók felismerése most', 'ki-mit-adna-erte' ); ?></button>
			<span data-kmae-subject-status style="margin-left:10px;"></span>
			<script src="<?php echo esc_url( KMAE_PLUGIN_URL . 'assets/js/photo-subject.js?ver=' . KMAE_VERSION ); ?>"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
			<script>
			(function () {
				var btn = document.querySelector('[data-kmae-subject-run]');
				var left = document.querySelector('[data-kmae-subject-left]');
				var status = document.querySelector('[data-kmae-subject-status]');
				var ajax = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
				var nonce = <?php echo wp_json_encode( wp_create_nonce( 'kmae_subject' ) ); ?>;
				function post(data) {
					var body = new FormData();
					Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
					body.append('nonce', nonce);
					return fetch(ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); });
				}
				btn && btn.addEventListener('click', function () {
					btn.disabled = true;
					status.textContent = <?php echo wp_json_encode( __( 'Felismerő betöltése…', 'ki-mit-adna-erte' ) ); ?>;
					post({ action: 'kmae_subject_pending' }).then(function (res) {
						var items = (res && res.success && res.data) || [];
						var done = 0;
						return items.reduce(function (chain, item) {
							return chain.then(function () {
								return window.kmaePhotoSubject.detect(item.url);
							}).then(function (box) {
								return post({ action: 'kmae_subject_save', id: item.id, box: JSON.stringify(box) });
							}).then(function () {
								done++;
								left.textContent = Math.max(0, parseInt(left.textContent, 10) - 1);
								status.textContent = done + ' / ' + items.length;
							});
						}, Promise.resolve()).then(function () {
							status.textContent = <?php echo wp_json_encode( __( 'Kész.', 'ki-mit-adna-erte' ) ); ?> + ' (' + done + ')';
							btn.disabled = parseInt(left.textContent, 10) === 0;
						});
					}).catch(function () {
						status.textContent = <?php echo wp_json_encode( __( 'Hiba történt, próbáld újra.', 'ki-mit-adna-erte' ) ); ?>;
						btn.disabled = false;
					});
				});
			})();
			</script>
		</div>
		<?php
	}
}
