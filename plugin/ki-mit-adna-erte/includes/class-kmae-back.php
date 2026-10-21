<?php
/**
 * Back URL helpers for inline page navigation.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Back {

	public static function browse_url() {
		return home_url( '/' );
	}

	/**
	 * Back to logged-in home / guest landing.
	 */
	public static function home_url() {
		return home_url( '/' );
	}

	public static function home_label() {
		return __( 'Vissza', 'ki-mit-adna-erte' );
	}

	public static function listing_back_url() {
		$referer = wp_get_referer();
		if ( $referer && str_starts_with( $referer, home_url() ) ) {
			return $referer;
		}

		return self::home_url();
	}
}
