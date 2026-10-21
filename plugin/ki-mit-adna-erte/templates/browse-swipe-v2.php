<?php
/**
 * Böngészés v2 — kártyás nézet (csak a kártya-stack; a browse-swipe.php include-olja).
 * Az 1. kártya kész HTML, az első feed-oldal (és belépve a /me/browse) inline JSON-ban;
 * a többit az assets/js/swipe-v2.js tölti. A DOM-ban egyszerre 3 kártya-elem van.
 * Markup-szerződés: PROJECT-MEMORY/FEATURES/BROWSE-V2.md „Markup a dizájnhoz”.
 *
 * @package KMAE
 * @var array $sw2         KMAE_Browse_Feed::client_state().
 * @var bool  $view_browse Belépett (Mentés billentyű a súgóban).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sw2_text = array(
	'noPhoto'       => __( 'Nincs kép', 'ki-mit-adna-erte' ),
	'photoPrev'     => __( 'Előző kép', 'ki-mit-adna-erte' ),
	'photoNext'     => __( 'Következő kép', 'ki-mit-adna-erte' ),
	'reserved'      => __( 'Foglalt', 'ki-mit-adna-erte' ),
	'reservedTitle' => __( 'Az eladó elfogadott egy ajánlatot, most nem lehet új ajánlatot tenni.', 'ki-mit-adna-erte' ),
	'save'          => __( 'Mentés', 'ki-mit-adna-erte' ),
	'saveTitle'     => __( 'Mentés a kedvencekhez', 'ki-mit-adna-erte' ),
	'open'          => __( 'Ajánlatot vár', 'ki-mit-adna-erte' ),
	'details'       => __( 'Részletes adatlap megtekintése', 'ki-mit-adna-erte' ),
	'message'       => __( 'Hirdetés megtekintése', 'ki-mit-adna-erte' ),
	'power'         => __( 'Teljesítmény', 'ki-mit-adna-erte' ),
	'body'          => __( 'Karosszéria', 'ki-mit-adna-erte' ),
	'photoN'        => __( '%d. kép', 'ki-mit-adna-erte' ),
	'full'          => __( 'Teljes képernyő', 'ki-mit-adna-erte' ),
	'reportSent'    => __( 'Köszönjük! A bejelentést megkaptuk, hamarosan megnézzük.', 'ki-mit-adna-erte' ),
	'reportFail'    => __( 'A bejelentés most nem ment el. Próbáld újra.', 'ki-mit-adna-erte' ),
	'reportPhoto'   => __( 'Ez a kép: %1$d. / %2$d', 'ki-mit-adna-erte' ),
	'sheetLoading'  => __( 'Adatlap betöltése…', 'ki-mit-adna-erte' ),
);
/* PC-n a fotó a teljes színpadot kitölti (2026-10-18), telefonon teljes szélesség. */
$sw2_sizes = '100vw';

/* <tag class="kmae-sw2-$cls"$attrs>$inner</tag> — a swipe-v2.js el() párja. */
$sw2_el = static function ( $tag, $cls, $inner, $attrs = '' ) {
	return '<' . $tag . ' class="kmae-sw2-' . $cls . '"' . $attrs . '>' . $inner . '</' . $tag . '>';
};

$sw2_srcset = static function ( array $p ) {
	$out = array();
	foreach ( array( 's' => 480, 'm' => 800, 'l' => 1200 ) as $key => $width ) {
		if ( ! empty( $p[ $key ] ) ) {
			$out[] = $p[ $key ] . ' ' . $width . 'w';
		}
	}
	return implode( ', ', $out );
};

/* A kártya belseje — PONTOSAN a swipe-v2.js card() kimenete (az 1. kártyához). */
$sw2_card = static function ( array $it ) use ( $sw2_el, $sw2_srcset, $sw2_text, $sw2_sizes ) {
	$c     = $it['card'];
	$ph    = isset( $c['photos'] ) ? (array) $c['photos'] : array();
	$n     = count( $ph );
	$ld    = isset( $c['lead'] ) ? (array) $c['lead'] : array();

	if ( $n ) {
		$p = $ph[0];
		$m = '<img class="kmae-sw2-img" src="' . esc_url( $p['m'] ? $p['m'] : $p['s'] ) . '" srcset="' . esc_attr( $sw2_srcset( $p ) ) . '" sizes="' . esc_attr( $sw2_sizes ) . '"'
			. ( ! empty( $p['w'] ) ? ' width="' . (int) $p['w'] . '" height="' . (int) $p['h'] . '"' : '' )
			. ' alt="" decoding="async" draggable="false" fetchpriority="high" data-ready>';
	} else {
		$m = $sw2_el( 'span', 'nophoto', esc_html( $sw2_text['noPhoto'] ) );
	}
	$m = $sw2_el( 'a', 'open', $m, ' href="' . esc_url( $c['url'] ) . '" draggable="false"' );
	if ( $n > 1 ) {
		$m .= $sw2_el( 'button', 'photo-prev', '‹', ' type="button" data-kmae-sw2-photo="-1" aria-label="' . esc_attr( $sw2_text['photoPrev'] ) . '" hidden' )
			. $sw2_el( 'button', 'photo-next', '›', ' type="button" data-kmae-sw2-photo="1" aria-label="' . esc_attr( $sw2_text['photoNext'] ) . '"' );
	}
	if ( $n ) {
		$m .= $sw2_el( 'span', 'photo-count', '1 / ' . $n, ' aria-hidden="true"' )
			. $sw2_el( 'button', 'full', '', ' type="button" data-kmae-sw2-full aria-label="' . esc_attr( $sw2_text['full'] ) . '" title="' . esc_attr( $sw2_text['full'] ) . '"' );
	}
	if ( $n ) {
		$dots = '';
		for ( $i = 0; $i < $n; $i++ ) {
			$dots .= $sw2_el( 'span', 'dot', '', $i ? '' : ' data-current' );
		}
		$m .= $sw2_el( 'span', 'dots', $dots, ' aria-hidden="true"' );
	}
	/* Bélyegkép-sáv (≥2 fotó): csak az aktuális kártyán töltődik ki (a JS a többin üresen hagyja). */
	if ( $n > 1 ) {
		$th = '';
		foreach ( $ph as $i => $tp ) {
			$th .= $sw2_el(
				'button',
				'thumb',
				'<img src="' . esc_url( $tp['s'] ) . '" alt="" loading="lazy" decoding="async" draggable="false">',
				' type="button" data-kmae-sw2-thumb="' . (int) $i . '" aria-label="' . esc_attr( sprintf( $sw2_text['photoN'], $i + 1 ) ) . '"' . ( $i ? '' : ' aria-current="true"' )
			);
		}
		$m .= $sw2_el( 'div', 'thumbs', $th, ' data-kmae-sw2-thumbs' );
	}
	if ( ! empty( $it['reserved'] ) ) {
		$m .= $sw2_el( 'span', 'reserved', esc_html( $sw2_text['reserved'] ), ' title="' . esc_attr( $sw2_text['reservedTitle'] ) . '"' );
	}
	$m .= $sw2_el( 'button', 'like', esc_html( $sw2_text['save'] ), ' type="button" data-kmae-sw2-like title="' . esc_attr( $sw2_text['saveTitle'] ) . '" aria-pressed="false"' );

	/* Fejléc (PC-n: logó bal fent, cím + eladó + helyszín felül középen): márka-logó, cím, LE/cm³, eladó, helyszín. */
	$h = ! empty( $c['brand_logo'] ) ? $sw2_el( 'span', 'brand', '<img src="' . esc_url( $c['brand_logo'] ) . '" alt="" width="44" height="44" decoding="async">' ) : '';
	$h .= $sw2_el( 'h3', 'title', esc_html( $c['title']['text'] ) );
	if ( ! empty( $ld['hp'] ) || ! empty( $ld['liters'] ) || ! empty( $c['body']['label'] ) ) {
		$h .= $sw2_el(
			'p',
			'lead',
			( ! empty( $ld['hp'] ) ? $sw2_el( 'span', 'lead-hp', esc_html( number_format( (int) $ld['hp'], 0, ',', ' ' ) ) . ' LE' ) : '' )
			. ( ! empty( $ld['liters'] ) ? $sw2_el( 'span', 'lead-cc', esc_html( $ld['liters'] ) . ' cm³' ) : '' )
			. ( ! empty( $c['body']['label'] ) ? $sw2_el( 'span', 'lead-body', esc_html( $c['body']['label'] ), ' data-code="' . esc_attr( $c['body']['code'] ) . '"' ) : '' )
		);
	}
	if ( ! empty( $c['seller']['name'] ) ) {
		$h .= $sw2_el( 'p', 'seller', $sw2_el( 'span', 'seller-name', esc_html( $c['seller']['name'] ) ) . ( ! empty( $c['seller']['type'] ) ? $sw2_el( 'span', 'seller-type', esc_html( $c['seller']['type'] ) ) : '' ) );
	}
	if ( ! empty( $c['location'] ) ) {
		$km = isset( $it['distance_km'] ) && null !== $it['distance_km']
			? $sw2_el( 'em', 'km', esc_html( '~' . number_format( max( 1, round( (float) $it['distance_km'] ) ), 0, ',', ' ' ) . ' km' ) )
			: '';
		$h .= $sw2_el( 'p', 'place', $sw2_el( 'span', 'loc', esc_html( $c['location'] ) . $km ) );
	}
	$i = $sw2_el( 'div', 'head', $h );
	$pr = isset( $c['price'] ) ? $c['price'] : null;
	if ( $pr ) {
		$i .= $sw2_el( 'p', 'price', ( '' !== $pr['label'] ? $sw2_el( 'span', 'price-label', esc_html( $pr['label'] ) ) : '' ) . $sw2_el( 'span', 'price-value', esc_html( $pr['formatted'] ) ), ' data-mode="' . esc_attr( $pr['mode'] ) . '"' );
	} elseif ( ! empty( $c['value'] ) ) {
		$i .= $sw2_el( 'p', 'price', $sw2_el( 'span', 'price-value', esc_html( $c['value']['formatted'] ) ), ' data-mode="value"' );
	} else {
		$i .= $sw2_el( 'p', 'price', $sw2_el( 'span', 'price-value', esc_html( $sw2_text['open'] ) ), ' data-mode="open"' );
	}
	/* A lista nézet tényei + a kártyás nézet saját csempéi (teljesítmény, karosszéria). */
	$all_facts = isset( $c['facts'] ) ? (array) $c['facts'] : array();
	if ( ! empty( $ld['hp'] ) ) {
		$all_facts[] = array( 'field' => 'power', 'code' => '', 'value' => number_format( (int) $ld['hp'], 0, ',', ' ' ) . ' LE', 'label' => $sw2_text['power'] );
	}
	if ( ! empty( $c['body']['label'] ) ) {
		$all_facts[] = array( 'field' => 'body', 'code' => $c['body']['code'], 'value' => $c['body']['label'], 'label' => $sw2_text['body'] );
	}
	if ( $all_facts ) {
		$facts = '';
		foreach ( $all_facts as $f ) {
			$facts .= $sw2_el( 'li', 'fact', '<strong>' . esc_html( $f['value'] ) . '</strong><small>' . esc_html( $f['label'] ) . '</small>', ' data-field="' . esc_attr( $f['field'] ) . '" data-code="' . esc_attr( $f['code'] ) . '"' );
		}
		$i .= $sw2_el( 'ul', 'facts', $facts );
	}
	/* Kiegészítő sor (PC fekvő fotónál): karosszéria, szín, műszaki, tulajdonos. */
	$extras = isset( $c['extras'] ) ? (array) $c['extras'] : array();
	if ( ! empty( $c['body']['label'] ) ) {
		array_unshift( $extras, array( 'field' => 'body', 'value' => $c['body']['label'], 'label' => $sw2_text['body'] ) );
	}
	if ( $extras ) {
		$ex = '';
		foreach ( $extras as $f ) {
			$ex .= $sw2_el( 'li', 'extra', '<strong>' . esc_html( $f['value'] ) . '</strong><small>' . esc_html( $f['label'] ) . '</small>', ' data-field="' . esc_attr( $f['field'] ) . '"' );
		}
		$i .= $sw2_el( 'ul', 'extras', $ex );
	}
	/* PC: „Hirdetés megtekintése” — CSAK ez visz a hirdetés adatlapjára (rendes oldalbetöltés). */
	$i .= $sw2_el( 'a', 'msg', esc_html( $sw2_text['message'] ), ' href="' . esc_url( $c['url'] ) . '"' );
	$i .= $sw2_el( 'a', 'cta', esc_html( $sw2_text['details'] ), ' href="' . esc_url( $c['url'] ) . '"' );

	return $sw2_el( 'div', 'media', $m ) . $sw2_el( 'div', 'info', $i );
};

$sw2_first = $sw2['first'];
$sw2_photo = $sw2_first && ! empty( $sw2_first['card']['photos'] ) ? $sw2_first['card']['photos'][0] : null;
$sw2_style = '';
if ( $sw2_first ) {
	$sw2_style = '--kmae-ambient:' . ( $sw2_photo && $sw2_photo['ambient'] ? $sw2_photo['ambient'] : 'transparent' )
		. ';--kmae-lqip:' . ( $sw2_photo && $sw2_photo['lqip'] ? 'url("' . $sw2_photo['lqip'] . '")' : 'none' )
		. ';--kmae-bg:' . ( $sw2_photo && ( $sw2_photo['m'] || $sw2_photo['s'] ) ? 'url("' . esc_url( $sw2_photo['m'] ? $sw2_photo['m'] : $sw2_photo['s'] ) . '")' : 'none' )
		. ( $sw2_photo && ! empty( $sw2_photo['subject'] ) && 4 === count( $sw2_photo['subject'] ) ? vsprintf( ';--kmae-sx0:%s;--kmae-sy0:%s;--kmae-sx1:%s;--kmae-sy1:%s', array_map( 'floatval', $sw2_photo['subject'] ) ) : '' )
		. ( $sw2_photo && ! empty( $sw2_photo['w'] ) && ! empty( $sw2_photo['h'] ) ? ';--kmae-ar:' . number_format( $sw2_photo['w'] / $sw2_photo['h'], 4, '.', '' ) : '' );
}

$sw2_data = array(
	'cfg'      => array(
		'feedUrl'       => rest_url( KMAE_Browse_Feed::REST_NS . '/feed' ),
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'loggedIn'      => is_user_logged_in(),
		'nonce'         => is_user_logged_in() ? wp_create_nonce( 'kmae_browse_pass' ) : '',
		'favoriteNonce' => is_user_logged_in() ? wp_create_nonce( 'kmae_favorite_add' ) : '',
		'reportNonce'   => is_user_logged_in() ? wp_create_nonce( 'kmae_report_nonce' ) : '',
		'loginUrl'      => KMAE_Auth::login_url( KMAE_Pages::browse_page_url() ),
		't'             => $sw2_text,
	),
	'feed'     => $sw2['feed'],
	'pages'    => (object) $sw2['pages'],
	'me'       => $sw2['me'],
	'base_ids' => $sw2['base_ids'],
);
?>
<div class="kmae-sw2" id="kmae-sw2" data-kmae-sw2>
	<div class="kmae-sw2-stack" data-kmae-sw2-stack>
		<?php for ( $sw2_slot = 0; $sw2_slot < 3; $sw2_slot++ ) : ?>
			<?php if ( 0 === $sw2_slot && $sw2_first ) : ?>
				<article class="kmae-sw2-card" data-kmae-sw2-card data-role="current" data-id="<?php echo esc_attr( $sw2_first['id'] ); ?>" data-favorite="<?php echo $sw2['me'] && in_array( $sw2_first['id'], $sw2['me']['favorites'], true ) ? '1' : '0'; ?>" data-reserved="<?php echo $sw2_first['reserved'] ? '1' : '0'; ?>" data-tone="<?php echo esc_attr( $sw2_photo ? $sw2_photo['tone'] : '' ); ?>" data-photo-index="0" data-photo-count="<?php echo esc_attr( count( (array) $sw2_first['card']['photos'] ) ); ?>" style="<?php echo esc_attr( $sw2_style ); ?>"><?php echo $sw2_card( $sw2_first ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a $sw2_card escape-el. ?></article>
			<?php else : ?>
				<article class="kmae-sw2-card" data-kmae-sw2-card data-role="idle"></article>
			<?php endif; ?>
		<?php endfor; ?>
		<p class="kmae-sw2-end" data-kmae-sw2-end hidden><?php esc_html_e( 'Elérted a találatok végét.', 'ki-mit-adna-erte' ); ?></p>
	</div>
	<div class="kmae-sw2-nav">
		<button type="button" class="kmae-sw2-prev" data-kmae-sw2-prev hidden><?php esc_html_e( 'Előző', 'ki-mit-adna-erte' ); ?></button>
		<?php if ( is_user_logged_in() ) : ?>
			<button type="button" class="kmae-sw2-pass" data-kmae-sw2-pass><?php esc_html_e( 'Nem érdekel', 'ki-mit-adna-erte' ); ?></button>
		<?php endif; ?>
		<button type="button" class="kmae-sw2-next" data-kmae-sw2-next><?php esc_html_e( 'Következő', 'ki-mit-adna-erte' ); ?></button>
	</div>
	<div class="kmae-sw2-bar">
		<?php /* Előző / következő hirdetés pirula (PC): kis kép + cím, a swipe-v2.js peek() tölti ki, kattintásra lapoz. */ ?>
		<?php foreach ( array( -1 => __( 'Előző hirdetés', 'ki-mit-adna-erte' ), 1 => __( 'Következő hirdetés', 'ki-mit-adna-erte' ) ) as $sw2_dir => $sw2_label ) : ?>
			<button type="button" class="kmae-sw2-peek kmae-sw2-peek--<?php echo $sw2_dir < 0 ? 'prev' : 'next'; ?>" data-kmae-sw2-peek="<?php echo (int) $sw2_dir; ?>" hidden>
				<span class="kmae-sw2-peek__img" aria-hidden="true"><img alt="" decoding="async" hidden></span>
				<span class="kmae-sw2-peek__text"><small><?php echo esc_html( $sw2_label ); ?></small><strong data-kmae-sw2-peek-title></strong></span>
			</button>
		<?php endforeach; ?>
		<?php /* Jelentés: az aktuális hirdetésre / annak látott képére (KMAE_Reports::photo_reasons), AJAX: kmae_report_listing. */ ?>
		<?php if ( is_user_logged_in() ) : ?>
			<button type="button" class="kmae-sw2-report" data-kmae-sw2-report><?php esc_html_e( 'Jelentés', 'ki-mit-adna-erte' ); ?></button>
		<?php else : ?>
			<a class="kmae-sw2-report" href="<?php echo esc_url( KMAE_Auth::login_url( KMAE_Pages::browse_page_url() ) ); ?>"><?php esc_html_e( 'Jelentés', 'ki-mit-adna-erte' ); ?></a>
		<?php endif; ?>
	</div>
	<?php /* Adatlap a színpad alatt (swipe-v2.js openSheet): a fotó / adatlap link / Enter ide tölti a hirdetés
		oldalának .kmae-single-listing részét; lapozáskor bezárul. Alul a „felfelé” gomb zárja. */ ?>
	<section class="kmae-sw2-sheet" id="kmae-sw2-sheet" data-kmae-sw2-sheet aria-label="<?php esc_attr_e( 'Adatlap', 'ki-mit-adna-erte' ); ?>" hidden>
		<div class="kmae-sw2-sheet__body" data-kmae-sw2-sheet-body></div>
		<div class="kmae-sw2-sheet__foot">
			<button type="button" class="kmae-sw2-sheet__close" data-kmae-sw2-sheet-close aria-label="<?php esc_attr_e( 'Adatlap bezárása', 'ki-mit-adna-erte' ); ?>">
				<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 15 6-6 6 6"/></svg>
				<span class="kmae-sw2-sheet__tip"><?php esc_html_e( 'Adatlap bezárása', 'ki-mit-adna-erte' ); ?></span>
			</button>
		</div>
	</section>
	<?php if ( is_user_logged_in() ) : ?>
		<dialog class="kmae-sw2-rep" id="kmae-sw2-rep" aria-labelledby="kmae-sw2-rep-title" data-kmae-sw2-rep>
			<form class="kmae-sw2-rep__form" method="dialog" data-kmae-sw2-rep-form>
				<div class="kmae-sw2-rep__head">
					<h2 class="kmae-sw2-rep__title" id="kmae-sw2-rep-title"><?php esc_html_e( 'Jelentés', 'ki-mit-adna-erte' ); ?></h2>
					<p class="kmae-sw2-rep__lead"><span data-kmae-sw2-rep-car></span> — <?php esc_html_e( 'a bejelentést mi nézzük át, az eladó nem látja, ki küldte.', 'ki-mit-adna-erte' ); ?></p>
					<button type="button" class="kmae-sw2-rep__x" data-kmae-sw2-rep-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>"></button>
				</div>
				<fieldset class="kmae-sw2-rep__group" data-kmae-sw2-rep-photo>
					<legend class="kmae-sw2-rep__label"><?php esc_html_e( 'A képpel kapcsolatos', 'ki-mit-adna-erte' ); ?> <small data-kmae-sw2-rep-photo-n></small></legend>
					<?php foreach ( KMAE_Reports::photo_reasons() as $sw2_rk => $sw2_rl ) : ?>
						<label class="kmae-sw2-rep__reason"><input type="radio" name="reason" value="<?php echo esc_attr( $sw2_rk ); ?>" required> <span><?php echo esc_html( $sw2_rl ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>
				<fieldset class="kmae-sw2-rep__group">
					<legend class="kmae-sw2-rep__label"><?php esc_html_e( 'A hirdetéssel kapcsolatos', 'ki-mit-adna-erte' ); ?></legend>
					<?php foreach ( KMAE_Reports::reasons( KMAE_Reports::CONTEXT_LISTING ) as $sw2_rk => $sw2_rl ) : ?>
						<label class="kmae-sw2-rep__reason<?php echo KMAE_Reports::REASON_OTHER === $sw2_rk ? ' kmae-sw2-rep__reason--other' : ''; ?>"><input type="radio" name="reason" value="<?php echo esc_attr( $sw2_rk ); ?>" required> <span><?php echo esc_html( $sw2_rl ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>
				<label class="kmae-sw2-rep__label" for="kmae-sw2-rep-details"><?php esc_html_e( 'Miért jelented be?', 'ki-mit-adna-erte' ); ?></label>
				<textarea class="kmae-sw2-rep__details" id="kmae-sw2-rep-details" name="details" rows="3" maxlength="<?php echo esc_attr( (string) KMAE_Reports::DETAILS_MAX_LENGTH ); ?>" placeholder="<?php esc_attr_e( 'Írd le röviden, mi a baj. A „Más” választásakor kötelező.', 'ki-mit-adna-erte' ); ?>"></textarea>
				<p class="kmae-sw2-rep__msg" data-kmae-sw2-rep-msg role="status" hidden></p>
				<div class="kmae-sw2-rep__actions">
					<button type="button" class="kmae-sw2-rep__cancel" data-kmae-sw2-rep-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
					<button type="submit" class="kmae-sw2-rep__submit"><?php esc_html_e( 'Bejelentés küldése', 'ki-mit-adna-erte' ); ?></button>
				</div>
			</form>
		</dialog>
	<?php endif; ?>
	<script type="application/json" id="kmae-sw2-data"><?php echo wp_json_encode( $sw2_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
</div>
