<?php
/**
 * Adásvételi szerződés aloldal (csak az eladónak, elfogadott ajánlat után).
 * Szerkeszthető, Wordben letölthető, nyomtatható — a szerveren semmi nem mentődik.
 *
 * @package KMAE
 * @var object $conversation
 * @var array  $values Előre kitöltött mezők.
 * @var array  $limits KMAE_Sale_Contract::fields().
 * @var string $back   Vissza a chatre.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kmae_from_listing = __( 'Ha a hirdetésben megadtad, onnan töltöttük ki.', 'ki-mit-adna-erte' );
$kmae_from_scan    = __( 'Ha a hirdetést forgalmi beolvasással adtad fel, onnan töltöttük ki — nézd át, és ha kell, javítsd.', 'ki-mit-adna-erte' );

/* Magyarázat az „i” gombhoz (csak a képernyőn; nyomtatásban és a Wordben nincs). */
$kmae_help = array(
	'jog'             => __( 'Eladásnál a „visszterhes” a helyes. Az „ingyenes” ajándékozáskor kell.', 'ki-mit-adna-erte' ),
	'rendszam'        => __( 'A jármű rendszáma, ahogy a forgalmi engedély „A” mezőjében áll (pl. ABC-123).', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_scan,
	'alvazszam'       => __( 'Az alvázszám (járműazonosító, VIN): a forgalmi engedély „E” mezőjében álló, általában 17 karakteres kód.', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_scan,
	'gyartmany'       => __( 'A jármű márkája (a forgalmi engedély „D.1” mezője).', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_listing,
	'tipus'           => __( 'A jármű típusa, kereskedelmi neve (a forgalmi engedély „D.3” mezője).', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_listing,
	'gyartasi_ev'     => __( 'A gyártás éve, ahogy a forgalmi engedélyben szerepel.', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_listing,
	'forgalmi_szam'   => __( 'Magának a forgalmi engedélynek a sorszáma (okmányszáma), amely a forgalmira külön van nyomtatva.', 'ki-mit-adna-erte' ) . ' ' . $kmae_from_scan,
	'torzskonyv_szam' => __( 'A törzskönyv sorszáma, a törzskönyv első oldalán található. Ezt kézzel kell beírni.', 'ki-mit-adna-erte' ),
	'nev'             => __( 'A teljes név a személyi igazolvány szerint: családi és utónév.', 'ki-mit-adna-erte' ),
	'szul_nev'        => __( 'A születéskor kapott teljes név. Ha azóta nem változott, ugyanaz, mint a név.', 'ki-mit-adna-erte' ),
	'szul_hely_ido'   => __( 'Születési hely és dátum, pl. Budapest, 1985. 04. 12.', 'ki-mit-adna-erte' ),
	'anyja_neve'      => __( 'Az anya születési családi és utóneve.', 'ki-mit-adna-erte' ),
	'okmany'          => __( 'A személyazonosításra használt okmány típusa és száma, pl. személyi igazolvány 123456AB.', 'ki-mit-adna-erte' ),
	'lakcim'          => __( 'A lakcímkártyán szereplő lakcím: irányítószám, település, utca, házszám.', 'ki-mit-adna-erte' ),
	'allampolgarsag'  => __( 'Az állampolgárság, pl. magyar.', 'ki-mit-adna-erte' ),
	'szekhely'        => __( 'A cég bejegyzett székhelyének címe.', 'ki-mit-adna-erte' ),
	'cegjegyzek'      => __( 'A cégjegyzékszám (pl. 01-09-123456), más szervezetnél a nyilvántartási szám.', 'ki-mit-adna-erte' ),
	'kepviselo'       => __( 'Annak a neve, aki a cég nevében aláírja a szerződést (pl. ügyvezető).', 'ki-mit-adna-erte' ),
	'c_nev'           => __( 'A cég vagy szervezet hivatalos, teljes neve, ahogy a cégjegyzékben szerepel.', 'ki-mit-adna-erte' ),
	'vetelar'         => __( 'A megegyezett vételár számmal, forintban.', 'ki-mit-adna-erte' ),
	'vetelar_szoveg'  => __( 'Ugyanez az összeg betűvel kiírva, pl. kétmillió-ötszázezer.', 'ki-mit-adna-erte' ),
	'fizetes'         => __( 'Hogyan és mikor fizet a vevő, pl. készpénz, 2026. október 20.', 'ki-mit-adna-erte' ),
	'okmany_atadas'   => __( 'Az a nap, amikor a vevő megkapja a forgalmi engedélyt, a törzskönyvet és a kulcsokat.', 'ki-mit-adna-erte' ),
	'birtokba_lepes'  => __( 'Az időpont, amikor a vevő átveszi az autót. Ettől kezdve ő viseli az autóval járó terheket.', 'ki-mit-adna-erte' ),
	'km_allas'        => __( 'A kilométeróra állása az átadáskor. A hirdetésből töltöttük ki — az átadáskor írd át a pontos értékre.', 'ki-mit-adna-erte' ),
	'hatalyba_lepes'  => __( 'Az a nap, amikor a tulajdonjog a vevőre száll. Általában az átadás napja.', 'ki-mit-adna-erte' ),
	'megjegyzes'      => __( 'Minden további megállapodás, pl. átadott tartozékok, ismert hibák. Az előre beírt mondatokat átírhatod vagy törölheted.', 'ki-mit-adna-erte' ),
	'datum'           => __( 'A szerződés aláírásának napja.', 'ki-mit-adna-erte' ),
);

$kmae_info = static function ( $key ) use ( $kmae_help ) {
	if ( empty( $kmae_help[ $key ] ) ) {
		return;
	}
	printf(
		'<button type="button" class="kmae-contract__info"><span class="kmae-contract__info-i" aria-hidden="true">i</span><span class="kmae-contract__tip" role="tooltip">%s</span></button>',
		esc_html( $kmae_help[ $key ] )
	);
};

$kmae_field = static function ( $key, $placeholder = '' ) use ( $values, $limits ) {
	printf(
		'<input type="text" class="kmae-contract__input" name="kmae_contract[%1$s]" value="%2$s" maxlength="%3$d" placeholder="%4$s" autocomplete="off" />',
		esc_attr( $key ),
		esc_attr( $values[ $key ] ?? '' ),
		(int) $limits[ $key ][0],
		esc_attr( $placeholder )
	);
};

/* Egy sor: címke + „i” | mező | (mértékegység). $help = a magyarázat kulcsa. */
$kmae_line = static function ( $key, $label, $placeholder = '', $unit = '', $help = '' ) use ( $kmae_field, $kmae_info ) {
	echo '<div class="kmae-contract__row' . ( '' !== $unit ? ' kmae-contract__row--unit' : '' ) . '"><span class="kmae-contract__label"><span class="kmae-contract__label-text">' . esc_html( $label ) . '</span>';
	$kmae_info( '' !== $help ? $help : $key );
	echo '</span>';
	$kmae_field( $key, $placeholder );
	if ( '' !== $unit ) {
		echo '<span class="kmae-contract__unit">' . esc_html( $unit ) . '</span>';
	}
	echo '</div>';
};

$kmae_rows = static function ( $prefix, array $labels ) use ( $kmae_line ) {
	foreach ( $labels as $key => $label ) {
		/* Cégnév külön magyarázatot kap; a többi kulcs eladónál/vevőnél azonos. */
		$help = ( 'ec_' === $prefix || 'vc_' === $prefix ) && 'nev' === $key ? 'c_nev' : $key;
		$kmae_line( $prefix . $key, $label, '', '', $help );
	}
};

$kmae_person = array(
	'nev'            => __( 'Név (családi és utónév):', 'ki-mit-adna-erte' ),
	'szul_nev'       => __( 'Születéskori név (családi és utónév):', 'ki-mit-adna-erte' ),
	'szul_hely_ido'  => __( 'Születési hely, idő:', 'ki-mit-adna-erte' ),
	'anyja_neve'     => __( 'Anyja neve (családi és utónév):', 'ki-mit-adna-erte' ),
	'okmany'         => __( 'Személyi okmány típus és száma:', 'ki-mit-adna-erte' ),
	'lakcim'         => __( 'Lakcíme:', 'ki-mit-adna-erte' ),
	'allampolgarsag' => __( 'Állampolgársága:', 'ki-mit-adna-erte' ),
);
$kmae_company = array(
	'nev'        => __( 'Név:', 'ki-mit-adna-erte' ),
	'szekhely'   => __( 'Székhely:', 'ki-mit-adna-erte' ),
	'cegjegyzek' => __( 'Cégjegyzék vagy nyilvántartási szám:', 'ki-mit-adna-erte' ),
	'kepviselo'  => __( 'Képviselő neve:', 'ki-mit-adna-erte' ),
);
$kmae_date = __( 'év, hónap, nap', 'ki-mit-adna-erte' );
?>
<div class="kmae-contract">
	<div class="kmae-contract__toolbar">
		<a class="kmae-contract__back" href="<?php echo esc_url( $back ); ?>">&larr; <?php esc_html_e( 'Vissza a beszélgetéshez', 'ki-mit-adna-erte' ); ?></a>
		<div class="kmae-contract__intro">
			<h1 class="kmae-contract__title"><?php esc_html_e( 'Adásvételi szerződés', 'ki-mit-adna-erte' ); ?></h1>
			<p class="kmae-contract__lead"><?php esc_html_e( 'Az autó és a te adataidat előre beírtuk. A hiányzó részeket itt kitöltheted vagy javíthatod, aztán töltsd le Wordben, vagy nyomtasd ki. A vevő adatait a helyszínen is kitölthetitek kézzel. A mezők melletti „i” megmutatja, mit kell beírni.', 'ki-mit-adna-erte' ); ?></p>
			<p class="kmae-contract__note"><?php esc_html_e( 'Semmit nem mentünk el: ha elhagyod az oldalt, a módosítások elvesznek.', 'ki-mit-adna-erte' ); ?></p>
		</div>
		<div class="kmae-contract__actions">
			<button type="submit" form="kmae-contract-form" class="kmae-contract__btn kmae-contract__btn--download"><?php esc_html_e( 'Letöltés (Word)', 'ki-mit-adna-erte' ); ?></button>
			<button type="button" class="kmae-contract__btn kmae-contract__btn--print" data-kmae-print><?php esc_html_e( 'Nyomtatás', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</div>

	<form id="kmae-contract-form" method="post" class="kmae-contract__paper">
		<?php wp_nonce_field( KMAE_Sale_Contract::NONCE, KMAE_Sale_Contract::NONCE ); ?>
		<input type="hidden" name="kmae_action" value="download_sale_contract" />
		<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (int) $conversation->id ); ?>" />

		<header class="kmae-contract__head">
			<p><?php esc_html_e( 'A KÖZÚTI KÖZLEKEDÉSI NYILVÁNTARTÁSBA BEJEGYZETT JÁRMŰ', 'ki-mit-adna-erte' ); ?></p>
			<p><?php esc_html_e( 'TULAJDONJOGÁNAK VÁLTOZÁSÁT', 'ki-mit-adna-erte' ); ?></p>
			<p><?php esc_html_e( 'IGAZOLÓ TELJES BIZONYÍTÓ EREJŰ MAGÁNOKIRAT', 'ki-mit-adna-erte' ); ?></p>
		</header>

		<p class="kmae-contract__small kmae-contract__small--center"><?php esc_html_e( 'A gépjármű tulajdonjoga változásának alapjául szolgáló jogügylet jellege (a megfelelő kiválasztandó):', 'ki-mit-adna-erte' ); ?><?php $kmae_info( 'jog' ); ?></p>
		<div class="kmae-contract__choices">
			<label><input type="radio" name="kmae_contract[jog]" value="visszterhes" <?php checked( $values['jog'], 'visszterhes' ); ?> /> <strong><?php esc_html_e( 'VISSZTERHES', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( '(pl. adásvételi szerződés)', 'ki-mit-adna-erte' ); ?></label>
			<label><input type="radio" name="kmae_contract[jog]" value="ingyenes" <?php checked( $values['jog'], 'ingyenes' ); ?> /> <strong><?php esc_html_e( 'INGYENES', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( '(pl. ajándékozási szerződés)', 'ki-mit-adna-erte' ); ?></label>
		</div>

		<h2 class="kmae-contract__section"><?php esc_html_e( '1. A jármű adatai', 'ki-mit-adna-erte' ); ?></h2>
		<div class="kmae-contract__grid">
			<?php
			$kmae_rows(
				'',
				array(
					'rendszam'        => __( 'Rendszám:', 'ki-mit-adna-erte' ),
					'alvazszam'       => __( 'Alvázszám:', 'ki-mit-adna-erte' ),
					'gyartmany'       => __( 'Gyártmány:', 'ki-mit-adna-erte' ),
					'tipus'           => __( 'Típus:', 'ki-mit-adna-erte' ),
					'gyartasi_ev'     => __( 'Gyártás éve:', 'ki-mit-adna-erte' ),
					'forgalmi_szam'   => __( 'Forgalmi engedély száma:', 'ki-mit-adna-erte' ),
					'torzskonyv_szam' => __( 'Törzskönyv száma:', 'ki-mit-adna-erte' ),
				)
			);
			?>
		</div>

		<h2 class="kmae-contract__section"><?php esc_html_e( '2.1 Eladó adatai magánszemély esetében', 'ki-mit-adna-erte' ); ?></h2>
		<?php $kmae_rows( 'e_', $kmae_person ); ?>
		<h2 class="kmae-contract__section"><?php esc_html_e( '2.2 Eladó adatai jogi személy vagy jogi személyiséggel nem rendelkező szervezet esetében', 'ki-mit-adna-erte' ); ?></h2>
		<?php $kmae_rows( 'ec_', $kmae_company ); ?>

		<h2 class="kmae-contract__section"><?php esc_html_e( '3.1 Vevő adatai magánszemély esetében', 'ki-mit-adna-erte' ); ?></h2>
		<?php $kmae_rows( 'v_', $kmae_person ); ?>
		<h2 class="kmae-contract__section"><?php esc_html_e( '3.2 Vevő adatai jogi személy, vagy jogi személyiséggel nem rendelkező szervezet esetében', 'ki-mit-adna-erte' ); ?></h2>
		<?php $kmae_rows( 'vc_', $kmae_company ); ?>

		<h2 class="kmae-contract__section"><?php esc_html_e( '4. Vételár, egyéb rendelkezések', 'ki-mit-adna-erte' ); ?></h2>
		<?php
		$kmae_line( 'vetelar', __( 'Kölcsönösen kialkudott vételár:', 'ki-mit-adna-erte' ), '', __( ',- Ft', 'ki-mit-adna-erte' ) );
		$kmae_line( 'vetelar_szoveg', __( 'Azaz (a vételár szöveggel):', 'ki-mit-adna-erte' ), '', __( ',- Forint', 'ki-mit-adna-erte' ) );
		$kmae_line( 'fizetes', __( 'Fizetés módja, ideje:', 'ki-mit-adna-erte' ), __( 'pl. készpénz; év, hónap, nap', 'ki-mit-adna-erte' ) );
		$kmae_line( 'okmany_atadas', __( 'Okmányok átadás-átvételi időpontja:', 'ki-mit-adna-erte' ), $kmae_date );
		$kmae_line( 'birtokba_lepes', __( 'Vevő birtokba lépésének időpontja:', 'ki-mit-adna-erte' ), __( 'év, hónap, nap, óra, perc', 'ki-mit-adna-erte' ) );
		$kmae_line( 'km_allas', __( 'A birtokba vételkor a kilométerszámláló műszer által jelzett érték (km-óra állás):', 'ki-mit-adna-erte' ), '', __( 'km', 'ki-mit-adna-erte' ) );
		$kmae_line( 'hatalyba_lepes', __( 'Tulajdonjog változás hatályba lépésének napja:', 'ki-mit-adna-erte' ), $kmae_date );
		?>
		<p class="kmae-contract__small"><?php esc_html_e( 'Ezen jármű tulajdonjoga a bizalmi vagyonkezelés alapján fennálló kezelt vagyonba tartozik: IGEN / NEM', 'ki-mit-adna-erte' ); ?></p>

		<h2 class="kmae-contract__section"><?php esc_html_e( '5. Jognyilatkozatok', 'ki-mit-adna-erte' ); ?></h2>
		<ol class="kmae-contract__legal">
			<li><?php esc_html_e( 'Vevő kötelezettséget vállal arra, hogy a tulajdonjog változás tényét – annak a járműnyilvántartásban történő átvezetése céljából – a tulajdonjog változás hatálybalépését követő 15 napon belül bejelenti, és a változás bejegyzésére irányuló kérelmét jelen magánokirat 1 eredeti példányának csatolásával benyújtja az okmányirodába. Vevő tudomásul veszi, hogy a birtokbavétel napjától kezdődően köteles az 1. pontban meghatározott járművel kapcsolatos valamennyi teher viselésére.', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Vevő tudomásul veszi, hogy amennyiben bejelentési kötelezettségét késedelmesen teljesíti, az okmányiroda a tulajdonjogot bejegyzi a nyilvántartásba, a forgalmi engedély, valamint a törzskönyv kiadására intézkedik, egyben a szabályszegés tényéről értesíti a szabálysértési hatóságot (illetékes rendőrkapitányság).', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Eladó kötelezettséget vállal arra, hogy a tulajdonjog változás tényét – annak hatálybalépését követő 8 napon belül – jelen magánokirat egy eredeti példányának a benyújtásával vagy megküldésével bejelenti az okmányirodában.', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Eladó a vevőt a gépjármű műszaki állapotáról, esetleges sérüléseiről részletesen tájékoztatta, a vevő ezeket tudomásul vette. Eladó kijelenti, hogy a gépjármű per- és tehermentes, valamint annak tulajdonjogával szabadon rendelkezik.', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Jelen magánokirat a közlekedési igazgatási hatósági eljárásban történő felhasználás céljából, a közúti közlekedési nyilvántartásról szóló 1999. évi LXXXIV. törvényben előírt bejelentési kötelezettség teljesítése érdekében, a közúti közlekedési nyilvántartásba bejegyzett jármű tulajdonjogának, illetve üzembentartó személyének változását igazoló teljes bizonyító erejű magánokiratnak a közlekedési igazgatási eljárásban történő felhasználhatóságához szükséges kötelező tartalmi elemekről szóló 304/2009.(XII.22.) Korm. rendeletben meghatározottak szerint került elkészítésre.', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Eladó és Vevő (együttesen: Felek) kijelentik, hogy ismerik a bejelentés nyilvántartásba történő bejegyzéséhez fűződő joghatásokat, valamint a bejelentés elmaradásának, illetve bejelentési kötelezettség késedelmes teljesítésének jogkövetkezményeit, továbbá tisztában vannak azzal, hogy a teljes bizonyító erejű magánokirat tartalmi követelményeinek meg nem felelő magánokirat a közlekedési igazgatási eljárásban alkalmatlan a változás nyilvántartásba történő bejegyzésére.', 'ki-mit-adna-erte' ); ?></li>
			<li><?php esc_html_e( 'Felek tudomásul veszik, hogy az eladás bejelentésének ténye a teljes bizonyító erejű magánokirat benyújtása esetén sem jegyezhető be, ha a bejelentés tárgyát képező járművön érvényes forgalmi korlátozás szerepel a nyilvántartásban. Ebben az esetben a bejegyzés teljesítését az eljáró hatóság elutasítja.', 'ki-mit-adna-erte' ); ?></li>
		</ol>

		<h2 class="kmae-contract__section"><?php esc_html_e( '6. Egyéb megjegyzések', 'ki-mit-adna-erte' ); ?><?php $kmae_info( 'megjegyzes' ); ?></h2>
		<textarea class="kmae-contract__textarea" name="kmae_contract[megjegyzes]" rows="4" maxlength="<?php echo (int) $limits['megjegyzes'][0]; ?>" data-kmae-autogrow><?php echo esc_textarea( $values['megjegyzes'] ); ?></textarea>

		<p class="kmae-contract__closing"><?php esc_html_e( '7. Felek a jelen magánokiratot – annak áttanulmányozását és értelmezését követően, mint akaratukkal mindenben megegyezőt 2 – 2 eredeti példányban tanúk előtt jóváhagyólag aláírták.', 'ki-mit-adna-erte' ); ?></p>
		<?php $kmae_line( 'datum', __( 'Dátum:', 'ki-mit-adna-erte' ), $kmae_date ); ?>

		<div class="kmae-contract__signs">
			<div class="kmae-contract__sign"><span class="kmae-contract__line"></span><?php esc_html_e( 'Aláírás (Eladó)', 'ki-mit-adna-erte' ); ?></div>
			<div class="kmae-contract__sign"><span class="kmae-contract__line"></span><?php esc_html_e( 'Aláírás (Vevő)', 'ki-mit-adna-erte' ); ?></div>
		</div>

		<p class="kmae-contract__small"><?php esc_html_e( 'Előttünk, mint tanúk előtt:', 'ki-mit-adna-erte' ); ?></p>
		<div class="kmae-contract__signs">
			<?php for ( $kmae_i = 1; $kmae_i <= 2; $kmae_i++ ) : ?>
				<div class="kmae-contract__witness">
					<strong><?php echo esc_html( $kmae_i . '.' ); ?></strong>
					<span class="kmae-contract__line"></span><?php esc_html_e( 'Név (olvasható)', 'ki-mit-adna-erte' ); ?>
					<span class="kmae-contract__line"></span><?php esc_html_e( 'Személyi okmány típusa és száma', 'ki-mit-adna-erte' ); ?>
					<span class="kmae-contract__line"></span><?php esc_html_e( 'Lakcím', 'ki-mit-adna-erte' ); ?>
					<span class="kmae-contract__line"></span><?php esc_html_e( 'Aláírás', 'ki-mit-adna-erte' ); ?>
				</div>
			<?php endfor; ?>
		</div>
	</form>
</div>
