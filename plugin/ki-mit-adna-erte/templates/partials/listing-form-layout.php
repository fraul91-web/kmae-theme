<?php
/**
 * Complete listing create/edit page layout.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cancel_url = $is_edit ? home_url( '/sajat-hirdetesek/' ) : KMAE_Back::browse_url();
$is_simple  = $is_edit || $draft_simple;
if ( $is_edit && isset( $_GET['kmae_mode'] ) && 'full' === sanitize_key( wp_unslash( $_GET['kmae_mode'] ) ) ) {
	$is_simple = false;
}
$simple_full_url = '';
if ( $is_simple && $draft_simple ) {
	$simple_full_url = remove_query_arg( 'kmae_mode' );
}
$preview_url = ( $is_edit && $listing ) ? get_permalink( $listing ) : '';
$simple_brand = '';
$simple_logo  = '';
if ( $is_simple && $is_vehicle && $is_edit && $listing ) {
	$simple_specs = KMAE_Vehicle::get_specs( $listing->ID );
	$simple_brand = isset( $simple_specs['brand'] ) ? (string) $simple_specs['brand'] : '';
	$simple_logos = KMAE_Vehicle::brand_logos();
	$simple_logo  = ( '' !== $simple_brand && isset( $simple_logos[ $simple_brand ] ) ) ? $simple_logos[ $simple_brand ] : '';
}
?>

<div class="kmae-create-page kmae-listing-create<?php echo $is_edit ? ' is-editing' : ' is-creating is-wizard-live'; ?><?php echo $is_simple ? ' is-simple' : ''; ?>">
	<?php if ( ! $is_simple && $is_edit ) : ?>
		<header class="kmae-create-hero">
			<div class="kmae-create-hero__content">
				<span class="kmae-create-hero__eyebrow"><?php esc_html_e( 'Hirdetés szerkesztése', 'ki-mit-adna-erte' ); ?></span>
				<h1 class="kmae-create-hero__title"><?php esc_html_e( 'Tedd naprakésszé a hirdetésed', 'ki-mit-adna-erte' ); ?></h1>
				<p class="kmae-create-hero__lead"><?php esc_html_e( 'Ellenőrizd az adatokat, az eladás módját és a feltöltött fotókat.', 'ki-mit-adna-erte' ); ?></p>
				<p><a href="<?php echo esc_url( remove_query_arg( 'kmae_mode' ) ); ?>"><?php esc_html_e( 'Vissza az egyszerű módba', 'ki-mit-adna-erte' ); ?></a></p>
			</div>
		</header>
	<?php endif; ?>

	<main class="kmae-create-main">
		<?php if ( $is_simple ) : ?>
			<header class="kmae-simple-head">
				<div class="kmae-simple-head__title">
					<img class="kmae-simple-head__car" src="https://albira.hu/wp-content/uploads/2026/09/sedan.webp" alt="" width="84" height="42" />
					<div>
						<h1>
							<?php esc_html_e( 'Hirdetés szerkesztése', 'ki-mit-adna-erte' ); ?>
							<span><?php esc_html_e( 'Egyszerű mód', 'ki-mit-adna-erte' ); ?></span>
						</h1>
						<p><?php echo $draft_simple ? esc_html__( 'Gyorsan és kényelmesen módosíthatod a legfontosabb adatokat. A mentés automatikus.', 'ki-mit-adna-erte' ) : esc_html__( 'Gyorsan és kényelmesen módosíthatod a legfontosabb adatokat.', 'ki-mit-adna-erte' ); ?></p>
					</div>
				</div>
				<?php if ( $simple_full_url ) : ?>
					<a class="kmae-simple-head__full" href="<?php echo esc_url( $simple_full_url ); ?>"><?php esc_html_e( 'Teljes szerkesztés (Varázsló mód)', 'ki-mit-adna-erte' ); ?></a>
				<?php endif; ?>
			</header>
		<?php endif; ?>
		<div class="kmae-create-notices">
			<?php if ( $is_awaiting ) : ?>
				<div class="kmae-create-notice kmae-create-notice--info">
					<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'info', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div>
						<strong><?php esc_html_e( 'A hirdetés fotókra vár', 'ki-mit-adna-erte' ); ?></strong>
						<p><?php esc_html_e( 'Az adatok már el vannak mentve. Készítsd el a kötelező autófotókat telefonról, majd mentsd újra a hirdetést.', 'ki-mit-adna-erte' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! $is_edit && is_wp_error( $can_create ) ) : ?>
				<div class="kmae-create-notice kmae-create-notice--error">
					<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'triangle-alert', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div>
						<strong><?php esc_html_e( 'Most nem adhatsz fel új hirdetést', 'ki-mit-adna-erte' ); ?></strong>
						<p><?php echo esc_html( $can_create->get_error_message() ); ?></p>
						<a href="<?php echo esc_url( home_url( '/sajat-hirdetesek/' ) ); ?>"><?php esc_html_e( 'Saját hirdetéseim megnyitása', 'ki-mit-adna-erte' ); ?> →</a>
					</div>
				</div>
			<?php else : ?>
				<?php /* Amit magunktól rendbe tettünk — megnyugtató, nem hibajelzés. */ ?>
				<?php if ( ! empty( $photo_fixes ) ) : ?>
					<div class="kmae-create-notice kmae-create-notice--fixed">
						<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'sparkles', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<div>
							<strong><?php esc_html_e( 'Ezeket kijavítottuk helyetted', 'ki-mit-adna-erte' ); ?></strong>
							<ul>
								<?php foreach ( $photo_fixes as $fix ) : ?>
									<li>
										<strong><?php echo esc_html( $fix['slot'] ); ?>:</strong>
										<?php echo esc_html( implode( ', ', (array) $fix['fixes'] ) ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $mod_errors ) ) : ?>
					<div class="kmae-create-notice kmae-create-notice--error">
						<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'triangle-alert', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<div>
							<strong><?php esc_html_e( 'Ezt neked kell megoldanod', 'ki-mit-adna-erte' ); ?></strong>
							<ul>
								<?php
								/* A moderáció sima szöveget ad, a fotóellenőrzés viszont
								   nézetre bontott adatot tanáccsal együtt — mindkettőt
								   kezeljük, hogy egyik forrás se törjön el. */
								foreach ( $mod_errors as $reason ) :
									?>
									<?php if ( is_array( $reason ) ) : ?>
										<li>
											<strong><?php echo esc_html( $reason['slot'] ?? '' ); ?>:</strong>
											<?php echo esc_html( $reason['reason'] ?? '' ); ?>
											<?php if ( ! empty( $reason['advice'] ) ) : ?>
												<span class="kmae-create-notice__advice"><?php echo esc_html( $reason['advice'] ); ?></span>
											<?php endif; ?>
										</li>
									<?php else : ?>
										<li><?php echo esc_html( $reason ); ?></li>
									<?php endif; ?>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $error ) : ?>
					<div class="kmae-create-notice kmae-create-notice--error">
						<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'triangle-alert', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<div><p><?php echo esc_html( $listing_error_messages[ $error ] ?? $listing_error_messages['missing_fields'] ); ?></p></div>
					</div>
				<?php endif; ?>

				<form method="post" enctype="multipart/form-data" class="kmae-form-wrap kmae-form kmae-create-form kmae-listing-form" id="kmae-listing-form" data-open-photos="<?php echo $open_photos ? '1' : '0'; ?>" data-awaiting="<?php echo $is_awaiting ? '1' : '0'; ?>" <?php echo $is_edit ? 'action="' . esc_url( $form_action ) . '"' : ''; ?>>
					<?php if ( $is_edit ) : ?>
						<?php wp_nonce_field( 'kmae_update_listing', 'kmae_update_listing_nonce' ); ?>
						<input type="hidden" name="action" value="kmae_update_listing" />
						<input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $listing->ID ); ?>" />
						<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( add_query_arg( 'kmae_edit', $listing->ID, home_url( '/hirdetes-feladasa/' ) ) ); ?>" />
					<?php else : ?>
						<?php wp_nonce_field( 'kmae_create_listing_nonce', 'kmae_create_listing_nonce' ); ?>
						<input type="hidden" name="kmae_action" value="create_listing" />
						<input type="hidden" name="kmae_draft_id" id="kmae_draft_id" value="<?php echo $draft_simple ? esc_attr( absint( $_GET['kmae_draft'] ) ) : ''; ?>" />
						<?php if ( KMAE_Photo_Motor::is_enabled() ) : ?>
							<input type="hidden" name="kmae_create_device" id="kmae_create_device" value="" />
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! $is_edit && ! $draft_simple ) : ?>
						<?php include KMAE_PLUGIN_DIR . 'templates/partials/listing-wizard.php'; ?>
					<?php endif; ?>

					<?php
					/*
					 * Feladásnál a klasszikus szekciók már a kiszolgált HTML-ben rejtve
					 * vannak: így nem villannak fel, amíg a wizard motor elindul. A JS
					 * ugyanezt az osztályt teszi rá később, tehát nincs kettős forrás.
					 */
					?>
					<?php if ( ! $is_edit && ! $draft_simple ) : ?>
						<noscript>
							<style>
								/* JS nélkül nincs wizard: a klasszikus űrlap veszi át. */
								.kmae-create-page .kmae-wizard { display: none; }
								.kmae-create-page .kmae-create-fields.is-wizard-active > .kmae-create-section { display: block; }
							</style>
						</noscript>
					<?php endif; ?>
					<div class="kmae-create-fields<?php echo ( $is_edit || $draft_simple ) ? '' : ' is-wizard-active'; ?>" id="kmae-listing-fields" <?php echo ( ! $is_edit && ! $draft_simple && KMAE_Photo_Motor::is_enabled() ) ? 'hidden' : ''; ?>>
						<section class="kmae-create-section" aria-labelledby="kmae-section-vehicle">
							<div class="kmae-create-section__head">
								<span class="kmae-create-section__number">01</span>
								<div>
									<h2 id="kmae-section-vehicle"><?php echo $is_simple ? esc_html__( 'Autó alapadatai', 'ki-mit-adna-erte' ) : esc_html__( 'Az autó legfontosabb adatai', 'ki-mit-adna-erte' ); ?></h2>
									<p><?php echo $is_simple ? esc_html__( 'Márka, modell, évjárat és a legfontosabb műszaki adatok.', 'ki-mit-adna-erte' ) : esc_html__( 'A pontos adatok jobb találatokat és komolyabb érdeklődőket hoznak.', 'ki-mit-adna-erte' ); ?></p>
								</div>
							</div>
							<div class="kmae-create-section__body" id="kmae-section-vehicle-body">
								<div class="kmae-simple-vehicle">
								<div class="kmae-simple-vehicle__main">
								<?php if ( $is_vehicle ) : ?>
									<?php include KMAE_PLUGIN_DIR . 'templates/partials/vehicle-form-fields.php'; ?>
								<?php else : ?>
									<div class="kmae-field">
										<label for="kmae_category"><?php esc_html_e( 'Kategória *', 'ki-mit-adna-erte' ); ?></label>
										<select name="kmae_category" id="kmae_category" required>
											<option value=""><?php esc_html_e( 'Válassz kategóriát', 'ki-mit-adna-erte' ); ?></option>
											<?php foreach ( $categories as $cat ) : ?>
												<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $current_cat_id, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								<?php endif; ?>
								<?php if ( $is_vehicle ) : ?>
									<input type="hidden" name="kmae_category" value="<?php echo esc_attr( $launch_cat_id ); ?>" />
								<?php endif; ?>
								</div>
								</div>
							</div>
						</section>

						<section class="kmae-create-section" aria-labelledby="kmae-section-ad">
							<div class="kmae-create-section__head">
								<span class="kmae-create-section__number">02</span>
								<div>
									<h2 id="kmae-section-ad"><?php esc_html_e( 'Hirdetés tartalma', 'ki-mit-adna-erte' ); ?></h2>
									<p><?php echo $is_simple ? esc_html__( 'Cím, helyszín és leírás.', 'ki-mit-adna-erte' ) : esc_html__( 'Mutasd be őszintén az autót, és add meg, hol tekinthető meg.', 'ki-mit-adna-erte' ); ?></p>
								</div>
							</div>
							<div class="kmae-create-section__body" id="kmae-section-ad-body">
								<div class="kmae-field">
									<label for="kmae_title"><?php esc_html_e( 'Hirdetés címe *', 'ki-mit-adna-erte' ); ?></label>
									<input type="text" name="kmae_title" id="kmae_title" required value="<?php echo esc_attr( $title_val ); ?>" placeholder="<?php esc_attr_e( 'Például: BMW 320d, 2018, automata', 'ki-mit-adna-erte' ); ?>" />
									<span class="kmae-simple-count" data-kmae-count-for="kmae_title" data-kmae-count-max="120"></span>
									<?php if ( $is_vehicle ) : ?><p class="kmae-hint"><?php esc_html_e( 'A márka, modell és évjárat alapján címet is javaslunk, amit szabadon átírhatsz.', 'ki-mit-adna-erte' ); ?></p><?php endif; ?>
								</div>
								<div class="kmae-field">
									<label for="kmae_description"><?php echo $is_simple ? esc_html__( 'Hirdetés szövege *', 'ki-mit-adna-erte' ) : esc_html__( 'Részletes leírás *', 'ki-mit-adna-erte' ); ?></label>
									<textarea name="kmae_description" id="kmae_description" rows="7" required placeholder="<?php esc_attr_e( 'Írj a szerviztörténetről, használatról, extrákról és az autó előnyeiről…', 'ki-mit-adna-erte' ); ?>"><?php echo esc_textarea( $desc_val ); ?></textarea>
									<span class="kmae-simple-count" data-kmae-count-for="kmae_description" data-kmae-count-max="5000"></span>
									<?php if ( KMAE_Moderation::is_enabled() ) : ?><p class="kmae-hint"><?php printf( esc_html__( 'Legalább %d karakter. Telefonszámot és e-mail-címet ne írj ide.', 'ki-mit-adna-erte' ), KMAE_Moderation::MIN_DESC_LENGTH ); ?></p><?php endif; ?>
								</div>
								<?php if ( $is_company_user ) : ?>
									<div class="kmae-create-notice kmae-create-notice--info">
										<span class="kmae-create-notice__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'map-pin', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<div>
											<strong><?php esc_html_e( 'Nem kell külön címet megadnod', 'ki-mit-adna-erte' ); ?></strong>
											<p><?php printf( esc_html__( 'A hirdetés helyszíne a céges telephelyed: %s', 'ki-mit-adna-erte' ), esc_html( $company_location ) ); ?></p>
											<a class="kmae-create-notice__action" href="<?php echo esc_url( home_url( '/profil/' ) ); ?>"><?php esc_html_e( 'Telephely módosítása a profilban', 'ki-mit-adna-erte' ); ?> →</a>
										</div>
									</div>
								<?php else : ?>
									<div class="kmae-field-row">
										<div class="kmae-field">
											<label for="kmae_county"><?php esc_html_e( 'Megye *', 'ki-mit-adna-erte' ); ?></label>
											<select name="kmae_county" id="kmae_county" required>
												<option value=""><?php esc_html_e( 'Válassz megyét', 'ki-mit-adna-erte' ); ?></option>
												<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
													<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $county_val, $slug ); ?>><?php echo esc_html( $label ); ?></option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="kmae-field">
											<label for="kmae_city"><?php esc_html_e( 'Város *', 'ki-mit-adna-erte' ); ?></label>
											<input type="text" name="kmae_city" id="kmae_city" required value="<?php echo esc_attr( $city_val ); ?>" placeholder="<?php esc_attr_e( 'Például: Szeged', 'ki-mit-adna-erte' ); ?>" />
										</div>
									</div>
									<?php /* Wizard és egyszerű szerkesztő: megye + város kitöltése a böngésző helymeghatározásából (KMAE_Counties::ajax_locate, listing-locate.js). */ ?>
										<div class="kmae-locate">
											<button type="button" class="kmae-locate__btn" data-kmae-locate>
												<span class="kmae-locate__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'crosshair', array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
												<span class="kmae-locate__text">
													<strong><?php esc_html_e( 'Jelenlegi helyzetem megadása', 'ki-mit-adna-erte' ); ?></strong>
													<small><?php esc_html_e( 'A megyét és a várost automatikusan kitöltjük.', 'ki-mit-adna-erte' ); ?></small>
												</span>
												<span class="kmae-locate__arrow" aria-hidden="true"><?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
											</button>
											<span class="kmae-locate__status" data-kmae-locate-status role="status" aria-live="polite"></span>

											<?php /* Útmutató ablak, ha a böngésző tiltja a helymeghatározást. A JS a <body>-ba teszi át. */ ?>
											<div class="kmae-locate-dialog" data-kmae-locate-dialog hidden>
												<div class="kmae-locate-dialog__backdrop" data-kmae-locate-close></div>
												<div class="kmae-locate-dialog__card" role="dialog" aria-modal="true" aria-labelledby="kmae-locate-dialog-title">
													<img class="kmae-locate-dialog__mascot" src="https://albira.hu/wp-content/uploads/2026/10/lefelemutat3.webp" alt="" width="120" height="224" decoding="async" />
													<div data-kmae-locate-view="blocked" hidden>
														<h3 class="kmae-locate-dialog__title" id="kmae-locate-dialog-title"><?php esc_html_e( 'A helymeghatározás le van tiltva', 'ki-mit-adna-erte' ); ?></h3>
														<p class="kmae-locate-dialog__lead"><?php esc_html_e( 'Korábban letiltottad ennél az oldalnál. Így engedélyezheted újra:', 'ki-mit-adna-erte' ); ?></p>
														<ol class="kmae-locate-dialog__steps">
															<li><?php esc_html_e( 'Kattints a címsor bal szélén lévő ikonra (lakat vagy csúszkák).', 'ki-mit-adna-erte' ); ?></li>
															<li><?php esc_html_e( 'Kapcsold be a „Tartózkodási hely” / „Helyadatok” engedélyt.', 'ki-mit-adna-erte' ); ?></li>
															<li><?php esc_html_e( 'Töltsd újra az oldalt, és nyomd meg újra a gombot.', 'ki-mit-adna-erte' ); ?></li>
														</ol>
														<p class="kmae-locate-dialog__note"><?php esc_html_e( 'Vagy egyszerűen add meg kézzel a megyét és a várost.', 'ki-mit-adna-erte' ); ?></p>
														<div class="kmae-locate-dialog__actions">
															<button type="button" class="kmae-locate-dialog__primary" data-kmae-locate-close><?php esc_html_e( 'Rendben', 'ki-mit-adna-erte' ); ?></button>
														</div>
													</div>
												</div>
											</div>
										</div>
								<?php endif; ?>
								<?php if ( ! $is_vehicle ) : ?>
									<div class="kmae-field">
										<label for="kmae_condition"><?php esc_html_e( 'Állapot', 'ki-mit-adna-erte' ); ?></label>
										<input type="text" name="kmae_condition" id="kmae_condition" value="<?php echo esc_attr( $is_edit ? get_post_meta( $listing->ID, '_kmae_condition', true ) : '' ); ?>" />
									</div>
								<?php endif; ?>
							</div>
						</section>

						<section class="kmae-create-section" aria-labelledby="kmae-section-sale">
							<div class="kmae-create-section__head">
								<span class="kmae-create-section__number">03</span>
								<div>
									<h2 id="kmae-section-sale"><?php esc_html_e( 'Értékesítés módja', 'ki-mit-adna-erte' ); ?></h2>
									<p><?php esc_html_e( 'Hogyan szeretnéd értékesíteni az autót?', 'ki-mit-adna-erte' ); ?></p>
								</div>
							</div>
							<div class="kmae-create-section__body" id="kmae-section-sale-body">
								<fieldset class="kmae-fieldset kmae-price-mode">
									<legend><?php esc_html_e( 'Értékesítés módja *', 'ki-mit-adna-erte' ); ?></legend>
									<div class="kmae-price-mode__options">
										<?php foreach ( KMAE_Seller::price_mode_defs() as $mode_code => $mode_def ) : ?>
											<label class="kmae-price-mode__option<?php echo 'open' === $mode_code ? ' kmae-price-mode__option--accent' : ''; ?>">
												<input type="radio" name="kmae_price_mode" value="<?php echo esc_attr( $mode_code ); ?>" data-kmae-price-mode="<?php echo esc_attr( $mode_code ); ?>" <?php checked( $price_mode, $mode_code ); ?> required />
												<span class="kmae-price-mode__icon" aria-hidden="true">
													<?php echo KMAE_Icons::render( $mode_def['icon'], array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</span>
												<span class="kmae-price-mode__text"><strong><?php echo esc_html( $mode_def['label'] ); ?></strong><span class="kmae-price-mode__desc"><?php echo esc_html( $mode_def['desc'] ); ?></span></span>
												<?php if ( ! empty( $mode_def['features'] ) ) : ?>
													<ul class="kmae-price-mode__features">
														<?php foreach ( $mode_def['features'] as $feature ) : ?>
															<li>
																<?php echo KMAE_Icons::render( 'circle-check', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
																<span><?php echo esc_html( $feature ); ?></span>
															</li>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>
											</label>
										<?php endforeach; ?>
									</div>
									<div class="kmae-field kmae-price-amount-field" data-kmae-price-field="<?php echo esc_attr( KMAE_Seller::PRICE_FIXED ); ?>" style="<?php echo KMAE_Seller::PRICE_FIXED === $price_mode ? '' : 'display:none;'; ?>">
										<label for="kmae_fixed_price"><?php esc_html_e( 'Fix ár *', 'ki-mit-adna-erte' ); ?></label>
										<div class="kmae-wizard-unit">
											<input type="number" name="kmae_fixed_price" id="kmae_fixed_price" min="1" step="1000" value="<?php echo esc_attr( $fixed_price_val > 0 ? $fixed_price_val : '' ); ?>" placeholder="3 500 000" />
											<span class="kmae-wizard-unit__suffix" aria-hidden="true">Ft</span>
										</div>
										<p class="kmae-hint"><?php esc_html_e( 'A vevő ezt az árat látja, és pontosan ennyit ajánlhat meg.', 'ki-mit-adna-erte' ); ?></p>
									</div>
									<div class="kmae-field kmae-price-amount-field" data-kmae-price-field="<?php echo esc_attr( KMAE_Seller::PRICE_GUIDE ); ?>" style="<?php echo KMAE_Seller::PRICE_GUIDE === $price_mode ? '' : 'display:none;'; ?>">
										<label for="kmae_guide_price"><?php esc_html_e( 'Irányár *', 'ki-mit-adna-erte' ); ?></label>
										<div class="kmae-wizard-unit">
											<input type="number" name="kmae_guide_price" id="kmae_guide_price" min="1" step="1000" value="<?php echo esc_attr( $guide_price_val > 0 ? $guide_price_val : '' ); ?>" placeholder="3 200 000" />
											<span class="kmae-wizard-unit__suffix" aria-hidden="true">Ft</span>
										</div>
										<p class="kmae-hint"><?php esc_html_e( 'A vevő látja az irányárat, de ettől eltérő összeget is ajánlhat.', 'ki-mit-adna-erte' ); ?></p>
									</div>
									<div class="kmae-field kmae-price-amount-field" data-kmae-minimum-field style="<?php echo in_array( $price_mode, array( KMAE_Seller::PRICE_GUIDE, KMAE_Seller::PRICE_OPEN ), true ) ? '' : 'display:none;'; ?>">
										<div class="kmae-minimum-label">
											<label for="kmae_minimum_cash"><?php esc_html_e( 'Rejtett minimum (opcionális)', 'ki-mit-adna-erte' ); ?></label>
											<button type="button" class="kmae-info-dot" data-kmae-minimum-info aria-expanded="false" aria-controls="kmae-minimum-info"><?php esc_html_e( 'i', 'ki-mit-adna-erte' ); ?></button>
										</div>
										<div class="kmae-wizard-unit">
											<input type="number" name="kmae_minimum_cash" id="kmae_minimum_cash" min="0" step="1000" value="<?php echo esc_attr( $minimum_cash_val > 0 ? $minimum_cash_val : '' ); ?>" placeholder="2 800 000" />
											<span class="kmae-wizard-unit__suffix" aria-hidden="true">Ft</span>
										</div>
										<div class="kmae-minimum-info" id="kmae-minimum-info" role="dialog" aria-labelledby="kmae-minimum-info-title" hidden>
											<img class="kmae-minimum-info__mascot" src="https://albira.hu/wp-content/uploads/2026/10/info3.webp" alt="" width="64" height="64" decoding="async" />
											<p class="kmae-minimum-info__title" id="kmae-minimum-info-title"><?php esc_html_e( 'Mire való a rejtett minimum?', 'ki-mit-adna-erte' ); ?></p>
											<p><?php esc_html_e( 'Arra, hogy ne kapj olyan ajánlatot, amit válaszra sem méltatnál. Ez alatt tuti nem adod el. A rendszer kiszűri, és meg se kapod — se üzenetben, se e-mailben.', 'ki-mit-adna-erte' ); ?></p>
										</div>
									</div>
								</fieldset>
							</div>
						</section>

						<section class="kmae-create-section" aria-labelledby="kmae-section-trust">
							<div class="kmae-create-section__head">
								<span class="kmae-create-section__number">04</span>
								<div>
									<h2 id="kmae-section-trust"><?php echo $is_simple ? esc_html__( 'Extrák és további adatok', 'ki-mit-adna-erte' ) : esc_html__( 'Bizalom és eladói vállalások', 'ki-mit-adna-erte' ); ?></h2>
									<p><?php echo $is_simple ? esc_html__( 'Jelöld be, ha az autóhoz extra szolgáltatás tartozik, vagy további információt szeretnél megadni.', 'ki-mit-adna-erte' ) : esc_html__( 'Az opcionális vállalások segítenek kitűnni a hasonló hirdetések közül.', 'ki-mit-adna-erte' ); ?></p>
								</div>
							</div>
							<div class="kmae-create-section__body" id="kmae-section-trust-body">
								<fieldset class="kmae-fieldset kmae-commitments">
									<legend class="screen-reader-text"><?php esc_html_e( 'Eladói vállalások', 'ki-mit-adna-erte' ); ?></legend>
									<div class="kmae-create-option-grid">
										<?php foreach ( KMAE_Seller::commitment_defs() as $code => $info ) : ?>
											<label class="kmae-commitment-option">
												<input type="checkbox" name="kmae_commitments[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $commitments, true ) ); ?> />
												<span class="kmae-commitment-option__text"><strong><?php echo esc_html( $info['label'] ); ?></strong><span class="kmae-commitment-option__desc"><?php echo esc_html( $info['desc'] ); ?></span><?php if ( ! empty( $info['reward'] ) ) : ?><span class="kmae-commitment-option__reward"><?php echo esc_html( $info['reward'] ); ?></span><?php endif; ?></span>
											</label>
										<?php endforeach; ?>
										<?php foreach ( KMAE_Seller::extra_commitment_defs() as $code => $info ) : ?>
											<label class="kmae-commitment-option">
												<input type="checkbox" name="kmae_commitments[]" value="<?php echo esc_attr( $code ); ?>" data-kmae-commit="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $commitments, true ) ); ?> />
												<span class="kmae-commitment-option__text"><strong><?php echo esc_html( $info['label'] ); ?></strong><span class="kmae-commitment-option__desc"><?php echo esc_html( $info['desc'] ); ?></span></span>
											</label>
										<?php endforeach; ?>
									</div>
									<div class="kmae-field kmae-extra-other-field" style="<?php echo in_array( KMAE_Seller::COMMIT_OTHER, $commitments, true ) ? '' : 'display:none;'; ?>">
										<label for="kmae_commit_extra_other"><?php esc_html_e( 'Egyéb vállalás leírása *', 'ki-mit-adna-erte' ); ?></label>
										<input type="text" name="kmae_commit_extra_other" id="kmae_commit_extra_other" value="<?php echo esc_attr( $extra_other_val ); ?>" minlength="10" maxlength="80" placeholder="<?php esc_attr_e( 'Például: új akkumulátor jár hozzá', 'ki-mit-adna-erte' ); ?>" />
									</div>
								</fieldset>
							</div>
						</section>

						<?php if ( ! $is_simple ) : ?>
						<section class="kmae-create-section" aria-labelledby="kmae-section-photos">
							<div class="kmae-create-section__head">
								<span class="kmae-create-section__number">05</span>
								<div>
									<h2 id="kmae-section-photos"><?php esc_html_e( 'Autófotók', 'ki-mit-adna-erte' ); ?></h2>
									<p><?php esc_html_e( 'Töltsd fel a kötelező fotókat kamerából, galériából vagy a számítógépedről.', 'ki-mit-adna-erte' ); ?></p>
								</div>
							</div>
							<div class="kmae-create-section__body" id="kmae-section-photos-body">
								<?php if ( $is_edit && ! empty( $gallery_ids ) && ! KMAE_Photo_Motor::is_enabled() ) : ?>
									<?php $listing_id = $listing->ID; include KMAE_PLUGIN_DIR . 'templates/partials/listing-gallery-manager.php'; ?>
								<?php endif; ?>

								<?php if ( ! KMAE_Photo_Motor::is_enabled() ) : ?>
									<div class="kmae-field">
									<label for="kmae_images"><?php echo $is_edit ? esc_html__( 'Új képek (opcionális, maximum 12 összesen)', 'ki-mit-adna-erte' ) : esc_html__( 'Képek * (minimum 1, maximum 12)', 'ki-mit-adna-erte' ); ?></label>

									<?php /* A natív fájlválasztó gomb helyett saját feltöltő felület. A mezőt
										NEM rejtjük el (akkor a böngésző nem tudná kijelölni a kötelező mező
										hibáját), hanem átlátszóan ráterítjük a dobozra — így a kattintás és a
										ráhúzás is közvetlenül a mezőt éri. */ ?>
									<div class="kmae-uploader" id="kmae-uploader">
										<span class="kmae-uploader__icon" aria-hidden="true">
											<?php echo KMAE_Icons::render( 'camera', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<span class="kmae-uploader__text">
											<span class="kmae-uploader__title"><?php esc_html_e( 'Húzd ide a képeket, vagy tallózz', 'ki-mit-adna-erte' ); ?></span>
											<span class="kmae-uploader__hint"><?php esc_html_e( 'JPG, PNG vagy PDF (pl. előélet-lekérdezés) · legfeljebb 12 kép · többször is hozzáadhatsz', 'ki-mit-adna-erte' ); ?></span>
										</span>
										<span class="kmae-uploader__btn"><?php esc_html_e( 'Képek kiválasztása', 'ki-mit-adna-erte' ); ?></span>
										<input type="file" class="kmae-uploader__input" name="kmae_images[]" id="kmae_images" accept="image/*,application/pdf,.pdf" multiple <?php echo $is_edit ? '' : 'required'; ?> />
									</div>
									<p class="kmae-uploader__status" id="kmae-upload-status" role="status" hidden></p>

									<div class="kmae-upload-preview" id="kmae-upload-preview" aria-live="polite"></div>
									</div>
								<?php else : ?>
									<?php
									$gate_slots = ( $is_edit && $listing ) ? KMAE_Photo_Motor::get_slots( $listing->ID ) : array();
									$gate_req_fill = 0;
									$gate_all_fill = 0;
									foreach ( array_keys( KMAE_Photo_Motor::required_slots() ) as $gk ) {
										if ( ! empty( $gate_slots[ $gk ] ) ) { $gate_req_fill++; $gate_all_fill++; }
									}
									foreach ( array_keys( KMAE_Photo_Motor::recommended_slots() ) as $gk ) {
										if ( ! empty( $gate_slots[ $gk ] ) ) { $gate_all_fill++; }
									}
									$gate_ready = $gate_req_fill >= KMAE_Photo_Motor::required_count();
									?>
									<div class="kmae-photo-gate" id="kmae-photo-gate" data-ready="<?php echo $gate_ready ? '1' : '0'; ?>" data-awaiting="<?php echo $is_awaiting ? '1' : '0'; ?>">
										<div class="kmae-photo-gate__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'car', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
										<div class="kmae-photo-gate__summary">
											<strong><?php esc_html_e( 'Autófotók', 'ki-mit-adna-erte' ); ?></strong>
											<p class="kmae-hint" id="kmae-photo-gate-status"><?php printf( esc_html__( 'Kötelező: %1$d / %2$d · Összes: %3$d / %4$d', 'ki-mit-adna-erte' ), (int) $gate_req_fill, (int) KMAE_Photo_Motor::required_count(), (int) $gate_all_fill, (int) KMAE_Photo_Motor::recommended_total_count() ); ?></p>
											<p class="kmae-hint" id="kmae-photo-gate-hint"><?php esc_html_e( 'Telefonon és tableten kamerából vagy galériából, számítógépen fájlból választhatsz képet.', 'ki-mit-adna-erte' ); ?></p>
										</div>
									<button type="button" class="kmae-btn kmae-btn-primary" id="kmae-open-photo-step"><?php echo $gate_ready ? esc_html__( 'Fotók szerkesztése', 'ki-mit-adna-erte' ) : esc_html__( 'Fotók feltöltése', 'ki-mit-adna-erte' ); ?></button>
									</div>
								<?php endif; ?>
							</div>
						</section>
						<?php endif; ?>

						<?php // Unnumbered form actions; the guided wizard moves this body into its final screen. ?>
						<div<?php echo ( ! $is_edit && ! $draft_simple ) ? ' class="kmae-create-section"' : ''; ?>>
							<div class="kmae-create-section__body" id="kmae-section-finish-body">

								<div class="kmae-create-submit<?php echo $is_simple ? ' is-simple-bar' : ''; ?>">
									<?php if ( ! $is_simple ) : ?>
									<div>
										<strong><?php esc_html_e( 'Minden készen áll?', 'ki-mit-adna-erte' ); ?></strong>
										<p class="kmae-create-submit__hint" id="kmae-listing-submit-hint"><?php esc_html_e( 'Ellenőrizd a kötelező mezőket, majd mentsd a hirdetést.', 'ki-mit-adna-erte' ); ?></p>
									</div>
									<?php endif; ?>
									<div class="kmae-form-actions">
										<a href="<?php echo esc_url( $cancel_url ); ?>" class="kmae-btn kmae-btn-secondary"><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></a>
										<?php if ( $is_simple && $preview_url ) : ?>
											<a class="kmae-btn kmae-btn-secondary" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Előnézet', 'ki-mit-adna-erte' ); ?></a>
										<?php endif; ?>
										<button type="submit" class="kmae-btn kmae-btn-primary" id="kmae-listing-submit" <?php echo ( KMAE_Photo_Motor::is_enabled() && ! $is_edit ) ? 'disabled' : ''; ?>>
											<?php echo $is_edit ? esc_html__( 'Változtatások mentése', 'ki-mit-adna-erte' ) : esc_html__( 'Hirdetés mentése', 'ki-mit-adna-erte' ); ?>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php if ( KMAE_Photo_Motor::is_enabled() ) : ?>
						<?php include KMAE_PLUGIN_DIR . 'templates/partials/listing-photo-motor.php'; ?>
					<?php endif; ?>
				</form>

				<div class="kmae-lightbox kmae-form-lightbox" hidden aria-hidden="true">
					<div class="kmae-lightbox-backdrop" data-close="1"></div>
					<div class="kmae-lightbox-inner">
						<button type="button" class="kmae-lightbox-close" aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>">&times;</button>
						<img src="" alt="" class="kmae-lightbox-img" />
					</div>
				</div>
			<?php endif; ?>
		</div>
	</main>
</div>
