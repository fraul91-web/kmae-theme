<?php
/**
 * Logged-in seller dashboard data + view tracking.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Dashboard {

	const META_VIEWS      = '_kmae_view_count';
	const META_VIEWS_WEEK = '_kmae_views_week';
	const COOKIE_PREFIX   = 'kmae_v_';
	/* Az első hirdetés utáni felület „Most nem” gombjával elrejtett hirdetés ID-ja. */
	const META_FIRST_TIPS_DISMISSED = '_kmae_first_tips_dismissed';
	/* Az első hirdetés tipp-küszöbei (szóköz és írásjel is karakter): minimum (piros) < elfogadható (narancs) < ideális (zöld). */
	const FIRST_TIP_FLOOR_DESC   = 80;
	const FIRST_TIP_BASE_DESC    = 120;
	const FIRST_TIP_MIN_DESC     = 200;
	const FIRST_TIP_FLOOR_PHOTOS = 1;
	const FIRST_TIP_BASE_PHOTOS  = 3;

	/**
	 * Megszólításra szánt név: az első betű mindig nagy, akkor is, ha a
	 * felhasználó csupa kisbetűvel regisztrált. Ékezetes kezdőbetűvel is
	 * helyesen működik (á → Á), a név többi része érintetlen marad.
	 *
	 * @param string $name Nyers név.
	 * @return string
	 */
	public static function greeting_case( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return '';
		}

		if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strtoupper' ) ) {
			return mb_strtoupper( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $name, 1, null, 'UTF-8' );
		}

		/* mbstring nélkül az ucfirst() az ékezetes betűket békén hagyná. */
		$accents = array(
			'á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó',
			'ö' => 'Ö', 'ő' => 'Ő', 'ú' => 'Ú', 'ü' => 'Ü', 'ű' => 'Ű',
		);
		foreach ( $accents as $lower => $upper ) {
			if ( 0 === strpos( $name, $lower ) ) {
				return $upper . substr( $name, strlen( $lower ) );
			}
		}

		return ucfirst( $name );
	}

	/** Show the personal home greeting once per authenticated WordPress session. */
	public static function take_home_greeting() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$token = wp_get_session_token();
		if ( ! $token ) {
			return false;
		}
		$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
		$session = $manager->get( $token );
		if ( ! $session || ! empty( $session['kmae_home_greeted'] ) ) {
			return false;
		}
		$session['kmae_home_greeted'] = true;
		$manager->update( $token, $session );
		return true;
	}

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_track_listing_view' ), 20 );
		add_action( 'init', array( __CLASS__, 'purge_home_welcome_flags' ) );
	}

	/** A megszűnt üdvözlő-képernyő régi jelzőit egyszer letörli a fiókokról. */
	public static function purge_home_welcome_flags() {
		if ( get_option( 'kmae_home_welcome_purged' ) ) {
			return;
		}
		delete_metadata( 'user', 0, 'kmae_home_welcome_pending', '', true );
		update_option( 'kmae_home_welcome_purged', 1, false );
	}

	/**
	 * @return bool
	 */
	private static function is_home_request() {
		if ( is_front_page() ) {
			return true;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		return untrailingslashit( (string) $path ) === untrailingslashit( (string) $home );
	}

	/**
	 * Count a listing view once per visitor session (skip owner / bots).
	 */
	public static function maybe_track_listing_view() {
		if ( ! is_singular( 'kmae_listing' ) || is_preview() ) {
			return;
		}

		$listing_id = get_queried_object_id();
		if ( ! $listing_id ) {
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $listing_id );
		if ( is_user_logged_in() && get_current_user_id() === $author_id ) {
			return;
		}

		if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === KMAE_Meta::get_status( $listing_id ) ) {
			return;
		}

		$cookie = self::COOKIE_PREFIX . $listing_id;
		if ( ! empty( $_COOKIE[ $cookie ] ) ) {
			return;
		}

		self::increment_views( $listing_id );
		if ( ! headers_sent() ) {
			setcookie( $cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * @param int $listing_id Listing ID.
	 */
	public static function increment_views( $listing_id ) {
		$listing_id = (int) $listing_id;
		$count      = (int) get_post_meta( $listing_id, self::META_VIEWS, true );
		update_post_meta( $listing_id, self::META_VIEWS, $count + 1 );

		$week_key = gmdate( 'oW' );
		$weeks    = get_post_meta( $listing_id, self::META_VIEWS_WEEK, true );
		if ( ! is_array( $weeks ) ) {
			$weeks = array();
		}
		$weeks[ $week_key ] = isset( $weeks[ $week_key ] ) ? (int) $weeks[ $week_key ] + 1 : 1;
		// Keep last ~8 week buckets.
		if ( count( $weeks ) > 8 ) {
			ksort( $weeks );
			$weeks = array_slice( $weeks, -8, null, true );
		}
		update_post_meta( $listing_id, self::META_VIEWS_WEEK, $weeks );

		/* Élő főoldal: az eladó jelzőfájlja frissül (egy apró fájlírás, nincs új lekérdezés),
		   így a megtekintés-szám a következő 4 mp-es körben megjelenik, nem 2–5 perc múlva. */
		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( (int) get_post_field( 'post_author', $listing_id ) );
		}
	}

	/**
	 * @param int $listing_id Listing ID.
	 * @return int
	 */
	public static function get_listing_views( $listing_id ) {
		return (int) get_post_meta( $listing_id, self::META_VIEWS, true );
	}

	/**
	 * Sum views for all listings of a user.
	 *
	 * @param int $user_id User ID.
	 * @return array{total:int,this_week:int,last_week:int,delta:int}
	 */
	public static function get_user_view_stats( $user_id ) {
		$ids = get_posts(
			array(
				'post_type'              => 'kmae_listing',
				'post_status'            => 'publish',
				'author'                 => (int) $user_id,
				'posts_per_page'         => 100,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$total      = 0;
		$this_week  = 0;
		$last_week  = 0;
		$week_key   = gmdate( 'oW' );
		$prev_key   = gmdate( 'oW', strtotime( '-7 days' ) );

		foreach ( $ids as $id ) {
			$total += self::get_listing_views( $id );
			$weeks  = get_post_meta( $id, self::META_VIEWS_WEEK, true );
			if ( ! is_array( $weeks ) ) {
				continue;
			}
			$this_week += isset( $weeks[ $week_key ] ) ? (int) $weeks[ $week_key ] : 0;
			$last_week += isset( $weeks[ $prev_key ] ) ? (int) $weeks[ $prev_key ] : 0;
		}

		return array(
			'total'     => $total,
			'this_week' => $this_week,
			'last_week' => $last_week,
			'delta'     => $this_week - $last_week,
		);
	}

	/**
	 * Full dashboard payload for the logged-in home.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_data( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		$name    = self::greeting_case( $user ? ( $user->display_name ?: $user->user_login ) : '' );

		$listings = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$active_count    = 0;
		$awaiting_count  = 0;
		$closed_count    = 0;
		$archived_count  = 0;
		$new_this_week   = 0;
		$week_ago        = strtotime( '-7 days' );
		$listing_rows    = array();

		foreach ( $listings as $listing ) {
			$status = KMAE_Meta::get_status( $listing->ID );
			if ( KMAE_Meta::STATUS_ACTIVE === $status ) {
				++$active_count;
			} elseif ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) {
				++$awaiting_count;
			} elseif ( KMAE_Meta::STATUS_CLOSED === $status ) {
				++$closed_count;
			} elseif ( KMAE_Meta::STATUS_ARCHIVED === $status ) {
				++$archived_count;
			}

			if ( strtotime( $listing->post_date ) >= $week_ago ) {
				++$new_this_week;
			}

			$listing_rows[] = self::format_listing_row( $listing );
		}

		$activity = array();

		foreach ( array_slice( $listings, 0, 8 ) as $listing ) {
			$status = KMAE_Meta::get_status( $listing->ID );
			if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) {
				$activity[] = array(
					'type' => 'gold',
					'text' => sprintf(
						/* translators: %s: listing title */
						__( '„%s” fotó feltöltésre vár', 'ki-mit-adna-erte' ),
						$listing->post_title
					),
					'time' => self::relative_time( $listing->post_date ),
					'ts'   => strtotime( $listing->post_date ),
				);
			} elseif ( strtotime( $listing->post_date ) >= $week_ago ) {
				$activity[] = array(
					'type' => 'gold',
					'text' => sprintf(
						/* translators: %s: listing title */
						__( 'Hirdetés feladva: „%s”', 'ki-mit-adna-erte' ),
						$listing->post_title
					),
					'time' => self::relative_time( $listing->post_date ),
					'ts'   => strtotime( $listing->post_date ),
				);
			}
		}

		usort(
			$activity,
			static function ( $a, $b ) {
				return ( $b['ts'] ?? 0 ) <=> ( $a['ts'] ?? 0 );
			}
		);
		$activity = array_slice( $activity, 0, 6 );

		$view_stats = self::get_user_view_stats( $user_id );

		$cycle_active = $active_count + $awaiting_count + $closed_count;

		return array(
			'name'            => $name,
			/* Köszönéshez a keresztnév — a teljes név a monogramhoz marad. */
			'first_name'      => self::greeting_case( KMAE_Auth::first_name( $user_id ) ),
			'initials'        => self::initials( $name ),
			'stats'           => array(
				'listings'         => $cycle_active,
				'listings_sub'     => $new_this_week > 0
					? sprintf(
						/* translators: %d: count */
						__( '↑ %d ezen a héten', 'ki-mit-adna-erte' ),
						$new_this_week
					)
					: __( 'Nincs új ezen a héten', 'ki-mit-adna-erte' ),
				'views'            => $view_stats['total'],
				'views_sub'        => self::format_view_delta( $view_stats['delta'] ),
				'active'           => $active_count,
				'awaiting'         => $awaiting_count,
			),
			'listings'        => array_slice( $listing_rows, 0, 5 ),
			'activity'        => $activity,
			'urls'            => array(
				'create'    => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'hirdetes-feladasa' ) : home_url( '/hirdetes-feladasa/' ),
				'listings'  => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'sajat-hirdetesek' ) : home_url( '/sajat-hirdetesek/' ),
				'messages'  => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'uzenetek' ) : home_url( '/uzenetek/' ),
				'browse'    => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'hirdetesek' ) : home_url( '/hirdetesek/' ),
				'profile'   => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'profil' ) : home_url( '/profil/' ),
				'notify'    => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'ertesitesek' ) : home_url( '/ertesitesek/' ),
				'settings'  => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'beallitasok' ) : home_url( '/beallitasok/' ),
				'hub_sell'  => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'eladni' ) : home_url( '/eladni/' ),
				'hub_buy'   => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'venni' ) : home_url( '/venni/' ),
				'hub_stats' => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'statisztikak' ) : home_url( '/statisztikak/' ),
				'education' => class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'oktatas' ) : home_url( '/oktatas/' ),
			),
		);
	}

	/** Első hirdetés tippjei: ennyi fotótól számít ideálisnak. */
	const STRENGTH_MIN_PHOTOS = 5;

	/** Az élő főoldal legfeljebb ennyi hirdetést mutat (a többi a Hirdetéseim oldalon). */
	const HOME_LIVE_MAX = 6;

	/** A heti megtekintés-grafikon ennyi hetet mutat (a META_VIEWS_WEEK ~8 hetet őriz). */
	const HOME_LIVE_WEEKS = 8;

	/**
	 * A belépett kezdőoldal állapota. Csak a felhasználó saját, ténylegesen
	 * tárolt adata; csak olvas, állapotot nem módosít.
	 *
	 * - `start`: hirdetés nélküli új felhasználó „Első lépéseid” doboza.
	 * - `first`: az első hirdetés utáni gratuláló felület (amíg el nem rejti).
	 * - `live`:  a hirdetései élő statisztikával — a gratuláló felület után ez a végleges főoldal.
	 *
	 * @param int   $user_id User ID.
	 * @param array $urls    `get_data()['urls']`.
	 * @return array
	 */
	public static function get_home_state( $user_id, array $urls ) {
		$user_id    = (int) $user_id;
		$create_url = $urls['create'] ?? home_url( '/hirdetes-feladasa/' );
		$sold       = class_exists( 'KMAE_Chat_Offers' ) ? KMAE_Chat_Offers::count_sold_for_seller( $user_id ) : 0;

		$posts = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$first = count( $posts ) === 1 ? self::first_listing_tips( $posts[0], $create_url ) : null;
		$live  = $first ? null : self::home_live( $user_id, $posts, $urls );

		$start = null;
		if ( ! $posts && 0 === $sold ) {
			$start = array(
				'title' => __( 'Add fel az első hirdetésed', 'ki-mit-adna-erte' ),
				'text'  => __( 'Lépésről lépésre vezetlek végig. Amit kitöltesz, elmentjük, így bármikor megszakíthatod és később folytathatod.', 'ki-mit-adna-erte' ),
			);
		}

		return array(
			'start' => $start,
			'first' => $first,
			'live'  => $live,
			'sold'  => $sold,
		);
	}

	/**
	 * Az élő főoldal: a felhasználó látható / fotóra váró / lejárt hirdetései
	 * hirdetésenkénti statisztikával, és a manó egy mondata a legfrissebb hírről.
	 *
	 * @param int       $user_id User ID.
	 * @param WP_Post[] $posts   A felhasználó publikált hirdetései (legújabb elöl).
	 * @param array     $urls    Oldal-URL-ek.
	 * @return array{cards:array,more:int,say:array,totals:array}|null
	 */
	private static function home_live( $user_id, array $posts, array $urls ) {
		$create_url = $urls['create'] ?? home_url( '/hirdetes-feladasa/' );
		$list_url   = $urls['listings'] ?? home_url( '/sajat-hirdetesek/' );
		$msg_url    = $urls['messages'] ?? home_url( '/uzenetek/' );
		$has_offers = class_exists( 'KMAE_Chat_Offers' );
		$reserved   = $has_offers ? KMAE_Chat_Offers::reserved_listing_ids() : array();

		$shown = array();
		foreach ( $posts as $post ) {
			$status = KMAE_Meta::get_status( $post->ID );
			if ( in_array( $status, array( KMAE_Meta::STATUS_ACTIVE, KMAE_Meta::STATUS_AWAITING_PHOTOS, KMAE_Meta::STATUS_EXPIRED ), true ) ) {
				$shown[] = $post;
			}
		}
		if ( ! $shown ) {
			return null;
		}
		$more  = max( 0, count( $shown ) - self::HOME_LIVE_MAX );
		$shown = array_slice( $shown, 0, self::HOME_LIVE_MAX );

		/* Érdeklődők hirdetésenként: a beszélgetések, amelyekben már van üzenet;
		   az olvasatlan ugyanaz, mint a fejléc-jelvényé. */
		$chats  = array();
		$unread = array();
		if ( class_exists( 'KMAE_Conversations' ) ) {
			foreach ( KMAE_Conversations::get_for_user( $user_id ) as $conversation ) {
				if ( (int) $conversation->seller_id !== (int) $user_id || (int) $conversation->message_count < 1 ) {
					continue;
				}
				$lid           = (int) $conversation->listing_id;
				$chats[ $lid ] = ( $chats[ $lid ] ?? 0 ) + 1;
				if ( 'good' === KMAE_Database::get_conversation_alert_state( $user_id, $conversation ) ) {
					$unread[ $lid ] = ( $unread[ $lid ] ?? 0 ) + 1;
				}
			}
		}

		$favorites = self::favorite_counts( wp_list_pluck( $shown, 'ID' ) );

		$week_keys = array();
		for ( $i = self::HOME_LIVE_WEEKS - 1; $i >= 0; $i-- ) {
			$week_keys[] = gmdate( 'oW', strtotime( '-' . $i . ' weeks' ) );
		}

		$totals = array(
			'views'  => 0,
			'week'   => 0,
			'unread' => 0,
			'offers' => 0,
		);
		$cards  = array();

		foreach ( $shown as $post ) {
			$id     = (int) $post->ID;
			$row    = self::format_listing_row( $post );
			$status = $row['status'];
			$state  = ( KMAE_Meta::STATUS_ACTIVE === $status && isset( $reserved[ $id ] ) ) ? 'reserved' : $status;

			$gallery = KMAE_Query::get_gallery_ids( $id );
			$weeks   = get_post_meta( $id, self::META_VIEWS_WEEK, true );
			$weeks   = is_array( $weeks ) ? $weeks : array();
			$series  = array();
			foreach ( $week_keys as $key ) {
				$series[] = isset( $weeks[ $key ] ) ? (int) $weeks[ $key ] : 0;
			}
			$days = KMAE_Listings::days_until_expiry( $id );

			$card = array(
				'id'        => $id,
				'title'     => $row['title'],
				'meta'      => $row['meta'],
				'price'     => $row['price'],
				'state'     => $state,
				'label'     => 'reserved' === $state ? __( 'Foglalt', 'ki-mit-adna-erte' ) : $row['label'],
				'photo'     => ! empty( $gallery ) ? KMAE_Images::get_url( (int) $gallery[0], 'medium_large' ) : '',
				'url'       => $row['url'],
				'edit_url'  => KMAE_Listings::can_edit_listing( $id ) ? add_query_arg( 'kmae_edit', $id, $create_url ) : '',
				'list_url'  => $list_url,
				'msg_url'   => $msg_url,
				'views'     => (int) $row['views'],
				'week'      => (int) end( $series ),
				'last_week' => (int) $series[ count( $series ) - 2 ],
				'series'    => $series,
				'chats'     => (int) ( $chats[ $id ] ?? 0 ),
				'unread'    => (int) ( $unread[ $id ] ?? 0 ),
				'favorites' => (int) ( $favorites[ $id ] ?? 0 ),
				'offers'    => ( KMAE_Meta::STATUS_ACTIVE === $status && $has_offers ) ? KMAE_Chat_Offers::count_live_for_listing( $id ) : 0,
				'days'      => $days,
				'life_pct'  => null === $days ? 0 : (int) round( min( 100, $days / KMAE_Listings::EXPIRE_DAYS * 100 ) ),
			);

			if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) {
				$card['edit_url'] = $row['url'];
			}

			$totals['views']  += $card['views'];
			$totals['week']   += $card['week'];
			$totals['unread'] += $card['unread'];
			$totals['offers'] += $card['offers'];
			$cards[]           = $card;
		}

		return array(
			'cards'  => $cards,
			'more'   => $more,
			'totals' => $totals,
			'say'    => self::home_live_say( $totals, count( $cards ), $msg_url ),
		);
	}

	/**
	 * Az élő főoldal buborékja: a legfrissebb, legfontosabb hír a hirdetés(ek)ről.
	 *
	 * @param array  $totals  Összesített számok.
	 * @param int    $count   Megjelenített hirdetések száma.
	 * @param string $msg_url Üzenetek oldal.
	 * @return array{text:string,url:string,cta:string}
	 */
	private static function home_live_say( array $totals, $count, $msg_url ) {
		$many = $count > 1;

		if ( $totals['unread'] > 0 ) {
			return array(
				/* translators: %d: unread conversation count */
				'text'   => sprintf( _n( 'Valaki írt neked! %d olvasatlan beszélgetésed vár.', 'Írtak neked! %d olvasatlan beszélgetésed vár.', $totals['unread'], 'ki-mit-adna-erte' ), $totals['unread'] ),
				'url'    => $msg_url,
				'cta'    => __( 'Megnézem az üzeneteket', 'ki-mit-adna-erte' ),
			);
		}
		if ( $totals['offers'] > 0 ) {
			return array(
				'text'   => KMAE_Chat_Offers::count_label( $totals['offers'] ) . ( $many ? __( ' a hirdetéseidre.', 'ki-mit-adna-erte' ) : __( ' a hirdetésedre.', 'ki-mit-adna-erte' ) ),
				'url'    => $msg_url,
				'cta'    => __( 'Megnézem', 'ki-mit-adna-erte' ),
			);
		}
		if ( $totals['week'] > 0 ) {
			return array(
				'text'   => sprintf(
					/* translators: %s: view count this week */
					$many ? __( 'Ezen a héten már %s alkalommal nézték meg a hirdetéseidet. Figyelem tovább!', 'ki-mit-adna-erte' ) : __( 'Ezen a héten már %s alkalommal nézték meg a hirdetésedet. Figyelem tovább!', 'ki-mit-adna-erte' ),
					number_format_i18n( $totals['week'] )
				),
				'url'    => '',
				'cta'    => '',
			);
		}
		if ( $totals['views'] > 0 ) {
			return array(
				'text'   => sprintf(
					/* translators: %s: total view count */
					$many ? __( 'Eddig %s alkalommal nézték meg a hirdetéseidet. Szólok, amint új érdeklődő jön.', 'ki-mit-adna-erte' ) : __( 'Eddig %s alkalommal nézték meg a hirdetésedet. Szólok, amint új érdeklődő jön.', 'ki-mit-adna-erte' ),
					number_format_i18n( $totals['views'] )
				),
				'url'    => '',
				'cta'    => '',
			);
		}
		return array(
			'text'   => $many ? __( 'A hirdetéseid élnek és láthatók. Figyelem őket, és szólok, amint jön az első érdeklődő.', 'ki-mit-adna-erte' ) : __( 'A hirdetésed él és látható. Figyelem, és szólok, amint jön az első érdeklődő.', 'ki-mit-adna-erte' ),
			'url'    => '',
			'cta'    => '',
		);
	}

	/**
	 * Hány felhasználó mentette kedvencnek a hirdetéseket. Csak darabszám:
	 * azt, hogy ki mentette, sosem adja vissza.
	 *
	 * @param int[] $ids Listing IDs.
	 * @return array<int,int>
	 */
	public static function favorite_counts( array $ids ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids || ! class_exists( 'KMAE_Browse' ) ) {
			return array();
		}

		$likes = array();
		$args  = array( KMAE_Browse::FAVORITE_META );
		foreach ( $ids as $id ) {
			$likes[] = 'meta_value LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( 'i:' . $id . ';' ) . '%';
		}
		$values = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND (" . implode( ' OR ', $likes ) . ')', $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$counts = array();
		foreach ( $values as $value ) {
			$saved = maybe_unserialize( $value );
			if ( ! is_array( $saved ) ) {
				continue;
			}
			$saved = array_map( 'intval', $saved );
			foreach ( $ids as $id ) {
				if ( in_array( $id, $saved, true ) ) {
					$counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
				}
			}
		}
		return $counts;
	}

	/**
	 * Az első hirdetés utáni gratuláló felület adatai. Csak akkor ad vissza
	 * adatot, ha a hirdetés aktív, nincs „Most nem”-mel elrejtve, és legalább
	 * egy fejlesztési tipp érvényes rá (a tippek a ténylegesen mentett adatból).
	 *
	 * @param WP_Post $post       A felhasználó egyetlen publikált hirdetése.
	 * @param string  $create_url Hirdetésfeladás oldal URL-je.
	 * @return array{listing_id:int,stars:int,stars_text:string,tips:array}|null
	 */
	private static function first_listing_tips( $post, $create_url ) {
		$id = (int) $post->ID;

		if ( KMAE_Meta::STATUS_ACTIVE !== KMAE_Meta::get_status( $id ) ) {
			return null;
		}
		if ( (int) get_user_meta( (int) $post->post_author, self::META_FIRST_TIPS_DISMISSED, true ) === $id ) {
			return null;
		}

		$content = (string) $post->post_content;
		$photos  = count( (array) KMAE_Query::get_gallery_ids( $id ) );
		$topics_all = KMAE_Listing_Wizard::description_topics();

		/* Ugyanúgy számol, mint a feladási minimum (KMAE_Moderation::validate): a teljes szöveg, a beszúrt vázakkal együtt. */
		$desc = mb_strlen( trim( wp_strip_all_tags( $content ) ) );

		$edit_url  = add_query_arg( 'kmae_edit', $id, $create_url );
		$photo_url = add_query_arg( 'kmae_photos', '1', $edit_url );

		$tips = array();

		if ( $photos < self::STRENGTH_MIN_PHOTOS ) {
			$tips[] = array(
				'icon'      => 'camera',
				'title'     => __( 'Mutasd meg autódat több oldalról is!', 'ki-mit-adna-erte' ),
				'text'      => __( 'A jó fotók segítenek az érdeklődőknek elképzelni, milyen állapotban van az autó. Néhány további képpel még informatívabbá teheted a hirdetésedet.', 'ki-mit-adna-erte' ),
				'metrics'   => self::tier_metrics( $photos, self::FIRST_TIP_FLOOR_PHOTOS, self::FIRST_TIP_BASE_PHOTOS, self::STRENGTH_MIN_PHOTOS, __( 'kép', 'ki-mit-adna-erte' ) ),
				'items'     => array(
					__( 'Autó műszerfala', 'ki-mit-adna-erte' ),
					__( 'Motortér', 'ki-mit-adna-erte' ),
					__( 'Csomagtartó', 'ki-mit-adna-erte' ),
					__( 'Utastér és az ülések állapota', 'ki-mit-adna-erte' ),
					__( 'Alváz, az elérhető és biztonságosan lefényképezhető részek', 'ki-mit-adna-erte' ),
				),
				'note'      => __( 'Ezek a képek segítenek az érdeklődőknek jobban megismerni az autót.', 'ki-mit-adna-erte' ),
				'cta_label' => __( 'Fotók hozzáadása', 'ki-mit-adna-erte' ),
				'cta_url'   => $photo_url,
			);
		}

		if ( $desc < self::FIRST_TIP_MIN_DESC ) {
			/* A varázsló leírásjavaslatai közül azok, amelyeket a leírás még nem tartalmaz (a beszúrt szöveg markere alapján). */
			$topics = array();
			foreach ( $topics_all as $topic ) {
				if ( false === strpos( $content, $topic['marker'] ) ) {
					$topics[] = $topic['label'];
				}
			}
			$tips[] = array(
				'icon'      => 'pencil',
				'title'     => __( 'Mesélj még egy kicsit az autódról!', 'ki-mit-adna-erte' ),
				'text'      => __( 'Az érdeklődők szeretnék minél jobban megismerni az autót, mielőtt kapcsolatba lépnek veled. Néhány további részlet segíthet megválaszolni a kérdéseiket, és még informatívabbá teheti a hirdetésedet.', 'ki-mit-adna-erte' ),
				'metrics'   => self::tier_metrics( $desc, self::FIRST_TIP_FLOOR_DESC, self::FIRST_TIP_BASE_DESC, self::FIRST_TIP_MIN_DESC, __( 'karakter', 'ki-mit-adna-erte' ) ),
				'items'     => $topics,
				'note'      => __( 'Tipp: a leírásban írd le őszintén az autó állapotát, a hibákat és a karbantartást is, mert ez épít bizalmat.', 'ki-mit-adna-erte' ),
				'cta_label' => __( 'Hirdetés leírásának bővítése', 'ki-mit-adna-erte' ),
				'cta_url'   => $edit_url . '#kmae-section-ad',
			);
		}

		/* Nincs tipp (fotó és leírás is ideális): nem tűnik el a felület, hanem dicséret jön. */
		$praise = null;
		if ( ! $tips ) {
			$praise = array(
				'title' => __( 'Szép munka, tökéletes hirdetést adtál fel!', 'ki-mit-adna-erte' ),
				'text'  => __( 'Elég fotót töltöttél fel, és a leírásod is részletes. Az érdeklődők így mindent megtudhatnak az autóról, ez nagyban növeli az esélyét, hogy hamar megtaláld az új gazdáját.', 'ki-mit-adna-erte' ),
			);
		}

		/* Csillag: 1 alap + fotó (0–2) + leírás (0–2). Minél több az adat, annál magasabb. */
		$photo_pts = $photos >= self::STRENGTH_MIN_PHOTOS ? 2 : ( $photos >= self::FIRST_TIP_BASE_PHOTOS ? 1 : 0 );
		$desc_pts  = $desc >= self::FIRST_TIP_MIN_DESC ? 2 : ( $desc >= self::FIRST_TIP_BASE_DESC ? 1 : 0 );
		$stars     = 1 + $photo_pts + $desc_pts;

		$stars_text = array(
			1 => __( 'Jó kezdet! Néhány további részlettel még többet mutathatsz meg az autódról.', 'ki-mit-adna-erte' ),
			2 => __( 'Már alakul! Egy kis kiegészítéssel még informatívabb lehet.', 'ki-mit-adna-erte' ),
			3 => __( 'Szép munka! A hirdetésed már sok hasznos információt tartalmaz.', 'ki-mit-adna-erte' ),
			4 => __( 'Nagyszerű! Már csak néhány apróság teheti még teljesebbé.', 'ki-mit-adna-erte' ),
			5 => __( 'Kiváló! A hirdetésed részletes és informatív.', 'ki-mit-adna-erte' ),
		);

		return array(
			'listing_id' => $id,
			'stars'      => $stars,
			'stars_text' => $stars_text[ $stars ],
			'tips'       => $tips,
			'praise'     => $praise,
		);
	}

	/**
	 * Egy tipp skálája: minimum / elfogadható / ideális küszöb és a jelenlegi érték
	 * színe (low = piros, mid = narancs, high = zöld) aszerint, melyik sávba esik.
	 *
	 * @param int    $current Jelenlegi érték.
	 * @param int    $floor   Minimum (piros).
	 * @param int    $ok      Elfogadható (narancs).
	 * @param int    $ideal   Ideális (zöld).
	 * @param string $unit    Mértékegység (kép / karakter).
	 * @return array{scale:array,current:array}
	 */
	private static function tier_metrics( $current, $floor, $ok, $ideal, $unit ) {
		$fmt  = static function ( $n, $plus = false ) use ( $unit ) {
			return sprintf( '%d%s %s', $n, $plus ? '+' : '', $unit );
		};
		$tone = $current >= $ideal ? 'high' : ( $current >= $ok ? 'mid' : 'low' );

		return array(
			'scale'   => array(
				array( 'label' => __( 'Minimum', 'ki-mit-adna-erte' ), 'value' => $fmt( $floor ), 'tone' => 'low' ),
				array( 'label' => __( 'Elfogadható', 'ki-mit-adna-erte' ), 'value' => $fmt( $ok ), 'tone' => 'mid' ),
				array( 'label' => __( 'Ideális', 'ki-mit-adna-erte' ), 'value' => $fmt( $ideal, true ), 'tone' => 'high' ),
			),
			'current' => array( 'label' => __( 'Jelenleg', 'ki-mit-adna-erte' ), 'value' => $fmt( $current ), 'tone' => $tone ),
		);
	}

	/**
	 * „Most nem, megnézem a hirdetésemet”: az első hirdetés tippjei elrejtve.
	 *
	 * @param int $user_id    Hirdetés tulajdonosa.
	 * @param int $listing_id Hirdetés ID.
	 */
	public static function dismiss_first_listing_tips( $user_id, $listing_id ) {
		update_user_meta( (int) $user_id, self::META_FIRST_TIPS_DISMISSED, (int) $listing_id );
	}

	/**
	 * @param WP_Post $listing Listing.
	 * @return array
	 */
	private static function format_listing_row( $listing ) {
		$status  = KMAE_Meta::get_status( $listing->ID );
		$labels  = KMAE_Meta::status_labels();
		$gallery = KMAE_Query::get_gallery_ids( $listing->ID );
		$thumb   = ! empty( $gallery ) ? KMAE_Images::get_url( (int) $gallery[0], 'thumbnail' ) : '';
		$price_row = KMAE_Seller::get_public_price_row( $listing->ID );
		if ( $price_row ) {
			$price = KMAE_Seller::PRICE_GUIDE === $price_row['mode']
				? $price_row['label'] . ': ' . $price_row['formatted']
				: $price_row['formatted'];
		} else {
			$price = __( 'Ajánlatokat vár', 'ki-mit-adna-erte' );
		}

		$edit_url = add_query_arg( 'kmae_edit', $listing->ID, home_url( '/hirdetes-feladasa/' ) );
		if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ) {
			$edit_url = add_query_arg( 'kmae_photos', '1', $edit_url );
		}

		return array(
			'id'       => (int) $listing->ID,
			'title'    => $listing->post_title,
			'meta'     => KMAE_Vehicle::get_swipe_subtitle( $listing->ID ) ?: '',
			'price'    => $price,
			'status'   => $status,
			'label'    => $labels[ $status ] ?? $status,
			'thumb'    => $thumb,
			'url'      => KMAE_Meta::STATUS_AWAITING_PHOTOS === $status ? $edit_url : get_permalink( $listing->ID ),
			'views'    => self::get_listing_views( $listing->ID ),
		);
	}

	/**
	 * @param int $delta Week-over-week delta.
	 * @return string
	 */
	private static function format_view_delta( $delta ) {
		if ( $delta > 0 ) {
			return sprintf(
				/* translators: %d: delta */
				__( '↑ %d a múlt héthez', 'ki-mit-adna-erte' ),
				$delta
			);
		}
		if ( $delta < 0 ) {
			return sprintf(
				/* translators: %d: abs delta */
				__( '↓ %d a múlt héthez', 'ki-mit-adna-erte' ),
				abs( $delta )
			);
		}
		return __( 'Nincs változás a múlt héthez', 'ki-mit-adna-erte' );
	}

	/**
	 * @param string $name Display name.
	 * @return string
	 */
	public static function initials( $name ) {
		$name  = trim( (string) $name );
		$parts = preg_split( '/\s+/u', $name ) ?: array();
		$out   = '';
		foreach ( array_slice( $parts, 0, 2 ) as $part ) {
			$out .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
		}
		return $out ?: 'A';
	}

	/**
	 * @param string $datetime MySQL datetime.
	 * @return string
	 */
	public static function relative_time( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		$ts = strtotime( $datetime );
		if ( ! $ts ) {
			return '';
		}
		return human_time_diff( $ts, current_time( 'timestamp' ) ) . ' ' . __( 'óta', 'ki-mit-adna-erte' );
	}
}
