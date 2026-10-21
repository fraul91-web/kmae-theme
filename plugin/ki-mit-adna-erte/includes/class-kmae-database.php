<?php
/**
 * Database table names and helper queries.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Database {

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'kmae_' . $name;
	}

	public static function get_private_data( $listing_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table( 'private_data' ) . ' WHERE listing_id = %d',
				$listing_id
			)
		);
	}

	public static function save_private_data( $listing_id, $minimum_cash, $currency = 'HUF' ) {
		global $wpdb;

		if ( $minimum_cash <= 0 ) {
			$wpdb->delete( self::table( 'private_data' ), array( 'listing_id' => $listing_id ), array( '%d' ) );
			return;
		}

		$existing = self::get_private_data( $listing_id );
		if ( $existing ) {
			$wpdb->update(
				self::table( 'private_data' ),
				array(
					'minimum_cash' => $minimum_cash,
					'currency'     => $currency,
				),
				array( 'listing_id' => $listing_id ),
				array( '%f', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				self::table( 'private_data' ),
				array(
					'listing_id'   => $listing_id,
					'minimum_cash' => $minimum_cash,
					'currency'     => $currency,
				),
				array( '%d', '%f', '%s' )
			);
		}
	}

	const CONVERSATIONS_SEEN_MAP_META   = '_kmae_conversations_seen_map';

	/**
	 * Az „olvasva” időpontokat az adatbázis órája szerint tároljuk: az
	 * üzenetek időbélyegei is a MySQL CURRENT_TIMESTAMP-jéből jönnek, így a két időskála garantáltan egyezik, akkor is, ha a
	 * WordPress időzónája eltér a MySQL-étől.
	 *
	 * @return string
	 */
	private static function db_now() {
		global $wpdb;
		return (string) $wpdb->get_var( 'SELECT NOW()' );
	}

	/**
	 * @return array<string, string>
	 */
	private static function get_conversations_seen_map( $user_id ) {
		$map = get_user_meta( $user_id, self::CONVERSATIONS_SEEN_MAP_META, true );
		return is_array( $map ) ? $map : array();
	}

	public static function get_conversation_seen_at( $user_id, $conversation_id ) {
		$map             = self::get_conversations_seen_map( $user_id );
		$conversation_id = (string) $conversation_id;
		if ( isset( $map[ $conversation_id ] ) && is_string( $map[ $conversation_id ] ) ) {
			return $map[ $conversation_id ];
		}

		return '1970-01-01 00:00:00';
	}

	public static function mark_conversation_seen( $user_id, $conversation_id ) {
		$map = self::get_conversations_seen_map( $user_id );
		$map[ (string) $conversation_id ] = self::db_now();
		update_user_meta( $user_id, self::CONVERSATIONS_SEEN_MAP_META, $map );
	}

	/**
	 * Mark a conversation thread as read.
	 *
	 * @param object $conversation Conversation row.
	 */
	public static function mark_conversation_thread_seen( $user_id, $conversation ) {
		if ( ! $conversation ) {
			return;
		}
		$seen_before = self::get_conversation_seen_at( $user_id, (int) $conversation->id );
		self::mark_conversation_seen( $user_id, (int) $conversation->id );

		/* Olvasási pipa élőben: csak akkor jelzünk a küldőnek, ha most tényleg
		   olvasott új üzenetet (különben a két fél oldala egymást töltené újra). */
		$last_other = class_exists( 'KMAE_Conversations' ) ? KMAE_Conversations::get_last_message_from_other( (int) $conversation->id, (int) $user_id ) : null;
		if ( $last_other && $last_other > $seen_before && class_exists( 'KMAE_Live_Signal' ) ) {
			$other_id = (int) $conversation->buyer_id === (int) $user_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
			KMAE_Live_Signal::bump( array( $other_id ) );
		}
	}

	/**
	 * Newest message of the sender that the other party has already read
	 * (live fingerprint of the sender's chat: the „read” ticks).
	 *
	 * @param object $conversation Conversation row.
	 * @param int    $sender_id    Message author.
	 * @return int Message ID, 0 when none is read yet.
	 */
	public static function conversation_read_upto( $conversation, $sender_id ) {
		global $wpdb;
		$reader_id = (int) $conversation->buyer_id === (int) $sender_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(MAX(id), 0) FROM ' . self::table( 'conversation_messages' ) . ' WHERE conversation_id = %d AND sender_id = %d AND created_at <= %s',
				(int) $conversation->id,
				(int) $sender_id,
				self::get_conversation_seen_at( $reader_id, (int) $conversation->id )
			)
		);
	}

	/**
	 * Listing still exists as a live badge target (not missing / trash).
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool
	 */
	public static function listing_target_is_live( $listing_id ) {
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return false;
		}
		$listing = get_post( $listing_id );
		if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
			return false;
		}
		return 'trash' !== $listing->post_status;
	}

	/**
	 * @return string ''|'good'
	 */
	public static function get_conversation_alert_state( $user_id, $conversation ) {
		if ( ! $conversation || ! class_exists( 'KMAE_Conversations' ) ) {
			return '';
		}
		if ( KMAE_Conversations::STATUS_OPEN !== (string) $conversation->status ) {
			return '';
		}
		if ( ! self::listing_target_is_live( (int) $conversation->listing_id ) ) {
			return '';
		}
		$last = KMAE_Conversations::get_last_message_from_other( (int) $conversation->id, (int) $user_id );
		if ( ! $last ) {
			return '';
		}
		$seen_at = self::get_conversation_seen_at( $user_id, (int) $conversation->id );
		return $last > $seen_at ? 'good' : '';
	}
	/**
	 * Conversations with an unread message from the other party (nav badge).
	 *
	 * @param int $user_id Viewer.
	 * @return int
	 */
	public static function count_unread_conversations( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! class_exists( 'KMAE_Conversations' ) ) {
			return 0;
		}

		$count = 0;
		foreach ( KMAE_Conversations::get_for_user( $user_id ) as $conversation ) {
			if ( 'good' === self::get_conversation_alert_state( $user_id, $conversation ) ) {
				++$count;
			}
		}

		return $count;
	}
}
