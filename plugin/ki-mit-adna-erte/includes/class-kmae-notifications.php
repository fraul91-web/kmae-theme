<?php
/**
 * Email notifications.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Notifications {

	/**
	 * When true, chat mails are skipped. Listing-delete regret mail uses
	 * Brand::send_mail directly.
	 *
	 * @var bool
	 */
	private static $silence_offer_pipeline = false;

	/**
	 * Mute chat emails while a listing is being deleted.
	 *
	 * @param bool $on Mute on/off.
	 */
	public static function silence_offer_pipeline( $on ) {
		self::$silence_offer_pipeline = (bool) $on;
	}

	public static function init() {
		add_action( 'kmae_conversation_message_created', array( __CLASS__, 'on_conversation_message_created' ), 10, 3 );
		add_action( 'kmae_listing_published', array( __CLASS__, 'on_listing_published' ) );
		add_action( 'kmae_remind_unfinished_listings', array( __CLASS__, 'remind_unfinished_listings' ) );
	}

	/**
	 * Kapcsolható levelek. A kulcs a mentett kapcsoló; ami nincs a listában
	 * (megerősítés, jelszó, e-mail csere), az mindig kimegy.
	 *
	 * @return array<int, array{id:string,label:string,items:array<int,array{key:string,label:string,hint:string}>}>
	 */
	public static function mail_catalog() {
		return array(
			array(
				'id'    => 'listing',
				'label' => __( 'Hirdetés', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'key'   => 'listing_active',
						'label' => __( 'A hirdetésed aktív lett', 'ki-mit-adna-erte' ),
						'hint'  => __( 'Amikor kikerül a böngészésbe.', 'ki-mit-adna-erte' ),
					),
					array(
						'key'   => 'listing_unfinished',
						'label' => __( '24 órája félbemaradt hirdetés', 'ki-mit-adna-erte' ),
						'hint'  => __( 'Ha egy napja nem nyúltál a piszkozathoz vagy a fotóra váró hirdetéshez.', 'ki-mit-adna-erte' ),
					),
				),
			),
			array(
				'id'    => 'message',
				'label' => __( 'Üzenet', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'key'   => 'message',
						'label' => __( 'Új üzenet a beszélgetésben', 'ki-mit-adna-erte' ),
						'hint'  => __( 'Amikor a másik fél ír neked a chatben.', 'ki-mit-adna-erte' ),
					),
				),
			),
			array(
				'id'    => 'other',
				'label' => __( 'Egyéb', 'ki-mit-adna-erte' ),
				'items' => array(
					array(
						'key'   => 'listing_deleted',
						'label' => __( 'A hirdetés törölve lett', 'ki-mit-adna-erte' ),
						'hint'  => __( 'Ha egy hirdetés, amiben benne voltál, eltűnik.', 'ki-mit-adna-erte' ),
					),
				),
			),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public static function mail_catalog_keys() {
		$keys = array();
		foreach ( self::mail_catalog() as $group ) {
			foreach ( $group['items'] as $item ) {
				$keys[] = $item['key'];
			}
		}
		return $keys;
	}

	/**
	 * Hiányzó kulcs bekapcsolt. Csak a mentett 0 kapcsolja ki.
	 *
	 * @param int $user_id User.
	 * @return array<string, bool>
	 */
	public static function mail_prefs( $user_id ) {
		$saved = get_user_meta( (int) $user_id, 'kmae_mail_prefs', true );
		if ( is_string( $saved ) && '' !== $saved ) {
			$saved = json_decode( $saved, true );
		}
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$prefs = array();
		foreach ( self::mail_catalog_keys() as $key ) {
			$prefs[ $key ] = ! array_key_exists( $key, $saved ) || ! empty( $saved[ $key ] );
		}
		return $prefs;
	}

	/**
	 * @param int    $user_id User.
	 * @param string $key     Catalog key. Unknown keys always send.
	 */
	public static function allows_mail( $user_id, $key ) {
		$key = sanitize_key( (string) $key );
		if ( ! in_array( $key, self::mail_catalog_keys(), true ) ) {
			return true;
		}
		$prefs = self::mail_prefs( $user_id );
		return ! empty( $prefs[ $key ] );
	}

	/**
	 * @param int               $user_id User.
	 * @param array<string,mixed> $posted Checkbox map. Missing key means off.
	 */
	public static function save_mail_prefs( $user_id, $posted ) {
		$posted = is_array( $posted ) ? $posted : array();
		$out    = array();
		foreach ( self::mail_catalog_keys() as $key ) {
			$out[ $key ] = empty( $posted[ $key ] ) ? 0 : 1;
		}
		update_user_meta( (int) $user_id, 'kmae_mail_prefs', wp_json_encode( $out ) );
	}

	public static function on_conversation_message_created( $message_id, $conversation_id, $sender_id ) {
		$conversation = KMAE_Conversations::get( (int) $conversation_id );
		if ( ! $conversation || ! KMAE_Conversations::is_member( $conversation, (int) $sender_id ) ) {
			return;
		}
		$recipient_id = (int) $conversation->buyer_id === (int) $sender_id
			? (int) $conversation->seller_id
			: (int) $conversation->buyer_id;
		/* Letiltott feladó: a tiltó nem kap levelet. */
		if ( KMAE_User_Blocks::hides_message( (int) $message_id, $recipient_id ) ) {
			return;
		}
		$title        = self::get_listing_title( (int) $conversation->listing_id );
		$kind         = KMAE_Chat_Offers::message_kind( (int) $message_id );

		if ( 'offer' === $kind ) {
			$message = KMAE_Conversations::get_message( (int) $message_id );
			self::send_offer_email(
				$recipient_id,
				sprintf( __( 'Ajánlatot kaptál: %s', 'ki-mit-adna-erte' ), $title ),
				sprintf(
					__( "Szia!\n\nAjánlat érkezett a „%1\$s” hirdetésedre.\n\n%2\$s\n\n— Albira", 'ki-mit-adna-erte' ),
					$title,
					$message ? (string) $message->message_text : ''
				),
				self::thread_email_args(
					__( 'Új ajánlat', 'ki-mit-adna-erte' ),
					__( 'Ajánlatot kaptál!', 'ki-mit-adna-erte' ),
					(int) $conversation->id,
					'cheer'
				),
				'message'
			);
			return;
		}

		if ( 'offer_retracted' === $kind ) {
			self::send_offer_email(
				$recipient_id,
				sprintf( __( 'Ajánlat visszavonva: %s', 'ki-mit-adna-erte' ), $title ),
				sprintf(
					__( "Szia!\n\nA vevő visszavonta az ajánlatát a „%s” hirdetésednél.\n\n— Albira", 'ki-mit-adna-erte' ),
					$title
				),
				self::thread_email_args(
					__( 'Ajánlat', 'ki-mit-adna-erte' ),
					__( 'Visszavonták az ajánlatot', 'ki-mit-adna-erte' ),
					(int) $conversation->id,
					'sad'
				),
				'message'
			);
			return;
		}

		self::send_offer_email(
			$recipient_id,
			sprintf( __( 'Új üzenet: %s', 'ki-mit-adna-erte' ), $title ),
			sprintf(
				__( "Szia!\n\nÚj üzeneted érkezett a „%s” hirdetés beszélgetésében.\n\n— Albira", 'ki-mit-adna-erte' ),
				$title
			),
			self::thread_email_args(
				__( 'Új üzenet', 'ki-mit-adna-erte' ),
				__( 'Üzeneted érkezett', 'ki-mit-adna-erte' ),
				(int) $conversation->id
			),
			'message'
		);
	}

	/**
	 * After a listing is deleted: one regret mail to each interested party
	 * (conversation), and one acknowledgment to the listing author.
	 *
	 * @param int           $listing_id Listing ID.
	 * @param WP_Post       $listing    Listing, still loaded.
	 * @param int           $actor_id   User who deleted the listing.
	 */
	public static function on_listing_deleted( $listing_id, $listing, $actor_id = 0 ) {
		if ( ! $listing ) {
			return;
		}

		$actor_id  = (int) $actor_id > 0 ? (int) $actor_id : (int) get_current_user_id();
		$seller_id = (int) $listing->post_author;
		$title     = $listing->post_title;
		$browse    = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
		$sad_wrap  = array(
			'tone'         => 'sad',
			'eyebrow'      => __( 'Hirdetés törölve', 'ki-mit-adna-erte' ),
			'button_label' => __( 'Autók böngészése', 'ki-mit-adna-erte' ),
			'button_url'   => $browse,
		);

		$interested = self::listing_party_ids( $listing_id );
		unset( $interested[ $actor_id ], $interested[ $seller_id ] );

		$buyer_wrap            = $sad_wrap;
		$buyer_wrap['title']   = __( 'Sajnáljuk, ez a hirdetés már nincs', 'ki-mit-adna-erte' );
		$buyer_subject         = sprintf(
			/* translators: %s: listing title */
			__( 'Sajnáljuk, a hirdetés törölve lett: %s', 'ki-mit-adna-erte' ),
			$title
		);

		foreach ( array_keys( $interested ) as $user_id ) {
			$user = get_userdata( (int) $user_id );
			if ( ! $user || ! is_email( $user->user_email ) ) {
				continue;
			}
			if ( ! self::allows_mail( (int) $user_id, 'listing_deleted' ) ) {
				continue;
			}

			$name  = class_exists( 'KMAE_Auth' ) ? KMAE_Auth::first_name( (int) $user_id ) : '';
			$greet = '' !== $name
				/* translators: %s: first name */
				? sprintf( __( 'Szia, %s!', 'ki-mit-adna-erte' ), $name )
				: __( 'Szia!', 'ki-mit-adna-erte' );

			$body = $greet . "\n\n" . sprintf(
				/* translators: %s: listing title */
				__( 'Sajnáljuk — a „%s” hirdetés, amire érdeklődést mutattál, sajnos törölve lett.', 'ki-mit-adna-erte' ),
				$title
			) . "\n\n" . __(
				'A hozzá tartozó beszélgetés ezzel lezárult.',
				'ki-mit-adna-erte'
			) . "\n\n" . __(
				'Ha szeretnél, böngéssz tovább — hátha találsz másik autót.',
				'ki-mit-adna-erte'
			) . "\n\n— Albira";

			KMAE_Brand::send_mail( $user->user_email, $buyer_subject, $body, array(), $buyer_wrap );
		}

		if ( $seller_id <= 0 ) {
			return;
		}
		if ( ! self::allows_mail( $seller_id, 'listing_deleted' ) ) {
			return;
		}

		$seller = get_userdata( $seller_id );
		if ( ! $seller || ! is_email( $seller->user_email ) ) {
			return;
		}

		$name  = class_exists( 'KMAE_Auth' ) ? KMAE_Auth::first_name( $seller_id ) : '';
		$greet = '' !== $name
			/* translators: %s: first name */
			? sprintf( __( 'Szia, %s!', 'ki-mit-adna-erte' ), $name )
			: __( 'Szia!', 'ki-mit-adna-erte' );

		$seller_deleted = $actor_id > 0 && $actor_id === $seller_id;
		$seller_wrap            = $sad_wrap;
		$seller_wrap['title']   = $seller_deleted
			? __( 'Törölted a hirdetésed', 'ki-mit-adna-erte' )
			: __( 'A hirdetésed törölve lett', 'ki-mit-adna-erte' );
		$seller_subject         = sprintf(
			/* translators: %s: listing title */
			__( 'Hirdetés törölve: %s — Albira', 'ki-mit-adna-erte' ),
			$title
		);

		if ( $seller_deleted ) {
			$body = $greet . "\n\n" . sprintf(
				/* translators: %s: listing title */
				__( 'Megkaptuk: a „%s” hirdetésedet törölted. Sajnáljuk, hogy így alakult.', 'ki-mit-adna-erte' ),
				$title
			) . "\n\n" . __(
				'A hozzá tartozó beszélgetések ezzel lezárultak.',
				'ki-mit-adna-erte'
			) . "\n\n" . __(
				'Ha később újra feltennéd, bármikor készíthetsz új hirdetést.',
				'ki-mit-adna-erte'
			) . "\n\n— Albira";
		} else {
			$body = $greet . "\n\n" . sprintf(
				/* translators: %s: listing title */
				__( 'A „%s” hirdetésed törölve lett. Sajnáljuk, hogy így alakult.', 'ki-mit-adna-erte' ),
				$title
			) . "\n\n" . __(
				'A hozzá tartozó beszélgetések ezzel lezárultak.',
				'ki-mit-adna-erte'
			) . "\n\n— Albira";
		}

		KMAE_Brand::send_mail( $seller->user_email, $seller_subject, $body, array(), $seller_wrap );
	}

	private static function get_listing_title( $listing_id ) {
		$listing = get_post( (int) $listing_id );
		return $listing ? $listing->post_title : __( 'Járműhirdetés', 'ki-mit-adna-erte' );
	}

	private static function thread_email_args( $eyebrow, $title, $conversation_id = 0, $tone = 'info' ) {
		$url = (int) $conversation_id ? KMAE_Pages::conversation_thread_url( (int) $conversation_id ) : '';
		return array(
			'tone'         => $tone,
			'eyebrow'      => $eyebrow,
			'title'        => $title,
			'button_label' => $url ? __( 'Beszélgetés megnyitása', 'ki-mit-adna-erte' ) : '',
			'button_url'   => $url,
		);
	}

	/**
	 * Buyer and seller ids of every conversation on one listing.
	 *
	 * @param int $listing_id Listing.
	 * @return array<int,true>
	 */
	private static function listing_party_ids( $listing_id ) {
		global $wpdb;
		$ids = array();

		$members = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT buyer_id, seller_id FROM ' . KMAE_Database::table( 'conversations' ) . ' WHERE listing_id = %d',
				(int) $listing_id
			)
		);
		foreach ( (array) $members as $member ) {
			foreach ( array( (int) $member->buyer_id, (int) $member->seller_id ) as $user_id ) {
				if ( $user_id > 0 ) {
					$ids[ $user_id ] = true;
				}
			}
		}

		return $ids;
	}

	/**
	 * Security notice after a password change. Goes to the account address.
	 *
	 * @param int $user_id User.
	 */
	public static function notify_password_changed( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		$contact = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'kapcsolat' ) : home_url( '/kapcsolat/' );
		self::send_security_mail(
			$user->user_email,
			(int) $user_id,
			__( 'A jelszavad megváltozott — Albira', 'ki-mit-adna-erte' ),
			__( "A fiókod jelszava az imént megváltozott.\n\nHa te kérted, nincs teendőd. Ha nem te voltál, azonnal írj nekünk a Kapcsolat oldalon.", 'ki-mit-adna-erte' ),
			__( 'Biztonság', 'ki-mit-adna-erte' ),
			__( 'A jelszavad megváltozott', 'ki-mit-adna-erte' ),
			__( 'Kapcsolat', 'ki-mit-adna-erte' ),
			$contact
		);
	}

	/**
	 * Security notice to the previous address and a confirmation to the new one.
	 *
	 * @param int    $user_id   User.
	 * @param string $old_email Address before the change.
	 * @param string $new_email Address after the change.
	 */
	public static function notify_email_changed( $user_id, $old_email, $new_email ) {
		$old_email = sanitize_email( $old_email );
		$new_email = sanitize_email( $new_email );
		$contact   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'kapcsolat' ) : home_url( '/kapcsolat/' );
		if ( is_email( $old_email ) ) {
			self::send_security_mail(
				$old_email,
				(int) $user_id,
				__( 'A belépési e-mail címed megváltozott — Albira', 'ki-mit-adna-erte' ),
				sprintf(
					__( "A fiókod belépési e-mail címe erre változott: %s.\n\nHa te kérted, nincs teendőd. Ha nem te voltál, azonnal írj nekünk a Kapcsolat oldalon.", 'ki-mit-adna-erte' ),
					$new_email
				),
				__( 'Biztonság', 'ki-mit-adna-erte' ),
				__( 'Az e-mail címed megváltozott', 'ki-mit-adna-erte' ),
				__( 'Kapcsolat', 'ki-mit-adna-erte' ),
				$contact
			);
		}
		if ( is_email( $new_email ) && strtolower( $new_email ) !== strtolower( $old_email ) ) {
			self::send_security_mail(
				$new_email,
				(int) $user_id,
				__( 'Ez az új belépési e-mail címed — Albira', 'ki-mit-adna-erte' ),
				__( "Mostantól ezzel az e-mail címmel lépsz be az Albirára.\n\nHa nem te kérted a cserét, azonnal írj nekünk a Kapcsolat oldalon.", 'ki-mit-adna-erte' ),
				__( 'Biztonság', 'ki-mit-adna-erte' ),
				__( 'Új belépési e-mail cím', 'ki-mit-adna-erte' ),
				__( 'Kapcsolat', 'ki-mit-adna-erte' ),
				$contact
			);
		}
	}

	/**
	 * @param string $email      Recipient address.
	 * @param int    $user_id    Account, for the greeting.
	 * @param string $subject    Subject.
	 * @param string $body       Plain body without greeting.
	 * @param string $eyebrow    Eyebrow.
	 * @param string $title      Title.
	 * @param string $button     Button label.
	 * @param string $button_url Button URL.
	 */
	private static function send_security_mail( $email, $user_id, $subject, $body, $eyebrow, $title, $button, $button_url ) {
		$name = class_exists( 'KMAE_Auth' ) ? KMAE_Auth::first_name( (int) $user_id ) : '';
		if ( '' !== $name ) {
			$name = mb_strtoupper( $name[0] ) . mb_substr( $name, 1 );
			$body = sprintf( __( 'Szia %s!', 'ki-mit-adna-erte' ), $name ) . "\n\n" . $body;
		} else {
			$body = __( 'Szia!', 'ki-mit-adna-erte' ) . "\n\n" . $body;
		}
		$body .= "\n\n— Albira";
		KMAE_Brand::send_mail(
			$email,
			$subject,
			$body,
			array(),
			array(
				'tone'         => 'info',
				'eyebrow'      => $eyebrow,
				'title'        => $title,
				'button_label' => $button,
				'button_url'   => $button_url,
			)
		);
	}

	/**
	 * A hirdetés kikerült a böngészésbe.
	 *
	 * @param int $listing_id Listing.
	 */
	public static function on_listing_published( $listing_id ) {
		$listing = get_post( (int) $listing_id );
		if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
			return;
		}
		if ( ! class_exists( 'KMAE_Meta' ) || KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $listing->ID ) ) {
			return;
		}

		$title = $listing->post_title ? $listing->post_title : __( 'Hirdetés', 'ki-mit-adna-erte' );
		$url   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'sajat-hirdetesek' ) : home_url( '/sajat-hirdetesek/' );
		self::send_offer_email(
			(int) $listing->post_author,
			sprintf( __( 'A hirdetésed kint van: %s', 'ki-mit-adna-erte' ), $title ),
			sprintf(
				__( "Szia!\n\nA „%s” hirdetésed aktív. Innentől látszik a böngészésben.\n\n— Albira", 'ki-mit-adna-erte' ),
				$title
			),
			array(
				'tone'         => 'cheer',
				'eyebrow'      => __( 'Hirdetés', 'ki-mit-adna-erte' ),
				'title'        => __( 'Kikerült a böngészésbe', 'ki-mit-adna-erte' ),
				'button_label' => __( 'Hirdetéseim', 'ki-mit-adna-erte' ),
				'button_url'   => $url,
			),
			'listing_active'
		);
	}

	/**
	 * A hirdetés 60 nap után lejárt: hosszabbítási levél az eladónak.
	 * Mindig megy (nem kapcsolható ki), mert enélkül a hirdetés csendben eltűnne.
	 *
	 * @param int $listing_id Listing.
	 */
	public static function notify_listing_expired( $listing_id ) {
		$listing = get_post( (int) $listing_id );
		if ( ! $listing || 'kmae_listing' !== $listing->post_type || (int) $listing->post_author <= 0 ) {
			return;
		}

		$title = $listing->post_title ? $listing->post_title : __( 'Hirdetés', 'ki-mit-adna-erte' );
		$url   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'sajat-hirdetesek' ) : home_url( '/sajat-hirdetesek/' );
		self::send_offer_email(
			(int) $listing->post_author,
			sprintf( __( 'Lejárt a hirdetésed: %s', 'ki-mit-adna-erte' ), $title ),
			sprintf(
				__( "Szia!\n\nA „%1\$s” hirdetésed %2\$d napja fut, és nem fogadtál el rá ajánlatot, ezért lejárt. Most nem látszik a böngészésben.\n\nHa még eladó, a hirdetéseid között a „Hosszabbítás” gombbal újabb %2\$d napra visszateheted. Ha már nem kell, ugyanott törölheted.\n\n— Albira", 'ki-mit-adna-erte' ),
				$title,
				KMAE_Listings::EXPIRE_DAYS
			),
			array(
				'tone'         => 'sad',
				'eyebrow'      => __( 'Hirdetés', 'ki-mit-adna-erte' ),
				'title'        => __( 'Lejárt a hirdetésed', 'ki-mit-adna-erte' ),
				'button_label' => __( 'Hosszabbítás', 'ki-mit-adna-erte' ),
				'button_url'   => $url,
			)
		);
	}

	/**
	 * 24 órája nem nyúltak a félbehagyott hirdetéshez. Egy hirdetésre
	 * egy emlékeztető megy, amíg újra nem szerkesztik.
	 */
	public static function remind_unfinished_listings() {
		$ids = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => array( 'draft', 'publish' ),
				'posts_per_page' => 40,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'date_query'     => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => '24 hours ago',
					),
				),
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_kmae_status',
						'value' => KMAE_Meta::STATUS_DRAFT,
					),
					array(
						'key'   => '_kmae_status',
						'value' => KMAE_Meta::STATUS_AWAITING_PHOTOS,
					),
				),
			)
		);

		$url = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'sajat-hirdetesek' ) : home_url( '/sajat-hirdetesek/' );
		foreach ( $ids as $listing_id ) {
			$listing_id = (int) $listing_id;
			$listing    = get_post( $listing_id );
			if ( ! $listing ) {
				continue;
			}
			$modified = (int) get_post_modified_time( 'U', true, $listing );
			$sent     = (int) get_post_meta( $listing_id, '_kmae_unfinished_mail_at', true );
			if ( $sent && $sent >= $modified ) {
				continue;
			}

			$author = (int) $listing->post_author;
			update_post_meta( $listing_id, '_kmae_unfinished_mail_at', time() );
			if ( $author <= 0 || ! self::allows_mail( $author, 'listing_unfinished' ) ) {
				continue;
			}

			$title = $listing->post_title ? $listing->post_title : __( 'Hirdetés', 'ki-mit-adna-erte' );
			self::send_offer_email(
				$author,
				sprintf( __( 'Még nem fejezted be: %s', 'ki-mit-adna-erte' ), $title ),
				sprintf(
					__( "Szia!\n\nA „%s” hirdetésed 24 órája félbemaradt. Ha befejezed, kikerülhet a böngészésbe. Ha már nem kell, a hirdetéseid között törölheted.\n\n— Albira", 'ki-mit-adna-erte' ),
					$title
				),
				array(
					'tone'         => 'info',
					'eyebrow'      => __( 'Félbehagyott hirdetés', 'ki-mit-adna-erte' ),
					'title'        => __( '24 órája vár rád', 'ki-mit-adna-erte' ),
					'button_label' => __( 'Folytatom', 'ki-mit-adna-erte' ),
					'button_url'   => $url,
				),
				'listing_unfinished'
			);
		}
	}

	private static function send_offer_email( $user_id, $subject, $body, $wrap_args = array(), $mail_key = '' ) {
		if ( self::$silence_offer_pipeline ) {
			return false;
		}
		$mail_key = sanitize_key( (string) $mail_key );
		if ( '' !== $mail_key && ! self::allows_mail( (int) $user_id, $mail_key ) ) {
			return false;
		}
		if ( isset( $wrap_args['mail_key'] ) ) {
			unset( $wrap_args['mail_key'] );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$name = KMAE_Auth::first_name( $user_id );
		if ( '' !== $name ) {
			$name  = mb_strtoupper( $name[0] ) . mb_substr( $name, 1 );
			$greeting = sprintf( __( 'Szia %s!', 'ki-mit-adna-erte' ), $name );
			$body = $greeting . "\n\n" . preg_replace( '/^Szia!\s*\n\n/', '', $body );
		}

		return KMAE_Brand::send_mail( $user->user_email, $subject, $body, array(), $wrap_args );
	}
}
