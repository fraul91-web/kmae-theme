<?php
/**
 * Felhasználó tiltása (chat jobb oszlop).
 *
 * A letiltott fél ugyanúgy írhat és ajánlatot tehet, semmit nem vesz észre —
 * csak a tiltó nem kapja meg: a tiltás óta küldött üzenetei és ajánlatai nem
 * látszanak nála, nem adnak jelvényt, e-mailt, push-t. Feloldás után újra
 * minden látszik. A rendszerüzenetek (pl. „másnak foglalta”) a tiltótól
 * függetlenül megjelennek, kivéve a letiltott vevő saját visszavonását.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_User_Blocks {

	/* User meta a tiltón: letiltott user ID => tiltás ideje (adatbázis-idő, mint a created_at). */
	const META  = '_kmae_blocked_users';
	const NONCE = 'kmae_user_block_nonce';

	/**
	 * @param int $user_id Tiltó.
	 * @return array<int,string>
	 */
	public static function get_blocked( $user_id ) {
		$map = get_user_meta( (int) $user_id, self::META, true );
		return is_array( $map ) ? $map : array();
	}

	/**
	 * Mióta tiltja a $user_id a $other_id-t ('' = nem tiltja).
	 */
	public static function blocked_since( $user_id, $other_id ) {
		$map = self::get_blocked( $user_id );
		return isset( $map[ (int) $other_id ] ) ? (string) $map[ (int) $other_id ] : '';
	}

	public static function is_blocked( $user_id, $other_id ) {
		return '' !== self::blocked_since( $user_id, $other_id );
	}

	/**
	 * @return true|WP_Error
	 */
	public static function block( $user_id, $other_id ) {
		global $wpdb;
		$user_id  = (int) $user_id;
		$other_id = (int) $other_id;
		if ( $user_id <= 0 || $other_id <= 0 || $user_id === $other_id || ! get_userdata( $other_id ) ) {
			return new WP_Error( 'forbidden', __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}
		$map = self::get_blocked( $user_id );
		if ( ! isset( $map[ $other_id ] ) ) {
			$map[ $other_id ] = (string) $wpdb->get_var( 'SELECT NOW()' );
			update_user_meta( $user_id, self::META, $map );
		}
		return true;
	}

	public static function unblock( $user_id, $other_id ) {
		$map = self::get_blocked( (int) $user_id );
		if ( isset( $map[ (int) $other_id ] ) ) {
			unset( $map[ (int) $other_id ] );
			update_user_meta( (int) $user_id, self::META, $map );
		}
		return true;
	}

	/**
	 * SQL feltétel a conversation_messages lekérdezésekhez: a néző elől rejtett
	 * üzenetek kiszűrése. Üres, ha a néző nem tiltja a beszélgetőtársát.
	 *
	 * @param object $conversation Beszélgetés.
	 * @param int    $viewer_id    Néző.
	 * @return string ' AND NOT (...)' vagy ''.
	 */
	public static function hidden_messages_sql( $conversation, $viewer_id ) {
		global $wpdb;
		if ( ! $conversation ) {
			return '';
		}
		$viewer_id = (int) $viewer_id;
		$other_id  = (int) $conversation->buyer_id === $viewer_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
		$since     = self::blocked_since( $viewer_id, $other_id );
		if ( '' === $since ) {
			return '';
		}
		$system = $wpdb->esc_like( KMAE_Chat_Offers::SYSTEM_KEY );
		return $wpdb->prepare(
			' AND NOT ( sender_id = %d AND created_at >= %s AND ( idempotency_key NOT LIKE %s OR idempotency_key LIKE %s ) )',
			$other_id,
			$since,
			$system . '%',
			$system . 'offer_retracted:%'
		);
	}

	/**
	 * Egy üzenet rejtett-e a címzettje elől (értesítés, élő jelzés).
	 *
	 * @param int $message_id   Üzenet.
	 * @param int $recipient_id Címzett.
	 */
	public static function hides_message( $message_id, $recipient_id ) {
		$message = KMAE_Conversations::get_message( (int) $message_id );
		if ( ! $message ) {
			return false;
		}
		$since = self::blocked_since( (int) $recipient_id, (int) $message->sender_id );
		if ( '' === $since || (string) $message->created_at < $since ) {
			return false;
		}
		$kind = KMAE_Chat_Offers::message_kind( (int) $message_id );
		return 'system' !== $kind;
	}
}
