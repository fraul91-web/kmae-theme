<?php
/**
 * Private-seller listing rules: price mode, commitments, active-cycle limit.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Seller {

	const PRICE_FIXED = 'fixed';
	const PRICE_GUIDE = 'guide';
	const PRICE_OPEN  = 'open';

	const META_PRICE_MODE   = '_kmae_price_mode';
	const META_FIXED_PRICE  = '_kmae_fixed_price';
	const META_GUIDE_PRICE  = '_kmae_guide_price';
	const META_COMMITMENTS  = '_kmae_commitments';
	const META_EXTRA_OTHER  = '_kmae_commit_extra_other';

	const COMMIT_PICKUP_24H         = 'pickup_24h';
	const COMMIT_SPARE_WHEELS       = 'spare_wheels';
	const COMMIT_INDEPENDENT_MECH   = 'independent_mechanic';
	const COMMIT_OTHER              = 'other';
	const EXTRA_OTHER_MIN_LENGTH    = 10;

	/**
	 * The three sale modes. Single source for wizard cards, admin and public labels.
	 *
	 * @return array<string, array{label:string,desc:string,buyer_hint:string,price_field:string}>
	 */
	public static function price_mode_defs() {
		return array(
			self::PRICE_FIXED => array(
				'label'       => __( 'Fix ár', 'ki-mit-adna-erte' ),
				'desc'        => __( 'Megadod az árat, és a vevő pontosan ennyit ajánlhat meg.', 'ki-mit-adna-erte' ),
				'buyer_hint'  => __( 'Az eladó fix árat adott meg.', 'ki-mit-adna-erte' ),
				'price_field' => 'kmae_fixed_price',
				'icon'        => 'tag',
				'popular'     => true,
				'features'    => array(
					__( 'Gyors és egyszerű', 'ki-mit-adna-erte' ),
					__( 'Egyértelmű a vevőknek', 'ki-mit-adna-erte' ),
					__( 'Nincs alku', 'ki-mit-adna-erte' ),
				),
			),
			self::PRICE_GUIDE => array(
				'label'       => __( 'Irányár', 'ki-mit-adna-erte' ),
				'desc'        => __( 'Megadod az irányárat — a vevő látja, de ettől eltérő összeget is ajánlhat.', 'ki-mit-adna-erte' ),
				'buyer_hint'  => __( 'Az eladó irányárat adott meg — ettől eltérő összeget is ajánlhatsz.', 'ki-mit-adna-erte' ),
				'price_field' => 'kmae_guide_price',
				'icon'        => 'sliders',
				'features'    => array(
					__( 'Rugalmas értékesítés', 'ki-mit-adna-erte' ),
					__( 'Több érdeklődőt vonzhat', 'ki-mit-adna-erte' ),
					__( 'Lehetőség az alkura', 'ki-mit-adna-erte' ),
				),
			),
			self::PRICE_OPEN  => array(
				'label'       => __( 'Ki mit adna érte', 'ki-mit-adna-erte' ),
				'desc'        => __( 'Nem adsz meg árat — megvárod, ki mit adna érte.', 'ki-mit-adna-erte' ),
				'buyer_hint'  => __( 'Az eladó nem adott meg árat — te döntöd el, mennyit adnál érte.', 'ki-mit-adna-erte' ),
				'price_field' => '',
				'icon'        => 'chat',
				'features'    => array(
					__( 'Valós piaci visszajelzés', 'ki-mit-adna-erte' ),
					__( 'Több ajánlatot kaphatsz', 'ki-mit-adna-erte' ),
					__( 'Te döntöd el, melyik a legjobb', 'ki-mit-adna-erte' ),
				),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function price_mode_labels() {
		return wp_list_pluck( self::price_mode_defs(), 'label' );
	}

	/**
	 * Core optional seller pledges (checkboxes).
	 *
	 * @return array<string, array{label:string,desc:string,reward?:string}>
	 */
	public static function commitment_defs() {
		return array(
			self::COMMIT_PICKUP_24H       => array(
				'label'  => __( '24 órán belül elvihető', 'ki-mit-adna-erte' ),
				'desc'   => __( 'Ha a vevő 24 órán belül eljön, a megállapodott összegért eladod.', 'ki-mit-adna-erte' ),
				'reward' => '',
			),
		);
	}

	/**
	 * Extra pledges shown as badges.
	 *
	 * @return array<string, array{label:string,desc:string,badge:string}>
	 */
	public static function extra_commitment_defs() {
		return array(
			self::COMMIT_SPARE_WHEELS     => array(
				'label' => __( 'Plusz kerékgarnitúra', 'ki-mit-adna-erte' ),
				'desc'  => __( 'Pl. téli/nyári plusz garnitúra jár az autóval.', 'ki-mit-adna-erte' ),
				'badge' => __( '+ kerékgarnitúra', 'ki-mit-adna-erte' ),
			),
			self::COMMIT_INDEPENDENT_MECH => array(
				'label' => __( 'Független szerelőhöz vihető', 'ki-mit-adna-erte' ),
				'desc'  => __( 'A vevő elviheti független szerelőhöz megnézni.', 'ki-mit-adna-erte' ),
				'badge' => __( 'Független szerelő', 'ki-mit-adna-erte' ),
			),
			self::COMMIT_OTHER            => array(
				'label' => __( 'Egyéb extra / ajándék', 'ki-mit-adna-erte' ),
				'desc'  => __( 'Írd le röviden, mit adsz még.', 'ki-mit-adna-erte' ),
				'badge' => __( 'Extra', 'ki-mit-adna-erte' ),
			),
		);
	}

	/**
	 * @param string $mode Raw.
	 * @return string
	 */
	public static function sanitize_price_mode( $mode ) {
		$mode = sanitize_key( (string) $mode );

		// Pre-3.18 listings used a single "offers" mode with a mandatory hidden minimum.
		if ( 'offers' === $mode ) {
			$mode = self::PRICE_OPEN;
		}

		return isset( self::price_mode_defs()[ $mode ] ) ? $mode : self::PRICE_OPEN;
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return string
	 */
	public static function get_price_mode( $listing_id ) {
		return self::sanitize_price_mode( get_post_meta( $listing_id, self::META_PRICE_MODE, true ) );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return float
	 */
	public static function get_fixed_price( $listing_id ) {
		return (float) get_post_meta( $listing_id, self::META_FIXED_PRICE, true );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return float
	 */
	public static function get_guide_price( $listing_id ) {
		return (float) get_post_meta( $listing_id, self::META_GUIDE_PRICE, true );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function is_fixed_price( $listing_id ) {
		return self::PRICE_FIXED === self::get_price_mode( $listing_id );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function is_guide_price( $listing_id ) {
		return self::PRICE_GUIDE === self::get_price_mode( $listing_id );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function is_open_price( $listing_id ) {
		return self::PRICE_OPEN === self::get_price_mode( $listing_id );
	}

	/**
	 * Publicly shown price. 0 when the mode has no public price.
	 *
	 * @param int $listing_id Listing ID.
	 * @return float
	 */
	public static function get_public_price( $listing_id ) {
		switch ( self::get_price_mode( $listing_id ) ) {
			case self::PRICE_FIXED:
				return self::get_fixed_price( $listing_id );

			case self::PRICE_GUIDE:
				return self::get_guide_price( $listing_id );
		}

		return 0;
	}

	/**
	 * Keep `_kmae_vehicle_value` aligned with the seller's public fixed/guide price.
	 *
	 * @param int $listing_id Listing ID.
	 */
	public static function sync_vehicle_value_meta( $listing_id ) {
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 || ! class_exists( 'KMAE_Vehicle' ) ) {
			return;
		}

		update_post_meta( $listing_id, KMAE_Vehicle::META_VALUE, (int) self::get_public_price( $listing_id ) );
	}

	/**
	 * Single source for price rendering on cards, swipe and single view.
	 *
	 * @param int $listing_id Listing ID.
	 * @return array{mode:string,label:string,amount:float,formatted:string}|null Null when there is no public price.
	 */
	public static function get_public_price_row( $listing_id ) {
		$mode   = self::get_price_mode( $listing_id );
		$amount = self::get_public_price( $listing_id );

		if ( $amount < 1 ) {
			return null;
		}

		return array(
			'mode'      => $mode,
			'label'     => self::price_mode_defs()[ $mode ]['label'],
			'amount'    => $amount,
			'formatted' => self::format_cash( $amount ),
		);
	}

	public static function format_cash( $amount ) {
		return number_format( (float) $amount, 0, ',', ' ' ) . ' Ft';
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return array<int, string>
	 */
	public static function get_commitments( $listing_id ) {
		$raw = get_post_meta( $listing_id, self::META_COMMITMENTS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$allowed = array_merge( array_keys( self::commitment_defs() ), array_keys( self::extra_commitment_defs() ) );
		return array_values( array_intersect( array_map( 'sanitize_key', $raw ), $allowed ) );
	}

	/**
	 * @param int    $listing_id Listing ID.
	 * @param string $code       Commitment code.
	 * @return bool
	 */
	public static function has_commitment( $listing_id, $code ) {
		return in_array( $code, self::get_commitments( $listing_id ), true );
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return string
	 */
	public static function get_extra_other( $listing_id ) {
		return (string) get_post_meta( $listing_id, self::META_EXTRA_OTHER, true );
	}

	/**
	 * Public badge rows for listing cards / single view.
	 *
	 * @param int $listing_id Listing ID.
	 * @return array<int, array{code:string,label:string,class:string}>
	 */
	public static function get_badge_rows( $listing_id ) {
		$rows = array();
		$extras = self::extra_commitment_defs();

		if ( self::has_commitment( $listing_id, self::COMMIT_PICKUP_24H ) ) {
			$rows[] = array(
				'code'  => self::COMMIT_PICKUP_24H,
				'label' => __( '24 órán belül elvihető', 'ki-mit-adna-erte' ),
				'class' => 'kmae-pledge-badge--pickup',
			);
		}

		foreach ( $extras as $code => $info ) {
			if ( ! self::has_commitment( $listing_id, $code ) ) {
				continue;
			}
			$label = $info['badge'];
			if ( self::COMMIT_OTHER === $code ) {
				$other = self::get_extra_other( $listing_id );
				$label = $other !== '' ? $other : $info['badge'];
			}
			$rows[] = array(
				'code'  => $code,
				'label' => $label,
				'class' => 'kmae-pledge-badge--extra',
			);
		}

		unset( $defs );
		return $rows;
	}

	/**
	 * Active selling cycle = listing with status "active".
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function count_active_cycle_listings( $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'kmae_listing',
				'post_status'            => 'publish',
				'author'                 => (int) $user_id,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_kmae_status',
						'value'   => array(
							KMAE_Meta::STATUS_ACTIVE,
							KMAE_Meta::STATUS_AWAITING_PHOTOS,
							KMAE_Meta::STATUS_CLOSED,
						),
						'compare' => 'IN',
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Listing creation is unlimited for every account type (private and company).
	 *
	 * @param int $user_id User ID.
	 * @return true|WP_Error
	 */
	public static function can_create_listing( $user_id ) {
		return true;
	}

	/**
	 * Parse a whole-number price from POST (supports formatted input like 3.500.000).
	 *
	 * @param string $key POST field name.
	 * @return int
	 */
	private static function parse_price_from_post( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return 0;
		}

		$raw = preg_replace( '/\D/', '', (string) wp_unslash( $_POST[ $key ] ) );
		return '' !== $raw ? absint( $raw ) : 0;
	}

	/**
	 * Validate + save price mode, defects, commitments from POST.
	 *
	 * @param int  $post_id Listing ID.
	 * @param bool $is_new  New listing.
	 * @return true|WP_Error
	 */
	public static function save_from_post( $post_id, $is_new = false ) {
		$mode = self::sanitize_price_mode( isset( $_POST['kmae_price_mode'] ) ? wp_unslash( $_POST['kmae_price_mode'] ) : '' );

		$fixed_price  = self::parse_price_from_post( 'kmae_fixed_price' );
		$guide_price  = self::parse_price_from_post( 'kmae_guide_price' );
		$minimum_cash = self::parse_price_from_post( 'kmae_minimum_cash' );
		$extra_other  = isset( $_POST['kmae_commit_extra_other'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_commit_extra_other'] ) ) : '';

		$raw_commits = isset( $_POST['kmae_commitments'] ) ? (array) wp_unslash( $_POST['kmae_commitments'] ) : array();
		$allowed     = array_merge( array_keys( self::commitment_defs() ), array_keys( self::extra_commitment_defs() ) );
		$commits     = array_values( array_intersect( array_map( 'sanitize_key', $raw_commits ), $allowed ) );

		if ( self::PRICE_FIXED === $mode && $fixed_price < 1 ) {
			return new WP_Error( 'fixed_price_required', __( 'Add meg a fix árat.', 'ki-mit-adna-erte' ) );
		}

		if ( self::PRICE_GUIDE === $mode && $guide_price < 1 ) {
			return new WP_Error( 'guide_price_required', __( 'Add meg az irányárat.', 'ki-mit-adna-erte' ) );
		}

		/* Fix árnál nincs rejtett minimum: a vevő pontosan a kiírt árat ajánlja. */
		$minimum_enabled = in_array( $mode, array( self::PRICE_GUIDE, self::PRICE_OPEN ), true );
		$public_price    = $guide_price;
		/* „Ki mit adna érte” módban nincs kiírt ár, amihez a minimumot mérni lehetne. */
		if ( self::PRICE_GUIDE === $mode && $minimum_cash > 0 && $minimum_cash > $public_price ) {
			return new WP_Error( 'minimum_cash_too_high', __( 'A rejtett minimum nem lehet magasabb a megadott árnál.', 'ki-mit-adna-erte' ) );
		}

		if ( in_array( self::COMMIT_OTHER, $commits, true ) && '' === $extra_other ) {
			return new WP_Error( 'extra_other_required', __( 'Írd le az egyéb extrát.', 'ki-mit-adna-erte' ) );
		}

		if ( in_array( self::COMMIT_OTHER, $commits, true ) && mb_strlen( trim( $extra_other ) ) < self::EXTRA_OTHER_MIN_LENGTH ) {
			return new WP_Error( 'extra_other_short', __( 'Az egyéb extra leírása legalább 10 karakter legyen.', 'ki-mit-adna-erte' ) );
		}

		update_post_meta( $post_id, self::META_PRICE_MODE, $mode );
		update_post_meta( $post_id, self::META_FIXED_PRICE, self::PRICE_FIXED === $mode ? $fixed_price : 0 );
		update_post_meta( $post_id, self::META_GUIDE_PRICE, self::PRICE_GUIDE === $mode ? $guide_price : 0 );
		update_post_meta( $post_id, self::META_COMMITMENTS, $commits );
		update_post_meta( $post_id, self::META_EXTRA_OTHER, in_array( self::COMMIT_OTHER, $commits, true ) ? $extra_other : '' );

		$offer_types = KMAE_Meta::get_offer_types( $post_id );
		if ( empty( $offer_types ) ) {
			update_post_meta( $post_id, '_kmae_offer_types', KMAE_Taxonomies::default_offer_types() );
		}

		$minimum_cash = $minimum_enabled ? $minimum_cash : 0;
		KMAE_Database::save_private_data( $post_id, $minimum_cash );

		self::sync_vehicle_value_meta( $post_id );

		unset( $is_new );
		return true;
	}
}
