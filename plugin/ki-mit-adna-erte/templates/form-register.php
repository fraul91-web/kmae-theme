<?php
/**
 * Registration form.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	/* Az első Google-belépés után a hiányzó adatokat kérjük be, nem a
	   „már be vagy jelentkezve" üzenetet mutatjuk. */
	if ( class_exists( 'KMAE_Google_Auth' ) && KMAE_Google_Auth::needs_completion( get_current_user_id() ) ) {
		include KMAE_PLUGIN_DIR . 'templates/partials/google-complete.php';
		return;
	}
	?>
	<div class="kmae-auth">
		<div class="kmae-notice kmae-notice-info">
			<p><?php esc_html_e( 'Már be vagy jelentkezve.', 'ki-mit-adna-erte' ); ?></p>
		</div>
		<a href="<?php echo esc_url( home_url( '/sajat-hirdetesek/' ) ); ?>" class="kmae-btn kmae-btn-primary"><?php esc_html_e( 'Saját hirdetéseim', 'ki-mit-adna-erte' ); ?></a>
	</div>
	<?php
	return;
}

$error = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';

$error_messages = array(
	'missing_fields'         => __( 'Kérjük, tölts ki minden mezőt.', 'ki-mit-adna-erte' ),
	'invalid_email'          => __( 'Érvénytelen e-mail cím.', 'ki-mit-adna-erte' ),
	'phone_invalid'          => __( 'A telefonszám kilenc számjegy, például 30 111 11 11.', 'ki-mit-adna-erte' ),
	'email_exists'           => __( 'Ez az e-mail cím már regisztrálva van.', 'ki-mit-adna-erte' ),
	'weak_password'          => __( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ),
	'password_mismatch'      => __( 'A két jelszó nem egyezik.', 'ki-mit-adna-erte' ),
	'terms_required'         => __( 'El kell fogadnod az ÁSZF-et és a Felhasználási Feltételeket.', 'ki-mit-adna-erte' ),
	'account_type_required'  => __( 'Válaszd ki, hogy magánszemély vagy cég vagy.', 'ki-mit-adna-erte' ),
	'company_name_required'  => __( 'Add meg a cégnevet.', 'ki-mit-adna-erte' ),
	'company_location_required' => __( 'Add meg a céges telephelyet.', 'ki-mit-adna-erte' ),
	'nonce_failed'           => __( 'A munkamenet lejárt — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
	'too_fast'               => __( 'Túl gyors volt a beküldés. Nézd át az adatokat, és küldd be újra.', 'ki-mit-adna-erte' ),
	'too_many'               => __( 'Túl sok regisztrációs kísérlet érkezett. Próbáld újra később.', 'ki-mit-adna-erte' ),
);

$terms_url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'feltetelek' ) : home_url( '/feltetelek/' );
?>
<div class="kmae-auth kmae-auth--register">
	<header class="kmae-auth__header">
		<p class="kmae-auth__eyebrow"><?php esc_html_e( 'Fiók létrehozása', 'ki-mit-adna-erte' ); ?></p>
		<h2 class="kmae-auth__title"><?php esc_html_e( 'Regisztráció', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-auth__lead"><?php esc_html_e( 'Hozd létre a fiókod. Hirdetés feladásához később e-mail és telefon megerősítés szükséges.', 'ki-mit-adna-erte' ); ?></p>
	</header>

	<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
		<div class="kmae-notice kmae-notice-error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
	<?php endif; ?>

	<?php
	$google_redirect = home_url( '/' );
	$google_label    = __( 'Regisztráció Google-fiókkal', 'ki-mit-adna-erte' );
	$google_layout   = 'top';
	include KMAE_PLUGIN_DIR . 'templates/partials/google-button.php';
	?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-form kmae-auth__form">
		<?php wp_nonce_field( 'kmae_register', 'kmae_register_nonce' ); ?>
		<input type="hidden" name="action" value="kmae_register" />

		<?php
		/* Botszűrés. Két egyszerű eszköz, külső szolgáltatás nélkül:
		   1) Csapda-mező: ember nem látja (a képernyőn kívülre tesszük, nem
		      display:none-nal, mert azt a botok egy része felismeri), így üresen
		      marad. A botok viszont minden mezőt kitöltenek.
		   2) Aláírt időbélyeg: mikor töltődött be az űrlap. Az aláírás miatt nem
		      hamisítható. Ha a beküldés pár másodpercen belül érkezik, az nem
		      emberi kitöltés.
		   A mezők neve szándékosan hétköznapi, hogy a bot bedőljön neki. */
		$kmae_reg_time = time();
		?>
		<div class="kmae-form-extra" aria-hidden="true">
			<label for="kmae_reg_website"><?php esc_html_e( 'Ezt a mezőt hagyd üresen', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" name="kmae_website" id="kmae_reg_website" value="" tabindex="-1" autocomplete="off" />
		</div>
		<input type="hidden" name="kmae_form_time" value="<?php echo esc_attr( $kmae_reg_time ); ?>" />
		<input type="hidden" name="kmae_form_token" value="<?php echo esc_attr( wp_hash( 'kmae_register_' . $kmae_reg_time ) ); ?>" />

		<section class="kmae-auth__section">
			<h3 class="kmae-auth__section-title"><?php esc_html_e( '1. Fiók típusa', 'ki-mit-adna-erte' ); ?></h3>
			<p class="kmae-auth__section-hint"><?php esc_html_e( 'Ettől függnek a későbbi szabályok — függetlenül attól, hogy eladsz vagy vásárolsz.', 'ki-mit-adna-erte' ); ?></p>

			<fieldset class="kmae-fieldset kmae-account-type">
				<legend class="screen-reader-text"><?php esc_html_e( 'Fiók típusa', 'ki-mit-adna-erte' ); ?></legend>
				<div class="kmae-account-type__options">
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
					<label for="kmae_reg_company_name"><?php esc_html_e( 'Cégnév *', 'ki-mit-adna-erte' ); ?></label>
					<input type="text" name="kmae_company_name" id="kmae_reg_company_name" placeholder="<?php esc_attr_e( 'pl. Autópark Kft.', 'ki-mit-adna-erte' ); ?>" />
					<p class="kmae-hint"><?php esc_html_e( 'Ez jelenik meg a kereskedői profilodon.', 'ki-mit-adna-erte' ); ?></p>
				</div>
				<div class="kmae-field-row">
					<div class="kmae-field">
						<label for="kmae_reg_company_county"><?php esc_html_e( 'Telephely megyéje *', 'ki-mit-adna-erte' ); ?></label>
						<select name="kmae_company_county" id="kmae_reg_company_county">
							<option value=""><?php esc_html_e( 'Válassz megyét', 'ki-mit-adna-erte' ); ?></option>
							<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="kmae-field">
						<label for="kmae_reg_company_city"><?php esc_html_e( 'Telephely városa *', 'ki-mit-adna-erte' ); ?></label>
						<input type="text" name="kmae_company_city" id="kmae_reg_company_city" placeholder="<?php esc_attr_e( 'Például: Szeged', 'ki-mit-adna-erte' ); ?>" />
					</div>
				</div>
				<div class="kmae-field">
					<label for="kmae_reg_company_address"><?php esc_html_e( 'Cím *', 'ki-mit-adna-erte' ); ?></label>
					<input type="text" name="kmae_company_address" id="kmae_reg_company_address" placeholder="<?php esc_attr_e( 'utca, házszám', 'ki-mit-adna-erte' ); ?>" />
				</div>
			</div>
		</section>

		<section class="kmae-auth__section">
			<h3 class="kmae-auth__section-title"><?php esc_html_e( '2. Személyes adatok', 'ki-mit-adna-erte' ); ?></h3>

			<div class="kmae-field" data-kmae-private-name>
				<label for="kmae_reg_display_name"><?php esc_html_e( 'Keresztnév vagy Becenév', 'ki-mit-adna-erte' ); ?></label>
				<?php /* A címke keresztnevet vagy becenevet kér — a példa is ilyen
					legyen, különben teljes nevet írnak be. Ez a név jelenik meg a
					köszönésben. */ ?>
				<input type="text" name="kmae_display_name" id="kmae_reg_display_name" placeholder="<?php esc_attr_e( 'pl. Anna', 'ki-mit-adna-erte' ); ?>" />
				<p class="kmae-hint"><?php esc_html_e( 'Így fogunk megszólítani.', 'ki-mit-adna-erte' ); ?></p>
			</div>

			<div class="kmae-field-row">
				<div class="kmae-field">
					<label for="kmae_reg_email"><?php esc_html_e( 'E-mail cím *', 'ki-mit-adna-erte' ); ?></label>
					<input type="email" name="kmae_email" id="kmae_reg_email" required autocomplete="email" placeholder="nev@email.hu" />
				</div>
				<div class="kmae-field">
					<label for="kmae_reg_phone"><?php esc_html_e( 'Telefonszám *', 'ki-mit-adna-erte' ); ?></label>
					<div class="kmae-phone-input">
						<span class="kmae-phone-input__prefix" aria-hidden="true">+36</span>
						<input
							type="tel"
							name="kmae_phone"
							id="kmae_reg_phone"
							data-kmae-phone
							inputmode="numeric"
							autocomplete="tel-national"
							maxlength="12"
							placeholder="30 111 11 11"
							required
						/>
					</div>
					<p class="kmae-hint"><?php esc_html_e( 'Később SMS-sel erősítheted meg.', 'ki-mit-adna-erte' ); ?></p>
				</div>
			</div>

			<div class="kmae-field-row">
				<div class="kmae-field">
					<label for="kmae_reg_password"><?php esc_html_e( 'Jelszó *', 'ki-mit-adna-erte' ); ?></label>
					<input type="password" name="kmae_password" id="kmae_reg_password" required minlength="8" autocomplete="new-password" />
				</div>
				<div class="kmae-field">
					<label for="kmae_reg_password2"><?php esc_html_e( 'Jelszó újra *', 'ki-mit-adna-erte' ); ?></label>
					<input type="password" name="kmae_password_confirm" id="kmae_reg_password2" required minlength="8" autocomplete="new-password" />
				</div>
			</div>
			<p class="kmae-hint kmae-auth__pw-hint"><?php esc_html_e( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ); ?></p>
		</section>

		<section class="kmae-auth__section">
			<h3 class="kmae-auth__section-title"><?php esc_html_e( '3. Feltételek', 'ki-mit-adna-erte' ); ?></h3>

			<div class="kmae-auth__checks">
				<label class="kmae-checkbox">
					<input type="checkbox" name="kmae_terms" value="1" required />
					<span>
						<?php esc_html_e( 'Elfogadom az', 'ki-mit-adna-erte' ); ?>
						<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'ÁSZF-et és a Felhasználási Feltételeket', 'ki-mit-adna-erte' ); ?></a>
					</span>
				</label>
			</div>
		</section>

		<button type="submit" class="kmae-btn kmae-btn-primary kmae-btn--register kmae-auth__submit"><?php esc_html_e( 'Fiók létrehozása', 'ki-mit-adna-erte' ); ?></button>
	</form>

	<div class="kmae-auth-switch">
		<p class="kmae-auth-switch__text"><?php esc_html_e( 'Van már fiókod?', 'ki-mit-adna-erte' ); ?></p>
		<a class="kmae-auth-switch__btn kmae-auth-switch__btn--login" href="<?php echo esc_url( KMAE_Auth::login_url() ); ?>"><?php esc_html_e( 'Bejelentkezés', 'ki-mit-adna-erte' ); ?></a>
	</div>
</div>
