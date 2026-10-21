<?php
/**
 * Böngészés — „Távolság szerint” ablak: hely lekérése, majd a kör sugara.
 * JS: assets/js/browse-sort.js (a <body>-ba teszi át). Nézetek: locating / denied / radius.
 *
 * @package KMAE
 * @var array  $sort_req   KMAE_Browse::current_sort()
 * @var string $sort_clean Az aktuális cím rendezés nélkül.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dist_km = $sort_req['km'] > 0 ? (int) $sort_req['km'] : 50;
if ( 'tavolsag' === $sort_req['sort'] && 0 === (int) $sort_req['km'] ) {
	$dist_km = 0;
}
?>
<div class="kmae-dist" data-kmae-dist hidden
	data-base="<?php echo esc_url( $sort_clean ); ?>"
	data-lat="<?php echo 'tavolsag' === $sort_req['sort'] ? esc_attr( (string) $sort_req['lat'] ) : ''; ?>"
	data-lon="<?php echo 'tavolsag' === $sort_req['sort'] ? esc_attr( (string) $sort_req['lon'] ) : ''; ?>"
	data-km="<?php echo esc_attr( (string) $dist_km ); ?>">
	<div class="kmae-dist__backdrop" data-kmae-dist-close></div>
	<div class="kmae-dist__card" role="dialog" aria-modal="true" aria-labelledby="kmae-dist-title" tabindex="-1">
		<button type="button" class="kmae-dist__x" data-kmae-dist-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>">
			<?php echo KMAE_Icons::render( 'x', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>

		<section class="kmae-dist__view" data-kmae-dist-view="locating">
			<span class="kmae-dist__radar" aria-hidden="true">
				<span></span><span></span>
				<?php echo KMAE_Icons::render( 'crosshair', array( 'size' => 28 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<p class="kmae-dist__eyebrow"><?php esc_html_e( 'Távolság szerint', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-dist__title" id="kmae-dist-title"><?php esc_html_e( 'Hol vagy most?', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-dist__lead"><?php esc_html_e( 'A böngésző most megkérdezi, megoszthatod-e a helyzetedet. Engedélyezd, és a hozzád legközelebbi autókat mutatjuk előre.', 'ki-mit-adna-erte' ); ?></p>
			<p class="kmae-dist__note"><?php esc_html_e( 'A helyzetedet nem mentjük el — csak ehhez a kereséshez használjuk.', 'ki-mit-adna-erte' ); ?></p>
			<p class="kmae-dist__status" data-kmae-dist-status role="status"></p>
		</section>

		<section class="kmae-dist__view" data-kmae-dist-view="denied" hidden>
			<span class="kmae-dist__radar is-off" aria-hidden="true">
				<?php echo KMAE_Icons::render( 'lock', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<p class="kmae-dist__eyebrow is-warn"><?php esc_html_e( 'Nincs engedély', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-dist__title"><?php esc_html_e( 'A helymeghatározás le van tiltva', 'ki-mit-adna-erte' ); ?></h2>
			<ol class="kmae-dist__steps">
				<li><?php esc_html_e( 'Kattints a címsor bal szélén lévő ikonra (lakat vagy csúszkák).', 'ki-mit-adna-erte' ); ?></li>
				<li><?php esc_html_e( 'Kapcsold be a „Tartózkodási hely” engedélyt.', 'ki-mit-adna-erte' ); ?></li>
				<li><?php esc_html_e( 'Töltsd újra az oldalt, és válaszd újra a „Távolság szerint” rendezést.', 'ki-mit-adna-erte' ); ?></li>
			</ol>
			<div class="kmae-dist__actions">
				<button type="button" class="kmae-dist__btn kmae-dist__btn--ghost" data-kmae-dist-close><?php esc_html_e( 'Rendben', 'ki-mit-adna-erte' ); ?></button>
			</div>
		</section>

		<section class="kmae-dist__view" data-kmae-dist-view="radius" hidden>
			<p class="kmae-dist__eyebrow"><?php esc_html_e( 'Megvan a helyzeted', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-dist__title"><?php esc_html_e( 'Hány km-es körben keressünk?', 'ki-mit-adna-erte' ); ?></h2>

			<p class="kmae-dist__value" aria-live="polite"><strong data-kmae-dist-value></strong></p>

			<input type="range" class="kmae-dist__range" data-kmae-dist-range min="5" max="300" step="5" aria-label="<?php esc_attr_e( 'Kör sugara (km)', 'ki-mit-adna-erte' ); ?>" />

			<div class="kmae-dist__chips">
				<?php foreach ( KMAE_Browse::RADIUS_STEPS as $dist_step ) : ?>
					<button type="button" class="kmae-dist__chip" data-kmae-dist-chip="<?php echo esc_attr( (string) $dist_step ); ?>"><?php echo esc_html( $dist_step . ' km' ); ?></button>
				<?php endforeach; ?>
				<button type="button" class="kmae-dist__chip" data-kmae-dist-chip="0"><?php esc_html_e( 'Egész ország', 'ki-mit-adna-erte' ); ?></button>
			</div>

			<p class="kmae-dist__note"><?php esc_html_e( 'Légvonalban, a hirdetés településéig mérve. A legközelebbi autók kerülnek előre.', 'ki-mit-adna-erte' ); ?></p>

			<div class="kmae-dist__actions">
				<button type="button" class="kmae-dist__btn kmae-dist__btn--ghost" data-kmae-dist-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
				<button type="button" class="kmae-dist__btn kmae-dist__btn--primary" data-kmae-dist-apply>
					<?php esc_html_e( 'Mutasd a hirdetéseket', 'ki-mit-adna-erte' ); ?>
					<?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</section>
	</div>
</div>
