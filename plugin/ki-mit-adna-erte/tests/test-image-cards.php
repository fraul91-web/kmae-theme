<?php
/**
 * Böngészés v2 kártyaképek: szín-algoritmus + KMAE_Images::card_sources() tartalék-lánc.
 * WordPress nélkül fut, a szükséges WP-függvények itt vannak stubolva.
 *
 * Run: php tests/test-image-cards.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$root     = dirname( __DIR__ );
$uploads  = sys_get_temp_dir() . '/kmae-cards-test-' . getmypid();
$meta_db  = array();
$attached = array();

mkdir( $uploads . '/2026/10', 0777, true );

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
	global $uploads;
	return array( 'basedir' => $uploads, 'baseurl' => 'https://example.test/uploads' );
}
function wp_get_attachment_url( $id ) {
	global $attached;
	return isset( $attached[ $id ] ) ? 'https://example.test/uploads/' . $attached[ $id ]['file'] : false;
}
function wp_get_attachment_metadata( $id ) {
	global $attached;
	return isset( $attached[ $id ] ) ? $attached[ $id ] : false;
}
function get_post_meta( $id, $key, $single = false ) {
	global $meta_db;
	return isset( $meta_db[ $id ][ $key ] ) ? $meta_db[ $id ][ $key ] : '';
}

require $root . '/includes/class-kmae-images.php';
require $root . '/includes/class-kmae-image-ambient.php';
require $root . '/includes/class-kmae-image-cards.php';

$passed = 0;
$failed = array();
function cards_assert( $name, $condition ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo 'PASS  ' . $name . "\n";
		return;
	}
	$failed[] = $name;
	echo 'FAIL  ' . $name . "\n";
}
function cards_make_file( $name ) {
	global $uploads;
	file_put_contents( $uploads . '/2026/10/' . $name, str_repeat( 'x', 600 ) );
}
function cards_size( $file, $w, $h ) {
	return array( 'file' => $file, 'width' => $w, 'height' => $h );
}

/* --- card_sources: minden méret megvan --- */
foreach ( array( 'a.webp', 'a-480w.webp', 'a-800w.webp', 'a-1200w.webp', 'a-1400x933.webp' ) as $f ) {
	cards_make_file( $f );
}
$attached[1] = array(
	'file'   => '2026/10/a.webp',
	'width'  => 1920,
	'height' => 1280,
	'sizes'  => array(
		'kmae_card_s' => cards_size( 'a-480w.webp', 480, 320 ),
		'kmae_card_m' => cards_size( 'a-800w.webp', 800, 533 ),
		'kmae_card_l' => cards_size( 'a-1200w.webp', 1200, 800 ),
		'kmae_gallery' => cards_size( 'a-1400x933.webp', 1400, 933 ),
	),
);
$meta_db[1] = array(
	'_kmae_ambient' => '#28599a',
	'_kmae_tone'    => 'cool',
	'_kmae_lqip'    => 'data:image/webp;base64,UklGRg==',
);
$c = KMAE_Images::card_sources( 1 );
cards_assert( 'all sizes present: s/m/l are their own files', str_ends_with( $c['s'], '/a-480w.webp' ) && str_ends_with( $c['m'], '/a-800w.webp' ) && str_ends_with( $c['l'], '/a-1200w.webp' ) );
cards_assert( 'w/h come from the master', 1920 === $c['w'] && 1280 === $c['h'] );
cards_assert( 'ambient / tone / lqip are passed through', '#28599a' === $c['ambient'] && 'cool' === $c['tone'] && 0 === strpos( $c['lqip'], 'data:image/webp;base64,' ) );

/* --- kis forrás: 1200 és 800 hiányzik → a legjobb meglévő (a teljes kép), s marad --- */
foreach ( array( 'b.webp', 'b-480w.webp' ) as $f ) {
	cards_make_file( $f );
}
$attached[2] = array(
	'file'   => '2026/10/b.webp',
	'width'  => 700,
	'height' => 500,
	'sizes'  => array( 'kmae_card_s' => cards_size( 'b-480w.webp', 480, 343 ) ),
);
$c = KMAE_Images::card_sources( 2 );
cards_assert( 'small source: s is its own file', str_ends_with( $c['s'], '/b-480w.webp' ) );
cards_assert( 'small source: m and l fall back to the full image (best existing)', str_ends_with( $c['m'], '/b.webp' ) && str_ends_with( $c['l'], '/b.webp' ) );
cards_assert( 'no ambient meta -> empty strings', '' === $c['ambient'] && '' === $c['tone'] && '' === $c['lqip'] );

/* --- régi, még nem feldolgozott kép: csak kmae_gallery + full --- */
foreach ( array( 'c.webp', 'c-1400x933.webp' ) as $f ) {
	cards_make_file( $f );
}
$attached[3] = array(
	'file'   => '2026/10/c.webp',
	'width'  => 1920,
	'height' => 1280,
	'sizes'  => array( 'kmae_gallery' => cards_size( 'c-1400x933.webp', 1400, 933 ) ),
);
$c = KMAE_Images::card_sources( 3 );
cards_assert( 'old image: s/m/l all use kmae_gallery', str_ends_with( $c['s'], '/c-1400x933.webp' ) && $c['s'] === $c['m'] && $c['m'] === $c['l'] );

/* --- a metaadat szerinti fájl hiányzik a lemezről → kihagyja, a következőt veszi --- */
cards_make_file( 'd.webp' );
$attached[4] = array(
	'file'   => '2026/10/d.webp',
	'width'  => 1920,
	'height' => 1280,
	'sizes'  => array(
		'kmae_card_s' => cards_size( 'd-480w.webp', 480, 320 ), // nincs fájl
		'kmae_card_m' => cards_size( 'd-800w.webp', 800, 533 ), // nincs fájl
	),
);
$c = KMAE_Images::card_sources( 4 );
cards_assert( 'missing files on disk fall back to full', str_ends_with( $c['s'], '/d.webp' ) );

/* --- hibás meta nem kerül ki --- */
$meta_db[4] = array( '_kmae_ambient' => 'red', '_kmae_tone' => 'purple', '_kmae_lqip' => 'data:image/webp;base64,' . str_repeat( 'A', 700 ) );
$c = KMAE_Images::card_sources( 4 );
cards_assert( 'invalid ambient/tone and an over-long lqip are dropped', '' === $c['ambient'] && '' === $c['tone'] && '' === $c['lqip'] );
cards_assert( 'unknown attachment -> empty', '' === KMAE_Images::card_sources( 999 )['s'] && 0 === KMAE_Images::card_sources( 0 )['w'] );

/* --- szín-algoritmus --- */
function cards_grid( $w, $h, $fn ) {
	$px = array();
	for ( $y = 0; $y < $h; $y++ ) {
		for ( $x = 0; $x < $w; $x++ ) {
			array_push( $px, ...$fn( $x, $y ) );
		}
	}
	return $px;
}
$sky_over_road = KMAE_Image_Ambient::analyze( 16, 16, cards_grid( 16, 16, function ( $x, $y ) {
	return $y < 8 ? array( 80, 140, 220 ) : array( 120, 120, 120 );
} ) );
cards_assert( 'blue sky over grey road: bluish ambient, cool', 'cool' === $sky_over_road['tone'] && hexdec( substr( $sky_over_road['ambient'], 5, 2 ) ) > hexdec( substr( $sky_over_road['ambient'], 1, 2 ) ) );
$orange = KMAE_Image_Ambient::analyze( 16, 16, cards_grid( 16, 16, function () {
	return array( 230, 120, 30 );
} ) );
cards_assert( 'orange image: warm, ambient darkened into the background range', 'warm' === $orange['tone'] && hexdec( substr( $orange['ambient'], 1, 2 ) ) < hexdec( substr( $orange['raw'], 1, 2 ) ) );
$edge_vs_center = KMAE_Image_Ambient::analyze( 20, 20, cards_grid( 20, 20, function ( $x, $y ) {
	$inner = $x >= 6 && $x < 14 && $y >= 6 && $y < 14;
	return $inner ? array( 220, 30, 30 ) : array( 30, 60, 200 );
} ) );
cards_assert( 'the edge colour wins over a bigger-contrast centre', hexdec( substr( $edge_vs_center['ambient'], 5, 2 ) ) > hexdec( substr( $edge_vs_center['ambient'], 1, 2 ) ) );
cards_assert( 'too small a pixel list -> null', null === KMAE_Image_Ambient::analyze( 4, 4, array( 1, 2, 3 ) ) );

foreach ( glob( $uploads . '/2026/10/*' ) as $f ) {
	unlink( $f );
}
rmdir( $uploads . '/2026/10' );
rmdir( $uploads . '/2026' );
rmdir( $uploads );

echo "\n" . $passed . ' passed, ' . count( $failed ) . " failed\n";
exit( empty( $failed ) ? 0 : 1 );
