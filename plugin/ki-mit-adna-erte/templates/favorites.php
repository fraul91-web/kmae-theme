<?php
/**
 * Saved listings for the current user.
 *
 * @package KMAE
 * @var WP_Post[] $listings
 * @var string    $browse_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_listings = ! empty( $listings );
$reserved_ids = $has_listings ? KMAE_Chat_Offers::reserved_listing_ids() : array();
?>
<div class="kmae-favorites<?php echo $has_listings ? '' : ' is-empty'; ?>">
	<?php if ( $has_listings ) : ?>
		<?php
		$fav_total    = count( $listings );
		$fav_reserved = 0;
		foreach ( $listings as $listing ) {
			if ( isset( $reserved_ids[ (int) $listing->ID ] ) ) {
				++$fav_reserved;
			}
		}
		?>
		<header class="kmae-favorites__hero">
			<span class="kmae-favorites__hero-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div class="kmae-favorites__hero-copy">
				<span class="kmae-favorites__eyebrow"><?php esc_html_e( 'Mentett autóid', 'ki-mit-adna-erte' ); ?></span>
				<h1 class="kmae-favorites__title"><?php esc_html_e( 'Kedvenceid', 'ki-mit-adna-erte' ); ?></h1>
				<p class="kmae-favorites__lead"><?php esc_html_e( 'Ezek az autók tetszettek neked. Nézd meg őket újra, és írj az eladónak, mielőtt más csap le rájuk.', 'ki-mit-adna-erte' ); ?></p>
			</div>
			<div class="kmae-favorites__hero-side">
				<ul class="kmae-favorites__stats">
					<li class="kmae-favorites__stat">
						<strong><?php echo esc_html( (string) $fav_total ); ?></strong>
						<span><?php esc_html_e( 'mentett autó', 'ki-mit-adna-erte' ); ?></span>
					</li>
					<?php if ( $fav_reserved > 0 ) : ?>
						<li class="kmae-favorites__stat kmae-favorites__stat--reserved">
							<strong><?php echo esc_html( (string) $fav_reserved ); ?></strong>
							<span><?php esc_html_e( 'már foglalt', 'ki-mit-adna-erte' ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
				<button type="button" class="kmae-favorites__clear" data-kmae-favorites-clear>
					<?php echo KMAE_Icons::render( 'trash', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Lista ürítése', 'ki-mit-adna-erte' ); ?>
				</button>
			</div>
		</header>
	<?php else : ?>
		<div class="kmae-favorites__empty">
			<div class="kmae-favorites__stage">
				<img class="kmae-favorites__mascot" src="<?php echo esc_url( 'https://albira.hu/wp-content/uploads/2026/10/szomoru3.webp' ); ?>" alt="" width="150" height="241">
				<div class="kmae-favorites__copy">
					<h2 class="kmae-favorites__empty-title"><?php esc_html_e( 'Még nincs mentett hirdetésed', 'ki-mit-adna-erte' ); ?></h2>
					<p class="kmae-favorites__empty-text"><?php esc_html_e( 'Ha böngészés közben megtetszik egy autó, kattints a szív ikonra vagy nyomd meg a Space billentyűt, és itt később könnyen visszatalálod.', 'ki-mit-adna-erte' ); ?></p>
					<a class="kmae-favorites__browse" href="<?php echo esc_url( $browse_url ); ?>">
						<?php echo KMAE_Icons::render( 'search', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Autók böngészése', 'ki-mit-adna-erte' ); ?>
						<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>
			</div>
			<ul class="kmae-favorites__perks">
				<li>
					<span class="kmae-favorites__perk-icon"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<strong><?php esc_html_e( 'Mentsd el, ami tetszik', 'ki-mit-adna-erte' ); ?></strong>
						<?php esc_html_e( 'Egy kattintás a szív ikonra vagy nyomd meg a Space-t.', 'ki-mit-adna-erte' ); ?>
					</span>
				</li>
				<li>
					<span class="kmae-favorites__perk-icon"><?php echo KMAE_Icons::render( 'clock', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<strong><?php esc_html_e( 'Nézd meg később', 'ki-mit-adna-erte' ); ?></strong>
						<?php esc_html_e( 'A kedvenceid itt mindig kéznél lesznek.', 'ki-mit-adna-erte' ); ?>
					</span>
				</li>
				<li>
					<span class="kmae-favorites__perk-icon"><?php echo KMAE_Icons::render( 'star', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<strong><?php esc_html_e( 'Találd meg könnyebben', 'ki-mit-adna-erte' ); ?></strong>
						<?php esc_html_e( 'Ne veszítsd el a számodra érdekes autókat.', 'ki-mit-adna-erte' ); ?>
					</span>
				</li>
			</ul>
		</div>
	<?php endif; ?>
	<?php if ( $has_listings ) : ?>
		<div class="kmae-favorites__grid">
			<?php foreach ( $listings as $fav_index => $listing ) : ?>
				<?php
				$thumb       = KMAE_Query::get_listing_thumbnail_url( $listing->ID, 'medium_large' );
				$price_row   = KMAE_Seller::get_public_price_row( $listing->ID );
				$location    = KMAE_Counties::format_listing_location( $listing->ID );
				$permalink   = get_permalink( $listing->ID );
				$fav_specs   = KMAE_Vehicle::get_specs( $listing->ID );
				$is_reserved = isset( $reserved_ids[ (int) $listing->ID ] );
				$photo_count = count( KMAE_Query::get_gallery_ids( $listing->ID ) );
				$fav_logo    = '';
				foreach ( KMAE_Vehicle::brand_logos() as $logo_name => $logo_url ) {
					if ( 0 === strcasecmp( $logo_name, (string) $fav_specs['brand'] ) ) {
						$fav_logo = $logo_url;
						break;
					}
				}
				$fav_facts = array(
					array( 'field' => 'year', 'code' => '', 'value' => ! empty( $fav_specs['year'] ) ? (string) $fav_specs['year'] : '' ),
					array( 'field' => 'mileage', 'code' => '', 'value' => KMAE_Vehicle::format_mileage( $fav_specs['mileage'] ) ),
					array( 'field' => 'fuel', 'code' => (string) $fav_specs['fuel'], 'value' => KMAE_Vehicle::fuel_labels()[ $fav_specs['fuel'] ] ?? '' ),
					array( 'field' => 'transmission', 'code' => (string) $fav_specs['transmission'], 'value' => KMAE_Vehicle::transmission_labels()[ $fav_specs['transmission'] ] ?? '' ),
				);
				$fav_facts = array_values(
					array_filter(
						$fav_facts,
						static function ( $fact ) {
							return '' !== $fact['value'];
						}
					)
				);
				?>
				<article class="kmae-fav-card<?php echo $is_reserved ? ' is-reserved' : ''; ?><?php echo $thumb ? '' : ' is-no-photo'; ?>" style="--fav-i: <?php echo esc_attr( (string) min( (int) $fav_index, 12 ) ); ?>">
					<a class="kmae-fav-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $listing->post_title ); ?>">
						<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" decoding="async" />
						<?php else : ?>
							<span class="kmae-fav-card__no-photo"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Nincs fotó', 'ki-mit-adna-erte' ); ?></span></span>
						<?php endif; ?>
						<span class="kmae-fav-card__shade" aria-hidden="true"></span>
						<?php if ( $fav_logo ) : ?>
							<span class="kmae-fav-card__logo" aria-hidden="true"><img src="<?php echo esc_url( $fav_logo ); ?>" alt="" width="30" height="30" loading="lazy" onerror="this.parentNode.remove()" /></span>
						<?php endif; ?>
						<?php if ( $is_reserved ) : ?>
							<span class="kmae-fav-card__reserved" title="<?php esc_attr_e( 'Az eladó elfogadott egy ajánlatot, most nem lehet új ajánlatot tenni.', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'lock', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Foglalt', 'ki-mit-adna-erte' ); ?></span>
						<?php endif; ?>
						<span class="kmae-fav-card__price<?php echo $price_row ? '' : ' is-open'; ?>">
							<?php if ( $price_row ) : ?>
								<small><?php echo esc_html( KMAE_Seller::PRICE_GUIDE === $price_row['mode'] ? $price_row['label'] : __( 'Fix ár', 'ki-mit-adna-erte' ) ); ?></small>
							<?php endif; ?>
							<strong><?php echo esc_html( $price_row ? $price_row['formatted'] : __( 'Ajánlatot vár', 'ki-mit-adna-erte' ) ); ?></strong>
						</span>
						<?php if ( $photo_count > 0 ) : ?>
							<span class="kmae-fav-card__count" aria-hidden="true"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( (string) $photo_count ); ?></span></span>
						<?php endif; ?>
					</a>
					<form method="post" class="kmae-fav-card__remove-form">
						<?php wp_nonce_field( 'kmae_remove_favorite_nonce', 'kmae_remove_favorite_nonce' ); ?>
						<input type="hidden" name="kmae_action" value="remove_favorite" />
						<input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
						<button type="submit" class="kmae-fav-card__remove" aria-label="<?php esc_attr_e( 'Törlés a kedvencek közül', 'ki-mit-adna-erte' ); ?>">
							<span class="kmae-fav-card__remove-heart"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-fav-card__remove-trash"><?php echo KMAE_Icons::render( 'trash', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-fav-card__tip" role="tooltip"><?php esc_html_e( 'Törlés a kedvencek közül', 'ki-mit-adna-erte' ); ?></span>
						</button>
					</form>
					<div class="kmae-fav-card__body">
						<h2 class="kmae-fav-card__name"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></h2>
						<?php if ( $location ) : ?>
							<p class="kmae-fav-card__place"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $location ); ?></span></p>
						<?php endif; ?>
						<?php if ( $fav_facts ) : ?>
							<ul class="kmae-fav-card__facts">
								<?php foreach ( $fav_facts as $fact ) : ?>
									<li><?php echo KMAE_Vehicle_Icons::render( $fact['field'], $fact['code'], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $fact['value'] ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<a class="kmae-fav-card__details" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Megnézem', 'ki-mit-adna-erte' ); ?><span aria-hidden="true"><?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<form method="post" id="kmae-favorites-clear-form" hidden>
			<?php wp_nonce_field( 'kmae_clear_favorites_nonce', 'kmae_clear_favorites_nonce' ); ?>
			<input type="hidden" name="kmae_action" value="clear_favorites" />
		</form>
	<?php endif; ?>
</div>
<?php if ( $has_listings ) : ?>
	<div class="kmae-favorites-confirm" id="kmae-favorites-confirm" role="alertdialog" aria-modal="true" aria-labelledby="kmae-favorites-confirm-title" hidden>
		<div class="kmae-favorites-confirm__backdrop" data-kmae-favorites-cancel></div>
		<div class="kmae-favorites-confirm__card">
			<h2 id="kmae-favorites-confirm-title"><?php esc_html_e( 'Biztosan törölni szeretnéd az összes mentett hirdetést a kedvencek közül?', 'ki-mit-adna-erte' ); ?></h2>
			<div class="kmae-favorites-confirm__actions">
				<button type="button" class="kmae-favorites-confirm__cancel" data-kmae-favorites-cancel><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
				<button type="button" class="kmae-favorites-confirm__ok" data-kmae-favorites-ok><?php esc_html_e( 'Igen, ürítés', 'ki-mit-adna-erte' ); ?></button>
			</div>
		</div>
	</div>
	<script>
	(function () {
		var open = document.querySelector('[data-kmae-favorites-clear]');
		var dialog = document.getElementById('kmae-favorites-confirm');
		var form = document.getElementById('kmae-favorites-clear-form');
		if (!open || !dialog || !form) return;
		function show() { dialog.hidden = false; }
		function hide() { dialog.hidden = true; }
		open.addEventListener('click', show);
		dialog.querySelectorAll('[data-kmae-favorites-cancel]').forEach(function (node) {
			node.addEventListener('click', hide);
		});
		var ok = dialog.querySelector('[data-kmae-favorites-ok]');
		if (ok) ok.addEventListener('click', function () { form.submit(); });
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && !dialog.hidden) hide();
		});
	})();
	</script>
<?php endif; ?>
