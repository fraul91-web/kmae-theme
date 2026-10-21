<?php
/**
 * Albira FAQ page.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Kulcsszavak: a keresés a kérdést, a választ és ezeket a szavakat nézi (ékezet nélkül is működik).
$faq_groups = array(
	array(
		'id'        => 'hirdetes',
		'theme'     => 'blue',
		'eyebrow'   => __( 'ELADÓKNAK', 'ki-mit-adna-erte' ),
		'title'     => __( 'Hirdetés feladása', 'ki-mit-adna-erte' ),
		'questions' => array(
			array(
				'question' => __( 'Hogyan adhatok fel hirdetést?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Hirdetés feladása oldalon add meg az autó adatait, válaszd ki az értékesítés módját, majd töltsd fel a szükséges képeket. A rendszer lépésről lépésre végigvezet a folyamaton.', 'ki-mit-adna-erte' ),
				'keywords' => 'feladas uj hirdetes letrehozas varazslo',
			),
			array(
				'question' => __( 'Mit kell kötelezően megadnom?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az autó kötelező adatait, a hirdetés címét, legalább 80 karakteres leírást és legalább egy fotót. A feladáskor a rendszer ellenőrzi ezeket, és jelzi, mi hiányzik.', 'ki-mit-adna-erte' ),
				'keywords' => 'kotelezo mezok leiras minimum fotó kep',
			),
			array(
				'question' => __( 'Megadhatok saját árat is?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen. Három ármód közül választhatsz: fix ár (a vevő pontosan ennyit ajánlhat), irányár (a vevő látja, de ettől eltérő összeget is ajánlhat), vagy „Ki mit adna érte?” (ár nélkül hirdetsz, és a vevők ajánlanak).', 'ki-mit-adna-erte' ),
				'keywords' => 'ar fix irany ar ki mit adna erte arazas',
			),
			array(
				'question' => __( 'Mikor lesz látható a hirdetésem?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Ha a feladáskor a rendszer ellenőrzése rendben van, a hirdetés azonnal megjelenik a böngészésben. Ha valami hiányzik vagy nem megfelelő, a rendszer megmondja, mit kell javítanod.', 'ki-mit-adna-erte' ),
				'keywords' => 'megjelenes lathato ellenorzes publikus',
			),
			array(
				'question' => __( 'Miért nem írhatok telefonszámot vagy e-mail-címet a hirdetésbe?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A rendszer a címben és a leírásban keresi a telefonszámot és az e-mail-címet, és nem engedi őket. A kapcsolattartás az oldalon belüli üzenetekben történik. A telefonszámot a felek csak elfogadott ajánlat után látják.', 'ki-mit-adna-erte' ),
				'keywords' => 'telefonszam email e-mail kapcsolat tiltas szures',
			),
			array(
				'question' => __( 'Mennyi ideig marad fent a hirdetésem?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Egy hirdetés 60 napig aktív. Utána lejár: a böngészésben nem látszik, de nem törlődik, és bármikor újraindíthatod.', 'ki-mit-adna-erte' ),
				'keywords' => 'lejarat 60 nap lejart expired ido',
			),
			array(
				'question' => __( 'Hogyan hosszabbítom meg a hirdetésemet?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Saját hirdetéseim oldalon, a lejárt hirdetés kártyáján a Hosszabbítás gombbal a hirdetés újra 60 napig aktív lesz.', 'ki-mit-adna-erte' ),
				'keywords' => 'hosszabbitas ujra aktiv lejart ujrainditas',
			),
			array(
				'question' => __( 'Szerkeszthetem a hirdetésemet feladás után?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen. A Saját hirdetéseim oldalon a hirdetés kártyáján keresd meg a szerkesztést, és módosíthatod az adatokat.', 'ki-mit-adna-erte' ),
				'keywords' => 'szerkesztes modositas valtoztatas atirni',
			),
			array(
				'question' => __( 'Törölhetem a hirdetésemet?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, bármikor a Saját hirdetéseim oldalon. Törléskor a hirdetéshez tartozó beszélgetések olvashatók maradnak, az érintett vevők pedig értesítést kapnak.', 'ki-mit-adna-erte' ),
				'keywords' => 'torles torol hirdetes eltavolitas',
			),
			array(
				'question' => __( 'Mi az a „Foglalt” állapot?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Ha elfogadsz egy vevői ajánlatot a chatben, az autó Foglalt lesz. A többi érdeklődőt a rendszer értesíti, hogy az autó másnak foglalt, és új ajánlatot addig nem lehet tenni. Ha meggondolod magad, a Mégse kelt el gombbal újra elérhetővé teheted.', 'ki-mit-adna-erte' ),
				'keywords' => 'foglalt elfogadas ajanlat elfogad megse kelt el ujra elerheto',
			),
			array(
				'question' => __( 'Hogyan jelölöm eladottnak az autót?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az elfogadott ajánlat chatjében a Lezárom gombbal. Ekkor a hirdetés lezárul, a beszélgetések csak olvashatók lesznek, és az adatlapon nő az „N eladott autó” szám.', 'ki-mit-adna-erte' ),
				'keywords' => 'eladva eladott lezaras lezaroml sikeres eladas szam',
			),
			array(
				'question' => __( 'Mi történik, ha nem jelölöm le az eladást?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Ha az elfogadás után 7 napig nem jelölöd eladottnak, a rendszer automatikusan lezárja a hirdetést és a beszélgetéseket. Ez nem számít eladásnak: az eladott autók száma nem nő.', 'ki-mit-adna-erte' ),
				'keywords' => '7 nap automatikus lezaras eladas nem szamit',
			),
			array(
				'question' => __( 'Mi az az adásvételi szerződés, és hol találom?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Elfogadott ajánlat után az eladó a beszélgetés jobb oszlopában lévő kártyáról nyitja meg az Adásvételi szerződés gombbal. A szerződés adatait a hirdetésből előre kitöltjük, a végén Word-fájlként (.docx) letöltheted vagy kinyomtathatod. A rendszer a szerződést nem menti el.', 'ki-mit-adna-erte' ),
				'keywords' => 'szerzodes adasveteli word docx nyomtat sablon',
			),
			array(
				'question' => __( 'Mi a forgalmi-beolvasás?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A hirdetésfeladó varázslóban felajánlott funkció: a forgalmi engedély fényképéről a rendszer kiolvassa az autó adatait, így kevesebbet kell gépelned. A képet egy külső AI-szolgáltató dolgozza fel; név- és lakcímadatokat nem olvas ki. A rendszám, az alvázszám és a forgalmi szám a későbbi adásvételi szerződésbe kerül.', 'ki-mit-adna-erte' ),
				'keywords' => 'forgalmi engedely beolvasas kep ocr automatikus kitoltes rendszam alvazszam',
			),
			array(
				'question' => __( 'Hirdethetek cégként, autókereskedésként is?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen. Regisztrációkor válaszd a „Cég / autókereskedés” típust, és a céges profilban add meg a telephelyet. A hirdetések helyszíne ebből jön.', 'ki-mit-adna-erte' ),
				'keywords' => 'ceg autokereskedes kereskedo telephely profil',
			),
		),
	),
	array(
		'id'        => 'vasarlas',
		'theme'     => 'orange',
		'eyebrow'   => __( 'VEVŐKNEK', 'ki-mit-adna-erte' ),
		'title'     => __( 'Keresés és vásárlás', 'ki-mit-adna-erte' ),
		'questions' => array(
			array(
				'question' => __( 'Ingyenes az Albira használata?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen. A böngészés és a hirdetésfeladás is ingyenes.', 'ki-mit-adna-erte' ),
				'keywords' => 'ingyenes dij fizetes koltseg ar',
			),
			array(
				'question' => __( 'Hogyan keresek autót?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Böngészés oldalon a Keresés panelben szűrhetsz, például márka, modell, ár és évjárat szerint. A találatokat rendezheted is, például legújabb, ár vagy kilométer szerint.', 'ki-mit-adna-erte' ),
				'keywords' => 'kereses szuro szures marka modell ar evjarat rendezes',
			),
			array(
				'question' => __( 'Váltogathatok kártyás és listás nézet között?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, a böngészés oldalon a nézetváltóval.', 'ki-mit-adna-erte' ),
				'keywords' => 'nezet lista kartya kartyas nezetvalto',
			),
			array(
				'question' => __( 'Hogyan mentem el egy hirdetést?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A hirdetés csillag gombjával kedvencnek jelölheted. A mentett autók a Kedvencek oldalon találhatók.', 'ki-mit-adna-erte' ),
				'keywords' => 'kedvenc csillag mentes mentett favorit szivu',
			),
			array(
				'question' => __( 'Mit jelent a „Foglalt” felirat egy autónál?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az eladó elfogadott egy ajánlatot, így az autó másnak van lefoglalva. Ilyenkor nem lehet új ajánlatot tenni. Ha az eladó mégsem adja el, és a Mégse kelt el gombbal visszavonja a foglalást, az autó újra elérhető lesz.', 'ki-mit-adna-erte' ),
				'keywords' => 'foglalt lefoglalt ajanlat tenni nem lehet elerheto',
			),
			array(
				'question' => __( 'Mit jelent a fix ár, az irányár és a „Ki mit adna érte?” ármód?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Fix ár: az eladó által megadott összeget ajánlhatod. Irányár: az eladó kiír egy irányárat, de te eltérő összeget is ajánlhatsz. „Ki mit adna érte?”: nincs megadott ár, te döntöd el, mennyit ajánlasz.', 'ki-mit-adna-erte' ),
				'keywords' => 'ar fix irany ki mit adna erte ajanlat mennyit',
			),
			array(
				'question' => __( 'Hogyan veszem fel a kapcsolatot az eladóval?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A hirdetés oldalán kattints az Üzenet küldése gombra. Ehhez be kell jelentkezned. Ezután a beszélgetésben írhatsz az eladónak.', 'ki-mit-adna-erte' ),
				'keywords' => 'kapcsolat eladó uzenet kuldes chat bejelentkezes',
			),
			array(
				'question' => __( 'Hogyan teszek ajánlatot?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A beszélgetésben az „Ajánlatot teszek” gombbal add meg az összeget. Az ajánlat a chatben buborékként jelenik meg. Ha új ajánlatot küldesz, a korábbi élő ajánlatod felülíródik.', 'ki-mit-adna-erte' ),
				'keywords' => 'ajanlat tesz ajanlatot osszeg buborek felulir',
			),
			array(
				'question' => __( 'Visszavonhatom az ajánlatomat?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, amíg az eladó nem fogadta el. A saját ajánlatod alatt a Visszavonom gombbal. Az elfogadott ajánlatot már nem lehet visszavonni.', 'ki-mit-adna-erte' ),
				'keywords' => 'visszavon ajanlat torol meggondol',
			),
			array(
				'question' => __( 'Mi történik, ha az eladó elfogadja az ajánlatomat?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az autó Foglalt lesz, a beszélgetésben megjelenik az eladó telefonszáma, és rendszerüzenetet kapsz. Az adásvétel ezután az eladóval közvetlenül zajlik.', 'ki-mit-adna-erte' ),
				'keywords' => 'elfogadas elfogadta telefonszam foglalt utan',
			),
			array(
				'question' => __( 'Mire figyeljek vásárláskor?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A beszélgetésben a „Mire figyelj” kép (nagyítható) segít az átvizsgálásban. Elfogadott ajánlat után az ellenőrzőlistát is letöltheted Excel-fájlként, amellyel végigmehetsz az autón.', 'ki-mit-adna-erte' ),
				'keywords' => 'vasarlas ellenorzes atvetel tippek atnezes ellenorzolista xlsx',
			),
			array(
				'question' => __( 'Bejelenthetek hirdetést vagy felhasználót?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen. A hirdetés oldalán a Hirdetés bejelentése gombbal, a beszélgetésben a Bejelentés gombbal. Válaszd ki az okot, és írd le röviden. Az Albira a bejelentést ellenőrzi; a bejelentett fél nem tudja, ki jelentette.', 'ki-mit-adna-erte' ),
				'keywords' => 'bejelentes jelentes visszaeles csalas gyanus hirdetes',
			),
			array(
				'question' => __( 'Letilthatok valakit?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, a beszélgetés jobb oszlopában a Felhasználó tiltása gombbal. A tiltott fél nem tudja, hogy letiltottad: az üzenetei nálad nem jelennek meg, és nem kapsz róluk értesítést. A tiltás a beszélgetés jobb oszlopából bármikor feloldható.', 'ki-mit-adna-erte' ),
				'keywords' => 'tiltas blokkolas letilt feloldas zaklat',
			),
		),
	),
	array(
		'id'        => 'uzenetek',
		'theme'     => 'blue',
		'eyebrow'   => __( 'ÜZENETEK ÉS ADÁSVÉTEL', 'ki-mit-adna-erte' ),
		'title'     => __( 'Beszélgetések és ajánlatok', 'ki-mit-adna-erte' ),
		'questions' => array(
			array(
				'question' => __( 'Hol találom a beszélgetéseimet?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az Üzenetek menüpontban. Minden beszélgetés mellett látszik az állapota, például ajánlat, foglalt vagy eladva.', 'ki-mit-adna-erte' ),
				'keywords' => 'uzenetek chat beszelgetes lista inbox',
			),
			array(
				'question' => __( 'Mit jelentenek a pipák az üzenetek mellett?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Egy pipa: az üzenet elment. Két pipa: a másik fél elolvasta.', 'ki-mit-adna-erte' ),
				'keywords' => 'pipa olvasott elolvasta elkuldve',
			),
			array(
				'question' => __( 'Törölhetem a beszélgetést?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, az Üzenetek listában a piros X-szel. A törlés csak nálad rejti el a beszélgetést, a másik félnél megmarad. Ha van élő ajánlatod, az ezzel visszavonódik. Elfogadott ajánlat esetén a beszélgetés nem törölhető.', 'ki-mit-adna-erte' ),
				'keywords' => 'torles torol eltuntet beszelgetes uzenet',
			),
			array(
				'question' => __( 'Küldhetek képet vagy fájlt is?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, a chatben emojit és fájlt is küldhetsz. Egy csatolmány legfeljebb 3 MB lehet.', 'ki-mit-adna-erte' ),
				'keywords' => 'kep fajl csatolmany emoji 3 mb feltoltes',
			),
			array(
				'question' => __( 'Mi történik, ha a hirdetést törlik egy beszélgetés közben?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A beszélgetés olvasható marad, de írni már nem lehet benne. Az Üzenetek listában a hirdetés címe megmarad. Ha elfogadott ajánlatod volt, erről rendszerüzenetet kapsz.', 'ki-mit-adna-erte' ),
				'keywords' => 'torolt hirdetes torles beszelgetes olvashato',
			),
			array(
				'question' => __( 'Mit jelent a „Lezárva” állapot?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A beszélgetés már nem aktív: a hirdetés lezárult, mert eladták, vagy mert 7 napig nem jelölték le az eladást. Ilyenkor a beszélgetés csak olvasható.', 'ki-mit-adna-erte' ),
				'keywords' => 'lezarva lezaras inaktiv csak olvashato',
			),
			array(
				'question' => __( 'Miért nem látom az „Ajánlatot teszek” gombot?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Ajánlatot csak vevőként, nyitott beszélgetésben, aktív hirdetésnél tehetsz. Ha az autó már Foglalt vagy lezárult, nincs ajánlattétel.', 'ki-mit-adna-erte' ),
				'keywords' => 'nincs gomb ajanlat tesz hianyzik nem latszik',
			),
			array(
				'question' => __( 'Nem kaptam értesítést az új üzenetről. Mit tegyek?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Nézd meg az Üzenetek menüpontot, ahol a jelvény mutatja az olvasatlan beszélgetéseket. Az e-mail értesítéseket a Beállítások oldalon az „E-mail értesítések beállítása” gombbal állíthatod.', 'ki-mit-adna-erte' ),
				'keywords' => 'ertesites nem jott email e-mail uzenet jelveny push beallitas',
			),
		),
	),
	array(
		'id'        => 'fiok',
		'theme'     => 'orange',
		'eyebrow'   => __( 'FIÓK ÉS BIZTONSÁG', 'ki-mit-adna-erte' ),
		'title'     => __( 'Fiók, adatok és biztonság', 'ki-mit-adna-erte' ),
		'questions' => array(
			array(
				'question' => __( 'Hogyan regisztrálok?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Regisztráció oldalon add meg az adataidat, fogadd el az ÁSZF-et, majd a megadott e-mail-címre érkező linkkel erősítsd meg a címedet.', 'ki-mit-adna-erte' ),
				'keywords' => 'regisztracio fiok letrehozas uj felhasznalo aszf',
			),
			array(
				'question' => __( 'Miért kell megerősítenem az e-mail-címemet?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A böngészés szabad, de hirdetést feladni és az eladónak írni csak megerősített e-mail-címmel lehet. Ha nem érkezett meg a levél, nézd meg a spam mappát is.', 'ki-mit-adna-erte' ),
				'keywords' => 'megerosites email e-mail level nem jott spam link',
			),
			array(
				'question' => __( 'Elfelejtettem a jelszavamat. Mit tegyek?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A bejelentkezési oldalon az Elfelejtett jelszó lehetőséggel add meg a regisztrált e-mail-címedet. A rendszer e-mailben küld egy linket, amellyel új jelszót állíthatsz be.', 'ki-mit-adna-erte' ),
				'keywords' => 'jelszo elfelejtett visszaallitas reset bejelentkezes',
			),
			array(
				'question' => __( 'Hogyan módosíthatom az adataimat?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Profilom oldalon az Adataim űrlapon a neved, a telefonszámod és a többi adat. Az e-mail-címet és a jelszót a Beállítások oldalon változtathatod meg.', 'ki-mit-adna-erte' ),
				'keywords' => 'adatok modositas profil nev telefon email jelszo valtoztat',
			),
			array(
				'question' => __( 'Kijelentkezhetek minden eszközön egyszerre?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Igen, a Beállítások oldalon a „Kiléptetés a többi eszközről” gombbal. Ez minden más gépen és telefonon megszünteti a belépést, az eszközödön maradsz bejelentkezve. Például akkor hasznos, ha idegen gépen is beléptél.', 'ki-mit-adna-erte' ),
				'keywords' => 'kijelentkezes minden eszkoz biztonsag masik gep session',
			),
			array(
				'question' => __( 'Hogyan törölhetem a fiókomat?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Profil oldal jobb oszlopában a Profilom törlése gombbal. A törlés eltávolítja a hirdetéseidet, a beszélgetéseidet, az ajánlataidat és a profil adataidat. Ezt nem lehet visszavonni.', 'ki-mit-adna-erte' ),
				'keywords' => 'fiok torles torol profil megszunes eltavolitas',
			),
			array(
				'question' => __( 'Mit kezel az Albira az adataimból?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az Adatvédelem oldalon részletesen olvasható, milyen adatokat kezelünk és miért. Az Általános Szerződési Feltételek (ÁSZF) a Feltételek oldalon találhatók.', 'ki-mit-adna-erte' ),
				'keywords' => 'adatvedelem gdpr adatkezeles aszf feltetelek adatok',
			),
			array(
				'question' => __( 'Hogyan védem magam csalás ellen?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'Az oldalon nincs online fizetés: az adásvételt a felek közvetlenül bonyolítják. Ne fizess előleget olyan félnek, akit nem ismersz, és az autót mindig nézd meg személyesen. Gyanús hirdetést vagy felhasználót a bejelentés gombbal jelezhetsz.', 'ki-mit-adna-erte' ),
				'keywords' => 'csalas biztonsag elolege fizetes gyanus veszely becsapas',
			),
			array(
				'question' => __( 'Nem találtam meg a választ. Kihez fordulhatok?', 'ki-mit-adna-erte' ),
				'answer'   => __( 'A Kapcsolat oldalon található űrlapon írhatsz nekünk, és ott megadhatod a kérdésedet.', 'ki-mit-adna-erte' ),
				'keywords' => 'kapcsolat segitseg kerdes ugyfelszolgalat iras',
			),
		),
	),
);
?>
<div class="kmae-faq">
	<header class="kmae-faq__hero">
		<p class="kmae-faq__eyebrow"><?php esc_html_e( 'GYAKORI KÉRDÉSEK', 'ki-mit-adna-erte' ); ?></p>
		<h1 class="kmae-faq__title"><?php esc_html_e( 'Miben segíthetünk?', 'ki-mit-adna-erte' ); ?></h1>
		<p class="kmae-faq__lead"><?php esc_html_e( 'Itt gyorsan megtalálod a legfontosabb válaszokat az eladáshoz és a vásárláshoz.', 'ki-mit-adna-erte' ); ?></p>
	</header>

	<div class="kmae-faq__search" role="search">
		<input type="search" id="kmae-faq-search" class="kmae-faq__search-input" placeholder="<?php esc_attr_e( 'Keress rá, pl. jelszó, ajánlat, lejárat…', 'ki-mit-adna-erte' ); ?>" aria-label="<?php esc_attr_e( 'Keresés a gyakori kérdések között', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
		<p class="kmae-faq__count" data-kmae-faq-count aria-live="polite"></p>
	</div>

	<div class="kmae-faq__groups">
		<?php foreach ( $faq_groups as $group ) : ?>
			<section class="kmae-faq-group kmae-faq-group--<?php echo esc_attr( $group['theme'] ); ?>" data-kmae-faq-group aria-labelledby="kmae-faq-<?php echo esc_attr( $group['id'] ); ?>">
				<header class="kmae-faq-group__header">
					<div>
						<p class="kmae-faq-group__eyebrow"><?php echo esc_html( $group['eyebrow'] ); ?></p>
						<h2 id="kmae-faq-<?php echo esc_attr( $group['id'] ); ?>" class="kmae-faq-group__title"><?php echo esc_html( $group['title'] ); ?></h2>
					</div>
				</header>

				<div class="kmae-faq-group__items">
					<?php foreach ( $group['questions'] as $item ) : ?>
						<details class="kmae-faq-item" data-kmae-faq-item data-keywords="<?php echo esc_attr( $item['keywords'] ); ?>">
							<summary class="kmae-faq-item__question">
								<span><?php echo esc_html( $item['question'] ); ?></span>
							</summary>
							<div class="kmae-faq-item__answer"><p><?php echo esc_html( $item['answer'] ); ?></p></div>
						</details>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>

	<div class="kmae-faq__empty" data-kmae-faq-empty hidden>
		<p><?php esc_html_e( 'Ehhez a kereséshez nincs találat. Próbálj más szavakat, vagy írj nekünk.', 'ki-mit-adna-erte' ); ?></p>
	</div>

	<footer class="kmae-faq__footer">
		<p><?php esc_html_e( 'Nem találtad meg a választ?', 'ki-mit-adna-erte' ); ?></p>
		<a href="<?php echo esc_url( KMAE_Pages::url( 'kapcsolat' ) ); ?>"><?php esc_html_e( 'Írj nekünk', 'ki-mit-adna-erte' ); ?><span aria-hidden="true">→</span></a>
	</footer>
</div>
<script>
(function () {
	var input = document.getElementById('kmae-faq-search');
	if (!input) {
		return;
	}

	var items = [].slice.call(document.querySelectorAll('[data-kmae-faq-item]'));
	var groups = [].slice.call(document.querySelectorAll('[data-kmae-faq-group]'));
	var count = document.querySelector('[data-kmae-faq-count]');
	var empty = document.querySelector('[data-kmae-faq-empty]');

	// Kisbetű és ékezet nélkül hasonlít, így a „jelszo” is megtalálja a „jelszó”-t.
	var norm = function (text) {
		return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	};

	items.forEach(function (item) {
		item.kmaeText = norm(item.textContent + ' ' + item.getAttribute('data-keywords'));
		item.kmaeOpen = item.open;
	});

	input.addEventListener('input', function () {
		var words = norm(input.value).trim().split(/\s+/).filter(Boolean);
		var searching = words.length > 0;
		var found = 0;

		items.forEach(function (item) {
			var match = words.every(function (word) {
				return item.kmaeText.indexOf(word) !== -1;
			});
			item.hidden = !match;
			item.open = searching ? match : item.kmaeOpen;
			if (match) {
				found++;
			}
		});

		groups.forEach(function (group) {
			group.hidden = !group.querySelector('[data-kmae-faq-item]:not([hidden])');
		});

		count.textContent = searching ? found + ' találat' : '';
		empty.hidden = found > 0;
	});
})();
</script>
