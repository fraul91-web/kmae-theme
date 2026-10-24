<?php
/**
 * Magyar települések hivatalos listája (megye, név, koordináta, irányítószám).
 *
 * Forrás: assets/data/settlements.json (tools/build-settlements.py: KSH
 * Helységnévtár + Wikidata koordináták + Magyar Posta irányítószámok).
 * A fájl kérésenként egyszer töltődik be (statikus gyorsítótár), a
 * keresőindex megyénként, csak ha kell.
 *
 * Egyezés: kis-/nagybetű és szóköz nem számít; az ékezet csak akkor
 * hagyható el, ha így is egyértelmű (pl. „Komoro” Szabolcsban lehet Komoró
 * vagy Kömörő → nincs találat).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Settlements {

	const FILE         = 'assets/data/settlements.json';
	const BUDAPEST     = 'budapest';

	/** @var array|null Nyers adat (counties, zip, bp_prefix). */
	private static $data = null;

	/** @var array<string,array> Megyénkénti keresőindex. */
	private static $index = array();

	/** @var array|null Országos index (find_anywhere). */
	private static $national = null;

	/* ------------------------------------------------------------------
	   Nyilvános lekérdezések
	   ------------------------------------------------------------------ */

	/**
	 * A megye adott települése (a megyének is egyeznie kell).
	 *
	 * @param string $county Megye slug.
	 * @param string $city   Beírt településnév.
	 * @return array{county:string,name:string,lat:float,lon:float,ksh:string,zips:string[]}|null
	 */
	public static function find( $county, $city ) {
		$county = strtolower( trim( (string) $county ) );
		$idx    = '' !== $county ? self::index( $county ) : null;
		if ( ! $idx ) {
			return null;
		}
		$name = self::resolve_name( (string) $city, $idx['lower'], $idx['ascii'], self::BUDAPEST === $county );
		return null === $name ? null : self::row( $county, $name );
	}

	/**
	 * @return string|null A hivatalos (KSH) név, vagy null, ha nem a megye települése.
	 */
	public static function canonical_name( $county, $city ) {
		$row = self::find( $county, $city );
		return $row ? $row['name'] : null;
	}

	/**
	 * @return array{0:float,1:float}|null
	 */
	public static function coords( $county, $city ) {
		$row = self::find( $county, $city );
		return $row ? array( $row['lat'], $row['lon'] ) : null;
	}

	/**
	 * Település a megyétől függetlenül (a név országosan egyedi).
	 *
	 * @param string $city Beírt településnév.
	 * @return array{county:string,name:string,lat:float,lon:float,ksh:string,zips:string[]}|null
	 */
	public static function find_anywhere( $city ) {
		$all  = self::national_index();
		$name = self::resolve_name( (string) $city, $all['lower'], $all['ascii'], true );
		if ( null === $name ) {
			return null;
		}
		return self::row( $all['where'][ $name ], $name );
	}

	/**
	 * Irányítószám → települések (közös irányítószámnál több).
	 *
	 * @param string|int $zip Irányítószám.
	 * @return array<int, array{0:string,1:string}> [megye slug, név] párok.
	 */
	public static function by_zip( $zip ) {
		$zip = preg_replace( '/\D+/', '', (string) $zip );
		if ( 4 !== strlen( $zip ) ) {
			return array();
		}
		$data = self::data();
		if ( '' !== $data['bp_prefix'] && 0 === strpos( $zip, $data['bp_prefix'] ) ) {
			return array( array( self::BUDAPEST, 'Budapest' ) );
		}
		$out = array();
		foreach ( $data['zip'][ $zip ] ?? array() as $pair ) {
			if ( is_array( $pair ) && isset( $pair[0], $pair[1] ) ) {
				$out[] = array( (string) $pair[0], (string) $pair[1] );
			}
		}
		return $out;
	}

	/**
	 * @param string $slug Megye slug.
	 * @return string[] A megye településnevei (a fájl sorrendjében, magyar ábécé szerint).
	 */
	public static function for_county( $slug ) {
		$slug = strtolower( trim( (string) $slug ) );
		$idx  = '' !== $slug ? self::index( $slug ) : null;
		return $idx ? array_map( 'strval', array_keys( $idx['rows'] ) ) : array();
	}

	/* ------------------------------------------------------------------
	   Normalizálás
	   ------------------------------------------------------------------ */

	/**
	 * Kisbetűs, ékezetes alak (mbstring nélkül is), egységes szóköz/kötőjel.
	 */
	public static function lower_key( $text ) {
		$text = self::fold_case( trim( (string) $text ) );
		$text = preg_replace( '/\s*-\s*/u', '-', $text );
		$text = preg_replace( '/\s+/u', ' ', (string) $text );
		return trim( (string) $text, " \t\n\r\0\x0B.,;" );
	}

	/**
	 * Ékezet nélküli kulcs (a lower_key-ből).
	 */
	public static function ascii_key( $text ) {
		return self::strip_accents( self::lower_key( $text ) );
	}

	/**
	 * Kisbetűsítés mbstring nélkül. Régi kódolási hibák: õ/ô → ő, û → ű.
	 */
	private static function fold_case( $text ) {
		static $map = array(
			'Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ö' => 'ö', 'Ő' => 'ő', 'Ú' => 'ú', 'Ü' => 'ü', 'Ű' => 'ű',
			'Õ' => 'ő', 'Ô' => 'ő', 'õ' => 'ő', 'ô' => 'ő', 'Û' => 'ű', 'û' => 'ű',
		);
		return strtolower( strtr( $text, $map ) ); /* PHP 8: a strtolower csak ASCII-t alakít, az UTF-8 bájtokat nem rontja. */
	}

	private static function strip_accents( $text ) {
		static $map = array(
			'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
		);
		$text = strtr( $text, $map );
		if ( function_exists( 'remove_accents' ) && preg_match( '/[\x80-\xff]/', $text ) ) {
			$text = strtolower( remove_accents( $text ) );
		}
		return $text;
	}

	/* ------------------------------------------------------------------
	   Belső
	   ------------------------------------------------------------------ */

	/**
	 * @param string $city        Beírt név.
	 * @param array  $lower_index lower_key => név.
	 * @param array  $ascii_index ascii_key => név[].
	 * @param bool   $budapest    Budapesti alakok („Budapest XI. ker.”, „Bp.”) elfogadása.
	 * @return string|null
	 */
	private static function resolve_name( $city, $lower_index, $ascii_index, $budapest ) {
		/* „6720 Szeged” / „Szeged 6720” → az irányítószám nem része a névnek. */
		$city  = preg_replace( '/^\s*\d{4}\s+|\s+\d{4}\s*$/', '', $city );
		$lower = self::lower_key( $city );
		if ( '' === $lower ) {
			return null;
		}
		if ( isset( $lower_index[ $lower ] ) ) {
			return $lower_index[ $lower ];
		}
		$ascii = self::ascii_key( $city );
		if ( isset( $ascii_index[ $ascii ] ) && 1 === count( $ascii_index[ $ascii ] ) ) {
			return $ascii_index[ $ascii ][0];
		}
		if ( $budapest && preg_match( '/^(budapest|bp)\b/', $ascii ) && isset( $lower_index['budapest'] ) ) {
			return $lower_index['budapest'];
		}
		return null;
	}

	private static function row( $county, $name ) {
		$r = self::index( $county )['rows'][ $name ]; /* nyers sor: [név, lat, lon, ksh, [zip…]] */
		return array(
			'county' => $county,
			'name'   => $name,
			'lat'    => (float) $r[1],
			'lon'    => (float) $r[2],
			'ksh'    => (string) ( $r[3] ?? '' ),
			'zips'   => array_map( 'strval', (array) ( $r[4] ?? array() ) ),
		);
	}

	/**
	 * A JSON betöltése, kérésenként egyszer (~7 ms). A keresőindex megyénként,
	 * csak szükség esetén épül (index()), így egy hirdetés mentése nem építi fel
	 * mind a 20 megyét.
	 */
	private static function data() {
		if ( null !== self::$data ) {
			return self::$data;
		}
		$file = ( defined( 'KMAE_PLUGIN_DIR' ) ? KMAE_PLUGIN_DIR : dirname( __DIR__ ) . '/' ) . self::FILE;
		$raw  = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		self::$data = array(
			'counties'  => ( is_array( $raw ) && isset( $raw['counties'] ) && is_array( $raw['counties'] ) ) ? $raw['counties'] : array(),
			/* A json_decode a számszerű kulcsokat int-té alakítja; a PHP a „6720” string
			   kulcsot is int-ként keresi, így a by_zip() string kulccsal is talál. */
			'zip'       => ( is_array( $raw ) && isset( $raw['zip'] ) && is_array( $raw['zip'] ) ) ? $raw['zip'] : array(),
			'bp_prefix' => ( is_array( $raw ) && isset( $raw['budapestZipPrefix'] ) ) ? (string) $raw['budapestZipPrefix'] : '1',
		);
		return self::$data;
	}

	/**
	 * Egy megye keresőindexe.
	 *
	 * @param string $slug Megye slug.
	 * @return array{rows:array,lower:array,ascii:array}|null
	 */
	private static function index( $slug ) {
		if ( isset( self::$index[ $slug ] ) ) {
			return self::$index[ $slug ];
		}
		$data = self::data();
		if ( ! isset( $data['counties'][ $slug ] ) || ! is_array( $data['counties'][ $slug ] ) ) {
			return null;
		}
		$idx = array(
			'rows'  => array(),
			'lower' => array(),
			'ascii' => array(),
		);
		foreach ( $data['counties'][ $slug ] as $r ) {
			if ( ! is_array( $r ) || ! isset( $r[0], $r[1], $r[2] ) ) {
				continue;
			}
			$name                  = (string) $r[0];
			$idx['rows'][ $name ]  = $r;
			/* A hivatalos nevekben nincs fölös szóköz → a gyors út elég (nincs preg). */
			$lower                 = self::fold_case( $name );
			$idx['lower'][ $lower ] = $name;
			$idx['ascii'][ self::strip_accents( $lower ) ][] = $name;
		}
		self::$index[ $slug ] = $idx;
		return $idx;
	}

	/**
	 * Országos index (find_anywhere): a megyei indexek összefésülése.
	 *
	 * @return array{lower:array,ascii:array,where:array}
	 */
	private static function national_index() {
		if ( null !== self::$national ) {
			return self::$national;
		}
		$all = array(
			'lower' => array(),
			'ascii' => array(),
			'where' => array(),
		);
		foreach ( array_keys( self::data()['counties'] ) as $slug ) {
			$slug = (string) $slug;
			$idx  = self::index( $slug );
			if ( ! $idx ) {
				continue;
			}
			$all['lower'] += $idx['lower'];
			foreach ( $idx['ascii'] as $key => $names ) {
				$all['ascii'][ $key ] = isset( $all['ascii'][ $key ] ) ? array_merge( $all['ascii'][ $key ], $names ) : $names;
			}
			foreach ( $idx['rows'] as $name => $unused ) {
				$all['where'][ $name ] = $slug;
			}
		}
		self::$national = $all;
		return $all;
	}

	/**
	 * A település-választó (irányítószám + város-lista) JS-e és CSS-e.
	 * Az űrlapot kirajzoló oldalakon hívandó (hirdetés, regisztráció, profil, admin
	 * hirdetés-szerkesztő); kérésenként egyszer enqueue-ol. Az adatfájl a böngészőben
	 * lustán töltődik, a `ver` a fájl módosítási ideje (cache-bust).
	 */
	public static function enqueue_picker() {
		static $done = false;
		if ( $done || ! function_exists( 'wp_enqueue_script' ) ) {
			return;
		}
		$done = true;

		$data_path = KMAE_PLUGIN_DIR . self::FILE;
		$data_url  = KMAE_PLUGIN_URL . self::FILE;
		if ( is_file( $data_path ) ) {
			$data_url = add_query_arg( 'ver', (string) filemtime( $data_path ), $data_url );
		}

		wp_enqueue_style( 'kmae-settlement-picker', KMAE_PLUGIN_URL . 'assets/css/settlement-picker.css', array(), KMAE_VERSION );
		wp_enqueue_script( 'kmae-settlement-picker', KMAE_PLUGIN_URL . 'assets/js/settlement-picker.js', array(), KMAE_VERSION, true );
		wp_localize_script(
			'kmae-settlement-picker',
			'kmaeSettlementPicker',
			array(
				'dataUrl' => $data_url,
				'i18n'    => array(
					'mismatch'       => __( 'Ez a település nem ebben a megyében van — válassz a listából.', 'ki-mit-adna-erte' ),
					'zipUnknown'     => __( 'Ilyen irányítószámot nem találunk — válaszd ki a megyét és a települést.', 'ki-mit-adna-erte' ),
					'zipChoose'      => __( 'Ez az irányítószám több településhez tartozik — válaszd ki a tiédet:', 'ki-mit-adna-erte' ),
					'zipUnavailable' => __( 'Az irányítószám-keresés most nem érhető el — válaszd ki a megyét és a települést.', 'ki-mit-adna-erte' ),
				),
			)
		);
	}

	/**
	 * Csak tesztekhez: a betöltött adat ürítése.
	 */
	public static function reset() {
		self::$data     = null;
		self::$index    = array();
		self::$national = null;
	}
}
