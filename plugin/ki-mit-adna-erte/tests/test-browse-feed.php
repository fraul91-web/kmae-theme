<?php
/**
 * Böngészés v2 feed: a régi PHP-s szűrés/rendezés (KMAE_Query::get_listings +
 * KMAE_Browse::filter_by_wizard + apply_sort) és az új SQL (KMAE_Browse_Feed::build_sql
 * a KMAE_Browse_Index::columns() sorain) ugyanazt a sorrendet adja-e.
 *
 * WordPress és MySQL nélkül fut: a WP-függvények itt tömb-alapú stubok, az SQL-t a
 * tests/test-browse-feed-sqlite.py futtatja Python sqlite3-mal (ezt a script hívja meg).
 *
 * Run: php tests/test-browse-feed.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
define( 'KMAE_LAUNCH_MODE', 'single' );
define( 'KMAE_LAUNCH_CATEGORY', 'jarmu' );
define( 'KMAE_OFFER_TYPE_CASH', 'cash' );

$root = dirname( __DIR__ );

/* ------------------------------------------------------------------
   WP stubok
   ------------------------------------------------------------------ */

class WP_Post {
	public $ID;
	public $post_author;
	public $post_status;
	public $post_date;
	public $post_date_gmt;
	public $post_title;
	public $post_type = 'kmae_listing';
	public function __construct( array $a ) {
		foreach ( $a as $k => $v ) {
			$this->$k = $v;
		}
	}
}

$POSTS = array();
$META  = array();
$TERMS = array();
$OPTS  = array();
$RESERVED = array();

function __( $s ) {
	return $s;
}
function absint( $v ) {
	return abs( (int) $v );
}
function sanitize_key( $key ) {
	return is_scalar( $key ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ) : '';
}
function sanitize_text_field( $s ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) );
}
function wp_unslash( $v ) {
	return is_array( $v ) ? array_map( 'wp_unslash', $v ) : ( is_string( $v ) ? stripslashes( $v ) : $v );
}
function wp_slash( $v ) {
	return is_array( $v ) ? array_map( 'wp_slash', $v ) : ( is_string( $v ) ? addslashes( $v ) : $v );
}
function remove_accents( $s ) {
	return strtr( $s, array( 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u', 'š' => 's', 'Š' => 'S', 'ë' => 'e' ) );
}
/* A helyi PHP-ban nincs mbstring: a teszt ékezeteihez elég polyfill (régi és új kód ugyanezt kapja). */
if ( ! function_exists( 'mb_strtolower' ) ) {
	function mb_strtolower( $s ) {
		return strtr( strtolower( $s ), array( 'Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ö' => 'ö', 'Ő' => 'ő', 'Ú' => 'ú', 'Ü' => 'ü', 'Ű' => 'ű', 'Š' => 'š' ) );
	}
}
function wp_parse_args( $a, $d ) {
	return array_merge( $d, (array) $a );
}
function untrailingslashit( $s ) {
	return rtrim( $s, '/\\' );
}
$CURRENT_USER = 0;
function is_user_logged_in() {
	return $GLOBALS['CURRENT_USER'] > 0;
}
function get_current_user_id() {
	return $GLOBALS['CURRENT_USER'];
}
$USER_META = array();
function get_user_meta( $uid, $key, $single = false ) {
	return $GLOBALS['USER_META'][ $uid ][ $key ] ?? '';
}
function update_user_meta( $uid, $key, $value ) {
	$GLOBALS['USER_META'][ $uid ][ $key ] = $value;
}
function wp_set_current_user( $id ) {
	$GLOBALS['CURRENT_USER'] = (int) $id;
}
function current_user_can() {
	return false;
}
function get_option( $key, $default = false ) {
	global $OPTS;
	return array_key_exists( $key, $OPTS ) ? $OPTS[ $key ] : $default;
}
function get_post_meta( $id, $key, $single = false ) {
	global $META;
	return $META[ $id ][ $key ] ?? '';
}
function has_term( $slug, $tax, $id ) {
	global $TERMS;
	return in_array( $slug, $TERMS[ $id ] ?? array(), true );
}
function get_post_time( $format, $gmt, $post ) {
	$d = $gmt ? $post->post_date_gmt : $post->post_date;
	if ( 0 === strpos( $d, '0000' ) ) {
		return false;
	}
	return ( new DateTimeImmutable( $d, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
}

/* get_posts: a KMAE_Query::get_listings által használt részhalmaz, MySQL-szerű (ci) összevetéssel. */
function get_posts( $args ) {
	global $POSTS, $META, $TERMS;
	$out = array();
	foreach ( $POSTS as $p ) {
		if ( 'publish' !== $p->post_status
			|| in_array( (int) $p->post_author, $args['author__not_in'] ?? array(), true )
			|| in_array( (int) $p->ID, array_map( 'intval', $args['post__not_in'] ?? array() ), true ) ) {
			continue;
		}
		$ok = true;
		foreach ( $args['meta_query'] ?? array() as $c ) {
			if ( ! isset( $META[ $p->ID ][ $c['key'] ] ) ) {
				$ok = false;
				break;
			}
			$have    = (string) $META[ $p->ID ][ $c['key'] ];
			$compare = $c['compare'] ?? '=';
			if ( '=' === $compare && 0 !== strcasecmp( $have, (string) $c['value'] ) ) {
				$ok = false;
			} elseif ( 'IN' === $compare ) {
				$hit = false;
				foreach ( (array) $c['value'] as $v ) {
					$hit = $hit || 0 === strcasecmp( $have, (string) $v );
				}
				$ok = $hit;
			} elseif ( 'LIKE' === $compare && false === stripos( $have, (string) $c['value'] ) ) {
				$ok = false;
			}
			if ( ! $ok ) {
				break;
			}
		}
		foreach ( $args['tax_query'] ?? array() as $t ) {
			if ( ! in_array( $t['terms'], $TERMS[ $p->ID ] ?? array(), true ) ) {
				$ok = false;
			}
		}
		if ( $ok ) {
			$out[] = $p;
		}
	}
	/* ORDER BY post_date DESC (+ determinisztikus ID DESC, mint az új SQL tiebreakje). */
	usort(
		$out,
		static function ( $a, $b ) {
			return strcmp( $b->post_date, $a->post_date ) ?: $b->ID <=> $a->ID;
		}
	);
	return array_slice( $out, 0, (int) $args['posts_per_page'] );
}

class KMAE_Chat_Offers {
	public static function get_accepted_for_listing( $id ) {
		global $RESERVED;
		return isset( $RESERVED[ $id ] ) ? (object) array( 'id' => 1 ) : null;
	}
}

require $root . '/includes/class-kmae-launch.php';
require $root . '/includes/class-kmae-taxonomies.php';
require $root . '/includes/class-kmae-meta.php';
require $root . '/includes/class-kmae-counties.php';
require $root . '/includes/class-kmae-vehicle.php';
require $root . '/includes/class-kmae-seller.php';
require $root . '/includes/class-kmae-geo.php';
require $root . '/includes/class-kmae-browse.php';
require $root . '/includes/class-kmae-query.php';
require $root . '/includes/class-kmae-browse-index.php';
require $root . '/includes/class-kmae-browse-feed.php';

$passed = 0;
$failed = array();
function check( $name, $cond, $detail = '' ) {
	global $passed, $failed;
	if ( $cond ) {
		++$passed;
		return;
	}
	$failed[] = $name;
	echo 'FAIL  ' . $name . ( '' !== $detail ? '  ' . $detail : '' ) . "\n";
}

/* ------------------------------------------------------------------
   50 kitalált hirdetés (determinisztikus)
   ------------------------------------------------------------------ */

mt_srand( 20261017 );
$pick  = static function ( array $a ) {
	return $a[ mt_rand( 0, count( $a ) - 1 ) ];
};
$today = new DateTimeImmutable( 'today' );

$brands = array(
	array( 'Opel', 'Astra' ),
	array( 'OPEL', 'Corsa' ),
	array( 'opel', 'astra' ),
	array( 'Škoda', 'Octavia' ),
	array( 'Volkswagen', 'Golf' ),
	array( 'Volkswagen', 'Passat' ),
	array( 'BMW', '320d' ),
	array( 'Audi', 'A4' ),
	array( 'Toyota', 'Corolla' ),
	array( 'Egyéb', '' ),
	array( '', '' ),
);
$bodies    = array( 'sedan', 'estate', 'hatchback', 'suv', 'crossover', 'Crossover', 'other', '', 'van', 'limousine', 'nincs-ilyen' );
$counties  = array( 'pest', 'budapest', 'fejer', 'bacs-kiskun', 'zala', 'Pest', '' );
$cities    = array( 'Budaörs', 'Érd', 'Székesfehérvár', 'Kecskemét', '' );
$fuels     = array( 'benzin', 'diesel', 'hybrid', 'electric', '', 'Benzin' );
$gears     = array( 'manual', 'automatic', '' );
$conds     = array( 'excellent', 'good', 'fair', 'project', '' );
$modes     = array( 'fixed', 'guide', 'open', '', 'offers', 'fixed', 'guide' );
$mot_specs = array( '', 'nem-datum', '2026-02-30', '-40d', '-10d', '+0d', '+1m', '+1m-1d', '+3m', '+6m', '+6m+1d', '+12m', '+12m-1d', '+24m', '+30m' );

/* Település-koordináta gyorsítótár (KMAE_Geo::listing_coords tartalék-lánca). */
$OPTS['kmae_geo_cities'] = array(
	md5( 'pest|érd' ) => array( 47.3919, 18.9045 ),
	md5( 'fejer|székesfehérvár' ) => array( 47.1860, 18.4221 ),
);

$base_ts = ( new DateTimeImmutable( '2026-03-01 08:00:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
for ( $i = 1; $i <= 50; $i++ ) {
	$id   = 100 + $i;
	$ts   = $base_ts + mt_rand( 0, 200 ) * 86400 + mt_rand( 0, 86399 );
	if ( 0 === $i % 9 ) {
		$ts = $base_ts + 50 * 86400; /* azonos időpontok: a tiebreak próbája */
	}
	$gmt  = gmdate( 'Y-m-d H:i:s', $ts );
	$off  = ( (int) gmdate( 'n', $ts ) >= 4 && (int) gmdate( 'n', $ts ) <= 10 ) ? 7200 : 3600;
	$local = gmdate( 'Y-m-d H:i:s', $ts + $off );
	$bm   = $pick( $brands );

	$POSTS[ $id ] = new WP_Post(
		array(
			'ID'            => $id,
			'post_author'   => mt_rand( 2, 9 ),
			'post_status'   => 0 === $i % 17 ? 'draft' : 'publish',
			'post_date'     => $local,
			'post_date_gmt' => $gmt,
			'post_title'    => 'Eladó ' . $bm[0] . ' autó – jó állapot',
		)
	);
	$TERMS[ $id ] = 0 === $i % 23 ? array( 'egyeb-kategoria' ) : array( 'jarmu' );

	$mode  = $pick( $modes );
	$price = mt_rand( 0, 9 ) ? mt_rand( 5, 90 ) * 100000 : 0;
	$mot   = $pick( $mot_specs );
	if ( preg_match( '/^([+-]\d+)([dm])([+-]\d+d)?$/', $mot, $m ) ) {
		$d = $today->modify( $m[1] . ( 'd' === $m[2] ? ' days' : ' months' ) );
		if ( ! empty( $m[3] ) ) {
			$d = $d->modify( substr( $m[3], 0, -1 ) . ' days' );
		}
		$mot = $d->format( 'Y-m-d' );
	}

	$META[ $id ] = array(
		'_kmae_status'               => 0 === $i % 11 ? 'expired' : ( 0 === $i % 19 ? '' : 'active' ),
		'_kmae_vehicle_brand'        => $bm[0],
		'_kmae_vehicle_model'        => $bm[1],
		'_kmae_vehicle_year'         => mt_rand( 0, 6 ) ? (string) mt_rand( 2003, 2024 ) : '',
		'_kmae_vehicle_mileage'      => mt_rand( 0, 6 ) ? (string) ( mt_rand( 1, 40 ) * 10000 ) : '0',
		'_kmae_vehicle_power_kw'     => mt_rand( 0, 6 ) ? (string) mt_rand( 40, 220 ) : '',
		'_kmae_vehicle_engine_cc'    => mt_rand( 0, 6 ) ? (string) mt_rand( 900, 3200 ) : '',
		'_kmae_vehicle_fuel'         => $pick( $fuels ),
		'_kmae_vehicle_transmission' => $pick( $gears ),
		'_kmae_vehicle_body'         => $pick( $bodies ),
		'_kmae_vehicle_mot'          => $mot,
		'_kmae_vehicle_value'        => mt_rand( 0, 3 ) ? '' : (string) ( mt_rand( 5, 90 ) * 100000 ),
		'_kmae_condition'            => $pick( $conds ),
		'_kmae_county'               => $pick( $counties ),
		'_kmae_city'                 => $pick( $cities ),
		'_kmae_price_mode'           => $mode,
		'_kmae_fixed_price'          => 'fixed' === $mode ? (string) $price : '',
		'_kmae_guide_price'          => 'guide' === $mode ? (string) $price : '',
	);
	if ( 0 === $i % 19 ) {
		unset( $META[ $id ]['_kmae_status'] ); /* hiányzó státusz-meta: a get_listings nem hozza */
	}
	if ( 0 === $i % 4 ) {
		$META[ $id ]['_kmae_geo'] = array( 46.0 + mt_rand( 0, 2500 ) / 1000, 16.5 + mt_rand( 0, 6000 ) / 1000 );
	}
	if ( 0 === $i % 7 ) {
		$RESERVED[ $id ] = true;
	}
}
/* Két egyforma koordinátájú, egyforma dátumú hirdetés: távolságnál is a tiebreak dönt. */
$META[110]['_kmae_geo'] = array( 47.1, 19.2 );
$META[120]['_kmae_geo'] = array( 47.1, 19.2 );

/* ------------------------------------------------------------------
   Régi út (pontosan a KMAE_Shortcodes::browse_vehicle_wizard adat-útja, teljes scan)
   ------------------------------------------------------------------ */

function old_ids( array $params ) {
	$_GET = wp_slash( $params );
	if ( isset( $_GET['osszes'] ) && '1' === sanitize_key( wp_unslash( $_GET['osszes'] ) ) ) {
		$listings = KMAE_Query::get_listings( array( 'per_page' => KMAE_Browse::LIST_SCAN, 'exclude_contacted' => true ) );
		$listings = KMAE_Browse::apply_sort( $listings );
	} else {
		$wizard = KMAE_Browse::parse_wizard_params();
		if ( empty( $wizard['county'] ) || ! KMAE_Browse::counties_are_valid( $wizard['county'] ) || empty( $wizard['bodies'] ) || ! KMAE_Browse::is_wizard_complete( $wizard ) ) {
			return null; /* a régi oldal itt varázsló-lépést mutat, nincs találati lista */
		}
		$county_slugs = KMAE_Browse::county_slugs( $wizard['county'] );
		$listings     = KMAE_Query::get_listings(
			array(
				'per_page'          => KMAE_Browse::LIST_SCAN,
				'county'            => 1 === count( $county_slugs ) ? $county_slugs[0] : '',
				'bodies'            => $wizard['bodies'],
				'exclude_contacted' => true,
			)
		);
		$listings = KMAE_Browse::filter_by_wizard( $listings, $wizard );
		$listings = KMAE_Browse::apply_sort( $listings, $wizard['mod'] ?? '' );
	}
	return array_map(
		static function ( $p ) {
			return (int) $p->ID;
		},
		$listings
	);
}

/* wpdb::prepare utánzat (%d, %f → %F, %s → idézett) — az sqlite-os futtatáshoz. */
function fake_prepare( $sql, array $args ) {
	$i = 0;
	return preg_replace_callback(
		'/%[dsf]/',
		static function ( $m ) use ( &$i, $args ) {
			$v = $args[ $i++ ];
			if ( '%d' === $m[0] ) {
				return (string) (int) $v;
			}
			if ( '%f' === $m[0] ) {
				return sprintf( '%F', (float) $v );
			}
			return "'" . str_replace( "'", "''", (string) $v ) . "'";
		},
		$sql
	);
}

/* ------------------------------------------------------------------
   Esetek: minden rendezés × szűrő-kombinációk
   ------------------------------------------------------------------ */

$combos = array(
	'egy megye'           => array( 'megye' => 'pest', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'tobb megye'          => array( 'megye' => 'fejer,pest,budapest', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'egesz orszag'        => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'kivitel'             => array( 'megye' => 'orszag', 'kivitel' => 'sedan,other', 'mod' => 'mind' ),
	'egy megye+kivitel'   => array( 'megye' => 'pest', 'kivitel' => 'suv,estate,other', 'mod' => 'mind' ),
	'marka'               => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'marka' => 'opel' ),
	'marka+modell'        => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'marka' => 'Opel', 'modell' => 'ASTRA' ),
	'ev'                  => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'ev_min' => '2010', 'ev_max' => '2020' ),
	'km'                  => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'km_min' => '50000', 'km_max' => '250000' ),
	'ar (kp mod)'         => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'kp', 'kp_min' => '1000000', 'kp_max' => '6000000' ),
	'ar lista'            => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'ar' => 'guide,fixed' ),
	'ajanlat mod'         => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'ajanlat' ),
	'tilto lista'         => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'tilt' => array( 'opel', 'Volkswagen|golf', 'BMW|x5' ) ),
	'ccm+le+uzem+valto'   => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'ccm_min' => '1200', 'ccm_max' => '2500', 'le_min' => '90', 'uzem' => 'benzin', 'valto' => 'manual' ),
	'allapot'             => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'allapot' => 'good,excellent' ),
	'muszaki 1-12 ho'     => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'muszaki' => 'honap', 'mot_min' => '1', 'mot_max' => '12' ),
	'muszaki+lejart'      => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'muszaki' => 'honap', 'mot_min' => '6', 'mot_lejart' => '1' ),
	'muszaki csak ervenyes' => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'muszaki' => 'van' ),
	'osszes'              => array( 'osszes' => '1', 'megye' => 'pest', 'marka' => 'opel' ),
	'kombinalt'           => array( 'megye' => 'pest,budapest,fejer', 'kivitel' => 'sedan,suv,other,hatchback', 'mod' => 'mind', 'ev_min' => '2008', 'tilt' => array( 'Toyota' ), 'ar' => 'fixed,open,guide' ),
	'hianyos (nincs mod)' => array( 'megye' => 'pest', 'kivitel' => 'mind' ),
);
$sorts = array(
	'ajanlott'     => array(),
	'legujabb'     => array( 'rendez' => 'legujabb' ),
	'ar_novekvo'   => array( 'rendez' => 'ar_novekvo' ),
	'ar_csokkeno'  => array( 'rendez' => 'ar_csokkeno' ),
	'evjarat'      => array( 'rendez' => 'evjarat' ),
	'km'           => array( 'rendez' => 'km' ),
	'teljesitmeny' => array( 'rendez' => 'teljesitmeny' ),
	'nev'          => array( 'rendez' => 'nev' ),
	'tavolsag'     => array( 'rendez' => 'tavolsag', 'lat' => '47.50', 'lon' => '19.04' ),
	'tavolsag 60km' => array( 'rendez' => 'tavolsag', 'lat' => '47.19', 'lon' => '18.41', 'km' => '60' ),
	'tavolsag rossz hely' => array( 'rendez' => 'tavolsag', 'lat' => '52.00', 'lon' => '19.04' ),
);

$ctx  = array(
	'today'           => $today->format( 'Y-m-d' ),
	'single_category' => KMAE_Launch::is_single_category(),
);
$rows = array();
foreach ( $POSTS as $p ) {
	$rows[] = KMAE_Browse_Index::columns( $p );
}

/* Személyes állapot (user 3): saját hirdetések, 3 megkeresett, 14 átlépett. */
define( 'PERSONAL_USER', 3 );
$personal_passed    = array();
$personal_contacted = array();
$active_ids         = array_keys( $POSTS );
mt_srand( 99 );
shuffle( $active_ids );
$personal_contacted = array_slice( $active_ids, 0, 3 );
$personal_passed    = array_slice( $active_ids, 3, 14 );
$USER_META[ PERSONAL_USER ] = array(
	KMAE_Browse::PASSED_META    => $personal_passed,
	KMAE_Browse::CONTACTED_META => $personal_contacted,
);
$personal_own = array();
foreach ( $POSTS as $p ) {
	if ( PERSONAL_USER === (int) $p->post_author ) {
		$personal_own[] = (int) $p->ID;
	}
}
/* A get_contacted_listing_ids() chat-lekérdezése: itt nincs chat. */
$chat_wpdb = new class() {
	public function prepare( $sql ) {
		return $sql;
	}
	public function get_col() {
		return array();
	}
};

/* A régi út a személyre szabást a szűrés ELŐTT, a get_listings adagján végzi: egyezik-e ez az adag a szűrt halmazzal? */
function old_prefilter_equal( array $params ) {
	if ( isset( $params['osszes'] ) ) {
		return true;
	}
	$wizard = KMAE_Browse::parse_wizard_params();
	$slugs  = KMAE_Browse::county_slugs( $wizard['county'] );
	$base   = KMAE_Query::get_listings( array( 'per_page' => KMAE_Browse::LIST_SCAN, 'county' => 1 === count( $slugs ) ? $slugs[0] : '', 'bodies' => $wizard['bodies'] ) );
	return count( $base ) === count( KMAE_Browse::filter_by_wizard( $base, $wizard ) );
}

$cases     = array();
$nonempty  = 0;
foreach ( $combos as $cname => $cparams ) {
	foreach ( $sorts as $sname => $sparams ) {
		$params = array_merge( $cparams, $sparams );
		$name   = $cname . ' / ' . $sname;
		$old    = old_ids( $params );
		$q      = KMAE_Browse_Feed::parse_query( $params );

		check( $name . ': complete', ( null !== $old ) === $q['complete'] );
		if ( null === $old ) {
			continue;
		}
		$nonempty += $old ? 1 : 0;

		/* Kanonikus query: idempotens, sorrend-független, és ugyanazt az SQL-t adja. */
		$canon = KMAE_Browse_Feed::canonical_query( $params );
		parse_str( $canon, $reparsed );
		check( $name . ': canonical idempotens', KMAE_Browse_Feed::canonical_query( $reparsed ) === $canon, $canon );
		$shuffled = $params;
		uksort(
			$shuffled,
			static function ( $a, $b ) {
				return strcmp( md5( $b ), md5( $a ) );
			}
		);
		check( $name . ': canonical sorrend-fuggetlen', KMAE_Browse_Feed::canonical_query( $shuffled ) === $canon );
		check( $name . ': canonical = ugyanaz az SQL', KMAE_Browse_Feed::build_sql( KMAE_Browse_Feed::parse_query( $reparsed ), 'idx', 'ids', $ctx ) === KMAE_Browse_Feed::build_sql( $q, 'idx', 'ids', $ctx ) );

		list( $ids_sql, $ids_args )     = KMAE_Browse_Feed::build_sql( $q, 'idx', 'ids', $ctx );
		list( $count_sql, $count_args ) = KMAE_Browse_Feed::build_sql( $q, 'idx', 'count', $ctx );
		$pages = array();
		$npages = max( 1, (int) ceil( count( $old ) / KMAE_Browse_Feed::PER_PAGE ) ) + 1; /* +1: az utolsó utáni üres oldal */
		for ( $page = 1; $page <= $npages; $page++ ) {
			$pq                 = KMAE_Browse_Feed::parse_query( array_merge( $params, array( 'page' => (string) $page ) ) );
			list( $psql, $parg ) = KMAE_Browse_Feed::build_sql( $pq, 'idx', 'page', $ctx );
			$pages[]            = fake_prepare( $psql, $parg );
		}

		$distances = array();
		foreach ( $old as $lid ) {
			$d = KMAE_Browse::distance_for( $lid );
			if ( null !== $d && 'tavolsag' === $q['sort'] ) {
				$distances[ (string) $lid ] = $d;
			}
		}

		/* Belépett user (átlépett / megkeresett / saját hirdetések): a régi út személyre szabott sorrendje. */
		$CURRENT_USER = PERSONAL_USER;
		$wpdb         = $chat_wpdb;
		$old_personal = old_ids( $params );
		$_GET         = wp_slash( $params );
		$prefilter    = old_prefilter_equal( $params );
		$CURRENT_USER = 0;

		parse_str( KMAE_Browse_Feed::base_query( $params ), $base_params );
		list( $dsql, $dargs ) = KMAE_Browse_Feed::build_sql( KMAE_Browse_Feed::parse_query( $base_params ), 'idx', 'ids', $ctx );

		$cases[] = array(
			'name'      => $name,
			'base_ids_sql' => fake_prepare( $dsql, $dargs ),
			'personal'  => array(
				'expected'         => $old_personal,
				'prefilter_equal'  => $prefilter,
				'default_sort'     => KMAE_Browse::SORT_DEFAULT === $q['sort'],
			),
			'expected'  => $old,
			'ids_sql'   => fake_prepare( $ids_sql, $ids_args ),
			'count_sql' => fake_prepare( $count_sql, $count_args ),
			'pages'     => $pages,
			'distances' => (object) $distances,
		);
	}
}

/* ------------------------------------------------------------------
   Műszaki: months_left = a régi privát mot_months_left; a határdátumok pontosak
   ------------------------------------------------------------------ */

$ref = new ReflectionMethod( 'KMAE_Browse', 'mot_months_left' );
$ref->setAccessible( true );
$probe = array( '', 'x', '2026-02-30', '0000-00-00' );
for ( $n = -800; $n <= 1200; $n += 7 ) {
	$probe[] = $today->modify( ( $n < 0 ? '' : '+' ) . $n . ' days' )->format( 'Y-m-d' );
}
foreach ( $probe as $date ) {
	check( 'months_left = mot_months_left (' . $date . ')', $ref->invoke( null, $date ) === KMAE_Browse_Feed::months_left( $date, $ctx['today'] ) );
}

$day   = new DateTimeImmutable( '2026-01-01' );
$ks    = array( 0, 1, 2, 3, 6, 11, 12, 13, 24 );
$monot = 0;
for ( $t = 0; $t < 731; $t++ ) {
	$td   = $day->modify( '+' . $t . ' days' )->format( 'Y-m-d' );
	$prev = PHP_INT_MIN;
	for ( $n = -70; $n <= 26 * 31; $n++ ) {
		$v = KMAE_Browse_Feed::months_left( $day->modify( sprintf( '%+d days', $t + $n ) )->format( 'Y-m-d' ), $td );
		if ( $v < $prev ) {
			++$monot;
		}
		$prev = $v;
	}
	foreach ( $ks as $k ) {
		$first  = KMAE_Browse_Feed::mot_first_date( $k, $td );
		$before = ( new DateTimeImmutable( $first ) )->modify( '-1 day' )->format( 'Y-m-d' );
		if ( ! ( KMAE_Browse_Feed::months_left( $first, $td ) >= $k && KMAE_Browse_Feed::months_left( $before, $td ) < $k ) ) {
			check( "mot_first_date($k, $td)", false, $first );
		} else {
			++$passed;
		}
	}
}
check( 'months_left monoton (731 nap × 876 nap)', 0 === $monot, (string) $monot );

/* ------------------------------------------------------------------
   REST feed: fejléc, user-függetlenség (stub REST-objektumok + rögzítő $wpdb)
   ------------------------------------------------------------------ */

class WP_REST_Request {
	private $params;
	private $route;
	public function __construct( $params, $route = '/kmae/v1/feed' ) {
		$this->params = $params;
		$this->route  = $route;
	}
	public function get_query_params() {
		return $this->params;
	}
	public function get_route() {
		return $this->route;
	}
}
class WP_REST_Response {
	public $data;
	public $headers = array();
	public function __construct( $data, $status ) {
		$this->data = $data;
	}
	public function header( $k, $v ) {
		$this->headers[ $k ] = $v;
	}
}
class Fake_WPDB {
	public $sql = array();
	public function prepare( $sql, $args ) {
		return fake_prepare( $sql, $args );
	}
	public function get_col( $sql ) {
		$this->sql[] = $sql;
		return array( 101, 102 );
	}
	public function get_results( $sql, $output = 'OBJECT' ) {
		$this->sql[] = $sql;
		if ( ARRAY_N === $output ) {
			return array( array( '101', '5' ), array( '102', '5' ) );
		}
		return array( (object) array( 'listing_id' => 101, 'is_reserved' => '1', 'card' => '{"v":1,"id":101}' ) );
	}
	public function get_var( $sql ) {
		$this->sql[] = $sql;
		return 2;
	}
}
define( 'ARRAY_N', 'ARRAY_N' );
check( 'ranks: egyenlő értékek és null-ok csoportja', array( 0, 0, 1, 2, 2, 3 ) === KMAE_Browse_Feed::ranks( array( '1.5', '1.5', '2', null, null, '3' ) ) );
function wp_json_encode( $v, $f = 0 ) {
	return json_encode( $v, $f );
}
$wpdb = new Fake_WPDB();
$OPTS[ KMAE_Browse_Index::SCHEMA_OPTION ] = KMAE_Browse_Index::SCHEMA_VERSION;
class KMAE_Database {
	public static function table( $n ) {
		return 'wp_kmae_' . $n;
	}
}

$feed_params = array( 'megye' => 'pest', 'kivitel' => 'mind', 'mod' => 'mind', 'rendez' => 'km' );
$outputs     = array();
foreach ( array( 0, 7, 31 ) as $uid ) {
	$CURRENT_USER = $uid;
	$wpdb->sql    = array();
	$resp         = KMAE_Browse_Feed::rest_feed( new WP_REST_Request( $feed_params ) );
	check( "feed user=$uid: Cache-Control public s-maxage=60", 'public, max-age=0, s-maxage=60' === ( $resp->headers['Cache-Control'] ?? '' ) );
	check( "feed user=$uid: a feed alatt a user 0", 0 === $CURRENT_USER );
	check( "feed user=$uid: SQL-ben nincs user", ! preg_grep( '/author|user/i', $wpdb->sql ) );
	$outputs[] = json_encode( array( $resp->data, $resp->headers, $wpdb->sql ) );
}
check( 'feed: vendég és két belépett user válasza bájtra azonos', 1 === count( array_unique( $outputs ) ) );
check( 'feed: tétel-séma', array( 'id', 'reserved', 'distance_km', 'card' ) === array_keys( $resp->data['items'][0] ) && true === $resp->data['items'][0]['reserved'] );
check( 'feed: 1. oldalon ids + total + ranks', array( 101, 102 ) === $resp->data['ids'] && 2 === $resp->data['total'] && 1 === $resp->data['pages'] && array( 0, 0 ) === $resp->data['ranks'] );
$GLOBALS['wp'] = (object) array( 'query_vars' => array( 'rest_route' => '/kmae/v1/feed' ) );
check( 'nocache fejléc a feednél kikapcsolva (belépve is)', false === KMAE_Browse_Feed::filter_nocache_headers( true ) );
$GLOBALS['wp'] = (object) array( 'query_vars' => array( 'rest_route' => '/kmae/v1/me/browse' ) );
check( 'nocache fejléc más végpontnál marad', true === KMAE_Browse_Feed::filter_nocache_headers( true ) );
check( 'is_feed_route: /kmae/v1/feed/ igen, /kmae/v1/feedx nem', KMAE_Browse_Feed::is_feed_route( '/kmae/v1/feed/' ) && ! KMAE_Browse_Feed::is_feed_route( '/kmae/v1/feedx' ) );
$CURRENT_USER = 0;

/* ------------------------------------------------------------------
   Kiírás + sqlite futtatás
   ------------------------------------------------------------------ */

$out = sys_get_temp_dir() . '/kmae-browse-feed-' . getmypid() . '.json';
file_put_contents(
	$out,
	json_encode(
		array(
			'rows'  => $rows,
			'cases' => $cases,
			'me'    => array(
				'passed'          => $personal_passed,
				'contacted'       => $personal_contacted,
				'own'             => $personal_own,
				'passed_interval' => KMAE_Browse::PASSED_INTERVAL,
			),
		),
		JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
	)
);

echo 'PHP: ' . $passed . ' ok, ' . count( $failed ) . " hiba; SQL-esetek: " . count( $cases ) . ' (nem üres: ' . $nonempty . ")\n";

$py = __DIR__ . '/test-browse-feed-sqlite.py';
passthru( 'python3 -I ' . escapeshellarg( $py ) . ' ' . escapeshellarg( $out ), $code );
unlink( $out );

exit( $failed || $code ? 1 : 0 );
