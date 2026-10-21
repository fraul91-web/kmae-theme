<?php
/**
 * Free conversation compose. Timeline renders existing messages.
 *
 * @package KMAE
 * @var object $conversation
 * @var int    $user_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $conversation || ! KMAE_Conversations::is_member( $conversation, $user_id ) ) {
	return;
}

$listing_deleted = isset( $listing_deleted )
	? (bool) $listing_deleted
	: KMAE_Conversations::listing_is_deleted( (int) $conversation->listing_id );
$compose_closed = $listing_deleted || KMAE_Conversations::STATUS_OPEN !== (string) $conversation->status;

if ( $compose_closed ) {
	?>
	<section class="kmae-free-chat kmae-free-chat--closed<?php echo $listing_deleted ? ' kmae-free-chat--listing-deleted' : ''; ?>" aria-label="<?php echo $listing_deleted ? esc_attr__( 'Hirdetés törölve lett', 'ki-mit-adna-erte' ) : esc_attr__( 'Lezárt beszélgetés', 'ki-mit-adna-erte' ); ?>">
		<?php if ( $listing_deleted ) : ?>
			<p class="kmae-free-chat__closed-title"><?php esc_html_e( 'Hirdetés törölve lett', 'ki-mit-adna-erte' ); ?></p>
			<p class="kmae-free-chat__closed-note"><?php esc_html_e( 'Sajnáljuk — az autó, ami iránt érdeklődtél, sajnos törölve lett. A beszélgetés lezárult: nincs már kinek írni.', 'ki-mit-adna-erte' ); ?></p>
		<?php else : ?>
			<p class="kmae-free-chat__closed-note"><?php esc_html_e( 'Ez a beszélgetés lezárult. Elolvashatod, mi történt, írni már nem lehet.', 'ki-mit-adna-erte' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
	return;
}

$emoji      = array( '😀', '😊', '👍', '🙏', '❤️', '🚗', '✅', '🤔' );
$can_offer  = (int) $conversation->buyer_id === (int) $user_id && KMAE_Chat_Offers::can_offer( $conversation );
$live_offer = $can_offer ? KMAE_Chat_Offers::get_live_for_conversation( (int) $conversation->id ) : null;
?>
<section class="kmae-free-chat kmae-free-chat--compose" aria-labelledby="kmae-free-chat-title">
	<h3 id="kmae-free-chat-title" class="screen-reader-text"><?php esc_html_e( 'Szabad üzenet', 'ki-mit-adna-erte' ); ?></h3>
	<?php if ( $can_offer ) : ?>
		<details class="kmae-chat-offer">
			<summary class="kmae-chat-offer__toggle">
				<?php echo KMAE_Icons::render( 'tag', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Ajánlatot teszek', 'ki-mit-adna-erte' ); ?></span>
			</summary>
			<form method="post" class="kmae-chat-offer__form">
				<button type="button" class="kmae-chat-offer__close" data-kmae-offer-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>">
					<?php echo KMAE_Icons::render( 'x', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<?php wp_nonce_field( 'kmae_chat_offer_nonce', 'kmae_chat_offer_nonce' ); ?>
				<input type="hidden" name="kmae_action" value="send_chat_offer" />
				<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />
				<input type="hidden" name="kmae_idempotency_key" value="<?php echo esc_attr( (int) $conversation->id . ':' . wp_generate_uuid4() ); ?>" />
				<label class="kmae-chat-offer__label" for="kmae_offer_amount_<?php echo esc_attr( (int) $conversation->id ); ?>"><?php esc_html_e( 'Mennyit adnál érte?', 'ki-mit-adna-erte' ); ?></label>
				<div class="kmae-chat-offer__row">
					<span class="kmae-chat-offer__field">
						<input id="kmae_offer_amount_<?php echo esc_attr( (int) $conversation->id ); ?>" type="text" name="kmae_offer_amount" inputmode="numeric" autocomplete="off" maxlength="16" required placeholder="<?php esc_attr_e( 'pl. 3 500 000', 'ki-mit-adna-erte' ); ?>" />
						<span class="kmae-chat-offer__unit">Ft</span>
					</span>
					<button type="submit" class="kmae-chat-offer__send"><?php esc_html_e( 'Ajánlat elküldése', 'ki-mit-adna-erte' ); ?></button>
				</div>
				<p class="kmae-chat-offer__hint">
					<?php if ( $live_offer ) : ?>
						<?php
						/* translators: %s: formatted amount */
						echo esc_html( sprintf( __( 'Élő ajánlatod: %s. Az új ajánlat ezt felülírja.', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( (int) $live_offer->amount ) ) );
						?>
					<?php else : ?>
						<?php esc_html_e( 'Egy autóra egyszerre egy élő ajánlatod lehet. Ha újat küldesz, az felülírja az előzőt.', 'ki-mit-adna-erte' ); ?>
					<?php endif; ?>
				</p>
			</form>
		</details>
	<?php endif; ?>
	<form method="post" class="kmae-free-chat__form" enctype="multipart/form-data" data-kmae-chat-compose>
		<?php wp_nonce_field( 'kmae_conversation_message_nonce', 'kmae_conversation_message_nonce' ); ?>
		<input type="hidden" name="kmae_action" value="send_conversation_message" />
		<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />
		<input type="hidden" name="kmae_idempotency_key" value="<?php echo esc_attr( 'message:' . (int) $conversation->id . ':' . wp_generate_uuid4() ); ?>" />
		<label class="screen-reader-text" for="kmae_message_text_<?php echo esc_attr( (int) $conversation->id ); ?>"><?php esc_html_e( 'Üzenet', 'ki-mit-adna-erte' ); ?></label>
		<div class="kmae-free-chat__bar">
			<button type="button" class="kmae-free-chat__tool" data-kmae-chat-pick="file" aria-label="<?php esc_attr_e( 'Fájl csatolása', 'ki-mit-adna-erte' ); ?>">
				<?php echo KMAE_Icons::render( 'paperclip', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button type="button" class="kmae-free-chat__tool" data-kmae-chat-pick="image" aria-label="<?php esc_attr_e( 'Kép feltöltése', 'ki-mit-adna-erte' ); ?>">
				<?php echo KMAE_Icons::render( 'image', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<input class="kmae-free-chat__file" type="file" name="kmae_chat_file" data-kmae-chat-file-input="file" />
			<input class="kmae-free-chat__file" type="file" name="kmae_chat_image" accept="image/jpeg,image/png,image/webp,image/gif" data-kmae-chat-file-input="image" />
			<button type="button" class="kmae-free-chat__tool kmae-free-chat__tool--emoji" data-kmae-chat-emoji-toggle aria-label="<?php esc_attr_e( 'Emoji', 'ki-mit-adna-erte' ); ?>" aria-expanded="false">
				<?php echo KMAE_Icons::render( 'smile', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="kmae-free-chat__emoji" data-kmae-chat-emoji hidden>
				<?php foreach ( $emoji as $item ) : ?>
					<button type="button" class="kmae-free-chat__emoji-btn" data-kmae-chat-emoji-insert="<?php echo esc_attr( $item ); ?>"><?php echo esc_html( $item ); ?></button>
				<?php endforeach; ?>
			</div>
			<textarea id="kmae_message_text_<?php echo esc_attr( (int) $conversation->id ); ?>" name="kmae_message_text" rows="1" maxlength="5000" placeholder="<?php esc_attr_e( 'Írj üzenetet...', 'ki-mit-adna-erte' ); ?>" data-kmae-chat-text></textarea>
			<button type="submit" class="kmae-free-chat__send">
				<?php echo KMAE_Icons::render( 'send', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Küldés', 'ki-mit-adna-erte' ); ?></span>
			</button>
		</div>
		<p class="kmae-free-chat__file-name" data-kmae-chat-file-name hidden></p>
		<p class="kmae-free-chat__hint">
			<span class="kmae-free-chat__hint-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'paperclip', array( 'size' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php esc_html_e( 'A feltöltött kép és fájl legfeljebb 3 MB, és 48 óráig érhető el. Utána automatikusan törlődik.', 'ki-mit-adna-erte' ); ?>
		</p>
	</form>
</section>
