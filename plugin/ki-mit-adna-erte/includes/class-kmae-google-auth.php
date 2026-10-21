<?php
/**
 * Belépés Google-fiókkal.
 *
 * A folyamat (OAuth 2.0 „authorization code"):
 *   1. A látogató a gombra kattint → átirányítjuk a Google-höz.
 *   2. A Google visszaküldi egy egyszer használatos kóddal.
 *   3. A kódot a szerverünk cseréli ki adatokra — így a böngésző nem
 *      hamisíthatja meg, hogy ki lépett be.
 *   4. Ha van már fiók ezzel az e-maillel, abba léptetünk be; ha nincs,
 *      létrehozzuk jelszó nélkül.
 *   5. Ami a Google-től nem jöhet (telefonszám, magánszemély/cég, ÁSZF),
 *      azt egy rövid képernyőn kérjük be — csak az első alkalommal.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Google_Auth {

	/** A Google-fiók állandó azonosítója (nem az e-mail — az változhat). */
	const META_SUB = 'kmae_google_sub';

	/** A keresztnév külön: köszönni ezzel természetes, nem a teljes névvel. */
	const META_GIVEN = 'kmae_given_name';

	/** Igaz, amíg a kötelező adatok hiányoznak az első belépés után. */
	const META_PENDING = 'kmae_google_pending';

	const AUTH_ENDPOINT  = 'https://accounts.google.com/o/oauth2/v2/auth';
	const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_handle_request' ) );
		add_action( 'admin_post_kmae_google_complete', array( __CLASS__, 'handle_complete' ) );
		add_action( 'template_redirect', array( __CLASS__, 'enforce_completion' ) );
		add_filter( 'manage_users_columns', array( __CLASS__, 'admin_user_column' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'admin_user_column_value' ), 10, 3 );
	}

	/* ------------------------------------------------------------------
	   Félbehagyott regisztráció
	   ------------------------------------------------------------------ */

	/**
	 * Aki a kiegészítő képernyőt elhagyja, félkész fiókkal maradna: nincs
	 * telefonszáma (a kapcsolatfeloldás alapja), nincs fióktípusa, és nem
	 * fogadta el a feltételeket. Ilyen fiókkal nem engedjük használni az
	 * oldalt — bármelyik oldalra lép, visszatereljük a befejezéshez.
	 */
	public static function enforce_completion() {
		if ( is_admin() || wp_doing_ajax() || ! is_user_logged_in() ) {
			return;
		}

		/* Rendszergazdát soha nem zárunk ki a saját oldalából. */
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! self::needs_completion( get_current_user_id() ) ) {
			return;
		}

		/* A befejező képernyő maga, különben körbe-körbe irányítanánk. */
		if ( is_page( 'regisztracio' ) || isset( $_GET['kmae_complete'] ) ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'kmae_complete', '1', KMAE_Pages::url( 'regisztracio' ) ) );
		exit;
	}

	/**
	 * A felhasználólistában lássuk, melyik fiók maradt félbe.
	 *
	 * @param array $columns Oszlopok.
	 * @return array
	 */
	public static function admin_user_column( $columns ) {
		$columns['kmae_status'] = __( 'Albira fiók', 'ki-mit-adna-erte' );
		return $columns;
	}

	/**
	 * @param string $output      Eddigi tartalom.
	 * @param string $column_name Oszlop neve.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public static function admin_user_column_value( $output, $column_name, $user_id ) {
		if ( 'kmae_status' !== $column_name ) {
			return $output;
		}

		if ( self::needs_completion( $user_id ) ) {
			return '<span style="color:#b32d2e;font-weight:600;">'
				. esc_html__( 'Befejezetlen regisztráció', 'ki-mit-adna-erte' )
				. '</span>';
		}

		if ( get_user_meta( $user_id, self::META_SUB, true ) ) {
			return esc_html__( 'Google-fiókkal', 'ki-mit-adna-erte' );
		}

		return '—';
	}

	/* ------------------------------------------------------------------
	   Beállítások
	   ------------------------------------------------------------------ */

	public static function client_id() {
		return trim( (string) get_option( 'kmae_google_client_id', '' ) );
	}

	public static function client_secret() {
		return trim( (string) get_option( 'kmae_google_client_secret', '' ) );
	}

	/** Addig nem mutatjuk a gombot, amíg nincsenek meg a kulcsok. */
	public static function is_configured() {
		return '' !== self::client_id() && '' !== self::client_secret();
	}

	/** Ezt a címet kell beírni a Google Console „Redirect URI" mezőjébe. */
	public static function redirect_uri() {
		return add_query_arg( 'kmae_google', 'callback', home_url( '/' ) );
	}

	/* ------------------------------------------------------------------
	   Indítás
	   ------------------------------------------------------------------ */

	/**
	 * A gomb célcíme: innen megy a látogató a Google-höz.
	 *
	 * @param string $redirect_to Ahova belépés után visszatér.
	 * @return string
	 */
	public static function start_url( $redirect_to = '' ) {
		return add_query_arg(
			array(
				'kmae_google' => 'start',
				'redirect_to' => rawurlencode( $redirect_to ? $redirect_to : home_url( '/' ) ),
			),
			home_url( '/' )
		);
	}

	public static function maybe_handle_request() {
		$action = isset( $_GET['kmae_google'] ) ? sanitize_key( wp_unslash( $_GET['kmae_google'] ) ) : '';
		if ( '' === $action || ! self::is_configured() ) {
			return;
		}

		if ( 'start' === $action ) {
			self::redirect_to_google();
		} elseif ( 'callback' === $action ) {
			self::handle_callback();
		}
	}

	private static function redirect_to_google() {
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' );

		/* A „state" köti össze az indítást a visszatéréssel — e nélkül más is
		   megpróbálhatná lejátszani a visszatérést a nevünkben. */
		$state = wp_generate_password( 32, false );
		set_transient( 'kmae_google_' . $state, $redirect_to, 15 * MINUTE_IN_SECONDS );

		$url = add_query_arg(
			array(
				'client_id'     => rawurlencode( self::client_id() ),
				'redirect_uri'  => rawurlencode( self::redirect_uri() ),
				'response_type' => 'code',
				'scope'         => rawurlencode( 'openid email profile' ),
				'state'         => $state,
				'prompt'        => 'select_account',
			),
			self::AUTH_ENDPOINT
		);

		wp_redirect( $url );
		exit;
	}

	/* ------------------------------------------------------------------
	   Visszatérés a Google-től
	   ------------------------------------------------------------------ */

	private static function handle_callback() {
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

		$redirect_to = $state ? get_transient( 'kmae_google_' . $state ) : false;
		if ( $state ) {
			delete_transient( 'kmae_google_' . $state );
		}

		if ( false === $redirect_to || '' === $code ) {
			self::fail( 'google_state' );
		}

		$profile = self::fetch_profile( $code );
		if ( is_wp_error( $profile ) ) {
			self::fail( $profile->get_error_code() );
		}

		$user_id = self::resolve_user( $profile );
		if ( is_wp_error( $user_id ) ) {
			self::fail( $user_id->get_error_code() );
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		/* Minden Google-belépésnél beállítjuk, nem csak a kiegészítő képernyő
		   végén: aki a javítás előtt regisztrált, annál az a lépés már régen
		   lefutott, és sosem futna le újra — a fiókja örökre „nincs
		   megerősítve" maradna. A Google minden belépéskor igazolja a címet,
		   így ez a helyes pont rá. */
		KMAE_Auth::mark_email_verified( $user_id );

		/* Az első belépés után a hiányzó adatokat egy rövid képernyőn kérjük. */
		if ( get_user_meta( $user_id, self::META_PENDING, true ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_complete', '1', KMAE_Pages::url( 'regisztracio' ) ) );
			exit;
		}

		wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
		exit;
	}

	/**
	 * A kódot kicseréljük a Google-nél, és kiolvassuk a fiók adatait.
	 *
	 * @param string $code Egyszer használatos kód.
	 * @return array|WP_Error sub, email, name
	 */
	private static function fetch_profile( $code ) {
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'timeout' => 15,
				'body'    => array(
					'code'          => $code,
					'client_id'     => self::client_id(),
					'client_secret' => self::client_secret(),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'google_network' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['id_token'] ) ) {
			return new WP_Error( 'google_token' );
		}

		$claims = self::decode_id_token( $body['id_token'] );
		if ( empty( $claims['email'] ) || empty( $claims['sub'] ) ) {
			return new WP_Error( 'google_token' );
		}

		/* Meg nem erősített címmel nem léptetünk be: azzal más fiókjához
		   lehetne hozzáférni. */
		if ( isset( $claims['email_verified'] ) && ! filter_var( $claims['email_verified'], FILTER_VALIDATE_BOOLEAN ) ) {
			return new WP_Error( 'google_unverified' );
		}

		return array(
			'sub'   => sanitize_text_field( $claims['sub'] ),
			'email' => sanitize_email( $claims['email'] ),
			'name'  => isset( $claims['name'] ) ? sanitize_text_field( $claims['name'] ) : '',
			/* A Google külön adja a keresztnevet — köszönéshez ezt használjuk. */
			'given' => isset( $claims['given_name'] ) ? sanitize_text_field( $claims['given_name'] ) : '',
		);
	}

	/**
	 * Az azonosító jegy középső szelete hordozza az adatokat. Aláírást nem
	 * ellenőrzünk: a jegyet közvetlenül a Google-től kaptuk, HTTPS-en, a
	 * saját titkunkkal hitelesítve — nem a böngészőn keresztül érkezett.
	 *
	 * @param string $id_token JWT.
	 * @return array
	 */
	private static function decode_id_token( $id_token ) {
		$parts = explode( '.', $id_token );
		if ( count( $parts ) < 2 ) {
			return array();
		}

		$payload = base64_decode( strtr( $parts[1], '-_', '+/' ) . str_repeat( '=', 3 - ( 3 + strlen( $parts[1] ) ) % 4 ) );
		$claims  = json_decode( (string) $payload, true );

		return is_array( $claims ) ? $claims : array();
	}

	/**
	 * Meglévő fiók megkeresése, vagy új létrehozása.
	 *
	 * @param array $profile sub, email, name.
	 * @return int|WP_Error
	 */
	private static function resolve_user( $profile ) {
		/* 1. Akit már összekötöttünk ezzel a Google-fiókkal. */
		$existing = get_users(
			array(
				'meta_key'   => self::META_SUB,
				'meta_value' => $profile['sub'],
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		if ( ! empty( $existing ) ) {
			self::store_given_name( (int) $existing[0], $profile );
			return (int) $existing[0];
		}

		/* 2. Aki korábban jelszóval regisztrált ugyanezzel a címmel: a Google
		   igazolta, hogy övé a cím, ezért a meglévő fiókjába léptetjük, és
		   összekötjük a kettőt. Mostantól bármelyik módon beléphet. */
		$by_email = get_user_by( 'email', $profile['email'] );
		if ( $by_email ) {
			update_user_meta( $by_email->ID, self::META_SUB, $profile['sub'] );
			self::store_given_name( (int) $by_email->ID, $profile );
			return (int) $by_email->ID;
		}

		/* 3. Új fiók, jelszó nélkül. Csak a keresztnév kerül a nyilvános névre —
		   a családnevet adatvédelmi okból nem tároljuk. */
		$public_name = self::resolve_given_name( $profile );
		$user_id     = wp_insert_user(
			array(
				'user_login'   => self::unique_login( $profile['email'] ),
				'user_email'   => $profile['email'],
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $public_name ? $public_name : $profile['email'],
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'google_create' );
		}

		update_user_meta( $user_id, self::META_SUB, $profile['sub'] );
		update_user_meta( $user_id, self::META_PENDING, 1 );
		self::store_given_name( (int) $user_id, $profile );

		if ( class_exists( 'KMAE_Auth' ) && method_exists( 'KMAE_Auth', 'ensure_subscriber_caps' ) ) {
			KMAE_Auth::ensure_subscriber_caps();
		}

		return (int) $user_id;
	}

	/**
	 * Keresztnév a Google-profilból. Ha nincs given_name, a teljes név első
	 * szava — a családnevet soha nem tartjuk meg.
	 *
	 * @param array $profile A Google-tól kapott adatok.
	 * @return string
	 */
	private static function resolve_given_name( $profile ) {
		$given = isset( $profile['given'] ) ? trim( (string) $profile['given'] ) : '';

		if ( '' === $given && ! empty( $profile['name'] ) ) {
			$parts = preg_split( '/\s+/u', trim( (string) $profile['name'] ) );
			$given = is_array( $parts ) && ! empty( $parts[0] ) ? $parts[0] : '';
		}

		if ( '' === $given || is_email( $given ) ) {
			return '';
		}

		return $given;
	}

	/**
	 * Keresztnév mentése meta + display_name. Csak magánszemélynél, és csak
	 * amíg a felhasználó nem állított be saját megjelenített nevet
	 * (kmae_public_name_set). Cégnél a cégnév marad. A teljes nevet / családnevet
	 * sehova nem írjuk.
	 *
	 * @param int   $user_id User ID.
	 * @param array $profile A Google-tól kapott adatok.
	 */
	private static function store_given_name( $user_id, $profile ) {
		$given = self::resolve_given_name( $profile );
		if ( '' === $given ) {
			return;
		}

		/* A profilban / céges űrlapon megadott név győz — ne írjuk felül. */
		if ( get_user_meta( $user_id, 'kmae_public_name_set', true ) ) {
			return;
		}

		update_user_meta( $user_id, self::META_GIVEN, $given );

		/* Cégfióknál a nyilvános név a cégnév — a keresztnevet csak metában tartjuk. */
		if ( class_exists( 'KMAE_Auth' ) && method_exists( 'KMAE_Auth', 'is_company' ) && KMAE_Auth::is_company( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || $given === trim( (string) $user->display_name ) ) {
			return;
		}

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $given,
			)
		);
	}

	private static function unique_login( $email ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( '' === $base ) {
			$base = 'tag';
		}

		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . $i;
			$i++;
		}

		return $login;
	}

	/* ------------------------------------------------------------------
	   A hiányzó adatok bekérése (csak az első belépés után)
	   ------------------------------------------------------------------ */

	public static function needs_completion( $user_id ) {
		return (bool) get_user_meta( $user_id, self::META_PENDING, true );
	}

	public static function handle_complete() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( KMAE_Pages::url( 'belepes' ) );
			exit;
		}

		$user_id  = get_current_user_id();
		$redirect = add_query_arg( 'kmae_complete', '1', KMAE_Pages::url( 'regisztracio' ) );

		if ( ! isset( $_POST['kmae_google_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kmae_google_nonce'] ) ), 'kmae_google_complete' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $redirect ) );
			exit;
		}

		$phone = isset( $_POST['kmae_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_phone'] ) ) : '';
		$type  = isset( $_POST['kmae_account_type'] ) ? sanitize_key( wp_unslash( $_POST['kmae_account_type'] ) ) : '';
		$terms = ! empty( $_POST['kmae_terms'] );

		if ( '' === $phone ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'phone_required', $redirect ) );
			exit;
		}

		/* A böngészőben szűrünk, de az megkerülhető — itt dől el, mi kerül
		   az adatbázisba. Egységes alak: +36 30 111 11 11. */
		$phone = KMAE_Auth::normalize_phone( $phone );
		if ( '' === $phone ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'phone_invalid', $redirect ) );
			exit;
		}

		if ( ! isset( KMAE_Auth::account_types()[ $type ] ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'account_type_required', $redirect ) );
			exit;
		}

		if ( ! $terms ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'terms_required', $redirect ) );
			exit;
		}

		$company_name = isset( $_POST['kmae_company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_company_name'] ) ) : '';
		if ( KMAE_Auth::ACCOUNT_COMPANY === $type ) {
			$company_address = isset( $_POST['kmae_company_address'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_company_address'] ) ) : '';
			$company_county  = isset( $_POST['kmae_company_county'] ) ? sanitize_key( wp_unslash( $_POST['kmae_company_county'] ) ) : '';
			$company_city    = isset( $_POST['kmae_company_city'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_company_city'] ) ) : '';
			if ( '' === $company_name ) {
				wp_safe_redirect( add_query_arg( 'kmae_error', 'company_name_required', $redirect ) );
				exit;
			}
			if ( '' === $company_address || ! KMAE_Counties::validate_listing_location( $company_county, $company_city ) ) {
				wp_safe_redirect( add_query_arg( 'kmae_error', 'company_location_required', $redirect ) );
				exit;
			}
			/* A típus a profilsorban és a metában egyszerre váljon céges-ré, mielőtt a
			   céges adatok mentése megnézi, céges-e a fiók (a Google-belépés már
			   korábban létrehozott egy „magánszemély” profilsort). */
			KMAE_Auth::set_account_type( $user_id, $type );
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $company_name,
				)
			);
			if ( class_exists( 'KMAE_Company' ) && method_exists( 'KMAE_Company', 'save_profile_fields' ) ) {
				KMAE_Company::save_profile_fields(
					$user_id,
					array(
						'company_name' => $company_name,
						'address'      => $company_address,
						'county'       => $company_county,
						'city'         => $company_city,
					)
				);
			}
		}

		KMAE_Auth::set_account_type( $user_id, $type );
		update_user_meta( $user_id, 'kmae_phone', $phone );
		KMAE_Auth::sync_phone( $user_id, $phone );

		/* A Google igazolta az e-mail címet, ezért azt megerősítettnek vesszük —
		   nincs szükség még egy levélre és linkre, amit a jelszavas
		   regisztrációnál egyébként kiküldünk. */
		KMAE_Auth::save_email_prefs( $user_id, true, true );
		KMAE_Auth::mark_email_verified( $user_id );
		KMAE_Legal::record_acceptance( $user_id );

		delete_user_meta( $user_id, self::META_PENDING );

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	private static function fail( $code ) {
		wp_safe_redirect( add_query_arg( 'kmae_error', sanitize_key( $code ), KMAE_Pages::url( 'belepes' ) ) );
		exit;
	}
}
