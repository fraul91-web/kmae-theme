<?php
/**
 * Plugin Name:       Albira
 * Plugin URI:        https://kimitadnaerte.hu
 * Description:       Albira piactér — nem eladod a tárgyad, hanem megnézed, mit adnának érte. Alkudozás megalázás nélkül.
 * Version:           4.65.0
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

define( 'KMAE_VERSION', '4.65.0' );

/** Launch: 'single' = one category only, 'multi' = all categories. */
define( 'KMAE_LAUNCH_MODE', 'single' );
define( 'KMAE_LAUNCH_CATEGORY', 'jarmu' );

/** Automated rule-based moderation on listing submit. */
define( 'KMAE_AUTO_MODERATION', true );
define( 'KMAE_PLUGIN_FILE', __FILE__ );
define( 'KMAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KMAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KMAE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Automatikus fájlverzió: a plugin saját CSS/JS fájljai a módosítási idejüket
 * kapják `ver`-nek, így minden feltöltés után a böngészők frissítik őket
 * (hosszú böngésző-gyorsítótár mellett is) — nem kell kézzel verziót emelni.
 *
 * @param string $src Asset URL.
 * @return string
 */
function kmae_plugin_asset_version_src( $src ) {
	if ( ! is_string( $src ) || 0 !== strpos( $src, KMAE_PLUGIN_URL ) ) {
		return $src;
	}
	$path = KMAE_PLUGIN_DIR . strtok( substr( $src, strlen( KMAE_PLUGIN_URL ) ), '?' );
	if ( ! is_file( $path ) ) {
		return $src;
	}
	return add_query_arg( 'ver', (string) filemtime( $path ), $src );
}
add_filter( 'style_loader_src', 'kmae_plugin_asset_version_src', 20 );
add_filter( 'script_loader_src', 'kmae_plugin_asset_version_src', 20 );

/** Archived listing lifecycle (days). Auto-purge after TOTAL. */
define( 'KMAE_ARCHIVE_LOCK_DAYS', 1 );
define( 'KMAE_ARCHIVE_REACTIVATE_DAYS', 2 );
define( 'KMAE_ARCHIVE_TOTAL_DAYS', KMAE_ARCHIVE_LOCK_DAYS + KMAE_ARCHIVE_REACTIVATE_DAYS );

/** Active offer type. */
define( 'KMAE_OFFER_TYPE_CASH', 'cash' );

require_once KMAE_PLUGIN_DIR . 'includes/class-kmae-loader.php';

register_activation_hook( __FILE__, array( 'KMAE_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KMAE_Deactivator', 'deactivate' ) );

function kmae_run() {
	$plugin = new KMAE_Plugin();
	$plugin->run();
}
kmae_run();
