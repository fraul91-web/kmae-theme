<?php
/**
 * Authoritative buyer/seller conversation service.
 *
 * Chat messages are opaque free-form text.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Conversations {

	const STATUS_OPEN        = 'open';
	const STATUS_CLOSED      = 'closed';
	const MESSAGE_MAX_LENGTH = 5000;
	const IMAGE_TTL           = 172800;
	const CHAT_FILE_MAX_BYTES = 3145728;
	/* Üzenetek lista „törlés” (csak a törlő félnél): beszélgetés ID => az utolsó üzenet ID-ja a törléskor. */
	const HIDDEN_META         = '_kmae_conversations_hidden';

	public static function init() {
		add_action( 'kmae_purge_chat_images', array( __CLASS__, 'purge_expired_images' ) );
	}

	/**
	 * True when the listing post is gone or unpublished (force-deleted / trashed).
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function listing_is_deleted( $listing_id ) {
		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return true;
		}
		$post = get_post( $listing_id );
		if ( ! $post || 'kmae_listing' !== $post->post_type ) {
			return true;
		}
		return 'publish' !== (string) $post->post_status;
	}

	/**
	 * Get a conversation by ID.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return object|null
	 */
	public static function get( $conversation_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE id = %d',
				(int) $conversation_id
			)
		);
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @param int $buyer_id   Buyer ID.
	 * @return object|null
	 */
	public static function get_for_listing_buyer( $listing_id, $buyer_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE listing_id = %d AND buyer_id = %d LIMIT 1',
				(int) $listing_id,
				(int) $buyer_id
			)
		);
	}

	/**
	 * Conversations where the user is buyer or seller, newest activity first.
	 *
	 * @return array<object>
	 */
	public static function get_for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE buyer_id = %d OR seller_id = %d ORDER BY COALESCE(last_message_at, updated_at) DESC, id DESC',
				$user_id,
				$user_id
			)
		);
	}

	/**
	 * Messages inbox rows for the viewer: talks with at least one message (a deleted
	 * listing's talk stays, read-only, under its saved title).
	 *
	 * @param int $user_id Viewer.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_inbox_for_user( $user_id ) {
		$user_id = (int) $user_id;
		$rows    = array();
		$hidden  = self::get_hidden_map( $user_id );
		$all     = self::get_for_user( $user_id );
		$offers  = KMAE_Chat_Offers::latest_by_conversation( wp_list_pluck( $all, 'id' ) );
		$reserved = KMAE_Chat_Offers::reserved_listing_ids();
		foreach ( $all as $conversation ) {
			if ( (int) $conversation->message_count <= 0 ) {
				continue;
			}
			if ( isset( $hidden[ (int) $conversation->id ] ) && ! self::has_message_after( $conversation, $user_id, (int) $hidden[ (int) $conversation->id ] ) ) {
				continue;
			}
			$messages = self::get_messages( (int) $conversation->id, $user_id, 1 );
			$last     = ( ! is_wp_error( $messages ) && ! empty( $messages ) ) ? end( $messages ) : null;
			if ( ! $last ) {
				/* Csak letiltott féltől jött üzenet: a tiltónak nem jelenik meg. */
				continue;
			}
			$other_id = (int) $conversation->buyer_id === $user_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
			$other    = get_userdata( $other_id );
			$preview  = $last ? trim( (string) $last->message_text ) : '';
			if ( '' === $preview && $last && ! empty( $last->attachment_id ) ) {
				$preview = __( 'Fájl', 'ki-mit-adna-erte' );
			}
			$is_buyer     = (int) $conversation->buyer_id === $user_id;
			$listing_gone = self::listing_is_deleted( (int) $conversation->listing_id );
			$offer        = $offers[ (int) $conversation->id ] ?? null;
			$offer_status = $offer ? (string) $offer->status : '';
			$state        = '';
			$state_label  = '';
			if ( $listing_gone ) {
				$state = '';
			} elseif ( KMAE_Chat_Offers::STATUS_ACCEPTED === $offer_status ) {
				$state       = 'reserved';
				$state_label = __( 'Foglalt', 'ki-mit-adna-erte' );
			} elseif ( KMAE_Chat_Offers::STATUS_SOLD === $offer_status ) {
				$state       = 'sold';
				$state_label = __( 'Eladva', 'ki-mit-adna-erte' );
			} elseif ( KMAE_Chat_Offers::STATUS_LIVE === $offer_status ) {
				$state = 'offer';
				/* translators: %s: formatted amount */
				$state_label = sprintf( __( 'Ajánlat: %s', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( (int) $offer->amount ) );
			} elseif ( KMAE_Chat_Offers::STATUS_LAPSED === $offer_status || self::STATUS_OPEN !== (string) $conversation->status ) {
				$state       = 'closed';
				$state_label = __( 'Lezárva', 'ki-mit-adna-erte' );
			} elseif ( isset( $reserved[ (int) $conversation->listing_id ] ) ) {
				$state       = 'reserved-other';
				$state_label = $is_buyer ? __( 'Másnak foglalva', 'ki-mit-adna-erte' ) : __( 'Foglalt', 'ki-mit-adna-erte' );
			}
			$rows[] = array(
				'conversation' => $conversation,
				'listing_id'   => (int) $conversation->listing_id,
				'listing_gone' => $listing_gone,
				'is_buyer'     => $is_buyer,
				'state'        => $state,
				'state_label'  => $state_label,
				/* Foglalt (elfogadott ajánlat) alatt egyik fél sem törölheti, amíg az eladó [Mégse kelt el]-t nem nyom. */
				'delete_locked' => KMAE_Chat_Offers::STATUS_ACCEPTED === $offer_status,
				'live_offer'   => $is_buyer && KMAE_Chat_Offers::STATUS_LIVE === $offer_status,
				'other_name'   => $other ? (string) $other->display_name : '',
				'preview'      => $preview,
				'last_at'      => $last ? (string) $last->created_at : (string) $conversation->updated_at,
				'last_is_mine' => $last && (int) $last->sender_id === $user_id,
				'unread'       => 'good' === KMAE_Database::get_conversation_alert_state( $user_id, $conversation ),
				'url'          => KMAE_Pages::conversation_thread_url( (int) $conversation->id ),
			);
		}
		return $rows;
	}

	/**
	 * Conversations the user removed from their own Üzenetek list.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,int> Conversation ID => last message ID at removal.
	 */
	private static function get_hidden_map( $user_id ) {
		$map = get_user_meta( (int) $user_id, self::HIDDEN_META, true );
		return is_array( $map ) ? array_map( 'intval', $map ) : array();
	}

	/**
	 * A removed talk comes back when a newer message arrives from the other
	 * party, or the user writes into it again (own system notices don't count).
	 *
	 * @param object $conversation Conversation row.
	 * @param int    $user_id      Viewer.
	 * @param int    $after_id     Last message ID at removal.
	 * @return bool
	 */
	private static function has_message_after( $conversation, $user_id, $after_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE conversation_id = %d AND id > %d AND ( sender_id <> %d OR idempotency_key NOT LIKE %s )',
				(int) $conversation->id,
				(int) $after_id,
				(int) $user_id,
				$wpdb->esc_like( KMAE_Chat_Offers::SYSTEM_KEY ) . '%'
			) . KMAE_User_Blocks::hidden_messages_sql( $conversation, $user_id ) . ' LIMIT 1'
		);
	}

	/**
	 * Remove a talk from the user's own list (the other party keeps it). A
	 * buyer's live offer is retracted first; a „Foglalt” talk can't be removed.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $user_id         Acting user.
	 * @return true|WP_Error
	 */
	public static function hide_for_user( $conversation_id, $user_id ) {
		global $wpdb;

		$user_id      = (int) $user_id;
		$conversation = self::get( (int) $conversation_id );
		if ( ! self::is_member( $conversation, $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez a beszélgetéshez.', 'ki-mit-adna-erte' ) );
		}
		$accepted = KMAE_Chat_Offers::get_accepted_for_listing( (int) $conversation->listing_id );
		if ( $accepted && (int) $accepted->conversation_id === (int) $conversation->id ) {
			return new WP_Error( 'conversation_locked', __( 'Foglalt állapotban a beszélgetés nem törölhető.', 'ki-mit-adna-erte' ) );
		}
		if ( (int) $conversation->buyer_id === $user_id ) {
			$live = KMAE_Chat_Offers::get_live_for_conversation( (int) $conversation->id );
			if ( $live ) {
				$retracted = KMAE_Chat_Offers::retract( (int) $live->id, $user_id );
				if ( is_wp_error( $retracted ) ) {
					return $retracted;
				}
			}
		}

		KMAE_Database::mark_conversation_thread_seen( $user_id, $conversation );
		$last_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(MAX(id), 0) FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE conversation_id = %d', (int) $conversation->id ) );
		$map     = self::get_hidden_map( $user_id );
		$map[ (int) $conversation->id ] = $last_id;
		update_user_meta( $user_id, self::HIDDEN_META, $map );
		return true;
	}

	/**
	 * Thread events: chat messages, oldest first.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public static function get_thread_events( $conversation_id, $user_id ) {
		$conversation = self::get( (int) $conversation_id );
		if ( ! self::is_member( $conversation, (int) $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez a beszélgetéshez.', 'ki-mit-adna-erte' ) );
		}

		self::purge_expired_images();

		$events   = array();
		$messages = self::get_messages( (int) $conversation_id, (int) $user_id, 100 );
		if ( is_wp_error( $messages ) ) {
			return $messages;
		}
		foreach ( $messages as $message ) {
			$events[] = array(
				'type'       => 'message',
				'created_at' => $message->created_at,
				'sort'       => strtotime( $message->created_at ) . '.1.' . (int) $message->id,
				'message'    => $message,
			);
		}
		return $events;
	}

	/**
	 * Latest chat timestamp from the other party, or null.
	 *
	 * @return string|null MySQL datetime.
	 */
	public static function get_last_message_from_other( $conversation_id, $user_id ) {
		global $wpdb;

		$conversation_id = (int) $conversation_id;
		$user_id         = (int) $user_id;
		if ( $conversation_id <= 0 || $user_id <= 0 ) {
			return null;
		}

		$hidden  = KMAE_User_Blocks::hidden_messages_sql( self::get( $conversation_id ), $user_id );
		$created = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT created_at FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE conversation_id = %d AND sender_id <> %d',
				$conversation_id,
				$user_id
			) . $hidden . ' ORDER BY id DESC LIMIT 1'
		);

		return is_string( $created ) && '' !== $created ? $created : null;
	}

	/**
	 * Listing deleted: close talks (they stay readable, with the listing title
	 * saved) and clear unread badges for every party.
	 *
	 * @param int   $listing_id  Listing ID (call it while the post still exists).
	 * @param int[] $keep_unread Users whose fresh notice should stay unread.
	 */
	public static function withdraw_alerts_for_listing( $listing_id, $keep_unread = array() ) {
		global $wpdb;

		$listing_id = (int) $listing_id;
		if ( $listing_id <= 0 ) {
			return;
		}

		/* A törlés után is látszódjon a chatben és a listában, melyik autóról volt szó. */
		$title = get_post( $listing_id ) ? KMAE_Vehicle::get_display_title( $listing_id ) : '';
		if ( '' !== $title ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . KMAE_Database::table( 'conversations' ) . ' SET listing_title = %s WHERE listing_id = %d',
					mb_substr( $title, 0, 255 ),
					$listing_id
				)
			);
		}
		$keep_unread = array_map( 'intval', (array) $keep_unread );

		$conversations = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE listing_id = %d',
				$listing_id
			)
		);
		if ( empty( $conversations ) ) {
			return;
		}

		foreach ( $conversations as $conversation ) {
			if ( self::STATUS_OPEN === (string) $conversation->status ) {
				$wpdb->query(
					$wpdb->prepare(
						'UPDATE ' . KMAE_Database::table( 'conversations' ) . ' SET status = %s, updated_at = NOW() WHERE id = %d AND status = %s',
						self::STATUS_CLOSED,
						(int) $conversation->id,
						self::STATUS_OPEN
					)
				);
				$conversation->status = self::STATUS_CLOSED;
			}

			foreach ( array( (int) $conversation->buyer_id, (int) $conversation->seller_id ) as $party_id ) {
				if ( $party_id > 0 && ! in_array( $party_id, $keep_unread, true ) ) {
					KMAE_Database::mark_conversation_thread_seen( $party_id, $conversation );
				}
			}
		}
	}

	/**
	 * Listing sold: every talk on it becomes read-only.
	 *
	 * @param int $listing_id Listing ID.
	 */
	public static function close_for_listing( $listing_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . KMAE_Database::table( 'conversations' ) . ' SET status = %s, updated_at = NOW() WHERE listing_id = %d AND status = %s',
				self::STATUS_CLOSED,
				(int) $listing_id,
				self::STATUS_OPEN
			)
		);
	}

	/**
	 * Bump the conversation clock so the messages inbox fingerprint changes.
	 *
	 * @param int $conversation_id Conversation ID.
	 */
	public static function touch( $conversation_id ) {
		global $wpdb;
		$conversation_id = (int) $conversation_id;
		if ( $conversation_id <= 0 ) {
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . KMAE_Database::table( 'conversations' ) . ' SET updated_at = NOW() WHERE id = %d',
				$conversation_id
			)
		);
	}

	/**
	 * Create or resolve the single conversation for a listing and buyer.
	 *
	 * @param int         $listing_id       Listing ID.
	 * @param int         $buyer_id         Buyer user ID.
	 * @param int         $seller_id        Seller user ID.
	 * @return object|WP_Error
	 */
	public static function create( $listing_id, $buyer_id, $seller_id ) {
		global $wpdb;

		$listing_id = (int) $listing_id;
		$buyer_id   = (int) $buyer_id;
		$seller_id  = (int) $seller_id;
		if ( $listing_id <= 0 || $buyer_id <= 0 || $seller_id <= 0 || $buyer_id === $seller_id ) {
			return new WP_Error( 'invalid_conversation_parties', __( 'Érvénytelen beszélgetési résztvevők.', 'ki-mit-adna-erte' ) );
		}

		$table = KMAE_Database::table( 'conversations' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE listing_id = %d AND buyer_id = %d",
				$listing_id,
				$buyer_id
			)
		);
		if ( $row ) {
			if ( (int) $row->seller_id !== $seller_id ) {
				return new WP_Error( 'conversation_seller_mismatch', __( 'A beszélgetés eladója nem egyezik.', 'ki-mit-adna-erte' ) );
			}
			return $row;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'listing_id'       => $listing_id,
				'buyer_id'         => $buyer_id,
				'seller_id'        => $seller_id,
				'status'           => self::STATUS_OPEN,
			),
			array( '%d', '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			// A concurrent insert may have won the unique listing+buyer slot.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE listing_id = %d AND buyer_id = %d",
					$listing_id,
					$buyer_id
				)
			);
			if ( $row && (int) $row->seller_id === $seller_id ) {
				return $row;
			}
			return new WP_Error( 'conversation_create_failed', __( 'A beszélgetés nem hozható létre.', 'ki-mit-adna-erte' ) );
		}

		return self::get( (int) $wpdb->insert_id );
	}

	/**
	 * @param object|int $conversation Conversation row or ID.
	 * @param int        $user_id      User ID.
	 * @return bool
	 */
	public static function is_member( $conversation, $user_id ) {
		if ( ! is_object( $conversation ) ) {
			$conversation = self::get( (int) $conversation );
		}
		return $conversation
			&& in_array( (int) $user_id, array( (int) $conversation->buyer_id, (int) $conversation->seller_id ), true );
	}

	/**
	 * Receiver rejection ends the thread. Both parties can still read it.
	 *
	 * @param int $conversation_id Conversation ID.
	 */
	public static function save_message( $conversation_id, $sender_id, $message_text, $idempotency_key, $attachment_id = 0, $silent = false ) {
		global $wpdb;

		$conversation = self::get( $conversation_id );
		if ( ! self::is_member( $conversation, $sender_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez a beszélgetéshez.', 'ki-mit-adna-erte' ) );
		}
		if ( self::STATUS_OPEN !== (string) $conversation->status ) {
			return new WP_Error( 'conversation_not_open', __( 'A beszélgetés lezárult, ide már nem lehet írni.', 'ki-mit-adna-erte' ) );
		}

		$message_text    = self::sanitize_textarea( $message_text, self::MESSAGE_MAX_LENGTH );
		$idempotency_key = sanitize_text_field( (string) $idempotency_key );
		$attachment_id   = (int) $attachment_id;
		if ( ( '' === $message_text && $attachment_id <= 0 ) || '' === $idempotency_key || strlen( $idempotency_key ) > 160 ) {
			return new WP_Error( 'invalid_message', __( 'Az üzenet és az ismétlési kulcs kötelező.', 'ki-mit-adna-erte' ) );
		}
		if ( $attachment_id > 0 && 'attachment' !== get_post_type( $attachment_id ) ) {
			return new WP_Error( 'invalid_image', __( 'A kép nem tölthető fel.', 'ki-mit-adna-erte' ) );
		}

		$messages = KMAE_Database::table( 'conversation_messages' );
		$existing = self::get_message_by_key( $conversation_id, $idempotency_key );
		if ( $existing ) {
			$same_image = (int) ( $existing->attachment_id ?? 0 ) === $attachment_id;
			return (int) $existing->sender_id === (int) $sender_id && (string) $existing->message_text === $message_text && $same_image
				? $existing
				: new WP_Error( 'idempotency_conflict', __( 'Az ismétlési kulcs már más üzenethez tartozik.', 'ki-mit-adna-erte' ) );
		}

		$wpdb->query( 'START TRANSACTION' );
		$row = array(
				'conversation_id' => (int) $conversation_id,
				'sender_id'       => (int) $sender_id,
				'message_text'    => $message_text,
				'idempotency_key' => $idempotency_key,
		);
		$formats = array( '%d', '%d', '%s', '%s' );
		if ( $attachment_id > 0 ) {
			$row['attachment_id']    = $attachment_id;
			$row['image_expires_at'] = self::image_expires_at();
			$formats[]               = '%d';
			$formats[]               = '%s';
		}
		$inserted = $wpdb->insert( $messages, $row, $formats );
		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			$existing = self::get_message_by_key( $conversation_id, $idempotency_key );
			if ( $existing && (int) $existing->sender_id === (int) $sender_id && (string) $existing->message_text === $message_text && (int) ( $existing->attachment_id ?? 0 ) === $attachment_id ) {
				return $existing;
			}
			return $existing
				? new WP_Error( 'idempotency_conflict', __( 'Az ismétlési kulcs már más üzenethez tartozik.', 'ki-mit-adna-erte' ) )
				: new WP_Error( 'message_save_failed', __( 'Az üzenet nem menthető.', 'ki-mit-adna-erte' ) );
		}

		$message_id = (int) $wpdb->insert_id;
		$updated    = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . KMAE_Database::table( 'conversations' ) . ' SET last_message_at = NOW(), message_count = message_count + 1, updated_at = NOW() WHERE id = %d',
				(int) $conversation_id
			)
		);
		if ( false === $updated ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'conversation_update_failed', __( 'A beszélgetés nem frissíthető.', 'ki-mit-adna-erte' ) );
		}
		$wpdb->query( 'COMMIT' );

		if ( (int) $conversation->buyer_id === (int) $sender_id ) {
			KMAE_Browse::remember_contacted( (int) $sender_id, (int) $conversation->listing_id );
		}

		$message = self::get_message( $message_id );
		if ( ! $silent ) {
		do_action( 'kmae_conversation_message_created', $message_id, (int) $conversation_id, (int) $sender_id );
		}
		return $message;
	}

	/**
	 * Read conversation messages for a member.
	 *
	 * @return array<object>|WP_Error
	 */
	public static function get_messages( $conversation_id, $user_id, $limit = 50, $before_id = 0 ) {
		global $wpdb;
		$conversation = self::get( (int) $conversation_id );
		if ( ! self::is_member( $conversation, (int) $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Nincs jogosultságod ehhez a beszélgetéshez.', 'ki-mit-adna-erte' ) );
		}

		$limit  = max( 1, min( 100, (int) $limit ) );
		$table  = KMAE_Database::table( 'conversation_messages' );
		$params = array( (int) $conversation_id );
		$sql    = "SELECT * FROM {$table} WHERE conversation_id = %d";
		if ( (int) $before_id > 0 ) {
			$sql     .= ' AND id < %d';
			$params[] = (int) $before_id;
		}
		$sql     .= ' ORDER BY id DESC LIMIT %d';
		$params[] = $limit;
		/* Letiltott beszélgetőtárs üzenetei a tiltó elől rejtve (már előkészített SQL). */
		$hidden = KMAE_User_Blocks::hidden_messages_sql( $conversation, (int) $user_id );
		$sql    = $wpdb->prepare( $sql, $params );
		if ( '' !== $hidden ) {
			$sql = str_replace( ' ORDER BY id DESC', $hidden . ' ORDER BY id DESC', $sql );
		}

		return array_reverse( $wpdb->get_results( $sql ) );
	}

	public static function get_message( $message_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE id = %d',
				(int) $message_id
			)
		);
	}

	private static function get_message_by_key( $conversation_id, $idempotency_key ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . KMAE_Database::table( 'conversation_messages' ) . ' WHERE conversation_id = %d AND idempotency_key = %s',
				(int) $conversation_id,
				(string) $idempotency_key
			)
		);
	}

	/**
	 * Sanitize plain textarea text and cap its Unicode length.
	 */
	private static function sanitize_textarea( $value, $max_length ) {
		$value = trim( sanitize_textarea_field( (string) $value ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, (int) $max_length );
		}
		return substr( $value, 0, (int) $max_length );
	}

	/**
	 * Store a chat attachment. Images are converted to WebP; other files stay as uploaded.
	 * Everything is removed after 48 hours.
	 *
	 * @param string $file_key $_FILES key.
	 * @return int|WP_Error Attachment ID, or 0 when no file was sent.
	 */
	public static function store_chat_attachment( $file_key ) {
		if ( empty( $_FILES[ $file_key ]['name'] ) ) {
			return 0;
		}

		$file = $_FILES[ $file_key ];
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'file_too_large', __( 'A fájl nem tölthető fel. A méret legfeljebb 3 MB lehet.', 'ki-mit-adna-erte' ) );
		}
		if ( ! empty( $file['size'] ) && (int) $file['size'] > self::CHAT_FILE_MAX_BYTES ) {
			return new WP_Error( 'file_too_large', __( 'A fájl legfeljebb 3 MB lehet.', 'ki-mit-adna-erte' ) );
		}

		$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( self::chat_extension_blocked( $extension ) ) {
			return new WP_Error( 'invalid_file', __( 'Ez a fájltípus nem tölthető fel.', 'ki-mit-adna-erte' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		add_filter( 'upload_mimes', array( __CLASS__, 'chat_upload_mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'allow_chat_filetype' ), 10, 4 );
		$attachment_id = media_handle_upload( $file_key, 0 );
		remove_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'allow_chat_filetype' ), 10 );
		remove_filter( 'upload_mimes', array( __CLASS__, 'chat_upload_mimes' ) );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_Error( 'invalid_file', __( 'A fájl nem tölthető fel.', 'ki-mit-adna-erte' ) );
		}

		$mime = (string) get_post_mime_type( (int) $attachment_id );
		if ( 0 === strpos( $mime, 'image/' ) ) {
			KMAE_Images::process_attachment( (int) $attachment_id );
		}
		update_post_meta( (int) $attachment_id, '_kmae_chat_attachment', 1 );
		return (int) $attachment_id;
	}

	/**
	 * @param array<string,string> $mimes Mime map.
	 * @return array<string,string>
	 */
	public static function chat_upload_mimes( $mimes ) {
		$extra = array(
			'txt'  => 'text/plain',
			'csv'  => 'text/csv',
			'pdf'  => 'application/pdf',
			'zip'  => 'application/zip',
			'rar'  => 'application/vnd.rar',
			'7z'   => 'application/x-7z-compressed',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'rtf'  => 'application/rtf',
			'mp3'  => 'audio/mpeg',
			'wav'  => 'audio/wav',
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'json' => 'application/json',
		);
		return array_merge( $mimes, $extra );
	}

	/**
	 * @param array<string,mixed> $data     Filetype check.
	 * @param string              $file     Temp path.
	 * @param string              $filename Original name.
	 * @param array<string,string>|null $mimes Allowed mimes.
	 * @return array<string,mixed>
	 */
	public static function allow_chat_filetype( $data, $file, $filename, $mimes ) {
		unset( $file );
		$extension = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
		if ( self::chat_extension_blocked( $extension ) ) {
			$data['ext']  = false;
			$data['type'] = false;
			return $data;
		}
		if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
			return $data;
		}
		$checked = wp_check_filetype( $filename, is_array( $mimes ) ? $mimes : null );
		if ( ! empty( $checked['ext'] ) && ! empty( $checked['type'] ) ) {
			$data['ext']             = $checked['ext'];
			$data['type']            = $checked['type'];
			$data['proper_filename'] = false;
			return $data;
		}
		if ( '' !== $extension ) {
			$data['ext']             = $extension;
			$data['type']            = 'application/octet-stream';
			$data['proper_filename'] = false;
		}
		return $data;
	}

	/**
	 * @param string $extension File extension without dot.
	 */
	private static function chat_extension_blocked( $extension ) {
		return in_array(
			$extension,
			array( 'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'pht', 'htaccess', 'htpasswd', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'exe', 'dll', 'so', 'bat', 'cmd', 'com', 'msi', 'scr', 'html', 'htm', 'svg', 'js', 'xhtml', 'shtml' ),
			true
		);
	}

	/**
	 * Delete chat images older than 48 hours, including the stored file.
	 */
	public static function purge_expired_images() {
		global $wpdb;

		$table = KMAE_Database::table( 'conversation_messages' );
		$now   = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, attachment_id FROM {$table} WHERE attachment_id IS NOT NULL AND attachment_id > 0 AND image_expires_at IS NOT NULL AND image_expires_at <= %s LIMIT 40",
				$now
			)
		);
		if ( ! $rows ) {
			return;
		}

		foreach ( $rows as $row ) {
			KMAE_Images::delete_attachment( (int) $row->attachment_id );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET attachment_id = NULL WHERE id = %d",
					(int) $row->id
				)
			);
		}
	}

	/**
	 * UTC expiry timestamp for a new chat image.
	 */
	public static function image_expires_at() {
		return wp_date( 'Y-m-d H:i:s', time() + self::IMAGE_TTL, new DateTimeZone( 'UTC' ) );
	}
}
