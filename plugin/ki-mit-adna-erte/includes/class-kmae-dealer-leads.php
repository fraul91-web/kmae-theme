<?php
/**
 * Csoportos kereskedői megkeresés — adatréteg (1.), jogosultság + találatok (3.), előnézet/küldés + cég-értesítés (4.),
 * a cég válasza „Érdekel” → chat / „Köszönöm, nem” (5. fázis).
 *
 * Egy magánszemély eladó egy aktív hirdetéséhez egyetlen gombnyomással megkeresést küld
 * a környékbeli, autót felvásárló / beszámító cégeknek. Minden cég külön sort kap
 * (`kmae_dealer_leads`), a válasza: „Érdekel” (→ chat) vagy „Köszönöm, nem”; válasz
 * nélkül 7 nap után lejár. A sorok sosem törlődnek (előzmény).
 *
 * A FŐ SZABÁLY: egy cég ugyanazt a hirdetést SOHA nem kapja meg kétszer — ezt a
 * UNIQUE KEY (listing_id, dealer_id) garantálja, az insert_batch() csendben átugorja.
 *
 * Időpontok: UTC (gmdate), nem a MySQL órája. Állapotváltás csak 'pending'-ből,
 * `WHERE status = 'pending'` feltétellel (dupla kattintás / párhuzamos kérés ellen).
 * A felületen user ID / lead ID helyett a `token` megy ki.
 *
 * Térkép: PROJECT-MEMORY/FEATURES/DEALER-LEADS.md
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Dealer_Leads {

	/* Saját séma-verzió: a KMAE_VERSION emelése (DB-upgrade, oldal-szinkron) nélkül jön létre. */
	const SCHEMA_VERSION = '2'; /* 2: dealer_price oszlop */
	const SCHEMA_OPTION  = 'kmae_dealer_leads_schema';
	const RETRY          = 'kmae_dealer_leads_retry';

	/* Kapcsoló (Albira → Beállítások). Kikapcsolva csak az adminnak él. */
	const OPTION = 'kmae_dealer_leads_enabled';

	const STATUS_PENDING    = 'pending';
	const STATUS_INTERESTED = 'interested';
	const STATUS_DECLINED   = 'declined';
	const STATUS_EXPIRED    = 'expired';
	const STATUS_CANCELLED  = 'cancelled';

	const PURPOSE_SELL    = 'sell';    /* „Eladnám” */
	const PURPOSE_TRADEIN = 'tradein'; /* „Beszámíttatnám” */
	const PURPOSE_BOTH    = 'both';    /* mindkettő: az eladó „Eladnám” ÉS „Beszámíttatnám” jelölt; cégnél a VAGY, soronként a tényleges (metszet) cél */

	const TTL_DAYS = 7;

	/* A kereskedői ár (Ft) határai: a küldésnél kötelező, 50 ezer – 500 millió. */
	const PRICE_MIN = 50000;
	const PRICE_MAX = 500000000;

	/* km; 0 = egész ország. */
	const RADII = array( 25, 50, 100, 200, 0 );

	/* Egy INSERT-be ennyi sor kerül. */
	const INSERT_CHUNK = 200;

	/* AJAX (4. fázis): nonce-action és a két végpont. */
	const NONCE          = 'kmae_dealer_leads';
	const ACTION_PREVIEW = 'kmae_dealer_leads_preview';
	const ACTION_SEND    = 'kmae_dealer_leads_send';

	/* Óránkénti cron: a lejárt, megválaszolatlan megkeresések lezárása (6. fázis). */
	const CRON_EXPIRE = 'kmae_expire_dealer_leads';

	/* Levél-kulcs a KMAE_Notifications::mail_catalog()-ban (a cég kikapcsolhatja). */
	const MAIL_KEY = 'dealer_lead';

	/* Válasz (5. fázis): admin-post action (= nonce-action is), a két gomb értéke, rendszerüzenet-kulcs. */
	const ACTION_ANSWER     = 'kmae_dealer_lead_answer';
	const ANSWER_INTERESTED = 'interested';
	const ANSWER_DECLINED   = 'declined';
	const SYSTEM_KEY        = 'dealer_lead:'; /* a KMAE_Chat_Offers::SYSTEM_KEY ('system:') után + lead ID */

	/** @var array<int,array> A válasz után értesítendő új megkeresések (send_queued_notifications). */
	private static $notify_queue = array();

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION_PREVIEW, array( __CLASS__, 'ajax_preview' ) );
		add_action( 'wp_ajax_' . self::ACTION_SEND, array( __CLASS__, 'ajax_send' ) );
		add_action( 'admin_post_' . self::ACTION_ANSWER, array( __CLASS__, 'handle_answer' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION_ANSWER, array( __CLASS__, 'handle_answer_nopriv' ) );
		add_action( self::CRON_EXPIRE, array( __CLASS__, 'run_expire_cron' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'on_trash_post' ) );
	}

	/** Hirdetés a kukába (wp_trash_post): a nyitott megkeresések 'cancelled' (idempotens; a force_delete is hívja a maga útján). */
	public static function on_trash_post( $post_id ) {
		if ( 'kmae_listing' === get_post_type( $post_id ) ) {
			self::on_listing_ended( (int) $post_id );
		}
	}

	public static function statuses() {
		return array(
			self::STATUS_PENDING,
			self::STATUS_INTERESTED,
			self::STATUS_DECLINED,
			self::STATUS_EXPIRED,
			self::STATUS_CANCELLED,
		);
	}

	public static function purposes() {
		return array( self::PURPOSE_SELL, self::PURPOSE_TRADEIN, self::PURPOSE_BOTH );
	}

	/**
	 * Az eladó választása (sell|tradein|both) + a cég jelölői → a soron tárolt TÉNYLEGES cél
	 * (a kettő metszete): 'sell' | 'tradein' | 'both'; null = a cég nem felel meg.
	 */
	public static function effective_purpose( $chosen, $buys, $tradein ) {
		$sell = $buys && ( self::PURPOSE_SELL === $chosen || self::PURPOSE_BOTH === $chosen );
		$tin  = $tradein && ( self::PURPOSE_TRADEIN === $chosen || self::PURPOSE_BOTH === $chosen );
		if ( $sell && $tin ) {
			return self::PURPOSE_BOTH;
		}
		if ( $sell ) {
			return self::PURPOSE_SELL;
		}
		return $tin ? self::PURPOSE_TRADEIN : null;
	}

	public static function table() {
		return KMAE_Database::table( 'dealer_leads' );
	}

	/**
	 * Kapcsoló: az opció be, VAGY a belépett user admin (kikapcsolva élőben kipróbálható).
	 */
	public static function is_enabled() {
		if ( get_option( self::OPTION ) ) {
			return true;
		}
		return current_user_can( 'manage_options' );
	}

	/* ------------------------------------------------------------------
	   Séma (KMAE_Activator::create_tables és maybe_upgrade hívja)
	   ------------------------------------------------------------------ */

	public static function ready() {
		return self::SCHEMA_VERSION === (string) get_option( self::SCHEMA_OPTION, '' );
	}

	/** Minden betöltéskor fut (KMAE_Activator::maybe_upgrade); olcsó (autoload opció). */
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

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			listing_id bigint(20) unsigned NOT NULL,
			seller_id bigint(20) unsigned NOT NULL,
			dealer_id bigint(20) unsigned NOT NULL,
			purpose varchar(16) NOT NULL DEFAULT 'sell',
			status varchar(16) NOT NULL DEFAULT 'pending',
			token char(32) NOT NULL,
			batch_id char(32) NOT NULL DEFAULT '',
			distance_km decimal(6,1) DEFAULT NULL,
			dealer_price bigint(20) unsigned DEFAULT NULL,
			conversation_id bigint(20) unsigned DEFAULT NULL,
			listing_title varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			expires_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			responded_at datetime DEFAULT NULL,
			updated_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY listing_dealer (listing_id,dealer_id),
			UNIQUE KEY token (token),
			KEY dealer_status (dealer_id,status),
			KEY seller_listing (seller_id,listing_id),
			KEY status_expires (status,expires_at),
			KEY batch_id (batch_id)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			set_transient( self::RETRY, 1, HOUR_IN_SECONDS ); /* nem jött létre: óránként újrapróbáljuk */
			return;
		}
		/* Meglévő (1-es sémájú) táblánál a dbDelta adja hozzá az új oszlopot; ha mégsem jött létre, a séma-opció marad régi. */
		if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $table . " LIKE 'dealer_price'" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			set_transient( self::RETRY, 1, HOUR_IN_SECONDS );
			return;
		}
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );
	}

	/* ------------------------------------------------------------------
	   Segédek
	   ------------------------------------------------------------------ */

	/** Most, UTC. */
	public static function now() {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/** 32 hex karakter, kriptográfiailag véletlen. */
	public static function new_token() {
		return bin2hex( random_bytes( 16 ) );
	}

	public static function is_token( $token ) {
		return is_string( $token ) && 1 === preg_match( '/^[a-f0-9]{32}$/', $token );
	}

	/** Adatbázis-sor → típusos tömb. */
	private static function row( $raw ) {
		if ( ! $raw ) {
			return null;
		}
		$raw = (array) $raw;
		return array(
			'id'              => (int) $raw['id'],
			'listing_id'      => (int) $raw['listing_id'],
			'seller_id'       => (int) $raw['seller_id'],
			'dealer_id'       => (int) $raw['dealer_id'],
			'purpose'         => (string) $raw['purpose'],
			'status'          => (string) $raw['status'],
			'token'           => (string) $raw['token'],
			'batch_id'        => (string) $raw['batch_id'],
			'distance_km'     => null === $raw['distance_km'] ? null : (float) $raw['distance_km'],
			'dealer_price'    => isset( $raw['dealer_price'] ) && (int) $raw['dealer_price'] > 0 ? (int) $raw['dealer_price'] : null,
			'conversation_id' => empty( $raw['conversation_id'] ) ? null : (int) $raw['conversation_id'],
			'listing_title'   => (string) $raw['listing_title'],
			'created_at'      => (string) $raw['created_at'],
			'expires_at'      => (string) $raw['expires_at'],
			'responded_at'    => null === $raw['responded_at'] ? null : (string) $raw['responded_at'],
			'updated_at'      => (string) $raw['updated_at'],
		);
	}

	private static function rows( $raws ) {
		return array_values( array_filter( array_map( array( __CLASS__, 'row' ), (array) $raws ) ) );
	}

	/* ------------------------------------------------------------------
	   Jogosultság + találatok (3. fázis)
	   ------------------------------------------------------------------ */

	/**
	 * Indíthat-e az eladó megkeresést ehhez a hirdetéshez?
	 * Feltételek: kapcsoló, a hirdetés szerzője, magánszemély, a hirdetés aktív és
	 * közzétett, NEM „Foglalt” (elfogadott ajánlat; eladott/lejárt ág is tiltott),
	 * és van koordinátája.
	 *
	 * @param int $listing_id Hirdetés.
	 * @param int $user_id    Eladó.
	 * @return true|WP_Error
	 */
	public static function can_start( $listing_id, $user_id ) {
		$listing_id = (int) $listing_id;
		$user_id    = (int) $user_id;

		if ( ! self::is_enabled() ) {
			return new WP_Error( 'dealer_leads_disabled', 'A kereskedői megkeresés még nem érhető el.' );
		}
		$post = $listing_id > 0 ? get_post( $listing_id ) : null;
		if ( ! $post || 'kmae_listing' !== $post->post_type ) {
			return new WP_Error( 'dealer_leads_no_listing', 'Ez a hirdetés nem található.' );
		}
		if ( $user_id <= 0 || (int) $post->post_author !== $user_id ) {
			return new WP_Error( 'dealer_leads_not_owner', 'Csak a saját hirdetésedhez indíthatsz megkeresést.' );
		}
		if ( ! KMAE_Auth::is_private_person( $user_id ) ) {
			return new WP_Error( 'dealer_leads_not_private', 'A kereskedői megkeresés magánszemély eladóknak érhető el.' );
		}
		if ( 'publish' !== $post->post_status || KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $listing_id ) ) {
			return new WP_Error( 'dealer_leads_inactive', 'Megkeresést csak aktív hirdetéshez küldhetsz.' );
		}
		/* Foglalt (elfogadott ajánlat) hirdetéshez nem indítunk új megkeresést; az eladott/lezárt ág is tiltott. */
		if ( class_exists( 'KMAE_Chat_Offers' ) && in_array( KMAE_Chat_Offers::listing_state( $listing_id ), array( 'reserved', 'sold', 'lapsed' ), true ) ) {
			return new WP_Error( 'dealer_leads_reserved', 'Ez a hirdetés most foglalt vagy már nem elérhető, ezért nem küldhető megkeresés.' );
		}
		if ( ! KMAE_Geo::listing_coords( $listing_id ) ) {
			return new WP_Error( 'dealer_leads_no_location', 'A hirdetés helyszíne nem ismert, ezért nem tudjuk a környékbeli cégeket megkeresni.' );
		}
		return true;
	}

	/**
	 * Kik kapnák a megkeresést? Egyetlen felhasználó-lekérdezés (a két jelölő meta
	 * valamelyike '1'), a többi PHP-ban, batch meta-cache-sel. Nem ír, nem küld.
	 *
	 * @param int         $listing_id Hirdetés.
	 * @param int         $radius_km  A RADII egyike (0 = egész ország).
	 * @param string|null $purpose    'sell' | 'tradein' | 'both' (= VAGY: bármelyik jelölős cég) | null (csak a sugár szerinti szűrés).
	 * @return array{radius:int,purpose:?string,in_radius:int,matching:int,contacted:int,new:int,new_dealers:array<int,?float>,new_dealer_purposes:array<int,string>,contacted_dealers:array<int,?float>}|WP_Error
	 */
	public static function match( $listing_id, $radius_km, $purpose = null ) {
		$listing_id = (int) $listing_id;

		if ( ! is_numeric( $radius_km ) || ! in_array( (int) $radius_km, self::RADII, true ) || (int) $radius_km != $radius_km ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
			return new WP_Error( 'dealer_leads_bad_radius', 'Ez a távolság nem választható.' );
		}
		$radius = (int) $radius_km;
		if ( null !== $purpose && ! in_array( $purpose, self::purposes(), true ) ) {
			return new WP_Error( 'dealer_leads_bad_purpose', 'Ez a cél nem választható.' );
		}
		$post = $listing_id > 0 ? get_post( $listing_id ) : null;
		if ( ! $post || 'kmae_listing' !== $post->post_type ) {
			return new WP_Error( 'dealer_leads_no_listing', 'Ez a hirdetés nem található.' );
		}
		$seller_id = (int) $post->post_author;
		$origin    = KMAE_Geo::listing_coords( $listing_id );
		if ( $radius > 0 && ! $origin ) {
			return new WP_Error( 'dealer_leads_no_location', 'A hirdetés helyszíne nem ismert, ezért nem tudjuk a környékbeli cégeket megkeresni.' );
		}

		$result = array(
			'radius'            => $radius,
			'purpose'           => $purpose,
			'in_radius'         => 0,
			'matching'          => 0,
			'contacted'         => 0,
			'new'               => 0,
			'new_dealers'       => array(),
			'new_dealer_purposes' => array(),
			'contacted_dealers' => array(),
		);

		/* Egyetlen lekérdezés: a két jelölő valamelyikét bekapcsolt felhasználók. */
		$query = new WP_User_Query(
			array(
				'fields'      => 'ID',
				'number'      => -1,
				'count_total' => false,
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'   => KMAE_Company::META_COMPANY_BUYS,
						'value' => '1',
					),
					array(
						'key'   => KMAE_Company::META_COMPANY_TRADEIN,
						'value' => '1',
					),
				),
			)
		);
		$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $query->get_results() ) ) ) );
		if ( ! $ids ) {
			return $result;
		}
		update_meta_cache( 'user', $ids ); /* a get_user_meta hívások innen mennek, nem külön lekérdezésből */

		$seller_blocks = $seller_id > 0 ? KMAE_User_Blocks::get_blocked( $seller_id ) : array();
		$contacted_ids = array_flip( self::contacted_dealer_ids( $listing_id ) );

		/* Kapcsoló KI (csak az admin próbálhatja): csak admin cég kapjon megkeresést, mert a valódi cég az Értesítések oldalt nem látná, és nem tudna válaszolni. */
		$admins_only = ! get_option( self::OPTION );

		$new       = array();
		$new_purp  = array();
		$contacted = array();
		foreach ( $ids as $dealer_id ) {
			if ( $dealer_id === $seller_id ) {
				continue;
			}
			if ( $admins_only && ! user_can( $dealer_id, 'manage_options' ) ) {
				continue;
			}
			$buys    = '1' === (string) get_user_meta( $dealer_id, KMAE_Company::META_COMPANY_BUYS, true );
			$tradein = '1' === (string) get_user_meta( $dealer_id, KMAE_Company::META_COMPANY_TRADEIN, true );
			if ( ! $buys && ! $tradein ) {
				continue;
			}

			/* Távolság: sugárnál kötelező érvényes telephely; „egész országnál” a koordináta nélküli cég is bent van. */
			$km     = null;
			/* Mint a KMAE_Geo::user_coords(), de a céges-fiók ellenőrzés (get_profile = felhasználónként 1 SQL) nélkül; az a szűrők UTÁN lent fut. */
			$coords = $origin ? KMAE_Settlements::coords( sanitize_key( KMAE_Company::get_county( $dealer_id ) ), KMAE_Company::get_city( $dealer_id ) ) : null;
			if ( $coords && $origin ) {
				$dist = KMAE_Geo::distance_km( $origin[0], $origin[1], $coords[0], $coords[1] );
				if ( $radius > 0 && $dist > $radius ) {
					continue;
				}
				$km = round( $dist, 1 );
			} elseif ( $radius > 0 ) {
				continue;
			}

			/* Tiltás mindkét irányban. */
			if ( isset( $seller_blocks[ $dealer_id ] ) || KMAE_User_Blocks::is_blocked( $dealer_id, $seller_id ) ) {
				continue;
			}
			/* Csak létező, céges fiók (a get_profile felhasználónként 1 lekérdezés, ezért a szűrők UTÁN). */
			if ( ! KMAE_Auth::is_company( $dealer_id ) ) {
				continue;
			}

			++$result['in_radius'];
			$effective = null === $purpose ? null : self::effective_purpose( $purpose, $buys, $tradein );
			if ( null !== $purpose && null === $effective ) {
				continue;
			}
			++$result['matching'];
			if ( isset( $contacted_ids[ $dealer_id ] ) ) {
				$contacted[ $dealer_id ] = $km;
			} else {
				$new[ $dealer_id ] = $km;
				if ( null !== $effective ) {
					$new_purp[ $dealer_id ] = $effective;
				}
			}
		}

		$by_distance = static function ( array $map ) {
			uksort(
				$map,
				static function ( $a, $b ) use ( $map ) {
					if ( $map[ $a ] === $map[ $b ] ) {
						return $a <=> $b;
					}
					if ( null === $map[ $a ] ) {
						return 1;
					}
					if ( null === $map[ $b ] ) {
						return -1;
					}
					return $map[ $a ] <=> $map[ $b ];
				}
			);
			return $map;
		};

		$result['new_dealers']       = $by_distance( $new );
		$result['new_dealer_purposes'] = array_intersect_key( array_replace( $result['new_dealers'], $new_purp ), $new_purp ); /* a new_dealers (távolság) sorrendjében */
		$result['contacted_dealers'] = $by_distance( $contacted );
		$result['contacted']         = count( $contacted );
		$result['new']               = count( $new );
		return $result;
	}

	/* ------------------------------------------------------------------
	   Előnézet + küldés (4. fázis): AJAX végpontok, értesítés
	   Szerződés: PROJECT-MEMORY/FEATURES/DEALER-LEADS.md „Végpontok”
	   ------------------------------------------------------------------ */

	public static function nonce() {
		return wp_create_nonce( self::NONCE );
	}

	/** Az Értesítések oldal (a cégek itt látják a megkereséseket, 7. fázis). */
	public static function notifications_url() {
		return class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'ertesitesek' ) : home_url( '/ertesitesek/' );
	}

	/** Egy megkeresés linkje a cégnek: token, sosem ID. */
	public static function lead_url( $token ) {
		return add_query_arg( 'kmae_lead', rawurlencode( (string) $token ), self::notifications_url() );
	}

	/** Sugár-választó feliratai (eladói UI, 8. fázis). */
	public static function radius_options() {
		$out = array();
		foreach ( self::RADII as $km ) {
			$out[] = array(
				'value' => $km,
				'label' => 0 === $km ? __( 'Egész ország', 'ki-mit-adna-erte' ) : sprintf( __( '%d km', 'ki-mit-adna-erte' ), $km ),
			);
		}
		return $out;
	}

	public static function purpose_options() {
		return array(
			array(
				'value' => self::PURPOSE_SELL,
				'label' => __( 'Eladnám', 'ki-mit-adna-erte' ),
			),
			array(
				'value' => self::PURPOSE_TRADEIN,
				'label' => __( 'Beszámíttatnám', 'ki-mit-adna-erte' ),
			),
		);
		/* Az UI két független kapcsolót rajzol (sell + tradein); a szerverre a kombináció megy: sell | tradein | both. */
	}

	/**
	 * Az eladói felület (8. fázis) adatai egy hirdetéshez, a belépett felhasználónak.
	 * Cég-azonosító / név nincs benne.
	 */
	public static function client_config( $listing_id ) {
		$listing_id = (int) $listing_id;
		$can        = self::can_start( $listing_id, get_current_user_id() );
		$public     = class_exists( 'KMAE_Seller' ) ? (int) KMAE_Seller::get_public_price( $listing_id ) : 0;
		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => self::nonce(),
			'listingId' => $listing_id,
			'available' => true === $can,
			'reason'    => is_wp_error( $can ) ? $can->get_error_message() : '',
			'actions'   => array(
				'preview' => self::ACTION_PREVIEW,
				'send'    => self::ACTION_SEND,
			),
			'radii'     => self::radius_options(),
			'purposes'  => self::purpose_options(),
			'ttlDays'   => self::TTL_DAYS,
			'priceMin'  => self::PRICE_MIN,
			'priceMax'  => self::PRICE_MAX,
			/* A hirdetés nyilvános ára (puha tipphez); „Ajánlatot vár” hirdetésnél null. */
			'listingPrice' => $public > 0 ? $public : null,
		);
	}

	/**
	 * Darabszámok a választott sugárra / célra (cég-azonosító, név SOHA nincs benne).
	 *
	 * @param int         $listing_id
	 * @param mixed       $radius_km  a RADII egyike
	 * @param string|null $purpose    null = még nem választott
	 * @param int         $user_id    eladó
	 * @return array{in_radius:int,matching:int,contacted:int,new:int,all_sent:bool}|WP_Error
	 */
	public static function preview( $listing_id, $radius_km, $purpose, $user_id ) {
		$can = self::can_start( $listing_id, $user_id );
		if ( is_wp_error( $can ) ) {
			return $can;
		}
		$m = self::match( $listing_id, $radius_km, $purpose );
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		return array(
			'in_radius' => (int) $m['in_radius'],
			'matching'  => (int) $m['matching'],
			'contacted' => (int) $m['contacted'],
			'new'       => (int) $m['new'],
			'all_sent'  => $m['matching'] > 0 && 0 === (int) $m['new'],
		);
	}

	/**
	 * „Elküldöm”: friss match() (a kliens számait nem hisszük el), hirdetésenkénti
	 * MySQL-zár alatt insert_batch(), majd az ÚJ cégek értesítése a válasz után.
	 * Dupla kattintás / két fül: a zár miatt a második kérés már 0 újat lát; a
	 * UNIQUE KEY zár nélkül is kizárja a dupla sort.
	 *
	 * @param mixed $price Kereskedői ár (Ft), kötelező: számjegyek (szóköz / pont megengedett), 50 000 – 500 000 000.
	 * @return array{sent:int,already:int,total_matching:int,all_sent:bool,message:string}|WP_Error
	 */
	public static function send( $listing_id, $radius_km, $purpose, $user_id, $price = null ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		$user_id    = (int) $user_id;

		if ( ! in_array( $purpose, self::purposes(), true ) ) {
			return new WP_Error( 'dealer_leads_bad_purpose', 'Válassz legalább egyet: eladnád vagy beszámíttatnád az autót.' );
		}
		$price = self::parse_price( $price );
		if ( null === $price || $price < self::PRICE_MIN || $price > self::PRICE_MAX ) {
			return new WP_Error( 'dealer_leads_bad_price', 'Add meg, mennyiért adnád az autót kereskedőnek.' );
		}
		$can = self::can_start( $listing_id, $user_id );
		if ( is_wp_error( $can ) ) {
			return $can;
		}

		$lock = 'kmae_dealer_leads_' . $listing_id;
		$got  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
		if ( null !== $got && '1' !== (string) $got ) {
			return new WP_Error( 'dealer_leads_busy', 'Épp küldjük a megkeresést. Várj pár másodpercet, és nézd meg újra.' );
		}
		$m     = self::match( $listing_id, $radius_km, $purpose );
		$leads = ! is_wp_error( $m ) && $m['new_dealers'] ? self::insert_batch( $listing_id, $user_id, $m['new_dealer_purposes'] ? $m['new_dealer_purposes'] : $purpose, $m['new_dealers'], null, $price ) : array();
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		if ( is_wp_error( $m ) ) {
			return $m;
		}

		$sent = count( $leads );
		if ( $m['new'] > 0 && 0 === $sent ) {
			return new WP_Error( 'dealer_leads_failed', 'Most nem sikerült elküldeni. Próbáld újra pár perc múlva.' );
		}
		if ( $leads ) {
			self::queue_notifications( $leads );
		}

		$all_sent = $m['matching'] > 0 && $sent >= $m['new'];
		if ( $sent > 0 ) {
			$message = sprintf( 'Elküldtük %d cégnek. %d napig válaszolhatnak.', $sent, self::TTL_DAYS );
		} elseif ( $all_sent ) {
			$message = 'Ezt az autót már minden megfelelő cégnek elküldted ebben a körzetben.';
		} elseif ( 0 === (int) $m['radius'] ) {
			$message = 'Most nincs olyan cég az oldalon, amelyiknek elküldhetnénk.';
		} else {
			$message = 'Ebben a körzetben most nincs megfelelő cég. Próbáld nagyobb távolsággal.';
		}

		return array(
			'sent'           => $sent,
			'already'        => (int) $m['contacted'],
			'total_matching' => (int) $m['matching'],
			'all_sent'       => $all_sent,
			'message'        => $message,
		);
	}

	/**
	 * A beírt kereskedői ár → egész Ft, vagy null, ha nem csak számjegy (a szóköz, nbsp és a pont ezreselválasztóként elmegy).
	 *
	 * @param mixed $raw
	 * @return int|null
	 */
	public static function parse_price( $raw ) {
		if ( is_int( $raw ) ) {
			return $raw;
		}
		$clean = preg_replace( '/[\s\x{00A0}.]/u', '', (string) $raw );
		if ( null === $clean || ! preg_match( '/^[0-9]{1,12}$/', $clean ) ) {
			return null;
		}
		return (int) $clean;
	}

	/**
	 * Csengő/push azonnal egy bump-pal (a fájlírás + push a KMAE_Live_Signal::flush-ban, shutdown 100);
	 * a levelek felépítése a válasz lezárása UTÁN (shutdown 90), a kiküldés a KMAE_Perf sorából (shutdown 110).
	 */
	private static function queue_notifications( array $leads ) {
		if ( ! self::$notify_queue ) {
			add_action( 'shutdown', array( __CLASS__, 'send_queued_notifications' ), 90 );
		}
		foreach ( $leads as $lead ) {
			self::$notify_queue[] = $lead;
		}
		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			$first = reset( $leads );
			KMAE_Live_Signal::bump(
				array_values( array_unique( array_map( 'intval', array_column( $leads, 'dealer_id' ) ) ) ),
				array(
					'title' => __( 'Új autó-megkeresés érkezett', 'ki-mit-adna-erte' ),
					'body'  => sprintf( __( '%1$s — %2$d napig válaszolhattok.', 'ki-mit-adna-erte' ), '' !== $first['listing_title'] ? $first['listing_title'] : __( 'Autó', 'ki-mit-adna-erte' ), self::TTL_DAYS ),
					'url'   => self::notifications_url(),
					'tag'   => 'kmae-dealer-lead-' . (int) $first['listing_id'],
				)
			);
		}
	}

	/** Shutdown (90): a sorban álló új megkeresések levelei, cégenként külön. */
	public static function send_queued_notifications() {
		$leads              = self::$notify_queue;
		self::$notify_queue = array();
		if ( ! $leads || ! class_exists( 'KMAE_Notifications' ) ) {
			return;
		}
		if ( class_exists( 'KMAE_Perf' ) ) {
			KMAE_Perf::finish_response();
		}
		if ( function_exists( 'cache_users' ) ) {
			cache_users( array_map( 'intval', array_column( $leads, 'dealer_id' ) ) );
		}
		$facts = array();
		foreach ( $leads as $lead ) {
			$lid = (int) $lead['listing_id'];
			if ( ! isset( $facts[ $lid ] ) ) {
				$facts[ $lid ] = class_exists( 'KMAE_Vehicle' ) ? (string) KMAE_Vehicle::get_swipe_subtitle( $lid ) : '';
			}
			KMAE_Notifications::notify_dealer_lead( $lead, $facts[ $lid ] );
		}
	}

	/* ---- AJAX ---- */

	/** Belépés + nonce; hibánál JSON-válasszal kilép. */
	private static function ajax_user() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'code'    => 'dealer_leads_login',
					'message' => 'Jelentkezz be újra, és próbáld meg még egyszer.',
				),
				403
			);
		}
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'code'    => 'dealer_leads_nonce',
					'message' => 'Lejárt az oldal. Frissítsd, és próbáld újra.',
				),
				403
			);
		}
		return get_current_user_id();
	}

	private static function ajax_error( WP_Error $error ) {
		wp_send_json_error(
			array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			400
		);
	}

	private static function posted( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax_user() ellenőrzi.
	}

	public static function ajax_preview() {
		$user_id = self::ajax_user();
		$purpose = sanitize_key( self::posted( 'purpose' ) );
		$result  = self::preview( absint( self::posted( 'listing_id' ) ), self::posted( 'radius' ), '' === $purpose ? null : $purpose, $user_id );
		if ( is_wp_error( $result ) ) {
			self::ajax_error( $result );
		}
		wp_send_json_success( $result );
	}

	public static function ajax_send() {
		$user_id = self::ajax_user();
		$result  = self::send( absint( self::posted( 'listing_id' ) ), self::posted( 'radius' ), sanitize_key( self::posted( 'purpose' ) ), $user_id, self::posted( 'price' ) );
		if ( is_wp_error( $result ) ) {
			self::ajax_error( $result );
		}
		wp_send_json_success( $result );
	}

	/* ------------------------------------------------------------------
	   A cég válasza (5. fázis): „Érdekel” → normál chat, „Köszönöm, nem” → semmi
	   Szerződés: PROJECT-MEMORY/FEATURES/DEALER-LEADS.md „Válasz”
	   ------------------------------------------------------------------ */

	/** Az Értesítések oldali válasz-űrlap célja (POST). */
	public static function answer_form_action() {
		return admin_url( 'admin-post.php' );
	}

	/**
	 * Az Értesítések oldali válasz-űrlap rejtett mezői (action + token + nonce).
	 * A két gombot a sablon adja: `name="answer"` value `interested` / `declined`.
	 *
	 * @param string $token A megkeresés tokenje (sosem ID).
	 * @param bool   $echo  Kiírja-e (a visszatérési érték mindig a HTML).
	 * @return string
	 */
	public static function answer_form_fields( $token, $echo = true ) {
		$html  = '<input type="hidden" name="action" value="' . esc_attr( self::ACTION_ANSWER ) . '">';
		$html .= '<input type="hidden" name="token" value="' . esc_attr( self::is_token( $token ) ? $token : '' ) . '">';
		$html .= wp_nonce_field( self::ACTION_ANSWER, '_wpnonce', false, false );
		if ( $echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fent escape-elve.
		}
		return $html;
	}

	/** admin-post, kijelentkezve: belépés, utána vissza a megkereséshez. */
	public static function handle_answer_nopriv() {
		$token = self::posted( 'token' );
		self::redirect( KMAE_Auth::login_url( self::is_token( $token ) ? self::lead_url( $token ) : self::notifications_url() ) );
	}

	/** admin-post, belépve: nonce → answer() → átirányítás. */
	public static function handle_answer() {
		if ( ! is_user_logged_in() ) {
			self::handle_answer_nopriv();
			return;
		}
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- itt ellenőrizzük.
		if ( ! wp_verify_nonce( $nonce, self::ACTION_ANSWER ) ) {
			self::redirect( self::answer_error_url( 'lead_forbidden' ) );
			return;
		}
		self::redirect( self::answer( self::posted( 'token' ), sanitize_key( self::posted( 'answer' ) ), get_current_user_id() ) );
	}

	private static function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}

	/** Értesítések oldal + `kmae_error=<kulcs>` (a kulcsok: DEALER-LEADS.md „Válasz”). */
	public static function answer_error_url( $code ) {
		return add_query_arg( 'kmae_error', sanitize_key( $code ), self::notifications_url() );
	}

	/**
	 * A válasz feldolgozása (a nonce-ot a hívó ellenőrizte). Nem lép ki: a cél-URL-t adja vissza.
	 * Megkeresésenkénti MySQL-zár alatt fut (dupla kattintás / két fül → egy chat, egy rendszerüzenet).
	 *
	 * @param string $token   A megkeresés tokenje.
	 * @param string $answer  'interested' | 'declined'.
	 * @param int    $user_id A belépett felhasználó (a megkeresés cége kell legyen).
	 * @return string Átirányítási URL (chat / Értesítések + kmae_success / kmae_error).
	 */
	public static function answer( $token, $answer, $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( ! in_array( $answer, array( self::ANSWER_INTERESTED, self::ANSWER_DECLINED ), true ) ) {
			return self::answer_error_url( 'lead_forbidden' );
		}
		$lead = self::get_by_token( $token );
		if ( ! $lead || $user_id <= 0 || $lead['dealer_id'] !== $user_id ) {
			return self::answer_error_url( 'lead_forbidden' );
		}
		if ( ! self::is_enabled() ) {
			return self::answer_error_url( 'lead_unavailable' );
		}

		$lock = 'kmae_dealer_lead_' . $lead['id'];
		$got  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
		if ( null !== $got && '1' !== (string) $got ) {
			return self::answer_error_url( 'lead_failed' );
		}
		try {
			return self::answer_locked( self::get( $lead['id'] ) ?: $lead, $answer );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	private static function answer_locked( array $lead, $answer ) {
		$done = self::answered_url( $lead, $answer );
		if ( null !== $done ) {
			return $done;
		}

		if ( self::ANSWER_DECLINED === $answer ) {
			if ( ! self::mark_declined( $lead['id'] ) ) {
				return self::answered_url( self::get( $lead['id'] ) ?: $lead, $answer, true );
			}
			/* Az eladónak csak a számok változnak az Értesítéseken: néma élő jelzés (push nélkül, mert nincs note). */
			if ( class_exists( 'KMAE_Live_Signal' ) ) {
				KMAE_Live_Signal::bump( array( $lead['seller_id'] ) );
			}
			return add_query_arg( 'kmae_success', 'lead_declined', self::notifications_url() );
		}

		/* „Érdekel” = chat indítása a cég (vevő) részéről: ugyanaz a kapu, mint az „Üzenet küldése”-nél. */
		if ( KMAE_Auth::should_gate_for_verification( $lead['dealer_id'] ) ) {
			return self::answer_error_url( 'lead_unverified' );
		}
		if ( true !== self::can_answer_interested( $lead ) ) {
			return self::answer_error_url( 'lead_unavailable' );
		}
		/* Előbb a válasz „lefoglalása” (csak pending + le nem járt sorból), utána a chat. */
		if ( ! self::mark_interested( $lead['id'] ) ) {
			return self::answered_url( self::get( $lead['id'] ) ?: $lead, $answer, true );
		}
		$lead['status'] = self::STATUS_INTERESTED;
		return self::finish_interested( $lead );
	}

	/**
	 * Már nem nyitott megkeresés → cél-URL; nyitott (pending, le nem járt) → null.
	 *
	 * @param array  $lead   Friss sor.
	 * @param string $answer A kért válasz.
	 * @param bool   $lost   Az állapotváltás most nem sikerült (más kérés / lejárat): nyitott sornál is hiba.
	 * @return string|null
	 */
	private static function answered_url( array $lead, $answer, $lost = false ) {
		switch ( $lead['status'] ) {
			case self::STATUS_INTERESTED:
				/* Már érdeklődött: vissza a chatjébe (ha még nincs beírva — félbeszakadt kérés —, most befejezzük). */
				if ( $lead['conversation_id'] ) {
					return KMAE_Pages::conversation_thread_url( $lead['conversation_id'] );
				}
				/* Félbeszakadt kérés: a befejezés előtt ugyanazok az ellenőrzések, mint az első „Érdekel”-nél (nincs visszaállítás, a sor 'interested' marad). */
				if ( KMAE_Auth::should_gate_for_verification( $lead['dealer_id'] ) ) {
					return self::answer_error_url( 'lead_unverified' );
				}
				if ( true !== self::can_answer_interested( $lead ) ) {
					return self::answer_error_url( 'lead_unavailable' );
				}
				return self::finish_interested( $lead );
			case self::STATUS_DECLINED:
				/* Ismételt „nem” = ugyanaz a siker; „nem” utáni „Érdekel” = már megválaszolta. */
				return self::ANSWER_DECLINED === $answer
					? add_query_arg( 'kmae_success', 'lead_declined', self::notifications_url() )
					: self::answer_error_url( 'lead_answered' );
			case self::STATUS_EXPIRED:
				return self::answer_error_url( 'lead_expired' );
			case self::STATUS_PENDING:
				if ( $lead['expires_at'] <= self::now() ) {
					return self::answer_error_url( 'lead_expired' );
				}
				return $lost ? self::answer_error_url( 'lead_failed' ) : null;
			default: /* cancelled: a hirdetést törölték / lezárták */
				return self::answer_error_url( 'lead_unavailable' );
		}
	}

	/**
	 * Indíthat-e a cég chatet ehhez a megkereséshez? (A can_start az ELADÓT nézi; ez a cég oldala.)
	 * A hirdetés még ugyanazé az eladóé, közzétett + aktív, nem Foglalt / eladott / lezárt,
	 * és egyik fél sem tiltotta a másikat.
	 *
	 * @return true|WP_Error
	 */
	public static function can_answer_interested( array $lead ) {
		$post = $lead['listing_id'] > 0 ? get_post( $lead['listing_id'] ) : null;
		if ( ! $post || 'kmae_listing' !== $post->post_type || (int) $post->post_author !== $lead['seller_id'] || $lead['dealer_id'] === $lead['seller_id'] ) {
			return new WP_Error( 'lead_unavailable', 'Ez a hirdetés már nem elérhető.' );
		}
		if ( 'publish' !== $post->post_status || KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $lead['listing_id'] ) ) {
			return new WP_Error( 'lead_unavailable', 'Ez a hirdetés már nem aktív.' );
		}
		if ( class_exists( 'KMAE_Chat_Offers' ) && in_array( KMAE_Chat_Offers::listing_state( $lead['listing_id'] ), array( 'reserved', 'sold', 'lapsed' ), true ) ) {
			return new WP_Error( 'lead_unavailable', 'Ez a hirdetés most foglalt vagy már nem elérhető.' );
		}
		if ( KMAE_User_Blocks::is_blocked( $lead['seller_id'], $lead['dealer_id'] ) || KMAE_User_Blocks::is_blocked( $lead['dealer_id'], $lead['seller_id'] ) ) {
			return new WP_Error( 'lead_unavailable', 'Ez a hirdetés már nem elérhető.' );
		}
		return true;
	}

	/** A rendszerüzenet szövege (a cég nevében megy az eladónak). */
	public static function interest_text( array $lead ) {
		$name = trim( (string) KMAE_Company::get_company_name( $lead['dealer_id'] ) );
		if ( '' === $name ) {
			$name = __( 'Egy', 'ki-mit-adna-erte' );
		}
		if ( self::PURPOSE_BOTH === $lead['purpose'] ) {
			/* translators: %s: company name */
			return sprintf( __( '%s kereskedés érdeklődik az autód iránt (eladás vagy beszámítás). Írjatok egymásnak itt a chatben.', 'ki-mit-adna-erte' ), $name );
		}
		return self::PURPOSE_TRADEIN === $lead['purpose']
			/* translators: %s: company name */
			? sprintf( __( '%s kereskedés érdeklődik az autód iránt, beszámításra. Írjatok egymásnak itt a chatben.', 'ki-mit-adna-erte' ), $name )
			/* translators: %s: company name */
			: sprintf( __( '%s kereskedés érdeklődik az autód iránt. Írjatok egymásnak itt a chatben.', 'ki-mit-adna-erte' ), $name );
	}

	/**
	 * „Érdekel” után (a sor már 'interested'): a normál chat (a cég = vevő), a rendszerüzenet
	 * egyszer, a chat azonosítója a sorba. Hibánál a sor visszaáll 'pending'-re (újrapróbálható).
	 * Ismételt futásnál (félbeszakadt kérés) nem küld második üzenetet / levelet.
	 */
	private static function finish_interested( array $lead ) {
		$conversation = KMAE_Conversations::create( $lead['listing_id'], $lead['dealer_id'], $lead['seller_id'] );
		if ( is_wp_error( $conversation ) || ! $conversation || empty( $conversation->id ) ) {
			self::log_failure( $lead, is_wp_error( $conversation ) ? $conversation->get_error_code() : 'conversation_missing' );
			self::rollback_interested( $lead['id'] );
			return self::answer_error_url( 'lead_failed' );
		}
		$conversation_id = (int) $conversation->id;

		/* Döntés: a create() a meglévő chatet státuszától függetlenül adja vissza; zárt chatbe a save_message nem ír,
		   ezért a megkeresés 'interested' marad ennek a (olvasható, zárt) chatnek az azonosítójával — a cég kártyáján
		   „Chat megnyitása” — és oda irányítjuk; nincs rendszerüzenet, nincs visszaállítás 'pending'-re (nem lenne végtelen lead_failed-hurok). */
		if ( KMAE_Conversations::STATUS_OPEN !== (string) $conversation->status ) {
			self::set_conversation( $lead['id'], $conversation_id );
			return KMAE_Pages::conversation_thread_url( $conversation_id );
		}

		/* Az Üzenetek lista csak üzenetes chatet mutat → egy rendszerüzenet a cég nevében:
		   az eladó olvasatlan jelvényt, levelet, pusht kap (kmae_conversation_message_created). */
		if ( ! self::has_interest_message( $conversation_id, $lead['id'] ) ) {
			$posted = KMAE_Chat_Offers::post_system_message( $conversation_id, $lead['dealer_id'], self::interest_text( $lead ), self::SYSTEM_KEY . $lead['id'] );
			if ( ! $posted ) {
				self::log_failure( $lead, 'system_message' );
				self::rollback_interested( $lead['id'] );
				return self::answer_error_url( 'lead_failed' );
			}
		}
		self::set_conversation( $lead['id'], $conversation_id );
		return KMAE_Pages::conversation_thread_url( $conversation_id );
	}

	private static function has_interest_message( $conversation_id, $lead_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE conversation_id = %d AND idempotency_key = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $conversation_id,
				KMAE_Chat_Offers::SYSTEM_KEY . self::SYSTEM_KEY . (int) $lead_id
			)
		);
	}

	/** Sikertelen chat-létrehozás után: csak EZ a sor, csak 'interested'-ből vissza 'pending'-re. */
	private static function rollback_interested( $id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . ' SET status = %s, responded_at = NULL, conversation_id = NULL, updated_at = %s WHERE id = %d AND status = %s', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::STATUS_PENDING,
				self::now(),
				(int) $id,
				self::STATUS_INTERESTED
			)
		);
		return 1 === (int) $wpdb->rows_affected;
	}

	private static function log_failure( array $lead, $reason ) {
		error_log( sprintf( '[kmae dealer lead] chat failed: lead %d, listing %d, reason %s', $lead['id'], $lead['listing_id'], $reason ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/* ------------------------------------------------------------------
	   Írás
	   ------------------------------------------------------------------ */

	/**
	 * Egy küldés („Elküldöm” kattintás): minden cégnek egy új 'pending' sor.
	 * A már megkeresett cégeket (bármilyen állapotban) a UNIQUE KEY csendben kihagyja.
	 *
	 * @param int                   $listing_id
	 * @param int                   $seller_id
	 * @param string|array<int,string> $purpose       'sell' | 'tradein' | 'both' az egész batchre, VAGY dealer_id => cél térkép (soronkénti tényleges cél; a térképből hiányzó cég kimarad)
	 * @param array<int,float|null> $dealer_distances dealer_id => távolság km-ben (null = ismeretlen)
	 * @param string|null           $title            cím-pillanatkép (null = KMAE_Vehicle::get_display_title)
	 * @param int|null              $price            kereskedői ár Ft-ban (a batch minden sorára; null = nincs megadva)
	 * @return array Az ÚJONNAN létrehozott sorok (row() formában); üres, ha nem jött létre új.
	 */
	public static function insert_batch( $listing_id, $seller_id, $purpose, array $dealer_distances, $title = null, $price = null ) {
		global $wpdb;

		$listing_id = (int) $listing_id;
		$seller_id  = (int) $seller_id;
		$per_row = is_array( $purpose );
		if ( $listing_id <= 0 || $seller_id <= 0 || ( ! $per_row && ! in_array( $purpose, self::purposes(), true ) ) ) {
			return array();
		}

		$dealers  = array();
		$purposes = array();
		foreach ( $dealer_distances as $dealer_id => $km ) {
			$dealer_id = (int) $dealer_id;
			if ( $dealer_id <= 0 || $dealer_id === $seller_id ) {
				continue;
			}
			$row_purpose = $per_row ? ( isset( $purpose[ $dealer_id ] ) ? $purpose[ $dealer_id ] : null ) : $purpose;
			if ( ! in_array( $row_purpose, self::purposes(), true ) ) {
				continue;
			}
			$purposes[ $dealer_id ] = $row_purpose;
			$dealers[ $dealer_id ]  = ( null === $km || '' === $km ) ? null : round( max( 0, (float) $km ), 1 );
		}
		if ( ! $dealers ) {
			return array();
		}

		if ( null === $title ) {
			$title = class_exists( 'KMAE_Vehicle' ) ? (string) KMAE_Vehicle::get_display_title( $listing_id ) : '';
		}
		$title = function_exists( 'mb_substr' ) ? mb_substr( (string) $title, 0, 255 ) : substr( (string) $title, 0, 255 );

		$price   = null === $price || (int) $price <= 0 ? null : (int) $price;
		$batch   = self::new_token();
		$now     = self::now();
		$expires = gmdate( 'Y-m-d H:i:s', time() + self::TTL_DAYS * DAY_IN_SECONDS );
		$table   = self::table();

		foreach ( array_chunk( $dealers, self::INSERT_CHUNK, true ) as $chunk ) {
			$values = array();
			$args   = array();
			foreach ( $chunk as $dealer_id => $km ) {
				$values[] = '(%d, %d, %d, %s, %s, %s, %s, ' . ( null === $km ? 'NULL' : '%f' ) . ', ' . ( null === $price ? 'NULL' : '%d' ) . ', %s, %s, %s, %s)';
				array_push( $args, $listing_id, $seller_id, $dealer_id, $purposes[ $dealer_id ], self::STATUS_PENDING, self::new_token(), $batch );
				if ( null !== $km ) {
					$args[] = $km;
				}
				if ( null !== $price ) {
					$args[] = $price;
				}
				array_push( $args, $title, $now, $expires, $now );
			}
			/* INSERT IGNORE: a (listing_id, dealer_id) ütközés = már megkeresett cég → kimarad. */
			$wpdb->query(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT IGNORE INTO {$table} (listing_id, seller_id, dealer_id, purpose, status, token, batch_id, distance_km, dealer_price, listing_title, created_at, expires_at, updated_at) VALUES " . implode( ', ', $values ),
					$args
				)
			);
		}

		return self::rows(
			$wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE batch_id = %s ORDER BY id ASC", $batch ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			)
		);
	}

	/**
	 * Állapotváltás csak 'pending'-ből (és csak le nem járt sorból). Igaz, ha ez a hívás váltott.
	 */
	private static function transition( $id, $to, array $extra_set = array(), array $extra_args = array() ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}
		$now  = self::now();
		$set  = array_merge( array( 'status = %s', 'updated_at = %s' ), $extra_set );
		$args = array_merge( array( $to, $now ), $extra_args, array( $id, self::STATUS_PENDING, $now ) );
		$wpdb->query(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'UPDATE ' . self::table() . ' SET ' . implode( ', ', $set ) . ' WHERE id = %d AND status = %s AND expires_at > %s',
				$args
			)
		);
		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * „Érdekel”. A chat azonosítója utólag is beírható (set_conversation), ha a hívó
	 * előbb „lefoglalja” a választ, és csak utána hozza létre a chatet.
	 */
	public static function mark_interested( $id, $conversation_id = 0 ) {
		$conversation_id = (int) $conversation_id;
		$now             = self::now();
		if ( $conversation_id > 0 ) {
			return self::transition( $id, self::STATUS_INTERESTED, array( 'responded_at = %s', 'conversation_id = %d' ), array( $now, $conversation_id ) );
		}
		return self::transition( $id, self::STATUS_INTERESTED, array( 'responded_at = %s' ), array( $now ) );
	}

	/** „Köszönöm, nem”. */
	public static function mark_declined( $id ) {
		return self::transition( $id, self::STATUS_DECLINED, array( 'responded_at = %s' ), array( self::now() ) );
	}

	/** Az 'interested' sorhoz a létrejött chat azonosítója (csak ha még nincs beírva). */
	public static function set_conversation( $id, $conversation_id ) {
		global $wpdb;
		$id              = (int) $id;
		$conversation_id = (int) $conversation_id;
		if ( $id <= 0 || $conversation_id <= 0 ) {
			return false;
		}
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . ' SET conversation_id = %d, updated_at = %s WHERE id = %d AND status = %s AND conversation_id IS NULL', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$conversation_id,
				self::now(),
				$id,
				self::STATUS_INTERESTED
			)
		);
		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Válasz nélküli, lejárt megkeresések → 'expired' (cron, 6. fázis).
	 *
	 * @param string|null $now   UTC 'Y-m-d H:i:s' (teszthez); null = most.
	 * @param int         $limit egy futásban legfeljebb ennyi sor
	 * @return int[] a ténylegesen lejárttá tett sorok azonosítói
	 */
	public static function expire_due( $now = null, $limit = 500 ) {
		if ( ! self::ready() ) {
			return array();
		}
		global $wpdb;
		$now   = null === $now ? self::now() : (string) $now;
		$table = self::table();
		$ids   = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE status = %s AND expires_at <= %s ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					self::STATUS_PENDING,
					$now,
					max( 1, (int) $limit )
				)
			)
		);
		return self::move_pending( $ids, self::STATUS_EXPIRED, $now );
	}

	/**
	 * A hirdetés minden nyitott megkeresése → 'cancelled' (hirdetés törlése / lezárása, 6. fázis).
	 *
	 * @return int[] a ténylegesen visszavont sorok azonosítói
	 */
	public static function cancel_for_listing( $listing_id ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return array();
		}
		$table = self::table();
		$ids   = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE listing_id = %d AND status = %s ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$listing_id,
					self::STATUS_PENDING
				)
			)
		);
		return self::move_pending( $ids, self::STATUS_CANCELLED, self::now() );
	}

	/**
	 * A hirdetés véget ért (törlés / lejárat / eladás-lezárás): nyitott megkeresései 'cancelled',
	 * az érintett cégek Értesítések oldala NÉMÁN (note nélkül = nincs push, nincs levél) frissül.
	 * A „Foglalt” állapot NEM ér véget: az elfogadás visszavonható („Mégse kelt el”), ezért ott nincs hívás.
	 *
	 * @return int[] a visszavont sorok azonosítói
	 */
	public static function on_listing_ended( $listing_id ) {
		if ( ! self::ready() ) {
			return array();
		}
		$ids = self::cancel_for_listing( $listing_id );
		if ( $ids && class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( self::parties_of( $ids, 'dealer_id' ) );
		}
		return $ids;
	}

	/**
	 * Fiók-törlés, cég oldal: a cég nyitott megkeresései 'cancelled' (a sorok maradnak, előzmény).
	 * A fiókját törlő cég nem kap jelzést; az érintett eladók Értesítések oldala némán frissül.
	 *
	 * @return int[] a visszavont sorok azonosítói
	 */
	public static function cancel_for_dealer( $dealer_id ) {
		if ( ! self::ready() ) {
			return array();
		}
		global $wpdb;
		$dealer_id = (int) $dealer_id;
		if ( $dealer_id <= 0 ) {
			return array();
		}
		$ids = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					'SELECT id FROM ' . self::table() . ' WHERE dealer_id = %d AND status = %s ORDER BY id ASC', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$dealer_id,
					self::STATUS_PENDING
				)
			)
		);
		$done = self::move_pending( $ids, self::STATUS_CANCELLED, self::now() );
		if ( $done && class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( self::parties_of( $done, 'seller_id' ) );
		}
		return $done;
	}

	/**
	 * „Újraindítás tiszta lappal” (admin): a hirdetés MINDEN megkeresése törlődik (előzmény sincs).
	 * Jelzést nem küld; a hívó (KMAE_Listings::reset_listing) a visszaadott felhasználókat a saját némított bump-jába veszi.
	 *
	 * @return int[] az érintett felhasználók (cégek + eladó)
	 */
	public static function delete_for_listing( $listing_id ) {
		if ( ! self::ready() ) {
			return array();
		}
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return array();
		}
		$table = self::table();
		$rows  = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT dealer_id, seller_id FROM {$table} WHERE listing_id = %d", $listing_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$users = array();
		foreach ( $rows as $r ) {
			$r       = (array) $r;
			$users[] = (int) $r['dealer_id'];
			$users[] = (int) $r['seller_id'];
		}
		if ( $rows ) {
			$wpdb->delete( $table, array( 'listing_id' => $listing_id ), array( '%d' ) );
		}
		return array_values( array_unique( array_filter( $users ) ) );
	}

	/**
	 * Óránkénti cron (CRON_EXPIRE): a lejárt, válasz nélküli megkeresések → 'expired'. Levél / push nincs;
	 * az érintett cégek és eladók Értesítések oldala némán frissül (1 bump az összes érintettre).
	 */
	public static function run_expire_cron() {
		$all = array();
		for ( $i = 0; $i < 20; $i++ ) { /* 500-asával; a felső korlát csak végtelen ciklus ellen */
			$ids = self::expire_due();
			$all = array_merge( $all, $ids );
			if ( count( $ids ) < 500 ) {
				break;
			}
		}
		if ( $all && class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( array_merge( self::parties_of( $all, 'dealer_id' ), self::parties_of( $all, 'seller_id' ) ) );
		}
		return $all;
	}

	/**
	 * Sor-azonosítókhoz tartozó felhasználók (egy oszlop), egyedítve.
	 *
	 * @param int[]  $ids
	 * @param string $column 'dealer_id' | 'seller_id'
	 * @return int[]
	 */
	private static function parties_of( array $ids, $column ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids || ! in_array( $column, array( 'dealer_id', 'seller_id' ), true ) ) {
			return array();
		}
		$out = array();
		foreach ( array_chunk( $ids, 500 ) as $chunk ) {
			$col = (array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT {$column} FROM " . self::table() . ' WHERE id IN (' . implode( ', ', array_fill( 0, count( $chunk ), '%d' ) ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$chunk
				)
			);
			$out = array_merge( $out, array_map( 'intval', $col ) );
		}
		return array_values( array_unique( array_filter( $out ) ) );
	}

	/** Soronként, 'pending' feltétellel: csak a ténylegesen váltott sorok kerülnek a válaszba. */
	private static function move_pending( array $ids, $to, $now ) {
		global $wpdb;
		$done = array();
		foreach ( $ids as $id ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . self::table() . ' SET status = %s, updated_at = %s WHERE id = %d AND status = %s', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$to,
					$now,
					(int) $id,
					self::STATUS_PENDING
				)
			);
			if ( 1 === (int) $wpdb->rows_affected ) {
				$done[] = (int) $id;
			}
		}
		return $done;
	}

	/* ------------------------------------------------------------------
	   Olvasás
	   ------------------------------------------------------------------ */

	public static function get( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return null;
		}
		return self::row(
			$wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		if ( ! self::is_token( $token ) ) {
			return null;
		}
		return self::row(
			$wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Minden cég, akit ezzel a hirdetéssel valaha megkerestünk (bármilyen állapot).
	 *
	 * @return int[]
	 */
	public static function contacted_dealer_ids( $listing_id ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return array();
		}
		return array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare( 'SELECT dealer_id FROM ' . self::table() . ' WHERE listing_id = %d ORDER BY id ASC', $listing_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			)
		);
	}

	private static function empty_counts() {
		$counts = array_fill_keys( self::statuses(), 0 );
		$counts['total'] = 0;
		return $counts;
	}

	/**
	 * Darabszám állapotonként (a nyers tárolt állapot; a cron előtt lejárt sor még 'pending').
	 *
	 * @return array{pending:int,interested:int,declined:int,expired:int,cancelled:int,total:int}
	 */
	public static function counts_for_listing( $listing_id ) {
		global $wpdb;
		$counts     = self::empty_counts();
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return $counts;
		}
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM ' . self::table() . ' WHERE listing_id = %d GROUP BY status', $listing_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $rows as $r ) {
			$r = (array) $r;
			if ( isset( $counts[ $r['status'] ] ) ) {
				$counts[ $r['status'] ] = (int) $r['n'];
			}
			$counts['total'] += (int) $r['n'];
		}
		return $counts;
	}

	/**
	 * A cég megkeresései, legújabb elöl.
	 *
	 * @param int      $dealer_id
	 * @param int      $limit
	 * @param string[] $statuses üres = mind
	 */
	public static function list_for_dealer( $dealer_id, $limit = 50, array $statuses = array() ) {
		if ( ! self::ready() ) {
			return array();
		}
		global $wpdb;
		$dealer_id = (int) $dealer_id;
		if ( $dealer_id <= 0 ) {
			return array();
		}
		$statuses = array_values( array_intersect( $statuses, self::statuses() ) );
		$where    = 'dealer_id = %d';
		$args     = array( $dealer_id );
		if ( $statuses ) {
			$where .= ' AND status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ')';
			$args   = array_merge( $args, $statuses );
		}
		$args[] = max( 1, (int) $limit );
		return self::rows(
			$wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT %d", $args ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			)
		);
	}

	/**
	 * Az eladó megkeresései hirdetésenként (a legutóbb küldött hirdetés elöl).
	 *
	 * @return array<int,array{listing_id:int,listing_title:string,counts:array,last_sent_at:string,dealer_price:?int,leads:array}>
	 */
	public static function list_for_seller( $seller_id ) {
		if ( ! self::ready() ) {
			return array();
		}
		global $wpdb;
		$seller_id = (int) $seller_id;
		if ( $seller_id <= 0 ) {
			return array();
		}
		$leads  = self::rows(
			$wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE seller_id = %d ORDER BY created_at DESC, id DESC', $seller_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			)
		);
		$groups = array();
		foreach ( $leads as $lead ) {
			$lid = $lead['listing_id'];
			if ( ! isset( $groups[ $lid ] ) ) {
				$groups[ $lid ] = array(
					'listing_id'    => $lid,
					'listing_title' => $lead['listing_title'],
					'counts'        => self::empty_counts(),
					'last_sent_at'  => $lead['created_at'],
					'dealer_price'  => $lead['dealer_price'], /* a legutóbbi küldésé (a sorok legújabb elöl) */
					'leads'         => array(),
				);
			}
			$groups[ $lid ]['counts'][ $lead['status'] ] = ( $groups[ $lid ]['counts'][ $lead['status'] ] ?? 0 ) + 1;
			++$groups[ $lid ]['counts']['total'];
			$groups[ $lid ]['leads'][] = $lead;
		}
		return array_values( $groups );
	}

	/**
	 * Fejléc-csengő jelvény: a cég válaszra váró megkeresései. Nem céges fiókra / kikapcsolt
	 * funkcióra 0 (a csengő csak a CÉG beérkező kéréseit számolja; az eladó „érdekel” válaszát
	 * a chat / Üzenetek jelvény hozza).
	 */
	public static function bell_count( $user_id ) {
		if ( ! self::ready() ) {
			return 0;
		}
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! self::is_enabled() || ! KMAE_Auth::is_company( $user_id ) ) {
			return 0;
		}
		return self::count_pending_for_dealer( $user_id );
	}

	/** A cég még megválaszolható (pending, nem lejárt) megkereséseinek száma (jelvényhez). */
	public static function count_pending_for_dealer( $dealer_id ) {
		if ( ! self::ready() ) {
			return 0;
		}
		global $wpdb;
		$dealer_id = (int) $dealer_id;
		if ( $dealer_id <= 0 ) {
			return 0;
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE dealer_id = %d AND status = %s AND expires_at > %s', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$dealer_id,
				self::STATUS_PENDING,
				self::now()
			)
		);
	}
}
