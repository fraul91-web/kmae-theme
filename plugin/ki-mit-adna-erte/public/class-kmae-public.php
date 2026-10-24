<?php
/**
 * Frontend assets and form handlers.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		/* A plugin saját CSS/JS fájljai a módosítási idejüket kapják `ver`-nek:
		   feltöltés után a böngésző és a Cloudflare sem adhat régi fájlt. */
		add_filter( 'style_loader_src', array( __CLASS__, 'asset_version_src' ), 20 );
		add_filter( 'script_loader_src', array( __CLASS__, 'asset_version_src' ), 20 );
		add_action( 'init', array( $this, 'handle_forms' ) );
		add_action( 'wp_ajax_kmae_browse_pass', array( $this, 'ajax_browse_pass' ) );
		add_action( 'wp_ajax_kmae_report_listing', array( $this, 'ajax_report_listing' ) );
		add_action( 'wp_ajax_kmae_favorite_add', array( $this, 'ajax_favorite_add' ) );
		add_action( 'wp_ajax_kmae_favorite_remove', array( $this, 'ajax_favorite_remove' ) );
		add_action( 'wp_ajax_kmae_poll_page', array( $this, 'ajax_poll_page' ) );
		add_action( 'admin_post_kmae_save_profile', array( $this, 'admin_save_profile' ) );
		add_action( 'admin_post_kmae_delete_account', array( $this, 'admin_delete_account' ) );
		add_action( 'admin_post_kmae_update_listing', array( $this, 'admin_update_listing' ) );
		add_action( 'admin_post_kmae_delete_listing', array( $this, 'admin_delete_listing' ) );
		add_action( 'admin_post_kmae_reactivate_listing', array( $this, 'admin_reactivate_listing' ) );
		add_action( 'admin_post_kmae_renew_listing', array( $this, 'admin_renew_listing' ) );
		add_action( 'template_redirect', array( $this, 'block_nonpublic_listings' ) );
		add_action( 'template_redirect', array( $this, 'nocache_live_pages' ), 0 );
		add_action( 'admin_post_kmae_login', array( $this, 'admin_login' ) );
		add_action( 'admin_post_nopriv_kmae_login', array( $this, 'admin_login' ) );
		add_action( 'admin_post_kmae_register', array( $this, 'admin_register' ) );
		add_action( 'admin_post_nopriv_kmae_register', array( $this, 'admin_register' ) );
		add_action( 'admin_post_kmae_lost_password', array( $this, 'admin_lost_password' ) );
		add_action( 'admin_post_nopriv_kmae_lost_password', array( $this, 'admin_lost_password' ) );
		add_action( 'admin_post_kmae_reset_password', array( $this, 'admin_reset_password' ) );
		add_action( 'admin_post_nopriv_kmae_reset_password', array( $this, 'admin_reset_password' ) );
		add_action( 'admin_post_kmae_contact_form', array( $this, 'admin_contact_form' ) );
		add_action( 'admin_post_nopriv_kmae_contact_form', array( $this, 'admin_contact_form' ) );
		add_action( 'admin_post_kmae_save_settings', array( $this, 'admin_save_settings' ) );
		add_action( 'admin_post_kmae_save_mail_prefs', array( $this, 'admin_save_mail_prefs' ) );
		add_action( 'admin_post_kmae_change_email', array( $this, 'admin_change_email' ) );
		add_action( 'admin_post_kmae_change_password', array( $this, 'admin_change_password' ) );
		add_action( 'admin_post_kmae_logout_everywhere', array( $this, 'admin_logout_everywhere' ) );
	}

	public function enqueue() {
		/* A telefonmező-kezelő a belépés/regisztráció oldalon is kell, ahol a
		   többi közös eszközt szándékosan nem töltjük be. */
		wp_enqueue_script(
			'kmae-phone-field',
			KMAE_PLUGIN_URL . 'assets/js/phone-field.js',
			array(),
			KMAE_VERSION,
			true
		);

		if ( $this->is_register_page() ) {
			wp_enqueue_script(
				'kmae-auth-account-type',
				KMAE_PLUGIN_URL . 'assets/js/auth-account-type.js',
				array(),
				KMAE_VERSION,
				true
			);
			/* Céges telephely: irányítószám + település-lista (a Google-kiegészítő űrlap is itt jelenik meg). */
			KMAE_Settlements::enqueue_picker();
		}

		if ( ! $this->should_enqueue_public_assets() ) {
			return;
		}

		/* Település-választó: hirdetés-űrlap (megye + város) és profil (céges telephely). */
		$picker_post = get_post();
		if ( $this->is_create_listing_page() || ( is_user_logged_in() && $picker_post && has_shortcode( (string) $picker_post->post_content, 'kmae_profile' ) ) ) {
			KMAE_Settlements::enqueue_picker();
		}

		$public_deps = array();
		if ( KMAE_Vehicle::is_vehicle_launch() && $this->is_create_listing_page() ) {
			wp_enqueue_script( 'kmae-listing-form-numbers', KMAE_PLUGIN_URL . 'assets/js/listing-form-numbers.js', array(), KMAE_VERSION, true );
			$public_deps[] = 'kmae-listing-form-numbers';

			/* „Jelenlegi helyzetem megadása” — wizard és egyszerű szerkesztő is. */
			wp_enqueue_script( 'kmae-listing-locate', KMAE_PLUGIN_URL . 'assets/js/listing-locate.js', array(), KMAE_VERSION, true );
			wp_localize_script(
				'kmae-listing-locate',
				'kmaeListingLocate',
				array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'locateNonce' => wp_create_nonce( KMAE_Counties::LOCATE_NONCE ),
				)
			);
		}

		/* Albira megerősítő ablak (kmaeConfirm) — a böngésző confirm() helyett. */
		wp_enqueue_style( 'kmae-confirm', KMAE_PLUGIN_URL . 'assets/css/confirm.css', array(), KMAE_VERSION );
		wp_enqueue_script( 'kmae-confirm', KMAE_PLUGIN_URL . 'assets/js/confirm.js', array(), KMAE_VERSION, true );
		$public_deps[] = 'kmae-confirm';

		wp_enqueue_style( 'kmae-public', KMAE_PLUGIN_URL . 'assets/css/public.css', array(), KMAE_VERSION );
		wp_enqueue_script( 'kmae-public', KMAE_PLUGIN_URL . 'assets/js/public.js', $public_deps, KMAE_VERSION, true );
		wp_enqueue_script( 'kmae-swipe', KMAE_PLUGIN_URL . 'assets/js/swipe.js', array(), KMAE_VERSION, true );
		/* Képnagyító (PC): a galériák teljes képernyős / felugró nézetében — böngészés, adatlap, varázsló, szerkesztő. */
		wp_enqueue_style( 'kmae-image-zoom', KMAE_PLUGIN_URL . 'assets/css/image-zoom.css', array(), KMAE_VERSION );
		wp_enqueue_script( 'kmae-image-zoom', KMAE_PLUGIN_URL . 'assets/js/image-zoom.js', array(), KMAE_VERSION, true );
		if ( KMAE_Vehicle::is_vehicle_launch() && KMAE_Browse_Feed::is_enabled() && $this->is_browse_request() ) {
			/* Böngészés v2 kártyák (templates/browse-swipe-v2.php; lista nézetben a JS magától kilép). */
			wp_enqueue_style( 'kmae-browse-v2', KMAE_PLUGIN_URL . 'assets/css/browse-v2.css', array(), KMAE_VERSION );
			wp_enqueue_script( 'kmae-swipe-v2', KMAE_PLUGIN_URL . 'assets/js/swipe-v2.js', array(), KMAE_VERSION, true );
			/* Lapozó (telefon): a v2 kártyák és a lista nézet fölötti lapozó; a ver-t az asset_version_src filemtime-ra cseréli. */
			wp_enqueue_script( 'kmae-browse-lapozo', KMAE_PLUGIN_URL . 'assets/js/browse-lapozo.js', array(), KMAE_VERSION, true );
		}
		wp_enqueue_script( 'kmae-map', KMAE_PLUGIN_URL . 'assets/js/map.js', array(), KMAE_VERSION, true );

		if ( KMAE_Vehicle::is_vehicle_launch() ) {
			wp_enqueue_script( 'kmae-browse-wizard', KMAE_PLUGIN_URL . 'assets/js/browse-wizard.js', array(), KMAE_VERSION, true );
			wp_enqueue_script( 'kmae-browse-sort', KMAE_PLUGIN_URL . 'assets/js/browse-sort.js', array(), KMAE_VERSION, true );
			wp_localize_script(
				'kmae-browse-wizard',
				'kmaeBrowseCatalog',
				array(
					'brands' => KMAE_Vehicle::brands(),
					'models' => KMAE_Vehicle::catalog(),
				)
			);
			wp_enqueue_script( 'kmae-photo-subject', KMAE_PLUGIN_URL . 'assets/js/photo-subject.js', array(), KMAE_VERSION, true );
			wp_enqueue_script( 'kmae-photo-motor', KMAE_PLUGIN_URL . 'assets/js/photo-motor.js', array( 'kmae-photo-subject' ), KMAE_VERSION, true );
			wp_localize_script(
				'kmae-photo-motor',
				'kmaePhotoMotor',
				array(
					/* A küszöbök egyetlen forrása a PHP — a böngésző innen kapja
					   őket, így a két oldal sosem mondhat mást ugyanarról a képről. */
					'config' => KMAE_Photo_Motor::client_config(),
					/* PDF (pl. előélet-lekérdezés): a böngésző oldalanként képpé alakítja,
					   az eredeti PDF fel sem kerül a szerverre. Csak PDF választásakor töltődik be. */
					'pdf'    => array(
						'lib'    => KMAE_PLUGIN_URL . 'assets/vendor/pdfjs/pdf.min.js',
						'worker' => KMAE_PLUGIN_URL . 'assets/vendor/pdfjs/pdf.worker.min.js',
					),
					'i18n' => array(
						'tooDark'            => __( 'Ez a fotó túl sötét lett. Készíts másikat világosabb helyen.', 'ki-mit-adna-erte' ),
						'tooBright'          => __( 'Ez a fotó túl világos lett. Készíts másikat úgy, hogy ne süssön a kamerába a nap vagy egy erős lámpa.', 'ki-mit-adna-erte' ),
						'darkFix'            => __( 'A fotó egy kicsit sötét, de feltöltéskor kivilágosítjuk. Nincs vele teendőd.', 'ki-mit-adna-erte' ),
						'darkFixLabel'       => __( 'Kivilágosítjuk', 'ki-mit-adna-erte' ),
						'shadowClipped'      => __( 'Ez a fotó túl sötét, ezért nem engedjük feltölteni. Készíts helyette egy újat világosabb helyen.', 'ki-mit-adna-erte' ),
						'shadowClippedLabel' => __( 'Túl sötét lett', 'ki-mit-adna-erte' ),
						'brightFix'          => __( 'A fotó egy kicsit túl világos, de feltöltéskor javítunk rajta. Nincs vele teendőd.', 'ki-mit-adna-erte' ),
						'brightFixLabel'     => __( 'Javítunk a fényén', 'ki-mit-adna-erte' ),
						'highlightClipped'   => __( 'Ez a fotó túl világos, ezért nem engedjük feltölteni. Készíts helyette egy újat árnyékosabb helyen.', 'ki-mit-adna-erte' ),
						'highlightClippedLabel' => __( 'Túl világos lett', 'ki-mit-adna-erte' ),
						'lowContrastFix'     => __( 'A fotó kissé fakó, de feltöltéskor szebbé tesszük. Nincs vele teendőd.', 'ki-mit-adna-erte' ),
						'lowContrastLabel'   => __( 'Szebbé tesszük', 'ki-mit-adna-erte' ),
						'softFix'            => __( 'A fotó egy kicsit életlen, de feltöltéskor javítunk rajta. Nincs vele teendőd.', 'ki-mit-adna-erte' ),
						'softLabel'          => __( 'Élesítünk rajta', 'ki-mit-adna-erte' ),
						'smallHint'          => __( 'A fotó használható, de egy nagyobb méretű képen szebben látszana az autó.', 'ki-mit-adna-erte' ),
						'smallLabel'         => __( 'Kisebb kép', 'ki-mit-adna-erte' ),
						'okLabel'            => __( 'Rendben', 'ki-mit-adna-erte' ),
						'blurryLabel'        => __( 'Nem elég éles', 'ki-mit-adna-erte' ),
						'tooSmallLabel'      => __( 'Túl kicsi a kép', 'ki-mit-adna-erte' ),
						'blurry'             => __( 'Ez a fotó nem elég éles. Készíts másikat, és várd meg, amíg a kamera élesre áll.', 'ki-mit-adna-erte' ),
						'duplicate'          => __( 'Ezt a fotót már kiválasztottad. Válassz helyette egy másikat.', 'ki-mit-adna-erte' ),
						'tooSmall'           => __( 'Ez a kép túl kicsi, ezért rosszul mutatna a hirdetésben. Válassz egy nagyobb méretű fotót.', 'ki-mit-adna-erte' ),
						'invalid'            => __( 'Ezt a fájlt nem tudjuk fotóként megnyitni. Válassz egy másik képet.', 'ki-mit-adna-erte' ),
						'cameraDenied'       => __( 'A böngésző nem fér hozzá a kamerához. Engedélyezd a kamerát, vagy válassz egy már elkészített fotót.', 'ki-mit-adna-erte' ),
						'cameraFailed'       => __( 'A kamerát most nem sikerült megnyitni. Próbáld újra, vagy válassz egy már elkészített fotót.', 'ki-mit-adna-erte' ),
						'requiredSlot'       => __( 'Ez a nézet kötelező.', 'ki-mit-adna-erte' ),
						'retakeLabel'        => __( 'Újrafotózás', 'ki-mit-adna-erte' ),
						'captureLabel'       => __( 'Fotó készítése', 'ki-mit-adna-erte' ),
						'cameraTitle'        => __( 'Fotó készítése', 'ki-mit-adna-erte' ),
						'cameraHint'         => __( 'Válassz képet a kamerából vagy az eszközöd galériájából.', 'ki-mit-adna-erte' ),
						'closeCamera'        => __( 'Bezárás', 'ki-mit-adna-erte' ),
						'cancelCamera'       => __( 'Mégse', 'ki-mit-adna-erte' ),
						'shootPhoto'         => __( 'Fotó készítése', 'ki-mit-adna-erte' ),
						'openPhotos'         => __( 'Fotók készítése', 'ki-mit-adna-erte' ),
						'editPhotos'         => __( 'Fotók szerkesztése', 'ki-mit-adna-erte' ),
						'saveAndActivate'    => __( 'Fotók mentése és aktiválás', 'ki-mit-adna-erte' ),
						'save'               => __( 'Mentés', 'ki-mit-adna-erte' ),
						'awaitingReady'      => __( 'A kötelező fotók megvannak — mentés után a hirdetés aktív lesz.', 'ki-mit-adna-erte' ),
						'awaitingPending'    => __( 'Fotók nélkül is mentheted az adatokat. Aktiváláshoz töltsd fel a 7 kötelező fotót.', 'ki-mit-adna-erte' ),
						'computerSubmitHint' => __( 'Mentés után a hirdetés „Fotó feltöltésre vár” státuszban lesz.', 'ki-mit-adna-erte' ),
						'desktopSubmitHint'  => __( 'Töltsd fel a fotókat kamerából vagy az eszközödről.', 'ki-mit-adna-erte' ),
						'mobileSubmitHint'   => __( 'Töltsd fel a 7 kötelező fotót a „Fotók készítése” gombbal.', 'ki-mit-adna-erte' ),
					),
				)
			);
			$this->enqueue_vehicle_catalog();
			$this->enqueue_listing_wizard();
		}

		$current_post = get_post();
		if ( is_user_logged_in() && $current_post && (
			has_shortcode( (string) $current_post->post_content, 'kmae_my_listings' )
			|| has_shortcode( (string) $current_post->post_content, 'kmae_create_listing' )
		) ) {
			wp_enqueue_script( 'kmae-my-listings', KMAE_PLUGIN_URL . 'assets/js/my-listings.js', array(), KMAE_VERSION, true );
		}

		if ( is_user_logged_in() && $current_post && class_exists( 'KMAE_Dealer_Leads' ) && (
			has_shortcode( (string) $current_post->post_content, 'kmae_my_listings' )
			|| has_shortcode( (string) $current_post->post_content, 'kmae_notifications' )
		) ) {
			/* Kereskedők megkeresése: eladói küldő ablak (partials/dealer-leads-dialog.php). */
			wp_enqueue_script( 'kmae-dealer-leads', KMAE_PLUGIN_URL . 'assets/js/dealer-leads.js', array(), KMAE_VERSION, true );
		}

		wp_localize_script(
			'kmae-public',
			'kmaePublic',
			array(
				'livePoll'           => is_user_logged_in()
					? array(
						'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
						'nonce'          => wp_create_nonce( 'kmae_poll_page' ),
						'interval'       => 4000,
						'idleInterval'   => 25000,
						'idleAfter'      => 60000,
						/* Könnyűsúlyú jelző: sima fájl (uploads/kmae-live/<kulcs>.v),
						   PHP és WordPress nélkül. A teljes lekérdezés csak változáskor,
						   illetve biztonsági hálóként 2 percenként fut. */
						'signalUrl'      => KMAE_Live_Signal::signal_url( get_current_user_id() ),
						'signalVersion'  => KMAE_Live_Signal::version( get_current_user_id() ),
						'fullEvery'      => 120000,
					)
					: null,
			)
		);

		wp_localize_script(
			'kmae-swipe',
			'kmaeSwipe',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => is_user_logged_in() ? wp_create_nonce( 'kmae_browse_pass' ) : '',
				'favoriteNonce' => is_user_logged_in() ? wp_create_nonce( 'kmae_favorite_add' ) : '',
				'loggedIn'      => is_user_logged_in(),
				'loginUrl'      => class_exists( 'KMAE_Auth' ) ? KMAE_Auth::login_url( class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' ) ) : wp_login_url(),
			)
		);
	}

	/**
	 * Böngészés oldal (= theme kmae_is_browse_page): a /hirdetesek/ archívum vagy a [kmae_browse] oldal.
	 */
	private function is_browse_request() {
		if ( is_post_type_archive( 'kmae_listing' ) ) {
			return true;
		}
		if ( ! is_page() ) {
			return false;
		}
		$post_id = get_queried_object_id();
		return 'hirdetesek' === (string) get_post_field( 'post_name', $post_id )
			|| false !== strpos( (string) get_post_field( 'post_content', $post_id ), '[kmae_browse' );
	}

	/**
	 * Create / edit listing form page (logged-in users only).
	 *
	 * @return bool
	 */
	private function is_create_listing_page() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$post = get_post();
		return $post && has_shortcode( (string) $post->post_content, 'kmae_create_listing' );
	}

	/**
	 * Brand / model catalog picker for create and edit listing forms.
	 */
	private function enqueue_vehicle_catalog() {
		if ( ! $this->is_create_listing_page() ) {
			return;
		}

		$payload = KMAE_Vehicle::catalog_payload();
		$payload['i18n'] = array(
			'pickOrCustom' => __( 'Válassz a listából, vagy add meg kézzel.', 'ki-mit-adna-erte' ),
		);

		wp_enqueue_script( 'kmae-vehicle-catalog', KMAE_PLUGIN_URL . 'assets/js/vehicle-catalog.js', array(), KMAE_VERSION, true );
		wp_enqueue_script( 'kmae-listing-field-guards', KMAE_PLUGIN_URL . 'assets/js/listing-field-guards.js', array(), KMAE_VERSION, true );
		wp_localize_script( 'kmae-vehicle-catalog', 'kmaeVehicleCatalog', $payload );
	}

	/**
	 * Plugin asset URL → `ver` = fájl módosítási ideje.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public static function asset_version_src( $src ) {
		if ( ! is_string( $src ) || 0 !== strpos( $src, KMAE_PLUGIN_URL ) ) {
			return $src;
		}
		$path = KMAE_PLUGIN_DIR . strtok( substr( $src, strlen( KMAE_PLUGIN_URL ) ), '?' );
		if ( ! is_file( $path ) ) {
			return $src;
		}
		return add_query_arg( 'ver', (string) filemtime( $path ), $src );
	}

	/**
	 * Guided create wizard: engine plus its phase and validation config.
	 */
	private function enqueue_listing_wizard() {
		if ( ! is_user_logged_in() || isset( $_GET['kmae_edit'] ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'kmae_create_listing' ) ) {
			return;
		}

		wp_enqueue_script( 'kmae-listing-wizard', KMAE_PLUGIN_URL . 'assets/js/listing-wizard.js', array( 'kmae-listing-form-numbers' ), KMAE_VERSION, true );
		wp_enqueue_script( 'kmae-listing-wizard-preview', KMAE_PLUGIN_URL . 'assets/js/listing-wizard-preview.js', array( 'kmae-listing-wizard' ), KMAE_VERSION, true );

		/* Forgalmi engedély beolvasása (opcionális, csak ha be van kapcsolva és van API-kulcs). */
		if ( KMAE_Registration_Scan::is_enabled() ) {
			wp_enqueue_style( 'kmae-registration-scan', KMAE_PLUGIN_URL . 'assets/css/registration-scan.css', array(), KMAE_VERSION );
			wp_enqueue_script( 'kmae-registration-scan', KMAE_PLUGIN_URL . 'assets/js/registration-scan.js', array( 'kmae-listing-wizard' ), KMAE_VERSION, true );
			wp_localize_script( 'kmae-registration-scan', 'kmaeRegistrationScan', KMAE_Registration_Scan::client_config() );
		} elseif ( current_user_can( 'manage_options' ) ) {
			/* Az admin állapotjelzőjéhez (listing-wizard.php) csak a stílus kell. */
			wp_enqueue_style( 'kmae-registration-scan', KMAE_PLUGIN_URL . 'assets/css/registration-scan.css', array(), KMAE_VERSION );
		}

		$phases = array();
		foreach ( KMAE_Listing_Wizard::phases() as $code => $phase ) {
			$phases[] = array(
				'code'  => $code,
				'label' => $phase['label'],
				'short' => $phase['short'],
			);
		}

		/* Félbehagyott hirdetés: `?kmae_draft=ID` esetén azt töltjük be kérdés
		   nélkül, különben a legutóbbit ajánljuk fel folytatásra. */
		$user_id         = get_current_user_id();
		$requested_draft = isset( $_GET['kmae_draft'] ) ? KMAE_Listing_Drafts::get_user_draft( absint( $_GET['kmae_draft'] ), $user_id ) : null;
		$offered_draft   = $requested_draft ? $requested_draft : KMAE_Listing_Drafts::get_latest_draft( $user_id );

		wp_localize_script(
			'kmae-listing-wizard',
			'kmaeListingWizard',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'draftNonce'     => wp_create_nonce( KMAE_Listing_Drafts::NONCE_ACTION ),
				'draft'          => $offered_draft ? KMAE_Listing_Drafts::to_client( $offered_draft ) : null,
				'draftRequested' => (bool) $requested_draft,
				'draftSimple'    => $requested_draft && isset( $_GET['kmae_mode'] ) && 'simple' === sanitize_key( wp_unslash( $_GET['kmae_mode'] ) ),
				'phases'         => $phases,
				'phaseLabels'    => wp_list_pluck( KMAE_Listing_Wizard::phases(), 'short' ),
				'minDescription' => KMAE_Moderation::is_enabled() ? (int) KMAE_Moderation::MIN_DESC_LENGTH : 0,
				'assistNudges'   => KMAE_Listing_Wizard::description_progress_messages(),
				'assistIntro'    => KMAE_Listing_Wizard::assistant_intro(),
				'assistReactions' => KMAE_Listing_Wizard::assistant_reactions(),
				'assistPublish'   => KMAE_Listing_Wizard::assistant_publish(),
				'icons'          => KMAE_Vehicle_Icons::js_catalog(),
				'i18n'           => array(
					'next'             => __( 'Tovább', 'ki-mit-adna-erte' ),
					'invalid'          => __( 'Ezt a mezőt még ki kell töltened.', 'ki-mit-adna-erte' ),
					'descriptionShort' => sprintf(
						/* translators: %d: minimum characters */
						__( 'A leírás legyen legalább %d karakter.', 'ki-mit-adna-erte' ),
						(int) KMAE_Moderation::MIN_DESC_LENGTH
					),
				),
			)
		);
	}

	/**
	 * Skip plugin public assets on Simple Mode guest marketing shell (theme owns layout).
	 *
	 * @return bool
	 */
	private function should_enqueue_public_assets() {
		if ( is_user_logged_in() ) {
			return true;
		}

		if ( function_exists( 'kmae_is_guest_simple_marketing_shell' ) && kmae_is_guest_simple_marketing_shell() ) {
			return false;
		}

		return true;
	}

	private function is_register_page() {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}

		$content = (string) $post->post_content;
		return has_shortcode( $content, 'kmae_register' ) || false !== strpos( $content, '[kmae_register' );
	}

	/**
	 * Hide awaiting-photos listings from non-owners.
	 */
	/**
	 * Live pages (messages inbox, open chat, own listings, listing page) must
	 * not be served from a page cache for logged-in users: the live poll
	 * reloads them, and a cached copy would show the old state for minutes.
	 * The LiteSpeed Cache plugin ignores plain no-cache headers, so it is
	 * told directly (its own action + DONOTCACHEPAGE).
	 */
	public function nocache_live_pages() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		/* Belépve a főoldal az élő hirdetés-statisztika (megtekintés, üzenet, ajánlat). */
		if ( is_front_page() ) {
			nocache_headers();
			do_action( 'litespeed_control_set_nocache', 'kmae live home' );
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			return;
		}
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		$content = (string) $post->post_content;
		$is_live = 'kmae_listing' === $post->post_type;
		/* kmae_browse: belépve felhasználónként más (Tiltó lista, nézet-meta, kedvencek, átlapozott) — régi cache-elt változat ne jöjjön.
		   kmae_favorites: a „Foglalt” címke a kedvenc nélkül is változik.
		   kmae_create_listing / kmae_profile / kmae_settings: az e-mail megerősítés után a régi
		   „Erősítsd meg” képernyő ne jöjjön vissza a cache-ből.
		   kmae_notifications: a kereskedői megkeresések állapota (válasz, lejárat) percenként változhat. */
		foreach ( array( 'kmae_messages', 'kmae_my_listings', 'kmae_browse', 'kmae_favorites', 'kmae_create_listing', 'kmae_profile', 'kmae_settings', 'kmae_notifications' ) as $tag ) {
			if ( has_shortcode( $content, $tag ) || false !== strpos( $content, '[' . $tag ) ) {
				$is_live = true;
			}
		}
		if ( ! $is_live ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'kmae live page' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	public function block_nonpublic_listings() {
		if ( ! is_singular( 'kmae_listing' ) ) {
			return;
		}

		$listing_id = get_queried_object_id();
		if ( ! $listing_id ) {
			return;
		}
		if ( KMAE_Meta::STATUS_AWAITING_PHOTOS !== KMAE_Meta::get_status( $listing_id ) ) {
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $listing_id );
		$is_owner  = is_user_logged_in() && get_current_user_id() === $author_id;
		if ( $is_owner || current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/hirdetesek/' ) );
		exit;
	}

	public function ajax_browse_pass() {
		check_ajax_referer( 'kmae_browse_pass', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'auth' ), 403 );
		}

		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( ! $listing_id || 'kmae_listing' !== get_post_type( $listing_id ) ) {
			wp_send_json_error( array( 'message' => 'invalid' ), 400 );
		}

		KMAE_Browse::record_pass( get_current_user_id(), $listing_id );
		wp_send_json_success();
	}

	/**
	 * Böngészés v2 „Jelentés” ablak: hirdetés vagy annak egy képe (KMAE_Reports::photo_reasons).
	 * Ugyanaz, mint a process_report, csak oldalújratöltés nélkül.
	 */
	public function ajax_report_listing() {
		check_ajax_referer( 'kmae_report_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'A bejelentéshez lépj be.', 'ki-mit-adna-erte' ) ), 403 );
		}

		$result = KMAE_Reports::submit(
			array(
				'context'     => KMAE_Reports::CONTEXT_LISTING,
				'listing_id'  => isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0,
				'reporter_id' => get_current_user_id(),
				'reason'      => isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'details'     => isset( $_POST['details'] ) ? wp_unslash( $_POST['details'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'photo'       => isset( $_POST['photo'] ) ? absint( $_POST['photo'] ) : 0,
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success();
	}

	public function ajax_favorite_add() {
		check_ajax_referer( 'kmae_favorite_add', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'auth' ), 403 );
		}

		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( ! KMAE_Browse::add_favorite( get_current_user_id(), $listing_id ) ) {
			wp_send_json_error( array( 'message' => 'invalid' ), 400 );
		}

		wp_send_json_success(
			array(
				'saved'  => true,
				'unseen' => (int) KMAE_Browse::count_unseen_favorites( get_current_user_id() ),
			)
		);
	}

	public function ajax_favorite_remove() {
		check_ajax_referer( 'kmae_favorite_add', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'auth' ), 403 );
		}

		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		KMAE_Browse::remove_favorite( get_current_user_id(), $listing_id );
		wp_send_json_success(
			array(
				'saved'  => false,
				'unseen' => (int) KMAE_Browse::count_unseen_favorites( get_current_user_id() ),
			)
		);
	}

	public function ajax_poll_page() {
		check_ajax_referer( 'kmae_poll_page', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'auth' ), 403 );
		}

		$context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
		$args    = array();

		if ( 'conversation_thread' === $context ) {
			$args['conversation_id'] = isset( $_POST['conversation_id'] ) ? absint( $_POST['conversation_id'] ) : 0;
		} elseif ( 'single_listing' === $context ) {
			$args['listing_id'] = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		}

		$response = array(
			'fingerprint' => KMAE_Live_Page::get_fingerprint( $context, $args ),
		);

		/* Az élő oldalak kérése a menü számlálóját is viszi — így oldalanként
		   egyetlen háttérkérés fut (public.js élő frissítés). */
		$response['unseen'] = (int) KMAE_Database::count_unread_conversations( get_current_user_id() );

		/* Fejléc-csengő (kereskedői megkeresések): ugyanebben a válaszban, külön kérés nélkül. */
		if ( class_exists( 'KMAE_Dealer_Leads' ) ) {
			$response['notif_pending'] = (int) KMAE_Dealer_Leads::bell_count( get_current_user_id() );
		}

		wp_send_json_success( $response );
	}

	public function handle_forms() {
		/* A beküldési korlátot (post_max_size) túllépő hirdetésfeladásnál a PHP
		   az egész űrlapot eldobja: hibaüzenet nélkül a varázsló indulna újra. */
		if ( empty( $_POST ) && is_user_logged_in() && isset( $_SERVER['REQUEST_METHOD'], $_SERVER['CONTENT_LENGTH'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$post_limit  = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
			$create_url  = home_url( '/hirdetes-feladasa/' );
			$create_path = untrailingslashit( (string) wp_parse_url( $create_url, PHP_URL_PATH ) );
			$req_path    = untrailingslashit( (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH ) );
			if ( $post_limit > 0 && (int) $_SERVER['CONTENT_LENGTH'] > $post_limit && $req_path === $create_path ) {
				wp_safe_redirect( add_query_arg( 'kmae_error', 'upload_too_large', $create_url ) );
				exit;
			}
		}

		if ( ! isset( $_POST['kmae_action'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['kmae_action'] ) );

		/* A böngészőben elrejtett gomb megkerülhető közvetlen POST-tal — itt
		   dől el ténylegesen, ki adhat fel hirdetést vagy írhat az eladónak. */
		$email_gated_actions = array( 'create_listing', 'start_conversation' );
		if ( in_array( $action, $email_gated_actions, true ) && KMAE_Auth::should_gate_for_verification( get_current_user_id() ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'email_unverified', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		if ( 'create_listing' === $action ) {
			$this->process_create_listing();
		}

		if ( 'start_conversation' === $action ) {
			$this->process_start_conversation();
		}

		if ( 'send_conversation_message' === $action ) {
			$this->process_conversation_message();
		}

		if ( 'send_chat_offer' === $action ) {
			$this->process_chat_offer();
		}

		if ( 'accept_chat_offer' === $action || 'cancel_chat_sale' === $action || 'close_chat_sale' === $action || 'retract_chat_offer' === $action ) {
			$this->process_chat_offer_decision( $action );
		}

		if ( 'hide_conversation' === $action ) {
			$this->process_hide_conversation();
		}

		if ( 'block_user' === $action || 'unblock_user' === $action ) {
			$this->process_user_block( $action );
		}

		if ( 'download_sale_contract' === $action ) {
			KMAE_Sale_Contract::process_download();
		}

		if ( 'download_buyer_checklist' === $action ) {
			KMAE_Sale_Contract::process_checklist_download();
		}

		if ( 'submit_report' === $action ) {
			$this->process_report();
		}

		if ( 'dismiss_first_listing_tips' === $action ) {
			$this->process_dismiss_first_listing_tips();
		}

		if ( 'remove_favorite' === $action ) {
			$this->process_remove_favorite();
		}

		if ( 'clear_favorites' === $action ) {
			$this->process_clear_favorites();
		}
	}

	private function process_remove_favorite() {
		if ( ! $this->verify_nonce( 'kmae_remove_favorite_nonce' ) ) {
			return;
		}
		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		KMAE_Browse::remove_favorite( get_current_user_id(), $listing_id );
		wp_safe_redirect( KMAE_Pages::url( 'kedvencek' ) );
		exit;
	}

	private function process_clear_favorites() {
		if ( ! $this->verify_nonce( 'kmae_clear_favorites_nonce' ) ) {
			return;
		}
		KMAE_Browse::clear_favorites( get_current_user_id() );
		wp_safe_redirect( KMAE_Pages::url( 'kedvencek' ) );
		exit;
	}

	/**
	 * „Most nem, megnézem a hirdetésemet”: elrejti a tippeket, és a hirdetés
	 * adatlapjára visz. Csak a saját hirdetésnél működik.
	 */
	private function process_dismiss_first_listing_tips() {
		$user_id    = get_current_user_id();
		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$listing    = $listing_id ? get_post( $listing_id ) : null;

		if ( ! $this->verify_nonce( 'kmae_dismiss_first_tips_nonce' ) || ! $listing || (int) $listing->post_author !== $user_id ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		KMAE_Dashboard::dismiss_first_listing_tips( $user_id, $listing_id );
		/* A gratuláló doboz után a főoldal az élő hirdetés-statisztikát mutatja. */
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	private function verify_nonce( $field ) {
		return isset( $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $field );
	}

	private function verify_nonce_action( $field, $action ) {
		return isset( $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action );
	}

	private function get_form_redirect_url() {
		if ( ! empty( $_POST['kmae_redirect'] ) ) {
			return esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) );
		}
		return wp_get_referer() ?: home_url( '/' );
	}

	private function redirect_form_result( $code, $is_error = false ) {
		wp_safe_redirect( add_query_arg( $is_error ? 'kmae_error' : 'kmae_success', $code, $this->get_form_redirect_url() ) );
		exit;
	}

	private function get_profile_redirect_url() {
		if ( ! empty( $_POST['kmae_redirect'] ) ) {
			return esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) );
		}
		return home_url( '/profil/' );
	}

	private function redirect_profile( $code, $is_error = false ) {
		$key = $is_error ? 'kmae_error' : 'kmae_success';
		wp_safe_redirect( add_query_arg( $key, $code, $this->get_profile_redirect_url() ) );
		exit;
	}

	public function admin_delete_account() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_profile_redirect_url() ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_delete_account_nonce', 'kmae_delete_account' ) ) {
			$this->redirect_profile( 'nonce_failed', true );
		}

		$confirm = isset( $_POST['kmae_delete_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_delete_confirm'] ) ) : '';
		if ( 'TÖRLÖM' !== mb_strtoupper( trim( $confirm ) ) ) {
			$this->redirect_profile( 'delete_confirm_failed', true );
		}

		$result = KMAE_Auth::delete_account( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_profile( 'admin_account' === $result->get_error_code() ? 'delete_admin_blocked' : 'delete_failed', true );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( 0 );
		wp_safe_redirect( add_query_arg( 'kmae_success', 'account_deleted', KMAE_Auth::login_url() ) );
		exit;
	}

	public function admin_save_profile() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_profile_redirect_url() ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_profile_nonce', 'kmae_save_profile' ) ) {
			$this->redirect_profile( 'nonce_failed', true );
		}

		$user_id      = get_current_user_id();
		$display_name = isset( $_POST['kmae_display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_display_name'] ) ) : '';
		$phone        = isset( $_POST['kmae_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_phone'] ) ) : '';

		if ( ! empty( $display_name ) && ! KMAE_Auth::is_company( $user_id ) ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $display_name,
				)
			);
			KMAE_Auth::adopt_public_name( $user_id, $display_name );
		}

		/* Üresen hagyható, de ha van érték, egységes alakban tároljuk. */
		if ( '' !== trim( $phone ) ) {
			$normalized = KMAE_Auth::normalize_phone( $phone );
			if ( '' === $normalized ) {
				$this->redirect_profile( 'phone_invalid', true );
			}
			$phone = $normalized;
		}

		update_user_meta( $user_id, 'kmae_phone', $phone );
		KMAE_Auth::sync_phone( $user_id, $phone );

		if ( KMAE_Auth::is_company( $user_id ) ) {
			$company_data = array(
				'company_name' => isset( $_POST['kmae_company_name'] ) ? wp_unslash( $_POST['kmae_company_name'] ) : $display_name,
				'address'      => isset( $_POST['kmae_company_address'] ) ? wp_unslash( $_POST['kmae_company_address'] ) : '',
				'county'       => isset( $_POST['kmae_company_county'] ) ? wp_unslash( $_POST['kmae_company_county'] ) : '',
				'city'         => isset( $_POST['kmae_company_city'] ) ? wp_unslash( $_POST['kmae_company_city'] ) : '',
				'intro'        => isset( $_POST['kmae_company_intro'] ) ? wp_unslash( $_POST['kmae_company_intro'] ) : '',
			);
			/* Autóátvétel: csak ha az űrlapon ott volt a blokk (a jelölő mező jelzi). */
			if ( ! empty( $_POST['kmae_company_offer_flags'] ) ) {
				$company_data['buys']    = ! empty( $_POST['kmae_company_buys'] );
				$company_data['tradein'] = ! empty( $_POST['kmae_company_tradein'] );
			}
			$company_result = KMAE_Company::save_profile_fields( $user_id, $company_data );
			if ( is_wp_error( $company_result ) ) {
				$this->redirect_profile( $company_result->get_error_code(), true );
			}
		}

		$this->redirect_profile( 'profile_saved' );
	}

	/* ------------------------------------------------------------------
	   Beállítások oldal — értesítések, belépési adatok, munkamenetek.
	   ------------------------------------------------------------------ */

	private function get_settings_redirect_url() {
		if ( ! empty( $_POST['kmae_redirect'] ) ) {
			return esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) );
		}
		return KMAE_Pages::url( 'beallitasok' );
	}

	/**
	 * @param string $code     Üzenetkód.
	 * @param bool   $is_error Hibaüzenet-e.
	 */
	private function redirect_settings( $code, $is_error = false ) {
		$key = $is_error ? 'kmae_error' : 'kmae_success';
		wp_safe_redirect( add_query_arg( $key, $code, $this->get_settings_redirect_url() ) );
		exit;
	}

	private function require_settings_login() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_settings_redirect_url() ) );
			exit;
		}
	}

	public function admin_save_settings() {
		$this->require_settings_login();

		if ( ! $this->verify_nonce_action( 'kmae_settings_nonce', 'kmae_save_settings' ) ) {
			$this->redirect_settings( 'nonce_failed', true );
		}

		KMAE_Auth::save_email_prefs(
			get_current_user_id(),
			! empty( $_POST['kmae_email_offers'] ),
			! empty( $_POST['kmae_email_news'] )
		);

		$this->redirect_settings( 'settings_saved' );
	}

	public function admin_save_mail_prefs() {
		$this->require_settings_login();

		if ( ! $this->verify_nonce_action( 'kmae_mail_prefs_nonce', 'kmae_save_mail_prefs' ) ) {
			$this->redirect_settings( 'nonce_failed', true );
		}

		$posted = isset( $_POST['kmae_mail'] ) ? (array) wp_unslash( $_POST['kmae_mail'] ) : array();
		KMAE_Notifications::save_mail_prefs( get_current_user_id(), $posted );
		$this->redirect_settings( 'mail_saved' );
	}

	public function admin_change_email() {
		$this->require_settings_login();

		if ( ! $this->verify_nonce_action( 'kmae_email_nonce', 'kmae_change_email' ) ) {
			$this->redirect_settings( 'nonce_failed', true );
		}

		$user     = wp_get_current_user();
		$email    = isset( $_POST['kmae_new_email'] ) ? sanitize_email( wp_unslash( $_POST['kmae_new_email'] ) ) : '';
		$password = isset( $_POST['kmae_current_password'] ) ? (string) wp_unslash( $_POST['kmae_current_password'] ) : '';

		/* Az e-mail a belépési azonosító, ezért jelszóval kell megerősíteni. */
		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			$this->redirect_settings( 'wrong_password', true );
		}

		if ( ! is_email( $email ) ) {
			$this->redirect_settings( 'invalid_email', true );
		}

		if ( strtolower( $email ) === strtolower( $user->user_email ) ) {
			$this->redirect_settings( 'email_unchanged', true );
		}

		if ( email_exists( $email ) ) {
			$this->redirect_settings( 'email_taken', true );
		}

		$old_email = $user->user_email;
		$result    = wp_update_user(
			array(
				'ID'         => $user->ID,
				'user_email' => $email,
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_settings( 'email_failed', true );
		}

		KMAE_Notifications::notify_email_changed( $user->ID, $old_email, $email );
		$this->redirect_settings( 'email_saved' );
	}

	public function admin_change_password() {
		$this->require_settings_login();

		if ( ! $this->verify_nonce_action( 'kmae_password_nonce', 'kmae_change_password' ) ) {
			$this->redirect_settings( 'nonce_failed', true );
		}

		$user    = wp_get_current_user();
		$current = isset( $_POST['kmae_current_password'] ) ? (string) wp_unslash( $_POST['kmae_current_password'] ) : '';
		$new     = isset( $_POST['kmae_new_password'] ) ? (string) wp_unslash( $_POST['kmae_new_password'] ) : '';
		$repeat  = isset( $_POST['kmae_new_password_repeat'] ) ? (string) wp_unslash( $_POST['kmae_new_password_repeat'] ) : '';

		if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			$this->redirect_settings( 'wrong_password', true );
		}

		if ( strlen( $new ) < 8 ) {
			$this->redirect_settings( 'password_short', true );
		}

		if ( $new !== $repeat ) {
			$this->redirect_settings( 'password_mismatch', true );
		}

		wp_set_password( $new, $user->ID );

		/* A jelszócsere kilépteti a felhasználót — azonnal vissza is léptetjük,
		   hogy ne a bejelentkező képernyőn kössön ki. */
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		KMAE_Notifications::notify_password_changed( $user->ID );
		$this->redirect_settings( 'password_saved' );
	}

	public function admin_logout_everywhere() {
		$this->require_settings_login();

		if ( ! $this->verify_nonce_action( 'kmae_sessions_nonce', 'kmae_logout_everywhere' ) ) {
			$this->redirect_settings( 'nonce_failed', true );
		}

		/* A jelenlegi eszköz bejelentkezve marad, a többi munkamenet megszűnik. */
		wp_destroy_other_sessions();

		$this->redirect_settings( 'sessions_cleared' );
	}

	public function admin_login() {
		if ( ! $this->verify_nonce_action( 'kmae_login_nonce', 'kmae_login' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', KMAE_Auth::login_url() ) );
			exit;
		}

		$email    = isset( $_POST['kmae_login_email'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_login_email'] ) ) : '';
		$password = isset( $_POST['kmae_login_password'] ) ? wp_unslash( $_POST['kmae_login_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remember = ! empty( $_POST['kmae_remember'] );
		$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : home_url( '/' );

		if ( empty( $email ) || empty( $password ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'missing_fields', KMAE_Auth::login_url( $redirect ) ) );
			exit;
		}

		$result = KMAE_Auth::login( $email, $password, $remember );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), KMAE_Auth::login_url( $redirect ) ) );
			exit;
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * A látogató valódi IP-címe. Csak korlátozáshoz használjuk, nem tároljuk.
	 *
	 * Cloudflare mögött a REMOTE_ADDR a Cloudflare szerverének címe (sok
	 * látogató közös címe), a valódi címet a CF-Connecting-IP fejléc hozza.
	 * Ezt CSAK akkor fogadjuk el, ha a kérés tényleg a Cloudflare hivatalos
	 * tartományából jön — különben a fejléc hamisítható lenne.
	 *
	 * @return string
	 */
	private function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && self::is_cloudflare_ip( $ip ) ) {
			$real = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			if ( filter_var( $real, FILTER_VALIDATE_IP ) ) {
				$ip = $real;
			}
		}
		return $ip ? $ip : 'unknown';
	}

	/**
	 * A Cloudflare hivatalos címtartományai (cloudflare.com/ips).
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private static function is_cloudflare_ip( $ip ) {
		$ranges = array(
			'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
			'108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
			'162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
			'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
			'2a06:98c0::/29', '2c0f:f248::/32',
		);
		$bin = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $bin ) {
			return false;
		}
		foreach ( $ranges as $range ) {
			list( $subnet, $bits ) = explode( '/', $range );
			$sub = @inet_pton( $subnet ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $sub || strlen( $sub ) !== strlen( $bin ) ) {
				continue;
			}
			$bits  = (int) $bits;
			$bytes = intdiv( $bits, 8 );
			if ( substr( $bin, 0, $bytes ) !== substr( $sub, 0, $bytes ) ) {
				continue;
			}
			$rest = $bits % 8;
			if ( 0 === $rest ) {
				return true;
			}
			$mask = chr( ( 0xff << ( 8 - $rest ) ) & 0xff );
			if ( ( $bin[ $bytes ] & $mask ) === ( $sub[ $bytes ] & $mask ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Botszűrés a regisztrációnál. Három egymástól független ellenőrzés.
	 * Mindegyik „fail open": ha az adat hiányzik (pl. gyorsítótárazott oldal
	 * miatt), NEM utasítjuk el a regisztrációt — valódi felhasználót sosem
	 * akadályozhat meg.
	 *
	 * @return string Üres, ha rendben van; különben a hibakód.
	 */
	private function register_spam_check() {
		/* 1. Csapda-mező: ember nem látja, tehát üresen kell maradnia. */
		if ( ! empty( $_POST['kmae_website'] ) ) {
			return 'too_fast';
		}

		/* 2. Időzár: az űrlap betöltése óta legalább 3 másodperc teljen el.
		      Az időbélyeget aláírással ellenőrizzük, hogy ne legyen hamisítható. */
		$form_time  = isset( $_POST['kmae_form_time'] ) ? (int) $_POST['kmae_form_time'] : 0;
		$form_token = isset( $_POST['kmae_form_token'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_form_token'] ) ) : '';

		if ( $form_time && $form_token && hash_equals( wp_hash( 'kmae_register_' . $form_time ), $form_token ) ) {
			$elapsed = time() - $form_time;
			if ( $elapsed >= 0 && $elapsed < 3 ) {
				return 'too_fast';
			}
		}

		/* 3. IP-korlát: óránként legfeljebb 10 regisztrációs kísérlet. Azért
		      ennyi, mert egy címen többen is ülhetnek (céges háló, mobilnet),
		      és a sikertelen próbálkozás (pl. elgépelt jelszó) is beleszámít. */
		$key   = 'kmae_reg_' . md5( $this->client_ip() );
		$tries = (int) get_transient( $key );
		if ( $tries >= 10 ) {
			return 'too_many';
		}
		set_transient( $key, $tries + 1, HOUR_IN_SECONDS );

		return '';
	}

	public function admin_register() {
		if ( ! $this->verify_nonce_action( 'kmae_register_nonce', 'kmae_register' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', KMAE_Auth::register_url() ) );
			exit;
		}

		$spam = $this->register_spam_check();
		if ( $spam ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $spam, KMAE_Auth::register_url() ) );
			exit;
		}

		if ( empty( $_POST['kmae_terms'] ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'terms_required', KMAE_Auth::register_url() ) );
			exit;
		}

		$register_data = array(
				'display_name'     => isset( $_POST['kmae_display_name'] ) ? wp_unslash( $_POST['kmae_display_name'] ) : '',
				'email'            => isset( $_POST['kmae_email'] ) ? wp_unslash( $_POST['kmae_email'] ) : '',
				'phone'            => isset( $_POST['kmae_phone'] ) ? wp_unslash( $_POST['kmae_phone'] ) : '',
				'password'         => isset( $_POST['kmae_password'] ) ? wp_unslash( $_POST['kmae_password'] ) : '',
				'password_confirm' => isset( $_POST['kmae_password_confirm'] ) ? wp_unslash( $_POST['kmae_password_confirm'] ) : '',
				'account_type'     => isset( $_POST['kmae_account_type'] ) ? wp_unslash( $_POST['kmae_account_type'] ) : '',
				'company_name'     => isset( $_POST['kmae_company_name'] ) ? wp_unslash( $_POST['kmae_company_name'] ) : '',
				'address'          => isset( $_POST['kmae_company_address'] ) ? wp_unslash( $_POST['kmae_company_address'] ) : '',
				'county'           => isset( $_POST['kmae_company_county'] ) ? wp_unslash( $_POST['kmae_company_county'] ) : '',
				'city'             => isset( $_POST['kmae_company_city'] ) ? wp_unslash( $_POST['kmae_company_city'] ) : '',
				'email_news'       => true,
				'email_offers'     => true,
		);
		/* Autóátvétel (céges regisztráció): csak ha a blokk az űrlapon volt. */
		if ( ! empty( $_POST['kmae_company_offer_flags'] ) ) {
			$register_data['buys']    = ! empty( $_POST['kmae_company_buys'] );
			$register_data['tradein'] = ! empty( $_POST['kmae_company_tradein'] );
		}
		$result = KMAE_Auth::register( $register_data );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), KMAE_Auth::register_url() ) );
			exit;
		}

		/* Az ÁSZF és Felhasználási Feltételek elfogadása: verzió + időpont. */
		KMAE_Legal::record_acceptance( (int) $result );

		wp_safe_redirect( add_query_arg( 'kmae_success', 'registered', KMAE_Auth::login_url() ) );
		exit;
	}

	public function admin_lost_password() {
		if ( ! $this->verify_nonce_action( 'kmae_lost_password_nonce', 'kmae_lost_password' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', KMAE_Auth::lost_password_url() ) );
			exit;
		}

		$email  = isset( $_POST['kmae_email'] ) ? wp_unslash( $_POST['kmae_email'] ) : '';
		$result = KMAE_Auth::request_password_reset( $email );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), KMAE_Auth::lost_password_url() ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'reset_sent', KMAE_Auth::login_url() ) );
		exit;
	}

	public function admin_reset_password() {
		if ( ! $this->verify_nonce_action( 'kmae_reset_password_nonce', 'kmae_reset_password' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', KMAE_Auth::lost_password_url() ) );
			exit;
		}

		$key   = isset( $_POST['kmae_reset_key'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_reset_key'] ) ) : '';
		$login = isset( $_POST['kmae_reset_login'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_reset_login'] ) ) : '';
		$pass1 = isset( $_POST['kmae_password'] ) ? wp_unslash( $_POST['kmae_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pass2 = isset( $_POST['kmae_password_confirm'] ) ? wp_unslash( $_POST['kmae_password_confirm'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = KMAE_Auth::reset_password_with_key( $login, $key, $pass1, $pass2 );
		if ( is_wp_error( $result ) ) {
			$redirect = KMAE_Auth::reset_password_url( $key, $login );
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'password_set', KMAE_Auth::login_url() ) );
		exit;
	}

	/**
	 * Public contact form → email inbox.
	 */
	public function admin_contact_form() {
		$redirect = KMAE_Pages::url( 'kapcsolat' );
		if ( ! $redirect ) {
			$redirect = home_url( '/kapcsolat/' );
		}

		if ( ! $this->verify_nonce_action( 'kmae_contact_form_nonce', 'kmae_contact_form' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $redirect ) );
			exit;
		}

		// Honeypot: bots fill this; humans never see it.
		$honeypot = isset( $_POST['kmae_website'] ) ? trim( (string) wp_unslash( $_POST['kmae_website'] ) ) : '';
		if ( '' !== $honeypot ) {
			wp_safe_redirect( add_query_arg( 'kmae_success', 'contact_sent', $redirect ) );
			exit;
		}

		$ip_key = 'kmae_contact_' . md5( $this->client_ip() );
		$hits   = (int) get_transient( $ip_key );
		if ( $hits >= 5 ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'rate_limited', $redirect ) );
			exit;
		}

		$name    = isset( $_POST['kmae_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_contact_name'] ) ) : '';
		$email   = isset( $_POST['kmae_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['kmae_contact_email'] ) ) : '';
		$topic   = isset( $_POST['kmae_contact_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_contact_topic'] ) ) : '';
		$message = isset( $_POST['kmae_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kmae_contact_message'] ) ) : '';

		$allowed_topics = array(
			'altalanos'   => __( 'Általános kérdés', 'ki-mit-adna-erte' ),
			'fiok'        => __( 'Fiók / belépés', 'ki-mit-adna-erte' ),
			'hirdetes'    => __( 'Hirdetés', 'ki-mit-adna-erte' ),
			'technikai'   => __( 'Technikai hiba', 'ki-mit-adna-erte' ),
			'egyeb'       => __( 'Egyéb', 'ki-mit-adna-erte' ),
		);

		if ( '' === $name || '' === $email || '' === $message || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'missing_fields', $redirect ) );
			exit;
		}

		if ( ! isset( $allowed_topics[ $topic ] ) ) {
			$topic = 'altalanos';
		}

		if ( function_exists( 'mb_strlen' ) ) {
			$msg_len = mb_strlen( $message );
		} else {
			$msg_len = strlen( $message );
		}

		if ( $msg_len < 10 ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'message_short', $redirect ) );
			exit;
		}

		if ( $msg_len > 5000 ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'message_long', $redirect ) );
			exit;
		}

		$to      = KMAE_Settings::contact_form_email();
		$subject = sprintf(
			/* translators: 1: site name, 2: topic label */
			__( '[%1$s] Kapcsolat: %2$s', 'ki-mit-adna-erte' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$allowed_topics[ $topic ]
		);

		$body_lines = array(
			__( 'Új üzenet érkezett a kapcsolat űrlapról.', 'ki-mit-adna-erte' ),
			'',
			sprintf( __( 'Név: %s', 'ki-mit-adna-erte' ), $name ),
			sprintf( __( 'E-mail: %s', 'ki-mit-adna-erte' ), $email ),
			sprintf( __( 'Téma: %s', 'ki-mit-adna-erte' ), $allowed_topics[ $topic ] ),
			'',
			__( 'Üzenet:', 'ki-mit-adna-erte' ),
			$message,
			'',
			sprintf( __( 'Oldal: %s', 'ki-mit-adna-erte' ), home_url( '/kapcsolat/' ) ),
			sprintf( __( 'Időpont: %s', 'ki-mit-adna-erte' ), wp_date( 'Y-m-d H:i:s' ) ),
		);

		$headers = array(
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		);

		$sent = KMAE_Brand::send_mail( $to, $subject, implode( "\n", $body_lines ), $headers );

		set_transient( $ip_key, $hits + 1, HOUR_IN_SECONDS );

		if ( ! $sent ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'send_failed', $redirect ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'contact_sent', $redirect ) );
		exit;
	}

	private function get_listings_redirect_url() {
		return home_url( '/sajat-hirdetesek/' );
	}

	public function admin_update_listing() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url( '/hirdetes-feladasa/' ) ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_update_listing_nonce', 'kmae_update_listing' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $this->get_listings_redirect_url() ) );
			exit;
		}

		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$user_id    = get_current_user_id();
		if ( ! KMAE_Listings::user_owns_listing( $listing_id, $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'forbidden', $this->get_listings_redirect_url() ) );
			exit;
		}

		if ( ! KMAE_Listings::can_edit_listing( $listing_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'listing_locked', $this->get_listings_redirect_url() ) );
			exit;
		}

		$title = isset( $_POST['kmae_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_title'] ) ) : '';
		$desc  = isset( $_POST['kmae_description'] ) ? wp_kses_post( wp_unslash( $_POST['kmae_description'] ) ) : '';
		$county = isset( $_POST['kmae_county'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_county'] ) ) : '';
		$city   = isset( $_POST['kmae_city'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_city'] ) ) : '';

		if ( KMAE_Auth::is_company( $user_id ) ) {
			$county = KMAE_Company::get_county( $user_id );
			$city   = KMAE_Company::get_city( $user_id );
		}

		$location_error = self::listing_location_error_code( $user_id, $county, $city );
		if ( empty( $title ) || empty( $desc ) || '' !== $location_error ) {
			$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : add_query_arg( 'kmae_edit', $listing_id, home_url( '/hirdetes-feladasa/' ) );
			$code     = ( ! empty( $title ) && ! empty( $desc ) && '' !== $location_error ) ? $location_error : 'missing_fields';
			wp_safe_redirect( add_query_arg( 'kmae_error', $code, $redirect ) );
			exit;
		}

		$status      = KMAE_Meta::get_status( $listing_id );
		$is_awaiting = KMAE_Meta::STATUS_AWAITING_PHOTOS === $status;

		if ( KMAE_Moderation::is_enabled() ) {
			$vehicle_data = KMAE_Vehicle::parse_post_data();
			$vehicle_data['title']       = $title;
			$vehicle_data['description'] = wp_strip_all_tags( $desc );
			$existing_gallery            = KMAE_Query::get_gallery_ids( $listing_id );
			$new_images                  = KMAE_Moderation::count_uploaded_images();
			$image_count                 = count( $existing_gallery ) + $new_images;
			if ( $image_count < KMAE_Moderation::MIN_IMAGES ) {
				$image_count = max( $image_count, count( $existing_gallery ) );
			}
			$require_images = ! $is_awaiting;
			$moderation     = KMAE_Moderation::validate( $vehicle_data, max( $image_count, count( $existing_gallery ) ), $require_images );
			if ( is_wp_error( $moderation ) ) {
				$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : add_query_arg( 'kmae_edit', $listing_id, home_url( '/hirdetes-feladasa/' ) );
				$reasons  = $moderation->get_error_data()['reasons'] ?? array( $moderation->get_error_message() );
				KMAE_Moderation::stash_errors_for_user( get_current_user_id(), $reasons );
				wp_safe_redirect( add_query_arg( 'kmae_error', 'moderation_failed', $redirect ) );
				exit;
			}
		}

		wp_update_post(
			array(
				'ID'           => $listing_id,
				'post_title'   => $title,
				'post_content' => $desc,
			)
		);

		$meta_saved = KMAE_Listings::save_meta_from_post( $listing_id, false );
		if ( is_wp_error( $meta_saved ) ) {
			$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : add_query_arg( 'kmae_edit', $listing_id, home_url( '/hirdetes-feladasa/' ) );
			wp_safe_redirect( add_query_arg( 'kmae_error', $meta_saved->get_error_code(), $redirect ) );
			exit;
		}

		$redirect = ! empty( $_POST['kmae_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['kmae_redirect'] ) ) : add_query_arg( 'kmae_edit', $listing_id, home_url( '/hirdetes-feladasa/' ) );

		if ( KMAE_Photo_Motor::is_enabled() ) {
			$photos_complete = KMAE_Photo_Motor::count_required_filled( $listing_id ) >= KMAE_Photo_Motor::required_count();
			$has_new_photos  = KMAE_Photo_Motor::has_any_upload();

			if ( $is_awaiting && ! $has_new_photos && ! $photos_complete ) {
				// Allow saving listing details from computer without photos yet.
				wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_updated', $this->get_listings_redirect_url() ) );
				exit;
			}

			$photo_ok = KMAE_Photo_Motor::validate_request( $listing_id, false );
			if ( is_wp_error( $photo_ok ) ) {
				$reasons = $photo_ok->get_error_data()['reasons'] ?? array( $photo_ok->get_error_message() );
				KMAE_Moderation::stash_errors_for_user( get_current_user_id(), is_array( $reasons ) ? $reasons : array( $photo_ok->get_error_message() ) );
				wp_safe_redirect( add_query_arg( 'kmae_error', 'photo_slots_required', $redirect ) );
				exit;
			}

			$gallery = KMAE_Listings::handle_image_uploads( $listing_id, KMAE_Query::get_gallery_ids( $listing_id ) );
			if ( is_wp_error( $gallery ) ) {
				$reasons = $gallery->get_error_data()['reasons'] ?? array( $gallery->get_error_message() );
				KMAE_Moderation::stash_errors_for_user( get_current_user_id(), $reasons );
				wp_safe_redirect( add_query_arg( 'kmae_error', $gallery->get_error_code(), $redirect ) );
				exit;
			}

			if ( $is_awaiting ) {
				if ( KMAE_Listings::activate_if_photos_ready( $listing_id ) ) {
					wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_activated', $this->get_listings_redirect_url() ) );
					exit;
				}
				wp_safe_redirect( add_query_arg( 'kmae_error', 'photo_slots_required', $redirect ) );
				exit;
			}
		} else {
			$existing = KMAE_Query::get_gallery_ids( $listing_id );
			$existing = KMAE_Listings::apply_gallery_edits( $listing_id, $existing );
			KMAE_Listings::handle_image_uploads( $listing_id, $existing );

			/* Kép nélkül nem lehet élő a hirdetés; új kép után újra az lehet. */
			$photo_state = KMAE_Listings::sync_status_with_photos( $listing_id );
			if ( 'awaiting' === $photo_state ) {
				wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_awaiting_photos', $this->get_listings_redirect_url() ) );
				exit;
			}
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_updated', $this->get_listings_redirect_url() ) );
		exit;
	}

	public function admin_delete_listing() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_listings_redirect_url() ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_delete_listing_nonce', 'kmae_delete_listing' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $this->get_listings_redirect_url() ) );
			exit;
		}

		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		if ( ! KMAE_Listings::can_delete_listing( $listing_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'listing_locked', $this->get_listings_redirect_url() ) );
			exit;
		}

		$result     = KMAE_Listings::delete_listing( $listing_id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $this->get_listings_redirect_url() ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_deleted', $this->get_listings_redirect_url() ) );
		exit;
	}

	public function admin_reactivate_listing() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_listings_redirect_url() ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_reactivate_listing_nonce', 'kmae_reactivate_listing' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $this->get_listings_redirect_url() ) );
			exit;
		}

		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$result     = KMAE_Listings::reactivate_listing( $listing_id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $this->get_listings_redirect_url() ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_reactivated', $this->get_listings_redirect_url() ) );
		exit;
	}

	public function admin_renew_listing() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_listings_redirect_url() ) );
			exit;
		}

		if ( ! $this->verify_nonce_action( 'kmae_renew_listing_nonce', 'kmae_renew_listing' ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'nonce_failed', $this->get_listings_redirect_url() ) );
			exit;
		}

		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$result     = KMAE_Listings::renew_listing( $listing_id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $this->get_listings_redirect_url() ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_renewed', $this->get_listings_redirect_url() ) );
		exit;
	}

	/**
	 * Hirdetés helyszíne → hibakód a `form-listing.php` üzenettömbjéhez ('' = jó).
	 * Magánszemély: a beírt megye + település; cég: a profil telephelye.
	 *
	 * @param int    $user_id User.
	 * @param string $county  Megye slug.
	 * @param string $city    Település.
	 * @return string
	 */
	private static function listing_location_error_code( $user_id, $county, $city ) {
		$problem = KMAE_Counties::location_error( $county, $city );
		if ( '' === $problem ) {
			return '';
		}
		if ( KMAE_Auth::is_company( $user_id ) ) {
			return 'mismatch' === $problem ? 'company_location_mismatch' : 'company_location_required';
		}
		return 'mismatch' === $problem ? 'location_mismatch' : 'missing_fields';
	}

	private function process_create_listing() {
		if ( ! $this->verify_nonce( 'kmae_create_listing_nonce' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$title   = isset( $_POST['kmae_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_title'] ) ) : '';
		$desc    = isset( $_POST['kmae_description'] ) ? wp_kses_post( wp_unslash( $_POST['kmae_description'] ) ) : '';
		$county  = isset( $_POST['kmae_county'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_county'] ) ) : '';
		$city    = isset( $_POST['kmae_city'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_city'] ) ) : '';
		$device  = isset( $_POST['kmae_create_device'] ) ? sanitize_key( wp_unslash( $_POST['kmae_create_device'] ) ) : '';
		$referer = wp_get_referer() ?: home_url( '/hirdetes-feladasa/' );

		$can_create = KMAE_Seller::can_create_listing( $user_id );
		if ( is_wp_error( $can_create ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $can_create->get_error_code(), $referer ) );
			exit;
		}

		if ( KMAE_Auth::is_company( $user_id ) ) {
			$county = KMAE_Company::get_county( $user_id );
			$city   = KMAE_Company::get_city( $user_id );
		}

		$location_error = self::listing_location_error_code( $user_id, $county, $city );
		if ( empty( $title ) || empty( $desc ) || '' !== $location_error ) {
			$code = ( ! empty( $title ) && ! empty( $desc ) && '' !== $location_error ) ? $location_error : 'missing_fields';
			wp_safe_redirect( add_query_arg( 'kmae_error', $code, $referer ) );
			exit;
		}

		if ( empty( $_POST['kmae_price_mode'] ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'price_mode_required', $referer ) );
			exit;
		}

		$photo_motor   = KMAE_Photo_Motor::is_enabled();
		$defer_photos  = false;
		if ( $photo_motor ) {
			if ( ! in_array( $device, array( 'computer', 'phone', 'tablet' ), true ) ) {
				wp_safe_redirect( add_query_arg( 'kmae_error', 'device_required', $referer ) );
				exit;
			}
			$defer_photos = ( 'computer' === $device );
		}

		$vehicle_data = KMAE_Vehicle::parse_post_data();
		$vehicle_data['title']       = $title;
		$vehicle_data['description'] = wp_strip_all_tags( $desc );
		$image_count                 = KMAE_Moderation::count_uploaded_images();
		$moderation                  = KMAE_Moderation::validate( $vehicle_data, $image_count, ! $defer_photos );

		if ( is_wp_error( $moderation ) ) {
			$reasons = $moderation->get_error_data()['reasons'] ?? array( $moderation->get_error_message() );
			KMAE_Moderation::stash_errors_for_user( $user_id, $reasons );
			wp_safe_redirect( add_query_arg( 'kmae_error', 'moderation_failed', $referer ) );
			exit;
		}

		if ( $photo_motor && ! $defer_photos ) {
			$photo_ok = KMAE_Photo_Motor::validate_request( 0, true );
			if ( is_wp_error( $photo_ok ) ) {
				$reasons = $photo_ok->get_error_data()['reasons'] ?? array( $photo_ok->get_error_message() );
				KMAE_Moderation::stash_errors_for_user( $user_id, is_array( $reasons ) ? $reasons : array( $photo_ok->get_error_message() ) );
				wp_safe_redirect( add_query_arg( 'kmae_error', 'photo_slots_required', $referer ) );
				exit;
			}
		}

		$initial_status = $defer_photos ? KMAE_Meta::STATUS_AWAITING_PHOTOS : KMAE_Meta::STATUS_ACTIVE;

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $desc,
				'post_type'    => 'kmae_listing',
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'create_failed', $referer ) );
			exit;
		}

		$meta_saved = KMAE_Listings::save_meta_from_post( $post_id, true, $initial_status );
		if ( is_wp_error( $meta_saved ) ) {
			wp_delete_post( $post_id, true );
			wp_safe_redirect( add_query_arg( 'kmae_error', $meta_saved->get_error_code(), $referer ) );
			exit;
		}

		if ( $device ) {
			update_post_meta( $post_id, '_kmae_create_device', $device );
		}

		if ( $defer_photos ) {
			KMAE_Listing_Drafts::discard_submitted( $user_id );
			wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_awaiting_photos', home_url( '/sajat-hirdetesek/' ) ) );
			exit;
		}

		$gallery = KMAE_Listings::handle_image_uploads( $post_id, array() );
		if ( is_wp_error( $gallery ) ) {
			$reasons = $gallery->get_error_data()['reasons'] ?? array( $gallery->get_error_message() );
			KMAE_Moderation::stash_errors_for_user( $user_id, $reasons );
			wp_delete_post( $post_id, true );
			wp_safe_redirect( add_query_arg( 'kmae_error', $gallery->get_error_code(), $referer ) );
			exit;
		}

		if ( empty( $gallery ) || ( $photo_motor && KMAE_Photo_Motor::count_required_filled( $post_id ) < KMAE_Photo_Motor::required_count() ) ) {
			wp_delete_post( $post_id, true );
			wp_safe_redirect( add_query_arg( 'kmae_error', 'upload_failed', $referer ) );
			exit;
		}

		do_action( 'kmae_listing_published', $post_id );

		KMAE_Listing_Drafts::discard_submitted( $user_id );
		wp_safe_redirect( add_query_arg( 'kmae_success', 'listing_created', home_url( '/sajat-hirdetesek/' ) ) );
		exit;
	}

	private function process_start_conversation() {
		if ( ! $this->verify_nonce( 'kmae_start_conversation_nonce' ) ) {
			return;
		}

		$listing_id = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$listing    = get_post( $listing_id );
		$buyer_id   = get_current_user_id();
		if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
			$this->redirect_form_result( 'invalid_listing', true );
		}
		if ( (int) $listing->post_author === (int) $buyer_id ) {
			$this->redirect_form_result( 'own_listing', true );
		}
		if ( KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $listing_id ) ) {
			$this->redirect_form_result( 'closed', true );
		}

		$conversation = KMAE_Conversations::create( $listing_id, $buyer_id, (int) $listing->post_author );
		if ( is_wp_error( $conversation ) ) {
			$this->redirect_form_result( $conversation->get_error_code(), true );
		}

		wp_safe_redirect( add_query_arg( 'kmae_success', 'conversation_started', KMAE_Pages::conversation_thread_url( (int) $conversation->id ) ) );
		exit;
	}

	private function process_conversation_message() {
		if ( ! $this->verify_nonce( 'kmae_conversation_message_nonce' ) ) {
			return;
		}

		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$message         = isset( $_POST['kmae_message_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kmae_message_text'] ) ) : '';
		$request_key     = isset( $_POST['kmae_idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_idempotency_key'] ) ) : '';
		if ( '' === $request_key ) {
			$request_key = 'message:' . get_current_user_id() . ':' . wp_generate_uuid4();
		}

		$conversation = KMAE_Conversations::get( $conversation_id );
		$redirect     = $conversation_id
			? KMAE_Pages::conversation_thread_url( $conversation_id )
			: $this->get_form_redirect_url();
		if ( ! KMAE_Conversations::is_member( $conversation, get_current_user_id() ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'forbidden', $redirect ) );
			exit;
		}

		$attachment_id = 0;
		if ( ! empty( $_FILES['kmae_chat_file']['name'] ) ) {
			$attachment_id = KMAE_Conversations::store_chat_attachment( 'kmae_chat_file' );
		} elseif ( ! empty( $_FILES['kmae_chat_image']['name'] ) ) {
			$attachment_id = KMAE_Conversations::store_chat_attachment( 'kmae_chat_image' );
		}
		if ( is_wp_error( $attachment_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $attachment_id->get_error_code(), $redirect ) );
			exit;
		}

		$result = KMAE_Conversations::save_message( $conversation_id, get_current_user_id(), $message, $request_key, (int) $attachment_id );
		if ( is_wp_error( $result ) ) {
			if ( $attachment_id ) {
				KMAE_Images::delete_attachment( (int) $attachment_id );
			}
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'kmae_success', 'message_sent', $redirect ) );
		exit;
	}

	private function process_chat_offer() {
		if ( ! $this->verify_nonce( 'kmae_chat_offer_nonce' ) ) {
			return;
		}

		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$amount          = isset( $_POST['kmae_offer_amount'] ) ? KMAE_Chat_Offers::parse_amount( wp_unslash( $_POST['kmae_offer_amount'] ) ) : 0;
		$request_key     = isset( $_POST['kmae_idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['kmae_idempotency_key'] ) ) : '';
		if ( '' === $request_key ) {
			$request_key = get_current_user_id() . ':' . wp_generate_uuid4();
		}
		$redirect = $conversation_id
			? KMAE_Pages::conversation_thread_url( $conversation_id )
			: $this->get_form_redirect_url();

		$result = KMAE_Chat_Offers::submit( $conversation_id, get_current_user_id(), $amount, $request_key );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'kmae_success', 'offer_sent', $redirect ) );
		exit;
	}

	/**
	 * Seller: [Elfogadom], [Lezárom] or [Mégse kelt el]; buyer: [Visszavonom] on a chat offer.
	 *
	 * @param string $action accept_chat_offer|cancel_chat_sale|close_chat_sale|retract_chat_offer.
	 */
	private function process_chat_offer_decision( $action ) {
		if ( ! $this->verify_nonce( 'kmae_chat_offer_decision_nonce' ) ) {
			return;
		}

		$offer_id = isset( $_POST['kmae_offer_id'] ) ? absint( $_POST['kmae_offer_id'] ) : 0;
		$offer    = $offer_id ? KMAE_Chat_Offers::get( $offer_id ) : null;
		$redirect = $offer
			? KMAE_Pages::conversation_thread_url( (int) $offer->conversation_id )
			: $this->get_form_redirect_url();

		$handlers = array(
			'accept_chat_offer'  => array( 'accept', 'offer_accepted' ),
			'cancel_chat_sale'   => array( 'cancel_sale', 'sale_cancelled' ),
			'close_chat_sale'    => array( 'close_sale', 'sale_closed' ),
			'retract_chat_offer' => array( 'retract', 'offer_retracted' ),
		);
		$result   = call_user_func( array( 'KMAE_Chat_Offers', $handlers[ $action ][0] ), $offer_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'kmae_success', $handlers[ $action ][1], $redirect ) );
		exit;
	}

	/**
	 * Üzenetek lista piros X: a beszélgetés csak a törlő félnél tűnik el.
	 */
	private function process_hide_conversation() {
		if ( ! $this->verify_nonce( 'kmae_hide_conversation_nonce' ) ) {
			return;
		}

		$redirect        = KMAE_Pages::url( 'uzenetek' );
		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$result          = KMAE_Conversations::hide_for_user( $conversation_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'kmae_success', 'conversation_hidden', $redirect ) );
		exit;
	}

	/**
	 * Chat jobb oszlop: a beszélgetőtárs tiltása / feloldása.
	 *
	 * @param string $action block_user|unblock_user.
	 */
	private function process_user_block( $action ) {
		if ( ! $this->verify_nonce( KMAE_User_Blocks::NONCE ) ) {
			return;
		}

		$user_id         = get_current_user_id();
		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$conversation    = KMAE_Conversations::get( $conversation_id );
		$redirect        = KMAE_Pages::conversation_thread_url( $conversation_id );
		if ( ! KMAE_Conversations::is_member( $conversation, $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', 'forbidden', KMAE_Pages::messages_page_url() ) );
			exit;
		}

		$other_id = (int) $conversation->buyer_id === $user_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
		if ( 'block_user' === $action ) {
			$result = KMAE_User_Blocks::block( $user_id, $other_id );
			if ( is_wp_error( $result ) ) {
				wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
				exit;
			}
			wp_safe_redirect( add_query_arg( 'kmae_success', 'user_blocked', $redirect ) );
			exit;
		}

		KMAE_User_Blocks::unblock( $user_id, $other_id );
		wp_safe_redirect( add_query_arg( 'kmae_success', 'user_unblocked', $redirect ) );
		exit;
	}

	/**
	 * Bejelentés a hirdetésről vagy a chatből.
	 */
	private function process_report() {
		if ( ! $this->verify_nonce( 'kmae_report_nonce' ) ) {
			return;
		}

		$context         = isset( $_POST['kmae_report_context'] ) ? sanitize_key( wp_unslash( $_POST['kmae_report_context'] ) ) : '';
		$listing_id      = isset( $_POST['kmae_listing_id'] ) ? absint( $_POST['kmae_listing_id'] ) : 0;
		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		if ( KMAE_Reports::CONTEXT_CHAT === $context && $conversation_id ) {
			$redirect = KMAE_Pages::conversation_thread_url( $conversation_id );
		} elseif ( $listing_id && get_permalink( $listing_id ) ) {
			$redirect = get_permalink( $listing_id );
		} else {
			$redirect = $this->get_form_redirect_url();
		}

		$result = KMAE_Reports::submit(
			array(
				'context'         => $context,
				'listing_id'      => $listing_id,
				'conversation_id' => $conversation_id,
				'reporter_id'     => get_current_user_id(),
				'reason'          => isset( $_POST['kmae_report_reason'] ) ? wp_unslash( $_POST['kmae_report_reason'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'details'         => isset( $_POST['kmae_report_details'] ) ? wp_unslash( $_POST['kmae_report_details'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'kmae_error', $result->get_error_code(), $redirect ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'kmae_success', 'report_sent', $redirect ) );
		exit;
	}

}
