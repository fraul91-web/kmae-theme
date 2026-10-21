<?php
/**
 * Custom post type: kmae_listing (hirdetés).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Post_Types {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 0 );
		add_filter( 'template_include', array( $this, 'template_loader' ) );
	}

	public function register() {
		self::register_post_type();
	}

	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Hirdetések', 'ki-mit-adna-erte' ),
			'singular_name'      => __( 'Hirdetés', 'ki-mit-adna-erte' ),
			'add_new'            => __( 'Új hirdetés', 'ki-mit-adna-erte' ),
			'add_new_item'       => __( 'Új hirdetés feladása', 'ki-mit-adna-erte' ),
			'edit_item'          => __( 'Hirdetés szerkesztése', 'ki-mit-adna-erte' ),
			'view_item'          => __( 'Hirdetés megtekintése', 'ki-mit-adna-erte' ),
			'all_items'          => __( 'Összes hirdetés', 'ki-mit-adna-erte' ),
			'search_items'       => __( 'Hirdetés keresése', 'ki-mit-adna-erte' ),
			'not_found'          => __( 'Nincs hirdetés.', 'ki-mit-adna-erte' ),
		);

		register_post_type(
			'kmae_listing',
			array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'hirdetesek' ),
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail' ),
				'menu_icon'           => 'dashicons-store',
				'show_in_menu'        => 'kmae-dashboard',
				'show_in_rest'        => true,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Lejárt hirdetés: a link nem törik el, hanem „Ez az autó már nem elérhető”
	 * + hasonló autók. A tulajdonos és az admin a rendes adatlapot látja.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function is_unavailable_for_viewer( $listing_id ) {
		if ( ! $listing_id || KMAE_Meta::STATUS_EXPIRED !== KMAE_Meta::get_status( $listing_id ) ) {
			return false;
		}
		$is_owner = is_user_logged_in() && get_current_user_id() === (int) get_post_field( 'post_author', $listing_id );
		return ! $is_owner && ! current_user_can( 'manage_options' );
	}

	public function template_loader( $template ) {
		if ( is_post_type_archive( 'kmae_listing' ) ) {
			$custom = KMAE_PLUGIN_DIR . 'templates/archive-listing.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		if ( is_singular( 'kmae_listing' ) && self::is_unavailable_for_viewer( get_queried_object_id() ) ) {
			return KMAE_PLUGIN_DIR . 'templates/listing-unavailable.php';
		}
		if ( is_singular( 'kmae_listing' ) ) {
			$custom = KMAE_PLUGIN_DIR . 'templates/single-listing.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}
}
