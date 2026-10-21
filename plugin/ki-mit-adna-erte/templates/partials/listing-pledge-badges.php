<?php
/**
 * Commitment / price badges for listing cards.
 *
 * @package KMAE
 * @var int $listing_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pledge_badges = KMAE_Seller::get_badge_rows( $listing_id );
if ( empty( $pledge_badges ) ) {
	return;
}
?>
<div class="kmae-pledge-badges">
	<?php foreach ( $pledge_badges as $badge ) : ?>
		<span class="kmae-pledge-badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
	<?php endforeach; ?>
</div>
