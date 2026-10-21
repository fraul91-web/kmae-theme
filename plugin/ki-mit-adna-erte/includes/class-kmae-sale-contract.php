<?php
/**
 * Adásvételi szerződés az elfogadott ajánlat után (eladó) + „Mire figyelj” kép és
 * letölthető ellenőrzőlista (.xlsx, vevő).
 *
 * Az eladó a chatből egy aloldalra jut (`/uzenetek/?kmae_conversation=ID&kmae_contract=1`),
 * ahol a szerződés előre kitöltve (autó + eladó adatai, vételár nélkül; rendszám,
 * alvázszám, forgalmi szám a forgalmi beolvasásból, ha volt) szerkeszthető,
 * majd Wordben letölthető vagy kinyomtatható. A szerveren semmi nem mentődik: a
 * letöltés a beküldött mezőkből, a `templates/contract/adasveteli/` kicsomagolt
 * DOCX-sablonból azonnal készül és rögtön ki is megy.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Sale_Contract {

	const QUERY_VAR    = 'kmae_contract';
	const TEMPLATE_DIR = 'templates/contract/adasveteli/';
	const TIPS_IMAGE   = 'assets/images/mire-figyelj.jpg';
	const NONCE        = 'kmae_sale_contract_nonce';
	const CHECK_ON     = '☒';
	const CHECK_OFF    = '□';
	const CHECKLIST_NONCE = 'kmae_buyer_checklist_nonce';

	/**
	 * Mezők: kulcs => array( max hossz, üres értéknél a sablon eredeti szövege ).
	 * A kulcsok a sablon `{{kulcs}}` helyőrzői.
	 *
	 * @return array<string,array>
	 */
	public static function fields() {
		$person  = array( 'nev', 'szul_nev', 'szul_hely_ido', 'anyja_neve', 'okmany', 'lakcim', 'allampolgarsag' );
		$company = array( 'nev', 'szekhely', 'cegjegyzek', 'kepviselo' );
		$fields  = array(
			'rendszam'        => array( 20, '' ),
			'alvazszam'       => array( 30, '' ),
			'gyartmany'       => array( 40, '' ),
			'tipus'           => array( 40, '' ),
			'gyartasi_ev'     => array( 10, '' ),
			'forgalmi_szam'   => array( 30, '' ),
			'torzskonyv_szam' => array( 30, '' ),
		);
		foreach ( array( 'e_', 'v_' ) as $prefix ) {
			foreach ( $person as $key ) {
				$fields[ $prefix . $key ] = array( 80, '' );
			}
		}
		foreach ( array( 'ec_', 'vc_' ) as $prefix ) {
			foreach ( $company as $key ) {
				$fields[ $prefix . $key ] = array( 80, '' );
			}
		}
		$fields['vetelar']        = array( 20, '' );
		$fields['vetelar_szoveg'] = array( 80, '' );
		$fields['fizetes']        = array( 80, 'Módja:                                                        ;                   év                       hónap                   nap' );
		$fields['okmany_atadas']  = array( 60, '                       év                               hónap                       nap                   ' );
		$fields['birtokba_lepes'] = array( 60, '          év                             hónap                       nap                   óra                       perc' );
		$fields['km_allas']       = array( 20, '                                                             km ' );
		$fields['hatalyba_lepes'] = array( 60, '                         év                hónap                       nap                   ' );
		$fields['megjegyzes']     = array( 2000, '' );
		$fields['datum']          = array( 60, 'év                               hónap                       nap' );
		return $fields;
	}

	public static function tips_image_url() {
		return KMAE_PLUGIN_URL . self::TIPS_IMAGE;
	}

	public static function page_url( $conversation_id ) {
		return add_query_arg( self::QUERY_VAR, 1, KMAE_Pages::conversation_thread_url( $conversation_id ) );
	}

	public static function is_contract_request() {
		return ! empty( $_GET[ self::QUERY_VAR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Az elfogadott ajánlat, ha a felhasználó ennek a beszélgetésnek az eladója.
	 *
	 * @param object $conversation Conversation row.
	 * @param int    $user_id      User.
	 * @return object|null
	 */
	public static function get_deal( $conversation, $user_id ) {
		if ( ! $conversation || (int) $conversation->seller_id !== (int) $user_id ) {
			return null;
		}
		$offer = KMAE_Chat_Offers::get_accepted_for_listing( (int) $conversation->listing_id );
		return $offer && (int) $offer->conversation_id === (int) $conversation->id ? $offer : null;
	}

	/**
	 * Előre kitöltött értékek: autó a hirdetésből, eladó a profilból. Vételár nincs benne.
	 *
	 * @param object $conversation Conversation row.
	 * @return array<string,string>
	 */
	public static function prefill( $conversation ) {
		$values = array_fill_keys( array_keys( self::fields() ), '' );
		$specs  = KMAE_Vehicle::get_specs( (int) $conversation->listing_id );
		$seller = (int) $conversation->seller_id;

		$values['gyartmany']   = (string) $specs['brand'];
		$values['tipus']       = (string) $specs['model'];
		$values['gyartasi_ev'] = $specs['year'] ? (string) $specs['year'] : '';
		$values['km_allas']    = $specs['mileage'] ? number_format( (int) $specs['mileage'], 0, '', ' ' ) : '';

		/* Rendszám, alvázszám, forgalmi szám: csak ha a hirdetést forgalmi
		   beolvasással adták fel; különben üres marad, kézzel kitölthető. */
		foreach ( KMAE_Registration_Scan::get_ids( (int) $conversation->listing_id ) as $key => $value ) {
			$values[ $key ] = $value;
		}

		if ( KMAE_Auth::is_company( $seller ) ) {
			$values['ec_nev']      = KMAE_Company::get_company_name( $seller );
			$values['ec_szekhely'] = KMAE_Company::get_address( $seller );
		} else {
			$name = trim( get_user_meta( $seller, 'last_name', true ) . ' ' . get_user_meta( $seller, 'first_name', true ) );
			if ( '' === $name ) {
				$user = get_userdata( $seller );
				$name = $user ? (string) $user->display_name : '';
			}
			$values['e_nev'] = $name;
		}

		$values['megjegyzes'] = __( 'Eladó: A jármű műszaki állapotáról és minden tudomásom szerinti hibájáról tájékoztattam a Vevőt.', 'ki-mit-adna-erte' )
			. "\n"
			. __( 'Vevő: A járművet megtekintettem, minden lényeges információ birtokában vagyok, és a fenti feltételeket elfogadom.', 'ki-mit-adna-erte' );

		return array( 'jog' => 'visszterhes' ) + $values;
	}

	/**
	 * Szerződés aloldal (az `[kmae_messages]` shortcode hívja).
	 *
	 * @param object $conversation Conversation row.
	 * @param int    $user_id      User.
	 * @return string
	 */
	public static function render( $conversation, $user_id ) {
		$back = KMAE_Pages::conversation_thread_url( (int) $conversation->id );
		if ( ! self::get_deal( $conversation, $user_id ) ) {
			return '<div class="kmae-notice kmae-notice-error"><p>' . esc_html__( 'Az adásvételi szerződés csak az eladónak érhető el, egy elfogadott ajánlat után.', 'ki-mit-adna-erte' ) . '</p><p><a href="' . esc_url( $back ) . '" class="kmae-btn kmae-btn-secondary">' . esc_html__( 'Vissza a beszélgetéshez', 'ki-mit-adna-erte' ) . '</a></p></div>';
		}

		$values = self::prefill( $conversation );
		$limits = self::fields();
		ob_start();
		include KMAE_PLUGIN_DIR . 'templates/sale-contract.php';
		return ob_get_clean();
	}

	/**
	 * POST `download_sale_contract`: a beküldött mezőkből DOCX, mentés nélkül.
	 */
	public static function process_download() {
		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_die( esc_html__( 'A kérés lejárt. Töltsd újra az oldalt.', 'ki-mit-adna-erte' ), '', array( 'response' => 403 ) );
		}

		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$conversation    = $conversation_id ? KMAE_Conversations::get( $conversation_id ) : null;
		if ( ! self::get_deal( $conversation, get_current_user_id() ) ) {
			wp_die( esc_html__( 'Az adásvételi szerződés nem érhető el.', 'ki-mit-adna-erte' ), '', array( 'response' => 403 ) );
		}

		$posted = isset( $_POST['kmae_contract'] ) && is_array( $_POST['kmae_contract'] ) ? wp_unslash( $_POST['kmae_contract'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$values = array();
		foreach ( self::fields() as $key => $field ) {
			$raw            = isset( $posted[ $key ] ) && is_scalar( $posted[ $key ] ) ? (string) $posted[ $key ] : '';
			$clean          = 'megjegyzes' === $key ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
			$values[ $key ] = self::cut( $clean, $field[0] );
		}
		$values['jog'] = isset( $posted['jog'] ) && in_array( $posted['jog'], array( 'visszterhes', 'ingyenes' ), true ) ? $posted['jog'] : '';

		$docx = self::build_docx( $values );
		if ( '' === $docx ) {
			wp_die( esc_html__( 'A szerződést most nem sikerült elkészíteni. Próbáld újra később.', 'ki-mit-adna-erte' ), '', array( 'response' => 500 ) );
		}

		$name = sanitize_title( remove_accents( trim( $values['gyartmany'] . ' ' . $values['tipus'] ) ) );
		$file = 'adasveteli-szerzodes' . ( $name ? '-' . $name : '' ) . '.docx';

		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
		header( 'Content-Disposition: attachment; filename="' . $file . '"' );
		header( 'Content-Length: ' . strlen( $docx ) );
		echo $docx; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * A vevő ellenőrzőlistájának szempontjai, a javasolt ellenőrzési sorrendben
	 * (karosszéria → gumik → csövek → hűtőrendszer → elektromos → váltó → fékek → próbaút).
	 * A fékek szemrevételezése külön pont; a működésük a próbaút része.
	 *
	 * @return array[] Elemenként: label (szempont), note (mikor ellenőrizd).
	 */
	public static function checklist_items() {
		return array(
			array(
				'label' => __( 'Karosszéria és korrózió', 'ki-mit-adna-erte' ),
				'note'  => __( 'Először járd körbe az autót, nézd meg a rozsdát, a fényezést és az illesztéseket.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Gumik és kerekek', 'ki-mit-adna-erte' ),
				'note'  => __( 'Ellenőrizd a profilmélységet, a kopást, a repedéseket és a sérüléseket.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Csövek és csatlakozások', 'ki-mit-adna-erte' ),
				'note'  => __( 'A motortérben és az autó alatti hozzáférhető részeken keresd a szivárgást, repedéseket és sérüléseket.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Hűtőrendszer', 'ki-mit-adna-erte' ),
				'note'  => __( 'Hideg motornál ellenőrizd a hűtőfolyadék szintjét és a látható szivárgásokat.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Elektromos rendszer és műszerfal', 'ki-mit-adna-erte' ),
				'note'  => __( 'Gyújtáskor figyeld a visszajelzőket, majd próbáld ki a világítást, a klímát és az elektromos berendezéseket.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Váltó és kuplung', 'ki-mit-adna-erte' ),
				'note'  => __( 'Álló helyzetben végezd el az alapellenőrzést, majd menet közben figyeld a működésüket is.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Fékek', 'ki-mit-adna-erte' ),
				'note'  => __( 'Álló helyzetben ellenőrizd a fékpedált és a fékek látható állapotát. A fékezést a próbaúton teszteld.', 'ki-mit-adna-erte' ),
			),
			array(
				'label' => __( 'Próbaút', 'ki-mit-adna-erte' ),
				'note'  => __( 'Biztonságos körülmények között menet közben próbáld ki a fékezést is, majd értékeld együtt a motor, a váltó, a fékek és a futómű viselkedését.', 'ki-mit-adna-erte' ),
			),
		);
	}

	/**
	 * Az elfogadott ajánlat, ha a felhasználó ennek a beszélgetésnek a vevője.
	 *
	 * @param object|null $conversation Conversation row.
	 * @param int         $user_id      User.
	 * @return object|null
	 */
	public static function get_buyer_deal( $conversation, $user_id ) {
		if ( ! $conversation || (int) $conversation->buyer_id !== (int) $user_id ) {
			return null;
		}
		$offer = KMAE_Chat_Offers::get_accepted_for_listing( (int) $conversation->listing_id );
		return $offer && (int) $offer->conversation_id === (int) $conversation->id ? $offer : null;
	}

	/**
	 * POST `download_buyer_checklist`: a vevő üres ellenőrzőlistája .xlsx-ben, mentés nélkül.
	 */
	public static function process_checklist_download() {
		$nonce = isset( $_POST[ self::CHECKLIST_NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CHECKLIST_NONCE ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::CHECKLIST_NONCE ) ) {
			wp_die( esc_html__( 'A kérés lejárt. Töltsd újra az oldalt.', 'ki-mit-adna-erte' ), '', array( 'response' => 403 ) );
		}

		$conversation_id = isset( $_POST['kmae_conversation_id'] ) ? absint( $_POST['kmae_conversation_id'] ) : 0;
		$conversation    = $conversation_id ? KMAE_Conversations::get( $conversation_id ) : null;
		if ( ! self::get_buyer_deal( $conversation, get_current_user_id() ) ) {
			wp_die( esc_html__( 'Az ellenőrzőlista nem érhető el.', 'ki-mit-adna-erte' ), '', array( 'response' => 403 ) );
		}

		$xlsx = self::build_checklist_xlsx();
		if ( '' === $xlsx ) {
			wp_die( esc_html__( 'Az ellenőrzőlistát most nem sikerült elkészíteni. Próbáld újra később.', 'ki-mit-adna-erte' ), '', array( 'response' => 500 ) );
		}

		$title = (int) $conversation->listing_id && get_post( (int) $conversation->listing_id )
			? KMAE_Vehicle::get_display_title( (int) $conversation->listing_id )
			: '';
		$name  = sanitize_title( remove_accents( $title ) );
		$file  = 'ellenorzolista' . ( $name ? '-' . $name : '' ) . '.xlsx';

		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $file . '"' );
		header( 'Content-Length: ' . strlen( $xlsx ) );
		echo $xlsx; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Az ellenőrzőlista bájtjai: „Szempont” + üres „Megfelelt?” oszlop, amelyben
	 * legördülőből ✔ (zöld) vagy ✖ (piros) választható, és a „Mire figyelj” magyarázat.
	 * ('' hiba esetén).
	 *
	 * @return string
	 */
	public static function build_checklist_xlsx() {
		if ( ! function_exists( 'gzdeflate' ) ) {
			return '';
		}

		$tick  = '✔';
		$cross = '✖';
		$items = self::checklist_items();
		$last  = count( $items ) + 1;
		$head  = '<row r="1" ht="26" customHeight="1">'
			. '<c r="A1" s="1" t="inlineStr"><is><t>' . self::xml_cell( __( 'Szempont', 'ki-mit-adna-erte' ) ) . '</t></is></c>'
			. '<c r="B1" s="1" t="inlineStr"><is><t>' . self::xml_cell( __( 'Megfelelt?', 'ki-mit-adna-erte' ) ) . '</t></is></c>'
			. '<c r="C1" s="1" t="inlineStr"><is><t>' . self::xml_cell( __( 'Mikor ellenőrizd?', 'ki-mit-adna-erte' ) ) . '</t></is></c>'
			. '</row>';
		$rows  = '';
		foreach ( $items as $index => $item ) {
			$row    = $index + 2;
			// Kb. 75 karakter fér egy sorba a 80-as C oszlopban; ennyi sorhoz igazítjuk a magasságot.
			$lines  = (int) ceil( strlen( $item['note'] ) / 75 );
			$height = max( 24, $lines * 17 + 10 );
			$rows  .= '<row r="' . $row . '" ht="' . $height . '" customHeight="1">'
				. '<c r="A' . $row . '" s="2" t="inlineStr"><is><t>' . self::xml_cell( $item['label'] ) . '</t></is></c>'
				. '<c r="B' . $row . '" s="3"/>'
				. '<c r="C' . $row . '" s="4" t="inlineStr"><is><t>' . self::xml_cell( $item['note'] ) . '</t></is></c>'
				. '</row>';
		}

		$ns    = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$sheet = $xml . '<worksheet xmlns="' . $ns . '">'
			. '<dimension ref="A1:C' . $last . '"/>'
			. '<sheetViews><sheetView workbookViewId="0" tabSelected="1" showGridLines="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="B2" sqref="B2"/></sheetView></sheetViews>'
			. '<sheetFormatPr defaultRowHeight="20"/>'
			. '<cols><col min="1" max="1" width="44" customWidth="1"/><col min="2" max="2" width="16" customWidth="1"/><col min="3" max="3" width="80" customWidth="1"/></cols>'
			. '<sheetData>' . $head . $rows . '</sheetData>'
			. '<conditionalFormatting sqref="B2:B' . $last . '">'
			. '<cfRule type="cellIs" dxfId="0" priority="1" operator="equal"><formula>"' . $tick . '"</formula></cfRule>'
			. '<cfRule type="cellIs" dxfId="1" priority="2" operator="equal"><formula>"' . $cross . '"</formula></cfRule>'
			. '</conditionalFormatting>'
			. '<dataValidations count="1"><dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1"'
			. ' promptTitle="' . esc_attr( __( 'Megfelelt?', 'ki-mit-adna-erte' ) ) . '"'
			. ' prompt="' . esc_attr( __( 'Válassz: ✔ megfelelt, ✖ nem felelt meg.', 'ki-mit-adna-erte' ) ) . '"'
			. ' errorTitle="' . esc_attr( __( 'Megfelelt?', 'ki-mit-adna-erte' ) ) . '"'
			. ' error="' . esc_attr( __( 'Csak ✔ vagy ✖ választható.', 'ki-mit-adna-erte' ) ) . '"'
			. ' sqref="B2:B' . $last . '"><formula1>"' . $tick . ',' . $cross . '"</formula1></dataValidation></dataValidations>'
			. '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
			. '</worksheet>';

		$styles = $xml . '<styleSheet xmlns="' . $ns . '">'
			. '<fonts count="3">'
			. '<font><sz val="12"/><name val="Calibri"/></font>'
			. '<font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
			. '<font><b/><sz val="16"/><name val="Segoe UI Symbol"/></font>'
			. '</fonts>'
			. '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FF1C69D5"/><bgColor indexed="64"/></patternFill></fill></fills>'
			. '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
			. '<border><left style="thin"><color rgb="FFBCD8F6"/></left><right style="thin"><color rgb="FFBCD8F6"/></right><top style="thin"><color rgb="FFBCD8F6"/></top><bottom style="thin"><color rgb="FFBCD8F6"/></bottom><diagonal/></border></borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="5">'
			. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
			. '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>'
			. '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
			. '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
			. '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1" indent="1"/></xf>'
			. '</cellXfs>'
			. '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
			. '<dxfs count="2">'
			. '<dxf><font><b/><color rgb="FF12936D"/></font><fill><patternFill patternType="solid"><fgColor rgb="FFE9F9F2"/><bgColor rgb="FFE9F9F2"/></patternFill></fill></dxf>'
			. '<dxf><font><b/><color rgb="FFD4333A"/></font><fill><patternFill patternType="solid"><fgColor rgb="FFFDEEEE"/><bgColor rgb="FFFDEEEE"/></patternFill></fill></dxf>'
			. '</dxfs>'
			. '</styleSheet>';

		$files = array(
			'[Content_Types].xml'        => $xml . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
				. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
				. '<Default Extension="xml" ContentType="application/xml"/>'
				. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
				. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
				. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
				. '</Types>',
			'_rels/.rels'                => $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
				. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
				. '</Relationships>',
			'xl/workbook.xml'            => $xml . '<workbook xmlns="' . $ns . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
				. '<sheets><sheet name="' . self::xml_cell( __( 'Ellenőrzőlista', 'ki-mit-adna-erte' ) ) . '" sheetId="1" r:id="rId1"/></sheets>'
				. '</workbook>',
			'xl/_rels/workbook.xml.rels' => $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
				. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
				. '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
				. '</Relationships>',
			'xl/styles.xml'              => $styles,
			'xl/worksheets/sheet1.xml'   => $sheet,
		);

		return self::zip( $files );
	}

	/**
	 * Cella- / attribútumszöveg XML-ben.
	 *
	 * @param string $value Plain text.
	 * @return string
	 */
	private static function xml_cell( $value ) {
		return htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * A kitöltött DOCX bájtjai ('' hiba esetén).
	 *
	 * @param array $values Field values.
	 * @return string
	 */
	public static function build_docx( array $values ) {
		$dir = KMAE_PLUGIN_DIR . self::TEMPLATE_DIR;
		if ( ! is_dir( $dir ) || ! function_exists( 'gzdeflate' ) ) {
			return '';
		}

		$replace = array(
			'{{jog_visszterhes}}' => 'visszterhes' === ( $values['jog'] ?? '' ) ? self::CHECK_ON : self::CHECK_OFF,
			'{{jog_ingyenes}}'    => 'ingyenes' === ( $values['jog'] ?? '' ) ? self::CHECK_ON : self::CHECK_OFF,
		);
		foreach ( self::fields() as $key => $field ) {
			$value = trim( (string) ( $values[ $key ] ?? '' ) );
			if ( '' === $value ) {
				$value = $field[1];
			} elseif ( 'km_allas' === $key ) {
				$value .= ' km';
			}
			$replace[ '{{' . $key . '}}' ] = self::xml_text( $value );
		}

		$files    = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file ) {
			$path           = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) ) );
			$data           = (string) file_get_contents( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$files[ $path ] = 'word/document.xml' === $path ? strtr( $data, $replace ) : $data;
		}
		if ( empty( $files['word/document.xml'] ) || ! isset( $files['[Content_Types].xml'] ) ) {
			return '';
		}

		/* A [Content_Types].xml legyen elöl, ahogy a Word is írja. */
		$files = array( '[Content_Types].xml' => $files['[Content_Types].xml'] ) + $files;
		return self::zip( $files );
	}

	/**
	 * XML-be írható szöveg; a sortörés Word-sortörés lesz.
	 *
	 * @param string $value Plain text.
	 * @return string
	 */
	private static function xml_text( $value ) {
		$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value );
		$value = htmlspecialchars( $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
		return preg_replace( '/\r\n|\r|\n/', '</w:t><w:br/><w:t xml:space="preserve">', $value );
	}

	private static function cut( $value, $max ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/**
	 * Minimális ZIP író (deflate), hogy ne kelljen ZipArchive a szerveren.
	 *
	 * @param array<string,string> $files Path => contents.
	 * @return string
	 */
	private static function zip( array $files ) {
		$time    = getdate();
		$dostime = ( $time['hours'] << 11 ) | ( $time['minutes'] << 5 ) | intdiv( $time['seconds'], 2 );
		$dosdate = ( ( $time['year'] - 1980 ) << 9 ) | ( $time['mon'] << 5 ) | $time['mday'];
		$body    = '';
		$central = '';

		foreach ( $files as $name => $data ) {
			$crc      = crc32( $data );
			$packed   = gzdeflate( $data, 6 );
			$offset   = strlen( $body );
			$common   = pack( 'vvvvvVVVv', 20, 0x0800, 8, $dostime, $dosdate, $crc, strlen( $packed ), strlen( $data ), strlen( $name ) );
			$body    .= "PK\x03\x04" . $common . pack( 'v', 0 ) . $name . $packed;
			$central .= "PK\x01\x02" . pack( 'v', 20 ) . $common . pack( 'vvvvVV', 0, 0, 0, 0, 0, $offset ) . $name;
		}

		return $body . $central . "PK\x05\x06" . pack( 'vvvvVVv', 0, 0, count( $files ), count( $files ), strlen( $central ), strlen( $body ), 0 );
	}
}
