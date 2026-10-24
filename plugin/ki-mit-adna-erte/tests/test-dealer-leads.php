<?php
/**
 * Csoportos kereskedői megkeresés — adatréteg (KMAE_Dealer_Leads) teszt.
 *
 * WordPress és MySQL nélkül: az SQL-t a tests/test-dealer-leads-sqlite.py futtatja
 * (háttérfolyamat, soronként JSON). MySQL → sqlite fordítás a stubban: csak
 * `INSERT IGNORE` → `INSERT OR IGNORE` (a sqlite-ban nincs INSERT IGNORE).
 * A séma a create_table() DDL-jének sqlite-megfelelője (ugyanazok a UNIQUE kulcsok).
 *
 * Run: php tests/test-dealer-leads.php
 */

/* ABSPATH = ideiglenes mappa, benne egy dbDelta-stub (a séma-teszthez: create_table() `require_once ABSPATH . wp-admin/includes/upgrade.php`). */
$test_abspath = sys_get_temp_dir() . '/kmae-leads-abspath-' . getmypid() . '/';
@mkdir( $test_abspath . 'wp-admin/includes', 0777, true );
file_put_contents( $test_abspath . 'wp-admin/includes/upgrade.php', '<?php function dbDelta( $sql ) { $GLOBALS["test_dbdelta"][] = $sql; return array(); }' );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $test_abspath );
}
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

$root = dirname( __DIR__ );

/* ------------------------------------------------------------------
   WP stubok
   ------------------------------------------------------------------ */

$GLOBALS['test_options'] = array();
$GLOBALS['test_is_admin'] = false;
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['test_options'] ) ? $GLOBALS['test_options'][ $key ] : $default;
}
function current_user_can( $cap ) {
	return 'manage_options' === $cap && $GLOBALS['test_is_admin'];
}
function update_option( $key, $value ) {
	$GLOBALS['test_options'][ $key ] = $value;
	return true;
}
$GLOBALS['test_post_types'] = array();
function get_post_type( $id ) {
	return $GLOBALS['test_post_types'][ (int) $id ] ?? 'kmae_listing';
}
$GLOBALS['test_transients'] = array();
function get_transient( $key ) {
	return $GLOBALS['test_transients'][ $key ] ?? false;
}
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['test_transients'][ $key ] = $value;
	return true;
}
$GLOBALS['test_bumps'] = array();
class KMAE_Live_Signal {
	public static function bump( $user_ids, $note = null ) {
		$GLOBALS['test_bumps'][] = array( 'ids' => array_values( (array) $user_ids ), 'note' => $note );
	}
}

class KMAE_Database {
	public static function table( $name ) {
		return 'wp_kmae_' . $name;
	}
}

/* wpdb::prepare utánzat (%d, %f → %F, %s → idézett). */
function fake_prepare( $sql, array $args ) {
	$i = 0;
	$out = preg_replace_callback(
		'/%[dsf]/',
		static function ( $m ) use ( &$i, $args ) {
			if ( ! array_key_exists( $i, $args ) ) {
				throw new RuntimeException( 'prepare: kevés argumentum' );
			}
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
	public $insert_id     = 0;
	public $queries       = array();
	private $proc;
	private $pipes;
	public function __construct() {
		$py         = __DIR__ . '/test-dealer-leads-sqlite.py';
		$this->proc = proc_open( array( 'python3', '-I', $py ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ), $this->pipes );
	}
	public function prepare( $sql, ...$args ) {
		return fake_prepare( $sql, 1 === count( $args ) && is_array( $args[0] ) ? $args[0] : $args );
	}
	private function run( $sql ) {
		$this->queries[] = $sql;
		/* Egyetlen MySQL-specifikus elem a kódban. */
		$sql = preg_replace( '/^INSERT IGNORE\b/', 'INSERT OR IGNORE', $sql );
		fwrite( $this->pipes[0], json_encode( array( 'sql' => $sql ) ) . "\n" );
		fflush( $this->pipes[0] );
		$res = json_decode( (string) fgets( $this->pipes[1] ), true );
		if ( ! is_array( $res ) || isset( $res['error'] ) ) {
			throw new RuntimeException( 'SQL hiba: ' . ( $res['error'] ?? 'nincs válasz' ) . ' — ' . $sql );
		}
		return $res;
	}
	public function query( $sql ) {
		$res                 = $this->run( $sql );
		$this->rows_affected = max( 0, (int) $res['rowcount'] );
		$this->insert_id     = (int) $res['lastrowid'];
		return $this->rows_affected;
	}
	public function get_results( $sql, $output = 'OBJECT' ) {
		$res = $this->run( $sql );
		return array_map(
			static function ( $row ) use ( $res ) {
				return (object) array_combine( $res['cols'], $row );
			},
			$res['rows']
		);
	}
	public function get_row( $sql ) {
		$rows = $this->get_results( $sql );
		return $rows ? $rows[0] : null;
	}
	/* $wpdb->delete( tábla, [oszlop => érték], [%d] ) — csak egész feltételek kellenek. */
	public function delete( $table, $where, $format = null ) {
		$parts = array();
		foreach ( $where as $col => $val ) {
			$parts[] = $col . ' = ' . (int) $val;
		}
		return $this->query( 'DELETE FROM ' . $table . ' WHERE ' . implode( ' AND ', $parts ) );
	}
	public function get_col( $sql ) {
		$res = $this->run( $sql );
		return array_map(
			static function ( $row ) {
				return $row[0];
			},
			$res['rows']
		);
	}
	public function get_var( $sql ) {
		$res = $this->run( $sql );
		return $res['rows'] ? $res['rows'][0][0] : null;
	}
	public function close() {
		fclose( $this->pipes[0] );
		proc_close( $this->proc );
	}
}

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

require $root . '/includes/class-kmae-dealer-leads.php';

$wpdb = new Sqlite_WPDB();
$GLOBALS['wpdb'] = $wpdb;

/* A create_table() DDL-jének sqlite-megfelelője. */
$wpdb->query(
	"CREATE TABLE wp_kmae_dealer_leads (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		listing_id INTEGER NOT NULL,
		seller_id INTEGER NOT NULL,
		dealer_id INTEGER NOT NULL,
		purpose TEXT NOT NULL DEFAULT 'sell',
		status TEXT NOT NULL DEFAULT 'pending',
		token TEXT NOT NULL,
		batch_id TEXT NOT NULL DEFAULT '',
		distance_km REAL DEFAULT NULL,
		dealer_price INTEGER DEFAULT NULL,
		conversation_id INTEGER DEFAULT NULL,
		listing_title TEXT NOT NULL DEFAULT '',
		created_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00',
		expires_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00',
		responded_at TEXT DEFAULT NULL,
		updated_at TEXT NOT NULL DEFAULT '1970-01-01 00:00:00',
		UNIQUE (listing_id, dealer_id),
		UNIQUE (token)
	)"
);

$ids_of = static function ( array $rows, $key = 'dealer_id' ) {
	$out = array_map( static function ( $r ) use ( $key ) { return $r[ $key ]; }, $rows );
	sort( $out );
	return $out;
};

$GLOBALS['test_options'][ KMAE_Dealer_Leads::SCHEMA_OPTION ] = KMAE_Dealer_Leads::SCHEMA_VERSION; /* a tábla kész (ready()) */

/* --- Kapcsoló ---------------------------------------------------- */
check( 'switch: off, not admin → false', false === KMAE_Dealer_Leads::is_enabled() );
$GLOBALS['test_is_admin'] = true;
check( 'switch: off, admin → true', true === KMAE_Dealer_Leads::is_enabled() );
$GLOBALS['test_is_admin'] = false;
$GLOBALS['test_options'][ KMAE_Dealer_Leads::OPTION ] = 1;
check( 'switch: on, not admin → true', true === KMAE_Dealer_Leads::is_enabled() );
check( 'RADII', array( 25, 50, 100, 200, 0 ) === KMAE_Dealer_Leads::RADII );

/* --- insert_batch ------------------------------------------------ */
$L1 = 101;
$L2 = 102;
$S  = 7;
$b1 = KMAE_Dealer_Leads::insert_batch( $L1, $S, 'sell', array( 11 => 12.34, 12 => 48.0, 13 => null ), 'Opel Astra' );
check( 'batch1: 3 new rows', 3 === count( $b1 ), (string) count( $b1 ) );
check( 'batch1: dealers 11,12,13', array( 11, 12, 13 ) === $ids_of( $b1 ) );
check( 'batch1: one batch_id', 1 === count( array_unique( $ids_of( $b1, 'batch_id' ) ) ) && KMAE_Dealer_Leads::is_token( $b1[0]['batch_id'] ) );
check( 'batch1: status pending', array( 'pending' ) === array_values( array_unique( $ids_of( $b1, 'status' ) ) ) );
check( 'batch1: title snapshot', 'Opel Astra' === $b1[0]['listing_title'] );
check( 'batch1: distance rounded', 12.3 === $b1[0]['distance_km'] && null === $b1[2]['distance_km'] );
check( 'batch1: purpose', 'sell' === $b1[0]['purpose'] );
check( 'batch1: conversation null', null === $b1[0]['conversation_id'] && null === $b1[0]['responded_at'] );
$ttl = strtotime( $b1[0]['expires_at'] . ' UTC' ) - strtotime( $b1[0]['created_at'] . ' UTC' );
check( 'batch1: expires = created + 7 days', 7 * 86400 === $ttl, (string) $ttl );

$tokens = $ids_of( $b1, 'token' );
check( 'tokens: 32 hex', 3 === count( array_filter( $tokens, array( 'KMAE_Dealer_Leads', 'is_token' ) ) ) );
check( 'tokens: unique', 3 === count( array_unique( $tokens ) ) );
check( 'tokens: not batch_id', ! in_array( $b1[0]['batch_id'], $tokens, true ) );
$many = array();
for ( $i = 0; $i < 2000; $i++ ) {
	$many[ KMAE_Dealer_Leads::new_token() ] = 1;
}
check( 'new_token: 2000 distinct', 2000 === count( $many ) );
check( 'is_token rejects bad', ! KMAE_Dealer_Leads::is_token( 'ABC' ) && ! KMAE_Dealer_Leads::is_token( str_repeat( 'g', 32 ) ) && ! KMAE_Dealer_Leads::is_token( 123 ) );

/* Átfedő második küldés: csak az új cégek kerülnek be (bármilyen állapotú régi kimarad). */
$b2 = KMAE_Dealer_Leads::insert_batch( $L1, $S, 'tradein', array( 12 => 50, 13 => 1, 14 => 80, 15 => 199.96 ), 'Opel Astra' );
check( 'batch2: only new dealers 14,15', array( 14, 15 ) === $ids_of( $b2 ), json_encode( $ids_of( $b2 ) ) );
check( 'batch2: new batch_id', $b2 && $b2[0]['batch_id'] !== $b1[0]['batch_id'] );
check( 'batch2: old row untouched (purpose sell)', 'sell' === KMAE_Dealer_Leads::get( $b1[1]['id'] )['purpose'] );
check( 'contacted_dealer_ids L1', array( 11, 12, 13, 14, 15 ) === KMAE_Dealer_Leads::contacted_dealer_ids( $L1 ) );

$b3 = KMAE_Dealer_Leads::insert_batch( $L1, $S, 'sell', array( 11 => 1, 12 => 2 ) );
check( 'batch3: all already contacted → empty', array() === $b3 );

/* Ugyanaz a cég, másik hirdetés: szabad. Az eladó saját magát nem keresheti meg. */
$b4 = KMAE_Dealer_Leads::insert_batch( $L2, $S, 'sell', array( 11 => 5, $S => 0, 0 => 3, -4 => 1 ), 'BMW 320d' );
check( 'other listing: same dealer allowed', array( 11 ) === $ids_of( $b4 ) );
check( 'contacted_dealer_ids L2', array( 11 ) === KMAE_Dealer_Leads::contacted_dealer_ids( $L2 ) );

/* Soronkénti cél: a $purpose tömb (dealer_id => cél) — a tényleges (metszet) cél soronként; both is tárolható (varchar(16)). */
$L3 = 970;
$b5 = KMAE_Dealer_Leads::insert_batch( $L3, 77, array( 31 => 'both', 32 => 'sell', 33 => 'tradein', 34 => 'gift', 35 => 'both' ), array( 31 => 1, 32 => 2, 33 => 3, 34 => 4 ), 'Audi A4', 1500000 );
$pm = array();
foreach ( $b5 as $r ) { $pm[ $r['dealer_id'] ] = $r['purpose']; }
ksort( $pm );
check( 'batch per-row purpose: mind a három érték tárolva, érvénytelen/hiányzó cél kimarad', array( 31 => 'both', 32 => 'sell', 33 => 'tradein' ) === $pm, json_encode( $pm ) );
check( 'batch per-row purpose: DB-ből visszaolvasva is both', 'both' === KMAE_Dealer_Leads::get( $b5[0]['id'] )['purpose'] && 1500000 === $b5[0]['dealer_price'] );
check( 'batch per-row purpose: a 35 (nincs távolság-sor) és 34 (érvénytelen cél) nincs a contacted-ben', array( 31, 32, 33 ) === KMAE_Dealer_Leads::contacted_dealer_ids( $L3 ) );
$b6 = KMAE_Dealer_Leads::insert_batch( 971, 77, 'both', array( 41 => 1, 42 => 2 ), 'Audi A6' );
check( 'batch string both: minden sor both', 2 === count( $b6 ) && array( 'both' ) === array_values( array_unique( array_column( $b6, 'purpose' ) ) ) );
check( 'batch per-row purpose: üres térkép → üres', array() === KMAE_Dealer_Leads::insert_batch( 972, 77, array(), array( 51 => 1 ) ) );
check( 'purpose oszlop varchar(16): a both belefér', strlen( 'both' ) <= 16 && false !== strpos( file_get_contents( __DIR__ . '/../includes/class-kmae-dealer-leads.php' ), "purpose varchar(16)" ) );

/* Érvénytelen bemenet. */
check( 'invalid purpose → empty', array() === KMAE_Dealer_Leads::insert_batch( $L1, $S, 'gift', array( 99 => 1 ) ) );
check( 'invalid listing → empty', array() === KMAE_Dealer_Leads::insert_batch( 0, $S, 'sell', array( 99 => 1 ) ) );
check( 'no dealers → empty', array() === KMAE_Dealer_Leads::insert_batch( $L1, $S, 'sell', array() ) );
check( 'contacted unchanged after invalid', array( 11, 12, 13, 14, 15 ) === KMAE_Dealer_Leads::contacted_dealer_ids( $L1 ) );

/* --- get / get_by_token ------------------------------------------ */
$lead = KMAE_Dealer_Leads::get_by_token( $b1[0]['token'] );
check( 'get_by_token', $lead && $lead['id'] === $b1[0]['id'] && 11 === $lead['dealer_id'] );
check( 'get_by_token bad format → null', null === KMAE_Dealer_Leads::get_by_token( "x' OR 1=1 --" ) );
check( 'get_by_token unknown → null', null === KMAE_Dealer_Leads::get_by_token( str_repeat( 'a', 32 ) ) );
check( 'get unknown → null', null === KMAE_Dealer_Leads::get( 999999 ) );

/* --- átmenetek ---------------------------------------------------- */
$id11 = $b1[0]['id'];
$id12 = $b1[1]['id'];
$id13 = $b1[2]['id'];
$id14 = $b2[0]['id'];
$id15 = $b2[1]['id'];
check( 'mark_interested first → true', true === KMAE_Dealer_Leads::mark_interested( $id11, 555 ) );
check( 'mark_interested second → false', false === KMAE_Dealer_Leads::mark_interested( $id11, 556 ) );
check( 'mark_declined after interested → false', false === KMAE_Dealer_Leads::mark_declined( $id11 ) );
$r = KMAE_Dealer_Leads::get( $id11 );
check( 'interested row: conv 555 + responded_at', 'interested' === $r['status'] && 555 === $r['conversation_id'] && null !== $r['responded_at'] );
check( 'set_conversation when already set → false', false === KMAE_Dealer_Leads::set_conversation( $id11, 777 ) );

check( 'mark_declined → true', true === KMAE_Dealer_Leads::mark_declined( $id12 ) );
check( 'mark_declined twice → false', false === KMAE_Dealer_Leads::mark_declined( $id12 ) );
check( 'mark_interested after declined → false', false === KMAE_Dealer_Leads::mark_interested( $id12, 1 ) );
check( 'declined row', 'declined' === KMAE_Dealer_Leads::get( $id12 )['status'] && null === KMAE_Dealer_Leads::get( $id12 )['conversation_id'] );

/* „Lefoglalás”, majd chat-azonosító utólag. */
check( 'mark_interested without conv → true', true === KMAE_Dealer_Leads::mark_interested( $id13 ) );
check( 'set_conversation → true', true === KMAE_Dealer_Leads::set_conversation( $id13, 888 ) );
check( 'set_conversation twice → false', false === KMAE_Dealer_Leads::set_conversation( $id13, 889 ) );
check( 'conv 888 kept', 888 === KMAE_Dealer_Leads::get( $id13 )['conversation_id'] );
check( 'set_conversation on pending → false', false === KMAE_Dealer_Leads::set_conversation( $id14, 1 ) );

/* --- lejárat ------------------------------------------------------ */
check( 'expire_due now → nothing', array() === KMAE_Dealer_Leads::expire_due() );
check( 'count_pending_for_dealer 14', 1 === KMAE_Dealer_Leads::count_pending_for_dealer( 14 ) );
/* A 14-es sort „8 napja” küldöttre állítjuk. */
$past = gmdate( 'Y-m-d H:i:s', time() - 86400 );
$wpdb->query( $wpdb->prepare( 'UPDATE wp_kmae_dealer_leads SET expires_at = %s WHERE id = %d', $past, $id14 ) );
check( 'answer after expiry (before cron) → false', false === KMAE_Dealer_Leads::mark_interested( $id14, 9 ) );
check( 'count_pending ignores overdue', 0 === KMAE_Dealer_Leads::count_pending_for_dealer( 14 ) );
check( 'expire_due → [14]', array( $id14 ) === KMAE_Dealer_Leads::expire_due() );
check( 'expire_due again → nothing', array() === KMAE_Dealer_Leads::expire_due() );
check( 'expired row', 'expired' === KMAE_Dealer_Leads::get( $id14 )['status'] && null === KMAE_Dealer_Leads::get( $id14 )['responded_at'] );
/* expire_due a jövőbeli $now-val: minden pending lejár (L1: 15, L2: 11). */
$future = gmdate( 'Y-m-d H:i:s', time() + 8 * 86400 );
$exp    = KMAE_Dealer_Leads::expire_due( $future, 1 );
check( 'expire_due limit 1', 1 === count( $exp ) && $exp[0] === $id15 );
/* Visszaállítjuk a 15-öt pendingre a lemondás teszthez. */
$wpdb->query( $wpdb->prepare( "UPDATE wp_kmae_dealer_leads SET status = 'pending' WHERE id = %d", $id15 ) );

/* --- lemondás ------------------------------------------------------ */
check( 'cancel_for_listing L1 → [15] only', array( $id15 ) === KMAE_Dealer_Leads::cancel_for_listing( $L1 ) );
check( 'cancel_for_listing again → nothing', array() === KMAE_Dealer_Leads::cancel_for_listing( $L1 ) );
check( 'cancelled → cannot answer', false === KMAE_Dealer_Leads::mark_declined( $id15 ) );
check( 'L2 untouched by L1 cancel', 'pending' === KMAE_Dealer_Leads::get( $b4[0]['id'] )['status'] );
check( 'cancelled dealer still blocked', array() === KMAE_Dealer_Leads::insert_batch( $L1, $S, 'sell', array( 15 => 3 ) ) );

/* --- számlálók / listák ---------------------------------------------- */
$c = KMAE_Dealer_Leads::counts_for_listing( $L1 );
check(
	'counts L1',
	array( 'pending' => 0, 'interested' => 2, 'declined' => 1, 'expired' => 1, 'cancelled' => 1, 'total' => 5 ) === $c,
	json_encode( $c )
);
check( 'counts L2', 1 === KMAE_Dealer_Leads::counts_for_listing( $L2 )['pending'] && 1 === KMAE_Dealer_Leads::counts_for_listing( $L2 )['total'] );
check( 'counts unknown listing', 0 === KMAE_Dealer_Leads::counts_for_listing( 4242 )['total'] );

$d11 = KMAE_Dealer_Leads::list_for_dealer( 11 );
check( 'list_for_dealer 11: 2 rows, newest first', 2 === count( $d11 ) && $L2 === $d11[0]['listing_id'] && $L1 === $d11[1]['listing_id'] );
check( 'list_for_dealer limit', 1 === count( KMAE_Dealer_Leads::list_for_dealer( 11, 1 ) ) );
$d11p = KMAE_Dealer_Leads::list_for_dealer( 11, 50, array( 'pending' ) );
check( 'list_for_dealer status filter', 1 === count( $d11p ) && $L2 === $d11p[0]['listing_id'] );
check( 'list_for_dealer bad status ignored', 2 === count( KMAE_Dealer_Leads::list_for_dealer( 11, 50, array( "x' --" ) ) ) );
check( 'count_pending_for_dealer 11', 1 === KMAE_Dealer_Leads::count_pending_for_dealer( 11 ) );
check( 'count_pending_for_dealer 12', 0 === KMAE_Dealer_Leads::count_pending_for_dealer( 12 ) );

$sg = KMAE_Dealer_Leads::list_for_seller( $S );
check( 'list_for_seller: 2 groups, L2 first', 2 === count( $sg ) && $L2 === $sg[0]['listing_id'] && $L1 === $sg[1]['listing_id'] );
check( 'list_for_seller: L1 counts = counts_for_listing', $sg[1]['counts'] === $c, json_encode( $sg[1]['counts'] ) );
check( 'list_for_seller: L1 5 leads + title', 5 === count( $sg[1]['leads'] ) && 'Opel Astra' === $sg[1]['listing_title'] );
check( 'list_for_seller: unknown seller', array() === KMAE_Dealer_Leads::list_for_seller( 999 ) );

/* --- nagy küldés (darabolt INSERT) ---------------------------------- */
$big = array();
for ( $d = 1000; $d < 1450; $d++ ) {
	$big[ $d ] = $d % 7;
}
$bb = KMAE_Dealer_Leads::insert_batch( 300, $S, 'sell', $big, 'Nagy küldés' );
check( 'big batch: 450 rows over 3 chunks', 450 === count( $bb ) && 450 === count( array_unique( $ids_of( $bb, 'token' ) ) ) );

/* --- Kereskedői ár (2. séma) ---------------------------------------- */
$bp = KMAE_Dealer_Leads::insert_batch( 700, $S, 'sell', array( 31 => 5.0, 32 => null ), 'Ár-teszt', 2450000 );
check( 'price: minden sorra beíródik', 2 === count( $bp ) && 2450000 === $bp[0]['dealer_price'] && 2450000 === $bp[1]['dealer_price'] );
check( 'price: olvasáskor is megvan (get / token)', 2450000 === KMAE_Dealer_Leads::get( $bp[0]['id'] )['dealer_price'] && 2450000 === KMAE_Dealer_Leads::get_by_token( $bp[1]['token'] )['dealer_price'] );
$bp2 = KMAE_Dealer_Leads::insert_batch( 700, $S, 'sell', array( 33 => 1.0 ), 'Ár-teszt', 1800000 );
check( 'price: új batch más árral, a régiek érintetlenek', 1800000 === $bp2[0]['dealer_price'] && 2450000 === KMAE_Dealer_Leads::get( $bp[0]['id'] )['dealer_price'] );
check( 'price: nélkül (régi hívás) → null', null === $b1[0]['dealer_price'] && null === $b4[0]['dealer_price'] );
$bz = KMAE_Dealer_Leads::insert_batch( 701, $S, 'sell', array( 31 => 1.0 ), 'Ár-teszt', 0 );
check( 'price: 0 / negatív → null', null === $bz[0]['dealer_price'] );
$sg700 = array();
foreach ( KMAE_Dealer_Leads::list_for_seller( $S ) as $g ) {
	$sg700[ $g['listing_id'] ] = $g;
}
check( 'list_for_seller: csoport dealer_price = a legutóbbi batch ára', 1800000 === $sg700[700]['dealer_price'] && null === $sg700[ $L1 ]['dealer_price'] );

/* --- Séma-verzió: a meglévő 1-es tábla dbDelta-val kap oszlopot ---------- */
$real_wpdb = $GLOBALS['wpdb'];
class Schema_WPDB {
	public $col_present = true;
	public function prepare( $sql, ...$args ) { return fake_prepare( $sql, 1 === count( $args ) && is_array( $args[0] ) ? $args[0] : $args ); }
	public function esc_like( $s ) { return $s; }
	public function get_charset_collate() { return 'DEFAULT CHARSET=utf8mb4'; }
	public function get_var( $sql ) {
		if ( 0 === strpos( $sql, 'SHOW TABLES' ) ) {
			return 'wp_kmae_dealer_leads';
		}
		if ( 0 === strpos( $sql, 'SHOW COLUMNS' ) ) {
			return $this->col_present ? 'dealer_price' : null;
		}
		return null;
	}
}
$GLOBALS['wpdb'] = new Schema_WPDB();
check( 'schema: a verzió 2', '2' === KMAE_Dealer_Leads::SCHEMA_VERSION );
$GLOBALS['test_dbdelta'] = array();
update_option( KMAE_Dealer_Leads::SCHEMA_OPTION, '1' );
check( 'schema: tárolt 1 → nincs kész', false === KMAE_Dealer_Leads::ready() );
KMAE_Dealer_Leads::maybe_create();
check( 'schema: maybe_create újrafuttatja a dbDelta-t, benne a dealer_price oszlop', 1 === count( $GLOBALS['test_dbdelta'] ) && false !== strpos( $GLOBALS['test_dbdelta'][0], 'dealer_price bigint(20) unsigned DEFAULT NULL' ) );
check( 'schema: siker után az opció 2 és kész', '2' === get_option( KMAE_Dealer_Leads::SCHEMA_OPTION ) && true === KMAE_Dealer_Leads::ready() );
KMAE_Dealer_Leads::maybe_create();
check( 'schema: kész táblánál nincs újabb dbDelta', 1 === count( $GLOBALS['test_dbdelta'] ) );
update_option( KMAE_Dealer_Leads::SCHEMA_OPTION, '1' );
$GLOBALS['wpdb']->col_present = false;
KMAE_Dealer_Leads::maybe_create();
check( 'schema: ha az oszlop mégsem jött létre, az opció marad 1 + újrapróbálás-zár', '1' === get_option( KMAE_Dealer_Leads::SCHEMA_OPTION ) && ! KMAE_Dealer_Leads::ready() && ! empty( $GLOBALS['test_transients'][ KMAE_Dealer_Leads::RETRY ] ) );
KMAE_Dealer_Leads::maybe_create();
check( 'schema: a zár alatt nem próbálkozik', 2 === count( $GLOBALS['test_dbdelta'] ) );
$GLOBALS['wpdb'] = $real_wpdb;
update_option( KMAE_Dealer_Leads::SCHEMA_OPTION, KMAE_Dealer_Leads::SCHEMA_VERSION );

/* --- ready() őrök: hiányzó tábla (séma még nincs kész) → biztonságos üres értékek, SQL nélkül --- */
class Missing_Table_WPDB {
	public $queries = 0;
	public $rows_affected = 0;
	public $prefix = 'wp_';
	public function __call( $n, $a ) { ++$this->queries; throw new RuntimeException( 'SQL a hiányzó táblára: ' . $n ); }
}
$GLOBALS['wpdb'] = new Missing_Table_WPDB();
update_option( KMAE_Dealer_Leads::SCHEMA_OPTION, '' );
$GLOBALS['test_bumps'] = array();
$guard_err = '';
try {
	$g1 = KMAE_Dealer_Leads::count_pending_for_dealer( 41 );
	$g2 = KMAE_Dealer_Leads::on_listing_ended( 800 );
	$g3 = KMAE_Dealer_Leads::cancel_for_dealer( 41 );
	$g4 = KMAE_Dealer_Leads::delete_for_listing( 800 );
	$g5 = KMAE_Dealer_Leads::list_for_dealer( 41 );
	$g6 = KMAE_Dealer_Leads::list_for_seller( 41 );
	$g7 = KMAE_Dealer_Leads::expire_due();
	$g8 = KMAE_Dealer_Leads::run_expire_cron();
	$g9 = KMAE_Dealer_Leads::on_trash_post( 800 );
} catch ( Throwable $e ) {
	$guard_err = $e->getMessage();
}
check( 'ready-őr: egyik függvény sem fordul a hiányzó táblához', '' === $guard_err && 0 === $GLOBALS['wpdb']->queries, $guard_err );
check( 'ready-őr: biztonságos üres értékek', 0 === $g1 && array() === $g2 && array() === $g3 && array() === $g4 && array() === $g5 && array() === $g6 && array() === $g7 && array() === $g8 && array() === $GLOBALS['test_bumps'] );
$GLOBALS['wpdb'] = $real_wpdb;
update_option( KMAE_Dealer_Leads::SCHEMA_OPTION, KMAE_Dealer_Leads::SCHEMA_VERSION );

/* --- 6. fázis: életciklus ------------------------------------------- */
$GLOBALS['test_bumps'] = array();
$la = KMAE_Dealer_Leads::insert_batch( 800, $S, 'sell', array( 41 => 1.0, 42 => 2.0, 43 => 3.0 ), 'Életciklus A', 2000000 );
KMAE_Dealer_Leads::mark_interested( $la[0]['id'], 5001 ); /* a 41 már érdeklődött: marad */
KMAE_Dealer_Leads::mark_declined( $la[1]['id'] );          /* a 42 elutasította: marad */
$ended = KMAE_Dealer_Leads::on_listing_ended( 800 );
check( 'on_listing_ended: csak a pending (43) → cancelled', array( $la[2]['id'] ) === $ended && 'cancelled' === KMAE_Dealer_Leads::get( $la[2]['id'] )['status'] && 'interested' === KMAE_Dealer_Leads::get( $la[0]['id'] )['status'] && 'declined' === KMAE_Dealer_Leads::get( $la[1]['id'] )['status'] );
check( 'on_listing_ended: néma jelzés csak az érintett cégnek (43)', 1 === count( $GLOBALS['test_bumps'] ) && array( 43 ) === $GLOBALS['test_bumps'][0]['ids'] && null === $GLOBALS['test_bumps'][0]['note'], json_encode( $GLOBALS['test_bumps'] ) );
$GLOBALS['test_bumps'] = array();
check( 'on_listing_ended: másodszor semmi, jelzés sincs', array() === KMAE_Dealer_Leads::on_listing_ended( 800 ) && array() === $GLOBALS['test_bumps'] );
check( 'on_listing_ended: a sor megmarad (előzmény), a cím-pillanatkép is', 3 === KMAE_Dealer_Leads::counts_for_listing( 800 )['total'] && 'Életciklus A' === KMAE_Dealer_Leads::get( $la[2]['id'] )['listing_title'] );
check( 'on_listing_ended: ismeretlen / 0 hirdetés', array() === KMAE_Dealer_Leads::on_listing_ended( 0 ) && array() === KMAE_Dealer_Leads::on_listing_ended( 99999 ) && array() === $GLOBALS['test_bumps'] );

/* wp_trash_post kampó: csak kmae_listing, idempotens (a már kész on_listing_ended mellett sincs dupla). */
$GLOBALS['test_bumps'] = array();
$lt = KMAE_Dealer_Leads::insert_batch( 830, $S, 'sell', array( 41 => 1.0, 42 => 2.0 ), 'Kuka', 1500000 );
$GLOBALS['test_post_types'][830] = 'post';
KMAE_Dealer_Leads::on_trash_post( 830 );
check( 'on_trash_post: nem kmae_listing → nem nyúl a sorokhoz', 2 === KMAE_Dealer_Leads::counts_for_listing( 830 )['pending'] && array() === $GLOBALS['test_bumps'] );
$GLOBALS['test_post_types'][830] = 'kmae_listing';
KMAE_Dealer_Leads::on_trash_post( 830 );
check( 'on_trash_post: kmae_listing → a pending sorok cancelled + néma jelzés a cégeknek', 2 === KMAE_Dealer_Leads::counts_for_listing( 830 )['cancelled'] && 1 === count( $GLOBALS['test_bumps'] ) && array( 41, 42 ) === $GLOBALS['test_bumps'][0]['ids'], json_encode( $GLOBALS['test_bumps'] ) );
$GLOBALS['test_bumps'] = array();
KMAE_Dealer_Leads::on_trash_post( 830 );
KMAE_Dealer_Leads::on_listing_ended( 830 );
check( 'on_trash_post: másodszor / on_listing_ended után nincs dupla jelzés, sor nem változik', array() === $GLOBALS['test_bumps'] && 2 === KMAE_Dealer_Leads::counts_for_listing( 830 )['cancelled'] );

/* Cég fiók-törlése. */
$lb = array_merge(
	KMAE_Dealer_Leads::insert_batch( 810, 8, 'sell', array( 51 => 1.0, 52 => 1.0 ), 'B1', 1000000 ),
	KMAE_Dealer_Leads::insert_batch( 811, 9, 'tradein', array( 51 => 1.0 ), 'B2', 1000000 ),
	KMAE_Dealer_Leads::insert_batch( 812, 9, 'sell', array( 51 => 1.0 ), 'B3', 1000000 )
);
KMAE_Dealer_Leads::mark_declined( $lb[3]['id'] ); /* 812: már elutasítva */
$GLOBALS['test_bumps'] = array();
$cd = KMAE_Dealer_Leads::cancel_for_dealer( 51 );
check( 'cancel_for_dealer: csak a pending sorok (810, 811)', 2 === count( $cd ) && in_array( $lb[0]['id'], $cd, true ) && in_array( $lb[2]['id'], $cd, true ) && 'declined' === KMAE_Dealer_Leads::get( $lb[3]['id'] )['status'] );
check( 'cancel_for_dealer: cancelled, a sorok megmaradnak', 'cancelled' === KMAE_Dealer_Leads::get( $lb[0]['id'] )['status'] && 'cancelled' === KMAE_Dealer_Leads::get( $lb[2]['id'] )['status'] && 3 === count( KMAE_Dealer_Leads::list_for_dealer( 51 ) ) );
check( 'cancel_for_dealer: a másik cég (52) érintetlen', 'pending' === KMAE_Dealer_Leads::get( $lb[1]['id'] )['status'] );
check( 'cancel_for_dealer: néma jelzés az érintett eladóknak (8, 9), a cégnek nem', 1 === count( $GLOBALS['test_bumps'] ) && array( 8, 9 ) === ( static function ( $a ) { sort( $a ); return $a; } )( $GLOBALS['test_bumps'][0]['ids'] ) && null === $GLOBALS['test_bumps'][0]['note'] && ! in_array( 51, $GLOBALS['test_bumps'][0]['ids'], true ), json_encode( $GLOBALS['test_bumps'] ) );
check( 'cancel_for_dealer: újra / ismeretlen / 0 → üres', array() === KMAE_Dealer_Leads::cancel_for_dealer( 51 ) && array() === KMAE_Dealer_Leads::cancel_for_dealer( 0 ) && array() === KMAE_Dealer_Leads::cancel_for_dealer( 99999 ) );
check( 'cancel_for_dealer: törölt cég sorára nem lehet válaszolni', false === KMAE_Dealer_Leads::mark_interested( $lb[0]['id'], 1 ) );

/* Admin „Újraindítás tiszta lappal”: a hirdetés sorai törlődnek. */
$GLOBALS['test_bumps'] = array();
$parties = KMAE_Dealer_Leads::delete_for_listing( 800 );
check( 'delete_for_listing: 3 sor törölve, érintettek = cégek + eladó', 0 === KMAE_Dealer_Leads::counts_for_listing( 800 )['total'] && array( $S, 41, 42, 43 ) === ( static function ( $a ) { sort( $a ); return $a; } )( $parties ), json_encode( $parties ) );
check( 'delete_for_listing: más hirdetés érintetlen, jelzést nem küld', 3 === KMAE_Dealer_Leads::counts_for_listing( 700 )['total'] && array() === $GLOBALS['test_bumps'] );
check( 'delete_for_listing: újra küldhető a törölt hirdetés ugyanannak a cégnek', 1 === count( KMAE_Dealer_Leads::insert_batch( 800, $S, 'sell', array( 41 => 1 ), 'Újra', 1500000 ) ) );
check( 'delete_for_listing: üres / 0', array() === KMAE_Dealer_Leads::delete_for_listing( 99999 ) && array() === KMAE_Dealer_Leads::delete_for_listing( 0 ) );

/* Óránkénti cron. */
$GLOBALS['test_bumps'] = array();
$le = array_merge(
	KMAE_Dealer_Leads::insert_batch( 820, 61, 'sell', array( 71 => 1.0, 72 => 1.0 ), 'E1', 1000000 ),
	KMAE_Dealer_Leads::insert_batch( 821, 62, 'sell', array( 71 => 1.0 ), 'E2', 1000000 ),
	KMAE_Dealer_Leads::insert_batch( 822, 63, 'sell', array( 73 => 1.0 ), 'E3', 1000000 )
);
$wpdb->query( "UPDATE wp_kmae_dealer_leads SET expires_at = '2020-01-01 00:00:00' WHERE listing_id IN (820, 821)" );
check( 'cron: a hook neve + a kezelő létezik', 'kmae_expire_dealer_leads' === KMAE_Dealer_Leads::CRON_EXPIRE && is_callable( array( 'KMAE_Dealer_Leads', 'run_expire_cron' ) ) );
$exp = KMAE_Dealer_Leads::run_expire_cron();
check( 'cron: csak a lejárt, megválaszolatlan sorok (820, 821) → expired', 3 === count( $exp ) && 'expired' === KMAE_Dealer_Leads::get( $le[0]['id'] )['status'] && 'expired' === KMAE_Dealer_Leads::get( $le[2]['id'] )['status'] && 'pending' === KMAE_Dealer_Leads::get( $le[3]['id'] )['status'] );
check( 'cron: egyetlen néma jelzés a cégeknek (71, 72) és eladóknak (61, 62), a nem érintettek (73, 63) nélkül', 1 === count( $GLOBALS['test_bumps'] ) && array( 61, 62, 71, 72 ) === ( static function ( $a ) { sort( $a ); return $a; } )( $GLOBALS['test_bumps'][0]['ids'] ) && null === $GLOBALS['test_bumps'][0]['note'], json_encode( $GLOBALS['test_bumps'] ) );
$GLOBALS['test_bumps'] = array();
check( 'cron: másodszor nincs teendő, jelzés sincs', array() === KMAE_Dealer_Leads::run_expire_cron() && array() === $GLOBALS['test_bumps'] );

$wpdb->close();
@unlink( $test_abspath . 'wp-admin/includes/upgrade.php' );
@rmdir( $test_abspath . 'wp-admin/includes' );
@rmdir( $test_abspath . 'wp-admin' );
@rmdir( $test_abspath );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
