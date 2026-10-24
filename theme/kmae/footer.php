<?php
/**
 * Footer + mobile bottom nav.
 *
 * @package KMAE
 */

$kmae_logged_in = is_user_logged_in();
$kmae_app_shell = function_exists( 'kmae_uses_app_shell' ) ? kmae_uses_app_shell() : $kmae_logged_in;
?>
</main>

<?php if ( $kmae_app_shell ) : ?>
		</div><!-- .kmae-app-content -->
	</div><!-- .kmae-app-layout -->
<?php endif; ?>

<footer class="kmae-footer" id="site-footer">
	<button
		type="button"
		class="kmae-footer__toggle"
		id="kmae-footer-toggle"
		aria-expanded="false"
		aria-controls="kmae-footer-panel"
		aria-label="<?php echo esc_attr__( 'Lábléc megnyitása', 'kmae' ); ?>"
		data-label-open="<?php echo esc_attr__( 'Lábléc bezárása', 'kmae' ); ?>"
		data-label-closed="<?php echo esc_attr__( 'Lábléc megnyitása', 'kmae' ); ?>"
	>
		<span class="kmae-footer__toggle-icon" aria-hidden="true"></span>
	</button>

	<div class="kmae-footer__panel" id="kmae-footer-panel" hidden>
		<div class="kmae-container kmae-footer__inner">
			<p class="kmae-footer__copy">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<?php esc_html_e( 'Albira. Minden jog fenntartva.', 'kmae' ); ?>
			</p>

			<nav class="kmae-footer__nav" aria-label="<?php echo esc_attr__( 'Lábléc navigáció', 'kmae' ); ?>">
				<a class="kmae-footer__link" href="<?php echo esc_url( kmae_page_url( 'gyik' ) ); ?>"><?php esc_html_e( 'Gyakori kérdések', 'kmae' ); ?></a>
				<a class="kmae-footer__link" href="<?php echo esc_url( kmae_page_url( 'biztonsag' ) ); ?>"><?php esc_html_e( 'Biztonság', 'kmae' ); ?></a>
				<a class="kmae-footer__link" href="<?php echo esc_url( kmae_page_url( 'kapcsolat' ) ); ?>"><?php esc_html_e( 'Kapcsolat', 'kmae' ); ?></a>
				<a class="kmae-footer__link" href="<?php echo esc_url( kmae_page_url( 'feltetelek' ) ); ?>"><?php esc_html_e( 'ÁSZF és felhasználási feltételek', 'kmae' ); ?></a>
				<a class="kmae-footer__link" href="<?php echo esc_url( kmae_page_url( 'adatvedelem' ) ); ?>"><?php esc_html_e( 'Adatvédelem', 'kmae' ); ?></a>
			</nav>
		</div>
	</div>
</footer>

<?php if ( $kmae_logged_in ) : ?>
	<nav class="kmae-bottom-nav" aria-label="<?php echo esc_attr__( 'Alsó navigáció', 'kmae' ); ?>">
		<a class="kmae-bottom-nav__item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
			<span><?php esc_html_e( 'Főoldal', 'kmae' ); ?></span>
		</a>
		<a class="kmae-bottom-nav__item kmae-bottom-nav__item--sell" href="<?php echo esc_url( kmae_page_url( 'eladni' ) ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
			<span><?php esc_html_e( 'Eladni', 'kmae' ); ?></span>
		</a>
		<a class="kmae-bottom-nav__item kmae-bottom-nav__item--buy" href="<?php echo esc_url( kmae_page_url( 'venni' ) ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
			<span><?php esc_html_e( 'Venni', 'kmae' ); ?></span>
		</a>
		<a class="kmae-bottom-nav__item kmae-bottom-nav__item--stats" href="<?php echo esc_url( kmae_page_url( 'statisztikak' ) ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
			<span><?php esc_html_e( 'Statisztikák', 'kmae' ); ?></span>
		</a>
	</nav>
<?php endif; ?>

<?php
if ( function_exists( 'kmae_show_guest_tabbar' ) && kmae_show_guest_tabbar() ) {
	get_template_part( 'template-parts/guest-tabbar' );
}

if ( function_exists( 'kmae_is_browse_page' ) && kmae_is_browse_page() ) {
	get_template_part( 'template-parts/browse-tabbar' );
}
?>

<?php get_template_part( 'template-parts/browse-rotate' ); ?>

<?php wp_footer(); ?>
</body>
</html>
