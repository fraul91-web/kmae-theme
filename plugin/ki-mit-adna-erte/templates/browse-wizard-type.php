<?php
/**
 * Browse wizard step 2 — offer type.
 *
 * @package KMAE
 * @var string $base_url
 * @var string $map_url
 * @var string $current_county
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$offer_types = KMAE_Taxonomies::offer_type_labels();
$back_url    = $map_url;
?>
<div class="kmae-wizard kmae-wizard-type">
	<div class="kmae-wizard-header">
		<span class="kmae-wizard-step-num"><?php echo esc_html( KMAE_Launch::wizard_step_label( 2 ) ); ?></span>
		<h2><?php esc_html_e( 'Milyen ajánlat érdekel?', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-subtitle">
			<?php
			if ( KMAE_Vehicle::is_vehicle_launch() ) {
				printf(
					/* translators: %s: selected region */
					esc_html__( 'Autók — %s', 'ki-mit-adna-erte' ),
					esc_html( KMAE_Counties::get_label( $current_county ) )
				);
			} else {
				printf(
					/* translators: %s: selected region */
					esc_html__( 'Helyszín: %s', 'ki-mit-adna-erte' ),
					esc_html( KMAE_Counties::get_label( $current_county ) )
				);
			}
			?>
		</p>
	</div>

	<div class="kmae-wizard-grid">
		<a href="<?php echo esc_url( add_query_arg( 'tipus', 'mind', $base_url ) ); ?>" class="kmae-wizard-card kmae-wizard-card-all">
			<span class="kmae-wizard-card-icon">✦</span>
			<strong><?php esc_html_e( 'Mind', 'ki-mit-adna-erte' ); ?></strong>
			<span><?php esc_html_e( 'Minden ajánlattípus', 'ki-mit-adna-erte' ); ?></span>
		</a>
		<?php foreach ( $offer_types as $code => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tipus', $code, $base_url ) ); ?>" class="kmae-wizard-card">
				<strong><?php echo esc_html( $label ); ?></strong>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="kmae-wizard-nav">
		<a href="<?php echo esc_url( $back_url ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Vissza a térképhez', 'ki-mit-adna-erte' ); ?></a>
	</div>
</div>
