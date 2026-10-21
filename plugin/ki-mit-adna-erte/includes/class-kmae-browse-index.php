<?php
/**
 * Böngészés v2: előre kiszámolt hirdetés-index ({prefix}kmae_browse_index).
 *
 * Hirdetésenként egy sor: minden mező, amire a böngészés szűrése és rendezése
 * épít (KMAE_Browse::listing_matches_wizard / listing_matches_detail_filters /
 * apply_sort / county_slugs + távolság), az aktív és a Foglalt állapot, és a
 * kártya kész adata JSON-ban. A feed (KMAE_Browse_Feed) innen szűr, rendez és
 * lapoz SQL-ben. Szerződés: PROJECT-MEMORY/FEATURES/BROWSE-V2.md.
 *
 * Frissen tartás: a WordPress post/meta hookjai (mentés, státusz, lejárat cron,
 * hosszabbítás, törlés, kép- és koordináta-csere) és a Foglalt-változás
 * (`kmae_listing_reserved_changed`) sorba teszik a hirdetést; a sor a kérés
 * végén (shutdown) egyszer épül újra.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Browse_Index {

	/** A tábla sémájának verziója: emelve a meglévő DB-upgrade (maybe_upgrade) újra lefuttatja a dbDelta-t
	    és a háttérben újraépíti az indexet (2: a kártyába bekerült az eladó). */
	const SCHEMA_VERSION = '2';
	const SCHEMA_OPTION  = 'kmae_browse_index_schema';
	const RETRY          = 'kmae_browse_index_retry';

	/** Teljes újraépítés állapota (kurzor), cron-hook, zár, adag. */
	const REBUILD_OPTION = 'kmae_browse_index_rebuild';
	const REBUILD_HOOK   = 'kmae_browse_index_rebuild';
	const LOCK           = 'kmae_browse_index_lock';
	const BATCH          = 200;
	const TIME_BUDGET    = 20;

	/** Szöveges oszlopok szélessége (bájt): a hosszabb értéket a wpdb elutasítaná, ezért vágjuk (a feed a keresett értéket ugyanígy). */
	const WIDTHS = array(
		'county'     => 40,
		'body'       => 40,
		'brand_key'  => 191,
		'model_key'  => 191,
		'sort_name'  => 191,
		'fuel'       => 40,
		'gear'       => 40,
		'cond'       => 40,
		'price_mode' => 10,
	);

	/** A kártya JSON formátumának verziója (a kliens ezt ellenőrizheti). 2: lead.kw + body. 3: seller. 4: extras. */
	const CARD_VERSION = 4;

	/** Hirdetés-meta kulcsok, amelyek változása a sort érinti (a megtekintés-számláló és társai NEM). */
	const WATCHED_META = array(
		'_kmae_vehicle_brand',
		'_kmae_vehicle_model',
		'_kmae_vehicle_year',
		'_kmae_vehicle_mileage',
		'_kmae_vehicle_fuel',
		'_kmae_vehicle_transmission',
		'_kmae_vehicle_body',
		'_kmae_vehicle_color',
		'_kmae_vehicle_owners',
		'_kmae_vehicle_power_kw',
		'_kmae_vehicle_engine_cc',
		'_kmae_vehicle_mot',
		'_kmae_vehicle_value',
		'_kmae_condition',
		'_kmae_county',
		'_kmae_city',
		'_kmae_location',
		'_kmae_status',
		'_kmae_offer_types',
		'_kmae_gallery',
		'_thumbnail_id',
		'_kmae_price_mode',
		'_kmae_fixed_price',
		'_kmae_guide_price',
		'_kmae_geo',
	);

	/** Kép (attachment) meta kulcsok: a kártyakép-források (KMAE_Images::card_sources) ezekből jönnek. */
	const WATCHED_ATTACHMENT_META = array(
		'_wp_attachment_metadata',
		'_wp_attached_file',
		'_kmae_ambient',
		'_kmae_tone',
		'_kmae_lqip',
		'_kmae_subject',
	);

	/** @var array<int,bool> A kérés végén újraépítendő hirdetések. */
	private static $queue = array();

	/** @var bool Építés közben a saját meta-írásainkat (pl. galéria-takarítás) nem tesszük újra sorba. */
	private static $syncing = false;

	public static function init() {
		add_action( 'save_post_kmae_listing', array( __CLASS__, 'queue' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_meta' ), 10, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_meta' ), 10, 3 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'on_meta' ), 10, 3 );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted' ) );
		add_action( 'kmae_listing_reserved_changed', array( __CLASS__, 'queue' ) );
		add_action( 'shutdown', array( __CLASS__, 'flush' ), 5 );
		add_action( self::REBUILD_HOOK, array( __CLASS__, 'cron_run' ) );
		add_action( 'admin_post_kmae_browse_index_rebuild', array( __CLASS__, 'handle_admin_rebuild' ) );
	}

	public static function table() {
		return KMAE_Database::table( 'browse_index' );
	}

	/** A tábla a jelenlegi sémával létezik-e (különben a hookok nem írnak bele). */
	public static function ready() {
		return self::SCHEMA_VERSION === (string) get_option( self::SCHEMA_OPTION, '' );
	}

	/* ------------------------------------------------------------------
	   Séma (a KMAE_Activator::create_tables és maybe_upgrade hívja)
	   ------------------------------------------------------------------ */

	/** A meglévő DB-upgrade (KMAE_Activator::maybe_upgrade) minden betöltéskor hívja; olcsó (autoload opció). */
	public static function maybe_create() {
		if ( ! self::ready() && ! get_transient( self::RETRY ) ) {
			self::create_table();
		}
	}

	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		/* brand_key / model_key / sort_name VARBINARY: bájtonkénti egyezés és rendezés,
		   mint a PHP strcasecmp/strcmp (a *_ci kolláció ékezet-érzéketlen lenne). */
		$sql = "CREATE TABLE {$table} (
			listing_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 0,
			in_category tinyint(1) NOT NULL DEFAULT 0,
			is_reserved tinyint(1) NOT NULL DEFAULT 0,
			post_date datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			post_date_gmt datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			county varchar(40) NOT NULL DEFAULT '',
			body varchar(40) NOT NULL DEFAULT '',
			brand_key varbinary(191) NOT NULL DEFAULT '',
			model_key varbinary(191) NOT NULL DEFAULT '',
			sort_name varbinary(191) DEFAULT NULL,
			model_year int(11) NOT NULL DEFAULT 0,
			mileage int(11) NOT NULL DEFAULT 0,
			engine_cc int(11) NOT NULL DEFAULT 0,
			power_kw int(11) NOT NULL DEFAULT 0,
			power_hp int(11) NOT NULL DEFAULT 0,
			fuel varchar(40) NOT NULL DEFAULT '',
			gear varchar(40) NOT NULL DEFAULT '',
			cond varchar(40) NOT NULL DEFAULT '',
			price_mode varchar(10) NOT NULL DEFAULT 'open',
			has_cash tinyint(1) NOT NULL DEFAULT 0,
			vehicle_value bigint(20) NOT NULL DEFAULT 0,
			price_amount double DEFAULT NULL,
			mot_date date DEFAULT NULL,
			lat double DEFAULT NULL,
			lon double DEFAULT NULL,
			card mediumtext NOT NULL,
			indexed_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY  (listing_id),
			KEY active_date (is_active,post_date,listing_id),
			KEY active_county_date (is_active,county,post_date),
			KEY active_body (is_active,body),
			KEY brand_model (brand_key,model_key),
			KEY price_value (price_mode,vehicle_value),
			KEY model_year (model_year),
			KEY mileage (mileage),
			KEY power_hp (power_hp),
			KEY engine_cc (engine_cc),
			KEY price_amount (price_amount),
			KEY mot_date (mot_date),
			KEY author_id (author_id)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			set_transient( self::RETRY, 1, HOUR_IN_SECONDS ); /* nem jött létre: óránként újrapróbáljuk, addig a hookok nem írnak */
			return;
		}

		$changed = ! self::ready();
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

		/* Új tábla (vagy séma-csere): a meglévő hirdetések a háttérben kerülnek bele. */
		if ( $changed ) {
			self::start_rebuild();
		}
	}

	/* ------------------------------------------------------------------
	   Egy sor felépítése
	   ------------------------------------------------------------------ */

	/**
	 * A szűréshez/rendezéshez használt oszlopok, PONTOSAN azokkal a függvényekkel,
	 * amelyekkel a régi böngészés számol (lásd BROWSE-V2.md „Táblamezők”).
	 *
	 * @param WP_Post $post Hirdetés.
	 * @return array<string,mixed>
	 */
	public static function columns( $post ) {
		$id        = (int) $post->ID;
		$brand_raw = (string) get_post_meta( $id, KMAE_Vehicle::META_BRAND, true );
		$model_raw = (string) get_post_meta( $id, KMAE_Vehicle::META_MODEL, true );
		$public    = (float) KMAE_Seller::get_public_price( $id );
		$coords    = class_exists( 'KMAE_Geo' ) ? KMAE_Geo::listing_coords( $id ) : null;
		$sort_name = remove_accents( mb_strtolower( trim( $brand_raw . ' ' . $model_raw ) ) );
		$power_kw  = (int) get_post_meta( $id, KMAE_Vehicle::META_POWER_KW, true );

		$row = array(
			'listing_id'    => $id,
			'author_id'     => (int) $post->post_author,
			/* = KMAE_Query::get_listings: post_status publish + `_kmae_status` meta = active. */
			'is_active'     => ( 'publish' === $post->post_status && KMAE_Meta::STATUS_ACTIVE === (string) get_post_meta( $id, '_kmae_status', true ) ) ? 1 : 0,
			/* Egykategóriás indulásnál a get_listings a kategóriára is szűr. */
			'in_category'   => has_term( KMAE_Launch::category_slug(), 'kmae_category', $id ) ? 1 : 0,
			'is_reserved'   => self::is_reserved( $id ) ? 1 : 0,
			'post_date'     => self::datetime( $post->post_date ),
			'post_date_gmt' => self::datetime( $post->post_date_gmt ),
			'county'        => sanitize_key( (string) get_post_meta( $id, '_kmae_county', true ) ),
			'body'          => KMAE_Vehicle::normalize_body( get_post_meta( $id, KMAE_Vehicle::META_BODY, true ) ),
			/* strcasecmp = ASCII kis/nagybetű-független bájt-egyezés → strtolower (PHP 8: csak ASCII). */
			'brand_key'     => strtolower( trim( $brand_raw ) ),
			'model_key'     => strtolower( trim( $model_raw ) ),
			'sort_name'     => '' !== $sort_name ? $sort_name : null,
			'model_year'    => (int) get_post_meta( $id, KMAE_Vehicle::META_YEAR, true ),
			'mileage'       => (int) get_post_meta( $id, KMAE_Vehicle::META_MILEAGE, true ),
			'engine_cc'     => (int) get_post_meta( $id, KMAE_Vehicle::META_ENGINE_CC, true ),
			'power_kw'      => $power_kw,
			'power_hp'      => KMAE_Vehicle::kw_to_horsepower( $power_kw ),
			'fuel'          => sanitize_key( (string) get_post_meta( $id, KMAE_Vehicle::META_FUEL, true ) ),
			'gear'          => sanitize_key( (string) get_post_meta( $id, KMAE_Vehicle::META_TRANSMISSION, true ) ),
			'cond'          => sanitize_key( (string) get_post_meta( $id, '_kmae_condition', true ) ),
			'price_mode'    => KMAE_Seller::get_price_mode( $id ),
			'has_cash'      => in_array( KMAE_OFFER_TYPE_CASH, (array) KMAE_Meta::get_offer_types( $id ), true ) ? 1 : 0,
			'vehicle_value' => (int) KMAE_Vehicle::get_vehicle_value( $id ),
			/* = KMAE_Seller::get_public_price_row()['amount'] (null, ha nincs nyilvános ár). */
			'price_amount'  => $public >= 1 ? $public : null,
			'mot_date'      => self::normalize_mot( get_post_meta( $id, KMAE_Vehicle::META_MOT, true ) ),
			'lat'           => $coords ? (float) $coords[0] : null,
			'lon'           => $coords ? (float) $coords[1] : null,
		);
		foreach ( self::WIDTHS as $key => $width ) {
			if ( is_string( $row[ $key ] ) ) {
				$row[ $key ] = self::clip( $row[ $key ], $width );
			}
		}
		return $row;
	}

	/**
	 * Bájt-pontos vágás (VARBINARY / ASCII kulcsok: a karakterhatár nem számít).
	 *
	 * @param string $value Érték.
	 * @param int    $width Bájt.
	 */
	public static function clip( $value, $width ) {
		return strlen( $value ) > $width ? substr( $value, 0, $width ) : $value;
	}

	/**
	 * A 0000-00-00 dátum (strict MySQL nem fogadja) = 1970-01-01: a régi rendezésben is 0 időbélyeg.
	 *
	 * @param string $value DATETIME.
	 */
	private static function datetime( $value ) {
		$value = (string) $value;
		return ( '' === $value || 0 === strpos( $value, '0000' ) ) ? '1970-01-01 00:00:00' : $value;
	}

	/**
	 * Műszaki érvényesség dátuma úgy, ahogy a KMAE_Browse::mot_months_left() értelmezi
	 * (ÉÉÉÉ-HH-NN minta, a DateTime „túlcsorduló” napjával együtt), különben null (= lejárt).
	 *
	 * @param mixed $raw Meta érték.
	 * @return string|null
	 */
	public static function normalize_mot( $raw ) {
		$raw = trim( (string) $raw );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
			return null;
		}
		try {
			$date = new DateTimeImmutable( $raw );
		} catch ( Exception $e ) {
			return null;
		}
		$out = $date->format( 'Y-m-d' );
		return (int) substr( $out, 0, 4 ) >= 1000 ? $out : null;
	}

	/**
	 * Foglalt = van `accepted` ajánlata (ugyanaz, mint KMAE_Chat_Offers::reserved_listing_ids();
	 * itt soronként és statikus gyorsítótár nélkül, hogy egy kérésen belüli elfogadás is látszódjon).
	 *
	 * @param int $listing_id Listing.
	 */
	public static function is_reserved( $listing_id ) {
		return class_exists( 'KMAE_Chat_Offers' ) && (bool) KMAE_Chat_Offers::get_accepted_for_listing( (int) $listing_id );
	}

	/**
	 * A kártya minden kész adata (a browse-swipe.php / browse-list.php kártyájával azonos számítás).
	 * Személyes adat (szerző, elérhetőség) NINCS benne.
	 *
	 * @param WP_Post $post Hirdetés.
	 * @return array<string,mixed>
	 */
	public static function card( $post ) {
		$id    = (int) $post->ID;
		$specs = KMAE_Vehicle::get_specs( $id );
		$name  = static function ( $raw ) {
			$raw = trim( (string) $raw );
			if ( '' === $raw ) {
				return '';
			}
			if ( mb_strlen( $raw ) <= 3 ) {
				return mb_strtoupper( $raw );
			}
			if ( preg_match( '/^[\p{Lu}\d\s.\-]+$/u', $raw ) ) {
				return mb_convert_case( mb_strtolower( $raw ), MB_CASE_TITLE, 'UTF-8' );
			}
			return $raw;
		};

		$brand      = $name( $specs['brand'] );
		$model      = $name( $specs['model'] );
		$text       = trim( $brand . ' ' . $model );
		$list_text  = $text;
		if ( '' === $text ) {
			$list_text = (string) preg_replace( '/^\s*elad[oó]\s+/iu', '', (string) $post->post_title );
			$pieces    = preg_split( '/\s+[–\-|]\s+/u', $list_text );
			$text      = trim( (string) ( $pieces[0] ?? $list_text ) );
		}

		$hp = KMAE_Vehicle::kw_to_horsepower( (int) $specs['power_kw'] );
		$cc = (int) $specs['engine_cc'];

		$price_row = KMAE_Seller::get_public_price_row( $id );
		$value     = (int) KMAE_Vehicle::get_vehicle_value( $id );

		$offer_labels = KMAE_Taxonomies::offer_type_labels();
		$offer_types  = array();
		foreach ( (array) KMAE_Meta::get_offer_types( $id ) as $slug ) {
			$offer_types[] = array(
				'slug'  => (string) $slug,
				'label' => (string) ( $offer_labels[ $slug ] ?? $slug ),
				'icon'  => KMAE_OFFER_TYPE_CASH === $slug ? 'banknote' : 'handshake',
			);
		}

		$facts = array(
			array(
				'field' => 'year',
				'code'  => '',
				'value' => ! empty( $specs['year'] ) ? (string) $specs['year'] : '',
				'label' => __( 'Évjárat', 'ki-mit-adna-erte' ),
			),
			array(
				'field' => 'mileage',
				'code'  => '',
				'value' => KMAE_Vehicle::format_mileage( $specs['mileage'] ),
				'label' => __( 'Futott km', 'ki-mit-adna-erte' ),
			),
			array(
				'field' => 'fuel',
				'code'  => (string) $specs['fuel'],
				'value' => KMAE_Vehicle::fuel_labels()[ $specs['fuel'] ] ?? '',
				'label' => __( 'Üzemanyag', 'ki-mit-adna-erte' ),
			),
			array(
				'field' => 'transmission',
				'code'  => (string) $specs['transmission'],
				'value' => KMAE_Vehicle::transmission_labels()[ $specs['transmission'] ] ?? '',
				'label' => __( 'Váltó', 'ki-mit-adna-erte' ),
			),
		);
		$facts = array_values(
			array_filter(
				$facts,
				static function ( $fact ) {
					return '' !== $fact['value'];
				}
			)
		);

		$brand_name = trim( (string) $specs['brand'] );
		$brand_logo = '';
		if ( '' !== $brand_name && ! in_array( mb_strtolower( $brand_name ), array( 'egyéb', 'egyeb', 'other' ), true ) ) {
			foreach ( KMAE_Vehicle::brand_logos() as $known_name => $known_url ) {
				if ( 0 === strcasecmp( $known_name, $brand_name ) ) {
					$brand_logo = $known_url;
					break;
				}
			}
		}

		/* Kártyás nézet (PC) kiegészítő sora: szín, műszaki, tulajdonos — üresen kimarad. */
		$mot    = preg_match( '/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', trim( (string) $specs['mot'] ), $mot_m )
			? $mot_m[1] . '. ' . $mot_m[2] . '.' . ( ! empty( $mot_m[3] ) ? ' ' . $mot_m[3] . '.' : '' )
			: '';
		$owners = KMAE_Vehicle::format_owners( $specs['owners'] );
		$extras = array(
			array(
				'field' => 'color',
				'value' => trim( (string) $specs['color'] ),
				'label' => __( 'Szín', 'ki-mit-adna-erte' ),
			),
			array(
				'field' => 'mot',
				'value' => $mot,
				'label' => __( 'Műszaki', 'ki-mit-adna-erte' ),
			),
			array(
				'field' => 'owners',
				'value' => '' !== $owners ? $owners . ' ' . __( 'tulajdonos', 'ki-mit-adna-erte' ) : '',
				'label' => __( 'Tulajdonos', 'ki-mit-adna-erte' ),
			),
		);
		$extras = array_values(
			array_filter(
				$extras,
				static function ( $extra ) {
					return '' !== $extra['value'];
				}
			)
		);

		$photos = array();
		foreach ( KMAE_Query::get_gallery_ids( $id ) as $aid ) {
			$src = KMAE_Images::card_sources( (int) $aid );
			if ( '' !== $src['s'] ) {
				$photos[] = $src;
			}
		}
		if ( ! $photos ) {
			$thumb = KMAE_Query::get_listing_thumbnail_id( $id );
			$src   = $thumb ? KMAE_Images::card_sources( $thumb ) : null;
			if ( $src && '' !== $src['s'] ) {
				$photos[] = $src;
			}
		}

		return array(
			'v'           => self::CARD_VERSION,
			'id'          => $id,
			'url'         => (string) get_permalink( $id ),
			'title'       => array(
				'brand' => $brand,
				'model' => $model,
				'text'  => $text,
				'list'  => $list_text,
			),
			'subtitle'    => KMAE_Vehicle::get_swipe_subtitle( $id ),
			'lead'        => array(
				'hp'     => $hp,
				'kw'     => (int) $specs['power_kw'],
				'cc'     => $cc,
				'liters' => $cc > 0 ? number_format( $cc / 1000, 1, ',', '' ) : '',
			),
			'price'       => $price_row ? array(
				'mode'      => (string) $price_row['mode'],
				'label'     => (string) $price_row['label'],
				'amount'    => (float) $price_row['amount'],
				'formatted' => (string) $price_row['formatted'],
			) : null,
			'value'       => $value > 0 ? array(
				'amount'    => $value,
				'formatted' => KMAE_Vehicle::format_value( $value ),
			) : null,
			'offer_types' => $offer_types,
			'facts'       => $facts,
			/* Karosszéria a kártyás nézethez (a facts-ba nem kerül, mert azt a lista nézet is kirakja). */
			'body'        => array(
				'code'  => 'other' === $specs['body'] ? '' : (string) $specs['body'],
				'label' => 'other' === $specs['body'] ? '' : (string) ( KMAE_Vehicle::body_labels()[ $specs['body'] ] ?? '' ),
			),
			'extras'      => $extras,
			'seller'      => self::seller( (int) $post->post_author ),
			'location'    => (string) KMAE_Counties::format_listing_location( $id ),
			'brand_logo'  => $brand_logo,
			'photos'      => $photos,
			'photo_count' => count( $photos ),
		);
	}

	/**
	 * Eladó a kártyához: cégnél a cégnév, magánszemélynél a nyilvános név
	 * (mint az adatlapon: e-mail címet sosem ad ki), mellette a fióktípus.
	 *
	 * @param int $user_id Author.
	 * @return array{name: string, type: string}
	 */
	private static function seller( $user_id ) {
		$name = $user_id > 0 ? trim( (string) KMAE_Company::seller_display_name( $user_id ) ) : '';
		if ( '' === $name || is_email( $name ) ) {
			$name = $user_id > 0 ? (string) KMAE_Auth::first_name( $user_id ) : '';
		}
		if ( '' === $name || is_email( $name ) ) {
			$name = __( 'Eladó', 'ki-mit-adna-erte' );
		}
		return array(
			'name' => $name,
			'type' => $user_id > 0 ? (string) KMAE_Auth::account_type_label( $user_id ) : '',
		);
	}

	/**
	 * Egy hirdetés sorának újraépítése (vagy törlése, ha már nincs / kukában van).
	 *
	 * @param int $listing_id Listing.
	 */
	public static function sync( $listing_id ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 || ! self::ready() ) {
			return;
		}

		$post = get_post( $listing_id );
		if ( ! $post || 'kmae_listing' !== $post->post_type || in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
			self::delete( $listing_id );
			return;
		}

		$was           = self::$syncing;
		self::$syncing = true;
		try {
			$row = self::columns( $post );
			/* Kártya csak a feedben megjelenő (aktív) sorhoz kell; aktiváláskor a státusz-meta újraépíti. */
			$row['card']       = $row['is_active'] ? ( wp_json_encode( self::card( $post ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}' ) : '{}';
			$row['indexed_at'] = current_time( 'mysql', true );

			$formats = array();
			foreach ( $row as $key => $value ) {
				if ( is_float( $value ) ) {
					$formats[] = '%s'; /* a %f 6 tizedesre vágna; a PHP float-szöveg pontosan visszaolvasható */
				} elseif ( is_int( $value ) ) {
					$formats[] = '%d';
				} else {
					$formats[] = '%s';
				}
			}
			$wpdb->replace( self::table(), $row, $formats );
		} finally {
			self::$syncing = $was;
		}
	}

	/**
	 * @param int $listing_id Listing.
	 */
	public static function delete( $listing_id ) {
		global $wpdb;
		if ( self::ready() ) {
			$wpdb->delete( self::table(), array( 'listing_id' => (int) $listing_id ), array( '%d' ) );
		}
	}

	/* ------------------------------------------------------------------
	   Frissen tartás
	   ------------------------------------------------------------------ */

	/**
	 * @param int $listing_id Listing.
	 */
	public static function queue( $listing_id ) {
		$listing_id = (int) $listing_id;
		if ( self::$syncing || $listing_id <= 0 ) {
			return;
		}
		self::$queue[ $listing_id ] = true;
	}

	/**
	 * @param string  $new_status New.
	 * @param string  $old_status Old.
	 * @param WP_Post $post       Post.
	 */
	public static function on_transition( $new_status, $old_status, $post ) {
		if ( $post instanceof WP_Post && 'kmae_listing' === $post->post_type && $new_status !== $old_status ) {
			self::queue( (int) $post->ID );
		}
	}

	/**
	 * added/updated/deleted_post_meta: hirdetés-mezők, vagy a hirdetés egy képének kártya-adatai.
	 *
	 * @param mixed  $meta_ids  Meta ID(s).
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 */
	public static function on_meta( $meta_ids, $object_id, $meta_key ) {
		unset( $meta_ids );
		if ( self::$syncing ) {
			return;
		}
		$object_id = (int) $object_id;
		if ( in_array( $meta_key, self::WATCHED_META, true ) ) {
			if ( 'kmae_listing' === get_post_type( $object_id ) ) {
				self::queue( $object_id );
			}
			return;
		}
		if ( in_array( $meta_key, self::WATCHED_ATTACHMENT_META, true ) && 'attachment' === get_post_type( $object_id ) ) {
			$parent = (int) wp_get_post_parent_id( $object_id );
			if ( $parent && 'kmae_listing' === get_post_type( $parent ) ) {
				self::queue( $parent );
			}
		}
	}

	/**
	 * @param int $post_id Deleted post.
	 */
	public static function on_deleted( $post_id ) {
		$post_id = (int) $post_id;
		unset( self::$queue[ $post_id ] );
		self::delete( $post_id );
	}

	/** A kérés végén: minden sorba tett hirdetés egyszer épül újra. */
	public static function flush() {
		if ( ! self::$queue || ! self::ready() ) {
			return;
		}
		$ids         = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $listing_id ) {
			try {
				self::sync( $listing_id );
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				/* Az index járulékos: hiba esetén a következő mentés / újraépítés pótolja. */
			}
		}
	}

	/* ------------------------------------------------------------------
	   Teljes újraépítés: kötegelt, újrafuttatható (cron + admin gomb)
	   ------------------------------------------------------------------ */

	public static function start_rebuild() {
		update_option(
			self::REBUILD_OPTION,
			array(
				'running'  => 1,
				'cursor'   => 0,
				'done'     => 0,
				'started'  => time(),
				'finished' => 0,
			),
			false
		);
		if ( ! wp_next_scheduled( self::REBUILD_HOOK ) ) {
			wp_schedule_single_event( time() + 30, self::REBUILD_HOOK );
		}
	}

	/** @return array{running:int,cursor:int,done:int,started:int,finished:int} */
	public static function rebuild_state() {
		$state = get_option( self::REBUILD_OPTION );
		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'running'  => 0,
				'cursor'   => 0,
				'done'     => 0,
				'started'  => 0,
				'finished' => 0,
			)
		);
	}

	/**
	 * Egy adag: a kurzor utáni hirdetések (ID szerint), időkerettel. Ha elfogyott:
	 * a már nem létező hirdetések sorai törlődnek, és az újraépítés kész.
	 *
	 * @param int $limit Max. hirdetés.
	 * @return array{locked:bool,processed:int,running:bool}
	 */
	public static function run_batch( $limit = self::BATCH ) {
		global $wpdb;
		if ( ! self::ready() || get_transient( self::LOCK ) ) {
			return array(
				'locked'    => true,
				'processed' => 0,
				'running'   => (bool) self::rebuild_state()['running'],
			);
		}
		set_transient( self::LOCK, 1, 300 );

		$state     = self::rebuild_state();
		$deadline  = time() + self::TIME_BUDGET;
		$processed = 0;
		try {
			$ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID > %d ORDER BY ID ASC LIMIT %d", 'kmae_listing', (int) $state['cursor'], max( 1, (int) $limit ) ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			foreach ( $ids as $listing_id ) {
				try {
					self::sync( $listing_id );
				} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					/* Egy hibás hirdetés nem állítja meg a köteget. */
				}
				$state['cursor'] = $listing_id;
				++$state['done'];
				++$processed;
				if ( time() >= $deadline ) {
					break;
				}
			}

			if ( count( $ids ) < (int) $limit && $processed === count( $ids ) ) {
				self::purge_orphans();
				$state['running']  = 0;
				$state['finished'] = time();
			} else {
				$state['running'] = 1;
			}
			update_option( self::REBUILD_OPTION, $state, false );
		} finally {
			delete_transient( self::LOCK );
		}

		if ( $state['running'] && ! wp_next_scheduled( self::REBUILD_HOOK ) ) {
			wp_schedule_single_event( time() + 60, self::REBUILD_HOOK );
		}

		return array(
			'locked'    => false,
			'processed' => $processed,
			'running'   => (bool) $state['running'],
		);
	}

	/** Sorok, amelyekhez már nincs hirdetés (vagy kukában van). */
	private static function purge_orphans() {
		global $wpdb;
		$table = self::table();
		$wpdb->query( "DELETE i FROM {$table} i LEFT JOIN {$wpdb->posts} p ON p.ID = i.listing_id WHERE p.ID IS NULL OR p.post_type <> 'kmae_listing' OR p.post_status IN ('trash','auto-draft','inherit')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	public static function cron_run() {
		if ( self::rebuild_state()['running'] ) {
			self::run_batch( self::BATCH );
		}
	}

	/** @return array{rows:int,active:int} */
	public static function counts() {
		global $wpdb;
		if ( ! self::ready() ) {
			return array(
				'rows'   => 0,
				'active' => 0,
			);
		}
		$table = self::table();
		$row   = $wpdb->get_row( "SELECT COUNT(*) AS n, COALESCE(SUM(is_active), 0) AS a FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return array(
			'rows'   => $row ? (int) $row->n : 0,
			'active' => $row ? (int) $row->a : 0,
		);
	}

	/** Admin gomb (Albira → Beállítások): ha nem fut, új újraépítést indít; utána egy adagot azonnal lefuttat. */
	public static function handle_admin_rebuild() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kmae_browse_index_rebuild' ) ) {
			wp_die( esc_html__( 'Nincs jogosultság.', 'ki-mit-adna-erte' ) );
		}
		if ( ! self::ready() ) {
			self::create_table();
		}
		if ( ! self::rebuild_state()['running'] ) {
			self::start_rebuild();
		}
		$result = self::run_batch( self::BATCH );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'kmae-settings',
					'kmae_bi' => $result['locked'] ? 'locked' : ( $result['running'] ? 'running' : 'done' ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
