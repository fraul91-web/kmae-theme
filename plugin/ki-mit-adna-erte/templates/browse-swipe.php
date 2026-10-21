<?php
/**
 * Tinder-style swipe browse (after wizard).
 *
 * @package KMAE
 * @var array  $listings
 * @var array  $wizard_summary
 * @var string $browse_base_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_vehicle_wizard = KMAE_Vehicle::is_vehicle_launch() && ! empty( $wizard_summary['mod'] );
$restart_keys      = $is_vehicle_wizard
	? array( 'megye', 'kivitel', 'mod', 'kp_min', 'kp_max', 'step', 'marka', 'modell', 'tilt', 'ccm_min', 'ccm_max', 'le_min', 'le_max', 'ev_min', 'ev_max', 'km_min', 'km_max', 'uzem', 'valto', 'muszaki', 'mot_min', 'mot_max', 'mot_lejart', 'allapot', 'ar', 'osszes' )
	: array( 'megye', 'tipus', 'kategoria' );
$restart_url       = remove_query_arg( $restart_keys, $browse_base_url );
$offer_labels      = KMAE_Taxonomies::offer_type_labels();
$favorite_ids      = is_user_logged_in() ? KMAE_Browse::get_favorite_listing_ids( get_current_user_id() ) : array();
$reserved_ids      = KMAE_Chat_Offers::reserved_listing_ids();
/* Belépett fiók (céges és magánszemély): kártyás böngészés vagy lista (50 / oldal) — KMAE_Browse::is_list_view(). */
$view_browse       = is_user_logged_in();
$list_view         = $view_browse && KMAE_Browse::is_list_view();
/* Böngészés v2 (KMAE_Browse_Feed::is_enabled): a kártya-stack helyett templates/browse-swipe-v2.php;
   a találat-lista innen a feedből jön (üres → a lenti üres állapot). Lista nézet / régi kártyák: változatlan. */
$sw2               = KMAE_Browse_Feed::use_card_index()
	? KMAE_Browse_Feed::client_state( wp_unslash( $_GET ), get_current_user_id() ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	: null;
if ( $sw2 ) {
	$listings = $sw2['order'];
}
/* Böngészés v2 lista nézet: a személyre szabott ID-sorrend az indexből (a shortcode ekkor nem kérte le a hirdetéseket);
   a sorokat a partials/browse-list.php az oldalhoz tartozó 50 ID-hoz tölti be. */
$list_idx = $list_view && KMAE_Browse_Feed::use_list_index();
if ( $list_idx ) {
	$listings = KMAE_Browse_Feed::list_order( wp_unslash( $_GET ), get_current_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
?>
<div class="kmae-swipe-browse<?php echo $sw2 ? ' kmae-sw2-browse' : ''; ?>">
	<?php
	if ( KMAE_Vehicle::is_vehicle_launch() ) {
		include KMAE_PLUGIN_DIR . 'templates/partials/browse-filter-rail.php';
	}
	?>
	<?php
	$sort_mod     = $wizard_summary['mod'] ?? '';
	$sort_req     = KMAE_Browse::current_sort( $sort_mod );
	$sort_options = KMAE_Browse::sort_options( $sort_mod );
	$sort_now     = $sort_options[ $sort_req['sort'] ];
	$sort_clean   = remove_query_arg( array( 'rendez', 'lat', 'lon', 'km', 'oldal' ) );
	$is_distance  = 'tavolsag' === $sort_req['sort'];
	?>
	<?php if ( ! empty( $listings ) || KMAE_Browse::SORT_DEFAULT !== $sort_req['sort'] ) : ?>
		<div class="kmae-browse-toolbar">
			<div class="kmae-browse-sort" data-kmae-sort>
				<button type="button" class="kmae-browse-sort__btn" data-kmae-sort-toggle aria-haspopup="menu" aria-expanded="false">
					<span class="kmae-browse-sort__icon" aria-hidden="true"><?php echo KMAE_Icons::render( $sort_now['icon'], array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-browse-sort__text">
						<small><?php esc_html_e( 'Rendezés', 'ki-mit-adna-erte' ); ?></small>
						<strong><?php echo esc_html( $sort_now['label'] ); ?></strong>
					</span>
					<span class="kmae-browse-sort__chev" aria-hidden="true"><?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</button>
				<div class="kmae-browse-sort__menu" role="menu" data-kmae-sort-menu hidden>
					<?php foreach ( $sort_options as $sort_key => $sort_option ) : ?>
						<?php $sort_current = $sort_key === $sort_req['sort']; ?>
						<?php if ( 'tavolsag' === $sort_key ) : ?>
							<button type="button" class="kmae-browse-sort__item<?php echo $sort_current ? ' is-current' : ''; ?>" role="menuitem" data-kmae-sort-distance>
						<?php else : ?>
							<a class="kmae-browse-sort__item<?php echo $sort_current ? ' is-current' : ''; ?>" role="menuitem" href="<?php echo esc_url( KMAE_Browse::SORT_DEFAULT === $sort_key ? $sort_clean : add_query_arg( 'rendez', $sort_key, $sort_clean ) ); ?>">
						<?php endif; ?>
							<span class="kmae-browse-sort__item-icon" aria-hidden="true"><?php echo KMAE_Icons::render( $sort_option['icon'], array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-browse-sort__item-label"><?php echo esc_html( $sort_option['label'] ); ?></span>
							<?php if ( 'tavolsag' === $sort_key ) : ?>
								<small><?php esc_html_e( 'a helyzeted alapján', 'ki-mit-adna-erte' ); ?></small>
							<?php endif; ?>
							<?php if ( $sort_current ) : ?>
								<span class="kmae-browse-sort__check" aria-hidden="true"><?php echo KMAE_Icons::render( 'check', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						<?php echo 'tavolsag' === $sort_key ? '</button>' : '</a>'; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $is_distance ) : ?>
				<button type="button" class="kmae-browse-radius" data-kmae-sort-radius>
					<?php echo KMAE_Icons::render( 'crosshair', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span>
						<?php
						echo $sort_req['km'] > 0
							/* translators: %d: radius in km */
							? esc_html( sprintf( __( '%d km-es körben', 'ki-mit-adna-erte' ), $sort_req['km'] ) )
							: esc_html__( 'Egész ország', 'ki-mit-adna-erte' );
						?>
					</span>
					<?php echo KMAE_Icons::render( 'pencil', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			<?php endif; ?>

			<?php if ( $view_browse && ! empty( $listings ) ) : ?>
				<nav class="kmae-browse-viewswitch" aria-label="<?php esc_attr_e( 'Nézet', 'ki-mit-adna-erte' ); ?>">
					<a class="kmae-browse-viewswitch__btn<?php echo $list_view ? '' : ' is-active'; ?>" href="<?php echo esc_url( add_query_arg( 'nezet', 'kartya', remove_query_arg( 'oldal' ) ) ); ?>"<?php echo $list_view ? '' : ' aria-current="true"'; ?>>
						<?php echo KMAE_Icons::render( 'layers', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Kártyák', 'ki-mit-adna-erte' ); ?></span>
					</a>
					<a class="kmae-browse-viewswitch__btn<?php echo $list_view ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'nezet', 'lista', remove_query_arg( 'oldal' ) ) ); ?>"<?php echo $list_view ? ' aria-current="true"' : ''; ?>>
						<?php echo KMAE_Icons::render( 'list', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Lista', 'ki-mit-adna-erte' ); ?></span>
					</a>
				</nav>
			<?php endif; ?>
		</div>
		<?php include KMAE_PLUGIN_DIR . 'templates/partials/browse-distance.php'; ?>
	<?php endif; ?>
	<?php if ( empty( $listings ) ) : ?>
		<?php
		$all_url = add_query_arg( 'osszes', '1', $browse_base_url );
		$chips   = array();
		if ( $is_vehicle_wizard ) {
			$county = $wizard_summary['county'] ?? '';
			$bodies = isset( $wizard_summary['bodies'] ) && is_array( $wizard_summary['bodies'] ) ? $wizard_summary['bodies'] : array();
			$mod    = $wizard_summary['mod'] ?? '';
			if ( $county ) {
				$chips[] = array(
					'icon'   => 'map-pin',
					'label'  => KMAE_Counties::get_label( $county ),
					'remove' => $restart_url,
				);
			}
			if ( $bodies ) {
				$chips[] = array(
					'icon'   => 'car',
					'label'  => KMAE_Browse::bodies_label( $bodies ),
					'remove' => add_query_arg( 'megye', $county, $browse_base_url ),
				);
			}
			if ( $mod ) {
				$modes   = KMAE_Vehicle::browse_mode_labels();
				$chips[] = array(
					'icon'   => 'tag',
					'label'  => $modes[ $mod ] ?? $mod,
					'remove' => add_query_arg(
						array(
							'megye'   => $county,
							'kivitel' => KMAE_Browse::bodies_to_query_value( $bodies ),
						),
						$browse_base_url
					),
				);
			}
		}
		?>
		<div class="kmae-swipe-empty">
			<div class="kmae-swipe-empty__card">
				<div class="kmae-swipe-empty__mascot">
					<img src="https://albira.hu/wp-content/uploads/2026/10/szomoru3.webp" alt="" width="220" height="354" decoding="async" />
					<span class="kmae-swipe-empty__badge" aria-hidden="true">
						<?php echo KMAE_Icons::render( 'search', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo KMAE_Icons::render( 'x', array( 'size' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</div>
				<div class="kmae-swipe-empty__body">
					<h3 class="kmae-swipe-empty__title"><?php esc_html_e( 'Nincs', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'találat', 'ki-mit-adna-erte' ); ?></span></h3>
					<p class="kmae-swipe-empty__text"><?php esc_html_e( 'Ezekkel a szűrőkkel most nem találtunk autót. Módosíts a feltételeken, és nézzük meg újra.', 'ki-mit-adna-erte' ); ?></p>
					<div class="kmae-swipe-empty__actions">
						<a class="kmae-swipe-empty__btn kmae-swipe-empty__btn--edit" href="<?php echo esc_url( $restart_url ); ?>">
							<?php echo KMAE_Icons::render( 'sliders', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Szűrők módosítása', 'ki-mit-adna-erte' ); ?></span>
							<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<a class="kmae-swipe-empty__btn kmae-swipe-empty__btn--all" href="<?php echo esc_url( $all_url ); ?>">
							<?php echo KMAE_Icons::render( 'car', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Összes hirdetés', 'ki-mit-adna-erte' ); ?></span>
						</a>
					</div>
					<?php if ( $chips ) : ?>
						<?php
						$county_now = $wizard_summary['county'] ?? '';
						$body_now   = KMAE_Browse::bodies_to_query_value( isset( $wizard_summary['bodies'] ) && is_array( $wizard_summary['bodies'] ) ? $wizard_summary['bodies'] : array() );
						$mod_now    = $wizard_summary['mod'] ?? '';
						$kp_min_now = isset( $wizard_summary['kp_min'] ) ? (int) $wizard_summary['kp_min'] : 0;
						$kp_max_now = isset( $wizard_summary['kp_max'] ) ? (int) $wizard_summary['kp_max'] : 0;
						$county_options = array( KMAE_Counties::ORSZAG => __( 'Egész ország', 'ki-mit-adna-erte' ) ) + KMAE_Counties::get_all();
						$body_options   = array( 'mind' => __( 'Minden kivitel', 'ki-mit-adna-erte' ) );
						foreach ( KMAE_Vehicle::browse_body_cards() as $body_slug => $body_info ) {
							$body_options[ $body_slug ] = $body_info['label'];
						}
						$mode_options = KMAE_Vehicle::browse_mode_labels();
						?>
						<div
							class="kmae-swipe-empty__filters"
							id="kmae-empty-filters"
							data-base="<?php echo esc_url( $browse_base_url ); ?>"
							data-county="<?php echo esc_attr( $county_now ); ?>"
							data-body="<?php echo esc_attr( $body_now ); ?>"
							data-mod="<?php echo esc_attr( $mod_now ); ?>"
							data-kp-min="<?php echo esc_attr( $kp_min_now ); ?>"
							data-kp-max="<?php echo esc_attr( $kp_max_now ); ?>"
						>
							<p><?php esc_html_e( 'Aktív szűrők:', 'ki-mit-adna-erte' ); ?></p>
							<div class="kmae-swipe-empty__chips">
								<?php
								$menus = array(
									array(
										'key'     => 'county',
										'icon'    => 'map-pin',
										'current' => $county_now,
										'options' => $county_options,
									),
									array(
										'key'     => 'body',
										'icon'    => 'car',
										'current' => $body_now,
										'options' => $body_options,
									),
									array(
										'key'     => 'mod',
										'icon'    => 'tag',
										'current' => $mod_now,
										'options' => $mode_options,
									),
								);
								foreach ( $menus as $menu ) :
									$current_label = $menu['options'][ $menu['current'] ] ?? '';
									if ( 'body' === $menu['key'] && '' === $current_label ) {
										$current_label = KMAE_Browse::bodies_label( isset( $wizard_summary['bodies'] ) && is_array( $wizard_summary['bodies'] ) ? $wizard_summary['bodies'] : array() );
									}
									?>
									<div class="kmae-swipe-empty__pick">
										<button type="button" class="kmae-swipe-empty__chip" aria-expanded="false" aria-haspopup="listbox">
											<?php echo KMAE_Icons::render( $menu['icon'], array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<span data-kmae-chip-label><?php echo esc_html( $current_label ); ?></span>
											<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</button>
										<div class="kmae-swipe-empty__menu" role="listbox" hidden>
											<?php foreach ( $menu['options'] as $option_value => $option_label ) : ?>
												<button
													type="button"
													class="kmae-swipe-empty__option<?php echo (string) $option_value === (string) $menu['current'] ? ' is-current' : ''; ?>"
													role="option"
													data-kmae-filter="<?php echo esc_attr( $menu['key'] ); ?>"
													data-value="<?php echo esc_attr( $option_value ); ?>"
													aria-selected="<?php echo (string) $option_value === (string) $menu['current'] ? 'true' : 'false'; ?>"
												><?php echo esc_html( $option_label ); ?></button>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="kmae-swipe-empty__cash" id="kmae-empty-cash" hidden>
								<label>
									<span><?php esc_html_e( 'Minimum (Ft)', 'ki-mit-adna-erte' ); ?></span>
									<input type="text" inputmode="numeric" id="kmae-empty-kp-min" autocomplete="off" value="<?php echo $kp_min_now ? esc_attr( number_format( $kp_min_now, 0, ',', ' ' ) ) : ''; ?>" />
								</label>
								<label>
									<span><?php esc_html_e( 'Maximum (Ft)', 'ki-mit-adna-erte' ); ?></span>
									<input type="text" inputmode="numeric" id="kmae-empty-kp-max" autocomplete="off" value="<?php echo $kp_max_now ? esc_attr( number_format( $kp_max_now, 0, ',', ' ' ) ) : ''; ?>" />
								</label>
							</div>
							<a class="kmae-swipe-empty__btn kmae-swipe-empty__btn--go" id="kmae-empty-search" href="<?php echo esc_url( $browse_base_url ); ?>" hidden><?php esc_html_e( 'Új keresés indítása', 'ki-mit-adna-erte' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php elseif ( $list_view ) : ?>
		<?php include KMAE_PLUGIN_DIR . 'templates/partials/browse-list.php'; ?>
	<?php elseif ( $sw2 ) : ?>
		<?php include KMAE_PLUGIN_DIR . 'templates/browse-swipe-v2.php'; ?>
	<?php else : ?>
		<div class="kmae-swipe-stack" id="kmae-swipe-stack">
			<?php foreach ( $listings as $i => $listing ) : ?>
				<?php
				$gallery_ids = KMAE_Query::get_gallery_ids( $listing->ID );
				$photo_urls  = array();
				foreach ( $gallery_ids as $aid ) {
					/* Kártyához az 1400 px-es változat elég (az 1920-as eredeti kb. kétszer akkora);
					   régi képnél a get_url a `large` / kisebb méretre esik vissza. */
					$url = KMAE_Images::get_url( (int) $aid, 'kmae_gallery' );
					if ( $url ) {
						$photo_urls[] = $url;
					}
				}
				if ( ! $photo_urls ) {
					$thumb = KMAE_Query::get_listing_thumbnail_url( $listing->ID, 'kmae_gallery' );
					if ( $thumb ) {
						$photo_urls[] = $thumb;
					}
				}
				$photo_count = count( $photo_urls );
				$types       = KMAE_Meta::get_offer_types( $listing->ID );
				$labels      = array_map( fn( $t ) => $offer_labels[ $t ] ?? $t, $types );
				$loc         = KMAE_Counties::format_listing_location( $listing->ID );
				$subtitle    = KMAE_Vehicle::get_swipe_subtitle( $listing->ID );
				$value       = KMAE_Vehicle::get_vehicle_value( $listing->ID );
				$is_favorite = in_array( (int) $listing->ID, $favorite_ids, true );
				$listing_url = get_permalink( $listing->ID );
				?>
				<div class="albira-card-shell">
					<span class="albira-corner albira-corner--blue" aria-hidden="true"></span>
					<span class="albira-corner albira-corner--orange" aria-hidden="true"></span>
				<div class="kmae-swipe-card albira-car-card<?php echo 0 === $i ? ' is-current' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>" data-listing-id="<?php echo esc_attr( $listing->ID ); ?>" data-favorite="<?php echo $is_favorite ? '1' : '0'; ?>">
					<div class="kmae-swipe-card__glass">
					<div class="kmae-swipe-card-photo">
						<a class="kmae-swipe-open" href="<?php echo esc_url( $listing_url ); ?>">
							<?php if ( $photo_urls ) : ?>
								<img src="<?php echo esc_url( $photo_urls[0] ); ?>" alt="" class="kmae-card-image" data-photos="<?php echo esc_attr( wp_json_encode( $photo_urls ) ); ?>" data-photo-index="0" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>" decoding="async" />
							<?php else : ?>
								<span class="kmae-no-photo"><?php esc_html_e( 'Nincs kép', 'ki-mit-adna-erte' ); ?></span>
							<?php endif; ?>
						</a>
						<?php if ( $photo_count > 1 ) : ?>
							<button type="button" class="kmae-swipe-photo-nav kmae-swipe-photo-prev" hidden aria-label="<?php esc_attr_e( 'Előző kép', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'arrow-left', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							<button type="button" class="kmae-swipe-photo-nav kmae-swipe-photo-next" aria-label="<?php esc_attr_e( 'Következő kép', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<?php endif; ?>
						<?php if ( $photo_count > 0 ) : ?>
							<span class="kmae-swipe-photo-count" aria-hidden="true">1 / <?php echo esc_html( (string) $photo_count ); ?></span>
						<?php endif; ?>
						<?php if ( $photo_count > 1 ) : ?>
							<span class="kmae-swipe-photo-dots" aria-hidden="true">
								<?php for ( $dot = 0; $dot < $photo_count; $dot++ ) : ?>
									<span class="kmae-swipe-photo-dot<?php echo 0 === $dot ? ' is-current' : ''; ?>"></span>
								<?php endfor; ?>
							</span>
						<?php endif; ?>
						<?php
						$brand_name = trim( (string) get_post_meta( $listing->ID, KMAE_Vehicle::META_BRAND, true ) );
						$brand_logo = '';
						$brand_other = in_array( mb_strtolower( $brand_name ), array( 'egyéb', 'egyeb', 'other' ), true );
						if ( '' !== $brand_name && ! $brand_other ) {
							foreach ( KMAE_Vehicle::brand_logos() as $known_name => $known_url ) {
								if ( 0 === strcasecmp( $known_name, $brand_name ) ) {
									$brand_logo = $known_url;
									break;
								}
							}
						}
						?>
						<?php if ( $brand_logo ) : ?>
							<span class="kmae-swipe-brand">
								<img src="<?php echo esc_url( $brand_logo ); ?>" alt="" width="44" height="44" onerror="this.parentNode.remove()" />
							</span>
						<?php endif; ?>
						<?php if ( isset( $reserved_ids[ (int) $listing->ID ] ) ) : ?>
							<span class="kmae-swipe-reserved" title="<?php esc_attr_e( 'Az eladó elfogadott egy ajánlatot, most nem lehet új ajánlatot tenni.', 'ki-mit-adna-erte' ); ?>"><?php esc_html_e( 'Foglalt', 'ki-mit-adna-erte' ); ?></span>
						<?php endif; ?>
						<button type="button" class="kmae-swipe-like<?php echo $is_favorite ? ' is-saved' : ''; ?>" title="<?php esc_attr_e( 'Mentés a kedvencekhez', 'ki-mit-adna-erte' ); ?>" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</div>
					<div class="kmae-swipe-card-info">
						<?php
						$spec_row   = KMAE_Vehicle::get_specs( $listing->ID );
						$swipe_name = static function ( $raw ) {
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
						$card_title = trim( $swipe_name( $spec_row['brand'] ) . ' ' . $swipe_name( $spec_row['model'] ) );
						if ( '' === $card_title ) {
							$card_title = preg_replace( '/^\s*elad[oó]\s+/iu', '', (string) $listing->post_title );
							$pieces     = preg_split( '/\s+[–\-|]\s+/u', $card_title );
							$card_title = trim( (string) ( $pieces[0] ?? $card_title ) );
						}
						?>
						<div class="kmae-swipe-card-head">
							<div class="kmae-swipe-card-title">
								<h3><?php echo esc_html( $card_title ); ?></h3>
								<?php
								$hp     = KMAE_Vehicle::kw_to_horsepower( (int) $spec_row['power_kw'] );
								$cc     = (int) $spec_row['engine_cc'];
								$liters = $cc > 0 ? number_format( $cc / 1000, 1, ',', '' ) : '';
								?>
								<?php if ( $hp || $cc ) : ?>
									<span class="kmae-swipe-card-title__dash" aria-hidden="true">–</span>
									<p class="kmae-swipe-card-lead">
										<?php if ( $hp ) : ?>
											<span class="kmae-swipe-card-lead__bit" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%d lóerő', 'ki-mit-adna-erte' ), $hp ) ); ?>">
												<?php echo KMAE_Vehicle_Icons::render( 'power', '', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<span aria-hidden="true"><?php echo esc_html( number_format( $hp, 0, ',', ' ' ) ); ?></span>
												<span class="kmae-swipe-card-lead__unit" aria-hidden="true"><?php esc_html_e( 'le', 'ki-mit-adna-erte' ); ?></span>
											</span>
										<?php endif; ?>
										<?php if ( $hp && $liters ) : ?>
											<span class="kmae-swipe-card-lead__sep" aria-hidden="true">·</span>
										<?php endif; ?>
										<?php if ( $liters ) : ?>
											<span class="kmae-swipe-card-lead__bit" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%1$s liter, %2$s köbcentiméter', 'ki-mit-adna-erte' ), $liters, number_format( $cc, 0, ',', ' ' ) ) ); ?>">
												<?php echo KMAE_Vehicle_Icons::render( 'engine', '', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<span aria-hidden="true"><?php echo esc_html( $liters ); ?></span>
												<span class="kmae-swipe-card-lead__unit" aria-hidden="true"><?php esc_html_e( 'cm³', 'ki-mit-adna-erte' ); ?></span>
											</span>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</div>
							<?php $price_row = KMAE_Seller::get_public_price_row( $listing->ID ); ?>
							<?php if ( $price_row ) : ?>
								<p class="kmae-swipe-value kmae-swipe-value--<?php echo esc_attr( $price_row['mode'] ); ?>">
									<?php if ( KMAE_Seller::PRICE_GUIDE === $price_row['mode'] ) : ?>
										<span class="kmae-swipe-value__label"><?php echo esc_html( $price_row['label'] ); ?></span>
									<?php endif; ?>
									<?php echo esc_html( $price_row['formatted'] ); ?>
								</p>
							<?php elseif ( $value ) : ?>
								<p class="kmae-swipe-value"><?php echo esc_html( KMAE_Vehicle::format_value( $value ) ); ?></p>
							<?php else : ?>
								<p class="kmae-swipe-value kmae-swipe-value--open"><?php echo KMAE_Icons::render( 'tag', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Ajánlatot vár', 'ki-mit-adna-erte' ); ?></span></p>
							<?php endif; ?>
						</div>
						<?php
						$spec_facts = array(
							array(
								'field' => 'year',
								'value' => ! empty( $spec_row['year'] ) ? (string) $spec_row['year'] : '',
								'label' => __( 'Évjárat', 'ki-mit-adna-erte' ),
							),
							array(
								'field' => 'mileage',
								'value' => KMAE_Vehicle::format_mileage( $spec_row['mileage'] ),
								'label' => __( 'Futott km', 'ki-mit-adna-erte' ),
							),
							array(
								'field' => 'fuel',
								'code'  => (string) $spec_row['fuel'],
								'value' => KMAE_Vehicle::fuel_labels()[ $spec_row['fuel'] ] ?? '',
								'label' => __( 'Üzemanyag', 'ki-mit-adna-erte' ),
							),
							array(
								'field' => 'transmission',
								'code'  => (string) $spec_row['transmission'],
								'value' => KMAE_Vehicle::transmission_labels()[ $spec_row['transmission'] ] ?? '',
								'label' => __( 'Váltó', 'ki-mit-adna-erte' ),
							),
						);
						$spec_facts = array_values(
							array_filter(
								$spec_facts,
								static function ( $fact ) {
									return '' !== $fact['value'];
								}
							)
						);
						?>
						<?php if ( $spec_facts ) : ?>
							<div class="kmae-swipe-facts">
								<?php foreach ( $spec_facts as $fact ) : ?>
									<div class="kmae-swipe-fact">
										<span class="kmae-swipe-fact__icon" aria-hidden="true"><?php echo KMAE_Vehicle_Icons::render( $fact['field'], $fact['code'] ?? '', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="kmae-swipe-fact__copy">
											<strong><?php echo esc_html( $fact['value'] ); ?></strong>
											<small><?php echo esc_html( $fact['label'] ); ?></small>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( $loc || $labels ) : ?>
							<div class="kmae-swipe-chips">
								<?php if ( $loc ) : ?>
									<span class="kmae-swipe-location"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $loc ); ?></span><?php $loc_km = KMAE_Browse::distance_for( $listing->ID ); ?><?php if ( null !== $loc_km ) : ?><em class="kmae-swipe-location__km"><?php echo esc_html( '~' . number_format_i18n( max( 1, round( $loc_km ) ) ) . ' km' ); ?></em><?php endif; ?></span>
								<?php endif; ?>
								<?php foreach ( $types as $type_slug ) : ?>
									<?php
									$type_label = $offer_labels[ $type_slug ] ?? $type_slug;
									$type_icon  = KMAE_OFFER_TYPE_CASH === $type_slug ? 'banknote' : 'handshake';
									?>
									<span class="kmae-type-tag<?php echo KMAE_OFFER_TYPE_CASH === $type_slug ? ' kmae-type-tag--cash' : ''; ?>"><?php echo KMAE_Icons::render( $type_icon, array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $type_label ); ?></span></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<div class="kmae-swipe-actions">
							<a href="<?php echo esc_url( $listing_url ); ?>" class="kmae-btn kmae-btn-primary kmae-swipe-cta"><?php esc_html_e( 'Részletek', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">→</span></a>
						</div>
					</div>
					</div>
				</div>
				<div class="kmae-swipe-keys" role="note" aria-label="<?php esc_attr_e( 'Billentyűparancsok a böngészéshez', 'ki-mit-adna-erte' ); ?>">
					<div class="kmae-swipe-keys__inner">
						<div class="kmae-swipe-keys__segment">
							<div class="kmae-swipe-keys__keys" aria-hidden="true">
								<kbd><?php echo KMAE_Icons::render( 'arrow-left', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></kbd>
								<kbd><?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></kbd>
							</div>
							<div class="kmae-swipe-keys__copy">
								<strong class="kmae-swipe-keys__title"><?php esc_html_e( 'Böngészés', 'ki-mit-adna-erte' ); ?></strong>
							</div>
						</div>
						<div class="kmae-swipe-keys__segment">
							<div class="kmae-swipe-keys__keys" aria-hidden="true">
								<kbd class="kmae-swipe-keys__kbd-wide">Enter</kbd>
							</div>
							<div class="kmae-swipe-keys__copy">
								<strong class="kmae-swipe-keys__title"><?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?></strong>
							</div>
						</div>
						<?php if ( $view_browse ) : ?>
						<div class="kmae-swipe-keys__segment">
							<div class="kmae-swipe-keys__keys" aria-hidden="true">
								<kbd class="kmae-swipe-keys__kbd-wide">Space</kbd>
							</div>
							<div class="kmae-swipe-keys__copy">
								<strong class="kmae-swipe-keys__title"><?php esc_html_e( 'Mentés', 'ki-mit-adna-erte' ); ?></strong>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
				</div>
			<?php endforeach; ?>
			<button type="button" class="kmae-swipe-btn kmae-swipe-prev" id="kmae-swipe-prev" hidden title="<?php esc_attr_e( 'Előző', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'arrow-left', array( 'size' => 34 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<button type="button" class="kmae-swipe-btn kmae-swipe-next" id="kmae-swipe-next" title="<?php esc_attr_e( 'Következő', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 34 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
	<?php endif; ?>
	<?php if ( ! KMAE_Vehicle::is_vehicle_launch() ) : ?>
	<div class="kmae-wizard-summary">
		<div class="kmae-wizard-summary-tags">
			<?php if ( $is_vehicle_wizard ) : ?>
				<?php
				$modes = KMAE_Vehicle::browse_mode_labels();
				if ( ! empty( $wizard_summary['county'] ) ) :
					?>
					<span class="kmae-summary-tag"><?php echo esc_html( KMAE_Counties::get_label( $wizard_summary['county'] ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $wizard_summary['bodies'] ) ) : ?>
					<span class="kmae-summary-tag"><?php echo esc_html( KMAE_Browse::bodies_label( $wizard_summary['bodies'] ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $wizard_summary['mod'] ) ) : ?>
					<span class="kmae-summary-tag kmae-summary-tag--accent"><?php echo esc_html( $modes[ $wizard_summary['mod'] ] ?? $wizard_summary['mod'] ); ?></span>
				<?php endif; ?>
			<?php else : ?>
				<?php
				$county_slug = $wizard_summary['county'] ?? '';
				$type_slug   = $wizard_summary['type'] ?? 'mind';
				$type_label  = 'mind' === $type_slug ? __( 'Mind', 'ki-mit-adna-erte' ) : ( $offer_labels[ $type_slug ] ?? $type_slug );
				?>
				<span class="kmae-summary-tag"><?php echo esc_html( KMAE_Counties::get_label( $county_slug ) ); ?></span>
				<span class="kmae-summary-tag"><?php echo esc_html( $type_label ); ?></span>
			<?php endif; ?>
		</div>
		<a href="<?php echo esc_url( $restart_url ); ?>" class="kmae-btn kmae-btn-secondary kmae-btn-sm kmae-wizard-summary__restart"><?php esc_html_e( 'Új keresés', 'ki-mit-adna-erte' ); ?></a>
	</div>
	<?php endif; ?>
</div>
