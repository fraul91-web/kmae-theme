<?php
/**
 * Guided listing wizard: the single source of the wizard phases.
 *
 * The wizard keeps no server side state — a half-finished listing is simply
 * started over by the user.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Listing_Wizard {

	const PHASE_VEHICLE = 'auto';
	const PHASE_LISTING = 'listing';
	const PHASE_EXTRAS  = 'extras';
	const PHASE_SALE    = 'sale';
	const PHASE_PHOTOS  = 'photos';
	const PHASE_DONE    = 'done';

	/**
	 * Wizard phases in business order. Single source for the progress bar.
	 *
	 * @return array<string, array{label:string,short:string,icon:string}>
	 */
	/**
	 * Az asszisztens szempontjai a hirdetés szövegéhez.
	 *
	 * Az Albira NEM ír a felhasználó helyett. Minden szemponthoz tartozik egy
	 * FIX mondatkezdet: a „+" gombbal ez kerül a szövegmezőbe, a folytatást
	 * pedig az eladó írja a saját szavaival.
	 *
	 * Mezők:
	 *   label  — a szempont neve a panelen
	 *   level  — „fontos" vagy „ajanlott"
	 *   icon   — a panelen megjelenő jelvény ikonja
	 *   insert — a beszúrandó fix szöveg
	 *   marker — rövid, jellegzetes részlet, ami akkor is megmarad, ha az eladó
	 *            beleír a blokkba. Ebből tudjuk, hogy a szempont bent van-e.
	 *            Szándékosan nem szavakat keresünk a szövegben: azt figyeljük,
	 *            amit MI illesztettünk be — így nincs találgatás.
	 *   caret  — hányadik karakter után álljon a kurzor a beszúrás után
	 *            (ha hiányzik, a szöveg végére kerül)
	 *
	 * A helyszín szándékosan NINCS a listában: a megyét és a várost külön,
	 * kötelező mezőben kérjük be, és a hirdetésen is külön jelenik meg —
	 * a szövegben megismételve csak duplázás lenne.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function description_topics() {
		return array(
			'reason'      => array(
				'label'  => __( 'Miért adod el', 'ki-mit-adna-erte' ),
				'level'  => 'fontos',
				'icon'   => 'lightbulb',
				'insert' => __( 'Az autó eladásának az oka, hogy ', 'ki-mit-adna-erte' ),
				'marker' => __( 'Az autó eladásának az oka', 'ki-mit-adna-erte' ),
			),
			'defects'     => array(
				'label'  => __( 'Ismert hibák', 'ki-mit-adna-erte' ),
				'level'  => 'fontos',
				'icon'   => 'file-text',
				'insert' => __( "Ezt fedeztem fel eddig mint hiba vagy hibák:\n", 'ki-mit-adna-erte' ),
				'marker' => __( 'Ezt fedeztem fel eddig mint hiba', 'ki-mit-adna-erte' ),
			),
			'service'     => array(
				'label'  => __( 'Szerviz, mit cseréltél', 'ki-mit-adna-erte' ),
				'level'  => 'fontos',
				'icon'   => 'wrench',
				'insert' => __( "Amiket biztosan cseréltem az autón:\n", 'ki-mit-adna-erte' ),
				'marker' => __( 'Amiket biztosan cseréltem az autón', 'ki-mit-adna-erte' ),
			),
			'usage'       => array(
				'label'  => __( 'Hogyan használtad', 'ki-mit-adna-erte' ),
				'level'  => 'fontos',
				'icon'   => 'user',
				'insert' => __( 'Amíg nálam volt az autó, ', 'ki-mit-adna-erte' ),
				'marker' => __( 'Amíg nálam volt az autó', 'ki-mit-adna-erte' ),
			),
			'consumption' => array(
				'label'  => __( 'Fogyasztás', 'ki-mit-adna-erte' ),
				'level'  => 'ajanlott',
				'icon'   => 'fuel',
				'insert' => __( "Az autó fogyasztása:\nVárosi: \nVegyes: \nAutópálya: ", 'ki-mit-adna-erte' ),
				'marker' => __( 'Az autó fogyasztása:', 'ki-mit-adna-erte' ),
				/* A kurzor rögtön az első érték helyére álljon. */
				'caret'  => 29,
			),
			'tyres'       => array(
				/* A segítség a panel feliratában van — a beszúrt szöveg maradjon
				   tiszta, hogy a kész hirdetésben ne legyen benne a zárójeles
				   emlékeztető. */
				'label'  => __( 'Gumik, szettek (nyári, téli, 4 évszakos)', 'ki-mit-adna-erte' ),
				'level'  => 'ajanlott',
				'icon'   => 'tyre',
				'insert' => __( 'Az autón jelenleg ilyen gumik vannak: ', 'ki-mit-adna-erte' ),
				'marker' => __( 'Az autón jelenleg ilyen gumik vannak', 'ki-mit-adna-erte' ),
			),
			'documents'   => array(
				'label'  => __( 'Okmányok, szervizkönyv', 'ki-mit-adna-erte' ),
				'level'  => 'ajanlott',
				'icon'   => 'book-open',
				'insert' => __( 'Az autó okmányai: ', 'ki-mit-adna-erte' ),
				'marker' => __( 'Az autó okmányai', 'ki-mit-adna-erte' ),
			),
			'equipment'   => array(
				'label'  => __( 'Extrák, felszereltség', 'ki-mit-adna-erte' ),
				'level'  => 'ajanlott',
				'icon'   => 'star',
				'insert' => __( "Extrák:\n", 'ki-mit-adna-erte' ),
				'marker' => __( 'Extrák:', 'ki-mit-adna-erte' ),
			),
			'condition'   => array(
				'label'  => __( 'Karosszéria, utastér, motor állapota', 'ki-mit-adna-erte' ),
				'level'  => 'ajanlott',
				'icon'   => 'car',
				'insert' => __( "- Karosszéria állapota: \n- Utastér állapota: \n- Motor állapota: ", 'ki-mit-adna-erte' ),
				'marker' => __( '- Karosszéria állapota:', 'ki-mit-adna-erte' ),
				'caret'  => 24,
			),
		);
	}

	/**
	 * Az asszisztens bemutatkozása a szövegíró lépésen.
	 *
	 * Soronként jelenik meg, kis szünettel — hogy el lehessen olvasni, de ne
	 * kelljen rá várni. A név a bejelentkezett felhasználóé; ha nincs, a
	 * köszönés név nélkül is működik.
	 *
	 * @return array<int, string>
	 */
	public static function assistant_intro() {
		$base = 'https://albira.hu/wp-content/uploads/2026/10/';

		/*
		 * Soronként jelenik meg, és a kép is vált közben. A `highlight` sornál
		 * a lista első „+" gombja felvillan, hogy látszódjon, miről van szó.
		 */
		return array(
			array(
				/* translators: %s: a kék „+" gomb kis jelvénye, ugyanaz mint a lenti gombon */
				'text'      => sprintf( __( 'Válassz a lenti mezőkből, és nyomj a %s gombra a hozzáadáshoz.', 'ki-mit-adna-erte' ), self::assist_inline_badge( 'plus' ) ),
				'image'     => $base . 'lefelemutat3.webp',
				'highlight' => true,
			),
			array(
				'text' => __( 'A gomb csak elkezdi a mondatot, a folytatást te írod.', 'ki-mit-adna-erte' ),
			),
			array(
				/* translators: %s: a piros „−" gomb kis jelvénye, ugyanaz mint a lenti gombon */
				'text' => sprintf( __( 'Ha mégsem tetszik, egyszerűen töröld, vagy nyomj a %s jelre.', 'ki-mit-adna-erte' ), self::assist_inline_badge( 'minus' ) ),
			),
		);
	}

	/**
	 * Az asszisztens szövegébe ágyazott kis jelvény — pontosan úgy fest,
	 * mint a lenti hozzáadó/eltávolító gomb, hogy a mondat ne csak leírja,
	 * hanem meg is mutassa, melyik gombra gondol.
	 *
	 * @param string $type 'plus' vagy 'minus'.
	 * @return string Biztonságos, statikus HTML (nincs benne felhasználói adat).
	 */
	private static function assist_inline_badge( $type ) {
		$minus = 'minus' === $type;
		$glyph = $minus ? '&minus;' : '+';
		$mod   = $minus ? ' kmae-assist-inline-badge--minus' : '';

		/* Az idézőjelek és a jelvény együtt, nem törhető egységként: e nélkül
		   a nyitó idézőjel a sor végén ragadna, a jelvény pedig átugrana a
		   következő sor elejére. */
		return '<span class="kmae-assist-inline-quote">„'
			. '<span class="kmae-assist-inline-badge' . $mod . '" aria-hidden="true">' . $glyph . '</span>'
			. '”</span>';
	}

	/**
	 * Az asszisztens reakciói a hozzáadásokra, sorszám szerint: az első
	 * hozzáadásnál az 1-es, a másodiknál a 2-es szólal meg. Amelyik sorszámhoz
	 * nincs bejegyzés, ott nem szól bele — nem dicsér minden kattintásnál.
	 *
	 * @return array<int, array{text:string,image:string}>
	 */
	public static function assistant_reactions() {
		$base = 'https://albira.hu/wp-content/uploads/2026/10/';

		return array(
			1 => array(
				'text'  => __( 'Ez az!', 'ki-mit-adna-erte' ),
				'image' => $base . 'ezaz2.webp',
			),
			2 => array(
				'text'  => __( 'Csak így tovább!', 'ki-mit-adna-erte' ),
				'image' => $base . 'csakigytovabb3.webp',
			),
		);
	}

	/**
	 * A feladás pillanatához tartozó dicséret. A fotók lépésén a „Tovább"
	 * helyett „Hirdetés feladása" áll — megnyomás után ez jelenik meg, és csak
	 * utána megy el a mentés, hogy a visszajelzés ne villanjon el.
	 *
	 * @return array{text:string,image:string}
	 */
	public static function assistant_publish() {
		return array(
			'text'  => __( 'Nagyszerű! Kész a hirdetésed!', 'ki-mit-adna-erte' ),
			'image' => 'https://albira.hu/wp-content/uploads/2026/10/csakigytovabb3.webp',
		);
	}

	/**
	 * Bátorító mondat a kitöltöttség arányához. A cél nem a 100%, hanem hogy
	 * az eladó lássa: minden kitöltött szempont növeli az eladás esélyét.
	 *
	 * @return array<int, array{min:int,text:string}>
	 */
	public static function description_progress_messages() {
		return array(
			array( 'min' => 100, 'text' => __( 'Kész. Ez a hirdetés mindent elmond, amit egy vevő tudni akar.', 'ki-mit-adna-erte' ) ),
			array( 'min' => 70, 'text' => __( 'Nagyon jó — ennyi információval kevesebb kérdés jön, és gyorsabban elkel.', 'ki-mit-adna-erte' ) ),
			array( 'min' => 40, 'text' => __( 'Ez már tisztességes hirdetés. Még néhány szempont, és kitűnik a többi közül.', 'ki-mit-adna-erte' ) ),
			array( 'min' => 10, 'text' => __( 'Jó kezdet — minél többet írsz, annál komolyabb érdeklődők jönnek.', 'ki-mit-adna-erte' ) ),
			array( 'min' => 0, 'text' => __( 'Minden kitöltött szempont növeli az esélyt, hogy eladd az autót.', 'ki-mit-adna-erte' ) ),
		);
	}

	public static function phases() {
		return array(
			self::PHASE_VEHICLE => array(
				'label' => __( 'Autó adatai', 'ki-mit-adna-erte' ),
				'short' => __( 'Az autó adatai', 'ki-mit-adna-erte' ),
				'icon'  => 'car',
			),
			self::PHASE_LISTING => array(
				'label' => __( 'Hirdetés adatai', 'ki-mit-adna-erte' ),
				'short' => __( 'A hirdetés tartalma', 'ki-mit-adna-erte' ),
				'icon'  => 'pencil',
			),
			self::PHASE_EXTRAS  => array(
				'label' => __( 'Extrák és állapot', 'ki-mit-adna-erte' ),
				'short' => __( 'Opcionális vállalások', 'ki-mit-adna-erte' ),
				'icon'  => 'star',
			),
			self::PHASE_SALE    => array(
				'label' => __( 'Értékesítés módja', 'ki-mit-adna-erte' ),
				'short' => __( 'Az értékesítés módja', 'ki-mit-adna-erte' ),
				'icon'  => 'tag',
			),
			self::PHASE_PHOTOS  => array(
				'label' => __( 'Fotók', 'ki-mit-adna-erte' ),
				'short' => __( 'Kötelező autófotók', 'ki-mit-adna-erte' ),
				'icon'  => 'camera',
			),
			self::PHASE_DONE    => array(
				'label' => __( 'Kész', 'ki-mit-adna-erte' ),
				'short' => __( 'Ellenőrzés és mentés', 'ki-mit-adna-erte' ),
				'icon'  => 'circle-check',
			),
		);
	}
}
