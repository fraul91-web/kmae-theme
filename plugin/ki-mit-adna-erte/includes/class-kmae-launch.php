<?php
/**
 * Launch mode — single-category focus (e.g. cars only at launch).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Launch {

	/** @var string 'single' | 'multi' */
	const MODE_SINGLE = 'single';
	const MODE_MULTI  = 'multi';

	public static function mode() {
		return defined( 'KMAE_LAUNCH_MODE' ) ? KMAE_LAUNCH_MODE : self::MODE_MULTI;
	}

	public static function category_slug() {
		return defined( 'KMAE_LAUNCH_CATEGORY' ) ? KMAE_LAUNCH_CATEGORY : 'jarmu';
	}

	public static function is_single_category() {
		return self::MODE_SINGLE === self::mode();
	}

	public static function should_skip_category_wizard() {
		return self::is_single_category();
	}

	public static function get_launch_category_id() {
		$term = get_term_by( 'slug', self::category_slug(), 'kmae_category' );
		return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
	}

	/**
	 * Categories visible in forms and browse (filtered in single mode).
	 *
	 * @return WP_Term[]|array
	 */
	public static function get_available_categories() {
		KMAE_Taxonomies::seed_categories();

		if ( ! self::is_single_category() ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'kmae_category',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			return is_wp_error( $terms ) ? array() : $terms;
		}

		$term = get_term_by( 'slug', self::category_slug(), 'kmae_category' );
		return ( $term && ! is_wp_error( $term ) ) ? array( $term ) : array();
	}

	public static function wizard_total_steps() {
		if ( KMAE_Vehicle::is_vehicle_launch() ) {
			return 3;
		}
		return self::should_skip_category_wizard() ? 2 : 3;
	}

	public static function wizard_step_label( $step ) {
		return sprintf(
			/* translators: 1: current step, 2: total steps */
			__( 'Lépés %1$d / %2$d', 'ki-mit-adna-erte' ),
			$step,
			self::wizard_total_steps()
		);
	}

	public static function site_tagline() {
		if ( self::is_single_category() && 'jarmu' === self::category_slug() ) {
			return __( 'Autóvásárlás és alku — megalázás nélkül.', 'ki-mit-adna-erte' );
		}
		return __( 'Nem eladod. Megnézed, mit adnának érte.', 'ki-mit-adna-erte' );
	}
}
