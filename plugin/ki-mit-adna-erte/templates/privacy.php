<?php
/**
 * Adatkezelési tájékoztató (GDPR 13–14. cikk).
 *
 * A verzió, a hatálybalépés és az adatkezelő adatai: KMAE_Legal (közös az ÁSZF-fel).
 * A tartalom a tényleges működést írja le — új adatkezelésnél ezt is frissíteni kell.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$op      = KMAE_Legal::operator();
$missing = current_user_can( 'manage_options' ) ? KMAE_Legal::missing_fields() : array();
$terms   = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'feltetelek' ) : home_url( '/feltetelek/' );
$contact = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'kapcsolat' ) : home_url( '/kapcsolat/' );

$toc = array(
	'adatkezelo'   => 'Az adatkezelő',
	'alapelvek'    => 'Alapelvek és jogszabályok',
	'kezelesek'    => 'Milyen adatot, miért, meddig kezelünk',
	'lathatosag'   => 'Ki látja az adataidat',
	'feldolgozok'  => 'Adatfeldolgozók és külső szolgáltatások',
	'harmadik'     => 'Adattovábbítás az EGT-n kívülre',
	'sutik'        => 'Sütik és helyi tárolás',
	'biztonsag'    => 'Adatbiztonság',
	'jogok'        => 'Jogaid',
	'jogorvoslat'  => 'Panasz és jogorvoslat',
	'kiskoruak'    => 'Kiskorúak',
	'modositas'    => 'A tájékoztató módosítása',
);
$n = 0;
$h = static function ( $id ) use ( $toc, &$n ) {
	++$n;
	return '<h2 id="' . esc_attr( $id ) . '"><span class="kmae-legal__num">' . (int) $n . '.</span> ' . esc_html( $toc[ $id ] ) . '</h2>';
};

/* Adatkezelések: [cél, adatok, jogalap, időtartam]. */
$rows = array(
	array(
		'Regisztráció és felhasználói fiók',
		'Név (megjelenő név, keresztnév), e-mail cím, jelszó (csak titkosított, visszafejthetetlen formában), fióktípus (magánszemély / cég), telefonszám, az e-mail cím megerősítésének adatai.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig.',
	),
	array(
		'Céges fiók',
		'Cégnév, telephely (megye, település, cím).',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig.',
	),
	array(
		'Bejelentkezés Google-fiókkal (ha ezt választod)',
		'A Google által átadott név, e-mail cím és Google-azonosító. A Google-jelszavadhoz nem férünk hozzá.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig.',
	),
	array(
		'Az ÁSZF elfogadásának igazolása',
		'Az elfogadott változat száma és az elfogadás időpontja.',
		'Jogos érdek (a szerződéskötés igazolása) — GDPR 6. cikk (1) f)',
		'A fiók megszüntetését követő 5 évig (polgári jogi elévülési idő).',
	),
	array(
		'Hirdetések',
		'Járműadatok, leírás, fotók, megye és település, ár és értékesítési mód, rejtett minimum (csak te látod), vállalások. Félbehagyott hirdetésnél a piszkozat.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A hirdetés vagy a fiók törléséig; a piszkozat a közzétételéig vagy törléséig.',
	),
	array(
		'Forgalmi engedély beolvasása (ha használod)',
		'A forgalmi engedélyről feltöltött 1–2 fotó, kizárólag a beolvasás idejére. Ebből csak a jármű adatait olvassuk ki: a műszaki adatokat (márka, modell, gyártási év, hengerűrtartalom, teljesítmény, üzemanyag, váltó, műszaki érvényesség), valamint az adásvételi szerződéshez a rendszámot, az alvázszámot és a forgalmi engedély számát. Az oldal elmenti az adásvételi szerződéshez szükséges járműadatokat, a személyes adataid nélkül. A fotón szereplő személyes adatokat (név, lakcím) nem olvassuk ki és nem tároljuk.',
		'Hozzájárulás — GDPR 6. cikk (1) a)',
		'A fotókat nem tároljuk: a beolvasás végén azonnal törlődnek. A kiolvasott műszaki adatok csak akkor kerülnek a hirdetésbe, ha jóváhagyod őket. A rendszám, az alvázszám és a forgalmi engedély száma nem jelenik meg a hirdetésben: csak te látod, az adásvételi szerződés kitöltésekor, és a hirdetés törléséig őrizzük. A napi beolvasási keret számlálója 1 napig él.',
	),
	array(
		'Beszélgetés',
		'Üzenetek és időpontjaik. A beszélgetésben küldött képek.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig. A beszélgetésben küldött képek a küldéstől számított 48 óra után automatikusan törlődnek.',
	),
	array(
		'Böngészés és kedvencek',
		'Kedvencnek jelölt, valamint már megnézett vagy átugrott hirdetések.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig.',
	),
	array(
		'E-mail értesítések',
		'E-mail cím, az értesítési beállításaid.',
		'Szerződés teljesítése — GDPR 6. cikk (1) b)',
		'A fiók megszüntetéséig; a nem kötelező értesítések a beállításokban kikapcsolhatók.',
	),
	array(
		'Böngésző-értesítés (push)',
		'A böngésződ értesítési feliratkozásának technikai adatai (végpont és titkosító kulcsok), a be- és kikapcsolás állapota.',
		'Hozzájárulás — GDPR 6. cikk (1) a)',
		'A hozzájárulás visszavonásáig (a beállításokban vagy a böngészőben kikapcsolható); a böngésző által érvénytelenített feliratkozást automatikusan töröljük.',
	),
	array(
		'Helymeghatározás a hirdetésfeladásnál (ha a gombot megnyomod)',
		'A böngésződ által átadott földrajzi koordináták, egyszeri lekérdezésre.',
		'Hozzájárulás — GDPR 6. cikk (1) a)',
		'A koordinátákat a fiókodhoz nem mentjük: csak a megye és a település kerül a hirdetésbe. A koordinátából kapott település egy névtelen gyorsítótárban legfeljebb 30 napig marad.',
	),
	array(
		'Távolság szerinti rendezés a böngészésben (ha választod)',
		'A böngésződ által átadott helyzeted, kb. 1 km-re kerekítve, valamint a választott kör sugara.',
		'Hozzájárulás — GDPR 6. cikk (1) a)',
		'Nem mentjük el: csak az adott kereséshez, a böngészési címben utazik (a szerver technikai naplóiban a szolgáltató megőrzési ideje szerint maradhat nyoma). A hirdetések településének koordinátáit az OpenStreetMap-ből kérjük le; ehhez személyes adat nem kell.',
	),
	array(
		'Kapcsolatfelvétel, panasz, bejelentés',
		'Név, e-mail cím, az üzenet tartalma.',
		'Jogos érdek (a megkeresés megválaszolása) — GDPR 6. cikk (1) f); panasznál jogi kötelezettség — 6. cikk (1) c)',
		'A megkeresés lezárásától számított 1 évig; fogyasztói panasznál 3 évig (fogyasztóvédelmi törvény).',
	),
	array(
		'Biztonság és visszaélés-megelőzés',
		'IP-cím (a regisztráció és a kapcsolatfelvétel túlterhelés elleni korlátozásához, csak kódolt formában), a szerver és a tartalomszolgáltató hálózat technikai naplói.',
		'Jogos érdek (a szolgáltatás és a felhasználók védelme) — GDPR 6. cikk (1) f)',
		'A korlátozási adatok legfeljebb 1 óráig; a tárhely- és hálózati naplók a szolgáltatók saját megőrzési ideje szerint.',
	),
	array(
		'A működés sebességének mérése',
		'A lassú oldalbetöltések technikai adatai: időpont, az oldal címe, a felhasználói azonosító, a betöltési idő.',
		'Jogos érdek (a szolgáltatás hibamentes működése) — GDPR 6. cikk (1) f)',
		'Csak a legutóbbi 60 bejegyzés marad meg, a régebbiek automatikusan törlődnek.',
	),
);
?>
<article class="kmae-legal">
	<?php if ( ! empty( $missing ) ) : ?>
		<div class="kmae-legal__admin" role="note">
			<strong>Csak adminisztrátor látja — kitöltendő adatok:</strong>
			<ul>
				<?php foreach ( $missing as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>Az adatok helye: <code>includes/class-kmae-legal.php</code> → <code>operator()</code>. A végleges szöveget ügyvéddel vagy adatvédelmi szakemberrel érdemes átnézetni.</p>
		</div>
	<?php endif; ?>

	<header class="kmae-legal__head">
		<p class="kmae-legal__eyebrow">Albira</p>
		<h1>Adatkezelési tájékoztató</h1>
		<p class="kmae-legal__meta">
			Verzió: <?php echo esc_html( KMAE_Legal::TERMS_VERSION ); ?> · Hatályos: <?php echo esc_html( KMAE_Legal::TERMS_EFFECTIVE ); ?>
		</p>
	</header>

	<nav class="kmae-legal__toc" aria-label="Tartalomjegyzék">
		<p class="kmae-legal__toc-title">Tartalom</p>
		<ol>
			<?php foreach ( $toc as $id => $title ) : ?>
				<li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></a></li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<section>
		<?php echo $h( 'adatkezelo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Ez a tájékoztató elmondja, hogy az Albira (<?php echo esc_html( $op['site'] ); ?>) használata során milyen személyes adataidat, milyen célból, milyen jogalapon és meddig kezeljük, kik férhetnek hozzájuk, és milyen jogaid vannak.</p>
		<dl class="kmae-legal__facts">
			<div><dt>Adatkezelő</dt><dd><?php echo esc_html( $op['name'] ); ?> (<?php echo esc_html( $op['form'] ); ?>)</dd></div>
			<div><dt>Székhely</dt><dd><?php echo esc_html( $op['seat'] ); ?></dd></div>
			<div><dt>Nyilvántartási szám</dt><dd><?php echo esc_html( $op['reg_number'] ); ?></dd></div>
			<div><dt>Adószám</dt><dd><?php echo esc_html( $op['tax_number'] ); ?></dd></div>
			<div><dt>E-mail (adatvédelmi ügyekben is)</dt><dd><?php echo esc_html( $op['email'] ); ?></dd></div>
			<div><dt>Telefon</dt><dd><?php echo esc_html( $op['phone'] ); ?></dd></div>
		</dl>
		<p>Adatvédelmi tisztviselő kijelölésére az adatkezelő nem köteles. Kérdéseiddel a fenti e-mail címen vagy a <a href="<?php echo esc_url( $contact ); ?>">Kapcsolat</a> oldalon fordulhatsz hozzánk.</p>
	</section>

	<section>
		<?php echo $h( 'alapelvek' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az adatkezelés az Európai Parlament és a Tanács (EU) 2016/679 rendelete (<strong>GDPR</strong>), valamint az információs önrendelkezési jogról és az információszabadságról szóló 2011. évi CXII. törvény (<strong>Infotv.</strong>) szerint történik.</p>
		<ul>
			<li>Csak annyi adatot kérünk, amennyi a szolgáltatáshoz szükséges, és csak addig őrizzük, ameddig szükséges.</li>
			<li>Az adataidat <strong>nem adjuk el</strong>, és nem használjuk reklámcélú profilalkotásra. Az Albirán nincs hirdetési vagy látogatottságmérő (analitikai) követés.</li>
			<li>Automatizált döntéshozatal, amely rád nézve joghatással járna, nincs.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'kezelesek' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="kmae-legal__table-wrap">
			<table class="kmae-legal__table">
				<thead>
					<tr>
						<th scope="col">Cél</th>
						<th scope="col">Kezelt adatok</th>
						<th scope="col">Jogalap</th>
						<th scope="col">Időtartam</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
							<td><?php echo esc_html( $row[1] ); ?></td>
							<td><?php echo esc_html( $row[2] ); ?></td>
							<td><?php echo esc_html( $row[3] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p>A telefonszám és a jelszó megadása a regisztrációhoz szükséges; enélkül a fiók nem hozható létre. Minden más, fent „hozzájárulás” jogalapú adatkezelés önkéntes.</p>
		<p>A fiók megszüntetésekor az adataidat töröljük vagy visszafordíthatatlanul anonimizáljuk, kivéve azokat, amelyeket jogszabály vagy a fenti, megjelölt megőrzési idő alapján tovább kell őriznünk.</p>
		<p><strong>Fotók:</strong> a hirdetés fotóin más személyek vagy a rendszámtábla is látszhat. Javasoljuk, hogy a feltöltés előtt ezeket takard ki; a feltöltött tartalomért a feltöltő felel (lásd az <a href="<?php echo esc_url( $terms ); ?>">ÁSZF</a>-et).</p>
	</section>

	<section>
		<?php echo $h( 'lathatosag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li><strong>Mindenki</strong> (bejelentkezés nélkül is): a közzétett hirdetés adatai, fotói, a megye és a település, valamint az eladó megjelenő neve. A rejtett minimum soha nem nyilvános.</li>
			<li><strong>A beszélgetőpartnered:</strong> a megjelenő neved és az üzeneteid.</li>
			<li><strong>Az üzemeltető:</strong> a szolgáltatás működtetéséhez, a bejelentések kivizsgálásához és a visszaélések megelőzéséhez szükséges mértékben.</li>
			<li><strong>Hatóságok:</strong> csak jogszabályon alapuló, kötelező megkeresés esetén.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'feldolgozok' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li><strong>Tárhely:</strong> <?php echo esc_html( $op['host'] ); ?> (<?php echo esc_html( $op['host_seat'] ); ?>) — a weboldal és az adatbázis tárolása Magyarországon.</li>
			<li><strong>Tartalomszolgáltató hálózat és védelem:</strong> Cloudflare, Inc. (101 Townsend St., San Francisco, CA 94107, USA) — az oldal gyorsítása és a támadások kiszűrése; ehhez a forgalom (IP-cím, böngészőadatok) rajta halad át.</li>
			<li><strong>E-mail küldés:</strong> <?php echo esc_html( $op['mail_provider'] ); ?> — a rendszerlevelek és értesítések kézbesítése.</li>
			<li><strong>Google-bejelentkezés:</strong> Google Ireland Limited (Gordon House, Barrow Street, Dublin 4, Írország) — csak ha a Google-fiókkal való belépést választod.</li>
			<li><strong>Helymeghatározás:</strong> OpenStreetMap Foundation (Nominatim szolgáltatás, Egyesült Királyság) — csak ha a „Jelenlegi helyzetem megadása” gombot megnyomod; a koordinátákat a szerverünk küldi el, a nevedet és a fiókodat nem.</li>
			<li><strong>Forgalmi engedély beolvasása:</strong> OpenAI (OpenAI Ireland Ltd., 1st Floor, The Liffey Trust Centre, 117–126 Sheriff Street Upper, Dublin 1, Írország, illetve OpenAI, L.L.C., USA) — csak ha a forgalmi beolvasását választod. A fotót a szerverünk küldi el, a nevedet és a fiókodat nem; a szolgáltató a kérést nem használja modelltanításra, és legfeljebb a visszaélések kiszűréséhez szükséges ideig (legfeljebb 30 napig) őrizheti meg.</li>
			<li><strong>Böngésző-értesítések kézbesítése:</strong> a böngésződ gyártójának értesítési szolgáltatása (pl. Google, Mozilla, Apple, Microsoft) — csak ha bekapcsolod; az értesítés tartalma végponttól végpontig titkosítva halad át rajtuk.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'harmadik' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>A Cloudflare, az OpenAI és egyes böngésző-értesítési szolgáltatók az Egyesült Államokban is kezelhetnek adatot. Ennek garanciája az EU–USA adatvédelmi keretrendszer (Data Privacy Framework) szerinti megfelelőségi határozat, illetve az Európai Bizottság általános adatvédelmi kikötései. Az Egyesült Királyságba (OpenStreetMap) történő továbbításra az Európai Bizottság megfelelőségi határozata vonatkozik.</p>
	</section>

	<section>
		<?php echo $h( 'sutik' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Albira csak a működéshez <strong>feltétlenül szükséges</strong> sütiket használ, ezért ezekhez nem kell hozzájárulás. Reklám- vagy követő sütit nem használunk.</p>
		<ul>
			<li><strong>Bejelentkezési sütik</strong> (<code>wordpress_logged_in_…</code>, <code>wordpress_sec_…</code>, <code>wordpress_test_cookie</code>): a bejelentkezés fenntartása; kijelentkezéskor vagy legfeljebb 14 nap után törlődnek.</li>
			<li><strong>Megtekintésszámláló süti</strong> (<code>kmae_v_…</code>): hogy egy hirdetés megnézése egy napon belül csak egyszer számítson; 1 nap után törlődik.</li>
			<li><strong>Védelmi süti</strong> (pl. <code>__cf_bm</code>): a Cloudflare a robotforgalom kiszűréséhez helyezheti el; legfeljebb 30 percig él.</li>
			<li><strong>Helyi tárolás a böngészőben:</strong> kényelmi állapotok (pl. hogy a böngésző-értesítést már felajánlottuk-e) — a böngésző adatainak törlésével eltávolítható.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'biztonsag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az oldal kizárólag titkosított (HTTPS) kapcsolaton érhető el. A jelszavakat visszafejthetetlen formában tároljuk, a hozzáférést jogosultságokhoz kötjük, a túlterheléses és automatizált visszaéléseket korlátozzuk. Ha mégis adatvédelmi incidens történne, azt a jogszabály szerint 72 órán belül bejelentjük a hatóságnak, és — ha az kockázattal jár rád nézve — téged is értesítünk.</p>
	</section>

	<section>
		<?php echo $h( 'jogok' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li><strong>Hozzáférés:</strong> tájékoztatást kérhetsz arról, milyen adatodat kezeljük, és másolatot kérhetsz róla.</li>
			<li><strong>Helyesbítés:</strong> a pontatlan adatot kijavíttathatod — a legtöbbet a Profil és a Beállítások oldalon magad is módosíthatod.</li>
			<li><strong>Törlés:</strong> kérheted az adataid törlését, illetve a fiókod megszüntetését.</li>
			<li><strong>Korlátozás:</strong> kérheted, hogy bizonyos ideig csak tároljuk, de ne használjuk az adatot (pl. vitatott pontosság esetén).</li>
			<li><strong>Adathordozhatóság:</strong> a megadott adataidat géppel olvasható formában kikérheted.</li>
			<li><strong>Tiltakozás:</strong> a jogos érdeken alapuló adatkezelés ellen tiltakozhatsz.</li>
			<li><strong>Hozzájárulás visszavonása:</strong> bármikor, a korábbi adatkezelés jogszerűségét nem érintve (pl. a böngésző-értesítés kikapcsolásával).</li>
		</ul>
		<p>Kérésedet a(z) <?php echo esc_html( $op['email'] ); ?> címre küldd. Legfeljebb egy hónapon belül válaszolunk (indokolt esetben ez további két hónappal meghosszabbodhat, erről értesítünk). A kérés teljesítése díjmentes. Ha kétség merül fel a kilétedet illetően, azonosításhoz további adatot kérhetünk.</p>
	</section>

	<section>
		<?php echo $h( 'jogorvoslat' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Ha úgy érzed, hogy az adataidat jogellenesen kezeljük, kérjük, először fordulj hozzánk — igyekszünk gyorsan rendezni. Emellett panaszt tehetsz a felügyeleti hatóságnál:</p>
		<dl class="kmae-legal__facts">
			<div><dt>Hatóság</dt><dd>Nemzeti Adatvédelmi és Információszabadság Hatóság (NAIH)</dd></div>
			<div><dt>Cím</dt><dd>1055 Budapest, Falk Miksa utca 9–11.</dd></div>
			<div><dt>Postacím</dt><dd>1363 Budapest, Pf. 9.</dd></div>
			<div><dt>Telefon</dt><dd>+36 1 391 1400</dd></div>
			<div><dt>E-mail</dt><dd>ugyfelszolgalat@naih.hu</dd></div>
			<div><dt>Honlap</dt><dd>www.naih.hu</dd></div>
		</dl>
		<p>Jogaid megsértése esetén bírósághoz is fordulhatsz; a pert — választásod szerint — a lakóhelyed vagy tartózkodási helyed szerinti törvényszék előtt is megindíthatod.</p>
	</section>

	<section>
		<?php echo $h( 'kiskoruak' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Albirán csak 18. életévét betöltött személy regisztrálhat. Ha tudomásunkra jut, hogy kiskorú adatait kezeljük, azokat haladéktalanul töröljük.</p>
	</section>

	<section>
		<?php echo $h( 'modositas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Ha az adatkezelés érdemben változik, ezt a tájékoztatót frissítjük, és a változásról az Albirán vagy e-mailben értesítünk. A mindenkor hatályos változat ezen az oldalon érhető el. A szolgáltatás használatának szabályait az <a href="<?php echo esc_url( $terms ); ?>">ÁSZF és Felhasználási feltételek</a> tartalmazza.</p>
	</section>
</article>
