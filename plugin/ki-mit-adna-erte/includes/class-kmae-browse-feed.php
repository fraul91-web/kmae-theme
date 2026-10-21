<?php
/**
 * Böngészés v2: nyilvános, gyorsítótárazható feed + személyes kiegészítő végpont.
 *
 * GET /wp-json/kmae/v1/feed       — a böngészés URL-jének paraméterei + page; 12 tétel/oldal,
 *                                   szűrés/rendezés/lapozás SQL-ben a KMAE_Browse_Index táblán.
 *                                   Mindenkinek ugyanaz (nincs user-függés), s-maxage=60.
 * GET /wp-json/kmae/v1/me/browse  — kedvencek, átlépettek, megkeresettek, saját hirdetések
 *                                   (nocache, csak belépve, X-WP-Nonce).
 *
 * Szerződés (mezők, JSON-séma, a személyes adat alkalmazása a kliensen):
 * PROJECT-MEMORY/FEATURES/BROWSE-V2.md.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Browse_Feed {

	const OPTION        = 'kmae_browse_v2';
	const REST_NS       = 'kmae/v1';
	const PER_PAGE      = 12;
	const MAX_PAGE      = 1000;
	const IDS_MAX       = 5000;
	const CACHE_CONTROL = 'public, max-age=0, s-maxage=60';

	/** A műszaki-szűrő hónap-határainak felső korlátja (efölött „soha”). */
	const MOT_MONTHS_CAP = 1200;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'rest_send_nocache_headers', array( __CLASS__, 'filter_nocache_headers' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'strip_private_headers' ), 99, 3 );
	}

	/**
	 * Kapcsoló: az opció be, VAGY admin és `?uj=1`. Kikapcsolva a régi böngészés fut, változatlanul.
	 */
	public static function is_enabled() {
		if ( get_option( self::OPTION ) ) {
			return true;
		}
		return current_user_can( 'manage_options' ) && isset( $_GET['uj'] ) && '1' === sanitize_key( wp_unslash( $_GET['uj'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/* ------------------------------------------------------------------
	   Paraméterek (tiszta függvények: csak a kapott tömbből dolgoznak)
	   ------------------------------------------------------------------ */

	/**
	 * A böngészés URL-jének paraméterei normalizálva (KMAE_Browse::parse_wizard_params + rendezés + oldal).
	 *
	 * @param array $params Nyers, slash nélküli query paraméterek (REST get_query_params()).
	 * @return array<string,mixed>
	 */
	public static function parse_query( array $params ) {
		$all    = isset( $params['osszes'] ) && is_scalar( $params['osszes'] ) && '1' === sanitize_key( (string) $params['osszes'] );
		$wizard = KMAE_Browse::parse_wizard_params( wp_slash( $params ) );
		$mod    = $all ? '' : (string) $wizard['mod'];
		$sort   = self::parse_sort( $params, $mod );

		$bodies = array_values( array_diff( (array) $wizard['bodies'], array( 'mind' ) ) );
		sort( $bodies );
		$counties = KMAE_Browse::county_slugs( $wizard['county'] );
		sort( $counties );

		$blocked = array();
		foreach ( (array) $wizard['blocked'] as $block ) {
			$pair = array( KMAE_Browse_Index::clip( strtolower( trim( $block['brand'] ) ), 191 ), KMAE_Browse_Index::clip( strtolower( trim( $block['model'] ) ), 191 ) );
			$blocked[ $pair[0] . '|' . $pair[1] ] = $pair;
		}
		ksort( $blocked );

		$conditions = (array) $wizard['conditions'];
		sort( $conditions );
		$prices = (array) $wizard['prices'];
		sort( $prices );

		$fuel     = (string) $wizard['fuel'];
		$gear     = (string) $wizard['gear'];
		$mot_mode = (string) $wizard['mot'];

		$page = isset( $params['page'] ) && is_scalar( $params['page'] ) ? absint( $params['page'] ) : 1;

		return array(
			'all'            => $all,
			'complete'       => $all || KMAE_Browse::is_wizard_complete( $wizard ),
			/* filter_by_wizard: csak járműves indulásnál és megadott módnál szűr. */
			'wizard'         => ! $all && '' !== $mod && KMAE_Vehicle::is_vehicle_launch(),
			'county_param'   => (string) $wizard['county'],
			'counties_valid' => KMAE_Browse::counties_are_valid( $wizard['county'] ),
			'counties'       => $counties,
			'bodies'         => $bodies,
			'bodies_all'     => in_array( 'mind', (array) $wizard['bodies'], true ),
			'mod'            => $mod,
			'kp_min'         => (int) $wizard['kp_min'],
			'kp_max'         => (int) $wizard['kp_max'],
			'brand'          => KMAE_Browse_Index::clip( strtolower( trim( (string) $wizard['brand'] ) ), 191 ),
			'model'          => KMAE_Browse_Index::clip( strtolower( trim( (string) $wizard['model'] ) ), 191 ),
			'blocked'        => array_values( $blocked ),
			'cc_min'         => (int) $wizard['cc_min'],
			'cc_max'         => (int) $wizard['cc_max'],
			'hp_min'         => (int) $wizard['hp_min'],
			'hp_max'         => (int) $wizard['hp_max'],
			'year_min'       => (int) $wizard['year_min'],
			'year_max'       => (int) $wizard['year_max'],
			'km_min'         => (int) $wizard['km_min'],
			'km_max'         => (int) $wizard['km_max'],
			'fuel'           => 'mind' === $fuel ? '' : $fuel,
			'gear'           => 'mind' === $gear ? '' : $gear,
			'conditions'     => $conditions,
			'prices'         => $prices,
			'mot'            => array(
				'mode'    => 'mind' === $mot_mode ? '' : $mot_mode,
				'min'     => (int) $wizard['mot_min'],
				'max'     => (int) $wizard['mot_max'],
				'expired' => ! empty( $wizard['mot_expired'] ),
			),
			'sort'           => $sort['sort'],
			'lat'            => $sort['lat'],
			'lon'            => $sort['lon'],
			'km'             => $sort['km'],
			'page'           => min( max( 1, $page ), self::MAX_PAGE ),
		);
	}

	/**
	 * = KMAE_Browse::current_sort(), de a kapott tömbből ($_GET helyett). A koordináta 2 tizedesre
	 * kerekítve (a böngésző is így küldi: browse-sort.js toFixed(2)), hogy a cache-kulcs egyezzen.
	 *
	 * @param array  $params Query.
	 * @param string $mod    Böngészési mód.
	 * @return array{sort:string,lat:float,lon:float,km:int}
	 */
	public static function parse_sort( array $params, $mod ) {
		$sort = isset( $params['rendez'] ) && is_scalar( $params['rendez'] ) ? sanitize_key( (string) $params['rendez'] ) : KMAE_Browse::SORT_DEFAULT;
		if ( ! isset( KMAE_Browse::sort_options( $mod )[ $sort ] ) ) {
			$sort = KMAE_Browse::SORT_DEFAULT;
		}
		$lat = isset( $params['lat'] ) && is_scalar( $params['lat'] ) ? (float) $params['lat'] : 0.0;
		$lon = isset( $params['lon'] ) && is_scalar( $params['lon'] ) ? (float) $params['lon'] : 0.0;
		$km  = isset( $params['km'] ) && is_scalar( $params['km'] ) ? absint( $params['km'] ) : 0;
		if ( 'tavolsag' === $sort && ( $lat < 45.6 || $lat > 48.7 || $lon < 16.0 || $lon > 23.0 ) ) {
			$sort = KMAE_Browse::SORT_DEFAULT;
		}
		if ( 'tavolsag' !== $sort ) {
			return array(
				'sort' => $sort,
				'lat'  => 0.0,
				'lon'  => 0.0,
				'km'   => 0,
			);
		}
		return array(
			'sort' => $sort,
			'lat'  => round( $lat, 2 ),
			'lon'  => round( $lon, 2 ),
			'km'   => min( $km, 1000 ),
		);
	}

	/**
	 * Kanonikus query string (fix kulcs-sorrend, csak a hatásos értékek, rendezett listák).
	 * A kliens EZT kéri le, így azonos keresés = azonos URL = egy Cloudflare-bejegyzés.
	 *
	 * @param array $params Nyers query paraméterek.
	 * @return string `?` nélkül.
	 */
	public static function canonical_query( array $params ) {
		$q     = self::parse_query( $params );
		$pairs = array();
		$add   = static function ( $key, $value ) use ( &$pairs ) {
			$pairs[] = rawurlencode( $key ) . '=' . rawurlencode( (string) $value );
		};

		if ( $q['all'] ) {
			$add( 'osszes', '1' );
		} else {
			if ( '' !== $q['county_param'] ) {
				if ( $q['counties_valid'] ) {
					$add( 'megye', $q['counties'] ? implode( ',', $q['counties'] ) : KMAE_Counties::ORSZAG );
				} else {
					$add( 'megye', $q['county_param'] );
				}
			}
			if ( $q['bodies_all'] ) {
				$add( 'kivitel', 'mind' );
			} elseif ( $q['bodies'] ) {
				$add( 'kivitel', implode( ',', $q['bodies'] ) );
			}
			if ( '' !== $q['mod'] ) {
				$add( 'mod', $q['mod'] );
			}
			if ( KMAE_Vehicle::BROWSE_KP === $q['mod'] ) {
				if ( $q['kp_min'] > 0 ) {
					$add( 'kp_min', $q['kp_min'] );
				}
				if ( $q['kp_max'] > 0 ) {
					$add( 'kp_max', $q['kp_max'] );
				}
			}
			if ( '' !== $q['brand'] ) {
				$add( 'marka', $q['brand'] );
			}
			if ( '' !== $q['model'] ) {
				$add( 'modell', $q['model'] );
			}
			foreach ( $q['blocked'] as $block ) {
				$add( 'tilt[]', '' !== $block[1] ? $block[0] . '|' . $block[1] : $block[0] );
			}
			$ranges = array(
				'ccm_min' => 'cc_min',
				'ccm_max' => 'cc_max',
				'le_min'  => 'hp_min',
				'le_max'  => 'hp_max',
				'ev_min'  => 'year_min',
				'ev_max'  => 'year_max',
				'km_min'  => 'km_min',
				'km_max'  => 'km_max',
			);
			foreach ( $ranges as $key => $field ) {
				if ( $q[ $field ] > 0 ) {
					$add( $key, $q[ $field ] );
				}
			}
			if ( '' !== $q['fuel'] ) {
				$add( 'uzem', $q['fuel'] );
			}
			if ( '' !== $q['gear'] ) {
				$add( 'valto', $q['gear'] );
			}
			if ( '' !== $q['mot']['mode'] ) {
				$add( 'muszaki', $q['mot']['mode'] );
				if ( $q['mot']['min'] > 0 ) {
					$add( 'mot_min', $q['mot']['min'] );
				}
				if ( $q['mot']['max'] > 0 ) {
					$add( 'mot_max', $q['mot']['max'] );
				}
				if ( $q['mot']['expired'] ) {
					$add( 'mot_lejart', '1' );
				}
			}
			if ( $q['conditions'] ) {
				$add( 'allapot', implode( ',', $q['conditions'] ) );
			}
			if ( $q['prices'] ) {
				$add( 'ar', implode( ',', $q['prices'] ) );
			}
		}

		if ( KMAE_Browse::SORT_DEFAULT !== $q['sort'] ) {
			$add( 'rendez', $q['sort'] );
			if ( 'tavolsag' === $q['sort'] ) {
				$add( 'lat', sprintf( '%.2F', $q['lat'] ) );
				$add( 'lon', sprintf( '%.2F', $q['lon'] ) );
				if ( $q['km'] > 0 ) {
					$add( 'km', $q['km'] );
				}
			}
		}
		if ( $q['page'] > 1 ) {
			$add( 'page', $q['page'] );
		}

		return implode( '&', $pairs );
	}

	/**
	 * A személyre szabás alapja: a régi kód a KMAE_Query::get_listings() adagján (csak egy megye /
	 * kivitel szűréssel, dátum szerint) szúrja be az átlépetteket, és csak UTÁNA szűr és rendez.
	 * Ennek a kanonikus feed-query-je; a kliens ennek az `ids`-éből építi a beszúrást (BROWSE-V2.md).
	 *
	 * @param array $params Nyers query paraméterek.
	 * @return string
	 */
	public static function base_query( array $params ) {
		$q = self::parse_query( $params );
		if ( $q['all'] ) {
			return self::canonical_query( array( 'osszes' => '1' ) );
		}
		return self::canonical_query(
			array(
				'megye'   => 1 === count( $q['counties'] ) ? $q['counties'][0] : KMAE_Counties::ORSZAG,
				'kivitel' => $q['bodies_all'] ? 'mind' : implode( ',', $q['bodies'] ),
				'mod'     => 'mind',
			)
		);
	}

	/* ------------------------------------------------------------------
	   Műszaki: hátralévő hónapok → dátumhatárok (tiszta)
	   ------------------------------------------------------------------ */

	/**
	 * = KMAE_Browse::mot_months_left(), a „ma” paraméterként.
	 *
	 * @param string $date  Y-m-d.
	 * @param string $today Y-m-d.
	 * @return int|null
	 */
	public static function months_left( $date, $today ) {
		$date = trim( (string) $date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}
		try {
			$until = new DateTimeImmutable( $date );
			$now   = new DateTimeImmutable( $today );
		} catch ( Exception $e ) {
			return null;
		}
		$diff   = $now->diff( $until );
		$months = ( $diff->y * 12 ) + $diff->m;
		return $diff->invert ? -$months : $months;
	}

	/**
	 * A legkorábbi dátum, amelyre months_left() >= $k (a függvény a dátumban nem csökkenő,
	 * ezt a teszt napra pontosan ellenőrzi). Bináris keresés napokon.
	 *
	 * @param int    $k     Hónap (0 = még nem lejárt).
	 * @param string $today Y-m-d.
	 * @return string Y-m-d
	 */
	public static function mot_first_date( $k, $today ) {
		$k = (int) $k;
		if ( $k > self::MOT_MONTHS_CAP ) {
			return '9999-12-31';
		}
		$base = new DateTimeImmutable( $today );
		$at   = static function ( $days ) use ( $base ) {
			return $base->modify( ( $days < 0 ? '-' : '+' ) . abs( $days ) . ' days' )->format( 'Y-m-d' );
		};
		$lo = -70;                  /* itt months_left < 0 <= k */
		$hi = ( max( 0, $k ) + 2 ) * 31; /* itt months_left >= k */
		while ( $hi - $lo > 1 ) {
			$mid = (int) floor( ( $lo + $hi ) / 2 );
			if ( self::months_left( $at( $mid ), $today ) >= $k ) {
				$hi = $mid;
			} else {
				$lo = $mid;
			}
		}
		return $at( $hi );
	}

	/* ------------------------------------------------------------------
	   SQL (tiszta függvény: normalizált query + tábla + környezet → SQL + paraméterek)
	   ------------------------------------------------------------------ */

	/**
	 * @param array  $q     parse_query() eredménye.
	 * @param string $table Index tábla neve.
	 * @param string $mode  'page' (kártyák, LIMIT/OFFSET) | 'ids' (teljes sorrend, csak ID) | 'count'.
	 * @param array  $ctx   {today: Y-m-d, single_category: bool}.
	 * @return array{0:string,1:array} wpdb::prepare-hez (%s / %d / %f).
	 */
	public static function build_sql( array $q, $table, $mode, array $ctx ) {
		$where = array( 'is_active = 1' );
		$args  = array();

		if ( ! empty( $ctx['single_category'] ) ) {
			$where[] = 'in_category = 1';
		}

		if ( ! $q['all'] ) {
			/* get_listings: egy megyénél mindig; filter_by_wizard: módnál bármennyinél. */
			if ( $q['counties'] && ( $q['wizard'] || 1 === count( $q['counties'] ) ) ) {
				$where[] = 'county IN (' . self::placeholders( count( $q['counties'] ), '%s' ) . ')';
				$args    = array_merge( $args, $q['counties'] );
			}
			if ( $q['bodies'] ) {
				$where[] = 'body IN (' . self::placeholders( count( $q['bodies'] ), '%s' ) . ')';
				$args    = array_merge( $args, $q['bodies'] );
			}
		}

		if ( $q['wizard'] ) {
			if ( KMAE_Vehicle::BROWSE_AJANLAT === $q['mod'] ) {
				$where[] = 'price_mode = %s';
				$args[]  = KMAE_Seller::PRICE_OPEN;
			} elseif ( KMAE_Vehicle::BROWSE_KP === $q['mod'] ) {
				$where[] = 'price_mode <> %s AND has_cash = 1 AND vehicle_value > 0 AND vehicle_value >= %d AND vehicle_value <= %d';
				array_push( $args, KMAE_Seller::PRICE_OPEN, (int) $q['kp_min'], (int) $q['kp_max'] );
			}

			foreach ( $q['blocked'] as $block ) {
				if ( '' === $block[1] ) {
					$where[] = 'NOT (brand_key = %s)';
					$args[]  = $block[0];
				} else {
					$where[] = 'NOT (brand_key = %s AND model_key = %s)';
					array_push( $args, $block[0], $block[1] );
				}
			}
			if ( '' !== $q['brand'] ) {
				$where[] = 'brand_key = %s';
				$args[]  = $q['brand'];
			}
			if ( '' !== $q['model'] ) {
				$where[] = 'model_key = %s';
				$args[]  = $q['model'];
			}

			$ranges = array(
				'engine_cc'  => array( 'cc_min', 'cc_max' ),
				'power_hp'   => array( 'hp_min', 'hp_max' ),
				'model_year' => array( 'year_min', 'year_max' ),
				'mileage'    => array( 'km_min', 'km_max' ),
			);
			foreach ( $ranges as $column => $keys ) {
				if ( (int) $q[ $keys[0] ] > 0 ) {
					$where[] = $column . ' >= %d';
					$args[]  = (int) $q[ $keys[0] ];
				}
				if ( (int) $q[ $keys[1] ] > 0 ) {
					$where[] = $column . ' <= %d';
					$args[]  = (int) $q[ $keys[1] ];
				}
			}

			if ( '' !== $q['fuel'] ) {
				$where[] = 'fuel = %s';
				$args[]  = $q['fuel'];
			}
			if ( '' !== $q['gear'] ) {
				$where[] = 'gear = %s';
				$args[]  = $q['gear'];
			}
			if ( $q['conditions'] ) {
				$where[] = 'cond IN (' . self::placeholders( count( $q['conditions'] ), '%s' ) . ')';
				$args    = array_merge( $args, $q['conditions'] );
			}
			if ( $q['prices'] ) {
				$where[] = 'price_mode IN (' . self::placeholders( count( $q['prices'] ), '%s' ) . ')';
				$args    = array_merge( $args, $q['prices'] );
			}

			if ( '' !== $q['mot']['mode'] ) {
				$today = (string) $ctx['today'];
				$valid = 'mot_date IS NOT NULL AND mot_date >= %s';
				$vargs = array( self::mot_first_date( 0, $today ) );
				if ( $q['mot']['min'] > 0 ) {
					$valid  .= ' AND mot_date >= %s';
					$vargs[] = self::mot_first_date( $q['mot']['min'], $today );
				}
				if ( $q['mot']['max'] > 0 ) {
					$valid  .= ' AND mot_date < %s';
					$vargs[] = self::mot_first_date( $q['mot']['max'] + 1, $today );
				}
				if ( $q['mot']['expired'] ) {
					$where[] = '((' . $valid . ') OR mot_date IS NULL OR mot_date < %s)';
					$vargs[] = self::mot_first_date( 0, $today );
				} else {
					$where[] = '(' . $valid . ')';
				}
				$args = array_merge( $args, $vargs );
			}
		}

		$where_sql = implode( ' AND ', $where );
		$tiebreak  = 'post_date DESC, listing_id DESC';

		if ( 'tavolsag' === $q['sort'] ) {
			/* = KMAE_Geo::distance_km (haversine), a PHP műveleti sorrendjével. */
			$hav   = 'POW(SIN(RADIANS(lat - %f) / 2), 2) + COS(RADIANS(%f)) * COS(RADIANS(lat)) * POW(SIN(RADIANS(lon - %f) / 2), 2)';
			$dist  = '6371.0 * 2 * ATAN2(SQRT(' . $hav . '), SQRT(1 - (' . $hav . ')))';
			$dargs = array( $q['lat'], $q['lat'], $q['lon'], $q['lat'], $q['lat'], $q['lon'] );
			$cols  = 'page' === $mode ? 'listing_id, is_reserved, card, post_date' : 'listing_id, post_date';
			$inner = 'SELECT ' . $cols . ', ' . $dist . ' AS dist FROM ' . $table . ' WHERE ' . $where_sql;
			$args  = array_merge( $dargs, $args );
			$outer = '';
			if ( $q['km'] > 0 ) {
				$outer  = ' WHERE d.dist <= %d';
				$args[] = (int) $q['km'];
			}
			$order = ' ORDER BY (d.dist IS NULL), d.dist ASC, d.' . str_replace( ', ', ', d.', $tiebreak );
			if ( 'count' === $mode ) {
				return array( 'SELECT COUNT(*) FROM (' . $inner . ') AS d' . $outer, $args );
			}
			if ( 'ids' === $mode ) {
				$args[] = self::IDS_MAX;
				return array( 'SELECT d.listing_id, d.dist AS sv FROM (' . $inner . ') AS d' . $outer . $order . ' LIMIT %d', $args );
			}
			array_push( $args, self::PER_PAGE, ( (int) $q['page'] - 1 ) * self::PER_PAGE );
			return array( 'SELECT d.listing_id, d.is_reserved, d.card, d.dist FROM (' . $inner . ') AS d' . $outer . $order . ' LIMIT %d OFFSET %d', $args );
		}

		$sort_sql = array(
			'legujabb'     => 'post_date_gmt DESC, ',
			'ar_novekvo'   => '(price_amount IS NULL), price_amount ASC, ',
			'ar_csokkeno'  => '(price_amount IS NULL), price_amount DESC, ',
			'evjarat'      => '(model_year = 0), model_year DESC, ',
			'km'           => '(mileage <= 0), mileage ASC, ',
			'teljesitmeny' => '(power_kw = 0), power_kw DESC, ',
			'nev'          => '(sort_name IS NULL), sort_name ASC, ',
		);
		$order = ' ORDER BY ' . ( $sort_sql[ $q['sort'] ] ?? '' ) . $tiebreak;
		/* A rendezési érték (null = „ismeretlen”, a végére): az ids módban ebből lesznek a `ranks`. */
		$sort_value = array(
			'legujabb'     => 'post_date_gmt',
			'ar_novekvo'   => 'price_amount',
			'ar_csokkeno'  => 'price_amount',
			'evjarat'      => 'CASE WHEN model_year = 0 THEN NULL ELSE model_year END',
			'km'           => 'CASE WHEN mileage <= 0 THEN NULL ELSE mileage END',
			'teljesitmeny' => 'CASE WHEN power_kw = 0 THEN NULL ELSE power_kw END',
			'nev'          => 'sort_name',
		);

		if ( 'count' === $mode ) {
			return array( 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where_sql, $args );
		}
		if ( 'ids' === $mode ) {
			$args[] = self::IDS_MAX;
			$sv = isset( $sort_value[ $q['sort'] ] ) ? ', ' . $sort_value[ $q['sort'] ] . ' AS sv' : '';
			return array( 'SELECT listing_id' . $sv . ' FROM ' . $table . ' WHERE ' . $where_sql . $order . ' LIMIT %d', $args );
		}
		array_push( $args, self::PER_PAGE, ( (int) $q['page'] - 1 ) * self::PER_PAGE );
		return array( 'SELECT listing_id, is_reserved, card FROM ' . $table . ' WHERE ' . $where_sql . $order . ' LIMIT %d OFFSET %d', $args );
	}

	/**
	 * @param int    $count Darab.
	 * @param string $token %s / %d.
	 */
	private static function placeholders( $count, $token ) {
		return implode( ', ', array_fill( 0, max( 1, (int) $count ), $token ) );
	}

	/** @return array{today:string,single_category:bool} */
	public static function context() {
		return array(
			'today'           => ( new DateTimeImmutable( 'today' ) )->format( 'Y-m-d' ),
			'single_category' => KMAE_Launch::is_single_category(),
		);
	}

	/* ------------------------------------------------------------------
	   REST
	   ------------------------------------------------------------------ */

	public static function register_routes() {
		register_rest_route(
			self::REST_NS,
			'/feed',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_feed' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NS,
			'/me/browse',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_me' ),
				'permission_callback' => static function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * A feed: csak a query és az index tartalma számít, a látogató nem.
	 *
	 * @param WP_REST_Request $request Kérés.
	 * @return WP_REST_Response
	 */
	public static function rest_feed( $request ) {
		/* Mindenki ugyanazt kapja: semmi ne függjön a belépett felhasználótól. */
		wp_set_current_user( 0 );

		$response = new WP_REST_Response( self::build( (array) $request->get_query_params() ), 200 );
		$response->header( 'Cache-Control', self::CACHE_CONTROL );
		return $response;
	}

	/**
	 * A feed válasza (user-független: csak a query és az index tartalma számít).
	 * A REST és a v2 kártyás oldal első, beágyazott oldala is ezt használja.
	 *
	 * @param array $params Nyers, slash nélküli query paraméterek.
	 * @return array<string,mixed>
	 */
	public static function build( array $params ) {
		global $wpdb;

		$q    = self::parse_query( $params );
		$data = array(
			'v'        => 1,
			'query'    => self::canonical_query( $params ),
			'base'     => self::base_query( $params ),
			'complete' => $q['complete'],
			'sort'     => $q['sort'],
			'page'     => $q['page'],
			'per_page' => self::PER_PAGE,
			'total'    => 0,
			'pages'    => 0,
			'items'    => array(),
		);

		if ( $q['complete'] && KMAE_Browse_Index::ready() ) {
			$table = KMAE_Browse_Index::table();
			$ctx   = self::context();

			if ( 1 === $q['page'] ) {
				list( $sql, $args ) = self::build_sql( $q, $table, 'ids', $ctx );
				$id_rows            = (array) $wpdb->get_results( self::prepare( $sql, $args ), ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
				$ids                = array_map( 'intval', array_column( $id_rows, 0 ) );
				$data['ids']        = $ids;
				if ( KMAE_Browse::SORT_DEFAULT !== $q['sort'] ) {
					$data['ranks'] = self::ranks( array_column( $id_rows, 1 ) );
				}
				$data['total']      = count( $ids );
				if ( count( $ids ) >= self::IDS_MAX ) {
					$data['ids_truncated'] = true;
					$data['total']         = self::count( $q, $table, $ctx );
				}
			} else {
				$data['total'] = self::count( $q, $table, $ctx );
			}

			list( $sql, $args ) = self::build_sql( $q, $table, 'page', $ctx );
			$rows               = (array) $wpdb->get_results( self::prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			foreach ( $rows as $row ) {
				$card            = json_decode( (string) $row->card, true );
				$data['items'][] = array(
					'id'          => (int) $row->listing_id,
					'reserved'    => (bool) (int) $row->is_reserved,
					'distance_km' => isset( $row->dist ) ? round( (float) $row->dist, 2 ) : null,
					'card'        => is_array( $card ) ? $card : null,
				);
			}
		}
		$data['pages'] = (int) ceil( $data['total'] / self::PER_PAGE );

		return $data;
	}

	/**
	 * A v2 kártyás oldal beágyazott adata: az első feed-oldal (ids + ranks), belépve a /me/browse
	 * tartalma és a `base` feed ids-e (a kliens ebből szab személyre, extra kérés nélkül), valamint
	 * az első megjelenő kártya (a szerver ezt rendereli kész HTML-ként).
	 *
	 * @param array $params  Az oldal nyers query paraméterei (wp_unslash( $_GET )).
	 * @param int   $user_id Belépett user (0 = vendég).
	 * @return array{feed:array,pages:array,me:?array,base_ids:?array,order:int[],first:?array}
	 */
	public static function client_state( array $params, $user_id ) {
		unset( $params['page'] );
		$feed  = self::build( $params );
		$ids   = isset( $feed['ids'] ) ? $feed['ids'] : array();
		$state = array(
			'feed'     => $feed,
			'pages'    => array(),
			'me'       => null,
			'base_ids' => null,
			'order'    => $ids,
			'first'    => null,
		);

		if ( $user_id && $feed['complete'] ) {
			$base_ids = $ids;
			if ( $feed['base'] !== $feed['query'] ) {
				parse_str( $feed['base'], $base_params );
				$base     = self::build( (array) $base_params );
				$base_ids = isset( $base['ids'] ) ? $base['ids'] : array();
			}
			$state['me']       = self::me_payload( $user_id );
			$state['base_ids'] = $base_ids;
			$state['order']    = self::personalize( $ids, isset( $feed['ranks'] ) ? $feed['ranks'] : null, $base_ids, $state['me'] );
		}

		if ( $state['order'] ) {
			$first = $state['order'][0];
			$page  = (int) floor( (int) array_search( $first, $ids, true ) / self::PER_PAGE ) + 1;
			$items = $feed['items'];
			if ( 1 !== $page ) {
				$more                     = self::build( $params + array( 'page' => $page ) );
				$items                    = $more['items'];
				$state['pages'][ $page ] = $items;
			}
			foreach ( $items as $item ) {
				if ( $item['id'] === $first && is_array( $item['card'] ) ) {
					$state['first'] = $item;
					break;
				}
			}
		}
		return $state;
	}

	/**
	 * Kártyás nézet az indexből? (nem lista nézet, járműves indulás, v2 kapcsoló, kész index).
	 * Ha igen, a shortcode nem kéri le a régi adagot (24 / 500 / 1000), a sorrend a client_state()-ből jön.
	 */
	public static function use_card_index() {
		return ! KMAE_Browse::is_list_view() && KMAE_Vehicle::is_vehicle_launch() && self::is_enabled() && KMAE_Browse_Index::ready();
	}

	/**
	 * Lista nézet az indexből? (belépett user listanézete, járműves indulás, v2 kapcsoló, kész index).
	 * Ha igen, a shortcode nem kéri le a 1000 hirdetést, a lista a list_order() + list_rows() adatából épül.
	 */
	public static function use_list_index() {
		return KMAE_Browse::is_list_view() && KMAE_Vehicle::is_vehicle_launch() && self::is_enabled() && KMAE_Browse_Index::ready();
	}

	/**
	 * A lista nézet teljes, személyre szabott hirdetés-sorrendje (csak ID-k): a régi
	 * LIST_SCAN-es lekérdezés + filter_by_wizard + apply_sort eredménye, SQL-ben (BROWSE-V2.md,
	 * „Személyes adat a kliensen”). Hiányos varázslónál üres.
	 *
	 * @param array $params  Az oldal nyers query paraméterei (wp_unslash( $_GET )).
	 * @param int   $user_id Belépett user.
	 * @return int[]
	 */
	public static function list_order( array $params, $user_id ) {
		global $wpdb;

		unset( $params['page'] );
		$q = self::parse_query( $params );
		if ( ! $q['complete'] || ! KMAE_Browse_Index::ready() ) {
			return array();
		}

		$table              = KMAE_Browse_Index::table();
		$ctx                = self::context();
		list( $sql, $args ) = self::build_sql( $q, $table, 'ids', $ctx );
		$id_rows            = (array) $wpdb->get_results( self::prepare( $sql, $args ), ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$ids                = array_map( 'intval', array_column( $id_rows, 0 ) );
		$ranks              = KMAE_Browse::SORT_DEFAULT !== $q['sort'] ? self::ranks( array_column( $id_rows, 1 ) ) : null;
		if ( ! $user_id ) {
			return $ids;
		}

		$base_ids = $ids;
		$base     = self::base_query( $params );
		if ( $base !== self::canonical_query( $params ) ) {
			parse_str( $base, $base_params );
			list( $sql, $args ) = self::build_sql( self::parse_query( (array) $base_params ), $table, 'ids', $ctx );
			$base_rows          = (array) $wpdb->get_results( self::prepare( $sql, $args ), ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			$base_ids           = array_map( 'intval', array_column( $base_rows, 0 ) );
		}
		return self::personalize( $ids, $ranks, $base_ids, self::me_payload( $user_id ) );
	}

	/**
	 * A lista egy oldalának sorai az indexből (egy lekérdezés): kártya-JSON, Foglalt jelző, koordináta.
	 * A lista sorrendjét a hívó tartja; az időközben inaktívvá vált (üres kártyás) sorok kimaradnak.
	 *
	 * @param int[] $ids Hirdetés ID-k.
	 * @return array<int,array{card:array,reserved:bool,lat:?float,lon:?float}> ID szerint.
	 */
	public static function list_rows( array $ids ) {
		global $wpdb;

		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		if ( ! $ids || ! KMAE_Browse_Index::ready() ) {
			return array();
		}
		$sql  = 'SELECT listing_id, is_reserved, card, lat, lon FROM ' . KMAE_Browse_Index::table() . ' WHERE listing_id IN (' . self::placeholders( count( $ids ), '%d' ) . ')';
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$out  = array();
		foreach ( $rows as $row ) {
			$card = json_decode( (string) $row->card, true );
			if ( ! is_array( $card ) || empty( $card['id'] ) ) {
				continue;
			}
			$out[ (int) $row->listing_id ] = array(
				'card'     => $card,
				'reserved' => (bool) (int) $row->is_reserved,
				'lat'      => null === $row->lat ? null : (float) $row->lat,
				'lon'      => null === $row->lon ? null : (float) $row->lon,
			);
		}
		return $out;
	}

	/**
	 * A lista oldal hirdetéseinek előnézeti képe (a régi lista `src`-je: KMAE_Query::get_listing_thumbnail_url).
	 * A meta- és a csatolmány-gyorsítótárat előre tölti, így az oldal 50 sora nem 50+ külön lekérdezés.
	 *
	 * @param int[]  $ids  Hirdetés ID-k (az oldal sorai).
	 * @param string $size Képméret.
	 * @return array<int,string> ID => URL ('' ha nincs kép).
	 */
	public static function list_thumbs( array $ids, $size = 'medium_large' ) {
		$ids = array_map( 'intval', $ids );
		if ( $ids ) {
			update_meta_cache( 'post', $ids );
			$attachments = array();
			foreach ( $ids as $id ) {
				$thumb_id = (int) get_post_thumbnail_id( $id );
				if ( $thumb_id ) {
					$attachments[] = $thumb_id;
				}
			}
			if ( $attachments ) {
				_prime_post_caches( $attachments, false, true );
			}
		}
		$out = array();
		foreach ( $ids as $id ) {
			$out[ $id ] = (string) KMAE_Query::get_listing_thumbnail_url( $id, $size );
		}
		return $out;
	}

	/**
	 * A BROWSE-V2.md „Személyes adat a kliensen” szabálya (= swipe-v2.js personalize(),
	 * = tests/test-browse-feed-sqlite.py client_personalize()).
	 *
	 * @param int[]      $s_ids   A kért feed ids-e.
	 * @param int[]|null $s_ranks A kért feed ranks-e (alap-rendezésnél null).
	 * @param int[]      $d_ids   A `base` feed ids-e.
	 * @param array      $me      me_payload().
	 * @return int[]
	 */
	public static function personalize( array $s_ids, $s_ranks, array $d_ids, array $me ) {
		$excluded = array_flip( array_merge( (array) $me['own'], (array) $me['contacted'] ) );
		$passed   = array_flip( (array) $me['passed'] );
		$fresh    = array();
		$passed_d = array();
		foreach ( $d_ids as $id ) {
			if ( isset( $excluded[ $id ] ) ) {
				continue;
			}
			if ( isset( $passed[ $id ] ) ) {
				$passed_d[] = $id;
			} else {
				$fresh[] = $id;
			}
		}
		if ( ! $fresh ) {
			$merged = $passed_d;
		} elseif ( ! $passed_d ) {
			$merged = $fresh;
		} else {
			$merged   = array();
			$k        = 0;
			$interval = max( 1, (int) $me['passed_interval'] );
			foreach ( $fresh as $i => $id ) {
				$merged[] = $id;
				if ( 0 === ( $i + 1 ) % $interval ) {
					$merged[] = $passed_d[ $k % count( $passed_d ) ];
					++$k;
				}
			}
		}
		$in_feed = array_flip( $s_ids );
		$kept    = array_values(
			array_filter(
				$merged,
				static function ( $id ) use ( $in_feed ) {
					return isset( $in_feed[ $id ] );
				}
			)
		);
		if ( null === $s_ranks ) {
			return $kept;
		}
		$rank  = array_combine( $s_ids, $s_ranks );
		$order = array_keys( $kept );
		usort(
			$order,
			static function ( $a, $b ) use ( $kept, $rank ) {
				return ( $rank[ $kept[ $a ] ] <=> $rank[ $kept[ $b ] ] ) ?: $a <=> $b;
			}
		);
		return array_map(
			static function ( $i ) use ( $kept ) {
				return $kept[ $i ];
			},
			$order
		);
	}

	/**
	 * Egyenlő rendezési érték = azonos rang (a sorba rendezett értékeken). A régi apply_sort az
	 * egyenlő értékűeket a személyre szabott sorrend szerint hagyja: a kliens ehhez kell (BROWSE-V2.md).
	 *
	 * @param array $values A rendezési értékek az ids sorrendjében (null = ismeretlen).
	 * @return int[]
	 */
	public static function ranks( array $values ) {
		$ranks = array();
		$rank  = -1;
		$prev  = null;
		foreach ( array_values( $values ) as $i => $value ) {
			$same = 0 < $i && ( null === $value ? null === $prev : ( null !== $prev && (string) $value === (string) $prev ) );
			if ( ! $same ) {
				++$rank;
			}
			$ranks[] = $rank;
			$prev    = $value;
		}
		return $ranks;
	}

	/**
	 * @param array  $q     Query.
	 * @param string $table Tábla.
	 * @param array  $ctx   Környezet.
	 */
	private static function count( array $q, $table, array $ctx ) {
		global $wpdb;
		list( $sql, $args ) = self::build_sql( $q, $table, 'count', $ctx );
		return (int) $wpdb->get_var( self::prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * wpdb::prepare, de paraméter nélkül (szűrő nélküli COUNT) a nyers SQL-t adja.
	 *
	 * @param string $sql  SQL.
	 * @param array  $args Paraméterek.
	 */
	private static function prepare( $sql, array $args ) {
		global $wpdb;
		return $args ? $wpdb->prepare( $sql, $args ) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Személyes kiegészítés a feedhez (a régi KMAE_Query::get_listings + personalize_listings bemenetei).
	 *
	 * @param WP_REST_Request $request Kérés.
	 * @return WP_REST_Response
	 */
	public static function rest_me( $request ) {
		unset( $request );

		$response = new WP_REST_Response( self::me_payload( get_current_user_id() ), 200 );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0' );
		return $response;
	}

	/**
	 * A /me/browse tartalma (a v2 kártyás oldal belépve beágyazza, így nem kell külön kérés).
	 *
	 * @param int $user_id User.
	 * @return array<string,mixed>
	 */
	public static function me_payload( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		$own     = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status NOT IN ('trash','auto-draft')", 'kmae_listing', $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return array(
			'v'               => 1,
			'favorites'       => array_map( 'intval', (array) KMAE_Browse::get_favorite_listing_ids( $user_id ) ),
			'passed'          => array_map( 'intval', (array) KMAE_Browse::get_passed_listing_ids( $user_id ) ),
			'contacted'       => array_map( 'intval', (array) KMAE_Browse::get_contacted_listing_ids( $user_id ) ),
			'own'             => array_map( 'intval', (array) $own ),
			'passed_interval' => KMAE_Browse::PASSED_INTERVAL,
			'view'            => 'lista' === (string) get_user_meta( $user_id, KMAE_Browse::VIEW_META, true ) ? 'lista' : 'kartya',
		);
	}

	/**
	 * A WP REST belépett usernek no-cache fejlécet küld (a dispatch ELŐTT dönt) — a feednél ki.
	 *
	 * @param bool $send Küldje-e.
	 */
	public static function filter_nocache_headers( $send ) {
		return self::is_feed_route() ? false : $send;
	}

	/**
	 * Kiszolgálás előtt: a feed válaszából minden user-függő fejléc ki (Set-Cookie,
	 * a nonce-os kérésre a WP által frissített X-WP-Nonce, Expires/Pragma), a Cache-Control marad.
	 *
	 * @param bool             $served  Már kiszolgálva.
	 * @param WP_HTTP_Response $result  Válasz.
	 * @param WP_REST_Request  $request Kérés.
	 * @return bool
	 */
	public static function strip_private_headers( $served, $result, $request ) {
		unset( $result );
		if ( $request instanceof WP_REST_Request && self::is_feed_route( $request->get_route() ) && ! headers_sent() ) {
			header_remove( 'Set-Cookie' );
			header_remove( 'X-WP-Nonce' );
			header_remove( 'Expires' );
			header_remove( 'Pragma' );
			header( 'Cache-Control: ' . self::CACHE_CONTROL );
		}
		return $served;
	}

	/**
	 * @param string|null $route REST route (null: a jelenlegi kérésé).
	 */
	public static function is_feed_route( $route = null ) {
		if ( null === $route ) {
			$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
		}
		return '/' . self::REST_NS . '/feed' === untrailingslashit( '/' . ltrim( (string) $route, '/' ) );
	}
}
