<?php
/**
 * Simple mode — guest landing.
 * Hero (élő termék-bemutató) + három eladási mód + eladói út + vevőknek
 * + biztonság + záró CTA. Stílus: assets/css/landing-home.css,
 * mozgás: assets/js/landing-home.js.
 * Telefonon (≤ 1024 px) saját nézet: hős + élő hirdetés-kártya
 * (.kmae-lpm-live), majd .kmae-lpm (eladási módok leírással, „Így működik”
 * eladó / vevő váltóval, biztonság); a PC-s szekciók ott rejtve vannak.
 * Alsó menüsor: Kezdő / Böngészés / Eladás / GYIK / Kapcsolat.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$browse_url = kmae_page_url( 'hirdetesek' );
$create_url = kmae_page_url( 'belepes' );
$car_main   = kmae_media_url( 'landing_car_main' );

$grad = static function ( $text ) {
	return '<span class="kmae-lp-grad">' . esc_html( $text ) . '</span>';
};

$icons = array(
	'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
	'sliders'  => '<line x1="4" x2="4" y1="21" y2="14"/><line x1="4" x2="4" y1="10" y2="3"/><line x1="12" x2="12" y1="21" y2="12"/><line x1="12" x2="12" y1="8" y2="3"/><line x1="20" x2="20" y1="21" y2="16"/><line x1="20" x2="20" y1="12" y2="3"/><line x1="2" x2="6" y1="14" y2="14"/><line x1="10" x2="14" y1="8" y2="8"/><line x1="18" x2="22" y1="16" y2="16"/>',
	'chat'     => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8L21 21l-5.2-1.4A8.5 8.5 0 1 1 21 11.5Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/>',
	'camera'   => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3.5"/>',
	'upload'   => '<path d="M4 14.9A7 7 0 1 1 15.7 8h1.8a4.5 4.5 0 0 1 2.5 8.2"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/>',
	'check'    => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m9 11 3 3L22 4"/>',
	'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
	'doc'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
	'flag'     => '<path d="M4 22V4"/><path d="M4 4h12l-2 4 2 4H4"/>',
	'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
	'list'     => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="m3 6 1 1 2-2M3 12l1 1 2-2M3 18l1 1 2-2"/>',
	'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
	'block'    => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
	'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
	'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
	'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
	'plus'     => '<path d="M12 5v14M5 12h14"/>',
	'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
);

$icon = static function ( $key, $size = 24 ) use ( $icons ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $key ] . '</svg>';
};

$kses_svg = array(
	'span'     => array( 'class' => true ),
	'svg'      => array( 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true ),
	'path'     => array( 'd' => true ),
	'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true ),
	'line'     => array( 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true ),
	'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ),
);

$modes = array(
	array(
		'key'      => 'fixed',
		'icon'     => 'tag',
		'label'    => __( 'Fix ár', 'kmae' ),
		'short'    => __( 'Nincs alku', 'kmae' ),
		'desc'     => __( 'Megadod az árat, a vevő pontosan ennyit ajánlhat meg.', 'kmae' ),
		'features' => array( __( 'Gyors és egyszerű', 'kmae' ), __( 'Egyértelmű a vevőknek', 'kmae' ), __( 'Nincs alku', 'kmae' ) ),
	),
	array(
		'key'      => 'guide',
		'icon'     => 'sliders',
		'label'    => __( 'Irányár', 'kmae' ),
		'short'    => __( 'Lehet alkudni', 'kmae' ),
		'desc'     => __( 'Megadod az irányárat, de a vevő ettől eltérő összeget is ajánlhat.', 'kmae' ),
		'features' => array( __( 'Rugalmas értékesítés', 'kmae' ), __( 'Több érdeklődőt vonzhat', 'kmae' ), __( 'Lehetőség az alkura', 'kmae' ) ),
	),
	array(
		'key'      => 'open',
		'icon'     => 'chat',
		'label'    => __( 'Ki mit adna érte?', 'kmae' ),
		'short'    => __( 'A vevők ajánlanak', 'kmae' ),
		'desc'     => __( 'Nem adsz meg árat: a vevők mondják meg, mennyit adnának érte.', 'kmae' ),
		'features' => array( __( 'Valós piaci visszajelzés', 'kmae' ), __( 'Több ajánlatot kaphatsz', 'kmae' ), __( 'Te döntöd el, melyik a legjobb', 'kmae' ) ),
	),
);

$seller_steps = array(
	array( 'camera', __( 'Fotózd le a forgalmit', 'kmae' ), __( 'Az autó adatai maguktól kitöltődnek.', 'kmae' ) ),
	array( 'upload', __( 'Add fel a hirdetést', 'kmae' ), __( 'Képek, ár vagy ár nélkül: pár perc, ingyen.', 'kmae' ) ),
	array( 'chat', __( 'Jönnek az ajánlatok', 'kmae' ), __( 'Mind a chatben érkezik, egy helyen.', 'kmae' ) ),
	array( 'check', __( 'Elfogadod a legjobbat', 'kmae' ), __( 'Az autód „Foglalt” lesz, más nem viheti el.', 'kmae' ) ),
	array( 'doc', __( 'Kész a szerződés', 'kmae' ), __( 'Az adásvételi előre kitöltve vár.', 'kmae' ) ),
	array( 'lock', __( 'Lezárod', 'kmae' ), __( 'Egy kattintás, és az autó eladva.', 'kmae' ) ),
);

$buyer_points = array(
	array( 'search', __( 'Részletes szűrők', 'kmae' ), __( 'Gyorsan megtalálod, ami neked kell.', 'kmae' ) ),
	array( 'chat', __( 'Ajánlat a chatben', 'kmae' ), __( 'Közvetlenül az eladóval beszélsz.', 'kmae' ) ),
	array( 'list', __( '„Mire figyelj” ellenőrzőlista', 'kmae' ), __( 'Letölthető lista a megtekintéshez.', 'kmae' ) ),
	array( 'doc', __( 'Kész adásvételi szerződés', 'kmae' ), __( 'Az elfogadott ajánlat után előre kitöltve.', 'kmae' ) ),
);

$trust = array(
	array( 'shield', __( 'Ellenőrzött hirdetések', 'kmae' ), __( 'A gyanús vagy hiányos hirdetéseket kiszűrjük.', 'kmae' ) ),
	array( 'flag', __( 'Bejelentés', 'kmae' ), __( 'Ha valami nem stimmel, egy kattintással jelezheted.', 'kmae' ) ),
	array( 'block', __( 'Tiltás', 'kmae' ), __( 'Aki zavar, azt letilthatod, és többé nem ér el.', 'kmae' ) ),
	array( 'gift', __( 'Ingyenes hirdetés', 'kmae' ), __( '60 napig él, és egy kattintással meghosszabbítod.', 'kmae' ) ),
);

$browse_cars = array(
	array( kmae_media_url( 'landing_car_a' ), __( 'Szedán · 2017', 'kmae' ), 'fixed', __( '4 290 000 Ft', 'kmae' ) ),
	array( kmae_media_url( 'landing_car_b' ), __( 'Kombi · 2018', 'kmae' ), 'guide', __( '~ 3 750 000 Ft', 'kmae' ) ),
	array( kmae_media_url( 'landing_car_c' ), __( 'Terepjáró · 2016', 'kmae' ), 'open', __( 'Ki mit adna érte?', 'kmae' ) ),
);
?>
<div class="kmae-landing kmae-lp">

	<section class="kmae-lp-hero" aria-labelledby="kmae-lp-hero-title">
		<div class="kmae-lp-hero__orb kmae-lp-hero__orb--blue" aria-hidden="true"></div>
		<div class="kmae-lp-hero__orb kmae-lp-hero__orb--orange" aria-hidden="true"></div>

		<div class="kmae-lp-wrap kmae-lp-hero__grid">
			<div class="kmae-lp-hero__copy">
				<p class="kmae-lp-eyebrow"><?php esc_html_e( 'Használtautó-piac, új szabályokkal', 'kmae' ); ?></p>
				<p class="kmae-lpm-pill"><?php esc_html_e( 'Ingyenes használtautó-piac', 'kmae' ); ?></p>
				<h1 id="kmae-lp-hero-title" class="kmae-lp-hero__title">
					<span><?php esc_html_e( 'Add el az autód', 'kmae' ); ?></span>
					<span><?php echo wp_kses( $grad( __( 'úgy, ahogy te akarod.', 'kmae' ) ), $kses_svg ); ?></span>
				</h1>
				<p class="kmae-lp-hero__lead">
					<?php esc_html_e( 'Fix ár, irányár, vagy ár nélkül, és megnézed, ki mit adna érte. Az ajánlatok a chatben érkeznek, a szerződés pedig magától elkészül.', 'kmae' ); ?>
				</p>

				<div class="kmae-lp-actions">
					<a class="kmae-lp-btn kmae-lp-btn--sell" href="<?php echo esc_url( $create_url ); ?>">
						<?php esc_html_e( 'Hirdetést feladok', 'kmae' ); ?>
						<?php echo wp_kses( $icon( 'arrow', 18 ), $kses_svg ); ?>
					</a>
					<a class="kmae-lp-btn kmae-lp-btn--buy" href="<?php echo esc_url( $browse_url ); ?>">
						<?php esc_html_e( 'Autót keresek', 'kmae' ); ?>
						<?php echo wp_kses( $icon( 'arrow', 18 ), $kses_svg ); ?>
					</a>
				</div>

				<ul class="kmae-lp-hero__chips">
					<li><?php echo wp_kses( $icon( 'gift', 16 ), $kses_svg ); ?><?php esc_html_e( 'Ingyenes hirdetés', 'kmae' ); ?></li>
					<li><?php echo wp_kses( $icon( 'camera', 16 ), $kses_svg ); ?><?php esc_html_e( 'Kitöltés forgalmi-fotóból', 'kmae' ); ?></li>
					<li><?php echo wp_kses( $icon( 'doc', 16 ), $kses_svg ); ?><?php esc_html_e( 'Kész adásvételi szerződés', 'kmae' ); ?></li>
				</ul>
			</div>

			<?php /* Élő bemutató: egy hirdetés, amelyre ajánlatok érkeznek, majd
				elfogadás → Foglalt → kész szerződés. Csak dísz (aria-hidden). */ ?>
			<div class="kmae-lp-stage" aria-hidden="true">
				<div class="kmae-lp-stage__ring"></div>

				<article class="kmae-lp-demo-card">
					<div class="kmae-lp-demo-card__photo">
						<img src="<?php echo esc_url( $car_main ); ?>" alt="" width="250" height="113" decoding="async" fetchpriority="high" />
						<span class="kmae-lp-demo-card__mode"><?php esc_html_e( 'Ki mit adna érte?', 'kmae' ); ?></span>
						<span class="kmae-lp-demo-card__reserved"><?php esc_html_e( 'Foglalt', 'kmae' ); ?></span>
					</div>
					<div class="kmae-lp-demo-card__body">
						<p class="kmae-lp-demo-card__title"><?php esc_html_e( 'SUV · 2019 · 2.0 benzin', 'kmae' ); ?></p>
						<ul class="kmae-lp-demo-card__specs">
							<li><?php esc_html_e( '148 000 km', 'kmae' ); ?></li>
							<li><?php esc_html_e( 'Automata', 'kmae' ); ?></li>
							<li><?php esc_html_e( 'Budapest', 'kmae' ); ?></li>
						</ul>
					</div>
				</article>

				<div class="kmae-lp-demo-chat">
					<p class="kmae-lp-demo-chat__head">
						<span class="kmae-lp-demo-chat__dot"></span>
						<?php esc_html_e( 'Üzenetek', 'kmae' ); ?>
					</p>
					<p class="kmae-lp-demo-chat__msg kmae-lp-demo-chat__msg--in kmae-lp-seq-1"><?php esc_html_e( 'Szép autó! Megnézhetném hétvégén?', 'kmae' ); ?></p>
					<p class="kmae-lp-demo-chat__offer kmae-lp-seq-2">
						<span><?php esc_html_e( 'Ajánlat', 'kmae' ); ?></span>
						<strong><?php esc_html_e( '5 650 000 Ft', 'kmae' ); ?></strong>
					</p>
					<p class="kmae-lp-demo-chat__offer kmae-lp-demo-chat__offer--best kmae-lp-seq-3">
						<span><?php esc_html_e( 'Új ajánlat', 'kmae' ); ?></span>
						<strong><?php esc_html_e( '5 900 000 Ft', 'kmae' ); ?></strong>
					</p>
					<p class="kmae-lp-demo-chat__msg kmae-lp-demo-chat__msg--out kmae-lp-seq-4"><?php esc_html_e( 'Elfogadom, áll az alku!', 'kmae' ); ?></p>
				</div>

				<div class="kmae-lp-demo-toast kmae-lp-seq-6">
					<span class="kmae-lp-demo-toast__icon"><?php echo wp_kses( $icon( 'doc', 18 ), $kses_svg ); ?></span>
					<span>
						<strong><?php esc_html_e( 'Adásvételi szerződés', 'kmae' ); ?></strong>
						<?php esc_html_e( 'előre kitöltve, letölthető', 'kmae' ); ?>
					</span>
				</div>
			</div>

			<?php /* Telefonos élő hirdetés-kártya: az ajánlatok egymás után
				pörögnek be. Csak dísz, PC-n rejtve (landing-home.css). */ ?>
			<div class="kmae-lpm-live" aria-hidden="true">
				<div class="kmae-lpm-live__photo">
					<img src="<?php echo esc_url( $car_main ); ?>" alt="" width="250" height="113" decoding="async" />
					<span class="kmae-lpm-live__mode"><?php esc_html_e( 'Ki mit adna érte?', 'kmae' ); ?></span>
					<span class="kmae-lpm-live__live"><?php esc_html_e( 'Élő', 'kmae' ); ?></span>
				</div>
				<div class="kmae-lpm-live__body">
					<div class="kmae-lpm-live__info">
						<p class="kmae-lpm-live__title"><?php esc_html_e( 'SUV · 2019 · 2.0 benzin', 'kmae' ); ?></p>
						<p class="kmae-lpm-live__specs"><?php esc_html_e( '148 000 km · Automata · Budapest', 'kmae' ); ?></p>
					</div>
					<div class="kmae-lpm-live__offer">
						<span class="kmae-lpm-live__offer-label"><?php esc_html_e( 'Új ajánlat', 'kmae' ); ?></span>
						<span class="kmae-lpm-live__offer-track">
							<span class="kmae-lpm-live__offer-list">
								<span><?php esc_html_e( '5 400 000 Ft', 'kmae' ); ?></span>
								<span><?php esc_html_e( '5 650 000 Ft', 'kmae' ); ?></span>
								<span class="is-top"><?php esc_html_e( '5 900 000 Ft', 'kmae' ); ?></span>
								<span><?php esc_html_e( '5 400 000 Ft', 'kmae' ); ?></span>
							</span>
						</span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php /* Telefonos nézet (≤ 1024 px): eladási módok leírással, „Így működik”
		eladó / vevő váltóval, biztonság. PC-n rejtve (landing-home.css). */ ?>
	<section class="kmae-lpm" aria-labelledby="kmae-lpm-modes-title">
		<div class="kmae-lp-wrap kmae-lpm__stack">
			<div class="kmae-lpm-card kmae-lpm-card--modes" data-lp-reveal>
				<p class="kmae-lpm-kick"><?php esc_html_e( 'Háromféle eladás', 'kmae' ); ?></p>
				<h2 id="kmae-lpm-modes-title" class="kmae-lpm-title">
					<?php esc_html_e( 'Te döntöd el,', 'kmae' ); ?>
					<?php echo wp_kses( $grad( __( 'hogyan adod el.', 'kmae' ) ), $kses_svg ); ?>
				</h2>
				<ul class="kmae-lpm-modes">
					<?php foreach ( $modes as $mode ) : ?>
						<li class="kmae-lpm-mode kmae-lpm-mode--<?php echo esc_attr( $mode['key'] ); ?>">
							<span class="kmae-lpm-mode__icon"><?php echo wp_kses( $icon( $mode['icon'], 20 ), $kses_svg ); ?></span>
							<span class="kmae-lpm-mode__text">
								<span class="kmae-lpm-mode__head">
									<strong><?php echo esc_html( $mode['label'] ); ?></strong>
									<em><?php echo esc_html( $mode['short'] ); ?></em>
								</span>
								<span class="kmae-lpm-mode__desc"><?php echo esc_html( $mode['desc'] ); ?></span>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="kmae-lpm-card kmae-lpm-how" data-lp-reveal data-lpm-tabs style="--lp-delay: 100ms;">
				<p class="kmae-lpm-kick"><?php esc_html_e( 'Így működik', 'kmae' ); ?></p>
				<h2 class="kmae-lpm-title">
					<?php esc_html_e( 'A hirdetéstől', 'kmae' ); ?>
					<?php echo wp_kses( $grad( __( 'a kész szerződésig.', 'kmae' ) ), $kses_svg ); ?>
				</h2>
				<div class="kmae-lpm-switch" role="tablist" hidden>
					<button type="button" class="kmae-lpm-switch__btn is-on" role="tab" id="kmae-lpm-tab-seller" aria-controls="kmae-lpm-seller" aria-selected="true"><?php esc_html_e( 'Eladóként', 'kmae' ); ?></button>
					<button type="button" class="kmae-lpm-switch__btn" role="tab" id="kmae-lpm-tab-buyer" aria-controls="kmae-lpm-buyer" aria-selected="false"><?php esc_html_e( 'Vevőként', 'kmae' ); ?></button>
				</div>

				<div id="kmae-lpm-seller" class="kmae-lpm-panel kmae-lpm-panel--seller" role="tabpanel" aria-labelledby="kmae-lpm-tab-seller">
					<p class="kmae-lpm-panel__label"><?php esc_html_e( 'Eladóként', 'kmae' ); ?></p>
					<ol class="kmae-lpm-steps">
						<?php foreach ( $seller_steps as $i => $step ) : ?>
							<li class="kmae-lpm-step">
								<span class="kmae-lpm-step__node"><?php echo wp_kses( $icon( $step[0], 18 ), $kses_svg ); ?></span>
								<span class="kmae-lpm-step__text">
									<strong><span class="kmae-lpm-step__num"><?php echo (int) $i + 1; ?>.</span> <?php echo esc_html( $step[1] ); ?></strong>
									<span><?php echo esc_html( $step[2] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>

				<div id="kmae-lpm-buyer" class="kmae-lpm-panel kmae-lpm-panel--buyer" role="tabpanel" aria-labelledby="kmae-lpm-tab-buyer">
					<p class="kmae-lpm-panel__label"><?php esc_html_e( 'Vevőként', 'kmae' ); ?></p>
					<p class="kmae-lpm-panel__lead"><?php esc_html_e( 'Ha nincs ár, te mondod meg, mennyit adnál érte. Irányárnál alkudhatsz, fix árnál pedig rögtön jelezheted, hogy megvennéd.', 'kmae' ); ?></p>
					<ul class="kmae-lpm-points">
						<?php foreach ( $buyer_points as $point ) : ?>
							<li class="kmae-lpm-point">
								<span class="kmae-lpm-step__node"><?php echo wp_kses( $icon( $point[0], 18 ), $kses_svg ); ?></span>
								<span class="kmae-lpm-step__text">
									<strong><?php echo esc_html( $point[1] ); ?></strong>
									<span><?php echo esc_html( $point[2] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<div class="kmae-lpm-card kmae-lpm-safe" data-lp-reveal style="--lp-delay: 100ms;">
				<p class="kmae-lpm-kick"><?php esc_html_e( 'Biztonság', 'kmae' ); ?></p>
				<h2 class="kmae-lpm-title">
					<?php esc_html_e( 'Nyugodt üzlet,', 'kmae' ); ?>
					<?php echo wp_kses( $grad( __( 'mindkét félnek.', 'kmae' ) ), $kses_svg ); ?>
				</h2>
				<ul class="kmae-lpm-safe__list">
					<?php foreach ( $trust as $item ) : ?>
						<li class="kmae-lpm-safe__item">
							<span class="kmae-lpm-safe__icon"><?php echo wp_kses( $icon( $item[0], 18 ), $kses_svg ); ?></span>
							<strong><?php echo esc_html( $item[1] ); ?></strong>
							<span><?php echo esc_html( $item[2] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>

	<section class="kmae-lp-section kmae-lp-modes" aria-labelledby="kmae-lp-modes-title">
		<div class="kmae-lp-wrap">
			<header class="kmae-lp-head" data-lp-reveal>
				<p class="kmae-lp-eyebrow"><?php esc_html_e( 'Háromféle eladás', 'kmae' ); ?></p>
				<h2 id="kmae-lp-modes-title" class="kmae-lp-title">
					<?php esc_html_e( 'Te döntöd el,', 'kmae' ); ?>
					<?php echo wp_kses( $grad( __( 'hogyan adod el.', 'kmae' ) ), $kses_svg ); ?>
				</h2>
				<p class="kmae-lp-lead"><?php esc_html_e( 'A hirdetés feladásakor választasz, a vevők pedig pontosan látják, mire számíthatnak.', 'kmae' ); ?></p>
			</header>

			<div class="kmae-lp-modes__grid">
				<?php foreach ( $modes as $i => $mode ) : ?>
					<article class="kmae-lp-mode kmae-lp-mode--<?php echo esc_attr( $mode['key'] ); ?>" data-lp-reveal style="--lp-delay: <?php echo (int) $i * 120; ?>ms;">
						<?php if ( 'open' === $mode['key'] ) : ?>
							<span class="kmae-lp-mode__ribbon"><?php esc_html_e( 'Kíváncsi vagy az értékére?', 'kmae' ); ?></span>
						<?php endif; ?>
						<div class="kmae-lp-mode__visual" aria-hidden="true">
							<?php if ( 'fixed' === $mode['key'] ) : ?>
								<span class="kmae-lp-mode__price"><?php esc_html_e( '5 990 000 Ft', 'kmae' ); ?></span>
								<span class="kmae-lp-mode__note"><?php esc_html_e( 'Ennyi, és kész.', 'kmae' ); ?></span>
							<?php elseif ( 'guide' === $mode['key'] ) : ?>
								<span class="kmae-lp-mode__price"><?php esc_html_e( '~ 5 990 000 Ft', 'kmae' ); ?></span>
								<span class="kmae-lp-mode__range"><span></span></span>
							<?php else : ?>
								<span class="kmae-lp-mode__bids">
									<span style="--w: 62%;"><?php esc_html_e( '5,4 M', 'kmae' ); ?></span>
									<span style="--w: 78%;"><?php esc_html_e( '5,7 M', 'kmae' ); ?></span>
									<span class="is-top" style="--w: 94%;"><?php esc_html_e( '6,1 M', 'kmae' ); ?></span>
								</span>
							<?php endif; ?>
						</div>
						<div class="kmae-lp-mode__top">
							<span class="kmae-lp-icon"><?php echo wp_kses( $icon( $mode['icon'] ), $kses_svg ); ?></span>
							<h3 class="kmae-lp-mode__label"><?php echo esc_html( $mode['label'] ); ?></h3>
						</div>
						<p class="kmae-lp-mode__desc"><?php echo esc_html( $mode['desc'] ); ?></p>
						<ul class="kmae-lp-mode__features">
							<?php foreach ( $mode['features'] as $feature ) : ?>
								<li><?php echo esc_html( $feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="kmae-lp-section kmae-lp-journey" aria-labelledby="kmae-lp-journey-title">
		<div class="kmae-lp-wrap">
			<div class="kmae-lp-panel kmae-lp-panel--blue">
				<header class="kmae-lp-head" data-lp-reveal>
					<p class="kmae-lp-eyebrow"><?php esc_html_e( 'Eladóként', 'kmae' ); ?></p>
					<h2 id="kmae-lp-journey-title" class="kmae-lp-title">
						<?php esc_html_e( 'Hat lépés, és', 'kmae' ); ?>
						<?php echo wp_kses( $grad( __( 'az autód gazdára talál.', 'kmae' ) ), $kses_svg ); ?>
					</h2>
				</header>

				<ol class="kmae-lp-journey__steps" data-lp-reveal>
					<?php foreach ( $seller_steps as $i => $step ) : ?>
						<li class="kmae-lp-journey__step" style="--lp-delay: <?php echo (int) $i * 140; ?>ms;">
							<span class="kmae-lp-journey__node">
								<?php echo wp_kses( $icon( $step[0], 26 ), $kses_svg ); ?>
								<span class="kmae-lp-journey__num"><?php echo (int) $i + 1; ?></span>
							</span>
							<h3 class="kmae-lp-journey__title"><?php echo esc_html( $step[1] ); ?></h3>
							<p class="kmae-lp-journey__desc"><?php echo esc_html( $step[2] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>

				<div class="kmae-lp-journey__foot" data-lp-reveal>
					<a class="kmae-lp-btn kmae-lp-btn--sell" href="<?php echo esc_url( $create_url ); ?>">
						<?php esc_html_e( 'Hirdetést feladok', 'kmae' ); ?>
						<?php echo wp_kses( $icon( 'arrow', 18 ), $kses_svg ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<section class="kmae-lp-section kmae-lp-buyer" aria-labelledby="kmae-lp-buyer-title">
		<div class="kmae-lp-wrap">
			<div class="kmae-lp-panel kmae-lp-panel--orange kmae-lp-buyer__grid">
				<div class="kmae-lp-buyer__copy" data-lp-reveal>
					<p class="kmae-lp-eyebrow"><?php esc_html_e( 'Vevőként', 'kmae' ); ?></p>
					<h2 id="kmae-lp-buyer-title" class="kmae-lp-title">
						<?php esc_html_e( 'Nem csak böngészel.', 'kmae' ); ?>
						<?php echo wp_kses( $grad( __( 'Ajánlatot is tehetsz.', 'kmae' ) ), $kses_svg ); ?>
					</h2>
					<p class="kmae-lp-lead"><?php esc_html_e( 'Ha nincs ár, te mondod meg, mennyit adnál érte. Irányárnál alkudhatsz, fix árnál pedig rögtön jelezheted, hogy megvennéd.', 'kmae' ); ?></p>

					<ul class="kmae-lp-buyer__points">
						<?php foreach ( $buyer_points as $point ) : ?>
							<li>
								<span class="kmae-lp-icon"><?php echo wp_kses( $icon( $point[0], 22 ), $kses_svg ); ?></span>
								<span>
									<strong><?php echo esc_html( $point[1] ); ?></strong>
									<?php echo esc_html( $point[2] ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>

					<a class="kmae-lp-btn kmae-lp-btn--buy" href="<?php echo esc_url( $browse_url ); ?>">
						<?php esc_html_e( 'Autók böngészése', 'kmae' ); ?>
						<?php echo wp_kses( $icon( 'arrow', 18 ), $kses_svg ); ?>
					</a>
				</div>

				<div class="kmae-lp-buyer__cars" aria-hidden="true">
					<?php foreach ( $browse_cars as $i => $car ) : ?>
						<div class="kmae-lp-car kmae-lp-car--<?php echo esc_attr( $car[2] ); ?>" data-lp-reveal style="--lp-delay: <?php echo (int) $i * 150; ?>ms;">
							<span class="kmae-lp-car__photo"><img src="<?php echo esc_url( $car[0] ); ?>" alt="" loading="lazy" decoding="async" /></span>
							<span class="kmae-lp-car__name"><?php echo esc_html( $car[1] ); ?></span>
							<span class="kmae-lp-car__price"><?php echo esc_html( $car[3] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="kmae-lp-section kmae-lp-trust" aria-labelledby="kmae-lp-trust-title">
		<div class="kmae-lp-wrap">
			<header class="kmae-lp-head" data-lp-reveal>
				<p class="kmae-lp-eyebrow"><?php esc_html_e( 'Biztonság', 'kmae' ); ?></p>
				<h2 id="kmae-lp-trust-title" class="kmae-lp-title">
					<?php esc_html_e( 'Nyugodt üzlet,', 'kmae' ); ?>
					<?php echo wp_kses( $grad( __( 'mindkét félnek.', 'kmae' ) ), $kses_svg ); ?>
				</h2>
			</header>

			<div class="kmae-lp-trust__grid">
				<?php foreach ( $trust as $i => $item ) : ?>
					<article class="kmae-lp-trust__item" data-lp-reveal style="--lp-delay: <?php echo (int) $i * 100; ?>ms;">
						<span class="kmae-lp-icon"><?php echo wp_kses( $icon( $item[0] ), $kses_svg ); ?></span>
						<h3><?php echo esc_html( $item[1] ); ?></h3>
						<p><?php echo esc_html( $item[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* Alsó menüsor: footer.php → template-parts/guest-tabbar.php */ ?>

</div>
