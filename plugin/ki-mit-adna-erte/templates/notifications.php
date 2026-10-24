<?php
/**
 * Értesítések: kereskedői megkeresések.
 * - Cég: a magánszemélyektől érkezett autó-megkeresések (válaszolható kártyák).
 * - Magánszemély eladó: hirdetésenként az elküldött megkeresések összesítése (csak számok, nincs cégnév).
 *
 * Adat: KMAE_Dealer_Leads (list_for_dealer / list_for_seller). A HTML-be user- vagy
 * megkeresés-azonosító nem kerül, csak a token (a cég saját kártyáján).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kmae_uid       = get_current_user_id();
/* Élő frissítés: az ujjlenyomat a lekérdezések ELŐTT készül (lásd KMAE_Live_Page 'notifications'). */
$kmae_live_fp   = KMAE_Live_Page::get_fingerprint( 'notifications' );
$kmae_now_ts    = time();
$kmae_hide_days = 14;

$kmae_is_company = KMAE_Auth::is_company( $kmae_uid );
$kmae_is_private = KMAE_Auth::is_private_person( $kmae_uid );

$kmae_dealer_all  = KMAE_Dealer_Leads::list_for_dealer( $kmae_uid, 200 );
$kmae_seller_all  = KMAE_Dealer_Leads::list_for_seller( $kmae_uid );
$kmae_show_dealer = $kmae_is_company || ! empty( $kmae_dealer_all );
$kmae_show_seller = $kmae_is_private || ! empty( $kmae_seller_all );

/* Idő: az adatbázisban UTC, a kijelzés a WP helyi ideje szerint. */
$kmae_utc_ts = static function ( $utc ) {
	$utc = (string) $utc;
	return '' === $utc ? 0 : (int) strtotime( $utc . ' UTC' );
};

/* Megjelenített állapot: a le nem járt-jelölt, de már lejárt „pending” = lejárt. */
$kmae_effective = static function ( array $lead ) use ( $kmae_now_ts, $kmae_utc_ts ) {
	if ( KMAE_Dealer_Leads::STATUS_PENDING === $lead['status'] && $kmae_utc_ts( $lead['expires_at'] ) <= $kmae_now_ts ) {
		return KMAE_Dealer_Leads::STATUS_EXPIRED;
	}
	return $lead['status'];
};

/* ?kmae_lead=<token>: csak a saját megkeresés emelődik ki; idegen token → „nem érhető el”. */
$kmae_focus_token = '';
$kmae_forbidden   = false;
if ( isset( $_GET['kmae_lead'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$kmae_req_token = sanitize_text_field( wp_unslash( $_GET['kmae_lead'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$kmae_req_lead  = KMAE_Dealer_Leads::is_token( $kmae_req_token ) ? KMAE_Dealer_Leads::get_by_token( $kmae_req_token ) : null;
	if ( $kmae_req_lead && (int) $kmae_req_lead['dealer_id'] === $kmae_uid ) {
		$kmae_focus_token = $kmae_req_token;
	} else {
		$kmae_forbidden = true;
	}
}

/* Cég: a lezárt (nem kért / lejárt / visszavont) megkeresések 14 nap után eltűnnek a nézetből (az előzmény marad az adatbázisban). */
$kmae_dealer_leads = array();
foreach ( $kmae_dealer_all as $kmae_lead ) {
	$eff = $kmae_effective( $kmae_lead );
	if ( $kmae_lead['token'] !== $kmae_focus_token && in_array( $eff, array( 'declined', 'expired', 'cancelled' ), true ) ) {
		if ( 'expired' === $eff ) {
			$ref = $kmae_utc_ts( $kmae_lead['expires_at'] );
		} elseif ( 'declined' === $eff ) {
			$ref = $kmae_utc_ts( $kmae_lead['responded_at'] ? $kmae_lead['responded_at'] : $kmae_lead['updated_at'] );
		} else {
			$ref = $kmae_utc_ts( $kmae_lead['updated_at'] );
		}
		if ( $ref && ( $kmae_now_ts - $ref ) > $kmae_hide_days * DAY_IN_SECONDS ) {
			continue;
		}
	}
	$kmae_lead['eff']    = $eff;
	$kmae_dealer_leads[] = $kmae_lead;
}

$kmae_pending_count = 0;
foreach ( $kmae_dealer_leads as $kmae_lead ) {
	if ( KMAE_Dealer_Leads::STATUS_PENDING === $kmae_lead['eff'] ) {
		++$kmae_pending_count;
	}
}

/* Visszajelzés (az eredményt az admin-post válasz-kezelő a query argumentumokkal hozza vissza). */
$kmae_flash_ok_map  = array(
	'lead_declined' => __( 'Rendben, jeleztük, hogy most nem kéritek.', 'ki-mit-adna-erte' ),
);
$kmae_flash_err_map = array(
	'lead_forbidden'   => __( 'Ez a megkeresés nem érhető el.', 'ki-mit-adna-erte' ),
	'lead_expired'     => __( 'Ez a megkeresés már lejárt.', 'ki-mit-adna-erte' ),
	'lead_answered'    => __( 'Erre a megkeresésre már válaszoltatok.', 'ki-mit-adna-erte' ),
	'lead_unavailable' => __( 'Ez az autó már nem elérhető.', 'ki-mit-adna-erte' ),
	'lead_failed'      => __( 'Valami hiba történt, próbáld újra.', 'ki-mit-adna-erte' ),
	'lead_unverified'  => __( 'Előbb erősítsd meg az e-mail címedet, utána tudsz válaszolni.', 'ki-mit-adna-erte' ),
);
$kmae_success_key = isset( $_GET['kmae_success'] ) ? sanitize_key( wp_unslash( $_GET['kmae_success'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$kmae_error_key   = isset( $_GET['kmae_error'] ) ? sanitize_key( wp_unslash( $_GET['kmae_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$kmae_flash_ok    = isset( $kmae_flash_ok_map[ $kmae_success_key ] ) ? $kmae_flash_ok_map[ $kmae_success_key ] : '';
$kmae_flash_err   = isset( $kmae_flash_err_map[ $kmae_error_key ] ) ? $kmae_flash_err_map[ $kmae_error_key ] : '';
if ( '' === $kmae_flash_err && $kmae_forbidden ) {
	$kmae_flash_err = $kmae_flash_err_map['lead_forbidden'];
}

$kmae_purpose_label = static function ( $purpose ) {
	if ( KMAE_Dealer_Leads::PURPOSE_BOTH === $purpose ) {
		return __( 'Eladná vagy beszámíttatná', 'ki-mit-adna-erte' );
	}
	return KMAE_Dealer_Leads::PURPOSE_TRADEIN === $purpose
		? __( 'Beszámíttatná', 'ki-mit-adna-erte' )
		: __( 'Eladná', 'ki-mit-adna-erte' );
};

$kmae_has_any = ( $kmae_show_dealer && ! empty( $kmae_dealer_leads ) ) || ( $kmae_show_seller && ! empty( $kmae_seller_all ) );
?>
<div class="kmae-notif" data-kmae-live-page="notifications" data-kmae-live-fingerprint="<?php echo esc_attr( $kmae_live_fp ); ?>">
	<header class="kmae-notif__hero">
		<span class="kmae-notif__hero-icon" aria-hidden="true">
			<?php echo KMAE_Icons::render( 'bell', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
		<div class="kmae-notif__hero-text">
			<span class="kmae-notif__eyebrow"><?php esc_html_e( 'Megkeresések', 'ki-mit-adna-erte' ); ?></span>
			<h1 class="kmae-notif__title"><?php esc_html_e( 'Értesítések', 'ki-mit-adna-erte' ); ?></h1>
		</div>
		<?php if ( $kmae_show_dealer && ! empty( $kmae_dealer_leads ) ) : ?>
			<div class="kmae-notif__stats">
				<span class="kmae-notif__stat<?php echo $kmae_pending_count ? ' is-active' : ''; ?>">
					<strong><?php echo esc_html( number_format_i18n( $kmae_pending_count ) ); ?></strong>
					<span><?php esc_html_e( 'válaszra vár', 'ki-mit-adna-erte' ); ?></span>
				</span>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( '' !== $kmae_flash_ok ) : ?>
		<p class="kmae-notif__flash" role="status"><?php echo esc_html( $kmae_flash_ok ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $kmae_flash_err ) : ?>
		<p class="kmae-notif__flash is-error" role="alert"><?php echo esc_html( $kmae_flash_err ); ?></p>
	<?php endif; ?>

	<?php if ( ! $kmae_has_any && ! $kmae_show_dealer && ! $kmae_show_seller ) : ?>
		<div class="kmae-notif__empty">
			<span class="kmae-notif__empty-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'bell', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p class="kmae-notif__empty-text"><?php esc_html_e( 'Nincs új értesítésed.', 'ki-mit-adna-erte' ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ================= Cég: beérkezett autó-megkeresések ================= */ ?>
	<?php if ( $kmae_show_dealer ) : ?>
		<section class="kmae-notif__section kmae-notif__section--dealer" aria-labelledby="kmae-notif-dealer-title">
			<h2 class="kmae-notif__section-title" id="kmae-notif-dealer-title">
				<span class="kmae-notif__section-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'car', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Autó-megkeresések', 'ki-mit-adna-erte' ); ?>
			</h2>

			<?php if ( empty( $kmae_dealer_leads ) ) : ?>
				<p class="kmae-notif__empty-line"><?php esc_html_e( 'Itt jelennek meg a magánszemélyektől érkező autó-megkeresések.', 'ki-mit-adna-erte' ); ?></p>
			<?php else : ?>
				<ul class="kmae-notif__list">
					<?php foreach ( $kmae_dealer_leads as $kmae_lead ) : ?>
						<?php
						$eff        = $kmae_lead['eff'];
						$token      = $kmae_lead['token'];
						$listing_id = (int) $kmae_lead['listing_id'];
						$post       = $listing_id ? get_post( $listing_id ) : null;
						$exists     = $post && 'kmae_listing' === $post->post_type && 'publish' === $post->post_status;
						$is_public  = $exists && KMAE_Meta::STATUS_ACTIVE === KMAE_Meta::get_status( $listing_id );
						$title      = '' !== $kmae_lead['listing_title'] ? $kmae_lead['listing_title'] : ( $exists ? get_the_title( $listing_id ) : __( 'Autó', 'ki-mit-adna-erte' ) );
						$facts      = $exists ? KMAE_Vehicle::get_swipe_subtitle( $listing_id ) : '';
						$thumb      = '';
						if ( $exists ) {
							$gallery_ids = KMAE_Query::get_gallery_ids( $listing_id );
							$thumb_id    = ! empty( $gallery_ids ) ? (int) $gallery_ids[0] : (int) get_post_thumbnail_id( $listing_id );
							$thumb       = $thumb_id ? KMAE_Images::get_url( $thumb_id, 'medium_large' ) : '';
						}
						$distance   = null !== $kmae_lead['distance_km'] && $kmae_lead['distance_km'] > 0 ? max( 1, (int) round( $kmae_lead['distance_km'] ) ) : 0;
						$price      = isset( $kmae_lead['dealer_price'] ) ? (int) $kmae_lead['dealer_price'] : 0;
						$is_tradein = KMAE_Dealer_Leads::PURPOSE_TRADEIN === $kmae_lead['purpose'];
						$is_focus   = '' !== $kmae_focus_token && $token === $kmae_focus_token;

						$left_ts = $kmae_utc_ts( $kmae_lead['expires_at'] ) - $kmae_now_ts;
						if ( $left_ts >= DAY_IN_SECONDS ) {
							/* translators: %d: days left to answer */
							$left_text = sprintf( __( 'Még %d napig válaszolhattok', 'ki-mit-adna-erte' ), (int) ceil( $left_ts / DAY_IN_SECONDS ) );
						} else {
							/* translators: %d: hours left to answer */
							$left_text = sprintf( __( 'Még %d óráig válaszolhattok', 'ki-mit-adna-erte' ), max( 1, (int) ceil( $left_ts / HOUR_IN_SECONDS ) ) );
						}

						$status_label = array(
							'pending'    => __( 'Válaszra vár', 'ki-mit-adna-erte' ),
							'interested' => __( 'Érdeklődtetek', 'ki-mit-adna-erte' ),
							'declined'   => __( 'Nem kértétek', 'ki-mit-adna-erte' ),
							'expired'    => __( 'Lejárt', 'ki-mit-adna-erte' ),
							'cancelled'  => __( 'Visszavonva', 'ki-mit-adna-erte' ),
						);
						$chat_url = ! empty( $kmae_lead['conversation_id'] )
							? KMAE_Pages::conversation_thread_url( $kmae_lead['conversation_id'] )
							: KMAE_Pages::messages_page_url();
						?>
						<li class="kmae-notif-card kmae-notif-card--<?php echo esc_attr( $eff ); ?><?php echo $is_focus ? ' is-focus' : ''; ?>" id="lead-<?php echo esc_attr( $token ); ?>"<?php echo $is_focus ? ' data-kmae-lead-focus' : ''; ?>>
							<div class="kmae-notif-card__media">
								<?php if ( $thumb ) : ?>
									<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" decoding="async" />
								<?php else : ?>
									<span class="kmae-notif-card__nophoto" aria-hidden="true"><?php echo KMAE_Icons::render( 'car', array( 'size' => 34 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
							</div>

							<div class="kmae-notif-card__body">
								<div class="kmae-notif-card__top">
									<h3 class="kmae-notif-card__title"><?php echo esc_html( $title ); ?></h3>
									<span class="kmae-notif-card__status"><?php echo esc_html( $status_label[ $eff ] ?? '' ); ?></span>
								</div>

								<?php if ( '' !== $facts ) : ?>
									<p class="kmae-notif-card__facts"><?php echo esc_html( $facts ); ?></p>
								<?php endif; ?>

								<div class="kmae-notif-card__meta">
									<span class="kmae-notif-card__pill kmae-notif-card__pill--<?php echo $is_tradein ? 'tradein' : ( KMAE_Dealer_Leads::PURPOSE_BOTH === $kmae_lead['purpose'] ? 'both' : 'sell' ); ?>"><?php echo esc_html( $kmae_purpose_label( $kmae_lead['purpose'] ) ); ?></span>
									<?php if ( $distance ) : ?>
										<span class="kmae-notif-card__dist"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<?php
											/* translators: %d: distance in km */
											echo esc_html( sprintf( __( 'kb. %d km-re', 'ki-mit-adna-erte' ), $distance ) );
											?>
										</span>
									<?php endif; ?>
									<?php if ( $is_public ) : ?>
										<a class="kmae-notif-card__view" href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>"><?php esc_html_e( 'Hirdetés megnézése', 'ki-mit-adna-erte' ); ?><?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
									<?php endif; ?>
								</div>

								<?php if ( $price > 0 ) : ?>
									<p class="kmae-notif-card__price">
										<span><?php echo esc_html( $is_tradein ? __( 'Beszámításra ennyiért adná:', 'ki-mit-adna-erte' ) : __( 'Kereskedőnek ennyiért adná:', 'ki-mit-adna-erte' ) ); ?></span>
										<strong><?php echo esc_html( KMAE_Vehicle::format_value( $price ) ); ?></strong>
									</p>
								<?php endif; ?>

								<div class="kmae-notif-card__foot">
									<?php if ( KMAE_Dealer_Leads::STATUS_PENDING === $eff ) : ?>
										<p class="kmae-notif-card__left"><?php echo KMAE_Icons::render( 'clock', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $left_text ); ?></p>
										<?php if ( method_exists( 'KMAE_Dealer_Leads', 'answer_form_fields' ) ) : ?>
											<p class="kmae-notif-card__note"><?php esc_html_e( 'Ne utasítsátok el csak az ár vagy a képek miatt. Ha az autó érdekes, nyomjatok az Érdekel gombra, és alkudjatok a chatben.', 'ki-mit-adna-erte' ); ?></p>
											<form class="kmae-notif-card__answer" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
												<?php KMAE_Dealer_Leads::answer_form_fields( $token ); ?>
												<button type="submit" name="answer" value="interested" class="kmae-notif-btn kmae-notif-btn--yes"><?php esc_html_e( 'Érdekel', 'ki-mit-adna-erte' ); ?></button>
												<button type="submit" name="answer" value="declined" class="kmae-notif-btn kmae-notif-btn--no"><?php esc_html_e( 'Köszönöm, nem', 'ki-mit-adna-erte' ); ?></button>
											</form>
										<?php endif; ?>
									<?php elseif ( KMAE_Dealer_Leads::STATUS_INTERESTED === $eff ) : ?>
										<a class="kmae-notif-btn kmae-notif-btn--yes" href="<?php echo esc_url( $chat_url ); ?>">
											<?php echo KMAE_Icons::render( 'message-square', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<span><?php esc_html_e( 'Chat megnyitása', 'ki-mit-adna-erte' ); ?></span>
										</a>
									<?php elseif ( KMAE_Dealer_Leads::STATUS_CANCELLED === $eff ) : ?>
										<p class="kmae-notif-card__closed"><?php esc_html_e( 'Az eladó visszavonta, vagy a hirdetés megszűnt.', 'ki-mit-adna-erte' ); ?></p>
									<?php elseif ( KMAE_Dealer_Leads::STATUS_EXPIRED === $eff ) : ?>
										<p class="kmae-notif-card__closed"><?php esc_html_e( 'Ez a megkeresés lejárt.', 'ki-mit-adna-erte' ); ?></p>
									<?php else : ?>
										<p class="kmae-notif-card__closed"><?php esc_html_e( 'Ezt most nem kértétek.', 'ki-mit-adna-erte' ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php /* ================= Magánszemély: elküldött megkeresések (csak számok) ================= */ ?>
	<?php if ( $kmae_show_seller ) : ?>
		<section class="kmae-notif__section kmae-notif__section--seller" aria-labelledby="kmae-notif-seller-title">
			<h2 class="kmae-notif__section-title" id="kmae-notif-seller-title">
				<span class="kmae-notif__section-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'send', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Kereskedői megkereséseid', 'ki-mit-adna-erte' ); ?>
			</h2>

			<?php if ( empty( $kmae_seller_all ) ) : ?>
				<p class="kmae-notif__empty-line">
					<?php esc_html_e( 'A Hirdetéseim oldalon egyszerre több kereskedésnek is elküldheted az autódat.', 'ki-mit-adna-erte' ); ?>
					<a href="<?php echo esc_url( KMAE_Pages::url( 'sajat-hirdetesek' ) ); ?>"><?php esc_html_e( 'Hirdetéseim', 'ki-mit-adna-erte' ); ?></a>
				</p>
			<?php else : ?>
				<?php $kmae_dl_ids = array(); ?>
				<ul class="kmae-notif__list kmae-notif__list--seller">
					<?php foreach ( $kmae_seller_all as $kmae_group ) : ?>
						<?php
						$g_listing  = (int) $kmae_group['listing_id'];
						$g_counts   = array(
							'interested' => 0,
							'declined'   => 0,
							'pending'    => 0,
							'expired'    => 0,
							'cancelled'  => 0,
						);
						foreach ( $kmae_group['leads'] as $g_lead ) {
							$g_eff = $kmae_effective( $g_lead );
							if ( isset( $g_counts[ $g_eff ] ) ) {
								++$g_counts[ $g_eff ];
							}
						}
						$g_total  = (int) $kmae_group['counts']['total'];
						$g_latest = ! empty( $kmae_group['leads'] ) ? $kmae_group['leads'][0] : array();
						$g_price  = isset( $g_latest['dealer_price'] ) ? (int) $g_latest['dealer_price'] : 0;
						$g_active = $g_listing && true === KMAE_Dealer_Leads::can_start( $g_listing, $kmae_uid );
						if ( $g_active ) {
							$kmae_dl_ids[] = $g_listing;
						}
						$g_title  = '' !== $kmae_group['listing_title'] ? $kmae_group['listing_title'] : __( 'Autó', 'ki-mit-adna-erte' );
						$g_sent   = '' !== $kmae_group['last_sent_at'] ? get_date_from_gmt( $kmae_group['last_sent_at'], 'Y. m. d.' ) : '';
						?>
						<li class="kmae-notif-sent">
							<div class="kmae-notif-sent__head">
								<h3 class="kmae-notif-sent__title"><?php echo esc_html( $g_title ); ?></h3>
								<?php if ( '' !== $g_sent ) : ?>
									<span class="kmae-notif-sent__date">
										<?php
										/* translators: %s: date of the last send */
										echo esc_html( sprintf( __( 'Utoljára elküldve: %s', 'ki-mit-adna-erte' ), $g_sent ) );
										?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $g_price > 0 ) : ?>
								<p class="kmae-notif-sent__price"><span><?php esc_html_e( 'Kereskedői ár:', 'ki-mit-adna-erte' ); ?></span> <strong><?php echo esc_html( KMAE_Vehicle::format_value( $g_price ) ); ?></strong></p>
							<?php endif; ?>

							<p class="kmae-notif-sent__counts">
								<span class="kmae-notif-sent__count"><strong><?php echo esc_html( number_format_i18n( $g_total ) ); ?></strong> <?php esc_html_e( 'elküldve', 'ki-mit-adna-erte' ); ?></span>
								<span class="kmae-notif-sent__count is-yes<?php echo $g_counts['interested'] ? ' is-on' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $g_counts['interested'] ) ); ?></strong> <?php esc_html_e( 'érdeklődött', 'ki-mit-adna-erte' ); ?></span>
								<span class="kmae-notif-sent__count"><strong><?php echo esc_html( number_format_i18n( $g_counts['declined'] ) ); ?></strong> <?php esc_html_e( 'nem kérte', 'ki-mit-adna-erte' ); ?></span>
								<span class="kmae-notif-sent__count"><strong><?php echo esc_html( number_format_i18n( $g_counts['pending'] ) ); ?></strong> <?php esc_html_e( 'vár', 'ki-mit-adna-erte' ); ?></span>
								<span class="kmae-notif-sent__count"><strong><?php echo esc_html( number_format_i18n( $g_counts['expired'] ) ); ?></strong> <?php esc_html_e( 'lejárt', 'ki-mit-adna-erte' ); ?></span>
							</p>

							<?php if ( $g_counts['interested'] > 0 || $g_active ) : ?>
								<div class="kmae-notif-sent__actions">
									<?php if ( $g_counts['interested'] > 0 ) : ?>
										<a class="kmae-notif-btn kmae-notif-btn--yes" href="<?php echo esc_url( KMAE_Pages::messages_page_url() ); ?>">
											<?php echo KMAE_Icons::render( 'message-square', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<span><?php esc_html_e( 'Üzenetek megnyitása', 'ki-mit-adna-erte' ); ?></span>
										</a>
									<?php endif; ?>
									<?php if ( $g_active ) : ?>
										<button type="button" class="kmae-notif-btn kmae-notif-btn--more" data-kmae-dealer-leads-open="<?php echo esc_attr( $g_listing ); ?>"><?php esc_html_e( 'Újabb kereskedők keresése', 'ki-mit-adna-erte' ); ?></button>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>
<?php
if ( ! empty( $kmae_dl_ids ) ) {
	$dealer_leads_listing_ids = array_values( array_unique( $kmae_dl_ids ) );
	include KMAE_PLUGIN_DIR . 'templates/partials/dealer-leads-dialog.php';
}
?>
<?php if ( '' !== $kmae_focus_token ) : ?>
<script>
(function () {
	var el = document.querySelector('[data-kmae-lead-focus]');
	if (el && el.scrollIntoView) {
		el.scrollIntoView({ block: 'center', behavior: 'instant' });
	}
})();
</script>
<?php endif; ?>
