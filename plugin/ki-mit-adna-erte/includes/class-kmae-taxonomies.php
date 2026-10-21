<?php
/**
 * Taxonomies: category + accepted offer types on listing.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Taxonomies {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 0 );
		add_action( 'init', array( $this, 'maybe_seed_categories' ), 20 );
	}

	public function register() {
		self::register_taxonomy();
	}

	public static function register_taxonomy() {
		register_taxonomy(
			'kmae_category',
			'kmae_listing',
			array(
				'labels'            => array(
					'name'          => __( 'Kategóriák', 'ki-mit-adna-erte' ),
					'singular_name' => __( 'Kategória', 'ki-mit-adna-erte' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'rewrite'           => array( 'slug' => 'kategoria' ),
				'show_admin_column' => true,
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Ensure default categories exist (activation + fallback on init).
	 */
	public function maybe_seed_categories() {
		if ( ! taxonomy_exists( 'kmae_category' ) ) {
			return;
		}

		$existing = get_terms(
			array(
				'taxonomy'   => 'kmae_category',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $existing ) || empty( $existing ) ) {
			self::seed_categories();
		}
	}

	public static function seed_categories() {
		$categories = array(
			'jarmu'       => 'Jármű',
			'ingatlan'    => 'Ingatlan',
			'elektronika' => 'Elektronika',
			'telefon'     => 'Telefon',
			'laptop'      => 'Laptop',
			'gyujtemeny'  => 'Gyűjtemény',
			'otthon'      => 'Otthon',
			'sport'       => 'Sport',
			'ruhazat'     => 'Ruházat',
			'egyeb'       => 'Egyéb',
		);

		foreach ( $categories as $slug => $name ) {
			if ( ! term_exists( $slug, 'kmae_category' ) ) {
				wp_insert_term( $name, 'kmae_category', array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Get human-readable offer type labels.
	 */
	public static function offer_type_labels() {
		return array(
			KMAE_OFFER_TYPE_CASH => __( 'Készpénz', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Default accepted offer types when the seller form no longer collects them.
	 *
	 * @return string[]
	 */
	public static function default_offer_types() {
		return array( KMAE_OFFER_TYPE_CASH );
	}
}
