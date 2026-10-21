<?php
/**
 * Upload processing: WebP conversion, resize, orientation meta.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Images {

	const MAX_EDGE = 1920;
	/* 82-ről 90-re: a kép a feltöltés után egyszer tömörül (a kliens 0,95-ös JPEG-je után
	   már nincs további lépés a mesterig); a sötét, zajos fotók 82-nél maszatosak voltak. */
	const WEBP_QUALITY = 90;

	/** Attachment meta: az autó doboza a fotón („x0,y0,x1,y1”, 0–1), a feltöltő böngészője ismeri fel (photo-subject.js). */
	const META_SUBJECT = '_kmae_subject';

	/**
	 * Feliratkozás a feltöltési folyamatra.
	 */
	public static function init() {
		add_filter( 'big_image_size_threshold', '__return_false' );

		/* A WordPress alapméretei 1024 (`large`) fölött abbahagyják, a mi
		   felső határunk viszont 1920 — a kettő között nincs fokozat, ezért a
		   `srcset` vagy túl kicsit, vagy a teljes 1920-at ajánlaná. Ez a méret
		   tölti ki a lyukat a galéria tipikus szélességénél. */
		add_image_size( 'kmae_gallery', 1400, 0, false );
	}

	/**
	 * A WordPress alapból minden nagy feltöltésből készít egy `-scaled`
	 * munkapéldányt, és MEGTARTJA mellette az érintetlen eredetit. Ezt a
	 * viselkedést kikapcsoljuk: így eredeti példány létre sem jön, nem csak
	 * utólag töröljük. A méretezést a `process_attachment()` amúgy is elvégzi
	 * (MAX_EDGE), tehát nem veszítünk semmit — csak a felesleges nagy fájlt.
	 *
	 * A `process_attachment()` a már meglévő, korábban feltöltött képeknél is
	 * takarít, ezért mindkét irányból védve vagyunk.
	 */

	public static function supports_webp() {
		if ( function_exists( 'imagewebp' ) ) {
			return true;
		}
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			$formats = Imagick::queryFormats( 'WEBP' );
			return is_array( $formats ) && in_array( 'WEBP', $formats, true );
		}
		return false;
	}

	public static function orientation_from_dimensions( $width, $height ) {
		$width  = (int) $width;
		$height = (int) $height;
		if ( $width <= 0 || $height <= 0 ) {
			return 'portrait';
		}
		$ratio = $width / $height;
		if ( $ratio > 1.15 ) {
			return 'landscape';
		}
		if ( $ratio < 0.87 ) {
			return 'portrait';
		}
		return 'square';
	}

	/**
	 * Attachment URL with fallbacks when intermediate sizes are missing.
	 */
	public static function get_url( $attachment_id, $size = 'medium' ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return '';
		}

		$sizes = array_unique( array( $size, 'large', 'medium_large', 'medium', 'full' ) );
		foreach ( $sizes as $try_size ) {
			$url = wp_get_attachment_image_url( $attachment_id, $try_size );
			if ( ! $url || ! self::attachment_url_is_valid( $url ) ) {
				continue;
			}

			if ( 'full' === $try_size ) {
				return $url;
			}

			$meta = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $meta['sizes'][ $try_size ]['file'] ) ) {
				return $url;
			}
		}

		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			return ( $url && self::attachment_url_is_valid( $url ) ) ? $url : '';
		}

		return '';
	}

	/**
	 * A reszponzív `srcset`/`sizes` attribútumok egy képhez.
	 *
	 * A böngésző így a doboz tényleges szélességéhez választ fájlt: kis
	 * kártyához nem tölti le az 1920 képpontos változatot, retina kijelzőn
	 * viszont a nagyobbat kéri. Ha a WordPress nem tud `srcset`-et adni
	 * (pl. régi képnél nincsenek méretek), üres tömböt kapunk vissza, és a
	 * hívó marad a sima `src`-nél — nincs hibás kimenet.
	 *
	 * @param int    $attachment_id Kép azonosítója.
	 * @param string $sizes         A `sizes` attribútum értéke.
	 * @param string $size          Melyik méretre számoljon.
	 * @return array{srcset:string,sizes:string}
	 */
	public static function responsive_attrs( $attachment_id, $sizes, $size = 'full' ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id || ! function_exists( 'wp_get_attachment_image_srcset' ) ) {
			return array( 'srcset' => '', 'sizes' => '' );
		}

		/* A kmae_card_* méretek a böngészés v2-é: a régi kártyák és a galéria srcset-je
		   ugyanaz marad, mint előtte. */
		add_filter( 'wp_calculate_image_srcset_meta', array( __CLASS__, 'hide_card_sizes' ) );
		$srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
		remove_filter( 'wp_calculate_image_srcset_meta', array( __CLASS__, 'hide_card_sizes' ) );
		if ( ! $srcset ) {
			return array( 'srcset' => '', 'sizes' => '' );
		}

		return array(
			'srcset' => $srcset,
			'sizes'  => $sizes,
		);
	}

	/**
	 * @param array|mixed $image_meta Attachment metaadat.
	 * @return array|mixed
	 */
	public static function hide_card_sizes( $image_meta ) {
		if ( is_array( $image_meta ) && ! empty( $image_meta['sizes'] ) ) {
			foreach ( array_keys( KMAE_Image_Cards::SIZES ) as $key ) {
				unset( $image_meta['sizes'][ $key ] );
			}
		}
		return $image_meta;
	}

	/**
	 * A böngészés v2 kártyáinak képei egy fotóhoz.
	 *
	 * s/m/l = 480 / 800 / 1200 px széles URL. Hiányzó méretnél (pl. a forrás kisebb a
	 * méretnél, vagy a régi kép még nincs feldolgozva) a meglévő legjobb jön:
	 * előbb a nagyobb kártyaméret, majd a kmae_gallery (1400), a teljes kép, végül a kisebbek.
	 * w/h a mester (teljes kép) mérete — az arány az elrendezés ugrálása nélkül kiszámolható.
	 * ambient/tone/lqip a feltöltéskor (vagy az utólagos köteggel) elkészült meta, különben ''.
	 *
	 * @param int $attachment_id Kép azonosítója.
	 * @return array{s:string,m:string,l:string,w:int,h:int,ambient:string,tone:string,lqip:string}
	 */
	public static function card_sources( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$out           = array(
			's'       => '',
			'm'       => '',
			'l'       => '',
			'w'       => 0,
			'h'       => 0,
			'ambient' => '',
			'tone'    => '',
			'lqip'    => '',
			'subject' => array(),
		);
		if ( ! $attachment_id ) {
			return $out;
		}

		$meta  = wp_get_attachment_metadata( $attachment_id );
		$meta  = is_array( $meta ) ? $meta : array();
		$chain = array(
			's' => array( 'kmae_card_s', 'kmae_card_m', 'kmae_card_l', 'kmae_gallery', 'full' ),
			'm' => array( 'kmae_card_m', 'kmae_card_l', 'kmae_gallery', 'full', 'kmae_card_s' ),
			'l' => array( 'kmae_card_l', 'kmae_gallery', 'full', 'kmae_card_m', 'kmae_card_s' ),
		);
		foreach ( $chain as $slot => $keys ) {
			foreach ( $keys as $key ) {
				$url = self::sized_url( $attachment_id, $meta, $key );
				if ( '' !== $url ) {
					$out[ $slot ] = $url;
					break;
				}
			}
		}

		$out['w'] = ! empty( $meta['width'] ) ? (int) $meta['width'] : 0;
		$out['h'] = ! empty( $meta['height'] ) ? (int) $meta['height'] : 0;

		$ambient = (string) get_post_meta( $attachment_id, KMAE_Image_Cards::META_AMBIENT, true );
		$tone    = (string) get_post_meta( $attachment_id, KMAE_Image_Cards::META_TONE, true );
		$lqip    = (string) get_post_meta( $attachment_id, KMAE_Image_Cards::META_LQIP, true );
		if ( preg_match( '/^#[0-9a-f]{6}$/', $ambient ) ) {
			$out['ambient'] = $ambient;
		}
		if ( in_array( $tone, array( 'warm', 'cool' ), true ) ) {
			$out['tone'] = $tone;
		}
		if ( 0 === strpos( $lqip, KMAE_Image_Cards::LQIP_PREFIX ) && strlen( $lqip ) <= KMAE_Image_Cards::LQIP_MAX_BYTES ) {
			$out['lqip'] = $lqip;
		}
		$out['subject'] = self::parse_subject( get_post_meta( $attachment_id, self::META_SUBJECT, true ) );
		return $out;
	}

	/**
	 * Az autó doboza a fotón (photo-subject.js): „x0,y0,x1,y1” 0–1 között → 4 szám, vagy üres tömb.
	 *
	 * @param mixed $raw Meta érték vagy beküldött tömb.
	 * @return float[]
	 */
	public static function parse_subject( $raw ) {
		$parts = is_array( $raw ) ? array_values( $raw ) : explode( ',', (string) $raw );
		if ( 4 !== count( $parts ) ) {
			return array();
		}
		$box = array();
		foreach ( $parts as $part ) {
			if ( ! is_numeric( $part ) ) {
				return array();
			}
			$box[] = round( min( 1, max( 0, (float) $part ) ), 3 );
		}
		if ( $box[2] - $box[0] < 0.05 || $box[3] - $box[1] < 0.05 ) {
			return array();
		}
		return $box;
	}

	/**
	 * A feltöltéskor felismert autó-doboz mentése a képhez.
	 *
	 * @param int   $attachment_id Kép.
	 * @param mixed $raw           [x0, y0, x1, y1] vagy null.
	 */
	public static function save_subject( $attachment_id, $raw ) {
		$box = self::parse_subject( $raw );
		if ( $box ) {
			update_post_meta( (int) $attachment_id, self::META_SUBJECT, implode( ',', $box ) );
		}
	}

	/**
	 * Egy méret URL-je a metaadatból ('full' = a mester), csak ha a fájl tényleg megvan.
	 */
	private static function sized_url( $attachment_id, array $meta, $key ) {
		$full = wp_get_attachment_url( $attachment_id );
		if ( ! $full ) {
			return '';
		}
		if ( 'full' === $key ) {
			$url = $full;
		} elseif ( ! empty( $meta['sizes'][ $key ]['file'] ) ) {
			$url = trailingslashit( dirname( $full ) ) . wp_basename( $meta['sizes'][ $key ]['file'] );
		} else {
			return '';
		}
		return self::attachment_url_is_valid( $url ) ? $url : '';
	}

	private static function attachment_url_is_valid( $url ) {
		if ( ! self::attachment_url_exists( $url ) ) {
			return false;
		}

		$upload = wp_upload_dir();
		if ( empty( $upload['baseurl'] ) || empty( $upload['basedir'] ) ) {
			return true;
		}
		if ( 0 !== strpos( $url, $upload['baseurl'] ) ) {
			return true;
		}

		$path = $upload['basedir'] . substr( $url, strlen( $upload['baseurl'] ) );
		$size = @filesize( $path );
		return false !== $size && $size > 256;
	}

	private static function attachment_url_exists( $url ) {
		$upload = wp_upload_dir();
		if ( empty( $upload['baseurl'] ) || empty( $upload['basedir'] ) ) {
			return true;
		}
		if ( 0 !== strpos( $url, $upload['baseurl'] ) ) {
			return true;
		}
		$path = $upload['basedir'] . substr( $url, strlen( $upload['baseurl'] ) );
		return file_exists( $path );
	}

	public static function get_orientation( $attachment_id ) {
		$stored = get_post_meta( $attachment_id, '_kmae_orientation', true );
		if ( $stored && in_array( $stored, array( 'portrait', 'landscape', 'square' ), true ) ) {
			return $stored;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			return self::orientation_from_dimensions( $meta['width'], $meta['height'] );
		}

		return 'portrait';
	}

	/**
	 * Optimize uploaded image: resize, WebP, orientation meta.
	 */
	public static function process_attachment( $attachment_id ) {
		return self::process_attachment_inner( $attachment_id );
	}

	/**
	 * Mutat-e másik kép bejegyzése ugyanarra a fájlra?
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public static function file_is_shared( $attachment_id ) {
		global $wpdb;
		$path = (string) get_post_meta( (int) $attachment_id, '_wp_attached_file', true );
		if ( '' === $path ) {
			return false;
		}
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s AND post_id != %d LIMIT 1",
				$path,
				(int) $attachment_id
			)
		);
	}

	/**
	 * Kép törlése úgy, hogy közös fájlhoz ne nyúljon: ha másik bejegyzés ugyanarra a
	 * fájlra mutat (régebbi, azonos nevű feltöltések ütközése miatt), csak a
	 * bejegyzés törlődik — a fájl nem, így a másik hirdetés képe megmarad.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed wp_delete_attachment() eredménye.
	 */
	public static function delete_attachment( $attachment_id ) {
		$shared = self::file_is_shared( $attachment_id );
		if ( $shared ) {
			add_filter( 'wp_delete_file', '__return_empty_string' );
		}
		$result = wp_delete_attachment( (int) $attachment_id, true );
		if ( $shared ) {
			remove_filter( 'wp_delete_file', '__return_empty_string' );
		}
		return $result;
	}

	private static function process_attachment_inner( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$mime = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime, array( 'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif' ), true ) ) {
			return;
		}

		// Skip animated GIF.
		if ( 'image/gif' === $mime ) {
			$meta = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				update_post_meta(
					$attachment_id,
					'_kmae_orientation',
					self::orientation_from_dimensions( $meta['width'], $meta['height'] )
				);
			}
			return;
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return;
		}

		$size = $editor->get_size();
		if ( is_wp_error( $size ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
			return;
		}

		update_post_meta(
			$attachment_id,
			'_kmae_orientation',
			self::orientation_from_dimensions( $size['width'], $size['height'] )
		);

		if ( $size['width'] > self::MAX_EDGE || $size['height'] > self::MAX_EDGE ) {
			$editor->resize( self::MAX_EDGE, self::MAX_EDGE, false );
		}

		$editor->set_quality( self::WEBP_QUALITY );

		$original_file = $file;
		$convert_webp  = self::supports_webp() && 'image/webp' !== $mime;
		$out_mime      = self::supports_webp() ? 'image/webp' : $mime;

		if ( $convert_webp ) {
			/* Egyedi név: az eredeti (pl. image.jpg) az átalakítás után törlődik, így a
			   következő, azonos nevű feltöltést a WordPress újra „image.jpg”-nek
			   nevezné — és a WebP (image.webp) felülírná az ELŐZŐ hirdetés képét.
			   Ezért a WebP neve soha nem egyezhet meglévő fájléval.
			   Véletlen toldalék is kell: egy korábban törölt, azonos nevű fájl URL-jére a
			   Cloudflare 404-et gyorsítótárazhatott (31 nap), és az az új képet is „eltüntetné”
			   (hard refreshnél csak szürke paca marad). Az új név sosem járt még 404-ben. */
			$webp_dir  = dirname( $file );
			$webp_base = preg_replace( '/\.[^.]+$/', '', wp_basename( $file ) );
			$webp_name = wp_unique_filename( $webp_dir, $webp_base . '-' . strtolower( wp_generate_password( 6, false, false ) ) . '.webp' );
			$webp_file = $webp_dir . '/' . $webp_name;
			$saved     = $editor->save( $webp_file, 'image/webp' );
		} else {
			$saved = $editor->save( $file );
		}

		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			return;
		}

		$new_file = $saved['path'];

		$base_dir = dirname( $original_file );
		$old_meta = wp_get_attachment_metadata( $attachment_id );

		/* Böngészés v2 kártyaméretek + ambient/hangulat/LQIP. Még az eredeti törlése előtt,
		   a már betöltött szerkesztőből (a pixelek újratöltése és újratömörítés nélkül).
		   Csak hirdetéshez tartozó képnél (a chat-képnek post_parent = 0). */
		$cards = null;
		if ( (int) get_post_field( 'post_parent', $attachment_id ) > 0 ) {
			wp_raise_memory_limit( 'image' );
			$cards = KMAE_Image_Cards::build_from_editor( $editor, dirname( $new_file ), preg_replace( '/\.[^.]+$/', '', wp_basename( $new_file ) ), $out_mime );
		}

		if ( $convert_webp && $new_file !== $original_file && file_exists( $original_file ) ) {
			if ( ! empty( $old_meta['sizes'] ) ) {
				foreach ( $old_meta['sizes'] as $size_data ) {
					if ( ! empty( $size_data['file'] ) ) {
						wp_delete_file( $base_dir . '/' . $size_data['file'] );
					}
				}
			}
			wp_delete_file( $original_file );
		}

		/* A WordPress a nagy feltöltésből egy `-scaled` munkapéldányt készít, és
		   az ÉRINTETLEN eredetit külön megtartja (`original_image`). A
		   `get_attached_file()` a munkapéldányt adja vissza, ezért az eredeti
		   eddig a tárhelyen maradt — pedig épp az a legnagyobb fájl. Ezt is
		   töröljük, hogy tényleg ne maradjon nyoma. */
		if ( ! empty( $old_meta['original_image'] ) ) {
			$stashed = $base_dir . '/' . wp_basename( $old_meta['original_image'] );
			if ( $stashed !== $new_file && file_exists( $stashed ) ) {
				wp_delete_file( $stashed );
			}
		}

		update_attached_file( $attachment_id, $new_file );

		if ( $convert_webp ) {
			wp_update_post(
				array(
					'ID'             => $attachment_id,
					'post_mime_type' => 'image/webp',
				)
			);
		}

		/* A WordPress-méretek (medium, large, kmae_gallery…) a 90-es mesterből készülnek,
		   ezért az alapértelmezett 82/86 helyett itt is 90-es minőséget kapnak. */
		add_filter( 'wp_editor_set_quality', array( __CLASS__, 'filter_quality' ) );
		$metadata = wp_generate_attachment_metadata( $attachment_id, $new_file );
		remove_filter( 'wp_editor_set_quality', array( __CLASS__, 'filter_quality' ) );
		if ( ! is_wp_error( $metadata ) && $metadata ) {
			/* Az eredeti fájl már nincs meg, ezért a rá mutató bejegyzés is
			   kikerül — különben a WordPress egy nem létező fájlt keresne. */
			unset( $metadata['original_image'] );
			if ( $cards ) {
				KMAE_Image_Cards::delete_recorded( $old_meta, $base_dir );
				KMAE_Image_Cards::merge( $metadata, $cards );
			}
			wp_update_attachment_metadata( $attachment_id, $metadata );
			if ( $cards ) {
				KMAE_Image_Cards::save_meta( $attachment_id, $cards );
			}
		} elseif ( $cards ) {
			KMAE_Image_Cards::delete_files( $cards['files'] );
		}
	}

	public static function filter_quality() {
		return self::WEBP_QUALITY;
	}
}
