<?php
/**
 * Required frontend pages and content injection fallback.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Pages {

	const PAGES = array(
		'belepes'           => array(
			'title'     => 'Bejelentkezés',
			'shortcode' => 'kmae_login',
			'template'  => 'form-login.php',
		),
		'regisztracio'      => array(
			'title'     => 'Regisztráció',
			'shortcode' => 'kmae_register',
			'template'  => 'form-register.php',
		),
		'hirdetes-feladasa' => array(
			'title'     => 'Hirdetés feladása',
			'shortcode' => 'kmae_create_listing',
		),
		'sajat-hirdetesek'  => array(
			'title'     => 'Saját hirdetéseim',
			'shortcode' => 'kmae_my_listings',
		),
		'hirdetesek'        => array(
			'title'     => 'Böngészés',
			'shortcode' => 'kmae_browse',
		),
		'kedvencek'         => array(
			'title'     => 'Kedvencek',
			'shortcode' => 'kmae_favorites',
		),
		'kezdolap'          => array(
			'title'     => 'Kezdőlap',
			'shortcode' => 'kmae_landing',
		),
		'profil'            => array(
			'title'     => 'Profil',
			'shortcode' => 'kmae_profile',
		),
		'oktatas'           => array(
			'title'     => 'Oktatás',
			'shortcode' => 'kmae_education',
		),
		'beallitasok'       => array(
			'title'     => 'Beállítások',
			'shortcode' => 'kmae_settings',
		),
		'biztonsag'         => array(
			'title'     => 'Biztonság és bizalom',
			'shortcode' => 'kmae_security',
		),
		'gyik'              => array(
			'title'     => 'Gyakori kérdések',
			'shortcode' => 'kmae_faq',
		),
		'kapcsolat'         => array(
			'title'     => 'Kapcsolat',
			'shortcode' => 'kmae_contact',
		),
		'feltetelek'        => array(
			'title'     => 'ÁSZF és Felhasználási feltételek',
			'shortcode' => 'kmae_terms',
		),
		'adatvedelem'       => array(
			'title'     => 'Adatvédelem',
			'shortcode' => 'kmae_privacy',
		),
		'ertesitesek'       => array(
			'title'     => 'Értesítések',
			'shortcode' => 'kmae_notifications',
		),
		'uzenetek'          => array(
			'title'     => 'Üzenetek',
			'shortcode' => 'kmae_messages',
		),
		'eladni'            => array(
			'title'     => 'Eladni',
			'shortcode' => 'kmae_hub_sell',
		),
		'venni'             => array(
			'title'     => 'Venni',
			'shortcode' => 'kmae_hub_buy',
		),
		'statisztikak'      => array(
			'title'     => 'Statisztikák, értesítések',
			'shortcode' => 'kmae_hub_stats',
		),
	);

	const MESSAGES_PAGE_SLUG = 'uzenetek';

	public function __construct() {
		add_filter( 'the_content', array( $this, 'inject_page_content' ), 9 );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'mark_conversation_seen_on_visit' ), 1 );
		add_filter( 'body_class', array( __CLASS__, 'offer_thread_body_class' ) );
		add_action( 'init', array( __CLASS__, 'maybe_ensure_pages' ), 20 );
	}

	/**
	 * Keep browse wizard filters on canonical page URLs.
	 *
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public static function register_query_vars( $vars ) {
		return array_merge(
			$vars,
			array(
				'megye',
				'kivitel',
				'mod',
				'kp_min',
				'kp_max',
				'step',
				'tipus',
				'kategoria',
			)
		);
	}

	/**
	 * Create/update shortcode pages after plugin updates (without re-activation).
	 */
	public static function maybe_ensure_pages() {
		self::remove_retired_pages();
		$stored = get_option( 'kmae_pages_version', '' );
		if ( $stored === KMAE_VERSION ) {
			return;
		}
		self::ensure_pages();
		update_option( 'kmae_pages_version', KMAE_VERSION );
	}

	/**
	 * Megszűnt oldalak végleges törlése (a „Hogyan működik?” oldal már nem létezik).
	 */
	public static function remove_retired_pages() {
		if ( get_option( 'kmae_retired_pages_removed' ) ) {
			return;
		}
		$page = get_page_by_path( 'hogyan-mukodik' );
		if ( $page instanceof WP_Post ) {
			wp_delete_post( $page->ID, true );
		}
		update_option( 'kmae_retired_pages_removed', 1 );
	}

	/**
	 * Full-page chat room styling when viewing a single conversation.
	 */
	public static function offer_thread_body_class( $classes ) {
		if ( ! is_user_logged_in() || ! self::is_messages_page() ) {
			return $classes;
		}

		$conversation_id = isset( $_GET['kmae_conversation'] ) ? absint( $_GET['kmae_conversation'] ) : 0;
		if ( $conversation_id ) {
			$classes[] = KMAE_Sale_Contract::is_contract_request() ? 'kmae-sale-contract-active' : 'kmae-offer-thread-active';
		}

		return $classes;
	}

	/**
	 * Mark a conversation as read when opened (before the header badge renders).
	 */
	public static function mark_conversation_seen_on_visit() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$conversation_id = isset( $_GET['kmae_conversation'] ) ? absint( $_GET['kmae_conversation'] ) : 0;

		if ( ! $conversation_id || ! self::is_messages_page() ) {
			return;
		}

		$user_id      = get_current_user_id();
		$conversation = class_exists( 'KMAE_Conversations' ) ? KMAE_Conversations::get( $conversation_id ) : null;
		if ( $conversation && KMAE_Conversations::is_member( $conversation, $user_id ) ) {
			KMAE_Database::mark_conversation_thread_seen( $user_id, $conversation );
		}
	}

	/**
	 * Whether the current request is the Üzenetek page.
	 */
	public static function is_messages_page() {
		return is_page( self::MESSAGES_PAGE_SLUG );
	}

	public static function browse_page_url() {
		return self::url( 'hirdetesek' );
	}

	public static function messages_page_url() {
		return self::url( self::MESSAGES_PAGE_SLUG );
	}

	public static function conversation_thread_url( $conversation_id ) {
		return add_query_arg( 'kmae_conversation', absint( $conversation_id ), self::messages_page_url() );
	}

	/**
	 * Nyilvános előzményprofil: ugyanazok az Albira-adatok, mint a profilkártyán.
	 *
	 * @param int $user_id User.
	 * @return string
	 */
	public static function public_profile_url( $user_id ) {
		$user_id = (int) $user_id;
		$url     = self::url( 'profil' );
		$key     = self::public_profile_key( $user_id );
		if ( '' === $key ) {
			return $url;
		}
		return add_query_arg( self::PUBLIC_PROFILE_ARG, $key, $url );
	}

	/** Linkparaméter: véletlen kulcs, a felhasználó sorszáma soha nem kerül a címbe. */
	const PUBLIC_PROFILE_ARG = 'kmae_p';

	const PUBLIC_PROFILE_META = '_kmae_public_key';

	/**
	 * Felhasználónként egyszer generált, kitalálhatatlan profil-kulcs.
	 *
	 * @param int $user_id User.
	 * @return string
	 */
	public static function public_profile_key( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return '';
		}
		$key = (string) get_user_meta( $user_id, self::PUBLIC_PROFILE_META, true );
		if ( ! preg_match( '/^[a-z0-9]{24}$/', $key ) ) {
			$key = strtolower( wp_generate_password( 24, false ) );
			update_user_meta( $user_id, self::PUBLIC_PROFILE_META, $key );
		}
		return $key;
	}

	/**
	 * @param string $key Profil-kulcs a linkből.
	 * @return int User ID vagy 0.
	 */
	public static function user_by_public_key( $key ) {
		$key = strtolower( (string) $key );
		if ( ! preg_match( '/^[a-z0-9]{24}$/', $key ) ) {
			return 0;
		}
		$ids = get_users(
			array(
				'meta_key'   => self::PUBLIC_PROFILE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Permalink for a registered shortcode page (by slug key in PAGES).
	 * Falls back to /{slug}/ if the page is missing.
	 *
	 * @param string $slug Page slug key from self::PAGES.
	 * @return string
	 */
	public static function url( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return home_url( '/' );
		}

		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			$link = get_permalink( $page );
			if ( $link ) {
				return $link;
			}
		}

		return home_url( '/' . $slug . '/' );
	}

	/**
	 * Shortcode tag for a registered page slug, or empty string.
	 *
	 * @param string $slug Page slug.
	 * @return string
	 */
	public static function shortcode_for( $slug ) {
		return isset( self::PAGES[ $slug ]['shortcode'] ) ? self::PAGES[ $slug ]['shortcode'] : '';
	}

	public static function ensure_pages() {
		foreach ( self::PAGES as $slug => $config ) {
			self::ensure_page( $slug, $config['title'], $config['shortcode'] );
		}
		self::ensure_front_page();
	}

	/**
	 * Set dedicated landing page as site front page; browse lives at /hirdetesek/.
	 */
	public static function ensure_front_page() {
		self::ensure_page( 'kezdolap', 'Kezdőlap', 'kmae_landing' );

		$landing = get_page_by_path( 'kezdolap' );
		if ( ! $landing ) {
			return;
		}

		$current_front = (int) get_option( 'page_on_front', 0 );
		if ( $current_front === (int) $landing->ID ) {
			return;
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $landing->ID );
	}

	private static function ensure_page( $slug, $title, $shortcode ) {
		$existing = get_page_by_path( $slug );
		$content  = '[' . $shortcode . ']';

		if ( $existing ) {
			if ( $existing->post_title !== $title ) {
				wp_update_post(
					array(
						'ID'         => $existing->ID,
						'post_title' => $title,
					)
				);
			}
			// Fix pages where shortcode shows as plain text (wrong block type).
			if ( false === strpos( $existing->post_content, $shortcode ) ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
					)
				);
			}
			return $existing->ID;
		}

		return wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
	}

	public function inject_page_content( $content ) {
		if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post || ! isset( self::PAGES[ $post->post_name ] ) ) {
			return $content;
		}

		$config = self::PAGES[ $post->post_name ];

		// Auth pages: always render template directly (works even if shortcode block fails).
		if ( ! empty( $config['template'] ) ) {
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/' . $config['template'];
			return ob_get_clean();
		}

		$shortcode = $config['shortcode'];

		// Hub menus must always render — don't rely on stored page content alone.
		if ( in_array( $shortcode, array( 'kmae_hub_sell', 'kmae_hub_buy', 'kmae_hub_stats' ), true ) ) {
			return do_shortcode( '[' . $shortcode . ']' );
		}

		// Other pages: force shortcode processing if content looks broken.
		if ( has_shortcode( $content, $shortcode ) ) {
			return do_shortcode( $content );
		}

		if ( false !== strpos( $content, '[' . $shortcode . ']' ) ) {
			return do_shortcode( '[' . $shortcode . ']' );
		}

		return $content;
	}
}
