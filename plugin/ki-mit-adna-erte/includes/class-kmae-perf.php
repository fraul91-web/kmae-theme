<?php
/**
 * Háttérben küldött e-mailek.
 *
 * Az e-mailek a válasz UTÁN mennek ki: a felhasználónak nem kell megvárnia
 * a levelezőszervert (a WP Mail SMTP admin próbalevele kivétel).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Perf {

	/** @var bool A válasz már le van zárva a felhasználó felé. */
	private static $finished = false;

	/** @var array<int,array> Háttérbe tett e-mailek. */
	private static $mail_queue = array();

	/** @var bool A sorból küldünk épp (ilyenkor nem tesszük újra sorba). */
	private static $sending_queue = false;

	public static function init() {
		add_filter( 'pre_wp_mail', array( __CLASS__, 'pre_wp_mail' ), 9999, 2 );
		add_action( 'shutdown', array( __CLASS__, 'send_queue' ), 110 );
	}

	/**
	 * A választ lezárjuk a felhasználó felé; a további munka (push, e-mail)
	 * már a háttérben fut. Többször hívható.
	 */
	public static function finish_response() {
		if ( self::$finished ) {
			return;
		}
		self::$finished = true;
		ignore_user_abort( true );
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}
	}

	/**
	 * Mehet-e háttérbe a levél? Normál admin oldalakon (pl. WP Mail SMTP
	 * próbalevél) nem — ott az eredményt azonnal látni kell.
	 */
	private static function can_defer( $atts ) {
		if ( ! empty( $atts['attachments'] ) ) {
			return false;
		}
		if ( wp_doing_ajax() || wp_doing_cron() || ! is_admin() ) {
			return true;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : '';
		return 'admin-post.php' === $script;
	}

	public static function pre_wp_mail( $return, $atts ) {
		if ( null !== $return ) {
			return $return;
		}
		if ( ! self::$sending_queue && self::can_defer( $atts ) ) {
			self::$mail_queue[] = $atts;
			return true;
		}
		return $return;
	}

	/**
	 * A háttérbe tett levelek kiküldése a válasz lezárása után.
	 */
	public static function send_queue() {
		if ( empty( self::$mail_queue ) ) {
			return;
		}
		self::finish_response();
		self::$sending_queue = true;
		foreach ( self::$mail_queue as $atts ) {
			wp_mail(
				$atts['to'] ?? '',
				$atts['subject'] ?? '',
				$atts['message'] ?? '',
				$atts['headers'] ?? '',
				$atts['attachments'] ?? array()
			);
		}
		self::$mail_queue    = array();
		self::$sending_queue = false;
	}
}
