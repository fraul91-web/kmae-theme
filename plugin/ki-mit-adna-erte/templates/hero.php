<?php
/**
 * Landing hero — AutoNext-style automotive launch.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$browse_url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
?>
<section class="kmae-hero kmae-hero-autonext">
	<div class="kmae-hero-autonext-grid">
		<div class="kmae-hero-autonext-copy">
			<h1 class="kmae-hero-autonext-title">
				<?php esc_html_e( 'Következő lépésed', 'ki-mit-adna-erte' ); ?>
				<span><?php esc_html_e( 'az új autód felé.', 'ki-mit-adna-erte' ); ?></span>
			</h1>
			<p class="kmae-hero-autonext-lead"><?php esc_html_e( 'Intelligens keresés.', 'ki-mit-adna-erte' ); ?></p>
			<div class="kmae-hero-autonext-actions">
				<a href="<?php echo esc_url( $browse_url ); ?>" class="kmae-btn kmae-btn-primary kmae-hero-cta"><?php esc_html_e( 'Kezdés', 'ki-mit-adna-erte' ); ?></a>
			</div>
		</div>
	</div>
</section>
