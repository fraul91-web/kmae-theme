<?php
/**
 * Dynamic nav: login/register when logged out, logout when logged in.
 * Works with classic menus, block navigation, and custom theme headers.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Nav {

	public function __construct() {
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_menu_items' ), 10, 2 );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_menu_items' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'filter_navigation_block' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'inline_nav_css' ), 99 );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'kmae-nav',
			KMAE_PLUGIN_URL . 'assets/js/nav.js',
			array(),
			KMAE_VERSION,
			true
		);
		wp_localize_script(
			'kmae-nav',
			'kmaeNav',
			array(
				'loggedIn'    => is_user_logged_in() ? 1 : 0,
				'logoutUrl'   => wp_logout_url( home_url( '/' ) ),
				'logoutLabel' => __( 'Kijelentkezés', 'ki-mit-adna-erte' ),
			)
		);
	}

	public function inline_nav_css() {
		?>
		<style id="kmae-nav-css">
			body.logged-in #header li:has(> a[href*="belepes"]),
			body.logged-in #header li:has(> a[href*="regisztracio"]),
			body.logged-in .mobile-menu li:has(> a[href*="belepes"]),
			body.logged-in .mobile-menu li:has(> a[href*="regisztracio"]),
			body.logged-in header li:has(> a[href*="belepes"]),
			body.logged-in header li:has(> a[href*="regisztracio"]),
			body.logged-in .kmae-hide-when-logged-in { display: none !important; }

			body:not(.logged-in) .kmae-header-logout-wrap { display: none !important; }

			/* Fekete menübeli duplikátum elrejtése */
			body.logged-in nav ul.menu li.kmae-nav-logout,
			body.logged-in #header-menu-1 li.kmae-nav-logout,
			body.logged-in li.kmae-nav-logout { display: none !important; }

			/* Blocksy: kék kijelentkezés jobb szél */
			body.logged-in #header [data-column="end"] [data-items="primary"] {
				display: flex !important;
				align-items: center;
				flex-wrap: wrap;
				gap: 0.75rem;
				width: 100%;
			}
			body.logged-in #header-menu-1 ul.menu {
				display: flex !important;
				flex-wrap: wrap;
				align-items: center;
				flex: 1 1 auto;
			}
			body.logged-in .kmae-header-logout-wrap {
				display: flex !important;
				align-items: center;
				margin-left: auto !important;
				order: 99;
			}
			body.logged-in .kmae-header-logout-wrap .kmae-nav-logout-link {
				font-weight: 600;
				text-decoration: none;
				white-space: nowrap;
				color: #6366f1;
			}
		</style>
		<?php
	}

	public function filter_menu_items( $items, $args ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return $items;
		}

		unset( $args );

		$logged_in  = is_user_logged_in();
		$filtered   = array();
		$has_logout = false;

		foreach ( $items as $item ) {
			if ( $this->is_login_item( $item ) || $this->is_register_item( $item ) ) {
				if ( ! $logged_in ) {
					$filtered[] = $item;
				}
				continue;
			}

			if ( $this->is_logout_item( $item ) ) {
				if ( $logged_in ) {
					$filtered[] = $item;
					$has_logout = true;
				}
				continue;
			}

			$filtered[] = $item;
		}

		if ( $logged_in && ! $has_logout ) {
			// Ne adjunk hozzá elemet a menülistához — a JS a jobb szélre teszi.
		}

		return $filtered;
	}

	public function filter_navigation_block( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/navigation-link' !== $block['blockName'] ) {
			return $block_content;
		}

		$url   = $block['attrs']['url'] ?? '';
		$label = $block['attrs']['label'] ?? '';

		$logged_in = is_user_logged_in();

		if ( $logged_in && ( $this->url_is_auth( $url, 'login' ) || $this->url_is_auth( $url, 'register' ) || $this->label_is_auth( $label, 'login' ) || $this->label_is_auth( $label, 'register' ) ) ) {
			return '';
		}

		if ( ! $logged_in && ( $this->label_is_auth( $label, 'logout' ) || str_contains( $url, 'action=logout' ) ) ) {
			return '';
		}

		return $block_content;
	}

	private function is_login_item( $item ) {
		$url   = is_object( $item ) && isset( $item->url ) ? $item->url : '';
		$title = is_object( $item ) && isset( $item->title ) ? $item->title : '';
		return $this->url_is_auth( $url, 'login' ) || $this->label_is_auth( $title, 'login' );
	}

	private function is_register_item( $item ) {
		$url   = is_object( $item ) && isset( $item->url ) ? $item->url : '';
		$title = is_object( $item ) && isset( $item->title ) ? $item->title : '';
		return $this->url_is_auth( $url, 'register' ) || $this->label_is_auth( $title, 'register' );
	}

	private function is_logout_item( $item ) {
		$url   = is_object( $item ) && isset( $item->url ) ? $item->url : '';
		$title = is_object( $item ) && isset( $item->title ) ? $item->title : '';
		return str_contains( $url, 'action=logout' ) || $this->label_is_auth( $title, 'logout' );
	}

	private function url_is_auth( $url, $type ) {
		$url = strtolower( (string) $url );
		if ( 'login' === $type ) {
			return str_contains( $url, 'belepes' );
		}
		if ( 'register' === $type ) {
			return str_contains( $url, 'regisztracio' );
		}
		return false;
	}

	private function label_is_auth( $label, $type ) {
		$normalized = remove_accents( mb_strtolower( trim( (string) $label ) ) );
		$map        = array(
			'login'    => array( 'bejelentkezés', 'bejelentkezes', 'login' ),
			'register' => array( 'regisztráció', 'regisztracio', 'register' ),
			'logout'   => array( 'kijelentkezés', 'kijelentkezes', 'logout' ),
		);
		foreach ( $map[ $type ] ?? array() as $needle ) {
			if ( $normalized === $needle || str_contains( $normalized, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	private function create_logout_item( $items ) {
		$parent_id = 0;
		if ( ! empty( $items ) ) {
			$parent_id = (int) $items[0]->menu_item_parent;
		}

		$item                   = new stdClass();
		$item->ID               = 999999001;
		$item->db_id            = 999999001;
		$item->menu_item_parent = $parent_id;
		$item->object_id        = 999999001;
		$item->post_parent      = 0;
		$item->type             = 'custom';
		$item->object           = 'custom';
		$item->type_label       = __( 'Egyedi hivatkozás', 'ki-mit-adna-erte' );
		$item->title            = __( 'Kijelentkezés', 'ki-mit-adna-erte' );
		$item->url              = wp_logout_url( home_url( '/' ) );
		$item->target           = '';
		$item->attr_title       = '';
		$item->description      = '';
		$item->classes          = array( 'kmae-nav-logout' );
		$item->xfn              = '';

		return $item;
	}
}
