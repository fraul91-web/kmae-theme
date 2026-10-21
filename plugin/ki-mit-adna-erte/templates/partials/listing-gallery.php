<?php
/**
 * Listing image gallery.
 *
 * @package KMAE
 * @var int         $listing_id
 * @var string|null $gallery_class Extra CSS class(es), e.g. kmae-gallery--card.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gallery_ids = KMAE_Query::get_gallery_ids( $listing_id );
if ( empty( $gallery_ids ) ) {
	return;
}

$lightbox_id = 'kmae-lightbox-' . $listing_id;
$extra_class = isset( $gallery_class ) ? trim( (string) $gallery_class ) : '';
$full_urls   = array();

foreach ( $gallery_ids as $aid ) {
	$url = KMAE_Images::get_url( $aid, 'full' );
	if ( $url ) {
		$full_urls[] = $url;
	}
}
?>
<div
	class="kmae-gallery<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>"
	data-full-urls="<?php echo esc_attr( wp_json_encode( $full_urls ) ); ?>"
>
	<?php if ( count( $gallery_ids ) > 1 ) : ?>
		<button type="button" class="kmae-gallery-nav kmae-gallery-prev" aria-label="<?php esc_attr_e( 'Előző kép', 'ki-mit-adna-erte' ); ?>">&#8249;</button>
		<button type="button" class="kmae-gallery-nav kmae-gallery-next" aria-label="<?php esc_attr_e( 'Következő kép', 'ki-mit-adna-erte' ); ?>">&#8250;</button>
	<?php endif; ?>

	<div class="kmae-gallery-stage">
		<?php foreach ( $gallery_ids as $i => $aid ) : ?>
			<?php
			$full_url = KMAE_Images::get_url( $aid, 'full' );
			if ( ! $full_url ) {
				continue;
			}
			?>
			<?php /* Nincs külön nagyítás: a dia csak kép, nem kattintható gomb.
				A kép a saját, elmosott mására kerül háttérként — így teljes egészében
				látszik (semmi nincs levágva), a széleken mégsem marad üres sáv. */ ?>
			<?php
			/* A böngésző a doboz tényleges szélességéhez válasszon fájlt — a
			   galéria a panel 70%-a, legfeljebb 1100 képpont. */
			$responsive = KMAE_Images::responsive_attrs( $aid, '(max-width: 1100px) 70vw, 1100px' );
			/* A fotó mérete: PC-n a public.js ebből rendezi a képeket a böngésző (swipe-v2.js) sorrendjére. */
			$slide_meta = wp_get_attachment_metadata( $aid );
			?>
			<div
				class="kmae-gallery-slide <?php echo 0 === $i ? 'active' : ''; ?>"
				data-index="<?php echo esc_attr( $i ); ?>"
				data-w="<?php echo esc_attr( ! empty( $slide_meta['width'] ) ? (int) $slide_meta['width'] : 0 ); ?>"
				data-h="<?php echo esc_attr( ! empty( $slide_meta['height'] ) ? (int) $slide_meta['height'] : 0 ); ?>"
				style="--kmae-slide-bg: url('<?php echo esc_url( $full_url ); ?>');"
			>
				<img
					src="<?php echo esc_url( $full_url ); ?>"
					<?php if ( $responsive['srcset'] ) : ?>
						srcset="<?php echo esc_attr( $responsive['srcset'] ); ?>"
						sizes="<?php echo esc_attr( $responsive['sizes'] ); ?>"
					<?php endif; ?>
					alt=""
					class="kmae-gallery-img"
					loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>"
					decoding="async"
				/>
			</div>
		<?php endforeach; ?>
		<span class="kmae-gallery-counter"><?php echo esc_html( '1 / ' . count( $gallery_ids ) ); ?></span>
		<button type="button" class="kmae-gallery-zoom" aria-label="<?php esc_attr_e( 'Galéria megnyitása', 'ki-mit-adna-erte' ); ?>" title="<?php esc_attr_e( 'Galéria megnyitása', 'ki-mit-adna-erte' ); ?>"></button>
	</div>

	<?php if ( count( $gallery_ids ) > 1 ) : ?>
		<div class="kmae-gallery-dots">
			<?php foreach ( $gallery_ids as $i => $aid ) : ?>
				<button type="button" class="kmae-gallery-dot <?php echo 0 === $i ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%d. kép', 'ki-mit-adna-erte' ), $i + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
		<div class="kmae-gallery-thumbs">
			<?php foreach ( $gallery_ids as $i => $aid ) : ?>
				<?php
				$thumb = KMAE_Images::get_url( $aid, 'medium' );
				if ( ! $thumb ) {
					continue;
				}
				?>
				<button type="button" class="kmae-gallery-thumb <?php echo 0 === $i ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<div class="kmae-lightbox" id="<?php echo esc_attr( $lightbox_id ); ?>" hidden aria-hidden="true" data-index="0">
	<div class="kmae-lightbox-backdrop" data-close="1"></div>
	<div class="kmae-lightbox-inner">
		<button type="button" class="kmae-lightbox-close" aria-label="<?php esc_attr_e( 'Bezárés', 'ki-mit-adna-erte' ); ?>">&times;</button>
		<button type="button" class="kmae-lightbox-nav kmae-lightbox-prev" aria-label="<?php esc_attr_e( 'Előző kép', 'ki-mit-adna-erte' ); ?>" hidden>&#8249;</button>
		<img src="" alt="" class="kmae-lightbox-img" />
		<button type="button" class="kmae-lightbox-nav kmae-lightbox-next" aria-label="<?php esc_attr_e( 'Következő kép', 'ki-mit-adna-erte' ); ?>" hidden>&#8250;</button>
	</div>
</div>
