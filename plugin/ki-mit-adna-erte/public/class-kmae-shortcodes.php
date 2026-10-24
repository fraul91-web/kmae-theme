<?php
/**
 * Frontend shortcodes.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Shortcodes {

	public function __construct() {
		add_shortcode( 'kmae_hero', array( $this, 'hero' ) );
		add_shortcode( 'kmae_landing_search', array( $this, 'landing_search' ) );
		add_shortcode( 'kmae_landing_popular', array( $this, 'landing_popular' ) );
		add_shortcode( 'kmae_landing_features', array( $this, 'landing_features' ) );
		add_shortcode( 'kmae_landing', array( $this, 'landing' ) );
		add_shortcode( 'kmae_login', array( $this, 'login' ) );
		add_shortcode( 'kmae_register', array( $this, 'register' ) );
		add_shortcode( 'kmae_create_listing', array( $this, 'create_listing' ) );
		add_shortcode( 'kmae_my_listings', array( $this, 'my_listings' ) );
		add_shortcode( 'kmae_browse', array( $this, 'browse' ) );
		add_shortcode( 'kmae_favorites', array( $this, 'favorites' ) );
		add_shortcode( 'kmae_profile', array( $this, 'profile' ) );
		add_shortcode( 'kmae_education', array( $this, 'education' ) );
		add_shortcode( 'kmae_settings', array( $this, 'settings' ) );
		add_shortcode( 'kmae_security', array( $this, 'security' ) );
		add_shortcode( 'kmae_faq', array( $this, 'faq' ) );
		add_shortcode( 'kmae_contact', array( $this, 'contact' ) );
		add_shortcode( 'kmae_terms', array( $this, 'terms' ) );
		add_shortcode( 'kmae_privacy', array( $this, 'privacy' ) );
		add_shortcode( 'kmae_notifications', array( $this, 'notifications' ) );
		add_shortcode( 'kmae_messages', array( $this, 'messages' ) );
		add_shortcode( 'kmae_hub_sell', array( $this, 'hub_sell' ) );
		add_shortcode( 'kmae_hub_buy', array( $this, 'hub_buy' ) );
		add_shortcode( 'kmae_hub_stats', array( $this, 'hub_stats' ) );
	}

	private function require_login( $redirect_message ) {
		if ( is_user_logged_in() ) {
			return false;
		}
		$login_url = KMAE_Auth::login_url( get_permalink() );
		ob_start();
		?>
		<div class="kmae-notice kmae-notice-info">
			<p><?php echo esc_html( $redirect_message ); ?></p>
			<p>
				<a href="<?php echo esc_url( $login_url ); ?>" class="kmae-btn kmae-btn-primary kmae-btn--login"><?php esc_html_e( 'Bejelentkezés', 'ki-mit-adna-erte' ); ?></a>
				<a href="<?php echo esc_url( KMAE_Auth::register_url() ); ?>" class="kmae-btn kmae-btn-secondary kmae-btn--register"><?php esc_html_e( 'Regisztráció', 'ki-mit-adna-erte' ); ?></a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	public function login() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/form-login.php';
		return ob_get_clean();
	}

	public function register() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/form-register.php';
		return ob_get_clean();
	}

	public function hero() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/hero.php';
		return ob_get_clean();
	}

	public function landing_features() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/landing-features.php';
		return ob_get_clean();
	}

	public function landing_search() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/landing-search.php';
		return ob_get_clean();
	}

	public function landing_popular() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/landing-popular.php';
		return ob_get_clean();
	}

	public function landing() {
		if ( function_exists( 'get_template_part' ) && locate_template( 'template-parts/landing.php' ) ) {
			ob_start();
			get_template_part( 'template-parts/landing' );
			return ob_get_clean();
		}
		return $this->hero() . $this->landing_search() . $this->landing_popular() . $this->landing_features();
	}

	public function create_listing() {
		if ( ! is_user_logged_in() ) {
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/partials/create-listing-guest.php';
			return ob_get_clean();
		}

		/* A hirdetésfeladás (új és szerkesztés is) megerősített e-mail címhez
		   kötött — böngészni, üzenetet küldeni eddig is lehet nélküle. */
		if ( KMAE_Auth::should_gate_for_verification( get_current_user_id() ) ) {
			$gate_context = 'listing';
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/partials/email-verify-gate.php';
			return ob_get_clean();
		}

		$edit_id = isset( $_GET['kmae_edit'] ) ? absint( $_GET['kmae_edit'] ) : 0;
		if ( $edit_id ) {
			$listing = get_post( $edit_id );
			if ( ! $listing || 'kmae_listing' !== $listing->post_type || ! KMAE_Listings::user_owns_listing( $edit_id, get_current_user_id() ) ) {
				return '<div class="kmae-notice kmae-notice-error"><p>' . esc_html__( 'Érvénytelen hirdetés, vagy nincs jogosultságod szerkeszteni.', 'ki-mit-adna-erte' ) . '</p></div>';
			}
			if ( ! KMAE_Listings::can_edit_listing( $edit_id ) ) {
				return '<div class="kmae-notice kmae-notice-error"><p>' . esc_html__( 'Ez a hirdetés archivált — jelenleg nem szerkeszthető.', 'ki-mit-adna-erte' ) . '</p><p><a href="' . esc_url( home_url( '/sajat-hirdetesek/' ) ) . '" class="kmae-btn kmae-btn-secondary">' . esc_html__( 'Vissza a hirdetéseimhez', 'ki-mit-adna-erte' ) . '</a></p></div>';
			}
			$mode = 'edit';
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/form-listing.php';
			return ob_get_clean();
		}

		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/form-create-listing.php';
		return ob_get_clean();
	}

	public function my_listings() {
		$login_msg = $this->require_login( __( 'Saját hirdetéseid megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}
		$listings = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'author'         => get_current_user_id(),
				'posts_per_page' => 50,
				'post_status'    => array( 'publish', 'draft' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		/* Piszkozatból csak a wizard félbehagyott hirdetései kellenek. */
		$listings = array_values(
			array_filter(
				$listings,
				static function ( $item ) {
					return 'draft' !== $item->post_status || KMAE_Meta::STATUS_DRAFT === KMAE_Meta::get_status( $item->ID );
				}
			)
		);
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/my-listings.php';
		return ob_get_clean();
	}

	public function browse( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 24,
				'category' => '',
				'type'     => '',
				'county'   => '',
				'body'     => '',
				'mode'     => 'swipe',
			),
			$atts,
			'kmae_browse'
		);
		$atts['exclude_contacted'] = true;
		/* A „Foglalt” címke változásakor ezt a címkét üríti a KMAE_Chat_Offers (vendég-cache). */
		do_action( 'litespeed_tag_add', KMAE_Chat_Offers::BROWSE_CACHE_TAG );

		$base_url = $this->get_browse_base_url();

		if ( KMAE_Vehicle::is_vehicle_launch() ) {
			return $this->browse_vehicle_wizard( $atts, $base_url );
		}

		$map_url = remove_query_arg( array( 'megye', 'tipus', 'kategoria' ), $base_url );

		if ( 'grid' === $atts['mode'] ) {
			if ( isset( $_GET['kategoria'] ) ) {
				$atts['category'] = sanitize_text_field( wp_unslash( $_GET['kategoria'] ) );
			}
			if ( isset( $_GET['tipus'] ) ) {
				$requested_type = sanitize_text_field( wp_unslash( $_GET['tipus'] ) );
				$atts['type'] = KMAE_OFFER_TYPE_CASH === $requested_type ? KMAE_OFFER_TYPE_CASH : '';
			}
			if ( isset( $_GET['megye'] ) && KMAE_Counties::ORSZAG !== $_GET['megye'] ) {
				$atts['county'] = sanitize_text_field( wp_unslash( $_GET['megye'] ) );
			}

			$listings = KMAE_Query::get_listings( $atts );
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse.php';
			return ob_get_clean();
		}

		$current_county = isset( $_GET['megye'] ) ? sanitize_text_field( wp_unslash( $_GET['megye'] ) ) : '';

		if ( empty( $current_county ) || ! KMAE_Counties::is_valid( $current_county ) ) {
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-map.php';
			return ob_get_clean();
		}

		$base_with_county = add_query_arg( 'megye', $current_county, remove_query_arg( array( 'tipus', 'kategoria' ), $base_url ) );

		if ( ! isset( $_GET['tipus'] ) ) {
			$base_url = $base_with_county;
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-type.php';
			return ob_get_clean();
		}

		$current_type   = sanitize_text_field( wp_unslash( $_GET['tipus'] ) );
		$current_type   = in_array( $current_type, array( 'mind', KMAE_OFFER_TYPE_CASH ), true ) ? $current_type : 'mind';
		$base_with_type = add_query_arg( 'tipus', $current_type, $base_with_county );
		$type_step_url  = $base_with_county;

		if ( KMAE_Launch::should_skip_category_wizard() ) {
			$current_cat = KMAE_Launch::category_slug();
		} elseif ( ! isset( $_GET['kategoria'] ) ) {
			$base_url = $base_with_type;
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-category.php';
			return ob_get_clean();
		} else {
			$current_cat = sanitize_text_field( wp_unslash( $_GET['kategoria'] ) );
		}

		$atts['county']   = KMAE_Counties::ORSZAG === $current_county ? '' : $current_county;
		$atts['type']     = 'mind' === $current_type ? '' : $current_type;
		$atts['category'] = 'mind' === $current_cat ? '' : $current_cat;

		$listings = KMAE_Query::get_listings( $atts );

		$wizard_summary  = array(
			'county'   => $current_county,
			'type'     => $current_type,
			'category' => $current_cat,
		);
		$browse_base_url = $map_url;

		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/browse-swipe.php';
		return ob_get_clean();
	}

	private function browse_vehicle_wizard( $atts, $base_url ) {
		$wizard_keys = array(
			'nezet',
			'oldal',
			'rendez',
			'lat',
			'lon',
			'km',
			'megye',
			'kivitel',
			'mod',
			'kp_min',
			'kp_max',
			'step',
			'marka',
			'modell',
			'tilt',
			'ccm_min',
			'ccm_max',
			'le_min',
			'le_max',
			'ev_min',
			'ev_max',
			'km_min',
			'km_max',
			'uzem',
			'valto',
			'muszaki',
			'mot_min',
			'mot_max',
			'mot_lejart',
			'allapot',
			'ar',
			'osszes',
		);
		$map_url     = remove_query_arg( $wizard_keys, $base_url );
		$show_all    = isset( $_GET['osszes'] ) && '1' === sanitize_key( wp_unslash( $_GET['osszes'] ) );

		/* Böngészés v2: a lista és a kártyás nézet adata az indexből jön (browse-swipe.php), a régi lekérés (akár 1000 hirdetés) elmarad. */
		$list_from_index = KMAE_Browse_Feed::use_list_index() || KMAE_Browse_Feed::use_card_index();

		if ( $show_all ) {
			$listings = array();
			if ( ! $list_from_index ) {
				$listings = KMAE_Query::get_listings(
					array(
						'per_page'          => KMAE_Browse::needs_full_scan() ? KMAE_Browse::LIST_SCAN : $atts['per_page'],
						'exclude_contacted' => true,
					)
				);
				$listings = KMAE_Browse::apply_sort( $listings );
			}
			$wizard_summary  = array(
				'county' => '',
				'bodies' => array(),
				'mod'    => '',
			);
			$browse_base_url = $map_url;
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-swipe.php';
			return ob_get_clean();
		}

		$wizard          = KMAE_Browse::parse_wizard_params();
		$force_body_step = isset( $_GET['step'] ) && 'kivitel' === sanitize_key( wp_unslash( $_GET['step'] ) );

		if ( empty( $wizard['county'] ) || ! KMAE_Browse::counties_are_valid( $wizard['county'] ) ) {
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-map.php';
			return ob_get_clean();
		}

		$base_with_county = add_query_arg( 'megye', $wizard['county'], remove_query_arg( array( 'kivitel', 'mod', 'kp_min', 'kp_max', 'step' ), $base_url ) );

		if ( empty( $wizard['bodies'] ) || $force_body_step ) {
			$base_url       = $base_with_county;
			$current_county = $wizard['county'];
			$current_bodies = $wizard['bodies'];
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-body.php';
			return ob_get_clean();
		}

		if ( ! KMAE_Browse::is_wizard_complete( $wizard ) ) {
			$base_url       = add_query_arg( 'kivitel', KMAE_Browse::bodies_to_query_value( $wizard['bodies'] ), $base_with_county );
			$body_step_url  = add_query_arg(
				array(
					'kivitel' => KMAE_Browse::bodies_to_query_value( $wizard['bodies'] ),
					'step'    => 'kivitel',
				),
				$base_with_county
			);
			$current_county = $wizard['county'];
			$current_bodies = $wizard['bodies'];
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/browse-wizard-offer.php';
			return ob_get_clean();
		}

		$county_slugs = KMAE_Browse::county_slugs( $wizard['county'] );
		$query_args   = array(
			'per_page' => KMAE_Browse::needs_full_scan( $wizard['mod'] ?? '' ) ? KMAE_Browse::LIST_SCAN : $this->browse_scan_size( $atts, $wizard ),
			'county'   => 1 === count( $county_slugs ) ? $county_slugs[0] : '',
			'bodies'   => $wizard['bodies'],
			'exclude_contacted' => true,
		);

		if ( $list_from_index ) {
			$listings = array();
		} else {
			$listings = KMAE_Query::get_listings( $query_args );
			$listings = KMAE_Browse::filter_by_wizard( $listings, $wizard );
			$listings = KMAE_Browse::apply_sort( $listings, $wizard['mod'] ?? '' );
		}

		$wizard_summary  = $wizard;
		$browse_base_url = $map_url;

		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/browse-swipe.php';
		return ob_get_clean();
	}

	/**
	 * Fine filters need the whole active set, not only the first swipe page.
	 *
	 * @param array $atts   Shortcode attrs.
	 * @param array $wizard Parsed wizard.
	 * @return int
	 */
	private function browse_scan_size( $atts, array $wizard ) {
		$keys = array( 'brand', 'model', 'blocked', 'cc_min', 'cc_max', 'hp_min', 'hp_max', 'year_min', 'year_max', 'km_min', 'km_max', 'fuel', 'gear', 'conditions', 'prices' );
		foreach ( $keys as $key ) {
			if ( ! empty( $wizard[ $key ] ) ) {
				return 500;
			}
		}
		if ( ! empty( $wizard['mot'] ) && 'mind' !== $wizard['mot'] ) {
			return 500;
		}
		if ( count( KMAE_Browse::county_slugs( $wizard['county'] ?? '' ) ) > 1 ) {
			return 500;
		}
		return (int) $atts['per_page'];
	}

	private function get_browse_base_url() {
		return class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
	}

	public function favorites() {
		$login_msg = $this->require_login( __( 'A kedvencek megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}

		$listings   = KMAE_Browse::favorite_listings( get_current_user_id() );
		$browse_url = $this->get_browse_base_url();
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/favorites.php';
		$html = ob_get_clean();
		KMAE_Browse::mark_favorites_seen( get_current_user_id() );
		return $html;
	}

	public function profile() {
		// Csak a véletlen profil-kulcs nyit meg más profilt; sorszámmal (régi ?kmae_user=) semmit.
		$view_key = isset( $_GET[ KMAE_Pages::PUBLIC_PROFILE_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ KMAE_Pages::PUBLIC_PROFILE_ARG ] ) ) : '';
		$view_id  = '' !== $view_key ? KMAE_Pages::user_by_public_key( $view_key ) : 0;
		if ( $view_id > 0 && get_userdata( $view_id ) ) {
			$history_user_id = $view_id;
			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/public-profile.php';
			return ob_get_clean();
		}

		$login_msg = $this->require_login( __( 'Profil megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/form-profile.php';
		return ob_get_clean();
	}

	public function education() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/education.php';
		return ob_get_clean();
	}

	public function settings() {
		$login_msg = $this->require_login( __( 'A beállítások megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/settings.php';
		return ob_get_clean();
	}

	public function security() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/security.php';
		return ob_get_clean();
	}

	public function faq() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/faq.php';
		return ob_get_clean();
	}

	public function contact() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/contact.php';
		return ob_get_clean();
	}

	public function terms() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/terms.php';
		return ob_get_clean();
	}

	public function privacy() {
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/privacy.php';
		return ob_get_clean();
	}

	/**
	 * Értesítések page: kereskedői megkeresések (cég: beérkezett, eladó: elküldött).
	 * A kapcsoló (KMAE_Dealer_Leads::is_enabled) nélkül az oldal szándékosan üres marad.
	 */
	public function notifications() {
		if ( ! class_exists( 'KMAE_Dealer_Leads' ) || ! KMAE_Dealer_Leads::is_enabled() ) {
			return '';
		}
		$login_msg = $this->require_login( __( 'Az értesítések megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}

		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/notifications.php';
		return ob_get_clean();
	}

	public function messages() {
		$login_msg = $this->require_login( __( 'Az üzenetek megtekintéséhez be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}

		$user_id         = get_current_user_id();
		$conversation_id = isset( $_GET['kmae_conversation'] ) ? absint( $_GET['kmae_conversation'] ) : 0;

		if ( $conversation_id ) {
			$conversation = KMAE_Conversations::get( $conversation_id );
			if ( ! $conversation || ! KMAE_Conversations::is_member( $conversation, $user_id ) ) {
				return '<div class="kmae-notice kmae-notice-error"><p>' . esc_html__( 'Ez a beszélgetés nem elérhető.', 'ki-mit-adna-erte' ) . '</p><p><a href="' . esc_url( KMAE_Pages::url( 'uzenetek' ) ) . '" class="kmae-btn kmae-btn-secondary">' . esc_html__( 'Vissza az üzenetekhez', 'ki-mit-adna-erte' ) . '</a></p></div>';
			}

			if ( KMAE_Sale_Contract::is_contract_request() ) {
				return KMAE_Sale_Contract::render( $conversation, $user_id );
			}

			KMAE_Database::mark_conversation_thread_seen( $user_id, $conversation );

			ob_start();
			include KMAE_PLUGIN_DIR . 'templates/offer-thread.php';
			return ob_get_clean();
		}

		$inbox = KMAE_Conversations::get_inbox_for_user( $user_id );
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/messages.php';
		return ob_get_clean();
	}

	public function hub_sell() {
		return $this->render_hub_section( 'sell' );
	}

	public function hub_buy() {
		return $this->render_hub_section( 'buy' );
	}

	public function hub_stats() {
		return $this->render_hub_section( 'stats' );
	}

	/**
	 * Hub submenu pages (Eladni / Venni / Statisztikák).
	 *
	 * @param string $mod sell|buy|stats.
	 * @return string
	 */
	private function render_hub_section( $mod ) {
		$login_msg = $this->require_login( __( 'Ehhez a menühöz be kell jelentkezned.', 'ki-mit-adna-erte' ) );
		if ( $login_msg ) {
			return $login_msg;
		}

		$dash  = class_exists( 'KMAE_Dashboard' ) ? KMAE_Dashboard::get_data( get_current_user_id() ) : array();
		$urls  = isset( $dash['urls'] ) ? $dash['urls'] : array();
		$stats = isset( $dash['stats'] ) ? $dash['stats'] : array();

		$sections = array(
			'sell'  => array(
				'mod'   => 'sell',
				'title' => __( 'Eladni', 'ki-mit-adna-erte' ),
				'lead'  => __( 'Mi legyen a következő lépés?', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'label' => __( 'Új hirdetés feladása', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Adatok, fotók, ár – pár perc alatt készíts professzionális hirdetést.', 'ki-mit-adna-erte' ),
						'url'   => $urls['create'] ?? home_url( '/hirdetes-feladasa/' ),
						'cta'   => __( 'Hirdetés feladása', 'ki-mit-adna-erte' ),
						'icon'  => 'plus',
						'tone'  => 'sell',
					),
					array(
						'label' => __( 'Hirdetéseim', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Szerkeszd, kezeld és kövesd nyomon az aktív hirdetéseidet.', 'ki-mit-adna-erte' ),
						'url'   => $urls['listings'] ?? home_url( '/sajat-hirdetesek/' ),
						'cta'   => __( 'Hirdetések kezelése', 'ki-mit-adna-erte' ),
						'icon'  => 'clipboard-list',
						'badge' => ! empty( $stats['listings'] ) ? (string) (int) $stats['listings'] : '',
						'tone'  => 'buy',
					),
				),
			),
			'buy'   => array(
				'mod'   => 'buy',
				'title' => __( 'Venni', 'ki-mit-adna-erte' ),
				'lead'  => __( 'Mit szeretnél csinálni?', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'label' => __( 'Autók böngészése', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Szűrj megyére, kivitelre és árra.', 'ki-mit-adna-erte' ),
						'url'   => $urls['browse'] ?? home_url( '/hirdetesek/' ),
						'cta'   => __( 'Böngészés indítása', 'ki-mit-adna-erte' ),
						'icon'  => 'search',
					),
					array(
						'label' => __( 'Üzenetek', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Egyeztetés chatben.', 'ki-mit-adna-erte' ),
						'url'   => $urls['messages'] ?? home_url( '/uzenetek/' ),
						'cta'   => __( 'Üzenetek megnyitása', 'ki-mit-adna-erte' ),
						'icon'  => 'chat',
					),
				),
			),
			'stats' => array(
				'mod'   => 'stats',
				'title' => __( 'Statisztikák', 'ki-mit-adna-erte' ),
				'lead'  => __( 'Mit szeretnél csinálni?', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'label' => __( 'Értesítések', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Válaszok, fontos lépések.', 'ki-mit-adna-erte' ),
						'url'   => $urls['notify'] ?? home_url( '/ertesitesek/' ),
						'cta'   => __( 'Értesítések megnyitása', 'ki-mit-adna-erte' ),
						'icon'  => 'bell',
					),
					array(
						'label' => __( 'Profilom', 'ki-mit-adna-erte' ),
						'desc'  => __( 'Adatok, megjelenés.', 'ki-mit-adna-erte' ),
						'url'   => $urls['profile'] ?? home_url( '/profil/' ),
						'cta'   => __( 'Profil megnyitása', 'ki-mit-adna-erte' ),
						'icon'  => 'user',
					),
					array(
						'label' => __( 'Beállítások', 'ki-mit-adna-erte' ),
						'desc'  => __( 'E-mail, jelszó, belépés.', 'ki-mit-adna-erte' ),
						'url'   => $urls['settings'] ?? home_url( '/beallitasok/' ),
						'cta'   => __( 'Beállítások', 'ki-mit-adna-erte' ),
						'icon'  => 'gear',
					),
				),
			),
		);

		$hub = $sections[ $mod ] ?? $sections['sell'];

		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/hub-section.php';
		return ob_get_clean();
	}

}
