<?php
/**
 * Phone hamburger — full menu with icons and labels.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) || ! is_user_logged_in() ) {
	return;
}

$groups = kmae_app_menu_groups();

$render = static function ( $item ) {
	?>
	<a
		class="kmae-sheet__item<?php echo ! empty( $item['active'] ) ? ' is-active' : ''; ?>"
		href="<?php echo esc_url( $item['url'] ); ?>"
	>
		<span class="kmae-sheet__icon" aria-hidden="true">
			<?php
			if ( class_exists( 'KMAE_Icons' ) ) {
				echo KMAE_Icons::render( $item['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</span>
		<span class="kmae-sheet__label"><?php echo esc_html( $item['label'] ); ?></span>
		<?php if ( ! empty( $item['badge'] ) ) : ?>
			<span class="kmae-sheet__badge<?php echo ! empty( $item['badge_class'] ) ? ' ' . esc_attr( $item['badge_class'] ) : ''; ?>"><?php echo esc_html( $item['badge'] ); ?></span>
		<?php endif; ?>
	</a>
	<?php
};
?>
<div class="kmae-sheet">
	<nav class="kmae-sheet__panel kmae-sheet__panel--nav" aria-label="<?php echo esc_attr__( 'Navigáció', 'kmae' ); ?>">
		<p class="kmae-sheet__title"><?php esc_html_e( 'Navigáció', 'kmae' ); ?></p>
		<?php foreach ( $groups['nav'] as $item ) { $render( $item ); } ?>
	</nav>
	<nav class="kmae-sheet__panel kmae-sheet__panel--info" aria-label="<?php echo esc_attr__( 'Információ', 'kmae' ); ?>">
		<p class="kmae-sheet__title"><?php esc_html_e( 'Információ', 'kmae' ); ?></p>
		<?php foreach ( $groups['info'] as $item ) { $render( $item ); } ?>
	</nav>
</div>
