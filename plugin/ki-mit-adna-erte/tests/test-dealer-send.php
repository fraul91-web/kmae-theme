<?php
/**
 * Csoportos kereskedői megkeresés — 4. fázis: AJAX előnézet + küldés, cég-értesítés.
 *
 * WordPress és MySQL nélkül: a lead-tábla SQL-jét a tests/test-dealer-leads-sqlite.py
 * futtatja (mint a test-dealer-leads.php), a felhasználók / meta / hirdetések
 * memóriában (mint a test-dealer-match.php), valódi településlistával.
 * A levélküldés (KMAE_Brand::send_mail), a csengő (KMAE_Live_Signal::bump) és a
 * JSON-válasz (wp_send_json_*) rögzítve; a „shutdown” akciókat a teszt futtatja.
 *
 * Run: php tests/test-dealer-send.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
$root = dirname( __DIR__ );
define( 'KMAE_PLUGIN_DIR', $root . '/' );

/* --- mbstring polyfill (a helyi PHP-ban nincs) ------------------------- */
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null ) { $a = preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ); return implode( '', array_slice( $a, $start, $len ) ); }
	function mb_strtoupper( $s ) { return strtoupper( (string) $s ); }
	function mb_strlen( $s ) { return count( preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ) ); }
}

/* --- WP stubok --------------------------------------------------------- */
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $text ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function absint( $v ) { return abs( (int) $v ); }
function home_url( $p = '/' ) { return 'https://albira.test' . $p; }
function admin_url( $p = '' ) { return 'https://albira.test/wp-admin/' . $p; }
function add_query_arg( $k, $v, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $k . '=' . $v; }
function is_email( $v ) { return false !== strpos( (string) $v, '@' ); }
function wp_json_encode( $v ) { return json_encode( $v ); }

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
$CURRENT   = 0;
$NONCE_OK  = true;
$META      = array();
$USER_META = array();
$USERS     = array(); /* ID => (object) user_email, display_name */
$COMPANIES = array();
$PRIVATE   = array();
$POSTS     = array();
$STATE     = array();
$HOOKS     = array(); /* shutdown akciók */
$MAILS     = array();
$BUMPS     = array();
$LOCK_BUSY = false;
$FINISHED  = 0;

function get_option( $key, $default = false ) { global $OPTIONS; return array_key_exists( $key, $OPTIONS ) ? $OPTIONS[ $key ] : $default; }
function current_user_can( $cap ) { global $IS_ADMIN; return 'manage_options' === $cap && $IS_ADMIN; }
$ADMIN_IDS = array(); /* user_can(.., manage_options): kapcsoló KI esetén csak ők kaphatnak megkeresést */
function user_can( $id, $cap ) { global $ADMIN_IDS; return 'manage_options' === $cap && in_array( (int) $id, $ADMIN_IDS, true ); }
function is_user_logged_in() { global $CURRENT; return $CURRENT > 0; }
function get_current_user_id() { global $CURRENT; return $CURRENT; }
function check_ajax_referer( $action, $field, $die = true ) { global $NONCE_OK; return 'kmae_dealer_leads' === $action && 'nonce' === $field && $NONCE_OK; }
function wp_create_nonce( $action ) { return 'nonce-' . $action; }
function get_post_meta( $id, $key, $single = false ) { global $META; return $META[ $id ][ $key ] ?? ''; }
function get_user_meta( $id, $key, $single = false ) { global $USER_META; return $USER_META[ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { global $USER_META; $USER_META[ $id ][ $key ] = $value; return true; }
function get_userdata( $id ) { global $USERS; return $USERS[ $id ] ?? false; }
function get_post( $id ) { global $POSTS; return $POSTS[ $id ] ?? null; }
function update_meta_cache( $type, $ids ) { return array(); }
function cache_users( $ids ) {}
function add_action( $hook, $cb, $prio = 10, $args = 1 ) { global $HOOKS; $HOOKS[ $hook ][ $prio ][] = $cb; }
function run_shutdown() {
	global $HOOKS;
	$h = $HOOKS['shutdown'] ?? array();
	unset( $HOOKS['shutdown'] );
	ksort( $h );
	foreach ( $h as $cbs ) {
		foreach ( $cbs as $cb ) {
			call_user_func( $cb );
		}
	}
}

class Json_Exit extends Exception {
	public $ok;
	public $data;
	public $status;
	public function __construct( $ok, $data, $status ) { parent::__construct( 'json' ); $this->ok = $ok; $this->data = $data; $this->status = $status; }
}
function wp_send_json_success( $data = null, $status = 200 ) { throw new Json_Exit( true, $data, $status ); }
function wp_send_json_error( $data = null, $status = 200 ) { throw new Json_Exit( false, $data, $status ); }

class WP_User_Query {
	private $ids = array();
	public function __construct( $args ) {
		global $USER_META;
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
	public static function is_company( $user_id ) { global $COMPANIES; return ! empty( $COMPANIES[ $user_id ] ); }
	public static function is_private_person( $user_id ) { global $PRIVATE; return ! empty( $PRIVATE[ $user_id ] ); }
	public static function first_name( $user_id ) { global $USERS; return isset( $USERS[ $user_id ] ) ? explode( ' ', $USERS[ $user_id ]->display_name )[0] : ''; }
}
class KMAE_Chat_Offers {
	public static function listing_state( $listing_id ) { global $STATE; return $STATE[ $listing_id ] ?? ''; }
}
class KMAE_Pages {
	public static function url( $slug ) { return 'https://albira.test/' . $slug . '/'; }
}
class KMAE_Vehicle {
	public static function get_display_title( $id ) { return 'Opel Astra ' . $id; }
	public static function get_swipe_subtitle( $id ) { return '2015 · 168 000 km · Dízel'; }
	public static function format_value( $amount ) { return $amount <= 0 ? '' : number_format( $amount, 0, ',', ' ' ) . ' Ft'; }
}
class KMAE_Seller {
	/* A nyilvános ár a teszt-hirdetés '_pub' meta-jából (0 = „Ajánlatot vár”). */
	public static function get_public_price( $id ) { global $META; return (float) ( $META[ $id ]['_pub'] ?? 0 ); }
}
class KMAE_Brand {
	public static function send_mail( $to, $subject, $body, $headers = array(), $wrap = array() ) {
		global $MAILS;
		$MAILS[] = compact( 'to', 'subject', 'body', 'wrap' );
		return true;
	}
}
class KMAE_Live_Signal {
	public static function bump( $user_ids, $note = null ) { global $BUMPS; $BUMPS[] = array( 'ids' => (array) $user_ids, 'note' => $note ); }
}
class KMAE_Perf {
	public static function finish_response() { global $FINISHED; ++$FINISHED; }
}

/* --- sqlite wpdb (a test-dealer-leads.php mintájára) -------------------- */
function fake_prepare( $sql, array $args ) {
	$i   = 0;
	$out = preg_replace_callback(
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
	if ( $i !== count( $args ) ) {
		throw new RuntimeException( 'prepare: ' . count( $args ) . ' argumentum, ' . $i . ' helyőrző — ' . $sql );
	}
	return $out;
}
class Sqlite_WPDB {
	public $rows_affected = 0;
	public $locks         = array();
	private $proc;
	private $pipes;
	public function __construct() {
		$this->proc = proc_open( array( 'python3', '-I', __DIR__ . '/test-dealer-leads-sqlite.py' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ), $this->pipes );
	}
	public function prepare( $sql, ...$args ) {
		return fake_prepare( $sql, 1 === count( $args ) && is_array( $args[0] ) ? $args[0] : $args );
	}
	private function run( $sql ) {
		global $LOCK_BUSY;
		/* MySQL-only elemek: INSERT IGNORE, GET_LOCK / RELEASE_LOCK. */
		if ( preg_match( "/^SELECT (GET_LOCK|RELEASE_LOCK)\('([^']+)'/", $sql, $mm ) ) {
			$this->locks[] = $mm[1] . ':' . $mm[2];
			return array( 'cols' => array( 'x' ), 'rows' => array( array( 'GET_LOCK' === $mm[1] && $LOCK_BUSY ? '0' : '1' ) ), 'rowcount' => 1, 'lastrowid' => 0 );
		}
		$sql = preg_replace( '/^INSERT IGNORE\b/', 'INSERT OR IGNORE', $sql );
		fwrite( $this->pipes[0], json_encode( array( 'sql' => $sql ) ) . "\n" );
		fflush( $this->pipes[0] );
		$res = json_decode( (string) fgets( $this->pipes[1] ), true );
		if ( ! is_array( $res ) || isset( $res['error'] ) ) {
			throw new RuntimeException( 'SQL hiba: ' . ( $res['error'] ?? 'nincs válasz' ) . ' — ' . $sql );
		}
		return $res;
	}
	public function query( $sql ) { $res = $this->run( $sql ); $this->rows_affected = max( 0, (int) $res['rowcount'] ); return $this->rows_affected; }
	public function get_results( $sql ) {
		$res = $this->run( $sql );
		return array_map( static function ( $row ) use ( $res ) { return (object) array_combine( $res['cols'], $row ); }, $res['rows'] );
	}
	public function get_row( $sql ) { $r = $this->get_results( $sql ); return $r ? $r[0] : null; }
	public function get_col( $sql ) { $res = $this->run( $sql ); return array_map( static function ( $r ) { return $r[0]; }, $res['rows'] ); }
	public function get_var( $sql ) { $res = $this->run( $sql ); return $res['rows'] ? $res['rows'][0][0] : null; }
}
$wpdb = new Sqlite_WPDB();
$GLOBALS['wpdb'] = $wpdb;
$wpdb->query(
	"CREATE TABLE wp_kmae_dealer_leads (
		id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INTEGER NOT NULL, seller_id INTEGER NOT NULL, dealer_id INTEGER NOT NULL,
		purpose TEXT NOT NULL DEFAULT 'sell', status TEXT NOT NULL DEFAULT 'pending', token TEXT NOT NULL, batch_id TEXT NOT NULL DEFAULT '',
		distance_km REAL DEFAULT NULL, dealer_price INTEGER DEFAULT NULL, conversation_id INTEGER DEFAULT NULL, listing_title TEXT NOT NULL DEFAULT '',
		created_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00', expires_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00',
		responded_at TEXT DEFAULT NULL, updated_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00',
		UNIQUE (listing_id, dealer_id), UNIQUE (token)
	)"
);

require $root . '/includes/class-kmae-counties.php';
require $root . '/includes/class-kmae-settlements.php';
require $root . '/includes/class-kmae-geo.php';
require $root . '/includes/class-kmae-company.php';
require $root . '/includes/class-kmae-user-blocks.php';
require $root . '/includes/class-kmae-meta.php';
require $root . '/includes/class-kmae-dealer-leads.php';
require $root . '/includes/class-kmae-notifications.php';

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

/** AJAX-hívás: [ok, data, status]. */
function ajax( $action, array $post ) {
	$_POST = $post;
	try {
		call_user_func( array( 'KMAE_Dealer_Leads', 'kmae_dealer_leads_send' === $action ? 'ajax_send' : 'ajax_preview' ) );
	} catch ( Json_Exit $e ) {
		return array( $e->ok, $e->data, $e->status );
	}
	return array( null, null, 0 );
}
function send( $listing, $radius, $purpose, $price = '2450000' ) {
	$post = array( 'listing_id' => (string) $listing, 'radius' => (string) $radius, 'purpose' => $purpose, 'nonce' => 'x' );
	if ( null !== $price ) {
		$post['price'] = (string) $price;
	}
	return ajax( 'kmae_dealer_leads_send', $post );
}
function preview( $listing, $radius, $purpose = '' ) { return ajax( 'kmae_dealer_leads_preview', array( 'listing_id' => (string) $listing, 'radius' => (string) $radius, 'purpose' => $purpose, 'nonce' => 'x' ) ); }
function rows_for( $listing ) { global $wpdb; return $wpdb->get_results( 'SELECT * FROM wp_kmae_dealer_leads WHERE listing_id = ' . (int) $listing . ' ORDER BY dealer_id' ); }
function reset_capture() { global $MAILS, $BUMPS; $MAILS = array(); $BUMPS = array(); }
function mail_to( $email ) { global $MAILS; foreach ( $MAILS as $m ) { if ( $m['to'] === $email ) { return $m; } } return null; }
function mail_tos() { global $MAILS; $t = array_column( $MAILS, 'to' ); sort( $t ); return $t; }

/* --- Adatok ------------------------------------------------------------- */
const SELLER = 1;
$PRIVATE[ SELLER ] = true;
$USERS[ SELLER ]   = (object) array( 'user_email' => 'kovacs.bela@example.com', 'display_name' => 'Kovács Béla' );
$USER_META[ SELLER ]['kmae_phone'] = '+36 30 123 4567';
$USERS[2]   = (object) array( 'user_email' => 'masik@example.com', 'display_name' => 'Másik Elek' );
$PRIVATE[2] = true;

function listing( $id, $author, $county = 'csongrad-csanad', $city = 'Szeged' ) {
	global $POSTS, $META;
	$POSTS[ $id ] = (object) array( 'ID' => $id, 'post_author' => $author, 'post_type' => 'kmae_listing', 'post_status' => 'publish', 'post_title' => 'Opel Astra ' . $id );
	$META[ $id ]  = array( '_kmae_status' => 'active', '_kmae_county' => $county, '_kmae_city' => $city );
}
listing( 500, SELLER );
listing( 501, 30 );                          /* céges eladó */
listing( 510, SELLER );
listing( 511, SELLER, 'baranya', 'Pécs' );    /* a környéken nincs cég */
listing( 512, SELLER );
listing( 513, SELLER );
$COMPANIES[30] = true;
$USERS[30]     = (object) array( 'user_email' => 'ceg30@example.com', 'display_name' => 'Cég Harminc' );

function dealer( $id, $county, $city, $buys, $tradein ) {
	global $USER_META, $COMPANIES, $USERS;
	$USER_META[ $id ] = array( KMAE_Company::META_COMPANY_COUNTY => $county, KMAE_Company::META_COMPANY_CITY => $city );
	if ( $buys ) {
		$USER_META[ $id ][ KMAE_Company::META_COMPANY_BUYS ] = '1';
	}
	if ( $tradein ) {
		$USER_META[ $id ][ KMAE_Company::META_COMPANY_TRADEIN ] = '1';
	}
	$COMPANIES[ $id ] = true;
	$USERS[ $id ]     = (object) array( 'user_email' => 'dealer' . $id . '@example.com', 'display_name' => 'Kereskedő' . $id . ' Kft' );
}
dealer( 11, 'csongrad-csanad', 'Hódmezővásárhely', true, true );     /* 23,2 */
dealer( 12, 'bacs-kiskun', 'Kecskemét', true, false );               /* 81,6 — kikapcsolta a levelet */
dealer( 20, 'csongrad-csanad', 'Szeged', true, true );               /* 0 */
dealer( 16, 'csongrad-csanad', 'Makó', true, true );                 /* az eladó tiltja */
dealer( 14, 'szabolcs-szatmar-bereg', 'Nyíregyháza', false, true );  /* 222,5 */
$USER_META[ SELLER ][ KMAE_User_Blocks::META ] = array( 16 => '2026-10-01 10:00:00' );
$USER_META[12]['kmae_mail_prefs']              = json_encode( array( 'dealer_lead' => 0, 'message' => 1 ) );

$OPTIONS[ KMAE_Dealer_Leads::SCHEMA_OPTION ] = KMAE_Dealer_Leads::SCHEMA_VERSION; /* a tábla kész (ready()) */

/* --- Mail catalog -------------------------------------------------------- */
$OPTIONS[ KMAE_Dealer_Leads::OPTION ] = 1; /* bekapcsolt funkció: a cégeknek szóló csoport látszik */
check( 'catalog: dealer_lead kulcs a teljes listában', in_array( 'dealer_lead', KMAE_Notifications::mail_catalog_keys(), true ) );
check( 'catalog: magánszemélynek nem látszik', ! in_array( 'dealer_lead', KMAE_Notifications::mail_catalog_keys( SELLER ), true ) && ! array_key_exists( 'dealer_lead', KMAE_Notifications::mail_prefs( SELLER ) ) );
check( 'catalog: cégnek látszik, alapból be', true === ( KMAE_Notifications::mail_prefs( 11 )['dealer_lead'] ?? null ) );
check( 'catalog: kikapcsolt cégnél ki', false === KMAE_Notifications::mail_prefs( 12 )['dealer_lead'] && ! KMAE_Notifications::allows_mail( 12, 'dealer_lead' ) && KMAE_Notifications::allows_mail( 11, 'dealer_lead' ) );
$USER_META[2]['kmae_mail_prefs'] = json_encode( array( 'dealer_lead' => 0 ) );
KMAE_Notifications::save_mail_prefs( 2, array( 'message' => '1' ) );
$saved2 = json_decode( $USER_META[2]['kmae_mail_prefs'], true );
check( 'save_mail_prefs: a nem látszó kulcs megtartja az értékét, a többi mentődik', 0 === $saved2['dealer_lead'] && 1 === $saved2['message'] && 0 === $saved2['listing_active'], json_encode( $saved2 ) );
KMAE_Notifications::save_mail_prefs( 11, array( 'message' => '1' ) );
check( 'save_mail_prefs: cégnél a dealer_lead is a posztolt érték (ki)', 0 === json_decode( $USER_META[11]['kmae_mail_prefs'], true )['dealer_lead'] );
unset( $USER_META[11]['kmae_mail_prefs'] );

/* Funkció KI: a „Cégeknek” csoport cégnek sem látszik, a mentett kapcsolók érintetlenek maradnak. */
unset( $OPTIONS[ KMAE_Dealer_Leads::OPTION ] );
$USER_META[12]['kmae_mail_prefs'] = json_encode( array( 'dealer_lead' => 0, 'message' => 1 ) );
check( 'catalog: funkció ki → cégnek sem látszik a dealer_lead', ! in_array( 'dealer_lead', KMAE_Notifications::mail_catalog_keys( 12 ), true ) && ! array_key_exists( 'dealer_lead', KMAE_Notifications::mail_prefs( 12 ) ) );
check( 'catalog: funkció ki → a csoport nincs a katalógusban', ! in_array( 'dealer', array_column( KMAE_Notifications::mail_catalog( 12 ), 'id' ), true ) );
check( 'catalog: funkció ki → a teljes lista (0) változatlanul tartalmazza', in_array( 'dealer_lead', KMAE_Notifications::mail_catalog_keys(), true ) );
KMAE_Notifications::save_mail_prefs( 12, array( 'message' => '1' ) );
check( 'save_mail_prefs: funkció ki → a mentett dealer_lead=0 megmarad', 0 === json_decode( $USER_META[12]['kmae_mail_prefs'], true )['dealer_lead'], $USER_META[12]['kmae_mail_prefs'] );
$IS_ADMIN = true;
check( 'catalog: funkció ki, de admin → látszik (kipróbálható)', in_array( 'dealer_lead', KMAE_Notifications::mail_catalog_keys( 11 ), true ) );
$IS_ADMIN = false;
$USER_META[12]['kmae_mail_prefs'] = json_encode( array( 'dealer_lead' => 0, 'message' => 1 ) );

/* --- Kapu: belépés, nonce, kapcsoló, jogosultság --------------------------- */
$CURRENT = 0;
list( $ok, $data, $st ) = send( 500, 100, 'sell' );
check( 'nincs belépve → hiba 403', false === $ok && 403 === $st && 'dealer_leads_login' === $data['code'] );
$CURRENT  = SELLER;
$NONCE_OK = false;
list( $ok, $data, $st ) = send( 500, 100, 'sell' );
check( 'rossz nonce → hiba 403', false === $ok && 403 === $st && 'dealer_leads_nonce' === $data['code'] && '' !== $data['message'] );
list( $ok, $data ) = preview( 500, 100 );
check( 'rossz nonce (előnézet) → hiba', false === $ok && 'dealer_leads_nonce' === $data['code'] );
$NONCE_OK = true;
list( $ok, $data, $st ) = send( 500, 100, 'sell' );
check( 'kapcsoló ki, nem admin → hiba', false === $ok && 'dealer_leads_disabled' === $data['code'] && 400 === $st );
$IS_ADMIN = true;
list( $ok, $data ) = preview( 500, 100, 'sell' );
check( 'kapcsoló ki, admin → előnézet megy', true === $ok, json_encode( $data ) );
$IS_ADMIN = false;
$OPTIONS[ KMAE_Dealer_Leads::OPTION ] = 1;
$CURRENT = 2;
list( $ok, $data ) = send( 500, 100, 'sell' );
check( 'nem a szerző → hiba', false === $ok && 'dealer_leads_not_owner' === $data['code'] );
list( $ok, $data ) = preview( 500, 100, 'sell' );
check( 'nem a szerző (előnézet) → hiba, nincs szám', false === $ok && 'dealer_leads_not_owner' === $data['code'] && ! isset( $data['matching'] ) );
$CURRENT = 30;
list( $ok, $data ) = send( 501, 100, 'sell' );
check( 'céges eladó → hiba', false === $ok && 'dealer_leads_not_private' === $data['code'] );
$CURRENT = SELLER;
list( $ok, $data ) = send( 500, 100, '' );
check( 'küldés cél nélkül → hiba', false === $ok && 'dealer_leads_bad_purpose' === $data['code'] && false !== strpos( $data['message'], 'beszámíttatnád' ) );
list( $ok, $data ) = send( 500, 30, 'sell' );
check( 'küldés rossz sugárral → hiba', false === $ok && 'dealer_leads_bad_radius' === $data['code'] );
list( $ok, $data ) = preview( 500, '', '' );
check( 'előnézet sugár nélkül → hiba', false === $ok && 'dealer_leads_bad_radius' === $data['code'] );
check( 'a hibák után nincs sor, levél, jelzés', array() === rows_for( 500 ) && array() === $MAILS && array() === $BUMPS );
run_shutdown();
check( 'a hibák után a shutdown sem küld', array() === $MAILS );

/* --- Kereskedői ár: kötelező, validált ------------------------------------------ */
$locks_before = count( $wpdb->locks );
foreach ( array(
	'hiányzik'            => null,
	'üres'                => '',
	'betű'                => '2 milliót',
	'tizedes-vessző'      => '2450000,5',
	'negatív'             => '-2450000',
	'nulla'               => '0',
	'túl kicsi (49 999)'  => '49999',
	'túl nagy (500 000 001)' => '500000001',
	'óriási'              => '99999999999999999999',
) as $label => $bad ) {
	list( $ok, $data, $st ) = send( 500, 100, 'sell', $bad );
	check( 'ár ' . $label . ' → dealer_leads_bad_price', false === $ok && 400 === $st && 'dealer_leads_bad_price' === $data['code'] && 'Add meg, mennyiért adnád az autót kereskedőnek.' === $data['message'], json_encode( $data ) );
}
check( 'rossz ár: nincs sor, levél, jelzés, zár', array() === rows_for( 500 ) && array() === $MAILS && array() === $BUMPS && count( $wpdb->locks ) === $locks_before );
check( 'parse_price: szóköz / nbsp / pont ezreselválasztó', 2450000 === KMAE_Dealer_Leads::parse_price( '2 450 000' ) && 2450000 === KMAE_Dealer_Leads::parse_price( '2.450.000' ) && 2450000 === KMAE_Dealer_Leads::parse_price( "2\xC2\xA0450\xC2\xA0000" ) && 50000 === KMAE_Dealer_Leads::parse_price( '50000' ) && null === KMAE_Dealer_Leads::parse_price( '1e6' ) && null === KMAE_Dealer_Leads::parse_price( '12a' ) );

/* --- Előnézet: csak számok ---------------------------------------------- */
list( $ok, $data ) = preview( 500, 100, 'sell' );
check( 'előnézet 100/sell: számok', true === $ok && array( 'in_radius' => 3, 'matching' => 3, 'contacted' => 0, 'new' => 3, 'all_sent' => false ) === $data, json_encode( $data ) );
check( 'előnézet: csak az 5 kulcs, nincs cég-azonosító / név / e-mail', array( 'in_radius', 'matching', 'contacted', 'new', 'all_sent' ) === array_keys( $data ) && false === strpos( json_encode( $data ), 'dealer' ) && false === strpos( json_encode( $data ), 'example.com' ) );
list( $ok, $data ) = preview( 500, 100 );
check( 'előnézet cél nélkül (csak sugár)', true === $ok && 3 === $data['in_radius'] && 3 === $data['matching'] );
list( $ok, $data ) = preview( 500, 100, 'tradein' );
check( 'előnézet 100/tradein: 11, 20', true === $ok && 2 === $data['matching'] && 3 === $data['in_radius'] );
check( 'előnézet nem ír', array() === rows_for( 500 ) && array() === $BUMPS );

/* --- Első küldés --------------------------------------------------------- */
$FINISHED = 0;
list( $ok, $data ) = send( 500, 100, 'sell' );
check( 'első küldés: sent 3, already 0, total 3, all_sent', true === $ok && 3 === $data['sent'] && 0 === $data['already'] && 3 === $data['total_matching'] && true === $data['all_sent'], json_encode( $data ) );
check( 'első küldés: üzenet', false !== strpos( $data['message'], 'Elküldtük 3 cégnek' ) );
check( 'első küldés: válaszban nincs cég-azonosító', array( 'sent', 'already', 'total_matching', 'all_sent', 'message' ) === array_keys( $data ) );
$rows = rows_for( 500 );
check( 'első küldés: 3 sor (11, 12, 20), pending, sell', array( 11, 12, 20 ) === array_map( 'intval', array_column( array_map( 'get_object_vars', $rows ), 'dealer_id' ) ) && array( 'pending' ) === array_values( array_unique( array_column( array_map( 'get_object_vars', $rows ), 'status' ) ) ) );
check( 'első küldés: a megadott kereskedői ár minden soron (2 450 000)', array( 2450000 ) === array_values( array_unique( array_map( 'intval', array_column( array_map( 'get_object_vars', $rows ), 'dealer_price' ) ) ) ) && 3 === count( $rows ) );
check( 'zár: GET_LOCK + RELEASE_LOCK a hirdetésre', in_array( 'GET_LOCK:kmae_dealer_leads_500', $wpdb->locks, true ) && in_array( 'RELEASE_LOCK:kmae_dealer_leads_500', $wpdb->locks, true ) );
check( 'első küldés: 1 jelzés, pontosan az új cégekkel (a levelet kikapcsoló 12 is)', 1 === count( $BUMPS ) && array( 11, 12, 20 ) === ( static function ( $a ) { sort( $a ); return $a; } )( $BUMPS[0]['ids'] ) );
check( 'push szöveg', 'Új autó-megkeresés érkezett' === $BUMPS[0]['note']['title'] && 'https://albira.test/ertesitesek/' === $BUMPS[0]['note']['url'] && false === strpos( json_encode( $BUMPS[0]['note'] ), 'kmae_lead' ) );
check( 'levelek a válasz előtt még nem épülnek', array() === $MAILS && 0 === $FINISHED );
run_shutdown();
check( 'shutdown: a válasz lezárva a levelek előtt', 1 === $FINISHED );
check( 'shutdown: levél a 11-nek és 20-nak, a 12 kikapcsolta', array( 'dealer11@example.com', 'dealer20@example.com' ) === mail_tos(), json_encode( mail_tos() ) );
$tok = array();
foreach ( $rows as $r ) {
	$tok[ (int) $r->dealer_id ] = $r->token;
}
$m11 = mail_to( 'dealer11@example.com' );
$m20 = mail_to( 'dealer20@example.com' );
check( 'levél tárgya', 'Új autó-megkeresés: Opel Astra 500' === $m11['subject'], $m11['subject'] );
check( 'levél: eladná + ajánlat', false !== strpos( $m11['body'], 'Egy magánszemély eladná az autóját, és szeretné, ha ajánlatot tennétek rá.' ) );
check( 'levél: autó, adatok, távolság, határidő', false !== strpos( $m11['body'], '„Opel Astra 500”' ) && false !== strpos( $m11['body'], '2015 · 168 000 km' ) && false !== strpos( $m11['body'], 'Kb. 23 km-re van tőletek.' ) && false !== strpos( $m11['body'], '7 napig válaszolhattok.' ), $m11['body'] );
check( 'levél: 0 km-nél „Kb. 1 km”', false !== strpos( $m20['body'], 'Kb. 1 km-re van tőletek.' ) );
check( 'levél: kereskedői ár sor (eladás)', false !== strpos( $m11['body'], 'Kereskedőnek ennyiért adná: 2 450 000 Ft' ) && false === strpos( $m11['body'], 'Beszámításra' ), $m11['body'] );
check( 'levél: „ne utasítsátok el” megjegyzés', false !== strpos( $m11['body'], 'Ne utasítsátok el csak az ár vagy a képek miatt. Ha az autó érdekes, nyomjatok az Érdekel gombra, és alkudjatok a chatben.' ) );
check( 'levél: köszönés a cég nevével', 0 === strpos( $m11['body'], 'Szia Kereskedő11!' ) );
check( 'levél: gomb = Értesítések + saját token', 'Megnézem' === $m11['wrap']['button_label'] && 'https://albira.test/ertesitesek/?kmae_lead=' . $tok[11] === $m11['wrap']['button_url'] && 'info' === $m11['wrap']['tone'] );
check( 'levél: a másik cég tokenje nincs benne', false === strpos( json_encode( $m20 ), $tok[11] ) && false === strpos( json_encode( $m11 ), $tok[20] ) );
$all = json_encode( $MAILS, JSON_UNESCAPED_UNICODE );
check( 'levél: nincs eladó név / telefon / e-mail, nincs más cég', false === strpos( $all, 'Kovács' ) && false === strpos( $all, '123 4567' ) && false === strpos( $all, 'kovacs.bela' ) && false === strpos( json_encode( $m11, JSON_UNESCAPED_UNICODE ), 'Kereskedő20' ) );
check( 'levél: nincs user/lead ID az URL-ben', false === strpos( $m11['wrap']['button_url'], 'dealer' ) && 1 === preg_match( '~\?kmae_lead=[a-f0-9]{32}$~', $m11['wrap']['button_url'] ) );

/* --- Ismételt küldés (dupla kattintás / másik fül) ------------------------ */
reset_capture();
list( $ok, $data ) = send( 500, 100, 'sell' );
check( 'második küldés: sent 0, already 3, all_sent', true === $ok && 0 === $data['sent'] && 3 === $data['already'] && 3 === $data['total_matching'] && true === $data['all_sent'], json_encode( $data ) );
check( 'második küldés: barátságos üzenet', false !== strpos( $data['message'], 'már minden megfelelő cégnek' ) );
run_shutdown();
check( 'második küldés: 0 levél, 0 jelzés, sorok száma marad', array() === $MAILS && array() === $BUMPS && 3 === count( rows_for( 500 ) ) );
list( $ok, $data ) = preview( 500, 100, 'sell' );
check( 'előnézet küldés után: contacted 3, new 0, all_sent', true === $ok && 3 === $data['contacted'] && 0 === $data['new'] && true === $data['all_sent'] );

/* Két kérés egymás után, shutdown nélkül (dupla kattintás egy kérésen belül). */
reset_capture();
list( , $d1 ) = send( 512, 25, 'sell' );
list( , $d2 ) = send( 512, 25, 'sell' );
run_shutdown();
check( 'dupla kattintás: 2 + 0, egyszer kap levelet minden cég', 2 === $d1['sent'] && 0 === $d2['sent'] && true === $d2['all_sent'] && array( 'dealer11@example.com', 'dealer20@example.com' ) === mail_tos() && 1 === count( $BUMPS ), json_encode( array( $d1, $d2, mail_tos() ) ) );

/* --- Közben új cég regisztrál ---------------------------------------------- */
dealer( 21, 'csongrad-csanad', 'Szentes', true, false ); /* 45,2 */
reset_capture();
list( $ok, $data ) = preview( 500, 100, 'sell' );
check( 'új cég: előnézet new 1', true === $ok && 1 === $data['new'] && 3 === $data['contacted'] && false === $data['all_sent'] );
list( $ok, $data ) = send( 500, 100, 'sell' );
run_shutdown();
check( 'új cég: csak ő kap (sent 1, already 3)', true === $ok && 1 === $data['sent'] && 3 === $data['already'] && 4 === $data['total_matching'] && array( 'dealer21@example.com' ) === mail_tos() && 1 === count( $BUMPS ) && array( 21 ) === $BUMPS[0]['ids'], json_encode( array( $data, mail_tos(), $BUMPS ) ) );

/* Más cél ugyanarra a hirdetésre: a már megkeresett cég nem kap újra. */
reset_capture();
list( $ok, $data ) = send( 500, 100, 'tradein' );
run_shutdown();
check( 'tradein ugyanarra: 11, 20 már megvolt → 0 új', true === $ok && 0 === $data['sent'] && 2 === $data['total_matching'] && true === $data['all_sent'] && array() === $MAILS );

/* --- Beszámítás szöveg, ismeretlen távolság -------------------------------- */
reset_capture();
list( $ok, $data ) = send( 510, 25, 'tradein' );
run_shutdown();
$m = mail_to( 'dealer11@example.com' );
check( 'tradein levél: beszámíttatná', true === $ok && 2 === $data['sent'] && $m && false !== strpos( $m['body'], 'Egy magánszemély beszámíttatná az autóját' ) );
check( 'tradein levél: „Beszámításra ennyiért adná” (szóközös/pontos bevitel is)', $m && false !== strpos( $m['body'], 'Beszámításra ennyiért adná: 2 450 000 Ft' ) && false === strpos( $m['body'], 'Kereskedőnek ennyiért' ) );
/* Későbbi küldés más árral: a korábbi cégek megtartják a sajátjukat, az új batch az újat kapja. */
dealer( 22, 'csongrad-csanad', 'Szeged', true, true );
list( $ok, $data ) = send( 510, 25, 'tradein', '1 900.000' );
run_shutdown();
$pr = array();
foreach ( rows_for( 510 ) as $r ) { $pr[ (int) $r->dealer_id ] = (int) $r->dealer_price; }
check( 'második batch más árral: régi cégek 2 450 000, az új 1 900 000', true === $ok && 1 === $data['sent'] && 2450000 === $pr[11] && 2450000 === $pr[20] && 1900000 === $pr[22], json_encode( $pr ) );
check( 'list_for_seller: a csoport ára = a legutóbbi batch ára', 1900000 === ( static function () { foreach ( KMAE_Dealer_Leads::list_for_seller( SELLER ) as $g ) { if ( 510 === $g['listing_id'] ) { return $g['dealer_price']; } } return null; } )() );
check( 'row(): a sor dealer_price-a int', 1900000 === KMAE_Dealer_Leads::get_by_token( rows_for( 510 )[2]->token )['dealer_price'] );
dealer( 15, 'pest', 'Nincsilyenfalva', true, false ); /* érvénytelen telephely: csak „egész ország” */
reset_capture();
list( $ok, $data ) = send( 513, 0, 'sell' );
run_shutdown();
$m15 = mail_to( 'dealer15@example.com' );
check( 'egész ország: ismeretlen távolságnál nincs km-sor', true === $ok && $m15 && false === strpos( $m15['body'], 'km-re' ) && false !== strpos( $m15['body'], '7 napig' ), $m15 ? $m15['body'] : json_encode( $data ) );
check( 'egész ország: a tiltott cég (16) nem kap', null === mail_to( 'dealer16@example.com' ) && ! in_array( 16, $BUMPS[0]['ids'], true ) );

/* --- Nincs megfelelő cég ---------------------------------------------------- */
reset_capture();
list( $ok, $data ) = send( 511, 25, 'sell' );
run_shutdown();
check( 'nincs cég a körzetben: sent 0, all_sent false, tanács', true === $ok && 0 === $data['sent'] && 0 === $data['total_matching'] && false === $data['all_sent'] && false !== strpos( $data['message'], 'nagyobb távolsággal' ) && array() === $MAILS && array() === $BUMPS );

/* --- „both”: Eladnám ÉS Beszámíttatnám egyszerre ------------------------------ */
listing( 515, SELLER );
reset_capture();
list( $ok, $data ) = send( 515, 5, 'all' );
check( 'érvénytelen cél (all) → bad_purpose, nincs sor', false === $ok && 'dealer_leads_bad_purpose' === $data['code'] && array() === rows_for( 515 ) );
list( $ok, $data ) = send( 515, 0, 'sell,tradein' );
check( 'a „sell,tradein” szöveg NEM érvényes cél (a kliens both-t küld)', false === $ok && 'dealer_leads_bad_purpose' === $data['code'] && array() === rows_for( 515 ) );
list( $ok, $pv ) = preview( 515, 0, 'both' );
list( $ok2, $pvs ) = preview( 515, 0, 'sell' );
list( $ok3, $pvt ) = preview( 515, 0, 'tradein' );
check( 'előnézet both: VAGY = matching 7 (a sell 6 + a tradein 4, közös 11/20/22)', true === $ok && 7 === $pv['matching'] && 7 === $pv['new'] && 6 === $pvs['matching'] && 4 === $pvt['matching'], json_encode( array( $pv, $pvs, $pvt ) ) );
reset_capture();
list( $ok, $data ) = send( 515, 0, 'both' );
run_shutdown();
$pp = array();
foreach ( rows_for( 515 ) as $r ) { $pp[ (int) $r->dealer_id ] = $r->purpose; }
check( 'both küldés: 7 cég', true === $ok && 7 === $data['sent'] && 7 === $data['total_matching'] && 'Elküldtük 7 cégnek. 7 napig válaszolhatnak.' === $data['message'], json_encode( $data ) );
check( 'both küldés: soronkénti cél (metszet) tárolva', array( 11 => 'both', 12 => 'sell', 14 => 'tradein', 15 => 'sell', 20 => 'both', 21 => 'sell', 22 => 'both' ) === $pp, json_encode( $pp ) );
$mb = mail_to( 'dealer11@example.com' );
check( 'both levél (mindkét jelölős cég): eladná vagy beszámíttatná + Kereskedőnek ennyiért', $mb && false !== strpos( $mb['body'], 'Egy magánszemély eladná vagy beszámíttatná az autóját, és szeretné, ha ajánlatot tennétek rá.' ) && false !== strpos( $mb['body'], 'Kereskedőnek ennyiért adná: 2 450 000 Ft' ) && false === strpos( $mb['body'], 'Beszámításra ennyiért' ), $mb ? $mb['body'] : 'nincs levél' );
$m14 = mail_to( 'dealer14@example.com' );
$m15b = mail_to( 'dealer15@example.com' );
check( 'both küldés, csak beszámító cég: beszámítás szöveg', $m14 && false !== strpos( $m14['body'], 'Egy magánszemély beszámíttatná az autóját' ) && false !== strpos( $m14['body'], 'Beszámításra ennyiért adná:' ) );
check( 'both küldés, csak felvásárló cég: eladás szöveg', $m15b && false !== strpos( $m15b['body'], 'Egy magánszemély eladná az autóját' ) && false === strpos( $m15b['body'], 'vagy beszámíttatná' ) && false !== strpos( $m15b['body'], 'Kereskedőnek ennyiért adná:' ) );
check( 'both küldés: levél a 12-nek nincs (kikapcsolta), a tiltott 16 nem kap', null === mail_to( 'dealer12@example.com' ) && null === mail_to( 'dealer16@example.com' ) && 6 === count( $MAILS ) );
reset_capture();
list( $ok, $data ) = send( 515, 0, 'sell' );
run_shutdown();
check( 'soha kétszer: both után a sell/tradein/both mind 0 új', true === $ok && 0 === $data['sent'] && true === $data['all_sent'] && array() === $MAILS && 7 === count( rows_for( 515 ) ) );
list( $ok, $data ) = send( 515, 0, 'both' );
check( 'soha kétszer: ismételt both is 0 új', true === $ok && 0 === $data['sent'] && true === $data['all_sent'] );
check( 'az értesítés-sor nem tartalmaz mást, mint a sorokat (tárolt both a cég-oldali row()-ban)', 'both' === KMAE_Dealer_Leads::get_by_token( rows_for( 515 )[0]->token )['purpose'] );

/* --- Zár foglalt ------------------------------------------------------------ */
$LOCK_BUSY = true;
reset_capture();
listing( 514, SELLER );
list( $ok, $data ) = send( 514, 25, 'sell' );
run_shutdown();
check( 'zár foglalt → hiba, nincs sor / levél', false === $ok && 'dealer_leads_busy' === $data['code'] && array() === rows_for( 514 ) && array() === $MAILS );
$LOCK_BUSY = false;

/* --- client_config ---------------------------------------------------------- */
$cfg = KMAE_Dealer_Leads::client_config( 500 );
check( 'client_config', true === $cfg['available'] && 'nonce-kmae_dealer_leads' === $cfg['nonce'] && 'kmae_dealer_leads_send' === $cfg['actions']['send'] && 5 === count( $cfg['radii'] ) && 'Egész ország' === $cfg['radii'][4]['label'] && 2 === count( $cfg['purposes'] ) );
check( 'client_config: priceMin/priceMax', 50000 === $cfg['priceMin'] && 500000000 === $cfg['priceMax'] );
check( 'client_config: „Ajánlatot vár” hirdetésnél listingPrice null', array_key_exists( 'listingPrice', $cfg ) && null === $cfg['listingPrice'] );
$META[500]['_pub'] = 3200000;
check( 'client_config: nyilvános ár → listingPrice', 3200000 === KMAE_Dealer_Leads::client_config( 500 )['listingPrice'] );
$CURRENT = 2;
$cfg     = KMAE_Dealer_Leads::client_config( 500 );
check( 'client_config idegennek: nem elérhető + ok', false === $cfg['available'] && '' !== $cfg['reason'] );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
