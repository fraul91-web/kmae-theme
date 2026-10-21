<?php
/**
 * Browse wizard step 3 — price mode: cash range or open offers.
 *
 * @package KMAE
 * @var string $base_url
 * @var string $body_step_url
 * @var string $current_county
 * @var string[] $current_bodies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$body_label = KMAE_Browse::bodies_label( $current_bodies ?? array() );

$modes = array(
	KMAE_Vehicle::BROWSE_KP => array(
		'icon'  => 'banknote',
		'title' => __( 'Készpénz', 'ki-mit-adna-erte' ),
		'desc'  => __( 'Add meg a minimum és maximum összeget, majd böngéssz a neked megfelelő ajánlatok között.', 'ki-mit-adna-erte' ),
	),
	KMAE_Vehicle::BROWSE_AJANLAT => array(
		'icon'  => 'message-square',
		'title' => __( 'Ki mit adna érte?', 'ki-mit-adna-erte' ),
		'desc'  => __( 'Az eladó nem adott meg árat. Nézd meg az autót, és mondd meg, mennyit adnál érte.', 'ki-mit-adna-erte' ),
	),
);
?>
<div class="kmae-wizard kmae-wizard-offer">
	<div class="kmae-wizard-header">
		<span class="kmae-wizard-step-num"><?php echo esc_html( KMAE_Launch::wizard_step_label( 3 ) ); ?></span>
		<h2><?php esc_html_e( 'Mit keresel?', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-subtitle">
			<?php
			printf(
				esc_html__( '%1$s · %2$s', 'ki-mit-adna-erte' ),
				esc_html( KMAE_Counties::get_label( $current_county ) ),
				esc_html( $body_label )
			);
			?>
		</p>
	</div>

	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="kmae-wizard-offer-form" id="kmae-wizard-offer-form">
		<input type="hidden" name="megye" value="<?php echo esc_attr( $current_county ); ?>" />
		<input type="hidden" name="kivitel" value="<?php echo esc_attr( KMAE_Browse::bodies_to_query_value( $current_bodies ?? array() ) ); ?>" />
		<input type="hidden" name="mod" id="kmae-wizard-mod" value="" />

		<div class="kmae-wizard-mode-cards">
			<?php foreach ( $modes as $code => $mode ) : ?>
				<button type="button" class="kmae-wizard-mode-card" data-kmae-mode="<?php echo esc_attr( $code ); ?>">
					<span class="kmae-wizard-card-icon" aria-hidden="true"><?php echo KMAE_Icons::render( $mode['icon'], array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<strong><?php echo esc_html( $mode['title'] ); ?></strong>
					<span class="kmae-wizard-mode-desc"><?php echo esc_html( $mode['desc'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="kmae-wizard-mode-panel" id="kmae-panel-kp" hidden>
			<h3><?php esc_html_e( 'Készpénz — milyen árban keresel?', 'ki-mit-adna-erte' ); ?></h3>
			<p class="kmae-hint kmae-wizard-panel-hint"><?php esc_html_e( 'Írd be a minimum és a maximum összeget. Csak készpénzes hirdetéseket mutatunk a megadott tartományban.', 'ki-mit-adna-erte' ); ?></p>
			<label for="kmae-wizard-kp-min"><?php esc_html_e( 'Minimum (Ft)', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" inputmode="numeric" id="kmae-wizard-kp-min" class="kmae-wizard-input kmae-currency-input" autocomplete="off" value="" />
			<label for="kmae-wizard-kp-max"><?php esc_html_e( 'Maximum (Ft)', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" inputmode="numeric" id="kmae-wizard-kp-max" class="kmae-wizard-input kmae-currency-input" autocomplete="off" value="" />
			<input type="hidden" name="kp_min" id="kmae-wizard-kp-min-val" value="" />
			<input type="hidden" name="kp_max" id="kmae-wizard-kp-max-val" value="" />
		</div>

		<div
			class="kmae-wizard-form-errors"
			id="kmae-wizard-errors"
			hidden
			role="status"
			aria-live="polite"
			data-msg-mode="<?php echo esc_attr__( 'Válaszd ki, hogyan szeretnél böngészni.', 'ki-mit-adna-erte' ); ?>"
			data-msg-kp-min="<?php echo esc_attr__( 'Írd be a minimum összeget.', 'ki-mit-adna-erte' ); ?>"
			data-msg-kp-max="<?php echo esc_attr__( 'Írd be a maximum összeget.', 'ki-mit-adna-erte' ); ?>"
			data-msg-kp-range="<?php echo esc_attr__( 'A maximum nem lehet kisebb, mint a minimum.', 'ki-mit-adna-erte' ); ?>"
		></div>

		<div class="kmae-wizard-nav">
			<a href="<?php echo esc_url( $body_step_url ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Vissza a kivitelhez', 'ki-mit-adna-erte' ); ?></a>
			<button type="submit" class="kmae-btn kmae-btn-primary" id="kmae-wizard-search-btn" disabled><?php esc_html_e( 'Keresés', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</form>
</div>
