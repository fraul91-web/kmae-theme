<?php
/**
 * Albira theme functions.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KMAE_THEME_VERSION', '3.25.622' );
if ( ! defined( 'KMAE_VERSION' ) ) {
	define( 'KMAE_VERSION', KMAE_THEME_VERSION );
}
define( 'KMAE_DIR', get_template_directory() );
define( 'KMAE_URI', get_template_directory_uri() );

/**
 * Automatikus fájlverzió: a téma saját CSS/JS fájljai a módosítási idejüket
 * kapják `ver`-nek, így minden feltöltés után a böngészők frissítik őket
 * (hosszú böngésző-gyorsítótár mellett is) — nem kell kézzel verziót emelni.
 *
 * @param string $src Asset URL.
 * @return string
 */
function kmae_asset_version_src( $src ) {
	$base = KMAE_URI . '/';
	if ( ! is_string( $src ) || 0 !== strpos( $src, $base ) ) {
		return $src;
	}
	$path = KMAE_DIR . '/' . strtok( substr( $src, strlen( $base ) ), '?' );
	if ( ! is_file( $path ) ) {
		return $src;
	}
	return add_query_arg( 'ver', (string) filemtime( $path ), $src );
}
add_filter( 'style_loader_src', 'kmae_asset_version_src', 20 );
add_filter( 'script_loader_src', 'kmae_asset_version_src', 20 );

/* A felület egyetlen változatban létezik: simple mód, sötét téma, PC nézet. */
define( 'KMAE_CSS_BASE', '/assets/css/simple-mode/' );

/**
 * Theme setup.
 */
function kmae_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 180,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
}
add_action( 'after_setup_theme', 'kmae_setup' );

/**
 * Permalink for a KMAE shortcode page (plugin), with safe fallback.
 *
 * @param string $slug Page slug.
 * @return string
 */
function kmae_page_url( $slug ) {
	if ( class_exists( 'KMAE_Pages' ) && method_exists( 'KMAE_Pages', 'url' ) ) {
		return KMAE_Pages::url( $slug );
	}
	return home_url( '/' . trim( $slug, '/' ) . '/' );
}

function kmae_is_listing_create_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	if ( 'hirdetes-feladasa' === $slug ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_create_listing' )
		|| false !== strpos( $content, '[kmae_create_listing' );
}

/**
 * Desktop nav: „Hirdetés feladása" vs „Hirdetéseim" on /hirdetes-feladasa/.
 *
 * Create flow (new listing, wizard draft, awaiting photos) → create nav.
 * Editing an already submitted listing (?kmae_edit=) → my listings nav.
 */
function kmae_is_listing_create_nav_context() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	if ( false === strpos( $path, '/hirdetes-feladasa' ) ) {
		return false;
	}

	if ( isset( $_GET['kmae_draft'] ) ) {
		return true;
	}

	$edit_id = isset( $_GET['kmae_edit'] ) ? absint( $_GET['kmae_edit'] ) : 0;
	if ( ! $edit_id ) {
		return true;
	}

	if ( ! class_exists( 'KMAE_Meta' ) ) {
		return true;
	}

	$listing = get_post( $edit_id );
	if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
		return true;
	}

	if ( (int) $listing->post_author !== get_current_user_id() ) {
		return true;
	}

	$status = KMAE_Meta::get_status( $edit_id );

	return in_array( $status, array( KMAE_Meta::STATUS_DRAFT, KMAE_Meta::STATUS_AWAITING_PHOTOS ), true );
}

function kmae_is_browse_page() {
	/* A /hirdetesek/ cím a hirdetés-bejegyzéstípus archívuma (a plugin
	   archive-listing.php sablonja futtatja a [kmae_browse] kódot), nem oldal. */
	if ( is_post_type_archive( 'kmae_listing' ) ) {
		return true;
	}

	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	if ( 'hirdetesek' === (string) get_post_field( 'post_name', $post_id ) ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_browse' )
		|| false !== strpos( $content, '[kmae_browse' );
}

function kmae_is_favorites_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	if ( 'kedvencek' === (string) get_post_field( 'post_name', $post_id ) ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_favorites' )
		|| false !== strpos( $content, '[kmae_favorites' );
}

function kmae_is_my_listings_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	if ( 'sajat-hirdetesek' === $slug ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_my_listings' )
		|| false !== strpos( $content, '[kmae_my_listings' );
}

function kmae_is_messages_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	if ( 'uzenetek' === $slug ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_messages' )
		|| false !== strpos( $content, '[kmae_messages' );
}

function kmae_is_faq_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	$content = (string) get_post_field( 'post_content', $post_id );

	return 'gyik' === $slug
		|| has_shortcode( $content, 'kmae_faq' )
		|| false !== strpos( $content, '[kmae_faq' );
}

function kmae_is_contact_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	$content = (string) get_post_field( 'post_content', $post_id );

	return 'kapcsolat' === $slug
		|| has_shortcode( $content, 'kmae_contact' )
		|| false !== strpos( $content, '[kmae_contact' );
}

function kmae_is_security_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	$content = (string) get_post_field( 'post_content', $post_id );

	return 'biztonsag' === $slug
		|| has_shortcode( $content, 'kmae_security' )
		|| false !== strpos( $content, '[kmae_security' );
}

function kmae_is_profile_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	if ( 'profil' === $slug ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_profile' )
		|| false !== strpos( $content, '[kmae_profile' );
}

/**
 * A Google-belépés utáni kiegészítő képernyő (regisztráció oldal + jelző).
 * Itt szándékosan nincs navigáció: a felhasználó fejezze be a fiókját.
 */
function kmae_is_google_complete_page() {
	if ( ! is_user_logged_in() || ! isset( $_GET['kmae_complete'] ) ) {
		return false;
	}

	if ( ! class_exists( 'KMAE_Google_Auth' ) || ! KMAE_Google_Auth::needs_completion( get_current_user_id() ) ) {
		return false;
	}

	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	$content = (string) get_post_field( 'post_content', $post_id );

	return 'regisztracio' === $slug
		|| has_shortcode( $content, 'kmae_register' )
		|| false !== strpos( $content, '[kmae_register' );
}

function kmae_is_settings_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	if ( 'beallitasok' === $slug ) {
		return true;
	}

	$content = (string) get_post_field( 'post_content', $post_id );

	return has_shortcode( $content, 'kmae_settings' )
		|| false !== strpos( $content, '[kmae_settings' );
}

/**
 * Body classes. A felület egyetlen változatban létezik: simple mód, sötét
 * téma, PC nézet — ezért ezek fix osztályok, nincs mögöttük kapcsoló.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
/**
 * Vendég, aki a belépett felület kinézetét kapja: böngészés és hirdetés-adatlap.
 *
 * @return bool
 */
function kmae_is_guest_app_view() {
	if ( is_user_logged_in() ) {
		return false;
	}

	return is_singular( 'kmae_listing' ) || kmae_is_browse_page();
}

/**
 * A belépett app-héj (fejléc, stíluslapok, body osztályok) jár-e az oldalnak.
 *
 * @return bool
 */
function kmae_uses_app_shell() {
	return is_user_logged_in() || kmae_is_guest_app_view();
}

function kmae_body_classes( $classes ) {
	if ( kmae_uses_app_shell() ) {
		$classes[] = 'kmae-app';
		$classes[] = 'kmae-theme-dark';
		if ( ! is_user_logged_in() ) {
			$classes[] = 'kmae-guest-app';
		}
	} else {
		$classes[] = 'kmae-marketing';
	}
	if ( ! is_user_logged_in() && is_page( 'belepes' ) ) {
		$classes[] = 'kmae-login-page';
	}
	if ( ! is_user_logged_in() && is_page( 'regisztracio' ) ) {
		$classes[] = 'kmae-register-page';
	}
	if ( kmae_is_listing_create_page() ) {
		$classes[] = 'kmae-listing-create-page';
	}
	if ( is_singular( 'kmae_listing' ) ) {
		$classes[] = 'kmae-single-listing-page';
	}
	if ( kmae_is_my_listings_page() ) {
		$classes[] = 'kmae-my-listings-page';
	}
	if ( kmae_is_browse_page() ) {
		$classes[] = 'kmae-browse-page';
		if ( kmae_is_browse_v2_cards() ) {
			$classes[] = 'kmae-sw2-page';
		}
	}
	if ( kmae_is_favorites_page() ) {
		$classes[] = 'kmae-favorites-page';
	}
	if ( kmae_is_messages_page() ) {
		$classes[] = 'kmae-messages-page';
	}
	if ( kmae_is_faq_page() ) {
		$classes[] = 'kmae-faq-page';
	}
	if ( kmae_is_security_page() ) {
		$classes[] = 'kmae-security-page';
	}
	if ( is_page( 'adatvedelem' ) ) {
		$classes[] = 'kmae-privacy-page';
	}
	if ( is_page( 'feltetelek' ) ) {
		$classes[] = 'kmae-terms-page';
	}
	if ( kmae_is_contact_page() ) {
		$classes[] = 'kmae-contact-page-view';
	}
	if ( kmae_is_profile_page() ) {
		$classes[] = 'kmae-profile-page';
	}
	if ( kmae_is_settings_page() ) {
		$classes[] = 'kmae-settings-page';
	}
	if ( kmae_is_google_complete_page() ) {
		$classes[] = 'kmae-complete-page';
	}
	return $classes;
}
add_filter( 'body_class', 'kmae_body_classes' );

/**
 * Simple Mode guest marketing shell (landing, auth, contact) — no legacy plugin/mobile CSS layer.
 *
 * @return bool
 */
function kmae_is_guest_simple_marketing_shell() {
	if ( is_user_logged_in() ) {
		return false;
	}

	$shell_slugs = array( 'regisztracio', 'belepes', 'kapcsolat' );

	foreach ( $shell_slugs as $slug ) {
		if ( is_page( $slug ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Enqueue styles and scripts.
 */
function kmae_assets() {
	$logged_in = kmae_uses_app_shell();
	$css_base  = KMAE_CSS_BASE;
	$font_family = $logged_in
		? 'Outfit:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700'
		: 'Plus+Jakarta+Sans:wght@400;500;600;700;800';

	wp_enqueue_style(
		'kmae-fonts',
		'https://fonts.googleapis.com/css2?family=' . $font_family . '&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'kmae-theme',
		KMAE_URI . '/assets/css/theme.css',
		array( 'kmae-fonts' ),
		KMAE_THEME_VERSION
	);

	if ( ! is_user_logged_in() ) {
		wp_enqueue_style(
			'kmae-header-auth',
			KMAE_URI . $css_base . 'header-auth.css',
			array( 'kmae-theme' ),
			KMAE_THEME_VERSION
		);
	}

	if ( $logged_in ) {
		wp_enqueue_style(
			'kmae-app',
			KMAE_URI . $css_base . 'app.css',
			array( 'kmae-theme' ),
			KMAE_THEME_VERSION
		);
		wp_enqueue_style(
			'kmae-pc-dashboard',
			KMAE_URI . $css_base . 'pc-dashboard.css',
			array( 'kmae-app' ),
			KMAE_THEME_VERSION
		);
	} else {
		wp_enqueue_style(
			'kmae-landing',
			KMAE_URI . $css_base . 'landing.css',
			array( 'kmae-theme' ),
			KMAE_THEME_VERSION
		);

	}

	wp_enqueue_script(
		'kmae-theme',
		KMAE_URI . '/assets/js/theme.js',
		array(),
		KMAE_THEME_VERSION,
		true
	);

}
add_action( 'wp_enqueue_scripts', 'kmae_assets' );

/**
 * App chrome last, so plugin public.css copy can be overridden only on the
 * logged-in canvas.
 */
function kmae_enqueue_app_shell() {
	if ( ! kmae_uses_app_shell() ) {
		return;
	}

	$css_base = KMAE_CSS_BASE;

	wp_enqueue_style(
		'kmae-app-shell-dark',
		KMAE_URI . $css_base . 'app-shell-dark.css',
		array( 'kmae-pc-dashboard' ),
		KMAE_THEME_VERSION
	);
	wp_enqueue_style(
		'kmae-header',
		KMAE_URI . $css_base . 'header.css',
		array( 'kmae-app-shell-dark' ),
		KMAE_THEME_VERSION
	);
	wp_enqueue_style(
		'kmae-home-cards',
		KMAE_URI . '/assets/css/home-cards.css',
		array( 'kmae-header' ),
		KMAE_THEME_VERSION
	);


	/* A hirdetés adatlapja saját stílust kap — a sötét, üveges megjelenést az
	   alap plugin-stílusok fölé kell tenni, ezért itt, a legvégén töltjük be. */
	if ( is_singular( 'kmae_listing' ) ) {
		wp_enqueue_style(
			'kmae-single-listing',
			KMAE_URI . '/assets/css/single-listing.css',
			array( 'kmae-header' ),
			KMAE_THEME_VERSION
		);
		/* PC (1025px+): külön, nulláról írt stílus; a single-listing.css csak telefonon hat. */
		wp_enqueue_style(
			'kmae-single-listing-pc',
			KMAE_URI . '/assets/css/single-listing-pc.css',
			array( 'kmae-single-listing' ),
			KMAE_THEME_VERSION
		);
	}

	if ( kmae_is_my_listings_page() || kmae_is_listing_create_page() ) {
		wp_enqueue_style(
			'kmae-my-listings',
			KMAE_URI . '/assets/css/my-listings.css',
			array( 'kmae-header' ),
			KMAE_THEME_VERSION
		);
	}

	if ( kmae_is_messages_page() ) {
		/* Megnyitott beszélgetés (chat): saját, egyetlen stílusforrás. */
		if ( is_user_logged_in() && ! empty( $_GET['kmae_conversation'] ) && ! empty( $_GET['kmae_contract'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			/* Adásvételi szerződés aloldal (eladó, elfogadott ajánlat után). */
			wp_enqueue_style(
				'kmae-sale-contract',
				KMAE_URI . '/assets/css/sale-contract.css',
				array( 'kmae-header' ),
				KMAE_THEME_VERSION
			);
		} elseif ( is_user_logged_in() && ! empty( $_GET['kmae_conversation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style(
				'kmae-offer-thread',
				KMAE_URI . '/assets/css/offer-thread.css',
				array( 'kmae-header' ),
				KMAE_THEME_VERSION
			);
		} else {
			wp_enqueue_style(
				'kmae-messages',
				KMAE_URI . '/assets/css/messages.css',
				array( 'kmae-header' ),
				KMAE_THEME_VERSION
			);
		}
	}

	if ( kmae_is_profile_page() ) {
		wp_enqueue_style(
			'kmae-profile',
			KMAE_URI . '/assets/css/profile.css',
			array( 'kmae-header' ),
			KMAE_THEME_VERSION
		);
	}

	if ( kmae_is_settings_page() ) {
		wp_enqueue_style(
			'kmae-settings',
			KMAE_URI . '/assets/css/settings.css',
			array( 'kmae-header' ),
			KMAE_THEME_VERSION
		);
	}

}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_app_shell', 99 );

/**
 * Egységes háttér (landing kép) minden oldalon, vendégnek és belépve is.
 * A legvégén töltjük be, hogy felülírja a régi háttér-szabályokat.
 */
function kmae_enqueue_site_background() {
	wp_enqueue_style(
		'kmae-site-background',
		KMAE_URI . '/assets/css/site-background.css',
		array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_site_background', 999 );

/**
 * PC: szellem görgetősáv (csak egérrel a sávon látszik tömören).
 */
function kmae_enqueue_scrollbar_ghost() {
	wp_enqueue_style(
		'kmae-scrollbar-ghost',
		KMAE_URI . '/assets/css/scrollbar-ghost.css',
		array(),
		KMAE_THEME_VERSION
	);
	wp_enqueue_script(
		'kmae-scrollbar-ghost',
		KMAE_URI . '/assets/js/scrollbar-ghost.js',
		array(),
		KMAE_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_scrollbar_ghost', 1000 );

/**
 * A Google-belépés utáni kiegészítő képernyő önálló stílusa. Belépve a
 * marketing stíluslap nem töltődik be, ezért kap sajátot.
 */
function kmae_enqueue_google_complete() {
	if ( ! kmae_is_google_complete_page() ) {
		return;
	}

	/* A bővítmény sötét alapstílusai és az app-héj UTÁN kell betöltődnie,
	   különben azok írnák felül ezt a képernyőt. */
	wp_enqueue_style(
		'kmae-google-complete',
		KMAE_URI . '/assets/css/google-complete.css',
		array( 'kmae-public', 'kmae-header' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_google_complete', 101 );

/**
 * Vendég főoldal (landing): saját stílus + görgetéses megjelenés.
 */
function kmae_enqueue_landing_home() {
	if ( is_user_logged_in() || ! is_front_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-landing-home',
		KMAE_URI . '/assets/css/landing-home.css',
		array( 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
	wp_enqueue_script(
		'kmae-landing-home',
		KMAE_URI . '/assets/js/landing-home.js',
		array(),
		KMAE_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_landing_home', 100 );

/**
 * Belépés oldal telefonon: app-szerű elrendezés (álló + fekvő). Csak CSS,
 * csak vendégnek, a marketing stíluslap után töltődik.
 */
function kmae_enqueue_login_mobile() {
	if ( is_user_logged_in() || ! is_page( 'belepes' ) ) {
		return;
	}

	wp_enqueue_style(
		'kmae-login-mobile',
		KMAE_URI . '/assets/css/login-mobile.css',
		array( 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_login_mobile', 100 );

/**
 * Regisztráció oldal telefonon: app-szerű elrendezés (álló + fekvő).
 * Csak CSS, csak vendégnek, a marketing stíluslap után töltődik.
 */
function kmae_enqueue_register_mobile() {
	if ( is_user_logged_in() || ! is_page( 'regisztracio' ) ) {
		return;
	}

	wp_enqueue_style(
		'kmae-register-mobile',
		KMAE_URI . '/assets/css/register-mobile.css',
		array( 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_register_mobile', 100 );

/**
 * Infó oldalak mobilon (Biztonság, ÁSZF, Adatvédelem):
 * app-szerű elrendezés, álló + fekvő. Csak CSS, csak max 1024px.
 */
function kmae_enqueue_info_mobile() {
	if ( ! ( kmae_is_security_page() || is_page( 'feltetelek' ) ) ) {
		return;
	}

	$deps = array();
	foreach ( array( 'kmae-security' ) as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			$deps[] = $handle;
		}
	}

	wp_enqueue_style(
		'kmae-info-mobile',
		KMAE_URI . '/assets/css/info-mobile.css',
		$deps,
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_info_mobile', 101 );

/**
 * Adatvédelem telefonon (álló + fekvő): saját app-szerű nézet. Csak CSS, csak max 1024px.
 */
function kmae_enqueue_privacy_mobile() {
	if ( ! is_page( 'adatvedelem' ) ) {
		return;
	}

	wp_enqueue_style(
		'kmae-privacy-mobile',
		KMAE_URI . '/assets/css/privacy-mobile.css',
		array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_privacy_mobile', 101 );

/**
 * GYIK telefonon (álló + fekvő): saját app-szerű nézet. Csak CSS, csak max 1024px.
 */
function kmae_enqueue_faq_mobile() {
	if ( ! kmae_is_faq_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-faq-mobile',
		KMAE_URI . '/assets/css/faq-mobile.css',
		wp_style_is( 'kmae-faq', 'enqueued' ) ? array( 'kmae-faq' ) : array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_faq_mobile', 101 );

/**
 * Kapcsolat oldal telefonon: app-szerű elrendezés (álló + fekvő).
 * Csak CSS, csak max 1024px; a contact.css (asztali) után töltődik.
 */
function kmae_enqueue_contact_mobile() {
	if ( ! kmae_is_contact_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-contact-mobile',
		KMAE_URI . '/assets/css/contact-mobile.css',
		wp_style_is( 'kmae-contact', 'enqueued' ) ? array( 'kmae-contact' ) : array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_contact_mobile', 101 );

/**
 * Manók egységes mérete (200×200), telefonon és PC-n. Csak CSS.
 */
function kmae_enqueue_mascot_size() {
	wp_enqueue_style(
		'kmae-mascot-size',
		KMAE_URI . '/assets/css/mascot-size.css',
		array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_mascot_size', 102 );

/**
 * Böngészés: vendégként is a belépett app-héjon (kmae_is_guest_app_view).
 */
function kmae_enqueue_browse() {
	if ( ! kmae_is_browse_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-browse',
		KMAE_URI . '/assets/css/browse.css',
		array( 'kmae-header' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_browse', 100 );

/**
 * Böngészés telefonon (álló + fekvő), app-szerű: fejléc, alsó menüsor
 * (template-parts/browse-tabbar.php), kártyák, szűrő-lap. Csak 1024px
 * alatt hat, az asztali nézethez nem nyúl.
 */
function kmae_enqueue_browse_mobile() {
	if ( ! kmae_is_browse_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-browse-mobile',
		KMAE_URI . '/assets/css/browse-mobile.css',
		array( 'kmae-browse' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_browse_mobile', 101 );

/**
 * Böngészés v2 kártyás nézet (plugin KMAE_Browse_Feed::use_card_index): a régi
 * kártya helyett a színpad + adatpanel. Markup: plugin PROJECT-MEMORY/FEATURES/BROWSE-V2.md.
 */
function kmae_is_browse_v2_cards() {
	return kmae_is_browse_page()
		&& class_exists( 'KMAE_Browse_Feed' )
		&& method_exists( 'KMAE_Browse_Feed', 'use_card_index' )
		&& KMAE_Browse_Feed::use_card_index();
}

function kmae_enqueue_browse_v2() {
	if ( ! kmae_is_browse_v2_cards() ) {
		return;
	}

	$deps = array( 'kmae-browse' );
	if ( wp_style_is( 'kmae-browse-v2', 'enqueued' ) ) {
		$deps[] = 'kmae-browse-v2';
	}

	wp_enqueue_style(
		'kmae-theme-browse-v2',
		KMAE_URI . '/assets/css/browse-v2.css',
		$deps,
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_browse_v2', 102 );

/**
 * Kedvencek lista.
 */
function kmae_enqueue_favorites() {
	if ( ! kmae_is_favorites_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-favorites',
		KMAE_URI . '/assets/css/favorites.css',
		array( is_user_logged_in() ? 'kmae-header' : 'kmae-theme' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_favorites', 100 );

/**
 * Security (Biztonság) page styles, self-contained PC page.
 */
function kmae_enqueue_security() {
	if ( ! kmae_is_security_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-security',
		KMAE_URI . '/assets/css/security.css',
		array( is_user_logged_in() ? 'kmae-header' : 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_security', 100 );

/**
 * FAQ page styles, loaded after the shared application shell.
 */
function kmae_enqueue_faq() {
	if ( ! kmae_is_faq_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-faq',
		KMAE_URI . '/assets/css/faq.css',
		array( is_user_logged_in() ? 'kmae-header' : 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_faq', 100 );

/**
 * Contact page styles, loaded after the shared application shell.
 */
function kmae_enqueue_contact() {
	if ( ! kmae_is_contact_page() ) {
		return;
	}

	wp_enqueue_style(
		'kmae-contact',
		KMAE_URI . '/assets/css/contact.css',
		array( is_user_logged_in() ? 'kmae-header' : 'kmae-landing' ),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_contact', 100 );

/**
 * Lejárt hirdetés a látogatónak („Ez az autó már nem elérhető”) — vendégnek is.
 */
function kmae_enqueue_listing_unavailable() {
	if ( ! is_singular( 'kmae_listing' ) || ! class_exists( 'KMAE_Post_Types' ) || ! KMAE_Post_Types::is_unavailable_for_viewer( get_queried_object_id() ) ) {
		return;
	}
	wp_enqueue_style(
		'kmae-listing-unavailable',
		KMAE_URI . '/assets/css/listing-unavailable.css',
		array(),
		KMAE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_listing_unavailable', 101 );

/**
 * Asset URL helper (CSS/JS under theme assets/).
 *
 * @param string $path Relative path under assets/.
 * @return string
 */
function kmae_asset( $path ) {
	return KMAE_URI . '/assets/' . ltrim( $path, '/' );
}

/**
 * Hosted media on the production uploads (not shipped in the theme ZIP).
 *
 * @param string $key logo|landing_car_*.
 * @return string
 */
function kmae_media_url( $key ) {
	$guest = class_exists( 'KMAE_Brand' ) ? KMAE_Brand::logo_guest() : 'https://albira.hu/wp-content/uploads/2026/09/logo.webp';
	$app   = class_exists( 'KMAE_Brand' ) ? KMAE_Brand::logo_app() : 'https://albira.hu/wp-content/uploads/2026/09/logo.webp';

	static $urls = null;
	if ( null === $urls ) {
		$urls = array(
			'logo_guest'     => $guest,
			'logo_app'       => $app,
			/* Landing: a bemutató autó-képei (átlátszó hátterű kivágások). */
			'landing_car_main' => 'https://albira.hu/wp-content/uploads/2026/09/suv.webp',
			'landing_car_a'    => 'https://albira.hu/wp-content/uploads/2026/09/sedan3.webp',
			'landing_car_b'    => 'https://albira.hu/wp-content/uploads/2026/09/kombi.webp',
			'landing_car_c'    => 'https://albira.hu/wp-content/uploads/2026/09/terepjaro.webp',
		);
	}

	// A felület mindig sötét, így a vendég logó marad bejelentkezés után is.
	$urls['logo'] = $guest;

	return isset( $urls[ $key ] ) ? $urls[ $key ] : '';
}

/**
 * Current logo URL. A felület mindig sötét, ezért a vendég logó megy
 * mindenhol; az 'app' változat csak kifejezett kérésre.
 *
 * @param string $variant guest|app|auto.
 * @return string
 */
function kmae_logo_url( $variant = 'auto' ) {
	if ( 'guest' === $variant || 'app' === $variant ) {
		if ( class_exists( 'KMAE_Brand' ) ) {
			return KMAE_Brand::logo_url( $variant );
		}

		return kmae_media_url( 'guest' === $variant ? 'logo_guest' : 'logo_app' );
	}

	return class_exists( 'KMAE_Brand' ) ? KMAE_Brand::logo_guest() : kmae_media_url( 'logo_guest' );
}

/**
 * Print the correct Albira logo image for the current visitor.
 *
 * @param string $class Extra img class.
 */
function kmae_logo_img( $class = 'kmae-logo__img' ) {
	printf(
		'<img class="%1$s" src="%2$s" alt="%3$s" decoding="async" />',
		esc_attr( $class ),
		esc_url( kmae_logo_url() ),
		esc_attr__( 'Albira', 'kmae' )
	);
}

/**
 * Shared logged-in menu groups: navigation (blue) + info (orange).
 *
 * @return array{nav: array<int, array<string, mixed>>, info: array<int, array<string, mixed>>}
 */
function kmae_app_menu_groups() {
	if ( ! is_user_logged_in() ) {
		return array(
			'nav'  => array(),
			'info' => array(),
		);
	}

	$path     = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$listings = 0;
	$unseen   = 0;

	if ( class_exists( 'KMAE_Seller' ) ) {
		$listings = (int) KMAE_Seller::count_active_cycle_listings( get_current_user_id() );
	}
	if ( class_exists( 'KMAE_Database' ) ) {
		$unseen = (int) KMAE_Database::count_unread_conversations( get_current_user_id() );
	}

	$is = static function ( $slug ) use ( $path ) {
		return false !== strpos( $path, '/' . $slug );
	};

	return array(
		'nav'  => array(
			array(
				'label'  => __( 'Keresés', 'kmae' ),
				'url'    => kmae_page_url( 'hirdetesek' ),
				'active' => $is( 'hirdetesek' ) && ! $is( 'sajat-hirdetesek' ),
				'icon'   => 'search',
			),
			array(
				'label'  => __( 'Kedvencek', 'kmae' ),
				'url'    => kmae_page_url( 'kedvencek' ),
				'active' => $is( 'kedvencek' ),
				'icon'   => 'heart',
			),
			array(
				'label'       => __( 'Hirdetéseim', 'kmae' ),
				'url'         => kmae_page_url( 'sajat-hirdetesek' ),
				'active'      => $is( 'sajat-hirdetesek' ) || ( $is( 'hirdetes-feladasa' ) && ! kmae_is_listing_create_nav_context() ),
				'icon'        => 'clipboard-list',
				'badge'       => $listings > 0 ? (string) $listings : '',
				'badge_class' => 'is-soft',
			),
			array(
				'label'  => __( 'Üzenetek', 'kmae' ),
				'url'    => kmae_page_url( 'uzenetek' ),
				'active' => $is( 'uzenetek' ),
				'icon'   => 'message-square',
				'badge'  => $unseen > 0 ? (string) $unseen : '',
			),
			array(
				'label'  => __( 'Profilom', 'kmae' ),
				'url'    => kmae_page_url( 'profil' ),
				'active' => $is( 'profil' ),
				'icon'   => 'user',
			),
			array(
				'label'  => __( 'Beállítások', 'kmae' ),
				'url'    => kmae_page_url( 'beallitasok' ),
				'active' => $is( 'beallitasok' ),
				'icon'   => 'settings',
			),
			array(
				'label'  => __( 'Statisztikák', 'kmae' ),
				'url'    => kmae_page_url( 'statisztikak' ),
				'active' => $is( 'statisztikak' ),
				'icon'   => 'bar-chart',
			),
		),
		'info' => array(
			array(
				'label'  => __( 'Gyakori kérdések', 'kmae' ),
				'url'    => kmae_page_url( 'gyik' ),
				'active' => $is( 'gyik' ),
				'icon'   => 'circle-help',
			),
			array(
				'label'  => __( 'Biztonság', 'kmae' ),
				'url'    => kmae_page_url( 'biztonsag' ),
				'active' => $is( 'biztonsag' ),
				'icon'   => 'shield',
			),
			array(
				'label'  => __( 'Kapcsolat', 'kmae' ),
				'url'    => kmae_page_url( 'kapcsolat' ),
				'active' => $is( 'kapcsolat' ),
				'icon'   => 'mail',
			),
			array(
				'label'  => __( 'Feltételek', 'kmae' ),
				'url'    => kmae_page_url( 'feltetelek' ),
				'active' => $is( 'feltetelek' ),
				'icon'   => 'file-text',
			),
			array(
				'label'  => __( 'Adatvédelem', 'kmae' ),
				'url'    => kmae_page_url( 'adatvedelem' ),
				'active' => $is( 'adatvedelem' ),
				'icon'   => 'lock',
			),
		),
	);
}

/** Dedicated listing page stylesheet, shared by guests and signed-in members. */
function kmae_enqueue_listing_create_style() {
	if ( ! kmae_is_listing_create_page() ) {
		return;
	}
	wp_enqueue_style(
		'kmae-listing-create',
		KMAE_URI . KMAE_CSS_BASE . 'listing-create.css',
		array( is_user_logged_in() ? 'kmae-header' : 'kmae-landing' ),
		KMAE_THEME_VERSION
	);

	/* Egyszerű szerkesztő (.is-simple): saját, egyetlen stílusforrás. Ugyanaz a
	   feltétel, mint a plugin listing-form-layout.php-jában. */
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$mode   = isset( $_GET['kmae_mode'] ) ? sanitize_key( wp_unslash( $_GET['kmae_mode'] ) ) : '';
	$simple = ( ! empty( $_GET['kmae_edit'] ) && 'full' !== $mode ) || ( ! empty( $_GET['kmae_draft'] ) && 'simple' === $mode );
	// phpcs:enable
	if ( $simple ) {
		wp_enqueue_style(
			'kmae-listing-simple',
			KMAE_URI . KMAE_CSS_BASE . 'listing-simple.css',
			array( 'kmae-listing-create' ),
			KMAE_THEME_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_listing_create_style', 100 );
