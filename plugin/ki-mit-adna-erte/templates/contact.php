<?php
/**
 * Public contact form.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error   = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';
$success = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';

$error_messages = array(
	'nonce_failed'   => __( 'A munkamenet lejárt — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
	'missing_fields' => __( 'Kérjük, töltsd ki a nevet, az érvényes e-mail címet és az üzenetet.', 'ki-mit-adna-erte' ),
	'message_short'  => __( 'Az üzenet legyen legalább 10 karakter.', 'ki-mit-adna-erte' ),
	'message_long'   => __( 'Az üzenet túl hosszú (max. 5000 karakter).', 'ki-mit-adna-erte' ),
	'rate_limited'   => __( 'Túl sok üzenetet küldtél rövid időn belül. Próbáld újra később.', 'ki-mit-adna-erte' ),
	'send_failed'    => __( 'Az üzenet küldése nem sikerült. Próbáld újra pár perc múlva.', 'ki-mit-adna-erte' ),
);

$prefill_name  = '';
$prefill_email = '';
if ( is_user_logged_in() ) {
	$user          = wp_get_current_user();
	$prefill_name  = $user->display_name ? $user->display_name : '';
	$prefill_email = $user->user_email ? $user->user_email : '';
}

$topics = array(
	'altalanos' => __( 'Általános kérdés', 'ki-mit-adna-erte' ),
	'fiok'      => __( 'Fiók / belépés', 'ki-mit-adna-erte' ),
	'hirdetes'  => __( 'Hirdetés', 'ki-mit-adna-erte' ),
	'technikai' => __( 'Technikai hiba', 'ki-mit-adna-erte' ),
	'egyeb'     => __( 'Egyéb', 'ki-mit-adna-erte' ),
);
?>
<div class="kmae-contact-page">
	<aside class="kmae-contact-intro">
		<p class="kmae-contact-intro__eyebrow"><?php esc_html_e( 'KAPCSOLAT', 'ki-mit-adna-erte' ); ?></p>
		<h1><?php esc_html_e( 'Írj nekünk', 'ki-mit-adna-erte' ); ?></h1>
		<p class="kmae-contact-intro__lead"><?php esc_html_e( 'Mondd el röviden, miben segíthetünk. Általában 1–2 munkanapon belül válaszolunk.', 'ki-mit-adna-erte' ); ?></p>
		<div class="kmae-contact-intro__faq-block">
			<p><?php esc_html_e( 'Mielőtt írsz, nézz körül a gyakori kérdések között — lehet, hogy azonnal megtalálod a választ.', 'ki-mit-adna-erte' ); ?></p>
			<a class="kmae-contact-intro__faq" href="<?php echo esc_url( KMAE_Pages::url( 'gyik' ) ); ?>">
				<?php esc_html_e( 'Gyakori kérdések', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">→</span>
			</a>
		</div>
	</aside>

	<section class="kmae-contact-card" aria-labelledby="kmae-contact-form-title">
		<header class="kmae-contact-card__header">
			<p><?php esc_html_e( 'ÜZENETKÜLDÉS', 'ki-mit-adna-erte' ); ?></p>
			<h2 id="kmae-contact-form-title"><?php esc_html_e( 'Miben segíthetünk?', 'ki-mit-adna-erte' ); ?></h2>
		</header>

		<?php if ( $success && 'contact_sent' === $success ) : ?>
			<div class="kmae-contact-notice kmae-contact-notice--success" role="status">
				<strong><?php esc_html_e( 'Az üzeneted megérkezett.', 'ki-mit-adna-erte' ); ?></strong>
				<span><?php esc_html_e( 'Köszönjük! Hamarosan jelentkezünk.', 'ki-mit-adna-erte' ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( $error && isset( $error_messages[ $error ] ) ) : ?>
			<div class="kmae-contact-notice kmae-contact-notice--error" role="alert"><?php echo esc_html( $error_messages[ $error ] ); ?></div>
		<?php endif; ?>

		<?php if ( ! ( $success && 'contact_sent' === $success ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-contact-form" novalidate>
				<?php wp_nonce_field( 'kmae_contact_form', 'kmae_contact_form_nonce' ); ?>
				<input type="hidden" name="action" value="kmae_contact_form" />
				<p class="kmae-contact-form__hp" aria-hidden="true"><label for="kmae_website"><?php esc_html_e( 'Weboldal', 'ki-mit-adna-erte' ); ?></label><input type="text" name="kmae_website" id="kmae_website" value="" tabindex="-1" autocomplete="off" /></p>

				<div class="kmae-contact-form__row">
					<label class="kmae-contact-field" for="kmae_contact_name"><span><?php esc_html_e( 'Név', 'ki-mit-adna-erte' ); ?> *</span><input type="text" name="kmae_contact_name" id="kmae_contact_name" required maxlength="120" autocomplete="name" placeholder="<?php echo esc_attr__( 'Teljes neved', 'ki-mit-adna-erte' ); ?>" value="<?php echo esc_attr( $prefill_name ); ?>" /></label>
					<label class="kmae-contact-field" for="kmae_contact_email"><span><?php esc_html_e( 'E-mail', 'ki-mit-adna-erte' ); ?> *</span><input type="email" name="kmae_contact_email" id="kmae_contact_email" required autocomplete="email" placeholder="nev@email.hu" value="<?php echo esc_attr( $prefill_email ); ?>" /></label>
				</div>

				<label class="kmae-contact-field" for="kmae_contact_topic"><span><?php esc_html_e( 'Téma', 'ki-mit-adna-erte' ); ?></span><select name="kmae_contact_topic" id="kmae_contact_topic"><?php foreach ( $topics as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
				<label class="kmae-contact-field" for="kmae_contact_message"><span><?php esc_html_e( 'Üzenet', 'ki-mit-adna-erte' ); ?> *</span><textarea name="kmae_contact_message" id="kmae_contact_message" required rows="5" minlength="10" maxlength="5000" placeholder="<?php echo esc_attr__( 'Írd le röviden a kérdésed…', 'ki-mit-adna-erte' ); ?>"></textarea></label>

				<button class="kmae-contact-form__submit" type="submit"><?php esc_html_e( 'Üzenet küldése', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">→</span></button>
			</form>
		<?php else : ?>
			<a class="kmae-contact-card__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Vissza a főoldalra', 'ki-mit-adna-erte' ); ?></a>
		<?php endif; ?>
	</section>
</div>
