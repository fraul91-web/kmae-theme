<?php
/**
 * Csoportos kereskedői megkeresés — jogosultság + találatok (KMAE_Dealer_Leads::can_start / match).
 *
 * WordPress és MySQL nélkül, a hivatalos településlistával (valódi koordináták).
 * Szeged-i hirdetés; a cégek ismert távolságra (légvonal, Szegedtől):
 * Hódmezővásárhely 23,2 · Makó 24,2 · Szentes 45,2 · Kecskemét 81,6 · Budapest 163,1 ·
 * Debrecen 181 · Nyíregyháza 222,5 km.
 *
 * Run: php tests/test-dealer-match.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
$root = dirname( __DIR__ );
define( 'KMAE_PLUGIN_DIR', $root . '/' );

/* --- WP stubok --------------------------------------------------------- */
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $text ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) ); }
function add_action() {}

class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $v ) { return $v instanceof WP_Error; }

$OPTIONS   = array();
$IS_ADMIN  = false;
$META      = array();
$USER_META = array();
$COMPANIES = array(); /* user ID => true */
$PRIVATE   = array(); /* user ID => true */
$POSTS     = array();
$STATE     = array(); /* listing ID => listing_state() */
$CONTACTED = array(); /* listing ID => dealer ID-k */
$CALLS     = array( 'user_query' => 0, 'meta_cache' => 0, 'is_company' => 0, 'user_meta_reads' => 0 );

function get_option( $key, $default = false ) { global $OPTIONS; return array_key_exists( $key, $OPTIONS ) ? $OPTIONS[ $key ] : $default; }
function current_user_can( $cap ) { global $IS_ADMIN; return 'manage_options' === $cap && $IS_ADMIN; }
$ADMIN_IDS = array();
function user_can( $id, $cap ) { global $ADMIN_IDS; return 'manage_options' === $cap && in_array( (int) $id, $ADMIN_IDS, true ); }
function get_post_meta( $id, $key, $single = false ) { global $META; return $META[ $id ][ $key ] ?? ''; }
function get_user_meta( $id, $key, $single = false ) { global $USER_META, $CALLS; ++$CALLS['user_meta_reads']; return $USER_META[ $id ][ $key ] ?? ''; }
function get_post( $id ) { global $POSTS; return $POSTS[ $id ] ?? null; }
function update_meta_cache( $type, $ids ) { global $CALLS; ++$CALLS['meta_cache']; return array(); }

class WP_User_Query {
	private $ids = array();
	public function __construct( $args ) {
		global $USER_META, $CALLS;
		++$CALLS['user_query'];
		$keys = array_column( $args['meta_query'], 'key' );
		foreach ( $USER_META as $id => $meta ) {
			foreach ( $keys as $k ) {
				if ( isset( $meta[ $k ] ) && '1' === (string) $meta[ $k ] ) {
					$this->ids[] = $id;
					break;
				}
			}
		}
	}
	public function get_results() { return $this->ids; }
}

class KMAE_Database {
	public static function table( $name ) { return 'wp_kmae_' . $name; }
}
class KMAE_Auth {
	public static function is_company( $user_id ) { global $COMPANIES, $CALLS; ++$CALLS['is_company']; return ! empty( $COMPANIES[ $user_id ] ); }
	public static function is_private_person( $user_id ) { global $PRIVATE; return ! empty( $PRIVATE[ $user_id ] ); }
}
class KMAE_Chat_Offers {
	public static function listing_state( $listing_id ) { global $STATE; return $STATE[ $listing_id ] ?? ''; }
}
class FakeWpdb {
	public function prepare( $sql, ...$args ) { return $args ? array( 'listing' => (int) $args[0] ) : array(); }
	public function get_col( $q ) { global $CONTACTED; return $CONTACTED[ $q['listing'] ] ?? array(); }
}
$GLOBALS['wpdb'] = new FakeWpdb();

require $root . '/includes/class-kmae-counties.php';
require $root . '/includes/class-kmae-settlements.php';
require $root . '/includes/class-kmae-geo.php';
require $root . '/includes/class-kmae-company.php';
require $root . '/includes/class-kmae-user-blocks.php';
require $root . '/includes/class-kmae-meta.php';
require $root . '/includes/class-kmae-dealer-leads.php';

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
function err_code( $v ) { return is_wp_error( $v ) ? $v->get_error_code() : 'OK'; }
function ids( array $map ) { return array_keys( $map ); }

/* --- Adatok ------------------------------------------------------------- */
const SELLER = 1;
$PRIVATE[ SELLER ] = true;

function post( $id, $author, $status = 'publish', $type = 'kmae_listing' ) {
	global $POSTS;
	$POSTS[ $id ] = (object) array( 'ID' => $id, 'post_author' => $author, 'post_type' => $type, 'post_status' => $status );
}
function listing( $id, $author, $kmae_status = 'active', $county = 'csongrad-csanad', $city = 'Szeged', $wp_status = 'publish' ) {
	global $META;
	post( $id, $author, $wp_status );
	$META[ $id ] = array( '_kmae_status' => $kmae_status, '_kmae_county' => $county, '_kmae_city' => $city );
}
listing( 500, SELLER );                                       /* aktív, Szeged */
listing( 501, 30 );                                           /* céges eladó */
listing( 502, SELLER, 'closed' );                             /* lezárt */
listing( 503, SELLER );                                       /* foglalt */
listing( 504, SELLER, 'active', '', '' );                     /* nincs helyszín */
listing( 505, SELLER, 'active', 'csongrad-csanad', 'Szeged', 'draft' ); /* nem közzétett */
listing( 506, SELLER, 'expired' );                            /* lejárt */
listing( 507, SELLER );                                       /* eladott */
post( 508, SELLER, 'publish', 'post' );                       /* nem hirdetés */
$COMPANIES[30] = true;
$STATE[503] = 'reserved';
$STATE[507] = 'sold';

function dealer( $id, $county, $city, $buys, $tradein, $company = true ) {
	global $USER_META, $COMPANIES;
	$USER_META[ $id ] = array(
		KMAE_Company::META_COMPANY_COUNTY => $county,
		KMAE_Company::META_COMPANY_CITY   => $city,
	);
	if ( $buys ) {
		$USER_META[ $id ][ KMAE_Company::META_COMPANY_BUYS ] = '1';
	}
	if ( $tradein ) {
		$USER_META[ $id ][ KMAE_Company::META_COMPANY_TRADEIN ] = '1';
	}
	if ( $company ) {
		$COMPANIES[ $id ] = true;
	}
}
dealer( 11, 'csongrad-csanad', 'Hódmezővásárhely', true, true );     /* 23,2 mindkettő */
dealer( 12, 'bacs-kiskun', 'Kecskemét', true, false );               /* 81,6 csak felvásárol */
dealer( 13, 'budapest', 'Budapest', true, true );                    /* 163,1 mindkettő */
dealer( 14, 'szabolcs-szatmar-bereg', 'Nyíregyháza', false, true );  /* 222,5 csak beszámít */
dealer( 15, 'pest', 'Nincsilyenfalva', true, false );                /* érvénytelen telephely */
dealer( 16, 'csongrad-csanad', 'Makó', true, true );                 /* 24,2 — az eladó tiltja */
dealer( 17, 'csongrad-csanad', 'Szentes', true, true );              /* 45,2 — ő tiltja az eladót */
dealer( 18, 'csongrad-csanad', 'Hódmezővásárhely', false, true );    /* 23,2 csak beszámít, már megkeresett */
dealer( 19, 'csongrad-csanad', 'Szeged', true, true, false );        /* magánszemély fiók, a jelölő beragadt */
dealer( 20, 'csongrad-csanad', 'Szeged', true, true );               /* 0 mindkettő */
dealer( 21, 'hajdu-bihar', 'Debrecen', true, false );                /* 181 csak felvásárol */
dealer( 22, 'csongrad-csanad', 'Szentes', true, false );             /* 45,2 csak felvásárol */
$USER_META[ SELLER ][ KMAE_User_Blocks::META ] = array( 16 => '2026-10-01 10:00:00' );
$USER_META[ 17 ][ KMAE_User_Blocks::META ]     = array( SELLER => '2026-10-01 10:00:00' );
$CONTACTED[500] = array( 18 );

/* --- can_start ---------------------------------------------------------- */
check( 'can_start: kapcsoló ki, nem admin → hiba', 'dealer_leads_disabled' === err_code( KMAE_Dealer_Leads::can_start( 500, SELLER ) ) );
$IS_ADMIN = true;
check( 'can_start: kapcsoló ki, admin → true', true === KMAE_Dealer_Leads::can_start( 500, SELLER ) );
$IS_ADMIN = false;
$OPTIONS[ KMAE_Dealer_Leads::OPTION ] = 1;
check( 'can_start: jó eset → true', true === KMAE_Dealer_Leads::can_start( 500, SELLER ) );
check( 'can_start: nem szerző', 'dealer_leads_not_owner' === err_code( KMAE_Dealer_Leads::can_start( 500, 2 ) ) );
check( 'can_start: user 0', 'dealer_leads_not_owner' === err_code( KMAE_Dealer_Leads::can_start( 500, 0 ) ) );
check( 'can_start: céges eladó', 'dealer_leads_not_private' === err_code( KMAE_Dealer_Leads::can_start( 501, 30 ) ) );
check( 'can_start: lezárt hirdetés', 'dealer_leads_inactive' === err_code( KMAE_Dealer_Leads::can_start( 502, SELLER ) ) );
check( 'can_start: lejárt hirdetés', 'dealer_leads_inactive' === err_code( KMAE_Dealer_Leads::can_start( 506, SELLER ) ) );
check( 'can_start: nem közzétett (draft) hirdetés', 'dealer_leads_inactive' === err_code( KMAE_Dealer_Leads::can_start( 505, SELLER ) ) );
check( 'can_start: foglalt hirdetés', 'dealer_leads_reserved' === err_code( KMAE_Dealer_Leads::can_start( 503, SELLER ) ) );
check( 'can_start: eladott állapot', 'dealer_leads_reserved' === err_code( KMAE_Dealer_Leads::can_start( 507, SELLER ) ) );
$STATE[500] = 'reopened';
check( 'can_start: „Újra elérhető” (reopened) mehet', true === KMAE_Dealer_Leads::can_start( 500, SELLER ) );
unset( $STATE[500] );
check( 'can_start: nincs helyszín', 'dealer_leads_no_location' === err_code( KMAE_Dealer_Leads::can_start( 504, SELLER ) ) );
check( 'can_start: nem hirdetés post', 'dealer_leads_no_listing' === err_code( KMAE_Dealer_Leads::can_start( 508, SELLER ) ) );
check( 'can_start: nem létező hirdetés', 'dealer_leads_no_listing' === err_code( KMAE_Dealer_Leads::can_start( 9999, SELLER ) ) );
check( 'can_start: hibaüzenetek magyarul', false !== strpos( KMAE_Dealer_Leads::can_start( 503, SELLER )->get_error_message(), 'foglalt' ) );

/* --- match: érvénytelen bemenet ---------------------------------------- */
foreach ( array( 30, -25, 'x', '25abc', 25.5, null, '' ) as $bad ) {
	check( 'match: rossz sugár ' . var_export( $bad, true ), 'dealer_leads_bad_radius' === err_code( KMAE_Dealer_Leads::match( 500, $bad ) ) );
}
check( 'match: rossz cél', 'dealer_leads_bad_purpose' === err_code( KMAE_Dealer_Leads::match( 500, 25, 'buy' ) ) );
check( 'match: nem létező hirdetés', 'dealer_leads_no_listing' === err_code( KMAE_Dealer_Leads::match( 9999, 25 ) ) );
check( 'match: sugár + nincs helyszín → hiba', 'dealer_leads_no_location' === err_code( KMAE_Dealer_Leads::match( 504, 100 ) ) );
check( 'match: sugár szövegként is jó („50”)', 50 === KMAE_Dealer_Leads::match( 500, '50' )['radius'] );

/* --- match: darabszámok sugaranként és célonként ------------------------ */
$m = static function ( $radius, $purpose = null ) { return KMAE_Dealer_Leads::match( 500, $radius, $purpose ); };
$counts = static function ( $r ) { return array( $r['in_radius'], $r['matching'], $r['contacted'], $r['new'] ); };

/* [in_radius, matching, contacted, new] */
$expect = array(
	'25/null'    => array( 25, null, array( 3, 3, 1, 2 ) ),
	'25/sell'    => array( 25, 'sell', array( 3, 2, 0, 2 ) ),
	'25/tradein' => array( 25, 'tradein', array( 3, 3, 1, 2 ) ),
	'50/sell'    => array( 50, 'sell', array( 4, 3, 0, 3 ) ),
	'50/tradein' => array( 50, 'tradein', array( 4, 3, 1, 2 ) ),
	'100/sell'   => array( 100, 'sell', array( 5, 4, 0, 4 ) ),
	'100/tradein' => array( 100, 'tradein', array( 5, 3, 1, 2 ) ),
	'200/sell'   => array( 200, 'sell', array( 7, 6, 0, 6 ) ),
	'200/tradein' => array( 200, 'tradein', array( 7, 4, 1, 3 ) ),
	'0/sell'     => array( 0, 'sell', array( 9, 7, 0, 7 ) ),
	'0/tradein'  => array( 0, 'tradein', array( 9, 5, 1, 4 ) ),
	'0/null'     => array( 0, null, array( 9, 9, 1, 8 ) ),
);
foreach ( $expect as $name => $e ) {
	$r = $m( $e[0], $e[1] );
	check( 'counts ' . $name, ! is_wp_error( $r ) && $e[2] === $counts( $r ), is_wp_error( $r ) ? 'WP_Error' : json_encode( $counts( $r ) ) );
	check( 'consistency ' . $name, ! is_wp_error( $r ) && $r['matching'] === $r['contacted'] + $r['new'] && $r['new'] === count( $r['new_dealers'] ) && $r['contacted'] === count( $r['contacted_dealers'] ) && $r['radius'] === $e[0] && $r['purpose'] === $e[1] );
}

/* --- match: tartalom, rendezés, kizárások ------------------------------- */
$r = $m( 200, 'sell' );
check( 'sell/200: rendezés távolság szerint', array( 20, 11, 22, 12, 13, 21 ) === ids( $r['new_dealers'] ), json_encode( ids( $r['new_dealers'] ) ) );
check( 'sell/200: távolságok (1 tizedes)', 0.0 === $r['new_dealers'][20] && 23.2 === $r['new_dealers'][11] && 45.2 === $r['new_dealers'][22] && 81.6 === $r['new_dealers'][12] && 163.1 === $r['new_dealers'][13] && 181.0 === $r['new_dealers'][21], json_encode( $r['new_dealers'] ) );
check( 'sell/200: kihagyott cégek (tiltott ×2, magánszemély, tradein-only, érvénytelen helyszín)', ! array_intersect( array( 16, 17, 19, 18, 14, 15 ), array_merge( ids( $r['new_dealers'] ), ids( $r['contacted_dealers'] ) ) ) );
$r = $m( 25, 'tradein' );
check( 'tradein/25: contacted_dealers = [18 => 23,2], new = [20, 11]', array( 18 => 23.2 ) === $r['contacted_dealers'] && array( 20, 11 ) === ids( $r['new_dealers'] ), json_encode( $r ) );
$r = $m( 0, 'sell' );
check( 'sell/0: érvénytelen helyszínű cég bent, távolsága null, a lista végén', in_array( 15, ids( $r['new_dealers'] ), true ) && null === $r['new_dealers'][15] && 15 === array_key_last( $r['new_dealers'] ) );
check( 'sell/0: a többi rendezett (a null előtt)', array( 20, 11, 22, 12, 13, 21, 15 ) === ids( $r['new_dealers'] ), json_encode( ids( $r['new_dealers'] ) ) );
$r = $m( 25, 'sell' );
check( 'sell/25: érvénytelen helyszínű cég KI', ! isset( $r['new_dealers'][15] ) );
$r = $m( 0, 'tradein' );
check( 'tradein/0: Nyíregyháza (222,5) bent, 22 (csak felvásárol) kint', 222.5 === $r['new_dealers'][14] && ! isset( $r['new_dealers'][22] ) );

/* --- „both” (Eladnám ÉS Beszámíttatnám): VAGY, soronként a metszet-cél ---- */
foreach ( array( 25 => array( 3, 3, 1, 2 ), 50 => array( 4, 4, 1, 3 ), 100 => array( 5, 5, 1, 4 ), 200 => array( 7, 7, 1, 6 ), 0 => array( 9, 9, 1, 8 ) ) as $rad => $exp ) {
	$r = $m( $rad, 'both' );
	check( 'both counts ' . $rad, ! is_wp_error( $r ) && $exp === $counts( $r ) && 'both' === $r['purpose'], is_wp_error( $r ) ? 'WP_Error' : json_encode( $counts( $r ) ) );
	check( 'both consistency ' . $rad, $r['matching'] === $r['contacted'] + $r['new'] && $r['new'] === count( $r['new_dealers'] ) && array_keys( $r['new_dealers'] ) === array_keys( $r['new_dealer_purposes'] ) );
}
$r = $m( 0, 'both' );
check( 'both/0: VAGY = a sell és a tradein egyesítése (9), nem a metszet (11, 13, 20, 18)', 9 === $r['matching'] && count( array_unique( array_merge( array_keys( $m( 0, 'sell' )['new_dealers'] ), array_keys( $m( 0, 'tradein' )['new_dealers'] ), array_keys( $m( 0, 'tradein' )['contacted_dealers'] ) ) ) ) === 9 );
check( 'both/0: a csak felvásárló (12, 15, 21, 22) és a csak beszámító (14) is bent van', 5 === count( array_intersect( array( 12, 14, 15, 21, 22 ), ids( $r['new_dealers'] ) ) ) );
check( 'both/0: kihagyottak ugyanazok (tiltott ×2, magánszemély)', ! array_intersect( array( 16, 17, 19 ), array_merge( ids( $r['new_dealers'] ), ids( $r['contacted_dealers'] ) ) ) );
$exp_purp = array( 11 => 'both', 12 => 'sell', 13 => 'both', 14 => 'tradein', 15 => 'sell', 20 => 'both', 21 => 'sell', 22 => 'sell' );
$got_purp = $r['new_dealer_purposes'];
ksort( $got_purp );
check( 'both/0: soronkénti tényleges cél = metszet (mindkét jelölő → both, csak felvásárol → sell, csak beszámít → tradein)', $exp_purp === $got_purp, json_encode( $got_purp ) );
$r = $m( 0, 'sell' );
check( 'sell: a soronkénti cél mind sell (a két jelölős cégnél is)', array( 'sell' ) === array_values( array_unique( $r['new_dealer_purposes'] ) ) && 7 === count( $r['new_dealer_purposes'] ) );
$r = $m( 0, 'tradein' );
check( 'tradein: a soronkénti cél mind tradein (a két jelölős cégnél is)', array( 'tradein' ) === array_values( array_unique( $r['new_dealer_purposes'] ) ) && 4 === count( $r['new_dealer_purposes'] ) );
check( 'cél nélkül (csak sugár): nincs soronkénti cél', array() === $m( 25 )['new_dealer_purposes'] );
check( 'both/25: in_radius változatlan (3), új: 20 + 11 (both)', array( 20 => 'both', 11 => 'both' ) === $m( 25, 'both' )['new_dealer_purposes'] && 3 === $m( 25, 'both' )['in_radius'] );
check( 'effective_purpose: metszet-tábla', 'both' === KMAE_Dealer_Leads::effective_purpose( 'both', true, true ) && 'sell' === KMAE_Dealer_Leads::effective_purpose( 'both', true, false ) && 'tradein' === KMAE_Dealer_Leads::effective_purpose( 'both', false, true ) && null === KMAE_Dealer_Leads::effective_purpose( 'both', false, false ) && 'sell' === KMAE_Dealer_Leads::effective_purpose( 'sell', true, true ) && null === KMAE_Dealer_Leads::effective_purpose( 'sell', false, true ) && 'tradein' === KMAE_Dealer_Leads::effective_purpose( 'tradein', true, true ) && null === KMAE_Dealer_Leads::effective_purpose( 'tradein', true, false ) );
check( 'purposes() tartalmazza a both-t', in_array( 'both', KMAE_Dealer_Leads::purposes(), true ) );

/* Tiltás feloldása → a cég visszajön. */
unset( $USER_META[ SELLER ][ KMAE_User_Blocks::META ] );
check( 'tiltás feloldva (eladó → cég): 16 visszajön', isset( $m( 25, 'sell' )['new_dealers'][16] ) );
$USER_META[ SELLER ][ KMAE_User_Blocks::META ] = array( 16 => 'x' );
unset( $USER_META[17][ KMAE_User_Blocks::META ] );
check( 'tiltás feloldva (cég → eladó): 17 visszajön', isset( $m( 50, 'sell' )['new_dealers'][17] ) );
$USER_META[17][ KMAE_User_Blocks::META ] = array( SELLER => 'x' );

/* Az eladó önmagát soha nem találja (még ha beragadt jelölővel, céges fiókként is). */
$USER_META[ SELLER ][ KMAE_Company::META_COMPANY_BUYS ] = '1';
$COMPANIES[ SELLER ] = true;
check( 'eladó önmagát kihagyja', ! in_array( SELLER, ids( $m( 0, 'sell' )['new_dealers'] ), true ) && 9 === $m( 0 )['in_radius'] );
unset( $USER_META[ SELLER ][ KMAE_Company::META_COMPANY_BUYS ], $COMPANIES[ SELLER ] );

/* Már megkeresett cég sosem kerül a „new”-ba, a listing-hez kötötten. */
$CONTACTED[500] = array( 18, 11, 20 );
$r = $m( 25, 'sell' );
check( 'contacted: 11 és 20 átkerül a contacted-be', 0 === $r['new'] && 2 === $r['contacted'] && 2 === $r['matching'], json_encode( $counts( $r ) ) );
$CONTACTED[500] = array();
check( 'nincs megkeresett: minden new', 3 === $m( 25, 'tradein' )['new'] && 0 === $m( 25, 'tradein' )['contacted'] );
$CONTACTED[500] = array( 18 );

/* Nincs egyetlen cég sem. */
$saved = $USER_META;
foreach ( array_keys( $USER_META ) as $uid ) {
	if ( SELLER !== $uid ) {
		unset( $USER_META[ $uid ][ KMAE_Company::META_COMPANY_BUYS ], $USER_META[ $uid ][ KMAE_Company::META_COMPANY_TRADEIN ] );
	}
}
$r = $m( 0, 'sell' );
check( 'nincs jelölt cég → 0 mindenütt, üres listák', array( 0, 0, 0, 0 ) === $counts( $r ) && array() === $r['new_dealers'] && array() === $r['contacted_dealers'] );
$USER_META = $saved;

/* Radius 0, koordináta nélküli hirdetés (504) is működik, távolság null. */
$r = KMAE_Dealer_Leads::match( 504, 0, 'sell' );
check( 'radius 0 helyszín nélküli hirdetésnél: mindenki bent, távolság null', ! is_wp_error( $r ) && 7 === $r['new'] && array( null ) === array_values( array_unique( $r['new_dealers'] ) ), is_wp_error( $r ) ? 'WP_Error' : json_encode( $counts( $r ) ) );

/* --- Kapcsoló KI: csak admin cég kaphat megkeresést (a valódi cég nem látná / nem tudna válaszolni) --- */
$saved_option = $OPTIONS[ KMAE_Dealer_Leads::OPTION ];
unset( $OPTIONS[ KMAE_Dealer_Leads::OPTION ] );
$IS_ADMIN = true; /* az admin eladó küld */
$r = KMAE_Dealer_Leads::match( 500, 0, 'sell' );
check( 'kapcsoló ki, nincs admin cég → senki', array( 0, 0, 0, 0 ) === $counts( $r ) && array() === $r['new_dealers'], json_encode( $counts( $r ) ) );
$ADMIN_IDS = array( 11, 21 );
$r = KMAE_Dealer_Leads::match( 500, 0, 'sell' );
check( 'kapcsoló ki → csak az admin cégek (11, 21)', array( 11, 21 ) === ids( $r['new_dealers'] ) && 2 === $r['matching'] && 2 === $r['in_radius'], json_encode( $r['new_dealers'] ) );
$r = KMAE_Dealer_Leads::match( 500, 25, 'sell' );
check( 'kapcsoló ki + sugár: az admin cég is csak a sugáron belül (11)', array( 11 ) === ids( $r['new_dealers'] ) && 1 === $r['new'] );
$r = KMAE_Dealer_Leads::match( 500, 0, 'tradein' );
check( 'kapcsoló ki + cél: a beszámító admin cég (11)', array( 11 ) === ids( $r['new_dealers'] ), json_encode( $r['new_dealers'] ) );
$OPTIONS[ KMAE_Dealer_Leads::OPTION ] = $saved_option;
$r = KMAE_Dealer_Leads::match( 500, 0, 'sell' );
check( 'kapcsoló BE → az admin-szűrő nem hat (mindenki, mint eddig)', $r['matching'] > 2, json_encode( $counts( $r ) ) );
$ADMIN_IDS = array();
$IS_ADMIN  = false;

/* --- hatékonyság -------------------------------------------------------- */
$CALLS = array( 'user_query' => 0, 'meta_cache' => 0, 'is_company' => 0, 'user_meta_reads' => 0 );
$m( 25, 'sell' );
check( 'hatékonyság: 1 felhasználó-lekérdezés + 1 batch meta-cache', 1 === $CALLS['user_query'] && 1 === $CALLS['meta_cache'], json_encode( $CALLS ) );
check( 'hatékonyság: a sugáron kívüli cégekre nincs is_company hívás (csak a távolság+tiltás szűrőt túlélőkre: 11, 18, 20 + a magánszemély 19)', 4 === $CALLS["is_company"], (string) $CALLS['is_company'] );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
