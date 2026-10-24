<?php
/**
 * Kereskedők megkeresése: az eladó küldő ablaka (natív <dialog>, oldalanként egyszer).
 * Logika: assets/js/dealer-leads.js, adat: KMAE_Dealer_Leads::client_config().
 *
 * @package KMAE
 * @var int[] $dealer_leads_listing_ids Az oldalon gombot kapó hirdetések.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( empty( $dealer_leads_listing_ids ) || ! class_exists( 'KMAE_Dealer_Leads' ) ) {
	return;
}

$dl_configs = array();
foreach ( array_unique( array_map( 'intval', $dealer_leads_listing_ids ) ) as $dl_lid ) {
	$dl_cfg = KMAE_Dealer_Leads::client_config( $dl_lid );
	$dl_cfg['priceMin']     = isset( $dl_cfg['priceMin'] ) ? (int) $dl_cfg['priceMin'] : 50000;
	$dl_cfg['priceMax']     = isset( $dl_cfg['priceMax'] ) ? (int) $dl_cfg['priceMax'] : 500000000;
	$dl_cfg['listingPrice'] = isset( $dl_cfg['listingPrice'] ) && $dl_cfg['listingPrice'] ? (int) $dl_cfg['listingPrice'] : null;
	$dl_cfg['listingTitle'] = get_the_title( $dl_lid );
	$dl_configs[ $dl_lid ]  = $dl_cfg;
}
$dl_notif_url = KMAE_Dealer_Leads::notifications_url();
?>
<dialog class="kmae-dl" id="kmae-dl" aria-labelledby="kmae-dl-title" data-kmae-dl data-notifications-url="<?php echo esc_url( $dl_notif_url ); ?>">
	<script type="application/json" data-kmae-dl-config><?php echo wp_json_encode( $dl_configs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	<div class="kmae-dl__panel">
		<div class="kmae-dl__head">
			<div class="kmae-dl__head-copy">
				<h2 class="kmae-dl__title" id="kmae-dl-title"><?php esc_html_e( 'Kereskedők megkeresése', 'ki-mit-adna-erte' ); ?></h2>
				<p class="kmae-dl__car" data-kmae-dl-car></p>
			</div>
			<button type="button" class="kmae-dl__x" data-kmae-dl-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'x', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>

		<div class="kmae-dl__body" data-kmae-dl-body>
			<p class="kmae-dl__unavailable" data-kmae-dl-unavailable hidden></p>

			<section class="kmae-dl__step" data-kmae-dl-step="radius">
				<h3 class="kmae-dl__q"><span class="kmae-dl__n">1</span><?php esc_html_e( 'Mekkora távolságra vinnéd el az autót?', 'ki-mit-adna-erte' ); ?></h3>
				<div class="kmae-dl__chips" role="radiogroup" aria-label="<?php esc_attr_e( 'Távolság', 'ki-mit-adna-erte' ); ?>" data-kmae-dl-radii></div>
				<p class="kmae-dl__result" data-kmae-dl-radius-result aria-live="polite" hidden></p>
			</section>

			<section class="kmae-dl__step" data-kmae-dl-step="purpose" hidden>
				<h3 class="kmae-dl__q"><span class="kmae-dl__n">2</span><?php esc_html_e( 'Mit szeretnél?', 'ki-mit-adna-erte' ); ?></h3>
				<div class="kmae-dl__cards" role="group" aria-label="<?php esc_attr_e( 'Cél', 'ki-mit-adna-erte' ); ?>" data-kmae-dl-purposes></div>
				<p class="kmae-dl__hint"><?php esc_html_e( 'Akár mindkettőt is választhatod.', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-dl__result" data-kmae-dl-purpose-result aria-live="polite" hidden></p>
				<p class="kmae-dl__note" data-kmae-dl-contacted hidden></p>
			</section>

			<section class="kmae-dl__step" data-kmae-dl-step="price" hidden>
				<h3 class="kmae-dl__q"><span class="kmae-dl__n">3</span><label for="kmae-dl-price" data-kmae-dl-price-label><?php esc_html_e( 'Kereskedői ár', 'ki-mit-adna-erte' ); ?></label></h3>
				<div class="kmae-dl__price">
					<input type="text" inputmode="numeric" autocomplete="off" id="kmae-dl-price" class="kmae-dl__input" data-kmae-dl-price aria-describedby="kmae-dl-price-hint kmae-dl-price-msg" />
					<span class="kmae-dl__unit" aria-hidden="true">Ft</span>
				</div>
				<p class="kmae-dl__hint" id="kmae-dl-price-hint"><?php esc_html_e( 'A kereskedők jellemzően a piaci ár alatt vásárolnak. Az itt megadott árat csak a kereskedők látják, a hirdetésed ára nem változik.', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-dl__warn" id="kmae-dl-price-msg" data-kmae-dl-price-msg aria-live="polite" hidden></p>
			</section>

			<p class="kmae-dl__error" data-kmae-dl-error role="alert" hidden></p>
		</div>

		<div class="kmae-dl__success" data-kmae-dl-success hidden>
			<span class="kmae-dl__check" aria-hidden="true"><?php echo KMAE_Icons::render( 'check', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p class="kmae-dl__success-msg" data-kmae-dl-success-msg></p>
			<p class="kmae-dl__success-sub"><?php esc_html_e( 'A válaszokat az Értesítések oldalon látod.', 'ki-mit-adna-erte' ); ?></p>
			<a class="kmae-dl__btn kmae-dl__btn--go" href="<?php echo esc_url( $dl_notif_url ); ?>"><?php esc_html_e( 'Értesítések megnyitása', 'ki-mit-adna-erte' ); ?></a>
		</div>

		<div class="kmae-dl__foot" data-kmae-dl-foot>
			<button type="button" class="kmae-dl__btn kmae-dl__btn--ghost" data-kmae-dl-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
			<button type="button" class="kmae-dl__btn kmae-dl__btn--send" data-kmae-dl-send disabled><?php esc_html_e( 'Elküldöm', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</div>
</dialog>
