<?php
/**
 * Albira theme functions.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KMAE_THEME_VERSION', '1.17.66' );
if ( ! defined( 'KMAE_VERSION' ) ) {
	define( 'KMAE_VERSION', KMAE_THEME_VERSION );
}
define( 'KMAE_DIR', get_template_directory() );
define( 'KMAE_URI', get_template_directory_uri() );

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

/**
 * Body classes: app shell when logged in.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function kmae_body_classes( $classes ) {
	if ( is_user_logged_in() ) {
		$classes[] = 'kmae-app';
	} else {
		$classes[] = 'kmae-marketing';
	}
	if ( is_page() ) {
		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
		if ( 'hirdetes-feladasa' === $slug ) {
			$classes[] = 'kmae-listing-create-page';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'kmae_body_classes' );

/**
 * Enqueue styles and scripts.
 */
function kmae_assets() {
	$font_family = is_user_logged_in()
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

	if ( is_user_logged_in() ) {
		wp_enqueue_style(
			'kmae-app',
			KMAE_URI . '/assets/css/app.css',
			array( 'kmae-theme' ),
			KMAE_THEME_VERSION
		);
		wp_enqueue_style(
			'kmae-pc-dashboard',
			KMAE_URI . '/assets/css/pc-dashboard.css',
			array( 'kmae-app' ),
			KMAE_THEME_VERSION
		);
		wp_enqueue_style(
			'kmae-phone',
			KMAE_URI . '/assets/css/phone.css',
			array( 'kmae-pc-dashboard' ),
			KMAE_THEME_VERSION
		);
	} else {
		wp_enqueue_style(
			'kmae-landing',
			KMAE_URI . '/assets/css/landing.css',
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
 * Light app chrome last, so plugin public.css white-on-dark copy can be
 * overridden only on the logged-in canvas.
 */
function kmae_enqueue_app_shell_light() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	wp_enqueue_style(
		'kmae-app-shell-light',
		KMAE_URI . '/assets/css/app-shell-light.css',
		array( 'kmae-phone' ),
		KMAE_THEME_VERSION
	);
	wp_enqueue_style(
		'kmae-header',
		KMAE_URI . '/assets/css/header.css',
		array( 'kmae-app-shell-light' ),
		KMAE_THEME_VERSION
	);

	if ( is_page( 'hirdetes-feladasa' ) ) {
		wp_enqueue_style(
			'kmae-listing-create',
			KMAE_URI . '/assets/css/listing-create.css',
			array( 'kmae-app-shell-light' ),
			KMAE_THEME_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kmae_enqueue_app_shell_light', 99 );

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
 * @param string $key logo|hero_landscape|hero_portrait|cta_landscape|cta_portrait.
 * @return string
 */
function kmae_media_url( $key ) {
	$guest = class_exists( 'KMAE_Brand' ) ? KMAE_Brand::logo_guest() : 'https://albira.hu/wp-content/uploads/2026/08/albira-logo-belepes-elott.webp';
	$app   = class_exists( 'KMAE_Brand' ) ? KMAE_Brand::logo_app() : 'https://albira.hu/wp-content/uploads/2026/08/albira-logo-belepes-utan.webp';

	static $urls = null;
	if ( null === $urls ) {
		$urls = array(
			'logo_guest'     => $guest,
			'logo_app'       => $app,
			'hero_landscape' => 'https://albira.hu/wp-content/uploads/2026/08/hero-vizszintes.webp',
			'hero_portrait'  => 'https://albira.hu/wp-content/uploads/2026/08/allo-hero.webp',
			'cta_landscape'  => 'https://albira.hu/wp-content/uploads/2026/08/fekvo-fotter.webp',
			'cta_portrait'   => 'https://albira.hu/wp-content/uploads/2026/08/allo-footer.webp',
		);
	}

	$urls['logo'] = is_user_logged_in() ? $app : $guest;

	return isset( $urls[ $key ] ) ? $urls[ $key ] : '';
}

/**
 * Current logo URL: guest on the landing, official after login.
 *
 * @param string $variant guest|app|auto.
 * @return string
 */
function kmae_logo_url( $variant = 'auto' ) {
	if ( class_exists( 'KMAE_Brand' ) ) {
		return KMAE_Brand::logo_url( $variant );
	}

	return kmae_media_url( 'guest' === $variant ? 'logo_guest' : ( 'app' === $variant ? 'logo_app' : 'logo' ) );
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
		$unseen = (int) KMAE_Database::count_unseen_offer_activity( get_current_user_id() );
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
				'label'       => __( 'Hirdetéseim', 'kmae' ),
				'url'         => kmae_page_url( 'sajat-hirdetesek' ),
				'active'      => $is( 'sajat-hirdetesek' ) || $is( 'hirdetes-feladasa' ),
				'icon'        => 'clipboard-list',
				'badge'       => $listings > 0 ? (string) $listings : '',
				'badge_class' => 'is-soft',
			),
			array(
				'label'  => __( 'Ajánlataim', 'kmae' ),
				'url'    => kmae_page_url( 'ajanlatok' ),
				'active' => $is( 'ajanlatok' ) || isset( $_GET['kmae_offer'] ),
				'icon'   => 'handshake',
				'badge'  => $unseen > 0 ? (string) $unseen : '',
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
				'label'  => __( 'Szupertag', 'kmae' ),
				'url'    => kmae_page_url( 'profil' ),
				'active' => false,
				'icon'   => 'star',
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
				'label'  => __( 'Rólunk', 'kmae' ),
				'url'    => kmae_page_url( 'rolunk' ),
				'active' => $is( 'rolunk' ),
				'icon'   => 'info',
			),
			array(
				'label'  => __( 'Hogyan működik?', 'kmae' ),
				'url'    => kmae_page_url( 'hogyan-mukodik' ),
				'active' => $is( 'hogyan-mukodik' ),
				'icon'   => 'book-open',
			),
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
