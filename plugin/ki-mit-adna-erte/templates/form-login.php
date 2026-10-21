<?php
/**
 * Login / lost password / reset password forms.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	$user = wp_get_current_user();
	?>
	<div class="kmae-auth kmae-auth--login kmae-auth-logged-in">
		<div class="kmae-notice kmae-notice-success">
			<p><?php printf( esc_html__( 'Be vagy jelentkezve: %s', 'ki-mit-adna-erte' ), esc_html( KMAE_Dashboard::greeting_case( $user->display_name ) ) ); ?></p>
		</div>
		<p class="kmae-auth__logged-actions">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="kmae-btn kmae-btn-primary"><?php esc_html_e( 'Főoldal', 'ki-mit-adna-erte' ); ?></a>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Kijelentkezés', 'ki-mit-adna-erte' ); ?></a>
		</p>
	</div>
	<?php
	return;
}

$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' );
$error    = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';
$success  = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
$view     = isset( $_GET['kmae_view'] ) ? sanitize_key( wp_unslash( $_GET['kmae_view'] ) ) : 'login';

if ( ! in_array( $view, array( 'login', 'lost', 'reset' ), true ) ) {
	$view = 'login';
}

$reset_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$reset_login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
if ( 'reset' === $view && ( '' === $reset_key || '' === $reset_login ) ) {
	$view = 'lost';
}

$error_messages = array(
	'missing_fields'    => __( 'Kérjük, add meg az e-mail címed és a jelszavad.', 'ki-mit-adna-erte' ),
	'invalid_login'     => __( 'Hibás e-mail vagy jelszó.', 'ki-mit-adna-erte' ),
	'nonce_failed'      => __( 'A munkamenet lejárt — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
	'invalid_email'     => __( 'Érvénytelen e-mail cím.', 'ki-mit-adna-erte' ),
	'weak_password'     => __( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ),
	'password_mismatch' => __( 'A két jelszó nem egyezik.', 'ki-mit-adna-erte' ),
	'invalid_key'       => __( 'Érvénytelen vagy lejárt visszaállító link. Kérj újat.', 'ki-mit-adna-erte' ),
	/* Google belépés */
	'google_state'      => __( 'A Google belépés megszakadt. Próbáld újra.', 'ki-mit-adna-erte' ),
	'google_network'    => __( 'Nem értük el a Google-t. Próbáld újra kicsit később.', 'ki-mit-adna-erte' ),
	'google_token'      => __( 'A Google nem küldött vissza érvényes adatokat. Próbáld újra.', 'ki-mit-adna-erte' ),
	'google_unverified' => __( 'Ehhez a Google-fiókhoz nincs megerősített e-mail cím, ezért nem tudunk beléptetni.', 'ki-mit-adna-erte' ),
	'google_create'     => __( 'A fiók létrehozása nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ),
);

$success_messages = array(
	'registered'   => __( 'Sikeres regisztráció! Most már bejelentkezhetsz.', 'ki-mit-adna-erte' ),
	'reset_sent'   => __( 'Ha van fiók ezzel az e-mail címmel, elküldtük a visszaállító linket.', 'ki-mit-adna-erte' ),
	'password_set' => __( 'Az új jelszavad mentve. Most már bejelentkezhetsz.', 'ki-mit-adna-erte' ),
	'account_deleted' => __( 'A profilod és az adataid törölve lettek.', 'ki-mit-adna-erte' ),
	'email_verified'  => __( 'Sikeres megerősítés! A fiókod aktiválva lett, most már bejelentkezhetsz.', 'ki-mit-adna-erte' ),
);
?>
<div class="kmae-auth kmae-auth--login">
	<?php if ( 'lost' === $view ) : ?>
		<header class="kmae-auth__header">
			<p class="kmae-auth__eyebrow"><?php esc_html_e( 'Fiók helyreállítás', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-auth__title"><?php esc_html_e( 'Elfelejtett jelszó', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-auth__lead"><?php esc_html_e( 'Add meg a regisztrált e-mail címed, és küldünk egy linket az új jelszó megadásához.', 'ki-mit-adna-erte' ); ?></p>
		</header>

		<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
			<div class="kmae-notice kmae-notice-error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-form kmae-auth__form">
			<?php wp_nonce_field( 'kmae_lost_password', 'kmae_lost_password_nonce' ); ?>
			<input type="hidden" name="action" value="kmae_lost_password" />

			<div class="kmae-field">
				<label for="kmae_lost_email"><?php esc_html_e( 'E-mail cím', 'ki-mit-adna-erte' ); ?></label>
				<input type="email" name="kmae_email" id="kmae_lost_email" required autocomplete="email" placeholder="nev@email.hu" />
			</div>

			<button type="submit" class="kmae-btn kmae-btn-primary kmae-btn--login kmae-auth__submit"><?php esc_html_e( 'Visszaállító link küldése', 'ki-mit-adna-erte' ); ?></button>
		</form>

		<p class="kmae-auth__back">
			<a href="<?php echo esc_url( KMAE_Auth::login_url() ); ?>"><?php esc_html_e( 'Vissza a bejelentkezéshez', 'ki-mit-adna-erte' ); ?></a>
		</p>

	<?php elseif ( 'reset' === $view ) : ?>
		<header class="kmae-auth__header">
			<p class="kmae-auth__eyebrow"><?php esc_html_e( 'Fiók helyreállítás', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-auth__title"><?php esc_html_e( 'Új jelszó megadása', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-auth__lead"><?php esc_html_e( 'Válassz egy új, legalább 8 karakteres jelszót.', 'ki-mit-adna-erte' ); ?></p>
		</header>

		<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
			<div class="kmae-notice kmae-notice-error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-form kmae-auth__form">
			<?php wp_nonce_field( 'kmae_reset_password', 'kmae_reset_password_nonce' ); ?>
			<input type="hidden" name="action" value="kmae_reset_password" />
			<input type="hidden" name="kmae_reset_key" value="<?php echo esc_attr( $reset_key ); ?>" />
			<input type="hidden" name="kmae_reset_login" value="<?php echo esc_attr( $reset_login ); ?>" />

			<div class="kmae-field-row">
				<div class="kmae-field">
					<label for="kmae_reset_password"><?php esc_html_e( 'Új jelszó *', 'ki-mit-adna-erte' ); ?></label>
					<input type="password" name="kmae_password" id="kmae_reset_password" required minlength="8" autocomplete="new-password" />
				</div>
				<div class="kmae-field">
					<label for="kmae_reset_password2"><?php esc_html_e( 'Új jelszó újra *', 'ki-mit-adna-erte' ); ?></label>
					<input type="password" name="kmae_password_confirm" id="kmae_reset_password2" required minlength="8" autocomplete="new-password" />
				</div>
			</div>
			<p class="kmae-hint kmae-auth__pw-hint"><?php esc_html_e( 'A jelszónak legalább 8 karakter hosszúnak kell lennie.', 'ki-mit-adna-erte' ); ?></p>

			<button type="submit" class="kmae-btn kmae-btn-primary kmae-btn--login kmae-auth__submit"><?php esc_html_e( 'Jelszó mentése', 'ki-mit-adna-erte' ); ?></button>
		</form>

	<?php else : ?>
		<header class="kmae-auth__header">
			<p class="kmae-auth__eyebrow"><?php esc_html_e( 'Üdv újra', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-auth__title"><?php esc_html_e( 'Bejelentkezés', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-auth__lead"><?php esc_html_e( 'Lépj be a hirdetéseid kezeléséhez.', 'ki-mit-adna-erte' ); ?></p>
		</header>

		<?php if ( $success && isset( $success_messages[ $success ] ) ) : ?>
			<div class="kmae-notice kmae-notice-success" role="status"><?php echo esc_html( $success_messages[ $success ] ); ?></div>
		<?php endif; ?>

		<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
			<div class="kmae-notice kmae-notice-error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-form kmae-auth__form">
			<?php wp_nonce_field( 'kmae_login', 'kmae_login_nonce' ); ?>
			<input type="hidden" name="action" value="kmae_login" />
			<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $redirect ); ?>" />

			<div class="kmae-field">
				<label for="kmae_login_email"><?php esc_html_e( 'E-mail cím', 'ki-mit-adna-erte' ); ?></label>
				<input type="email" name="kmae_login_email" id="kmae_login_email" required autocomplete="email" placeholder="nev@email.hu" />
			</div>

			<div class="kmae-field">
				<label for="kmae_login_password"><?php esc_html_e( 'Jelszó', 'ki-mit-adna-erte' ); ?></label>
				<input type="password" name="kmae_login_password" id="kmae_login_password" required autocomplete="current-password" />
			</div>

			<p class="kmae-auth__forgot-wrap">
				<a class="kmae-auth__forgot" href="<?php echo esc_url( KMAE_Auth::lost_password_url() ); ?>"><?php esc_html_e( 'Elfelejtett jelszó?', 'ki-mit-adna-erte' ); ?></a>
			</p>

			<button type="submit" class="kmae-btn kmae-btn--login kmae-auth__submit"><?php esc_html_e( 'Belépés', 'ki-mit-adna-erte' ); ?></button>

			<label class="kmae-checkbox kmae-auth__remember">
				<input type="checkbox" name="kmae_remember" value="1" />
				<span><?php esc_html_e( 'Maradjak bejelentkezve', 'ki-mit-adna-erte' ); ?></span>
			</label>
		</form>

		<?php
		$google_redirect = $redirect;
		$google_label    = __( 'Belépés Google-fiókkal', 'ki-mit-adna-erte' );
		$google_layout   = 'bottom';
		include KMAE_PLUGIN_DIR . 'templates/partials/google-button.php';
		?>

		<div class="kmae-auth-switch">
			<p class="kmae-auth-switch__text"><?php esc_html_e( 'Még nincs fiókod?', 'ki-mit-adna-erte' ); ?></p>
			<a class="kmae-auth-switch__btn" href="<?php echo esc_url( KMAE_Auth::register_url() ); ?>"><?php esc_html_e( 'Regisztráció', 'ki-mit-adna-erte' ); ?></a>
		</div>
	<?php endif; ?>
</div>
