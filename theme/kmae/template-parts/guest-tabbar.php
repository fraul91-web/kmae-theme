<?php
/**
 * Vendég telefonos alsó menüsor (Kezdő / Böngészés / Eladás / GYIK / Kapcsolat).
 * Főoldal, GYIK, Biztonság, Kapcsolat, ÁSZF, Adatvédelem. Stílus: guest-tabbar.css.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url    = home_url( '/' );
$browse_url  = function_exists( 'kmae_page_url' ) ? kmae_page_url( 'hirdetesek' ) : home_url( '/hirdetesek/' );
$login_url   = function_exists( 'kmae_page_url' ) ? kmae_page_url( 'belepes' ) : wp_login_url();
$faq_url     = function_exists( 'kmae_page_url' ) ? kmae_page_url( 'gyik' ) : home_url( '/gyik/' );
$contact_url = function_exists( 'kmae_page_url' ) ? kmae_page_url( 'kapcsolat' ) : home_url( '/kapcsolat/' );

$is_home    = is_front_page();
$is_faq     = function_exists( 'kmae_is_faq_page' ) && kmae_is_faq_page();
$is_contact = function_exists( 'kmae_is_contact_page' ) && kmae_is_contact_page();

$icon = static function ( $paths, $size = 22 ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};

$kses_svg = array(
	'svg'    => array(
		'width'           => true,
		'height'          => true,
		'viewbox'         => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'aria-hidden'     => true,
	),
	'path'   => array( 'd' => true ),
	'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ),
	'rect'   => array(
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
	),
	'span'   => array( 'class' => true ),
);

$home_href = $is_home ? '#kmae-lp-hero-title' : $home_url;
?>
<nav class="kmae-lp-tabbar" aria-label="<?php echo esc_attr__( 'Gyors menü', 'kmae' ); ?>">
	<a class="kmae-lp-tabbar__item<?php echo $is_home ? ' is-active' : ''; ?>" href="<?php echo esc_url( $home_href ); ?>">
		<?php
		echo wp_kses(
			$icon( '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>' ),
			$kses_svg
		);
		?>
		<span><?php esc_html_e( 'Kezdő', 'kmae' ); ?></span>
	</a>
	<a class="kmae-lp-tabbar__item" href="<?php echo esc_url( $browse_url ); ?>">
		<?php
		echo wp_kses(
			$icon( '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>' ),
			$kses_svg
		);
		?>
		<span><?php esc_html_e( 'Böngészés', 'kmae' ); ?></span>
	</a>
	<a class="kmae-lp-tabbar__fab" href="<?php echo esc_url( $login_url ); ?>">
		<span class="kmae-lp-tabbar__fab-btn">
			<?php
			echo wp_kses(
				$icon( '<path d="M12 5v14M5 12h14"/>', 26 ),
				$kses_svg
			);
			?>
		</span>
		<span><?php esc_html_e( 'Eladás', 'kmae' ); ?></span>
	</a>
	<a class="kmae-lp-tabbar__item<?php echo $is_faq ? ' is-active' : ''; ?>" href="<?php echo esc_url( $faq_url ); ?>">
		<?php
		echo wp_kses(
			$icon( '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>' ),
			$kses_svg
		);
		?>
		<span><?php esc_html_e( 'GYIK', 'kmae' ); ?></span>
	</a>
	<a class="kmae-lp-tabbar__item<?php echo $is_contact ? ' is-active' : ''; ?>" href="<?php echo esc_url( $contact_url ); ?>">
		<?php
		echo wp_kses(
			$icon( '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>' ),
			$kses_svg
		);
		?>
		<span><?php esc_html_e( 'Kapcsolat', 'kmae' ); ?></span>
	</a>
</nav>
