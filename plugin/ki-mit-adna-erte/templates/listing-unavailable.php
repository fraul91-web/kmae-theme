<?php
/**
 * Lejárt hirdetés a látogatónak: „Ez az autó már nem elérhető” + hasonló autók.
 * (A tulajdonos és az admin a rendes adatlapot látja: KMAE_Post_Types::is_unavailable_for_viewer.)
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_robots', 'wp_robots_no_robots' );

get_header();

$listing_id = get_queried_object_id();
$title      = KMAE_Vehicle::get_display_title( $listing_id );
$similar    = KMAE_Query::get_similar_listings( $listing_id, 4 );
$browse_url = KMAE_Pages::browse_page_url();
?>
<article class="kmae-listing-unavailable">
	<section class="kmae-listing-unavailable__panel">
		<span class="kmae-listing-unavailable__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'hourglass', array( 'size' => 28 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h1 class="kmae-listing-unavailable__title"><?php esc_html_e( 'Ez az autó már nem elérhető', 'ki-mit-adna-erte' ); ?></h1>
		<?php if ( '' !== $title ) : ?>
			<p class="kmae-listing-unavailable__car"><?php echo esc_html( $title ); ?></p>
		<?php endif; ?>
		<p class="kmae-listing-unavailable__text"><?php esc_html_e( 'A hirdetés lejárt, ezért már nem lehet rá üzenetet vagy ajánlatot küldeni. Nézd meg a hasonló autókat!', 'ki-mit-adna-erte' ); ?></p>
		<a class="kmae-listing-unavailable__browse" href="<?php echo esc_url( $browse_url ); ?>"><?php esc_html_e( 'Autók böngészése', 'ki-mit-adna-erte' ); ?></a>
	</section>

	<?php if ( $similar ) : ?>
		<section class="kmae-listing-unavailable__similar" aria-labelledby="kmae-similar-title">
			<h2 class="kmae-listing-unavailable__similar-title" id="kmae-similar-title"><?php esc_html_e( 'Hasonló autók', 'ki-mit-adna-erte' ); ?></h2>
			<div class="kmae-listing-unavailable__grid">
				<?php foreach ( $similar as $item ) : ?>
					<?php
					$item_id    = (int) $item->ID;
					$item_thumb = KMAE_Query::get_listing_thumbnail_url( $item_id, 'medium_large' );
					$item_specs = KMAE_Vehicle::get_specs( $item_id );
					$item_price = KMAE_Seller::get_public_price_row( $item_id );
					$item_meta  = array_filter(
						array(
							! empty( $item_specs['year'] ) ? (string) $item_specs['year'] : '',
							! empty( $item_specs['mileage'] ) ? KMAE_Vehicle::format_mileage( $item_specs['mileage'] ) : '',
						)
					);
					?>
					<a class="kmae-listing-unavailable__card" href="<?php echo esc_url( get_permalink( $item_id ) ); ?>">
						<span class="kmae-listing-unavailable__card-photo">
							<?php if ( $item_thumb ) : ?>
								<img src="<?php echo esc_url( $item_thumb ); ?>" alt="" loading="lazy" decoding="async" />
							<?php else : ?>
								<?php echo KMAE_Icons::render( 'car', array( 'size' => 32 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						</span>
						<span class="kmae-listing-unavailable__card-body">
							<strong class="kmae-listing-unavailable__card-title"><?php echo esc_html( KMAE_Vehicle::get_display_title( $item_id ) ); ?></strong>
							<?php if ( $item_meta ) : ?>
								<span class="kmae-listing-unavailable__card-meta"><?php echo esc_html( implode( ' · ', $item_meta ) ); ?></span>
							<?php endif; ?>
							<span class="kmae-listing-unavailable__card-price"><?php echo esc_html( $item_price ? $item_price['formatted'] : __( 'Ajánlatot vár', 'ki-mit-adna-erte' ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</article>
<?php
get_footer();
