<?php
/**
 * Bejelentések: a felhasználó egy hirdetést vagy egy chat-partnert jelent be
 * (csalás gyanú, EU-s bejelentési kötelezettség). Az admin az Albira →
 * Jelentések oldalon nézi meg, és dönti el, igaz-e.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Reports {

	const CONTEXT_LISTING = 'listing';
	const CONTEXT_CHAT    = 'chat';

	const STATUS_NEW     = 'new';
	const STATUS_VALID   = 'valid';
	const STATUS_INVALID = 'invalid';

	const REASON_OTHER       = 'other';
	const DETAILS_MAX_LENGTH = 2000;
	const NOTE_MAX_LENGTH    = 2000;

	/**
	 * Választható okok helyenként (kulcs → felirat). Az „other” mindig az utolsó.
	 *
	 * @param string $context listing|chat.
	 * @return array<string,string>
	 */
	public static function reasons( $context ) {
		if ( self::CONTEXT_CHAT === $context ) {
			return array(
				'fraud'            => __( 'Csalásra gyanakszom', 'ki-mit-adna-erte' ),
				'advance_pay'      => __( 'Előleget vagy pénzt kér előre', 'ki-mit-adna-erte' ),
				'fake_identity'    => __( 'Nem az, akinek mondja magát', 'ki-mit-adna-erte' ),
				'harassment'       => __( 'Zaklat vagy sértő hangnemben ír', 'ki-mit-adna-erte' ),
				'spam'             => __( 'Kéretlen reklám, spam', 'ki-mit-adna-erte' ),
				self::REASON_OTHER => __( 'Más', 'ki-mit-adna-erte' ),
			);
		}
		return array(
			'fraud'            => __( 'Csalásra gyanakszom', 'ki-mit-adna-erte' ),
			'fake_listing'     => __( 'Az autó nem létezik, kamu hirdetés', 'ki-mit-adna-erte' ),
			'misleading'       => __( 'Hamis vagy félrevezető adatok, képek', 'ki-mit-adna-erte' ),
			'stolen'           => __( 'Lopott vagy illegális jármű gyanúja', 'ki-mit-adna-erte' ),
			'offensive'        => __( 'Sértő vagy nem odaillő tartalom', 'ki-mit-adna-erte' ),
			self::REASON_OTHER => __( 'Más', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * A hirdetés egy képére vonatkozó okok (böngészés „Jelentés” ablak). A hirdetés-
	 * bejelentésnél (context listing) a reasons() mellett ezek is elfogadottak; az
	 * érintett kép sorszáma és címe a leírás végére kerül (submit, `photo`).
	 *
	 * @return array<string,string>
	 */
	public static function photo_reasons() {
		return array(
			'photo_other_car' => __( 'A kép nem ezt az autót mutatja', 'ki-mit-adna-erte' ),
			'photo_stock'     => __( 'Internetről letöltött vagy katalógus kép', 'ki-mit-adna-erte' ),
			'photo_offensive' => __( 'Sértő vagy nem odaillő kép', 'ki-mit-adna-erte' ),
			'photo_personal'  => __( 'Személyes adat látszik rajta (arc, irat)', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Felirat egy tárolt okhoz (bármelyik helyről).
	 */
	public static function reason_label( $reason ) {
		$all = array_merge( self::reasons( self::CONTEXT_LISTING ), self::reasons( self::CONTEXT_CHAT ), self::photo_reasons() );
		return $all[ $reason ] ?? (string) $reason;
	}

	/**
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			self::STATUS_NEW     => __( 'Új', 'ki-mit-adna-erte' ),
			self::STATUS_VALID   => __( 'Igaz', 'ki-mit-adna-erte' ),
			self::STATUS_INVALID => __( 'Nem igaz', 'ki-mit-adna-erte' ),
		);
	}

	/**
	 * Bejelentés mentése.
	 *
	 * @param array $args {context, listing_id, conversation_id, reporter_id, reason, details}.
	 * @return object|WP_Error Mentett sor.
	 */
	public static function submit( $args ) {
		global $wpdb;

		$context         = (string) ( $args['context'] ?? '' );
		$reporter_id     = (int) ( $args['reporter_id'] ?? 0 );
		$listing_id      = (int) ( $args['listing_id'] ?? 0 );
		$conversation_id = (int) ( $args['conversation_id'] ?? 0 );
		$reason          = sanitize_key( (string) ( $args['reason'] ?? '' ) );
		$details         = trim( sanitize_textarea_field( (string) ( $args['details'] ?? '' ) ) );

		if ( $reporter_id <= 0 || ! in_array( $context, array( self::CONTEXT_LISTING, self::CONTEXT_CHAT ), true ) ) {
			return new WP_Error( 'forbidden', __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}
		$is_photo = self::CONTEXT_LISTING === $context && array_key_exists( $reason, self::photo_reasons() );
		if ( ! $is_photo && ! array_key_exists( $reason, self::reasons( $context ) ) ) {
			return new WP_Error( 'report_reason_missing', __( 'Válaszd ki a bejelentés okát.', 'ki-mit-adna-erte' ) );
		}
		if ( self::REASON_OTHER === $reason && '' === $details ) {
			return new WP_Error( 'report_details_missing', __( 'A „Más” oknál írd le, mi történt.', 'ki-mit-adna-erte' ) );
		}
		if ( mb_strlen( $details ) > self::DETAILS_MAX_LENGTH ) {
			$details = mb_substr( $details, 0, self::DETAILS_MAX_LENGTH );
		}

		if ( self::CONTEXT_CHAT === $context ) {
			$conversation = KMAE_Conversations::get( $conversation_id );
			if ( ! $conversation || ! KMAE_Conversations::is_member( $conversation, $reporter_id ) ) {
				return new WP_Error( 'forbidden', __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ) );
			}
			$listing_id  = (int) $conversation->listing_id;
			$reported_id = (int) $conversation->buyer_id === $reporter_id ? (int) $conversation->seller_id : (int) $conversation->buyer_id;
		} else {
			$listing = $listing_id ? get_post( $listing_id ) : null;
			if ( ! $listing || 'kmae_listing' !== $listing->post_type ) {
				return new WP_Error( 'forbidden', __( 'Ehhez a művelethez nincs jogosultságod.', 'ki-mit-adna-erte' ) );
			}
			$reported_id = (int) $listing->post_author;
			if ( $reported_id === $reporter_id ) {
				return new WP_Error( 'forbidden', __( 'A saját hirdetésedet nem jelentheted be.', 'ki-mit-adna-erte' ) );
			}
			if ( $is_photo ) {
				$details = trim( $details . "\n\n" . self::photo_note( $listing_id, (int) ( $args['photo'] ?? 0 ) ) );
			}
			/* Ha a bejelentő már írt az eladónak, a beszélgetésük is kerüljön az admin elé. */
			$conversation    = KMAE_Conversations::get_for_listing_buyer( $listing_id, $reporter_id );
			$conversation_id = $conversation ? (int) $conversation->id : 0;
		}

		$table     = KMAE_Database::table( 'reports' );
		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE reporter_id = %d AND context = %s AND listing_id = %d AND conversation_id = %d AND status = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$reporter_id,
				$context,
				$listing_id,
				$conversation_id,
				self::STATUS_NEW
			)
		);
		if ( $duplicate ) {
			return new WP_Error( 'report_duplicate', __( 'Ezt már bejelentetted, hamarosan megnézzük.', 'ki-mit-adna-erte' ) );
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'context'          => $context,
				'listing_id'       => $listing_id,
				'conversation_id'  => $conversation_id,
				'reporter_id'      => $reporter_id,
				'reported_user_id' => (int) $reported_id,
				'listing_title'    => mb_substr( wp_strip_all_tags( (string) get_the_title( $listing_id ) ), 0, 255 ),
				'reason'           => $reason,
				'details'          => $details,
				'status'           => self::STATUS_NEW,
				'admin_note'       => '',
			),
			array( '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'report_save_failed', __( 'A bejelentés nem menthető.', 'ki-mit-adna-erte' ) );
		}

		$report = self::get( (int) $wpdb->insert_id );
		self::notify_admins( $report );
		return $report;
	}

	/**
	 * „Érintett kép: N. / össz. — URL” a hirdetés képei közül (a böngésző-kártya
	 * sorrendje: galéria, ha üres, a bélyegkép — KMAE_Browse_Index::card). A sorszám
	 * a kliensről jön, a címet a szerver adja.
	 *
	 * @param int $listing_id Hirdetés.
	 * @param int $photo      1-től számozott kép.
	 */
	private static function photo_note( $listing_id, $photo ) {
		$ids = array();
		foreach ( KMAE_Query::get_gallery_ids( $listing_id ) as $aid ) {
			if ( '' !== KMAE_Images::card_sources( (int) $aid )['s'] ) {
				$ids[] = (int) $aid;
			}
		}
		if ( ! $ids ) {
			$thumb = (int) KMAE_Query::get_listing_thumbnail_id( $listing_id );
			if ( $thumb && '' !== KMAE_Images::card_sources( $thumb )['s'] ) {
				$ids[] = $thumb;
			}
		}
		if ( $photo < 1 || $photo > count( $ids ) ) {
			return __( 'Érintett kép: nem azonosítható.', 'ki-mit-adna-erte' );
		}
		/* translators: 1: kép sorszáma, 2: képek száma, 3: kép címe */
		return sprintf( __( 'Érintett kép: %1$d. / %2$d — %3$s', 'ki-mit-adna-erte' ), $photo, count( $ids ), (string) wp_get_attachment_url( $ids[ $photo - 1 ] ) );
	}

	/**
	 * Az adminok azonnal tudnak róla: e-mail (akkor is, ha napokig nem lépnek
	 * be) + Web Push a meglévő jelzőfájl-csatornán (nincs poll).
	 *
	 * @param object|null $report Sor.
	 */
	private static function notify_admins( $report ) {
		if ( ! $report ) {
			return;
		}
		$admins = get_users(
			array(
				'capability' => 'manage_options',
				'fields'     => array( 'ID', 'user_email' ),
			)
		);
		if ( empty( $admins ) ) {
			return;
		}
		$where = self::CONTEXT_CHAT === $report->context ? __( 'chat', 'ki-mit-adna-erte' ) : __( 'hirdetés', 'ki-mit-adna-erte' );
		$body  = self::reason_label( $report->reason ) . ' (' . $where . ')';
		if ( '' !== (string) $report->listing_title ) {
			$body .= ' — ' . $report->listing_title;
		}
		$url = self::admin_url( (int) $report->id );

		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump(
				array_map( 'intval', wp_list_pluck( $admins, 'ID' ) ),
				array(
					'title' => __( 'Új bejelentés', 'ki-mit-adna-erte' ),
					'body'  => $body,
					'url'   => $url,
					'tag'   => 'kmae-report-' . (int) $report->id,
				)
			);
		}

		$reporter = get_userdata( (int) $report->reporter_id );
		$reported = get_userdata( (int) $report->reported_user_id );
		$lines    = array(
			__( 'Új bejelentés érkezett az Albirán.', 'ki-mit-adna-erte' ),
			/* translators: %s: reason label */
			sprintf( __( 'Ok: %s', 'ki-mit-adna-erte' ), self::reason_label( $report->reason ) ),
			/* translators: %s: hirdetés / chat */
			sprintf( __( 'Hol: %s', 'ki-mit-adna-erte' ), $where ),
		);
		if ( '' !== (string) $report->listing_title ) {
			/* translators: %s: listing title */
			$lines[] = sprintf( __( 'Hirdetés: %s', 'ki-mit-adna-erte' ), $report->listing_title );
		}
		if ( $reporter ) {
			/* translators: %s: user name */
			$lines[] = sprintf( __( 'Bejelentő: %s', 'ki-mit-adna-erte' ), $reporter->display_name );
		}
		if ( $reported ) {
			/* translators: %s: user name */
			$lines[] = sprintf( __( 'Bejelentett: %s', 'ki-mit-adna-erte' ), $reported->display_name );
		}
		if ( '' !== trim( (string) $report->details ) ) {
			/* translators: %s: reporter's text */
			$lines[] = sprintf( __( 'Leírás: %s', 'ki-mit-adna-erte' ), trim( (string) $report->details ) );
		}
		$lines[] = __( 'Az EU-s szabály szerint a bejelentéssel érdemben és időben kell foglalkozni: nézd meg és dönts róla az adminban.', 'ki-mit-adna-erte' );

		/* translators: %s: reason label */
		$subject = sprintf( __( 'Új bejelentés: %s', 'ki-mit-adna-erte' ), self::reason_label( $report->reason ) );
		$wrap    = array(
			'tone'         => 'info',
			'eyebrow'      => __( 'Bejelentés', 'ki-mit-adna-erte' ),
			'title'        => __( 'Új bejelentés érkezett', 'ki-mit-adna-erte' ),
			'button_label' => __( 'Megnézem az adminban', 'ki-mit-adna-erte' ),
			'button_url'   => $url,
		);
		$sent = array();
		foreach ( $admins as $admin ) {
			$email = (string) $admin->user_email;
			if ( ! is_email( $email ) || isset( $sent[ strtolower( $email ) ] ) ) {
				continue;
			}
			$sent[ strtolower( $email ) ] = true;
			KMAE_Brand::send_mail( $email, $subject, implode( "\n\n", $lines ), array(), $wrap );
		}
	}

	/**
	 * @return object|null
	 */
	public static function get( $report_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'reports' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $report_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Admin lista.
	 *
	 * @param string $status Üres = mind.
	 * @return array
	 */
	public static function get_list( $status = '', $limit = 200 ) {
		global $wpdb;
		$table = KMAE_Database::table( 'reports' );
		if ( '' !== $status ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", $status, (int) $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * @return array<string,int> státusz → darab.
	 */
	public static function count_by_status() {
		global $wpdb;
		$table  = KMAE_Database::table( 'reports' );
		$rows   = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts = array_fill_keys( array_keys( self::statuses() ), 0 );
		foreach ( (array) $rows as $row ) {
			$counts[ $row->status ] = (int) $row->total;
		}
		return $counts;
	}

	/**
	 * Az admin menü piros számlálója.
	 */
	public static function count_new() {
		global $wpdb;
		$table = KMAE_Database::table( 'reports' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", self::STATUS_NEW ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Előzmények: más bejelentések, ahol ez a felhasználó a bejelentett.
	 *
	 * @return array
	 */
	public static function get_against_user( $user_id, $exclude_id = 0 ) {
		global $wpdb;
		$table = KMAE_Database::table( 'reports' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE reported_user_id = %d AND id <> %d ORDER BY id DESC LIMIT 50", (int) $user_id, (int) $exclude_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Hány bejelentést küldött ez a felhasználó (és ebből mennyi lett „Nem igaz”).
	 *
	 * @return array{total:int,invalid:int}
	 */
	public static function reporter_stats( $user_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'reports' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS total, SUM(status = %s) AS invalid FROM {$table} WHERE reporter_id = %d", self::STATUS_INVALID, (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array(
			'total'   => (int) ( $row->total ?? 0 ),
			'invalid' => (int) ( $row->invalid ?? 0 ),
		);
	}

	/**
	 * Admin döntés: státusz + saját jegyzet.
	 */
	public static function update_decision( $report_id, $status, $note, $admin_id ) {
		global $wpdb;
		if ( ! array_key_exists( $status, self::statuses() ) ) {
			return false;
		}
		$note = mb_substr( trim( sanitize_textarea_field( (string) $note ) ), 0, self::NOTE_MAX_LENGTH );
		return false !== $wpdb->update(
			KMAE_Database::table( 'reports' ),
			array(
				'status'      => $status,
				'admin_note'  => $note,
				'resolved_by' => self::STATUS_NEW === $status ? 0 : (int) $admin_id,
				'resolved_at' => self::STATUS_NEW === $status ? null : current_time( 'mysql' ),
			),
			array( 'id' => (int) $report_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * A beszélgetés összes üzenete az adminnak (tagság-ellenőrzés nélkül, csak olvasás).
	 *
	 * @return array
	 */
	public static function get_conversation_messages( $conversation_id ) {
		global $wpdb;
		$table = KMAE_Database::table( 'conversation_messages' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE conversation_id = %d ORDER BY created_at ASC, id ASC", (int) $conversation_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function admin_url( $report_id = 0 ) {
		$args = array( 'page' => 'kmae-reports' );
		if ( $report_id > 0 ) {
			$args['report'] = (int) $report_id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}
}
