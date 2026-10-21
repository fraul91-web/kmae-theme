<?php
/**
 * Admin meta box view for listing settings.
 *
 * @package KMAE
 * @var WP_Post $post
 * @var string  $location
 * @var string  $condition
 * @var string  $status
 * @var array   $offer_types
 * @var object|null $private
 * @var array   $all_offer_types
 * @var array|WP_Error $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="form-table">
	<tr>
		<th><label for="kmae_status"><?php esc_html_e( 'Státusz', 'ki-mit-adna-erte' ); ?></label></th>
		<td>
			<select name="kmae_status" id="kmae_status">
				<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Aktív', 'ki-mit-adna-erte' ); ?></option>
				<option value="awaiting_photos" <?php selected( $status, 'awaiting_photos' ); ?>><?php esc_html_e( 'Fotó feltöltésre vár', 'ki-mit-adna-erte' ); ?></option>
				<option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Lezárva', 'ki-mit-adna-erte' ); ?></option>
				<option value="archived" <?php selected( $status, 'archived' ); ?>><?php esc_html_e( 'Archivált', 'ki-mit-adna-erte' ); ?></option>
				<option value="expired" <?php selected( $status, 'expired' ); ?>><?php esc_html_e( 'Lejárt', 'ki-mit-adna-erte' ); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th><label for="kmae_county"><?php esc_html_e( 'Megye', 'ki-mit-adna-erte' ); ?></label></th>
		<td>
			<select name="kmae_county" id="kmae_county">
				<option value=""><?php esc_html_e( '—', 'ki-mit-adna-erte' ); ?></option>
				<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $county ?? '', $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><label for="kmae_city"><?php esc_html_e( 'Város', 'ki-mit-adna-erte' ); ?></label></th>
		<td><input type="text" name="kmae_city" id="kmae_city" value="<?php echo esc_attr( $city ?? '' ); ?>" class="regular-text" /></td>
	</tr>
	<tr>
		<th><label for="kmae_location"><?php esc_html_e( 'Helyszín (megjelenítés)', 'ki-mit-adna-erte' ); ?></label></th>
		<td><input type="text" name="kmae_location" id="kmae_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" readonly /></td>
	</tr>
		<tr>
		<th><label for="kmae_condition"><?php esc_html_e( 'Állapot', 'ki-mit-adna-erte' ); ?></label></th>
		<td>
			<?php if ( KMAE_Vehicle::is_vehicle_launch() ) : ?>
				<select name="kmae_condition" id="kmae_condition">
					<option value=""><?php esc_html_e( '—', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::condition_labels() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $condition, $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="text" name="kmae_condition" id="kmae_condition" value="<?php echo esc_attr( $condition ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'pl. Újszerű', 'ki-mit-adna-erte' ); ?>" />
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Ajánlattípus', 'ki-mit-adna-erte' ); ?></th>
		<td><?php esc_html_e( 'Készpénz', 'ki-mit-adna-erte' ); ?></td>
	</tr>
	<tr class="kmae-minimum-row">
		<th><label for="kmae_minimum_cash"><?php esc_html_e( 'Rejtett minimum (készpénz)', 'ki-mit-adna-erte' ); ?></label></th>
		<td>
			<input type="number" name="kmae_minimum_cash" id="kmae_minimum_cash" value="<?php echo $private ? esc_attr( $private->minimum_cash ) : ''; ?>" min="0" step="1" class="regular-text" />
			<p class="description"><?php esc_html_e( 'Csak te látod. Alacsonyabb ajánlatok automatikusan kiesnek — a vevő nem tud róla.', 'ki-mit-adna-erte' ); ?></p>
		</td>
	</tr>
</table>

<?php if ( KMAE_Vehicle::is_vehicle_launch() ) : ?>
	<?php
	$specs = KMAE_Vehicle::get_specs( $post->ID );
	?>
	<h4><?php esc_html_e( 'Autó adatok', 'ki-mit-adna-erte' ); ?></h4>
	<table class="form-table">
		<tr>
			<th><label for="kmae_vehicle_brand"><?php esc_html_e( 'Márka', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="text" name="kmae_vehicle_brand" id="kmae_vehicle_brand" value="<?php echo esc_attr( $specs['brand'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_model"><?php esc_html_e( 'Modell', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="text" name="kmae_vehicle_model" id="kmae_vehicle_model" value="<?php echo esc_attr( $specs['model'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_year"><?php esc_html_e( 'Évjárat', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="number" name="kmae_vehicle_year" id="kmae_vehicle_year" value="<?php echo esc_attr( $specs['year'] ); ?>" class="small-text" /></td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_mileage"><?php esc_html_e( 'Km óra', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="number" name="kmae_vehicle_mileage" id="kmae_vehicle_mileage" value="<?php echo esc_attr( $specs['mileage'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_fuel"><?php esc_html_e( 'Üzemanyag', 'ki-mit-adna-erte' ); ?></label></th>
			<td>
				<select name="kmae_vehicle_fuel" id="kmae_vehicle_fuel">
					<option value=""><?php esc_html_e( '—', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::fuel_labels() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $specs['fuel'], $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_transmission"><?php esc_html_e( 'Váltó', 'ki-mit-adna-erte' ); ?></label></th>
			<td>
				<select name="kmae_vehicle_transmission" id="kmae_vehicle_transmission">
					<option value=""><?php esc_html_e( '—', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::transmission_labels() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $specs['transmission'], $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_body"><?php esc_html_e( 'Kivitel', 'ki-mit-adna-erte' ); ?></label></th>
			<td>
				<select name="kmae_vehicle_body" id="kmae_vehicle_body">
					<option value=""><?php esc_html_e( '—', 'ki-mit-adna-erte' ); ?></option>
					<?php foreach ( KMAE_Vehicle::body_labels() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $specs['body'], $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_owners"><?php esc_html_e( 'Tulajdonosok', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="number" name="kmae_vehicle_owners" id="kmae_vehicle_owners" value="<?php echo esc_attr( $specs['owners'] ); ?>" class="small-text" min="1" /></td>
		</tr>
		<tr>
			<th><label for="kmae_vehicle_color"><?php esc_html_e( 'Szín', 'ki-mit-adna-erte' ); ?></label></th>
			<td><input type="text" name="kmae_vehicle_color" id="kmae_vehicle_color" value="<?php echo esc_attr( $specs['color'] ); ?>" class="regular-text" /></td>
		</tr>
	</table>
<?php endif; ?>
