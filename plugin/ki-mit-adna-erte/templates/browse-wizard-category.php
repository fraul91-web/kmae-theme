<?php
/**
 * Browse wizard step 3 — category.
 *
 * @package KMAE
 * @var string $base_url
 * @var string $type_step_url
 * @var string $current_county
 * @var string $current_type
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

KMAE_Taxonomies::seed_categories();
$categories  = get_terms( array( 'taxonomy' => 'kmae_category', 'hide_empty' => false ) );
$offer_labels = KMAE_Taxonomies::offer_type_labels();
$type_label   = 'mind' === $current_type ? __( 'Mind', 'ki-mit-adna-erte' ) : ( $offer_labels[ $current_type ] ?? $current_type );
$back_url     = $type_step_url;
?>
<div class="kmae-wizard kmae-wizard-category">
	<div class="kmae-wizard-header">
		<span class="kmae-wizard-step-num">3 / 3</span>
		<h2><?php esc_html_e( 'Milyen kategória?', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-subtitle">
			<?php
			printf(
				/* translators: 1: region, 2: offer type */
				esc_html__( '%1$s · %2$s', 'ki-mit-adna-erte' ),
				esc_html( KMAE_Counties::get_label( $current_county ) ),
				esc_html( $type_label )
			);
			?>
		</p>
	</div>

	<div class="kmae-wizard-grid kmae-wizard-grid-cats">
		<a href="<?php echo esc_url( add_query_arg( 'kategoria', 'mind', $base_url ) ); ?>" class="kmae-wizard-card kmae-wizard-card-all">
			<span class="kmae-wizard-card-icon">✦</span>
			<strong><?php esc_html_e( 'Mind', 'ki-mit-adna-erte' ); ?></strong>
			<span><?php esc_html_e( 'Minden kategória', 'ki-mit-adna-erte' ); ?></span>
		</a>
		<?php if ( ! is_wp_error( $categories ) ) : ?>
			<?php foreach ( $categories as $cat ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'kategoria', $cat->slug, $base_url ) ); ?>" class="kmae-wizard-card">
					<strong><?php echo esc_html( $cat->name ); ?></strong>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="kmae-wizard-nav">
		<a href="<?php echo esc_url( $back_url ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Vissza', 'ki-mit-adna-erte' ); ?></a>
	</div>
</div>
