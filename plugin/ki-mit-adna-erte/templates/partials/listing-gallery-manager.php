<?php
/**
 * Edit form — gallery manager (cover, delete, preview). A sorrendet a rendszer rendezi.
 *
 * @package KMAE
 * @var int   $listing_id
 * @var int[] $gallery_ids
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $gallery_ids ) ) {
	return;
}
?>
<div class="kmae-field kmae-gallery-manager-wrap">
	<label><?php esc_html_e( 'Képek kezelése', 'ki-mit-adna-erte' ); ?></label>
	<p class="kmae-hint">
		<?php esc_html_e( 'Az első kép a borítókép, a többi kép sorrendjét a rendszer rendezi. A borítóképet a gombbal jelölheted ki. Kattints a képre a nagyításhoz.', 'ki-mit-adna-erte' ); ?>
	</p>

	<ul class="kmae-gallery-manager" id="kmae-gallery-manager">
		<?php foreach ( $gallery_ids as $i => $gid ) : ?>
			<?php
			$thumb = KMAE_Images::get_url( $gid, 'medium_large' );
			$full  = KMAE_Images::get_url( $gid, 'full' );
			if ( ! $thumb ) {
				continue;
			}
			?>
			<li class="kmae-gallery-manager-item" data-id="<?php echo esc_attr( $gid ); ?>">
				<input type="hidden" name="kmae_gallery_order[]" value="<?php echo esc_attr( $gid ); ?>" />
				<button type="button" class="kmae-gallery-manager-view kmae-zoomable" data-full="<?php echo esc_url( $full ?: $thumb ); ?>">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
				</button>
				<?php if ( 0 === $i ) : ?>
					<span class="kmae-gallery-manager-badge"><?php esc_html_e( 'Főkép', 'ki-mit-adna-erte' ); ?></span>
				<?php endif; ?>
				<div class="kmae-gallery-manager-actions">
					<button type="button" class="kmae-gallery-manager-cover"<?php echo 0 === $i ? ' disabled' : ''; ?>>
						<?php echo KMAE_Icons::render( 'star', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo 0 === $i ? esc_html__( 'Ez a borítókép', 'ki-mit-adna-erte' ) : esc_html__( 'Borítókép', 'ki-mit-adna-erte' ); ?></span>
					</button>
					<button type="button" class="kmae-gallery-manager-remove">
						<?php echo KMAE_Icons::render( 'trash', array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Törlés', 'ki-mit-adna-erte' ); ?></span>
					</button>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<div id="kmae-gallery-removed-inputs" hidden aria-hidden="true"></div>
</div>
