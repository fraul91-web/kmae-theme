<?php
/**
 * Official Albira brand assets and HTML mail wrapper.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Brand {

	/* Egyetlen logó mindenhol: fejléc, lábléc, vendég és bejelentkezett
	   felület, e-mailek. A két korábbi (belépés előtti/utáni) változat
	   megszűnt, a régi metódusnevek viszont maradtak a hívási helyek miatt. */
	const LOGO       = 'https://albira.hu/wp-content/uploads/2026/09/logo.webp';
	const LOGO_GUEST = self::LOGO;
	const LOGO_APP   = self::LOGO;

	/* Levélképek: a pluginban élő PNG-k, a levélbe ágyazva (cid:) mennek ki,
	   így letöltés nélkül, azonnal látszanak. Kulcs = Content-ID. */
	const EMAIL_IMAGES = array(
		'albira-logo'         => array( 'file' => 'logo.png', 'url' => 'https://albira.hu/wp-content/uploads/2026/09/logoemail.webp', 'width' => 168 ),
		'albira-mano-info'    => array( 'file' => 'info.png', 'url' => 'https://albira.hu/wp-content/uploads/2026/10/info3.webp', 'width' => 96 ),
		'albira-mano-ezaz'    => array( 'file' => 'ezaz.png', 'url' => 'https://albira.hu/wp-content/uploads/2026/10/ezaz2.webp', 'width' => 126 ),
		'albira-mano-szomoru' => array( 'file' => 'sad.png', 'url' => 'https://albira.hu/wp-content/uploads/2026/10/szomoru3.webp', 'width' => 120 ),
	);

	public static function init() {
		add_filter( 'wp_mail', array( __CLASS__, 'ensure_html_headers' ) );
		add_action( 'phpmailer_init', array( __CLASS__, 'embed_email_images' ) );
	}

	public static function logo_guest() {
		return self::LOGO_GUEST;
	}

	public static function logo_app() {
		return self::LOGO_APP;
	}

	/**
	 * Levelek fejléc-logója (fehér háttéren jól olvasható változat).
	 *
	 * @return string
	 */
	public static function email_logo_url() {
		return self::EMAIL_IMAGES['albira-logo']['url'];
	}

	/**
	 * Semleges hír: új üzenet, elfelejtett jelszó, biztonsági értesítés.
	 *
	 * @return string
	 */
	public static function email_mascot_url() {
		return self::EMAIL_IMAGES['albira-mano-info']['url'];
	}

	/**
	 * Üdvözlés — jó hír, ugyanaz a manó, mint a gratulációnál.
	 *
	 * @return string
	 */
	public static function email_welcome_mascot_url() {
		return self::email_cheer_mascot_url();
	}

	/**
	 * Szomorú manó — törlés, visszavonás, lejárat.
	 *
	 * @return string
	 */
	public static function email_sad_mascot_url() {
		return self::EMAIL_IMAGES['albira-mano-szomoru']['url'];
	}

	/**
	 * Jó hír: gratuláció, ajánlat, kikerült hirdetés.
	 *
	 * @return string
	 */
	public static function email_cheer_mascot_url() {
		return self::EMAIL_IMAGES['albira-mano-ezaz']['url'];
	}

	/**
	 * @param string $tone info|welcome|sad|cheer
	 * @return string
	 */
	public static function email_mascot_for_tone( $tone ) {
		return self::EMAIL_IMAGES[ self::email_mascot_cid( $tone ) ]['url'];
	}

	/**
	 * Hangulat → manó: szomorú hír = szomorú manó, jó hír = „ezaz”, minden más = infó.
	 *
	 * @param string $tone Hangulat.
	 * @return string Content-ID.
	 */
	private static function email_mascot_cid( $tone ) {
		$tone = sanitize_key( (string) $tone );
		if ( 'sad' === $tone ) {
			return 'albira-mano-szomoru';
		}
		if ( in_array( $tone, array( 'cheer', 'welcome', 'good', 'agreement' ), true ) ) {
			return 'albira-mano-ezaz';
		}
		return 'albira-mano-info';
	}

	/**
	 * @param string $cid Content-ID.
	 * @return string Helyi fájl útvonala.
	 */
	private static function email_image_path( $cid ) {
		return KMAE_PLUGIN_DIR . 'assets/images/email/' . self::EMAIL_IMAGES[ $cid ]['file'];
	}

	/**
	 * A kép forrása a levélben: beágyazott kép, ha a fájl megvan, különben a távoli kép.
	 *
	 * @param string $cid Content-ID.
	 * @return string
	 */
	private static function email_image_src( $cid ) {
		return is_readable( self::email_image_path( $cid ) ) ? 'cid:' . $cid : self::EMAIL_IMAGES[ $cid ]['url'];
	}

	/**
	 * A levélben hivatkozott képeket csatolja beágyazottként (inline, cid:).
	 * A PHPMailer minden wp_mail előtt üríti a csatolmányokat, így nem halmozódnak.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
	 */
	public static function embed_email_images( $phpmailer ) {
		$body = (string) $phpmailer->Body;
		if ( false === strpos( $body, 'cid:albira-' ) ) {
			return;
		}
		foreach ( self::EMAIL_IMAGES as $cid => $image ) {
			if ( false === strpos( $body, 'cid:' . $cid . '"' ) ) {
				continue;
			}
			$path = self::email_image_path( $cid );
			if ( ! is_readable( $path ) ) {
				continue;
			}
			try {
				$phpmailer->addEmbeddedImage( $path, $cid, $image['file'], 'base64', 'image/png', 'inline' );
			} catch ( Exception $e ) {
				unset( $e );
			}
		}
	}

	/**
	 * A logó mindenhol ugyanaz; a paraméter csak a régi hívások miatt maradt.
	 *
	 * @param string $variant Nem használt.
	 * @return string
	 */
	public static function logo_url( $variant = 'auto' ) {
		unset( $variant );
		return self::LOGO;
	}

	public static function html_content_type() {
		return 'text/html';
	}

	/**
	 * Send a branded HTML email with the official (logged-in) logo.
	 *
	 * @param string|array $to      Recipient(s).
	 * @param string       $subject Subject.
	 * @param string       $body    Plain-text body.
	 * @param string|array $headers Extra headers.
	 * @return bool
	 */
	public static function send_mail( $to, $subject, $body, $headers = array(), $wrap_args = array() ) {
		$html    = self::wrap_email( $body, $wrap_args );
		$headers = is_array( $headers ) ? $headers : array_filter( array_map( 'trim', explode( "\n", (string) $headers ) ) );
		$headers = array_values(
			array_filter(
				$headers,
				static function ( $header ) {
					return is_string( $header ) && '' !== $header && stripos( $header, 'Content-Type:' ) !== 0;
				}
			)
		);
		array_unshift( $headers, 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $to, $subject, $html, $headers );
	}

	/**
	 * Közös levélhéj, minden levélhez ugyanaz: fehér háttér minden eszközön,
	 * logó + kék–narancs csík, manó (hangulat szerint), címke, cím,
	 * szövegkártya, narancs gomb, ügyfélszolgálat, aláírás, lábléc.
	 *
	 * @param string $text Törzsszöveg (üres sor = új bekezdés).
	 * @param array  $args {
	 *     @type string $tone         info|sad|cheer (welcome/good/agreement = cheer) — manó és címkeszín.
	 *     @type string $eyebrow      Kis címke a cím fölött.
	 *     @type string $title        Cím.
	 *     @type string $button_label Gomb felirata.
	 *     @type string $button_url   Gomb célja.
	 * }
	 * @return string
	 */
	public static function wrap_email( $text, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'tone'         => 'info',
				'eyebrow'      => '',
				'title'        => '',
				'button_label' => '',
				'button_url'   => '',
			)
		);

		$font         = "'Segoe UI',Roboto,'Helvetica Neue',Helvetica,Arial,sans-serif";
		$home         = esc_url( home_url( '/' ) );
		$support      = esc_html( class_exists( 'KMAE_Settings' ) ? KMAE_Settings::contact_form_email() : 'info@albira.hu' );
		$mascot_cid   = self::email_mascot_cid( $args['tone'] );
		$mascot_w     = (int) self::EMAIL_IMAGES[ $mascot_cid ]['width'];
		$mascot_src   = esc_attr( self::email_image_src( $mascot_cid ) );
		$logo_w       = (int) self::EMAIL_IMAGES['albira-logo']['width'];
		$logo_src     = esc_attr( self::email_image_src( 'albira-logo' ) );
		$eyebrow      = trim( (string) $args['eyebrow'] );
		$title        = trim( (string) $args['title'] );
		$button_label = trim( (string) $args['button_label'] );
		$button_url   = trim( (string) $args['button_url'] );
		$year         = (string) wp_date( 'Y' );
		$body_html    = self::format_email_body( $text, $font );
		$preheader    = esc_html( self::email_preheader( $text, $title ) );

		$palette = array(
			'albira-mano-info'    => array( '#1c69d5', '#eaf3ff' ),
			'albira-mano-ezaz'    => array( '#c4520a', '#fff1e4' ),
			'albira-mano-szomoru' => array( '#55657a', '#eef2f7' ),
		);
		list( $pill_fg, $pill_bg ) = $palette[ $mascot_cid ];

		$eyebrow_html = '';
		if ( '' !== $eyebrow ) {
			$eyebrow_html = '
								<table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 14px;">
									<tr>
										<td bgcolor="' . $pill_bg . '" style="padding:6px 14px;border-radius:999px;background-color:' . $pill_bg . ';font-family:' . $font . ';font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:' . $pill_fg . ';">' . esc_html( $eyebrow ) . '</td>
									</tr>
								</table>';
		}

		$title_html = '';
		if ( '' !== $title ) {
			$title_html = '
								<h1 style="margin:0;font-family:' . $font . ';font-size:26px;line-height:1.3;font-weight:800;letter-spacing:-0.01em;color:#0b3a66;">' . esc_html( $title ) . '</h1>';
		}

		$button_html = '';
		if ( '' !== $button_label && '' !== $button_url ) {
			$safe_url    = esc_url( $button_url );
			$button_html = '
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:26px 40px 0;">
						<table role="presentation" class="kmae-mail-btn" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
							<tr>
								<td align="center" bgcolor="#ff7a18" style="border-radius:12px;background-color:#ff7a18;background-image:linear-gradient(135deg,#ff9a3d 0%,#ff7a18 100%);mso-padding-alt:15px 36px;">
									<a href="' . $safe_url . '" target="_blank" style="display:inline-block;padding:15px 36px;font-family:' . $font . ';font-size:16px;line-height:20px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">' . esc_html( $button_label ) . '&nbsp;&rarr;</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:16px 40px 0;font-family:' . $font . ';font-size:12px;line-height:1.6;color:#8a99ab;">
						' . esc_html__( 'Ha a gomb nem működik, másold be ezt a linket a böngésződbe:', 'ki-mit-adna-erte' ) . '<br />
						<a href="' . $safe_url . '" target="_blank" style="color:#1c69d5;text-decoration:underline;word-break:break-all;">' . $safe_url . '</a>
					</td>
				</tr>';
		}

		return '<!DOCTYPE html>
<html lang="hu" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="x-apple-disable-message-reformatting" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
<meta name="color-scheme" content="light only" />
<meta name="supported-color-schemes" content="light only" />
<title>Albira</title>
<style type="text/css">
:root { color-scheme: light only; supported-color-schemes: light only; }
body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #ffffff !important; }
img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
a { color: #1c69d5; }
@media only screen and (max-width: 620px) {
	.kmae-mail-outer { padding: 0 !important; }
	.kmae-mail-shell { border: 0 !important; border-radius: 0 !important; }
	.kmae-mail-pad { padding-left: 20px !important; padding-right: 20px !important; }
	.kmae-mail-card { padding: 20px 18px !important; }
	.kmae-mail-btn { width: 100% !important; }
	.kmae-mail-btn a { display: block !important; }
	h1 { font-size: 23px !important; }
}
</style>
<!--[if mso]>
<style type="text/css">
body, table, td, a, p, h1 { font-family: Arial, Helvetica, sans-serif !important; }
</style>
<![endif]-->
</head>
<body bgcolor="#ffffff" style="margin:0;padding:0;width:100%;background-color:#ffffff;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:#ffffff;">' . $preheader . str_repeat( '&#8204;&nbsp;', 60 ) . '</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="background-color:#ffffff;">
	<tr>
		<td align="center" class="kmae-mail-outer" style="padding:32px 12px;">
			<!--[if mso]><table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
			<table role="presentation" class="kmae-mail-shell" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e3ebf5;border-radius:20px;">
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:30px 40px 20px;">
						<a href="' . $home . '" target="_blank" style="text-decoration:none;"><img src="' . $logo_src . '" alt="Albira" width="' . $logo_w . '" style="display:block;width:' . $logo_w . 'px;max-width:60%;height:auto;margin:0 auto;font-family:' . $font . ';font-size:24px;font-weight:800;color:#0b3a66;" /></a>
					</td>
				</tr>
				<tr>
					<td class="kmae-mail-pad" style="padding:0 40px;">
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td height="4" bgcolor="#3aa0e8" style="height:4px;line-height:4px;font-size:0;border-radius:999px;background-color:#3aa0e8;background-image:linear-gradient(90deg,#1c69d5 0%,#3aa0e8 50%,#ff7a18 100%);">&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:28px 40px 6px;">
						<img src="' . $mascot_src . '" alt="" width="' . $mascot_w . '" style="display:block;width:' . $mascot_w . 'px;max-width:100%;height:auto;margin:0 auto;" />
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:14px 40px 0;text-align:center;">' . $eyebrow_html . $title_html . '
					</td>
				</tr>
				<tr>
					<td class="kmae-mail-pad" style="padding:24px 40px 0;">
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f5f9fe" style="background-color:#f5f9fe;border:1px solid #e3ecf8;border-radius:16px;">
							<tr>
								<td class="kmae-mail-card" style="padding:24px 26px;font-family:' . $font . ';font-size:16px;line-height:1.65;color:#3b4b5f;">' . $body_html . '
								</td>
							</tr>
						</table>
					</td>
				</tr>' . $button_html . '
				<tr>
					<td class="kmae-mail-pad" style="padding:32px 40px 0;">
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td height="1" bgcolor="#e6edf6" style="height:1px;line-height:1px;font-size:0;background-color:#e6edf6;">&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:22px 40px 0;font-family:' . $font . ';font-size:14px;line-height:1.6;color:#6b7b8f;">
						' . esc_html__( 'Ha kérdésed van, ügyfélszolgálatunk készséggel segít:', 'ki-mit-adna-erte' ) . '<br />
						<a href="mailto:' . $support . '" style="color:#1c69d5;text-decoration:none;font-weight:700;">' . $support . '</a>
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:18px 40px 0;font-family:' . $font . ';font-size:15px;line-height:1.55;color:#0b3a66;">
						' . esc_html__( 'Üdvözlettel,', 'ki-mit-adna-erte' ) . '<br />
						<strong style="font-weight:700;">' . esc_html__( 'Az Albira csapata', 'ki-mit-adna-erte' ) . '</strong>
					</td>
				</tr>
				<tr>
					<td align="center" class="kmae-mail-pad" style="padding:26px 40px 30px;font-family:' . $font . ';font-size:12px;line-height:1.7;color:#97a6b7;">
						' . esc_html__( 'Biztonság', 'ki-mit-adna-erte' ) . ' &nbsp;&middot;&nbsp; ' . esc_html__( 'Megbízhatóság', 'ki-mit-adna-erte' ) . ' &nbsp;&middot;&nbsp; ' . esc_html__( 'Mindig veled', 'ki-mit-adna-erte' ) . '<br />
						&copy; ' . esc_html( $year ) . ' Albira. ' . esc_html__( 'Minden jog fenntartva.', 'ki-mit-adna-erte' ) . ' &nbsp;&middot;&nbsp; <a href="' . $home . '" target="_blank" style="color:#97a6b7;text-decoration:none;">albira.hu</a>
					</td>
				</tr>
			</table>
			<!--[if mso]></td></tr></table><![endif]-->
		</td>
	</tr>
</table>
</body>
</html>';
	}

	/**
	 * If the body already contains the official logo, force HTML content-type.
	 *
	 * @param array $args wp_mail args.
	 * @return array
	 */
	public static function ensure_html_headers( $args ) {
		if ( empty( $args['message'] ) || ! is_string( $args['message'] ) ) {
			return $args;
		}

		$message = $args['message'];
		$is_branded = false !== strpos( $message, 'cid:albira-' )
			|| false !== strpos( $message, self::LOGO )
			|| false !== strpos( $message, self::email_logo_url() )
			|| false !== strpos( $message, 'logo-1.webp' )
			|| false !== strpos( $message, 'logoemail-1.webp' )
			|| false !== strpos( $message, 'info3.webp' )
			|| false !== strpos( $message, 'szia3.webp' )
			|| false !== strpos( $message, 'szomoru3.webp' )
			|| false !== strpos( $message, 'csakigytovabb3.webp' )
			|| false !== strpos( $message, 'ezaz2.webp' );
		if ( ! $is_branded ) {
			return $args;
		}

		$headers = isset( $args['headers'] ) ? $args['headers'] : array();
		if ( is_string( $headers ) ) {
			$headers = array_filter( array_map( 'trim', explode( "\n", $headers ) ) );
		}
		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		$has_html = false;
		foreach ( $headers as $header ) {
			if ( is_string( $header ) && stripos( $header, 'text/html' ) !== false ) {
				$has_html = true;
				break;
			}
		}

		if ( ! $has_html ) {
			$headers[]       = 'Content-Type: text/html; charset=UTF-8';
			$args['headers'] = $headers;
		}

		return $args;
	}

	/**
	 * Rejtett előnézeti szöveg (a levéllistában a tárgy mellett): a köszönés
	 * utáni első bekezdés, ennek híján a cím.
	 *
	 * @param string $text  Törzsszöveg.
	 * @param string $title Cím.
	 * @return string
	 */
	private static function email_preheader( $text, $title ) {
		foreach ( self::email_paragraphs( $text ) as $paragraph ) {
			if ( self::is_greeting( $paragraph ) ) {
				continue;
			}
			$line = trim( preg_replace( '/\s+/u', ' ', $paragraph ) );
			return function_exists( 'mb_strimwidth' ) ? mb_strimwidth( $line, 0, 140, '…' ) : substr( $line, 0, 140 );
		}
		return (string) $title;
	}

	/**
	 * @param string $text Plain text.
	 * @return string[] Bekezdések (üres sor választja el őket), a záró „— Albira” nélkül.
	 */
	private static function email_paragraphs( $text ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		$text = preg_replace( '/\n*—\s*Albira\s*$/u', '', $text );
		return array_values( array_filter( array_map( 'trim', preg_split( '/\n\s*\n/u', trim( $text ) ) ), 'strlen' ) );
	}

	/**
	 * @param string $paragraph Bekezdés.
	 * @return bool
	 */
	private static function is_greeting( $paragraph ) {
		return (bool) preg_match( '/^Szia\b[^\n]{0,40}!$/u', $paragraph );
	}

	/**
	 * Bekezdések <p>-ként; a köszönés kiemelve, a „idézett” hirdetéscím
	 * félkövér, a linkek kattinthatók.
	 *
	 * @param string $text Plain text.
	 * @param string $font Betűkészlet.
	 * @return string
	 */
	private static function format_email_body( $text, $font ) {
		$paragraphs = self::email_paragraphs( $text );
		$count      = count( $paragraphs );
		$html       = '';

		foreach ( $paragraphs as $i => $paragraph ) {
			$margin = ( $i === $count - 1 ) ? '0' : '0 0 14px';
			if ( 0 === $i && self::is_greeting( $paragraph ) ) {
				$html .= '
									<p style="margin:' . $margin . ';font-family:' . $font . ';font-size:18px;line-height:1.5;font-weight:700;color:#0b3a66;">' . esc_html( $paragraph ) . '</p>';
				continue;
			}

			$part = esc_html( $paragraph );
			$part = preg_replace(
				'~(https?://[^\s<]+)~',
				'<a href="$1" target="_blank" style="color:#1c69d5;text-decoration:underline;word-break:break-all;">$1</a>',
				$part
			);
			$part = preg_replace( '/„([^”\n]{1,200})”/u', '„<strong style="font-weight:700;color:#0b3a66;">$1</strong>”', $part );

			$html .= '
									<p style="margin:' . $margin . ';font-family:' . $font . ';font-size:16px;line-height:1.65;color:#3b4b5f;">' . nl2br( $part ) . '</p>';
		}

		return $html;
	}
}
