<?php
/**
 * Biztonság és bizalom — mit tesz az Albira, mit tehetsz te, hogyan jelenthetsz.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sec_system = array(
	array(
		'title' => __( 'E-mail-megerősítés', 'ki-mit-adna-erte' ),
		'text'  => __( 'Hirdetést csak megerősített e-mail-címmel adhatsz fel, és csak így indíthatsz beszélgetést. Böngészni a megerősítés nélkül is lehet.', 'ki-mit-adna-erte' ),
		'icon'  => 'mail',
	),
	array(
		'title' => __( 'Automatikus ellenőrzés', 'ki-mit-adna-erte' ),
		'text'  => __( 'Feladás előtt a rendszer ellenőrzi, hogy minden kötelező adat megvan-e, a leírás legalább 80 karakter-e, és a szükséges fotók feltöltődtek-e. A címben és a leírásban nem szerepelhet telefonszám vagy e-mail-cím.', 'ki-mit-adna-erte' ),
		'icon'  => 'shield',
	),
	array(
		'title' => __( 'Beszélgetés az Albirán belül', 'ki-mit-adna-erte' ),
		'text'  => __( 'Az érdeklődők a rendszer üzenetfelületén keresnek meg. A telefonszámod nem jelenik meg a hirdetéseken.', 'ki-mit-adna-erte' ),
		'icon'  => 'chat',
	),
	array(
		'title' => __( 'Adataid védelme', 'ki-mit-adna-erte' ),
		'text'  => __( 'Az oldal csak titkosított (HTTPS) kapcsolaton érhető el, a jelszavakat visszafejthetetlen formában tároljuk. Jelszó- vagy e-mail-cím változtatásáról e-mailben értesítünk.', 'ki-mit-adna-erte' ),
		'icon'  => 'lock',
	),
);

$sec_tips = array(
	array(
		'title' => __( 'Ne fizess előleget ismeretlennek', 'ki-mit-adna-erte' ),
		'text'  => __( 'Foglalót vagy előleget csak akkor adj, ha már láttad az autót és az iratait. A Feltételek szerint tilos másokat Albirán kívüli előleg- vagy foglaló-fizetésre rábírni.', 'ki-mit-adna-erte' ),
		'icon'  => 'wallet',
	),
	array(
		'title' => __( 'Találkozz nappal, nyilvános helyen', 'ki-mit-adna-erte' ),
		'text'  => __( 'Válassz forgalmas, nappali helyszínt, és ha lehet, vigyél magaddal valakit. Az Oktatás oldal részletesebb tanácsokat ad.', 'ki-mit-adna-erte' ),
		'icon'  => 'sun',
	),
	array(
		'title' => __( 'Nézd át az iratokat', 'ki-mit-adna-erte' ),
		'text'  => __( 'Vesd össze a forgalmi adatait (rendszám, alvázszám) a hirdetéssel. A beszélgetésben a vevői ellenőrzőlista segít végigmenni a fontos pontokon.', 'ki-mit-adna-erte' ),
		'icon'  => 'check',
	),
	array(
		'title' => __( 'Kössetek szerződést', 'ki-mit-adna-erte' ),
		'text'  => __( 'A beszélgetésből elkészítheted az adásvételi szerződést. Ebben rögzíthetitek a fizetés módját és idejét is.', 'ki-mit-adna-erte' ),
		'icon'  => 'file',
	),
	array(
		'title' => __( 'Jelezz, ha gyanús', 'ki-mit-adna-erte' ),
		'text'  => __( 'Ha csalásnak vagy zaklatásnak tűnik valami, jelentsd a hirdetést vagy a beszélgetést. Hogyan? Lentebb olvashatsz róla.', 'ki-mit-adna-erte' ),
		'icon'  => 'flag',
	),
);

$sec_reports = array(
	array(
		'title' => __( 'Hirdetés bejelentése', 'ki-mit-adna-erte' ),
		'text'  => __( 'A hirdetés oldalsávjában találod a „Hirdetés bejelentése” gombot. Kiválasztod az okot, és röviden leírod, mi a gond. A bejelentést az Albira csapata átnézi.', 'ki-mit-adna-erte' ),
		'icon'  => 'flag',
	),
	array(
		'title' => __( 'Beszélgetés bejelentése és tiltása', 'ki-mit-adna-erte' ),
		'text'  => __( 'A beszélgetés jobb oszlopában bejelentheted a másik felet, vagy letilthatod. A tiltásról nem kap értesítést, a tiltás óta küldött üzenetei pedig neked nem jelennek meg. A tiltást bármikor feloldhatod.', 'ki-mit-adna-erte' ),
		'icon'  => 'ban',
	),
);

/**
 * Inline icons for the security page (self-contained, no dependency on other templates).
 *
 * @param string $name Icon key.
 */
if ( ! function_exists( 'kmae_sec_icon' ) ) {
	function kmae_sec_icon( $name ) {
		$icons = array(
			'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
			'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3z"/><path d="m9 12 2 2 4-4"/>',
			'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
			'lock'   => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'wallet' => '<rect x="2" y="6" width="20" height="14" rx="2"/><path d="M16 13h2"/><path d="M2 10h20"/>',
			'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
			'check'  => '<path d="M20 6 9 17l-5-5"/>',
			'file'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
			'flag'   => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
			'ban'    => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
		);

		$path = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['shield'];

		printf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
			$path // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG paths.
		);
	}
}
?>
<div class="kmae-sec-page">
	<header class="kmae-sec-page__intro">
		<p class="kmae-sec-page__eyebrow"><?php esc_html_e( 'BIZTONSÁG ÉS BIZALOM', 'ki-mit-adna-erte' ); ?></p>
		<h1 class="kmae-sec-page__title"><?php esc_html_e( 'Biztonságos adás-vétel,', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'amiben a rendszer és te is része vagy.', 'ki-mit-adna-erte' ); ?></span></h1>
		<p class="kmae-sec-page__lead"><?php esc_html_e( 'Az Albirán a hirdetések átvizsgálásával, a bejelentésekkel és a tiltással szűrjük a visszaéléseket. A vételár fizetése és az autó átadása viszont mindig a két fél között, az Albirán kívül történik, ezért itt azt is megmutatjuk, mit tehetsz te.', 'ki-mit-adna-erte' ); ?></p>
		<nav class="kmae-sec-page__jump" aria-label="<?php esc_attr_e( 'Oldal szakaszai', 'ki-mit-adna-erte' ); ?>">
			<a href="#kmae-sec-rendszer"><?php esc_html_e( 'A rendszer védelme', 'ki-mit-adna-erte' ); ?></a>
			<a href="#kmae-sec-te"><?php esc_html_e( 'Amit te is megtehetsz', 'ki-mit-adna-erte' ); ?></a>
			<a href="#kmae-sec-jelentes"><?php esc_html_e( 'Ha valami nem stimmel', 'ki-mit-adna-erte' ); ?></a>
		</nav>
	</header>

	<section class="kmae-sec-block" id="kmae-sec-rendszer" aria-labelledby="kmae-sec-rendszer-title">
		<header class="kmae-sec-block__header">
			<p class="kmae-sec-block__eyebrow"><?php esc_html_e( 'A RENDSZER VÉDELME', 'ki-mit-adna-erte' ); ?></p>
			<h2 id="kmae-sec-rendszer-title" class="kmae-sec-block__title"><?php esc_html_e( 'Amit az Albira megtesz', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-sec-block__subtitle"><?php esc_html_e( 'Több réteg véd: a fiókodtól a hirdetésen át a beszélgetésig.', 'ki-mit-adna-erte' ); ?></p>
		</header>
		<ul class="kmae-sec-cards kmae-sec-cards--system">
			<?php foreach ( $sec_system as $item ) : ?>
				<li class="kmae-sec-card">
					<span class="kmae-sec-card__icon" aria-hidden="true"><?php kmae_sec_icon( $item['icon'] ); ?></span>
					<strong class="kmae-sec-card__label"><?php echo esc_html( $item['title'] ); ?></strong>
					<p class="kmae-sec-card__text"><?php echo esc_html( $item['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="kmae-sec-block" id="kmae-sec-te" aria-labelledby="kmae-sec-te-title">
		<header class="kmae-sec-block__header">
			<p class="kmae-sec-block__eyebrow"><?php esc_html_e( 'A TE SZEREPED', 'ki-mit-adna-erte' ); ?></p>
			<h2 id="kmae-sec-te-title" class="kmae-sec-block__title"><?php esc_html_e( 'Amit te is megtehetsz', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-sec-block__subtitle"><?php esc_html_e( 'Néhány egyszerű szabállyal a legtöbb kockázat kiszűrhető.', 'ki-mit-adna-erte' ); ?></p>
		</header>
		<ol class="kmae-sec-tips">
			<?php foreach ( $sec_tips as $index => $tip ) : ?>
				<li class="kmae-sec-tips__item">
					<span class="kmae-sec-tips__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
					<span class="kmae-sec-tips__icon" aria-hidden="true"><?php kmae_sec_icon( $tip['icon'] ); ?></span>
					<span class="kmae-sec-tips__copy">
						<strong class="kmae-sec-tips__label"><?php echo esc_html( $tip['title'] ); ?></strong>
						<span class="kmae-sec-tips__text"><?php echo esc_html( $tip['text'] ); ?></span>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="kmae-sec-block" id="kmae-sec-jelentes" aria-labelledby="kmae-sec-jelentes-title">
		<header class="kmae-sec-block__header">
			<p class="kmae-sec-block__eyebrow"><?php esc_html_e( 'BEJELENTÉS ÉS TILTÁS', 'ki-mit-adna-erte' ); ?></p>
			<h2 id="kmae-sec-jelentes-title" class="kmae-sec-block__title"><?php esc_html_e( 'Ha valami nem stimmel', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-sec-block__subtitle"><?php esc_html_e( 'Két eszköz áll a rendelkezésedre: a bejelentés és a tiltás.', 'ki-mit-adna-erte' ); ?></p>
		</header>
		<ul class="kmae-sec-cards kmae-sec-cards--reports">
			<?php foreach ( $sec_reports as $item ) : ?>
				<li class="kmae-sec-card">
					<span class="kmae-sec-card__icon" aria-hidden="true"><?php kmae_sec_icon( $item['icon'] ); ?></span>
					<strong class="kmae-sec-card__label"><?php echo esc_html( $item['title'] ); ?></strong>
					<p class="kmae-sec-card__text"><?php echo esc_html( $item['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<footer class="kmae-sec-footer">
		<nav class="kmae-sec-footer__links" aria-label="<?php esc_attr_e( 'Kapcsolódó oldalak', 'ki-mit-adna-erte' ); ?>">
			<a class="kmae-sec-footer__link" href="<?php echo esc_url( KMAE_Pages::url( 'oktatas' ) ); ?>"><?php esc_html_e( 'Oktatás', 'ki-mit-adna-erte' ); ?></a>
			<a class="kmae-sec-footer__link" href="<?php echo esc_url( KMAE_Pages::url( 'feltetelek' ) ); ?>"><?php esc_html_e( 'Feltételek', 'ki-mit-adna-erte' ); ?></a>
			<a class="kmae-sec-footer__link" href="<?php echo esc_url( KMAE_Pages::url( 'adatvedelem' ) ); ?>"><?php esc_html_e( 'Adatvédelem', 'ki-mit-adna-erte' ); ?></a>
			<a class="kmae-sec-footer__link" href="<?php echo esc_url( KMAE_Pages::url( 'kapcsolat' ) ); ?>"><?php esc_html_e( 'Kérdésed van? Írj nekünk', 'ki-mit-adna-erte' ); ?></a>
		</nav>
		<p class="kmae-sec-footer__note"><?php esc_html_e( 'Ez az oldal tájékoztatás. A pontos szabályokat a Feltételek és az Adatvédelem oldal tartalmazza.', 'ki-mit-adna-erte' ); ?></p>
	</footer>
</div>
