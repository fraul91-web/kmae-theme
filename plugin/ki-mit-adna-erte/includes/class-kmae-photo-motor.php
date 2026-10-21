<?php
/**
 * Listing photo trust motor — required/recommended slots + basic quality gates.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Photo_Motor {

	const META_SLOTS = '_kmae_photo_slots';
	const ATTACH_SLOT_META = '_kmae_photo_slot';
	const ATTACH_HASH_META = '_kmae_photo_hash';
	const ATTACH_AUTOFIX_META = '_kmae_photo_autofixed';

	/* --- Minőségi küszöbök (V2) -------------------------------------------
	   Vezérelv: inkább ne nyúljunk hozzá egy bizonytalan képhez, mint hogy
	   egy jó képet elrontsunk. Automatikusan csak akkor javítunk, ha a
	   mérések nagy bizonyossággal mondják, hogy javítható — és a javítás
	   után újra mérünk. Ha nem lett jobb, az eredetit állítjuk vissza.

	   Az átlagfényerő („mean") itt szándékosan nem szerepel: egy világos ég
	   alatt álló sötét autónál az átlag tökéletes lehet, miközben maga a
	   téma sötét. Helyette luminancia-hisztogramot és percentiliseket
	   mérünk, külön a teljes képre és külön a középső területre. */

	/** Az elemző munkapéldány leghosszabb éle. */
	const ANALYSIS_MAX_EDGE = 192;

	/** A „téma" területe: a kép középső hányada. */
	const CENTER_RATIO = 0.70;

	/** Luminancia-határok a hisztogram arányaihoz. */
	const SHADOW_CLIP_LUMA = 6;
	const DEEP_SHADOW_LUMA = 24;
	const BRIGHT_FRACTION_LUMA = 232;
	const HIGHLIGHT_CLIP_LUMA = 249;

	/** Sötét kép: mindhárom feltétel kell, hogy automatikusan javítsunk. */
	const DARK_CENTER_MEDIAN = 60;
	const DARK_CENTER_P90 = 170;
	const DARK_GLOBAL_MEDIAN = 90;

	/** Világos kép: szintén mindhárom feltétel kell. */
	const BRIGHT_CENTER_MEDIAN = 198;
	const BRIGHT_CENTER_P10 = 85;
	const BRIGHT_GLOBAL_MEDIAN = 180;

	/** Bebukott árnyék / kiégett csúcsfény: itt már nincs mit visszahozni. */
	const SHADOW_CLIPPED_MEDIAN = 35;
	const SHADOW_CLIPPED_FRACTION = 0.18;
	const HIGHLIGHT_CLIPPED_MEDIAN = 215;
	const HIGHLIGHT_CLIPPED_FRACTION = 0.18;

	/** Fakó kép: a p95 és p05 távolsága, de csak ép szélek mellett. */
	const LOW_CONTRAST_RANGE = 70;
	const CLIP_SAFE_FRACTION = 0.03;

	/** Erős elmosódás: az élesítés nem hozza vissza a hiányzó részletet. */
	const BLUR_HARD_EDGE = 3.0;

	/** E fölött már csak enyhe lágyságról van szó, azt élesítjük. */
	const SOFT_LIMIT = 7.0;

	/* Egy nagyon sötét kép természetesen kevés élkontrasztot mutat, ezért az
	   éles fotót is homályosnak hinnénk. Homályosságot csak normális
	   expozíciós tartományban ítélünk meg. */
	const BLUR_TEST_MIN_MEDIAN = 45;
	const BLUR_TEST_MAX_MEDIAN = 210;

	/** Méret: orientációfüggetlen — a 240×320 és a 320×240 egyenrangú. */
	const MIN_SHORT_EDGE = 240;
	const MIN_LONG_EDGE = 320;

	/* Ez alatt kicsinek jelezzük a képet, de NEM nagyítjuk fel: a felnagyítás
	   nem teremt részletet, csak nagyobb fájlt. */
	const SMALL_HINT_SHORT_EDGE = 720;

	/** Tónusgörbe-erősségek. A gamma 1 alatt világosít, fölötte sötétít. */
	const DARK_GAMMA_MAX = 0.78;
	const DARK_GAMMA_MIN = 0.95;
	const BRIGHT_GAMMA_MAX = 1.20;
	const BRIGHT_GAMMA_MIN = 1.05;

	/** A csúcsfény-védelem sávja: e fölött fokozatosan elhal a világosítás. */
	const HIGHLIGHT_GUARD_FROM = 170;
	const HIGHLIGHT_GUARD_TO = 245;

	/** Az árnyékvédelem sávja sötétítésnél. */
	const SHADOW_GUARD_TO = 85;

	/** Enyhe S-görbe a fakó képhez — nem HDR, csak egy leheletnyi. */
	const S_CURVE_STRENGTH = 0.18;

	/** Élesítés: ennyit kell javulnia az élességen, hogy megtartsuk. */
	const SHARPEN_EDGE_GAIN = 0.25;
	const SHARPEN_CLIP_TOLERANCE = 0.01;

	/** Visszaállítási szabályok: ennyivel kell javulnia, hogy megtartsuk. */
	const ROLLBACK_MEDIAN_GAIN = 5;
	const ROLLBACK_CLIP_TOLERANCE = 0.02;
	const ROLLBACK_RANGE_GAIN = 4;

	/* A korrekciót legfeljebb ekkora példányon végezzük. A galéria végleges
	   mérete úgyis KMAE_Images::MAX_EDGE (1920 px), tehát ezzel nem veszítünk
	   részletet — viszont a képpontonkénti tónusgörbe sokszorosan gyorsabb,
	   mint egy 12 megapixeles nyers fotón. */
	const CORRECT_MAX_EDGE = 1920;

	/** A javított munkapéldány minősége — a végleges WebP ezután készül. */
	const AUTOFIX_JPEG_QUALITY = 95;

	/** A mérési adatok a képhez mentve — későbbi kalibrációhoz. */
	const ATTACH_ANALYSIS_META = '_kmae_photo_analysis_v2';

	/**
	 * 7 kötelező nézet (diagram pontokkal).
	 *
	 * @return array<string, array{label:string,hint:string,required:bool,x:float,y:float}>
	 */
	public static function required_slots() {
		return array(
			'front_left'  => array(
				'label'    => __( 'Bal első', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Fotózd le az autót bal elölről.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 22,
				'y'        => 18,
			),
			'front_right' => array(
				'label'    => __( 'Jobb első', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Fotózd le az autót jobb elölről.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 78,
				'y'        => 18,
			),
			'rear_left'   => array(
				'label'    => __( 'Bal hátul', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Fotózd le az autót bal hátulról.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 22,
				'y'        => 82,
			),
			'rear_right'  => array(
				'label'    => __( 'Jobb hátul', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Fotózd le az autót jobb hátulról.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 78,
				'y'        => 82,
			),
			'side'        => array(
				'label'    => __( 'Oldalnézet', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Teljes oldalnézet — az autó egésze látszódjon.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 12,
				'y'        => 50,
			),
			'interior'    => array(
				'label'    => __( 'Belső tér', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Fotózd le a belső teret (ülések, műszerfal felől).', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 50,
				'y'        => 48,
			),
			'odometer'    => array(
				'label'    => __( 'Műszerfal / kilométeróra', 'ki-mit-adna-erte' ),
				'hint'     => __( 'A kilométeróra állása legyen jól olvasható.', 'ki-mit-adna-erte' ),
				'required' => true,
				'x'        => 50,
				'y'        => 32,
			),
		);
	}

	/**
	 * 6 ajánlott — összesen 13 fotópont.
	 *
	 * @return array<string, array{label:string,hint:string,required:bool,x:float,y:float}>
	 */
	public static function recommended_slots() {
		return array(
			'engine_bay'  => array(
				'label'    => __( 'Motorháztető alatt', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Motortér — tiszta rálátással.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 50,
				'y'        => 12,
			),
			'trunk'       => array(
				'label'    => __( 'Csomagtartó', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Nyitott csomagtartó.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 50,
				'y'        => 88,
			),
			'front_seats' => array(
				'label'    => __( 'Ülések', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Első / hátsó ülések állapota.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 58,
				'y'        => 55,
			),
			'wheels'      => array(
				'label'    => __( 'Felnik / gumik', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Kerék / felni és profil közelről.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 88,
				'y'        => 50,
			),
			'windshield'  => array(
				'label'    => __( 'Szélvédő', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Szélvédő állapota.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 50,
				'y'        => 24,
			),
			'damage'      => array(
				'label'    => __( 'Sérülések / hibák', 'ki-mit-adna-erte' ),
				'hint'     => __( 'Karc, horpadás, rozsda, fényezési hiba — ha van, mutasd meg.', 'ki-mit-adna-erte' ),
				'required' => false,
				'x'        => 30,
				'y'        => 65,
			),
		);
	}

	/**
	 * @return int
	 */
	public static function recommended_total_count() {
		return count( self::all_slots() );
	}

	/**
	 * @return array<string, array{label:string,hint:string,required:bool}>
	 */
	public static function all_slots() {
		return array_merge( self::required_slots(), self::recommended_slots() );
	}

	public static function required_count() {
		return count( self::required_slots() );
	}

	public static function is_enabled() {
		// A slotos autós fotósablon kikapcsolva: az általános galéria-feltöltés az egyetlen forrás.
		return false;
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return array<string, int> slot => attachment_id
	 */
	public static function get_slots( $listing_id ) {
		$raw = get_post_meta( $listing_id, self::META_SLOTS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out     = array();
		$allowed = array_keys( self::all_slots() );
		foreach ( $raw as $slot => $id ) {
			$slot = sanitize_key( $slot );
			$id   = absint( $id );
			if ( $id && in_array( $slot, $allowed, true ) ) {
				$out[ $slot ] = $id;
			}
		}
		return $out;
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return int
	 */
	public static function count_required_filled( $listing_id ) {
		$slots = self::get_slots( $listing_id );
		$n     = 0;
		foreach ( array_keys( self::required_slots() ) as $key ) {
			if ( ! empty( $slots[ $key ] ) ) {
				$n++;
			}
		}
		return $n;
	}

	/**
	 * Count required slot files in current request.
	 *
	 * @return int
	 */
	public static function count_required_uploads() {
		$n = 0;
		foreach ( array_keys( self::required_slots() ) as $slot ) {
			if ( self::has_upload_for_slot( $slot ) ) {
				$n++;
			}
		}
		return $n;
	}

	/**
	 * @param string $slot Slot key.
	 * @return bool
	 */
	public static function has_upload_for_slot( $slot ) {
		if ( empty( $_FILES['kmae_slot_photo']['name'][ $slot ] ) ) {
			return false;
		}
		$error = isset( $_FILES['kmae_slot_photo']['error'][ $slot ] ) ? (int) $_FILES['kmae_slot_photo']['error'][ $slot ] : UPLOAD_ERR_NO_FILE;
		return UPLOAD_ERR_OK === $error && ! empty( $_FILES['kmae_slot_photo']['tmp_name'][ $slot ] );
	}

	/**
	 * Whether the current request includes any slot photo upload.
	 *
	 * @return bool
	 */
	public static function has_any_upload() {
		foreach ( array_keys( self::all_slots() ) as $slot ) {
			if ( self::has_upload_for_slot( $slot ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate create/update photo requirements.
	 *
	 * @param int  $listing_id 0 on create.
	 * @param bool $is_new     New listing.
	 * @return true|WP_Error
	 */
	public static function validate_request( $listing_id = 0, $is_new = true ) {
		if ( ! self::is_enabled() ) {
			return true;
		}

		$existing = $listing_id ? self::get_slots( $listing_id ) : array();
		$missing  = array();

		foreach ( self::required_slots() as $key => $info ) {
			$has_new = self::has_upload_for_slot( $key );
			$has_old = ! empty( $existing[ $key ] );
			if ( ! $has_new && ! $has_old ) {
				$missing[] = $info['label'];
			}
		}

		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'photo_slots_required',
				sprintf(
					/* translators: %s: comma-separated slot labels */
					__( 'Még ezeket a kötelező fotókat kell feltöltened: %s', 'ki-mit-adna-erte' ),
					implode( ', ', $missing )
				),
				array( 'reasons' => $missing )
			);
		}

		unset( $is_new );
		return true;
	}

	/**
	 * Process slot uploads, rebuild gallery order.
	 *
	 * @param int   $post_id Listing ID.
	 * @param array $existing_gallery Unused when photo motor owns gallery.
	 * @return array<int>|WP_Error gallery attachment IDs
	 */
	public static function handle_slot_uploads( $post_id, $existing_gallery = array() ) {
		unset( $existing_gallery );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$slots   = self::get_slots( $post_id );
		$allowed = array_keys( self::all_slots() );
		$hashes  = array();
		$errors  = array();
		/* Amit gépileg kijavítottunk — ezt visszajelezzük a feltöltőnek. */
		$fixed   = array();

		foreach ( $slots as $slot => $aid ) {
			$hash = get_post_meta( $aid, self::ATTACH_HASH_META, true );
			if ( $hash ) {
				$hashes[ $hash ] = $aid;
			}
		}

		foreach ( $allowed as $slot ) {
			if ( ! self::has_upload_for_slot( $slot ) ) {
				continue;
			}

			$slot_label = self::all_slots()[ $slot ]['label'] ?? $slot;
			$tmp        = $_FILES['kmae_slot_photo']['tmp_name'][ $slot ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$quality    = self::analyze_file( $tmp );
			if ( is_wp_error( $quality ) ) {
				$errors[] = array(
					'slot'   => $slot_label,
					'reason' => $quality->get_error_message(),
					'advice' => self::fix_advice( $quality->get_error_code() ),
				);
				continue;
			}

			/* A javítás még az ideiglenes fájlon fut, a feltöltés előtt. A V2
			   motor minden lépés után újramér, és ha nem lett jobb a kép,
			   magától visszaáll az eredetire — ilyenkor az `applied` üres. */
			$correction = self::autocorrect_file(
				$tmp,
				$quality,
				(string) ( $_FILES['kmae_slot_photo']['type'][ $slot ] ?? '' ) // phpcs:ignore
			);
			$applied    = $correction['applied'];
			if ( ! empty( $applied ) ) {
				$fixed[] = array(
					'slot'  => $slot_label,
					'fixes' => self::fix_labels( $applied ),
				);
			}

			$hash = $quality['hash'] ?? '';
			if ( $hash && isset( $hashes[ $hash ] ) && ( empty( $slots[ $slot ] ) || (int) $hashes[ $hash ] !== (int) ( $slots[ $slot ] ?? 0 ) ) ) {
				$errors[] = array(
					'slot'   => $slot_label,
					'reason' => __( 'Ezt a fotót már feltöltötted egy másik helyre.', 'ki-mit-adna-erte' ),
					'advice' => __( 'Ide válassz egy másik fotót az autóról.', 'ki-mit-adna-erte' ),
				);
				continue;
			}

			$_FILES['kmae_single_slot'] = array(
				'name'     => $_FILES['kmae_slot_photo']['name'][ $slot ], // phpcs:ignore
				'type'     => $_FILES['kmae_slot_photo']['type'][ $slot ], // phpcs:ignore
				'tmp_name' => $tmp,
				'error'    => $_FILES['kmae_slot_photo']['error'][ $slot ], // phpcs:ignore
				'size'     => $_FILES['kmae_slot_photo']['size'][ $slot ], // phpcs:ignore
			);

			$attachment_id = media_handle_upload( 'kmae_single_slot', $post_id );
			if ( is_wp_error( $attachment_id ) ) {
				$errors[] = array(
					'slot'   => $slot_label,
					'reason' => __( 'Ezt a fotót nem sikerült feltölteni.', 'ki-mit-adna-erte' ),
					'advice' => __( 'Próbáld meg újra. Ha továbbra sem sikerül, válassz egy másik fotót.', 'ki-mit-adna-erte' ),
				);
				continue;
			}

			KMAE_Images::process_attachment( $attachment_id );
			update_post_meta( $attachment_id, self::ATTACH_SLOT_META, $slot );
			if ( ! empty( $applied ) ) {
				update_post_meta( $attachment_id, self::ATTACH_AUTOFIX_META, $applied );
			}
			update_post_meta( $attachment_id, self::ATTACH_ANALYSIS_META, self::analysis_record( $quality, $correction ) );
			if ( $hash ) {
				update_post_meta( $attachment_id, self::ATTACH_HASH_META, $hash );
				$hashes[ $hash ] = $attachment_id;
			}

			if ( ! empty( $slots[ $slot ] ) && (int) $slots[ $slot ] !== (int) $attachment_id ) {
				$old = (int) $slots[ $slot ];
				if ( self::attachment_belongs_to_listing( $old, $post_id ) ) {
					KMAE_Images::delete_attachment( $old );
				}
			}

			$slots[ $slot ] = (int) $attachment_id;
		}

		update_post_meta( $post_id, self::META_SLOTS, $slots );

		$gallery = self::slots_to_gallery( $slots );
		if ( ! empty( $gallery ) ) {
			update_post_meta( $post_id, '_kmae_gallery', $gallery );
			set_post_thumbnail( $post_id, $gallery[0] );
		}

		/* A javításokat akkor is jelezzük, ha egyébként minden rendben ment —
		   a feltöltő lássa, mi történt a képével. */
		if ( ! empty( $fixed ) ) {
			set_transient( self::autofix_notice_key(), $fixed, 5 * MINUTE_IN_SECONDS );
		}

		if ( ! empty( $errors ) ) {
			$lines = array();
			foreach ( $errors as $item ) {
				$lines[] = $item['slot'] . ': ' . $item['reason'];
			}
			return new WP_Error(
				'photo_quality_failed',
				implode( ' ', $lines ),
				array(
					'reasons' => $errors,
					'fixed'   => $fixed,
				)
			);
		}

		return $gallery;
	}

	/**
	 * A gépi javításokról szóló értesítés kulcsa — felhasználónként.
	 *
	 * @return string
	 */
	public static function autofix_notice_key() {
		return 'kmae_photo_autofix_' . get_current_user_id();
	}

	/**
	 * Egyszer olvasható értesítés az utolsó mentés gépi javításairól.
	 *
	 * @return array<int, array{slot:string,fixes:array<int,string>}>
	 */
	public static function take_autofix_notice() {
		$key  = self::autofix_notice_key();
		$data = get_transient( $key );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}
		delete_transient( $key );
		return $data;
	}

	/**
	 * Gyakorlati tanács a hibakódhoz — általános biztatás helyett konkrétum.
	 *
	 * @param string $code Hibakód.
	 * @return string
	 */
	public static function fix_advice( $code ) {
		switch ( $code ) {
			case 'photo_blurry':
				return __( 'Töröld meg a kamera lencséjét, tartsd biztosan a telefont, és várd meg, amíg a kép éles lesz.', 'ki-mit-adna-erte' );
			case 'photo_too_small':
				return __( 'Válassz egy nagyobb méretű fotót, vagy készíts egy újat a telefonoddal.', 'ki-mit-adna-erte' );
			case 'photo_unreadable':
			case 'photo_invalid':
				return __( 'Válassz egy másik fotót. JPG vagy PNG formátumú képet tudsz feltölteni.', 'ki-mit-adna-erte' );
			default:
				return '';
		}
	}

	/**
	 * @param array<string,int> $slots Slots map.
	 * @return array<int>
	 */
	public static function slots_to_gallery( $slots ) {
		$gallery = array();
		foreach ( array_keys( self::all_slots() ) as $key ) {
			if ( ! empty( $slots[ $key ] ) ) {
				$gallery[] = (int) $slots[ $key ];
			}
		}
		return array_values( array_unique( $gallery ) );
	}

	/**
	 * Basic quality analysis (brightness + blur proxy + perceptual hash).
	 *
	 * @param string $path File path.
	 * @return array{hash:string,brightness:float,variance:float}|WP_Error
	 */
	/**
	 * Képminőség-elemzés (V2).
	 *
	 * Nem átlagfényerőt néz, hanem luminancia-hisztogramot: percentiliseket,
	 * bebukott árnyék- és kiégett csúcsfény-arányt, dinamikatartományt —
	 * külön a teljes képre és külön a középső területre, mert autófotónál a
	 * téma sötét lehet úgy is, hogy a teljes kép átlaga tökéletes.
	 *
	 * @param string $path    Vizsgálandó fájl.
	 * @param string $profile Későbbi slot-profilok helye; most mindig generic.
	 * @return array|WP_Error
	 */
	public static function analyze_file( $path, $profile = 'generic' ) {
		if ( ! $path || ! file_exists( $path ) || ! function_exists( 'imagecreatefromstring' ) ) {
			return new WP_Error( 'photo_unreadable', __( 'Ezt a fotót nem tudjuk megnyitni.', 'ki-mit-adna-erte' ) );
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			return new WP_Error( 'photo_unreadable', __( 'Ezt a fotót nem tudjuk megnyitni.', 'ki-mit-adna-erte' ) );
		}

		$img = @imagecreatefromstring( $raw );
		if ( ! $img ) {
			return new WP_Error( 'photo_invalid', __( 'Ezt a fájlt nem tudjuk fotóként megnyitni.', 'ki-mit-adna-erte' ) );
		}

		$w = imagesx( $img );
		$h = imagesy( $img );

		/* Orientációfüggetlen alsó határ: a 240×320 álló kép pontosan annyi
		   információt hordoz, mint a 320×240 fekvő. */
		if ( min( $w, $h ) < self::MIN_SHORT_EDGE || max( $w, $h ) < self::MIN_LONG_EDGE ) {
			imagedestroy( $img );
			return new WP_Error( 'photo_too_small', __( 'Ez a kép túl kicsi, ezért rosszul mutatna a hirdetésben.', 'ki-mit-adna-erte' ) );
		}

		$measured = self::measure( $img );
		imagedestroy( $img );

		if ( ! $measured ) {
			return new WP_Error( 'photo_invalid', __( 'Ezt a fájlt nem tudjuk fotóként megnyitni.', 'ki-mit-adna-erte' ) );
		}

		$judged = self::classify( $measured, $w, $h, $profile );

		/* Az erős elmosódás az egyetlen, amit gépileg nem tudunk orvosolni:
		   az élesítés nem hozza vissza a nem létező részletet. */
		if ( in_array( 'blur', $judged['issues'], true ) ) {
			return new WP_Error( 'photo_blurry', __( 'Ez a fotó nem elég éles. Készíts helyette egy másikat.', 'ki-mit-adna-erte' ) );
		}

		return array(
			'version' => 2,
			'hash'    => $measured['hash'],
			'width'   => $w,
			'height'  => $h,
			'profile' => $profile,
			'stats'   => array(
				'global' => $measured['global'],
				'center' => $measured['center'],
				'edge'   => $measured['edge'],
			),
			'issues'  => $judged['issues'],
			'fixes'   => $judged['fixes'],
			'verdict' => $judged['verdict'],
		);
	}

	/**
	 * Egy megnyitott képről minden mérés egy helyen.
	 *
	 * Az expozíciót 192 képpontos példányon mérjük (ott a középső terület is
	 * értelmesen elkülönül), az élességet és a képujjlenyomatot viszont a
	 * korábbi 64×64-es mintán — így a homályossági küszöbök kalibrációja
	 * érvényben marad.
	 *
	 * @param resource|GdImage $img Kép.
	 * @return array|null
	 */
	private static function measure( $img ) {
		$w = imagesx( $img );
		$h = imagesy( $img );
		if ( $w < 1 || $h < 1 ) {
			return null;
		}

		$scale = min( 1.0, self::ANALYSIS_MAX_EDGE / max( $w, $h ) );
		$sw    = max( 1, (int) round( $w * $scale ) );
		$sh    = max( 1, (int) round( $h * $scale ) );

		$sample = imagecreatetruecolor( $sw, $sh );
		imagecopyresampled( $sample, $img, 0, 0, 0, 0, $sw, $sh, $w, $h );

		$luma = array();
		for ( $y = 0; $y < $sh; $y++ ) {
			$row = $y * $sw;
			for ( $x = 0; $x < $sw; $x++ ) {
				$rgb = imagecolorat( $sample, $x, $y );
				$luma[ $row + $x ] = (int) round(
					( 0.299 * ( ( $rgb >> 16 ) & 0xFF ) )
					+ ( 0.587 * ( ( $rgb >> 8 ) & 0xFF ) )
					+ ( 0.114 * ( $rgb & 0xFF ) )
				);
			}
		}
		imagedestroy( $sample );

		$cw = max( 1, (int) round( $sw * self::CENTER_RATIO ) );
		$ch = max( 1, (int) round( $sh * self::CENTER_RATIO ) );

		$fine = self::edge_and_hash( $img );

		return array(
			'global'   => self::region_stats( $luma, $sw, $sh, 0, 0, $sw, $sh ),
			'center'   => self::region_stats(
				$luma,
				$sw,
				$sh,
				(int) floor( ( $sw - $cw ) / 2 ),
				(int) floor( ( $sh - $ch ) / 2 ),
				$cw,
				$ch
			),
			'edge'     => $fine['edge'],
			'variance' => $fine['variance'],
			'hash'     => $fine['hash'],
		);
	}

	/**
	 * Egy képterület hisztogramja és az abból számolt mérőszámok.
	 *
	 * @param array<int, int> $luma Luminancia-mátrix.
	 * @param int             $sw   Minta szélessége.
	 * @param int             $sh   Minta magassága.
	 * @param int             $x0   Terület bal széle.
	 * @param int             $y0   Terület teteje.
	 * @param int             $rw   Terület szélessége.
	 * @param int             $rh   Terület magassága.
	 * @return array<string, float|int>
	 */
	private static function region_stats( array $luma, $sw, $sh, $x0, $y0, $rw, $rh ) {
		$hist  = array_fill( 0, 256, 0 );
		$total = 0;
		$sum   = 0;

		$y_max = min( $sh, $y0 + $rh );
		$x_max = min( $sw, $x0 + $rw );

		for ( $y = max( 0, $y0 ); $y < $y_max; $y++ ) {
			$row = $y * $sw;
			for ( $x = max( 0, $x0 ); $x < $x_max; $x++ ) {
				$v = $luma[ $row + $x ];
				$hist[ $v ]++;
				$sum += $v;
				$total++;
			}
		}

		if ( ! $total ) {
			return array(
				'p05'             => 0,
				'p10'             => 0,
				'p25'             => 0,
				'p50'             => 0,
				'p75'             => 0,
				'p90'             => 0,
				'p95'             => 0,
				'mean'            => 0.0,
				'shadow_clip'     => 0.0,
				'deep_shadow'     => 0.0,
				'bright_fraction' => 0.0,
				'highlight_clip'  => 0.0,
				'dynamic_range'   => 0,
			);
		}

		$shadow = 0;
		$deep   = 0;
		$bright = 0;
		$high   = 0;
		for ( $v = 0; $v <= 255; $v++ ) {
			$count = $hist[ $v ];
			if ( ! $count ) {
				continue;
			}
			if ( $v <= self::SHADOW_CLIP_LUMA ) {
				$shadow += $count;
			}
			if ( $v <= self::DEEP_SHADOW_LUMA ) {
				$deep += $count;
			}
			if ( $v >= self::BRIGHT_FRACTION_LUMA ) {
				$bright += $count;
			}
			if ( $v >= self::HIGHLIGHT_CLIP_LUMA ) {
				$high += $count;
			}
		}

		$p05 = self::percentile( $hist, $total, 0.05 );
		$p95 = self::percentile( $hist, $total, 0.95 );

		return array(
			'p05'             => $p05,
			'p10'             => self::percentile( $hist, $total, 0.10 ),
			'p25'             => self::percentile( $hist, $total, 0.25 ),
			'p50'             => self::percentile( $hist, $total, 0.50 ),
			'p75'             => self::percentile( $hist, $total, 0.75 ),
			'p90'             => self::percentile( $hist, $total, 0.90 ),
			'p95'             => $p95,
			'mean'            => $sum / $total,
			'shadow_clip'     => $shadow / $total,
			'deep_shadow'     => $deep / $total,
			'bright_fraction' => $bright / $total,
			'highlight_clip'  => $high / $total,
			'dynamic_range'   => $p95 - $p05,
		);
	}

	/**
	 * @param array<int, int> $hist  256 rekeszes hisztogram.
	 * @param int             $total Képpontok száma.
	 * @param float           $q     Keresett hányad (0–1).
	 * @return int
	 */
	private static function percentile( array $hist, $total, $q ) {
		$target = $q * $total;
		$acc    = 0;
		for ( $v = 0; $v <= 255; $v++ ) {
			$acc += $hist[ $v ];
			if ( $acc >= $target ) {
				return $v;
			}
		}
		return 255;
	}

	/**
	 * Élesség, szórás és képujjlenyomat a régi 64×64-es mintáról.
	 *
	 * @param resource|GdImage $img Kép.
	 * @return array{edge:float,variance:float,hash:string}
	 */
	private static function edge_and_hash( $img ) {
		$size = 64;
		$w    = imagesx( $img );
		$h    = imagesy( $img );

		$sample = imagecreatetruecolor( $size, $size );
		imagecopyresampled( $sample, $img, 0, 0, 0, 0, $size, $size, $w, $h );

		$gray   = array();
		$sum    = 0;
		$sum_sq = 0;
		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				$rgb  = imagecolorat( $sample, $x, $y );
				$luma = ( 0.299 * ( ( $rgb >> 16 ) & 0xFF ) )
					+ ( 0.587 * ( ( $rgb >> 8 ) & 0xFF ) )
					+ ( 0.114 * ( $rgb & 0xFF ) );
				$gray[] = $luma;
				$sum   += $luma;
				$sum_sq += $luma * $luma;
			}
		}
		imagedestroy( $sample );

		$n        = $size * $size;
		$mean     = $sum / $n;
		$variance = ( $sum_sq / $n ) - ( $mean * $mean );

		$edge = 0;
		$ec   = 0;
		for ( $y = 1; $y < $size - 1; $y++ ) {
			for ( $x = 1; $x < $size - 1; $x++ ) {
				$i     = ( $y * $size ) + $x;
				$edge += abs(
					( 4 * $gray[ $i ] )
					- $gray[ $i - 1 ]
					- $gray[ $i + 1 ]
					- $gray[ $i - $size ]
					- $gray[ $i + $size ]
				);
				$ec++;
			}
		}

		$hash = '';
		for ( $by = 0; $by < 8; $by++ ) {
			for ( $bx = 0; $bx < 8; $bx++ ) {
				$block = 0;
				for ( $yy = 0; $yy < 8; $yy++ ) {
					for ( $xx = 0; $xx < 8; $xx++ ) {
						$block += $gray[ ( ( ( $by * 8 ) + $yy ) * $size ) + ( ( $bx * 8 ) + $xx ) ];
					}
				}
				$hash .= ( $block / 64 ) > $mean ? '1' : '0';
			}
		}

		return array(
			'edge'     => $ec ? ( $edge / $ec ) : 0.0,
			'variance' => $variance,
			'hash'     => $hash,
		);
	}

	/**
	 * A mérésekből ítélet: mi a baj, mit javítunk, és mit üzenünk.
	 *
	 * @param array  $m       A measure() eredménye.
	 * @param int    $w       Eredeti szélesség.
	 * @param int    $h       Eredeti magasság.
	 * @param string $profile Profil (most generic).
	 * @return array{issues:array,fixes:array,verdict:string}
	 */
	private static function classify( array $m, $w, $h, $profile = 'generic' ) {
		unset( $profile );

		$g      = $m['global'];
		$c      = $m['center'];
		$issues = array();
		$fixes  = array();

		/* Egy nagyon sötét képen természetesen kevés az élkontraszt, ezért egy
		   éles fotót is homályosnak hinnénk. Élességet csak normális
		   expozíciós tartományban ítélünk meg. */
		$exposure_allows_blur = $c['p50'] >= self::BLUR_TEST_MIN_MEDIAN
			&& $c['p50'] <= self::BLUR_TEST_MAX_MEDIAN;

		if ( $exposure_allows_blur && $m['edge'] < self::BLUR_HARD_EDGE && $m['variance'] < 180 ) {
			return array(
				'issues'  => array( 'blur' ),
				'fixes'   => array(),
				'verdict' => 'stop',
			);
		}

		$verdict = 'ok';

		/* Sötét oldal. A „bebukott" eset erősebb, mint a „javítható": ahol a
		   képpontok ötöde feketébe fulladt, ott nincs mit visszahozni. */
		$shadow_clipped = $c['p50'] < self::SHADOW_CLIPPED_MEDIAN
			&& $c['shadow_clip'] > self::SHADOW_CLIPPED_FRACTION;

		$dark_recoverable = $c['p50'] < self::DARK_CENTER_MEDIAN
			&& $c['p90'] < self::DARK_CENTER_P90
			&& $g['p50'] < self::DARK_GLOBAL_MEDIAN;

		if ( $shadow_clipped ) {
			$issues[] = 'shadow_clipped';
			$verdict  = 'warn';
		} elseif ( $dark_recoverable ) {
			$issues[] = 'dark';
			$fixes[]  = 'tone_dark';
			$verdict  = 'fix';
		}

		/* Világos oldal — ugyanez tükrözve. */
		$highlight_clipped = $c['p50'] > self::HIGHLIGHT_CLIPPED_MEDIAN
			&& $c['highlight_clip'] > self::HIGHLIGHT_CLIPPED_FRACTION;

		$bright_recoverable = $c['p50'] > self::BRIGHT_CENTER_MEDIAN
			&& $c['p10'] > self::BRIGHT_CENTER_P10
			&& $g['p50'] > self::BRIGHT_GLOBAL_MEDIAN;

		if ( $highlight_clipped ) {
			$issues[] = 'highlight_clipped';
			$verdict  = 'warn';
		} elseif ( $bright_recoverable && ! in_array( 'tone_dark', $fixes, true ) ) {
			$issues[] = 'bright';
			$fixes[]  = 'tone_bright';
			if ( 'warn' !== $verdict ) {
				$verdict = 'fix';
			}
		}

		/* Fakó kép: a szórás helyett a tényleges dinamikatartomány dönt, és
		   csak akkor, ha egyik szélen sincs már eleve gond. */
		if ( $g['dynamic_range'] < self::LOW_CONTRAST_RANGE
			&& $g['shadow_clip'] < self::CLIP_SAFE_FRACTION
			&& $g['highlight_clip'] < self::CLIP_SAFE_FRACTION
		) {
			$issues[] = 'low_contrast';
			$fixes[]  = 'contrast';
			if ( 'warn' !== $verdict ) {
				$verdict = 'fix';
			}
		}

		if ( $exposure_allows_blur && $m['edge'] < self::SOFT_LIMIT ) {
			$issues[] = 'soft';
			$fixes[]  = 'soft';
			if ( 'warn' !== $verdict ) {
				$verdict = 'fix';
			}
		}

		/* A kis méret csak tájékoztatás: nagyítással nem lesz több részlet. */
		if ( min( $w, $h ) < self::SMALL_HINT_SHORT_EDGE ) {
			$issues[] = 'small';
		}

		/* A figyelmeztetés azt jelenti: van gond, de nem nyúlunk hozzá. Enélkül
		   egy bebukott árnyékú kép a kontraszt- vagy élesítés-javítást még
		   megkaphatná — az ellentmondana az ígéretnek. */
		if ( 'warn' === $verdict ) {
			$fixes = array();
		}

		return array(
			'issues'  => $issues,
			'fixes'   => $fixes,
			'verdict' => $verdict,
		);
	}

	/**
	 * Gépi képjavítás a feltöltés ideiglenes fájlján (V2).
	 *
	 * Minden szakasz után újramérünk, és csak akkor tartjuk meg a lépést, ha
	 * a számok szerint tényleg jobb lett. Ha nem, az eredeti marad.
	 *
	 * @param string       $path     Ideiglenes fájl útvonala.
	 * @param array        $analysis Az analyze_file() eredménye.
	 * @param string       $mime     A feltöltött fájl MIME-típusa.
	 * @return array{applied:array,before:array,after:array,reverted:bool}
	 */
	public static function autocorrect_file( $path, $analysis, $mime = '' ) {
		$empty = array(
			'applied'  => array(),
			'rejected' => array(),
			'before'   => array(),
			'after'    => array(),
			'reverted' => false,
		);

		/* Kapuőr: bármi is kerül a `fixes` tömbbe, csak a biztosan javítható
		   („fix") képhez nyúlunk. A figyelmeztetett és a rendben lévő kép
		   érintetlen marad. */
		if ( ! is_array( $analysis ) || empty( $analysis['verdict'] ) || 'fix' !== $analysis['verdict'] ) {
			return $empty;
		}

		$fixes = isset( $analysis['fixes'] ) && is_array( $analysis['fixes'] ) ? $analysis['fixes'] : array();

		if ( empty( $fixes ) || ! $path || ! file_exists( $path ) ) {
			return $empty;
		}
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return $empty;
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			return $empty;
		}

		$img = @imagecreatefromstring( $raw );
		if ( ! $img ) {
			return $empty;
		}

		/* A visszamentés formátumát a fájl TARTALMA döntse el, ne a böngésző
		   által küldött típus. */
		$detected = function_exists( 'getimagesizefromstring' ) ? @getimagesizefromstring( $raw ) : false;
		$type     = ( $detected && ! empty( $detected[2] ) ) ? (int) $detected[2] : 0;
		if ( ! $type ) {
			$type = ( 'image/png' === $mime ) ? IMAGETYPE_PNG : ( ( 'image/webp' === $mime ) ? IMAGETYPE_WEBP : IMAGETYPE_JPEG );
		}

		if ( function_exists( 'imageistruecolor' ) && ! imageistruecolor( $img ) && function_exists( 'imagepalettetotruecolor' ) ) {
			imagepalettetotruecolor( $img );
		}

		/* A tónusgörbe képpontonként dolgozik, ezért a munkapéldányt a galéria
		   végleges méretére vesszük vissza. Részletet nem veszítünk (a
		   KMAE_Images úgyis ekkorára méretez), viszont sokszor gyorsabb. */
		$w = imagesx( $img );
		$h = imagesy( $img );
		if ( max( $w, $h ) > self::CORRECT_MAX_EDGE && function_exists( 'imagescale' ) ) {
			$factor = self::CORRECT_MAX_EDGE / max( $w, $h );
			$scaled = @imagescale( $img, (int) round( $w * $factor ), (int) round( $h * $factor ) );
			if ( $scaled ) {
				imagedestroy( $img );
				$img = $scaled;
			}
		}

		$started  = microtime( true );
		$before   = self::measure( $img );
		$applied  = array();
		$rejected = array();

		if ( ! $before ) {
			imagedestroy( $img );
			return $empty;
		}

		/* 1. Expozíció — tónusgörbe, nem globális fényerő-eltolás. */
		if ( in_array( 'tone_dark', $fixes, true ) ) {
			$result = self::try_step( $img, self::build_dark_lut( $before['center']['p50'] ), $before, 'dark' );
			$img    = $result['image'];
			if ( $result['accepted'] ) {
				$applied[] = 'tone_dark';
			} else {
				$rejected[] = 'tone_dark';
			}
		} elseif ( in_array( 'tone_bright', $fixes, true ) ) {
			$result = self::try_step( $img, self::build_bright_lut( $before['center']['p50'] ), $before, 'bright' );
			$img    = $result['image'];
			if ( $result['accepted'] ) {
				$applied[] = 'tone_bright';
			} else {
				$rejected[] = 'tone_bright';
			}
		}

		/* 2. Kontraszt — csak az expozíció utáni ÚJ mérés alapján. */
		$current = self::measure( $img );
		if ( $current
			&& $current['global']['dynamic_range'] < self::LOW_CONTRAST_RANGE
			&& $current['global']['shadow_clip'] < self::CLIP_SAFE_FRACTION
			&& $current['global']['highlight_clip'] < self::CLIP_SAFE_FRACTION
		) {
			$result = self::try_step( $img, self::build_s_curve_lut(), $current, 'contrast' );
			$img    = $result['image'];
			if ( $result['accepted'] ) {
				$applied[] = 'contrast';
			} else {
				$rejected[] = 'contrast';
			}
			$current = self::measure( $img );
		}

		/* 3. Élesítés — sötét, zajos vagy frissen világosított képen soha. */
		if ( $current && in_array( 'soft', $fixes, true )
			&& function_exists( 'imageconvolution' )
			&& self::sharpen_allowed( $current, $applied )
		) {
			$result = self::try_sharpen_step( $img, $current );
			$img    = $result['image'];
			if ( $result['accepted'] ) {
				$applied[] = 'soft';
			} else {
				$rejected[] = 'soft';
			}
		}

		/* Ha semmit nem fogadtunk el, a fájlhoz hozzá sem nyúlunk. */
		if ( empty( $applied ) ) {
			imagedestroy( $img );
			return array(
				'applied'  => array(),
				'rejected' => $rejected,
				'before'   => self::compact_stats( $before ),
				'after'    => self::compact_stats( $before ),
				'reverted' => false,
			);
		}

		$after  = self::measure( $img );
		$work_w = imagesx( $img );
		$work_h = imagesy( $img );

		/* Sosem írunk közvetlenül az eredeti fájlra: előbb egy ideiglenes
		   fájlba mentünk, és csak ellenőrzés után cseréljük le. Ha a mentés
		   félbeszakad, az eredeti sértetlen marad. */
		$tmp_output = $path . '.kmae-v2-' . wp_generate_password( 8, false, false );

		$saved = false;
		if ( IMAGETYPE_PNG === $type && function_exists( 'imagepng' ) ) {
			imagealphablending( $img, false );
			imagesavealpha( $img, true );
			$saved = @imagepng( $img, $tmp_output, 2 );
		} elseif ( IMAGETYPE_WEBP === $type && function_exists( 'imagewebp' ) ) {
			imagealphablending( $img, false );
			imagesavealpha( $img, true );
			$saved = @imagewebp( $img, $tmp_output, self::AUTOFIX_JPEG_QUALITY );
		} elseif ( IMAGETYPE_GIF === $type && function_exists( 'imagegif' ) ) {
			$saved = @imagegif( $img, $tmp_output );
		} elseif ( function_exists( 'imagejpeg' ) ) {
			$saved = @imagejpeg( $img, $tmp_output, self::AUTOFIX_JPEG_QUALITY );
		}

		imagedestroy( $img );

		$usable = $saved
			&& file_exists( $tmp_output )
			&& filesize( $tmp_output ) > 0
			&& @getimagesize( $tmp_output );

		if ( ! $usable || ! @rename( $tmp_output, $path ) ) {
			if ( file_exists( $tmp_output ) ) {
				@unlink( $tmp_output );
			}

			/* Itt tényleg az egész eredmény elveszett: az eredeti kép maradt. */
			return array(
				'applied'  => array(),
				'rejected' => $rejected,
				'before'   => self::compact_stats( $before ),
				'after'    => self::compact_stats( $before ),
				'reverted' => true,
			);
		}

		/* Fejlesztői módban kiírjuk, mennyi ideig tartott — így valódi, 7–12
		   képes feltöltésnél látszik, kell-e még gyorsítani. */
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'KMAE Photo V2: %dx%d, applied=[%s], rejected=[%s], total=%d ms',
					$work_w,
					$work_h,
					implode( ',', $applied ),
					implode( ',', $rejected ),
					(int) round( ( microtime( true ) - $started ) * 1000 )
				)
			);
		}

		return array(
			'applied'  => $applied,
			'rejected' => $rejected,
			'before'   => self::compact_stats( $before ),
			'after'    => self::compact_stats( $after ),
			'reverted' => false,
		);
	}

	/**
	 * Élesítés másolaton, méréssel: csak akkor marad, ha tényleg élesebb lett
	 * és közben nem gyártott új kiégést vagy bebukást.
	 *
	 * @param resource|GdImage $img    Jelenlegi kép.
	 * @param array            $before A lépés előtti mérés.
	 * @return array{image:mixed,accepted:bool}
	 */
	private static function try_sharpen_step( $img, array $before ) {
		$candidate = self::clone_image( $img );
		if ( ! $candidate ) {
			return array(
				'image'    => $img,
				'accepted' => false,
			);
		}

		$kernel = array(
			array( 0.0, -0.10, 0.0 ),
			array( -0.10, 1.40, -0.10 ),
			array( 0.0, -0.10, 0.0 ),
		);

		if ( ! @imageconvolution( $candidate, $kernel, 1, 0 ) ) {
			imagedestroy( $candidate );
			return array(
				'image'    => $img,
				'accepted' => false,
			);
		}

		$after = self::measure( $candidate );

		$accepted = $after
			&& ( $after['edge'] - $before['edge'] ) >= self::SHARPEN_EDGE_GAIN
			&& $after['global']['highlight_clip'] <= $before['global']['highlight_clip'] + self::SHARPEN_CLIP_TOLERANCE
			&& $after['global']['shadow_clip'] <= $before['global']['shadow_clip'] + self::SHARPEN_CLIP_TOLERANCE;

		if ( ! $accepted ) {
			imagedestroy( $candidate );
			return array(
				'image'    => $img,
				'accepted' => false,
			);
		}

		imagedestroy( $img );
		return array(
			'image'    => $candidate,
			'accepted' => true,
		);
	}

	/**
	 * Egy korrekciós lépés másolaton, méréssel és döntéssel.
	 *
	 * @param resource|GdImage $img    Jelenlegi kép.
	 * @param array<int, int>  $lut    256 elemű tónusgörbe.
	 * @param array            $before A lépés előtti mérés.
	 * @param string           $kind   'dark' | 'bright' | 'contrast'.
	 * @return array{image:mixed,accepted:bool}
	 */
	private static function try_step( $img, array $lut, array $before, $kind ) {
		$candidate = self::clone_image( $img );
		if ( ! $candidate ) {
			return array(
				'image'    => $img,
				'accepted' => false,
			);
		}

		self::apply_luma_lut( $candidate, $lut );
		$after = self::measure( $candidate );

		if ( $after && self::step_improved( $before, $after, $kind ) ) {
			imagedestroy( $img );
			return array(
				'image'    => $candidate,
				'accepted' => true,
			);
		}

		imagedestroy( $candidate );
		return array(
			'image'    => $img,
			'accepted' => false,
		);
	}

	/**
	 * A korrekció csak akkor marad, ha a számok szerint tényleg javított.
	 *
	 * @param array  $before Előtte.
	 * @param array  $after  Utána.
	 * @param string $kind   Lépés típusa.
	 * @return bool
	 */
	private static function step_improved( array $before, array $after, $kind ) {
		$tol = self::ROLLBACK_CLIP_TOLERANCE;

		if ( 'dark' === $kind ) {
			return $after['center']['p50'] >= $before['center']['p50'] + self::ROLLBACK_MEDIAN_GAIN
				&& $after['center']['highlight_clip'] <= $before['center']['highlight_clip'] + $tol
				&& $after['global']['highlight_clip'] <= $before['global']['highlight_clip'] + $tol;
		}

		if ( 'bright' === $kind ) {
			return $after['center']['p50'] <= $before['center']['p50'] - self::ROLLBACK_MEDIAN_GAIN
				&& $after['center']['shadow_clip'] <= $before['center']['shadow_clip'] + $tol
				&& $after['global']['shadow_clip'] <= $before['global']['shadow_clip'] + $tol;
		}

		return $after['global']['dynamic_range'] >= $before['global']['dynamic_range'] + self::ROLLBACK_RANGE_GAIN
			&& $after['global']['shadow_clip'] <= $before['global']['shadow_clip'] + $tol
			&& $after['global']['highlight_clip'] <= $before['global']['highlight_clip'] + $tol;
	}

	/**
	 * Élesítés csak ott, ahol nem a zajt emeli ki.
	 *
	 * @param array              $stats   Aktuális mérés.
	 * @param array<int, string> $applied Eddig elfogadott lépések.
	 * @return bool
	 */
	private static function sharpen_allowed( array $stats, array $applied ) {
		if ( in_array( 'tone_dark', $applied, true ) ) {
			return false;
		}
		if ( $stats['center']['p50'] < self::BLUR_TEST_MIN_MEDIAN ) {
			return false;
		}
		if ( $stats['center']['deep_shadow'] > 0.35 ) {
			return false;
		}
		if ( $stats['center']['shadow_clip'] > 0.10 ) {
			return false;
		}
		return true;
	}

	/**
	 * Sötét kép tónusgörbéje: az árnyékokat és a középtónusokat emeli, a
	 * csúcsfényeket pedig egyre kevésbé bántja — így az ég nem ég ki.
	 *
	 * @param int $center_median A téma medián fényessége.
	 * @return array<int, int>
	 */
	private static function build_dark_lut( $center_median ) {
		$t     = max( 0.0, min( 1.0, $center_median / max( 1, self::DARK_CENTER_MEDIAN ) ) );
		$gamma = self::DARK_GAMMA_MAX + ( ( self::DARK_GAMMA_MIN - self::DARK_GAMMA_MAX ) * $t );

		$lut = array();
		for ( $i = 0; $i <= 255; $i++ ) {
			$lifted  = 255 * pow( $i / 255, $gamma );
			$guard   = self::highlight_guard( $i );
			$lut[ $i ] = (int) max( 0, min( 255, round( $i + ( ( $lifted - $i ) * $guard ) ) ) );
		}
		return $lut;
	}

	/**
	 * Túl világos kép: a közép- és világos tónusokat tömöríti, az árnyékokat
	 * lehetőleg érintetlenül hagyja.
	 *
	 * @param int $center_median A téma medián fényessége.
	 * @return array<int, int>
	 */
	private static function build_bright_lut( $center_median ) {
		$span  = max( 1, 255 - self::BRIGHT_CENTER_MEDIAN );
		$t     = max( 0.0, min( 1.0, ( $center_median - self::BRIGHT_CENTER_MEDIAN ) / $span ) );
		$gamma = self::BRIGHT_GAMMA_MIN + ( ( self::BRIGHT_GAMMA_MAX - self::BRIGHT_GAMMA_MIN ) * $t );

		$lut = array();
		for ( $i = 0; $i <= 255; $i++ ) {
			$pulled = 255 * pow( $i / 255, $gamma );
			$guard  = $i < self::SHADOW_GUARD_TO ? ( $i / self::SHADOW_GUARD_TO ) : 1.0;
			$lut[ $i ] = (int) max( 0, min( 255, round( $i + ( ( $pulled - $i ) * $guard ) ) ) );
		}
		return $lut;
	}

	/**
	 * Enyhe S-görbe a fakó képhez: középen ad egy kis tartást, a két végén
	 * fokozatosan elhal, hogy ne gyártson új bebukást vagy kiégést.
	 *
	 * @return array<int, int>
	 */
	private static function build_s_curve_lut() {
		$lut = array();
		for ( $i = 0; $i <= 255; $i++ ) {
			$n     = $i / 255;
			$fade  = 1 - abs( ( 2 * $n ) - 1 );
			$moved = $n + ( self::S_CURVE_STRENGTH * ( $n - 0.5 ) * $fade );
			$lut[ $i ] = (int) max( 0, min( 255, round( $moved * 255 ) ) );
		}
		return $lut;
	}

	/**
	 * A világosítás ereje 170 fölött fokozatosan elhal, 245 közelében nulla.
	 *
	 * @param int $value Luminancia.
	 * @return float
	 */
	private static function highlight_guard( $value ) {
		if ( $value <= self::HIGHLIGHT_GUARD_FROM ) {
			return 1.0;
		}
		if ( $value >= self::HIGHLIGHT_GUARD_TO ) {
			return 0.0;
		}
		$span = self::HIGHLIGHT_GUARD_TO - self::HIGHLIGHT_GUARD_FROM;
		return max( 0.0, 1.0 - ( ( $value - self::HIGHLIGHT_GUARD_FROM ) / $span ) );
	}

	/**
	 * A tónusgörbét a fényességre alkalmazzuk, és a különbséget adjuk a
	 * csatornákhoz — így a színek nem tolódnak el. A lépést a rendelkezésre
	 * álló fejtérhez igazítjuk, hogy ne keletkezzen új kiégés vagy bebukás.
	 *
	 * @param resource|GdImage $img Kép (helyben módosul).
	 * @param array<int, int>  $lut Tónusgörbe.
	 * @return void
	 */
	private static function apply_luma_lut( $img, array $lut ) {
		$w = imagesx( $img );
		$h = imagesy( $img );

		for ( $y = 0; $y < $h; $y++ ) {
			for ( $x = 0; $x < $w; $x++ ) {
				$rgb = imagecolorat( $img, $x, $y );
				$a   = ( $rgb >> 24 ) & 0x7F;
				$r   = ( $rgb >> 16 ) & 0xFF;
				$g   = ( $rgb >> 8 ) & 0xFF;
				$b   = $rgb & 0xFF;

				$luma  = (int) round( ( 0.299 * $r ) + ( 0.587 * $g ) + ( 0.114 * $b ) );
				$delta = $lut[ $luma ] - $luma;
				if ( 0 === $delta ) {
					continue;
				}

				if ( $delta > 0 ) {
					$headroom = 255 - max( $r, $g, $b );
					if ( $delta > $headroom ) {
						$delta = $headroom;
					}
				} else {
					$room = min( $r, $g, $b );
					if ( -$delta > $room ) {
						$delta = -$room;
					}
				}
				if ( 0 === $delta ) {
					continue;
				}

				/* Az átlátszóság megmarad: PNG/WebP feltöltésnél e nélkül a
				   képpont átlátszatlanná válna. */
				imagesetpixel(
					$img,
					$x,
					$y,
					( $a << 24 ) | ( ( $r + $delta ) << 16 ) | ( ( $g + $delta ) << 8 ) | ( $b + $delta )
				);
			}
		}
	}

	/**
	 * @param resource|GdImage $img Kép.
	 * @return resource|GdImage|null
	 */
	private static function clone_image( $img ) {
		$w    = imagesx( $img );
		$h    = imagesy( $img );
		$copy = imagecreatetruecolor( $w, $h );
		if ( ! $copy ) {
			return null;
		}

		imagealphablending( $copy, false );
		imagesavealpha( $copy, true );
		$transparent = imagecolorallocatealpha( $copy, 0, 0, 0, 127 );
		if ( false !== $transparent ) {
			imagefill( $copy, 0, 0, $transparent );
		}

		imagecopy( $copy, $img, 0, 0, 0, 0, $w, $h );
		return $copy;
	}

	/**
	 * A metába csak a lényeg kerül — a 256 rekeszes hisztogram nem.
	 *
	 * @param array|null $m Mérés.
	 * @return array
	 */
	private static function compact_stats( $m ) {
		if ( ! is_array( $m ) ) {
			return array();
		}

		$keep = static function ( $s ) {
			return array(
				'p05'            => $s['p05'],
				'p50'            => $s['p50'],
				'p90'            => $s['p90'],
				'p95'            => $s['p95'],
				'shadow_clip'    => round( $s['shadow_clip'], 4 ),
				'deep_shadow'    => round( $s['deep_shadow'], 4 ),
				'highlight_clip' => round( $s['highlight_clip'], 4 ),
				'dynamic_range'  => $s['dynamic_range'],
			);
		};

		return array(
			'global' => $keep( $m['global'] ),
			'center' => $keep( $m['center'] ),
			'edge'   => round( $m['edge'], 2 ),
		);
	}

	/**
	 * A képhez mentett mérési napló. Ha később az derül ki, hogy egy kép
	 * megítélése rossz volt, nem találgatunk: itt vannak a számok.
	 *
	 * @param array $analysis   Az analyze_file() eredménye.
	 * @param array $correction Az autocorrect_file() eredménye.
	 * @return array
	 */
	public static function analysis_record( $analysis, $correction ) {
		$analysis   = is_array( $analysis ) ? $analysis : array();
		$correction = is_array( $correction ) ? $correction : array();

		/* Ha nem volt javítás (rendben lévő vagy figyelmeztetett kép), a mért
		   állapot maga a kiindulás — így minden feltöltött képről van adat,
		   nem csak a javítottakról. Kalibrálni csak ebből lehet. */
		$baseline = ! empty( $analysis['stats'] ) ? self::compact_stats( $analysis['stats'] ) : array();

		return array(
			'engine_version' => 2,
			'profile'        => $analysis['profile'] ?? 'generic',
			'verdict'        => $analysis['verdict'] ?? '',
			'issues'         => $analysis['issues'] ?? array(),
			'before'         => ! empty( $correction['before'] ) ? $correction['before'] : $baseline,
			'after'          => ! empty( $correction['after'] ) ? $correction['after'] : $baseline,
			'applied'        => $correction['applied'] ?? array(),
			'rejected'       => $correction['rejected'] ?? array(),
			'reverted'       => ! empty( $correction['reverted'] ),
		);
	}

	/**
	 * A küszöbök egyetlen forrása a PHP — a böngésző innen kapja őket, hogy a
	 * két oldal soha ne mondhasson mást ugyanarról a képről.
	 *
	 * @return array<string, float|int>
	 */
	public static function client_config() {
		return array(
			'analysisMaxEdge'      => self::ANALYSIS_MAX_EDGE,
			'centerRatio'          => self::CENTER_RATIO,
			'shadowClipLuma'       => self::SHADOW_CLIP_LUMA,
			'deepShadowLuma'       => self::DEEP_SHADOW_LUMA,
			'brightFractionLuma'   => self::BRIGHT_FRACTION_LUMA,
			'highlightClipLuma'    => self::HIGHLIGHT_CLIP_LUMA,
			'darkCenterMedian'     => self::DARK_CENTER_MEDIAN,
			'darkCenterP90'        => self::DARK_CENTER_P90,
			'darkGlobalMedian'     => self::DARK_GLOBAL_MEDIAN,
			'brightCenterMedian'   => self::BRIGHT_CENTER_MEDIAN,
			'brightCenterP10'      => self::BRIGHT_CENTER_P10,
			'brightGlobalMedian'   => self::BRIGHT_GLOBAL_MEDIAN,
			'shadowClippedMedian'  => self::SHADOW_CLIPPED_MEDIAN,
			'shadowClippedFrac'    => self::SHADOW_CLIPPED_FRACTION,
			'highlightClippedMed'  => self::HIGHLIGHT_CLIPPED_MEDIAN,
			'highlightClippedFrac' => self::HIGHLIGHT_CLIPPED_FRACTION,
			'lowContrastRange'     => self::LOW_CONTRAST_RANGE,
			'clipSafeFraction'     => self::CLIP_SAFE_FRACTION,
			'blurHard'             => self::BLUR_HARD_EDGE,
			'blurSoft'             => self::SOFT_LIMIT,
			'blurTestMinMedian'    => self::BLUR_TEST_MIN_MEDIAN,
			'blurTestMaxMedian'    => self::BLUR_TEST_MAX_MEDIAN,
			'minShortEdge'         => self::MIN_SHORT_EDGE,
			'minLongEdge'          => self::MIN_LONG_EDGE,
			'smallHintShortEdge'   => self::SMALL_HINT_SHORT_EDGE,
		);
	}

	/**
	 * A javítások emberi nyelvű megfogalmazása a visszajelzéshez.
	 *
	 * @param array<int, string> $applied Elvégzett javítások.
	 * @return array<int, string>
	 */
	public static function fix_labels( $applied ) {
		$map = array(
			'tone_dark'   => __( 'kivilágosítottuk a fotót', 'ki-mit-adna-erte' ),
			'tone_bright' => __( 'javítottunk a fotó fényén', 'ki-mit-adna-erte' ),
			'contrast'    => __( 'élénkebbé tettük a fotót', 'ki-mit-adna-erte' ),
			'soft'        => __( 'élesítettünk a fotón', 'ki-mit-adna-erte' ),
		);

		$out = array();
		foreach ( $applied as $code ) {
			if ( isset( $map[ $code ] ) ) {
				$out[] = $map[ $code ];
			}
		}
		return $out;
	}

	private static function attachment_belongs_to_listing( $attachment_id, $post_id ) {
		$attachment = get_post( $attachment_id );
		return $attachment
			&& 'attachment' === $attachment->post_type
			&& (int) $attachment->post_parent === (int) $post_id;
	}
}
