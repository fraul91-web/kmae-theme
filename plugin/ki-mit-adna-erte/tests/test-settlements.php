<?php
/**
 * KMAE_Settlements + helyszín-ellenőrzés + koordináták (WordPress nélkül).
 *
 * Run: php tests/test-settlements.php
 */

define( 'ABSPATH', __DIR__ . '/' );
$root = dirname( __DIR__ );
define( 'KMAE_PLUGIN_DIR', $root . '/' );
define( 'DAY_IN_SECONDS', 86400 );

/* --- WP stubok --------------------------------------------------------- */
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $text ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) ); }
function add_action() {}

$META      = array();
$USER_META = array();
$COMPANIES = array();
$SCHEDULED = array();
function get_post_meta( $id, $key, $single = false ) { global $META; return $META[ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { global $META; $META[ $id ][ $key ] = $value; return true; }
function delete_post_meta( $id, $key ) { global $META; unset( $META[ $id ][ $key ] ); return true; }
function get_user_meta( $id, $key, $single = false ) { global $USER_META; return $USER_META[ $id ][ $key ] ?? ''; }
function get_option( $key, $default = false ) { return $default; }
function wp_next_scheduled( $hook, $args = array() ) { return false; }
function wp_schedule_single_event( $ts, $hook, $args = array() ) { global $SCHEDULED; $SCHEDULED[] = $args; return true; }
function get_post_type( $id ) { return 'kmae_listing'; }

class KMAE_Auth {
	public static function is_company( $user_id ) { global $COMPANIES; return ! empty( $COMPANIES[ $user_id ] ); }
}

require $root . '/includes/class-kmae-counties.php';
require $root . '/includes/class-kmae-settlements.php';
require $root . '/includes/class-kmae-geo.php';
require $root . '/includes/class-kmae-company.php';

$passed = 0;
$failed = array();
function check( $name, $condition ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo 'PASS  ' . $name . "\n";
	} else {
		$failed[] = $name;
		echo 'FAIL  ' . $name . "\n";
	}
}

/* --- Kanonikus egyezés -------------------------------------------------- */
check( 'Szeged pontosan', 'Szeged' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'Szeged' ) );
check( '„szeged ” (kisbetű + szóköz)', 'Szeged' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'szeged ' ) );
check( '„SZEGED” (nagybetű)', 'Szeged' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'SZEGED' ) );
check( '„HÓDMEZŐVÁSÁRHELY” (ékezetes nagybetű)', 'Hódmezővásárhely' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'HÓDMEZŐVÁSÁRHELY' ) );
check( '„hodmezovasarhely” (ékezet nélkül)', 'Hódmezővásárhely' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'hodmezovasarhely' ) );
check( '„Hódmezôvásárhely” (ô kódolási hiba)', 'Hódmezővásárhely' === KMAE_Settlements::canonical_name( 'csongrad-csanad', 'Hódmezôvásárhely' ) );
check( '„  tiszavasvari  ” (Szabolcs)', 'Tiszavasvári' === KMAE_Settlements::canonical_name( 'szabolcs-szatmar-bereg', '  tiszavasvari  ' ) );
check( '„6720 Szeged” (irányítószámmal)', 'Szeged' === KMAE_Settlements::canonical_name( 'csongrad-csanad', '6720 Szeged' ) );
check( 'Ékezet nélküli, kétértelmű „Komoro” (Komoró / Kömörő) → null', null === KMAE_Settlements::find( 'szabolcs-szatmar-bereg', 'Komoro' ) );
check( 'Ékezetes „Kömörő” egyértelmű', 'Kömörő' === KMAE_Settlements::canonical_name( 'szabolcs-szatmar-bereg', 'kömörő' ) );
check( 'Nem létező település → null', null === KMAE_Settlements::find( 'pest', 'Nincsilyenfalva' ) );
check( 'Ismeretlen megye → null', null === KMAE_Settlements::find( 'nincs-megye', 'Szeged' ) );
check( 'Üres település → null', null === KMAE_Settlements::find( 'pest', '   ' ) );

/* --- Megye-ellenőrzés --------------------------------------------------- */
check( 'Pest + Tiszavasvári → false', false === KMAE_Counties::validate_listing_location( 'pest', 'Tiszavasvári' ) );
check( 'Szabolcs + Tiszavasvári → true', true === KMAE_Counties::validate_listing_location( 'szabolcs-szatmar-bereg', 'Tiszavasvári' ) );
check( 'location_error: mismatch', 'mismatch' === KMAE_Counties::location_error( 'pest', 'Tiszavasvári' ) );
check( 'location_error: required (üres város)', 'required' === KMAE_Counties::location_error( 'pest', '' ) );
check( 'location_error: required (rossz megye)', 'required' === KMAE_Counties::location_error( 'orszag', 'Szeged' ) );
check( 'location_error: jó', '' === KMAE_Counties::location_error( 'pest', 'érd' ) );
check( 'canonical_city: jó → KSH név', 'Érd' === KMAE_Counties::canonical_city( 'pest', 'érd' ) );
check( 'canonical_city: ismeretlen → változatlan', 'Valami' === KMAE_Counties::canonical_city( 'pest', 'Valami' ) );

/* --- Budapest ---------------------------------------------------------- */
check( 'Budapest', 'Budapest' === KMAE_Settlements::canonical_name( 'budapest', 'budapest' ) );
check( '„Budapest XI. kerület” → Budapest', 'Budapest' === KMAE_Settlements::canonical_name( 'budapest', 'Budapest XI. kerület' ) );
check( '„Bp.” → Budapest', 'Budapest' === KMAE_Settlements::canonical_name( 'budapest', 'Bp.' ) );
check( 'Pest megye + Budapest → false', false === KMAE_Counties::validate_listing_location( 'pest', 'Budapest' ) );
check( 'Budapest megye + Szeged → false', false === KMAE_Counties::validate_listing_location( 'budapest', 'Szeged' ) );

/* --- Irányítószám ------------------------------------------------------ */
check( '6720 → Szeged', array( array( 'csongrad-csanad', 'Szeged' ) ) === KMAE_Settlements::by_zip( '6720' ) );
check( '6720 (int) → Szeged', array( array( 'csongrad-csanad', 'Szeged' ) ) === KMAE_Settlements::by_zip( 6720 ) );
$z8981 = KMAE_Settlements::by_zip( '8981' );
check( '8981 közös: Gellénháza + Lickóvadamos', 2 === count( $z8981 ) && in_array( array( 'zala', 'Gellénháza' ), $z8981, true ) && in_array( array( 'zala', 'Lickóvadamos' ), $z8981, true ) );
check( '1051 → Budapest (előtag-szabály)', array( array( 'budapest', 'Budapest' ) ) === KMAE_Settlements::by_zip( '1051' ) );
check( '1999 → Budapest (listán nincs, előtag)', array( array( 'budapest', 'Budapest' ) ) === KMAE_Settlements::by_zip( '1999' ) );
check( 'Ismeretlen 0000 → üres', array() === KMAE_Settlements::by_zip( '0000' ) );
check( 'Ismeretlen 9999 → üres', array() === KMAE_Settlements::by_zip( '9999' ) );
check( 'Hibás „67” → üres', array() === KMAE_Settlements::by_zip( '67' ) );

/* --- Koordináták ------------------------------------------------------- */
$c = KMAE_Settlements::coords( 'csongrad-csanad', 'szeged' );
check( 'Szeged koordináta (≈46.25, 20.15)', is_array( $c ) && abs( $c[0] - 46.25 ) < 0.1 && abs( $c[1] - 20.15 ) < 0.1 );
check( 'Rossz megye → nincs koordináta', null === KMAE_Settlements::coords( 'pest', 'Szeged' ) );

/* --- find_anywhere ----------------------------------------------------- */
$a = KMAE_Settlements::find_anywhere( 'tiszavasvari' );
check( 'find_anywhere Tiszavasvári → Szabolcs', $a && 'szabolcs-szatmar-bereg' === $a['county'] && 'Tiszavasvári' === $a['name'] );
check( 'find_anywhere Budapest XI. ker. → budapest', ( $b = KMAE_Settlements::find_anywhere( 'Budapest XI. ker.' ) ) && 'budapest' === $b['county'] );
check( 'find_anywhere „komlo” (Komló / Kömlő) → null', null === KMAE_Settlements::find_anywhere( 'komlo' ) );
check( 'find_anywhere „Komló” → Baranya', ( $k = KMAE_Settlements::find_anywhere( 'Komló' ) ) && 'baranya' === $k['county'] );
check( 'find_anywhere ismeretlen → null', null === KMAE_Settlements::find_anywhere( 'Nincsilyenfalva' ) );

/* --- for_county -------------------------------------------------------- */
$bp = KMAE_Settlements::for_county( 'budapest' );
check( 'for_county budapest = [Budapest]', array( 'Budapest' ) === $bp );
$cs = KMAE_Settlements::for_county( 'csongrad-csanad' );
check( 'for_county csongrad-csanad tartalmazza Szegedet', in_array( 'Szeged', $cs, true ) && count( $cs ) > 50 );
$total = 0;
foreach ( array_keys( KMAE_Counties::get_all() ) as $slug ) {
	$total += count( KMAE_Settlements::for_county( $slug ) );
}
check( 'Összesen 3155 település, mind a 20 megyében', 3155 === $total );

/* --- KMAE_Geo ---------------------------------------------------------- */
$META[1] = array( '_kmae_county' => 'szabolcs-szatmar-bereg', '_kmae_city' => 'Tiszavasvári', '_kmae_geo' => array( 47.4, 19.25 ) );
$g = KMAE_Geo::listing_coords( 1 );
check( 'listing_coords: a lista felülírja a régi (rossz) metát', $g && abs( $g[0] - 47.95 ) < 0.1 && abs( $g[1] - 21.36 ) < 0.1 );
$META[2] = array( '_kmae_county' => 'pest', '_kmae_city' => 'Tiszavasvári', '_kmae_geo' => array( 47.1, 19.2 ) );
check( 'listing_coords: nem egyező régi adat → meta (tartalék)', array( 47.1, 19.2 ) === KMAE_Geo::listing_coords( 2 ) );
$META[3] = array( '_kmae_county' => 'pest', '_kmae_city' => 'Nincsilyenfalva' );
check( 'listing_coords: nincs meta → megyeszékhely', KMAE_Geo::COUNTY_SEATS['pest'] === KMAE_Geo::listing_coords( 3 ) );
$META[4] = array( '_kmae_county' => 'csongrad-csanad', '_kmae_city' => 'Szeged' );
KMAE_Geo::location_changed( 4 );
check( 'location_changed: azonnali lista-koordináta, nincs háttérfeladat', is_array( $META[4]['_kmae_geo'] ?? null ) && empty( $SCHEDULED ) );
KMAE_Geo::geocode_listing( 4 );
check( 'geocode_listing: lista-koordináta (nincs Nominatim)', abs( $META[4]['_kmae_geo'][0] - 46.25 ) < 0.1 );

/* save_listing_location: kanonikus név kerül a metába. */
KMAE_Counties::save_listing_location( 5, 'csongrad-csanad', '  szeged ' );
check( 'save_listing_location: kanonikus név + koordináta', 'Szeged' === $META[5]['_kmae_city'] && 'Csongrád-Csanád, Szeged' === $META[5]['_kmae_location'] && is_array( $META[5]['_kmae_geo'] ?? null ) );

/* user_coords */
$COMPANIES[10] = true;
$USER_META[10] = array( 'kmae_company_county' => 'csongrad-csanad', 'kmae_company_city' => 'Szeged' );
$COMPANIES[11] = true;
$USER_META[11] = array( 'kmae_company_county' => 'pest', 'kmae_company_city' => 'Szeged' );
$USER_META[12] = array( 'kmae_company_county' => 'csongrad-csanad', 'kmae_company_city' => 'Szeged' );
$u = KMAE_Geo::user_coords( 10 );
check( 'user_coords: céges, egyező telephely', $u && abs( $u[0] - 46.25 ) < 0.1 );
check( 'user_coords: céges, rossz megye → null', null === KMAE_Geo::user_coords( 11 ) );
check( 'user_coords: nem céges → null', null === KMAE_Geo::user_coords( 12 ) );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
