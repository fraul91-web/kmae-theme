<?php
/**
 * Plugin settings and defaults.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Settings {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function register_settings() {
		register_setting(
			'kmae_settings',
			'kmae_auto_moderation',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $value ) {
					return ! empty( $value ) ? 1 : 0;
				},
				'default'           => 1,
			)
		);
		register_setting(
			'kmae_settings',
			'kmae_seller_video_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_youtube_url' ),
				'default'           => '',
			)
		);
		register_setting(
			'kmae_settings',
			'kmae_buyer_video_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_youtube_url' ),
				'default'           => '',
			)
		);
		register_setting(
			'kmae_settings',
			'kmae_google_client_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'kmae_settings',
			'kmae_google_client_secret',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'kmae_settings',
			KMAE_Registration_Scan::OPTION_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $value ) {
					return ! empty( $value ) ? 1 : 0;
				},
				'default'           => 1,
			)
		);
		register_setting(
			'kmae_settings',
			KMAE_Registration_Scan::OPTION_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( 'KMAE_Registration_Scan', 'sanitize_api_key' ),
				'default'           => '',
			)
		);
		register_setting(
			'kmae_settings',
			KMAE_Registration_Scan::OPTION_MODEL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => KMAE_Registration_Scan::DEFAULT_MODEL,
			)
		);
		register_setting(
			'kmae_settings',
			KMAE_Registration_Scan::OPTION_FALLBACK,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => KMAE_Registration_Scan::DEFAULT_FALLBACK,
			)
		);
		register_setting(
			'kmae_settings',
			KMAE_Browse_Feed::OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $value ) {
					return ! empty( $value ) ? 1 : 0;
				},
				'default'           => 0,
			)
		);
		register_setting(
			'kmae_settings',
			'kmae_contact_form_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => 'info@albira.hu',
			)
		);
	}

	public static function init_defaults() {
		add_option( 'kmae_auto_moderation', 1 );
		add_option( 'kmae_seller_video_url', '' );
		add_option( 'kmae_buyer_video_url', '' );
		add_option( 'kmae_contact_form_email', 'info@albira.hu' );
	}

	/**
	 * Keep only valid YouTube watch / short / embed / youtu.be URLs.
	 *
	 * @param mixed $value Raw option.
	 * @return string
	 */
	public static function sanitize_youtube_url( $value ) {
		$url = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $url ) {
			return '';
		}

		$url = esc_url_raw( $url );
		if ( ! $url || ! self::youtube_id_from_url( $url ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Extract a YouTube video ID from common URL formats.
	 *
	 * @param string $url YouTube URL.
	 * @return string Empty if not found.
	 */
	public static function youtube_id_from_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return '';
		}

		$host = strtolower( $parts['host'] );
		$host = preg_replace( '/^www\./', '', $host );

		if ( 'youtu.be' === $host ) {
			$path = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
			return self::is_youtube_id( $path ) ? $path : '';
		}

		if ( 'youtube.com' !== $host && 'm.youtube.com' !== $host && 'youtube-nocookie.com' !== $host ) {
			return '';
		}

		$path = isset( $parts['path'] ) ? $parts['path'] : '';

		if ( preg_match( '#^/(embed|shorts|live)/([A-Za-z0-9_-]{6,})#', $path, $m ) ) {
			return $m[2];
		}

		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			if ( ! empty( $query['v'] ) && self::is_youtube_id( $query['v'] ) ) {
				return $query['v'];
			}
		}

		return '';
	}

	/**
	 * @param string $id Candidate ID.
	 */
	private static function is_youtube_id( $id ) {
		return is_string( $id ) && (bool) preg_match( '/^[A-Za-z0-9_-]{6,}$/', $id );
	}

	public static function auto_moderation_enabled() {
		return (bool) get_option( 'kmae_auto_moderation', 1 );
	}

	public static function seller_video_url() {
		return (string) get_option( 'kmae_seller_video_url', '' );
	}

	public static function buyer_video_url() {
		return (string) get_option( 'kmae_buyer_video_url', '' );
	}

	/**
	 * Inbox for the public contact form.
	 *
	 * @return string
	 */
	public static function contact_form_email() {
		$email = sanitize_email( (string) get_option( 'kmae_contact_form_email', 'info@albira.hu' ) );
		if ( ! is_email( $email ) || 'fraul91@gmail.com' === strtolower( $email ) ) {
			$email = 'info@albira.hu';
			update_option( 'kmae_contact_form_email', $email );
		}
		return $email;
	}

	public static function seller_video_id() {
		return self::youtube_id_from_url( self::seller_video_url() );
	}

	public static function buyer_video_id() {
		return self::youtube_id_from_url( self::buyer_video_url() );
	}
}
