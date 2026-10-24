<?php
/**
 * Böngészés — listanézet belépett felhasználóknak (KMAE_Browse::is_list_view()).
 *
 * 50 hirdetés oldalanként (`?oldal=N`), alul lapozó. Stílus: theme browse.css
 * „Céges listanézet” blokk.
 *
 * Két adatforrás, ugyanaz a markup: a régi (WP_Post[]) és a Böngészés v2 (`$list_idx`: int[] ID-sorrend,
 * a sorok a kmae_browse_index táblából — KMAE_Browse_Feed::list_order / list_rows).
 *
 * @package KMAE
 * @var WP_Post[]|int[] $listings
 * @var bool            $list_idx Az adat az indexből jön (browse-swipe.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$list_total = count( $listings );
$list_pages = max( 1, (int) ceil( $list_total / KMAE_Browse::LIST_PER_PAGE ) );
$list_page  = isset( $_GET['oldal'] ) ? absint( $_GET['oldal'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$list_page  = min( max( 1, $list_page ), $list_pages );
$list_from  = ( $list_page - 1 ) * KMAE_Browse::LIST_PER_PAGE;
$list_rows  = array_slice( $listings, $list_from, KMAE_Browse::LIST_PER_PAGE );
$list_idx   = ! empty( $list_idx );
$list_data  = array();
$list_thumbs = array();
if ( $list_idx ) {
	/* Az oldal sorai egy lekérdezéssel; az időközben inaktívvá vált (indexből kiesett) hirdetés kimarad. */
	$list_data   = KMAE_Browse_Feed::list_rows( $list_rows );
	$list_rows   = array_values(
		array_filter(
			$list_rows,
			static function ( $id ) use ( $list_data ) {
				return isset( $list_data[ (int) $id ] );
			}
		)
	);
	$list_thumbs = KMAE_Browse_Feed::list_thumbs( $list_rows );
}
$list_sort  = KMAE_Browse::current_sort( $wizard_summary['mod'] ?? '' );
$list_to    = $list_from + count( $list_rows );

$list_url = static function ( $page ) {
	return 1 === (int) $page ? remove_query_arg( 'oldal' ) : add_query_arg( 'oldal', (int) $page );
};

/* Lapszámok: első, utolsó, és az aktuális ±2; köztük „…”. */
$list_numbers = array();
for ( $n = 1; $n <= $list_pages; $n++ ) {
	if ( 1 === $n || $list_pages === $n || abs( $n - $list_page ) <= 2 ) {
		$list_numbers[] = $n;
	} elseif ( end( $list_numbers ) !== '…' ) {
		$list_numbers[] = '…';
	}
}

$list_name = static function ( $raw ) {
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
$list_logos   = $list_idx ? array() : KMAE_Vehicle::brand_logos();
$reserved_ids = $list_idx ? array() : KMAE_Chat_Offers::reserved_listing_ids();
?>
<section class="kmae-browse-list" aria-label="<?php esc_attr_e( 'Hirdetések listája', 'ki-mit-adna-erte' ); ?>">
	<header class="kmae-browse-list__head">
		<p class="kmae-browse-list__total">
			<strong><?php echo esc_html( number_format_i18n( $list_total ) ); ?></strong>
			<?php esc_html_e( 'hirdetés', 'ki-mit-adna-erte' ); ?>
		</p>
		<p class="kmae-browse-list__range">
			<?php
			printf(
				/* translators: 1: first row number, 2: last row number, 3: current page, 4: page count */
				esc_html__( '%1$d–%2$d. megjelenítve · %3$d. oldal / %4$d', 'ki-mit-adna-erte' ),
				(int) $list_from + 1,
				(int) $list_to,
				(int) $list_page,
				(int) $list_pages
			);
			?>
		</p>
	</header>

	<div class="kmae-browse-list__rows">
		<?php foreach ( $list_rows as $listing ) : ?>
			<?php
			if ( $list_idx ) {
				$row_id     = (int) $listing;
				$row_data   = $list_data[ $row_id ];
				$row_card   = $row_data['card'];
				$row_url    = (string) $row_card['url'];
				$row_thumb  = $list_thumbs[ $row_id ] ?? '';
				$row_photo  = isset( $row_card['photos'][0] ) ? (array) $row_card['photos'][0] : array();
				$row_srcset = array();
				foreach ( array( 's' => 480, 'm' => 800 ) as $row_slot => $row_width ) {
					if ( ! empty( $row_photo[ $row_slot ] ) ) {
						$row_srcset[] = $row_photo[ $row_slot ] . ' ' . $row_width . 'w';
					}
				}
				$row_srcset   = $row_thumb ? implode( ', ', $row_srcset ) : '';
				$row_title    = (string) $row_card['title']['list'];
				$row_hp       = (int) $row_card['lead']['hp'];
				$row_cc       = (int) $row_card['lead']['cc'];
				$row_loc      = (string) $row_card['location'];
				$row_price    = $row_card['price'];
				$row_count    = (int) $row_card['photo_count'];
				$row_logo     = (string) $row_card['brand_logo'];
				$row_facts    = (array) $row_card['facts'];
				$row_reserved = $row_data['reserved'];
				$row_km       = ( 'tavolsag' === $list_sort['sort'] && null !== $row_data['lat'] && null !== $row_data['lon'] )
					? KMAE_Geo::distance_km( $list_sort['lat'], $list_sort['lon'], $row_data['lat'], $row_data['lon'] )
					: null;
			} else {
				$row_id    = (int) $listing->ID;
				$row_srcset = '';
				$row_url   = get_permalink( $listing->ID );
				$row_thumb = KMAE_Query::get_listing_thumbnail_url( $listing->ID, 'medium_large' );
				$row_specs = KMAE_Vehicle::get_specs( $listing->ID );
				$row_title = trim( $list_name( $row_specs['brand'] ) . ' ' . $list_name( $row_specs['model'] ) );
				if ( '' === $row_title ) {
					$row_title = preg_replace( '/^\s*elad[oó]\s+/iu', '', (string) $listing->post_title );
				}
				$row_hp    = KMAE_Vehicle::kw_to_horsepower( (int) $row_specs['power_kw'] );
				$row_cc    = (int) $row_specs['engine_cc'];
				$row_loc   = KMAE_Counties::format_listing_location( $listing->ID );
				$row_price = KMAE_Seller::get_public_price_row( $listing->ID );
				$row_count = count( KMAE_Query::get_gallery_ids( $listing->ID ) );
				$row_logo  = '';
				foreach ( $list_logos as $logo_name => $logo_url ) {
					if ( 0 === strcasecmp( $logo_name, (string) $row_specs['brand'] ) ) {
						$row_logo = $logo_url;
						break;
					}
				}
				$row_facts = array_values(
					array_filter(
						array(
							array( 'field' => 'year', 'value' => ! empty( $row_specs['year'] ) ? (string) $row_specs['year'] : '', 'label' => __( 'Évjárat', 'ki-mit-adna-erte' ) ),
							array( 'field' => 'mileage', 'value' => KMAE_Vehicle::format_mileage( $row_specs['mileage'] ), 'label' => __( 'Futott km', 'ki-mit-adna-erte' ) ),
							array( 'field' => 'fuel', 'code' => (string) $row_specs['fuel'], 'value' => KMAE_Vehicle::fuel_labels()[ $row_specs['fuel'] ] ?? '', 'label' => __( 'Üzemanyag', 'ki-mit-adna-erte' ) ),
							array( 'field' => 'transmission', 'code' => (string) $row_specs['transmission'], 'value' => KMAE_Vehicle::transmission_labels()[ $row_specs['transmission'] ] ?? '', 'label' => __( 'Váltó', 'ki-mit-adna-erte' ) ),
						),
						static function ( $fact ) {
							return '' !== $fact['value'];
						}
					)
				);
				$row_reserved = isset( $reserved_ids[ $row_id ] );
				$row_km       = KMAE_Browse::distance_for( $row_id );
			}
			?>
			<article class="kmae-browse-row<?php echo $row_reserved ? ' is-reserved' : ''; ?>" data-id="<?php echo esc_attr( (string) $row_id ); ?>">
				<div class="kmae-browse-row__glass">
					<a class="kmae-browse-row__photo" href="<?php echo esc_url( $row_url ); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( $row_thumb ) : ?>
							<img src="<?php echo esc_url( $row_thumb ); ?>"<?php if ( $row_srcset ) : ?> srcset="<?php echo esc_attr( $row_srcset ); ?>" sizes="(max-width: 1024px) 112px, 236px"<?php endif; ?> alt="" loading="lazy" decoding="async" width="200" height="150" />
						<?php else : ?>
							<span class="kmae-browse-row__photo-fallback"></span>
						<?php endif; ?>
						<?php if ( $row_reserved ) : ?>
							<span class="kmae-browse-row__reserved"><?php esc_html_e( 'Foglalt', 'ki-mit-adna-erte' ); ?></span>
						<?php endif; ?>
						<?php if ( $row_count > 1 ) : ?>
							<span class="kmae-browse-row__count"><?php echo KMAE_Icons::render( 'camera', array( 'size' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) $row_count ); ?></span>
						<?php endif; ?>
					</a>

					<div class="kmae-browse-row__main">
						<div class="kmae-browse-row__titleline">
							<?php if ( $row_logo ) : ?>
								<span class="kmae-browse-row__logo"><img src="<?php echo esc_url( $row_logo ); ?>" alt="" width="30" height="30" loading="lazy" onerror="this.parentNode.remove()" /></span>
							<?php endif; ?>
							<h3 class="kmae-browse-row__title"><a href="<?php echo esc_url( $row_url ); ?>"><?php echo esc_html( $row_title ); ?></a></h3>
							<?php if ( $row_hp || $row_cc ) : ?>
								<p class="kmae-browse-row__stats">
									<?php if ( $row_hp ) : ?>
										<span class="kmae-browse-row__stat"><?php echo KMAE_Vehicle_Icons::render( 'power', '', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $row_hp . ' LE' ); ?></span></span>
									<?php endif; ?>
									<?php if ( $row_hp && $row_cc ) : ?>
										<span class="kmae-browse-row__dot" aria-hidden="true"></span>
									<?php endif; ?>
									<?php if ( $row_cc ) : ?>
										<span class="kmae-browse-row__stat"><?php echo KMAE_Vehicle_Icons::render( 'engine', '', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( number_format( $row_cc, 0, ',', ' ' ) . ' cm³' ); ?></span></span>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>

						<?php if ( $row_facts ) : ?>
							<div class="kmae-browse-row__facts">
								<?php foreach ( $row_facts as $fact ) : ?>
									<div class="kmae-browse-row__fact">
										<span class="kmae-browse-row__fact-icon" aria-hidden="true"><?php echo KMAE_Vehicle_Icons::render( $fact['field'], $fact['code'] ?? '', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="kmae-browse-row__fact-copy">
											<strong><?php echo esc_html( $fact['value'] ); ?></strong>
											<small><?php echo esc_html( $fact['label'] ); ?></small>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $row_loc ) : ?>
							<p class="kmae-browse-row__loc"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $row_loc ); ?></span><?php if ( null !== $row_km ) : ?><em><?php echo esc_html( '~' . number_format_i18n( max( 1, round( $row_km ) ) ) . ' km' ); ?></em><?php endif; ?></p>
						<?php endif; ?>
					</div>

					<div class="kmae-browse-row__aside">
						<?php if ( $row_price ) : ?>
							<p class="kmae-browse-row__price kmae-browse-row__price--<?php echo esc_attr( $row_price['mode'] ); ?>">
								<small><?php echo esc_html( KMAE_Seller::PRICE_GUIDE === $row_price['mode'] ? $row_price['label'] : __( 'Fix ár', 'ki-mit-adna-erte' ) ); ?></small>
								<strong><?php echo esc_html( $row_price['formatted'] ); ?></strong>
							</p>
						<?php else : ?>
							<p class="kmae-browse-row__price kmae-browse-row__price--open">
								<small><?php esc_html_e( 'Ki mit adna érte', 'ki-mit-adna-erte' ); ?></small>
								<strong><?php esc_html_e( 'Ajánlatot vár', 'ki-mit-adna-erte' ); ?></strong>
							</p>
						<?php endif; ?>
						<a class="kmae-browse-row__go" href="<?php echo esc_url( $row_url ); ?>">
							<?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?>
							<?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $list_pages > 1 ) : ?>
		<nav class="kmae-browse-pager" aria-label="<?php esc_attr_e( 'Oldalak', 'ki-mit-adna-erte' ); ?>">
			<?php if ( $list_page > 1 ) : ?>
				<a class="kmae-browse-pager__step" href="<?php echo esc_url( $list_url( $list_page - 1 ) ); ?>" rel="prev">
					<?php echo KMAE_Icons::render( 'chevron-left', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Előző', 'ki-mit-adna-erte' ); ?></span>
				</a>
			<?php else : ?>
				<span class="kmae-browse-pager__step is-disabled" aria-hidden="true"><?php echo KMAE_Icons::render( 'chevron-left', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Előző', 'ki-mit-adna-erte' ); ?></span></span>
			<?php endif; ?>

			<span class="kmae-browse-pager__numbers">
				<?php foreach ( $list_numbers as $number ) : ?>
					<?php if ( '…' === $number ) : ?>
						<span class="kmae-browse-pager__gap" aria-hidden="true">…</span>
					<?php elseif ( $number === $list_page ) : ?>
						<span class="kmae-browse-pager__num is-current" aria-current="page"><?php echo esc_html( (string) $number ); ?></span>
					<?php else : ?>
						<a class="kmae-browse-pager__num" href="<?php echo esc_url( $list_url( $number ) ); ?>"><?php echo esc_html( (string) $number ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</span>

			<?php if ( $list_page < $list_pages ) : ?>
				<a class="kmae-browse-pager__step" href="<?php echo esc_url( $list_url( $list_page + 1 ) ); ?>" rel="next">
					<span><?php esc_html_e( 'Következő', 'ki-mit-adna-erte' ); ?></span>
					<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php else : ?>
				<span class="kmae-browse-pager__step is-disabled" aria-hidden="true"><span><?php esc_html_e( 'Következő', 'ki-mit-adna-erte' ); ?></span><?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</section>
