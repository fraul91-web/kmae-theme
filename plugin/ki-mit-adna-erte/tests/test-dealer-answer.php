<?php
/**
 * Csoportos kereskedői megkeresés — 5. fázis: a cég válasza („Érdekel” → chat, „Köszönöm, nem”).
 *
 * WordPress és MySQL nélkül: a lead- és az üzenet-tábla SQL-jét a tests/test-dealer-leads-sqlite.py
 * futtatja (mint a test-dealer-send.php), a felhasználók / meta / hirdetések memóriában.
 * Rögzítve: wp_safe_redirect (kivétellel, az exit nem fut), KMAE_Conversations::create,
 * KMAE_Chat_Offers::post_system_message (+ a kmae_conversation_message_created esemény),
 * KMAE_Live_Signal::bump.
 *
 * Run: php tests/test-dealer-answer.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
$root = dirname( __DIR__ );
define( 'KMAE_PLUGIN_DIR', $root . '/' );
ini_set( 'error_log', '/dev/null' ); /* a log_failure() sorai */

if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null ) { $a = preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ); return implode( '', array_slice( $a, $start, $len ) ); }
}

/* --- WP stubok --------------------------------------------------------- */
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $text ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function absint( $v ) { return abs( (int) $v ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function home_url( $p = '/' ) { return 'https://albira.test' . $p; }
function admin_url( $p = '' ) { return 'https://albira.test/wp-admin/' . $p; }
function add_query_arg( $k, $v, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $k . '=' . $v; }

class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $v ) { return $v instanceof WP_Error; }

$OPTIONS     = array( 'kmae_dealer_leads_enabled' => 1 );
$IS_ADMIN    = false;
$CURRENT     = 0;
$META        = array();
$USER_META   = array();
$POSTS       = array();
$STATE       = array();
$UNVERIFIED  = array();
$CONVS       = array(); /* id => (object) */
$CREATE_FAIL = false;
$POST_FAIL   = false;
$CREATES     = 0;
$EVENTS      = array(); /* kmae_conversation_message_created */
$BUMPS       = array();
$HOOKS       = array();

function get_option( $key, $default = false ) { global $OPTIONS; return array_key_exists( $key, $OPTIONS ) ? $OPTIONS[ $key ] : $default; }
function current_user_can( $cap ) { global $IS_ADMIN; return 'manage_options' === $cap && $IS_ADMIN; }
function is_user_logged_in() { global $CURRENT; return $CURRENT > 0; }
function get_current_user_id() { global $CURRENT; return $CURRENT; }
function get_post_meta( $id, $key, $single = false ) { global $META; return $META[ $id ][ $key ] ?? ''; }
function get_user_meta( $id, $key, $single = false ) { global $USER_META; return $USER_META[ $id ][ $key ] ?? ''; }
function get_post( $id ) { global $POSTS; return $POSTS[ $id ] ?? null; }
function get_userdata( $id ) { return false; } /* cégnév nélkül nincs display_name-tartalék sem */
function add_action( $hook, $cb, $prio = 10, $args = 1 ) { global $HOOKS; $HOOKS[ $hook ][] = $cb; }
function wp_nonce_field( $action, $name, $referer = true, $echo = true ) { return '<input type="hidden" name="' . $name . '" value="nonce-' . $action . '">'; }
function wp_verify_nonce( $nonce, $action ) { return 'nonce-' . $action === $nonce ? 1 : false; }

class Redirect_Exit extends Exception {
	public $url;
	public function __construct( $url ) { parent::__construct( 'redirect' ); $this->url = $url; }
}
function wp_safe_redirect( $url ) { throw new Redirect_Exit( $url ); }

class KMAE_Database {
	public static function table( $name ) { return 'wp_kmae_' . $name; }
}
class KMAE_Auth {
	public static function login_url( $redirect = '' ) { return 'https://albira.test/belepes/' . ( $redirect ? '?redirect_to=' . rawurlencode( $redirect ) : '' ); }
	public static function should_gate_for_verification( $user_id ) { global $UNVERIFIED; return ! empty( $UNVERIFIED[ $user_id ] ); }
}
class KMAE_Pages {
	public static function url( $slug ) { return 'https://albira.test/' . $slug . '/'; }
	public static function conversation_thread_url( $id ) { return 'https://albira.test/uzenetek/?kmae_conversation=' . (int) $id; }
}
class KMAE_Live_Signal {
	public static function bump( $user_ids, $note = null ) { global $BUMPS; $BUMPS[] = array( 'ids' => (array) $user_ids, 'note' => $note ); }
}
/* A valódi create() logikája: (listing, buyer) egyedi, meglévő sort ad vissza. */
class KMAE_Conversations {
	const STATUS_OPEN   = 'open';
	const STATUS_CLOSED = 'closed';
	public static function create( $listing_id, $buyer_id, $seller_id ) {
		global $CONVS, $CREATE_FAIL, $CREATES;
		++$CREATES;
		if ( $CREATE_FAIL ) {
			return new WP_Error( 'conversation_create_failed', 'x' );
		}
		foreach ( $CONVS as $c ) {
			if ( $c->listing_id === (int) $listing_id && $c->buyer_id === (int) $buyer_id ) {
				return $c;
			}
		}
		$id           = count( $CONVS ) + 900;
		$CONVS[ $id ] = (object) array( 'id' => $id, 'listing_id' => (int) $listing_id, 'buyer_id' => (int) $buyer_id, 'seller_id' => (int) $seller_id, 'status' => 'open' );
		return $CONVS[ $id ];
	}
}
/* post_system_message: a valódi save_message idempotenciája (ugyanaz a kulcs → meglévő sor) + az esemény. */
class KMAE_Chat_Offers {
	const SYSTEM_KEY = 'system:';
	public static function listing_state( $listing_id ) { global $STATE; return $STATE[ $listing_id ] ?? ''; }
	public static function post_system_message( $conversation_id, $author_id, $text, $key ) {
		global $wpdb, $POST_FAIL, $EVENTS;
		if ( $POST_FAIL ) {
			return false;
		}
		$full = self::SYSTEM_KEY . $key;
		$row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_kmae_conversation_messages WHERE conversation_id = %d AND idempotency_key = %s', $conversation_id, $full ) );
		if ( ! $row ) {
			$wpdb->query( $wpdb->prepare( 'INSERT INTO wp_kmae_conversation_messages (conversation_id, sender_id, message_text, idempotency_key) VALUES (%d, %d, %s, %s)', $conversation_id, $author_id, $text, $full ) );
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_kmae_conversation_messages WHERE conversation_id = %d AND idempotency_key = %s', $conversation_id, $full ) );
		}
		$EVENTS[] = array( (int) $row->id, (int) $conversation_id, (int) $author_id );
		return true;
	}
}

/* --- sqlite wpdb (a test-dealer-send.php mintájára) ---------------------- */
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
	public $lock_busy     = false;
	private $pipes;
	private $proc;
	public function __construct() {
		$this->proc = proc_open( array( 'python3', '-I', __DIR__ . '/test-dealer-leads-sqlite.py' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ), $this->pipes );
	}
	public function prepare( $sql, ...$args ) {
		return fake_prepare( $sql, 1 === count( $args ) && is_array( $args[0] ) ? $args[0] : $args );
	}
	private function run( $sql ) {
		if ( preg_match( "/^SELECT (GET_LOCK|RELEASE_LOCK)\('([^']+)'/", $sql, $mm ) ) {
			$this->locks[] = $mm[1] . ':' . $mm[2];
			return array( 'cols' => array( 'x' ), 'rows' => array( array( 'GET_LOCK' === $mm[1] && $this->lock_busy ? '0' : '1' ) ), 'rowcount' => 1, 'lastrowid' => 0 );
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
$wpdb            = new Sqlite_WPDB();
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
$wpdb->query(
	'CREATE TABLE wp_kmae_conversation_messages (
		id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER NOT NULL, sender_id INTEGER NOT NULL,
		message_text TEXT NOT NULL, idempotency_key TEXT NOT NULL, UNIQUE (conversation_id, idempotency_key)
	)'
);

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

/** Egy admin-post kérés: a cél-URL. */
function post_answer( $token, $answer, $nonce = 'nonce-kmae_dealer_lead_answer' ) {
	global $CURRENT;
	$_POST = array( 'action' => 'kmae_dealer_lead_answer', 'token' => $token, 'answer' => $answer, '_wpnonce' => $nonce );
	try {
		if ( $CURRENT > 0 ) {
			KMAE_Dealer_Leads::handle_answer();
		} else {
			KMAE_Dealer_Leads::handle_answer_nopriv();
		}
	} catch ( Redirect_Exit $e ) {
		return $e->url;
	}
	return '(nincs átirányítás)';
}
function lead_row( $id ) { return KMAE_Dealer_Leads::get( $id ); }
function messages_in( $conv ) { global $wpdb; return $wpdb->get_results( 'SELECT * FROM wp_kmae_conversation_messages WHERE conversation_id = ' . (int) $conv ); }
function err( $code ) { return 'https://albira.test/ertesitesek/?kmae_error=' . $code; }
function thread( $id ) { return 'https://albira.test/uzenetek/?kmae_conversation=' . (int) $id; }
const DECLINED_URL = 'https://albira.test/ertesitesek/?kmae_success=lead_declined';

/* --- Adatok ------------------------------------------------------------- */
const SELLER = 1;
function listing( $id, $author = SELLER ) {
	global $POSTS, $META;
	$POSTS[ $id ] = (object) array( 'ID' => $id, 'post_author' => $author, 'post_type' => 'kmae_listing', 'post_status' => 'publish' );
	$META[ $id ]  = array( '_kmae_status' => 'active' );
}
foreach ( array( 600, 601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612 ) as $lid ) {
	listing( $lid );
}
$USER_META[11] = array( KMAE_Company::META_COMPANY_NAME => 'Autó Centrum Kft' );
$USER_META[12] = array( KMAE_Company::META_COMPANY_NAME => 'Beszámító Bt' );
$USER_META[13] = array();

/** Egy pending megkeresés a cégnek ($dealer) a hirdetéshez; a sor. */
function make_lead( $listing, $dealer, $purpose = 'sell' ) {
	$rows = KMAE_Dealer_Leads::insert_batch( $listing, SELLER, $purpose, array( $dealer => 10.0 ), 'Opel Astra ' . $listing );
	return $rows[0];
}

/* --- Űrlap-mezők ---------------------------------------------------------- */
$l    = make_lead( 600, 11 );
$html = KMAE_Dealer_Leads::answer_form_fields( $l['token'], false );
check( 'form: action + token + nonce', false !== strpos( $html, 'name="action" value="kmae_dealer_lead_answer"' ) && false !== strpos( $html, 'name="token" value="' . $l['token'] . '"' ) && false !== strpos( $html, 'name="_wpnonce" value="nonce-kmae_dealer_lead_answer"' ), $html );
ob_start();
KMAE_Dealer_Leads::answer_form_fields( $l['token'] );
check( 'form: alapból kiírja', ob_get_clean() === $html );
check( 'form: rossz token nem kerül ki', false !== strpos( KMAE_Dealer_Leads::answer_form_fields( '"><script>', false ), 'name="token" value=""' ) );
check( 'form action = admin-post.php', 'https://albira.test/wp-admin/admin-post.php' === KMAE_Dealer_Leads::answer_form_action() );
check( 'hookok: admin_post + nopriv', ( function () { global $HOOKS; KMAE_Dealer_Leads::init(); return isset( $HOOKS['admin_post_kmae_dealer_lead_answer'], $HOOKS['admin_post_nopriv_kmae_dealer_lead_answer'] ); } )() );

/* --- Kapu ---------------------------------------------------------------- */
$CURRENT = 0;
$url     = post_answer( $l['token'], 'interested' );
check( 'nincs belépve → belépés, vissza a megkereséshez', 'https://albira.test/belepes/?redirect_to=' . rawurlencode( 'https://albira.test/ertesitesek/?kmae_lead=' . $l['token'] ) === $url, $url );
check( 'nincs belépve, rossz token → belépés, vissza az Értesítésekre', 'https://albira.test/belepes/?redirect_to=' . rawurlencode( 'https://albira.test/ertesitesek/' ) === post_answer( 'xyz', 'interested' ) );
$CURRENT = 11;
check( 'rossz nonce → lead_forbidden', err( 'lead_forbidden' ) === post_answer( $l['token'], 'interested', 'rossz' ) );
check( 'ismeretlen token → lead_forbidden', err( 'lead_forbidden' ) === post_answer( str_repeat( 'a', 32 ), 'interested' ) );
check( 'rossz válasz-érték → lead_forbidden', err( 'lead_forbidden' ) === post_answer( $l['token'], 'talan' ) );
$CURRENT = 12;
check( 'más cég tokenje → lead_forbidden', err( 'lead_forbidden' ) === post_answer( $l['token'], 'interested' ) );
check( 'más cég „nem”-je sem megy', err( 'lead_forbidden' ) === post_answer( $l['token'], 'declined' ) && 'pending' === lead_row( $l['id'] )['status'] );
$CURRENT = SELLER;
check( 'az eladó sem válaszolhat', err( 'lead_forbidden' ) === post_answer( $l['token'], 'interested' ) );
$CURRENT = 11;
check( 'semmi nem jött létre a hibás kérésektől', 0 === $CREATES && array() === $EVENTS && 'pending' === lead_row( $l['id'] )['status'] );

/* --- Kapcsoló ki ---------------------------------------------------------- */
$OPTIONS = array();
check( 'kapcsoló ki → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $l['token'], 'interested' ) && 'pending' === lead_row( $l['id'] )['status'] );
$OPTIONS = array( 'kmae_dealer_leads_enabled' => 1 );

/* --- E-mail megerősítés nélkül -------------------------------------------- */
$UNVERIFIED[11] = true;
check( 'megerősítetlen e-mail → lead_unverified, sor marad', err( 'lead_unverified' ) === post_answer( $l['token'], 'interested' ) && 'pending' === lead_row( $l['id'] )['status'] && 0 === $CREATES );
$UNVERIFIED = array();

/* --- „Érdekel” ------------------------------------------------------------ */
$wpdb->locks = array();
$url         = post_answer( $l['token'], 'interested' );
$row         = lead_row( $l['id'] );
$conv        = $row['conversation_id'];
check( 'érdekel → chat létrejött, átirányítás a chatre', $conv > 0 && thread( $conv ) === $url, $url );
check( 'érdekel → a cég a vevő, az eladó az eladó', isset( $CONVS[ $conv ] ) && 11 === $CONVS[ $conv ]->buyer_id && SELLER === $CONVS[ $conv ]->seller_id && 600 === $CONVS[ $conv ]->listing_id );
check( 'érdekel → sor interested + responded_at + conversation_id', 'interested' === $row['status'] && null !== $row['responded_at'] );
$msgs = messages_in( $conv );
check( 'érdekel → egy rendszerüzenet a cég nevében, system:dealer_lead:<id> kulccsal', 1 === count( $msgs ) && 11 === (int) $msgs[0]->sender_id && 'system:dealer_lead:' . $l['id'] === $msgs[0]->idempotency_key, json_encode( $msgs ) );
check( 'érdekel → eladás szövege', 'Autó Centrum Kft kereskedés érdeklődik az autód iránt. Írjatok egymásnak itt a chatben.' === $msgs[0]->message_text, $msgs[0]->message_text );
check( 'érdekel → egy esemény (eladó: jelvény, levél, push)', 1 === count( $EVENTS ) && array( (int) $msgs[0]->id, $conv, 11 ) === $EVENTS[0] );
check( 'érdekel → megkeresésenkénti zár, elengedve', array( 'GET_LOCK:kmae_dealer_lead_' . $l['id'], 'RELEASE_LOCK:kmae_dealer_lead_' . $l['id'] ) === $wpdb->locks, json_encode( $wpdb->locks ) );
check( 'érdekel → nincs néma jelzés (az üzenet-csatorna jelez)', array() === $BUMPS );

/* Második kattintás */
$creates = $CREATES;
$url     = post_answer( $l['token'], 'interested' );
check( 'második „Érdekel” → ugyanaz a chat, nincs új üzenet / esemény / create', thread( $conv ) === $url && 1 === count( messages_in( $conv ) ) && 1 === count( $EVENTS ) && $creates === $CREATES, $url );
check( 'érdekel után „nem” → a chatre visz (nem hiba), marad interested', thread( $conv ) === post_answer( $l['token'], 'declined' ) && 'interested' === lead_row( $l['id'] )['status'] );

/* --- Beszámítás szövege + a cégnek már volt chatje erről a hirdetésről --- */
$CURRENT  = 12;
$existing = KMAE_Conversations::create( 601, 12, SELLER );
$creates  = $CREATES;
$t        = make_lead( 601, 12, 'tradein' );
$url      = post_answer( $t['token'], 'interested' );
check( 'meglévő chat → újrahasznosítva', thread( $existing->id ) === $url && $existing->id === lead_row( $t['id'] )['conversation_id'] && 2 === count( $CONVS ), $url );
$msgs = messages_in( $existing->id );
check( 'beszámítás szövege', 1 === count( $msgs ) && 'Beszámító Bt kereskedés érdeklődik az autód iránt, beszámításra. Írjatok egymásnak itt a chatben.' === $msgs[0]->message_text, json_encode( $msgs ) );

/* --- „both”: eladás vagy beszámítás szövege -------------------------------- */
$CURRENT = 11;
$bth     = make_lead( 612, 11, 'both' );
check( 'both: a sor purpose-a both', 'both' === lead_row( $bth['id'] )['purpose'] );
$url  = post_answer( $bth['token'], 'interested' );
$cb   = lead_row( $bth['id'] )['conversation_id'];
$msgs = messages_in( $cb );
check( 'both → Érdekel: chatre visz, egy üzenet', $cb > 0 && thread( $cb ) === $url && 1 === count( $msgs ) );
check( 'both → rendszerüzenet szövege (eladás vagy beszámítás)', 'Autó Centrum Kft kereskedés érdeklődik az autód iránt (eladás vagy beszámítás). Írjatok egymásnak itt a chatben.' === $msgs[0]->message_text, $msgs[0]->message_text );
check( 'both → interest_text közvetlenül is', false !== strpos( KMAE_Dealer_Leads::interest_text( lead_row( $bth['id'] ) ), '(eladás vagy beszámítás)' ) && false === strpos( KMAE_Dealer_Leads::interest_text( lead_row( $bth['id'] ) ), 'beszámításra.' ) );

/* --- „Köszönöm, nem” ------------------------------------------------------ */
$CURRENT = 11;
$d       = make_lead( 602, 11 );
$events  = count( $EVENTS );
$creates = $CREATES;
$BUMPS   = array();
$url     = post_answer( $d['token'], 'declined' );
$row     = lead_row( $d['id'] );
check( 'nem → Értesítések + lead_declined', DECLINED_URL === $url, $url );
check( 'nem → declined, nincs chat / üzenet', 'declined' === $row['status'] && null === $row['conversation_id'] && null !== $row['responded_at'] && $creates === $CREATES && $events === count( $EVENTS ) );
check( 'nem → néma élő jelzés az eladónak (note nélkül = nincs push)', 1 === count( $BUMPS ) && array( SELLER ) === $BUMPS[0]['ids'] && null === $BUMPS[0]['note'], json_encode( $BUMPS ) );
check( 'ismételt „nem” → ugyanaz a siker', DECLINED_URL === post_answer( $d['token'], 'declined' ) && 1 === count( $BUMPS ) );
check( 'nem után „Érdekel” → lead_answered, nincs chat', err( 'lead_answered' ) === post_answer( $d['token'], 'interested' ) && 'declined' === lead_row( $d['id'] )['status'] && $creates === $CREATES );

/* --- Lejárt ---------------------------------------------------------------- */
$e = make_lead( 603, 11 );
$wpdb->query( "UPDATE wp_kmae_dealer_leads SET expires_at = '2020-01-01 00:00:00' WHERE id = " . $e['id'] );
check( 'lejárt (cron előtt, még pending) → lead_expired', err( 'lead_expired' ) === post_answer( $e['token'], 'interested' ) && 'pending' === lead_row( $e['id'] )['status'] );
check( 'lejárt → „nem” sem megy', err( 'lead_expired' ) === post_answer( $e['token'], 'declined' ) );
KMAE_Dealer_Leads::expire_due();
check( 'lejárt (expired állapot) → lead_expired', err( 'lead_expired' ) === post_answer( $e['token'], 'interested' ) );

/* --- Visszavont (hirdetés törölve / lezárva) ------------------------------- */
$c = make_lead( 604, 11 );
KMAE_Dealer_Leads::cancel_for_listing( 604 );
check( 'cancelled → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $c['token'], 'interested' ) );

/* --- Nem elérhető hirdetés ------------------------------------------------- */
$creates = $CREATES;
$r       = make_lead( 605, 11 );
$STATE[605] = 'reserved';
check( 'Foglalt hirdetés → lead_unavailable, sor marad pending', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) && 'pending' === lead_row( $r['id'] )['status'] );
$STATE[605] = 'sold';
check( 'eladott → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) );
$STATE[605] = 'reopened';
$META[605]['_kmae_status'] = 'closed';
check( 'nem aktív (lezárt) → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) );
$META[605]['_kmae_status'] = 'active';
$POSTS[605]->post_status   = 'draft';
check( 'nem közzétett → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) );
unset( $POSTS[605] );
check( 'törölt hirdetés → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) );
check( 'törölt hirdetés → „nem” még megy', DECLINED_URL === post_answer( $r['token'], 'declined' ) );
check( 'nem elérhető hirdetésnél nem jött létre chat', $creates === $CREATES );

/* --- Tiltás mindkét irányban ----------------------------------------------- */
$b = make_lead( 606, 11 );
$USER_META[ SELLER ][ KMAE_User_Blocks::META ] = array( 11 => '2026-10-01 10:00:00' );
check( 'az eladó tiltja a céget → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $b['token'], 'interested' ) && 'pending' === lead_row( $b['id'] )['status'] );
unset( $USER_META[ SELLER ][ KMAE_User_Blocks::META ] );
$USER_META[11][ KMAE_User_Blocks::META ] = array( SELLER => '2026-10-01 10:00:00' );
check( 'a cég tiltja az eladót → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $b['token'], 'interested' ) );
unset( $USER_META[11][ KMAE_User_Blocks::META ] );
check( 'tiltás nélkül megy', 0 === strpos( post_answer( $b['token'], 'interested' ), 'https://albira.test/uzenetek/?kmae_conversation=' ) );

/* --- Chat-létrehozás hibája → lead_failed + visszaállás pendingre ---------- */
$f           = make_lead( 607, 11 );
$CREATE_FAIL = true;
$events      = count( $EVENTS );
check( 'create() WP_Error → lead_failed', err( 'lead_failed' ) === post_answer( $f['token'], 'interested' ) );
$row = lead_row( $f['id'] );
check( 'create() hiba → a sor vissza pending-re (responded_at, conversation_id üres)', 'pending' === $row['status'] && null === $row['responded_at'] && null === $row['conversation_id'] && $events === count( $EVENTS ) );
$CREATE_FAIL = false;
$url         = post_answer( $f['token'], 'interested' );
check( 'hiba után újrapróbálva → chat + egy üzenet', 0 === strpos( $url, 'https://albira.test/uzenetek/?kmae_conversation=' ) && 'interested' === lead_row( $f['id'] )['status'] && 1 === count( messages_in( lead_row( $f['id'] )['conversation_id'] ) ) && $events + 1 === count( $EVENTS ), $url );

/* Rendszerüzenet hibája → lead_failed + pending; a (üres) chat-sor marad, újrapróbáláskor újrahasznosítva. */
$g         = make_lead( 608, 11 );
$POST_FAIL = true;
check( 'rendszerüzenet hibája → lead_failed + pending', err( 'lead_failed' ) === post_answer( $g['token'], 'interested' ) && 'pending' === lead_row( $g['id'] )['status'] && null === lead_row( $g['id'] )['conversation_id'] );
$POST_FAIL = false;
$n_convs   = count( $CONVS );
$url       = post_answer( $g['token'], 'interested' );
check( 'újrapróbálva → ugyanaz a chat-sor, egy üzenet', $n_convs === count( $CONVS ) && 1 === count( messages_in( lead_row( $g['id'] )['conversation_id'] ) ), $url );

/* Félbeszakadt kérés (interested, de nincs beírva chat) → befejezzük, nincs dupla üzenet. */
$h = make_lead( 609, 11 );
KMAE_Dealer_Leads::mark_interested( $h['id'] );
$events = count( $EVENTS );
$url    = post_answer( $h['token'], 'interested' );
$hc     = lead_row( $h['id'] )['conversation_id'];
check( 'félbeszakadt „Érdekel” → befejezve: chat + üzenet + conversation_id', $hc > 0 && thread( $hc ) === $url && 1 === count( messages_in( $hc ) ) && $events + 1 === count( $EVENTS ), $url );
$wpdb->query( 'UPDATE wp_kmae_dealer_leads SET conversation_id = NULL WHERE id = ' . $h['id'] );
$url = post_answer( $h['token'], 'interested' );
check( 'üzenet megvan, csak a conversation_id hiányzott → nincs második üzenet / esemény', thread( $hc ) === $url && 1 === count( messages_in( $hc ) ) && $events + 1 === count( $EVENTS ) && $hc === lead_row( $h['id'] )['conversation_id'] );

/* Félbeszakadt kérés: a befejezés előtt futnak az „Érdekel” ellenőrzések (e-mail kapu, hirdetés elérhető); hiba = átirányítás, VISSZAÁLLÍTÁS nélkül. */
$r = make_lead( 610, 11 );
KMAE_Dealer_Leads::mark_interested( $r['id'] );
$creates = $CREATES;
$events  = count( $EVENTS );
$UNVERIFIED[11] = true;
check( 'félbeszakadt + e-mail nincs megerősítve → lead_unverified', err( 'lead_unverified' ) === post_answer( $r['token'], 'interested' ) );
check( 'félbeszakadt + e-mail kapu: sor interested marad, nincs create / üzenet', 'interested' === lead_row( $r['id'] )['status'] && null === lead_row( $r['id'] )['conversation_id'] && $creates === $CREATES && $events === count( $EVENTS ) );
$UNVERIFIED = array();
$META[610]['_kmae_status'] = 'closed';
check( 'félbeszakadt + a hirdetés már nem aktív → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) );
check( 'félbeszakadt + nem elérhető: sor interested marad (nincs rollback), nincs create / üzenet', 'interested' === lead_row( $r['id'] )['status'] && null === lead_row( $r['id'] )['conversation_id'] && $creates === $CREATES && $events === count( $EVENTS ) );
check( 'félbeszakadt + nem elérhető: „Köszönöm, nem” gombnál is ugyanaz az ellenőrzés', err( 'lead_unavailable' ) === post_answer( $r['token'], 'declined' ) && 'interested' === lead_row( $r['id'] )['status'] );
$META[610]['_kmae_status'] = 'active';
$STATE[610]                = 'reserved';
check( 'félbeszakadt + Foglalt → lead_unavailable', err( 'lead_unavailable' ) === post_answer( $r['token'], 'interested' ) && 'interested' === lead_row( $r['id'] )['status'] );
unset( $STATE[610] );
$url = post_answer( $r['token'], 'interested' );
$rc  = lead_row( $r['id'] )['conversation_id'];
check( 'félbeszakadt + minden rendben → befejezi (chat + egy üzenet)', $rc > 0 && thread( $rc ) === $url && 1 === count( messages_in( $rc ) ), $url );

/* Már létező, ZÁRT chat: a save_message nem ír bele → nem lehet lead_failed-hurok; a sor interested + a zárt chat azonosítója, oda irányít. */
$CONVS[777] = (object) array( 'id' => 777, 'listing_id' => 611, 'buyer_id' => 11, 'seller_id' => SELLER, 'status' => 'closed' );
$c          = make_lead( 611, 11 );
$events     = count( $EVENTS );
$url        = post_answer( $c['token'], 'interested' );
$row        = lead_row( $c['id'] );
check( 'zárt meglévő chat → átirányít a (zárt) chatre, nem lead_failed', thread( 777 ) === $url, $url );
check( 'zárt meglévő chat → a sor interested + conversation_id beírva (nincs rollback pending-re)', 'interested' === $row['status'] && 777 === $row['conversation_id'] && null !== $row['responded_at'] );
check( 'zárt meglévő chat → nincs rendszerüzenet / esemény', array() === messages_in( 777 ) && $events === count( $EVENTS ) );
$creates = $CREATES;
check( 'zárt meglévő chat → második kattintás: ugyanaz a chat, nincs újabb create()', thread( 777 ) === post_answer( $c['token'], 'interested' ) && $creates === $CREATES );

/* --- Foglalt zár → lead_failed, semmi nem változik ------------------------- */
$wpdb->lock_busy = true;
$z               = make_lead( 600, 13 );
$CURRENT         = 13;
check( 'zár foglalt → lead_failed, sor pending', err( 'lead_failed' ) === post_answer( $z['token'], 'interested' ) && 'pending' === lead_row( $z['id'] )['status'] );
$wpdb->lock_busy = false;
check( 'cégnév nélkül: „Egy kereskedés …”', 'Egy kereskedés érdeklődik az autód iránt. Írjatok egymásnak itt a chatben.' === KMAE_Dealer_Leads::interest_text( lead_row( $z['id'] ) ) );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
