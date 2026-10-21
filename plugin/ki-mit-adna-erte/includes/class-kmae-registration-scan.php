<?php
/**
 * Forgalmi engedély beolvasása a hirdetés-wizardhoz (opcionális).
 *
 * - A böngésző előkészíti a képet (kivágás a zöld papírra, kicsinyítés),
 *   a szerver továbbadja az OpenAI-nak (Responses API, `store: false`),
 *   és CSAK a jármű adatait kéri vissza (név, lakcím, születési adat nem
 *   szerepel a sémában).
 * - A rendszám, az alvázszám és a forgalmi engedély száma nem jelenik meg a
 *   beolvasóban: a wizard rejtett mezőben viszi tovább, és a hirdetés
 *   `META_IDS` metájába kerül — csak az adásvételi szerződés tölti ki vele.
 * - A képeket nem mentjük: a PHP ideiglenes feltöltött fájljai a kérés végén
 *   törlődnek, naplóba sem kerülnek.
 * - Az eredményt az Albira értékkészletére fordítjuk (katalógus-márka/modell,
 *   üzemanyag- és váltókód, LE, műszaki dátum).
 * - API-kulcs: wp-config `KMAE_OPENAI_API_KEY`, különben Albira → Beállítások.
 *
 * Kliens: assets/js/registration-scan.js, sablon: templates/partials/registration-scan.php.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Registration_Scan {

	const NONCE          = 'kmae_registration_scan';
	const OPTION_ENABLED = 'kmae_scan_enabled';
	const OPTION_KEY     = 'kmae_openai_api_key';
	const OPTION_MODEL   = 'kmae_scan_model';
	const OPTION_FALLBACK = 'kmae_scan_fallback_model';

	const DEFAULT_MODEL    = 'gpt-4.1-mini';
	const DEFAULT_FALLBACK = 'gpt-4.1';

	const MAX_IMAGES     = 2;
	const MAX_FILE_BYTES = 6291456; /* 6 MB — a böngésző ~1 MB-os JPEG-et küld */
	const DAILY_LIMIT    = 10;      /* beolvasás / felhasználó / nap */
	const API_TIMEOUT    = 45;      /* mp / modellhívás (Cloudflare 100 mp-nél vág) */

	/* Hirdetés-meta: a szerződéshez kiolvasott azonosítók (kulcs = szerződésmező). */
	const META_IDS = '_kmae_registration_ids';

	/* Azonosítók: modellmező => max hossz. Nem számítanak a tartalékmodell
	   döntésébe, és nem jelennek meg a beolvasóban. */
	const ID_FIELDS = array(
		'rendszam'      => 12,
		'alvazszam'     => 17,
		'forgalmi_szam' => 20,
	);

	/* A modelltől kért műszaki mezők. */
	const FIELDS = array(
		'marka',
		'modell',
		'gyartasi_ev',
		'sebessegvalto_kod',
		'hengerurtartalom_cm3',
		'teljesitmeny_kw',
		'uzemanyag',
		'muszaki_ervenyes',
	);

	/* Modellmező → wizard-mező (a „bizonytalan” jelöléshez). */
	const FIELD_MAP = array(
		'marka'                => 'brand',
		'modell'               => 'model',
		'gyartasi_ev'          => 'year',
		'sebessegvalto_kod'    => 'transmission',
		'hengerurtartalom_cm3' => 'engine_cc',
		'teljesitmeny_kw'      => 'power_hp',
		'uzemanyag'            => 'fuel',
		'muszaki_ervenyes'     => 'mot',
	);

	public static function init() {
		add_action( 'wp_ajax_kmae_registration_scan', array( __CLASS__, 'ajax_scan' ) );
	}

	/* ------------------------------------------------------------------
	   Beállítások
	   ------------------------------------------------------------------ */

	public static function api_key() {
		if ( defined( 'KMAE_OPENAI_API_KEY' ) && '' !== trim( (string) KMAE_OPENAI_API_KEY ) ) {
			return trim( (string) KMAE_OPENAI_API_KEY );
		}
		return trim( (string) get_option( self::OPTION_KEY, '' ) );
	}

	public static function is_enabled() {
		return (bool) get_option( self::OPTION_ENABLED, 1 ) && '' !== self::api_key();
	}

	private static function model() {
		$model = trim( (string) get_option( self::OPTION_MODEL, '' ) );
		return '' !== $model ? $model : self::DEFAULT_MODEL;
	}

	private static function fallback_model() {
		$model = trim( (string) get_option( self::OPTION_FALLBACK, '' ) );
		return '' !== $model ? $model : self::DEFAULT_FALLBACK;
	}

	/**
	 * Az API-kulcs mezőt üresen beküldve a mentett kulcs megmarad.
	 *
	 * @param mixed $value Beküldött érték.
	 * @return string
	 */
	public static function sanitize_api_key( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return (string) get_option( self::OPTION_KEY, '' );
		}
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $value );
	}

	/**
	 * A kliensnek átadott beállítások.
	 *
	 * @return array<string,mixed>
	 */
	public static function client_config() {
		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( self::NONCE ),
			'maxImages' => self::MAX_IMAGES,
		);
	}

	/* ------------------------------------------------------------------
	   AJAX
	   ------------------------------------------------------------------ */

	public static function ajax_scan() {
		if ( ! is_user_logged_in() || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			self::fail( __( 'A munkamenet lejárt. Töltsd újra az oldalt, és próbáld újra.', 'ki-mit-adna-erte' ), 403 );
		}
		if ( ! self::is_enabled() ) {
			self::fail( __( 'A forgalmi beolvasása most nem érhető el. Add meg az adatokat kézzel.', 'ki-mit-adna-erte' ), 503 );
		}

		$user_id = get_current_user_id();
		$day_key = 'kmae_scan_u' . $user_id . '_' . gmdate( 'Ymd' );
		$used    = (int) get_transient( $day_key );
		/* Az adminra (tesztelés) nem vonatkozik a napi keret. */
		$limited = ! current_user_can( 'manage_options' );
		if ( $limited && $used >= self::DAILY_LIMIT ) {
			self::fail( __( 'Ma már többször beolvastál forgalmit. Holnap újra próbálhatod — addig add meg az adatokat kézzel.', 'ki-mit-adna-erte' ), 429 );
		}

		$images = self::read_uploaded_images();
		if ( is_wp_error( $images ) ) {
			self::fail( $images->get_error_message(), 400 );
		}

		if ( $limited ) {
			set_transient( $day_key, $used + 1, DAY_IN_SECONDS );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 150 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$primary = self::extract( self::model(), $images );
		if ( is_wp_error( $primary ) ) {
			self::log( $primary );
			/* A szolgáltatás hibája nem a felhasználó hibája: nem számít bele a keretbe. */
			if ( $limited ) {
				set_transient( $day_key, max( 0, $used ), DAY_IN_SECONDS );
			}
			self::fail( __( 'Most nem sikerült beolvasni a forgalmit. Próbáld újra egy kicsit később, vagy add meg az adatokat kézzel.', 'ki-mit-adna-erte' ), 502 );
		}

		$result   = $primary;
		$fallback = self::fallback_model();
		$check    = self::evaluate( $result, count( $images ) );
		if ( $check['needs_fallback'] && '' !== $fallback && $fallback !== self::model() ) {
			$second = self::extract( $fallback, $images );
			if ( is_wp_error( $second ) ) {
				self::log( $second );
			} else {
				$result = self::merge( $primary, $second );
			}
		}

		/* A műszaki dátuma kézzel írt / bélyegzett, kis felirat — ha az általános
		   olvasás nem találta, egy célzott, csak erre figyelő kérés olvassa ki. */
		if ( count( $images ) > 1 && '' === $result['muszaki_ervenyes'] ) {
			$mot = self::extract_mot( '' !== $fallback ? $fallback : self::model(), $images );
			if ( is_wp_error( $mot ) ) {
				self::log( $mot );
			} elseif ( '' !== $mot ) {
				$result['muszaki_ervenyes']  = $mot;
				$result['bizonytalan_mezok'] = array_values( array_diff( $result['bizonytalan_mezok'], array( 'muszaki_ervenyes' ) ) );
			}
		}

		if ( empty( $result['forgalmi_engedely'] ) && self::filled_count( $result ) < 2 ) {
			self::fail( __( 'Ezen a képen nem találtunk forgalmi engedélyt. Fotózd le újra a forgalmi elejét, jó fényben, egészében.', 'ki-mit-adna-erte' ), 422 );
		}

		wp_send_json_success( self::to_albira( $result ) );
	}

	/**
	 * @param string $message Felhasználónak szóló üzenet.
	 * @param int    $status  HTTP státusz.
	 */
	private static function fail( $message, $status ) {
		wp_send_json_error( array( 'message' => $message ), $status );
	}

	/**
	 * Hibanapló a hiba okával — a kép tartalma soha nem kerül bele.
	 *
	 * @param WP_Error $error Hiba.
	 */
	private static function log( $error ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'KMAE forgalmi beolvasás: ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * A feltöltött képek data URL-ként (csak memóriában).
	 *
	 * @return array<int,string>|WP_Error
	 */
	private static function read_uploaded_images() {
		$files = isset( $_FILES['kmae_scan_images'] ) ? $_FILES['kmae_scan_images'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! $files || empty( $files['tmp_name'] ) || ! is_array( $files['tmp_name'] ) ) {
			return new WP_Error( 'no_image', __( 'Nem érkezett kép. Válaszd ki a forgalmi fotóját.', 'ki-mit-adna-erte' ) );
		}

		$count = count( $files['tmp_name'] );
		if ( $count > self::MAX_IMAGES ) {
			return new WP_Error( 'too_many', __( 'Legfeljebb 2 képet tölts fel: a forgalmi elejét és hátulját.', 'ki-mit-adna-erte' ) );
		}

		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		$images  = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$tmp = (string) $files['tmp_name'][ $i ];
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
				return new WP_Error( 'upload', __( 'A kép feltöltése nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ) );
			}
			if ( (int) $files['size'][ $i ] > self::MAX_FILE_BYTES ) {
				return new WP_Error( 'too_big', __( 'A kép túl nagy. Válassz kisebbet, vagy fotózd le újra.', 'ki-mit-adna-erte' ) );
			}
			$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$mime = is_array( $info ) && isset( $info['mime'] ) ? $info['mime'] : '';
			if ( ! in_array( $mime, $allowed, true ) ) {
				return new WP_Error( 'type', __( 'Ezt a fájlt nem tudjuk képként megnyitni. JPG vagy PNG fotót válassz.', 'ki-mit-adna-erte' ) );
			}
			$bytes = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $bytes || '' === $bytes ) {
				return new WP_Error( 'read', __( 'A kép feltöltése nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ) );
			}
			$images[] = 'data:' . $mime . ';base64,' . base64_encode( $bytes ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return $images;
	}

	/* ------------------------------------------------------------------
	   Modellhívás
	   ------------------------------------------------------------------ */

	private static function system_prompt() {
		return <<<'PROMPT'
Magyar forgalmi engedély (zöldes papír) fotóiról olvasol le adatokat.
Az elülső oldalon minden adat előtt egy sötét négyzetben álló kód van
(A, B, D.1, D.2, D.3, P.1 ...), a kód MELLETT jobbra áll az érték.

Mit hol találsz:
- marka: "D.1" melletti érték (pl. "SKODA", "VOLKSWAGEN").
- modell: "D.3" melletti érték (kereskedelmi név, pl. "FABIA", "GOLF"). NEM a D.2 (típuskód).
- hengerurtartalom_cm3: "P.1" melletti szám (pl. "1198 CM3" → "1198").
- teljesitmeny_kw: "P.2" melletti szám kW-ban (pl. "47 KW" → "47"). Ne váltsd át.
- uzemanyag: "P.3" melletti szó (pl. "Benzin", "Gázolaj", "Elektromos", "Benzin-Elektromos").
- gyartasi_ev: a "GYÁRTÁSI ÉV:" felirat utáni évszám (a Q mező alatt, külön sorban).
  A "B" / "I" dátum (első forgalomba helyezés) NEM a gyártási év.
- sebessegvalto_kod: a "SEBESSÉGVÁLTÓ FAJTÁJA (KÓDSZÁMA):" utáni egyetlen számjegy (0/1/2/3).
- muszaki_ervenyes: a hátoldalon a "MŰSZAKI ÉRVÉNYESSÉGI IDŐ" alatti keretekbe
  kézzel írt vagy bélyegzett dátum. Ha több van, a legkésőbbi. Formátum: ÉÉÉÉ.HH.NN.
  A hónap római számmal is szerepelhet (pl. "2027. V. 14." = 2027.05.14.).
  A hátoldal fotója lehet elforgatva vagy fejjel lefelé — ettől még olvasd le.
  A "KIÁLLÍTÁS KELTE" NEM a műszaki érvényesség. Az ügyszám (pl. "26-25/064556") sem.
- rendszam: az "A" melletti érték (pl. "ABC-123", "AA BC-123").
- alvazszam: az "E" melletti járműazonosító (alvázszám, VIN), általában 17 karakter
  betűkből és számokból, szóköz nélkül.
- forgalmi_szam: magának a forgalmi engedélynek a sorszáma (okmányszám), amely a
  lapra külön van nyomtatva, jellemzően betűkkel kezdődik, utána számok
  (pl. "AB 123456"). NEM a törzskönyv száma, NEM az ügyszám.
- forgalmi_engedely: true, ha a képeken magyar forgalmi engedély látható, különben false.

Szabályok:
1. Olvasd le, ami nyomtatva/írva van, akkor is, ha a kép nem tökéletes.
   Ha egy érték olvasható, töltsd ki.
2. Ha egy értéket csak részben tudsz kiolvasni, add meg a legjobb olvasatot,
   ÉS a mező nevét tedd a bizonytalan_mezok tömbbe.
3. Üres stringet csak akkor adj, ha a mező tényleg üres a dokumentumon,
   vagy egyik képen sem látszik. Ilyenkor is tedd be a bizonytalan_mezok-be.
4. Ne találj ki adatot, ne egészítsd ki fejből (pl. a márkából ne következtess a modellre).
5. Személyes adatot (név, lakcím, születési adat) SEMMILYEN mezőbe ne írj.
6. A rendszam, alvazszam, forgalmi_szam mezőknél ne találgass: ha nem olvasható
   egyértelműen, üres string.
PROMPT;
	}

	private static function schema() {
		$properties = array();
		foreach ( array_merge( self::FIELDS, array_keys( self::ID_FIELDS ) ) as $key ) {
			$properties[ $key ] = array( 'type' => 'string' );
		}
		$properties['forgalmi_engedely'] = array( 'type' => 'boolean' );
		$properties['bizonytalan_mezok'] = array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => array_merge( self::FIELDS, array_keys( self::ID_FIELDS ), array( 'forgalmi_engedely', 'bizonytalan_mezok' ) ),
			'additionalProperties' => false,
		);
	}

	private static function supports_reasoning( $model ) {
		return (bool) preg_match( '/^(o\d|gpt-5|gpt-6)/i', $model );
	}

	/**
	 * Egy modell lefuttatása, normalizált nyers eredménnyel.
	 *
	 * @param string            $model  Modell.
	 * @param array<int,string> $images Data URL-ek.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function extract( $model, array $images ) {
		$content = array(
			array(
				'type' => 'input_text',
				'text' => 'Olvasd le a forgalmi engedélyből a kért adatokat. A képek:',
			),
		);
		foreach ( $images as $index => $image ) {
			$content[] = array(
				'type' => 'input_text',
				'text' => ( $index + 1 ) . '. kép',
			);
			$content[] = array(
				'type'      => 'input_image',
				'image_url' => $image,
				'detail'    => 'high',
			);
		}

		$body = array(
			'model'             => $model,
			'store'             => false,
			'max_output_tokens' => 4000,
			'input'             => array(
				array(
					'role'    => 'system',
					'content' => self::system_prompt(),
				),
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
			'text'              => array(
				'format' => array(
					'type'   => 'json_schema',
					'name'   => 'forgalmi_adatok',
					'strict' => true,
					'schema' => self::schema(),
				),
			),
		);
		if ( self::supports_reasoning( $model ) ) {
			$body['reasoning'] = array( 'effort' => 'low' );
		}

		$response = self::request( $body );
		if ( is_wp_error( $response ) && isset( $body['reasoning'] ) && 'bad_request' === $response->get_error_code() ) {
			unset( $body['reasoning'] );
			$response = self::request( $body );
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$raw = self::output_json( $response );
		if ( ! is_array( $raw ) ) {
			return new WP_Error( 'parse', sprintf( '%s: nem érvényes JSON válasz', $model ) );
		}

		return self::normalize( $raw );
	}

	/**
	 * A Responses API válaszának szöveges kimenete JSON-ként.
	 *
	 * @param array<string,mixed> $response Válasz.
	 * @return array<string,mixed>|null
	 */
	private static function output_json( array $response ) {
		$text = '';
		if ( isset( $response['output'] ) && is_array( $response['output'] ) ) {
			foreach ( $response['output'] as $item ) {
				if ( empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
					continue;
				}
				foreach ( $item['content'] as $part ) {
					if ( isset( $part['type'], $part['text'] ) && 'output_text' === $part['type'] ) {
						$text .= $part['text'];
					}
				}
			}
		}
		$raw = json_decode( $text, true );
		return is_array( $raw ) ? $raw : null;
	}

	/**
	 * Célzott olvasás: csak a műszaki érvényességi dátum.
	 *
	 * @param string            $model  Modell.
	 * @param array<int,string> $images Data URL-ek.
	 * @return string|WP_Error ÉÉÉÉ.HH.NN. vagy üres.
	 */
	private static function extract_mot( $model, array $images ) {
		$prompt = <<<'PROMPT'
Egy magyar forgalmi engedély fotóit kapod. EGYETLEN adatot keress: a műszaki
vizsga érvényességi idejét.

Hol van: a forgalmi HÁTOLDALÁN, a "MŰSZAKI ÉRVÉNYESSÉGI IDŐ" (vagy hasonló)
felirat alatti négyzetes keretekben. A dátumot általában bélyegző vagy kézírás
rögzíti, gyakran pecsét, aláírás vagy hologram matrica mellett.

- Ha több dátum van a keretekben, a LEGKÉSŐBBIT add meg.
- A hónap római számmal is lehet (pl. "2027. V. 14." → 2027.05.14.).
- A kép lehet elforgatva, fejjel lefelé, ferdén, rossz fényben — nézd meg alaposan.
- NEM ez: "KIÁLLÍTÁS KELTE", első forgalomba helyezés (B / I mező), ügyszám, okmányszám.
- Ha a nap nem olvasható, de az év és a hónap igen, a napot add meg 01-nek, és talalt legyen true.

Formátum: ÉÉÉÉ.HH.NN. Ha egyáltalán nem látható, üres string és talalt = false.
PROMPT;

		$content = array(
			array(
				'type' => 'input_text',
				'text' => 'Keresd meg a műszaki érvényességi dátumot. A képek:',
			),
		);
		foreach ( $images as $image ) {
			$content[] = array(
				'type'      => 'input_image',
				'image_url' => $image,
				'detail'    => 'high',
			);
		}

		$body = array(
			'model'             => $model,
			'store'             => false,
			'max_output_tokens' => 1500,
			'input'             => array(
				array(
					'role'    => 'system',
					'content' => $prompt,
				),
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
			'text'              => array(
				'format' => array(
					'type'   => 'json_schema',
					'name'   => 'muszaki',
					'strict' => true,
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'muszaki_ervenyes' => array( 'type' => 'string' ),
							'talalt'           => array( 'type' => 'boolean' ),
						),
						'required'             => array( 'muszaki_ervenyes', 'talalt' ),
						'additionalProperties' => false,
					),
				),
			),
		);
		if ( self::supports_reasoning( $model ) ) {
			$body['reasoning'] = array( 'effort' => 'low' );
		}

		$response = self::request( $body );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$raw = self::output_json( $response );
		if ( ! is_array( $raw ) || empty( $raw['talalt'] ) || ! isset( $raw['muszaki_ervenyes'] ) ) {
			return '';
		}
		return self::normalize_mot( (string) $raw['muszaki_ervenyes'] );
	}

	/**
	 * Műszaki dátum egységesítése: „2027. V. 14.” / „2027-05-14” → „2027.05.14.”; ha nem dátum, üres.
	 *
	 * @param string $value Nyers érték.
	 * @return string
	 */
	private static function normalize_mot( $value ) {
		$value = strtoupper( trim( $value ) );
		$roman = array( 'XII' => 12, 'XI' => 11, 'IX' => 9, 'X' => 10, 'VIII' => 8, 'VII' => 7, 'VI' => 6, 'IV' => 4, 'V' => 5, 'III' => 3, 'II' => 2, 'I' => 1 );
		if ( preg_match( '/(20\d{2})\s*[.\-\/ ]\s*(XII|XI|IX|X|VIII|VII|VI|IV|V|III|II|I)\s*[.\-\/ ]\s*(\d{1,2})/', $value, $m ) ) {
			return sprintf( '%04d.%02d.%02d.', (int) $m[1], $roman[ $m[2] ], (int) $m[3] );
		}
		if ( preg_match( '/(20\d{2})\s*[.\-\/ ]\s*(\d{1,2})\s*[.\-\/ ]\s*(\d{1,2})/', $value, $m ) ) {
			return sprintf( '%04d.%02d.%02d.', (int) $m[1], (int) $m[2], (int) $m[3] );
		}
		return '';
	}

	/**
	 * @param array<string,mixed> $body Kérés törzse.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function request( array $body ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => self::API_TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . self::api_key(),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $decoded ) ) {
			$message = is_array( $decoded ) && isset( $decoded['error']['message'] ) ? (string) $decoded['error']['message'] : 'HTTP ' . $code;
			return new WP_Error( 400 === $code ? 'bad_request' : 'api', $message );
		}
		return $decoded;
	}

	/* ------------------------------------------------------------------
	   Tisztítás, értékelés
	   ------------------------------------------------------------------ */

	/**
	 * @param array<string,mixed> $raw Modell válasza.
	 * @return array<string,mixed>
	 */
	private static function normalize( array $raw ) {
		$str = static function ( $key ) use ( $raw ) {
			return isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? trim( (string) $raw[ $key ] ) : '';
		};
		$digits = static function ( $value ) {
			return preg_match( '/\d+/', str_replace( ' ', '', $value ), $m ) ? $m[0] : '';
		};

		$year = preg_match( '/(19[5-9]\d|20[0-4]\d)/', $str( 'gyartasi_ev' ), $m ) ? $m[1] : '';
		$gear = preg_match( '/[0-3]/', $str( 'sebessegvalto_kod' ), $m ) ? $m[0] : '';
		$kw   = preg_replace( '/[^\d.]/', '', str_replace( ',', '.', $str( 'teljesitmeny_kw' ) ) );

		$uncertain = array();
		if ( isset( $raw['bizonytalan_mezok'] ) && is_array( $raw['bizonytalan_mezok'] ) ) {
			foreach ( $raw['bizonytalan_mezok'] as $field ) {
				if ( is_string( $field ) && in_array( $field, self::FIELDS, true ) ) {
					$uncertain[] = $field;
				}
			}
		}

		return array(
			'marka'                => $str( 'marka' ),
			'modell'               => $str( 'modell' ),
			'gyartasi_ev'          => $year,
			'sebessegvalto_kod'    => $gear,
			'hengerurtartalom_cm3' => $digits( $str( 'hengerurtartalom_cm3' ) ),
			'teljesitmeny_kw'      => $kw,
			'uzemanyag'            => $str( 'uzemanyag' ),
			'muszaki_ervenyes'     => self::normalize_mot( $str( 'muszaki_ervenyes' ) ),
			'rendszam'             => self::clean_id( 'rendszam', $str( 'rendszam' ) ),
			'alvazszam'            => self::clean_id( 'alvazszam', $str( 'alvazszam' ) ),
			'forgalmi_szam'        => self::clean_id( 'forgalmi_szam', $str( 'forgalmi_szam' ) ),
			'forgalmi_engedely'    => ! empty( $raw['forgalmi_engedely'] ),
			'bizonytalan_mezok'    => array_values( array_unique( $uncertain ) ),
		);
	}

	/**
	 * Kell-e a második (erősebb) modell? A műszaki dátuma csak a hátoldalon
	 * van, ezért egyetlen képnél nem számít hiánynak.
	 *
	 * @param array<string,mixed> $result Eredmény.
	 * @param int                 $images Képek száma.
	 * @return array{needs_fallback:bool}
	 */
	private static function evaluate( array $result, $images ) {
		$missing = 0;
		foreach ( self::FIELDS as $key ) {
			if ( 'muszaki_ervenyes' === $key && $images < 2 ) {
				continue;
			}
			if ( '' === $result[ $key ] ) {
				++$missing;
			}
		}
		return array( 'needs_fallback' => $missing >= 1 || count( $result['bizonytalan_mezok'] ) >= 2 );
	}

	/**
	 * A második modell eredménye az elsődleges; ahol üres, az elsőé marad.
	 *
	 * @param array<string,mixed> $primary  Első.
	 * @param array<string,mixed> $fallback Második.
	 * @return array<string,mixed>
	 */
	private static function merge( array $primary, array $fallback ) {
		$merged = $fallback;
		foreach ( array_merge( self::FIELDS, array_keys( self::ID_FIELDS ) ) as $key ) {
			if ( '' === $merged[ $key ] && '' !== $primary[ $key ] ) {
				$merged[ $key ] = $primary[ $key ];
			}
		}
		$merged['forgalmi_engedely'] = $primary['forgalmi_engedely'] || $fallback['forgalmi_engedely'];
		return $merged;
	}

	private static function filled_count( array $result ) {
		$count = 0;
		foreach ( self::FIELDS as $key ) {
			if ( '' !== $result[ $key ] ) {
				++$count;
			}
		}
		return $count;
	}

	/* ------------------------------------------------------------------
	   Fordítás az Albira értékeire
	   ------------------------------------------------------------------ */

	/**
	 * @param array<string,mixed> $r Normalizált eredmény.
	 * @return array<string,mixed>
	 */
	private static function to_albira( array $r ) {
		$out = array(
			'brand'        => '',
			'brandCustom'  => false,
			'model'        => '',
			'modelCustom'  => false,
			'year'         => '',
			'power_hp'     => '',
			'engine_cc'    => '',
			'fuel'         => '',
			'transmission' => '',
			'mot_has'      => '',
			'mot_year'     => '',
			'mot_month'    => '',
			'mot_day'      => '',
			'uncertain'    => array(),
		);

		$brand = self::match_brand( $r['marka'] );
		if ( '' !== $brand ) {
			$out['brand'] = $brand;
		} elseif ( '' !== $r['marka'] ) {
			$out['brand']       = $r['marka'];
			$out['brandCustom'] = true;
		}

		if ( '' !== $r['modell'] ) {
			$model = '' !== $brand ? self::match_model( $brand, $r['modell'] ) : '';
			if ( '' !== $model ) {
				$out['model'] = $model;
			} else {
				$out['model']       = self::title_case( $r['modell'] );
				$out['modelCustom'] = true;
			}
		}

		$year = (int) $r['gyartasi_ev'];
		if ( $year >= 1950 && $year <= (int) gmdate( 'Y' ) + 1 ) {
			$out['year'] = (string) $year;
		}

		$kw = (float) $r['teljesitmeny_kw'];
		$hp = $kw > 0 ? (int) round( $kw * 1.3596216173 ) : 0;
		if ( $hp > 0 && $hp <= 2000 ) {
			$out['power_hp'] = (string) $hp;
		}

		$cc = (int) $r['hengerurtartalom_cm3'];
		if ( $cc >= 50 && $cc <= 10000 ) {
			$out['engine_cc'] = (string) $cc;
		}

		$out['fuel'] = self::map_fuel( $r['uzemanyag'] );

		if ( '0' === $r['sebessegvalto_kod'] ) {
			$out['transmission'] = 'manual';
		} elseif ( '' !== $r['sebessegvalto_kod'] ) {
			$out['transmission'] = 'automatic';
		}

		if ( preg_match( '/(20\d{2})\s*[.\-\/ ]\s*(\d{1,2})\s*[.\-\/ ]\s*(\d{1,2})/', $r['muszaki_ervenyes'], $m ) ) {
			$y = (int) $m[1];
			$mo = (int) $m[2];
			$d = (int) $m[3];
			if ( $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31 ) {
				$valid = sprintf( '%04d-%02d-%02d', $y, $mo, $d ) >= current_time( 'Y-m-d' );
				$out['mot_has'] = $valid ? 'yes' : 'no';
				if ( $valid && $y <= (int) gmdate( 'Y' ) + 6 ) {
					$out['mot_year']  = (string) $y;
					$out['mot_month'] = (string) $mo;
					$out['mot_day']   = (string) $d;
				}
			}
		}

		foreach ( $r['bizonytalan_mezok'] as $field ) {
			if ( isset( self::FIELD_MAP[ $field ] ) ) {
				$out['uncertain'][] = self::FIELD_MAP[ $field ];
			}
		}
		if ( $out['brandCustom'] ) {
			$out['uncertain'][] = 'brand';
		}
		$out['uncertain'] = array_values( array_unique( $out['uncertain'] ) );

		/* Csak a szerződéshez: a beolvasó nem mutatja, a wizard rejtetten továbbviszi. */
		$out['ids'] = array();
		foreach ( array_keys( self::ID_FIELDS ) as $key ) {
			$out['ids'][ $key ] = $r[ $key ];
		}

		return $out;
	}

	/**
	 * Azonosító egységesítése: nagybetű, csak betű/szám (rendszámnál és
	 * okmányszámnál szóköz és kötőjel is), hosszkorlát.
	 *
	 * @param string $key   ID_FIELDS kulcs.
	 * @param string $value Nyers érték.
	 * @return string
	 */
	public static function clean_id( $key, $value ) {
		if ( ! isset( self::ID_FIELDS[ $key ] ) ) {
			return '';
		}
		$value = strtoupper( remove_accents( trim( (string) $value ) ) );
		if ( 'alvazszam' === $key ) {
			$value = preg_replace( '/[^A-Z0-9]/', '', $value );
		} else {
			$value = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^A-Z0-9 \-]/', '', $value ) ) );
		}
		return substr( $value, 0, self::ID_FIELDS[ $key ] );
	}

	/**
	 * A wizardból érkező azonosítók mentése a hirdetéshez (rejtett
	 * `kmae_registration_ids[...]` mezők). Csak a nem üres értékek írnak, így a
	 * beolvasás nélküli szerkesztés a korábban mentett adatot nem törli.
	 *
	 * @param int $post_id Hirdetés.
	 */
	public static function save_ids_from_post( $post_id ) {
		$posted = isset( $_POST['kmae_registration_ids'] ) && is_array( $_POST['kmae_registration_ids'] ) ? wp_unslash( $_POST['kmae_registration_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ids    = array();
		foreach ( array_keys( self::ID_FIELDS ) as $key ) {
			$value = isset( $posted[ $key ] ) && is_scalar( $posted[ $key ] ) ? self::clean_id( $key, sanitize_text_field( (string) $posted[ $key ] ) ) : '';
			if ( '' !== $value ) {
				$ids[ $key ] = $value;
			}
		}
		if ( $ids ) {
			update_post_meta( $post_id, self::META_IDS, $ids );
		}
	}

	/**
	 * A hirdetéshez mentett azonosítók (rendszam, alvazszam, forgalmi_szam; hiányzó = '').
	 *
	 * @param int $listing_id Hirdetés.
	 * @return array<string,string>
	 */
	public static function get_ids( $listing_id ) {
		$saved = get_post_meta( (int) $listing_id, self::META_IDS, true );
		$out   = array();
		foreach ( array_keys( self::ID_FIELDS ) as $key ) {
			$out[ $key ] = is_array( $saved ) && isset( $saved[ $key ] ) && is_scalar( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
		}
		return $out;
	}

	/* Összehasonlító kulcs: ékezet, kis-nagybetű, írásjel nélkül. */
	private static function key( $value ) {
		$value = strtoupper( remove_accents( (string) $value ) );
		return preg_replace( '/[^A-Z0-9]/', '', $value );
	}

	private static function match_brand( $value ) {
		$needle = self::key( $value );
		if ( '' === $needle ) {
			return '';
		}
		$aliases = array(
			'VW'       => 'VOLKSWAGEN',
			'MERCEDES' => 'MERCEDESBENZ',
		);
		if ( isset( $aliases[ $needle ] ) ) {
			$needle = $aliases[ $needle ];
		}
		foreach ( KMAE_Vehicle::brands() as $brand ) {
			if ( self::key( $brand ) === $needle ) {
				return $brand;
			}
		}
		/* Hosszabb hivatalos név (pl. „VOLKSWAGEN AG”): a leghosszabb katalógusmárka,
		   amellyel kezdődik. */
		$best = '';
		foreach ( KMAE_Vehicle::brands() as $brand ) {
			$key = self::key( $brand );
			if ( strlen( $key ) >= 3 && 0 === strpos( $needle, $key ) && strlen( $key ) > strlen( self::key( $best ) ) ) {
				$best = $brand;
			}
		}
		return $best;
	}

	/**
	 * Katalógusmodell: pontos egyezés, különben a leghosszabb katalógusnév,
	 * amellyel a beolvasott név kezdődik („OCTAVIA COMBI” → „Octavia”).
	 *
	 * @param string $brand Katalógusmárka.
	 * @param string $value Beolvasott modell.
	 * @return string
	 */
	private static function match_model( $brand, $value ) {
		$words  = preg_split( '/\s+/', strtoupper( remove_accents( trim( (string) $value ) ) ) );
		$needle = self::key( $value );
		$best   = '';
		$best_len = 0;
		foreach ( KMAE_Vehicle::models_for( $brand ) as $model ) {
			$key = self::key( $model );
			if ( '' === $key ) {
				continue;
			}
			if ( $key === $needle ) {
				return $model;
			}
			/* Szóhatáron illeszkedjen: „A4” ne illeszkedjen „A45”-re. */
			$prefix = '';
			foreach ( $words as $word ) {
				$prefix .= preg_replace( '/[^A-Z0-9]/', '', $word );
				if ( $prefix === $key && strlen( $key ) > $best_len ) {
					$best     = $model;
					$best_len = strlen( $key );
					break;
				}
			}
		}
		if ( '' !== $best ) {
			return $best;
		}

		/* Típusjelből sorozat: „C 220 D” / „C-KLASSE” → „C-Class”, „GLC 300” → „GLC”,
		   „320D” → „3 Series” (a katalógus a sorozatot tartalmazza). */
		$models = KMAE_Vehicle::models_for( $brand );
		$find   = static function ( $name ) use ( $models ) {
			foreach ( $models as $model ) {
				if ( self::key( $model ) === self::key( $name ) ) {
					return $model;
				}
			}
			return '';
		};
		$upper = str_replace( array( 'KLASSE', 'OSZTALY' ), 'CLASS', strtoupper( remove_accents( trim( (string) $value ) ) ) );
		if ( preg_match( '/^([A-Z]{1,3})(?:[\s-]*CLASS|\s*\d)/', $upper, $m ) ) {
			$hit = $find( $m[1] );
			if ( '' === $hit ) {
				$hit = $find( $m[1] . '-Class' );
			}
			if ( '' !== $hit ) {
				return $hit;
			}
		}
		if ( preg_match( '/^(\d)\d{2}/', $upper, $m ) ) {
			return $find( $m[1] . ' Series' );
		}
		return '';
	}

	private static function title_case( $value ) {
		$value = trim( (string) $value );
		if ( function_exists( 'mb_convert_case' ) ) {
			return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
		}
		return ucwords( strtolower( $value ) );
	}

	private static function map_fuel( $value ) {
		$v = strtolower( remove_accents( (string) $value ) );
		if ( '' === trim( $v ) ) {
			return '';
		}
		$electric = false !== strpos( $v, 'elektrom' ) || false !== strpos( $v, 'electric' );
		$combust  = false !== strpos( $v, 'benzin' ) || false !== strpos( $v, 'gazolaj' ) || false !== strpos( $v, 'diesel' ) || false !== strpos( $v, 'dizel' );
		if ( false !== strpos( $v, 'hibrid' ) || false !== strpos( $v, 'hybrid' ) || ( $electric && $combust ) ) {
			return 'hybrid';
		}
		if ( $electric ) {
			return 'electric';
		}
		if ( false !== strpos( $v, 'lpg' ) || false !== strpos( $v, 'cng' ) || ( false !== strpos( $v, 'gaz' ) && false === strpos( $v, 'gazolaj' ) ) ) {
			return 'lpg';
		}
		if ( false !== strpos( $v, 'gazolaj' ) || false !== strpos( $v, 'diesel' ) || false !== strpos( $v, 'dizel' ) ) {
			return 'diesel';
		}
		if ( false !== strpos( $v, 'benzin' ) ) {
			return 'benzin';
		}
		return 'other';
	}
}
