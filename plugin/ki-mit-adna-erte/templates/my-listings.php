<?php
/**
 * My listings page.
 *
 * @package KMAE
 * @var array $listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_count = is_array( $listings ) ? count( $listings ) : 0;
$active_count = 0;
$awaiting_count = 0;
$views_total = 0;
foreach ( $listings as $listing_item ) {
	$item_status = KMAE_Meta::get_status( $listing_item->ID );
	$active_count += KMAE_Meta::STATUS_ACTIVE === $item_status ? 1 : 0;
	$awaiting_count += in_array( $item_status, array( KMAE_Meta::STATUS_AWAITING_PHOTOS, KMAE_Meta::STATUS_DRAFT ), true ) ? 1 : 0;
	$views_total += class_exists( 'KMAE_Dashboard' ) ? KMAE_Dashboard::get_listing_views( $listing_item->ID ) : 0;
}
$create_url = home_url( '/hirdetes-feladasa/' );
$reserved_ids = KMAE_Chat_Offers::reserved_listing_ids();
$ml_stats = array(
	array( 'key' => 'total', 'icon' => 'car', 'label' => __( 'Összes hirdetés', 'ki-mit-adna-erte' ), 'value' => $total_count, 'unit' => __( 'db', 'ki-mit-adna-erte' ), 'note' => __( 'Minden hirdetésed egy helyen', 'ki-mit-adna-erte' ) ),
	array( 'key' => 'active', 'icon' => 'check', 'label' => __( 'Aktív hirdetések', 'ki-mit-adna-erte' ), 'value' => $active_count, 'unit' => __( 'db', 'ki-mit-adna-erte' ), 'note' => __( 'Most is látják a vevők a Böngészésben', 'ki-mit-adna-erte' ) ),
	array( 'key' => 'awaiting', 'icon' => 'clock', 'label' => __( 'Befejezésre vár', 'ki-mit-adna-erte' ), 'value' => $awaiting_count, 'unit' => __( 'db', 'ki-mit-adna-erte' ), 'note' => __( 'Még pár lépés, és élesedik', 'ki-mit-adna-erte' ) ),
	array( 'key' => 'views', 'icon' => 'eye', 'label' => __( 'Megtekintések', 'ki-mit-adna-erte' ), 'value' => $views_total, 'unit' => '', 'note' => __( 'Ennyiszer nézték meg az autóidat', 'ki-mit-adna-erte' ) ),
);
?>
<section class="kmae-my-listings<?php echo $total_count ? '' : ' kmae-my-listings--empty'; ?>" aria-labelledby="kmae-my-listings-title" data-kmae-live-page="my_listings" data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'my_listings' ) ); ?>">
	<div class="kmae-my-listings__inner">
		<header class="kmae-my-listings__header">
			<span class="kmae-my-listings__header-orbs" aria-hidden="true"><i></i><i></i><i></i></span>
			<div class="kmae-my-listings__heading-copy">
				<p class="kmae-my-listings__eyebrow"><?php echo KMAE_Icons::render( 'car', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'A garázsod', 'ki-mit-adna-erte' ); ?></span></p>
				<h1 id="kmae-my-listings-title" class="kmae-my-listings__title"><?php esc_html_e( 'Saját', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'hirdetéseim', 'ki-mit-adna-erte' ); ?></span></h1>
				<p class="kmae-my-listings__lead"><?php esc_html_e( 'Itt kezelheted az aktív és készülő hirdetéseidet.', 'ki-mit-adna-erte' ); ?></p>
			</div>
			<span class="kmae-my-listings__radar" aria-hidden="true"><i></i><i></i><i></i><span class="kmae-my-listings__radar-core"><?php echo KMAE_Icons::render( 'car', array( 'size' => 34 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></span>
		</header>

		<?php if ( $total_count ) : ?>
			<div class="kmae-my-listings__summary" aria-label="<?php esc_attr_e( 'Hirdetések összesítése', 'ki-mit-adna-erte' ); ?>">
				<?php foreach ( $ml_stats as $ml_i => $ml_stat ) : ?>
					<div class="kmae-my-listings__stat kmae-my-listings__stat--<?php echo esc_attr( $ml_stat['key'] ); ?><?php echo $ml_stat['value'] ? '' : ' is-zero'; ?>" style="--i:<?php echo (int) $ml_i; ?>">
						<span class="kmae-my-listings__stat-icon" aria-hidden="true"><?php echo KMAE_Icons::render( $ml_stat['icon'], array( 'size' => 24 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="kmae-my-listings__stat-copy">
							<span class="kmae-my-listings__stat-label"><?php echo esc_html( $ml_stat['label'] ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $ml_stat['value'] ) ); ?><?php if ( $ml_stat['unit'] ) : ?> <small><?php echo esc_html( $ml_stat['unit'] ); ?></small><?php endif; ?></strong>
							<span class="kmae-my-listings__stat-note"><?php echo esc_html( $ml_stat['note'] ); ?></span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $listings ) ) : ?>
			<div class="kmae-my-listings__grid">
				<?php foreach ( array_values( $listings ) as $ml_card_i => $listing ) : ?>
					<?php
					$status = KMAE_Meta::get_status( $listing->ID );
					$lifecycle = KMAE_Listings::get_archive_lifecycle( $listing->ID );
					$gallery_ids = KMAE_Query::get_gallery_ids( $listing->ID );
					$thumb_id = ! empty( $gallery_ids ) ? (int) $gallery_ids[0] : (int) get_post_thumbnail_id( $listing->ID );
					$thumb = $thumb_id ? KMAE_Images::get_url( $thumb_id, 'medium_large' ) : '';
					$view_url = get_permalink( $listing->ID );
					$edit_url = add_query_arg( 'kmae_edit', $listing->ID, $create_url );
					$photos_url = add_query_arg( array( 'kmae_edit' => $listing->ID, 'kmae_photos' => '1' ), $create_url );
					$is_draft = KMAE_Meta::STATUS_DRAFT === $status;
					$draft_wizard_url = add_query_arg( 'kmae_draft', $listing->ID, $create_url );
					$draft_simple_url = add_query_arg( array( 'kmae_draft' => $listing->ID, 'kmae_mode' => 'simple' ), $create_url );
					$detail_url = KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ? $photos_url : $view_url;
					if ( $is_draft ) {
						$detail_url = $draft_wizard_url;
					}
					$can_edit = KMAE_Listings::can_edit_listing( $listing->ID );
					$can_delete = KMAE_Listings::can_delete_listing( $listing->ID );
					$is_unfinished = $is_draft || KMAE_Meta::STATUS_AWAITING_PHOTOS === $status;
					$show_delete = $can_delete || $is_unfinished;
					$can_reactivate = KMAE_Listings::can_reactivate_listing( $listing->ID );

					$status_label = __( 'Archivált', 'ki-mit-adna-erte' );
					$status_class = 'archived';
					if ( $is_draft ) {
						$status_label = __( 'Befejezésre vár', 'ki-mit-adna-erte' );
						$status_class = 'awaiting';
					} elseif ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) {
						$status_label = __( 'Fotók feltöltésére vár', 'ki-mit-adna-erte' );
						$status_class = 'awaiting';
					} elseif ( KMAE_Meta::STATUS_ACTIVE === $status && isset( $reserved_ids[ (int) $listing->ID ] ) ) {
						$status_label = __( 'Foglalt', 'ki-mit-adna-erte' );
						$status_class = 'reserved';
					} elseif ( KMAE_Meta::STATUS_ACTIVE === $status ) {
						$status_label = __( 'Aktív', 'ki-mit-adna-erte' );
						$status_class = 'active';
					} elseif ( KMAE_Meta::STATUS_CLOSED === $status ) {
						$status_label = __( 'Lezárt', 'ki-mit-adna-erte' );
						$status_class = 'closed';
					} elseif ( KMAE_Meta::STATUS_EXPIRED === $status ) {
						$status_label = __( 'Lejárt', 'ki-mit-adna-erte' );
						$status_class = 'archived';
					}
					$is_expired   = KMAE_Meta::STATUS_EXPIRED === $status;

					/* A négy legfontosabb adat ikonnal, a kép alatti sávban.
					   A feliratokat a KMAE_Vehicle adja, hogy a kártyán ugyanaz
					   álljon, mint a hirdetésben ("diesel" helyett "Dízel"). */
					$specs = KMAE_Vehicle::get_specs( $listing->ID );
					$spec_row = array();
					if ( ! empty( $specs['year'] ) ) {
						$spec_row[] = array( 'field' => 'year', 'value' => (string) $specs['year'], 'label' => __( 'Évjárat', 'ki-mit-adna-erte' ) );
					}
					if ( ! empty( $specs['mileage'] ) ) {
						$spec_row[] = array( 'field' => 'mileage', 'value' => KMAE_Vehicle::format_mileage( $specs['mileage'] ), 'label' => __( 'Futott km', 'ki-mit-adna-erte' ) );
					}
					if ( ! empty( $specs['fuel'] ) ) {
						$spec_row[] = array( 'field' => 'fuel', 'code' => (string) $specs['fuel'], 'value' => KMAE_Vehicle::fuel_labels()[ $specs['fuel'] ] ?? (string) $specs['fuel'], 'label' => __( 'Üzemanyag', 'ki-mit-adna-erte' ) );
					}
					if ( ! empty( $specs['transmission'] ) ) {
						$spec_row[] = array(
							'field' => 'transmission',
							'code'  => (string) $specs['transmission'],
							'value' => KMAE_Vehicle::transmission_labels()[ $specs['transmission'] ] ?? (string) $specs['transmission'],
							'label' => __( 'Váltó', 'ki-mit-adna-erte' ),
						);
					}

					/* Cím: márka + modell a specifikációból (mint a böngészésben), alcím: motor. */
					$ml_name = static function ( $raw ) {
						$raw = trim( (string) $raw );
						if ( '' === $raw ) {
							return '';
						}
						if ( mb_strlen( $raw ) <= 3 ) {
							return mb_strtoupper( $raw );
						}
						if ( preg_match( '/^[\p{Lu}\d\s.\-]+$/u', $raw ) ) {
							return mb_convert_case( mb_strtolower( $raw ), MB_CASE_TITLE, 'UTF-8' );
						}
						return $raw;
					};
					$card_title = trim( $ml_name( $specs['brand'] ) . ' ' . $ml_name( $specs['model'] ) );
					$card_title_sub = '';
					if ( '' === $card_title ) {
						/* Félbehagyott hirdetés: még nincs márka/modell mező — a mentett cím
						   gondolatjel előtti része a főcím, utána a motor alcímként. */
						$card_title = preg_replace( '/^\s*elad[oó]\s+/iu', '', (string) $listing->post_title );
						foreach ( array( ' – ', ' — ', ' - ' ) as $separator ) {
							if ( false !== strpos( $card_title, $separator ) ) {
								list( $card_title, $card_title_sub ) = explode( $separator, $card_title, 2 );
								$card_title_sub = trim( str_replace( '|', '·', $card_title_sub ) );
								break;
							}
						}
					}
					$card_hp      = KMAE_Vehicle::kw_to_horsepower( (int) $specs['power_kw'] );
					$card_cc      = (int) $specs['engine_cc'];
					$card_engine  = array_filter(
						array(
							$card_cc ? number_format( $card_cc / 1000, 1, ',', '' ) . ' l' : '',
							$card_hp ? $card_hp . ' LE' : '',
						)
					);
					if ( ! $card_engine && '' !== $card_title_sub ) {
						$card_engine = array( $card_title_sub );
					}
					$card_price   = KMAE_Seller::get_public_price_row( $listing->ID );
					$card_loc     = KMAE_Counties::format_listing_location( $listing->ID );
					$card_logo    = '';
					foreach ( KMAE_Vehicle::brand_logos() as $logo_name => $logo_url ) {
						if ( 0 === strcasecmp( $logo_name, (string) $specs['brand'] ) ) {
							$card_logo = $logo_url;
							break;
						}
					}
					$card_views  = class_exists( 'KMAE_Dashboard' ) ? KMAE_Dashboard::get_listing_views( $listing->ID ) : 0;
					$photo_count = count( $gallery_ids );
					?>
					<article class="kmae-my-listing-card kmae-my-listing-card--<?php echo esc_attr( $status_class ); ?><?php echo $thumb ? '' : ' kmae-my-listing-card--no-photo'; ?><?php echo $lifecycle ? ' kmae-my-listing-card--archived' : ''; ?>" style="--i:<?php echo (int) min( $ml_card_i, 11 ); ?>">
						<a class="kmae-my-listing-card__media" href="<?php echo esc_url( $detail_url ); ?>" aria-label="<?php echo esc_attr( $listing->post_title ); ?>">
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" decoding="async" />
							<?php else : ?>
								<span class="kmae-my-listing-card__no-photo"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Még nincs fotó', 'ki-mit-adna-erte' ); ?></span></span>
							<?php endif; ?>
							<span class="kmae-my-listing-card__shade" aria-hidden="true"></span>
							<span class="kmae-my-listing-card__sheen" aria-hidden="true"></span>
							<span class="kmae-my-listing-card__status"><?php echo esc_html( $status_label ); ?></span>
							<?php if ( $card_logo ) : ?>
								<span class="kmae-my-listing-card__logo" aria-hidden="true"><img src="<?php echo esc_url( $card_logo ); ?>" alt="" width="30" height="30" loading="lazy" onerror="this.parentNode.remove()" /></span>
							<?php endif; ?>
							<span class="kmae-my-listing-card__price<?php echo $card_price ? '' : ' is-open'; ?>">
								<?php if ( $card_price ) : ?>
									<small><?php echo esc_html( KMAE_Seller::PRICE_GUIDE === $card_price['mode'] ? $card_price['label'] : __( 'Fix ár', 'ki-mit-adna-erte' ) ); ?></small>
								<?php endif; ?>
								<strong><?php echo esc_html( $card_price ? $card_price['formatted'] : __( 'Ajánlatot vár', 'ki-mit-adna-erte' ) ); ?></strong>
							</span>
							<?php if ( $photo_count > 0 ) : ?>
								<span class="kmae-my-listing-card__count" aria-hidden="true">
									<?php echo KMAE_Icons::render( 'camera', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( (string) $photo_count ); ?></span>
								</span>
							<?php endif; ?>
						</a>
						<div class="kmae-my-listing-card__body">
							<div class="kmae-my-listing-card__head">
								<h2 class="kmae-my-listing-card__title"><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $card_title ); ?></a></h2>
								<?php if ( $card_engine || $card_loc ) : ?>
									<p class="kmae-my-listing-card__sub">
										<?php if ( $card_cc ) : ?>
											<span class="kmae-my-listing-card__chip"><?php echo KMAE_Vehicle_Icons::render( 'engine', '', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( number_format( $card_cc / 1000, 1, ',', '' ) . ' l' ); ?></span>
										<?php endif; ?>
										<?php if ( $card_hp ) : ?>
											<span class="kmae-my-listing-card__chip"><?php echo KMAE_Vehicle_Icons::render( 'power', '', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $card_hp . ' LE' ); ?></span>
										<?php endif; ?>
										<?php if ( ! $card_cc && ! $card_hp && $card_engine ) : ?>
											<span><?php echo esc_html( implode( ' · ', $card_engine ) ); ?></span>
										<?php endif; ?>
										<?php if ( $card_loc ) : ?>
											<span class="kmae-my-listing-card__chip kmae-my-listing-card__loc"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $card_loc ); ?></span>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</div>
							<?php if ( $spec_row ) : ?>
								<div class="kmae-my-listing-card__specs">
									<?php foreach ( $spec_row as $spec ) : ?>
										<div class="kmae-my-listing-card__spec" title="<?php echo esc_attr( $spec['label'] ); ?>">
											<span class="kmae-my-listing-card__spec-icon" aria-hidden="true"><?php echo KMAE_Vehicle_Icons::render( $spec['field'], $spec['code'] ?? '', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
											<strong class="kmae-my-listing-card__spec-text"><?php echo esc_html( $spec['value'] ); ?></strong>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php if ( $is_draft ) : ?>
								<div class="kmae-my-listing-card__notice"><?php echo KMAE_Icons::render( 'pencil', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><strong><?php esc_html_e( 'Még nincs feladva.', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'Folytasd ott, ahol abbahagytad.', 'ki-mit-adna-erte' ); ?></span></div>
							<?php elseif ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) : ?>
								<div class="kmae-my-listing-card__notice"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ( get_post_meta( $listing->ID, '_kmae_photos_removed', true ) ) : ?><span><strong><?php esc_html_e( 'Nincs fotó, ezért nem látszik.', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'Tölts fel legalább egyet, és újra megjelenik.', 'ki-mit-adna-erte' ); ?></span><?php else : ?><span><strong><?php esc_html_e( 'Még nem publikus.', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'Töltsd fel a fotókat.', 'ki-mit-adna-erte' ); ?></span><?php endif; ?></div>
							<?php elseif ( KMAE_Meta::STATUS_ACTIVE === $status ) : ?>
								<div class="kmae-my-listing-card__stats">
									<span class="kmae-my-listing-card__stat kmae-my-listing-card__stat--views"><i aria-hidden="true"><?php echo KMAE_Icons::render( 'eye', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i><span><strong><?php echo esc_html( number_format_i18n( $card_views ) ); ?></strong> <?php esc_html_e( 'megtekintés', 'ki-mit-adna-erte' ); ?></span></span>
									<span class="kmae-my-listing-card__stat kmae-my-listing-card__stat--offers"><i aria-hidden="true"><?php echo KMAE_Icons::render( 'tag', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i><span><strong><?php echo esc_html( number_format_i18n( KMAE_Chat_Offers::count_live_for_listing( $listing->ID ) ) ); ?></strong> <?php esc_html_e( 'ajánlat érkezett', 'ki-mit-adna-erte' ); ?></span></span>
								</div>
							<?php elseif ( $is_expired ) : ?>
								<div class="kmae-my-listing-card__notice"><?php echo KMAE_Icons::render( 'hourglass', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><strong><?php esc_html_e( 'Lejárt, nem látszik a böngészésben.', 'ki-mit-adna-erte' ); ?></strong> <?php echo esc_html( sprintf( /* translators: %d: days */ __( 'Hosszabbítsd meg újabb %d napra.', 'ki-mit-adna-erte' ), KMAE_Listings::EXPIRE_DAYS ) ); ?></span></div>
							<?php endif; ?>
							<div class="kmae-my-listing-card__actions">
								<?php if ( $is_draft ) : ?>
									<a class="kmae-my-listing-card__button kmae-my-listing-card__button--primary" href="<?php echo esc_url( $draft_wizard_url ); ?>" data-kmae-draft-edit data-kmae-draft-wizard="<?php echo esc_url( $draft_wizard_url ); ?>" data-kmae-draft-simple="<?php echo esc_url( $draft_simple_url ); ?>">
										<?php echo KMAE_Icons::render( 'pencil', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Folytatás', 'ki-mit-adna-erte' ); ?></span>
										<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								<?php elseif ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) : ?>
									<a class="kmae-my-listing-card__button kmae-my-listing-card__button--primary" href="<?php echo esc_url( $photos_url ); ?>">
										<?php echo KMAE_Icons::render( 'camera', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Fotók feltöltése', 'ki-mit-adna-erte' ); ?></span>
										<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								<?php else : ?>
									<a class="kmae-my-listing-card__button kmae-my-listing-card__button--primary" href="<?php echo esc_url( $view_url ); ?>">
										<?php echo KMAE_Icons::render( 'eye', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?></span>
										<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								<?php endif; ?>
								<?php if ( $is_expired ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'kmae_renew_listing', 'kmae_renew_listing_nonce' ); ?><input type="hidden" name="action" value="kmae_renew_listing" /><input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $listing->ID ); ?>" /><button class="kmae-my-listing-card__button kmae-my-listing-card__button--primary" type="submit"><?php echo KMAE_Icons::render( 'repeat', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Hosszabbítás', 'ki-mit-adna-erte' ); ?></span></button></form>
								<?php endif; ?>
								<?php if ( $can_reactivate ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'kmae_reactivate_listing', 'kmae_reactivate_listing_nonce' ); ?><input type="hidden" name="action" value="kmae_reactivate_listing" /><input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $listing->ID ); ?>" /><button class="kmae-my-listing-card__button kmae-my-listing-card__button--primary" type="submit"><?php echo KMAE_Icons::render( 'repeat', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Újraaktiválás', 'ki-mit-adna-erte' ); ?></span></button></form>
								<?php endif; ?>
								<?php if ( ( $can_edit && ! $is_draft ) || $show_delete ) : ?>
									<div class="kmae-my-listing-card__subactions">
										<?php if ( $can_edit && ! $is_draft ) : ?>
											<a class="kmae-my-listing-card__button kmae-my-listing-card__button--secondary" href="<?php echo esc_url( $edit_url ); ?>">
												<?php echo KMAE_Icons::render( 'pencil', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<span><?php esc_html_e( 'Szerkesztés', 'ki-mit-adna-erte' ); ?></span>
											</a>
										<?php endif; ?>
										<?php if ( $show_delete ) : ?>
											<form class="kmae-my-listing-card__delete-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-kmae-delete-confirm data-kmae-delete-title="<?php echo esc_attr( $card_title ); ?>" data-kmae-delete-sub="<?php echo esc_attr( implode( ' · ', array_filter( array( $specs['year'] ? (string) $specs['year'] : '', implode( ' · ', $card_engine ) ) ) ) ); ?>" data-kmae-delete-thumb="<?php echo esc_url( $thumb ); ?>" data-kmae-delete-status="<?php echo esc_attr( $status_label ); ?>" data-kmae-delete-kind="<?php echo $is_draft ? 'draft' : 'listing'; ?>"<?php echo ! $is_draft && KMAE_Chat_Offers::get_accepted_for_listing( $listing->ID ) ? ' data-kmae-delete-reserved="1"' : ''; ?>><?php wp_nonce_field( 'kmae_delete_listing', 'kmae_delete_listing_nonce' ); ?><input type="hidden" name="action" value="kmae_delete_listing" /><input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $listing->ID ); ?>" /><button class="kmae-my-listing-card__button kmae-my-listing-card__button--danger" type="submit">
												<?php echo KMAE_Icons::render( 'trash', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<span><?php esc_html_e( 'Törlés', 'ki-mit-adna-erte' ); ?></span>
											</button></form>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
				<a class="kmae-my-listing-new" href="<?php echo esc_url( $create_url ); ?>" style="--i:<?php echo (int) min( $total_count, 12 ); ?>">
					<span class="kmae-my-listing-new__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'plus', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<strong><?php esc_html_e( 'Új hirdetés', 'ki-mit-adna-erte' ); ?></strong>
					<span><?php esc_html_e( 'Van még egy autód? Add fel pár perc alatt.', 'ki-mit-adna-erte' ); ?></span>
				</a>
			</div>
		<?php else : ?>
			<div class="kmae-my-listings__empty">
				<div class="kmae-my-listings__empty-visual" aria-hidden="true">
					<span class="kmae-my-listings__empty-halo"></span>
					<span class="kmae-my-listings__empty-spark kmae-my-listings__empty-spark--a"><?php echo KMAE_Icons::render( 'sparkles', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-my-listings__empty-spark kmae-my-listings__empty-spark--b"><?php echo KMAE_Icons::render( 'sparkles', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-my-listings__empty-spark kmae-my-listings__empty-spark--c"><?php echo KMAE_Icons::render( 'car', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<img class="kmae-my-listings__empty-mascot" src="https://albira.hu/wp-content/uploads/2026/10/szia3.webp" alt="" width="220" height="250" decoding="async" />
				</div>
				<div class="kmae-my-listings__empty-copy">
					<p class="kmae-my-listings__empty-eyebrow"><?php esc_html_e( 'Még üres a garázsod', 'ki-mit-adna-erte' ); ?></p>
					<h2 class="kmae-my-listings__empty-title"><?php esc_html_e( 'Add fel az első autódat, és nézd meg,', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'ki mit adna érte!', 'ki-mit-adna-erte' ); ?></span></h2>
					<p class="kmae-my-listings__empty-lead"><?php esc_html_e( 'Pár perc az egész. A hirdetésed itt fog élni: látod, hányan nézték meg, és innen kezelsz mindent.', 'ki-mit-adna-erte' ); ?></p>
					<ol class="kmae-my-listings__empty-steps">
						<li style="--i:0"><span class="kmae-my-listings__empty-step-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'pencil', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><strong><?php esc_html_e( 'Az autó adatai', 'ki-mit-adna-erte' ); ?></strong><small><?php esc_html_e( 'Lépésről lépésre végigvezetünk.', 'ki-mit-adna-erte' ); ?></small></span></li>
						<li style="--i:1"><span class="kmae-my-listings__empty-step-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><strong><?php esc_html_e( 'Pár jó fotó', 'ki-mit-adna-erte' ); ?></strong><small><?php esc_html_e( 'A szép képek több vevőt hoznak.', 'ki-mit-adna-erte' ); ?></small></span></li>
						<li style="--i:2"><span class="kmae-my-listings__empty-step-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'message-square', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><strong><?php esc_html_e( 'Jönnek az ajánlatok', 'ki-mit-adna-erte' ); ?></strong><small><?php esc_html_e( 'A vevők üzenetben jelentkeznek, te döntesz.', 'ki-mit-adna-erte' ); ?></small></span></li>
					</ol>
					<a class="kmae-my-listings__empty-cta" href="<?php echo esc_url( $create_url ); ?>"><?php echo KMAE_Icons::render( 'plus', array( 'size' => 19 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Első hirdetésem feladása', 'ki-mit-adna-erte' ); ?></span><?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 19 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php /* Törlés megerősítése oldalon belül (a böngésző saját ablaka helyett). */ ?>
	<div class="kmae-delete-confirm" id="kmae-delete-confirm" role="alertdialog" aria-modal="true" aria-labelledby="kmae-delete-confirm-title" aria-describedby="kmae-delete-confirm-list" hidden>
		<div class="kmae-delete-confirm__backdrop" data-kmae-delete-cancel></div>
		<div class="kmae-delete-confirm__card">
			<img class="kmae-delete-confirm__mascot" src="https://albira.hu/wp-content/uploads/2026/10/szomoru3.webp" alt="" aria-hidden="true" width="220" height="354" decoding="async" />
			<p class="kmae-delete-confirm__eyebrow"><?php echo KMAE_Icons::render( 'trash', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span data-kmae-delete-eyebrow><?php esc_html_e( 'Hirdetés törlése', 'ki-mit-adna-erte' ); ?></span></p>
			<h2 class="kmae-delete-confirm__title" id="kmae-delete-confirm-title" data-kmae-delete-heading><?php esc_html_e( 'Biztosan törlöd?', 'ki-mit-adna-erte' ); ?></h2>

			<div class="kmae-delete-confirm__item">
				<span class="kmae-delete-confirm__thumb" data-kmae-delete-thumb-box><img alt="" data-kmae-delete-thumb hidden /><?php echo KMAE_Icons::render( 'car', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-delete-confirm__item-text">
					<strong data-kmae-delete-name></strong>
					<small data-kmae-delete-sub></small>
				</span>
				<span class="kmae-delete-confirm__status" data-kmae-delete-status></span>
			</div>

			<ul class="kmae-delete-confirm__list" id="kmae-delete-confirm-list" data-kmae-delete-list>
				<li data-kmae-delete-for="listing"><?php echo KMAE_Icons::render( 'eye', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'A hirdetés azonnal eltűnik a keresőből.', 'ki-mit-adna-erte' ); ?></span></li>
				<li data-kmae-delete-for="draft"><?php echo KMAE_Icons::render( 'pencil', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'A félbehagyott hirdetés és a beírt adatai végleg törlődnek.', 'ki-mit-adna-erte' ); ?></span></li>
				<li class="is-strong"><?php echo KMAE_Icons::render( 'triangle-alert', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Ez nem vonható vissza.', 'ki-mit-adna-erte' ); ?></span></li>
			</ul>

			<div class="kmae-delete-confirm__actions">
				<button type="button" class="kmae-delete-confirm__btn kmae-delete-confirm__btn--cancel" data-kmae-delete-cancel><?php esc_html_e( 'Mégse, megtartom', 'ki-mit-adna-erte' ); ?></button>
				<button type="button" class="kmae-delete-confirm__btn kmae-delete-confirm__btn--ok" data-kmae-delete-ok>
					<?php echo KMAE_Icons::render( 'trash', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span data-kmae-delete-ok-label><?php esc_html_e( 'Igen, törlöm', 'ki-mit-adna-erte' ); ?></span>
				</button>
			</div>
		</div>
	</div>

	<?php /* Félbehagyott hirdetés szerkesztése: a Szerkesztés gomb körül ívben
		kinyíló két kerek opció (egyszerű űrlap / Albira Wizard). */ ?>
	<div class="kmae-draft-radial" id="kmae-draft-radial" role="menu" aria-label="<?php esc_attr_e( 'Hogyan folytatod?', 'ki-mit-adna-erte' ); ?>" hidden>
		<a class="kmae-draft-radial__item kmae-draft-radial__item--simple" href="#" role="menuitem" data-kmae-draft-link="simple">
			<span class="kmae-draft-radial__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'pencil', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="kmae-draft-radial__label"><?php echo wp_kses( __( 'Egyszerű<br>szerkesztés', 'ki-mit-adna-erte' ), array( 'br' => array() ) ); ?></span>
		</a>
		<a class="kmae-draft-radial__item kmae-draft-radial__item--wizard" href="#" role="menuitem" data-kmae-draft-link="wizard">
			<span class="kmae-draft-radial__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'sparkles', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="kmae-draft-radial__label"><?php echo wp_kses( __( 'Albira<br>Wizard', 'ki-mit-adna-erte' ), array( 'br' => array() ) ); ?></span>
		</a>
	</div>
</section>
