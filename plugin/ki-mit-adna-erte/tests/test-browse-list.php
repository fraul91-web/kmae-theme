<?php
/**
 * Böngészés v2 lista nézet: a templates/partials/browse-list.php HTML-kimenete a régi úton
 * (KMAE_Query::get_listings + filter_by_wizard + apply_sort → WP_Post[]) és az új úton
 * (KMAE_Browse_Feed::list_order + list_rows az index-táblából) bájtra azonos — egyetlen kivétel
 * az előnézeti kép `srcset` / `sizes` attribútuma. Belépett user (átlépett + megkeresett + saját hirdetések).
 *
 * WordPress és MySQL nélkül: az SQL-t a tests/test-browse-list-sqlite.py futtatja (háttérfolyamat,
 * soronként JSON), a WP-függvények tömb-alapú stubok. Opcionális: KMAE_LIST_DUMP=<mappa> → a HTML-ek kiírása.
 *
 * Run: php tests/test-browse-list.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
define( 'KMAE_LAUNCH_MODE', 'single' );
define( 'KMAE_LAUNCH_CATEGORY', 'jarmu' );
define( 'KMAE_OFFER_TYPE_CASH', 'cash' );
define( 'ARRAY_N', 'ARRAY_N' );

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

$POSTS     = array();
$META      = array();
$TERMS     = array();
$OPTS      = array();
$RESERVED  = array();
$ATT       = array();
$USER_META = array();
$CURRENT_USER = 0;

function __( $s ) {
	return $s;
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
	return esc_html( $s );
}
function esc_url( $s ) {
	return esc_html( $s );
}
function esc_html__( $s ) {
	return $s;
}
function esc_html_e( $s ) {
	echo $s;
}
function esc_attr_e( $s ) {
	echo $s;
}
function number_format_i18n( $n ) {
	return number_format( $n, 0, ',', ' ' );
}
function add_query_arg( $k, $v, $url = '/hirdetesek/' ) {
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $k . '=' . $v;
}
function remove_query_arg( $k, $url = '/hirdetesek/' ) {
	return $url;
}
function absint( $v ) {
	return abs( (int) $v );
}
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}
function wp_basename( $s ) {
	return basename( $s );
}
function wp_upload_dir() {
	return array();
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
/* A helyi PHP-ban nincs mbstring: polyfill (régi és új kód ugyanezt kapja). */
if ( ! function_exists( 'mb_strtolower' ) ) {
	function mb_strtolower( $s ) {
		return strtr( strtolower( $s ), array( 'Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ö' => 'ö', 'Ő' => 'ő', 'Ú' => 'ú', 'Ü' => 'ü', 'Ű' => 'ű', 'Š' => 'š' ) );
	}
	function mb_strtoupper( $s ) {
		return strtr( strtoupper( $s ), array( 'á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ö' => 'Ö', 'ő' => 'Ő', 'ú' => 'Ú', 'ü' => 'Ü', 'ű' => 'Ű', 'š' => 'Š' ) );
	}
	function mb_strlen( $s ) {
		return strlen( utf8_decode( $s ) );
	}
	function mb_convert_case( $s, $mode, $enc = null ) {
		return ucwords( $s );
	}
	define( 'MB_CASE_TITLE', 2 );
}
function wp_parse_args( $a, $d ) {
	return array_merge( $d, (array) $a );
}
function untrailingslashit( $s ) {
	return rtrim( $s, '/\\' );
}
function wp_json_encode( $v, $f = 0 ) {
	return json_encode( $v, $f );
}
function is_user_logged_in() {
	return $GLOBALS['CURRENT_USER'] > 0;
}
function get_current_user_id() {
	return $GLOBALS['CURRENT_USER'];
}
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
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['META'][ $id ][ $key ] = $value;
}
function delete_post_meta( $id, $key ) {
	unset( $GLOBALS['META'][ $id ][ $key ] );
}
function get_post_thumbnail_id( $id ) {
	return (int) ( $GLOBALS['META'][ $id ]['_thumbnail_id'] ?? 0 );
}
function set_post_thumbnail( $id, $att ) {
	$GLOBALS['META'][ $id ]['_thumbnail_id'] = (int) $att;
}
function delete_post_thumbnail( $id ) {
	unset( $GLOBALS['META'][ $id ]['_thumbnail_id'] );
}
function get_post_type( $id ) {
	if ( isset( $GLOBALS['ATT'][ $id ] ) ) {
		return 'attachment';
	}
	return isset( $GLOBALS['POSTS'][ $id ] ) ? 'kmae_listing' : false;
}
function get_permalink( $id ) {
	return 'https://example.test/hirdetes/' . $id . '/';
}
function wp_get_attachment_url( $id ) {
	return $GLOBALS['ATT'][ $id ]['url'] ?? false;
}
function wp_get_attachment_metadata( $id ) {
	return $GLOBALS['ATT'][ $id ]['meta'] ?? false;
}
/* image_downsize: van ilyen méret → az, különben a teljes kép. */
function wp_get_attachment_image_url( $id, $size = 'medium' ) {
	$att = $GLOBALS['ATT'][ $id ] ?? null;
	if ( ! $att ) {
		return false;
	}
	if ( 'full' !== $size && ! empty( $att['meta']['sizes'][ $size ]['file'] ) ) {
		return dirname( $att['url'] ) . '/' . $att['meta']['sizes'][ $size ]['file'];
	}
	return $att['url'];
}
function get_attached_file( $id ) {
	return false;
}
$PRIMED = array( 'meta' => 0, 'posts' => 0 );
function update_meta_cache( $type, $ids ) {
	$GLOBALS['PRIMED']['meta'] += count( $ids );
}
function _prime_post_caches( $ids, $term = true, $meta = true ) {
	$GLOBALS['PRIMED']['posts'] += count( $ids );
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
	public static function reserved_listing_ids() {
		global $RESERVED;
		return $RESERVED;
	}
}
class KMAE_Database {
	public static function table( $n ) {
		return 'wp_kmae_' . $n;
	}
}

/* Eladó a kártyához (KMAE_Browse_Index::seller). */
if ( ! class_exists( 'KMAE_Company' ) ) {
	class KMAE_Company {
		public static function seller_display_name( $user_id ) {
			return 5 === (int) $user_id ? 'Kiss Péter' : '';
		}
	}
}
if ( ! class_exists( 'KMAE_Auth' ) ) {
	class KMAE_Auth {
		public static function first_name( $user_id ) {
			return '';
		}
		public static function account_type_label( $user_id ) {
			return 'Magánszemély';
		}
	}
}
if ( ! function_exists( 'is_email' ) ) {
	function is_email( $v ) {
		return false !== strpos( (string) $v, '@' );
	}
}

require $root . '/includes/class-kmae-launch.php';
require $root . '/includes/class-kmae-taxonomies.php';
require $root . '/includes/class-kmae-meta.php';
require $root . '/includes/class-kmae-counties.php';
require $root . '/includes/class-kmae-vehicle.php';
require $root . '/includes/class-kmae-vehicle-icons.php';
require $root . '/includes/class-kmae-icons.php';
require $root . '/includes/class-kmae-seller.php';
require $root . '/includes/class-kmae-geo.php';
require $root . '/includes/class-kmae-browse.php';
require $root . '/includes/class-kmae-image-cards.php';
require $root . '/includes/class-kmae-images.php';
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

/* wpdb::prepare utánzat (%d, %f → %F, %s → idézett). */
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

/* Az SQL-t a Python/sqlite háttérfolyamat futtatja. */
class Sqlite_WPDB {
	public $queries = 0;
	public $own     = array();
	public $posts   = 'wp_posts';
	private $proc;
	private $pipes;
	public function __construct( $rows_file ) {
		$py         = __DIR__ . '/test-browse-list-sqlite.py';
		$this->proc = proc_open( array( 'python3', '-I', $py, $rows_file ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ), $this->pipes );
	}
	public function prepare( $sql, ...$args ) {
		return fake_prepare( $sql, 1 === count( $args ) && is_array( $args[0] ) ? $args[0] : $args );
	}
	private function run( $sql ) {
		++$this->queries;
		fwrite( $this->pipes[0], json_encode( array( 'sql' => $sql ) ) . "\n" );
		fflush( $this->pipes[0] );
		$res = json_decode( (string) fgets( $this->pipes[1] ), true );
		if ( ! is_array( $res ) || isset( $res['error'] ) ) {
			throw new RuntimeException( 'SQL hiba: ' . ( $res['error'] ?? 'nincs válasz' ) . ' — ' . $sql );
		}
		return $res;
	}
	public function get_results( $sql, $output = 'OBJECT' ) {
		$res = $this->run( $sql );
		if ( ARRAY_N === $output ) {
			return $res['rows'];
		}
		return array_map(
			static function ( $row ) use ( $res ) {
				return (object) array_combine( $res['cols'], $row );
			},
			$res['rows']
		);
	}
	public function get_col( $sql ) {
		return false !== strpos( $sql, 'post_author' ) ? $this->own : array();
	}
	public function get_var( $sql ) {
		return 0;
	}
	public function close() {
		fclose( $this->pipes[0] );
		proc_close( $this->proc );
	}
}
/* A get_contacted_listing_ids() chat-lekérdezése: itt nincs chat. */
class Chat_WPDB {
	public function prepare( $sql ) {
		return $sql;
	}
	public function get_col() {
		return array();
	}
}

/* ------------------------------------------------------------------
   Kitalált hirdetések (determinisztikus), képekkel
   ------------------------------------------------------------------ */

function make_dataset( $count, $seed ) {
	global $POSTS, $META, $TERMS, $RESERVED, $ATT, $OPTS;
	$POSTS    = $META = $TERMS = $RESERVED = $ATT = array();
	$today    = new DateTimeImmutable( 'today' );

	mt_srand( $seed );
	$pick = static function ( array $a ) {
		return $a[ mt_rand( 0, count( $a ) - 1 ) ];
	};

	$brands    = array(
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
		array( 'MERCEDES-BENZ', 'C 220' ),
		array( 'VW', 'Up' ),
	);
	$bodies    = array( 'sedan', 'estate', 'hatchback', 'suv', 'crossover', 'other', '', 'van' );
	$counties  = array( 'pest', 'budapest', 'fejer', 'bacs-kiskun', 'zala', '' );
	$cities    = array( 'Budaörs', 'Érd', 'Székesfehérvár', 'Kecskemét', '' );
	$fuels     = array( 'benzin', 'diesel', 'hybrid', 'electric', '' );
	$gears     = array( 'manual', 'automatic', '' );
	$conds     = array( 'excellent', 'good', 'fair', 'project', '' );
	$modes     = array( 'fixed', 'guide', 'open', '', 'fixed', 'guide' );
	$mot_specs = array( '', '-40d', '+0d', '+1m', '+6m', '+12m', '+24m' );

	$OPTS['kmae_geo_cities'] = array(
		md5( 'pest|érd' ) => array( 47.3919, 18.9045 ),
		md5( 'fejer|székesfehérvár' ) => array( 47.1860, 18.4221 ),
	);

	$base_ts = ( new DateTimeImmutable( '2026-03-01 08:00:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
	for ( $i = 1; $i <= $count; $i++ ) {
		$id = 100 + $i;
		$ts = $base_ts + mt_rand( 0, 200 ) * 86400 + mt_rand( 0, 86399 );
		if ( 0 === $i % 9 ) {
			$ts = $base_ts + 50 * 86400; /* azonos időpontok: a tiebreak próbája */
		}
		$gmt   = gmdate( 'Y-m-d H:i:s', $ts );
		$off   = ( (int) gmdate( 'n', $ts ) >= 4 && (int) gmdate( 'n', $ts ) <= 10 ) ? 7200 : 3600;
		$local = gmdate( 'Y-m-d H:i:s', $ts + $off );
		$bm    = $pick( $brands );

		$POSTS[ $id ] = new WP_Post(
			array(
				'ID'            => $id,
				'post_author'   => mt_rand( 2, 9 ),
				'post_status'   => 0 === $i % 17 ? 'draft' : 'publish',
				'post_date'     => $local,
				'post_date_gmt' => $gmt,
				'post_title'    => 0 === $i % 5 ? 'Eladó ' . $bm[0] . ' autó – jó állapot' : 'Eladó <' . $bm[0] . '> & "autó"',
			)
		);
		$TERMS[ $id ] = 0 === $i % 23 ? array( 'egyeb-kategoria' ) : array( 'jarmu' );

		$mode  = $pick( $modes );
		$price = mt_rand( 0, 9 ) ? mt_rand( 5, 90 ) * 100000 : 0;
		$mot   = $pick( $mot_specs );
		if ( preg_match( '/^([+-]\d+)([dm])$/', $mot, $m ) ) {
			$mot = $today->modify( $m[1] . ( 'd' === $m[2] ? ' days' : ' months' ) )->format( 'Y-m-d' );
		}

		$META[ $id ] = array(
			'_kmae_status'               => 0 === $i % 11 ? 'expired' : 'active',
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
		if ( 0 !== $i % 4 ) {
			$META[ $id ]['_kmae_geo'] = array( 46.0 + mt_rand( 0, 2500 ) / 1000, 16.5 + mt_rand( 0, 6000 ) / 1000 );
		}
		if ( 0 === $i % 7 ) {
			$RESERVED[ $id ] = true;
		}

		/* Képek: 0 nincs, 1 egy, 2 három, 3 csak bélyegkép (galéria nélkül), 4 hiányzó csatolmány a galériában,
		   5 kártyaméret nélküli (csak teljes) kép, 6 két kép. */
		$att = static function ( $aid, $with_sizes ) use ( &$ATT ) {
			$meta = array( 'width' => 1600, 'height' => 1200, 'sizes' => array() );
			if ( $with_sizes ) {
				foreach ( array( 'medium_large' => 768, 'kmae_card_s' => 480, 'kmae_card_m' => 800, 'kmae_card_l' => 1200 ) as $key => $w ) {
					$meta['sizes'][ $key ] = array( 'file' => "car-$aid-{$w}w.webp" );
				}
			}
			$ATT[ $aid ] = array( 'url' => "https://example.test/wp-content/uploads/2026/10/car-$aid.webp", 'meta' => $meta );
		};
		$kind = $i % 7;
		$base = 10000 + $i * 10;
		if ( 1 === $kind ) {
			$att( $base, true );
			$META[ $id ]['_kmae_gallery']  = array( $base );
			$META[ $id ]['_thumbnail_id']  = $base;
		} elseif ( 2 === $kind ) {
			foreach ( array( 0, 1, 2 ) as $k ) {
				$att( $base + $k, true );
			}
			$META[ $id ]['_kmae_gallery']  = array( $base, $base + 1, $base + 2 );
			$META[ $id ]['_thumbnail_id']  = $base;
		} elseif ( 3 === $kind ) {
			$att( $base, true );
			$META[ $id ]['_thumbnail_id']  = $base;
		} elseif ( 4 === $kind ) {
			$att( $base + 1, true );
			$META[ $id ]['_kmae_gallery']  = array( $base, $base + 1 ); /* a $base csatolmány nincs */
			$META[ $id ]['_thumbnail_id']  = $base;
		} elseif ( 5 === $kind ) {
			$att( $base, false );
			$META[ $id ]['_kmae_gallery']  = array( $base );
			$META[ $id ]['_thumbnail_id']  = $base;
		} elseif ( 6 === $kind ) {
			$att( $base, true );
			$att( $base + 1, true );
			$META[ $id ]['_kmae_gallery']  = array( $base, $base + 1 );
			$META[ $id ]['_thumbnail_id']  = $base;
		}
	}
	/* Két egyforma koordinátájú és időpontú hirdetés. */
	$META[110]['_kmae_geo'] = array( 47.1, 19.2 );
	$META[120]['_kmae_geo'] = array( 47.1, 19.2 );
}

/* ------------------------------------------------------------------
   Régi út (pontosan a KMAE_Shortcodes::browse_vehicle_wizard adat-útja, LIST_SCAN) és a kirajzolás
   ------------------------------------------------------------------ */

function old_listings( array $params ) {
	$_GET = wp_slash( $params );
	if ( isset( $_GET['osszes'] ) && '1' === sanitize_key( wp_unslash( $_GET['osszes'] ) ) ) {
		$listings = KMAE_Query::get_listings( array( 'per_page' => KMAE_Browse::LIST_SCAN, 'exclude_contacted' => true ) );
		$listings = KMAE_Browse::apply_sort( $listings );
		return array( $listings, array( 'county' => '', 'bodies' => array(), 'mod' => '' ) );
	}
	$wizard = KMAE_Browse::parse_wizard_params();
	if ( empty( $wizard['county'] ) || ! KMAE_Browse::counties_are_valid( $wizard['county'] ) || empty( $wizard['bodies'] ) || ! KMAE_Browse::is_wizard_complete( $wizard ) ) {
		return null;
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
	return array( $listings, $wizard );
}

/* A KMAE_Browse::$distances (privát, kérésenként egyszer töltődik) tiszta lappal indul minden esetnél. */
function reset_distances() {
	$ref = new ReflectionProperty( 'KMAE_Browse', 'distances' );
	$ref->setAccessible( true );
	$ref->setValue( null, array() );
}

function render_list( $listings, $list_idx, $wizard_summary, array $params ) {
	$_GET = wp_slash( $params );
	ob_start();
	include dirname( __DIR__ ) . '/templates/partials/browse-list.php';
	return ob_get_clean();
}

/** A megengedett egyetlen különbség: az előnézeti kép srcset + sizes attribútuma. */
function strip_srcset( $html ) {
	return preg_replace( '/ srcset="[^"]*" sizes="[^"]*"/', '', $html );
}

/* ------------------------------------------------------------------
   Esetek
   ------------------------------------------------------------------ */

$combos = array(
	'egy megye'         => array( 'megye' => 'pest', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'tobb megye'        => array( 'megye' => 'fejer,pest,budapest', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'egesz orszag'      => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind' ),
	'kivitel+marka'     => array( 'megye' => 'orszag', 'kivitel' => 'sedan,suv,other,hatchback', 'mod' => 'mind', 'marka' => 'opel' ),
	'ev+km+uzem'        => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'ev_min' => '2008', 'ev_max' => '2022', 'km_min' => '30000', 'uzem' => 'benzin' ),
	'kp mod'            => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'kp', 'kp_min' => '1000000', 'kp_max' => '7000000' ),
	'ajanlat mod'       => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'ajanlat' ),
	'tilto+ar+allapot'  => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'tilt' => array( 'opel', 'Volkswagen|golf' ), 'ar' => 'guide,fixed', 'allapot' => 'good,excellent,fair' ),
	'muszaki'           => array( 'megye' => 'orszag', 'kivitel' => 'mind', 'mod' => 'mind', 'muszaki' => 'honap', 'mot_min' => '1', 'mot_max' => '24', 'mot_lejart' => '1' ),
	'osszes'            => array( 'osszes' => '1', 'megye' => 'pest', 'marka' => 'opel' ),
	'hianyos (nincs mod)' => array( 'megye' => 'pest', 'kivitel' => 'mind' ),
);
$sorts = array(
	'ajanlott'      => array(),
	'legujabb'      => array( 'rendez' => 'legujabb' ),
	'ar_novekvo'    => array( 'rendez' => 'ar_novekvo' ),
	'ar_csokkeno'   => array( 'rendez' => 'ar_csokkeno' ),
	'evjarat'       => array( 'rendez' => 'evjarat' ),
	'km'            => array( 'rendez' => 'km' ),
	'teljesitmeny'  => array( 'rendez' => 'teljesitmeny' ),
	'nev'           => array( 'rendez' => 'nev' ),
	'tavolsag'      => array( 'rendez' => 'tavolsag', 'lat' => '47.50', 'lon' => '19.04' ),
	'tavolsag 80km' => array( 'rendez' => 'tavolsag', 'lat' => '47.19', 'lon' => '18.41', 'km' => '80' ),
	'tavolsag rossz hely' => array( 'rendez' => 'tavolsag', 'lat' => '52.00', 'lon' => '19.04' ),
);

define( 'PERSONAL_USER', 3 );
$dump_dir = getenv( 'KMAE_LIST_DUMP' );
$stats    = array( 'cases' => 0, 'empty' => 0, 'rows' => 0, 'srcset' => 0, 'pages' => 0, 'km_chips' => 0, 'reserved' => 0, 'queries' => 0 );
$chat_db  = new Chat_WPDB();

/**
 * Egy adathalmaz: index-sorok + sqlite, majd minden eset régi vs. új HTML.
 *
 * @param int   $count  Hirdetések száma.
 * @param int   $seed   Véletlen mag.
 * @param array $cases  név => [paraméterek, [oldalak]].
 */
function run_dataset( $count, $seed, array $cases, $label ) {
	global $POSTS, $META, $RESERVED, $USER_META, $CURRENT_USER, $wpdb, $chat_db, $stats, $dump_dir, $OPTS, $PRIMED;

	make_dataset( $count, $seed );

	/* Index-sorok: columns() + card (mint a KMAE_Browse_Index::sync). */
	$rows = array();
	foreach ( $POSTS as $p ) {
		$row         = KMAE_Browse_Index::columns( $p );
		$row['card'] = $row['is_active'] ? wp_json_encode( KMAE_Browse_Index::card( $p ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '{}';
		$rows[]      = $row;
	}
	$rows_file = sys_get_temp_dir() . '/kmae-browse-list-' . getmypid() . '.json';
	file_put_contents( $rows_file, json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION ) );
	$db = new Sqlite_WPDB( $rows_file );

	/* Személyes állapot (user 3): saját hirdetések, 3 megkeresett, 14 átlépett. */
	$ids = array_keys( $POSTS );
	mt_srand( $seed + 1 );
	shuffle( $ids );
	$USER_META[ PERSONAL_USER ] = array(
		KMAE_Browse::PASSED_META    => array_slice( $ids, 3, 14 ),
		KMAE_Browse::CONTACTED_META => array_slice( $ids, 0, 3 ),
	);
	foreach ( $POSTS as $p ) {
		if ( PERSONAL_USER === (int) $p->post_author ) {
			$db->own[] = (int) $p->ID;
		}
	}
	$OPTS[ KMAE_Browse_Index::SCHEMA_OPTION ] = KMAE_Browse_Index::SCHEMA_VERSION;

	foreach ( $cases as $name => $spec ) {
		list( $params, $pages ) = $spec;
		$CURRENT_USER           = PERSONAL_USER;

		/* Régi út. */
		$wpdb = $chat_db;
		reset_distances();
		$old = old_listings( $params );

		/* Új út (= browse-swipe.php: list_order → ID-k; a partial töltei a sorokat). */
		$wpdb = $db;
		$new_complete = KMAE_Browse_Feed::parse_query( wp_unslash( wp_slash( $params ) ) )['complete'];
		check( "$label $name: complete egyezik", ( null !== $old ) === $new_complete );
		if ( null === $old ) {
			$new_ids = KMAE_Browse_Feed::list_order( wp_unslash( wp_slash( $params ) ), PERSONAL_USER );
			check( "$label $name: hiányos varázsló → üres lista", array() === $new_ids );
			continue;
		}
		$new_ids = KMAE_Browse_Feed::list_order( wp_unslash( wp_slash( $params ) ), PERSONAL_USER );
		$old_ids = array_map(
			static function ( $p ) {
				return (int) $p->ID;
			},
			$old[0]
		);
		check( "$label $name: ID-sorrend azonos", $old_ids === $new_ids, 'régi ' . implode( ',', $old_ids ) . ' | új ' . implode( ',', $new_ids ) );
		++$stats['cases'];
		if ( ! $old_ids ) {
			++$stats['empty'];
		}

		foreach ( $pages as $page ) {
			$render_params = $params + ( $page > 1 ? array( 'oldal' => (string) $page ) : array() );
			$old_html = render_list( $old[0], false, $old[1], $render_params );
			$new_html = render_list( $new_ids, true, $old[1], $render_params );
			$same     = strip_srcset( $new_html ) === $old_html;
			check( "$label $name (oldal $page): HTML azonos (srcset/sizes nélkül)", $same );
			if ( ! $same ) {
				$a = explode( "\n", $old_html );
				$b = explode( "\n", strip_srcset( $new_html ) );
				foreach ( $a as $n => $line ) {
					if ( ( $b[ $n ] ?? null ) !== $line ) {
						echo "      első eltérés a(z) " . ( $n + 1 ) . ". sorban:\n      régi: " . trim( $line ) . "\n      új:   " . trim( (string) ( $b[ $n ] ?? '' ) ) . "\n";
						break;
					}
				}
			}
			check( "$label $name (oldal $page): régi úton nincs srcset", false === strpos( $old_html, 'srcset=' ) );
			++$stats['pages'];
			$stats['rows']     += substr_count( $old_html, '<article class="kmae-browse-row' );
			$stats['srcset']   += substr_count( $new_html, ' srcset="' );
			$stats['km_chips'] += substr_count( $old_html, 'kmae-browse-row__loc' ) ? preg_match_all( '/<em>~/', $old_html ) : 0;
			$stats['reserved'] += substr_count( $old_html, 'is-reserved' );
			if ( $dump_dir ) {
				$slug = preg_replace( '/[^a-z0-9]+/', '-', strtolower( "$label-$name-$page" ) );
				file_put_contents( "$dump_dir/$slug.old.html", $old_html );
				file_put_contents( "$dump_dir/$slug.new.html", $new_html );
			}
		}
	}
	$stats['queries'] += $db->queries;
	$db->close();
	unlink( $rows_file );
	$CURRENT_USER = 0;
}

/* A: 50 hirdetés, minden rendezés × szűrő-kombináció (1. oldal). */
$cases_a = array();
foreach ( $combos as $cname => $cparams ) {
	foreach ( $sorts as $sname => $sparams ) {
		$cases_a[ "$cname / $sname" ] = array( array_merge( $cparams, $sparams ), array( 1 ) );
	}
}
run_dataset( 50, 20261017, $cases_a, 'A(50)' );

/* B: 150 hirdetés, lapozás (3 oldal) néhány esetnél; az oldalszám-túllépés is. */
$cases_b = array();
foreach ( array( 'egesz orszag', 'egy megye', 'osszes' ) as $cname ) {
	foreach ( array( 'ajanlott', 'legujabb', 'nev', 'tavolsag' ) as $sname ) {
		$cases_b[ "$cname / $sname" ] = array( array_merge( $combos[ $cname ], $sorts[ $sname ] ), array( 1, 2, 3, 9 ) );
	}
}
run_dataset( 150, 7, $cases_b, 'B(150)' );

/* use_list_index(): kapcsoló + nézet + kész index. */
$OPTS[ KMAE_Browse_Index::SCHEMA_OPTION ] = KMAE_Browse_Index::SCHEMA_VERSION;
$CURRENT_USER                             = PERSONAL_USER;
$USER_META[ PERSONAL_USER ][ KMAE_Browse::VIEW_META ] = 'lista';
$_GET = array();
unset( $OPTS[ KMAE_Browse_Feed::OPTION ] );
check( 'use_list_index: kapcsoló nélkül hamis (a régi út fut)', false === KMAE_Browse_Feed::use_list_index() );
$OPTS[ KMAE_Browse_Feed::OPTION ] = 1;
check( 'use_list_index: kapcsolóval, listanézetben, kész indexszel igaz', true === KMAE_Browse_Feed::use_list_index() );
$OPTS[ KMAE_Browse_Index::SCHEMA_OPTION ] = '0';
check( 'use_list_index: nem kész indexnél hamis', false === KMAE_Browse_Feed::use_list_index() );

echo 'PHP: ' . $passed . ' ok, ' . count( $failed ) . ' hiba'
	. ' | esetek: ' . $stats['cases'] . ' (üres találat: ' . $stats['empty'] . '), oldalak: ' . $stats['pages']
	. ', kirajzolt sor: ' . $stats['rows'] . ', srcset-es kép (új): ' . $stats['srcset']
	. ', Foglalt: ' . $stats['reserved'] . ', távolság-chip: ' . $stats['km_chips']
	. ', index-lekérdezés: ' . $stats['queries'] . "\n";

exit( $failed ? 1 : 0 );
