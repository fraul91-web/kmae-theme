<?php
/**
 * Landing popular listings grid.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listings   = class_exists( 'KMAE_Query' ) ? KMAE_Query::get_popular_listings( 4 ) : array();
$browse_url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
?>
<section class="kmae-landing-popular">
	<div class="kmae-landing-popular-header">
		<h2 class="kmae-landing-popular-title"><?php esc_html_e( 'Népszerű hirdetések', 'ki-mit-adna-erte' ); ?></h2>
		<a href="<?php echo esc_url( $browse_url ); ?>" class="kmae-landing-popular-link"><?php esc_html_e( 'Összes megtekintése', 'ki-mit-adna-erte' ); ?></a>
	</div>

	<?php if ( empty( $listings ) ) : ?>
		<div class="kmae-landing-popular-empty">
			<p><?php esc_html_e( 'Hamarosan érkeznek az első hirdetések. Add fel te is az autódat!', 'ki-mit-adna-erte' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/hirdetes-feladasa/' ) ); ?>" class="kmae-btn kmae-btn-primary"><?php esc_html_e( 'Hirdetés feladása', 'ki-mit-adna-erte' ); ?></a>
		</div>
	<?php else : ?>
		<div class="kmae-landing-popular-grid">
			<?php foreach ( $listings as $listing ) : ?>
				<?php
				$listing_id = $listing->ID;
				$thumb      = KMAE_Query::get_listing_thumbnail_url( $listing_id, 'medium_large' );
				$title      = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::get_display_title( $listing_id ) : $listing->post_title;
				$value      = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::get_vehicle_value( $listing_id ) : 0;
				$price      = $value ? KMAE_Vehicle::format_value( $value ) : '';
				?>
				<a href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" class="kmae-landing-card">
					<div class="kmae-landing-card-image">
						<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" decoding="async" />
						<?php else : ?>
							<div class="kmae-landing-card-placeholder"><?php echo KMAE_Icons::render( 'car', array( 'size' => 32 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php endif; ?>
						<span class="kmae-landing-card-fav" aria-hidden="true"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
					<div class="kmae-landing-card-body">
						<h3 class="kmae-landing-card-title"><?php echo esc_html( $title ); ?></h3>
						<?php if ( $price ) : ?>
							<p class="kmae-landing-card-price"><?php echo esc_html( $price ); ?></p>
						<?php endif; ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
