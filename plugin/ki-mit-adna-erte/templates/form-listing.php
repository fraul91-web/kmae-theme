<?php
/**
 * Listing create/edit form data preparation.
 *
 * @package KMAE
 * @var string       $mode    create|edit
 * @var WP_Post|null $listing Edit mode only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_vehicle     = KMAE_Vehicle::is_vehicle_launch();
$categories     = KMAE_Launch::get_available_categories();
$error          = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';
$is_edit        = 'edit' === $mode && $listing instanceof WP_Post;
/* Félbehagyott hirdetés egyszerű (wizard nélküli) szerkesztése. */
$draft_simple   = ! $is_edit && is_user_logged_in() && isset( $_GET['kmae_draft'], $_GET['kmae_mode'] )
	&& 'simple' === sanitize_key( wp_unslash( $_GET['kmae_mode'] ) )
	&& KMAE_Listing_Drafts::get_user_draft( absint( $_GET['kmae_draft'] ), get_current_user_id() );
$mod_errors     = array();
$launch_cat_id  = KMAE_Launch::get_launch_category_id();

if ( is_user_logged_in() && ( ! $is_edit || 'moderation_failed' === $error ) ) {
	$mod_errors = KMAE_Moderation::pull_errors_for_user( get_current_user_id() );
}

/* Amit a rendszer magától kijavított a feltöltött fotókon — a feltöltő lássa,
   mi történt a képeivel, ne csak azt, hogy „valami megváltozott". */
$photo_fixes = is_user_logged_in() && class_exists( 'KMAE_Photo_Motor' )
	? KMAE_Photo_Motor::take_autofix_notice()
	: array();

$title_val       = $is_edit ? $listing->post_title : '';
$desc_val        = $is_edit ? $listing->post_content : '';
$county_val      = $is_edit ? get_post_meta( $listing->ID, '_kmae_county', true ) : '';
$city_val        = $is_edit ? get_post_meta( $listing->ID, '_kmae_city', true ) : '';
$current_cat     = $is_edit ? wp_get_object_terms( $listing->ID, 'kmae_category', array( 'fields' => 'ids' ) ) : array();
$current_cat_id  = ! empty( $current_cat ) && ! is_wp_error( $current_cat ) ? (int) $current_cat[0] : $launch_cat_id;
$gallery_ids     = $is_edit ? KMAE_Query::get_gallery_ids( $listing->ID ) : array();
$price_mode      = $is_edit ? KMAE_Seller::get_price_mode( $listing->ID ) : '';
$fixed_price_val = $is_edit ? KMAE_Seller::get_fixed_price( $listing->ID ) : 0;
$guide_price_val = $is_edit ? KMAE_Seller::get_guide_price( $listing->ID ) : 0;
$private_price    = $is_edit ? KMAE_Database::get_private_data( $listing->ID ) : null;
$minimum_cash_val = $private_price ? (float) $private_price->minimum_cash : 0;
$commitments     = $is_edit ? KMAE_Seller::get_commitments( $listing->ID ) : array();
$extra_other_val = $is_edit ? KMAE_Seller::get_extra_other( $listing->ID ) : '';
$can_create      = ( ! $is_edit && is_user_logged_in() ) ? KMAE_Seller::can_create_listing( get_current_user_id() ) : true;
$current_user_id = get_current_user_id();
$is_company_user = is_user_logged_in() && KMAE_Auth::is_company( $current_user_id );
$company_location = $is_company_user ? KMAE_Counties::get_label( KMAE_Company::get_county( $current_user_id ) ) . ', ' . KMAE_Company::get_city( $current_user_id ) : '';

$form_action = $is_edit ? admin_url( 'admin-post.php' ) : '';
$min_images  = KMAE_Moderation::is_enabled() ? KMAE_Moderation::MIN_IMAGES : 1;

$listing_error_messages = array(
	'upload_failed'         => __( 'A fotót nem sikerült feltölteni. Próbáld meg újra, vagy válassz egy másik fotót.', 'ki-mit-adna-erte' ),
	'upload_too_large'      => __( 'A kiválasztott fotók együtt túl nagyok voltak, ezért a hirdetés nem ment el. Folytasd a félbehagyott hirdetést, és tölts fel kevesebb fotót.', 'ki-mit-adna-erte' ),
	'moderation_failed'     => __( 'A hirdetés nem felelt meg az automatikus ellenőrzésnek. Javítsd a hibákat, és küldd be újra.', 'ki-mit-adna-erte' ),
	'private_listing_limit' => __( 'Magánszemélyként egyszerre csak egy aktív hirdetésed lehet.', 'ki-mit-adna-erte' ),
	'price_mode_required'   => __( 'Válaszd ki, hogyan szeretnéd értékesíteni az autót.', 'ki-mit-adna-erte' ),
	'fixed_price_required'  => __( 'Add meg a fix árat.', 'ki-mit-adna-erte' ),
	'guide_price_required'  => __( 'Add meg az irányárat.', 'ki-mit-adna-erte' ),
	'minimum_cash_too_high' => __( 'A rejtett minimum nem lehet magasabb a megadott árnál.', 'ki-mit-adna-erte' ),
	'extra_other_required'  => __( 'Írd le az egyéb vállalást.', 'ki-mit-adna-erte' ),
	'extra_other_short'     => __( 'Az egyéb vállalás leírása legalább 10 karakter legyen.', 'ki-mit-adna-erte' ),
	'photo_slots_required'  => __( 'Még nincs meg mind a hét kötelező fotó. Töltsd fel a hiányzó képeket.', 'ki-mit-adna-erte' ),
	'photo_quality_failed'  => __( 'Néhány fotót ki kell cserélned. A fotóknál megmutatjuk, melyiket és miért.', 'ki-mit-adna-erte' ),
	'photo_desktop_blocked' => __( 'Az autófotókat csak telefonról vagy tabletről, közvetlenül a kamerával lehet feltölteni. Kérjük, nyisd meg ezt az oldalt mobilon.', 'ki-mit-adna-erte' ),
	'device_required'       => __( 'Válaszd ki, milyen eszközön készíted a hirdetést.', 'ki-mit-adna-erte' ),
	'company_location_required' => __( 'A céges telephely hiányzik. A profilodban add meg a megyét és a várost.', 'ki-mit-adna-erte' ),
	'company_location_mismatch' => __( 'A céges telephely települése nem ebben a megyében van. Javítsd a profilodban: válaszd ki a listából vagy írd be az irányítószámot.', 'ki-mit-adna-erte' ),
	'location_mismatch'     => __( 'Ez a település nem ebben a megyében van — válaszd ki a listából vagy írd be az irányítószámot.', 'ki-mit-adna-erte' ),
	'missing_fields'        => __( 'Tölts ki minden kötelező mezőt.', 'ki-mit-adna-erte' ),
);

$edit_status = ( $is_edit && $listing ) ? KMAE_Meta::get_status( $listing->ID ) : '';
$is_awaiting = KMAE_Meta::STATUS_AWAITING_PHOTOS === $edit_status;
$open_photos = $is_awaiting || ( isset( $_GET['kmae_photos'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['kmae_photos'] ) ) );

include KMAE_PLUGIN_DIR . 'templates/partials/listing-form-layout.php';
