<?php
/**
 * Forgalmi engedély beolvasása — ablak.
 *
 * Két oszlop: balra a „látvány” (illusztráció, majd a feltöltött fotók a
 * szkenneléssel), alatta a 3 lépés; jobbra a nézetek (pick → scan → review,
 * hiba esetén error). A JS (assets/js/registration-scan.js) a <body>-ba teszi át,
 * és a gyökér `data-view` attribútumával vált. Az összesítő mezői ideiglenesek:
 * elfogadáskor a JS másolja át őket a wizard valódi mezőibe.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$regscan_years   = range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) + 6 );
$regscan_months  = array( 'január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december' );

/* Illusztráció sorai: [kód, olvassuk-e]. A személyes sorok „kitakarva” jelennek meg. */
$regscan_rows = array(
	array( 'A', false ),
	array( 'C.1', false ),
	array( 'D.1', true ),
	array( 'D.3', true ),
	array( 'P.1', true ),
	array( 'P.2', true ),
	array( 'P.3', true ),
);

$regscan_mascot = 'https://albira.hu/wp-content/uploads/2026/10/forgalmi4.webp';

$regscan_icon = static function ( $name, $size = 20 ) {
	$paths = array(
		'upload' => '<path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5" /><path d="M4 14.5V17a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2.5" />',
		'shield' => '<path d="M12 3l7 3v5.5c0 4.4-3 8.3-7 9.5-4-1.2-7-5.1-7-9.5V6l7-3z" /><path d="M8.8 12.2l2.2 2.2 4.4-4.6" />',
		'spark'  => '<path d="M12 3.5l1.9 5.1 5.1 1.9-5.1 1.9L12 17.5l-1.9-5.1L5 10.5l5.1-1.9z" /><path d="M18.5 16l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7z" />',
		'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5" />',
		'close'  => '<path d="M6 6l12 12M18 6L6 18" />',
		'lock'   => '<rect x="5" y="10.5" width="14" height="9.5" rx="2.5" /><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" />',
		'alert'  => '<path d="M12 8.5v4.5M12 16.5v.01" /><path d="M10.3 4.2L3 17a2 2 0 0 0 1.7 3h14.6A2 2 0 0 0 21 17L13.7 4.2a2 2 0 0 0-3.4 0z" />',
		'redo'   => '<path d="M20 11a8 8 0 1 0-2.3 5.7" /><path d="M20 5v6h-6" />',
	);
	return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
};
?>
<div class="kmae-regscan" data-kmae-regscan data-view="pick" hidden>
	<div class="kmae-regscan__backdrop" data-kmae-regscan-close></div>

	<div class="kmae-regscan__card" role="dialog" aria-modal="true" aria-labelledby="kmae-regscan-title" tabindex="-1">
		<button type="button" class="kmae-regscan__x" data-kmae-regscan-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>">
			<?php echo $regscan_icon( 'close', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>

		<?php /* ---------- Bal oszlop: látvány + lépések ---------- */ ?>
		<aside class="kmae-regscan__visual">
			<div class="kmae-regscan__stage">
				<?php /* Illusztráció: forgalmi, az olvasott sorok kéken, a személyesek kitakarva. */ ?>
				<div class="kmae-regscan__illu" aria-hidden="true">
					<div class="kmae-regscan__paper kmae-regscan__paper--back"></div>
					<div class="kmae-regscan__paper kmae-regscan__paper--front">
						<p class="kmae-regscan__paper-head"><span>HU</span><?php esc_html_e( 'Forgalmi engedély', 'ki-mit-adna-erte' ); ?></p>
						<?php foreach ( $regscan_rows as $regscan_row ) : ?>
							<p class="kmae-regscan__paper-row<?php echo $regscan_row[1] ? ' is-read' : ' is-private'; ?>">
								<b><?php echo esc_html( $regscan_row[0] ); ?></b>
								<i></i>
								<?php if ( ! $regscan_row[1] ) : ?>
									<em><?php echo $regscan_icon( 'lock', 10 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></em>
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
						<span class="kmae-regscan__beam"></span>
					</div>
					<span class="kmae-regscan__tag kmae-regscan__tag--read"><?php echo $regscan_icon( 'check', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Autó adatai', 'ki-mit-adna-erte' ); ?></span>
					<span class="kmae-regscan__shield">
						<span class="kmae-regscan__shield-icon"><?php echo $regscan_icon( 'shield', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="kmae-regscan__shield-text">
							<strong><?php esc_html_e( 'Személyes adataid védve', 'ki-mit-adna-erte' ); ?></strong>
							<small><?php esc_html_e( 'Nevet, lakcímet nem olvasunk ki', 'ki-mit-adna-erte' ); ?></small>
						</span>
					</span>
				</div>

				<?php /* Feltöltött fotók: szkennelés és összesítő alatt (JS tölti). */ ?>
				<div class="kmae-regscan__photos" data-kmae-regscan-photos aria-hidden="true">
					<span class="kmae-regscan__beam"></span>
					<span class="kmae-regscan__corner kmae-regscan__corner--tl"></span>
					<span class="kmae-regscan__corner kmae-regscan__corner--tr"></span>
					<span class="kmae-regscan__corner kmae-regscan__corner--bl"></span>
					<span class="kmae-regscan__corner kmae-regscan__corner--br"></span>
				</div>
			</div>

			<ol class="kmae-regscan__how">
				<li data-kmae-regscan-how="0"><span>1</span><?php esc_html_e( 'Fotó a forgalmiról', 'ki-mit-adna-erte' ); ?></li>
				<li data-kmae-regscan-how="1"><span>2</span><?php esc_html_e( 'Kiolvassuk az adatokat', 'ki-mit-adna-erte' ); ?></li>
				<li data-kmae-regscan-how="2"><span>3</span><?php esc_html_e( 'Átnézed, és kész', 'ki-mit-adna-erte' ); ?></li>
			</ol>
		</aside>

		<div class="kmae-regscan__main">

			<?php /* ---------- 1. Képek ---------- */ ?>
			<section class="kmae-regscan__view" data-kmae-regscan-view="pick">
				<?php /* Segítő manó: a feltöltőmezőre mutat, buborékban szól hozzád. */ ?>
				<div class="kmae-regscan__hello">
					<?php /* forgalmi4.webp (2026/10) valódi átlátszó háttérrel — az eredetibe bele volt égetve a kockás minta. */ ?>
					<img class="kmae-regscan__mascot" src="<?php echo esc_url( $regscan_mascot ); ?>" alt="" width="200" height="276" decoding="async" />
					<div class="kmae-regscan__hello-text">
						<p class="kmae-regscan__eyebrow"><?php esc_html_e( 'Gyorsabb hirdetésfeladás', 'ki-mit-adna-erte' ); ?></p>
						<h2 class="kmae-regscan__title" id="kmae-regscan-title"><?php esc_html_e( 'Kitöltöm helyetted', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'a forgalmiból', 'ki-mit-adna-erte' ); ?></span></h2>
						<p class="kmae-regscan__bubble"><?php esc_html_e( 'Segítek! Fotózd le a forgalmi két oldalát, és az autó adatait én írom be helyetted — neked csak át kell nézned.', 'ki-mit-adna-erte' ); ?></p>
					</div>
				</div>

				<div class="kmae-regscan__pick" data-kmae-regscan-pick>
					<label class="kmae-regscan__drop" data-kmae-regscan-drop>
						<input type="file" class="kmae-regscan__file" accept="image/*" multiple data-kmae-regscan-file />
						<span class="kmae-regscan__drop-icon"><?php echo $regscan_icon( 'upload', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong class="kmae-regscan__drop-title"><?php esc_html_e( 'Válaszd ki egyszerre a két fotót', 'ki-mit-adna-erte' ); ?></strong>
						<span class="kmae-regscan__drop-text"><?php esc_html_e( 'vagy húzd ide őket — az első és a hátsó oldalt', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-regscan__drop-chips">
							<span><?php esc_html_e( 'Első oldal', 'ki-mit-adna-erte' ); ?></span>
							<span><?php esc_html_e( 'Hátoldal', 'ki-mit-adna-erte' ); ?></span>
							<span class="is-muted"><?php esc_html_e( 'legfeljebb 2 kép', 'ki-mit-adna-erte' ); ?></span>
						</span>
					</label>

					<div class="kmae-regscan__picked" data-kmae-regscan-picked hidden></div>
				</div>

				<?php /* Egy fotó után: a kiolvasás rövid várakozás után magától indul (két fotónál azonnal indul). */ ?>
				<div class="kmae-regscan__auto" data-kmae-regscan-auto role="status" hidden>
					<span class="kmae-regscan__auto-icon"><?php echo $regscan_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-regscan__auto-text"><strong><?php esc_html_e( 'Megvan az első oldal!', 'ki-mit-adna-erte' ); ?></strong> <?php esc_html_e( 'Mindjárt kezdem a kiolvasást.', 'ki-mit-adna-erte' ); ?></span>
				</div>

				<p class="kmae-regscan__error" data-kmae-regscan-pick-error role="alert" hidden></p>

				<div class="kmae-regscan__actions">
					<button type="button" class="kmae-regscan__skip" data-kmae-regscan-close><?php esc_html_e( 'Kihagyom, kézzel töltöm ki', 'ki-mit-adna-erte' ); ?></button>
				</div>
			</section>

			<?php /* ---------- 2. Szkennelés ---------- */ ?>
			<section class="kmae-regscan__view" data-kmae-regscan-view="scan" hidden>
				<p class="kmae-regscan__eyebrow"><?php echo $regscan_icon( 'spark', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Beolvasás folyamatban', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-regscan__title"><?php esc_html_e( 'Kiolvassuk', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'az autó adatait', 'ki-mit-adna-erte' ); ?></span></h2>
				<p class="kmae-regscan__lead"><?php esc_html_e( 'Általában 10–20 másodperc. Addig nyugodtan dőlj hátra.', 'ki-mit-adna-erte' ); ?></p>

				<div class="kmae-regscan__progress" aria-hidden="true"><span data-kmae-regscan-bar></span></div>

				<?php /* Külön, kiemelt blokk: a személyes adatok kitakarása. A JS a szkennelés elején zöldre váltja. */ ?>
				<div class="kmae-regscan__guard" data-kmae-regscan-guard>
					<span class="kmae-regscan__guard-icon"><?php echo $regscan_icon( 'shield', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="kmae-regscan__guard-text">
						<strong data-kmae-regscan-guard-title><?php esc_html_e( 'Személyes adatok kitakarása…', 'ki-mit-adna-erte' ); ?></strong>
						<small><?php esc_html_e( 'Név, lakcím: ezeket nem olvassuk ki és nem mentjük el.', 'ki-mit-adna-erte' ); ?></small>
					</span>
					<span class="kmae-regscan__guard-state" aria-hidden="true"></span>
				</div>

				<ol class="kmae-regscan__checks" data-kmae-regscan-checks>
					<?php /* A válaszra mindig az utolsó, semleges „összeállítás” lépés vár. */ ?>
					<li><span></span><?php esc_html_e( 'Forgalmi engedély felismerése', 'ki-mit-adna-erte' ); ?></li>
					<li><span></span><?php esc_html_e( 'Márka és modell', 'ki-mit-adna-erte' ); ?></li>
					<li><span></span><?php esc_html_e( 'Évjárat, motor, teljesítmény', 'ki-mit-adna-erte' ); ?></li>
					<li><span></span><?php esc_html_e( 'Üzemanyag és váltó', 'ki-mit-adna-erte' ); ?></li>
					<li data-kmae-regscan-check-mot><span></span><?php esc_html_e( 'Műszaki érvényesség', 'ki-mit-adna-erte' ); ?></li>
					<li><span></span><?php esc_html_e( 'Adatok összeállítása', 'ki-mit-adna-erte' ); ?></li>
				</ol>
			</section>

			<?php /* ---------- 3. Összesítő ---------- */ ?>
			<section class="kmae-regscan__view" data-kmae-regscan-view="review" hidden>
				<p class="kmae-regscan__eyebrow kmae-regscan__eyebrow--ok"><?php echo $regscan_icon( 'check', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Kész a beolvasás', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-regscan__title"><?php esc_html_e( 'Minden', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'rendben van?', 'ki-mit-adna-erte' ); ?></span></h2>
				<p class="kmae-regscan__lead"><?php esc_html_e( 'Vesd össze a fotóval. Bármelyik adatra kattintva javíthatod.', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-regscan__safe"><?php echo $regscan_icon( 'shield', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Személyes adatot nem olvastunk ki, a fotókat már töröltük.', 'ki-mit-adna-erte' ); ?></span></p>

				<div class="kmae-regscan__review" data-kmae-regscan-review>
					<label class="kmae-regscan__field" data-field="brand">
						<span><?php esc_html_e( 'Márka', 'ki-mit-adna-erte' ); ?></span>
						<input type="text" data-kmae-regscan-input="brand" list="kmae-regscan-brands" autocomplete="off" />
					</label>
					<label class="kmae-regscan__field" data-field="model">
						<span><?php esc_html_e( 'Modell', 'ki-mit-adna-erte' ); ?></span>
						<input type="text" data-kmae-regscan-input="model" list="kmae-regscan-models" autocomplete="off" />
					</label>
					<label class="kmae-regscan__field" data-field="year">
						<span><?php esc_html_e( 'Évjárat', 'ki-mit-adna-erte' ); ?></span>
						<input type="number" data-kmae-regscan-input="year" min="1950" max="<?php echo esc_attr( (int) gmdate( 'Y' ) + 1 ); ?>" inputmode="numeric" />
					</label>
					<label class="kmae-regscan__field" data-field="power_hp">
						<span><?php esc_html_e( 'Teljesítmény', 'ki-mit-adna-erte' ); ?></span>
						<input type="number" data-kmae-regscan-input="power_hp" min="1" inputmode="numeric" />
						<small>LE</small>
					</label>
					<label class="kmae-regscan__field" data-field="engine_cc">
						<span><?php esc_html_e( 'Hengerűrtartalom', 'ki-mit-adna-erte' ); ?></span>
						<input type="number" data-kmae-regscan-input="engine_cc" min="1" inputmode="numeric" />
						<small>cm³</small>
					</label>
					<label class="kmae-regscan__field" data-field="fuel">
						<span><?php esc_html_e( 'Üzemanyag', 'ki-mit-adna-erte' ); ?></span>
						<select data-kmae-regscan-input="fuel">
							<option value=""><?php esc_html_e( 'Válassz', 'ki-mit-adna-erte' ); ?></option>
							<?php foreach ( KMAE_Vehicle::fuel_labels() as $regscan_value => $regscan_label ) : ?>
								<option value="<?php echo esc_attr( $regscan_value ); ?>"><?php echo esc_html( $regscan_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="kmae-regscan__field" data-field="transmission">
						<span><?php esc_html_e( 'Váltó', 'ki-mit-adna-erte' ); ?></span>
						<select data-kmae-regscan-input="transmission">
							<option value=""><?php esc_html_e( 'Válassz', 'ki-mit-adna-erte' ); ?></option>
							<?php foreach ( KMAE_Vehicle::transmission_labels() as $regscan_value => $regscan_label ) : ?>
								<option value="<?php echo esc_attr( $regscan_value ); ?>"><?php echo esc_html( $regscan_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="kmae-regscan__field kmae-regscan__field--mot" data-field="mot">
						<span><?php esc_html_e( 'Műszaki', 'ki-mit-adna-erte' ); ?></span>
						<div class="kmae-regscan__mot">
							<select data-kmae-regscan-input="mot_has" aria-label="<?php esc_attr_e( 'Van érvényes műszaki?', 'ki-mit-adna-erte' ); ?>">
								<option value=""><?php esc_html_e( 'Válassz', 'ki-mit-adna-erte' ); ?></option>
								<option value="yes"><?php esc_html_e( 'Érvényes', 'ki-mit-adna-erte' ); ?></option>
								<option value="no"><?php esc_html_e( 'Nincs / lejárt', 'ki-mit-adna-erte' ); ?></option>
							</select>
							<select data-kmae-regscan-input="mot_year" aria-label="<?php esc_attr_e( 'Év', 'ki-mit-adna-erte' ); ?>">
								<option value=""><?php esc_html_e( 'Év', 'ki-mit-adna-erte' ); ?></option>
								<?php foreach ( $regscan_years as $regscan_year ) : ?>
									<option value="<?php echo esc_attr( $regscan_year ); ?>"><?php echo esc_html( $regscan_year ); ?></option>
								<?php endforeach; ?>
							</select>
							<select data-kmae-regscan-input="mot_month" aria-label="<?php esc_attr_e( 'Hónap', 'ki-mit-adna-erte' ); ?>">
								<option value=""><?php esc_html_e( 'Hónap', 'ki-mit-adna-erte' ); ?></option>
								<?php foreach ( $regscan_months as $regscan_index => $regscan_month ) : ?>
									<option value="<?php echo esc_attr( $regscan_index + 1 ); ?>"><?php echo esc_html( $regscan_month ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php /* Hátralévő érvényesség a mai naphoz képest (JS). */ ?>
						<p class="kmae-regscan__mot-left" data-kmae-regscan-mot-left hidden></p>
					</div>
					<datalist id="kmae-regscan-brands"></datalist>
					<datalist id="kmae-regscan-models"></datalist>
				</div>

				<p class="kmae-regscan__legend" data-kmae-regscan-legend hidden>
					<span class="kmae-regscan__dot" aria-hidden="true"></span>
					<?php esc_html_e( 'A narancs mezőket nem tudtuk biztosan kiolvasni — ezeket nézd meg különösen.', 'ki-mit-adna-erte' ); ?>
				</p>

				<div class="kmae-regscan__actions">
					<button type="button" class="kmae-regscan__btn kmae-regscan__btn--ghost" data-kmae-regscan-discard><?php esc_html_e( 'Elvetés', 'ki-mit-adna-erte' ); ?></button>
					<button type="button" class="kmae-regscan__btn kmae-regscan__btn--ok" data-kmae-regscan-accept>
						<?php echo $regscan_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Igen, megfelel', 'ki-mit-adna-erte' ); ?></span>
					</button>
				</div>
			</section>

			<?php /* ---------- Hiba ---------- */ ?>
			<section class="kmae-regscan__view" data-kmae-regscan-view="error" hidden>
				<span class="kmae-regscan__alert"><?php echo $regscan_icon( 'alert', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<p class="kmae-regscan__eyebrow kmae-regscan__eyebrow--warn"><?php esc_html_e( 'Nem sikerült', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-regscan__title"><strong data-kmae-regscan-error-a><?php esc_html_e( 'Most nem tudtuk', 'ki-mit-adna-erte' ); ?></strong> <span data-kmae-regscan-error-b><?php esc_html_e( 'kiolvasni', 'ki-mit-adna-erte' ); ?></span></h2>
				<p class="kmae-regscan__lead" data-kmae-regscan-error></p>
				<ul class="kmae-regscan__tips" data-kmae-regscan-tips>
					<li><?php esc_html_e( 'Jó fényben fotózz, vaku és tükröződés nélkül.', 'ki-mit-adna-erte' ); ?></li>
					<li><?php esc_html_e( 'A teljes oldal férjen bele a képbe, élesen.', 'ki-mit-adna-erte' ); ?></li>
					<li><?php esc_html_e( 'Az első oldal a fontosabb: ott van a márka, a modell és a motor.', 'ki-mit-adna-erte' ); ?></li>
				</ul>
				<div class="kmae-regscan__actions">
					<button type="button" class="kmae-regscan__skip" data-kmae-regscan-close><?php esc_html_e( 'Kézzel töltöm ki', 'ki-mit-adna-erte' ); ?></button>
					<button type="button" class="kmae-regscan__btn kmae-regscan__btn--primary" data-kmae-regscan-retry>
						<?php echo $regscan_icon( 'redo', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Új fotókkal próbálom', 'ki-mit-adna-erte' ); ?></span>
					</button>
				</div>
			</section>
		</div>
	</div>
</div>
