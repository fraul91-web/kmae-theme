<?php
/**
 * Main plugin orchestrator.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Plugin {

	public function run() {
		KMAE_Activator::maybe_upgrade();
		KMAE_Brand::init();
		KMAE_Privacy::init();
		KMAE_Auth::init();
		KMAE_Google_Auth::init();
		KMAE_Notifications::init();
		KMAE_Conversations::init();
		KMAE_Chat_Offers::init();
		KMAE_Listings::init();
		KMAE_Listing_Drafts::init();
		KMAE_Counties::init();
		KMAE_Live_Signal::init();
		KMAE_Push::init();
		KMAE_Perf::init();
		KMAE_Legal::init();
		KMAE_Registration_Scan::init();
		KMAE_Geo::init();
		KMAE_Images::init();
		KMAE_Image_Cards::init();
		KMAE_Browse_Index::init();
		KMAE_Browse_Feed::init();
		KMAE_Dashboard::init();
		KMAE_Dealer_Leads::init();

		new KMAE_Post_Types();
		new KMAE_Taxonomies();
		new KMAE_Meta();

		if ( is_admin() ) {
			require_once KMAE_PLUGIN_DIR . 'admin/class-kmae-admin.php';
			new KMAE_Admin();
		}

		require_once KMAE_PLUGIN_DIR . 'public/class-kmae-public.php';
		require_once KMAE_PLUGIN_DIR . 'public/class-kmae-shortcodes.php';
		new KMAE_Public();
		new KMAE_Shortcodes();
		new KMAE_Pages();
		new KMAE_Nav();
	}
}
