<?php
/**
 * Könnyűsúlyú élő-jelzés („van-e újdonság?”).
 *
 * Minden felhasználó kap egy véletlen, kitalálhatatlan kulcsot (user meta). Ha
 * egy őt érintő esemény történik (új chatüzenet),
 * a `uploads/kmae-live/<kulcs>.v` jelzőfájl új értéket kap. A böngésző ezt
 * SIMA FÁJLKÉNT olvassa (se PHP, se WordPress — mint egy kép) — a teljes,
 * drága `kmae_poll_page` lekérdezés csak akkor fut, ha a jelző változott.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Live_Signal {

	const META_KEY = 'kmae_live_key';
	const DIR_NAME = 'kmae-live';
	/* A mappa .htaccess-ének verziója — változáskor újraíródik. */
	const HTACCESS_REV = 'kmae-live rev3';

	/** @var array<int,bool> Egy kérésen belül egyszer jelzünk felhasználónként. */
	private static $pending = array();

	/** @var array<int,array{title:string,body:string,url:string,tag:string}> Push értesítés felhasználónként (az első, konkrétabb esemény nyer). */
	private static $notes = array();

	public static function init() {
		add_action( 'kmae_conversation_message_created', array( __CLASS__, 'on_conversation_event' ), 10, 2 );

		/* A fájlírás és a push a kérés végére kerül: egy műveletben több
		   esemény is lehet, de felhasználónként elég egyszer jelezni. */
		add_action( 'shutdown', array( __CLASS__, 'flush' ), 100 );
	}

	/**
	 * Eseményenkénti értesítésszöveg: [cím, szöveg a hirdetés neve után].
	 *
	 * @return array{0:string,1:string}
	 */
	private static function text_for( $hook, $kind = '' ) {
		if ( 'offer' === $kind ) {
			return array( __( 'Ajánlatot kaptál!', 'ki-mit-adna-erte' ), __( 'új ajánlat érkezett.', 'ki-mit-adna-erte' ) );
		}
		if ( 'offer_retracted' === $kind ) {
			return array( __( 'Ajánlat visszavonva', 'ki-mit-adna-erte' ), __( 'a vevő visszavonta az ajánlatát.', 'ki-mit-adna-erte' ) );
		}
		if ( 'dealer_lead' === $kind ) {
			return array( __( 'Egy kereskedés érdeklődik az autód iránt', 'ki-mit-adna-erte' ), __( 'nézd meg a chatben.', 'ki-mit-adna-erte' ) );
		}
		$map = array(
			'kmae_conversation_message_created' => array( __( 'Új üzenet', 'ki-mit-adna-erte' ), __( 'új üzeneted érkezett.', 'ki-mit-adna-erte' ) ),
		);
		return $map[ $hook ] ?? array( __( 'Új üzenet', 'ki-mit-adna-erte' ), __( 'nézd meg a részleteket.', 'ki-mit-adna-erte' ) );
	}

	/**
	 * @return array{title:string,body:string,url:string,tag:string}
	 */
	private static function note( $listing_id, $url, $kind = '' ) {
		list( $title, $tail ) = self::text_for( current_filter(), $kind );
		$name                 = $listing_id > 0 ? wp_strip_all_tags( get_the_title( (int) $listing_id ) ) : '';
		$body                 = '' !== $name ? $name . ': ' . $tail : mb_strtoupper( mb_substr( $tail, 0, 1 ) ) . mb_substr( $tail, 1 );
		return array(
			'title' => $title,
			'body'  => $body,
			'url'   => (string) $url,
			'tag'   => 'kmae-' . md5( (string) $url ),
		);
	}

	/**
	 * Működik-e a feltöltési mappa (ide kerülnek a jelzőfájlok).
	 */
	public static function is_available() {
		$uploads = wp_upload_dir( null, false );
		return empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) && ! empty( $uploads['baseurl'] );
	}

	/**
	 * A felhasználó jelzőfájljának nyilvános URL-je (a fájl létezését biztosítja).
	 */
	public static function signal_url( $user_id ) {
		$key = self::user_key( $user_id );
		if ( '' === $key || ! self::is_available() ) {
			return '';
		}
		$file = self::ensure_dir() . '/' . $key . '.v';
		if ( ! is_file( $file ) ) {
			@file_put_contents( $file, '0', LOCK_EX ); // phpcs:ignore
		}
		$uploads = wp_upload_dir( null, false );
		return set_url_scheme( trailingslashit( $uploads['baseurl'] ) . self::DIR_NAME . '/' . $key . '.v' );
	}

	private static function dir() {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . self::DIR_NAME;
	}

	private static function ensure_dir() {
		$dir = self::dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
		}
		/* A .v fájlok webről olvashatók (a nevük kitalálhatatlan), a mappát az
		   index.php védi a listázástól, PHP nem futhat benne, és a böngésző /
		   proxy nem tárolhatja a jelzőket. */
		$htaccess = $dir . '/.htaccess';
		if ( ! is_file( $htaccess ) || false === strpos( (string) @file_get_contents( $htaccess ), self::HTACCESS_REV ) ) { // phpcs:ignore
			@file_put_contents( // phpcs:ignore
				$htaccess,
				'# ' . self::HTACCESS_REV . "\n"
				. "<FilesMatch \"\\.(php|phtml|phar)$\">\n\t<IfModule mod_authz_core.c>\n\t\tRequire all denied\n\t</IfModule>\n\t<IfModule !mod_authz_core.c>\n\t\tDeny from all\n\t</IfModule>\n</FilesMatch>\n"
				. "<IfModule mod_headers.c>\n\t<FilesMatch \"\\.v$\">\n\t\tHeader set Cache-Control \"no-store, max-age=0\"\n\t\tHeader set Content-Type \"text/plain; charset=utf-8\"\n\t</FilesMatch>\n</IfModule>\n"
			);
		}
		return $dir;
	}

	/**
	 * A felhasználó véletlen kulcsa (32 hex karakter).
	 */
	public static function user_key( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return '';
		}
		$key = (string) get_user_meta( $user_id, self::META_KEY, true );
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $key ) ) {
			$key = bin2hex( random_bytes( 16 ) );
			update_user_meta( $user_id, self::META_KEY, $key );
		}
		return $key;
	}

	/**
	 * A jelző jelenlegi értéke ('0', ha még nem volt esemény).
	 */
	public static function version( $user_id ) {
		$key = self::user_key( $user_id );
		if ( '' === $key ) {
			return '0';
		}
		$file = self::dir() . '/' . $key . '.v';
		$v    = is_file( $file ) ? preg_replace( '/[^0-9]/', '', (string) @file_get_contents( $file ) ) : ''; // phpcs:ignore
		return '' !== $v ? $v : '0';
	}

	/**
	 * Jelzés kérése egy vagy több felhasználónak (a kérés végén íródik ki).
	 *
	 * @param int[]      $user_ids Users.
	 * @param array|null $note     Push értesítés tartalma (opcionális).
	 */
	public static function bump( $user_ids, $note = null ) {
		foreach ( (array) $user_ids as $user_id ) {
			$user_id = (int) $user_id;
			if ( $user_id > 0 ) {
				self::$pending[ $user_id ] = true;
				if ( is_array( $note ) && ! isset( self::$notes[ $user_id ] ) ) {
					self::$notes[ $user_id ] = $note;
				}
			}
		}
	}

	public static function flush() {
		if ( empty( self::$pending ) ) {
			return;
		}
		if ( self::is_available() ) {
			$dir   = self::ensure_dir();
			$value = (string) (int) round( microtime( true ) * 1000 );
			foreach ( array_keys( self::$pending ) as $user_id ) {
				$key = self::user_key( $user_id );
				if ( '' !== $key ) {
					@file_put_contents( $dir . '/' . $key . '.v', $value, LOCK_EX ); // phpcs:ignore
				}
			}
		}
		self::$pending = array();

		/* Push: a műveletet végző felhasználó nem kap értesítést a saját
		   lépéséről — csak a másik fél. */
		$notes = self::$notes;
		unset( $notes[ get_current_user_id() ] );
		self::$notes = array();
		if ( empty( $notes ) || ! class_exists( 'KMAE_Push' ) || ! KMAE_Push::is_available() ) {
			return;
		}

		/* A választ előbb lezárjuk, így a felhasználónak nem kell megvárnia
		   a push szolgáltatók válaszát. */
		if ( class_exists( 'KMAE_Perf' ) ) {
			KMAE_Perf::finish_response();
		}
		foreach ( $notes as $user_id => $note ) {
			KMAE_Push::send_to_user( (int) $user_id, $note );
		}
	}

	/* ---- Eseményből érintett felek ---- */

	public static function on_conversation_event( $first, $conversation_id = 0 ) {
		$kind = class_exists( 'KMAE_Chat_Offers' ) ? KMAE_Chat_Offers::message_kind( (int) $first ) : '';
		self::bump_conversation( (int) $conversation_id, $kind, (int) $first );
	}

	private static function bump_conversation( $conversation_id, $kind = '', $message_id = 0 ) {
		$conversation = $conversation_id > 0 && class_exists( 'KMAE_Conversations' ) ? KMAE_Conversations::get( $conversation_id ) : null;
		if ( $conversation ) {
			$url   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::conversation_thread_url( $conversation_id ) : home_url( '/' );
			$users = array();
			foreach ( array( (int) $conversation->buyer_id, (int) $conversation->seller_id ) as $member_id ) {
				/* Letiltott feladó: a tiltó nem kap jelzést / push-t. */
				if ( $message_id <= 0 || ! KMAE_User_Blocks::hides_message( $message_id, $member_id ) ) {
					$users[] = $member_id;
				}
			}
			self::bump( $users, self::note( (int) ( $conversation->listing_id ?? 0 ), $url, $kind ) );
		}
	}
}
