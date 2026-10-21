<?php
/**
 * Single listing template.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$listing_id   = get_the_ID();
	$status       = KMAE_Meta::get_status( $listing_id );
	$location     = KMAE_Counties::format_listing_location( $listing_id );
	$condition    = get_post_meta( $listing_id, '_kmae_condition', true );
	$seller_id    = (int) get_the_author_meta( 'ID' );
	$seller_type  = KMAE_Auth::account_type_label( $seller_id );
	$is_owner     = is_user_logged_in() && $seller_id === get_current_user_id();
	$user_conversation = is_user_logged_in() && ! $is_owner
		? KMAE_Conversations::get_for_listing_buyer( $listing_id, get_current_user_id() )
		: null;
	$success      = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
	$error        = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';
	$success_messages = array(
		'report_sent' => __( 'Köszönjük, a bejelentést megkaptuk. Hamarosan átnézzük.', 'ki-mit-adna-erte' ),
	);
	$error_messages   = array(
		'report_reason_missing'  => __( 'Válaszd ki a bejelentés okát.', 'ki-mit-adna-erte' ),
		'report_details_missing' => __( 'A „Más” oknál írd le, mi történt.', 'ki-mit-adna-erte' ),
		'report_duplicate'       => __( 'Ezt már bejelentetted, hamarosan megnézzük.', 'ki-mit-adna-erte' ),
		'report_save_failed'     => __( 'A bejelentés nem menthető. Próbáld újra.', 'ki-mit-adna-erte' ),
		'forbidden'              => __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ),
	);
	$live_attrs = '';
	if ( is_user_logged_in() && ( $is_owner || $user_conversation ) ) {
		$live_attrs = sprintf(
			' data-kmae-live-page="single_listing" data-kmae-listing-id="%1$d" data-kmae-live-fingerprint="%2$s"',
			$listing_id,
			esc_attr( KMAE_Live_Page::get_fingerprint( 'single_listing', array( 'listing_id' => $listing_id ) ) )
		);
	}
	?>
	<article class="kmae-single-listing kmae-listing-sheet"<?php echo $live_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

		<?php
		$price_row      = KMAE_Seller::get_public_price_row( $listing_id );
		$can_contact    = ! $is_owner && KMAE_Meta::STATUS_ACTIVE === $status;
		$seller_user    = get_userdata( $seller_id );
		$seller_name    = $seller_user ? trim( (string) $seller_user->display_name ) : '';
		if ( '' === $seller_name || is_email( $seller_name ) ) {
			$seller_name = KMAE_Auth::first_name( $seller_id );
		}
		if ( '' === $seller_name ) {
			$seller_name = __( 'Eladó', 'ki-mit-adna-erte' );
		}
		$sheet_specs    = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::get_specs( $listing_id ) : array();
		$back_url       = KMAE_Back::listing_back_url();
		?>

		<div class="kmae-listing-sheet__grid">
			<div class="kmae-listing-sheet__media">
				<div class="kmae-listing-sheet__photo">
					<?php
					$brand_name  = isset( $sheet_specs['brand'] ) ? trim( (string) $sheet_specs['brand'] ) : '';
					$brand_logo  = '';
					$brand_other = in_array( function_exists( 'mb_strtolower' ) ? mb_strtolower( $brand_name ) : strtolower( $brand_name ), array( 'egyéb', 'egyeb', 'other' ), true );
					if ( '' !== $brand_name && ! $brand_other && class_exists( 'KMAE_Vehicle' ) ) {
						foreach ( KMAE_Vehicle::brand_logos() as $known_name => $known_url ) {
							if ( 0 === strcasecmp( $known_name, $brand_name ) ) {
								$brand_logo = $known_url;
								break;
							}
						}
					}
					?>
					<?php if ( $brand_logo ) : ?>
						<span class="kmae-listing-sheet__brand">
							<img src="<?php echo esc_url( $brand_logo ); ?>" alt="" width="44" height="44" onerror="this.parentNode.remove()" />
						</span>
					<?php endif; ?>
					<?php if ( ! $price_row ) : ?>
						<span class="kmae-listing-sheet__badge"><?php esc_html_e( 'Ajánlatot vár', 'ki-mit-adna-erte' ); ?></span>
					<?php endif; ?>
					<?php include KMAE_PLUGIN_DIR . 'templates/partials/listing-gallery.php'; ?>
				</div>
				<section class="kmae-listing-sheet__copy">
					<h2 class="kmae-listing-section-title"><?php esc_html_e( 'Hirdetés szövege', 'ki-mit-adna-erte' ); ?></h2>
					<div class="kmae-single-description"><?php the_content(); ?></div>
				</section>
				<?php if ( $can_contact ) : ?>
					<div class="kmae-offer-section kmae-listing-contact" id="kmae-offer">
						<div class="kmae-listing-contact__lead">
							<span class="kmae-listing-contact__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'message-square', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div>
								<strong class="kmae-listing-contact__title"><?php esc_html_e( 'Kapcsolat az eladóval', 'ki-mit-adna-erte' ); ?></strong>
								<p class="kmae-listing-contact__hint"><?php esc_html_e( 'Kérdezz az autóval kapcsolatban, egyeztess megtekintést!', 'ki-mit-adna-erte' ); ?></p>
							</div>
						</div>
							<?php if ( ! is_user_logged_in() ) : ?>
							<a href="<?php echo esc_url( KMAE_Auth::login_url( get_permalink() ) ); ?>" class="kmae-listing-contact__go kmae-conversation-action" title="<?php esc_attr_e( 'Kapcsolat az eladóval', 'ki-mit-adna-erte' ); ?>">
								<?php echo KMAE_Icons::render( 'send', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Üzenet küldése', 'ki-mit-adna-erte' ); ?>
							</a>
							<?php elseif ( KMAE_Auth::should_gate_for_verification( get_current_user_id() ) ) : ?>
							<?php
							$gate_context = 'offer';
							include KMAE_PLUGIN_DIR . 'templates/partials/email-verify-gate.php';
							?>
							<?php elseif ( $user_conversation ) : ?>
							<a href="<?php echo esc_url( KMAE_Pages::conversation_thread_url( (int) $user_conversation->id ) ); ?>" class="kmae-listing-contact__go kmae-conversation-action" title="<?php esc_attr_e( 'Kapcsolat az eladóval', 'ki-mit-adna-erte' ); ?>">
								<?php echo KMAE_Icons::render( 'send', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Üzenet küldése', 'ki-mit-adna-erte' ); ?>
							</a>
							<?php else : ?>
							<?php
							$contact_button_label = __( 'Üzenet küldése', 'ki-mit-adna-erte' );
							include KMAE_PLUGIN_DIR . 'templates/partials/offer-forms.php';
							?>
							<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<aside class="kmae-listing-sheet__side">
				<?php
				// A szerkesztő által generált cím („Eladó {márka} {modell} – {motor} | {LE} | Műszaki {dátum}-ig”) darabolva jelenik meg; más címnél marad az eredeti.
				$title_text  = get_the_title();
				$title_parts = preg_match( '/^(Eladó)\s+(.+?)\s+–\s+(.+)$/u', $title_text, $title_match ) ? $title_match : array();
				?>
				<h1 class="kmae-listing-title">
					<?php if ( $title_parts ) : ?>
						<span class="kmae-listing-title__kicker"><?php echo esc_html( $title_parts[1] ); ?></span>
						<span class="kmae-listing-title__name"><?php echo esc_html( $title_parts[2] ); ?></span>
						<span class="kmae-listing-title__specs">
							<?php foreach ( explode( ' | ', $title_parts[3] ) as $title_spec ) : ?>
								<?php
								// Ikon + fajta a darab szövege szerint (a cím-minta: motor | LE | Műszaki …-ig, bármelyik hiányozhat).
								if ( 0 === strpos( $title_spec, 'Műszaki' ) ) {
									$title_spec_kind = 'mot';
									$title_spec_icon = KMAE_Icons::render( 'shield-check', array( 'size' => 16 ) );
								} elseif ( preg_match( '/\b(LE|kW)\b/u', $title_spec ) ) {
									$title_spec_kind = 'power';
									$title_spec_icon = KMAE_Vehicle_Icons::render( 'power', '', 18 );
								} else {
									$title_spec_kind = 'engine';
									$title_spec_icon = KMAE_Vehicle_Icons::render( 'engine', '', 18 );
								}
								?>
								<span class="kmae-listing-title__spec kmae-listing-title__spec--<?php echo esc_attr( $title_spec_kind ); ?>">
									<?php if ( $title_spec_icon ) : ?>
										<span class="kmae-listing-title__spec-icon" aria-hidden="true"><?php echo $title_spec_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
									<?php echo esc_html( $title_spec ); ?>
								</span>
							<?php endforeach; ?>
						</span>
					<?php else : ?>
						<?php the_title(); ?>
					<?php endif; ?>
				</h1>
				<div class="kmae-listing-facts">
				<?php include KMAE_PLUGIN_DIR . 'templates/partials/vehicle-specs-display.php'; ?>
				<?php
				$feature_traits  = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::get_traits( $listing_id ) : array();
				$feature_defs    = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::traits() : array();
				$feature_pledges = class_exists( 'KMAE_Seller' ) ? KMAE_Seller::get_badge_rows( $listing_id ) : array();
				$pledge_icons    = array(
					'pickup_24h'           => 'clock',
					'spare_wheels'         => 'tyre',
					'independent_mechanic' => 'wrench',
					'other'                => 'plus',
				);
				?>
				<?php if ( $feature_traits || $feature_pledges ) : ?>
					<section class="kmae-listing-features">
						<h2 class="kmae-listing-features__title"><?php esc_html_e( 'Jellemzők', 'ki-mit-adna-erte' ); ?></h2>
						<div class="kmae-listing-features__list">
							<?php foreach ( $feature_traits as $trait_code ) : ?>
								<?php if ( empty( $feature_defs[ $trait_code ] ) ) { continue; } ?>
								<span class="kmae-listing-feature kmae-listing-feature--<?php echo esc_attr( $trait_code ); ?>">
									<span class="kmae-listing-feature__icon" aria-hidden="true"><?php echo KMAE_Icons::render( $feature_defs[ $trait_code ]['icon'], array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php echo esc_html( $feature_defs[ $trait_code ]['label'] ); ?>
								</span>
							<?php endforeach; ?>
							<?php foreach ( $feature_pledges as $badge ) : ?>
								<?php
								$pledge_icon = $pledge_icons[ $badge['code'] ] ?? 'badge-check';
								?>
								<span class="kmae-listing-feature kmae-listing-feature--<?php echo esc_attr( $badge['code'] ); ?>">
									<span class="kmae-listing-feature__icon" aria-hidden="true"><?php echo KMAE_Icons::render( $pledge_icon, array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php echo esc_html( $badge['label'] ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

			<?php if ( $location ) : ?>
			<div class="kmae-listing-sheet__meta">
				<p class="kmae-listing-location">
					<span class="kmae-listing-location__icon" aria-hidden="true">
						<?php echo KMAE_Icons::render( 'crosshair', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="kmae-listing-location__text">
						<span class="kmae-listing-location__label"><?php esc_html_e( 'Helyszín', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-listing-location__value"><?php echo esc_html( $location ); ?></span>
					</span>
				</p>
			</div>
			<?php endif; ?>

			<div class="kmae-listing-person">
			<div class="kmae-listing-seller">
				<div class="kmae-listing-seller__who">
					<span class="kmae-listing-seller__name"><?php echo esc_html( $seller_name ); ?></span>
					<span class="kmae-listing-seller__type"><?php echo esc_html( $seller_type ); ?></span>
				</div>
				<span class="kmae-listing-seller__sold">
					<span class="kmae-listing-seller__sold-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'car', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-listing-seller__sold-text"><?php echo esc_html( KMAE_Chat_Offers::sold_label( KMAE_Chat_Offers::count_sold_for_seller( $seller_id ) ) ); ?></span>
				</span>
			</div>
			<?php $seller_intro = class_exists( 'KMAE_Company' ) ? KMAE_Company::get_intro( $seller_id ) : ''; ?>
			<?php if ( '' !== $seller_intro ) : ?>
				<div class="kmae-listing-seller-intro">
					<span class="kmae-listing-seller-intro__label"><?php esc_html_e( 'Bemutatkozás', 'ki-mit-adna-erte' ); ?></span>
					<div class="kmae-listing-seller-intro__text"><?php echo wp_kses_post( wpautop( esc_html( $seller_intro ) ) ); ?></div>
				</div>
			<?php endif; ?>
			</div>

			</div>

				<div class="kmae-listing-side-panel">

					<?php if ( $price_row ) : ?>
						<div class="kmae-single-price">
							<span class="kmae-single-price__copy">
								<span class="kmae-single-price__label"><?php echo esc_html( $price_row['label'] ); ?></span>
								<strong class="kmae-single-price__value"><?php echo esc_html( $price_row['formatted'] ); ?></strong>
							</span>
						</div>
					<?php endif; ?>

					<?php $sale_state = KMAE_Chat_Offers::listing_state( $listing_id ); ?>
					<?php if ( 'sold' === $sale_state ) : ?>
						<p class="kmae-single-sale-state is-sold">
							<span class="kmae-single-sale-state__title"><?php esc_html_e( 'Eladva', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-single-sale-state__note"><?php esc_html_e( 'Az autó gazdára talált, a hirdetés lezárult.', 'ki-mit-adna-erte' ); ?></span>
						</p>
					<?php elseif ( 'lapsed' === $sale_state ) : ?>
						<p class="kmae-single-sale-state is-lapsed">
							<span class="kmae-single-sale-state__title"><?php esc_html_e( 'Lezárva', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-single-sale-state__note"><?php esc_html_e( 'A foglalás után 7 napig nem történt lezárás, ezért a hirdetés archiválódott. Ez nem számít eladásnak.', 'ki-mit-adna-erte' ); ?></span>
						</p>
					<?php elseif ( 'reserved' === $sale_state ) : ?>
						<p class="kmae-single-sale-state is-reserved">
							<span class="kmae-single-sale-state__title"><?php esc_html_e( 'Foglalt', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-single-sale-state__note"><?php esc_html_e( 'Az eladó elfogadott egy ajánlatot, most nem lehet új ajánlatot tenni.', 'ki-mit-adna-erte' ); ?></span>
						</p>
					<?php elseif ( 'reopened' === $sale_state ) : ?>
						<p class="kmae-single-sale-state is-reopened">
							<span class="kmae-single-sale-state__title"><?php esc_html_e( 'Újra elérhető', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-single-sale-state__note"><?php esc_html_e( 'Az autó mégsem kelt el, várja az új ajánlatokat.', 'ki-mit-adna-erte' ); ?></span>
						</p>
					<?php endif; ?>

					<?php $offer_count = KMAE_Chat_Offers::count_live_for_listing( $listing_id ); ?>
					<?php if ( $offer_count > 0 ) : ?>
						<p class="kmae-single-offer-count">
							<span class="kmae-single-offer-count__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'tag', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-single-offer-count__text"><?php echo esc_html( KMAE_Chat_Offers::count_label( $offer_count ) ); ?></span>
						</p>
					<?php endif; ?>

					<?php if ( $success && isset( $success_messages[ $success ] ) ) : ?>
						<div class="kmae-notice kmae-notice-success"><?php echo esc_html( $success_messages[ $success ] ); ?></div>
					<?php endif; ?>

					<?php if ( $error ) : ?>
						<div class="kmae-notice kmae-notice-error"><?php echo esc_html( $error_messages[ $error ] ?? $error ); ?></div>
					<?php endif; ?>

					<?php if ( ! $is_owner ) : ?>
						<?php
						$report_context         = KMAE_Reports::CONTEXT_LISTING;
						$report_listing_id      = $listing_id;
						$report_conversation_id = 0;
						include KMAE_PLUGIN_DIR . 'templates/partials/report-form.php';
						?>
					<?php endif; ?>

			</div>
			</aside>
		</div>

		<?php
		include KMAE_PLUGIN_DIR . 'templates/partials/page-back.php';
		?>
	</article>
	<?php
endwhile;

get_footer();
