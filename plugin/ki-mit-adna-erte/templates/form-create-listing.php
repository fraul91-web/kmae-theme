<?php
/**
 * Create listing — uses shared form.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mode    = 'create';
$listing = null;
include KMAE_PLUGIN_DIR . 'templates/form-listing.php';
