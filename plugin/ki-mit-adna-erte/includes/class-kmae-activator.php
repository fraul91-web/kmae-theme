<?php
/**
 * Plugin activation: database tables, default categories, rewrite rules.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Activator {
	/** Set once the old offer/deal engine data has been removed. */
	const OFFER_SYSTEM_REMOVED_OPTION = 'kmae_offer_system_removed';

	public static function activate() {
		self::create_tables();
		self::remove_offer_system();
		self::migrate_price_modes();

		// CPT + taxonomy must be registered BEFORE seeding categories.
		KMAE_Post_Types::register_post_type();
		KMAE_Taxonomies::register_taxonomy();
		KMAE_Taxonomies::seed_categories();

		self::migrate_browse_visibility();

		flush_rewrite_rules();
		KMAE_Pages::ensure_pages();
		KMAE_Auth::ensure_subscriber_caps();
		self::schedule_cron();
	}

	public static function schedule_cron() {
		if ( ! wp_next_scheduled( 'kmae_purge_archived_listings' ) ) {
			wp_schedule_event( time(), 'daily', 'kmae_purge_archived_listings' );
		}
		if ( ! wp_next_scheduled( 'kmae_purge_chat_images' ) ) {
			wp_schedule_event( time(), 'hourly', 'kmae_purge_chat_images' );
		}
		if ( ! wp_next_scheduled( 'kmae_remind_unfinished_listings' ) ) {
			wp_schedule_event( time(), 'hourly', 'kmae_remind_unfinished_listings' );
		}
		if ( ! wp_next_scheduled( 'kmae_finalize_chat_sales' ) ) {
			wp_schedule_event( time(), 'hourly', 'kmae_finalize_chat_sales' );
		}
		if ( ! wp_next_scheduled( 'kmae_expire_listings' ) ) {
			wp_schedule_event( time(), 'hourly', 'kmae_expire_listings' );
		}
	}

	private static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_private_data = "CREATE TABLE {$prefix}kmae_private_data (
			listing_id bigint(20) unsigned NOT NULL,
			minimum_cash decimal(12,2) NOT NULL,
			currency varchar(3) NOT NULL DEFAULT 'HUF',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (listing_id)
		) $charset;";

		dbDelta( $sql_private_data );

		$sql_profiles = "CREATE TABLE {$prefix}kmae_user_profiles (
			user_id bigint(20) unsigned NOT NULL,
			phone varchar(32) NOT NULL DEFAULT '',
			account_type varchar(20) NOT NULL DEFAULT 'private',
			email_verified tinyint(1) NOT NULL DEFAULT 0,
			phone_verified tinyint(1) NOT NULL DEFAULT 0,
			email_news tinyint(1) NOT NULL DEFAULT 0,
			email_offers tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (user_id),
			KEY account_type (account_type)
		) $charset;";

		dbDelta( $sql_profiles );

		$sql_conversations = "CREATE TABLE {$prefix}kmae_conversations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			listing_id bigint(20) unsigned NOT NULL,
			buyer_id bigint(20) unsigned NOT NULL,
			seller_id bigint(20) unsigned NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'open',
			listing_title varchar(255) NOT NULL DEFAULT '',
			legacy_offer_id bigint(20) unsigned DEFAULT NULL,
			legacy_source_key varchar(191) DEFAULT NULL,
			last_message_at datetime DEFAULT NULL,
			message_count bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY listing_buyer (listing_id, buyer_id),
			UNIQUE KEY legacy_offer (legacy_offer_id),
			UNIQUE KEY legacy_source (legacy_source_key),
			KEY buyer_id (buyer_id),
			KEY seller_id (seller_id),
			KEY last_message_at (last_message_at)
		) ENGINE=InnoDB $charset;";

		$sql_conversation_messages = "CREATE TABLE {$prefix}kmae_conversation_messages (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			message_text longtext NOT NULL,
			attachment_id bigint(20) unsigned DEFAULT NULL,
			image_expires_at datetime DEFAULT NULL,
			idempotency_key varchar(160) NOT NULL,
			legacy_source_key varchar(191) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY conversation_idempotency (conversation_id, idempotency_key),
			UNIQUE KEY legacy_source (legacy_source_key),
			KEY conversation_created (conversation_id, created_at),
			KEY sender_id (sender_id),
			KEY image_expires_at (image_expires_at)
		) ENGINE=InnoDB $charset;";

		$sql_chat_offers = "CREATE TABLE {$prefix}kmae_chat_offers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			listing_id bigint(20) unsigned NOT NULL,
			conversation_id bigint(20) unsigned NOT NULL,
			message_id bigint(20) unsigned NOT NULL,
			buyer_id bigint(20) unsigned NOT NULL,
			seller_id bigint(20) unsigned NOT NULL,
			amount bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'live',
			accepted_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY message_id (message_id),
			KEY listing_status (listing_id, status),
			KEY conversation_id (conversation_id),
			KEY buyer_listing (buyer_id, listing_id)
		) ENGINE=InnoDB $charset;";

		/* Bejelentések (hirdetés / chat). A beszélgetés és a hirdetés címe
		   megmarad bizonyítéknak akkor is, ha a hirdetést törlik. */
		$sql_reports = "CREATE TABLE {$prefix}kmae_reports (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			context varchar(20) NOT NULL DEFAULT 'listing',
			listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
			conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reporter_id bigint(20) unsigned NOT NULL,
			reported_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			listing_title varchar(255) NOT NULL DEFAULT '',
			reason varchar(40) NOT NULL,
			details text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			admin_note text NOT NULL,
			resolved_by bigint(20) unsigned NOT NULL DEFAULT 0,
			resolved_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY reported_user_id (reported_user_id),
			KEY reporter_id (reporter_id),
			KEY listing_id (listing_id)
		) ENGINE=InnoDB $charset;";

		dbDelta( $sql_conversations );
		dbDelta( $sql_conversation_messages );
		dbDelta( $sql_chat_offers );
		dbDelta( $sql_reports );
		KMAE_Browse_Index::create_table();

		// Existing installs: add account_type if missing.
		$profiles_table = $prefix . 'kmae_user_profiles';
		$account_col    = $wpdb->get_results( "SHOW COLUMNS FROM {$profiles_table} LIKE 'account_type'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( empty( $account_col ) ) {
			$wpdb->query( "ALTER TABLE {$profiles_table} ADD COLUMN account_type varchar(20) NOT NULL DEFAULT 'private' AFTER phone, ADD KEY account_type (account_type)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Existing users before email prefs: keep receiving offer emails.
		$column = $wpdb->get_results( "SHOW COLUMNS FROM {$profiles_table} LIKE 'email_offers'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $column ) ) {
			$wpdb->query( "UPDATE {$profiles_table} SET email_offers = 1 WHERE email_offers = 0 AND email_news = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		KMAE_Settings::init_defaults();
		update_option( 'kmae_db_version', KMAE_VERSION );
	}

	/**
	 * One-time removal of the old offer / deal / rating engine.
	 * Drops its tables and wipes every (test) conversation, message and chat file.
	 */
	public static function remove_offer_system() {
		if ( get_option( self::OFFER_SYSTEM_REMOVED_OPTION ) ) {
			return;
		}

		global $wpdb;
		$prefix = $wpdb->prefix;

		$attachment_ids = array();
		$images_table   = $prefix . 'kmae_offer_images';
		if ( $images_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $images_table ) ) ) ) {
			$attachment_ids = array_merge( $attachment_ids, (array) $wpdb->get_col( "SELECT attachment_id FROM {$images_table}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		$messages_table = $prefix . 'kmae_conversation_messages';
		$attachment_ids = array_merge( $attachment_ids, (array) $wpdb->get_col( "SELECT attachment_id FROM {$messages_table} WHERE attachment_id IS NOT NULL" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( array_unique( array_map( 'intval', $attachment_ids ) ) as $attachment_id ) {
			if ( $attachment_id > 0 ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}

		$wpdb->query( "DELETE FROM {$messages_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$prefix}kmae_conversations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( array( 'offers', 'offer_images', 'counter_offers', 'contact_unlocks', 'ratings', 'official_offers', 'deals', 'deal_closures', 'vehicle_feedback' ) as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}kmae_{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		foreach ( array( 'kmae_hub_hidden_offers', 'kmae_hub_hidden_conversations', '_kmae_offers_seen_map', '_kmae_offers_last_seen_offer_id', '_kmae_offers_last_seen_at', '_kmae_conversations_seen_map', 'kmae_trust_score', 'kmae_trust_deals', 'kmae_trust_positive', 'kmae_fair_buyer_pledge', 'kmae_post_purchase_offer_id' ) as $meta_key ) {
			delete_metadata( 'user', 0, $meta_key, '', true );
		}
		foreach ( array( '_kmae_accepted_offer_id', '_kmae_deal_phase', '_kmae_deal_locked_at' ) as $meta_key ) {
			delete_metadata( 'post', 0, $meta_key, '', true );
		}

		wp_clear_scheduled_hook( 'kmae_process_deal_closures' );
		delete_option( 'kmae_core_schema_version' );
		delete_option( 'kmae_core_legacy_backfill_version' );

		update_option( self::OFFER_SYSTEM_REMOVED_OPTION, 1 );
	}

	/**
	 * Pre-3.18 listings stored a single "offers" mode. It now means "Ki mit adna érte" (open).
	 * Hidden minimums already set by sellers stay untouched until the listing is edited.
	 */
	public static function migrate_price_modes() {
		if ( get_option( 'kmae_price_modes_migrated' ) ) {
			return;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => KMAE_Seller::PRICE_OPEN ),
			array(
				'meta_key'   => KMAE_Seller::META_PRICE_MODE,
				'meta_value' => 'offers',
			),
			array( '%s' ),
			array( '%s', '%s' )
		);

		update_option( 'kmae_price_modes_migrated', 1 );
	}

	/**
	 * Backfill browse-critical listing meta for already published listings:
	 * - sync `_kmae_vehicle_value` from fixed/guide prices
	 * - ensure launch category on vehicle listings
	 * - ensure default cash offer type meta exists
	 */
	public static function migrate_browse_visibility() {
		if ( get_option( 'kmae_browse_visibility_migrated' ) ) {
			return;
		}

		$listing_ids = get_posts(
			array(
				'post_type'              => 'kmae_listing',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $listing_ids as $listing_id ) {
			$listing_id = (int) $listing_id;
			if ( $listing_id <= 0 ) {
				continue;
			}

			KMAE_Seller::sync_vehicle_value_meta( $listing_id );
			KMAE_Listings::ensure_launch_category( $listing_id );

			$offer_types = get_post_meta( $listing_id, '_kmae_offer_types', true );
			if ( empty( $offer_types ) || ! is_array( $offer_types ) ) {
				update_post_meta( $listing_id, '_kmae_offer_types', KMAE_Taxonomies::default_offer_types() );
			}
		}

		update_option( 'kmae_browse_visibility_migrated', 1 );
	}


	public static function maybe_upgrade() {
		if ( version_compare( get_option( 'kmae_db_version', '0' ), KMAE_VERSION, '<' )
			|| ! get_option( self::OFFER_SYSTEM_REMOVED_OPTION )
		) {
			self::create_tables();
			self::remove_offer_system();
			self::migrate_price_modes();
			self::migrate_browse_visibility();
			KMAE_Pages::ensure_pages();
			KMAE_Auth::ensure_subscriber_caps();
			self::schedule_cron();
		}
		/* Böngészés v2 index: saját séma-verzió, így a KMAE_VERSION emelése (és mellékhatásai) nélkül jön létre. */
		KMAE_Browse_Index::maybe_create();
		if ( ! get_option( 'kmae_cover_studio_removed' ) ) {
			add_action( 'init', array( __CLASS__, 'remove_cover_studio' ), 30 );
		}
		/* A megszűnt Rendszerfigyelő mentett adatának egyszeri törlése. */
		if ( ! get_option( 'kmae_cron_monitor_removed' ) ) {
			delete_option( 'kmae_cron_monitor' );
			update_option( 'kmae_cron_monitor_removed', 1 );
		}
		/* A megszűnt Teljesítménynapló mentett adatának egyszeri törlése. */
		if ( ! get_option( 'kmae_perf_log_removed' ) ) {
			delete_option( 'kmae_perf_log' );
			update_option( 'kmae_perf_log_removed', 1 );
		}
	}

	/**
	 * A megszűnt Borítókép-stúdió (API-s kivágás) maradványainak egyszeri törlése
	 * az élő adatbázisból: a kivágott autóképek (a hirdetés gyermek-csatolmányai,
	 * nem galéria-képek), a hozzájuk tartozó meta és az API-kulcs opció.
	 */
	public static function remove_cover_studio() {
		global $wpdb;

		$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_kmae_cover_cutout'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $rows as $row ) {
			$listing_id = (int) $row->post_id;
			$cutout_id  = (int) $row->meta_value;
			$gallery    = class_exists( 'KMAE_Query' ) ? (array) KMAE_Query::get_gallery_ids( $listing_id ) : array();
			if ( $cutout_id
				&& 'attachment' === get_post_type( $cutout_id )
				&& (int) get_post_field( 'post_parent', $cutout_id ) === $listing_id
				&& ! in_array( $cutout_id, array_map( 'intval', $gallery ), true )
				&& (int) get_post_thumbnail_id( $listing_id ) !== $cutout_id
			) {
				KMAE_Images::delete_attachment( $cutout_id );
			}
		}
		delete_post_meta_by_key( '_kmae_cover_cutout' );
		delete_post_meta_by_key( '_kmae_cover_cutout_src' );
		delete_option( 'kmae_cutout_api_key' );

		/* A böngészés-index kártyáiban még ott a régi kivágás címe. */
		if ( class_exists( 'KMAE_Browse_Index' ) && KMAE_Browse_Index::ready() ) {
			foreach ( (array) $rows as $row ) {
				KMAE_Browse_Index::sync( (int) $row->post_id );
			}
		}

		update_option( 'kmae_cover_studio_removed', 1 );
	}
}