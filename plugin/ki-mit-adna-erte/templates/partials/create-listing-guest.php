<?php
/**
 * Guest gate — hirdetés feladása (bejelentkezés szükséges).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$login_url    = KMAE_Auth::login_url( get_permalink() );
$register_url = KMAE_Auth::register_url();
?>
<section class="kmae-listing-guest" aria-labelledby="kmae-listing-guest-title">
	<div class="kmae-listing-guest__shell">
		<h1 id="kmae-listing-guest-title" class="kmae-listing-guest__title">
			<?php esc_html_e( 'Hirdetés feladása', 'ki-mit-adna-erte' ); ?>
		</h1>

		<div class="kmae-listing-guest__columns">
			<div class="kmae-listing-guest__col kmae-listing-guest__col--login">
				<p class="kmae-listing-guest__text">
					<?php esc_html_e( 'A hirdetés feladásához jelentkezz be.', 'ki-mit-adna-erte' ); ?>
				</p>
				<a href="<?php echo esc_url( $login_url ); ?>" class="kmae-btn kmae-btn--login kmae-listing-guest__btn">
					<?php esc_html_e( 'Belépés', 'ki-mit-adna-erte' ); ?>
				</a>
			</div>

			<div class="kmae-listing-guest__col kmae-listing-guest__col--register">
				<p class="kmae-listing-guest__text">
					<?php esc_html_e( 'Ha még nincs fiókod, regisztrálj.', 'ki-mit-adna-erte' ); ?>
				</p>
				<a href="<?php echo esc_url( $register_url ); ?>" class="kmae-btn kmae-btn--register kmae-listing-guest__btn">
					<?php esc_html_e( 'Regisztráció', 'ki-mit-adna-erte' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
