<?php
/**
 * Landing feature strip — AutoNext style.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$features = array(
	array(
		'icon'  => 'sparkles',
		'title' => __( 'AI segít a hirdetésedhez', 'ki-mit-adna-erte' ),
	),
	array(
		'icon'  => 'zap',
		'title' => __( '1 kattintásos érdeklődés', 'ki-mit-adna-erte' ),
	),
);
?>
<section class="kmae-landing-features kmae-landing-features-autonext">
	<div class="kmae-landing-features-inner">
		<ul class="kmae-landing-features-grid">
			<?php foreach ( $features as $feature ) : ?>
				<li class="kmae-landing-feature">
					<span class="kmae-landing-feature-icon" aria-hidden="true">
						<?php echo KMAE_Icons::render( $feature['icon'], array( 'size' => 24 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="kmae-landing-feature-label"><?php echo esc_html( $feature['title'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
