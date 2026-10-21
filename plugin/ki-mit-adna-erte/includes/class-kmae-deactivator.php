<?php
/**
 * Plugin deactivation.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'kmae_purge_archived_listings' );
		wp_clear_scheduled_hook( 'kmae_purge_chat_images' );
		wp_clear_scheduled_hook( 'kmae_remind_unfinished_listings' );
		wp_clear_scheduled_hook( 'kmae_finalize_chat_sales' );
		wp_clear_scheduled_hook( 'kmae_expire_listings' );
		wp_clear_scheduled_hook( 'kmae_cards_backfill' );
		wp_clear_scheduled_hook( 'kmae_browse_index_rebuild' );
		flush_rewrite_rules();
	}
}
