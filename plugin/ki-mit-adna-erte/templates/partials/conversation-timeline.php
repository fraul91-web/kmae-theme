<?php
/**
 * Chronological conversation timeline: chat messages.
 *
 * @package KMAE
 * @var array  $thread_events
 * @var int    $user_id
 * @var object $conversation
 * @var array  $thread_offers Offer rows keyed by message ID.
 * @var bool   $can_accept    Seller may accept a live offer now.
 * @var bool   $conversation_open Chat still writable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prev_date_key = '';
/* Olvasási pipa: a másik fél „olvasva” időpontjáig minden saját üzenet olvasott. */
$other_seen_at = '';
if ( isset( $conversation ) ) {
	$other_id      = (int) $conversation->buyer_id === (int) $user_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
	$other_seen_at = KMAE_Database::get_conversation_seen_at( $other_id, (int) $conversation->id );
}
/* Nap felirata: Ma / Tegnap / dátum. */
$today_key     = wp_date( 'Y-m-d' );
$yesterday_key = wp_date( 'Y-m-d', time() - DAY_IN_SECONDS );
$day_label     = static function ( $ts, $long ) use ( $today_key, $yesterday_key ) {
	$key = wp_date( 'Y-m-d', $ts );
	if ( $key === $today_key ) {
		return __( 'Ma', 'ki-mit-adna-erte' );
	}
	if ( $key === $yesterday_key ) {
		return __( 'Tegnap', 'ki-mit-adna-erte' );
	}
	return $long ? wp_date( 'Y. F j., l', $ts ) : wp_date( 'M j.', $ts );
};
?>
<div class="kmae-chat-thread kmae-conversation-timeline" id="kmae-chat-thread" role="log" aria-live="polite">
	<?php if ( empty( $thread_events ) ) : ?>
		<p class="kmae-conversation-timeline__empty"><?php esc_html_e( 'Még nincs üzenet. Írhattok szabadon.', 'ki-mit-adna-erte' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $thread_events as $event ) : ?>
		<?php
		$event_ts = isset( $event['created_at'] ) ? (string) $event['created_at'] : '';

		if ( $event_ts ) {
			$date_key = wp_date( 'Y-m-d', strtotime( $event_ts ) );
			if ( $date_key !== $prev_date_key ) {
				$prev_date_key = $date_key;
				?>
				<div class="kmae-chat-date-sep">
					<span><?php echo esc_html( $day_label( strtotime( $event_ts ), true ) ); ?></span>
				</div>
				<?php
			}
		}
		?>

		<?php if ( 'message' === $event['type'] && KMAE_Chat_Offers::is_system_message( $event['message'] ) ) : ?>
			<div class="kmae-chat-system" role="note">
				<span class="kmae-chat-system__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'info', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-chat-system__text"><?php echo esc_html( $event['message']->message_text ); ?></span>
			</div>
		<?php elseif ( 'message' === $event['type'] ) : ?>
			<?php
			$message     = $event['message'];
			$is_own      = (int) $message->sender_id === (int) $user_id;
			$is_buyer    = isset( $conversation ) && (int) $message->sender_id === (int) $conversation->buyer_id;
			$role_class  = $is_buyer ? 'kmae-chat-msg--buyer' : 'kmae-chat-msg--seller';
			$align_class = $is_own ? 'kmae-chat-msg--own' : 'kmae-chat-msg--other';
			$sender      = get_userdata( (int) $message->sender_id );
			$who         = $is_own ? __( 'Te', 'ki-mit-adna-erte' ) : ( $sender ? $sender->display_name : __( 'Másik fél', 'ki-mit-adna-erte' ) );
			$msg_ts      = strtotime( $message->created_at );
			$time_short  = $day_label( $msg_ts, false ) . ', ' . wp_date( 'H:i', $msg_ts );
			$is_read     = $is_own && '' !== $other_seen_at && (string) $message->created_at <= $other_seen_at;
			$read_label  = $is_read ? __( 'Olvasta', 'ki-mit-adna-erte' ) : __( 'Még nem olvasta', 'ki-mit-adna-erte' );
			ob_start();
			?>
			<span class="kmae-chat-msg__foot">
				<time class="kmae-chat-time" datetime="<?php echo esc_attr( mysql2date( DATE_W3C, $message->created_at, false ) ); ?>"><?php echo esc_html( $time_short ); ?></time>
				<?php if ( $is_own ) : ?>
					<span class="kmae-chat-read<?php echo $is_read ? ' is-read' : ''; ?>" role="img" title="<?php echo esc_attr( $read_label ); ?>" aria-label="<?php echo esc_attr( $read_label ); ?>"><?php echo KMAE_Icons::render( $is_read ? 'checks' : 'check', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
			</span>
			<?php
			$msg_foot = (string) ob_get_clean();
			$offer       = isset( $thread_offers[ (int) $message->id ] ) ? $thread_offers[ (int) $message->id ] : null;
			$attachment  = ! empty( $message->attachment_id ) ? (int) $message->attachment_id : 0;
			$expires_at  = ! empty( $message->image_expires_at ) ? (string) $message->image_expires_at : '';
			$now_utc     = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
			$image_url   = '';
			$file_url    = '';
			$file_name   = '';
			$attach_gone = false;
			$attach_live = $attachment && ( ! $expires_at || $expires_at > $now_utc );
			if ( $attach_live ) {
				$mime = (string) get_post_mime_type( $attachment );
				if ( 0 === strpos( $mime, 'image/' ) ) {
					$image_url = wp_get_attachment_image_url( $attachment, 'large' );
				}
				if ( ! $image_url ) {
					$file_url  = wp_get_attachment_url( $attachment );
					$file_path = get_attached_file( $attachment );
					$file_name = $file_path ? wp_basename( $file_path ) : get_the_title( $attachment );
				}
			} elseif ( $expires_at && $expires_at <= $now_utc ) {
				$attach_gone = true;
			}
			?>
			<article class="kmae-chat-msg kmae-conversation-msg <?php echo esc_attr( $role_class . ' ' . $align_class ); ?>">
				<?php if ( $is_buyer ) : ?>
					<span class="kmae-chat-avatar" title="<?php echo esc_attr( $who ); ?>" aria-hidden="true"><?php echo KMAE_Icons::render( 'user', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
				<div class="kmae-chat-msg__body"<?php echo $is_own ? ' title="' . esc_attr( $read_label ) . '"' : ''; ?>>
					<?php if ( $offer ) : ?>
						<?php
						$offer_status = (string) $offer->status;
						$offer_states = array(
							KMAE_Chat_Offers::STATUS_LIVE      => __( 'Élő', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_REPLACED  => __( 'Felülírva', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_ACCEPTED  => __( 'Elfogadva', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_CLOSED    => __( 'Lezárva', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_WITHDRAWN => __( 'Mégse kelt el', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_SOLD      => __( 'Eladva', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_RETRACTED => __( 'Visszavonva', 'ki-mit-adna-erte' ),
							KMAE_Chat_Offers::STATUS_LAPSED    => __( 'Lezárva', 'ki-mit-adna-erte' ),
						);
						?>
						<div class="kmae-chat-offer-bubble is-<?php echo esc_attr( isset( $offer_states[ $offer_status ] ) ? $offer_status : KMAE_Chat_Offers::STATUS_REPLACED ); ?>">
							<span class="kmae-chat-offer-bubble__head">
								<span class="kmae-chat-offer-bubble__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'tag', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="kmae-chat-offer-bubble__label"><?php esc_html_e( 'Ajánlat', 'ki-mit-adna-erte' ); ?></span>
								<span class="kmae-chat-offer-bubble__state"><?php echo esc_html( $offer_states[ $offer_status ] ?? $offer_states[ KMAE_Chat_Offers::STATUS_REPLACED ] ); ?></span>
							</span>
							<strong class="kmae-chat-offer-bubble__amount"><?php echo esc_html( KMAE_Seller::format_cash( (int) $offer->amount ) ); ?></strong>
							<?php echo $msg_foot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<?php if ( ! empty( $can_accept ) && KMAE_Chat_Offers::STATUS_LIVE === $offer_status ) : ?>
							<form method="post" class="kmae-chat-offer-accept">
								<?php wp_nonce_field( 'kmae_chat_offer_decision_nonce', 'kmae_chat_offer_decision_nonce' ); ?>
								<input type="hidden" name="kmae_action" value="accept_chat_offer" />
								<input type="hidden" name="kmae_offer_id" value="<?php echo esc_attr( (int) $offer->id ); ?>" />
								<button type="submit" class="kmae-chat-offer-accept__btn">
									<?php echo KMAE_Icons::render( 'check', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php esc_html_e( 'Elfogadom', 'ki-mit-adna-erte' ); ?></span>
								</button>
								<span class="kmae-chat-offer-accept__note">
									<span class="kmae-chat-offer-accept__note-title"><?php esc_html_e( 'Ha elfogadod:', 'ki-mit-adna-erte' ); ?></span>
									<span class="kmae-chat-offer-accept__note-line"><?php esc_html_e( 'a hirdetés Foglalt lesz,', 'ki-mit-adna-erte' ); ?></span>
									<span class="kmae-chat-offer-accept__note-line"><?php esc_html_e( 'új ajánlatot senki nem tehet rá,', 'ki-mit-adna-erte' ); ?></span>
									<span class="kmae-chat-offer-accept__note-line"><?php esc_html_e( 'mindenki más, aki írt vagy ajánlatot tett, üzenetet kap tőlünk.', 'ki-mit-adna-erte' ); ?></span>
								</span>
							</form>
						<?php endif; ?>
						<?php if ( $is_own && ! empty( $conversation_open ) && KMAE_Chat_Offers::STATUS_LIVE === $offer_status && (int) $offer->buyer_id === (int) $user_id ) : ?>
							<form method="post" class="kmae-chat-offer-retract">
								<?php wp_nonce_field( 'kmae_chat_offer_decision_nonce', 'kmae_chat_offer_decision_nonce' ); ?>
								<input type="hidden" name="kmae_action" value="retract_chat_offer" />
								<input type="hidden" name="kmae_offer_id" value="<?php echo esc_attr( (int) $offer->id ); ?>" />
								<button type="submit" class="kmae-chat-offer-retract__btn"><?php esc_html_e( 'Visszavonom', 'ki-mit-adna-erte' ); ?></button>
							</form>
						<?php endif; ?>
					<?php else : ?>
					<div class="kmae-chat-bubble">
						<?php if ( $image_url ) : ?>
							<button type="button" class="kmae-chat-image" data-kmae-chat-image="<?php echo esc_url( $image_url ); ?>">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'Küldött kép', 'ki-mit-adna-erte' ); ?>" />
							</button>
							<p class="kmae-chat-image__note"><?php esc_html_e( '48 órán belül megtekinthető, utána törlődik.', 'ki-mit-adna-erte' ); ?></p>
						<?php elseif ( $file_url ) : ?>
							<a class="kmae-chat-file" href="<?php echo esc_url( $file_url ); ?>" download><?php echo esc_html( $file_name ); ?></a>
							<p class="kmae-chat-image__note"><?php esc_html_e( '48 órán belül letölthető, utána törlődik.', 'ki-mit-adna-erte' ); ?></p>
						<?php elseif ( $attach_gone ) : ?>
							<p class="kmae-chat-image__gone"><?php esc_html_e( 'A csatolmány 48 óra után törölve lett.', 'ki-mit-adna-erte' ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $message->message_text ) ) : ?>
							<span class="kmae-chat-bubble__text"><?php echo nl2br( esc_html( $message->message_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php echo $msg_foot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
