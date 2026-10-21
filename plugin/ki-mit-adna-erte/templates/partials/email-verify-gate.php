<?php
/**
 * Letiltó képernyő addig, amíg az e-mail cím nincs megerősítve.
 *
 * Csak a hirdetésfeladást és az eladónak írást zárja le — a böngészés, az
 * üzenetek és minden más funkció eddig is elérhető marad.
 *
 * @package KMAE
 * @var string $gate_context 'listing' vagy 'offer' — melyik funkciót várja.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gate_context = isset( $gate_context ) ? $gate_context : 'listing';
$gate_user    = wp_get_current_user();
$gate_success = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
$gate_error   = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';

$gate_intro = 'offer' === $gate_context
	? __( 'Mielőtt írnál az eladónak, erősítsd meg az e-mail címed.', 'ki-mit-adna-erte' )
	: __( 'Mielőtt feladnád a hirdetésed, erősítsd meg az e-mail címed.', 'ki-mit-adna-erte' );

$gate_messages = array(
	'success' => array(
		'verification_sent' => __( 'Elküldtük a megerősítő levelet — nézd meg a postaládád.', 'ki-mit-adna-erte' ),
		'already_verified'  => __( 'Ez az e-mail cím már megerősítve — frissítsd az oldalt.', 'ki-mit-adna-erte' ),
	),
	'error'   => array(
		'nonce_failed'   => __( 'A kérés nem sikerült — próbáld újra.', 'ki-mit-adna-erte' ),
		'invalid_token'  => __( 'Érvénytelen vagy már felhasznált megerősítő link. Kérj újat lent.', 'ki-mit-adna-erte' ),
		'expired_token'  => __( 'A korábbi link lejárt. Kérj újat lent.', 'ki-mit-adna-erte' ),
		'email_unverified' => __( 'Ehhez előbb erősítsd meg az e-mail címed.', 'ki-mit-adna-erte' ),
	),
);
/* A hirdetésfeladás oldalon a gate a shortcode teljes kimenete — nincs
   körülötte a `.kmae-create-page` héj, amit a CSS-e elvár, ezért itt
   pótoljuk. A hirdetés oldalán ez felesleges, ott a `.kmae-single-listing`
   már körülveszi. */
$gate_wrap_open  = 'listing' === $gate_context ? '<div class="kmae-create-page">' : '';
$gate_wrap_close = 'listing' === $gate_context ? '</div>' : '';
?>
<?php echo $gate_wrap_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div class="kmae-verify-gate" data-kmae-live-page="email_verify" data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'email_verify' ) ); ?>">
	<img
		class="kmae-verify-gate__mascot"
		src="https://albira.hu/wp-content/uploads/2026/10/info3.webp"
		alt=""
		aria-hidden="true"
		width="140"
		height="140"
		decoding="async"
	/>

	<div class="kmae-verify-gate__body">
		<p class="kmae-verify-gate__eyebrow"><?php esc_html_e( 'Egy lépés van hátra', 'ki-mit-adna-erte' ); ?></p>
		<h2 class="kmae-verify-gate__title"><?php esc_html_e( 'Erősítsd meg az e-mail címed', 'ki-mit-adna-erte' ); ?></h2>

		<p class="kmae-verify-gate__text">
			<?php echo esc_html( $gate_intro ); ?>
			<?php
			printf(
				/* translators: %s: user's email address */
				esc_html__( ' Küldtünk egy levelet ide: %s — nyisd meg, és kattints a benne lévő linkre.', 'ki-mit-adna-erte' ),
				'<strong>' . esc_html( $gate_user->user_email ) . '</strong>'
			);
			?>
		</p>

		<?php if ( $gate_success && isset( $gate_messages['success'][ $gate_success ] ) ) : ?>
			<p class="kmae-verify-gate__alert kmae-verify-gate__alert--ok"><?php echo esc_html( $gate_messages['success'][ $gate_success ] ); ?></p>
		<?php endif; ?>

		<?php if ( $gate_error && isset( $gate_messages['error'][ $gate_error ] ) ) : ?>
			<p class="kmae-verify-gate__alert kmae-verify-gate__alert--warn"><?php echo esc_html( $gate_messages['error'][ $gate_error ] ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-verify-gate__form">
			<?php wp_nonce_field( 'kmae_resend_verification', 'kmae_resend_nonce' ); ?>
			<input type="hidden" name="action" value="kmae_resend_verification" />
			<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( home_url( add_query_arg( array() ) ) ); ?>" />
			<button type="submit" class="kmae-verify-gate__btn"><?php esc_html_e( 'Küldd újra a levelet', 'ki-mit-adna-erte' ); ?></button>
		</form>

		<p class="kmae-verify-gate__note"><?php esc_html_e( 'Böngészni, üzenetet küldeni és a hirdetéseket nézni eddig is tudsz — csak ehhez a lépéshez kell a megerősítés.', 'ki-mit-adna-erte' ); ?></p>
	</div>
</div>
<?php echo $gate_wrap_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
