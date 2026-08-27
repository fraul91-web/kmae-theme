<?php
/**
 * Plugin Name:       Albira
 * Plugin URI:        https://kimitadnaerte.hu
 * Description:       Albira piactér — nem eladod a tárgyad, hanem megnézed, mit adnának érte. Alkudozás megalázás nélkül.
 * Version:           3.16.83
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Albira
 * Text Domain:       ki-mit-adna-erte
 * Domain Path:       /languages
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KMAE_VERSION', '3.16.83' );

/** Launch: 'single' = one category only, 'multi' = all categories. */
define( 'KMAE_LAUNCH_MODE', 'single' );
define( 'KMAE_LAUNCH_CATEGORY', 'jarmu' );

/** Automated rule-based moderation on listing submit. */
define( 'KMAE_AUTO_MODERATION', true );
define( 'KMAE_PLUGIN_FILE', __FILE__ );
define( 'KMAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KMAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KMAE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/** Max counter-offer rounds per party. */
define( 'KMAE_MAX_COUNTER_ROUNDS', 2 );

/** Archived listing lifecycle (days). */
define( 'KMAE_ARCHIVE_LOCK_DAYS', 5 );
define( 'KMAE_ARCHIVE_REACTIVATE_DAYS', 2 );
define( 'KMAE_ARCHIVE_TOTAL_DAYS', KMAE_ARCHIVE_LOCK_DAYS + KMAE_ARCHIVE_REACTIVATE_DAYS );

/** Offer type codes. */
define( 'KMAE_OFFER_TYPE_CASH', 'cash' );
define( 'KMAE_OFFER_TYPE_TRADE', 'trade' );
define( 'KMAE_OFFER_TYPE_TRADE_PLUS_CASH', 'trade_plus_cash' );
define( 'KMAE_OFFER_TYPE_TRADE_IN', 'trade_in' );

require_once KMAE_PLUGIN_DIR . 'includes/class-kmae-loader.php';

register_activation_hook( __FILE__, array( 'KMAE_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KMAE_Deactivator', 'deactivate' ) );

function kmae_run() {
	$plugin = new KMAE_Plugin();
	$plugin->run();
}
kmae_run();
