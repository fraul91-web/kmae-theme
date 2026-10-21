<?php
/**
 * Böngészés v2: KMAE_Browse_Index::card() — a kártya JSON minden kész adata, személyes adat nélkül.
 * WordPress nélkül; a KMAE_Query / KMAE_Images itt stub (a card_sources-t a test-image-cards.php teszteli).
 *
 * Run: php tests/test-browse-index-card.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'KMAE_LAUNCH_MODE', 'single' );
define( 'KMAE_LAUNCH_CATEGORY', 'jarmu' );
define( 'KMAE_OFFER_TYPE_CASH', 'cash' );

$root = dirname( __DIR__ );
$META = array(
	7 => array(
		'_kmae_vehicle_brand'        => 'BMW',
		'_kmae_vehicle_model'        => '320D TOURING',
		'_kmae_vehicle_year'         => '2016',
		'_kmae_vehicle_mileage'      => '187000',
		'_kmae_vehicle_fuel'         => 'diesel',
		'_kmae_vehicle_transmission' => 'automatic',
		'_kmae_vehicle_power_kw'     => '140',
		'_kmae_vehicle_engine_cc'    => '1995',
		'_kmae_vehicle_body'         => 'estate',
		'_kmae_price_mode'           => 'guide',
		'_kmae_guide_price'          => '5490000',
		'_kmae_county'               => 'pest',
		'_kmae_city'                 => 'Budaörs',
	),
	8 => array(
		'_kmae_price_mode' => 'open',
		'_kmae_county'     => 'zala',
	),
);

if ( ! function_exists( 'mb_strtolower' ) ) {
	function mb_strtolower( $s ) {
		return strtolower( $s );
	}
	function mb_strtoupper( $s ) {
		return strtoupper( $s );
	}
	function mb_strlen( $s ) {
		return strlen( utf8_decode( $s ) );
	}
	function mb_convert_case( $s, $mode, $enc = null ) {
		return ucwords( strtolower( $s ) );
	}
	define( 'MB_CASE_TITLE', 2 );
}
function __( $s ) {
	return $s;
}
function sanitize_key( $k ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) );
}
function get_post_meta( $id, $key, $single = false ) {
	global $META;
	return $META[ $id ][ $key ] ?? '';
}
function get_permalink( $id ) {
	return 'https://example.test/hirdetes/' . $id . '/';
}
function wp_json_encode( $v, $f = 0 ) {
	return json_encode( $v, $f );
}

class WP_Post {
	public $ID;
	public $post_title;
	public $post_author = 5;
	public function __construct( $id, $title ) {
		$this->ID         = $id;
		$this->post_title = $title;
	}
}
class KMAE_Query {
	public static function get_gallery_ids( $id ) {
		return 7 === $id ? array( 70, 71, 72 ) : array();
	}
	public static function get_listing_thumbnail_id( $id ) {
		return 0;
	}
}
class KMAE_Images {
	public static function card_sources( $aid ) {
		return array(
			's'       => 72 === $aid ? '' : "https://example.test/u/$aid-480w.webp",
			'm'       => "https://example.test/u/$aid-800w.webp",
			'l'       => "https://example.test/u/$aid-1200w.webp",
			'w'       => 1600,
			'h'       => 1200,
			'ambient' => '#203040',
			'tone'    => 'cool',
			'lqip'    => 'data:image/webp;base64,AAAA',
		);
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
require $root . '/includes/class-kmae-seller.php';
require $root . '/includes/class-kmae-browse-index.php';

$fails = 0;
function ok( $name, $cond ) {
	global $fails;
	echo ( $cond ? 'PASS  ' : 'FAIL  ' ) . $name . "\n";
	$fails += $cond ? 0 : 1;
}

$card = KMAE_Browse_Index::card( new WP_Post( 7, 'Eladó BMW' ) );
$json = wp_json_encode( $card, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

ok( 'JSON kódolható', is_string( $json ) && '' !== $json );
ok( 'cím: márka + modell (csupa nagybetű → Title Case)', 'BMW' === $card['title']['brand'] && '320d Touring' === $card['title']['model'] && 'BMW 320d Touring' === $card['title']['text'] );
ok( 'alcím', '2016 · 187 000 km · Dízel' === $card['subtitle'] );
ok( 'lead: LE / cm³ / liter', 190 === $card['lead']['hp'] && 1995 === $card['lead']['cc'] && '2,0' === $card['lead']['liters'] );
ok( 'ár (irányár)', 'guide' === $card['price']['mode'] && 'Irányár' === $card['price']['label'] && '5 490 000 Ft' === $card['price']['formatted'] );
ok( 'érték', 5490000 === $card['value']['amount'] );
ok( 'ajánlat-típus címke', 'cash' === $card['offer_types'][0]['slug'] && 'Készpénz' === $card['offer_types'][0]['label'] );
ok( 'kártyás nézet: kW + karosszéria (nem a facts-ban)', 140 === $card['lead']['kw'] && array( 'code' => 'estate', 'label' => 'Kombi' ) === $card['body'] );
ok( 'nincs kivágott borító (cutout) kulcs', ! array_key_exists( 'cutout', $card ) );
ok( 'tények: év, km, üzemanyag, váltó', array( 'year', 'mileage', 'fuel', 'transmission' ) === array_column( $card['facts'], 'field' ) );
ok( 'hely', 'Pest, Budaörs' === $card['location'] );
ok( 'eladó: név + fióktípus', array( 'name' => 'Kiss Péter', 'type' => 'Magánszemély' ) === $card['seller'] );
ok( 'márka-logó', false !== strpos( $card['brand_logo'], '.webp' ) );
ok( 'permalink', 'https://example.test/hirdetes/7/' === $card['url'] );
ok( 'fotók: csak kész kártyaméretű (s nem üres)', 2 === $card['photo_count'] && '#203040' === $card['photos'][0]['ambient'] );
ok( 'nincs személyes adat (szerző, telefon, e-mail)', ! preg_match( '/author|phone|telefon|email|user/i', $json ) );

$open = KMAE_Browse_Index::card( new WP_Post( 8, 'Eladó Lada Niva – jó állapot' ) );
ok( 'cím tartalék a post_title-ból', 'Lada Niva' === $open['title']['text'] && 'Lada Niva – jó állapot' === $open['title']['list'] );
ok( 'ár nélkül: price/value null, fotó 0', null === $open['price'] && null === $open['value'] && 0 === $open['photo_count'] );

echo $fails ? "$fails hiba\n" : "OK\n";
exit( $fails ? 1 : 0 );
