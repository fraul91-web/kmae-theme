<?php
/**
 * Általános Szerződési Feltételek (ÁSZF) és Felhasználási Feltételek — egy dokumentum.
 *
 * A verzió, a hatálybalépés és az üzemeltető adatai: KMAE_Legal.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$op      = KMAE_Legal::operator();
$missing = current_user_can( 'manage_options' ) ? KMAE_Legal::missing_fields() : array();
$privacy = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'adatvedelem' ) : home_url( '/adatvedelem/' );
$contact = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::url( 'kapcsolat' ) : home_url( '/kapcsolat/' );

$toc = array(
	'bevezetes'    => 'Bevezetés és a dokumentum jellege',
	'uzemelteto'   => 'Az üzemeltető adatai',
	'fogalmak'     => 'Fogalmak',
	'szerep'       => 'Az Albira szerepe',
	'fiok'         => 'Regisztráció és felhasználói fiók',
	'dijak'        => 'Díjak',
	'hirdetes'     => 'Hirdetések',
	'ertekesites'  => 'Értékesítési módok és rejtett minimum',
	'vallalasok'   => 'Vállalások',
	'beszelgetes'  => 'Beszélgetés és üzenetek',
	'ertesitesek'  => 'Értesítések és helymeghatározás',
	'tilalmak'     => 'Tiltott magatartások',
	'bejelentes'   => 'Jogsértő tartalom bejelentése',
	'felfuggesztes' => 'Korlátozás, felfüggesztés, fiók megszüntetése',
	'uzleti'       => 'Cégként regisztrált felhasználók',
	'felelosseg'   => 'Felelősség',
	'szellemi'     => 'Szellemi tulajdon és a feltöltött tartalom',
	'adatvedelem'  => 'Adatkezelés',
	'panasz'       => 'Panaszkezelés és jogorvoslat',
	'modositas'    => 'A feltételek módosítása',
	'jog'          => 'Irányadó jog',
);
$n = 0;
$h = static function ( $id ) use ( $toc, &$n ) {
	++$n;
	return '<h2 id="' . esc_attr( $id ) . '"><span class="kmae-legal__num">' . (int) $n . '.</span> ' . esc_html( $toc[ $id ] ) . '</h2>';
};
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
			<p>Az adatok helye: <code>includes/class-kmae-legal.php</code> → <code>operator()</code>. A végleges szöveget ügyvéddel érdemes átnézetni.</p>
		</div>
	<?php endif; ?>

	<header class="kmae-legal__head">
		<p class="kmae-legal__eyebrow">Albira</p>
		<h1>Általános Szerződési Feltételek (ÁSZF) és Felhasználási Feltételek</h1>
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
		<?php echo $h( 'bevezetes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Ez a dokumentum egyszerre az Albira <strong>Általános Szerződési Feltételei (ÁSZF)</strong> és <strong>Felhasználási Feltételei</strong>. Szabályozza az Albira online platform (<?php echo esc_html( $op['site'] ); ?>, a továbbiakban: Albira vagy Platform) használatát, valamint az Üzemeltető és a Felhasználók közötti jogviszonyt.</p>
		<p>A regisztrációval — a jelölőnégyzet bepipálásával — a Felhasználó kijelenti, hogy a jelen feltételeket megismerte, megértette és elfogadja. Az elfogadás időpontját és az elfogadott változat számát a rendszer rögzíti. Aki a feltételeket nem fogadja el, nem regisztrálhat.</p>
		<p>A szerződés nyelve magyar. A szerződés elektronikus úton jön létre, nem minősül írásba foglalt szerződésnek, az Üzemeltető nem iktatja; a mindenkor hatályos szöveg ezen az oldalon érhető el, és bármikor elmenthető, kinyomtatható.</p>
	</section>

	<section>
		<?php echo $h( 'uzemelteto' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<dl class="kmae-legal__facts">
			<div><dt>Üzemeltető</dt><dd><?php echo esc_html( $op['name'] ); ?> (<?php echo esc_html( $op['form'] ); ?>)</dd></div>
			<div><dt>Székhely</dt><dd><?php echo esc_html( $op['seat'] ); ?></dd></div>
			<div><dt>Nyilvántartási szám</dt><dd><?php echo esc_html( $op['reg_number'] ); ?></dd></div>
			<div><dt>Adószám</dt><dd><?php echo esc_html( $op['tax_number'] ); ?></dd></div>
			<div><dt>E-mail</dt><dd><?php echo esc_html( $op['email'] ); ?></dd></div>
			<div><dt>Telefon</dt><dd><?php echo esc_html( $op['phone'] ); ?></dd></div>
			<div><dt>Tárhelyszolgáltató</dt><dd><?php echo esc_html( $op['host'] ); ?>, <?php echo esc_html( $op['host_seat'] ); ?>, <?php echo esc_html( $op['host_email'] ); ?></dd></div>
		</dl>
		<p>Kapcsolatfelvétel: a fenti e-mail címen vagy a <a href="<?php echo esc_url( $contact ); ?>">Kapcsolat</a> oldalon.</p>
	</section>

	<section>
		<?php echo $h( 'fogalmak' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li><strong>Felhasználó:</strong> aki az Albirán regisztrált. Lehet magánszemély vagy cég (üzleti felhasználó).</li>
			<li><strong>Eladó:</strong> a hirdetést feladó Felhasználó. <strong>Vevő:</strong> a hirdetés iránt érdeklődő Felhasználó.</li>
			<li><strong>Hirdetés:</strong> egy jármű eladására vonatkozó, az Eladó által közzétett tartalom (adatok, leírás, fotók, értékesítési mód).</li>
			<li><strong>Beszélgetés:</strong> a Vevő és az Eladó egy hirdetéshez tartozó üzenetváltása az Albirán.</li>
			<li><strong>Vállalás:</strong> a Hirdetéshez az Eladó által önként vállalt többletszolgáltatás (pl. 24 órán belüli elvitel).</li>
			<li><strong>Rejtett minimum:</strong> az Eladó által megadott, csak számára látható legalacsonyabb elfogadható összeg.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'szerep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Albira <strong>online közvetítő platform</strong>: felületet biztosít ahhoz, hogy a Felhasználók járműveket hirdessenek és egymással kapcsolatba lépjenek.</p>
		<ul>
			<li>Az Üzemeltető a Felhasználók közötti adásvételnek <strong>nem részese</strong>: nem eladó, nem vevő, nem ügynök és nem képviselő.</li>
			<li>Az Üzemeltető <strong>nem ellenőrzi</strong> a járművek tényleges állapotát, műszaki adatait, okmányait, tulajdonjogát, terheltségét, és nem garantálja a Hirdetések valóságtartalmát.</li>
			<li>Az Albira <strong>nem kezel pénzt</strong>: a vételár fizetése, a jármű átadása és a tulajdonjog átírása kizárólag a felek között, az Albirán kívül történik.</li>
		</ul>
		<p>Javasoljuk, hogy a jármű átvétele előtt ellenőrizd az okmányokat és az autó állapotát (szükség esetén független szakemberrel), és kössetek írásbeli adásvételi szerződést.</p>
	</section>

	<section>
		<?php echo $h( 'fiok' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>Regisztrálni az tud, aki <strong>betöltötte a 18. életévét</strong> és cselekvőképes, illetve cég nevében az, aki a cég képviseletére jogosult.</li>
			<li>A regisztráció e-mail címmel és jelszóval vagy Google-fiókkal lehetséges. Az e-mail címet meg kell erősíteni; amíg ez nem történik meg, egyes funkciók (hirdetésfeladás, Beszélgetés indítása) nem érhetők el.</li>
			<li>A Felhasználó köteles <strong>valós, pontos adatokat</strong> megadni (név, telefonszám, cég esetén cégnév és telephely), és azokat naprakészen tartani.</li>
			<li>Egy személy egy fiókot használhat. A fiók nem ruházható át. A belépési adatok titokban tartása a Felhasználó felelőssége; a fiókjával végzett tevékenységért a Felhasználó felel.</li>
			<li>A fiókhoz tartozó beállításokban (e-mail értesítések, böngésző-értesítések, kiléptetés más eszközökről) a Felhasználó maga dönthet.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'dijak' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p><?php echo esc_html( $op['fees'] ); ?> Ha az Üzemeltető a jövőben díjköteles szolgáltatást vezet be, annak feltételeiről a bevezetés előtt, a jelen feltételek módosításával tájékoztat; díjköteles szolgáltatás csak a Felhasználó kifejezett hozzájárulásával vehető igénybe.</p>
	</section>

	<section>
		<?php echo $h( 'hirdetes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>Hirdetést csak olyan járműről lehet feladni, amelynek eladására a Felhasználó <strong>jogosult</strong>.</li>
			<li>A Hirdetés adatainak (márka, modell, évjárat, futásteljesítmény, állapot, műszaki érvényesség stb.) <strong>a valóságnak megfelelőnek</strong> kell lenniük. Az Eladó köteles feltüntetni minden általa ismert lényeges hibát és körülményt.</li>
			<li>A fotóknak <strong>a hirdetett járműről</strong> kell készülniük. A feltöltött képeket a rendszer automatikusan feldolgozza (méretezés, tömörítés, formátumváltás), az eredeti fájlt nem őrzi meg.</li>
			<li>A jármű adatai a forgalmi engedély fotójából is kitölthetők (önkéntes). A kiolvasást mesterséges intelligencia végzi, ezért hibázhat: a kitöltött adatokat a Felhasználó köteles átnézni, és a Hirdetés adataiért a beolvasás után is ő felel. Az oldal elmenti az adásvételi szerződéshez szükséges járműadatokat, a személyes adataid nélkül.</li>
			<li>A leírásban nem szerepelhet telefonszám, e-mail cím vagy más elérhetőség; a kapcsolattartás az Albira Beszélgetés funkcióján keresztül történik.</li>
			<li>A Hirdetéseket a rendszer automatikus szabályok alapján is ellenőrzi; a szabályokat sértő Hirdetés megjelenése megtagadható, illetve a Hirdetés módosítása kérhető.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'ertekesites' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li><strong>Fix ár:</strong> az Eladó megadja az árat.</li>
			<li><strong>Irányár:</strong> az Eladó megad egy irányárat.</li>
			<li><strong>Ki mit adna érte:</strong> az Eladó nem ad meg árat.</li>
			<li><strong>Rejtett minimum</strong> (Irányár és „Ki mit adna érte” esetén, opcionális): csak az Eladó látja.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'vallalasok' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Eladó a Hirdetéshez önkéntes Vállalásokat jelölhet meg (például: 24 órán belül elvihető, plusz kerékgarnitúra, független szerelőhöz vihető, egyéb extra). A Vállalás <strong>az Eladó kötelezettsége a Vevővel szemben</strong>; a Hirdetésben megjelenik, és az Eladó köteles azt betartani. Az Üzemeltető a Vállalások teljesítését nem garantálja és nem kényszeríti ki.</p>
	</section>

	<section>
		<?php echo $h( 'beszelgetes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>A Vevő a Hirdetésből indíthat Beszélgetést az Eladóval. A felek szabadon üzenhetnek, képet és fájlt csatolhatnak.</li>
			<li>A csatolmány legfeljebb 3 MB lehet, és <strong>48 óráig</strong> érhető el, utána automatikusan törlődik.</li>
			<li>Tilos a Beszélgetésben zaklatni, sértegetni, kéretlen tartalmat küldeni, csalásra, Albirán kívüli előleg- vagy foglaló-fizetésre rábírni a másik felet.</li>
			<li>Ha a Hirdetés törlésre kerül vagy a Beszélgetés lezárul, a Beszélgetés csak olvasható marad.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'ertesitesek' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>Az Albira a fiókkal kapcsolatos eseményekről (pl. új üzenet) <strong>e-mailt</strong> küld; az értesítések a Beállításokban egyenként kikapcsolhatók, kivéve a fiók működéséhez szükséges leveleket.</li>
			<li>A <strong>böngésző-értesítés</strong> önkéntes: a Felhasználó a böngészőjében engedélyezheti, és a Beállításokban bármikor kikapcsolhatja.</li>
			<li>A hirdetésfeladásnál a <strong>helymeghatározás</strong> önkéntes, kizárólag a megye és a település kitöltését segíti; a Hirdetésbe nem a pontos hely, hanem a település kerül.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'tilalmak' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Tilos különösen:</p>
		<ul>
			<li>valótlan, megtévesztő Hirdetést feladni, más járművéről vagy más fotóival hirdetni;</li>
			<li>lopott, jogi akadállyal érintett vagy más tulajdonában lévő járművet engedély nélkül hirdetni;</li>
			<li>csalásra, adathalászatra, előlegcsalásra irányuló tevékenység;</li>
			<li>más Felhasználó zaklatása, sértegetése, személyes adatainak jogosulatlan közzététele;</li>
			<li>a rendszer kijátszása (pl. hamis fiókok, automatizált adatgyűjtés, a működés akadályozása);</li>
			<li>jogszabályba vagy jó erkölcsbe ütköző tartalom közzététele.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'bejelentes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Ha bárki jogsértő vagy a jelen feltételekbe ütköző tartalmat észlel, azt a <a href="<?php echo esc_url( $contact ); ?>">Kapcsolat</a> oldalon vagy a(z) <?php echo esc_html( $op['email'] ); ?> címen jelentheti be. A bejelentésben meg kell jelölni a tartalom helyét (pl. a Hirdetés linkjét) és a jogsértés indokát.</p>
		<p>Az Üzemeltető a bejelentést ésszerű időn belül megvizsgálja, és döntéséről — indokolással — tájékoztatja a bejelentőt és az érintett Felhasználót. A döntés ellen az érintett a fenti elérhetőségen panasszal élhet. Az Üzemeltető kapcsolattartási pontja a hatóságok és a Felhasználók számára a fenti e-mail cím.</p>
	</section>

	<section>
		<?php echo $h( 'felfuggesztes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>A jelen feltételek megsértése esetén az Üzemeltető a Hirdetést elrejtheti vagy törölheti, a fiók egyes funkcióit korlátozhatja, a fiókot felfüggesztheti, súlyos vagy ismételt jogsértés esetén megszüntetheti. A döntésről az érintett — indokolással — értesítést kap, kivéve, ha jogszabály ezt kizárja.</li>
			<li>A Felhasználó a fiókját bármikor megszüntetheti.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'uzleti' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>Cégként a cég képviseletére jogosult személy regisztrálhat; a cégnevet és a telephelyet valósan kell megadni. A Hirdetés helyszíne a céges telephely alapján jelenik meg.</li>
			<li>A Hirdetések böngészésbeli megjelenési sorrendjét elsősorban a Vevő által beállított szűrők, a Hirdetés frissessége és a Vevő korábbi tevékenysége (pl. már megtekintett Hirdetések) befolyásolja. Fizetett kiemelés jelenleg nincs.</li>
			<li>A fiók korlátozásáról vagy megszüntetéséről az üzleti felhasználó a jogszabályokban előírt módon és határidővel, indokolással értesítést kap, és panaszt nyújthat be.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'felelosseg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>A Hirdetések és üzenetek tartalmáért az azokat közzétevő Felhasználó felel.</li>
			<li>Az Üzemeltető nem felel a Felhasználók között létrejött vagy elmaradt adásvételből, a jármű hibájából, a Vállalások nem teljesítéséből eredő kárért; ezek rendezése a felek dolga.</li>
			<li>Az Üzemeltető törekszik a folyamatos, hibamentes működésre, de nem garantálja; karbantartás vagy üzemzavar idején a szolgáltatás átmenetileg szünetelhet.</li>
			<li>A felelősség korlátozása nem vonatkozik a szándékosan vagy súlyos gondatlansággal okozott, valamint az életet, testi épséget vagy egészséget megkárosító szerződésszegésre, és nem korlátozza a fogyasztókat megillető, jogszabályon alapuló jogokat.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'szellemi' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Albira felülete, arculata, szövegei, grafikái és szoftvere az Üzemeltető (illetve jogosultjai) szellemi tulajdona; engedély nélkül nem másolhatók és nem használhatók fel.</p>
		<p>A Felhasználó által feltöltött tartalom (szöveg, fotó) a Felhasználóé marad. A feltöltéssel a Felhasználó nem kizárólagos, díjmentes felhasználási jogot ad az Üzemeltetőnek arra, hogy a tartalmat a szolgáltatás nyújtásához szükséges mértékben tárolja, feldolgozza, megjelenítse. A Felhasználó szavatolja, hogy a feltöltött tartalommal más jogát nem sérti.</p>
	</section>

	<section>
		<?php echo $h( 'adatvedelem' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>A személyes adatok kezeléséről az <a href="<?php echo esc_url( $privacy ); ?>">Adatkezelési tájékoztató</a> rendelkezik.</p>
	</section>

	<section>
		<?php echo $h( 'panasz' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<ul>
			<li>Panaszt a(z) <?php echo esc_html( $op['email'] ); ?> címen vagy a <a href="<?php echo esc_url( $contact ); ?>">Kapcsolat</a> oldalon lehet tenni. Az Üzemeltető a panaszt 30 napon belül érdemben megvizsgálja és megválaszolja.</li>
			<li>Fogyasztónak minősülő Felhasználó a panasz elutasítása esetén a lakóhelye vagy tartózkodási helye szerint illetékes békéltető testülethez fordulhat (az Üzemeltető székhelye szerint illetékes: <?php echo esc_html( $op['conciliator'] ); ?>), illetve bírósághoz fordulhat.</li>
		</ul>
	</section>

	<section>
		<?php echo $h( 'modositas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Az Üzemeltető a jelen feltételeket egyoldalúan módosíthatja. A módosításról a Felhasználókat a hatálybalépés előtt legalább 15 nappal (üzleti felhasználókat a jogszabály szerinti határidővel) e-mailben vagy az Albirán értesíti. Aki a módosítást nem fogadja el, a fiókját megszüntetheti; a hatálybalépés utáni használat a módosítás elfogadásának minősül.</p>
	</section>

	<section>
		<?php echo $h( 'jog' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>A jelen feltételekre a magyar jog — különösen a Polgári Törvénykönyvről szóló 2013. évi V. törvény és az elektronikus kereskedelmi szolgáltatásokról szóló 2001. évi CVIII. törvény —, valamint a közvetlenül alkalmazandó uniós jog az irányadó. A fogyasztókat megillető, jogszabályon alapuló jogok a jelen feltételekkel nem korlátozhatók.</p>
	</section>
</article>
