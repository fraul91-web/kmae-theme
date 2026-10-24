<?php
/**
 * Hungarian counties (megyék) for location and map browse.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Counties {

	const ORSZAG = 'orszag';

	const LOCATE_NONCE = 'kmae_locate';

	public static function init() {
		add_action( 'wp_ajax_kmae_locate', array( __CLASS__, 'ajax_locate' ) );
	}

	/**
	 * „Jelenlegi helyzetem” — böngészős koordinátából megye + település.
	 *
	 * A koordinátát a saját szerverünk küldi tovább az OpenStreetMap
	 * Nominatim szolgáltatásának (a böngésző nem hívja közvetlenül). Az
	 * eredményt kerekített koordinátánként gyorsítótárazzuk, és felhasználónként
	 * korlátozzuk a kérések sűrűségét (a Nominatim használati feltételei miatt).
	 */
	public static function ajax_locate() {
		check_ajax_referer( self::LOCATE_NONCE, 'nonce' );

		$lat = isset( $_POST['lat'] ) ? (float) wp_unslash( $_POST['lat'] ) : 0.0;
		$lon = isset( $_POST['lon'] ) ? (float) wp_unslash( $_POST['lon'] ) : 0.0;

		/* Magyarország befoglaló téglalapja, kis ráhagyással. */
		if ( $lat < 45.6 || $lat > 48.7 || $lon < 16.0 || $lon > 23.0 ) {
			wp_send_json_error( array( 'message' => __( 'A helyzeted alapján nem Magyarországon vagy — add meg kézzel a megyét és a várost.', 'ki-mit-adna-erte' ) ) );
		}

		$cache_key = 'kmae_locate_' . md5( round( $lat, 3 ) . ',' . round( $lon, 3 ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			/* Régebbi (lista előtti) gyorsítótár-bejegyzés is kanonikus alakban menjen ki. */
			$official = self::locate_official( (string) ( $cached['county'] ?? '' ), array( (string) ( $cached['city'] ?? '' ) ) );
			if ( $official ) {
				$cached['county'] = $official['county'];
				$cached['city']   = $official['name'];
			}
			wp_send_json_success( $cached );
		}

		$throttle_key = 'kmae_locate_u' . get_current_user_id();
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Várj egy pillanatot, és próbáld újra.', 'ki-mit-adna-erte' ) ) );
		}
		set_transient( $throttle_key, 1, 3 );

		$url      = add_query_arg(
			array(
				'format'          => 'jsonv2',
				'lat'             => round( $lat, 6 ),
				'lon'             => round( $lon, 6 ),
				'zoom'            => 14,
				'addressdetails'  => 1,
				'accept-language' => 'hu',
			),
			'https://nominatim.openstreetmap.org/reverse'
		);
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'User-Agent' => 'Albira/1.0 (' . home_url( '/' ) . ')',
					'Referer'    => home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Most nem sikerült meghatározni a helyet. Add meg kézzel, vagy próbáld újra később.', 'ki-mit-adna-erte' ) ) );
		}

		$data    = json_decode( wp_remote_retrieve_body( $response ), true );
		$address = ( is_array( $data ) && isset( $data['address'] ) && is_array( $data['address'] ) ) ? $data['address'] : array();

		$city       = '';
		$candidates = array();
		foreach ( array( 'city', 'town', 'village', 'municipality', 'hamlet' ) as $key ) {
			if ( ! empty( $address[ $key ] ) ) {
				$candidates[] = sanitize_text_field( $address[ $key ] );
			}
		}
		if ( $candidates ) {
			$city = $candidates[0];
		}

		$county = 'Budapest' === $city ? 'budapest' : self::match_county( $address['county'] ?? '' );
		if ( '' === $county ) {
			$county = self::match_county( $address['state'] ?? '' );
		}

		/* A hivatalos településlista dönt: kanonikus név, és a megye is onnan jön,
		   ha a Nominatim mást mond (a település neve országosan egyedi). */
		$official = self::locate_official( $county, $candidates );
		if ( $official ) {
			$county = $official['county'];
			$city   = $official['name'];
		}

		if ( '' === $county && '' === $city ) {
			wp_send_json_error( array( 'message' => __( 'Nem találtunk települést ezen a helyen. Add meg kézzel.', 'ki-mit-adna-erte' ) ) );
		}

		$result = array(
			'county' => $county,
			'city'   => $city,
		);
		set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );
		wp_send_json_success( $result );
	}

	/**
	 * Helymeghatározás eredménye → hivatalos település (előbb a megadott megyében,
	 * aztán országosan). A jelöltek sorrendje: city, town, village, municipality, hamlet.
	 *
	 * @param string   $county     Nominatim alapján egyeztetett megye slug (lehet üres).
	 * @param string[] $candidates Településnév-jelöltek.
	 * @return array|null KMAE_Settlements sor.
	 */
	private static function locate_official( $county, $candidates ) {
		if ( ! class_exists( 'KMAE_Settlements' ) ) {
			return null;
		}
		foreach ( $candidates as $name ) {
			$row = '' !== $county ? KMAE_Settlements::find( $county, $name ) : null;
			if ( $row ) {
				return $row;
			}
		}
		foreach ( $candidates as $name ) {
			$row = KMAE_Settlements::find_anywhere( $name );
			if ( $row ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Nominatim megyenév („Csongrád-Csanád vármegye”) → saját slug.
	 *
	 * @param string $name County name from the geocoder.
	 * @return string Slug or empty string.
	 */
	public static function match_county( $name ) {
		$norm = static function ( $text ) {
			$text = strtolower( remove_accents( (string) $text ) );
			$text = preg_replace( '/\b(varmegye|megye)\b/', '', $text );
			return trim( preg_replace( '/[^a-z]+/', '-', $text ), '-' );
		};

		$needle = $norm( $name );
		if ( '' === $needle ) {
			return '';
		}
		foreach ( self::get_all() as $slug => $label ) {
			if ( $norm( $label ) === $needle ) {
				return $slug;
			}
		}
		return '';
	}

	/**
	 * @return array<string, string> slug => label
	 */
	public static function get_all() {
		return array(
			'bacs-kiskun'           => 'Bács-Kiskun',
			'baranya'               => 'Baranya',
			'bekes'                 => 'Békés',
			'borsod-abauj-zemplen'  => 'Borsod-Abaúj-Zemplén',
			'budapest'              => 'Budapest',
			'csongrad-csanad'       => 'Csongrád-Csanád',
			'fejer'                 => 'Fejér',
			'gyor-moson-sopron'     => 'Győr-Moson-Sopron',
			'hajdu-bihar'           => 'Hajdú-Bihar',
			'heves'                 => 'Heves',
			'jasz-nagykun-szolnok'  => 'Jász-Nagykun-Szolnok',
			'komarom-esztergom'     => 'Komárom-Esztergom',
			'nograd'                => 'Nógrád',
			'pest'                  => 'Pest',
			'somogy'                => 'Somogy',
			'szabolcs-szatmar-bereg' => 'Szabolcs-Szatmár-Bereg',
			'tolna'                 => 'Tolna',
			'vas'                   => 'Vas',
			'veszprem'              => 'Veszprém',
			'zala'                  => 'Zala',
		);
	}

	public static function is_valid( $slug ) {
		return self::ORSZAG === $slug || isset( self::get_all()[ $slug ] );
	}

	public static function is_listing_county( $slug ) {
		return isset( self::get_all()[ $slug ] );
	}

	public static function get_label( $slug ) {
		if ( self::ORSZAG === $slug ) {
			return __( 'Egész ország', 'ki-mit-adna-erte' );
		}
		return self::get_all()[ $slug ] ?? $slug;
	}

	/**
	 * Real county paths from Wikipedia HU_counties_blank.svg (public domain).
	 *
	 * @return array{viewBox: string, counties: array<string, array{d: string, cx: float, cy: float}>}
	 */
	public static function get_map_data() {
		static $data = null;
		if ( null !== $data ) {
			return $data;
		}

		$file = KMAE_PLUGIN_DIR . 'assets/data/counties-paths.json';
		if ( ! is_readable( $file ) ) {
			return array(
				'viewBox'  => '0 0 841.89 595.28',
				'counties' => array(),
			);
		}

		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			$data = array(
				'viewBox'  => '0 0 841.89 595.28',
				'counties' => array(),
			);
		}

		return $data;
	}

	/**
	 * @return array<string, string> slug => SVG path d
	 */
	public static function get_map_paths() {
		$data   = self::get_map_data();
		$paths  = array();
		foreach ( $data['counties'] ?? array() as $slug => $info ) {
			$paths[ $slug ] = $info['d'] ?? '';
		}
		return $paths;
	}

	/** @deprecated Use get_map_data() centroids */
	public static function get_map_label_xy( $slug ) {
		$data = self::get_map_data();
		$info = $data['counties'][ $slug ] ?? null;
		if ( $info ) {
			return $info['cx'] . ',' . $info['cy'];
		}
		return '0,0';
	}

	public static function format_listing_location( $post_id ) {
		$county = get_post_meta( $post_id, '_kmae_county', true );
		$city   = get_post_meta( $post_id, '_kmae_city', true );

		if ( $county && $city ) {
			return self::get_label( $county ) . ', ' . $city;
		}

		return (string) get_post_meta( $post_id, '_kmae_location', true );
	}

	/**
	 * Megye + település: a település a hivatalos listában van, és ebben a megyében.
	 */
	public static function validate_listing_location( $county, $city ) {
		return '' === self::location_error( $county, $city );
	}

	/**
	 * Miért nem jó a helyszín?
	 *
	 * @return string '' = jó; 'required' = hiányzik a megye vagy a település;
	 *                'mismatch' = a település nem ennek a megyének a települése
	 *                (vagy nincs ilyen település).
	 */
	public static function location_error( $county, $city ) {
		if ( ! self::is_listing_county( $county ) || '' === trim( (string) $city ) ) {
			return 'required';
		}
		if ( class_exists( 'KMAE_Settlements' ) && null === KMAE_Settlements::find( $county, $city ) ) {
			return 'mismatch';
		}
		return '';
	}

	/**
	 * A hivatalos (KSH) településnév, ha a megyében megvan; különben a beírt szöveg.
	 */
	public static function canonical_city( $county, $city ) {
		$canonical = class_exists( 'KMAE_Settlements' ) ? KMAE_Settlements::canonical_name( $county, $city ) : null;
		return null !== $canonical ? $canonical : (string) $city;
	}

	public static function save_listing_location( $post_id, $county, $city ) {
		$county = sanitize_text_field( $county );
		$city   = sanitize_text_field( self::canonical_city( $county, $city ) );

		$old_county = (string) get_post_meta( $post_id, '_kmae_county', true );
		$old_city   = (string) get_post_meta( $post_id, '_kmae_city', true );

		update_post_meta( $post_id, '_kmae_county', $county );
		update_post_meta( $post_id, '_kmae_city', $city );
		update_post_meta( $post_id, '_kmae_location', self::get_label( $county ) . ', ' . $city );

		/* Új helyszín → a „Távolság szerint” rendezés koordinátája a háttérben frissül. */
		if ( class_exists( 'KMAE_Geo' ) && ( $old_county !== $county || $old_city !== $city || ! get_post_meta( $post_id, KMAE_Geo::META, true ) ) ) {
			KMAE_Geo::location_changed( $post_id );
		}
	}

	public static function get_map_label_short( $slug ) {
		$short = array(
			'budapest'               => 'Bp.',
			'bacs-kiskun'            => 'B-K',
			'borsod-abauj-zemplen'   => 'BAZ',
			'csongrad-csanad'        => 'Cs.',
			'gyor-moson-sopron'      => 'Gy-M-S',
			'hajdu-bihar'            => 'HB',
			'jasz-nagykun-szolnok'   => 'J-N-Sz',
			'komarom-esztergom'      => 'K-E',
			'szabolcs-szatmar-bereg' => 'Sz-Sz-B',
		);
		if ( isset( $short[ $slug ] ) ) {
			return $short[ $slug ];
		}
		$label = self::get_all()[ $slug ] ?? '';
		return mb_strlen( $label ) > 10 ? mb_substr( $label, 0, 8 ) . '.' : $label;
	}

}
