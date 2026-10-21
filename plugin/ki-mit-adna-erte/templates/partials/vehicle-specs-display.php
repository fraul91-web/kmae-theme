<?php
/**
 * Vehicle specs display grid.
 *
 * @package KMAE
 * @var int $listing_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rows  = KMAE_Vehicle::get_display_rows( $listing_id );
$specs = KMAE_Vehicle::get_specs( $listing_id );
if ( empty( $rows ) ) {
	return;
}
?>
<?php
/* A jellemzőkhöz ikon is tartozik, ezért azt a sort nem sima szövegként,
   hanem ikonos elemekként rajzoljuk ki. */
$trait_defs  = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::traits() : array();
$trait_codes = class_exists( 'KMAE_Vehicle' ) ? KMAE_Vehicle::get_traits( $listing_id ) : array();
?>
<div class="kmae-vehicle-specs">
	<?php foreach ( $rows as $row ) : ?>
		<?php
		$spec_key = $row['key'] ?? '';
		if ( 'traits' === $spec_key ) {
			continue;
		}
		?>
		<div class="kmae-vehicle-spec" data-kmae-spec="<?php echo esc_attr( $spec_key ); ?>">
			<?php
			$spec_code = isset( $specs[ $spec_key ] ) ? (string) $specs[ $spec_key ] : '';
			$spec_icon = KMAE_Vehicle_Icons::render( $spec_key, $spec_code, 18 );
			?>
			<?php if ( $spec_icon ) : ?>
				<span class="kmae-vehicle-spec-icon" aria-hidden="true"><?php echo $spec_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
			<span class="kmae-vehicle-spec-label"><?php echo esc_html( $row['label'] ); ?></span>
			<?php if ( 'traits' === $spec_key && $trait_codes ) : ?>
				<div class="kmae-vehicle-traits">
					<?php foreach ( $trait_codes as $trait_code ) : ?>
						<?php if ( empty( $trait_defs[ $trait_code ] ) ) { continue; } ?>
						<span class="kmae-vehicle-trait">
							<span class="kmae-vehicle-trait__icon" aria-hidden="true">
								<?php echo KMAE_Icons::render( $trait_defs[ $trait_code ]['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="kmae-vehicle-trait__label"><?php echo esc_html( $trait_defs[ $trait_code ]['label'] ); ?></span>
						</span>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<span class="kmae-vehicle-spec-value"><?php echo esc_html( $row['value'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
