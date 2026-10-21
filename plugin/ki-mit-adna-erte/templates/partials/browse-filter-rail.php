<?php
/**
 * Left search rail on the vehicle browse results.
 *
 * @package KMAE
 * @var array  $wizard_summary
 * @var string $browse_base_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wizard = is_array( $wizard_summary ?? null ) ? $wizard_summary : array();
$county_raw = (string) ( $wizard['county'] ?? '' );
$county_slugs = class_exists( 'KMAE_Browse' ) ? KMAE_Browse::county_slugs( $county_raw ) : array();
$county_any = empty( $county_slugs );
$bodies = isset( $wizard['bodies'] ) && is_array( $wizard['bodies'] ) ? $wizard['bodies'] : array();
$body_value = in_array( 'mind', $bodies, true ) || empty( $bodies ) ? '' : (string) reset( $bodies );
$prices = isset( $wizard['prices'] ) && is_array( $wizard['prices'] ) ? $wizard['prices'] : array();
if ( ! $prices ) {
	if ( KMAE_Vehicle::BROWSE_AJANLAT === ( $wizard['mod'] ?? '' ) ) {
		$prices = array( KMAE_Seller::PRICE_OPEN );
	} elseif ( KMAE_Vehicle::BROWSE_KP === ( $wizard['mod'] ?? '' ) ) {
		$prices = array( KMAE_Seller::PRICE_FIXED, KMAE_Seller::PRICE_GUIDE );
	}
}
$price_any = empty( $prices );
$conditions = isset( $wizard['conditions'] ) && is_array( $wizard['conditions'] ) ? $wizard['conditions'] : array();
$condition_any = empty( $conditions );
$mot_mode = (string) ( $wizard['mot'] ?? '' );
$mot_any = '' === $mot_mode || 'mind' === $mot_mode;
$mot_min = (int) ( $wizard['mot_min'] ?? 3 );
$mot_max = (int) ( $wizard['mot_max'] ?? 24 );
if ( $mot_min <= 0 ) {
	$mot_min = 3;
}
if ( $mot_max <= 0 ) {
	$mot_max = 24;
}
$cc_min = (int) ( $wizard['cc_min'] ?? 0 );
$cc_max = (int) ( $wizard['cc_max'] ?? 0 );
$hp_min = (int) ( $wizard['hp_min'] ?? 0 );
$hp_max = (int) ( $wizard['hp_max'] ?? 0 );
$year_now = (int) gmdate( 'Y' ) + 1;
$blocked  = isset( $wizard['blocked'] ) && is_array( $wizard['blocked'] ) ? $wizard['blocked'] : array();
?>
<button
	type="button"
	class="kmae-filter-rail__toggle"
	id="kmae-filter-toggle"
	title="<?php esc_attr_e( 'Részletes keresés', 'ki-mit-adna-erte' ); ?>"
	aria-label="<?php esc_attr_e( 'Részletes keresés', 'ki-mit-adna-erte' ); ?>"
	aria-expanded="false"
	aria-controls="kmae-filter-rail"
>
	<span class="kmae-filter-rail__toggle-word" aria-hidden="true">
		<span>K</span><span>E</span><span>R</span><span>E</span><span>S</span><span>É</span><span>S</span>
	</span>
	<svg class="kmae-filter-rail__toggle-arrow" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
		<path d="M9 5.5 15.5 12 9 18.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
	</svg>
</button>
<div class="kmae-filter-rail is-shut" id="kmae-filter-rail" data-browse-url="<?php echo esc_url( $browse_base_url ); ?>">
	<div class="kmae-filter-rail__backdrop" data-kmae-filter-close></div>
	<form class="kmae-filter-rail__panel" id="kmae-filter-form" action="<?php echo esc_url( $browse_base_url ); ?>" method="get">
		<div class="kmae-filter-rail__head">
			<strong><?php esc_html_e( 'Részletes keresés', 'ki-mit-adna-erte' ); ?></strong>
			<button type="button" class="kmae-filter-rail__close" data-kmae-filter-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>">×</button>
		</div>
		<div class="kmae-filter-rail__body">
			<label class="kmae-filter-field">
				<span><?php esc_html_e( 'Márka', 'ki-mit-adna-erte' ); ?></span>
				<select name="marka" id="kmae-filter-brand">
					<option value=""><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::brands() as $brand ) : ?>
						<option value="<?php echo esc_attr( $brand ); ?>" <?php selected( $wizard['brand'] ?? '', $brand ); ?>><?php echo esc_html( $brand ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="kmae-filter-field">
				<span><?php esc_html_e( 'Modell', 'ki-mit-adna-erte' ); ?></span>
				<select name="modell" id="kmae-filter-model" data-current="<?php echo esc_attr( $wizard['model'] ?? '' ); ?>" <?php disabled( empty( $wizard['brand'] ) ); ?>>
					<option value=""><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></option>
				</select>
			</label>

			<fieldset class="kmae-filter-field kmae-filter-range" data-unit="cm³" data-min="0" data-max="6000" data-step="50">
				<legend><?php esc_html_e( 'Motor térfogat', 'ki-mit-adna-erte' ); ?></legend>
				<div class="kmae-filter-range__nums">
					<input type="text" inputmode="numeric" name="ccm_min" value="<?php echo $cc_min > 0 ? esc_attr( (string) $cc_min ) : ''; ?>" placeholder="<?php esc_attr_e( 'ettől', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
					<input type="text" inputmode="numeric" name="ccm_max" value="<?php echo $cc_max > 0 ? esc_attr( (string) $cc_max ) : ''; ?>" placeholder="<?php esc_attr_e( 'eddig', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
				</div>
				<div class="kmae-filter-range__sliders">
					<input type="range" min="0" max="6000" step="50" value="<?php echo esc_attr( (string) ( $cc_min > 0 ? $cc_min : 0 ) ); ?>" data-bound="min" aria-label="<?php esc_attr_e( 'Motor térfogat ettől', 'ki-mit-adna-erte' ); ?>">
					<input type="range" min="0" max="6000" step="50" value="<?php echo esc_attr( (string) ( $cc_max > 0 ? $cc_max : 6000 ) ); ?>" data-bound="max" aria-label="<?php esc_attr_e( 'Motor térfogat eddig', 'ki-mit-adna-erte' ); ?>">
				</div>
				<p class="kmae-filter-range__readout" data-empty="<?php esc_attr_e( 'Mindegy', 'ki-mit-adna-erte' ); ?>"></p>
			</fieldset>

			<fieldset class="kmae-filter-field kmae-filter-range" data-unit="LE" data-min="0" data-max="800" data-step="5">
				<legend><?php esc_html_e( 'Lóerő', 'ki-mit-adna-erte' ); ?></legend>
				<div class="kmae-filter-range__nums">
					<input type="text" inputmode="numeric" name="le_min" value="<?php echo $hp_min > 0 ? esc_attr( (string) $hp_min ) : ''; ?>" placeholder="<?php esc_attr_e( 'ettől', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
					<input type="text" inputmode="numeric" name="le_max" value="<?php echo $hp_max > 0 ? esc_attr( (string) $hp_max ) : ''; ?>" placeholder="<?php esc_attr_e( 'eddig', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
				</div>
				<div class="kmae-filter-range__sliders">
					<input type="range" min="0" max="800" step="5" value="<?php echo esc_attr( (string) ( $hp_min > 0 ? $hp_min : 0 ) ); ?>" data-bound="min" aria-label="<?php esc_attr_e( 'Lóerő ettől', 'ki-mit-adna-erte' ); ?>">
					<input type="range" min="0" max="800" step="5" value="<?php echo esc_attr( (string) ( $hp_max > 0 ? $hp_max : 800 ) ); ?>" data-bound="max" aria-label="<?php esc_attr_e( 'Lóerő eddig', 'ki-mit-adna-erte' ); ?>">
				</div>
				<p class="kmae-filter-range__readout" data-empty="<?php esc_attr_e( 'Mindegy', 'ki-mit-adna-erte' ); ?>"></p>
			</fieldset>

			<fieldset class="kmae-filter-field">
				<legend><?php esc_html_e( 'Évjárat', 'ki-mit-adna-erte' ); ?></legend>
				<div class="kmae-filter-range__nums">
					<input type="text" inputmode="numeric" name="ev_min" maxlength="4" value="<?php echo ! empty( $wizard['year_min'] ) ? esc_attr( (string) $wizard['year_min'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'ettől', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
					<input type="text" inputmode="numeric" name="ev_max" maxlength="4" value="<?php echo ! empty( $wizard['year_max'] ) ? esc_attr( (string) $wizard['year_max'] ) : ''; ?>" placeholder="<?php echo esc_attr( (string) $year_now ); ?>" autocomplete="off">
				</div>
			</fieldset>

			<fieldset class="kmae-filter-field">
				<legend><?php esc_html_e( 'Km állás', 'ki-mit-adna-erte' ); ?></legend>
				<div class="kmae-filter-range__nums">
					<input type="text" inputmode="numeric" name="km_min" value="<?php echo ! empty( $wizard['km_min'] ) ? esc_attr( (string) $wizard['km_min'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'ettől', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
					<input type="text" inputmode="numeric" name="km_max" value="<?php echo ! empty( $wizard['km_max'] ) ? esc_attr( (string) $wizard['km_max'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'eddig', 'ki-mit-adna-erte' ); ?>" autocomplete="off">
				</div>
			</fieldset>

			<label class="kmae-filter-field">
				<span><?php esc_html_e( 'Üzemanyag', 'ki-mit-adna-erte' ); ?></span>
				<select name="uzem">
					<option value=""><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::fuel_labels() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $wizard['fuel'] ?? '', $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="kmae-filter-field">
				<span><?php esc_html_e( 'Váltó', 'ki-mit-adna-erte' ); ?></span>
				<select name="valto">
					<option value=""><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::transmission_labels() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $wizard['gear'] ?? '', $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="kmae-filter-field">
				<span><?php esc_html_e( 'Kivitel', 'ki-mit-adna-erte' ); ?></span>
				<select name="kivitel">
					<option value="mind" <?php selected( $body_value, '' ); ?>><?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::body_types() as $slug => $info ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $body_value, $slug ); ?>><?php echo esc_html( $info['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<fieldset class="kmae-filter-field kmae-filter-mot">
				<legend><?php esc_html_e( 'Műszaki — érvényes még legalább', 'ki-mit-adna-erte' ); ?></legend>
				<label class="kmae-filter-check"><input type="checkbox" name="muszaki_mind" value="1" <?php checked( $mot_any ); ?> data-mot-any> <?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></label>
				<label class="kmae-filter-check"><input type="checkbox" name="mot_lejart" value="1" <?php checked( ! empty( $wizard['mot_expired'] ) ); ?>> <?php esc_html_e( 'Lejárt műszaki is jó', 'ki-mit-adna-erte' ); ?></label>
				<div class="kmae-filter-range" data-unit="" data-min="0" data-max="60" data-step="1" data-months="1">
					<div class="kmae-filter-range__nums">
						<input type="text" inputmode="numeric" name="mot_min" value="<?php echo esc_attr( (string) $mot_min ); ?>" <?php disabled( $mot_any ); ?> autocomplete="off" aria-label="<?php esc_attr_e( 'Minimum hónap', 'ki-mit-adna-erte' ); ?>">
						<input type="text" inputmode="numeric" name="mot_max" value="<?php echo esc_attr( (string) $mot_max ); ?>" <?php disabled( $mot_any ); ?> autocomplete="off" aria-label="<?php esc_attr_e( 'Maximum hónap', 'ki-mit-adna-erte' ); ?>">
					</div>
					<div class="kmae-filter-range__sliders">
						<input type="range" min="0" max="60" step="1" value="<?php echo esc_attr( (string) $mot_min ); ?>" data-bound="min" <?php disabled( $mot_any ); ?> aria-label="<?php esc_attr_e( 'Műszaki ettől', 'ki-mit-adna-erte' ); ?>">
						<input type="range" min="0" max="60" step="1" value="<?php echo esc_attr( (string) $mot_max ); ?>" data-bound="max" <?php disabled( $mot_any ); ?> aria-label="<?php esc_attr_e( 'Műszaki eddig', 'ki-mit-adna-erte' ); ?>">
					</div>
					<p class="kmae-filter-range__readout"></p>
				</div>
			</fieldset>

			<fieldset class="kmae-filter-field">
				<legend><?php esc_html_e( 'Milyen állapotban van', 'ki-mit-adna-erte' ); ?></legend>
				<label class="kmae-filter-check"><input type="checkbox" name="allapot_mind" value="1" <?php checked( $condition_any ); ?> data-group-any="allapot"> <?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></label>
				<?php foreach ( KMAE_Vehicle::condition_labels() as $slug => $label ) : ?>
					<label class="kmae-filter-check"><input type="checkbox" name="allapot[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $conditions, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</fieldset>

			<fieldset class="kmae-filter-field">
				<legend><?php esc_html_e( 'Terület', 'ki-mit-adna-erte' ); ?></legend>
				<label class="kmae-filter-check"><input type="checkbox" name="megye_mind" value="1" <?php checked( $county_any ); ?> data-group-any="megye"> <?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></label>
				<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
					<label class="kmae-filter-check"><input type="checkbox" name="megye[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $county_slugs, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</fieldset>

			<fieldset class="kmae-filter-field">
				<legend><?php esc_html_e( 'Ár', 'ki-mit-adna-erte' ); ?></legend>
				<label class="kmae-filter-check"><input type="checkbox" name="ar_mind" value="1" <?php checked( $price_any ); ?> data-group-any="ar"> <?php esc_html_e( 'Mindegy', 'ki-mit-adna-erte' ); ?></label>
				<?php
				$price_order = array( KMAE_Seller::PRICE_GUIDE, KMAE_Seller::PRICE_FIXED, KMAE_Seller::PRICE_OPEN );
				$price_defs  = KMAE_Seller::price_mode_defs();
				foreach ( $price_order as $slug ) :
					if ( empty( $price_defs[ $slug ] ) ) {
						continue;
					}
					?>
					<label class="kmae-filter-check"><input type="checkbox" name="ar[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $prices, true ) ); ?>> <?php echo esc_html( $price_defs[ $slug ]['label'] ); ?></label>
				<?php endforeach; ?>
			</fieldset>
			<?php /* Tiltó lista: a Részletes keresés legalján; hozzáadás/törlés azonnal újratölt (browse-wizard.js applyBlocks). */ ?>
			<fieldset class="kmae-filter-field kmae-filter-block kmae-block-panel" id="kmae-filter-block">
				<legend>
					<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2.6"/><path d="M5.7 5.7 18.3 18.3" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
					<?php esc_html_e( 'Tiltó lista', 'ki-mit-adna-erte' ); ?>
				</legend>
				<p class="kmae-filter-block__hint"><?php esc_html_e( 'Ezt a márkát vagy modellt soha nem mutatjuk a találatok között.', 'ki-mit-adna-erte' ); ?></p>
				<ul class="kmae-filter-block__list" id="kmae-block-list">
					<?php foreach ( $blocked as $block ) : ?>
						<?php
						$block_value = $block['brand'] . ( '' !== $block['model'] ? '|' . $block['model'] : '' );
						$block_label = $block['brand'] . ( '' !== $block['model'] ? ' ' . $block['model'] : '' );
						?>
						<li class="kmae-filter-block__item" data-brand="<?php echo esc_attr( $block['brand'] ); ?>" data-model="<?php echo esc_attr( $block['model'] ); ?>" data-value="<?php echo esc_attr( $block_value ); ?>">
							<span><?php echo esc_html( $block_label ); ?></span>
							<button type="button" data-kmae-block-remove aria-label="<?php echo esc_attr( sprintf( /* translators: %s: brand and model */ __( '%s tiltásának törlése', 'ki-mit-adna-erte' ), $block_label ) ); ?>">×</button>
						</li>
					<?php endforeach; ?>
				</ul>
				<select id="kmae-block-brand" aria-label="<?php esc_attr_e( 'Tiltott márka', 'ki-mit-adna-erte' ); ?>">
					<option value=""><?php echo $blocked ? esc_html__( '+ Még egy márka tiltása…', 'ki-mit-adna-erte' ) : esc_html__( 'Tiltott márka kiválasztása…', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::brands() as $brand ) : ?>
						<option value="<?php echo esc_attr( $brand ); ?>"><?php echo esc_html( $brand ); ?></option>
					<?php endforeach; ?>
				</select>
				<select id="kmae-block-model" aria-label="<?php esc_attr_e( 'Tiltott modell', 'ki-mit-adna-erte' ); ?>" hidden>
					<option value=""><?php esc_html_e( 'Minden modell tiltva', 'ki-mit-adna-erte' ); ?></option>
				</select>
				<button type="button" class="kmae-filter-block__add" id="kmae-block-add" hidden><?php esc_html_e( 'Tiltás hozzáadása', 'ki-mit-adna-erte' ); ?></button>
			</fieldset>
		</div>
		<div class="kmae-filter-rail__foot">
			<button type="submit" class="kmae-filter-rail__go"><?php esc_html_e( 'Új keresés indítása', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</form>
</div>
