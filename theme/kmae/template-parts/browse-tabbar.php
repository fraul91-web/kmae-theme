<?php
/**
 * Böngészés: telefonos alsó app-menüsor (álló) / oldalsáv (fekvő).
 * Csak 1024px alatt látszik (browse-mobile.css), asztalon rejtett.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kmae_bt_logged = is_user_logged_in();
$kmae_bt_icon   = static function ( $key, $size = 22 ) {
	return class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $key, array( 'size' => $size ) ) : '';
};

if ( $kmae_bt_logged ) {
	$kmae_bt_uid   = get_current_user_id();
	$kmae_bt_items = array(
		array( 'home', __( 'Főoldal', 'kmae' ), home_url( '/' ), false, 0 ),
		array( 'search', __( 'Böngészés', 'kmae' ), kmae_page_url( 'hirdetesek' ), true, 0 ),
		array( 'heart', __( 'Kedvencek', 'kmae' ), kmae_page_url( 'kedvencek' ), false, class_exists( 'KMAE_Browse' ) ? (int) KMAE_Browse::count_unseen_favorites( $kmae_bt_uid ) : 0 ),
		array( 'message-square', __( 'Üzenetek', 'kmae' ), kmae_page_url( 'uzenetek' ), false, class_exists( 'KMAE_Database' ) ? (int) KMAE_Database::count_unread_conversations( $kmae_bt_uid ) : 0 ),
	);
	$kmae_bt_fab = array( __( 'Feladás', 'kmae' ), kmae_page_url( 'hirdetes-feladasa' ) );
} else {
	$kmae_bt_items = array(
		array( 'home', __( 'Kezdő', 'kmae' ), home_url( '/' ), false, 0 ),
		array( 'search', __( 'Böngészés', 'kmae' ), kmae_page_url( 'hirdetesek' ), true, 0 ),
		array( 'circle-help', __( 'GYIK', 'kmae' ), kmae_page_url( 'gyik' ), false, 0 ),
		array( 'mail', __( 'Kapcsolat', 'kmae' ), kmae_page_url( 'kapcsolat' ), false, 0 ),
	);
	$kmae_bt_fab = array( __( 'Eladás', 'kmae' ), kmae_page_url( 'belepes' ) );
}

$kmae_bt_render = static function ( $item ) use ( $kmae_bt_icon ) {
	?>
	<a class="kmae-bt__item<?php echo $item[3] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item[2] ); ?>"<?php echo $item[3] ? ' aria-current="page"' : ''; ?>>
		<span class="kmae-bt__icon">
			<?php echo $kmae_bt_icon( $item[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $item[4] > 0 ) : ?>
				<span class="kmae-bt__badge"><?php echo esc_html( $item[4] > 99 ? '99+' : (string) $item[4] ); ?></span>
			<?php endif; ?>
		</span>
		<span class="kmae-bt__label"><?php echo esc_html( $item[1] ); ?></span>
	</a>
	<?php
};
?>
<nav class="kmae-bt" aria-label="<?php esc_attr_e( 'Gyors menü', 'kmae' ); ?>">
	<?php $kmae_bt_render( $kmae_bt_items[0] ); ?>
	<?php $kmae_bt_render( $kmae_bt_items[1] ); ?>
	<a class="kmae-bt__fab" href="<?php echo esc_url( $kmae_bt_fab[1] ); ?>">
		<span class="kmae-bt__fab-btn"><?php echo $kmae_bt_icon( 'plus', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="kmae-bt__label"><?php echo esc_html( $kmae_bt_fab[0] ); ?></span>
	</a>
	<?php $kmae_bt_render( $kmae_bt_items[2] ); ?>
	<?php $kmae_bt_render( $kmae_bt_items[3] ); ?>
</nav>
