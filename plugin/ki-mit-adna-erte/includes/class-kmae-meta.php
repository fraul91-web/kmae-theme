<?php
/**
 * Listing meta fields and save handlers.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Meta {

	const STATUS_ACTIVE          = 'active';
	const STATUS_AWAITING_PHOTOS = 'awaiting_photos';
	const STATUS_CLOSED          = 'closed';
	const STATUS_ARCHIVED        = 'archived';
	const STATUS_DRAFT           = 'draft';
	const STATUS_EXPIRED         = 'expired';

	/**
	 * User-facing status labels (seller UI).
	 *
	 * @return array<string,string>
	 */
	public static function status_labels() {
		return array(
			self::STATUS_ACTIVE          => __( '🟢 Aktív', 'ki-mit-adna-erte' ),
			self::STATUS_AWAITING_PHOTOS => __( '📸 Fotó feltöltésre vár', 'ki-mit-adna-erte' ),
			self::STATUS_CLOSED          => __( 'Lezárt', 'ki-mit-adna-erte' ),
			self::STATUS_ARCHIVED        => __( 'Archivált', 'ki-mit-adna-erte' ),
			self::STATUS_EXPIRED         => __( 'Lejárt', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Whether buyers can see the listing in browse / public single.
	 *
	 * @param int $post_id Listing ID.
	 * @return bool
	 */
	public static function is_publicly_visible( $post_id ) {
		return self::STATUS_ACTIVE === self::get_status( $post_id );
	}

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_kmae_listing', array( $this, 'save_listing_meta' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'location_notice' ) );
	}

	/**
	 * Mentés után: az érvénytelen helyszín (megye + település) nem íródott át — jelezzük.
	 */
	public function location_notice() {
		if ( empty( $_GET['kmae_loc_error'] ) || ! current_user_can( 'edit_posts' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'kmae_listing' !== $screen->post_type ) {
			return;
		}
		$code = sanitize_key( wp_unslash( $_GET['kmae_loc_error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$text = 'mismatch' === $code
			? __( 'A helyszín nem mentődött el: a megadott település nem a kiválasztott megyében van. Válassz a listából, vagy add meg az irányítószámot.', 'ki-mit-adna-erte' )
			: __( 'A helyszín nem mentődött el: a megyét és a települést is meg kell adni.', 'ki-mit-adna-erte' );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	public function add_meta_boxes() {
		add_meta_box(
			'kmae_listing_details',
			__( 'Hirdetés beállítások', 'ki-mit-adna-erte' ),
			array( $this, 'render_meta_box' ),
			'kmae_listing',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'kmae_save_listing', 'kmae_listing_nonce' );

		$county       = get_post_meta( $post->ID, '_kmae_county', true );
		$city         = get_post_meta( $post->ID, '_kmae_city', true );
		$location     = get_post_meta( $post->ID, '_kmae_location', true );
		$condition    = get_post_meta( $post->ID, '_kmae_condition', true );
		$status       = get_post_meta( $post->ID, '_kmae_status', true ) ?: self::STATUS_ACTIVE;
		$offer_types  = self::get_offer_types( $post->ID );
		$private      = KMAE_Database::get_private_data( $post->ID );

		$all_offer_types = KMAE_Taxonomies::offer_type_labels();

		include KMAE_PLUGIN_DIR . 'admin/views/meta-box-listing.php';
	}

	public function save_listing_meta( $post_id, $post ) {
		if ( ! isset( $_POST['kmae_listing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kmae_listing_nonce'] ) ), 'kmae_save_listing' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$county    = isset( $_POST['kmae_county'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_county'] ) ) : '';
		$city      = isset( $_POST['kmae_city'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_city'] ) ) : '';
		$condition = isset( $_POST['kmae_condition'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_condition'] ) ) : '';
		$status    = isset( $_POST['kmae_status'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_status'] ) ) : self::STATUS_ACTIVE;

		if ( KMAE_Counties::validate_listing_location( $county, $city ) ) {
			KMAE_Counties::save_listing_location( $post_id, $county, $city );
		} else {
			if ( '' !== $county || '' !== $city ) {
				/* A megadott helyszín nem érvényes (a település nem a megyéé): ne maradjon szó nélkül. */
				$problem = KMAE_Counties::location_error( $county, $city );
				add_filter(
					'redirect_post_location',
					static function ( $location ) use ( $problem ) {
						return add_query_arg( 'kmae_loc_error', 'mismatch' === $problem ? 'mismatch' : 'required', $location );
					}
				);
			}
			if ( isset( $_POST['kmae_location'] ) ) {
				update_post_meta( $post_id, '_kmae_location', sanitize_text_field( wp_unslash( $_POST['kmae_location'] ) ) );
			}
		}

		$prev_status = self::get_status( $post_id );
		update_post_meta( $post_id, '_kmae_condition', $condition );
		update_post_meta( $post_id, '_kmae_status', $status );

		/* Aktívból bármi másba (admin szerkesztő): a nyitott kereskedői megkeresések nem maradhatnak válaszra várva (idempotens). */
		if ( self::STATUS_ACTIVE === $prev_status && self::STATUS_ACTIVE !== $status && class_exists( 'KMAE_Dealer_Leads' ) ) {
			KMAE_Dealer_Leads::on_listing_ended( $post_id );
		}

		$offer_types = KMAE_Taxonomies::default_offer_types();
		update_post_meta( $post_id, '_kmae_offer_types', $offer_types );
		delete_post_meta( $post_id, '_kmae_trade_in_categories' );

		// Hidden minimum — stored in private table, never in post meta.
		$price_mode = KMAE_Seller::get_price_mode( $post_id );
		if ( in_array( $price_mode, array( KMAE_Seller::PRICE_FIXED, KMAE_Seller::PRICE_GUIDE ), true ) && isset( $_POST['kmae_minimum_cash'] ) ) {
			$minimum = floatval( wp_unslash( $_POST['kmae_minimum_cash'] ) );
			KMAE_Database::save_private_data( $post_id, $minimum );
		} else {
			KMAE_Database::save_private_data( $post_id, 0 );
		}

		if ( self::STATUS_ACTIVE === $status && ! get_post_meta( $post_id, '_kmae_published_at', true ) ) {
			update_post_meta( $post_id, '_kmae_published_at', current_time( 'mysql' ) );
		}

		/* Lejárt → aktív: a 60 napos lejárati óra újraindul. */
		if ( self::STATUS_ACTIVE === $status && self::STATUS_EXPIRED === $prev_status ) {
			update_post_meta( $post_id, '_kmae_renewed_at', current_time( 'mysql' ) );
			delete_post_meta( $post_id, '_kmae_expired_at' );
		}

		/* Archive purge clock uses existing `_kmae_archived_at` (not a new meta key). */
		if ( self::STATUS_ARCHIVED === $status && self::STATUS_ARCHIVED !== $prev_status ) {
			update_post_meta( $post_id, '_kmae_archived_at', current_time( 'mysql' ) );
		} elseif ( self::STATUS_ARCHIVED !== $status && self::STATUS_ARCHIVED === $prev_status ) {
			delete_post_meta( $post_id, '_kmae_archived_at' );
		}

		KMAE_Vehicle::save_from_post( $post_id );
		KMAE_Seller::sync_vehicle_value_meta( $post_id );
		KMAE_Listings::ensure_launch_category( $post_id );
	}

	public static function get_status( $post_id ) {
		return get_post_meta( $post_id, '_kmae_status', true ) ?: self::STATUS_ACTIVE;
	}

	public static function get_offer_types( $post_id ) {
		unset( $post_id );
		return KMAE_Taxonomies::default_offer_types();
	}
}
