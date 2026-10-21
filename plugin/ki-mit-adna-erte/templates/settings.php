<?php
/**
 * Beállítások — fiók-összegző fejléc, belépési adatok, értesítések, biztonság.
 *
 * A saját osztálynevek (`kmae-settings-*`) szándékosak: a régi, közös
 * osztályokat a plugin alapstílusa sötét felületre festi, ami a világos
 * üvegen olvashatatlan. Az oldal a saját, egyetlen stílusforrásából él
 * (theme/assets/css/settings.css).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user         = wp_get_current_user();
$settings_url = get_permalink();
$success      = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
$error        = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';

$is_company   = KMAE_Auth::is_company( $user->ID );
$account      = KMAE_Auth::account_type_label( $user->ID );
$member_year  = (int) mysql2date( 'Y', $user->user_registered );

/* Monogram a fejléc-kártyára (legfeljebb két betű). */
$display_name = trim( (string) $user->display_name );
if ( '' === $display_name ) {
	$display_name = $user->user_login;
}
$initials = '';
foreach ( array_slice( preg_split( '/\s+/u', $display_name ), 0, 2 ) as $name_part ) {
	$initials .= function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name_part, 0, 1 ) ) : strtoupper( substr( $name_part, 0, 1 ) );
}

$kmae_mail_catalog = class_exists( 'KMAE_Notifications' ) ? KMAE_Notifications::mail_catalog() : array();
$kmae_mail_prefs   = class_exists( 'KMAE_Notifications' ) ? KMAE_Notifications::mail_prefs( $user->ID ) : array();
$kmae_mail_locked  = array(
	__( 'E-mail cím megerősítése', 'ki-mit-adna-erte' ),
	__( 'Jelszó visszaállítása', 'ki-mit-adna-erte' ),
	__( 'A jelszavad megváltozott', 'ki-mit-adna-erte' ),
	__( 'A belépési e-mail címed megváltozott', 'ki-mit-adna-erte' ),
);
$mail_total = count( $kmae_mail_prefs );
$mail_on    = count( array_filter( $kmae_mail_prefs ) );
$mail_pct   = $mail_total ? (int) round( $mail_on / $mail_total * 100 ) : 0;

$push_available = class_exists( 'KMAE_Push' ) && KMAE_Push::is_available();

$alerts   = array();
$messages = array(
	'success' => array(
		'email_saved'      => __( 'E-mail cím frissítve. Mostantól ezzel tudsz belépni.', 'ki-mit-adna-erte' ),
		'password_saved'   => __( 'Jelszó megváltoztatva.', 'ki-mit-adna-erte' ),
		'sessions_cleared' => __( 'A többi eszközről kiléptettünk. Ezen az eszközön bejelentkezve maradsz.', 'ki-mit-adna-erte' ),
		'mail_saved'       => __( 'Az e-mail értesítéseket elmentettük.', 'ki-mit-adna-erte' ),
	),
	'error'   => array(
		'nonce_failed'      => __( 'A mentés nem sikerült — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
		'wrong_password'    => __( 'A jelenlegi jelszó nem helyes.', 'ki-mit-adna-erte' ),
		'invalid_email'     => __( 'Ez nem érvényes e-mail cím.', 'ki-mit-adna-erte' ),
		'email_unchanged'   => __( 'Ez már a jelenlegi e-mail címed.', 'ki-mit-adna-erte' ),
		'email_taken'       => __( 'Ezzel az e-mail címmel már létezik fiók.', 'ki-mit-adna-erte' ),
		'email_failed'      => __( 'Az e-mail cím mentése nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ),
		'password_short'    => __( 'Az új jelszó legyen legalább 8 karakter.', 'ki-mit-adna-erte' ),
		'password_mismatch' => __( 'A két új jelszó nem egyezik.', 'ki-mit-adna-erte' ),
	),
);
if ( $success && isset( $messages['success'][ $success ] ) ) {
	$alerts[] = array(
		'tone' => 'ok',
		'icon' => 'circle-check',
		'text' => $messages['success'][ $success ],
	);
}
if ( $error && isset( $messages['error'][ $error ] ) ) {
	$alerts[] = array(
		'tone' => 'warn',
		'icon' => 'triangle-alert',
		'text' => $messages['error'][ $error ],
	);
}

$icon = static function ( $name, $size = 18 ) {
	return class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $name, array( 'size' => $size ) ) : '';
};

/* Jelszómező a megjelenítés-kapcsoló szemmel. */
$password_input = static function ( $name, $id, $autocomplete, $extra = '' ) use ( $icon ) {
	?>
	<span class="kmae-settings-input kmae-settings-input--password">
		<span class="kmae-settings-input__icon" aria-hidden="true"><?php echo $icon( 'lock', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<input type="password" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" autocomplete="<?php echo esc_attr( $autocomplete ); ?>" <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> required />
		<button type="button" class="kmae-settings-input__eye" data-kmae-pass-eye aria-label="<?php esc_attr_e( 'Jelszó megjelenítése', 'ki-mit-adna-erte' ); ?>" aria-pressed="false"><?php echo $icon( 'eye', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	</span>
	<?php
};
?>
<div class="kmae-settings">

	<?php if ( $alerts ) : ?>
		<div class="kmae-settings__alerts">
			<?php foreach ( $alerts as $alert ) : ?>
				<p class="kmae-settings-alert kmae-settings-alert--<?php echo esc_attr( $alert['tone'] ); ?>">
					<span class="kmae-settings-alert__icon" aria-hidden="true"><?php echo $icon( $alert['icon'], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( $alert['text'] ); ?></span>
				</p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<header class="kmae-settings-hero">
		<span class="kmae-settings-hero__mark" aria-hidden="true"><?php echo $icon( 'settings', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="kmae-settings-hero__copy">
			<p class="kmae-settings-hero__eyebrow"><?php esc_html_e( 'A fiókom', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-settings-hero__title"><?php esc_html_e( 'Beállítások', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-settings-hero__lead"><?php esc_html_e( 'Belépési adatok, értesítések és biztonság — minden egy helyen.', 'ki-mit-adna-erte' ); ?></p>
		</div>

		<div class="kmae-settings-id">
			<span class="kmae-settings-id__avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
			<span class="kmae-settings-id__copy">
				<strong class="kmae-settings-id__name"><?php echo esc_html( $display_name ); ?></strong>
				<span class="kmae-settings-id__mail"><?php echo esc_html( $user->user_email ); ?></span>
				<span class="kmae-settings-id__pill">
					<?php echo $icon( $is_company ? 'users' : 'user', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $account ); ?>
				</span>
			</span>
		</div>
	</header>

	<ul class="kmae-settings-status">
		<li class="kmae-settings-stat">
			<span class="kmae-settings-stat__icon" aria-hidden="true"><?php echo $icon( 'shield-check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="kmae-settings-stat__copy">
				<span class="kmae-settings-stat__label"><?php esc_html_e( 'Belépés', 'ki-mit-adna-erte' ); ?></span>
				<strong class="kmae-settings-stat__value"><?php esc_html_e( 'Jelszóval védve', 'ki-mit-adna-erte' ); ?></strong>
			</span>
		</li>
		<li class="kmae-settings-stat kmae-settings-stat--mail">
			<span class="kmae-settings-stat__icon" aria-hidden="true"><?php echo $icon( 'mail', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="kmae-settings-stat__copy">
				<span class="kmae-settings-stat__label"><?php esc_html_e( 'E-mail értesítések', 'ki-mit-adna-erte' ); ?></span>
				<strong class="kmae-settings-stat__value">
					<?php
					/* translators: 1: enabled count, 2: total count */
					echo esc_html( sprintf( __( '%1$d / %2$d bekapcsolva', 'ki-mit-adna-erte' ), $mail_on, $mail_total ) );
					?>
				</strong>
				<span class="kmae-settings-stat__bar" style="--pct: <?php echo esc_attr( $mail_pct ); ?>%" aria-hidden="true"></span>
			</span>
		</li>
		<?php if ( $member_year ) : ?>
			<li class="kmae-settings-stat kmae-settings-stat--warm">
				<span class="kmae-settings-stat__icon" aria-hidden="true"><?php echo $icon( 'calendar', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-settings-stat__copy">
					<span class="kmae-settings-stat__label"><?php esc_html_e( 'Tagság', 'ki-mit-adna-erte' ); ?></span>
					<strong class="kmae-settings-stat__value">
						<?php
						/* translators: %d: registration year */
						echo esc_html( sprintf( __( '%d óta velünk', 'ki-mit-adna-erte' ), $member_year ) );
						?>
					</strong>
				</span>
			</li>
		<?php endif; ?>
	</ul>

	<div class="kmae-settings__grid">

		<div class="kmae-settings__main">

			<section class="kmae-settings-panel">
				<header class="kmae-settings-panel__head">
					<span class="kmae-settings-panel__mark" aria-hidden="true"><?php echo $icon( 'mail', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="kmae-settings-panel__heading">
						<p class="kmae-settings-panel__eyebrow"><?php esc_html_e( 'Belépési azonosító', 'ki-mit-adna-erte' ); ?></p>
						<h3 class="kmae-settings-panel__title"><?php esc_html_e( 'E-mail cím', 'ki-mit-adna-erte' ); ?></h3>
					</div>
					<p class="kmae-settings-current">
						<span class="kmae-settings-current__label"><?php esc_html_e( 'Jelenlegi', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-settings-current__value"><?php echo esc_html( $user->user_email ); ?></span>
					</p>
				</header>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-settings-form">
					<?php wp_nonce_field( 'kmae_change_email', 'kmae_email_nonce' ); ?>
					<input type="hidden" name="action" value="kmae_change_email" />
					<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $settings_url ); ?>" />

					<div class="kmae-settings-field-row">
						<div class="kmae-settings-field">
							<label for="kmae_new_email"><?php esc_html_e( 'Új e-mail cím', 'ki-mit-adna-erte' ); ?></label>
							<span class="kmae-settings-input">
								<span class="kmae-settings-input__icon" aria-hidden="true"><?php echo $icon( 'mail', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="email" name="kmae_new_email" id="kmae_new_email" autocomplete="email" placeholder="pelda@email.hu" required />
							</span>
						</div>
						<div class="kmae-settings-field">
							<label for="kmae_email_current_password"><?php esc_html_e( 'Jelenlegi jelszó', 'ki-mit-adna-erte' ); ?></label>
							<?php $password_input( 'kmae_current_password', 'kmae_email_current_password', 'current-password' ); ?>
						</div>
					</div>

					<div class="kmae-settings-form__actions">
						<p class="kmae-settings-note">
							<span class="kmae-settings-note__icon" aria-hidden="true"><?php echo $icon( 'info', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php esc_html_e( 'Ezzel lépsz be, ezért jelszót kérünk hozzá.', 'ki-mit-adna-erte' ); ?>
						</p>
						<button type="submit" class="kmae-settings-btn kmae-settings-btn--accent">
							<?php echo $icon( 'check', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'E-mail cím módosítása', 'ki-mit-adna-erte' ); ?>
						</button>
					</div>
				</form>
			</section>

			<section class="kmae-settings-panel">
				<header class="kmae-settings-panel__head">
					<span class="kmae-settings-panel__mark" aria-hidden="true"><?php echo $icon( 'key-round', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="kmae-settings-panel__heading">
						<p class="kmae-settings-panel__eyebrow"><?php esc_html_e( 'Védelem', 'ki-mit-adna-erte' ); ?></p>
						<h3 class="kmae-settings-panel__title"><?php esc_html_e( 'Jelszó', 'ki-mit-adna-erte' ); ?></h3>
					</div>
				</header>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-settings-form" data-kmae-pass-form>
					<?php wp_nonce_field( 'kmae_change_password', 'kmae_password_nonce' ); ?>
					<input type="hidden" name="action" value="kmae_change_password" />
					<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $settings_url ); ?>" />

					<div class="kmae-settings-field-row kmae-settings-field-row--three">
						<div class="kmae-settings-field">
							<label for="kmae_current_password"><?php esc_html_e( 'Jelenlegi jelszó', 'ki-mit-adna-erte' ); ?></label>
							<?php $password_input( 'kmae_current_password', 'kmae_current_password', 'current-password' ); ?>
						</div>
						<div class="kmae-settings-field">
							<label for="kmae_new_password"><?php esc_html_e( 'Új jelszó', 'ki-mit-adna-erte' ); ?></label>
							<?php $password_input( 'kmae_new_password', 'kmae_new_password', 'new-password', 'minlength="8" data-kmae-pass-new' ); ?>
						</div>
						<div class="kmae-settings-field">
							<label for="kmae_new_password_repeat"><?php esc_html_e( 'Új jelszó újra', 'ki-mit-adna-erte' ); ?></label>
							<?php $password_input( 'kmae_new_password_repeat', 'kmae_new_password_repeat', 'new-password', 'minlength="8" data-kmae-pass-repeat' ); ?>
						</div>
					</div>

					<div class="kmae-settings-strength" data-kmae-pass-meter data-level="0">
						<span class="kmae-settings-strength__bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
						<span class="kmae-settings-strength__label" data-kmae-pass-label><?php esc_html_e( 'Legalább 8 karakter — betű, szám és jel együtt a legerősebb.', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-settings-strength__match" data-kmae-pass-match role="status" aria-live="polite"></span>
					</div>

					<div class="kmae-settings-form__actions">
						<p class="kmae-settings-note">
							<span class="kmae-settings-note__icon" aria-hidden="true"><?php echo $icon( 'shield', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php esc_html_e( 'Itt bejelentkezve maradsz.', 'ki-mit-adna-erte' ); ?>
						</p>
						<button type="submit" class="kmae-settings-btn kmae-settings-btn--accent">
							<?php echo $icon( 'check', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'Jelszó módosítása', 'ki-mit-adna-erte' ); ?>
						</button>
					</div>
				</form>
			</section>
		</div>

		<aside class="kmae-settings__side">

			<section class="kmae-settings-panel kmae-settings-panel--compact">
				<header class="kmae-settings-panel__head">
					<span class="kmae-settings-panel__mark" aria-hidden="true"><?php echo $icon( 'bell', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="kmae-settings-panel__heading">
						<p class="kmae-settings-panel__eyebrow"><?php esc_html_e( 'Jelzések', 'ki-mit-adna-erte' ); ?></p>
						<h3 class="kmae-settings-panel__title"><?php esc_html_e( 'Értesítések', 'ki-mit-adna-erte' ); ?></h3>
					</div>
				</header>

				<button type="button" class="kmae-settings-row" data-kmae-mail-open>
					<span class="kmae-settings-row__icon" aria-hidden="true"><?php echo $icon( 'mail', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-settings-row__copy">
						<strong><?php esc_html_e( 'E-mail értesítések', 'ki-mit-adna-erte' ); ?></strong>
						<span>
							<?php
							/* translators: 1: enabled count, 2: total count */
							echo esc_html( sprintf( __( '%1$d / %2$d bekapcsolva · egyenként állítható', 'ki-mit-adna-erte' ), $mail_on, $mail_total ) );
							?>
						</span>
					</span>
					<span class="kmae-settings-row__arrow" aria-hidden="true"><?php echo $icon( 'chevron-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</button>

				<?php if ( $push_available ) : ?>
					<?php /* Böngésző-értesítések (Web Push) — a kapcsolót assets/js/push.js kezeli. */ ?>
					<label class="kmae-settings-toggle kmae-settings-toggle--push">
						<input type="checkbox" data-kmae-push-toggle <?php checked( KMAE_Push::is_enabled( get_current_user_id() ) ); ?> />
						<span class="kmae-settings-toggle__switch" aria-hidden="true"></span>
						<span class="kmae-settings-toggle__copy">
							<strong><?php esc_html_e( 'Böngésző-értesítés', 'ki-mit-adna-erte' ); ?></strong>
							<span><?php esc_html_e( 'Azonnali jelzés új üzenetről', 'ki-mit-adna-erte' ); ?></span>
						</span>
					</label>
					<p class="kmae-settings-note kmae-settings-note--block" data-kmae-push-note role="status" aria-live="polite"><?php esc_html_e( 'Azonnal szólunk, ha üzenetet kapsz — akkor is, ha az oldal nincs nyitva.', 'ki-mit-adna-erte' ); ?></p>
				<?php endif; ?>
			</section>

			<a class="kmae-settings-profile" href="<?php echo esc_url( KMAE_Pages::url( 'profil' ) ); ?>">
				<span class="kmae-settings-profile__icon" aria-hidden="true"><?php echo $icon( $is_company ? 'users' : 'user', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-settings-profile__copy">
					<span class="kmae-settings-profile__eyebrow"><?php esc_html_e( 'Ugrás', 'ki-mit-adna-erte' ); ?></span>
					<strong><?php esc_html_e( 'Profilom', 'ki-mit-adna-erte' ); ?></strong>
					<span><?php esc_html_e( 'Név, telefonszám, bizalmi szám', 'ki-mit-adna-erte' ); ?></span>
				</span>
				<span class="kmae-settings-profile__arrow" aria-hidden="true"><?php echo $icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</a>

			<section class="kmae-settings-panel kmae-settings-panel--compact kmae-settings-panel--alert">
				<header class="kmae-settings-panel__head">
					<span class="kmae-settings-panel__mark" aria-hidden="true"><?php echo $icon( 'log-out', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="kmae-settings-panel__heading">
						<p class="kmae-settings-panel__eyebrow"><?php esc_html_e( 'Biztonság', 'ki-mit-adna-erte' ); ?></p>
						<h3 class="kmae-settings-panel__title"><?php esc_html_e( 'Más eszközök', 'ki-mit-adna-erte' ); ?></h3>
					</div>
				</header>

				<p class="kmae-settings-note kmae-settings-note--block"><?php esc_html_e( 'Idegen gépen maradtál belépve? Léptesd ki magad — ez az eszköz megmarad.', 'ki-mit-adna-erte' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'kmae_logout_everywhere', 'kmae_sessions_nonce' ); ?>
					<input type="hidden" name="action" value="kmae_logout_everywhere" />
					<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $settings_url ); ?>" />
					<button type="submit" class="kmae-settings-btn kmae-settings-btn--ghost kmae-settings-btn--block">
						<?php echo $icon( 'log-out', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Kiléptetés a többi eszközről', 'ki-mit-adna-erte' ); ?>
					</button>
				</form>
			</section>

			<nav class="kmae-settings-legal" aria-label="<?php echo esc_attr__( 'Jogi tudnivalók', 'ki-mit-adna-erte' ); ?>">
				<a href="<?php echo esc_url( KMAE_Pages::url( 'adatvedelem' ) ); ?>">
					<?php echo $icon( 'shield', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Adatvédelem', 'ki-mit-adna-erte' ); ?>
				</a>
				<a href="<?php echo esc_url( KMAE_Pages::url( 'feltetelek' ) ); ?>">
					<?php echo $icon( 'file-text', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Feltételek', 'ki-mit-adna-erte' ); ?>
				</a>
				<a href="<?php echo esc_url( KMAE_Pages::url( 'kapcsolat' ) ); ?>">
					<?php echo $icon( 'mail', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Kapcsolat', 'ki-mit-adna-erte' ); ?>
				</a>
			</nav>

		</aside>
	</div>

	<div class="kmae-settings__back">
		<a class="kmae-settings-btn kmae-settings-btn--ghost" href="<?php echo esc_url( KMAE_Back::home_url() ); ?>">
			<?php echo $icon( 'arrow-left', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo esc_html( KMAE_Back::home_label() ); ?>
		</a>
	</div>
</div>

<div class="kmae-mail-dialog" id="kmae-mail-dialog" hidden>
	<div class="kmae-mail-dialog__backdrop" data-kmae-mail-close></div>
	<div class="kmae-mail-dialog__card" role="dialog" aria-modal="true" aria-labelledby="kmae-mail-dialog-title">
		<header class="kmae-mail-dialog__head">
			<span class="kmae-mail-dialog__mark" aria-hidden="true"><?php echo $icon( 'mail', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div class="kmae-mail-dialog__heading">
				<p class="kmae-mail-dialog__eyebrow"><?php esc_html_e( 'Beállítások', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-mail-dialog__title" id="kmae-mail-dialog-title"><?php esc_html_e( 'E-mail értesítések', 'ki-mit-adna-erte' ); ?></h2>
			</div>
			<button type="button" class="kmae-mail-dialog__close" data-kmae-mail-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>"><?php echo $icon( 'x', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</header>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-mail-dialog__form" id="kmae-mail-form">
			<?php wp_nonce_field( 'kmae_save_mail_prefs', 'kmae_mail_prefs_nonce' ); ?>
			<input type="hidden" name="action" value="kmae_save_mail_prefs" />
			<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $settings_url ); ?>" />

			<div class="kmae-mail-dialog__masters">
				<label class="kmae-settings-toggle">
					<input type="checkbox" data-kmae-mail-all="on" />
					<span class="kmae-settings-toggle__switch" aria-hidden="true"></span>
					<span class="kmae-settings-toggle__copy"><strong><?php esc_html_e( 'Mindegyik be', 'ki-mit-adna-erte' ); ?></strong></span>
				</label>
				<label class="kmae-settings-toggle">
					<input type="checkbox" data-kmae-mail-all="off" />
					<span class="kmae-settings-toggle__switch" aria-hidden="true"></span>
					<span class="kmae-settings-toggle__copy"><strong><?php esc_html_e( 'Mindegyik ki', 'ki-mit-adna-erte' ); ?></strong></span>
				</label>
			</div>

			<div class="kmae-mail-dialog__scroll">
				<?php foreach ( $kmae_mail_catalog as $group ) : ?>
					<section class="kmae-mail-dialog__group">
						<h3><?php echo esc_html( $group['label'] ); ?></h3>
						<?php foreach ( $group['items'] as $item ) : ?>
							<label class="kmae-settings-toggle kmae-mail-dialog__row">
								<input type="checkbox" name="kmae_mail[<?php echo esc_attr( $item['key'] ); ?>]" value="1" data-kmae-mail-item <?php checked( ! empty( $kmae_mail_prefs[ $item['key'] ] ) ); ?> />
								<span class="kmae-settings-toggle__switch" aria-hidden="true"></span>
								<span class="kmae-settings-toggle__copy">
									<strong><?php echo esc_html( $item['label'] ); ?></strong>
									<span><?php echo esc_html( $item['hint'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>

				<section class="kmae-mail-dialog__group kmae-mail-dialog__group--locked">
					<h3>
						<?php echo $icon( 'lock', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Mindig kimegy', 'ki-mit-adna-erte' ); ?>
					</h3>
					<p><?php esc_html_e( 'Ezek a belépéshez és a fiók védelméhez kellenek, ezért nem kapcsolhatók ki.', 'ki-mit-adna-erte' ); ?></p>
					<ul>
						<?php foreach ( $kmae_mail_locked as $locked_label ) : ?>
							<li><?php echo esc_html( $locked_label ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>

			<div class="kmae-mail-dialog__actions">
				<button type="button" class="kmae-settings-btn kmae-settings-btn--ghost" data-kmae-mail-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
				<button type="submit" class="kmae-settings-btn kmae-settings-btn--accent"><?php esc_html_e( 'Mentés', 'ki-mit-adna-erte' ); ?></button>
			</div>
		</form>
	</div>
</div>
<script>
(function () {
	var dialog = document.getElementById('kmae-mail-dialog');
	var openBtn = document.querySelector('[data-kmae-mail-open]');
	if (!dialog || !openBtn) {
		return;
	}
	if (dialog.parentNode !== document.body) {
		document.body.appendChild(dialog);
	}
	var items = dialog.querySelectorAll('[data-kmae-mail-item]');
	var allOn = dialog.querySelector('[data-kmae-mail-all="on"]');
	var allOff = dialog.querySelector('[data-kmae-mail-all="off"]');

	function syncMasters() {
		var on = 0;
		items.forEach(function (box) {
			if (box.checked) {
				on += 1;
			}
		});
		if (allOn) {
			allOn.checked = items.length > 0 && on === items.length;
		}
		if (allOff) {
			allOff.checked = items.length > 0 && on === 0;
		}
	}

	function setAll(on) {
		items.forEach(function (box) {
			box.checked = on;
		});
		syncMasters();
	}

	if (allOn) {
		allOn.addEventListener('change', function () {
			setAll(true);
		});
	}
	if (allOff) {
		allOff.addEventListener('change', function () {
			setAll(false);
		});
	}
	items.forEach(function (box) {
		box.addEventListener('change', syncMasters);
	});
	syncMasters();

	function openDialog() {
		dialog.hidden = false;
		document.body.classList.add('kmae-mail-dialog-open');
		var closeBtn = dialog.querySelector('.kmae-mail-dialog__close');
		if (closeBtn) {
			closeBtn.focus();
		}
	}

	function closeDialog() {
		dialog.hidden = true;
		document.body.classList.remove('kmae-mail-dialog-open');
		openBtn.focus();
	}

	openBtn.addEventListener('click', openDialog);
	dialog.querySelectorAll('[data-kmae-mail-close]').forEach(function (node) {
		node.addEventListener('click', closeDialog);
	});
	document.addEventListener('keydown', function (event) {
		if (!dialog.hidden && (event.key === 'Escape' || event.key === 'Esc')) {
			closeDialog();
		}
	});
})();

/* Jelszómezők: szem (megjelenítés), erősségmérő, egyezés-jelzés. Csak kijelzés,
   a mentés szabályai a szerveren vannak (min. 8 karakter, egyezés). */
(function () {
	document.querySelectorAll('[data-kmae-pass-eye]').forEach(function (eye) {
		eye.addEventListener('click', function () {
			var input = eye.parentNode.querySelector('input');
			if (!input) {
				return;
			}
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			eye.setAttribute('aria-pressed', show ? 'true' : 'false');
			eye.classList.toggle('is-on', show);
		});
	});

	var form = document.querySelector('[data-kmae-pass-form]');
	if (!form) {
		return;
	}
	var pass = form.querySelector('[data-kmae-pass-new]');
	var repeat = form.querySelector('[data-kmae-pass-repeat]');
	var meter = form.querySelector('[data-kmae-pass-meter]');
	var label = form.querySelector('[data-kmae-pass-label]');
	var match = form.querySelector('[data-kmae-pass-match]');
	if (!pass || !repeat || !meter || !label || !match) {
		return;
	}
	var idle = label.textContent;
	var words = ['', 'Gyenge', 'Közepes', 'Erős', 'Nagyon erős'];

	function score(value) {
		if (value.length < 8) {
			return value.length ? 1 : 0;
		}
		var points = 1;
		if (value.length >= 12) {
			points += 1;
		}
		if (/[a-zà-ű]/.test(value) && /[A-ZÀ-Ű]/.test(value)) {
			points += 1;
		}
		if (/\d/.test(value) && /[^A-Za-z0-9À-ű]/.test(value)) {
			points += 1;
		}
		return Math.min(points, 4);
	}

	function paint() {
		var level = score(pass.value);
		meter.setAttribute('data-level', String(level));
		if (!pass.value) {
			label.textContent = idle;
		} else if (pass.value.length < 8) {
			label.textContent = 'Még ' + (8 - pass.value.length) + ' karakter hiányzik';
		} else {
			label.textContent = words[level];
		}
		if (!repeat.value) {
			match.textContent = '';
			match.className = 'kmae-settings-strength__match';
		} else if (repeat.value === pass.value) {
			match.textContent = '✓ A kettő egyezik';
			match.className = 'kmae-settings-strength__match is-ok';
		} else {
			match.textContent = 'A kettő még nem egyezik';
			match.className = 'kmae-settings-strength__match is-bad';
		}
	}

	pass.addEventListener('input', paint);
	repeat.addEventListener('input', paint);
})();
</script>
