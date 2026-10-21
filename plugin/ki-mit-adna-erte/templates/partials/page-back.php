<?php
/**
 * Inline back button — bottom of page, centered.
 *
 * @package KMAE
 * @var string $back_url
 * @var string $back_label
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$back_url   = isset( $back_url ) ? $back_url : ( class_exists( 'KMAE_Back' ) ? KMAE_Back::home_url() : home_url( '/' ) );
$back_label = isset( $back_label ) ? $back_label : ( class_exists( 'KMAE_Back' ) ? KMAE_Back::home_label() : __( 'Vissza', 'ki-mit-adna-erte' ) );
?>
<div class="kmae-page-back">
	<a href="<?php echo esc_url( $back_url ); ?>" class="kmae-btn kmae-btn-secondary kmae-btn-back"><?php echo esc_html( $back_label ); ?></a>
</div>
