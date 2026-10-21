<?php
/**
 * Jogi dokumentumok: ÁSZF és Felhasználási Feltételek (egy dokumentum).
 *
 * - Verziószám + hatálybalépés: ha a szöveg érdemben változik, a VERSION
 *   emelendő, és az elfogadás újra kérhető.
 * - Az üzemeltető (impresszum) adatai EGY helyen: operator(). A „[…]”
 *   értékek kitöltendők; amíg vannak, az admin figyelmeztetést lát az oldalon.
 * - Az elfogadás rögzítése regisztrációkor (verzió + időpont, user meta).
 * - Akinek nincs elfogadása az aktuális verzióról (régi fiók, új verzió), annak
 *   egyszer felugró ablak kéri az elfogadást (AJAX kmae_accept_terms).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Legal {

	const TERMS_VERSION   = '1.0';
	const TERMS_EFFECTIVE = '[HATÁLYBALÉPÉS DÁTUMA, pl. 2026. október 15.]';
	const META_ACCEPTED   = 'kmae_terms_accepted';
	const ACCEPT_NONCE    = 'kmae_accept_terms';

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_prompt' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_prompt' ) );
		add_action( 'wp_ajax_kmae_accept_terms', array( __CLASS__, 'ajax_accept' ) );
	}

	/**
	 * Az üzemeltető és a szolgáltatás adatai. A „[…]” értékeket ki kell tölteni.
	 *
	 * @return array<string,string>
	 */
	public static function operator() {
		return array(
			'name'        => '[ÜZEMELTETŐ NEVE / CÉGNEVE]',
			'form'        => '[CÉGFORMA, pl. egyéni vállalkozó / Kft.]',
			'seat'        => '[SZÉKHELY / LAKCÍM]',
			'reg_number'  => '[CÉGJEGYZÉKSZÁM vagy NYILVÁNTARTÁSI SZÁM]',
			'tax_number'  => '[ADÓSZÁM]',
			'email'       => '[ÜGYFÉLSZOLGÁLATI E-MAIL CÍM]',
			'phone'       => '[TELEFONSZÁM – opcionális]',
			'host'        => 'RackForest Zrt.',
			'host_seat'   => '[TÁRHELYSZOLGÁLTATÓ SZÉKHELYE]',
			'host_email'  => '[TÁRHELYSZOLGÁLTATÓ ELÉRHETŐSÉGE]',
			'mail_provider' => '[E-MAIL KÜLDŐ SZOLGÁLTATÓ NEVE ÉS SZÉKHELYE, pl. a tárhely levelezőszervere]',
			'conciliator' => '[ILLETÉKES BÉKÉLTETŐ TESTÜLET – a székhely szerinti megyei/budapesti testület]',
			'fees'        => 'Az Albira használata jelenleg díjmentes.',
			'site'        => home_url( '/' ),
		);
	}

	/**
	 * Kitöltetlen („[…]” jelölésű) adatok — az admin figyelmeztetéshez.
	 *
	 * @return array<int,string>
	 */
	public static function missing_fields() {
		$missing = array();
		foreach ( self::operator() as $value ) {
			if ( false !== strpos( (string) $value, '[' ) ) {
				$missing[] = (string) $value;
			}
		}
		if ( false !== strpos( self::TERMS_EFFECTIVE, '[' ) ) {
			$missing[] = self::TERMS_EFFECTIVE;
		}
		return $missing;
	}

	/**
	 * Elfogadás rögzítése: melyik verziót, mikor (UTC).
	 *
	 * @param int $user_id User.
	 */
	public static function record_acceptance( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}
		update_user_meta(
			$user_id,
			self::META_ACCEPTED,
			array(
				'version' => self::TERMS_VERSION,
				'at'      => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Kell-e még elfogadnia az aktuális verziót?
	 *
	 * @param int $user_id User.
	 * @return bool
	 */
	public static function needs_acceptance( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		$accepted = get_user_meta( $user_id, self::META_ACCEPTED, true );
		return ! is_array( $accepted ) || empty( $accepted['version'] ) || self::TERMS_VERSION !== (string) $accepted['version'];
	}

	/**
	 * Az ablak csak bejelentkezett, elfogadással még nem rendelkező felhasználónak
	 * jelenik meg — a Google-regisztráció befejezése közben és magukon a jogi
	 * oldalakon nem (ott épp olvassa őket).
	 *
	 * @return bool
	 */
	private static function should_prompt() {
		if ( ! is_user_logged_in() || is_admin() || wp_doing_ajax() || is_customize_preview() ) {
			return false;
		}
		$user_id = get_current_user_id();
		if ( ! self::needs_acceptance( $user_id ) ) {
			return false;
		}
		if ( class_exists( 'KMAE_Google_Auth' ) && KMAE_Google_Auth::needs_completion( $user_id ) ) {
			return false;
		}
		$post = get_post();
		if ( is_singular() && $post && (
			has_shortcode( (string) $post->post_content, 'kmae_terms' )
			|| has_shortcode( (string) $post->post_content, 'kmae_privacy' )
		) ) {
			return false;
		}
		return true;
	}

	public static function enqueue_prompt() {
		if ( ! self::should_prompt() ) {
			return;
		}
		wp_enqueue_style( 'kmae-terms-accept', KMAE_PLUGIN_URL . 'assets/css/terms-accept.css', array(), KMAE_VERSION );
		wp_enqueue_script( 'kmae-terms-accept', KMAE_PLUGIN_URL . 'assets/js/terms-accept.js', array(), KMAE_VERSION, true );
		wp_localize_script(
			'kmae-terms-accept',
			'kmaeTermsAccept',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::ACCEPT_NONCE ),
				'error'   => __( 'Most nem sikerült menteni. Próbáld újra egy pillanat múlva.', 'ki-mit-adna-erte' ),
			)
		);
	}

	public static function render_prompt() {
		if ( ! self::should_prompt() ) {
			return;
		}
		$terms   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'feltetelek' ) : home_url( '/feltetelek/' );
		$privacy = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'adatvedelem' ) : home_url( '/adatvedelem/' );
		?>
		<div class="kmae-terms-accept" data-kmae-terms-accept>
			<div class="kmae-terms-accept__backdrop"></div>
			<div class="kmae-terms-accept__card" role="dialog" aria-modal="true" aria-labelledby="kmae-terms-accept-title" aria-describedby="kmae-terms-accept-lead">
				<p class="kmae-terms-accept__eyebrow"><?php esc_html_e( 'Albira szabályzat', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-terms-accept__title" id="kmae-terms-accept-title"><?php esc_html_e( 'Frissült az ÁSZF és az Adatkezelési tájékoztató', 'ki-mit-adna-erte' ); ?></h2>
				<p class="kmae-terms-accept__lead" id="kmae-terms-accept-lead"><?php esc_html_e( 'Közzétettük az Albira egységes Általános Szerződési Feltételeit és Felhasználási Feltételeit, valamint az Adatkezelési tájékoztatót. Az Albira további használatához kérjük, olvasd el és fogadd el őket — ezt csak egyszer kérjük.', 'ki-mit-adna-erte' ); ?></p>
				<ul class="kmae-terms-accept__docs">
					<li><a href="<?php echo esc_url( $terms ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'ÁSZF és Felhasználási feltételek', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">↗</span></a></li>
					<li><a href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Adatkezelési tájékoztató', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">↗</span></a></li>
				</ul>
				<p class="kmae-terms-accept__error" data-kmae-terms-accept-error role="alert" hidden></p>
				<div class="kmae-terms-accept__actions">
					<a class="kmae-terms-accept__logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Kijelentkezés', 'ki-mit-adna-erte' ); ?></a>
					<button type="button" class="kmae-terms-accept__primary" data-kmae-terms-accept-btn><?php esc_html_e( 'Elolvastam és elfogadom', 'ki-mit-adna-erte' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	public static function ajax_accept() {
		if ( ! is_user_logged_in() || ! check_ajax_referer( self::ACCEPT_NONCE, 'nonce', false ) ) {
			wp_send_json_error( array(), 403 );
		}
		self::record_acceptance( get_current_user_id() );
		wp_send_json_success();
	}
}
