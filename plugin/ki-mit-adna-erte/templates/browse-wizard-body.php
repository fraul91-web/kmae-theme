<?php
/**
 * Browse wizard step 2 — vehicle body type (multi-select).
 *
 * @package KMAE
 * @var string $base_url
 * @var string $map_url
 * @var string $current_county
 * @var string[] $current_bodies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$body_types     = KMAE_Vehicle::browse_body_cards();
$back_url       = $map_url;
$current_bodies = isset( $current_bodies ) && is_array( $current_bodies ) ? $current_bodies : array();
$is_all         = in_array( 'mind', $current_bodies, true );
?>
<div class="kmae-wizard kmae-wizard-body">
	<div class="kmae-wizard-header">
		<span class="kmae-wizard-step-num"><?php echo esc_html( KMAE_Launch::wizard_step_label( 2 ) ); ?></span>
		<h2><?php esc_html_e( 'Milyen autót keresel?', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-subtitle">
			<?php
			printf(
				esc_html__( 'Válaszd ki a kiviteleket (több is lehet) — %s', 'ki-mit-adna-erte' ),
				esc_html( KMAE_Counties::get_label( $current_county ) )
			);
			?>
		</p>
	</div>

	<div
		class="kmae-wizard-grid kmae-wizard-grid-bodies"
		id="kmae-wizard-body-picker"
		data-base-url="<?php echo esc_url( $base_url ); ?>"
	>
		<?php foreach ( $body_types as $slug => $info ) : ?>
			<?php $selected = ! $is_all && in_array( $slug, $current_bodies, true ); ?>
			<button
				type="button"
				class="kmae-wizard-card kmae-wizard-card-body kmae-wizard-card-body--photo<?php echo $selected ? ' is-selected' : ''; ?>"
				data-kmae-body="<?php echo esc_attr( $slug ); ?>"
				aria-pressed="<?php echo $selected ? 'true' : 'false'; ?>"
			>
				<span class="kmae-wizard-card-body__frame">
					<img class="kmae-wizard-card-body__photo" src="<?php echo esc_url( $info['photo'] ); ?>" alt="" aria-hidden="true" decoding="async" />
					<span class="kmae-wizard-card-body__check" aria-hidden="true"><?php echo KMAE_Icons::render( 'check', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</span>
				<strong><?php echo esc_html( $info['label'] ); ?></strong>
			</button>
		<?php endforeach; ?>
		<button
			type="button"
			class="kmae-wizard-card kmae-wizard-card-body kmae-wizard-card-all<?php echo $is_all ? ' is-selected' : ''; ?>"
			data-kmae-body="mind"
			aria-pressed="<?php echo $is_all ? 'true' : 'false'; ?>"
		>
			<strong><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></strong>
			<span><?php esc_html_e( 'Bármilyen kivitel jöhet', 'ki-mit-adna-erte' ); ?></span>
		</button>
	</div>

	<div class="kmae-wizard-nav kmae-wizard-nav-split">
		<a href="<?php echo esc_url( $back_url ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Vissza a térképhez', 'ki-mit-adna-erte' ); ?></a>
		<button type="button" class="kmae-btn kmae-btn-primary" id="kmae-wizard-body-next" disabled><?php esc_html_e( 'Tovább', 'ki-mit-adna-erte' ); ?></button>
	</div>
</div>
