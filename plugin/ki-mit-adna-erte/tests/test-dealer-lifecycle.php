<?php
/**
 * Csoportos kereskedői megkeresés — 6. fázis: a hirdetés / fiók életciklusa a megkeresésekre.
 *
 * WordPress és MySQL nélkül. Két réteg:
 *  1. Valódi futás (stubokkal): KMAE_Chat_Offers::close_listing() (eladás / lejárt Foglalt lezárás)
 *     meghívja a KMAE_Dealer_Leads::on_listing_ended()-et, és a hirdetés lezárása után fut.
 *  2. Bekötés-ellenőrzés (forráselemzés): a függvénytörzsekben ott a hívás a megfelelő helyen
 *     (force_delete_listing a wp_delete_post ELŐTT, expire_listings, reset_listing, delete_account,
 *     cron ütemezés / törlés / kezelő). A „Foglalt” (accept) NEM hívja — a foglalás visszavonható.
 *
 * Run: php tests/test-dealer-lifecycle.php
 */

define( 'ABSPATH', __DIR__ . '/' );
$root = dirname( __DIR__ );

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

/** Egy függvény törzse (a fájlból, kapcsos-zárójel párosítással). */
function fn_body( $file, $name ) {
	$src = file_get_contents( $file );
	if ( ! preg_match( '/function\s+' . preg_quote( $name, '/' ) . '\s*\(/', $src, $m, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}
	$start = strpos( $src, '{', $m[0][1] );
	$depth = 0;
	$len   = strlen( $src );
	for ( $i = $start; $i < $len; $i++ ) {
		if ( '{' === $src[ $i ] ) {
			++$depth;
		} elseif ( '}' === $src[ $i ] ) {
			--$depth;
			if ( 0 === $depth ) {
				return substr( $src, $start, $i - $start + 1 );
			}
		}
	}
	return '';
}

/* --- 1. Valódi futás: KMAE_Chat_Offers::close_listing ----------------------- */
$LOG    = array();
$STATUS = array( 900 => 'active' );
function update_post_meta( $id, $key, $value ) { global $STATUS, $LOG; if ( '_kmae_status' === $key ) { $STATUS[ $id ] = $value; $LOG[] = 'status:' . $value; } return true; }
function do_action( $hook, ...$args ) { global $LOG; $LOG[] = 'action:' . $hook; }
class KMAE_Meta {
	const STATUS_ACTIVE = 'active';
	const STATUS_CLOSED = 'closed';
	public static function get_status( $id ) { global $STATUS; return $STATUS[ $id ] ?? ''; }
}
class KMAE_Conversations {
	public static function listing_is_deleted( $id ) { return false; }
	public static function close_for_listing( $id ) { global $LOG; $LOG[] = 'chats-closed'; }
}
class KMAE_Dealer_Leads {
	public static function on_listing_ended( $id ) { global $LOG; $LOG[] = 'dealer-leads-ended:' . (int) $id; return array(); }
}
require $root . '/includes/class-kmae-chat-offers.php';

$m = new ReflectionMethod( 'KMAE_Chat_Offers', 'close_listing' );
$m->setAccessible( true );
$m->invoke( null, 900 );
check( 'close_listing: a hirdetés closed lett', 'closed' === $STATUS[900] );
check( 'close_listing: a megkeresések megszűnnek (on_listing_ended 900)', in_array( 'dealer-leads-ended:900', $LOG, true ), json_encode( $LOG ) );
check( 'close_listing: a hirdetés lezárása UTÁN, a cache-ürítés ELŐTT', array_search( 'status:closed', $LOG, true ) < array_search( 'dealer-leads-ended:900', $LOG, true ) && array_search( 'dealer-leads-ended:900', $LOG, true ) < array_search( 'action:kmae_listing_reserved_changed', $LOG, true ), json_encode( $LOG ) );

/* --- 2. Bekötések (forráselemzés) ------------------------------------------- */
$listings = $root . '/includes/class-kmae-listings.php';
$offers   = $root . '/includes/class-kmae-chat-offers.php';
$auth     = $root . '/includes/class-kmae-auth.php';
$activ    = $root . '/includes/class-kmae-activator.php';
$deact    = $root . '/includes/class-kmae-deactivator.php';
$leads    = $root . '/includes/class-kmae-dealer-leads.php';

$b = fn_body( $listings, 'force_delete_listing' );
$pos_end = strpos( $b, 'KMAE_Dealer_Leads::on_listing_ended' );
check( 'force_delete_listing: on_listing_ended a wp_delete_post ELŐTT, a chat-takarítás mellett', false !== $pos_end && $pos_end < strpos( $b, 'wp_delete_post' ) && $pos_end > strpos( $b, 'withdraw_alerts_for_listing' ) && false !== strpos( $b, "class_exists( 'KMAE_Dealer_Leads' )" ) );

$b = fn_body( $listings, 'expire_listings' );
check( 'expire_listings: minden lejáratott hirdetésre on_listing_ended', false !== strpos( $b, 'KMAE_Dealer_Leads::on_listing_ended( $listing_id )' ) && strpos( $b, 'KMAE_Dealer_Leads::on_listing_ended' ) > strpos( $b, 'STATUS_EXPIRED' ) );

$b = fn_body( $listings, 'reset_listing' );
check( 'reset_listing: delete_for_listing, az eredmény a némított $parties bump-ba megy', false !== strpos( $b, 'KMAE_Dealer_Leads::delete_for_listing( $listing_id )' ) && false !== strpos( $b, '$parties = array_merge( $parties, KMAE_Dealer_Leads::delete_for_listing' ) && strpos( $b, 'delete_for_listing' ) < strpos( $b, 'KMAE_Live_Signal::bump' ) );

$b = fn_body( $auth, 'delete_account' );
check( 'delete_account: cancel_for_dealer a wp_delete_user ELŐTT; az eladói oldal a force_delete_listing-en át', false !== strpos( $b, 'KMAE_Dealer_Leads::cancel_for_dealer( $user_id )' ) && strpos( $b, 'cancel_for_dealer' ) < strpos( $b, 'wp_delete_user' ) && false !== strpos( $b, 'force_delete_listing' ) );

$b = fn_body( $offers, 'close_listing' );
check( 'close_sale + finalize_sales a közös close_listing()-et használja', false !== strpos( fn_body( $offers, 'close_sale' ), 'self::close_listing(' ) && false !== strpos( fn_body( $offers, 'finalize_sales' ), 'self::close_listing(' ) && false !== strpos( $b, 'KMAE_Dealer_Leads::on_listing_ended' ) );

$b = fn_body( $offers, 'accept' );
check( 'accept (Foglalt): NEM töröl megkeresést — a foglalás visszavonható', false === strpos( $b, 'KMAE_Dealer_Leads' ) );

check( 'cron: ütemezés (óránként) az Activator::schedule_cron-ban', 1 === preg_match( "/wp_next_scheduled\( 'kmae_expire_dealer_leads' \).*?wp_schedule_event\( time\(\), 'hourly', 'kmae_expire_dealer_leads' \)/s", fn_body( $activ, 'schedule_cron' ) ) );
check( 'cron: törlés a Deactivatorban', false !== strpos( fn_body( $deact, 'deactivate' ), "wp_clear_scheduled_hook( 'kmae_expire_dealer_leads' )" ) );
check( 'cron: a kezelő az init()-ben a CRON_EXPIRE hookra kötve', false !== strpos( fn_body( $leads, 'init' ), "add_action( self::CRON_EXPIRE, array( __CLASS__, 'run_expire_cron' ) )" ) && false !== strpos( file_get_contents( $leads ), "const CRON_EXPIRE = 'kmae_expire_dealer_leads'" ) );
check( 'cron: nincs levél a lejáratkor (run_expire_cron nem hív Notifications-t)', false === strpos( fn_body( $leads, 'run_expire_cron' ), 'KMAE_Notifications' ) );

/* --- 3. Admin szerkesztő (KMAE_Meta::save_listing_meta): aktívból bármi másba → on_listing_ended ---- */
function wp_verify_nonce( $n, $a ) { return 1; }
function sanitize_text_field( $v ) { return (string) $v; }
function wp_unslash( $v ) { return $v; }
function current_user_can( $cap, $id = 0 ) { return true; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) { return true; }
function get_post_meta( $id, $key, $single = false ) { return ''; }
function delete_post_meta( $id, $key ) { return true; }
function current_time( $t ) { return '2026-10-24 10:00:00'; }
class KMAE_Counties {
	public static function validate_listing_location( $c, $city ) { return true; }
	public static function save_listing_location( $id, $c, $city ) {}
	public static function location_error( $c, $city ) { return ''; }
}
class KMAE_Taxonomies { public static function default_offer_types() { return array(); } }
class KMAE_Seller {
	const PRICE_FIXED = 'fixed';
	const PRICE_GUIDE = 'guide';
	public static function get_price_mode( $id ) { return 'open'; }
	public static function sync_vehicle_value_meta( $id ) {}
}
class KMAE_Database { public static function save_private_data( $id, $v ) {} }
class KMAE_Vehicle { public static function save_from_post( $id ) {} }
class KMAE_Listings { public static function ensure_launch_category( $id ) {} }

$meta_src = fn_body( $root . '/includes/class-kmae-meta.php', 'save_listing_meta' );
check( 'save_listing_meta: a forrásban megvan', '' !== $meta_src );
eval( 'class Meta_Under_Test {
	const STATUS_ACTIVE   = "active";
	const STATUS_EXPIRED  = "expired";
	const STATUS_ARCHIVED = "archived";
	public static function get_status( $id ) { global $STATUS; return $STATUS[ $id ] ?? "active"; }
	public function save_listing_meta( $post_id, $post ) ' . $meta_src . '
}' );
function save_status( $id, $new_status ) {
	global $STATUS, $LOG;
	$_POST = array( 'kmae_listing_nonce' => 'x', 'kmae_status' => $new_status, 'kmae_county' => 'pest', 'kmae_city' => 'Vác' );
	$LOG   = array();
	( new Meta_Under_Test() )->save_listing_meta( $id, (object) array() );
	return array_values( array_filter( $LOG, static function ( $l ) { return 0 === strpos( $l, 'dealer-leads-ended:' ); } ) );
}
$STATUS = array( 920 => 'active', 921 => 'active', 922 => 'closed', 923 => 'expired' );
check( 'save_listing_meta: active → closed → megkeresések megszűnnek', array( 'dealer-leads-ended:920' ) === save_status( 920, 'closed' ) );
check( 'save_listing_meta: active → archived is', array( 'dealer-leads-ended:921' ) === save_status( 921, 'archived' ) );
$STATUS[924] = 'active';
check( 'save_listing_meta: active → active (mentés változatlan státusszal) → nincs hívás', array() === save_status( 924, 'active' ) );
check( 'save_listing_meta: closed → closed → nincs hívás', array() === save_status( 922, 'closed' ) );
check( 'save_listing_meta: expired → active (megújítás) → nincs hívás', array() === save_status( 923, 'active' ) );
$sm = fn_body( $root . '/includes/class-kmae-meta.php', 'save_listing_meta' );
check( 'save_listing_meta: a hívás class_exists-szel védett, a státusz mentése UTÁN', false !== strpos( $sm, "class_exists( 'KMAE_Dealer_Leads' )" ) && strpos( $sm, 'on_listing_ended' ) > strpos( $sm, "update_post_meta( \$post_id, '_kmae_status', \$status )" ) );

/* --- 4. wp_trash_post kampó (init) -------------------------------------------- */
check( 'init: wp_trash_post kampó → on_trash_post', false !== strpos( fn_body( $leads, 'init' ), "add_action( 'wp_trash_post', array( __CLASS__, 'on_trash_post' ) )" ) );
$tb = fn_body( $leads, 'on_trash_post' );
check( 'on_trash_post: csak kmae_listing, on_listing_ended-et hív (idempotens cancel_for_listing: csak pending → cancelled)', false !== strpos( $tb, "'kmae_listing' === get_post_type" ) && false !== strpos( $tb, 'self::on_listing_ended' ) );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( $failed ? 1 : 0 );
