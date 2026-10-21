<?php
/**
 * Hirdetések koordinátái a „Távolság szerint” rendezéshez.
 *
 * - A település → koordináta egyszer kerül lekérésre (OpenStreetMap Nominatim,
 *   strukturált keresés), és örökre gyorsítótárba megy (`kmae_geo_cities`).
 * - Mentéskor egyszeri háttérfeladat geokódolja a hirdetést; a régi hirdetéseket
 *   óránkénti háttérfeladat pótolja (Nominatim: legfeljebb 1 kérés / mp).
 * - Amíg nincs pontos adat, a megyeszékhely koordinátája számít.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Geo {

	const META         = '_kmae_geo';
	const CITY_OPTION  = 'kmae_geo_cities';
	const CRON_SINGLE  = 'kmae_geocode_listing';
	const CRON_BACKFILL = 'kmae_geo_backfill';
	const BACKFILL_BATCH = 20;

	/* Megyeszékhelyek (Pestnél a megye közepe) — tartalék, amíg nincs pontos adat. */
	const COUNTY_SEATS = array(
		'bacs-kiskun'            => array( 46.9062, 19.6913 ),
		'baranya'                => array( 46.0727, 18.2323 ),
		'bekes'                  => array( 46.6736, 21.0877 ),
		'borsod-abauj-zemplen'   => array( 48.1035, 20.7784 ),
		'budapest'               => array( 47.4979, 19.0402 ),
		'csongrad-csanad'        => array( 46.2530, 20.1414 ),
		'fejer'                  => array( 47.1860, 18.4221 ),
		'gyor-moson-sopron'      => array( 47.6875, 17.6504 ),
		'hajdu-bihar'            => array( 47.5316, 21.6273 ),
		'heves'                  => array( 47.9025, 20.3772 ),
		'jasz-nagykun-szolnok'   => array( 47.1621, 20.1825 ),
		'komarom-esztergom'      => array( 47.5692, 18.4048 ),
		'nograd'                 => array( 48.0935, 19.7999 ),
		'pest'                   => array( 47.4000, 19.2500 ),
		'somogy'                 => array( 46.3594, 17.7968 ),
		'szabolcs-szatmar-bereg' => array( 47.9554, 21.7167 ),
		'tolna'                  => array( 46.3474, 18.7062 ),
		'vas'                    => array( 47.2307, 16.6218 ),
		'veszprem'               => array( 47.0930, 17.9093 ),
		'zala'                   => array( 46.8417, 16.8416 ),
	);

	public static function init() {
		add_action( self::CRON_SINGLE, array( __CLASS__, 'geocode_listing' ) );
		add_action( self::CRON_BACKFILL, array( __CLASS__, 'backfill' ) );
		add_action(
			'init',
			static function () {
				if ( ! wp_next_scheduled( self::CRON_BACKFILL ) ) {
					wp_schedule_event( time() + 300, 'hourly', self::CRON_BACKFILL );
				}
			}
		);
	}

	/**
	 * Helyszín mentése után: a régi koordináta törlődik, a pontosat a háttérben kérjük le.
	 *
	 * @param int $post_id Listing.
	 */
	public static function location_changed( $post_id ) {
		delete_post_meta( $post_id, self::META );
		if ( ! wp_next_scheduled( self::CRON_SINGLE, array( (int) $post_id ) ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_SINGLE, array( (int) $post_id ) );
		}
	}

	/**
	 * A hirdetés koordinátája: pontos (meta / település-gyorsítótár), különben a megyeszékhely.
	 *
	 * @param int $post_id Listing.
	 * @return array{0:float,1:float}|null
	 */
	public static function listing_coords( $post_id ) {
		$saved = get_post_meta( $post_id, self::META, true );
		if ( is_array( $saved ) && isset( $saved[0], $saved[1] ) ) {
			return array( (float) $saved[0], (float) $saved[1] );
		}
		$county = sanitize_key( (string) get_post_meta( $post_id, '_kmae_county', true ) );
		$city   = (string) get_post_meta( $post_id, '_kmae_city', true );
		$cached = self::cached_city( $county, $city );
		if ( $cached ) {
			return $cached;
		}
		return isset( self::COUNTY_SEATS[ $county ] ) ? self::COUNTY_SEATS[ $county ] : null;
	}

	/**
	 * Légvonalbeli távolság km-ben (haversine).
	 */
	public static function distance_km( $lat1, $lon1, $lat2, $lon2 ) {
		$r    = 6371.0;
		$dlat = deg2rad( $lat2 - $lat1 );
		$dlon = deg2rad( $lon2 - $lon1 );
		$a    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlon / 2 ) ** 2;
		return $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	}

	/* ------------------------------------------------------------------
	   Háttérfeladatok
	   ------------------------------------------------------------------ */

	public static function geocode_listing( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || 'kmae_listing' !== get_post_type( $post_id ) ) {
			return;
		}
		$county = sanitize_key( (string) get_post_meta( $post_id, '_kmae_county', true ) );
		$city   = (string) get_post_meta( $post_id, '_kmae_city', true );
		$coords = self::resolve_city( $county, $city );
		if ( $coords ) {
			update_post_meta( $post_id, self::META, $coords );
		} elseif ( isset( self::COUNTY_SEATS[ $county ] ) ) {
			/* Ismeretlen település: a megyeszékhely marad, így nem kérdezzük újra. */
			update_post_meta( $post_id, self::META, self::COUNTY_SEATS[ $county ] );
		}
	}

	/**
	 * Régi hirdetések pótlása, kis adagokban.
	 */
	public static function backfill() {
		$ids = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => self::BACKFILL_BATCH,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::META,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_kmae_city',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		foreach ( $ids as $id ) {
			self::geocode_listing( $id );
		}
	}

	/* ------------------------------------------------------------------
	   Település → koordináta
	   ------------------------------------------------------------------ */

	private static function city_key( $county, $city ) {
		$city = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( (string) $city ), 'UTF-8' ) : strtolower( trim( (string) $city ) );
		return md5( $county . '|' . $city );
	}

	private static function cached_city( $county, $city ) {
		if ( '' === trim( (string) $city ) ) {
			return null;
		}
		$all = get_option( self::CITY_OPTION, array() );
		$hit = is_array( $all ) ? ( $all[ self::city_key( $county, $city ) ] ?? null ) : null;
		return is_array( $hit ) ? array( (float) $hit[0], (float) $hit[1] ) : null;
	}

	/**
	 * Gyorsítótárból, vagy Nominatim strukturált kereséssel (csak háttérfeladatból hívjuk).
	 *
	 * @return array{0:float,1:float}|null
	 */
	private static function resolve_city( $county, $city ) {
		$city = trim( (string) $city );
		if ( '' === $city ) {
			return null;
		}
		$all = get_option( self::CITY_OPTION, array() );
		$all = is_array( $all ) ? $all : array();
		$key = self::city_key( $county, $city );
		if ( array_key_exists( $key, $all ) ) {
			return is_array( $all[ $key ] ) ? array( (float) $all[ $key ][0], (float) $all[ $key ][1] ) : null;
		}

		/* Nominatim: legfeljebb 1 kérés másodpercenként. */
		$last = (float) get_transient( 'kmae_geo_last_call' );
		$wait = 1.1 - ( microtime( true ) - $last );
		if ( $wait > 0 ) {
			usleep( (int) ( $wait * 1000000 ) );
		}
		set_transient( 'kmae_geo_last_call', microtime( true ), 60 );

		$args = array(
			'format'          => 'jsonv2',
			'countrycodes'    => 'hu',
			'limit'           => 1,
			'accept-language' => 'hu',
			'city'            => $city,
		);
		if ( 'budapest' !== $county && class_exists( 'KMAE_Counties' ) && KMAE_Counties::is_listing_county( $county ) ) {
			$args['county'] = KMAE_Counties::get_label( $county );
		}
		$response = wp_remote_get(
			add_query_arg( $args, 'https://nominatim.openstreetmap.org/search' ),
			array(
				'timeout' => 8,
				'headers' => array(
					'User-Agent' => 'Albira/1.0 (' . home_url( '/' ) . ')',
					'Referer'    => home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null; /* hálózati hiba: nem gyorsítótárazzuk, később újra próbáljuk */
		}
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$coords = null;
		if ( is_array( $data ) && ! empty( $data[0]['lat'] ) && ! empty( $data[0]['lon'] ) ) {
			$lat = (float) $data[0]['lat'];
			$lon = (float) $data[0]['lon'];
			if ( $lat >= 45.6 && $lat <= 48.7 && $lon >= 16.0 && $lon <= 23.0 ) {
				$coords = array( round( $lat, 5 ), round( $lon, 5 ) );
			}
		}
		$all[ $key ] = $coords ? $coords : 0;
		update_option( self::CITY_OPTION, $all, false );
		return $coords;
	}
}
