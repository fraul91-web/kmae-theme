<?php
/**
 * Vehicle form fields (create/edit).
 *
 * @package KMAE
 * @var bool   $is_edit
 * @var WP_Post|null $listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$specs = $is_edit && $listing ? KMAE_Vehicle::get_specs( $listing->ID ) : array();
$brand_val = $specs['brand'] ?? '';
$model_val = $specs['model'] ?? '';
$year_val  = $specs['year'] ?? '';
$km_val    = $specs['mileage'] ?? '';
$fuel_val  = $specs['fuel'] ?? '';
$trans_val = $specs['transmission'] ?? '';
$body_val  = $specs['body'] ?? '';
$color_val = $specs['color'] ?? '';
$power_val  = ! empty( $specs['power_kw'] ) ? KMAE_Vehicle::kw_to_horsepower( $specs['power_kw'] ) : '';
$cc_val     = $specs['engine_cc'] ?? '';
$cond_val   = $specs['condition'] ?? '';
$cond_note  = $specs['condition_note'] ?? '';

/* Műszaki érvényessége: "ÉÉÉÉ-HH" formában tárolt érték szétbontva. */
$mot_val       = $specs['mot'] ?? '';
$mot_has       = $is_edit ? ( '' !== $mot_val ? 'yes' : 'no' ) : '';
$mot_year_val  = '' !== $mot_val ? substr( $mot_val, 0, 4 ) : '';
$mot_month_val = '' !== $mot_val ? substr( $mot_val, 5, 2 ) : '';
$mot_day_val   = strlen( $mot_val ) >= 10 ? substr( $mot_val, 8, 2 ) : '';
/* Az aktuális évtől indul: ami korábban járt le, az már nem érvényes műszaki. */
$mot_years     = range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) + 6 );

/* Szerkesztésnél a korábban mentett év maradjon választható, hogy ne vesszen el. */
if ( '' !== $mot_year_val && ! in_array( (int) $mot_year_val, $mot_years, true ) ) {
	array_unshift( $mot_years, (int) $mot_year_val );
}
$mot_months    = array(
	'01' => __( 'Január', 'ki-mit-adna-erte' ),
	'02' => __( 'Február', 'ki-mit-adna-erte' ),
	'03' => __( 'Március', 'ki-mit-adna-erte' ),
	'04' => __( 'Április', 'ki-mit-adna-erte' ),
	'05' => __( 'Május', 'ki-mit-adna-erte' ),
	'06' => __( 'Június', 'ki-mit-adna-erte' ),
	'07' => __( 'Július', 'ki-mit-adna-erte' ),
	'08' => __( 'Augusztus', 'ki-mit-adna-erte' ),
	'09' => __( 'Szeptember', 'ki-mit-adna-erte' ),
	'10' => __( 'Október', 'ki-mit-adna-erte' ),
	'11' => __( 'November', 'ki-mit-adna-erte' ),
	'12' => __( 'December', 'ki-mit-adna-erte' ),
);
?>
<fieldset class="kmae-fieldset kmae-vehicle-fieldset">
	<legend><?php esc_html_e( 'Autó adatai', 'ki-mit-adna-erte' ); ?></legend>
	<p class="kmae-hint"><?php esc_html_e( 'A pontos adatok segítenek — így többen ajánlanak, és kevesebb felesleges kérdés érkezik.', 'ki-mit-adna-erte' ); ?></p>

	<?php if ( ! empty( $is_simple ) ) : ?>
	<div class="kmae-simple-board">
	<div class="kmae-simple-maincol">
	<?php endif; ?>
	<div class="kmae-simple-top">
	<div class="kmae-simple-specs">
	<div class="kmae-field-row kmae-simple-row kmae-simple-row--brand">
		<?php
		$brand_is_custom = ( '' !== $brand_val && ! KMAE_Vehicle::is_known_brand( $brand_val ) );
		$model_is_custom = ( '' !== $model_val && ( $brand_is_custom || ! KMAE_Vehicle::is_known_model( $brand_val, $model_val ) ) );
		?>
		<div class="kmae-field kmae-catalog-field kmae-simple-slot kmae-simple-slot--brand<?php echo $brand_is_custom ? ' is-custom' : ''; ?>" data-kmae-catalog="brand">
			<label for="kmae_vehicle_brand"><?php esc_html_e( 'Márka *', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" name="kmae_vehicle_brand" id="kmae_vehicle_brand" required value="<?php echo esc_attr( $brand_val ); ?>" placeholder="<?php esc_attr_e( 'Kezdd el beírni a márkát...', 'ki-mit-adna-erte' ); ?>" autocomplete="off" autocorrect="off" spellcheck="false" data-kmae-assist="brand" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="kmae-catalog-list-brand" />
			<button type="button" class="kmae-catalog-showall" data-kmae-catalog-showall <?php echo $brand_is_custom ? 'hidden' : ''; ?>><?php esc_html_e( 'Összes márka megtekintése', 'ki-mit-adna-erte' ); ?> &rarr;</button>
			<div class="kmae-catalog-panel kmae-catalog-panel--brand" id="kmae-catalog-panel-brand" hidden>
				<div class="kmae-catalog-head">
					<span class="kmae-catalog-head__title" data-kmae-catalog-title><?php esc_html_e( 'Népszerű márkák', 'ki-mit-adna-erte' ); ?></span>
				</div>
				<ul class="kmae-catalog-list kmae-catalog-list--logos" id="kmae-catalog-list-brand" role="listbox" aria-label="<?php esc_attr_e( 'Márkák', 'ki-mit-adna-erte' ); ?>"></ul>
				<p class="kmae-catalog-empty" hidden><?php esc_html_e( 'Nincs találat.', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-catalog-or" hidden><span><?php esc_html_e( 'vagy', 'ki-mit-adna-erte' ); ?></span></p>
				<button type="button" class="kmae-catalog-custom" hidden><?php esc_html_e( 'Nincs a listán — kézzel adom meg', 'ki-mit-adna-erte' ); ?></button>
			</div>
			<button type="button" class="kmae-catalog-tolist" <?php echo $brand_is_custom ? '' : 'hidden'; ?>><?php esc_html_e( 'Választás a listából', 'ki-mit-adna-erte' ); ?></button>
		</div>
		<div class="kmae-field kmae-catalog-field kmae-simple-slot kmae-simple-slot--model<?php echo $model_is_custom ? ' is-custom' : ''; ?>" data-kmae-catalog="model">
			<label for="kmae_vehicle_model"><?php esc_html_e( 'Modell *', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" name="kmae_vehicle_model" id="kmae_vehicle_model" required value="<?php echo esc_attr( $model_val ); ?>" placeholder="<?php esc_attr_e( 'Kezdd el beírni a modellt...', 'ki-mit-adna-erte' ); ?>" autocomplete="off" autocorrect="off" spellcheck="false" data-kmae-assist="model" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="kmae-catalog-list-model" />
			<button type="button" class="kmae-catalog-showall" data-kmae-catalog-showall <?php echo $model_is_custom ? 'hidden' : ''; ?>><?php esc_html_e( 'Összes modell megtekintése', 'ki-mit-adna-erte' ); ?> &rarr;</button>
			<div class="kmae-catalog-panel" id="kmae-catalog-panel-model" hidden>
				<ul class="kmae-catalog-list" id="kmae-catalog-list-model" role="listbox" aria-label="<?php esc_attr_e( 'Modellek', 'ki-mit-adna-erte' ); ?>"></ul>
				<p class="kmae-catalog-empty" hidden><?php esc_html_e( 'Nincs találat.', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-catalog-or" hidden><span><?php esc_html_e( 'vagy', 'ki-mit-adna-erte' ); ?></span></p>
				<button type="button" class="kmae-catalog-custom" hidden><?php esc_html_e( 'Nincs a listán — kézzel adom meg', 'ki-mit-adna-erte' ); ?></button>
			</div>
			<button type="button" class="kmae-catalog-tolist" <?php echo $model_is_custom ? '' : 'hidden'; ?>><?php esc_html_e( 'Választás a listából', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</div>

	<div class="kmae-field-row kmae-simple-row kmae-simple-row--year">
		<div class="kmae-field kmae-simple-slot kmae-simple-slot--year">
			<label for="kmae_vehicle_year"><?php esc_html_e( 'Évjárat *', 'ki-mit-adna-erte' ); ?></label>
			<?php if ( ! empty( $is_simple ) ) : ?><div class="kmae-year-field"><?php endif; ?>
			<input type="number" name="kmae_vehicle_year" id="kmae_vehicle_year" required min="1950" max="<?php echo esc_attr( (int) gmdate( 'Y' ) + 1 ); ?>" value="<?php echo esc_attr( $year_val ); ?>" placeholder="<?php esc_attr_e( 'pl. 2018', 'ki-mit-adna-erte' ); ?>" data-kmae-assist="year" />
			<?php if ( ! empty( $is_simple ) ) : ?>
				<button type="button" class="kmae-year-field__cal" data-kmae-year-open aria-expanded="false" aria-label="<?php esc_attr_e( 'Év választása', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'calendar', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<div class="kmae-year-panel" data-kmae-year-panel hidden>
					<div class="kmae-year-panel__bar">
						<button type="button" data-kmae-year-prev aria-label="<?php esc_attr_e( 'Korábbi évek', 'ki-mit-adna-erte' ); ?>">&lsaquo;</button>
						<span data-kmae-year-label></span>
						<button type="button" data-kmae-year-next aria-label="<?php esc_attr_e( 'Későbbi évek', 'ki-mit-adna-erte' ); ?>">&rsaquo;</button>
					</div>
					<div class="kmae-year-panel__grid" data-kmae-year-grid></div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div class="kmae-field kmae-simple-slot kmae-simple-slot--mileage">
			<label for="kmae_vehicle_mileage"><?php esc_html_e( 'Futásteljesítmény *', 'ki-mit-adna-erte' ); ?></label>
			<input type="number" name="kmae_vehicle_mileage" id="kmae_vehicle_mileage" required min="0" step="1" value="<?php echo esc_attr( $km_val ); ?>" placeholder="<?php esc_attr_e( 'pl. 145000', 'ki-mit-adna-erte' ); ?>" data-kmae-assist="mileage" />
		</div>
	</div>

	<div class="kmae-field-row kmae-simple-row kmae-simple-row--drive">
		<div class="kmae-field kmae-simple-slot kmae-simple-slot--fuel">
			<label for="kmae_vehicle_fuel"><?php esc_html_e( 'Üzemanyag *', 'ki-mit-adna-erte' ); ?></label>
			<select name="kmae_vehicle_fuel" id="kmae_vehicle_fuel" required data-kmae-assist="fuel">
				<option value=""><?php esc_html_e( 'Válassz...', 'ki-mit-adna-erte' ); ?></option>
				<?php foreach ( KMAE_Vehicle::fuel_labels() as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fuel_val, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="kmae-field kmae-simple-slot kmae-simple-slot--transmission">
			<label for="kmae_vehicle_transmission"><?php esc_html_e( 'Váltó *', 'ki-mit-adna-erte' ); ?></label>
			<select name="kmae_vehicle_transmission" id="kmae_vehicle_transmission" required data-kmae-assist="transmission">
				<option value=""><?php esc_html_e( 'Válassz...', 'ki-mit-adna-erte' ); ?></option>
				<?php foreach ( KMAE_Vehicle::transmission_labels() as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $trans_val, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php if ( ! empty( $is_simple ) ) : ?>
		<div class="kmae-simple-emblem" data-kmae-simple-emblem>
			<img data-kmae-simple-emblem-img <?php echo ! empty( $simple_logo ) ? 'src="' . esc_url( $simple_logo ) . '"' : 'hidden'; ?> alt="" width="72" height="72" />
			<span data-kmae-simple-emblem-name><?php echo esc_html( $simple_brand ); ?></span>
			<button type="button" class="kmae-simple-emblem__edit" data-kmae-simple-emblem-edit><?php esc_html_e( 'Módosítás', 'ki-mit-adna-erte' ); ?></button>
		</div>
	<?php endif; ?>
	</div>
	</div>
	<?php if ( ! empty( $is_simple ) ) : ?>
	</div>
	<?php endif; ?>

	<div class="kmae-field-row kmae-simple-bodies-row">
		<div class="kmae-field kmae-body-native">
			<label for="kmae_vehicle_body"><?php esc_html_e( 'Karosszéria típusa *', 'ki-mit-adna-erte' ); ?></label>
			<select name="kmae_vehicle_body" id="kmae_vehicle_body" required data-kmae-assist="body">
				<option value=""><?php esc_html_e( 'Válassz...', 'ki-mit-adna-erte' ); ?></option>
				<?php foreach ( KMAE_Vehicle::body_types() as $code => $info ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $body_val, $code ); ?>><?php echo esc_html( $info['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
			$simple_body_cards = array(
				'hatchback' => array( 'label' => __( 'Hatchback', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/hetcback3.webp' ),
				'coupe'     => array( 'label' => __( 'Coupé', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/coupe3.webp' ),
				'suv'       => array( 'label' => __( 'SUV', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/suv3.webp' ),
				'sedan'     => array( 'label' => __( 'Sedan', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/sedan3.webp' ),
				'estate'    => array( 'label' => __( 'Kombi', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/kombi3.webp' ),
				'cabrio'    => array( 'label' => __( 'Cabrio', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/cabrio3.webp' ),
				'mpv'       => array( 'label' => __( 'Egyterű', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/egyteru3.webp' ),
				'pickup'    => array( 'label' => __( 'Pickup', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/pickup3.webp' ),
				'van'       => array( 'label' => __( '3.5 t kisteher', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/35-teher3.webp' ),
				'other'     => array( 'label' => __( 'Egyéb', 'ki-mit-adna-erte' ), 'photo' => 'https://albira.hu/wp-content/uploads/2026/09/egyeb3.webp' ),
			);
			?>
			<div class="kmae-simple-bodies" role="listbox" aria-label="<?php esc_attr_e( 'Karosszéria típusa', 'ki-mit-adna-erte' ); ?>">
				<?php foreach ( $simple_body_cards as $code => $info ) : ?>
					<button type="button" class="kmae-simple-body<?php echo $body_val === $code ? ' is-selected' : ''; ?>" data-value="<?php echo esc_attr( $code ); ?>">
						<span class="kmae-simple-body__art"><img src="<?php echo esc_url( $info['photo'] ); ?>" alt="" width="84" height="40" /></span>
						<span><?php echo esc_html( $info['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php if ( ! empty( $is_simple ) ) : ?>
	<aside class="kmae-simple-photos" aria-label="<?php esc_attr_e( 'Fotók', 'ki-mit-adna-erte' ); ?>" data-kmae-photos>
		<div class="kmae-simple-photos__head">
			<strong><?php esc_html_e( 'Fotók', 'ki-mit-adna-erte' ); ?></strong>
			<span data-kmae-photos-count></span>
		</div>

		<?php /* Látható nézegető (listing-field-guards.js): nagy kép, nyilak, bélyegképsor.
			A forrás a rejtett kezelő alatta — az viszi a mentéshez a sorrendet, a borítót és a törlést. */ ?>
		<div class="kmae-simple-photos__stage" data-kmae-photos-stage>
			<img class="kmae-simple-photos__main" data-kmae-photos-main alt="" hidden />
			<p class="kmae-simple-photos__empty" data-kmae-photos-empty>
				<?php echo KMAE_Icons::render( 'image', array( 'size' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Még nincs fotó', 'ki-mit-adna-erte' ); ?></span>
			</p>
			<span class="kmae-simple-photos__badge" data-kmae-photos-badge hidden></span>
			<div class="kmae-simple-photos__tools" data-kmae-photos-tools hidden>
				<button type="button" class="kmae-simple-photos__tool" data-kmae-photos-cover title="<?php esc_attr_e( 'Legyen ez a borítókép', 'ki-mit-adna-erte' ); ?>" aria-label="<?php esc_attr_e( 'Legyen ez a borítókép', 'ki-mit-adna-erte' ); ?>">
					<?php echo KMAE_Icons::render( 'star', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button type="button" class="kmae-simple-photos__tool kmae-simple-photos__tool--remove" data-kmae-photos-remove title="<?php esc_attr_e( 'Fotó törlése', 'ki-mit-adna-erte' ); ?>" aria-label="<?php esc_attr_e( 'Fotó törlése', 'ki-mit-adna-erte' ); ?>">
					<?php echo KMAE_Icons::render( 'trash', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<button type="button" class="kmae-simple-photos__nav kmae-simple-photos__nav--prev" data-kmae-photos-prev aria-label="<?php esc_attr_e( 'Előző fotó', 'ki-mit-adna-erte' ); ?>" hidden>
				<?php echo KMAE_Icons::render( 'chevron-left', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button type="button" class="kmae-simple-photos__nav kmae-simple-photos__nav--next" data-kmae-photos-next aria-label="<?php esc_attr_e( 'Következő fotó', 'ki-mit-adna-erte' ); ?>" hidden>
				<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button type="button" class="kmae-simple-photos__open" data-kmae-photos-open hidden>
				<?php echo KMAE_Icons::render( 'image', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Fotók megtekintése', 'ki-mit-adna-erte' ); ?></span>
			</button>
		</div>

		<div class="kmae-simple-photos__thumbs" data-kmae-photos-thumbs hidden></div>

		<div class="kmae-simple-photos__source" data-kmae-photos-source>
			<?php
			/* Meglévő képek: KMAE_Listings::apply_gallery_edits() dolgozza fel (sorrend, törlés). */
			if ( ! empty( $gallery_ids ) && $listing ) {
				$listing_id = $listing->ID;
				include KMAE_PLUGIN_DIR . 'templates/partials/listing-gallery-manager.php';
			}
			?>
			<?php /* Új (még nem mentett) képek — photo-motor.js. */ ?>
			<div class="kmae-upload-preview" id="kmae-upload-preview"></div>
		</div>

		<?php if ( class_exists( 'KMAE_Photo_Motor' ) && KMAE_Photo_Motor::is_enabled() ) : ?>
			<button type="button" class="kmae-simple-drop" id="kmae-open-photo-step">
				<?php echo KMAE_Icons::render( 'camera', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="kmae-simple-drop__title"><?php esc_html_e( 'Fotók hozzáadása', 'ki-mit-adna-erte' ); ?></span>
			</button>
		<?php else : ?>
			<div class="kmae-uploader kmae-simple-drop" id="kmae-uploader">
				<?php echo KMAE_Icons::render( 'camera', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="kmae-simple-drop__title"><?php esc_html_e( 'Fotók hozzáadása', 'ki-mit-adna-erte' ); ?></span>
				<span class="kmae-simple-drop__hint"><?php esc_html_e( 'JPG, PNG, PDF · max. 12', 'ki-mit-adna-erte' ); ?></span>
				<input type="file" class="kmae-uploader__input" name="kmae_images[]" id="kmae_images" accept="image/*,application/pdf,.pdf" multiple <?php echo ! empty( $is_edit ) ? '' : 'required'; ?> />
			</div>
			<p class="kmae-uploader__status" id="kmae-upload-status" role="status" hidden></p>
		<?php endif; ?>
	</aside>
	</div>
	<?php endif; ?>

	<div class="kmae-field-row<?php echo ! empty( $is_simple ) ? ' kmae-simple-cond-row' : ''; ?>">
		<?php if ( ! empty( $is_simple ) ) : ?>
			<?php /* Egyszerű módban nincs színmező; a meglévő értéket megőrizzük, hogy a mentés ne törölje. */ ?>
			<input type="hidden" name="kmae_vehicle_color" value="<?php echo esc_attr( $color_val ); ?>" />
		<?php else : ?>
		<div class="kmae-field">
			<label for="kmae_vehicle_color"><?php esc_html_e( 'Szín', 'ki-mit-adna-erte' ); ?></label>
			<input type="text" name="kmae_vehicle_color" id="kmae_vehicle_color" value="<?php echo esc_attr( $color_val ); ?>" placeholder="<?php esc_attr_e( 'pl. Fekete metál', 'ki-mit-adna-erte' ); ?>" />
		</div>
		<?php endif; ?>
		<div class="kmae-field">
			<label for="kmae_condition"><?php esc_html_e( 'Állapot *', 'ki-mit-adna-erte' ); ?></label>
			<select name="kmae_condition" id="kmae_condition" required data-kmae-assist="condition">
				<option value=""><?php esc_html_e( 'Válassz...', 'ki-mit-adna-erte' ); ?></option>
				<?php foreach ( KMAE_Vehicle::condition_labels() as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cond_val, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php /* Csak a „javítandó" állapothoz tartozik: mit kell javítani.
				Alapból rejtett — a vezetett feladásnál a kártya kiválasztásakor
				jelenik meg a saját beviteli mezője. */ ?>
			<input type="text" name="kmae_condition_note" id="kmae_condition_note" maxlength="160" value="<?php echo esc_attr( $cond_note ); ?>" placeholder="<?php esc_attr_e( 'Például: kuplung, futómű', 'ki-mit-adna-erte' ); ?>" hidden />
		</div>
	</div>

	<?php
	/* Jellemzők: legfeljebb három kiemelés az autóról. Az űrlapon jelölőnégyzetek,
	   a vezetett feladásnál ebből épülnek a választható kártyák. */
	$trait_vals = $is_edit && $listing ? KMAE_Vehicle::get_traits( $listing->ID ) : array();
	?>
	<div class="kmae-field kmae-traits-field" id="kmae-vehicle-traits" data-kmae-max="<?php echo esc_attr( KMAE_Vehicle::MAX_TRAITS ); ?>">
		<span class="kmae-field__legend"><?php esc_html_e( 'Mi jellemzi leginkább az autót?', 'ki-mit-adna-erte' ); ?></span>
		<div class="kmae-traits-options">
			<?php foreach ( KMAE_Vehicle::traits() as $trait_code => $trait ) : ?>
				<label class="kmae-trait-option">
					<input type="checkbox" name="kmae_vehicle_traits[]" value="<?php echo esc_attr( $trait_code ); ?>" <?php checked( in_array( $trait_code, $trait_vals, true ) ); ?> />
					<span class="kmae-trait-option__icon" aria-hidden="true">
						<?php echo KMAE_Icons::render( $trait['icon'], array( 'size' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="kmae-trait-option__label"><?php echo esc_html( $trait['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<p class="kmae-hint"><?php printf( esc_html__( 'Legfeljebb %d választható — amelyik leginkább illik az autódhoz.', 'ki-mit-adna-erte' ), (int) KMAE_Vehicle::MAX_TRAITS ); ?></p>
	</div>

	<div class="kmae-field-row">
		<div class="kmae-field">
			<label for="kmae_vehicle_mot_has"><?php esc_html_e( 'Érvényes műszaki *', 'ki-mit-adna-erte' ); ?></label>
			<select name="kmae_vehicle_mot_has" id="kmae_vehicle_mot_has" required>
				<option value=""><?php esc_html_e( 'Válassz...', 'ki-mit-adna-erte' ); ?></option>
				<option value="yes" <?php selected( $mot_has, 'yes' ); ?>><?php esc_html_e( 'Igen', 'ki-mit-adna-erte' ); ?></option>
				<option value="no" <?php selected( $mot_has, 'no' ); ?>><?php esc_html_e( 'Nem', 'ki-mit-adna-erte' ); ?></option>
			</select>
		</div>
		<div class="kmae-field kmae-mot-date">
			<label for="kmae_vehicle_mot_year"><?php esc_html_e( 'Meddig érvényes? *', 'ki-mit-adna-erte' ); ?></label>
			<div class="kmae-mot-date__row">
				<select name="kmae_vehicle_mot_year" id="kmae_vehicle_mot_year" aria-label="<?php esc_attr_e( 'Év', 'ki-mit-adna-erte' ); ?>">
					<option value=""><?php esc_html_e( 'Év', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( $mot_years as $mot_year ) : ?>
						<option value="<?php echo esc_attr( $mot_year ); ?>" <?php selected( $mot_year_val, (string) $mot_year ); ?>><?php echo esc_html( $mot_year ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="kmae_vehicle_mot_month" id="kmae_vehicle_mot_month" aria-label="<?php esc_attr_e( 'Hónap', 'ki-mit-adna-erte' ); ?>">
					<option value=""><?php esc_html_e( 'Hónap', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( $mot_months as $mot_num => $mot_name ) : ?>
						<option value="<?php echo esc_attr( $mot_num ); ?>" <?php selected( $mot_month_val, (string) $mot_num ); ?>><?php echo esc_html( $mot_num . ' – ' . $mot_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php /* A nap opcionális: ha üresen marad, a hónap utolsó napját vesszük. */ ?>
				<select name="kmae_vehicle_mot_day" id="kmae_vehicle_mot_day" aria-label="<?php esc_attr_e( 'Nap', 'ki-mit-adna-erte' ); ?>">
					<option value=""><?php esc_html_e( 'Nap', 'ki-mit-adna-erte' ); ?></option>
					<?php for ( $mot_day = 1; $mot_day <= 31; $mot_day++ ) : ?>
						<option value="<?php echo esc_attr( $mot_day ); ?>" <?php selected( (int) $mot_day_val, $mot_day ); ?>><?php echo esc_html( $mot_day ); ?></option>
					<?php endfor; ?>
				</select>
			</div>
			<p class="kmae-hint"><?php esc_html_e( 'Válaszd ki, vagy kezdd el beírni az évet.', 'ki-mit-adna-erte' ); ?></p>
		</div>
	</div>

	<div class="kmae-field-row">
		<div class="kmae-field">
			<label for="kmae_vehicle_power_hp"><?php esc_html_e( 'Teljesítmény (LE) *', 'ki-mit-adna-erte' ); ?></label>
			<input type="number" name="kmae_vehicle_power_hp" id="kmae_vehicle_power_hp" required min="1" step="1" value="<?php echo esc_attr( $power_val ); ?>" placeholder="<?php esc_attr_e( 'pl. 116', 'ki-mit-adna-erte' ); ?>" />
		</div>
		<div class="kmae-field">
			<label for="kmae_vehicle_engine_cc"><?php esc_html_e( 'Hengerűrtartalom (cm³) *', 'ki-mit-adna-erte' ); ?></label>
			<input type="number" name="kmae_vehicle_engine_cc" id="kmae_vehicle_engine_cc" required min="1" step="1" value="<?php echo esc_attr( $cc_val ); ?>" placeholder="<?php esc_attr_e( 'pl. 1598', 'ki-mit-adna-erte' ); ?>" />
		</div>
	</div>
</fieldset>
