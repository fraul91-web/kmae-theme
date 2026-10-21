<?php
/**
 * Listing query helpers with filters.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Query {

	public static function get_listings( $args = array() ) {
		$defaults = array(
			'per_page' => 24,
			'category' => '',
			'type'     => '',
			'county'   => '',
			'body'     => '',
			'bodies'   => array(),
			'mode'     => 'grid',
			/* Böngészés: amire a vevő már írt / ajánlatot tett, azt a chaten éri el. */
			'exclude_contacted' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		if ( KMAE_Launch::is_single_category() && empty( $args['category'] ) ) {
			$args['category'] = KMAE_Launch::category_slug();
		}

		$query_args = array(
			'post_type'      => 'kmae_listing',
			'posts_per_page' => absint( $args['per_page'] ),
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_kmae_status',
					'value' => KMAE_Meta::STATUS_ACTIVE,
				),
			),
		);

		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'kmae_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $args['category'] ),
				),
			);
		}

		if ( KMAE_OFFER_TYPE_CASH === $args['type'] ) {
			$query_args['meta_query'][] = array(
				'key'     => '_kmae_offer_types',
				'value'   => KMAE_OFFER_TYPE_CASH,
				'compare' => 'LIKE',
			);
		}

		if ( ! empty( $args['county'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_kmae_county',
				'value'   => sanitize_text_field( $args['county'] ),
				'compare' => '=',
			);
		}

		$bodies = ! empty( $args['bodies'] ) && is_array( $args['bodies'] )
			? array_values( array_filter( $args['bodies'], static function ( $slug ) {
				return 'mind' !== $slug;
			} ) )
			: array();

		if ( empty( $bodies ) && ! empty( $args['body'] ) && 'mind' !== $args['body'] ) {
			$bodies = array( sanitize_key( $args['body'] ) );
		}

		if ( ! empty( $bodies ) ) {
			$query_args['meta_query'][] = array(
				'key'     => KMAE_Vehicle::META_BODY,
				'value'   => KMAE_Vehicle::expand_body_query( $bodies ),
				'compare' => 'IN',
			);
		}

		if ( is_user_logged_in() ) {
			$query_args['author__not_in'] = array( get_current_user_id() );
			$contacted                    = $args['exclude_contacted'] ? KMAE_Browse::get_contacted_listing_ids( get_current_user_id() ) : array();
			if ( $contacted ) {
				$query_args['post__not_in'] = $contacted;
			}
		}

		$listings = get_posts( $query_args );

		usort(
			$listings,
			function ( $a, $b ) {
				return strtotime( $b->post_date ) - strtotime( $a->post_date );
			}
		);

		if ( is_user_logged_in() ) {
			$listings = KMAE_Browse::personalize_listings( $listings, get_current_user_id() );
		}

		return $listings;
	}

	/**
	 * Popular listings for landing page (newest first).
	 *
	 * @param int $limit Max items.
	 * @return array<int, WP_Post>
	 */
	public static function get_popular_listings( $limit = 4 ) {
		return self::get_listings(
			array(
				'per_page' => absint( $limit ),
				'mode'     => 'grid',
			)
		);
	}

	/**
	 * Similar active listings for a no-longer-available one: same body type
	 * first, topped up with the newest listings.
	 *
	 * @param int $listing_id Listing that is gone (excluded).
	 * @param int $limit      Max items.
	 * @return array<int, WP_Post>
	 */
	public static function get_similar_listings( $listing_id, $limit = 4 ) {
		$listing_id = (int) $listing_id;
		$limit      = absint( $limit );
		$body       = sanitize_key( (string) get_post_meta( $listing_id, KMAE_Vehicle::META_BODY, true ) );
		$picked     = array();
		$sources    = array();
		if ( '' !== $body ) {
			$sources[] = self::get_listings( array( 'per_page' => $limit + 1, 'body' => $body ) );
		}
		$sources[] = self::get_listings( array( 'per_page' => $limit * 2 + 1 ) );
		foreach ( $sources as $listings ) {
			foreach ( $listings as $listing ) {
				if ( count( $picked ) >= $limit ) {
					break 2;
				}
				if ( (int) $listing->ID !== $listing_id ) {
					$picked[ (int) $listing->ID ] = $listing;
				}
			}
		}
		return array_values( $picked );
	}

	/**
	 * Featured image URL, or first gallery image as fallback.
	 */
	public static function get_listing_thumbnail_url( $listing_id, $size = 'medium' ) {
		$thumb_id = get_post_thumbnail_id( $listing_id );
		if ( $thumb_id ) {
			$url = KMAE_Images::get_url( $thumb_id, $size );
			if ( $url ) {
				return $url;
			}
		}

		$gallery_ids = self::get_gallery_ids( $listing_id );
		if ( ! empty( $gallery_ids ) ) {
			$url = KMAE_Images::get_url( $gallery_ids[0], $size );
			if ( $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * A kártyaképhez tartozó attachment azonosítója — ugyanaz a visszaesési
	 * sorrend, mint a `get_listing_thumbnail_url()`-nél. A reszponzív
	 * `srcset` előállításához kell, ahhoz ugyanis nem az URL, hanem az
	 * azonosító szükséges.
	 *
	 * @param int $listing_id Hirdetés azonosítója.
	 * @return int 0, ha nincs kép.
	 */
	public static function get_listing_thumbnail_id( $listing_id ) {
		$thumb_id = (int) get_post_thumbnail_id( $listing_id );
		if ( $thumb_id ) {
			return $thumb_id;
		}

		$gallery_ids = self::get_gallery_ids( $listing_id );
		return ! empty( $gallery_ids ) ? (int) $gallery_ids[0] : 0;
	}

	/**
	 * Full-size URL for lightbox.
	 */
	public static function get_listing_full_image_url( $listing_id ) {
		return self::get_listing_thumbnail_url( $listing_id, 'full' );
	}

	public static function get_gallery_ids( $listing_id ) {
		$listing_id = (int) $listing_id;
		$gallery    = get_post_meta( $listing_id, '_kmae_gallery', true );
		$stored     = is_array( $gallery ) ? array_map( 'absint', $gallery ) : array();
		$valid      = array();

		foreach ( $stored as $attachment_id ) {
			if ( $attachment_id && self::gallery_attachment_exists( $attachment_id ) ) {
				$valid[] = $attachment_id;
			}
		}
		$valid = array_values( array_unique( $valid ) );

		if ( is_array( $gallery ) && $valid !== $stored ) {
			if ( $valid ) {
				update_post_meta( $listing_id, '_kmae_gallery', $valid );
				set_post_thumbnail( $listing_id, $valid[0] );
			} else {
				delete_post_meta( $listing_id, '_kmae_gallery' );
				delete_post_thumbnail( $listing_id );
			}
		} elseif ( $valid && (int) get_post_thumbnail_id( $listing_id ) !== (int) $valid[0] ) {
			set_post_thumbnail( $listing_id, $valid[0] );
		}

		if ( $valid ) {
			return $valid;
		}

		$thumb = (int) get_post_thumbnail_id( $listing_id );
		if ( $thumb && self::gallery_attachment_exists( $thumb ) ) {
			return array( $thumb );
		}

		if ( $thumb ) {
			delete_post_thumbnail( $listing_id );
		}

		return array();
	}

	/**
	 * A galéria csak olyan képet tart meg, amelynek a fájlja még a szerveren van.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private static function gallery_attachment_exists( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		return '' !== KMAE_Images::get_url( $attachment_id, 'full' );
	}
}
