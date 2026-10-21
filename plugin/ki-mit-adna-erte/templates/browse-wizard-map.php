<?php
/**
 * Browse wizard step 1 — Hungary map (country or county).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$browse_base = class_exists( 'KMAE_Pages' ) ? KMAE_Pages::browse_page_url() : home_url( '/hirdetesek/' );
$map_data    = KMAE_Counties::get_map_data();
$view_box    = $map_data['viewBox'] ?? '0 0 841.89 595.28';
$counties    = $map_data['counties'] ?? array();
$orszag_url  = add_query_arg( 'megye', KMAE_Counties::ORSZAG, $browse_base );

$vb_parts = preg_split( '/\s+/', trim( $view_box ) );
$vb_x     = isset( $vb_parts[0] ) ? (float) $vb_parts[0] : 0;
$vb_y     = isset( $vb_parts[1] ) ? (float) $vb_parts[1] : 0;
$vb_w     = isset( $vb_parts[2] ) ? (float) $vb_parts[2] : 841.89;
$vb_h     = isset( $vb_parts[3] ) ? (float) $vb_parts[3] : 595.28;
$aspect   = $vb_h > 0 ? round( $vb_w / $vb_h, 4 ) : 1.414;
?>
<div class="kmae-wizard kmae-wizard-map" data-browse-base="<?php echo esc_url( $browse_base ); ?>">
	<div class="kmae-wizard-header kmae-wizard-header--step-only">
		<span class="kmae-wizard-step-num"><?php echo esc_html( KMAE_Launch::wizard_step_label( 1 ) ); ?></span>
	</div>

	<div class="kmae-wizard-map-split">
		<div class="kmae-wizard-map-split__left">
			<h2 class="kmae-wizard-map-split__title"><?php esc_html_e( 'Hol keresel autót?', 'ki-mit-adna-erte' ); ?></h2>
			<p class="kmae-subtitle kmae-wizard-map-split__lead">
				<?php esc_html_e( 'Válaszd ki az egész országot, vagy kattints egy megyére a térképen.', 'ki-mit-adna-erte' ); ?>
			</p>
			<a
				href="<?php echo esc_url( $orszag_url ); ?>"
				class="kmae-btn kmae-btn-primary kmae-wizard-orszag-btn"
				data-county="<?php echo esc_attr( KMAE_Counties::ORSZAG ); ?>"
				data-label="<?php echo esc_attr__( 'Egész ország', 'ki-mit-adna-erte' ); ?>"
			>
				<?php esc_html_e( 'Egész ország', 'ki-mit-adna-erte' ); ?>
			</a>
		</div>

		<div class="kmae-wizard-map-split__right">
			<p class="kmae-map-or-hint"><?php esc_html_e( 'vagy válassz megyét', 'ki-mit-adna-erte' ); ?></p>

			<div class="kmae-map-wrap kmae-map-wrap-flat" style="--kmae-map-aspect: <?php echo esc_attr( $aspect ); ?>;">
				<svg
					class="kmae-hu-map"
					viewBox="<?php echo esc_attr( $view_box ); ?>"
					preserveAspectRatio="xMidYMid meet"
					role="img"
					aria-label="<?php esc_attr_e( 'Magyarország megyetérképe', 'ki-mit-adna-erte' ); ?>"
					xmlns="http://www.w3.org/2000/svg"
				>
					<defs>
						<linearGradient id="kmae-neon-county-fill" x1="0%" y1="0%" x2="100%" y2="100%">
							<stop offset="0%" stop-color="#2e1065" />
							<stop offset="100%" stop-color="#1e1b4b" />
						</linearGradient>
						<filter id="kmae-neon-glow" x="-50%" y="-50%" width="200%" height="200%">
							<feGaussianBlur stdDeviation="2.5" result="blur" />
							<feMerge>
								<feMergeNode in="blur" />
								<feMergeNode in="SourceGraphic" />
							</feMerge>
						</filter>
					</defs>
					<rect class="kmae-map-bg" x="<?php echo esc_attr( $vb_x ); ?>" y="<?php echo esc_attr( $vb_y ); ?>" width="<?php echo esc_attr( $vb_w ); ?>" height="<?php echo esc_attr( $vb_h ); ?>" fill="none" />
					<?php foreach ( $counties as $slug => $info ) : ?>
						<?php
						if ( empty( $info['d'] ) ) {
							continue;
						}
						$county_url = add_query_arg( 'megye', $slug, $browse_base );
						$label      = KMAE_Counties::get_label( $slug );
						$map_label  = $info['label'] ?? KMAE_Counties::get_map_label_short( $slug );
						$font_size  = isset( $info['fontSize'] ) ? (float) $info['fontSize'] : 6;
						$cx         = $info['cx'] ?? 0;
						$cy         = $info['cy'] ?? 0;
						?>
						<a
							href="<?php echo esc_url( $county_url ); ?>"
							class="kmae-county-link"
							data-county="<?php echo esc_attr( $slug ); ?>"
							data-label="<?php echo esc_attr( $label ); ?>"
						>
							<path
								class="kmae-county-path"
								data-county="<?php echo esc_attr( $slug ); ?>"
								d="<?php echo esc_attr( $info['d'] ); ?>"
							>
								<title><?php echo esc_html( $label ); ?></title>
							</path>
							<?php if ( $map_label && $font_size > 0 && $cx && $cy ) : ?>
								<text
									class="kmae-county-label"
									x="<?php echo esc_attr( $cx ); ?>"
									y="<?php echo esc_attr( $cy ); ?>"
									font-size="<?php echo esc_attr( $font_size ); ?>"
									text-anchor="middle"
									dominant-baseline="middle"
								><?php echo esc_html( $map_label ); ?></text>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</svg>
			</div>

			<div class="kmae-map-status" aria-live="polite">
				<span class="kmae-map-status-prefix"><?php esc_html_e( 'A kiválasztott terület:', 'ki-mit-adna-erte' ); ?></span>
				<strong class="kmae-map-status-name" data-default="<?php echo esc_attr__( '—', 'ki-mit-adna-erte' ); ?>">—</strong>
			</div>
			<?php /* Telefonon a megyére koppintás csak kijelöl (a név a státuszban látszik), innen a Tovább visz tovább (theme browse-mobile.css; asztalon rejtett). */ ?>
			<button type="button" class="kmae-btn kmae-btn-primary kmae-map-next" disabled><?php esc_html_e( 'Tovább', 'ki-mit-adna-erte' ); ?></button>
		</div>
	</div>
</div>
