<?php
/**
 * Böngészőértesítések (Web Push) — ingyenes, szabványos push külső
 * szolgáltatás nélkül: a kézbesítést a böngésző gyártója végzi (Google,
 * Mozilla, Apple). A szerver csak eseménykor küld egy titkosított üzenetet
 * (RFC 8291 aes128gcm + RFC 8292 VAPID), nem tart nyitva kapcsolatot.
 *
 * - Regisztráció után saját ablak kéri az engedélyt (egyszer; „Most nem”).
 * - Beállítások → „Böngésző-értesítések” kapcsoló (fiókszintű be/ki).
 * - Aki nem engedélyezi, annak a KMAE_Live_Signal könnyű ellenőrzése
 *   ugyanúgy frissíti az oldalt.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Push {

	const OPTION_VAPID  = 'kmae_push_vapid';
	const META_SUBS     = 'kmae_push_subs';
	const META_ENABLED  = 'kmae_push_enabled';
	const META_PROMPTED = 'kmae_push_prompted';
	const NONCE_ACTION  = 'kmae_push';
	const MAX_SUBS      = 10;

	public static function init() {
		add_action( 'wp_ajax_kmae_push_subscribe', array( __CLASS__, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_kmae_push_unsubscribe', array( __CLASS__, 'ajax_unsubscribe' ) );
		add_action( 'wp_ajax_kmae_push_prompted', array( __CLASS__, 'ajax_prompted' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
		add_action( 'wp_footer', array( __CLASS__, 'render_prompt' ), 40 );
	}

	/* ------------------------------------------------------------------ */
	/* Kulcsok                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * VAPID kulcspár (egyszer generálódik, opcióban tárolva).
	 *
	 * @return array{private_pem:string,public:string}|null
	 */
	public static function vapid() {
		$stored = get_option( self::OPTION_VAPID );
		if ( is_array( $stored ) && ! empty( $stored['private_pem'] ) && ! empty( $stored['public'] ) ) {
			return $stored;
		}
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			return null;
		}
		$key = openssl_pkey_new(
			array(
				'curve_name'       => 'prime256v1',
				'private_key_type' => OPENSSL_KEYTYPE_EC,
			)
		);
		if ( ! $key || ! openssl_pkey_export( $key, $pem ) ) {
			return null;
		}
		$raw = self::raw_public_key( $key );
		if ( '' === $raw ) {
			return null;
		}
		$stored = array(
			'private_pem' => $pem,
			'public'      => self::b64url( $raw ),
		);
		update_option( self::OPTION_VAPID, $stored, true );
		return $stored;
	}

	public static function is_available() {
		static $available = null;
		if ( null === $available ) {
			$available = function_exists( 'openssl_pkey_derive' ) && function_exists( 'hash_hkdf' ) && null !== self::vapid();
		}
		return $available;
	}

	/* ------------------------------------------------------------------ */
	/* Felhasználói állapot                                                */
	/* ------------------------------------------------------------------ */

	public static function is_enabled( $user_id ) {
		return '1' === (string) get_user_meta( (int) $user_id, self::META_ENABLED, true );
	}

	public static function was_prompted( $user_id ) {
		return '1' === (string) get_user_meta( (int) $user_id, self::META_PROMPTED, true );
	}

	/**
	 * @return array<string,array{endpoint:string,p256dh:string,auth:string}>
	 */
	private static function subs( $user_id ) {
		$subs = get_user_meta( (int) $user_id, self::META_SUBS, true );
		return is_array( $subs ) ? $subs : array();
	}

	/* ------------------------------------------------------------------ */
	/* AJAX                                                                */
	/* ------------------------------------------------------------------ */

	public static function ajax_subscribe() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( null, 403 );
		}

		$endpoint = isset( $_POST['endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['endpoint'] ) ) : '';
		$p256dh   = isset( $_POST['p256dh'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', wp_unslash( $_POST['p256dh'] ) ) : '';
		$auth     = isset( $_POST['auth'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', wp_unslash( $_POST['auth'] ) ) : '';

		if ( 0 !== strpos( $endpoint, 'https://' ) || 65 !== strlen( self::b64url_decode( $p256dh ) ) || 16 !== strlen( self::b64url_decode( $auth ) ) ) {
			wp_send_json_error( array( 'message' => 'invalid' ), 400 );
		}

		$subs                      = self::subs( $user_id );
		$subs[ md5( $endpoint ) ] = array(
			'endpoint' => $endpoint,
			'p256dh'   => $p256dh,
			'auth'     => $auth,
		);
		if ( count( $subs ) > self::MAX_SUBS ) {
			$subs = array_slice( $subs, -self::MAX_SUBS, null, true );
		}
		update_user_meta( $user_id, self::META_SUBS, $subs );
		update_user_meta( $user_id, self::META_ENABLED, '1' );
		update_user_meta( $user_id, self::META_PROMPTED, '1' );
		wp_send_json_success();
	}

	/**
	 * Kikapcsolás a Beállításokban: fiókszinten ki, minden eszköz leiratkozik.
	 */
	public static function ajax_unsubscribe() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( null, 403 );
		}
		delete_user_meta( $user_id, self::META_SUBS );
		update_user_meta( $user_id, self::META_ENABLED, '0' );
		update_user_meta( $user_id, self::META_PROMPTED, '1' );
		wp_send_json_success();
	}

	/**
	 * „Most nem” — az ablak többet nem jön fel magától.
	 */
	public static function ajax_prompted() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, self::META_PROMPTED, '1' );
		}
		wp_send_json_success();
	}

	/* ------------------------------------------------------------------ */
	/* Frontend                                                            */
	/* ------------------------------------------------------------------ */

	private static function is_settings_page() {
		$post = get_post();
		return $post && has_shortcode( (string) $post->post_content, 'kmae_settings' );
	}

	public static function enqueue() {
		if ( ! is_user_logged_in() || ! self::is_available() ) {
			return;
		}
		$user_id = get_current_user_id();

		wp_enqueue_script( 'kmae-push', KMAE_PLUGIN_URL . 'assets/js/push.js', array(), KMAE_VERSION, true );
		wp_localize_script(
			'kmae-push',
			'kmaePush',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'publicKey' => self::vapid()['public'],
				'swUrl'     => add_query_arg( 'ver', (string) filemtime( KMAE_PLUGIN_DIR . 'assets/js/push-sw.js' ), KMAE_PLUGIN_URL . 'assets/js/push-sw.js' ),
				'enabled'   => self::is_enabled( $user_id ),
				'prompted'  => self::was_prompted( $user_id ),
			)
		);

		/* A stílus csak ott kell, ahol ablak vagy kapcsoló van. */
		if ( ! self::was_prompted( $user_id ) || self::is_settings_page() ) {
			wp_enqueue_style( 'kmae-push', KMAE_PLUGIN_URL . 'assets/css/push.css', array(), KMAE_VERSION );
		}
	}

	/**
	 * Regisztráció utáni engedélykérő ablak (csak amíg nem döntött).
	 */
	public static function render_prompt() {
		if ( ! is_user_logged_in() || ! self::is_available() || self::was_prompted( get_current_user_id() ) ) {
			return;
		}
		/* Google-regisztrációnál a „maradék adatok” képernyőn még ne kérjünk engedélyt; csak a befejezés után. */
		if ( class_exists( 'KMAE_Google_Auth' ) && KMAE_Google_Auth::needs_completion( get_current_user_id() ) ) {
			return;
		}
		?>
		<div class="kmae-push-dialog" data-kmae-push-dialog hidden>
			<div class="kmae-push-dialog__backdrop"></div>
			<div class="kmae-push-dialog__card" role="dialog" aria-modal="true" aria-labelledby="kmae-push-dialog-title">
				<img class="kmae-push-dialog__mascot" src="https://albira.hu/wp-content/uploads/2026/10/lefelemutat3.webp" alt="" width="200" height="374" decoding="async" />
				<h3 class="kmae-push-dialog__title" id="kmae-push-dialog-title"><?php esc_html_e( 'Kapcsold be az értesítéseket!', 'ki-mit-adna-erte' ); ?></h3>
				<p class="kmae-push-dialog__lead"><?php esc_html_e( 'A gyors és hatékony üzletkötéshez elengedhetetlen, hogy azonnal értesülj az üzenetekről.', 'ki-mit-adna-erte' ); ?></p>
				<ul class="kmae-push-dialog__points">
					<li><?php echo KMAE_Icons::render( 'bell', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Azonnal szólunk, ha üzenetet kapsz — akkor is, ha az oldal nincs nyitva.', 'ki-mit-adna-erte' ); ?></span></li>
					<li><?php echo KMAE_Icons::render( 'settings', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Bármikor kikapcsolhatod a Beállításokban.', 'ki-mit-adna-erte' ); ?></span></li>
				</ul>
				<div class="kmae-push-dialog__actions">
					<button type="button" class="kmae-push-dialog__primary" data-kmae-push-allow><?php esc_html_e( 'Bekapcsolom', 'ki-mit-adna-erte' ); ?></button>
					<button type="button" class="kmae-push-dialog__later" data-kmae-push-later><?php esc_html_e( 'Most nem', 'ki-mit-adna-erte' ); ?></button>
				</div>
				<p class="kmae-push-dialog__status" data-kmae-push-status role="status" aria-live="polite"></p>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Küldés                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Értesítés egy felhasználó összes feliratkozott eszközére.
	 *
	 * @param int                                            $user_id User.
	 * @param array{title:string,body:string,url:string,tag?:string} $note    Tartalom.
	 */
	public static function send_to_user( $user_id, $note ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! self::is_enabled( $user_id ) || ! self::is_available() ) {
			return;
		}
		$subs = self::subs( $user_id );
		if ( empty( $subs ) ) {
			return;
		}

		$payload = wp_json_encode(
			array(
				'title' => (string) ( $note['title'] ?? 'Albira' ),
				'body'  => (string) ( $note['body'] ?? '' ),
				'url'   => (string) ( $note['url'] ?? home_url( '/' ) ),
				'tag'   => (string) ( $note['tag'] ?? 'kmae' ),
				'icon'  => (string) get_site_icon_url( 192 ),
			)
		);

		$changed = false;
		foreach ( $subs as $id => $sub ) {
			$status = self::send_one( $sub, $payload );
			if ( 404 === $status || 410 === $status ) {
				unset( $subs[ $id ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			update_user_meta( $user_id, self::META_SUBS, $subs );
		}
	}

	/**
	 * @return int HTTP status (0 hiba esetén).
	 */
	private static function send_one( $sub, $payload ) {
		$body = self::encrypt( $payload, self::b64url_decode( $sub['p256dh'] ), self::b64url_decode( $sub['auth'] ) );
		$jwt  = self::vapid_jwt( $sub['endpoint'] );
		if ( '' === $body || '' === $jwt ) {
			return 0;
		}
		$response = wp_remote_post(
			$sub['endpoint'],
			array(
				'timeout' => 5,
				'headers' => array(
					'Authorization'    => 'vapid t=' . $jwt . ', k=' . self::vapid()['public'],
					'Content-Encoding' => 'aes128gcm',
					'Content-Type'     => 'application/octet-stream',
					'TTL'              => '86400',
					'Urgency'          => 'high',
				),
				'body'    => $body,
			)
		);
		return is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * RFC 8291 — aes128gcm titkosítás egy rekordban.
	 */
	private static function encrypt( $payload, $ua_public, $auth_secret ) {
		if ( 65 !== strlen( $ua_public ) || 16 !== strlen( $auth_secret ) ) {
			return '';
		}
		$ephemeral = openssl_pkey_new(
			array(
				'curve_name'       => 'prime256v1',
				'private_key_type' => OPENSSL_KEYTYPE_EC,
			)
		);
		if ( ! $ephemeral ) {
			return '';
		}
		$as_public = self::raw_public_key( $ephemeral );
		$shared    = openssl_pkey_derive( self::public_pem( $ua_public ), $ephemeral, 32 );
		if ( '' === $as_public || false === $shared ) {
			return '';
		}
		$shared = str_pad( $shared, 32, "\0", STR_PAD_LEFT );

		$ikm   = hash_hkdf( 'sha256', $shared, 32, "WebPush: info\0" . $ua_public . $as_public, $auth_secret );
		$salt  = random_bytes( 16 );
		$cek   = hash_hkdf( 'sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt );
		$nonce = hash_hkdf( 'sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt );

		$tag        = '';
		$ciphertext = openssl_encrypt( $payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16 );
		if ( false === $ciphertext ) {
			return '';
		}
		return $salt . pack( 'N', 4096 ) . chr( 65 ) . $as_public . $ciphertext . $tag;
	}

	/**
	 * RFC 8292 — VAPID JWT (ES256), 12 órás érvénnyel.
	 */
	private static function vapid_jwt( $endpoint ) {
		$parts = wp_parse_url( $endpoint );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}
		$header = self::b64url( wp_json_encode( array( 'typ' => 'JWT', 'alg' => 'ES256' ) ) );
		$claims = self::b64url(
			wp_json_encode(
				array(
					'aud' => $parts['scheme'] . '://' . $parts['host'],
					'exp' => time() + 12 * HOUR_IN_SECONDS,
					'sub' => 'mailto:' . get_option( 'admin_email' ),
				)
			)
		);
		$input = $header . '.' . $claims;
		if ( ! openssl_sign( $input, $der, self::vapid()['private_pem'], OPENSSL_ALGO_SHA256 ) ) {
			return '';
		}
		$raw = self::der_to_raw_signature( $der );
		return '' === $raw ? '' : $input . '.' . self::b64url( $raw );
	}

	/* ------------------------------------------------------------------ */
	/* Segédfüggvények                                                     */
	/* ------------------------------------------------------------------ */

	private static function raw_public_key( $key ) {
		$details = openssl_pkey_get_details( $key );
		if ( empty( $details['ec']['x'] ) || empty( $details['ec']['y'] ) ) {
			return '';
		}
		return "\x04" . str_pad( $details['ec']['x'], 32, "\0", STR_PAD_LEFT ) . str_pad( $details['ec']['y'], 32, "\0", STR_PAD_LEFT );
	}

	private static function public_pem( $raw ) {
		$der = hex2bin( '3059301306072a8648ce3d020106082a8648ce3d030107034200' ) . $raw;
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PUBLIC KEY-----\n"; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	private static function der_to_raw_signature( $der ) {
		if ( strlen( $der ) < 8 || "\x30" !== $der[0] ) {
			return '';
		}
		$pos = 2;
		if ( "\x02" !== $der[ $pos ] ) {
			return '';
		}
		$r_len = ord( $der[ $pos + 1 ] );
		$r     = substr( $der, $pos + 2, $r_len );
		$pos   = $pos + 2 + $r_len;
		if ( ! isset( $der[ $pos ] ) || "\x02" !== $der[ $pos ] ) {
			return '';
		}
		$s_len = ord( $der[ $pos + 1 ] );
		$s     = substr( $der, $pos + 2, $s_len );
		$r     = str_pad( ltrim( $r, "\0" ), 32, "\0", STR_PAD_LEFT );
		$s     = str_pad( ltrim( $s, "\0" ), 32, "\0", STR_PAD_LEFT );
		return $r . $s;
	}

	public static function b64url( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public static function b64url_decode( $data ) {
		$data = strtr( (string) $data, '-_', '+/' );
		$pad  = strlen( $data ) % 4;
		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		$out = base64_decode( $data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false === $out ? '' : $out;
	}
}
