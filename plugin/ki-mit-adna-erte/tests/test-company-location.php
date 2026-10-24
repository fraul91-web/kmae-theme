<?php
/**
 * Céges profil: a szigorú helyszín-ellenőrzés csak VÁLTOZÁSNÁL hiba (KMAE_Company::save_profile_fields).
 * A régi, szabad szöveggel mentett telephelyű cég a többi adatát (pl. a kereskedői jelölőket) a helyszín
 * javítása nélkül is mentheti; megváltoztatott érvénytelen helyszín továbbra is hiba.
 * WordPress nélkül, stubokkal. Run: php tests/test-company-location.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$root = dirname( __DIR__ );

$USER_META = array();
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_textarea_field( $v ) { return trim( (string) $v ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $v ) ); }
function __( $t, $d = '' ) { return $t; }
function get_user_meta( $id, $key, $single = false ) { global $USER_META; return $USER_META[ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $v ) { global $USER_META; $USER_META[ $id ][ $key ] = $v; return true; }
function delete_user_meta( $id, $key ) { global $USER_META; unset( $USER_META[ $id ][ $key ] ); return true; }
function wp_update_user( $a ) { return $a['ID']; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
class WP_Error {
	public $code;
	public function __construct( $code = '', $m = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
class KMAE_Auth {
	public static function is_company( $id ) { return true; }
}
/* Szigorú ellenőrzés a stubban: a „Rossz” település egyik megyében sincs; a „vác” kisbetűs alak kanonizálódik „Vác”-ra. */
class KMAE_Counties {
	public static function location_error( $county, $city ) {
		if ( '' === $county || '' === $city ) {
			return 'required';
		}
		return in_array( $city, array( 'Vác', 'vác' ), true ) && 'pest' === $county ? '' : 'mismatch';
	}
	public static function canonical_city( $county, $city ) { return 'vác' === $city ? 'Vác' : $city; }
	public static function validate_listing_location( $c, $city ) { return '' === self::location_error( $c, $city ); }
	public static function save_listing_location( $id, $c, $city ) {}
}
require $root . '/includes/class-kmae-company.php';

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
function code( $r ) { return is_wp_error( $r ) ? $r->get_error_code() : 'OK'; }
function data( $over = array() ) {
	return array_merge( array( 'company_name' => 'Autó Kft', 'address' => 'Fő u. 1.', 'county' => 'pest', 'city' => 'Vác', 'intro' => '', 'buys' => 1 ), $over );
}

/* Régi cég: érvénytelen (megye, település) párral mentve. */
$USER_META[5] = array( KMAE_Company::META_COMPANY_COUNTY => 'pest', KMAE_Company::META_COMPANY_CITY => 'Nincsilyenfalva' );
$r = KMAE_Company::save_profile_fields( 5, data( array( 'city' => 'Nincsilyenfalva', 'buys' => 1 ) ) );
check( 'változatlan érvénytelen helyszín → a profil mentődik', 'OK' === code( $r ), code( $r ) );
check( 'változatlan érvénytelen helyszín → a többi adat (jelölő) tényleg mentve, a helyszín megmarad', '1' === $USER_META[5][ KMAE_Company::META_COMPANY_BUYS ] && 'Nincsilyenfalva' === $USER_META[5][ KMAE_Company::META_COMPANY_CITY ] && 'pest' === $USER_META[5][ KMAE_Company::META_COMPANY_COUNTY ] );

$r = KMAE_Company::save_profile_fields( 5, data( array( 'city' => 'Másikfalva' ) ) );
check( 'megváltoztatott érvénytelen település → hiba', 'company_location_mismatch' === code( $r ), code( $r ) );
check( 'hiba esetén nem íródik felül a régi helyszín', 'Nincsilyenfalva' === $USER_META[5][ KMAE_Company::META_COMPANY_CITY ] );

$r = KMAE_Company::save_profile_fields( 5, data( array( 'county' => 'heves', 'city' => 'Nincsilyenfalva' ) ) );
check( 'megváltoztatott megye (érvénytelen pár) → hiba', 'company_location_mismatch' === code( $r ), code( $r ) );

$r = KMAE_Company::save_profile_fields( 5, data( array( 'city' => 'vác' ) ) );
check( 'érvényes új helyszín → mentődik, a város kanonizálva', 'OK' === code( $r ) && 'Vác' === $USER_META[5][ KMAE_Company::META_COMPANY_CITY ], code( $r ) );

/* Új cég (nincs tárolt helyszín): az érvénytelen pár hiba marad; az üres telephely is. */
$r = KMAE_Company::save_profile_fields( 6, data( array( 'city' => 'Nincsilyenfalva' ) ) );
check( 'új cég érvénytelen helyszínnel → hiba', 'company_location_mismatch' === code( $r ), code( $r ) );
$r = KMAE_Company::save_profile_fields( 5, data( array( 'city' => '' ) ) );
check( 'üres település → company_location_required (nem enyhül)', 'company_location_required' === code( $r ), code( $r ) );

/* A hirdetésre alkalmazás szigorú marad. */
$USER_META[7] = array( KMAE_Company::META_COMPANY_COUNTY => 'pest', KMAE_Company::META_COMPANY_CITY => 'Nincsilyenfalva' );
check( 'apply_listing_location: érvénytelen tárolt helyszín → továbbra is hiba', 'company_location_mismatch' === code( KMAE_Company::apply_listing_location( 1, 7 ) ) );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
