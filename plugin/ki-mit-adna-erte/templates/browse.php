<?php
/**
 * Grid browse with filters.
 *
 * @package KMAE
 * @var array $listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories   = get_terms( array( 'taxonomy' => 'kmae_category', 'hide_empty' => false ) );
$offer_labels = KMAE_Taxonomies::offer_type_labels();
$current_cat  = isset( $_GET['kategoria'] ) ? sanitize_text_field( wp_unslash( $_GET['kategoria'] ) ) : '';
$current_type = isset( $_GET['tipus'] ) ? sanitize_text_field( wp_unslash( $_GET['tipus'] ) ) : '';
$base_url     = get_permalink();
?>
<div class="kmae-browse">
	<div class="kmae-browse-header">
		<h2><?php esc_html_e( 'Böngészés', 'ki-mit-adna-erte' ); ?></h2>
		<p class="kmae-subtitle"><?php esc_html_e( 'Nézd meg, mit adnának érte — anélkül, hogy eladnál.', 'ki-mit-adna-erte' ); ?></p>
	</div>

	<div class="kmae-filters">
		<span class="kmae-filter-label"><?php esc_html_e( 'Típus:', 'ki-mit-adna-erte' ); ?></span>
		<?php foreach ( $offer_labels as $code => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tipus', $code, remove_query_arg( 'tipus', $base_url ) ) ); ?>" class="kmae-filter-chip <?php echo $current_type === $code ? 'active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
	<div class="kmae-filters">
		<span class="kmae-filter-label"><?php esc_html_e( 'Kategória:', 'ki-mit-adna-erte' ); ?></span>
		<?php foreach ( $categories as $cat ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'kategoria', $cat->slug, $base_url ) ); ?>" class="kmae-filter-chip <?php echo $current_cat === $cat->slug ? 'active' : ''; ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endforeach; ?>
	</div>

	<?php if ( empty( $listings ) ) : ?>
		<div class="kmae-empty"><p><?php esc_html_e( 'Nincs találat.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php else : ?>
		<div class="kmae-browse-grid">
			<?php foreach ( $listings as $listing ) : ?>
				<?php
				$thumb    = KMAE_Query::get_listing_thumbnail_url( $listing->ID, 'full' );
				$types    = KMAE_Meta::get_offer_types( $listing->ID );
				$labels   = array_map( fn( $t ) => $offer_labels[ $t ] ?? $t, $types );
				$location = KMAE_Counties::format_listing_location( $listing->ID );
				/* A kártya kicsi — ne az 1920 képpontos fájl töltődjön le hozzá. */
				$card_img  = KMAE_Query::get_listing_thumbnail_id( $listing->ID );
				$card_resp = $card_img
					? KMAE_Images::responsive_attrs( $card_img, '(max-width: 700px) 92vw, 360px' )
					: array( 'srcset' => '', 'sizes' => '' );
				?>
				<a href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>" class="kmae-browse-card">
					<div class="kmae-browse-image kmae-image-frame">
						<?php if ( $thumb ) : ?>
							<img
								src="<?php echo esc_url( $thumb ); ?>"
								<?php if ( $card_resp['srcset'] ) : ?>
									srcset="<?php echo esc_attr( $card_resp['srcset'] ); ?>"
									sizes="<?php echo esc_attr( $card_resp['sizes'] ); ?>"
								<?php endif; ?>
								alt="" class="kmae-card-image" loading="lazy" decoding="async" />
						<?php else : ?>
							<div class="kmae-no-photo"><?php esc_html_e( 'Nincs kép', 'ki-mit-adna-erte' ); ?></div>
						<?php endif; ?>
					</div>
					<div class="kmae-browse-info">
						<h3><?php echo esc_html( $listing->post_title ); ?></h3>
						<?php $price_row = KMAE_Seller::get_public_price_row( $listing->ID ); ?>
						<?php if ( $price_row ) : ?>
							<span class="kmae-card-price kmae-card-price--<?php echo esc_attr( $price_row['mode'] ); ?>">
								<?php if ( KMAE_Seller::PRICE_GUIDE === $price_row['mode'] ) : ?>
									<span class="kmae-card-price__label"><?php echo esc_html( $price_row['label'] ); ?></span>
								<?php endif; ?>
								<?php echo esc_html( $price_row['formatted'] ); ?>
							</span>
						<?php else : ?>
							<span class="kmae-card-price kmae-card-price--open"><?php esc_html_e( 'Ajánlatokat vár', 'ki-mit-adna-erte' ); ?></span>
						<?php endif; ?>
						<?php if ( $location ) : ?><span class="kmae-location"><?php echo esc_html( $location ); ?></span><?php endif; ?>
						<?php
						$listing_id = $listing->ID;
						include KMAE_PLUGIN_DIR . 'templates/partials/listing-pledge-badges.php';
						?>
						<div class="kmae-type-tags">
							<?php foreach ( $labels as $label ) : ?>
								<span class="kmae-type-tag"><?php echo esc_html( $label ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
