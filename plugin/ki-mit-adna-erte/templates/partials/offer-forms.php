<?php
/**
 * Listing contact CTA. Starts or resumes a conversation; never an offer.
 *
 * @package KMAE
 * @var int $listing_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" class="kmae-form kmae-start-conversation-form">
	<?php wp_nonce_field( 'kmae_start_conversation_nonce', 'kmae_start_conversation_nonce' ); ?>
	<input type="hidden" name="kmae_action" value="start_conversation" />
	<input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $listing_id ); ?>" />
	<button type="submit" class="kmae-btn kmae-btn-primary kmae-conversation-action kmae-listing-contact__go"><?php echo esc_html( isset( $contact_button_label ) ? $contact_button_label : __( 'Kapcsolat az eladóval', 'ki-mit-adna-erte' ) ); ?></button>
</form>
