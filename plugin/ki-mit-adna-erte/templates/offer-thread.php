<?php
/**
 * Conversation thread: free chat only (text, emoji, files kept for 48 hours).
 *
 * @package KMAE
 * @var object $conversation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id         = get_current_user_id();
$listing_id      = (int) $conversation->listing_id;
$listing         = get_post( $listing_id );
$listing_title   = $listing ? KMAE_Vehicle::get_display_title( $listing_id ) : (string) ( $conversation->listing_title ?? '' );
$thumb_url       = $listing_id ? KMAE_Query::get_listing_thumbnail_url( $listing_id, 'medium' ) : '';
$success         = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
$error           = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';
$seller_id       = (int) $conversation->seller_id;
$conversation_open = empty( $conversation->status ) || KMAE_Conversations::STATUS_OPEN === $conversation->status;
$listing_deleted   = KMAE_Conversations::listing_is_deleted( $listing_id );
if ( $listing_deleted ) {
	$conversation_open = false;
}
$thread_events   = KMAE_Conversations::get_thread_events( (int) $conversation->id, $user_id );
$thread_events   = is_wp_error( $thread_events ) ? array() : $thread_events;
$thread_offers   = KMAE_Chat_Offers::get_by_message( (int) $conversation->id );
$is_seller       = (int) $conversation->seller_id === (int) $user_id;
$can_accept      = $is_seller && KMAE_Chat_Offers::can_offer( $conversation );
$accepted_offer  = KMAE_Chat_Offers::get_accepted_for_listing( $listing_id );
$deal_offer      = $accepted_offer && (int) $accepted_offer->conversation_id === (int) $conversation->id ? $accepted_offer : null;
$deal_phone      = '';
if ( $deal_offer ) {
	$deal_profile = KMAE_Auth::get_profile( $is_seller ? (int) $conversation->buyer_id : $seller_id );
	$deal_phone   = $deal_profile ? trim( (string) $deal_profile->phone ) : '';
}

$success_messages = array(
	'offer_sent'           => __( 'Az ajánlatod elküldve. Ha újat küldesz, az felülírja ezt.', 'ki-mit-adna-erte' ),
	'offer_accepted'       => __( 'Elfogadtad az ajánlatot. A többi érdeklődőt értesítettük.', 'ki-mit-adna-erte' ),
	'sale_cancelled'       => __( 'A hirdetés újra elérhető, lehet rá új ajánlatot tenni.', 'ki-mit-adna-erte' ),
	'sale_closed'          => __( 'Lezártad a hirdetést. Az eladás bekerült az eladott autóid közé.', 'ki-mit-adna-erte' ),
	'offer_retracted'      => __( 'Visszavontad az ajánlatodat. Ha szeretnéd, küldhetsz újat.', 'ki-mit-adna-erte' ),
	'report_sent'          => __( 'Köszönjük, a bejelentést megkaptuk. Hamarosan átnézzük.', 'ki-mit-adna-erte' ),
	'user_blocked'         => __( 'Letiltottad. Az üzeneteit és ajánlatait mostantól nem kapod meg.', 'ki-mit-adna-erte' ),
	'user_unblocked'       => __( 'Feloldottad a tiltást. Újra megkapod az üzeneteit.', 'ki-mit-adna-erte' ),
);
$error_messages = array(
	'invalid_message'       => __( 'Írj be üzenetet, vagy csatolj fájlt, majd próbáld újra.', 'ki-mit-adna-erte' ),
	'invalid_file'          => __( 'Ez a fájl nem tölthető fel.', 'ki-mit-adna-erte' ),
	'file_too_large'        => __( 'A fájl legfeljebb 3 MB lehet.', 'ki-mit-adna-erte' ),
	'idempotency_conflict'  => __( 'Ez a kérés már más tartalommal feldolgozásra került. Frissítsd az oldalt.', 'ki-mit-adna-erte' ),
	'forbidden'             => __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ),
	'conversation_not_open' => __( 'A beszélgetés nem aktív.', 'ki-mit-adna-erte' ),
	'invalid_offer_amount'  => __( 'Adj meg érvényes összeget (legalább 1 000 Ft).', 'ki-mit-adna-erte' ),
	'offer_closed'          => __( 'Erre a hirdetésre most nem lehet ajánlatot tenni.', 'ki-mit-adna-erte' ),
	'offer_save_failed'     => __( 'Az ajánlat nem menthető. Próbáld újra.', 'ki-mit-adna-erte' ),
	'offer_not_acceptable'  => __( 'Ez az ajánlat már nem fogadható el.', 'ki-mit-adna-erte' ),
	'sale_not_cancellable'  => __( 'Ehhez a hirdetéshez nincs elfogadott ajánlat.', 'ki-mit-adna-erte' ),
	'offer_not_retractable' => __( 'Ez az ajánlat már nem vonható vissza.', 'ki-mit-adna-erte' ),
	'report_reason_missing'  => __( 'Válaszd ki a bejelentés okát.', 'ki-mit-adna-erte' ),
	'report_details_missing' => __( 'A „Más” oknál írd le, mi történt.', 'ki-mit-adna-erte' ),
	'report_duplicate'       => __( 'Ezt már bejelentetted, hamarosan megnézzük.', 'ki-mit-adna-erte' ),
	'report_save_failed'     => __( 'A bejelentés nem menthető. Próbáld újra.', 'ki-mit-adna-erte' ),
);

$chat_reserved = $accepted_offer && $conversation_open && ! $listing_deleted;
$other_id      = $is_seller ? (int) $conversation->buyer_id : $seller_id;
$other_user    = $other_id ? get_userdata( $other_id ) : null;
$other_name    = $other_user ? (string) $other_user->display_name : __( 'a másik fél', 'ki-mit-adna-erte' );
$is_blocked    = KMAE_User_Blocks::is_blocked( $user_id, $other_id );

/* Autó adatai a fejléchez és a jobb oldali kártyához. */
$listing_url  = $listing ? get_permalink( $listing_id ) : '';
$specs        = $listing ? KMAE_Vehicle::get_specs( $listing_id ) : array();
$spec_year    = ! empty( $specs['year'] ) ? (string) $specs['year'] : '';
$spec_engine  = ! empty( $specs['engine_cc'] ) ? number_format( (int) $specs['engine_cc'], 0, ',', ' ' ) . ' cm³' : '';
$spec_km      = $listing ? KMAE_Vehicle::format_mileage( (int) ( $specs['mileage'] ?? 0 ) ) : '';
$spec_fuel    = $listing ? ( KMAE_Vehicle::fuel_labels()[ $specs['fuel'] ] ?? '' ) : '';
$spec_gear    = $listing ? ( KMAE_Vehicle::transmission_labels()[ $specs['transmission'] ] ?? '' ) : '';
$price_row    = $listing ? KMAE_Seller::get_public_price_row( $listing_id ) : null;
$listing_live = $listing && 'publish' === $listing->post_status && KMAE_Meta::STATUS_ACTIVE === KMAE_Meta::get_status( $listing_id );
if ( $chat_reserved ) {
	$car_badge = array( 'reserved', __( 'Foglalt', 'ki-mit-adna-erte' ) );
} elseif ( $listing_live ) {
	$car_badge = array( 'active', __( 'Aktív', 'ki-mit-adna-erte' ) );
} else {
	$car_badge = array( 'closed', __( 'Lezárva', 'ki-mit-adna-erte' ) );
}
$can_favorite = ! $is_seller && $listing_live;
$is_favorite  = $can_favorite && KMAE_Browse::is_favorite( $user_id, $listing_id );

if ( $listing_deleted ) {
	$status_class = 'is-listing-deleted';
	$status_text  = __( 'Hirdetés törölve', 'ki-mit-adna-erte' );
} elseif ( $chat_reserved ) {
	$status_class = 'is-reserved';
	$status_text  = $deal_offer ? __( 'Foglalt · elfogadott ajánlat', 'ki-mit-adna-erte' ) : __( 'Foglalt', 'ki-mit-adna-erte' );
} elseif ( $conversation_open ) {
	$status_class = '';
	$status_text  = __( 'Aktív beszélgetés', 'ki-mit-adna-erte' );
} else {
	$status_class = 'is-closed';
	$status_text  = __( 'Lezárt beszélgetés', 'ki-mit-adna-erte' );
}
?>
<div class="kmae-offer-thread kmae-offer-thread--room kmae-conversation-thread"
	data-kmae-live-page="conversation_thread"
	data-kmae-conversation-id="<?php echo esc_attr( (string) $conversation->id ); ?>"
	data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'conversation_thread', array( 'conversation_id' => (int) $conversation->id ) ) ); ?>">
	<div class="kmae-conversation-layout">
		<header class="kmae-chat-top">
			<a class="kmae-chat-top__back" href="<?php echo esc_url( KMAE_Pages::messages_page_url() ); ?>">
				<?php echo KMAE_Icons::render( 'chevron-left', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Vissza', 'ki-mit-adna-erte' ); ?></span>
			</a>
			<div class="kmae-chat-listing-status <?php echo esc_attr( $status_class ); ?>" role="status">
				<span class="kmae-chat-listing-status__dot" aria-hidden="true"></span>
				<span class="kmae-chat-listing-status__title"><?php echo esc_html( $status_text ); ?></span>
			</div>
			<?php if ( '' !== $deal_phone ) : ?>
				<div class="kmae-chat-top__tools">
					<a class="kmae-chat-top__tool" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $deal_phone ) ); ?>" title="<?php echo esc_attr( $deal_phone ); ?>" aria-label="<?php esc_attr_e( 'Hívás', 'ki-mit-adna-erte' ); ?>">
						<?php echo KMAE_Icons::render( 'smartphone', array( 'size' => 19 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>
			<?php endif; ?>
		</header>

		<div class="kmae-chat-shell kmae-chat-shell--<?php echo $is_seller ? 'seller' : 'buyer'; ?>">
			<?php if ( $listing_deleted || $is_blocked || ( $success && isset( $success_messages[ $success ] ) ) || $error ) : ?>
				<div class="kmae-chat-notices">
					<?php if ( $listing_deleted ) : ?>
						<div class="kmae-chat-deleted" role="note">
							<span class="kmae-chat-deleted__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'trash', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-chat-deleted__text"><?php esc_html_e( 'Az eladó törölte a hirdetést.', 'ki-mit-adna-erte' ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $success && isset( $success_messages[ $success ] ) ) : ?>
						<div class="kmae-chat-flash kmae-chat-flash--success"><?php echo esc_html( $success_messages[ $success ] ); ?></div>
					<?php endif; ?>
					<?php if ( $error ) : ?>
						<div class="kmae-chat-flash kmae-chat-flash--error"><?php echo esc_html( $error_messages[ $error ] ?? __( 'A művelet nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ) ); ?></div>
					<?php endif; ?>
					<?php if ( $is_blocked && 'user_blocked' !== $success ) : ?>
						<div class="kmae-chat-flash kmae-chat-flash--blocked"><?php esc_html_e( 'Letiltottad ezt a felhasználót: az üzeneteit és ajánlatait nem kapod meg.', 'ki-mit-adna-erte' ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php include KMAE_PLUGIN_DIR . 'templates/partials/conversation-timeline.php'; ?>
			<?php include KMAE_PLUGIN_DIR . 'templates/partials/conversation-free-chat.php'; ?>
		</div>

		<aside class="kmae-chat-side" aria-label="<?php esc_attr_e( 'A beszélgetés részletei', 'ki-mit-adna-erte' ); ?>">
			<section class="kmae-chat-car">
				<?php if ( $listing ) : ?>
					<a class="kmae-chat-car__photo" href="<?php echo esc_url( $listing_url ); ?>" target="_blank" rel="noopener">
						<?php if ( $thumb_url ) : ?>
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy" width="300" height="170" />
						<?php endif; ?>
						<span class="kmae-chat-car__badge is-<?php echo esc_attr( $car_badge[0] ); ?>"><?php echo esc_html( $car_badge[1] ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $listing_title ) : ?>
					<h3 class="kmae-chat-car__title"><?php echo esc_html( $listing_title ); ?></h3>
				<?php endif; ?>
				<?php if ( $listing ) : ?>
					<ul class="kmae-chat-car__specs">
						<?php if ( '' !== $spec_year ) : ?>
							<li>
								<span class="kmae-chat-car__icon" aria-hidden="true"><?php echo KMAE_Vehicle_Icons::render( 'year', '', 17 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="kmae-chat-car__value"><?php echo esc_html( $spec_year ); ?></span>
							</li>
						<?php endif; ?>
						<?php
						$car_rows = array(
							array( 'engine', '', $spec_engine ),
							array( 'mileage', '', $spec_km ),
							array( 'fuel', (string) ( $specs['fuel'] ?? '' ), $spec_fuel ),
							array( 'transmission', (string) ( $specs['transmission'] ?? '' ), $spec_gear ),
						);
						?>
						<?php foreach ( $car_rows as $car_row ) : ?>
							<?php if ( '' !== $car_row[2] ) : ?>
								<li>
									<span class="kmae-chat-car__icon" aria-hidden="true"><?php echo KMAE_Vehicle_Icons::render( $car_row[0], $car_row[1], 17 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="kmae-chat-car__value"><?php echo esc_html( $car_row[2] ); ?></span>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
					<?php if ( $price_row ) : ?>
						<a class="kmae-chat-car__price" href="<?php echo esc_url( $listing_url ); ?>" target="_blank" rel="noopener">
							<strong><?php echo esc_html( $price_row['formatted'] ); ?></strong>
							<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>

				<div class="kmae-chat-car__actions">
					<?php if ( $can_favorite ) : ?>
						<button type="button" class="kmae-chat-car__action kmae-chat-car__action--fav<?php echo $is_favorite ? ' is-saved' : ''; ?>"
							data-kmae-chat-favorite="<?php echo esc_attr( (string) $listing_id ); ?>"
							data-kmae-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
							data-kmae-nonce="<?php echo esc_attr( wp_create_nonce( 'kmae_favorite_add' ) ); ?>"
							data-label-add="<?php esc_attr_e( 'Hozzáadás a kedvencekhez', 'ki-mit-adna-erte' ); ?>"
							data-label-saved="<?php esc_attr_e( 'Kedvencek között', 'ki-mit-adna-erte' ); ?>"
							aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>">
							<span class="kmae-chat-car__action-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'heart', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span data-kmae-chat-favorite-label><?php echo $is_favorite ? esc_html__( 'Kedvencek között', 'ki-mit-adna-erte' ) : esc_html__( 'Hozzáadás a kedvencekhez', 'ki-mit-adna-erte' ); ?></span>
						</button>
					<?php endif; ?>

					<?php
					$report_context         = KMAE_Reports::CONTEXT_CHAT;
					$report_listing_id      = $listing_id;
					$report_conversation_id = (int) $conversation->id;
					include KMAE_PLUGIN_DIR . 'templates/partials/report-form.php';
					?>

					<?php if ( $is_blocked ) : ?>
						<form method="post" class="kmae-chat-block">
							<?php wp_nonce_field( KMAE_User_Blocks::NONCE, KMAE_User_Blocks::NONCE ); ?>
							<input type="hidden" name="kmae_action" value="unblock_user" />
							<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />
							<button type="submit" class="kmae-chat-car__action kmae-chat-car__action--block is-blocked">
								<span class="kmae-chat-car__action-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'user', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span><?php esc_html_e( 'Tiltás feloldása', 'ki-mit-adna-erte' ); ?></span>
							</button>
						</form>
					<?php else : ?>
						<button type="button" class="kmae-chat-car__action kmae-chat-car__action--block" data-kmae-report-open="kmae-block-user">
							<span class="kmae-chat-car__action-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'user', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php esc_html_e( 'Felhasználó tiltása', 'ki-mit-adna-erte' ); ?></span>
						</button>
						<dialog class="kmae-report kmae-block-dialog" id="kmae-block-user" aria-labelledby="kmae-block-user-title">
							<form method="post" class="kmae-report__form">
								<?php wp_nonce_field( KMAE_User_Blocks::NONCE, KMAE_User_Blocks::NONCE ); ?>
								<input type="hidden" name="kmae_action" value="block_user" />
								<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />
								<div class="kmae-report__head">
									<span class="kmae-report__head-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'user', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<div class="kmae-report__head-copy">
										<h2 class="kmae-report__title" id="kmae-block-user-title">
											<?php
											/* translators: %s: other user's name */
											echo esc_html( sprintf( __( 'Letiltod: %s?', 'ki-mit-adna-erte' ), $other_name ) );
											?>
										</h2>
										<p class="kmae-report__lead"><?php esc_html_e( 'Ő nem tud róla, és ugyanúgy írhat vagy ajánlatot tehet, de te ezeket nem kapod meg, és értesítést sem kapsz róluk. A tiltást bármikor feloldhatod.', 'ki-mit-adna-erte' ); ?></p>
									</div>
									<button type="button" class="kmae-report__x" data-kmae-report-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'x', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
								</div>
								<div class="kmae-report__actions">
									<button type="button" class="kmae-report__cancel" data-kmae-report-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
									<button type="submit" class="kmae-report__submit"><?php esc_html_e( 'Tiltás', 'ki-mit-adna-erte' ); ?></button>
								</div>
							</form>
						</dialog>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( $deal_offer ) : ?>
				<section class="kmae-chat-deal" aria-label="<?php esc_attr_e( 'Elfogadott ajánlat', 'ki-mit-adna-erte' ); ?>">
					<div class="kmae-chat-deal__info">
						<span class="kmae-chat-deal__amount">
							<?php
							/* translators: %s: formatted amount */
							echo esc_html( sprintf( __( 'Elfogadott ajánlat: %s', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( (int) $deal_offer->amount ) ) );
							?>
						</span>
					</div>
					<div class="kmae-chat-deal__phone">
						<span class="kmae-chat-deal__phone-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'smartphone', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="kmae-chat-deal__phone-label"><?php echo $is_seller ? esc_html__( 'A vevő telefonszáma:', 'ki-mit-adna-erte' ) : esc_html__( 'Az eladó telefonszáma:', 'ki-mit-adna-erte' ); ?></span>
						<?php if ( '' !== $deal_phone ) : ?>
							<a class="kmae-chat-deal__phone-number" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $deal_phone ) ); ?>"><?php echo esc_html( $deal_phone ); ?></a>
						<?php else : ?>
							<span class="kmae-chat-deal__phone-missing"><?php esc_html_e( 'nincs megadva — egyeztessetek itt a chatben.', 'ki-mit-adna-erte' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $is_seller ) : ?>
						<a class="kmae-chat-deal__contract" href="<?php echo esc_url( KMAE_Sale_Contract::page_url( (int) $conversation->id ) ); ?>"><?php esc_html_e( 'Adásvételi szerződés', 'ki-mit-adna-erte' ); ?></a>
						<div class="kmae-chat-deal__actions">
							<form method="post" class="kmae-chat-deal__close">
								<?php wp_nonce_field( 'kmae_chat_offer_decision_nonce', 'kmae_chat_offer_decision_nonce' ); ?>
								<input type="hidden" name="kmae_action" value="close_chat_sale" />
								<input type="hidden" name="kmae_offer_id" value="<?php echo esc_attr( (int) $deal_offer->id ); ?>" />
								<button type="submit" class="kmae-chat-deal__close-btn"><?php esc_html_e( 'Lezárom', 'ki-mit-adna-erte' ); ?></button>
							</form>
							<form method="post" class="kmae-chat-deal__cancel">
								<?php wp_nonce_field( 'kmae_chat_offer_decision_nonce', 'kmae_chat_offer_decision_nonce' ); ?>
								<input type="hidden" name="kmae_action" value="cancel_chat_sale" />
								<input type="hidden" name="kmae_offer_id" value="<?php echo esc_attr( (int) $deal_offer->id ); ?>" />
								<button type="submit" class="kmae-chat-deal__cancel-btn"><?php esc_html_e( 'Mégse kelt el', 'ki-mit-adna-erte' ); ?></button>
							</form>
						</div>
						<div class="kmae-chat-deal__guide">
							<span class="kmae-chat-deal__guide-title"><?php esc_html_e( 'Mi a következő lépés?', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-chat-deal__guide-line"><strong><?php esc_html_e( 'Lezárom:', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'az eladás megtörtént. A hirdetés lezárul, és eladásnak számít (+1 eladott autó a profilodon).', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-chat-deal__guide-line"><strong><?php esc_html_e( 'Ha nem teszel semmit:', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( '7 nap múlva a hirdetés archiválódik. Lezártként jelenik meg, de nem számít eladásnak.', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-chat-deal__guide-line"><strong><?php esc_html_e( 'Mégse kelt el:', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'a hirdetés újra elérhető lesz, és mindenkit értesítünk, aki írt vagy ajánlatot tett.', 'ki-mit-adna-erte' ); ?></span>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! $is_seller && $deal_offer ) : ?>
				<section class="kmae-chat-checklist">
					<div class="kmae-chat-checklist__body">
						<p class="kmae-chat-checklist__text"><?php esc_html_e( 'Segítek, hogy mit ellenőrizz, és észben tartsd, hol jársz.', 'ki-mit-adna-erte' ); ?></p>
						<form method="post" class="kmae-chat-checklist__form">
							<?php wp_nonce_field( KMAE_Sale_Contract::CHECKLIST_NONCE, KMAE_Sale_Contract::CHECKLIST_NONCE ); ?>
							<input type="hidden" name="kmae_action" value="download_buyer_checklist" />
							<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />
							<button type="submit" class="kmae-chat-checklist__btn"><?php esc_html_e( 'Ellenőrzőlista letöltése', 'ki-mit-adna-erte' ); ?></button>
						</form>
					</div>
				</section>

				<section class="kmae-chat-tips">
					<button type="button" class="kmae-chat-tips__btn" data-kmae-tips-image="<?php echo esc_url( KMAE_Sale_Contract::tips_image_url() ); ?>" data-kmae-tips-title="<?php esc_attr_e( 'Mire figyelj a megtekintésnél?', 'ki-mit-adna-erte' ); ?>">
						<img class="kmae-chat-tips__thumb" src="<?php echo esc_url( KMAE_Sale_Contract::tips_image_url() ); ?>" alt="" loading="lazy" width="44" height="44" />
						<span class="kmae-chat-tips__text">
							<span class="kmae-chat-tips__title"><?php esc_html_e( 'Mire figyelj?', 'ki-mit-adna-erte' ); ?></span>
							<span class="kmae-chat-tips__note"><?php esc_html_e( 'Tanácsok a megtekintéshez — kattints a nagyításhoz', 'ki-mit-adna-erte' ); ?></span>
						</span>
					</button>
				</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
