<?php
/**
 * Guided listing wizard shell (create flow).
 *
 * The stage stays empty in PHP on purpose: the engine moves the real form fields
 * into it step by step, so field markup and field names have a single source.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wizard_phases = KMAE_Listing_Wizard::phases();
?>
<section class="kmae-wizard is-initializing" id="kmae-listing-wizard" aria-busy="true" aria-label="<?php esc_attr_e( 'Hirdetés feladása', 'ki-mit-adna-erte' ); ?>">
	<?php /* Színátmenet-definíció a figyelmeztető (javítandó) kártyák ikonjaihoz:
		CSS-ből erre hivatkozunk `stroke: url(#kmae-warn-grad)` formában. */ ?>
	<svg class="kmae-gradient-defs" width="0" height="0" aria-hidden="true" focusable="false">
		<defs>
			<linearGradient id="kmae-warn-grad" x1="0" y1="1" x2="1" y2="0">
				<stop offset="0%" stop-color="#ff2d2d" />
				<stop offset="52%" stop-color="#ff7a18" />
				<stop offset="100%" stop-color="#ffd93d" />
			</linearGradient>
		</defs>
	</svg>

	<div class="kmae-wizard-panel">
		<div class="kmae-wizard-aside">
			<div class="kmae-wizard-sidecard">
				<div class="kmae-wizard-sidecard__head">
					<p class="kmae-wizard-sidecard__title">
						<span><?php esc_html_e( 'Hirdetés', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-wizard-sidecard__title-accent"><?php esc_html_e( 'feladása', 'ki-mit-adna-erte' ); ?></span>
					</p>
					<p class="kmae-wizard-sidecard__lead"><?php esc_html_e( 'Lépésről lépésre, egyszerűen.', 'ki-mit-adna-erte' ); ?></p>
				</div>

				<?php /* Nem rejtett: a lépéslista statikus, így nem ugrik be a JS után. */ ?>
				<nav class="kmae-wizard-steps" id="kmae-wizard-progress" aria-label="<?php esc_attr_e( 'A hirdetésfeladás szakaszai', 'ki-mit-adna-erte' ); ?>">
					<ol class="kmae-wizard-steps__list">
						<?php foreach ( $wizard_phases as $phase_code => $phase ) : ?>
							<li class="kmae-wizard-steps__item" data-kmae-phase="<?php echo esc_attr( $phase_code ); ?>">
								<span class="kmae-wizard-steps__num" aria-hidden="true"></span>
								<span class="kmae-wizard-steps__icon" aria-hidden="true">
									<?php echo KMAE_Icons::render( $phase['icon'], array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="kmae-wizard-steps__label"><?php echo esc_html( $phase['label'] ); ?></span>
								<span class="kmae-wizard-steps__check" aria-hidden="true">
									<?php echo KMAE_Icons::render( 'circle-check', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			</div>

			<?php if ( KMAE_Registration_Scan::is_enabled() ) : ?>
				<?php /* Forgalmi beolvasása: csak az autó adatainál látszik (CSS: data-phase="auto"). */ ?>
				<button type="button" class="kmae-regscan-entry" data-kmae-regscan-open hidden>
					<span class="kmae-regscan-entry__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" focusable="false"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</span>
					<span class="kmae-regscan-entry__text">
						<strong><?php esc_html_e( 'Kitöltés a forgalmiból', 'ki-mit-adna-erte' ); ?></strong>
						<small><?php esc_html_e( '2 fotó, és kész az autó adatlapja', 'ki-mit-adna-erte' ); ?></small>
					</span>
				</button>
			<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
				<?php /* Csak adminnak: miért nem látszik a forgalmi beolvasása. */ ?>
				<div class="kmae-regscan-admin-note" role="note">
					<strong><?php esc_html_e( 'Forgalmi beolvasása: nem aktív', 'ki-mit-adna-erte' ); ?></strong>
					<span>
						<?php
						if ( '' === KMAE_Registration_Scan::api_key() ) {
							esc_html_e( 'Nincs elmentve OpenAI API-kulcs.', 'ki-mit-adna-erte' );
						} else {
							esc_html_e( 'A funkció ki van kapcsolva a beállításokban.', 'ki-mit-adna-erte' );
						}
						?>
					</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmae-settings' ) ); ?>"><?php esc_html_e( 'Albira → Beállítások', 'ki-mit-adna-erte' ); ?></a>
					<small><?php esc_html_e( 'Ezt csak adminisztrátor látja.', 'ki-mit-adna-erte' ); ?></small>
				</div>
			<?php endif; ?>
		</div>

		<div class="kmae-wizard-main">
			<div class="kmae-wizard-boot" aria-hidden="true">
				<span></span><span></span><span></span>
			</div>
			<div class="kmae-wizard-step-progress" id="kmae-wizard-step-progress" hidden>
				<?php /* Az út: aszfaltsáv, szaggatott felezővonal, állomáspontok és a
					végén célzászló. Minden CSS-ből rajzolódik, nincs hozzá kép.

					Az autó három rétegből áll: leghátul a kerék (gumi), rá a külön FELNI
					kép, legelöl a karosszéria — így a kerékív takarja a gumi tetejét.
					Haladás közben a gumi és a felni azonos szögben forog, megálláskor a
					gumi megáll, a felni pedig kipörög és fedésbe áll vissza. A pozíciót,
					a forgást és az állomáspontokat a JS számolja. */ ?>
				<div class="kmae-wizard-road" id="kmae-wizard-road" aria-hidden="true">
					<span class="kmae-wizard-road__haze" id="kmae-wizard-road-haze"></span>
					<span class="kmae-wizard-road__surface"></span>
					<span class="kmae-wizard-road__trail" id="kmae-wizard-step-progress-fill"></span>
					<span class="kmae-wizard-road__lane"></span>
					<span class="kmae-wizard-road__stops" id="kmae-wizard-road-stops"></span>

					<span class="kmae-wizard-road__finish">
						<span class="kmae-wizard-road__finish-glow"></span>
						<span class="kmae-wizard-road__finish-pole"></span>
						<span class="kmae-wizard-road__finish-flag"></span>
					</span>

					<div class="kmae-wizard-roadcar" id="kmae-wizard-roadcar">
						<span class="kmae-wizard-roadcar__underglow"></span>
						<img class="kmae-wizard-roadcar__body" src="https://albira.hu/wp-content/uploads/2026/09/kocsi.webp" alt="" width="200" height="67" loading="lazy" decoding="async" />

						<span class="kmae-wizard-roadcar__wheel kmae-wizard-roadcar__wheel--rear">
							<img class="kmae-wizard-roadcar__tire" src="https://albira.hu/wp-content/uploads/2026/09/kerek.webp" alt="" width="100" height="100" loading="lazy" decoding="async" />
							<img class="kmae-wizard-roadcar__rim" src="https://albira.hu/wp-content/uploads/2026/09/felni.webp" alt="" width="100" height="100" loading="lazy" decoding="async" />
						</span>

						<span class="kmae-wizard-roadcar__wheel kmae-wizard-roadcar__wheel--front">
							<img class="kmae-wizard-roadcar__tire" src="https://albira.hu/wp-content/uploads/2026/09/kerek.webp" alt="" width="100" height="100" loading="lazy" decoding="async" />
							<img class="kmae-wizard-roadcar__rim" src="https://albira.hu/wp-content/uploads/2026/09/felni.webp" alt="" width="100" height="100" loading="lazy" decoding="async" />
						</span>
					</div>
				</div>

				<?php /* A lépésszámláló a célzászló ALATT, az út jobb szélén — így nem
					takarja ki a zászlót. */ ?>
				<span class="kmae-wizard-step-progress__count" id="kmae-wizard-step-progress-count">
					<span class="kmae-wizard-step-progress__now" id="kmae-wizard-step-progress-now"></span>
					<span class="kmae-wizard-step-progress__sep" aria-hidden="true">/</span>
					<span class="kmae-wizard-step-progress__all" id="kmae-wizard-step-progress-all"></span>
				</span>

			</div>

			<p class="kmae-wizard-eyebrow" id="kmae-wizard-eyebrow" hidden></p>

			<p class="kmae-wizard-section-intro" id="kmae-wizard-section-intro" hidden></p>

			<header class="kmae-wizard-question">
				<span class="kmae-wizard-question__icon" aria-hidden="true">
					<?php foreach ( $wizard_phases as $phase_code => $phase ) : ?>
						<span class="kmae-wizard-question__glyph" data-kmae-phase-icon="<?php echo esc_attr( $phase_code ); ?>">
							<?php echo KMAE_Icons::render( $phase['icon'], array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					<?php endforeach; ?>
					<?php /* Lépés-specifikus ikon: felülírja a fázisikont, ha van hozzá. */ ?>
					<span class="kmae-wizard-question__glyph" data-kmae-step-icon="vehicle_year">
						<?php echo KMAE_Vehicle_Icons::render( 'year', '', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="kmae-wizard-question__glyph" data-kmae-step-icon="vehicle_mileage">
						<?php echo KMAE_Vehicle_Icons::render( 'mileage', '', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="kmae-wizard-question__glyph" data-kmae-step-icon="vehicle_traits">
						<?php echo KMAE_Icons::render( 'star', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</span>
				<div class="kmae-wizard-question__text">
					<h3 class="kmae-wizard-question__title" id="kmae-wizard-prompt"></h3>
					<p class="kmae-wizard-question__lead" id="kmae-wizard-hint" hidden></p>
				</div>
			</header>

			<div class="kmae-wizard-stage" id="kmae-wizard-stage"></div>

			<p class="kmae-wizard-feedback" id="kmae-wizard-feedback" role="status" hidden></p>

			<div class="kmae-wizard-actions">
				<button type="button" class="kmae-btn kmae-wizard-btn kmae-wizard-btn--back" data-kmae-wizard="back" hidden>
					<?php echo KMAE_Icons::render( 'arrow-left', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Vissza', 'ki-mit-adna-erte' ); ?></span>
				</button>
				<button type="button" class="kmae-btn kmae-wizard-btn kmae-wizard-btn--next" data-kmae-wizard="next">
					<span><?php esc_html_e( 'Tovább', 'ki-mit-adna-erte' ); ?></span>
					<?php echo KMAE_Icons::render( 'arrow-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>

			<?php /* A dicséret a gombsor ALATT, külön sorban: a két gomb között
				összeszorult, és szétnyomta a sort. Itt van helye, és a
				szövegbuborék is rendesen látszik. */ ?>
			<div class="kmae-wizard-cheer" id="kmae-wizard-cheer" hidden>
				<img class="kmae-wizard-cheer__mascot" id="kmae-wizard-cheer-img"
					src="" alt="" aria-hidden="true" width="200" height="200" decoding="async" />
				<p class="kmae-wizard-cheer__bubble" id="kmae-wizard-cheer-text" role="status"></p>
			</div>
		</div>

		<?php /* Ez a sáv az eddigi VÁLASZOKAT gyűjti össze, nem a kész hirdetést
			mutatja — ezért nincs benne fotóhely. A tényleges „így fog kinézni"
			előnézet a folyamat végére való, amikor már szöveg és kép is van. */ ?>
		<aside class="kmae-wizard-preview" id="kmae-wizard-preview" aria-label="<?php esc_attr_e( 'Eddigi válaszaid', 'ki-mit-adna-erte' ); ?>">
			<div class="kmae-wizard-preview__head">
				<p class="kmae-wizard-preview__label">
					<?php
					echo wp_kses(
						__( 'Eddigi <span class="kmae-wizard-preview__label-accent">válaszaid</span>', 'ki-mit-adna-erte' ),
						array( 'span' => array( 'class' => true ) )
					);
					?>
				</p>
				<p class="kmae-wizard-preview__lead"><?php esc_html_e( 'Itt gyűjtjük össze, amit eddig megadtál az autóról.', 'ki-mit-adna-erte' ); ?></p>

				<?php /* Haladásjelző: a felhasználó lássa, hol tart az adatlap. */ ?>
				<div class="kmae-wizard-preview__progress">
					<span class="kmae-wizard-preview__progress-text">
						<strong id="kmae-wizard-preview-done">0</strong>
						<span class="kmae-wizard-preview__progress-sep">/</span>
						<span id="kmae-wizard-preview-total">0</span>
						<?php esc_html_e( 'kitöltve', 'ki-mit-adna-erte' ); ?>
					</span>
					<span class="kmae-wizard-preview__progress-track" aria-hidden="true">
						<span class="kmae-wizard-preview__progress-fill" id="kmae-wizard-preview-bar"></span>
					</span>
				</div>
			</div>

			<div class="kmae-wizard-preview__rows">
				<?php
				/* A sorrend PONTOSAN követi a kérdések sorrendjét: így az eddigi
					válaszok fentről lefelé épülnek, és a most megválaszolt kérdés
					mindig a lista alján jelenik meg.

					A `steps` mező köti a sort a wizard lépéséhez — ebből tudjuk,
					melyik adatblokk van éppen kitöltés alatt. */
				$preview_rows = array(
					array( 'icon' => 'car', 'field' => '', 'label' => __( 'Márka', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-brand', 'steps' => 'vehicle_brand' ),
					array( 'icon' => 'tag', 'field' => '', 'label' => __( 'Modell', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-model', 'steps' => 'vehicle_model' ),
					array( 'field' => 'year', 'label' => __( 'Évjárat', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-year', 'steps' => 'vehicle_year' ),
					array( 'field' => 'transmission', 'label' => __( 'Sebességváltó', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-transmission', 'steps' => 'vehicle_transmission' ),
					array( 'field' => 'power', 'label' => __( 'Teljesítmény', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-power', 'steps' => 'vehicle_power_hp' ),
					array( 'field' => 'engine', 'label' => __( 'Hengerűrtartalom', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-engine', 'steps' => 'vehicle_engine_cc' ),
					array( 'field' => 'fuel', 'label' => __( 'Üzemanyag', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-fuel', 'steps' => 'vehicle_fuel' ),
					array( 'field' => 'mot', 'label' => __( 'Műszaki érvényessége', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-mot', 'steps' => 'vehicle_mot_has vehicle_mot_date' ),
					array( 'field' => 'mileage', 'label' => __( 'Km óra állása', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-mileage', 'steps' => 'vehicle_mileage' ),
					array( 'icon' => 'layers', 'field' => '', 'label' => __( 'Kivitel', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-body', 'steps' => 'vehicle_body' ),
					array( 'field' => 'condition', 'label' => __( 'Állapot', 'ki-mit-adna-erte' ), 'id' => 'kmae-wizard-preview-condition', 'steps' => 'vehicle_condition' ),
				);
				foreach ( $preview_rows as $preview_row ) :
					?>
					<div class="kmae-wizard-preview__row is-empty" data-kmae-steps="<?php echo esc_attr( $preview_row['steps'] ); ?>">
						<span class="kmae-wizard-preview__row-icon" data-kmae-vehicle-icon="<?php echo esc_attr( $preview_row['field'] ); ?>" aria-hidden="true">
							<?php
							if ( ! empty( $preview_row['field'] ) ) {
								echo KMAE_Vehicle_Icons::render( $preview_row['field'], '', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo KMAE_Icons::render( $preview_row['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</span>
						<span class="kmae-wizard-preview__row-text">
							<span class="kmae-wizard-preview__row-label"><?php echo esc_html( $preview_row['label'] ); ?></span>
							<strong class="kmae-wizard-preview__row-value" id="<?php echo esc_attr( $preview_row['id'] ); ?>">&ndash;</strong>
						</span>
						<?php /* A kész jel saját kép, nem ikon — így pontosan az a pipa
							látszik, amit a hirdetés többi felületén is használunk. */ ?>
						<span class="kmae-wizard-preview__row-status" aria-hidden="true">
							<img class="kmae-wizard-preview__row-tick"
								src="https://albira.hu/wp-content/uploads/2026/09/pipa.webp"
								alt="" width="20" height="20" loading="lazy" decoding="async" />
						</span>
					</div>
				<?php endforeach; ?>
			</div>

			<?php /* A kiemelések nem adatsorok, hanem az eladó saját választásai —
				ezért külön blokkban, és csak akkor, ha tényleg választott. */ ?>
			<div class="kmae-wizard-preview__extras" id="kmae-wizard-preview-extras" hidden>
				<p class="kmae-wizard-preview__extras-title"><?php esc_html_e( 'Kiemelések', 'ki-mit-adna-erte' ); ?></p>
				<div class="kmae-wizard-preview__chips" id="kmae-wizard-preview-traits"></div>
			</div>

			<?php /* A szövegíró lépésen ez a sáv veszi át a panelt: az adatok
				összegzése ott már megtette a dolgát, itt a segítség a hasznos.
				Az Albira NEM ír helyetted — azt mutatja, miről nem esett még szó. */ ?>
			<div class="kmae-wizard-assist" id="kmae-wizard-assist" hidden>
				<?php /* Az Albira figurája saját sort kap, szövegbuborékkal — a
					keskeny sávban a sarokba szorítva alig látszott belőle valami. */ ?>
				<div class="kmae-wizard-assist__head">
					<?php /* A buborékba a JS írja a sorokat, egyesével. */ ?>
					<p class="kmae-wizard-assist__bubble" id="kmae-wizard-assist-bubble" role="status"></p>
					<img class="kmae-wizard-assist__mascot"
						src="https://albira.hu/wp-content/uploads/2026/10/lefelemutat3.webp"
						alt="" aria-hidden="true" width="200" height="374" loading="lazy" decoding="async" />
				</div>

				<?php $assist_topics = KMAE_Listing_Wizard::description_topics(); ?>

				<p class="kmae-wizard-assist__progress">
					<span class="kmae-wizard-assist__percent" id="kmae-wizard-assist-percent">0%</span>
					<span class="kmae-wizard-assist__count">
						<span id="kmae-wizard-assist-done">0</span>/<?php echo esc_html( count( $assist_topics ) ); ?>
						<?php esc_html_e( 'szempont', 'ki-mit-adna-erte' ); ?>
					</span>
				</p>

				<div class="kmae-wizard-assist__bar" aria-hidden="true">
					<span class="kmae-wizard-assist__bar-fill" id="kmae-wizard-assist-bar"></span>
				</div>

				<p class="kmae-wizard-assist__nudge" id="kmae-wizard-assist-nudge"></p>

				<?php /* Minden szemponthoz jelvény tartozik, hogy egy pillantással
					felismerhető legyen. A gomb állapotát a JS váltja:
					„+" beszúrja a fix szöveget, „−" kiveszi, „✓" készen van. */ ?>
				<ul class="kmae-wizard-assist__list">
					<?php foreach ( $assist_topics as $topic_code => $topic ) : ?>
						<li class="kmae-wizard-assist__item is-<?php echo esc_attr( $topic['level'] ); ?>"
							data-kmae-topic="<?php echo esc_attr( $topic_code ); ?>"
							data-kmae-insert="<?php echo esc_attr( $topic['insert'] ); ?>"
							data-kmae-marker="<?php echo esc_attr( $topic['marker'] ); ?>"
							data-kmae-caret="<?php echo esc_attr( isset( $topic['caret'] ) ? $topic['caret'] : '' ); ?>">
							<span class="kmae-wizard-assist__badge" aria-hidden="true">
								<?php echo KMAE_Icons::render( $topic['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="kmae-wizard-assist__label"><?php echo esc_html( $topic['label'] ); ?></span>
							<button type="button" class="kmae-wizard-assist__btn" data-kmae-assist-btn
								aria-label="<?php echo esc_attr( sprintf( __( '%s hozzáadása a leíráshoz', 'ki-mit-adna-erte' ), $topic['label'] ) ); ?>">
								<span class="kmae-wizard-assist__btn-glyph" aria-hidden="true">+</span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>

			</div>

		</aside>
	</div>

</section>

<?php if ( KMAE_Registration_Scan::is_enabled() ) { include KMAE_PLUGIN_DIR . 'templates/partials/registration-scan.php'; } ?>
