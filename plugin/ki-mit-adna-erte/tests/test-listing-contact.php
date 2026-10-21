<?php
/**
 * Listing contact CTA and chat-only conversation scan.
 *
 * Run: php tests/test-listing-contact.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$failed = array();
$passed = 0;

function listing_contact_assert( $name, $condition, &$passed, &$failed ) {
	if ( $condition ) {
		++$passed;
		echo 'PASS  ' . $name . "\n";
		return;
	}
	$failed[] = $name;
	echo 'FAIL  ' . $name . "\n";
}

$root          = dirname( __DIR__ );
$listing       = file_get_contents( $root . '/templates/single-listing.php' );
$forms         = file_get_contents( $root . '/templates/partials/offer-forms.php' );
$public        = file_get_contents( $root . '/public/class-kmae-public.php' );
$shortcodes    = file_get_contents( $root . '/public/class-kmae-shortcodes.php' );
$pages         = file_get_contents( $root . '/includes/class-kmae-pages.php' );
$thread        = file_get_contents( $root . '/templates/offer-thread.php' );
$chat          = file_get_contents( $root . '/templates/partials/conversation-free-chat.php' );
$notifications = file_get_contents( $root . '/includes/class-kmae-notifications.php' );
$conversations = file_get_contents( $root . '/includes/class-kmae-conversations.php' );

listing_contact_assert(
	'A listing CTA starts a conversation for every price type',
	false === strpos( $forms, 'is_fixed_price' )
	&& false !== strpos( $forms, 'value="start_conversation"' ),
	$passed,
	$failed
);

listing_contact_assert(
	'D existing conversation opens by GET, no second POST',
	false !== strpos( $listing, 'elseif ( $user_conversation )' )
	&& false !== strpos( $listing, 'conversation_thread_url' ),
	$passed,
	$failed
);

listing_contact_assert(
	'E chat composer: text, emoji, file upload',
	false !== strpos( $chat, 'send_conversation_message' )
	&& false !== strpos( $chat, 'data-kmae-chat-emoji' )
	&& false !== strpos( $chat, 'kmae_chat_file' )
	&& false === strpos( $chat, 'has_rejected_offer' ),
	$passed,
	$failed
);

listing_contact_assert(
	'F thread is chat only',
	false === strpos( $thread, 'conversation-business-actions.php' )
	&& false === strpos( $thread, 'conversation-info-banner.php' )
	&& false !== strpos( $thread, 'conversation-free-chat.php' ),
	$passed,
	$failed
);

listing_contact_assert(
	'G chat lives on the Üzenetek page',
	false !== strpos( $pages, "MESSAGES_PAGE_SLUG = 'uzenetek'" )
	&& false !== strpos( $shortcodes, 'templates/offer-thread.php' ),
	$passed,
	$failed
);

listing_contact_assert(
	'H seller does not get listing contact CTA',
	false !== strpos( $listing, '$can_contact' )
	&& false !== strpos( $listing, '! $is_owner' ),
	$passed,
	$failed
);

listing_contact_assert(
	'I guest CTA goes to login',
	false !== strpos( $listing, 'login_url' )
	&& false !== strpos( $listing, '! is_user_logged_in()' ),
	$passed,
	$failed
);

listing_contact_assert(
	'empty conversation create does not notify',
	false === strpos( $conversations, "do_action( 'kmae_conversation_created'" )
	&& false !== strpos( $notifications, 'kmae_conversation_message_created' ),
	$passed,
	$failed
);

$offer_actions = array( 'send_official_offer', 'accept_official_offer', 'reject_official_offer', 'withdraw_official_offer', 'dismiss_hub_offer', 'conversation_thinking' );
$left_actions  = array();
foreach ( $offer_actions as $offer_action ) {
	if ( false !== strpos( $public, "'" . $offer_action . "' === \$action" ) ) {
		$left_actions[] = $offer_action;
	}
}

listing_contact_assert(
	'public HTTP dispatcher has no offer/deal actions',
	array() === $left_actions,
	$passed,
	$failed
);

$templates = array();
$iterator  = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root . '/templates', FilesystemIterator::SKIP_DOTS )
);
foreach ( $iterator as $file ) {
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$contents = file_get_contents( $file->getPathname() );
	if ( preg_match( '/KMAE_(Offers|Official_Offers|Counter_Offers|Deal|Trust|History)\b/', $contents ) ) {
		$templates[] = str_replace( $root . '/', '', $file->getPathname() );
	}
}

listing_contact_assert(
	'no template uses a removed offer/deal class',
	array() === $templates,
	$passed,
	$failed
);

echo "\n{$passed} passed, " . count( $failed ) . " failed.\n";
if ( $failed ) {
	if ( $templates ) {
		echo 'Templates using removed classes: ' . implode( ', ', $templates ) . "\n";
	}
	throw new RuntimeException( 'Failed: ' . implode( '; ', $failed ) );
}

echo "Listing contact: all vectors passed.\n";
