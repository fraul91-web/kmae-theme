<?php
/**
 * Education hub — buyer + seller guides.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$buyer_topics = array(
	array(
		'title' => __( 'Mit nézz át az autón?', 'ki-mit-adna-erte' ),
		'items' => array(
			__( 'Karosszéria: rozsdák, ütések, festékeltérések, hézagok', 'ki-mit-adna-erte' ),
			__( 'Gumiabroncsok, felnik, fékek állapota', 'ki-mit-adna-erte' ),
			__( 'Belső: ülések, szagok, műszerfal, elektromos funkciók', 'ki-mit-adna-erte' ),
			__( 'Kilométeróra, szervizkönyv, papírok', 'ki-mit-adna-erte' ),
			__( 'Motorjárás, olajszivárgás, hűtőfolyadék', 'ki-mit-adna-erte' ),
			__( 'Próbaút: váltó, kormány, fék, zajok', 'ki-mit-adna-erte' ),
		),
	),
	array(
		'title' => __( 'Milyen hibákat keress?', 'ki-mit-adna-erte' ),
		'items' => array(
			__( 'Általános: rozsda, vízbeázás, olajfolyás, kopott fék', 'ki-mit-adna-erte' ),
			__( 'Típushibák: ellenőrizd a modellre jellemző gyakori hibákat', 'ki-mit-adna-erte' ),
			__( 'Rejtett sérülések: fényezésvastagság, hegesztésnyomok', 'ki-mit-adna-erte' ),
			__( 'Elektronikai hibák: visszajelző lámpák, hibakódok', 'ki-mit-adna-erte' ),
		),
	),
	array(
		'title' => __( 'Hol és hogyan találkozz?', 'ki-mit-adna-erte' ),
		'items' => array(
			__( 'Lehetőleg nappal nézd meg az autót', 'ki-mit-adna-erte' ),
			__( 'Nyilvános, biztonságos hely (pl. nagy parkoló)', 'ki-mit-adna-erte' ),
			__( 'Vigyél magaddal ismerőst, ha teheted', 'ki-mit-adna-erte' ),
			__( 'Nézd meg a képekkel és a leírással együtt', 'ki-mit-adna-erte' ),
			__( 'Ha eltérés van: jelezd az eladónak', 'ki-mit-adna-erte' ),
		),
	),
);

$seller_topics = array(
	array(
		'title' => __( 'Jó fotók = több komoly érdeklődő', 'ki-mit-adna-erte' ),
		'items' => array(
			__( 'Használd az Albira kamerás fotórendszerét — ne galériából tölts fel', 'ki-mit-adna-erte' ),
			__( 'Világos, nappali fény; kerüld a sötét, homályos képeket', 'ki-mit-adna-erte' ),
			__( 'Minden kötelező nézetet készítsd el (7 kötelező + ajánlottak)', 'ki-mit-adna-erte' ),
			__( 'Mutasd a hibákat is — az őszinteség bizalmat épít', 'ki-mit-adna-erte' ),
		),
	),
	array(
		'title' => __( 'Őszinte, tiszta hirdetés', 'ki-mit-adna-erte' ),
		'items' => array(
			__( 'Minden ismert hibát tüntess fel', 'ki-mit-adna-erte' ),
			__( 'Írj világos, részletes leírást', 'ki-mit-adna-erte' ),
			__( 'Mosd le és takarítsd ki az autót a fotók előtt', 'ki-mit-adna-erte' ),
			__( 'Egy kis befektetés (takarítás, kozmetika) sokszorosára növelheti az eladás esélyét', 'ki-mit-adna-erte' ),
		),
	),
	array(
		'title' => __( 'Vállalások és bizalom', 'ki-mit-adna-erte' ),
		'items' => array(
			__( '24 órán belüli elvitel: csak ha vállalod', 'ki-mit-adna-erte' ),
			__( 'A sikeres, korrekt üzletek építik a bizalmi számod', 'ki-mit-adna-erte' ),
		),
	),
);
?>
<div class="kmae-education">
	<h2><?php esc_html_e( 'Oktatórendszer', 'ki-mit-adna-erte' ); ?></h2>
	<p class="kmae-subtitle"><?php esc_html_e( 'Gyakorlati útmutatók vevőknek és eladóknak — hogy biztonságosabban és tisztességesebben menjen az üzlet.', 'ki-mit-adna-erte' ); ?></p>

	<div class="kmae-education__videos">
		<?php
		$buyer_video_id  = KMAE_Settings::buyer_video_id();
		$seller_video_id = KMAE_Settings::seller_video_id();
		?>
		<?php if ( $buyer_video_id || $seller_video_id ) : ?>
			<div class="kmae-education__video-grid">
				<?php if ( $buyer_video_id ) : ?>
					<figure class="kmae-education__video">
						<iframe src="<?php echo esc_url( 'https://www.youtube.com/embed/' . $buyer_video_id ); ?>" title="<?php esc_attr_e( 'Oktatóvideó vevőknek', 'ki-mit-adna-erte' ); ?>" allowfullscreen loading="lazy"></iframe>
						<figcaption><?php esc_html_e( 'Vevőknek', 'ki-mit-adna-erte' ); ?></figcaption>
					</figure>
				<?php endif; ?>
				<?php if ( $seller_video_id ) : ?>
					<figure class="kmae-education__video">
						<iframe src="<?php echo esc_url( 'https://www.youtube.com/embed/' . $seller_video_id ); ?>" title="<?php esc_attr_e( 'Oktatóvideó eladóknak', 'ki-mit-adna-erte' ); ?>" allowfullscreen loading="lazy"></iframe>
						<figcaption><?php esc_html_e( 'Eladóknak', 'ki-mit-adna-erte' ); ?></figcaption>
					</figure>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="kmae-notice kmae-notice-info">
				<p><strong><?php esc_html_e( 'Oktató videók', 'ki-mit-adna-erte' ); ?></strong></p>
				<p><?php esc_html_e( 'A videós anyagok hamarosan érkeznek (adminban beállítható YouTube link). Addig az alábbi írásos útmutatókat használd.', 'ki-mit-adna-erte' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<section class="kmae-education__section" id="kmae-edu-buyer">
		<h3><?php esc_html_e( 'Vásárlóknak', 'ki-mit-adna-erte' ); ?></h3>
		<p class="kmae-hint"><?php esc_html_e( 'Mit nézz át, milyen hibákat keress, hol találkozz — nappal, biztonságosan.', 'ki-mit-adna-erte' ); ?></p>
		<div class="kmae-education__grid">
			<?php foreach ( $buyer_topics as $topic ) : ?>
				<article class="kmae-education__card">
					<h4><?php echo esc_html( $topic['title'] ); ?></h4>
					<ul>
						<?php foreach ( $topic['items'] as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="kmae-education__section" id="kmae-edu-seller">
		<h3><?php esc_html_e( 'Eladóknak', 'ki-mit-adna-erte' ); ?></h3>
		<p class="kmae-hint"><?php esc_html_e( 'Világos fotók, őszinte hibák, tiszta autó — így jönnek a komoly vevők.', 'ki-mit-adna-erte' ); ?></p>
		<div class="kmae-education__grid">
			<?php foreach ( $seller_topics as $topic ) : ?>
				<article class="kmae-education__card">
					<h4><?php echo esc_html( $topic['title'] ); ?></h4>
					<ul>
						<?php foreach ( $topic['items'] as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<?php
	$back_url   = KMAE_Back::home_url();
	$back_label = KMAE_Back::home_label();
	include KMAE_PLUGIN_DIR . 'templates/partials/page-back.php';
	?>
</div>
