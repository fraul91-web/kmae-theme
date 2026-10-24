<?php
/**
 * User registration, login, and profile storage.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Auth {

	const ACCOUNT_PRIVATE = 'private';
	const ACCOUNT_COMPANY = 'company';

	public static function login_url( $redirect = '' ) {
		$url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'belepes' ) : home_url( '/belepes/' );
		if ( $redirect ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
		}
		return $url;
	}

	public static function register_url() {
		return class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'regisztracio' ) : home_url( '/regisztracio/' );
	}

	/**
	 * Branded lost-password URL.
	 *
	 * @return string
	 */
	public static function lost_password_url() {
		return add_query_arg( 'kmae_view', 'lost', self::login_url() );
	}

	/**
	 * Branded password-reset URL (from email link).
	 *
	 * @param string $key   Reset key.
	 * @param string $login User login.
	 * @return string
	 */
	public static function reset_password_url( $key, $login ) {
		return add_query_arg(
			array(
				'kmae_view' => 'reset',
				'key'       => (string) $key,
				'login'     => (string) $login,
			),
			self::login_url()
		);
	}

	public static function init() {
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_listing_caps' ), 10, 4 );
		add_filter( 'lostpassword_url', array( __CLASS__, 'filter_lostpassword_url' ), 10, 2 );
		add_filter( 'retrieve_password_message', array( __CLASS__, 'filter_reset_message' ), 10, 4 );
		add_action( 'login_init', array( __CLASS__, 'redirect_wp_password_screens' ) );
		add_action( 'init', array( __CLASS__, 'maybe_handle_email_verify_link' ) );
		add_action( 'admin_post_kmae_resend_verification', array( __CLASS__, 'handle_resend_verification' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'filter_admin_bar' ) );
	}

	/**
	 * A felső WordPress-eszköztár csak adminisztrátornak látszik az oldalon.
	 *
	 * @param bool $show Default visibility.
	 * @return bool
	 */
	public static function filter_admin_bar( $show ) {
		return $show && current_user_can( 'manage_options' );
	}

	/**
	 * Point WP lost-password links to the branded login page.
	 *
	 * @param string $url Lost password URL.
	 * @return string
	 */
	public static function filter_lostpassword_url( $url, $redirect = '' ) {
		return self::lost_password_url();
	}

	/**
	 * Replace default wp-login reset link in the email.
	 *
	 * @param string  $message    Email body.
	 * @param string  $key        Reset key.
	 * @param string  $user_login User login.
	 * @param WP_User $user_data  User object.
	 * @return string
	 */
	public static function filter_reset_message( $message, $key, $user_login, $user_data ) {
		unset( $message, $user_data );
		return self::password_reset_email_html( $user_login, self::reset_password_url( $key, $user_login ) );
	}

	/**
	 * A jelszó-visszaállító levél egyetlen forrása — ugyanez fut le akkor
	 * is, ha a WordPress saját folyamata küldi (fenti szűrő), és akkor is,
	 * ha a mi „Elfelejtett jelszó" űrlapunk (lásd lent).
	 *
	 * @param string $user_login Felhasználónév.
	 * @param string $reset_url  Visszaállító link.
	 * @return string Kész, összefésült HTML levéltest.
	 */
	private static function password_reset_email_html( $user_login, $reset_url ) {
		$site = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$body = sprintf(
			/* translators: 1: site name, 2: user login */
			__( 'Valaki jelszó-visszaállítást kért a(z) %1\$s oldalon erre a fiókra: %2\$s.', 'ki-mit-adna-erte' ),
			$site,
			$user_login
		) . "\n\n" . __(
			'Ha nem te kérted, nyugodtan hagyd figyelmen kívül ezt a levelet — a jelszavad addig nem változik, amíg meg nem nyitod a lenti gombot.',
			'ki-mit-adna-erte'
		);

		if ( ! class_exists( 'KMAE_Brand' ) ) {
			return $body . "\n\n" . $reset_url;
		}

		return KMAE_Brand::wrap_email(
			$body,
			array(
				'tone'         => 'info',
				'eyebrow'      => __( 'Biztonság', 'ki-mit-adna-erte' ),
				'title'        => __( 'Új jelszó beállítása', 'ki-mit-adna-erte' ),
				'button_label' => __( 'Új jelszó megadása', 'ki-mit-adna-erte' ),
				'button_url'   => $reset_url,
			)
		);
	}

	/**
	 * Keep users on branded screens instead of wp-login.php password flows.
	 */
	public static function redirect_wp_password_screens() {
		if ( is_user_logged_in() ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
			wp_safe_redirect( self::lost_password_url() );
			exit;
		}

		if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
			$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
			$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
			if ( $key && $login ) {
				wp_safe_redirect( self::reset_password_url( $key, $login ) );
				exit;
			}
			wp_safe_redirect( self::lost_password_url() );
			exit;
		}
	}

	/**
	 * Send password reset email for an address.
	 *
	 * @param string $email Email.
	 * @return true|WP_Error Always true on unknown email (no enumeration).
	 */
	public static function request_password_reset( $email ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Érvénytelen e-mail cím.', 'ki-mit-adna-erte' ) );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return true;
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$site    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Jelszó visszaállítása', 'ki-mit-adna-erte' ),
			$site
		);
		$html = self::password_reset_email_html( $user->user_login, self::reset_password_url( $key, $user->user_login ) );

		wp_mail( $user->user_email, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );

		return true;
	}

	/**
	 * Set a new password using WP reset key.
	 *
	 * @param string $login User login.
	 * @param string $key   Reset key.
	 * @param string $pass1 New password.
	 * @param string $pass2 Confirm password.
	 * @return true|WP_Error
	 */
	public static function reset_password_with_key( $login, $key, $pass1, $pass2 ) {
		$login = sanitize_text_field( $login );
		$key   = sanitize_text_field( $key );

		if ( '' === $login || '' === $key ) {
			return new WP_Error( 'invalid_key', __( 'Érvénytelen vagy lejárt visszaállító link.', 'ki-mit-adna-erte' ) );
		}

		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'invalid_key', __( 'Érvénytelen vagy lejárt visszaállító link.', 'ki-mit-adna-erte' ) );
		}

		if ( strlen( (string) $pass1 ) < 8 ) {
			return new WP_Error( 'weak_password', __( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ) );
		}

		if ( (string) $pass1 !== (string) $pass2 ) {
			return new WP_Error( 'password_mismatch', __( 'A két jelszó nem egyezik.', 'ki-mit-adna-erte' ) );
		}

		reset_password( $user, $pass1 );
		return true;
	}

	/**
	 * Allowed account types (magánszemély / cég).
	 *
	 * @return array<string, string> slug => label
	 */
	public static function account_types() {
		return array(
			self::ACCOUNT_PRIVATE => __( 'Magánszemély', 'ki-mit-adna-erte' ),
			self::ACCOUNT_COMPANY => __( 'Cég / autókereskedés', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * @param string $type Raw type.
	 * @return string Normalized type.
	 */
	public static function sanitize_account_type( $type ) {
		$type = sanitize_key( (string) $type );
		$allowed = array_keys( self::account_types() );
		return in_array( $type, $allowed, true ) ? $type : self::ACCOUNT_PRIVATE;
	}

	/**
	 * @param int $user_id User ID.
	 * @return string private|company
	 */
	public static function get_account_type( $user_id ) {
		$profile = self::get_profile( $user_id );
		if ( ! empty( $profile->account_type ) ) {
			return self::sanitize_account_type( $profile->account_type );
		}
		$meta = get_user_meta( $user_id, 'kmae_account_type', true );
		return self::sanitize_account_type( $meta );
	}

	public static function is_company( $user_id ) {
		return self::ACCOUNT_COMPANY === self::get_account_type( $user_id );
	}

	public static function is_private_person( $user_id ) {
		return self::ACCOUNT_PRIVATE === self::get_account_type( $user_id );
	}

	/**
	 * Human-readable account type label.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function account_type_label( $user_id ) {
		$type   = self::get_account_type( $user_id );
		$labels = self::account_types();
		return isset( $labels[ $type ] ) ? $labels[ $type ] : $labels[ self::ACCOUNT_PRIVATE ];
	}

	/**
	 * Subscribers need upload_files for listing photos.
	 */
	public static function ensure_subscriber_caps() {
		$role = get_role( 'subscriber' );
		if ( $role && ! $role->has_cap( 'upload_files' ) ) {
			$role->add_cap( 'upload_files' );
		}
	}

	/**
	 * Authors may edit/delete their own listings without edit_posts cap.
	 *
	 * @param array<string> $caps
	 * @return array<string>
	 */
	public static function map_listing_caps( $caps, $cap, $user_id, $args ) {
		$listing_caps = array( 'edit_post', 'delete_post', 'read_post' );
		if ( ! in_array( $cap, $listing_caps, true ) || empty( $args[0] ) ) {
			return $caps;
		}

		$post = get_post( (int) $args[0] );
		if ( ! $post || 'kmae_listing' !== $post->post_type ) {
			return $caps;
		}

		if ( (int) $post->post_author === (int) $user_id ) {
			return array( 'read' );
		}

		return $caps;
	}

	public static function register( $data ) {
		global $wpdb;

		$display_name  = isset( $data['display_name'] ) ? sanitize_text_field( $data['display_name'] ) : '';
		$email         = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$phone         = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
		$password      = isset( $data['password'] ) ? $data['password'] : '';
		$password2     = isset( $data['password_confirm'] ) ? $data['password_confirm'] : '';
		$raw_account_type = isset( $data['account_type'] ) ? sanitize_key( (string) $data['account_type'] ) : '';

		if ( empty( $email ) || empty( $phone ) || empty( $password ) ) {
			return new WP_Error( 'missing_fields', __( 'Kérjük, tölts ki minden mezőt.', 'ki-mit-adna-erte' ) );
		}

		$phone = self::normalize_phone( $phone );
		if ( '' === $phone ) {
			return new WP_Error( 'phone_invalid', __( 'A telefonszám kilenc számjegy, például 30 111 11 11.', 'ki-mit-adna-erte' ) );
		}

		if ( ! isset( self::account_types()[ $raw_account_type ] ) ) {
			return new WP_Error( 'account_type_required', __( 'Válaszd ki, hogy magánszemély vagy cég vagy.', 'ki-mit-adna-erte' ) );
		}

		$account_type = $raw_account_type;

		if ( self::ACCOUNT_COMPANY === $account_type ) {
			$company_name = isset( $data['company_name'] ) ? sanitize_text_field( (string) $data['company_name'] ) : '';
			if ( '' === $company_name ) {
				return new WP_Error( 'company_name_required', __( 'Add meg a cégnevet.', 'ki-mit-adna-erte' ) );
			}
			$company_address = isset( $data['address'] ) ? sanitize_text_field( (string) $data['address'] ) : '';
			$company_county  = isset( $data['county'] ) ? sanitize_key( (string) $data['county'] ) : '';
			$company_city    = isset( $data['city'] ) ? sanitize_text_field( (string) $data['city'] ) : '';
			$location_problem = KMAE_Counties::location_error( $company_county, $company_city );
			if ( '' === $company_address || 'required' === $location_problem ) {
				return new WP_Error( 'company_location_required', __( 'Add meg a céges telephelyet.', 'ki-mit-adna-erte' ) );
			}
			if ( 'mismatch' === $location_problem ) {
				return new WP_Error( 'company_location_mismatch', KMAE_Company::location_mismatch_message() );
			}
			$display_name = $company_name;
		} elseif ( empty( $display_name ) ) {
			return new WP_Error( 'missing_fields', __( 'Kérjük, tölts ki minden mezőt.', 'ki-mit-adna-erte' ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Érvénytelen e-mail cím.', 'ki-mit-adna-erte' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'email_exists', __( 'Ez az e-mail cím már regisztrálva van.', 'ki-mit-adna-erte' ) );
		}

		if ( strlen( $password ) < 8 ) {
			return new WP_Error( 'weak_password', __( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ) );
		}

		if ( $password !== $password2 ) {
			return new WP_Error( 'password_mismatch', __( 'A két jelszó nem egyezik.', 'ki-mit-adna-erte' ) );
		}

		$username = self::generate_username( $email );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $display_name,
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, 'kmae_phone', $phone );
		update_user_meta( $user_id, 'kmae_account_type', $account_type );

		if ( self::ACCOUNT_COMPANY === $account_type ) {
			$company_fields = array(
				'company_name' => $display_name,
				'address'      => isset( $data['address'] ) ? $data['address'] : '',
				'county'       => isset( $data['county'] ) ? $data['county'] : '',
				'city'         => isset( $data['city'] ) ? $data['city'] : '',
			);
			/* Autóátvétel: csak akkor kerül át, ha az űrlapon ott volt a blokk (jelölő mező). */
			if ( array_key_exists( 'buys', $data ) ) {
				$company_fields['buys'] = ! empty( $data['buys'] );
			}
			if ( array_key_exists( 'tradein', $data ) ) {
				$company_fields['tradein'] = ! empty( $data['tradein'] );
			}
			KMAE_Company::save_profile_fields( $user_id, $company_fields );
		}

		$wpdb->replace(
			KMAE_Database::table( 'user_profiles' ),
			array(
				'user_id'        => $user_id,
				'phone'          => $phone,
				'account_type'   => $account_type,
				'email_verified' => 0,
				'phone_verified' => 0,
				'email_news'     => 1,
				'email_offers'   => 1,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);

		/* A jelszavas regisztrációnál senki nem igazolta még ezt a címet —
		   ellentétben a Google-belépéssel, ahol a Google már megtette.
		   A levél elmaradása ne akassza meg a regisztrációt. */
		$token = self::generate_email_verify_token( $user_id );
		self::send_verification_email( $user_id, $email, $token );

		return $user_id;
	}

	/* ------------------------------------------------------------------
	   E-mail cím megerősítése
	   ------------------------------------------------------------------ */

	/**
	 * A profil sorát „megerősítve" állapotba teszi, a többi mezőt érintetlenül
	 * hagyva. Ugyanezt hívja a Google-belépés is, hiszen ott a Google már
	 * igazolta a címet.
	 *
	 * @param int $user_id User ID.
	 */
	public static function mark_email_verified( $user_id ) {
		global $wpdb;
		$existing = self::get_profile( $user_id );

		$wpdb->replace(
			KMAE_Database::table( 'user_profiles' ),
			array(
				'user_id'        => $user_id,
				'phone'          => (string) ( $existing->phone ?? '' ),
				'account_type'   => self::sanitize_account_type( $existing->account_type ?? self::ACCOUNT_PRIVATE ),
				'email_verified' => 1,
				'phone_verified' => (int) ( $existing->phone_verified ?? 0 ),
				'email_news'     => (int) ( $existing->email_news ?? 0 ),
				'email_offers'   => (int) ( $existing->email_offers ?? 0 ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);
	}

	/**
	 * Új megerősítő kulcs. A nyers érték megy a linkbe, az adatbázisba csak
	 * a lenyomata kerül — ahogy a jelszó-visszaállításnál is.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function generate_email_verify_token( $user_id ) {
		$token = wp_generate_password( 32, false );
		update_user_meta( $user_id, 'kmae_email_verify_token', wp_hash( $token ) );
		update_user_meta( $user_id, 'kmae_email_verify_sent', time() );
		return $token;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $email   Ahova küldjük (regisztrációkor még nincs elmentve).
	 * @param string $token   Nyers megerősítő kulcs.
	 */
	public static function send_verification_email( $user_id, $email, $token ) {
		// A linkben csak a véletlen kulcs van, a felhasználó sorszáma nem.
		$link = add_query_arg( 'kmae_email_token', $token, home_url( '/' ) );

		$name    = self::first_name( $user_id );
		$greet   = '' !== $name
			/* translators: %s: first name */
			? sprintf( __( 'Szia, %s!', 'ki-mit-adna-erte' ), $name )
			: __( 'Szia!', 'ki-mit-adna-erte' );

		$subject = __( 'Erősítsd meg az e-mail címed — Albira', 'ki-mit-adna-erte' );

		$body = $greet . "\n\n" . __(
			'Már csak egy lépés, és mehet is a hirdetésfeladás: erősítsd meg az e-mail címed a lenti gombbal.',
			'ki-mit-adna-erte'
		) . "\n\n" . __(
			'Ha nem te regisztráltál az Albirán, nyugodtan hagyd figyelmen kívül ezt a levelet — nem történik semmi.',
			'ki-mit-adna-erte'
		);

		$html = class_exists( 'KMAE_Brand' ) ? KMAE_Brand::wrap_email(
			$body,
			array(
				'tone'         => 'welcome',
				'eyebrow'      => __( 'Egy kattintás az egész', 'ki-mit-adna-erte' ),
				'title'        => __( 'Erősítsd meg az e-mail címed', 'ki-mit-adna-erte' ),
				'button_label' => __( 'E-mail cím megerősítése', 'ki-mit-adna-erte' ),
				'button_url'   => $link,
			)
		) : $body;

		wp_mail( $email, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $token   A linkből érkező nyers kulcs.
	 * @return true|WP_Error
	 */
	public static function verify_email_link( $user_id, $token ) {
		$stored = get_user_meta( $user_id, 'kmae_email_verify_token', true );
		if ( '' === $stored || ! hash_equals( $stored, wp_hash( (string) $token ) ) ) {
			return new WP_Error( 'invalid_token', __( 'Érvénytelen vagy már felhasznált megerősítő link.', 'ki-mit-adna-erte' ) );
		}

		$sent = (int) get_user_meta( $user_id, 'kmae_email_verify_sent', true );
		if ( $sent && ( time() - $sent ) > 7 * DAY_IN_SECONDS ) {
			return new WP_Error( 'expired_token', __( 'Ez a link lejárt — kérj újat a profilodból.', 'ki-mit-adna-erte' ) );
		}

		self::mark_email_verified( $user_id );
		delete_user_meta( $user_id, 'kmae_email_verify_token' );
		delete_user_meta( $user_id, 'kmae_email_verify_sent' );

		/* A másik fülön / eszközön nyitva hagyott oldal (letiltó képernyő, profil) magától frissüljön. */
		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( array( $user_id ) );
		}

		return true;
	}

	/**
	 * @param string $token A linkből érkező nyers kulcs.
	 * @return int User ID vagy 0.
	 */
	private static function user_by_email_token( $token ) {
		$token = (string) $token;
		if ( strlen( $token ) < 20 ) {
			return 0;
		}
		$ids = get_users(
			array(
				'meta_key'   => 'kmae_email_verify_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'meta_value' => wp_hash( $token ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * A levélben kapott linkre kattintva ide fut be a látogató, bárhol jár.
	 */
	public static function maybe_handle_email_verify_link() {
		if ( ! isset( $_GET['kmae_email_token'] ) ) {
			return;
		}

		// A felhasználót a kulcs lenyomata azonosítja (a régi linkek ?kmae_email_verify= sorszámát figyelmen kívül hagyjuk).
		$token   = sanitize_text_field( wp_unslash( $_GET['kmae_email_token'] ) );
		$user_id = self::user_by_email_token( $token );
		$result  = $user_id
			? self::verify_email_link( $user_id, $token )
			: new WP_Error( 'invalid_token', __( 'Érvénytelen vagy már felhasznált megerősítő link.', 'ki-mit-adna-erte' ) );

		$redirect = is_user_logged_in()
			? ( class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'profil' ) : home_url( '/profil/' ) )
			: self::login_url();

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
		} else {
			wp_safe_redirect( add_query_arg( 'kmae_success', 'email_verified', $redirect ) );
		}
		exit;
	}

	/**
	 * „Küldd újra" gomb a profilon és a letiltó képernyőkön.
	 */
	public static function handle_resend_verification() {
		$fallback = home_url( '/' );

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( self::login_url() );
			exit;
		}

		$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : ( wp_get_referer() ?: $fallback );

		if ( ! isset( $_POST['kmae_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kmae_resend_nonce'] ) ), 'kmae_resend_verification' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $redirect ) );
			exit;
		}

		$user_id = get_current_user_id();
		if ( self::is_email_verified( $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_success', 'already_verified', $redirect ) );
			exit;
		}

		$user  = wp_get_current_user();
		$token = self::generate_email_verify_token( $user_id );
		self::send_verification_email( $user_id, $user->user_email, $token );

		wp_safe_redirect( add_query_arg( 'kmae_success', 'verification_sent', $redirect ) );
		exit;
	}

	public static function login( $email_or_login, $password, $remember = false ) {
		$email_or_login = sanitize_text_field( $email_or_login );
		$user           = get_user_by( 'email', $email_or_login );

		if ( ! $user ) {
			$user = get_user_by( 'login', $email_or_login );
		}

		if ( ! $user ) {
			return new WP_Error( 'invalid_login', __( 'Hibás e-mail vagy jelszó.', 'ki-mit-adna-erte' ) );
		}

		$result = wp_signon(
			array(
				'user_login'    => $user->user_login,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'invalid_login', __( 'Hibás e-mail vagy jelszó.', 'ki-mit-adna-erte' ) );
		}

		return $result;
	}

	public static function get_profile( $user_id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'user_profiles' ) . ' WHERE user_id = %d',
				$user_id
			)
		);

		if ( ! $row ) {
			$phone = get_user_meta( $user_id, 'kmae_phone', true );
			$type  = get_user_meta( $user_id, 'kmae_account_type', true );
			return (object) array(
				'user_id'        => $user_id,
				'phone'          => $phone ?: '',
				'account_type'   => self::sanitize_account_type( $type ),
				'email_verified' => 0,
				'phone_verified' => 0,
				'email_news'     => 0,
				'email_offers'   => 0,
			);
		}

		if ( empty( $row->account_type ) ) {
			$row->account_type = self::ACCOUNT_PRIVATE;
		}

		return $row;
	}

	public static function wants_news_emails( $user_id ) {
		$profile = self::get_profile( $user_id );
		return ! empty( $profile->email_news );
	}

	/**
	 * Értesítési preferenciák mentése a beállítások oldalról. A profil sorának
	 * többi mezőjét érintetlenül hagyja.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $offers  Üzenet-e-mailek.
	 * @param bool $news    Hírlevél.
	 */
	public static function save_email_prefs( $user_id, $offers, $news ) {
		global $wpdb;
		$existing = self::get_profile( $user_id );

		$wpdb->replace(
			KMAE_Database::table( 'user_profiles' ),
			array(
				'user_id'        => $user_id,
				'phone'          => (string) ( $existing->phone ?? '' ),
				'account_type'   => self::sanitize_account_type( $existing->account_type ?? self::ACCOUNT_PRIVATE ),
				'email_verified' => (int) ( $existing->email_verified ?? 0 ),
				'phone_verified' => (int) ( $existing->phone_verified ?? 0 ),
				'email_news'     => $news ? 1 : 0,
				'email_offers'   => $offers ? 1 : 0,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);
	}

	public static function is_email_verified( $user_id ) {
		$profile = self::get_profile( $user_id );
		return ! empty( $profile->email_verified );
	}

	/**
	 * Hirdetésfeladás és üzenetküldés csak megerősített e-mail címmel megy —
	 * a rendszergazda tesztelés közben nem akad el ezen.
	 *
	 * @param int $user_id User ID.
	 * @return bool Igaz, ha a felhasználót erre a képernyőre kell terelni.
	 */
	public static function should_gate_for_verification( $user_id ) {
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}
		return ! self::is_email_verified( $user_id );
	}

	public static function is_phone_verified( $user_id ) {
		$profile = self::get_profile( $user_id );
		return ! empty( $profile->phone_verified );
	}

	public static function is_fully_verified( $user_id ) {
		return self::is_email_verified( $user_id ) && self::is_phone_verified( $user_id );
	}

	/**
	 * A köszönéshez való keresztnév.
	 *
	 * Google-belépésnél a keresztnevet külön megkapjuk és eltároljuk; minden
	 * más esetben a megjelenített név első szava. Üres, ha nincs használható
	 * nevünk (például e-mail cím áll a név helyén).
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function first_name( $user_id ) {
		if ( get_user_meta( $user_id, 'kmae_public_name_set', true ) ) {
			$user = get_userdata( $user_id );
			$name = $user ? trim( (string) $user->display_name ) : '';
			if ( '' !== $name && ! is_email( $name ) ) {
				$parts = preg_split( '/\s+/u', $name );
				$given = is_array( $parts ) && ! empty( $parts[0] ) ? $parts[0] : '';
				if ( '' !== $given && ! is_email( $given ) ) {
					return $given;
				}
			}
		}

		$given = trim( (string) get_user_meta( $user_id, 'kmae_given_name', true ) );

		if ( '' === $given ) {
			$user = get_userdata( $user_id );
			$name = $user ? trim( (string) $user->display_name ) : '';
			if ( '' !== $name && ! is_email( $name ) ) {
				$parts = preg_split( '/\s+/u', $name );
				$given = is_array( $parts ) && ! empty( $parts[0] ) ? $parts[0] : '';
			}
		}

		return is_email( $given ) ? '' : $given;
	}

	/**
	 * A profilban megadott név felülírja a Google-ből áthozott keresztnevet.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $public_name Megjelenített név.
	 */
	public static function adopt_public_name( $user_id, $public_name ) {
		$public_name = trim( (string) $public_name );
		if ( '' === $public_name || is_email( $public_name ) ) {
			return;
		}

		$parts = preg_split( '/\s+/u', $public_name );
		$given = is_array( $parts ) && ! empty( $parts[0] ) ? $parts[0] : $public_name;
		if ( '' !== $given && ! is_email( $given ) ) {
			update_user_meta( $user_id, 'kmae_given_name', $given );
		}
		update_user_meta( $user_id, 'kmae_public_name_set', 1 );
	}

	/**
	 * Magyar telefonszám egységes alakra hozása. A böngészőben is szűrünk,
	 * de az megkerülhető — itt dől el, mi kerül az adatbázisba.
	 *
	 * @param string $raw Beírt érték (bármilyen alakban).
	 * @return string '+36 30 111 11 11' vagy üres, ha nem érvényes.
	 */
	public static function normalize_phone( $raw ) {
		$digits = preg_replace( '/\D/', '', (string) $raw );

		/* A teljes számot is beillesztheti: +36…, 0036…, 06… */
		if ( 0 === strpos( $digits, '0036' ) ) {
			$digits = substr( $digits, 4 );
		} elseif ( 0 === strpos( $digits, '36' ) && strlen( $digits ) > 9 ) {
			$digits = substr( $digits, 2 );
		} elseif ( 0 === strpos( $digits, '06' ) ) {
			$digits = substr( $digits, 2 );
		}

		if ( 9 !== strlen( $digits ) ) {
			return '';
		}

		return '+36 ' . substr( $digits, 0, 2 )
			. ' ' . substr( $digits, 2, 3 )
			. ' ' . substr( $digits, 5, 2 )
			. ' ' . substr( $digits, 7, 2 );
	}

	/**
	 * Fióktípus beállítása — a profilsorban ÉS a user metában egyszerre.
	 * A get_account_type() a profilsort olvassa előbb, ezért a típust ott kell
	 * átírni (a Google-belépés már a regisztráció befejezése előtt létrehoz egy
	 * „magánszemély” sort; azt a céges választásnak felül kell írnia).
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    private|company.
	 */
	public static function set_account_type( $user_id, $type ) {
		global $wpdb;
		$type     = self::sanitize_account_type( $type );
		$existing = self::get_profile( $user_id );
		$wpdb->replace(
			KMAE_Database::table( 'user_profiles' ),
			array(
				'user_id'        => $user_id,
				'phone'          => (string) ( $existing->phone ?? '' ),
				'account_type'   => $type,
				'email_verified' => (int) ( $existing->email_verified ?? 0 ),
				'phone_verified' => (int) ( $existing->phone_verified ?? 0 ),
				'email_news'     => (int) ( $existing->email_news ?? 0 ),
				'email_offers'   => (int) ( $existing->email_offers ?? 0 ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);
		update_user_meta( $user_id, 'kmae_account_type', $type );
	}

	public static function sync_phone( $user_id, $phone ) {
		global $wpdb;
		$existing     = self::get_profile( $user_id );
		$account_type = self::sanitize_account_type( $existing->account_type ?? self::ACCOUNT_PRIVATE );
		$wpdb->replace(
			KMAE_Database::table( 'user_profiles' ),
			array(
				'user_id'        => $user_id,
				'phone'          => $phone,
				'account_type'   => $account_type,
				'email_verified' => (int) $existing->email_verified,
				'phone_verified' => (int) $existing->phone_verified,
				'email_news'     => (int) ( $existing->email_news ?? 0 ),
				'email_offers'   => (int) ( $existing->email_offers ?? 0 ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);
		update_user_meta( $user_id, 'kmae_phone', $phone );
		update_user_meta( $user_id, 'kmae_account_type', $account_type );
	}

	/**
	 * Fiók végleges törlése („Profilom törlése”): a felhasználó hirdetései (a
	 * hirdetés-törlés levele és takarítása fut), chatjei, ajánlatai és képei,
	 * profil-sora, majd maga a WP-felhasználó. A bejelentések (kmae_reports)
	 * bizonyítékként megmaradnak. Adminisztrátor fiókja nem törölhető.
	 *
	 * @param int $user_id User ID.
	 * @return true|WP_Error
	 */
	public static function delete_account( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
		if ( ! $user ) {
			return new WP_Error( 'invalid', __( 'Érvénytelen felhasználó.', 'ki-mit-adna-erte' ) );
		}
		if ( user_can( $user, 'manage_options' ) ) {
			return new WP_Error( 'admin_account', __( 'Adminisztrátori fiók itt nem törölhető.', 'ki-mit-adna-erte' ) );
		}

		/* 1. Saját hirdetések (piszkozat, aktív, lejárt, archív — minden állapot). */
		$listing_ids = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'any',
				'author'         => $user_id,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
		foreach ( (array) $listing_ids as $listing_id ) {
			KMAE_Listings::force_delete_listing( (int) $listing_id, $user_id );
		}

		/* 2. Maradék beszélgetések (vevőként indítottak): üzenetek, képek, ajánlatok. */
		$conversations_table = KMAE_Database::table( 'conversations' );
		$messages_table      = KMAE_Database::table( 'conversation_messages' );
		$conversations       = $wpdb->get_results( $wpdb->prepare( "SELECT id, buyer_id, seller_id FROM {$conversations_table} WHERE buyer_id = %d OR seller_id = %d", $user_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counterparties      = array();
		foreach ( (array) $conversations as $conversation ) {
			$counterparties[] = (int) $conversation->buyer_id === $user_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;

			$attachment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT attachment_id FROM {$messages_table} WHERE conversation_id = %d AND attachment_id IS NOT NULL AND attachment_id > 0", (int) $conversation->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $attachment_ids as $attachment_id ) {
				KMAE_Images::delete_attachment( (int) $attachment_id );
			}
			$wpdb->delete( $messages_table, array( 'conversation_id' => (int) $conversation->id ), array( '%d' ) );
			$wpdb->delete( KMAE_Database::table( 'chat_offers' ), array( 'conversation_id' => (int) $conversation->id ), array( '%d' ) );
			$wpdb->delete( $conversations_table, array( 'id' => (int) $conversation->id ), array( '%d' ) );
		}

		/* 2b. Cégként kapott kereskedői megkeresések: a nyitottak 'cancelled' (a sorok maradnak, előzmény). */
		if ( class_exists( 'KMAE_Dealer_Leads' ) ) {
			KMAE_Dealer_Leads::cancel_for_dealer( $user_id );
		}

		/* 3. Profil-sor, majd a felhasználó (a usermeta — kedvencek, push, tiltólista — vele törlődik). */
		$wpdb->delete( KMAE_Database::table( 'user_profiles' ), array( 'user_id' => $user_id ), array( '%d' ) );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		if ( ! wp_delete_user( $user_id ) ) {
			return new WP_Error( 'delete_failed', __( 'A fiók törlése sikertelen.', 'ki-mit-adna-erte' ) );
		}

		if ( class_exists( 'KMAE_Live_Signal' ) && $counterparties ) {
			KMAE_Live_Signal::bump( array_values( array_unique( array_filter( $counterparties ) ) ) );
		}

		return true;
	}

	private static function generate_username( $email ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( empty( $base ) ) {
			$base = 'user';
		}
		$username = $base;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}
		return $username;
	}
}
