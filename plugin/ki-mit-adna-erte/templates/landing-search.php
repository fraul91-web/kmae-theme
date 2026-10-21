<?php
/**
 * Landing quick search — brand / model / year.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$browse_url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
$brands     = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::brands() : array();
$years      = range( (int) gmdate( 'Y' ) + 1, 1995 );
?>
<section class="kmae-landing-search">
	<div class="kmae-landing-search-inner">
		<h2 class="kmae-landing-search-title"><?php esc_html_e( 'Keresés', 'ki-mit-adna-erte' ); ?></h2>
		<form class="kmae-landing-search-form" action="<?php echo esc_url( $browse_url ); ?>" method="get">
			<div class="kmae-landing-search-fields">
				<label class="kmae-landing-search-field">
					<span class="kmae-landing-search-label"><?php esc_html_e( 'Márka', 'ki-mit-adna-erte' ); ?></span>
					<select name="marka" class="kmae-landing-search-select">
						<option value=""><?php esc_html_e( 'Összes', 'ki-mit-adna-erte' ); ?></option>
						<?php foreach ( $brands as $brand ) : ?>
							<option value="<?php echo esc_attr( $brand ); ?>"><?php echo esc_html( $brand ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="kmae-landing-search-field">
					<span class="kmae-landing-search-label"><?php esc_html_e( 'Modell', 'ki-mit-adna-erte' ); ?></span>
					<input type="text" name="modell" class="kmae-landing-search-input" placeholder="<?php esc_attr_e( 'pl. Model 3', 'ki-mit-adna-erte' ); ?>" />
				</label>
				<label class="kmae-landing-search-field">
					<span class="kmae-landing-search-label"><?php esc_html_e( 'Évjárat', 'ki-mit-adna-erte' ); ?></span>
					<select name="evjarat" class="kmae-landing-search-select">
						<option value=""><?php esc_html_e( 'Összes', 'ki-mit-adna-erte' ); ?></option>
						<?php foreach ( $years as $year ) : ?>
							<option value="<?php echo esc_attr( (string) $year ); ?>"><?php echo esc_html( (string) $year ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<button type="submit" class="kmae-btn kmae-btn-primary kmae-landing-search-btn"><?php esc_html_e( 'Keresés', 'ki-mit-adna-erte' ); ?></button>
		</form>
	</div>
</section>
