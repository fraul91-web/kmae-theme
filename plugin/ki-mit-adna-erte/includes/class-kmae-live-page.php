<?php
/**
 * Live page fingerprint for auto-refresh polling.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Live_Page {

	/**
	 * @param array<string,mixed> $args
	 */
	public static function get_fingerprint( $context, $args = array() ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user_id = get_current_user_id();
		$parts   = array( $context, (string) $user_id );

		switch ( $context ) {
			case 'conversation_thread':
				$conversation_id = isset( $args['conversation_id'] ) ? absint( $args['conversation_id'] ) : 0;
				$conversation    = $conversation_id ? KMAE_Conversations::get( $conversation_id ) : null;
				if ( $conversation && KMAE_Conversations::is_member( $conversation, $user_id ) ) {
					$parts[] = 'conversation:' . (int) $conversation->id
						. ':' . (int) $conversation->message_count
						. ':' . ( $conversation->last_message_at ?? '' )
						. ':' . ( $conversation->status ?? '' )
						. ':' . ( $conversation->updated_at ?? '' );
					/* Csak a MÁSIK fél olvasottsága (pipák); a sajátja nem kerülhet ide (dupla újratöltés). */
					$parts[] = 'read:' . KMAE_Database::conversation_read_upto( $conversation, $user_id );
				}
				break;

			case 'messages_inbox':
				/* Az olvasottság szándékosan nincs benne: a lista csak új üzenetre
				   (message_count / last_message_at) frissüljön. */
				foreach ( KMAE_Conversations::get_for_user( $user_id ) as $conversation ) {
					$parts[] = 'c:' . (int) $conversation->id
						. ':' . (int) $conversation->message_count
						. ':' . ( $conversation->last_message_at ?? '' )
						. ':' . ( $conversation->status ?? '' );
				}
				break;

			case 'nav_badge':
				$parts[] = (string) KMAE_Database::count_unread_conversations( $user_id );
				break;

			case 'single_listing':
				$listing_id = isset( $args['listing_id'] ) ? absint( $args['listing_id'] ) : 0;
				if ( ! $listing_id ) {
					break;
				}
				$parts[] = KMAE_Meta::get_status( $listing_id );
				$parts[] = KMAE_Chat_Offers::listing_state( $listing_id );
				/* „N ajánlat érkezett”: küldés / visszavonás / újraküldés. */
				$parts[] = (string) KMAE_Chat_Offers::count_live_for_listing( $listing_id );
				break;

			case 'my_listings':
				/* Saját hirdetéseim: az ajánlat-számláló élőben kövesse a vevők lépéseit. */
				$parts[] = KMAE_Chat_Offers::seller_signature( $user_id );
				break;

			case 'dashboard_home':
				/* Élő főoldal: megtekintés, állapot, ajánlat, új üzenet. Az olvasottság
				   szándékosan nincs benne (a chat a render után jelöl, dupla újratöltés lenne). */
				$ids = get_posts(
					array(
						'post_type'              => 'kmae_listing',
						'post_status'            => 'publish',
						'author'                 => $user_id,
						'posts_per_page'         => 50,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => true,
						'update_post_term_cache' => false,
					)
				);
				foreach ( $ids as $listing_id ) {
					$parts[] = 'l:' . (int) $listing_id . ':' . KMAE_Meta::get_status( $listing_id ) . ':' . KMAE_Dashboard::get_listing_views( $listing_id );
				}
				$parts[] = KMAE_Chat_Offers::seller_signature( $user_id );
				/* Kedvencek: ki mentette / törölte a hirdetést (csak darabszám). */
				foreach ( KMAE_Dashboard::favorite_counts( $ids ) as $listing_id => $fav_count ) {
					$parts[] = 'f:' . (int) $listing_id . ':' . (int) $fav_count;
				}
				foreach ( KMAE_Conversations::get_for_user( $user_id ) as $conversation ) {
					if ( (int) $conversation->seller_id === (int) $user_id ) {
						$parts[] = 'c:' . (int) $conversation->id . ':' . (int) $conversation->message_count;
					}
				}
				break;

			case 'email_verify':
				/* Megerősítésre váró oldal (letiltó képernyő, profil): a levélben
				   kapott linkre kattintva — akár másik eszközön — magától frissül. */
				$parts[] = KMAE_Auth::is_email_verified( $user_id ) ? 'verified' : 'pending';
				break;

			default:
				break;
		}

		return md5( implode( '|', $parts ) );
	}
}
