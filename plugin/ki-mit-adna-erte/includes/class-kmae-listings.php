<?php
/**
 * Delete listing and related data.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Listings {

	/* Elfogadott ajánlat nélkül ennyi nap után lejár az aktív hirdetés. */
	const EXPIRE_DAYS = 60;

	public static function init() {
		add_action( 'kmae_purge_archived_listings', array( __CLASS__, 'purge_expired_archived' ) );
		add_action( 'kmae_expire_listings', array( __CLASS__, 'expire_listings' ) );
		KMAE_Activator::schedule_cron();
	}

	/**
	 * Lifecycle info for archived listings (null if not archived).
	 *
	 * @return array{phase:string,days_until_reactivate?:int,days_until_delete:int}|null
	 */
	public static function get_archive_lifecycle( $listing_id ) {
		if ( KMAE_Meta::STATUS_ARCHIVED !== KMAE_Meta::get_status( $listing_id ) ) {
			return null;
		}

		$archived_at = get_post_meta( $listing_id, '_kmae_archived_at', true );
		if ( ! $archived_at ) {
			return array(
				'phase'               => 'locked',
				'days_until_reactivate' => KMAE_ARCHIVE_LOCK_DAYS,
				'days_until_delete'   => KMAE_ARCHIVE_TOTAL_DAYS,
			);
		}

		$archived_ts  = strtotime( $archived_at );
		$now          = current_time( 'timestamp' );
		$elapsed      = max( 0, (int) floor( ( $now - $archived_ts ) / DAY_IN_SECONDS ) );
		$until_delete = max( 0, KMAE_ARCHIVE_TOTAL_DAYS - $elapsed );

		if ( $elapsed < KMAE_ARCHIVE_LOCK_DAYS ) {
			return array(
				'phase'                 => 'locked',
				'days_until_reactivate' => KMAE_ARCHIVE_LOCK_DAYS - $elapsed,
				'days_until_delete'     => $until_delete,
			);
		}

		if ( $elapsed < KMAE_ARCHIVE_TOTAL_DAYS ) {
			return array(
				'phase'             => 'reactivate',
				'days_until_delete' => $until_delete,
			);
		}

		return array(
			'phase'             => 'expired',
			'days_until_delete' => 0,
		);
	}

	public static function is_archive_locked( $listing_id ) {
		$lifecycle = self::get_archive_lifecycle( $listing_id );
		return $lifecycle && 'locked' === $lifecycle['phase'];
	}

	public static function can_reactivate_listing( $listing_id ) {
		$lifecycle = self::get_archive_lifecycle( $listing_id );
		return $lifecycle && 'reactivate' === $lifecycle['phase'];
	}

	public static function can_edit_listing( $listing_id ) {
		return ! self::is_archive_locked( $listing_id ) && ! self::can_reactivate_listing( $listing_id )
			&& KMAE_Meta::STATUS_ARCHIVED !== KMAE_Meta::get_status( $listing_id );
	}

	/**
	 * Az eladó bármikor törölheti a hirdetését (a tulajdon-ellenőrzés a
	 * delete_listing()-ben van).
	 */
	public static function can_delete_listing( $listing_id ) {
		$post = get_post( $listing_id );
		return $post && 'kmae_listing' === $post->post_type;
	}

	/**
	 * A 60 napos óra indulása: az utolsó közzététel, újraaktiválás vagy hosszabbítás.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int Timestamp (current_time skála).
	 */
	public static function expiry_clock_start( $listing_id ) {
		$start = 0;
		foreach ( array( '_kmae_published_at', '_kmae_reactivated_at', '_kmae_renewed_at' ) as $key ) {
			$value = get_post_meta( $listing_id, $key, true );
			$ts    = $value ? strtotime( (string) $value ) : 0;
			if ( $ts > $start ) {
				$start = $ts;
			}
		}
		if ( ! $start ) {
			$post  = get_post( $listing_id );
			$start = $post ? strtotime( $post->post_date ) : 0;
		}
		return (int) $start;
	}

	/**
	 * Aktív hirdetésnél: hány nap van hátra a lejáratig (null, ha nem aktív).
	 *
	 * @param int $listing_id Listing ID.
	 * @return int|null
	 */
	public static function days_until_expiry( $listing_id ) {
		if ( KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $listing_id ) ) {
			return null;
		}
		$expires = self::expiry_clock_start( $listing_id ) + self::EXPIRE_DAYS * DAY_IN_SECONDS;
		return max( 0, (int) ceil( ( $expires - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
	}

	/**
	 * Óránkénti cron: az aktív hirdetés 60 nap után lejár, ha nincs rajta
	 * elfogadott („Foglalt”) ajánlat. Ilyenkor kikerül a böngészésből, és az
	 * eladó e-mailt kap a hosszabbításhoz.
	 */
	public static function expire_listings() {
		foreach ( self::due_for_expiry() as $listing_id ) {
			update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_EXPIRED );
			update_post_meta( $listing_id, '_kmae_expired_at', current_time( 'mysql' ) );
			KMAE_Notifications::notify_listing_expired( $listing_id );
		}
	}

	/**
	 * Azok az aktív hirdetések, amelyeknek a 60 napja letelt, és nincs rajtuk
	 * elfogadott ajánlat — ezeket kell lejártatni.
	 *
	 * @return int[]
	 */
	public static function due_for_expiry() {
		$now    = current_time( 'timestamp' );
		$cutoff = $now - self::EXPIRE_DAYS * DAY_IN_SECONDS;

		/* Előszűrés: a létrehozás ideje mindig az óra indulása előtt van. */
		$ids = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'date_query'     => array(
					array(
						'column'    => 'post_date',
						'before'    => gmdate( 'Y-m-d H:i:s', $cutoff ),
						'inclusive' => true,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => '_kmae_status',
						'value' => KMAE_Meta::STATUS_ACTIVE,
					),
				),
			)
		);
		if ( empty( $ids ) ) {
			return array();
		}
		update_meta_cache( 'post', $ids );

		$due = array();
		foreach ( $ids as $listing_id ) {
			$listing_id = (int) $listing_id;
			if ( self::expiry_clock_start( $listing_id ) > $cutoff ) {
				continue;
			}
			if ( class_exists( 'KMAE_Chat_Offers' ) && KMAE_Chat_Offers::get_accepted_for_listing( $listing_id ) ) {
				continue;
			}
			$due[] = $listing_id;
		}
		return $due;
	}

	/**
	 * Lejárt hirdetés hosszabbítása: újra aktív, az óra újraindul (60 nap).
	 *
	 * @param int $listing_id Listing ID.
	 * @param int $user_id    Acting user.
	 * @return true|WP_Error
	 */
	public static function renew_listing( $listing_id, $user_id ) {
		if ( ! self::user_owns_listing( $listing_id, $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez.', 'ki-mit-adna-erte' ) );
		}
		if ( KMAE_Meta::STATUS_EXPIRED !== KMAE_Meta::get_status( $listing_id ) ) {
			return new WP_Error( 'not_allowed', __( 'Csak lejárt hirdetés hosszabbítható.', 'ki-mit-adna-erte' ) );
		}

		update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
		update_post_meta( $listing_id, '_kmae_renewed_at', current_time( 'mysql' ) );
		delete_post_meta( $listing_id, '_kmae_expired_at' );

		return true;
	}

	public static function reactivate_listing( $listing_id, $user_id ) {
		if ( ! self::user_owns_listing( $listing_id, $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez.', 'ki-mit-adna-erte' ) );
		}

		if ( ! self::can_reactivate_listing( $listing_id ) ) {
			return new WP_Error( 'not_allowed', __( 'Ez a hirdetés még nem aktiválható újra, vagy már lejárt az idő.', 'ki-mit-adna-erte' ) );
		}

		update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
		delete_post_meta( $listing_id, '_kmae_archived_at' );
		update_post_meta( $listing_id, '_kmae_reactivated_at', current_time( 'mysql' ) );

		do_action( 'kmae_listing_published', $listing_id );

		return true;
	}

	/**
	 * Admin „Újraindítás tiszta lappal”: a hirdetés (adatok, fotók) marad, minden
	 * más, ami rá épült, törlődik — chatek és képeik, ajánlatok (eladás, Foglalt),
	 * bejelentések, kedvencek / továbblapozások, lejárat/archív órák. A hirdetés
	 * újként, aktívan indul (levél nélkül, mint az admin „Aktiválás”).
	 *
	 * @param int $listing_id Listing ID.
	 * @return true|WP_Error
	 */
	public static function reset_listing( $listing_id ) {
		global $wpdb;

		$listing_id = (int) $listing_id;
		$listing    = get_post( $listing_id );
		if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
			return new WP_Error( 'invalid', __( 'Érvénytelen hirdetés.', 'ki-mit-adna-erte' ) );
		}

		$conversations_table = KMAE_Database::table( 'conversations' );
		$messages_table      = KMAE_Database::table( 'conversation_messages' );

		$conversations = $wpdb->get_results( $wpdb->prepare( "SELECT id, buyer_id, seller_id FROM {$conversations_table} WHERE listing_id = %d", $listing_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$parties       = array( (int) $listing->post_author );
		foreach ( (array) $conversations as $conversation ) {
			$parties[] = (int) $conversation->buyer_id;
			$parties[] = (int) $conversation->seller_id;

			$attachment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT attachment_id FROM {$messages_table} WHERE conversation_id = %d AND attachment_id IS NOT NULL AND attachment_id > 0", (int) $conversation->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $attachment_ids as $attachment_id ) {
				KMAE_Images::delete_attachment( (int) $attachment_id );
			}
			$wpdb->delete( $messages_table, array( 'conversation_id' => (int) $conversation->id ), array( '%d' ) );
		}
		$wpdb->delete( $conversations_table, array( 'listing_id' => $listing_id ), array( '%d' ) );
		$wpdb->delete( KMAE_Database::table( 'chat_offers' ), array( 'listing_id' => $listing_id ), array( '%d' ) );
		$wpdb->delete( KMAE_Database::table( 'reports' ), array( 'listing_id' => $listing_id ), array( '%d' ) );

		/* Kedvencek, friss-kedvenc jelvény, „továbblapozott” és „már megkeresett” lista minden felhasználónál. */
		$list_keys = array( KMAE_Browse::FAVORITE_META, '_kmae_favorite_unseen', KMAE_Browse::PASSED_META, KMAE_Browse::CONTACTED_META );
		$rows      = $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN ('" . implode( "','", array_map( 'esc_sql', $list_keys ) ) . "')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $rows as $row ) {
			$ids = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $ids ) || ! in_array( $listing_id, array_map( 'intval', $ids ), true ) ) {
				continue;
			}
			$left = array_values(
				array_filter(
					$ids,
					static function ( $id ) use ( $listing_id ) {
						return (int) $id !== $listing_id;
					}
				)
			);
			if ( empty( $left ) ) {
				delete_user_meta( (int) $row->user_id, $row->meta_key );
			} else {
				update_user_meta( (int) $row->user_id, $row->meta_key, $left );
			}
			$parties[] = (int) $row->user_id;
		}

		foreach ( array( '_kmae_archived_at', '_kmae_expired_at', '_kmae_reactivated_at', '_kmae_renewed_at' ) as $key ) {
			delete_post_meta( $listing_id, $key );
		}
		$now = current_time( 'mysql' );
		update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
		update_post_meta( $listing_id, '_kmae_published_at', $now );

		/* Újként a lista elején (közvetlen írás: ne fusson a save_post lánc). */
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => $now,
				'post_date_gmt' => get_gmt_from_date( $now ),
			),
			array( 'ID' => $listing_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $listing_id );

		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( array_values( array_unique( array_filter( $parties ) ) ) );
		}

		return true;
	}

	/**
	 * Daily cron: permanently delete archived listings past KMAE_ARCHIVE_TOTAL_DAYS
	 * (clock: `_kmae_archived_at`). Same cleanup as admin „Végleges törlés”.
	 */
	public static function purge_expired_archived() {
		$cutoff = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( KMAE_ARCHIVE_TOTAL_DAYS * DAY_IN_SECONDS ) );

		$query = new WP_Query(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_kmae_status',
						'value' => KMAE_Meta::STATUS_ARCHIVED,
					),
					array(
						'key'     => '_kmae_archived_at',
						'value'   => $cutoff,
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			)
		);

		foreach ( $query->posts as $listing_id ) {
			self::force_delete_listing( (int) $listing_id, 0 );
		}
	}

	public static function user_owns_listing( $listing_id, $user_id ) {
		$listing = get_post( $listing_id );
		return $listing && 'kmae_listing' === $listing->post_type && (int) $listing->post_author === (int) $user_id;
	}

	/**
	 * Activate listing when required vehicle photos are complete.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool True if activated (or already active with photos).
	 */
	public static function activate_if_photos_ready( $listing_id ) {
		if ( ! KMAE_Photo_Motor::is_enabled() ) {
			return false;
		}
		if ( KMAE_Photo_Motor::count_required_filled( $listing_id ) < KMAE_Photo_Motor::required_count() ) {
			return false;
		}

		$was = KMAE_Meta::get_status( $listing_id );
		update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
		if ( ! get_post_meta( $listing_id, '_kmae_published_at', true ) ) {
			update_post_meta( $listing_id, '_kmae_published_at', current_time( 'mysql' ) );
		}

		if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $was ) {
			do_action( 'kmae_listing_published', $listing_id );
		}

		return true;
	}

	/**
	 * Az állapot követi a fotókat (általános feltöltő módban):
	 * - aktív hirdetésből kikerül az utolsó kép → „Fotók feltöltésére vár”: nem látszik a
	 *   böngészésben és a keresőben, amíg nincs legalább egy fotója;
	 * - ilyenkor feltöltött új kép → visszaáll aktívra, és újra megjelenik.
	 *
	 * @param int $listing_id Listing ID.
	 * @return string '' | 'awaiting' | 'active' — mi változott.
	 */
	public static function sync_status_with_photos( $listing_id ) {
		$listing_id = (int) $listing_id;
		$status     = KMAE_Meta::get_status( $listing_id );
		$count      = count( KMAE_Query::get_gallery_ids( $listing_id ) );

		if ( KMAE_Meta::STATUS_ACTIVE === $status && 0 === $count ) {
			update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_AWAITING_PHOTOS );
			update_post_meta( $listing_id, '_kmae_photos_removed', 1 );
			return 'awaiting';
		}

		if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status && $count > 0
			&& ( get_post_meta( $listing_id, '_kmae_photos_removed', true ) || ! KMAE_Photo_Motor::is_enabled() ) ) {
			update_post_meta( $listing_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
			delete_post_meta( $listing_id, '_kmae_photos_removed' );
			if ( ! get_post_meta( $listing_id, '_kmae_published_at', true ) ) {
				/* Első közzététel: ilyenkor megy ki a „megjelent a hirdetésed” értesítés is.
				   Egy már korábban élő hirdetés visszaállásakor nem küldünk újat. */
				update_post_meta( $listing_id, '_kmae_published_at', current_time( 'mysql' ) );
				do_action( 'kmae_listing_published', $listing_id );
			}
			return 'active';
		}

		return '';
	}

	public static function save_meta_from_post( $post_id, $is_new = false, $initial_status = null ) {
		$county    = isset( $_POST['kmae_county'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_county'] ) ) : '';
		$city      = isset( $_POST['kmae_city'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_city'] ) ) : '';
		$condition = isset( $_POST['kmae_condition'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_condition'] ) ) : '';

		if ( KMAE_Auth::is_company( get_current_user_id() ) ) {
			$company_location = KMAE_Company::apply_listing_location( $post_id, get_current_user_id() );
			if ( is_wp_error( $company_location ) ) {
				return $company_location;
			}
		} elseif ( KMAE_Counties::validate_listing_location( $county, $city ) ) {
			KMAE_Counties::save_listing_location( $post_id, $county, $city );
		}

		update_post_meta( $post_id, '_kmae_condition', $condition );

		if ( $is_new ) {
			$status = in_array( $initial_status, array( KMAE_Meta::STATUS_ACTIVE, KMAE_Meta::STATUS_AWAITING_PHOTOS ), true )
				? $initial_status
				: KMAE_Meta::STATUS_ACTIVE;
			update_post_meta( $post_id, '_kmae_status', $status );
			if ( KMAE_Meta::STATUS_ACTIVE === $status ) {
				update_post_meta( $post_id, '_kmae_published_at', current_time( 'mysql' ) );
			}
		}

		KMAE_Vehicle::save_from_post( $post_id );
		KMAE_Registration_Scan::save_ids_from_post( $post_id );

		update_post_meta( $post_id, '_kmae_offer_types', KMAE_Taxonomies::default_offer_types() );
		delete_post_meta( $post_id, '_kmae_trade_in_categories' );

		$seller_save = KMAE_Seller::save_from_post( $post_id, $is_new );
		if ( is_wp_error( $seller_save ) ) {
			return $seller_save;
		}

		$category_id = isset( $_POST['kmae_category'] ) ? absint( $_POST['kmae_category'] ) : 0;
		if ( $category_id ) {
			wp_set_object_terms( $post_id, array( $category_id ), 'kmae_category' );
		} else {
			self::ensure_launch_category( $post_id );
		}

		return true;
	}

	/**
	 * Vehicle launch: every listing must belong to the launch category for browse queries.
	 *
	 * @param int $post_id Listing ID.
	 */
	public static function ensure_launch_category( $post_id ) {
		if ( ! KMAE_Vehicle::is_vehicle_launch() ) {
			return;
		}

		$launch_cat_id = KMAE_Launch::get_launch_category_id();
		if ( $launch_cat_id <= 0 ) {
			return;
		}

		$terms = wp_get_object_terms( (int) $post_id, 'kmae_category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			wp_set_object_terms( (int) $post_id, array( $launch_cat_id ), 'kmae_category' );
		}
	}

	public static function handle_image_uploads( $post_id, $existing_gallery = array() ) {
		if ( KMAE_Photo_Motor::is_enabled() ) {
			return KMAE_Photo_Motor::handle_slot_uploads( $post_id, $existing_gallery );
		}

		if ( empty( $_FILES['kmae_images']['name'] ) ) {
			return $existing_gallery;
		}

		// Single or multiple file field.
		if ( ! is_array( $_FILES['kmae_images']['name'] ) ) {
			$_FILES['kmae_images'] = array(
				'name'     => array( $_FILES['kmae_images']['name'] ),
				'type'     => array( $_FILES['kmae_images']['type'] ),
				'tmp_name' => array( $_FILES['kmae_images']['tmp_name'] ),
				'error'    => array( $_FILES['kmae_images']['error'] ),
				'size'     => array( $_FILES['kmae_images']['size'] ),
			);
		}

		if ( empty( $_FILES['kmae_images']['name'][0] ) ) {
			return $existing_gallery;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$gallery_ids = $existing_gallery;
		$new_ids     = array();
		$files       = $_FILES['kmae_images'];
		$count       = count( $files['name'] );
		$has_thumb   = has_post_thumbnail( $post_id );
		/* photo-motor.js: a képek sorrendjében az autó doboza ([x0,y0,x1,y1] vagy null). */
		$subjects = isset( $_POST['kmae_photo_subjects'] ) ? json_decode( wp_unslash( (string) $_POST['kmae_photo_subjects'] ), true ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a hívó ellenőrizte a nonce-t; a parse_subject számokra szűr.
		$subjects = is_array( $subjects ) ? $subjects : array();

		for ( $i = 0; $i < $count && count( $gallery_ids ) < 12; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}
			if ( ! empty( $files['error'][ $i ] ) && UPLOAD_ERR_OK !== (int) $files['error'][ $i ] ) {
				continue;
			}

			/* Gépi képjavítás a feltöltés ELŐTT, az ideiglenes fájlon — ugyanaz,
			   amit a varázsló fotó-lépése is csinál. Itt nem utasítunk el
			   semmit: ez a szabadon bővíthető galéria, nem a kötelező nézetek.
			   Ha a kép menthetetlen, egyszerűen javítás nélkül megy tovább. */
			$applied    = array();
			$quality    = null;
			$correction = null;
			/* PDF-ből készült dokumentumoldal (photo-motor.js, „albira-dokumentum-” név):
			   a fotójavítás (fényerő, kontraszt) egy fehér papíron csak rontana. */
			$is_document = 0 === strpos( (string) $files['name'][ $i ], 'albira-dokumentum-' );
			if ( class_exists( 'KMAE_Photo_Motor' ) && ! $is_document ) {
				$quality = KMAE_Photo_Motor::analyze_file( $files['tmp_name'][ $i ] );
				if ( ! is_wp_error( $quality ) && ! empty( $quality['fixes'] ) ) {
					$correction = KMAE_Photo_Motor::autocorrect_file(
						$files['tmp_name'][ $i ],
						$quality,
						(string) $files['type'][ $i ]
					);
					$applied    = $correction['applied'];
				}
			}

			$_FILES['kmae_single_image'] = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);

			$attachment_id = media_handle_upload( 'kmae_single_image', $post_id );
			if ( is_wp_error( $attachment_id ) ) {
				continue;
			}

			if ( ! empty( $applied ) ) {
				update_post_meta( $attachment_id, KMAE_Photo_Motor::ATTACH_AUTOFIX_META, $applied );
			}
			if ( ! $is_document && isset( $subjects[ $i ] ) ) {
				KMAE_Images::save_subject( $attachment_id, $subjects[ $i ] );
			}
			/* Nemcsak a javított képekről: a rendben lévőkről és a
			   figyelmeztetettekről is kell adat, különben nincs mihez
			   hangolni a küszöböket. */
			if ( ! $is_document && ! is_wp_error( $quality ) ) {
				update_post_meta(
					$attachment_id,
					KMAE_Photo_Motor::ATTACH_ANALYSIS_META,
					KMAE_Photo_Motor::analysis_record( $quality, $correction )
				);
			}

			KMAE_Images::process_attachment( $attachment_id );

			$gallery_ids[] = $attachment_id;
			$new_ids[]     = $attachment_id;
			if ( ! $has_thumb ) {
				set_post_thumbnail( $post_id, $attachment_id );
				$has_thumb = true;
			}
		}

		/* Az új képek a meglévők után kerülnek; a borító marad, kivéve ha a
		   felhasználó egy új képet jelölt borítónak (kmae_cover_new = 1 → az
		   első új kép kerül előre). */
		if ( ! empty( $new_ids ) && ! empty( $_POST['kmae_cover_new'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$cover_id    = $new_ids[0];
			$gallery_ids = array_values( array_diff( $gallery_ids, array( $cover_id ) ) );
			array_unshift( $gallery_ids, $cover_id );
		}

		if ( ! empty( $gallery_ids ) ) {
			update_post_meta( $post_id, '_kmae_gallery', $gallery_ids );
			set_post_thumbnail( $post_id, $gallery_ids[0] );
		}

		return $gallery_ids;
	}

	/**
	 * Apply reorder / delete from edit form before new uploads.
	 */
	public static function apply_gallery_edits( $post_id, $existing_gallery ) {
		$existing_gallery = array_map( 'absint', (array) $existing_gallery );
		$allowed          = array_flip( $existing_gallery );

		$order  = isset( $_POST['kmae_gallery_order'] ) ? array_map( 'absint', wp_unslash( (array) $_POST['kmae_gallery_order'] ) ) : $existing_gallery;
		$remove = isset( $_POST['kmae_remove_images'] ) ? array_map( 'absint', wp_unslash( (array) $_POST['kmae_remove_images'] ) ) : array();

		foreach ( $remove as $attachment_id ) {
			if ( ! $attachment_id || ! isset( $allowed[ $attachment_id ] ) ) {
				continue;
			}
			if ( ! self::attachment_belongs_to_listing( $attachment_id, $post_id ) ) {
				continue;
			}
			KMAE_Images::delete_attachment( $attachment_id );
			unset( $allowed[ $attachment_id ] );
		}

		$gallery_ids = array();
		foreach ( $order as $attachment_id ) {
			if ( ! $attachment_id || ! isset( $allowed[ $attachment_id ] ) ) {
				continue;
			}
			if ( in_array( $attachment_id, $remove, true ) ) {
				continue;
			}
			if ( ! self::attachment_belongs_to_listing( $attachment_id, $post_id ) ) {
				continue;
			}
			$gallery_ids[] = $attachment_id;
		}

		if ( ! empty( $gallery_ids ) ) {
			update_post_meta( $post_id, '_kmae_gallery', $gallery_ids );
			set_post_thumbnail( $post_id, $gallery_ids[0] );
		} else {
			delete_post_meta( $post_id, '_kmae_gallery' );
			delete_post_thumbnail( $post_id );
		}

		return $gallery_ids;
	}

	private static function attachment_belongs_to_listing( $attachment_id, $post_id ) {
		$attachment = get_post( $attachment_id );
		return $attachment
			&& 'attachment' === $attachment->post_type
			&& (int) $attachment->post_parent === (int) $post_id;
	}

	private static function collect_listing_attachment_ids( $listing_id ) {
		$ids = array();

		$thumb_id = (int) get_post_thumbnail_id( $listing_id );
		if ( $thumb_id ) {
			$ids[] = $thumb_id;
		}

		$gallery = get_post_meta( $listing_id, '_kmae_gallery', true );
		if ( is_array( $gallery ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $gallery ) );
		}

		$slots = get_post_meta( $listing_id, KMAE_Photo_Motor::META_SLOTS, true );
		if ( is_array( $slots ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $slots ) );
		}

		$child_ids = get_children(
			array(
				'post_parent' => $listing_id,
				'post_type'   => 'attachment',
				'fields'      => 'ids',
				'numberposts' => -1,
			)
		);
		if ( ! empty( $child_ids ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $child_ids ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	private static function delete_listing_attachments( $listing_id ) {
		foreach ( self::collect_listing_attachment_ids( $listing_id ) as $attachment_id ) {
			if ( ! self::attachment_belongs_to_listing( $attachment_id, $listing_id ) ) {
				continue;
			}
			if ( self::attachment_used_by_other_listing( $attachment_id, $listing_id ) ) {
				continue;
			}
			KMAE_Images::delete_attachment( $attachment_id );
		}
	}

	/**
	 * Ugyanaz a fájl több hirdetés galériájában is szerepelhet.
	 * Ilyenkor a hirdetés törlése nem viheti magával a képet.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $listing_id    A törlendő hirdetés.
	 */
	private static function attachment_used_by_other_listing( $attachment_id, $listing_id ) {
		global $wpdb;

		$attachment_id = (int) $attachment_id;
		$listing_id    = (int) $listing_id;
		if ( ! $attachment_id ) {
			return false;
		}

		$thumb_owner = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s AND post_id != %d LIMIT 1",
				(string) $attachment_id,
				$listing_id
			)
		);
		if ( $thumb_owner ) {
			return true;
		}

		$needle = 'i:' . $attachment_id . ';';
		$shared = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_kmae_gallery' AND post_id != %d AND meta_value LIKE %s LIMIT 1",
				$listing_id,
				'%' . $wpdb->esc_like( $needle ) . '%'
			)
		);

		return $shared > 0;
	}

	public static function delete_listing( $listing_id, $user_id ) {
		if ( ! self::user_owns_listing( $listing_id, $user_id ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez.', 'ki-mit-adna-erte' ) );
		}

		return self::force_delete_listing( $listing_id, (int) $user_id );
	}

	/**
	 * Delete listing and related data (admin or owner after permission check).
	 */
	public static function force_delete_listing( $listing_id, $actor_id = 0 ) {
		global $wpdb;

		$listing = get_post( $listing_id );
		if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
			return new WP_Error( 'invalid', __( 'Érvénytelen hirdetés.', 'ki-mit-adna-erte' ) );
		}

		// Regret mail first; mute chat mails while cleanup runs.
		KMAE_Notifications::silence_offer_pipeline( true );
		try {
		/* Félbehagyott hirdetés (piszkozat): nem volt közzétéve, nincs érdeklődője —
		   a törléséről nem megy levél („Hirdetésed törölve” sem). */
		$is_draft = KMAE_Meta::STATUS_DRAFT === KMAE_Meta::get_status( $listing_id ) || 'draft' === $listing->post_status;
		if ( ! $is_draft ) {
			KMAE_Notifications::on_listing_deleted( $listing_id, $listing, (int) $actor_id );
		}
		/* Foglalt hirdetés: az elfogadott vevő rendszerüzenetet kap (olvasatlan marad),
		   minden nyitott ajánlat lezárul — a törlés nem számít eladásnak. */
		$keep_unread = array();
		if ( class_exists( 'KMAE_Chat_Offers' ) ) {
			$accepted = KMAE_Chat_Offers::get_accepted_for_listing( (int) $listing_id );
			if ( $accepted ) {
				$keep_unread[] = (int) $accepted->buyer_id;
			}
			KMAE_Chat_Offers::on_listing_deleted( (int) $listing_id );
		}
		if ( class_exists( 'KMAE_Conversations' ) ) {
			KMAE_Conversations::withdraw_alerts_for_listing( (int) $listing_id, $keep_unread );
		}
		self::delete_listing_attachments( $listing_id );

			$wpdb->delete( KMAE_Database::table( 'private_data' ), array( 'listing_id' => $listing_id ), array( '%d' ) );

			$deleted = wp_delete_post( $listing_id, true );
			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', __( 'A hirdetés törlése sikertelen.', 'ki-mit-adna-erte' ) );
			}

			return true;
		} finally {
			KMAE_Notifications::silence_offer_pipeline( false );
		}
	}
}
