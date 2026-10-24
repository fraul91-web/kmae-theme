<?php
/**
 * Buyer price offers sent inside the chat.
 *
 * Each offer is stored as a chat message (so inbox, badge, email and push work
 * unchanged) plus a row in `kmae_chat_offers` that carries the amount and state.
 * A buyer has at most one live offer per listing; a new one replaces the old.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Chat_Offers {

	const STATUS_LIVE      = 'live';
	const STATUS_REPLACED  = 'replaced';
	const STATUS_ACCEPTED  = 'accepted';
	const STATUS_CLOSED    = 'closed';
	const STATUS_WITHDRAWN = 'withdrawn';
	const STATUS_SOLD      = 'sold';
	const STATUS_RETRACTED = 'retracted';
	const STATUS_LAPSED    = 'lapsed';
	const SOLD_AFTER_DAYS  = 7;
	const SYSTEM_KEY       = 'system:';
	const AMOUNT_MIN       = 1000;
	const AMOUNT_MAX       = 9999999999;
	const BROWSE_CACHE_TAG = 'kmae_browse';

	public static function init() {
		add_action( 'kmae_finalize_chat_sales', array( __CLASS__, 'finalize_sales' ) );
	}

	/**
	 * Parse a user-typed amount ("3 500 000 Ft") into whole forints.
	 *
	 * @param mixed $raw Raw input.
	 * @return int 0 when empty or invalid.
	 */
	public static function parse_amount( $raw ) {
		$digits = preg_replace( '/\D+/', '', (string) $raw );
		if ( '' === $digits || strlen( $digits ) > 10 ) {
			return 0;
		}
		return (int) $digits;
	}

	/**
	 * Buyer sends (or replaces) their offer in a conversation.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $buyer_id        Sender, must be the conversation buyer.
	 * @param int    $amount          Whole forints.
	 * @param string $idempotency_key Request key.
	 * @return object|WP_Error Offer row.
	 */
	public static function submit( $conversation_id, $buyer_id, $amount, $idempotency_key ) {
		global $wpdb;

		$conversation = KMAE_Conversations::get( (int) $conversation_id );
		if ( ! $conversation || (int) $conversation->buyer_id !== (int) $buyer_id ) {
			return new WP_Error( 'forbidden', __( 'Ajánlatot csak a vevő tehet.', 'ki-mit-adna-erte' ) );
		}
		if ( ! self::can_offer( $conversation ) ) {
			return new WP_Error( 'offer_closed', __( 'Erre a hirdetésre most nem lehet ajánlatot tenni.', 'ki-mit-adna-erte' ) );
		}
		$amount = (int) $amount;
		if ( $amount < self::AMOUNT_MIN || $amount > self::AMOUNT_MAX ) {
			return new WP_Error( 'invalid_offer_amount', __( 'Adj meg érvényes összeget.', 'ki-mit-adna-erte' ) );
		}

		/* translators: %s: formatted amount */
		$text    = sprintf( __( 'Ajánlat: %s', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( $amount ) );
		$message = KMAE_Conversations::save_message( (int) $conversation->id, (int) $buyer_id, $text, 'offer:' . $idempotency_key, 0, true );
		if ( is_wp_error( $message ) ) {
			return $message;
		}

		$table    = KMAE_Database::table( 'chat_offers' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE message_id = %d", (int) $message->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing ) {
			return $existing;
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s, updated_at = NOW() WHERE listing_id = %d AND buyer_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::STATUS_REPLACED,
				(int) $conversation->listing_id,
				(int) $buyer_id,
				self::STATUS_LIVE
			)
		);
		$inserted = $wpdb->insert(
			$table,
			array(
				'listing_id'      => (int) $conversation->listing_id,
				'conversation_id' => (int) $conversation->id,
				'message_id'      => (int) $message->id,
				'buyer_id'        => (int) $buyer_id,
				'seller_id'       => (int) $conversation->seller_id,
				'amount'          => $amount,
				'status'          => self::STATUS_LIVE,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'offer_save_failed', __( 'Az ajánlat nem menthető.', 'ki-mit-adna-erte' ) );
		}

		do_action( 'kmae_conversation_message_created', (int) $message->id, (int) $conversation->id, (int) $buyer_id );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $wpdb->insert_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Offers can be sent while the talk is open and the listing is active.
	 *
	 * @param object $conversation Conversation row.
	 * @return bool
	 */
	public static function can_offer( $conversation ) {
		return $conversation
			&& KMAE_Conversations::STATUS_OPEN === (string) $conversation->status
			&& ! KMAE_Conversations::listing_is_deleted( (int) $conversation->listing_id )
			&& KMAE_Meta::STATUS_ACTIVE === KMAE_Meta::get_status( (int) $conversation->listing_id )
			&& ! self::get_accepted_for_listing( (int) $conversation->listing_id );
	}

	/**
	 * Seller accepts a live offer: the listing becomes "Foglalt", every other
	 * live offer on it is closed and every other chat of the listing gets a
	 * system message.
	 *
	 * @param int $offer_id  Offer ID.
	 * @param int $seller_id Acting user, must be the listing seller.
	 * @return object|WP_Error Accepted offer row.
	 */
	public static function accept( $offer_id, $seller_id ) {
		global $wpdb;

		$table = KMAE_Database::table( 'chat_offers' );
		$offer = self::get( (int) $offer_id );
		if ( ! $offer || (int) $offer->seller_id !== (int) $seller_id ) {
			return new WP_Error( 'forbidden', __( 'Ajánlatot csak az eladó fogadhat el.', 'ki-mit-adna-erte' ) );
		}
		$conversation = KMAE_Conversations::get( (int) $offer->conversation_id );
		if ( self::STATUS_LIVE !== (string) $offer->status || ! self::can_offer( $conversation ) ) {
			return new WP_Error( 'offer_not_acceptable', __( 'Ez az ajánlat már nem fogadható el.', 'ki-mit-adna-erte' ) );
		}

		$lock = 'kmae_offer_accept_' . (int) $offer->listing_id;
		$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
		$updated = self::get_accepted_for_listing( (int) $offer->listing_id )
			? 0
			: $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, accepted_at = NOW(), updated_at = NOW() WHERE id = %d AND status = %s", self::STATUS_ACCEPTED, (int) $offer->id, self::STATUS_LIVE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		if ( ! $updated ) {
			return new WP_Error( 'offer_not_acceptable', __( 'Ez az ajánlat már nem fogadható el.', 'ki-mit-adna-erte' ) );
		}

		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE listing_id = %d AND status = %s", self::STATUS_CLOSED, (int) $offer->listing_id, self::STATUS_LIVE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( self::other_conversation_ids( (int) $offer->listing_id, (int) $offer->conversation_id ) as $other_id ) {
			self::post_system_message( $other_id, (int) $seller_id, __( 'Az eladó, akivel beszélgetsz, másnak foglalta le az autót. Ha mégsem jön létre az adásvétel, és a hirdetés újra elérhető lesz, értesítünk.', 'ki-mit-adna-erte' ), 'listing_reserved:' . (int) $offer->id );
		}

		/* translators: %s: formatted amount */
		self::post_system_message( (int) $offer->conversation_id, (int) $seller_id, sprintf( __( 'Az eladó elfogadta az ajánlatodat (%s). Mostantól látjátok egymás telefonszámát.', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( (int) $offer->amount ) ), 'offer_accepted:' . (int) $offer->id );
		self::purge_page_cache( (int) $offer->listing_id );

		return self::get( (int) $offer->id );
	}

	/**
	 * "Mégse kelt el": the accepted offer ends, the listing is open for offers
	 * again ("Újra elérhető") and every other chat of the listing is told so.
	 * Closed offers stay closed.
	 *
	 * @param int $offer_id  Accepted offer ID.
	 * @param int $seller_id Acting user, must be the listing seller.
	 * @return object|WP_Error Withdrawn offer row.
	 */
	public static function cancel_sale( $offer_id, $seller_id ) {
		global $wpdb;

		$table = KMAE_Database::table( 'chat_offers' );
		$offer = self::get( (int) $offer_id );
		if ( ! $offer || (int) $offer->seller_id !== (int) $seller_id ) {
			return new WP_Error( 'forbidden', __( 'Ezt csak az eladó teheti meg.', 'ki-mit-adna-erte' ) );
		}
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE id = %d AND status = %s", self::STATUS_WITHDRAWN, (int) $offer->id, self::STATUS_ACCEPTED ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $updated ) {
			return new WP_Error( 'sale_not_cancellable', __( 'Ehhez a hirdetéshez nincs elfogadott ajánlat.', 'ki-mit-adna-erte' ) );
		}

		/* „Újra elérhető”: a 60 napos lejárat innen indul újra (KMAE_Listings::expiry_clock_start). */
		update_post_meta( (int) $offer->listing_id, '_kmae_renewed_at', current_time( 'mysql' ) );
		self::post_system_message( (int) $offer->conversation_id, (int) $seller_id, __( 'Az eladó jelezte, hogy az autó mégsem kelt el. A hirdetés újra elérhető.', 'ki-mit-adna-erte' ), 'offer_withdrawn:' . (int) $offer->id );
		foreach ( self::other_conversation_ids( (int) $offer->listing_id, (int) $offer->conversation_id ) as $other_id ) {
			self::post_system_message( $other_id, (int) $seller_id, __( 'A hirdetés újra elérhető.', 'ki-mit-adna-erte' ), 'listing_reopened:' . (int) $offer->id );
		}
		self::purge_page_cache( (int) $offer->listing_id );

		return self::get( (int) $offer->id );
	}

	/**
	 * [Lezárom]: the seller confirms the sale. The accepted offer becomes
	 * `sold` (+1 on the seller's sold counter), the listing closes and its
	 * chats become read-only.
	 *
	 * @param int $offer_id  Accepted offer ID.
	 * @param int $seller_id Acting user, must be the listing seller.
	 * @return object|WP_Error Sold offer row.
	 */
	public static function close_sale( $offer_id, $seller_id ) {
		global $wpdb;

		$table = KMAE_Database::table( 'chat_offers' );
		$offer = self::get( (int) $offer_id );
		if ( ! $offer || (int) $offer->seller_id !== (int) $seller_id ) {
			return new WP_Error( 'forbidden', __( 'Ezt csak az eladó teheti meg.', 'ki-mit-adna-erte' ) );
		}
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE id = %d AND status = %s", self::STATUS_SOLD, (int) $offer->id, self::STATUS_ACCEPTED ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $updated ) {
			return new WP_Error( 'sale_not_closable', __( 'Ehhez a hirdetéshez nincs elfogadott ajánlat.', 'ki-mit-adna-erte' ) );
		}
		self::close_listing( (int) $offer->listing_id );

		return self::get( (int) $offer->id );
	}

	/**
	 * Buyer takes back their own live offer ([Visszavonom]). Not possible after
	 * acceptance: then only the seller's "Mégse kelt el" releases the listing.
	 *
	 * @param int $offer_id Offer ID.
	 * @param int $buyer_id Acting user, must be the offer's buyer.
	 * @return object|WP_Error Retracted offer row.
	 */
	public static function retract( $offer_id, $buyer_id ) {
		global $wpdb;

		$table = KMAE_Database::table( 'chat_offers' );
		$offer = self::get( (int) $offer_id );
		if ( ! $offer || (int) $offer->buyer_id !== (int) $buyer_id ) {
			return new WP_Error( 'forbidden', __( 'Ajánlatot csak az vonhat vissza, aki tette.', 'ki-mit-adna-erte' ) );
		}
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE id = %d AND status = %s", self::STATUS_RETRACTED, (int) $offer->id, self::STATUS_LIVE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $updated ) {
			return new WP_Error( 'offer_not_retractable', __( 'Ez az ajánlat már nem vonható vissza.', 'ki-mit-adna-erte' ) );
		}
		/* Rendszerüzenet a vevő nevében: az eladó ugyanúgy megkapja, mint egy
		   üzenetet (olvasatlan jelvény, e-mail, push, élő frissítés). */
		/* translators: %s: formatted amount */
		$posted = self::post_system_message( (int) $offer->conversation_id, (int) $buyer_id, sprintf( __( 'A vevő visszavonta az ajánlatát (%s).', 'ki-mit-adna-erte' ), KMAE_Seller::format_cash( (int) $offer->amount ) ), 'offer_retracted:' . (int) $offer->id );
		if ( ! $posted ) {
			KMAE_Conversations::touch( (int) $offer->conversation_id );
		}

		return self::get( (int) $offer->id );
	}

	/**
	 * Listing is being deleted (before the post is removed): a "Foglalt"
	 * buyer gets a system message, and every open offer ends as `closed`,
	 * so the 7-day cron never turns the deletion into a sale.
	 *
	 * @param int $listing_id Listing ID.
	 */
	public static function on_listing_deleted( $listing_id ) {
		global $wpdb;

		$table    = KMAE_Database::table( 'chat_offers' );
		$accepted = self::get_accepted_for_listing( (int) $listing_id );
		if ( $accepted ) {
			self::post_system_message( (int) $accepted->conversation_id, (int) $accepted->seller_id, __( 'Az eladó törölte a hirdetést, az elfogadott ajánlatod ezzel megszűnt.', 'ki-mit-adna-erte' ), 'listing_deleted:' . (int) $accepted->id );
		}
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE listing_id = %d AND status IN (%s, %s)", self::STATUS_CLOSED, (int) $listing_id, self::STATUS_LIVE, self::STATUS_ACCEPTED ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Hourly cron: an accepted offer left without [Lezárom] / [Mégse kelt el]
	 * for 7 days lapses. The listing closes and its chats become read-only,
	 * but it is NOT a sale (the sold counter counts `sold` rows only).
	 */
	public static function finalize_sales() {
		global $wpdb;

		$table  = KMAE_Database::table( 'chat_offers' );
		$offers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s AND accepted_at IS NOT NULL AND accepted_at <= DATE_SUB(NOW(), INTERVAL %d DAY)", self::STATUS_ACCEPTED, self::SOLD_AFTER_DAYS ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $offers as $offer ) {
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = NOW() WHERE id = %d AND status = %s", self::STATUS_LAPSED, (int) $offer->id, self::STATUS_ACCEPTED ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $updated ) {
				continue;
			}
			self::close_listing( (int) $offer->listing_id );
		}
	}

	/**
	 * Active listing → closed (not public), every chat on it read-only.
	 *
	 * @param int $listing_id Listing ID.
	 */
	private static function close_listing( $listing_id ) {
		if ( ! KMAE_Conversations::listing_is_deleted( (int) $listing_id ) && KMAE_Meta::STATUS_ACTIVE === KMAE_Meta::get_status( (int) $listing_id ) ) {
			update_post_meta( (int) $listing_id, '_kmae_status', KMAE_Meta::STATUS_CLOSED );
		}
		KMAE_Conversations::close_for_listing( (int) $listing_id );
		/* Eladás / lejárt Foglalt: a kereskedői megkeresések nyitott sorai megszűnnek (néma jelzés a cégeknek). */
		if ( class_exists( 'KMAE_Dealer_Leads' ) ) {
			KMAE_Dealer_Leads::on_listing_ended( (int) $listing_id );
		}
		self::purge_page_cache( (int) $listing_id );
	}

	/**
	 * The „Foglalt” state lives in this table, not in post meta, so LiteSpeed
	 * does not purge on its own: the listing page and every browse page
	 * (tag `kmae_browse`, KMAE_Shortcodes::browse) must be refreshed by hand.
	 *
	 * @param int $listing_id Listing ID.
	 */
	private static function purge_page_cache( $listing_id ) {
		/* Böngészés v2 index: a Foglalt oszlop újraszámolása (KMAE_Browse_Index). */
		do_action( 'kmae_listing_reserved_changed', (int) $listing_id );
		do_action( 'litespeed_purge_post', (int) $listing_id );
		do_action( 'litespeed_purge', self::BROWSE_CACHE_TAG );
	}

	/**
	 * Open chats of a listing except one: everyone who wrote or made an offer.
	 *
	 * @param int $listing_id      Listing ID.
	 * @param int $conversation_id Conversation to skip (the accepted buyer's).
	 * @return int[]
	 */
	private static function other_conversation_ids( $listing_id, $conversation_id ) {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE listing_id = %d AND status = %s AND id <> %d', (int) $listing_id, KMAE_Conversations::STATUS_OPEN, (int) $conversation_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Number of cars the seller sold through the site.
	 *
	 * @param int $seller_id Seller user ID.
	 * @return int
	 */
	public static function count_sold_for_seller( $seller_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE seller_id = %d AND status = %s", (int) $seller_id, self::STATUS_SOLD ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * "5 eladott autó"
	 *
	 * @param int $count Sold count.
	 * @return string
	 */
	public static function sold_label( $count ) {
		/* translators: %s: number of sold cars */
		return sprintf( __( '%s eladott autó', 'ki-mit-adna-erte' ), number_format_i18n( (int) $count ) );
	}

	/**
	 * System notice in a chat, stored as a message of the acting party (the
	 * other party is notified) so the inbox, e-mail, push and live refresh
	 * work unchanged.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $author_id       Message author (seller, or buyer on retract).
	 * @param string $text            Notice text.
	 * @param string $key             Unique event key (without the `system:` prefix).
	 * @return bool
	 */
	public static function post_system_message( $conversation_id, $author_id, $text, $key ) {
		$message = KMAE_Conversations::save_message( (int) $conversation_id, (int) $author_id, $text, self::SYSTEM_KEY . $key, 0, true );
		if ( is_wp_error( $message ) || ! $message ) {
			return false;
		}
		do_action( 'kmae_conversation_message_created', (int) $message->id, (int) $conversation_id, (int) $author_id );
		return true;
	}

	/**
	 * What a chat message is, for the notice texts (e-mail, push).
	 *
	 * @param int $message_id Message ID.
	 * @return string offer|offer_retracted|dealer_lead|system|message
	 */
	public static function message_kind( $message_id ) {
		$message = KMAE_Conversations::get_message( (int) $message_id );
		$key     = (string) ( $message->idempotency_key ?? '' );
		if ( 0 === strpos( $key, 'offer:' ) ) {
			return 'offer';
		}
		if ( 0 === strpos( $key, self::SYSTEM_KEY . 'offer_retracted:' ) ) {
			return 'offer_retracted';
		}
		/* Kereskedő „Érdekel”-je (KMAE_Dealer_Leads::answer), a kereskedő nevében. */
		if ( 0 === strpos( $key, self::SYSTEM_KEY . 'dealer_lead:' ) ) {
			return 'dealer_lead';
		}
		return 0 === strpos( $key, self::SYSTEM_KEY ) ? 'system' : 'message';
	}

	/**
	 * Whether a chat message is a system notice.
	 *
	 * @param object $message Message row.
	 * @return bool
	 */
	public static function is_system_message( $message ) {
		return 0 === strpos( (string) ( $message->idempotency_key ?? '' ), self::SYSTEM_KEY );
	}

	/**
	 * One offer row.
	 *
	 * @param int $offer_id Offer ID.
	 * @return object|null
	 */
	public static function get( $offer_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $offer_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * The accepted offer of a listing ("Foglalt"), if any.
	 *
	 * @param int $listing_id Listing ID.
	 * @return object|null
	 */
	public static function get_accepted_for_listing( $listing_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE listing_id = %d AND status = %s ORDER BY id DESC LIMIT 1", (int) $listing_id, self::STATUS_ACCEPTED ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Every listing that is „Foglalt” right now (one query for a whole card list).
	 *
	 * @return array<int,true> Listing ID => true.
	 */
	public static function reserved_listing_ids() {
		global $wpdb;
		static $ids = null;
		if ( null === $ids ) {
			$table = KMAE_Database::table( 'chat_offers' );
			$ids   = array_fill_keys( array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT listing_id FROM {$table} WHERE status = %s", self::STATUS_ACCEPTED ) ) ), true ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return $ids;
	}

	/**
	 * Listing sale state: 'sold' (Eladva), 'reserved' (Foglalt), 'lapsed'
	 * (7 nap után lezárult, nem eladás), 'reopened' (Újra elérhető) or ''.
	 *
	 * @param int $listing_id Listing ID.
	 * @return string
	 */
	public static function listing_state( $listing_id ) {
		global $wpdb;
		if ( self::get_accepted_for_listing( (int) $listing_id ) ) {
			return 'reserved';
		}
		$table = KMAE_Database::table( 'chat_offers' );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE listing_id = %d AND status = %s LIMIT 1", (int) $listing_id, self::STATUS_SOLD ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return 'sold';
		}
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE listing_id = %d AND status = %s LIMIT 1", (int) $listing_id, self::STATUS_LAPSED ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return 'lapsed';
		}
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE listing_id = %d AND status = %s LIMIT 1", (int) $listing_id, self::STATUS_WITHDRAWN ) ) ? 'reopened' : ''; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Offers of one conversation keyed by their chat message ID.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array<int,object>
	 */
	public static function get_by_message( $conversation_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE conversation_id = %d", (int) $conversation_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$map   = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->message_id ] = $row;
		}
		return $map;
	}

	/**
	 * The buyer's live offer in a conversation, if any.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return object|null
	 */
	public static function get_live_for_conversation( $conversation_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE conversation_id = %d AND status = %s ORDER BY id DESC LIMIT 1", (int) $conversation_id, self::STATUS_LIVE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Newest offer of each conversation (Üzenetek lista állapotcímke), one query.
	 *
	 * @param int[] $conversation_ids Conversation IDs.
	 * @return array<int,object> Conversation ID => offer row.
	 */
	public static function latest_by_conversation( $conversation_ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', (array) $conversation_ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$table = KMAE_Database::table( 'chat_offers' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} WHERE conversation_id IN (" . implode( ',', $ids ) . ') ORDER BY id ASC' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$map   = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->conversation_id ] = $row;
		}
		return $map;
	}

	/**
	 * Number of live offers on a listing (one per buyer). Amounts stay private.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int
	 */
	public static function count_live_for_listing( $listing_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE listing_id = %d AND status = %s", (int) $listing_id, self::STATUS_LIVE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Every offer change of a seller in one string (live page fingerprint of
	 * „Saját hirdetéseim”): send, retract, replace, accept, cancel, sold.
	 *
	 * @param int $seller_id Seller ID.
	 * @return string
	 */
	public static function seller_signature( $seller_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'chat_offers' );
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(id), 0), ':', COALESCE(SUM(status = %s), 0), ':', COALESCE(MAX(updated_at), '')) FROM {$table} WHERE seller_id = %d", self::STATUS_LIVE, (int) $seller_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * "4 ajánlat érkezett"
	 *
	 * @param int $count Offer count.
	 * @return string
	 */
	public static function count_label( $count ) {
		/* translators: %s: number of offers */
		return sprintf( __( '%s ajánlat érkezett', 'ki-mit-adna-erte' ), number_format_i18n( (int) $count ) );
	}
}
