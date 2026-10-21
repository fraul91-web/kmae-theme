<?php
/**
 * Vehicle-specific listing fields (car launch).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Vehicle {

	const META_BRAND         = '_kmae_vehicle_brand';
	const META_MODEL         = '_kmae_vehicle_model';
	const META_YEAR          = '_kmae_vehicle_year';
	const META_MILEAGE       = '_kmae_vehicle_mileage';
	const META_FUEL          = '_kmae_vehicle_fuel';
	const META_TRANSMISSION  = '_kmae_vehicle_transmission';
	const META_BODY          = '_kmae_vehicle_body';
	const META_COLOR         = '_kmae_vehicle_color';
	const META_OWNERS        = '_kmae_vehicle_owners';
	const META_POWER_KW      = '_kmae_vehicle_power_kw';
	const META_ENGINE_CC     = '_kmae_vehicle_engine_cc';
	const META_MOT           = '_kmae_vehicle_mot';
	const META_VALUE         = '_kmae_vehicle_value';
	const META_TRAITS        = '_kmae_vehicle_traits';

	/** Legfeljebb ennyi jellemzőt lehet kiválasztani. */
	const MAX_TRAITS = 3;

	const BROWSE_KP          = 'kp';
	const BROWSE_AJANLAT     = 'ajanlat';

	public static function is_vehicle_launch() {
		return KMAE_Launch::is_single_category() && 'jarmu' === KMAE_Launch::category_slug();
	}

	public static function fuel_labels() {
		return array(
			'benzin'    => __( 'Benzin', 'ki-mit-adna-erte' ),
			'diesel'    => __( 'Dízel', 'ki-mit-adna-erte' ),
			'hybrid'    => __( 'Hibrid', 'ki-mit-adna-erte' ),
			'electric'  => __( 'Elektromos', 'ki-mit-adna-erte' ),
			'lpg'       => __( 'LPG / Gáz', 'ki-mit-adna-erte' ),
			'other'     => __( 'Egyéb', 'ki-mit-adna-erte' ),
		);
	}

	public static function transmission_labels() {
		return array(
			'manual'    => __( 'Manuális', 'ki-mit-adna-erte' ),
			'automatic' => __( 'Automata', 'ki-mit-adna-erte' ),
		);
	}

	public static function body_labels() {
		$labels = array();
		foreach ( self::body_types() as $slug => $info ) {
			$labels[ $slug ] = $info['label'];
		}
		return $labels;
	}

	/**
	 * Body types that have a photo card. The simple-edit select, admin select
	 * and browse filters all use this list.
	 *
	 * @return array<string, array{label:string}>
	 */
	public static function body_types() {
		$types = array();
		foreach ( self::browse_body_cards() as $slug => $info ) {
			$types[ $slug ] = array( 'label' => $info['label'] );
		}
		return $types;
	}

	/**
	 * Older body slugs that no longer have their own card. They count as Egyéb.
	 *
	 * @return string[]
	 */
	public static function legacy_bodies() {
		return array( 'crossover', 'roadster', 'limousine', 'sport', 'classic', 'caravan', 'micro' );
	}

	/**
	 * @param mixed $slug Stored or posted body slug.
	 * @return string Known slug, `other` for a retired slug, or empty.
	 */
	public static function normalize_body( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( isset( self::body_types()[ $slug ] ) ) {
			return $slug;
		}
		if ( in_array( $slug, self::legacy_bodies(), true ) ) {
			return 'other';
		}
		return '';
	}

	/**
	 * When the search asks for Egyéb, include the retired slugs too.
	 *
	 * @param string[] $bodies
	 * @return string[]
	 */
	public static function expand_body_query( array $bodies ) {
		if ( ! in_array( 'other', $bodies, true ) ) {
			return $bodies;
		}
		return array_values( array_unique( array_merge( $bodies, self::legacy_bodies() ) ) );
	}

	/**
	 * A keresés kivitel-lépésének kártyái: ugyanazok a kiviteltípusok és képek,
	 * mint a hirdetésfeladás wizardjában (listing-wizard.js, BODY_CARD_PHOTOS).
	 *
	 * @return array<string, array{label: string, photo: string}>
	 */
	public static function browse_body_cards() {
		return array(
			'sedan'     => array( 'label' => __( 'Szedán', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/sedan.webp' ),
			'estate'    => array( 'label' => __( 'Kombi', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/kombi.webp' ),
			'hatchback' => array( 'label' => __( 'Ferdehátú', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/csapott.webp' ),
			'suv'       => array( 'label' => __( 'SUV', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/suv.webp' ),
			'coupe'     => array( 'label' => __( 'Kupé', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/coupe.webp' ),
			'van'       => array( 'label' => __( 'Kisbusz/Van', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/3-5es.webp' ),
			'cabrio'    => array( 'label' => __( 'Cabrio', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/caprio.webp' ),
			'mpv'       => array( 'label' => __( 'Egyterű', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/egyteru.webp' ),
			'pickup'    => array( 'label' => __( 'Pickup', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/pickup.webp' ),
			'offroad'   => array( 'label' => __( 'Terepjáró', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/terepjaro.webp' ),
			'other'     => array( 'label' => __( 'Egyéb', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/egyeb.webp' ),
		);
	}

	public static function browse_mode_labels() {
		return array(
			self::BROWSE_KP      => __( 'Készpénz', 'ki-mit-adna-erte' ),
			self::BROWSE_AJANLAT => __( 'Ki mit adna érte?', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Cash amount used for browse kp filtering and legacy displays.
	 *
	 * Prefers stored `_kmae_vehicle_value`; when empty, falls back to the seller's
	 * public fixed/guide price so browse and cards stay aligned with sale mode.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int Whole-number amount in HUF; 0 when there is no public cash price.
	 */
	public static function get_vehicle_value( $listing_id ) {
		$stored = (int) get_post_meta( $listing_id, self::META_VALUE, true );
		if ( $stored > 0 ) {
			return $stored;
		}

		if ( class_exists( 'KMAE_Seller' ) ) {
			return (int) KMAE_Seller::get_public_price( $listing_id );
		}

		return 0;
	}

	public static function format_value( $amount ) {
		if ( $amount <= 0 ) {
			return '';
		}
		return number_format( $amount, 0, ',', ' ' ) . ' Ft';
	}

	/**
	 * Az autó jellemzői. Legfeljebb három választható belőlük — ezek nem
	 * mérhető adatok, hanem az eladó saját kiemelései.
	 *
	 * @return array<string, array{label:string,icon:string}>
	 */
	public static function traits() {
		return array(
			'first_owner'      => array( 'label' => __( 'Első tulaj', 'ki-mit-adna-erte' ), 'icon' => 'baby' ),
			'low_mileage'      => array( 'label' => __( 'Keveset futott', 'ki-mit-adna-erte' ), 'icon' => 'gauge' ),
			'well_equipped'    => array( 'label' => __( 'Jól felszerelt', 'ki-mit-adna-erte' ), 'icon' => 'sliders' ),
			'family'           => array( 'label' => __( 'Családi autó', 'ki-mit-adna-erte' ), 'icon' => 'users' ),
			'city'             => array( 'label' => __( 'Városi használatra', 'ki-mit-adna-erte' ), 'icon' => 'home' ),
			'long_distance'    => array( 'label' => __( 'Hosszabb utakra', 'ki-mit-adna-erte' ), 'icon' => 'car' ),
			'ready_to_use'     => array( 'label' => __( 'Azonnal használható', 'ki-mit-adna-erte' ), 'icon' => 'zap' ),
			'freshly_serviced' => array( 'label' => __( 'Frissen szervizelt', 'ki-mit-adna-erte' ), 'icon' => 'settings' ),
			'economical'       => array( 'label' => __( 'Gazdaságos', 'ki-mit-adna-erte' ), 'icon' => 'percent' ),
			'special'          => array( 'label' => __( 'Ritkább modell', 'ki-mit-adna-erte' ), 'icon' => 'star' ),
		);
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return array<int, string> Trait codes.
	 */
	public static function get_traits( $listing_id ) {
		$saved = get_post_meta( $listing_id, self::META_TRAITS, true );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		return array_values( array_intersect( $saved, array_keys( self::traits() ) ) );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return array<int, string> Trait labels.
	 */
	public static function get_trait_labels( $listing_id ) {
		$all = self::traits();
		$out = array();
		foreach ( self::get_traits( $listing_id ) as $code ) {
			$out[] = $all[ $code ]['label'];
		}
		return $out;
	}

	/**
	 * A tulajdonosok számának felirata.
	 *
	 * A „4" a feladásnál a „Negyedik vagy több" kártyát jelenti, ezért nem
	 * írhatjuk ki pontos számként — az mást állítana, mint amit választott.
	 * A „0" pedig nem nulla tulajdonost jelent, hanem hogy nem tudja.
	 *
	 * @param int|string $owners Stored value.
	 * @return string
	 */
	public static function format_owners( $owners ) {
		$owners = (int) $owners;
		if ( $owners <= 0 ) {
			return '';
		}
		if ( $owners >= 4 ) {
			return __( '4 vagy több', 'ki-mit-adna-erte' );
		}
		return sprintf( '%d.', $owners );
	}

	public static function condition_labels() {
		return array(
			'excellent' => __( 'Kiváló', 'ki-mit-adna-erte' ),
			'good'      => __( 'Jó', 'ki-mit-adna-erte' ),
			'fair'      => __( 'Átlagos', 'ki-mit-adna-erte' ),
			'project'   => __( 'Javítandó', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Brand => models map from the bundled catalog.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function catalog() {
		static $catalog = null;
		if ( null !== $catalog ) {
			return $catalog;
		}

		$file = KMAE_PLUGIN_DIR . 'assets/data/vehicle-catalog.json';
		if ( ! is_readable( $file ) ) {
			$catalog = array();
			return $catalog;
		}

		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			$catalog = array();
			return $catalog;
		}

		$catalog = array();
		foreach ( $decoded as $brand => $models ) {
			if ( ! is_string( $brand ) || '' === trim( $brand ) || ! is_array( $models ) ) {
				continue;
			}
			$clean = array();
			foreach ( $models as $model ) {
				if ( is_string( $model ) && '' !== trim( $model ) ) {
					$clean[] = $model;
				}
			}
			$catalog[ $brand ] = $clean;
		}

		return $catalog;
	}

	/**
	 * Sorted brand names from the catalog.
	 *
	 * @return array<int, string>
	 */
	public static function brands() {
		$brands = array_keys( self::catalog() );
		natcasesort( $brands );
		return array_values( $brands );
	}

	/**
	 * Models for a catalog brand (case-insensitive brand match).
	 *
	 * @param string $brand Brand label.
	 * @return array<int, string>
	 */
	public static function models_for( $brand ) {
		$key = self::resolve_brand_key( $brand );
		if ( '' === $key ) {
			return array();
		}
		return self::catalog()[ $key ];
	}

	/**
	 * @param string $brand Brand label.
	 * @return bool
	 */
	public static function is_known_brand( $brand ) {
		return '' !== self::resolve_brand_key( $brand );
	}

	/**
	 * @param string $brand Brand label.
	 * @param string $model Model label.
	 * @return bool
	 */
	public static function is_known_model( $brand, $model ) {
		$model = trim( (string) $model );
		if ( '' === $model ) {
			return false;
		}
		foreach ( self::models_for( $brand ) as $known ) {
			if ( 0 === strcasecmp( $known, $model ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Brand emblem base URL (media library upload folder).
	 */
	const LOGO_BASE = 'https://albira.hu/wp-content/uploads/2026/09/';

	/**
	 * Catalog brand => emblem file slug.
	 *
	 * The slug is the uploaded file name, so a few entries intentionally differ
	 * from the brand name (rolls-royes, daevoo) — those are the real file names.
	 *
	 * @return array<string, string>
	 */
	private static function brand_logo_slugs() {
		return array(
			'ABARTH'           => 'abarth',
			'AIXAM'            => 'aixam',
			'ALFA ROMEO'       => 'alfa-romeo',
			'ASTON MARTIN'     => 'aston-martin',
			'AUDI'             => 'audi',
			'BENTLEY'          => 'bentley',
			'BMW'              => 'bmw',
			'BYD'              => 'byd',
			'CHERY'            => 'chery',
			'CHEVROLET'        => 'chevrolet',
			'CHRYSLER'         => 'chrysler',
			'CITROEN'          => 'citroen',
			'CUPRA'            => 'cupra',
			'DACIA'            => 'dacia',
			'DAEWOO'           => 'daevoo',
			'DODGE'            => 'dodge',
			'DONGFENG (DFSK)'  => 'dongfeng',
			'FERRARI'          => 'ferrari',
			'FIAT'             => 'fiat',
			'FORD'             => 'ford',
			'HONDA'            => 'honda',
			'HUMMER'           => 'hummer',
			'HYUNDAI'          => 'hyundai',
			'INFINITI'         => 'infiniti',
			'JAECOO'           => 'jaecoo',
			'JAGUAR'           => 'jaguar',
			'JEEP'             => 'jeep',
			'KIA'              => 'kia',
			'LADA'             => 'lada',
			'LAMBORGHINI'      => 'lamborghini',
			'LANCIA'           => 'lancia',
			'LAND ROVER'       => 'land-rover',
			'LEXUS'            => 'lexus',
			'MASERATI'         => 'maserati',
			'MAZDA'            => 'mazda',
			'MCLAREN'          => 'mclaren',
			'MERCEDES AMG'     => 'mercedes-amg',
			'MERCEDES-BENZ'    => 'mercedes-benz',
			'MERCEDES-MAYBACH' => 'mercedes-maybach',
			'MG'               => 'mg',
			'MINI'             => 'mini',
			'MITSUBISHI'       => 'mitsubishi',
			'NISSAN'           => 'nissan',
			'OMODA'            => 'omoda',
			'OPEL'             => 'opel',
			'PEUGEOT'          => 'peugeot',
			'PORSCHE'          => 'porsche',
			'RENAULT'          => 'renault',
			'ROLLS ROYCE'      => 'rolls-royes',
			'SEAT'             => 'seat',
			'SKODA'            => 'skoda',
			'SMART'            => 'smart',
			'SSANGYONG'        => 'ssangyong',
			'SUBARU'           => 'subaru',
			'SUZUKI'           => 'suzuki',
			'TESLA'            => 'tesla',
			'TOYOTA'           => 'toyota',
			'TRABANT'          => 'trabant',
			'UAZ'              => 'uaz',
			'VOLKSWAGEN'       => 'volkswagen',
			'VOLVO'            => 'volvo',
			'WARTBURG'         => 'wartburg',
		);
	}

	/**
	 * Brand => emblem URL, for catalog brands that have one.
	 *
	 * @return array<string, string>
	 */
	public static function brand_logos() {
		$logos = array();
		foreach ( self::brand_logo_slugs() as $brand => $slug ) {
			$logos[ $brand ] = self::LOGO_BASE . $slug . '.webp';
		}
		return $logos;
	}

	/**
	 * Brands offered before the user types anything.
	 *
	 * @return array<int, string>
	 */
	public static function popular_brands() {
		return array( 'SUZUKI', 'VOLKSWAGEN', 'BMW', 'AUDI', 'MERCEDES-BENZ', 'FORD' );
	}

	/**
	 * Catalog payload for the frontend picker.
	 *
	 * @return array{brands: array<int, string>, models: array<string, array<int, string>>, logos: array<string, string>, popular: array<int, string>}
	 */
	public static function catalog_payload() {
		return array(
			'brands'  => self::brands(),
			'models'  => self::catalog(),
			'logos'   => self::brand_logos(),
			'popular' => self::popular_brands(),
		);
	}

	/**
	 * @param string $brand Brand label.
	 * @return string Canonical catalog brand key, or empty string.
	 */
	private static function resolve_brand_key( $brand ) {
		$brand = trim( (string) $brand );
		if ( '' === $brand ) {
			return '';
		}
		foreach ( self::catalog() as $key => $models ) {
			if ( 0 === strcasecmp( $key, $brand ) ) {
				return $key;
			}
		}
		return '';
	}

	public static function get_specs( $listing_id ) {
		return array(
			'brand'        => get_post_meta( $listing_id, self::META_BRAND, true ),
			'model'        => get_post_meta( $listing_id, self::META_MODEL, true ),
			'year'         => (int) get_post_meta( $listing_id, self::META_YEAR, true ),
			'mileage'      => (int) get_post_meta( $listing_id, self::META_MILEAGE, true ),
			'fuel'         => get_post_meta( $listing_id, self::META_FUEL, true ),
			'transmission' => get_post_meta( $listing_id, self::META_TRANSMISSION, true ),
			'body'         => self::normalize_body( get_post_meta( $listing_id, self::META_BODY, true ) ),
			'color'        => get_post_meta( $listing_id, self::META_COLOR, true ),
			'owners'       => (int) get_post_meta( $listing_id, self::META_OWNERS, true ),
			'power_kw'     => (int) get_post_meta( $listing_id, self::META_POWER_KW, true ),
			'engine_cc'    => (int) get_post_meta( $listing_id, self::META_ENGINE_CC, true ),
			'mot'          => get_post_meta( $listing_id, self::META_MOT, true ),
			'condition'    => get_post_meta( $listing_id, '_kmae_condition', true ),
			'condition_note' => get_post_meta( $listing_id, '_kmae_condition_note', true ),
			'value'        => self::get_vehicle_value( $listing_id ),
		);
	}

	/**
	 * Műszaki érvényessége a beküldött mezőkből: "ÉÉÉÉ-HH", vagy üres.
	 *
	 * @return string
	 */
	private static function posted_mot() {
		$has = isset( $_POST['kmae_vehicle_mot_has'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_mot_has'] ) ) : '';
		if ( 'yes' !== $has ) {
			return '';
		}

		$year  = isset( $_POST['kmae_vehicle_mot_year'] ) ? absint( $_POST['kmae_vehicle_mot_year'] ) : 0;
		$month = isset( $_POST['kmae_vehicle_mot_month'] ) ? absint( $_POST['kmae_vehicle_mot_month'] ) : 0;
		$day   = isset( $_POST['kmae_vehicle_mot_day'] ) ? absint( $_POST['kmae_vehicle_mot_day'] ) : 0;

		if ( $year < 1990 || $year > 2100 || $month < 1 || $month > 12 ) {
			return '';
		}

		/* A nap nem kötelező: ha nincs megadva vagy kilóg a hónapból, a hónap
		   utolsó napját vesszük — a műszaki a hónap végéig érvényes. */
		$last_day = (int) gmdate( 't', gmmktime( 0, 0, 0, $month, 1, $year ) );
		if ( $day < 1 || $day > $last_day ) {
			$day = $last_day;
		}

		return sprintf( '%04d-%02d-%02d', $year, $month, $day );
	}

	public static function kw_to_horsepower( $kw ) {
		return $kw > 0 ? (int) round( (int) $kw * 1.3596216173 ) : 0;
	}

	public static function horsepower_to_kw( $horsepower ) {
		return $horsepower > 0 ? (int) round( (int) $horsepower * 0.73549875 ) : 0;
	}

	private static function posted_power_kw() {
		if ( isset( $_POST['kmae_vehicle_power_hp'] ) ) {
			return self::horsepower_to_kw( absint( $_POST['kmae_vehicle_power_hp'] ) );
		}

		return isset( $_POST['kmae_vehicle_power_kw'] ) ? absint( $_POST['kmae_vehicle_power_kw'] ) : 0;
	}

	public static function save_from_post( $post_id ) {
		if ( ! self::is_vehicle_launch() ) {
			return;
		}

		$brand = isset( $_POST['kmae_vehicle_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_brand'] ) ) : '';
		$model = isset( $_POST['kmae_vehicle_model'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_model'] ) ) : '';
		$year  = isset( $_POST['kmae_vehicle_year'] ) ? absint( $_POST['kmae_vehicle_year'] ) : 0;
		$km    = isset( $_POST['kmae_vehicle_mileage'] ) ? absint( $_POST['kmae_vehicle_mileage'] ) : 0;
		$fuel  = isset( $_POST['kmae_vehicle_fuel'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_fuel'] ) ) : '';
		$trans = isset( $_POST['kmae_vehicle_transmission'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_transmission'] ) ) : '';
		$body  = isset( $_POST['kmae_vehicle_body'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_body'] ) ) : '';
		$color = isset( $_POST['kmae_vehicle_color'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_color'] ) ) : '';
		$owners = isset( $_POST['kmae_vehicle_owners'] ) ? absint( wp_unslash( $_POST['kmae_vehicle_owners'] ) ) : null;
		$power  = self::posted_power_kw();
		$cc     = isset( $_POST['kmae_vehicle_engine_cc'] ) ? absint( $_POST['kmae_vehicle_engine_cc'] ) : 0;
		$value  = isset( $_POST['kmae_vehicle_value'] ) ? absint( $_POST['kmae_vehicle_value'] ) : 0;
		$cond   = isset( $_POST['kmae_condition'] ) ? sanitize_key( wp_unslash( $_POST['kmae_condition'] ) ) : '';
		$mot    = self::posted_mot();

		update_post_meta( $post_id, self::META_BRAND, $brand );
		update_post_meta( $post_id, self::META_MODEL, $model );
		update_post_meta( $post_id, self::META_YEAR, $year );
		update_post_meta( $post_id, self::META_MILEAGE, $km );
		update_post_meta( $post_id, self::META_FUEL, array_key_exists( $fuel, self::fuel_labels() ) ? $fuel : '' );
		update_post_meta( $post_id, self::META_TRANSMISSION, array_key_exists( $trans, self::transmission_labels() ) ? $trans : '' );
		update_post_meta( $post_id, self::META_BODY, self::normalize_body( $body ) );
		update_post_meta( $post_id, self::META_COLOR, $color );
		update_post_meta( $post_id, self::META_MOT, $mot );
		if ( null !== $owners ) {
			update_post_meta( $post_id, self::META_OWNERS, $owners );
		}
		update_post_meta( $post_id, self::META_POWER_KW, $power );
		update_post_meta( $post_id, self::META_ENGINE_CC, $cc );
		update_post_meta( $post_id, self::META_VALUE, $value );

		/* Jellemzők: csak ismert kódok, legfeljebb háromig. */
		$traits_raw = isset( $_POST['kmae_vehicle_traits'] ) ? (array) wp_unslash( $_POST['kmae_vehicle_traits'] ) : array();
		$traits     = array();
		foreach ( $traits_raw as $trait ) {
			$trait = sanitize_key( (string) $trait );
			if ( isset( self::traits()[ $trait ] ) && ! in_array( $trait, $traits, true ) ) {
				$traits[] = $trait;
			}
		}
		update_post_meta( $post_id, self::META_TRAITS, array_slice( $traits, 0, self::MAX_TRAITS ) );

		if ( array_key_exists( $cond, self::condition_labels() ) ) {
			update_post_meta( $post_id, '_kmae_condition', $cond );
		}

		/* A javítanivaló leírása csak a „javítandó" állapothoz tartozik — más
		   állapotnál töröljük, hogy ne maradjon ott egy korábbi szöveg. */
		$cond_note = isset( $_POST['kmae_condition_note'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_condition_note'] ) ) : '';
		if ( 'project' === $cond && '' !== $cond_note ) {
			update_post_meta( $post_id, '_kmae_condition_note', $cond_note );
		} else {
			delete_post_meta( $post_id, '_kmae_condition_note' );
		}

		$launch_cat = KMAE_Launch::get_launch_category_id();
		if ( $launch_cat ) {
			wp_set_object_terms( $post_id, array( $launch_cat ), 'kmae_category' );
		}
	}

	/**
	 * @return array<int, array{field:string,label:string}>
	 */
	public static function get_missing_required( $data ) {
		$missing = array();

		$required = array(
			'brand'        => __( 'Márka', 'ki-mit-adna-erte' ),
			'model'        => __( 'Modell', 'ki-mit-adna-erte' ),
			'year'         => __( 'Évjárat', 'ki-mit-adna-erte' ),
			'mileage'      => __( 'Kilométeróra állás', 'ki-mit-adna-erte' ),
			'fuel'         => __( 'Üzemanyag', 'ki-mit-adna-erte' ),
			'transmission' => __( 'Váltó', 'ki-mit-adna-erte' ),
			'body'         => __( 'Kivitel', 'ki-mit-adna-erte' ),
			/* A javítanivaló leírása NEM kötelező: csak a „javítandó" állapothoz
			   tartozik, és ott is kiegészítés. Kötelezőként minden hibátlan autó
			   feladását megakasztotta volna. */
			'condition'    => __( 'Állapot', 'ki-mit-adna-erte' ),
		);

		foreach ( $required as $key => $label ) {
			$invalid = empty( $data[ $key ] );
			if ( $invalid ) {
				$missing[] = array(
					'field' => $key,
					'label' => $label,
				);
			}
		}

		$year = (int) ( $data['year'] ?? 0 );
		if ( $year && ( $year < 1950 || $year > (int) gmdate( 'Y' ) + 1 ) ) {
			$missing[] = array(
				'field' => 'year',
				'label' => __( 'Érvényes évjárat', 'ki-mit-adna-erte' ),
			);
		}

		return $missing;
	}

	public static function build_title_from_specs( $data ) {
		$parts = array_filter(
			array(
				$data['brand'] ?? '',
				$data['model'] ?? '',
				! empty( $data['year'] ) ? (string) $data['year'] : '',
			)
		);
		return implode( ' ', $parts );
	}

	public static function format_mileage( $km ) {
		if ( $km <= 0 ) {
			return '';
		}
		return number_format( $km, 0, ',', ' ' ) . ' km';
	}

	/**
	 * Display rows for templates.
	 *
	 * @return array<int, array{label:string,value:string}>
	 */
	public static function get_display_rows( $listing_id ) {
		if ( ! self::is_vehicle_launch() ) {
			return array();
		}

		$s = self::get_specs( $listing_id );
		$rows = array();

		$map = array(
			'year'         => array( __( 'Évjárat', 'ki-mit-adna-erte' ), $s['year'] ? (string) $s['year'] : '' ),
			'mileage'      => array( __( 'Km óra', 'ki-mit-adna-erte' ), self::format_mileage( $s['mileage'] ) ),
			'fuel'         => array( __( 'Üzemanyag', 'ki-mit-adna-erte' ), self::fuel_labels()[ $s['fuel'] ] ?? '' ),
			'transmission' => array( __( 'Váltó', 'ki-mit-adna-erte' ), self::transmission_labels()[ $s['transmission'] ] ?? '' ),
			'body'         => array( __( 'Kivitel', 'ki-mit-adna-erte' ), self::body_labels()[ $s['body'] ] ?? '' ),
			'color'        => array( __( 'Szín', 'ki-mit-adna-erte' ), $s['color'] ),
			'owners'       => array( __( 'Tulajdonos', 'ki-mit-adna-erte' ), self::format_owners( $s['owners'] ) ),
			/* kW-ban tároljuk, de lóerőben mutatjuk (Raul, 2026-10-20) — mint a címben. */
			'power_kw'     => array( __( 'Teljesítmény', 'ki-mit-adna-erte' ), $s['power_kw'] ? number_format( self::kw_to_horsepower( $s['power_kw'] ), 0, ',', ' ' ) . ' LE' : '' ),
			'engine_cc'    => array( __( 'Hengerűrtartalom', 'ki-mit-adna-erte' ), $s['engine_cc'] ? number_format( $s['engine_cc'], 0, ',', ' ' ) . ' cm³' : '' ),
			'value'        => array( __( 'Becsült érték', 'ki-mit-adna-erte' ), self::format_value( $s['value'] ) ),
			/* A javítanivaló szövege szándékosan NEM adatsor: a hirdetésben az
			   „Ismert hibák" doboz mondja el, ott a helye. */
			'condition'    => array( __( 'Állapot', 'ki-mit-adna-erte' ), self::condition_labels()[ $s['condition'] ] ?? '' ),
			'traits'       => array( __( 'Jellemzők', 'ki-mit-adna-erte' ), implode( ', ', self::get_trait_labels( $listing_id ) ) ),
		);

		foreach ( $map as $key => $row ) {
			if ( ! empty( $row[1] ) ) {
				$rows[] = array(
					'key'   => $key,
					'label' => $row[0],
					'value' => $row[1],
				);
			}
		}

		return $rows;
	}

	public static function get_display_title( $listing_id ) {
		$s = self::get_specs( $listing_id );
		if ( ! empty( $s['brand'] ) && ! empty( $s['model'] ) ) {
			return trim( $s['brand'] . ' ' . $s['model'] );
		}
		if ( ! empty( $s['brand'] ) ) {
			return $s['brand'];
		}
		return get_the_title( $listing_id );
	}

	public static function get_swipe_subtitle( $listing_id ) {
		$s = self::get_specs( $listing_id );
		$parts = array_filter(
			array(
				$s['year'] ? (string) $s['year'] : '',
				self::format_mileage( $s['mileage'] ),
				self::fuel_labels()[ $s['fuel'] ] ?? '',
			)
		);
		return implode( ' · ', $parts );
	}

	public static function parse_post_data() {
		return array(
			'brand'        => isset( $_POST['kmae_vehicle_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_brand'] ) ) : '',
			'model'        => isset( $_POST['kmae_vehicle_model'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_model'] ) ) : '',
			'year'         => isset( $_POST['kmae_vehicle_year'] ) ? absint( $_POST['kmae_vehicle_year'] ) : 0,
			'mileage'      => isset( $_POST['kmae_vehicle_mileage'] ) ? absint( $_POST['kmae_vehicle_mileage'] ) : 0,
			'fuel'         => isset( $_POST['kmae_vehicle_fuel'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_fuel'] ) ) : '',
			'transmission' => isset( $_POST['kmae_vehicle_transmission'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_transmission'] ) ) : '',
			'body'         => isset( $_POST['kmae_vehicle_body'] ) ? sanitize_key( wp_unslash( $_POST['kmae_vehicle_body'] ) ) : '',
			'color'        => isset( $_POST['kmae_vehicle_color'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_vehicle_color'] ) ) : '',
			'owners'       => isset( $_POST['kmae_vehicle_owners'] ) && is_scalar( $_POST['kmae_vehicle_owners'] ) ? trim( (string) wp_unslash( $_POST['kmae_vehicle_owners'] ) ) : '',
			'power_kw'     => self::posted_power_kw(),
			'engine_cc'    => isset( $_POST['kmae_vehicle_engine_cc'] ) ? absint( $_POST['kmae_vehicle_engine_cc'] ) : 0,
			'mot'          => self::posted_mot(),
			'condition'    => isset( $_POST['kmae_condition'] ) ? sanitize_key( wp_unslash( $_POST['kmae_condition'] ) ) : '',
			'condition_note' => isset( $_POST['kmae_condition_note'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_condition_note'] ) ) : '',
			'value'        => isset( $_POST['kmae_vehicle_value'] ) ? absint( $_POST['kmae_vehicle_value'] ) : 0,
			'title'        => isset( $_POST['kmae_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_title'] ) ) : '',
			'description'  => isset( $_POST['kmae_description'] ) ? wp_strip_all_tags( wp_unslash( $_POST['kmae_description'] ) ) : '',
		);
	}
}
