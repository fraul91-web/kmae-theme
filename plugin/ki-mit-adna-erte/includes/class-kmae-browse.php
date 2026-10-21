<?php
/**
 * Browse personalization: favorites, resurface passes.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Browse {

	const PASSED_META       = '_kmae_browse_passed';
	const PASSED_INTERVAL   = 20;
	const FAVORITE_META     = '_kmae_favorite_listings';
	/* Hirdetések, amelyekre a vevő már írt / ajánlatot tett: a böngészésben nem jönnek újra.
	   Saját lista, nem a beszélgetésekből számolva, így a chat törlése után is megmarad. */
	const CONTACTED_META    = '_kmae_browse_contacted';

	/* Céges felhasználók listanézete a böngészésben (`?nezet=lista|kartya`, a választás megmarad). */
	const VIEW_META         = 'kmae_browse_view';
	const LIST_PER_PAGE     = 50;
	const LIST_SCAN         = 1000;

	/**
	 * Listanézet aktív-e? Minden belépett fióknak; a választást user metában őrizzük.
	 *
	 * @return bool
	 */
	public static function is_list_view() {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$cached = false;
			return $cached;
		}
		$saved = (string) get_user_meta( $user_id, self::VIEW_META, true );
		if ( isset( $_GET['nezet'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$asked = sanitize_key( wp_unslash( $_GET['nezet'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $asked, array( 'lista', 'kartya' ), true ) && $asked !== $saved ) {
				update_user_meta( $user_id, self::VIEW_META, $asked );
				$saved = $asked;
			}
		}
		$cached = 'lista' === $saved;
		return $cached;
	}

	/* ------------------------------------------------------------------
	   Rendezés (minden felhasználónak): `?rendez=…`, távolságnál `lat`, `lon`, `km`.
	   ------------------------------------------------------------------ */

	const SORT_DEFAULT = 'ajanlott';
	const RADIUS_STEPS = array( 10, 25, 50, 100, 200 );

	/** @var array<int,float> Hirdetés → távolság km-ben (a sablonok olvassák). */
	private static $distances = array();

	/**
	 * Választható rendezések. „Ki mit adna érte” módban nincs ár szerinti.
	 *
	 * @param string $mod Böngészési mód (kp / ajanlat / mind).
	 * @return array<string,array{label:string,icon:string}>
	 */
	public static function sort_options( $mod = '' ) {
		$options = array(
			'ajanlott'     => array( 'label' => __( 'Ajánlott', 'ki-mit-adna-erte' ), 'icon' => 'sparkles' ),
			'tavolsag'     => array( 'label' => __( 'Távolság szerint', 'ki-mit-adna-erte' ), 'icon' => 'crosshair' ),
			'legujabb'     => array( 'label' => __( 'Legújabb hirdetés', 'ki-mit-adna-erte' ), 'icon' => 'clock' ),
			'ar_novekvo'   => array( 'label' => __( 'Ár: olcsóbb elöl', 'ki-mit-adna-erte' ), 'icon' => 'banknote' ),
			'ar_csokkeno'  => array( 'label' => __( 'Ár: drágább elöl', 'ki-mit-adna-erte' ), 'icon' => 'banknote' ),
			'evjarat'      => array( 'label' => __( 'Évjárat: újabb elöl', 'ki-mit-adna-erte' ), 'icon' => 'calendar' ),
			'km'           => array( 'label' => __( 'Kilométer: kevesebb elöl', 'ki-mit-adna-erte' ), 'icon' => 'gauge' ),
			'teljesitmeny' => array( 'label' => __( 'Teljesítmény: erősebb elöl', 'ki-mit-adna-erte' ), 'icon' => 'zap' ),
			'nev'          => array( 'label' => __( 'Márka és modell (A–Z)', 'ki-mit-adna-erte' ), 'icon' => 'list' ),
		);
		if ( KMAE_Vehicle::BROWSE_AJANLAT === $mod ) {
			unset( $options['ar_novekvo'], $options['ar_csokkeno'] );
		}
		return $options;
	}

	/**
	 * A kért rendezés és a távolság paraméterei (érvénytelen kérésnél az alapértelmezés).
	 *
	 * @param string $mod Böngészési mód.
	 * @return array{sort:string,lat:float,lon:float,km:int}
	 */
	public static function current_sort( $mod = '' ) {
		$sort = isset( $_GET['rendez'] ) ? sanitize_key( wp_unslash( $_GET['rendez'] ) ) : self::SORT_DEFAULT; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( self::sort_options( $mod )[ $sort ] ) ) {
			$sort = self::SORT_DEFAULT;
		}
		$lat = isset( $_GET['lat'] ) ? (float) wp_unslash( $_GET['lat'] ) : 0.0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lon = isset( $_GET['lon'] ) ? (float) wp_unslash( $_GET['lon'] ) : 0.0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$km  = isset( $_GET['km'] ) ? absint( $_GET['km'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'tavolsag' === $sort && ( $lat < 45.6 || $lat > 48.7 || $lon < 16.0 || $lon > 23.0 ) ) {
			$sort = self::SORT_DEFAULT; /* hely nélkül nincs távolság */
		}
		return array(
			'sort' => $sort,
			'lat'  => $lat,
			'lon'  => $lon,
			'km'   => min( $km, 1000 ),
		);
	}

	/**
	 * Kell-e a teljes találati halmaz (nem csak az első adag)?
	 */
	public static function needs_full_scan( $mod = '' ) {
		return self::is_list_view() || self::SORT_DEFAULT !== self::current_sort( $mod )['sort'];
	}

	/**
	 * Távolság egy hirdetésig (csak távolság szerinti rendezésnél van értéke).
	 *
	 * @param int $listing_id Listing.
	 * @return float|null
	 */
	public static function distance_for( $listing_id ) {
		return self::$distances[ (int) $listing_id ] ?? null;
	}

	/**
	 * Rendezés (és távolságnál a sugár szerinti szűkítés). Ajánlottnál nem nyúl a sorrendhez.
	 *
	 * @param WP_Post[] $listings Hirdetések.
	 * @param string    $mod      Böngészési mód.
	 * @return WP_Post[]
	 */
	public static function apply_sort( array $listings, $mod = '' ) {
		$req  = self::current_sort( $mod );
		$sort = $req['sort'];
		if ( self::SORT_DEFAULT === $sort || ! $listings ) {
			return $listings;
		}

		$keyed = array();
		foreach ( array_values( $listings ) as $position => $listing ) {
			$id    = (int) $listing->ID;
			$specs = KMAE_Vehicle::get_specs( $id );
			$value = null;
			switch ( $sort ) {
				case 'tavolsag':
					$coords = class_exists( 'KMAE_Geo' ) ? KMAE_Geo::listing_coords( $id ) : null;
					if ( $coords ) {
						$value                  = KMAE_Geo::distance_km( $req['lat'], $req['lon'], $coords[0], $coords[1] );
						self::$distances[ $id ] = $value;
					}
					if ( $req['km'] > 0 && ( null === $value || $value > $req['km'] ) ) {
						continue 2; /* a körön kívül esik */
					}
					break;
				case 'legujabb':
					$value = - (int) get_post_time( 'U', true, $listing );
					break;
				case 'ar_novekvo':
				case 'ar_csokkeno':
					$price = KMAE_Seller::get_public_price_row( $id );
					$value = $price ? (float) $price['amount'] : null;
					if ( null !== $value && 'ar_csokkeno' === $sort ) {
						$value = -$value;
					}
					break;
				case 'evjarat':
					$value = ! empty( $specs['year'] ) ? - (int) $specs['year'] : null;
					break;
				case 'km':
					$value = (int) $specs['mileage'] > 0 ? (int) $specs['mileage'] : null;
					break;
				case 'teljesitmeny':
					$value = ! empty( $specs['power_kw'] ) ? - (int) $specs['power_kw'] : null;
					break;
				case 'nev':
					$value = remove_accents( mb_strtolower( trim( $specs['brand'] . ' ' . $specs['model'] ) ) );
					$value = '' !== $value ? $value : null;
					break;
			}
			$keyed[] = array( $value, $position, $listing );
		}

		/* Ismeretlen érték (pl. ár nélküli „Ki mit adna érte”) a végére; egyezésnél marad az ajánlott sorrend. */
		usort(
			$keyed,
			static function ( $a, $b ) {
				if ( null === $a[0] || null === $b[0] ) {
					return ( null === $a[0] ) <=> ( null === $b[0] ) ?: $a[1] <=> $b[1];
				}
				$cmp = is_string( $a[0] ) ? strcmp( $a[0], $b[0] ) : ( $a[0] <=> $b[0] );
				return 0 !== $cmp ? $cmp : $a[1] <=> $b[1];
			}
		);

		return array_map(
			static function ( $row ) {
				return $row[2];
			},
			$keyed
		);
	}

	/**
	 * @return int[]
	 */
	public static function get_passed_listing_ids( $user_id ) {
		$passed = get_user_meta( (int) $user_id, self::PASSED_META, true );
		if ( ! is_array( $passed ) ) {
			return array();
		}

		return array_values( array_unique( array_map( 'intval', $passed ) ) );
	}

	public static function record_pass( $user_id, $listing_id ) {
		$user_id    = (int) $user_id;
		$listing_id = (int) $listing_id;
		if ( $user_id <= 0 || $listing_id <= 0 ) {
			return false;
		}

		$passed   = self::get_passed_listing_ids( $user_id );
		$passed[] = $listing_id;
		$passed   = array_values( array_unique( $passed ) );

		update_user_meta( $user_id, self::PASSED_META, $passed );
		return true;
	}

	/**
	 * Listings this buyer already wrote to or made an offer on (hidden from
	 * browse). Older chats from before the list existed are added once.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_contacted_listing_ids( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}

		$saved = get_user_meta( $user_id, self::CONTACTED_META, true );
		$saved = is_array( $saved ) ? array_map( 'intval', $saved ) : array();
		$chats = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT c.listing_id FROM ' . KMAE_Database::table( 'conversations' ) . ' c INNER JOIN ' . KMAE_Database::table( 'conversation_messages' ) . ' m ON m.conversation_id = c.id AND m.sender_id = c.buyer_id WHERE c.buyer_id = %d', $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all   = array_values( array_unique( array_filter( array_merge( $saved, array_map( 'intval', (array) $chats ) ) ) ) );
		if ( count( $all ) !== count( array_unique( array_filter( $saved ) ) ) ) {
			update_user_meta( $user_id, self::CONTACTED_META, $all );
		}
		return $all;
	}

	/**
	 * The buyer wrote or sent an offer on a listing: hide it from their browse.
	 *
	 * @param int $user_id    Buyer ID.
	 * @param int $listing_id Listing ID.
	 */
	public static function remember_contacted( $user_id, $listing_id ) {
		$user_id    = (int) $user_id;
		$listing_id = (int) $listing_id;
		if ( $user_id <= 0 || $listing_id <= 0 ) {
			return;
		}

		$saved = get_user_meta( $user_id, self::CONTACTED_META, true );
		$saved = is_array( $saved ) ? array_map( 'intval', $saved ) : array();
		if ( in_array( $listing_id, $saved, true ) ) {
			return;
		}
		$saved[] = $listing_id;
		update_user_meta( $user_id, self::CONTACTED_META, $saved );
	}

	/**
	 * Saved listing IDs for this user, newest first. Other users' lists are never read.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_favorite_listing_ids( $user_id ) {
		$saved = get_user_meta( (int) $user_id, self::FAVORITE_META, true );
		if ( ! is_array( $saved ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $saved ) ) ) );
	}

	/**
	 * @param int $user_id    User ID.
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function is_favorite( $user_id, $listing_id ) {
		return in_array( (int) $listing_id, self::get_favorite_listing_ids( $user_id ), true );
	}

	/**
	 * Remember a published listing. A second save does not duplicate it.
	 *
	 * @param int $user_id    User ID.
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public static function add_favorite( $user_id, $listing_id ) {
		$user_id    = (int) $user_id;
		$listing_id = (int) $listing_id;
		$listing    = $listing_id > 0 ? get_post( $listing_id ) : null;
		if ( $user_id <= 0 || ! $listing || 'kmae_listing' !== $listing->post_type || 'publish' !== $listing->post_status ) {
			return false;
		}

		$saved = self::get_favorite_listing_ids( $user_id );
		if ( in_array( $listing_id, $saved, true ) ) {
			return true;
		}

		array_unshift( $saved, $listing_id );
		update_user_meta( $user_id, self::FAVORITE_META, $saved );
		self::remember_unseen_favorite( $user_id, $listing_id );
		/* Élő főoldal: az eladó „Kedvencek” száma azonnal frissüljön. */
		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( (int) get_post_field( 'post_author', $listing_id ) );
		}
		return true;
	}

	/**
	 * Drop one listing from this user's list. The listing post stays published.
	 *
	 * @param int $user_id    User ID.
	 * @param int $listing_id Listing ID.
	 */
	public static function remove_favorite( $user_id, $listing_id ) {
		$user_id    = (int) $user_id;
		$listing_id = (int) $listing_id;
		if ( $user_id <= 0 || $listing_id <= 0 ) {
			return;
		}

		self::forget_unseen_favorite( $user_id, $listing_id );
		/* Élő főoldal: az eladó „Kedvencek” száma azonnal frissüljön. */
		if ( class_exists( 'KMAE_Live_Signal' ) ) {
			KMAE_Live_Signal::bump( (int) get_post_field( 'post_author', $listing_id ) );
		}

		$saved = array_values(
			array_filter(
				self::get_favorite_listing_ids( $user_id ),
				static function ( $id ) use ( $listing_id ) {
					return (int) $id !== $listing_id;
				}
			)
		);
		if ( empty( $saved ) ) {
			delete_user_meta( $user_id, self::FAVORITE_META );
			return;
		}
		update_user_meta( $user_id, self::FAVORITE_META, $saved );
	}

	/**
	 * Frissen szívre tett hirdetések, amíg a kedvencek oldalt meg nem nyitja.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function count_unseen_favorites( $user_id ) {
		return count( self::unseen_favorite_ids( $user_id ) );
	}

	/**
	 * A kedvencek oldal megnyitása lenullázza a fejléc piros számát.
	 *
	 * @param int $user_id User ID.
	 */
	public static function mark_favorites_seen( $user_id ) {
		delete_user_meta( (int) $user_id, '_kmae_favorite_unseen' );
	}

	/**
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	private static function unseen_favorite_ids( $user_id ) {
		$ids = get_user_meta( (int) $user_id, '_kmae_favorite_unseen', true );
		if ( ! is_array( $ids ) ) {
			return array();
		}

		$ids     = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		$live    = array();
		$changed = false;
		foreach ( $ids as $listing_id ) {
			if ( class_exists( 'KMAE_Database' ) && KMAE_Database::listing_target_is_live( $listing_id ) ) {
				$live[] = $listing_id;
			} else {
				$changed = true;
			}
		}
		if ( $changed ) {
			if ( empty( $live ) ) {
				delete_user_meta( (int) $user_id, '_kmae_favorite_unseen' );
			} else {
				update_user_meta( (int) $user_id, '_kmae_favorite_unseen', $live );
			}
		}

		return $live;
	}

	/**
	 * @param int $user_id    User ID.
	 * @param int $listing_id Listing ID.
	 */
	private static function remember_unseen_favorite( $user_id, $listing_id ) {
		$ids = self::unseen_favorite_ids( $user_id );
		if ( in_array( (int) $listing_id, $ids, true ) ) {
			return;
		}
		array_unshift( $ids, (int) $listing_id );
		update_user_meta( (int) $user_id, '_kmae_favorite_unseen', $ids );
	}

	/**
	 * @param int $user_id    User ID.
	 * @param int $listing_id Listing ID.
	 */
	private static function forget_unseen_favorite( $user_id, $listing_id ) {
		$listing_id = (int) $listing_id;
		$ids        = array_values(
			array_filter(
				self::unseen_favorite_ids( $user_id ),
				static function ( $id ) use ( $listing_id ) {
					return (int) $id !== $listing_id;
				}
			)
		);
		update_user_meta( (int) $user_id, '_kmae_favorite_unseen', $ids );
	}

	/**
	 * Empty this user's saved list. Listing posts are untouched.
	 *
	 * @param int $user_id User ID.
	 */
	public static function clear_favorites( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id > 0 ) {
			delete_user_meta( $user_id, self::FAVORITE_META );
		}
	}

	/**
	 * Published listings still on this user's list, newest first.
	 *
	 * @param int $user_id User ID.
	 * @return WP_Post[]
	 */
	public static function favorite_listings( $user_id ) {
		$posts = array();
		foreach ( self::get_favorite_listing_ids( $user_id ) as $listing_id ) {
			$listing = get_post( $listing_id );
			if ( $listing && 'kmae_listing' === $listing->post_type && 'publish' === $listing->post_status ) {
				$posts[] = $listing;
			}
		}
		return $posts;
	}

	/**
	 * Fresh listings first; previously passed ones every Nth slot.
	 *
	 * @param WP_Post[] $listings
	 * @return WP_Post[]
	 */
	public static function personalize_listings( $listings, $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || empty( $listings ) ) {
			return $listings;
		}

		$passed_ids = array_flip( self::get_passed_listing_ids( $user_id ) );
		$fresh      = array();
		$passed     = array();

		foreach ( $listings as $listing ) {
			$id = (int) $listing->ID;
			if ( isset( $passed_ids[ $id ] ) ) {
				$passed[] = $listing;
			} else {
				$fresh[] = $listing;
			}
		}

		if ( empty( $fresh ) ) {
			return $passed;
		}

		if ( empty( $passed ) ) {
			return $fresh;
		}

		return self::merge_with_passed_interval( $fresh, $passed, self::PASSED_INTERVAL );
	}

	/**
	 * @param WP_Post[] $fresh
	 * @param WP_Post[] $passed
	 * @return WP_Post[]
	 */
	public static function merge_with_passed_interval( array $fresh, array $passed, $interval = 20 ) {
		$interval      = max( 1, (int) $interval );
		$result        = array();
		$passed_index  = 0;
		$passed_count  = count( $passed );

		foreach ( $fresh as $i => $listing ) {
			$result[] = $listing;
			if ( 0 === ( ( $i + 1 ) % $interval ) ) {
				$result[] = $passed[ $passed_index % $passed_count ];
				++$passed_index;
			}
		}

		return $result;
	}

	/**
	 * Parse browse wizard params from request.
	 *
	 * @return array{county:string,bodies:string[],mod:string,kp_min:int,kp_max:int}
	 */
	public static function parse_wizard_params( $source = null ) {
		$get = null === $source ? $_GET : $source;

		return array(
			'county'  => isset( $get['megye'] ) ? sanitize_text_field( wp_unslash( $get['megye'] ) ) : '',
			'bodies'  => self::parse_bodies_param( $get['kivitel'] ?? '' ),
			'mod'     => isset( $get['mod'] ) ? sanitize_key( wp_unslash( $get['mod'] ) ) : '',
			'kp_min'  => isset( $get['kp_min'] ) ? absint( $get['kp_min'] ) : 0,
			'kp_max'  => isset( $get['kp_max'] ) ? absint( $get['kp_max'] ) : 0,
			'brand'   => isset( $get['marka'] ) ? sanitize_text_field( wp_unslash( $get['marka'] ) ) : '',
			'model'   => isset( $get['modell'] ) ? sanitize_text_field( wp_unslash( $get['modell'] ) ) : '',
			'blocked' => self::parse_blocked_param( $get['tilt'] ?? array() ),
			'cc_min'  => isset( $get['ccm_min'] ) ? absint( $get['ccm_min'] ) : 0,
			'cc_max'  => isset( $get['ccm_max'] ) ? absint( $get['ccm_max'] ) : 0,
			'hp_min'  => isset( $get['le_min'] ) ? absint( $get['le_min'] ) : 0,
			'hp_max'  => isset( $get['le_max'] ) ? absint( $get['le_max'] ) : 0,
			'year_min' => isset( $get['ev_min'] ) ? absint( $get['ev_min'] ) : 0,
			'year_max' => isset( $get['ev_max'] ) ? absint( $get['ev_max'] ) : 0,
			'km_min'  => isset( $get['km_min'] ) ? absint( $get['km_min'] ) : 0,
			'km_max'  => isset( $get['km_max'] ) ? absint( $get['km_max'] ) : 0,
			'fuel'    => isset( $get['uzem'] ) ? sanitize_key( wp_unslash( $get['uzem'] ) ) : '',
			'gear'    => isset( $get['valto'] ) ? sanitize_key( wp_unslash( $get['valto'] ) ) : '',
			'mot'     => isset( $get['muszaki'] ) ? sanitize_key( wp_unslash( $get['muszaki'] ) ) : '',
			'mot_min' => isset( $get['mot_min'] ) ? absint( $get['mot_min'] ) : 0,
			'mot_max' => isset( $get['mot_max'] ) ? absint( $get['mot_max'] ) : 0,
			'mot_expired' => isset( $get['mot_lejart'] ) && '1' === (string) $get['mot_lejart'],
			'conditions' => self::parse_csv_keys( $get['allapot'] ?? '', array_keys( KMAE_Vehicle::condition_labels() ) ),
			'prices'  => self::parse_csv_keys( $get['ar'] ?? '', array( KMAE_Seller::PRICE_FIXED, KMAE_Seller::PRICE_GUIDE, KMAE_Seller::PRICE_OPEN ) ),
		);
	}

	/**
	 * Tiltó lista: `tilt[]=Márka` (az egész márka) vagy `tilt[]=Márka|Modell`.
	 *
	 * @param mixed $raw Query value (array of "Márka" / "Márka|Modell").
	 * @return array<int,array{brand:string,model:string}>
	 */
	public static function parse_blocked_param( $raw ) {
		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = array( $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$blocked = array();
		$seen    = array();
		foreach ( $raw as $item ) {
			if ( ! is_string( $item ) ) {
				continue;
			}
			$parts = explode( '|', sanitize_text_field( wp_unslash( $item ) ), 2 );
			$brand = trim( $parts[0] );
			$model = isset( $parts[1] ) ? trim( $parts[1] ) : '';
			if ( '' === $brand ) {
				continue;
			}
			$key = mb_strtolower( $brand . '|' . $model );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$blocked[]    = array(
				'brand' => $brand,
				'model' => $model,
			);
			if ( count( $blocked ) >= 40 ) {
				break;
			}
		}
		return $blocked;
	}

	/**
	 * @param mixed    $raw   Comma list or array.
	 * @param string[] $valid Allowed keys. "mind" short-circuits to empty (no filter).
	 * @return string[]
	 */
	public static function parse_csv_keys( $raw, array $valid ) {
		if ( is_array( $raw ) ) {
			$parts = $raw;
		} elseif ( is_string( $raw ) && '' !== $raw ) {
			$parts = explode( ',', $raw );
		} else {
			return array();
		}

		$keys = array();
		foreach ( $parts as $part ) {
			$slug = sanitize_key( is_string( $part ) ? trim( $part ) : '' );
			if ( 'mind' === $slug ) {
				return array();
			}
			if ( in_array( $slug, $valid, true ) ) {
				$keys[] = $slug;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * County query may be "orszag" or a comma list of real counties.
	 *
	 * @param string $county Raw megye param.
	 * @return string[] Real county slugs. Empty means the whole country.
	 */
	public static function county_slugs( $county ) {
		$county = sanitize_text_field( (string) $county );
		if ( '' === $county || KMAE_Counties::ORSZAG === $county ) {
			return array();
		}

		$slugs = array();
		foreach ( explode( ',', $county ) as $part ) {
			$slug = sanitize_key( trim( $part ) );
			if ( KMAE_Counties::is_listing_county( $slug ) ) {
				$slugs[] = $slug;
			}
		}
		return array_values( array_unique( $slugs ) );
	}

	/**
	 * @param string $county Raw megye param.
	 */
	public static function counties_are_valid( $county ) {
		$county = sanitize_text_field( (string) $county );
		if ( '' === $county ) {
			return false;
		}
		if ( KMAE_Counties::ORSZAG === $county ) {
			return true;
		}
		$parts = array_filter( array_map( 'trim', explode( ',', $county ) ) );
		if ( empty( $parts ) ) {
			return false;
		}
		foreach ( $parts as $part ) {
			if ( ! KMAE_Counties::is_listing_county( sanitize_key( $part ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param mixed $raw kivitel query value (string or array).
	 * @return string[]
	 */
	public static function parse_bodies_param( $raw ) {
		if ( is_array( $raw ) ) {
			$parts = $raw;
		} elseif ( is_string( $raw ) && '' !== $raw ) {
			$parts = explode( ',', $raw );
		} else {
			return array();
		}

		$valid  = array_keys( KMAE_Vehicle::body_types() );
		$bodies = array();

		foreach ( $parts as $part ) {
			$slug = sanitize_key( is_string( $part ) ? trim( $part ) : '' );
			if ( '' === $slug ) {
				continue;
			}
			if ( 'mind' === $slug ) {
				return array( 'mind' );
			}
			if ( in_array( $slug, $valid, true ) ) {
				$bodies[] = $slug;
			}
		}

		return array_values( array_unique( $bodies ) );
	}

	/**
	 * @param string[] $bodies
	 */
	public static function bodies_to_query_value( array $bodies ) {
		if ( empty( $bodies ) ) {
			return '';
		}
		if ( in_array( 'mind', $bodies, true ) ) {
			return 'mind';
		}
		return implode( ',', $bodies );
	}

	/**
	 * @param string[] $bodies
	 */
	public static function bodies_label( array $bodies ) {
		if ( empty( $bodies ) ) {
			return '';
		}
		if ( in_array( 'mind', $bodies, true ) ) {
			return __( 'Minden kivitel', 'ki-mit-adna-erte' );
		}

		$types  = KMAE_Vehicle::body_types();
		$labels = array();
		foreach ( $bodies as $slug ) {
			if ( isset( $types[ $slug ] ) ) {
				$labels[] = $types[ $slug ]['label'];
			}
		}

		return implode( ', ', $labels );
	}

	public static function is_wizard_complete( $wizard ) {
		if ( empty( $wizard['county'] ) || ! self::counties_are_valid( $wizard['county'] ) ) {
			return false;
		}
		if ( empty( $wizard['bodies'] ) ) {
			return false;
		}
		if ( empty( $wizard['mod'] ) ) {
			return false;
		}

		switch ( $wizard['mod'] ) {
			case 'mind':
			case KMAE_Vehicle::BROWSE_AJANLAT:
				return true;
			case KMAE_Vehicle::BROWSE_KP:
				return $wizard['kp_min'] > 0 && $wizard['kp_max'] >= $wizard['kp_min'];
		}

		return false;
	}

	/**
	 * Filter listings by vehicle browse wizard rules.
	 *
	 * @param WP_Post[] $listings
	 * @param array     $wizard
	 * @return WP_Post[]
	 */
	public static function filter_by_wizard( array $listings, array $wizard ) {
		if ( ! KMAE_Vehicle::is_vehicle_launch() || empty( $wizard['mod'] ) ) {
			return $listings;
		}

		$filtered = array();

		foreach ( $listings as $listing ) {
			if ( ! self::listing_matches_wizard( $listing->ID, $wizard ) ) {
				continue;
			}
			$filtered[] = $listing;
		}

		return $filtered;
	}

	public static function listing_matches_wizard( $listing_id, array $wizard ) {
		$bodies = $wizard['bodies'] ?? array();

		if ( ! empty( $bodies ) && ! in_array( 'mind', $bodies, true ) ) {
			$body = KMAE_Vehicle::normalize_body( get_post_meta( $listing_id, KMAE_Vehicle::META_BODY, true ) );
			if ( ! in_array( $body, $bodies, true ) ) {
				return false;
			}
		}

		switch ( $wizard['mod'] ) {
			case 'mind':
				break;

			case KMAE_Vehicle::BROWSE_AJANLAT:
				if ( ! KMAE_Seller::is_open_price( $listing_id ) ) {
					return false;
				}
				break;

			case KMAE_Vehicle::BROWSE_KP:
				if ( KMAE_Seller::is_open_price( $listing_id ) ) {
					return false;
				}

				$offer_types = KMAE_Meta::get_offer_types( $listing_id );
				$value       = KMAE_Vehicle::get_vehicle_value( $listing_id );
				if ( $value <= 0 ) {
					return false;
				}
				if ( ! in_array( KMAE_OFFER_TYPE_CASH, $offer_types, true ) ) {
					return false;
				}
				$min = (int) $wizard['kp_min'];
				$max = (int) $wizard['kp_max'];
				if ( $value < $min || $value > $max ) {
					return false;
				}
				break;

			default:
				break;
		}

		return self::listing_matches_detail_filters( $listing_id, $wizard );
	}

	/**
	 * Brand, model and the technical filters from the left search rail.
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $wizard     Parsed wizard params.
	 */
	private static function listing_matches_detail_filters( $listing_id, array $wizard ) {
		$counties = self::county_slugs( $wizard['county'] ?? '' );
		if ( $counties ) {
			$county = sanitize_key( (string) get_post_meta( $listing_id, '_kmae_county', true ) );
			if ( ! in_array( $county, $counties, true ) ) {
				return false;
			}
		}

		$blocked = $wizard['blocked'] ?? array();
		if ( $blocked ) {
			$have_brand = trim( (string) get_post_meta( $listing_id, KMAE_Vehicle::META_BRAND, true ) );
			$have_model = trim( (string) get_post_meta( $listing_id, KMAE_Vehicle::META_MODEL, true ) );
			foreach ( $blocked as $block ) {
				if ( 0 !== strcasecmp( $have_brand, $block['brand'] ) ) {
					continue;
				}
				if ( '' === $block['model'] || 0 === strcasecmp( $have_model, $block['model'] ) ) {
					return false;
				}
			}
		}

		$brand = trim( (string) ( $wizard['brand'] ?? '' ) );
		if ( '' !== $brand ) {
			$have = (string) get_post_meta( $listing_id, KMAE_Vehicle::META_BRAND, true );
			if ( 0 !== strcasecmp( $have, $brand ) ) {
				return false;
			}
		}

		$model = trim( (string) ( $wizard['model'] ?? '' ) );
		if ( '' !== $model ) {
			$have = (string) get_post_meta( $listing_id, KMAE_Vehicle::META_MODEL, true );
			if ( 0 !== strcasecmp( $have, $model ) ) {
				return false;
			}
		}

		if ( ! self::int_in_range( (int) get_post_meta( $listing_id, KMAE_Vehicle::META_ENGINE_CC, true ), (int) ( $wizard['cc_min'] ?? 0 ), (int) ( $wizard['cc_max'] ?? 0 ) ) ) {
			return false;
		}

		$hp = KMAE_Vehicle::kw_to_horsepower( (int) get_post_meta( $listing_id, KMAE_Vehicle::META_POWER_KW, true ) );
		if ( ! self::int_in_range( $hp, (int) ( $wizard['hp_min'] ?? 0 ), (int) ( $wizard['hp_max'] ?? 0 ) ) ) {
			return false;
		}

		if ( ! self::int_in_range( (int) get_post_meta( $listing_id, KMAE_Vehicle::META_YEAR, true ), (int) ( $wizard['year_min'] ?? 0 ), (int) ( $wizard['year_max'] ?? 0 ) ) ) {
			return false;
		}

		if ( ! self::int_in_range( (int) get_post_meta( $listing_id, KMAE_Vehicle::META_MILEAGE, true ), (int) ( $wizard['km_min'] ?? 0 ), (int) ( $wizard['km_max'] ?? 0 ) ) ) {
			return false;
		}

		$fuel = sanitize_key( (string) ( $wizard['fuel'] ?? '' ) );
		if ( '' !== $fuel && 'mind' !== $fuel ) {
			if ( sanitize_key( (string) get_post_meta( $listing_id, KMAE_Vehicle::META_FUEL, true ) ) !== $fuel ) {
				return false;
			}
		}

		$gear = sanitize_key( (string) ( $wizard['gear'] ?? '' ) );
		if ( '' !== $gear && 'mind' !== $gear ) {
			if ( sanitize_key( (string) get_post_meta( $listing_id, KMAE_Vehicle::META_TRANSMISSION, true ) ) !== $gear ) {
				return false;
			}
		}

		$conditions = $wizard['conditions'] ?? array();
		if ( $conditions ) {
			$cond = sanitize_key( (string) get_post_meta( $listing_id, '_kmae_condition', true ) );
			if ( ! in_array( $cond, $conditions, true ) ) {
				return false;
			}
		}

		$prices = $wizard['prices'] ?? array();
		if ( $prices && ! in_array( KMAE_Seller::get_price_mode( $listing_id ), $prices, true ) ) {
			return false;
		}

		return self::mot_matches( $listing_id, $wizard );
	}

	/**
	 * @param int $value Stored number.
	 * @param int $min   0 means no lower bound.
	 * @param int $max   0 means no upper bound.
	 */
	private static function int_in_range( $value, $min, $max ) {
		if ( $min > 0 && $value < $min ) {
			return false;
		}
		if ( $max > 0 && $value > $max ) {
			return false;
		}
		return true;
	}

	/**
	 * Remaining technical-inspection months. Empty or past dates count as expired.
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $wizard     Parsed wizard params.
	 */
	private static function mot_matches( $listing_id, array $wizard ) {
		$mode = sanitize_key( (string) ( $wizard['mot'] ?? '' ) );
		if ( '' === $mode || 'mind' === $mode ) {
			return true;
		}

		$raw = (string) get_post_meta( $listing_id, KMAE_Vehicle::META_MOT, true );
		$left = self::mot_months_left( $raw );
		$expired = null === $left || $left < 0;
		if ( ! empty( $wizard['mot_expired'] ) && $expired ) {
			return true;
		}
		if ( $expired ) {
			return false;
		}

		$min = (int) ( $wizard['mot_min'] ?? 0 );
		$max = (int) ( $wizard['mot_max'] ?? 0 );
		if ( $min > 0 && $left < $min ) {
			return false;
		}
		if ( $max > 0 && $left > $max ) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $date Y-m-d or empty.
	 * @return int|null Months left, negative if expired, null if unknown.
	 */
	private static function mot_months_left( $date ) {
		$date = trim( $date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}
		try {
			$until = new DateTimeImmutable( $date );
			$now   = new DateTimeImmutable( 'today' );
		} catch ( Exception $e ) {
			return null;
		}
		$diff = $now->diff( $until );
		$months = ( $diff->y * 12 ) + $diff->m;
		return $diff->invert ? -$months : $months;
	}

	public static function wizard_summary_label( array $wizard ) {
		$parts = array();

		if ( ! empty( $wizard['county'] ) ) {
			$parts[] = KMAE_Counties::get_label( $wizard['county'] );
		}

		if ( ! empty( $wizard['bodies'] ) ) {
			$parts[] = self::bodies_label( $wizard['bodies'] );
		}

		$modes = KMAE_Vehicle::browse_mode_labels();
		if ( ! empty( $wizard['mod'] ) ) {
			$parts[] = $modes[ $wizard['mod'] ] ?? $wizard['mod'];
		}

		return implode( ' · ', array_filter( $parts ) );
	}
}
