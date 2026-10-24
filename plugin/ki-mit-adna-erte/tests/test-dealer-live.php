<?php
/**
 * Csoportos kereskedői megkeresés — 9. fázis: élő frissítés + csengő-jelvény.
 *
 * WordPress és MySQL nélkül:
 *  1. KMAE_Live_Page 'notifications' ujjlenyomat (stubolt megkeresés-lista): stabil azonos adatra,
 *     független a „hátralévő időtől” és az olvasottságtól, változik állapot- / chat- / új sor- /
 *     darabszám- / ár-változásra, felhasználónként külön, vendégnek üres.
 *  2. KMAE_Dealer_Leads::bell_count (a valódi forrás-metódus, stubolt környezetben).
 *  3. Bekötések (forráselemzés): a poll válasza viszi a notif_pending-et, a sablon gyökere +
 *     az ujjlenyomat a lekérdezések ELŐTT, a JS frissítő + a küldés-esemény, a fejléc jelvényei.
 *
 * Run: php tests/test-dealer-live.php
 */

define( 'ABSPATH', __DIR__ . '/' );
$root  = dirname( __DIR__ );
$theme = dirname( dirname( $root ) ) . '/theme/kmae';

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

/** Egy metódus teljes forrása (fejléccel, kapcsos-zárójel párosítással). */
function fn_source( $file, $name ) {
	$src = file_get_contents( $file );
	if ( ! preg_match( '/public static function\s+' . preg_quote( $name, '/' ) . '\s*\(/', $src, $m, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}
	$start = $m[0][1];
	$open  = strpos( $src, '{', $start );
	$depth = 0;
	for ( $i = $open, $len = strlen( $src ); $i < $len; $i++ ) {
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

/* --- 1. Ujjlenyomat ---------------------------------------------------------- */
$GLOBALS['t_user']   = 7;
$GLOBALS['t_dealer'] = array();
$GLOBALS['t_seller'] = array();
function is_user_logged_in() { return $GLOBALS['t_user'] > 0; }
function get_current_user_id() { return $GLOBALS['t_user']; }
class KMAE_Dealer_Leads {
	public static function list_for_dealer( $id, $limit = 50 ) { return $GLOBALS['t_dealer'][ $id ] ?? array(); }
	public static function list_for_seller( $id ) { return $GLOBALS['t_seller'][ $id ] ?? array(); }
}
require $root . '/includes/class-kmae-live-page.php';

function dlead( $id, $status, $conv = null, $left = '2026-10-31 10:00:00' ) {
	return array( 'id' => $id, 'status' => $status, 'conversation_id' => $conv, 'expires_at' => $left, 'updated_at' => '2026-10-24 08:00:00', 'responded_at' => null, 'distance_km' => 12.5, 'token' => str_repeat( 'a', 32 ) );
}
function sgroup( $listing, array $counts, $price = 3000000 ) {
	$base = array( 'pending' => 0, 'interested' => 0, 'declined' => 0, 'expired' => 0, 'cancelled' => 0 );
	$c    = array_merge( $base, $counts );
	$c['total'] = $c['pending'] + $c['interested'] + $c['declined'] + $c['expired'] + $c['cancelled'];
	return array( 'listing_id' => $listing, 'listing_title' => 'Golf', 'counts' => $c, 'last_sent_at' => '2026-10-24 08:00:00', 'dealer_price' => $price, 'leads' => array() );
}
function fp() { return KMAE_Live_Page::get_fingerprint( 'notifications' ); }

$GLOBALS['t_dealer'][7] = array( dlead( 11, 'pending' ), dlead( 10, 'interested', 55 ) );
$GLOBALS['t_seller'][7] = array( sgroup( 900, array( 'pending' => 3, 'interested' => 1 ) ) );
$a = fp();
check( 'ujjlenyomat: 32 hex', 1 === preg_match( '/^[a-f0-9]{32}$/', $a ), $a );
check( 'ujjlenyomat: azonos adatra két renderen stabil', $a === fp() );

/* csak a „hátralévő idő” / időbélyegek változnak (óránként) → NEM változik */
$GLOBALS['t_dealer'][7] = array( dlead( 11, 'pending', null, '2026-10-31 11:00:00' ), dlead( 10, 'interested', 55, '2026-10-30 09:00:00' ) );
$GLOBALS['t_dealer'][7][0]['updated_at']   = '2026-10-24 12:00:00';
$GLOBALS['t_dealer'][7][0]['distance_km']  = 12.6;
$GLOBALS['t_seller'][7][0]['last_sent_at'] = '2026-10-24 12:00:00';
check( 'ujjlenyomat: csak a hátralévő idő / időbélyeg változik → ugyanaz', $a === fp() );

/* állapotváltás */
$GLOBALS['t_dealer'][7][0]['status'] = 'declined';
$b = fp();
check( 'ujjlenyomat: állapotváltásra (pending → declined) változik', $a !== $b );
$GLOBALS['t_dealer'][7][0]['status'] = 'pending';
check( 'ujjlenyomat: visszaállítva ugyanaz', $a === fp() );

/* chat-hozzárendelés */
$GLOBALS['t_dealer'][7][0]['conversation_id'] = 77;
check( 'ujjlenyomat: új chat (conversation_id) → változik', $a !== fp() );
$GLOBALS['t_dealer'][7][0]['conversation_id'] = null;

/* új megkeresés */
array_unshift( $GLOBALS['t_dealer'][7], dlead( 12, 'pending' ) );
check( 'ujjlenyomat: új megkeresés (cég) → változik', $a !== fp() );
array_shift( $GLOBALS['t_dealer'][7] );
check( 'ujjlenyomat: megszűnt az új sor → visszaáll', $a === fp() );

/* eladó: darabszám, új hirdetés, ár */
$GLOBALS['t_seller'][7][0]['counts']['pending'] = 2;
$GLOBALS['t_seller'][7][0]['counts']['declined'] = 1;
check( 'ujjlenyomat: eladói állapot-darabszám átcsúszik (összeg ugyanaz) → változik', $a !== fp() );
$GLOBALS['t_seller'][7][0]['counts']['pending'] = 3;
$GLOBALS['t_seller'][7][0]['counts']['declined'] = 0;
check( 'ujjlenyomat: eladói darabszám visszaállítva → ugyanaz', $a === fp() );
$GLOBALS['t_seller'][7][0]['counts']['total'] = 5;
check( 'ujjlenyomat: eladói összes változik → változik', $a !== fp() );
$GLOBALS['t_seller'][7][0]['counts']['total'] = 4;
$GLOBALS['t_seller'][7][] = sgroup( 901, array( 'pending' => 1 ) );
check( 'ujjlenyomat: új hirdetés megkereséssel (eladó) → változik', $a !== fp() );
array_pop( $GLOBALS['t_seller'][7] );
$GLOBALS['t_seller'][7][0]['dealer_price'] = 3500000;
check( 'ujjlenyomat: új kereskedői ár → változik', $a !== fp() );
$GLOBALS['t_seller'][7][0]['dealer_price'] = 3000000;
check( 'ujjlenyomat: minden visszaállítva → az eredeti', $a === fp() );

/* felhasználónként külön, vendégnek üres, rossz kontextus nem esik szét */
$GLOBALS['t_user'] = 8;
check( 'ujjlenyomat: másik felhasználóra más (és üres adatnál is stabil)', $a !== fp() && fp() === fp() );
$GLOBALS['t_user'] = 0;
check( 'ujjlenyomat: vendégnek üres', '' === fp() );
$GLOBALS['t_user'] = 7;

/* a többi kontextus érintetlen: ismeretlen kontextus csak (kontextus, user) */
check( 'ujjlenyomat: más kontextus nem keveredik (notifications ≠ ismeretlen)', KMAE_Live_Page::get_fingerprint( 'x' ) !== fp() );

/* --- 2. bell_count (valódi forrás, stubolt környezet) ----------------------- */
$GLOBALS['t_enabled'] = true;
$GLOBALS['t_ready'] = true;
$GLOBALS['t_company'] = array( 7 => true );
$GLOBALS['t_pending'] = array( 7 => 4 );
class KMAE_Auth { public static function is_company( $id ) { return ! empty( $GLOBALS['t_company'][ $id ] ); } }
$bell_src = fn_source( $root . '/includes/class-kmae-dealer-leads.php', 'bell_count' );
check( 'bell_count: a forrásban megvan', '' !== $bell_src );
eval( 'class Bell_Under_Test {
	public static function ready() { return $GLOBALS["t_ready"]; }
	public static function is_enabled() { return $GLOBALS["t_enabled"]; }
	public static function count_pending_for_dealer( $id ) { return $GLOBALS["t_pending"][ $id ] ?? 0; }
	' . $bell_src . '
}' );
check( 'bell_count: cég + bekapcsolva → a válaszra váró darabszám', 4 === Bell_Under_Test::bell_count( 7 ) );
check( 'bell_count: nem céges fiók (eladó) → 0', 0 === Bell_Under_Test::bell_count( 9 ) );
$GLOBALS['t_enabled'] = false;
check( 'bell_count: kikapcsolt funkció → 0', 0 === Bell_Under_Test::bell_count( 7 ) );
$GLOBALS['t_enabled'] = true;
check( 'bell_count: vendég / érvénytelen azonosító → 0', 0 === Bell_Under_Test::bell_count( 0 ) );
$GLOBALS['t_ready'] = false;
check( 'bell_count: hiányzó tábla (ready() hamis) → 0, a számláló sem fut', 0 === Bell_Under_Test::bell_count( 7 ) );
$GLOBALS['t_ready'] = true;

/* --- 3. Bekötések (forráselemzés) ------------------------------------------- */
$public = file_get_contents( $root . '/public/class-kmae-public.php' );
$poll   = '';
if ( preg_match( '/function ajax_poll_page\(\).*?\n\t}\n/s', $public, $m ) ) {
	$poll = $m[0];
}
check( 'poll: a válasz viszi a notif_pending-et (bell_count)', false !== strpos( $poll, "\$response['notif_pending']" ) && false !== strpos( $poll, 'KMAE_Dealer_Leads::bell_count' ) );
check( 'poll: az unseen (Üzenetek) jelvény változatlanul megvan', false !== strpos( $poll, "\$response['unseen']" ) );

$tpl = file_get_contents( $root . '/templates/notifications.php' );
check( 'sablon: gyökér data-kmae-live-page="notifications"', false !== strpos( $tpl, 'data-kmae-live-page="notifications"' ) && false !== strpos( $tpl, 'data-kmae-live-fingerprint=' ) );
$p_fp   = strpos( $tpl, "get_fingerprint( 'notifications' )" );
$p_list = strpos( $tpl, 'KMAE_Dealer_Leads::list_for_dealer' );
check( 'sablon: az ujjlenyomat a lekérdezések ELŐTT készül', false !== $p_fp && false !== $p_list && $p_fp < $p_list );
check( 'sablon: a gyökér a .kmae-notif (a fejléc és a szekciók ezen belül)', 1 === preg_match( '/<div class="kmae-notif" data-kmae-live-page="notifications"/', $tpl ) );
check( 'ujjlenyomat-forrás: nincs benne olvasottság / idő (seen, expires_at, time)', ! preg_match( "/case 'notifications':.*?(seen|expires_at|time\\(|left)/s", substr( file_get_contents( $root . '/includes/class-kmae-live-page.php' ), 0, strpos( file_get_contents( $root . '/includes/class-kmae-live-page.php' ), "case 'email_verify'" ) ) ) );

$js = file_get_contents( $root . '/assets/js/public.js' );
check( 'JS: updateNotifBadges + a poll-válasz notif_pending ágon hívja', false !== strpos( $js, 'function updateNotifBadges' ) && 1 === preg_match( "/notif_pending\s*!==\s*'undefined'\)\s*\{\s*updateNotifBadges/", $js ) );
check( 'JS: küldés-esemény csak az Értesítések oldalon újraellenőriz', 1 === preg_match( "/context === 'notifications'\).*?kmae:dealer-leads-sent.*?lastSignal = '';.*?fullPoll\(\)/s", $js ) );

$header = file_get_contents( $theme . '/header.php' );
check( 'fejléc: PC jelvény bell_count-ból (data-kmae-notif-badge)', false !== strpos( $header, 'KMAE_Dealer_Leads::bell_count' ) && false !== strpos( $header, "'badge_attr' => 'data-kmae-notif-badge'" ) );
check( 'fejléc: telefonos csengőn jelvény ugyanazzal az attribútummal', 1 === preg_match( '/kmae-phone-header__badge" data-kmae-notif-badge=/', $header ) );
$css = file_get_contents( $theme . '/assets/css/simple-mode/header.css' );
check( 'CSS: a telefonos jelvény max-width:1024px alatt, oldal-specifikusan', 1 === preg_match( '/@media \(max-width: 1024px\) \{\s*body\.kmae-app \.kmae-header__mobile-top \.kmae-phone-header__bell/', $css ) );

echo "\n" . $passed . ' ok, ' . count( $failed ) . " fail\n";
exit( $failed ? 1 : 0 );
