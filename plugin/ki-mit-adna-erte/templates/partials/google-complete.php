<?php
/**
 * Az első Google-belépés utáni kiegészítő képernyő.
 *
 * A nevet és az e-mail címet a Google adta. Ez a három adat viszont nem
 * jöhet onnan, viszont a platform működéséhez kell — ezért kérjük itt be,
 * egyszer. A következő belépés már egyetlen kattintás.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user  = wp_get_current_user();
$error = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';

$error_messages = array(
	'nonce_failed'          => __( 'A mentés nem sikerült — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
	'phone_required'        => __( 'Add meg a telefonszámod.', 'ki-mit-adna-erte' ),
	'phone_invalid'         => __( 'A telefonszám kilenc számjegy, például 30 111 11 11.', 'ki-mit-adna-erte' ),
	'account_type_required' => __( 'Válaszd ki, hogy magánszemély vagy cég vagy.', 'ki-mit-adna-erte' ),
	'company_name_required' => __( 'Add meg a cégnevet.', 'ki-mit-adna-erte' ),
	'company_location_required' => __( 'Add meg a céges telephelyet.', 'ki-mit-adna-erte' ),
	'company_location_mismatch' => __( 'A telephely települése nem ebben a megyében van — válaszd ki a listából vagy írd be az irányítószámot.', 'ki-mit-adna-erte' ),
	'terms_required'        => __( 'El kell fogadnod az ÁSZF-et és a Felhasználási Feltételeket.', 'ki-mit-adna-erte' ),
);
?>
<div class="kmae-auth kmae-auth--complete">
	<?php
	/* A Google külön adja át a keresztnevet — köszönni azzal természetes,
	   ahogy a regisztrációnál is egy néven szólítunk. Ha nincs használható
	   nevünk, inkább csak köszönünk. */
	$complete_name = KMAE_Auth::first_name( $user->ID );
	if ( '' !== $complete_name && class_exists( 'KMAE_Dashboard' ) && method_exists( 'KMAE_Dashboard', 'greeting_case' ) ) {
		$complete_name = KMAE_Dashboard::greeting_case( $complete_name );
	}
	?>
	<header class="kmae-auth__header kmae-complete-greet">
		<img
			class="kmae-complete-greet__mascot"
			src="https://albira.hu/wp-content/uploads/2026/10/szia7.webp"
			alt=""
			aria-hidden="true"
			width="200"
			height="200"
			decoding="async"
		/>

		<div class="kmae-complete-greet__copy">
			<p class="kmae-auth__eyebrow"><?php esc_html_e( 'Már majdnem kész', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-auth__title">
				<?php
				if ( '' !== $complete_name ) {
					printf(
						/* translators: %s: the user's name from Google */
						esc_html__( 'Szia, %s!', 'ki-mit-adna-erte' ),
						esc_html( $complete_name )
					);
				} else {
					esc_html_e( 'Szia!', 'ki-mit-adna-erte' );
				}
				?>
			</h2>
			<p class="kmae-auth__lead"><?php esc_html_e( 'Még két fontos kérdésre válaszolj, kérlek, a regisztráció befejezéséhez.', 'ki-mit-adna-erte' ); ?></p>
		</div>
	</header>

	<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
		<div class="kmae-notice kmae-notice-error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-form kmae-auth__form">
		<?php wp_nonce_field( 'kmae_google_complete', 'kmae_google_nonce' ); ?>
		<input type="hidden" name="action" value="kmae_google_complete" />

		<div class="kmae-field">
			<label for="kmae_complete_phone"><?php esc_html_e( 'Telefonszám *', 'ki-mit-adna-erte' ); ?></label>
			<?php /* A +36 fix előtag, a mezőbe csak a maradék kilenc számjegy megy. */ ?>
			<div class="kmae-phone-input">
				<span class="kmae-phone-input__prefix" aria-hidden="true">+36</span>
				<input
					type="tel"
					name="kmae_phone"
					id="kmae_complete_phone"
					data-kmae-phone
					inputmode="numeric"
					autocomplete="tel-national"
					maxlength="12"
					placeholder="30 111 11 11"
					required
				/>
			</div>
			<p class="kmae-auth__pw-hint"><?php esc_html_e( 'Privát marad — csak akkor látja a másik fél, ha megállapodtatok.', 'ki-mit-adna-erte' ); ?></p>
		</div>

		<fieldset class="kmae-fieldset">
			<p class="kmae-auth__section-title"><?php esc_html_e( 'Milyen fiókot szeretnél? *', 'ki-mit-adna-erte' ); ?></p>
			<div class="kmae-account-type">
				<label class="kmae-account-type__option">
					<input type="radio" name="kmae_account_type" value="<?php echo esc_attr( KMAE_Auth::ACCOUNT_PRIVATE ); ?>" required data-kmae-account="private" />
					<span class="kmae-account-type__text">
						<strong><?php echo wp_kses( __( 'Magánszemély<br>vagyok', 'ki-mit-adna-erte' ), array( 'br' => array() ) ); ?></strong>
						<span class="kmae-account-type__desc"><?php esc_html_e( 'Saját autó értékesítésére.', 'ki-mit-adna-erte' ); ?></span>
					</span>
				</label>
				<label class="kmae-account-type__option">
					<input type="radio" name="kmae_account_type" value="<?php echo esc_attr( KMAE_Auth::ACCOUNT_COMPANY ); ?>" required data-kmae-account="company" />
					<span class="kmae-account-type__text">
						<strong><?php echo wp_kses( __( 'Cég /<br>autókereskedés', 'ki-mit-adna-erte' ), array( 'br' => array() ) ); ?></strong>
						<span class="kmae-account-type__desc"><?php esc_html_e( 'Korlátlan hirdetés kereskedőknek.', 'ki-mit-adna-erte' ); ?></span>
					</span>
				</label>
			</div>
		</fieldset>

		<div class="kmae-auth__company" data-kmae-company-fields hidden>
			<div class="kmae-field">
				<label for="kmae_complete_company"><?php esc_html_e( 'Cégnév *', 'ki-mit-adna-erte' ); ?></label>
				<input type="text" name="kmae_company_name" id="kmae_complete_company" placeholder="<?php esc_attr_e( 'pl. Autópark Kft.', 'ki-mit-adna-erte' ); ?>" />
			</div>
			<div class="kmae-field-row">
				<div class="kmae-field">
					<label for="kmae_complete_company_zip"><?php esc_html_e( 'Irányítószám', 'ki-mit-adna-erte' ); ?></label>
					<input type="text" id="kmae_complete_company_zip" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="<?php esc_attr_e( 'Például: 6720', 'ki-mit-adna-erte' ); ?>" data-kmae-zip data-county="#kmae_complete_company_county" data-city="#kmae_complete_company_city" />
					<div class="kmae-sp-zip-result" data-kmae-zip-result role="status" aria-live="polite"></div>
				</div>
			</div>
			<div class="kmae-field-row">
				<div class="kmae-field">
					<label for="kmae_complete_company_county"><?php esc_html_e( 'Telephely megyéje *', 'ki-mit-adna-erte' ); ?></label>
					<select name="kmae_company_county" id="kmae_complete_company_county">
						<option value=""><?php esc_html_e( 'Válassz megyét', 'ki-mit-adna-erte' ); ?></option>
						<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="kmae-field">
					<label for="kmae_complete_company_city"><?php esc_html_e( 'Telephely városa *', 'ki-mit-adna-erte' ); ?></label>
					<input type="text" name="kmae_company_city" id="kmae_complete_company_city" autocomplete="off" data-kmae-city-picker="#kmae_complete_company_county" placeholder="<?php esc_attr_e( 'Például: Szeged', 'ki-mit-adna-erte' ); ?>" />
				</div>
			</div>
			<div class="kmae-field">
				<label for="kmae_complete_company_address"><?php esc_html_e( 'Cím *', 'ki-mit-adna-erte' ); ?></label>
				<input type="text" name="kmae_company_address" id="kmae_complete_company_address" placeholder="<?php esc_attr_e( 'utca, házszám', 'ki-mit-adna-erte' ); ?>" />
			</div>
			<?php if ( class_exists( 'KMAE_Dealer_Leads' ) && KMAE_Dealer_Leads::is_enabled() ) : ?>
				<?php /* Autóátvétel: a jelölő mező jelzi a szervernek, hogy a blokk az űrlapon volt (a kipipálatlan doboz nem küld semmit). */ ?>
				<div class="kmae-register-offer-flags">
					<input type="hidden" name="kmae_company_offer_flags" value="1" />
					<p class="kmae-register-offer-flags__title"><?php esc_html_e( 'Autóátvétel', 'ki-mit-adna-erte' ); ?></p>
					<label class="kmae-register-offer-flag">
						<input type="checkbox" name="kmae_company_buys" value="1" />
						<span class="kmae-register-offer-flag__switch" aria-hidden="true"></span>
						<span class="kmae-register-offer-flag__copy">
							<strong><?php esc_html_e( 'Autófelvásárlást vállalunk', 'ki-mit-adna-erte' ); ?></strong>
							<span><?php esc_html_e( 'Magánszemélyek elküldhetik nektek az eladó autójukat.', 'ki-mit-adna-erte' ); ?></span>
						</span>
					</label>
					<label class="kmae-register-offer-flag">
						<input type="checkbox" name="kmae_company_tradein" value="1" />
						<span class="kmae-register-offer-flag__switch" aria-hidden="true"></span>
						<span class="kmae-register-offer-flag__copy">
							<strong><?php esc_html_e( 'Beszámítást vállalunk', 'ki-mit-adna-erte' ); ?></strong>
							<span><?php esc_html_e( 'Fogadtok autót beszámításba egy másik autó vásárlásánál.', 'ki-mit-adna-erte' ); ?></span>
						</span>
					</label>
					<p class="kmae-register-offer-flags__note"><?php esc_html_e( 'Később a Profilomban bármikor módosíthatod.', 'ki-mit-adna-erte' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<label class="kmae-checkbox">
			<input type="checkbox" name="kmae_terms" value="1" required />
			<span>
				<?php
				printf(
					/* translators: 1: terms link, 2: privacy link */
					wp_kses( __( 'Elfogadom az <a href="%1$s" target="_blank" rel="noopener">ÁSZF-et és a Felhasználási Feltételeket</a>, és megismertem az <a href="%2$s" target="_blank" rel="noopener">adatkezelési tájékoztatót</a>.', 'ki-mit-adna-erte' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ),
					esc_url( KMAE_Pages::url( 'feltetelek' ) ),
					esc_url( KMAE_Pages::url( 'adatvedelem' ) )
				);
				?>
			</span>
		</label>

		<button type="submit" class="kmae-btn kmae-btn-primary kmae-btn--register kmae-auth__submit"><?php esc_html_e( 'Kész, mehetünk', 'ki-mit-adna-erte' ); ?></button>
	</form>
</div>
