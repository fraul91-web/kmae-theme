<?php
/**
 * Photo motor — separate step: car diagram (13 points) + image uploads.
 *
 * @package KMAE
 * @var bool         $is_edit
 * @var WP_Post|null $listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slot_map          = ( $is_edit && $listing ) ? KMAE_Photo_Motor::get_slots( $listing->ID ) : array();
$required_slots    = KMAE_Photo_Motor::required_slots();
$recommended_slots = KMAE_Photo_Motor::recommended_slots();
$all_slots         = KMAE_Photo_Motor::all_slots();
$required_filled   = 0;
$all_filled        = 0;
foreach ( array_keys( $required_slots ) as $rk ) {
	if ( ! empty( $slot_map[ $rk ] ) ) {
		$required_filled++;
		$all_filled++;
	}
}
foreach ( array_keys( $recommended_slots ) as $rk ) {
	if ( ! empty( $slot_map[ $rk ] ) ) {
		$all_filled++;
	}
}
$required_total = count( $required_slots );
$all_total      = count( $all_slots );
$photos_ready   = $required_filled >= $required_total;
?>
<div class="kmae-photo-step" id="kmae-photo-step" hidden>
	<div class="kmae-photo-step__bar">
		<button type="button" class="kmae-btn kmae-btn-secondary" id="kmae-close-photo-step">
			<?php esc_html_e( 'Vissza az adatokhoz', 'ki-mit-adna-erte' ); ?>
		</button>
		<span class="kmae-photo-motor__progress" id="kmae-photo-progress">
			<?php
			printf(
				esc_html__( 'Kötelező: %1$d / %2$d', 'ki-mit-adna-erte' ),
				(int) $required_filled,
				(int) $required_total
			);
			?>
		</span>
	</div>

	<div class="kmae-photo-motor" id="kmae-photo-motor" data-required-total="<?php echo esc_attr( $required_total ); ?>" data-all-total="<?php echo esc_attr( $all_total ); ?>">
		<header class="kmae-photo-step__intro">
			<h3><?php esc_html_e( 'Töltsd fel az autó fotóit', 'ki-mit-adna-erte' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: 1: required, 2: recommended total */
					esc_html__( '%1$d kötelező nézet — összesen %2$d fotópont ajánlott. Kattints a rajzolt autó pontjaira, vagy görgess le a listához.', 'ki-mit-adna-erte' ),
					(int) $required_total,
					(int) $all_total
				);
				?>
			</p>
			<p class="kmae-hint">
				<?php esc_html_e( 'Válassz képet az eszközödről. Telefonon és tableten a kamerát vagy a galériát is használhatod.', 'ki-mit-adna-erte' ); ?>
			</p>
			<p class="kmae-photo-step__block-note" id="kmae-photo-block-note">
				<?php esc_html_e( 'A 7 kötelező fotó után a hirdetés aktiválható.', 'ki-mit-adna-erte' ); ?>
			</p>
		</header>

		<div id="kmae-photo-slots-wrap">
			<div class="kmae-car-diagram" id="kmae-car-diagram" aria-label="<?php esc_attr_e( 'Autó fotópontok', 'ki-mit-adna-erte' ); ?>">
				<svg class="kmae-car-diagram__svg" viewBox="0 0 200 320" role="img" aria-hidden="true">
					<rect x="55" y="40" width="90" height="240" rx="28" fill="#1e293b" stroke="#64748b" stroke-width="3"/>
					<rect x="68" y="55" width="64" height="36" rx="8" fill="#334155" stroke="#94a3b8" stroke-width="1.5"/>
					<rect x="68" y="230" width="64" height="32" rx="8" fill="#334155" stroke="#94a3b8" stroke-width="1.5"/>
					<rect x="72" y="110" width="56" height="90" rx="6" fill="#0f172a" stroke="#475569" stroke-width="1.5"/>
					<circle cx="55" cy="95" r="14" fill="#0f172a" stroke="#64748b" stroke-width="2"/>
					<circle cx="145" cy="95" r="14" fill="#0f172a" stroke="#64748b" stroke-width="2"/>
					<circle cx="55" cy="225" r="14" fill="#0f172a" stroke="#64748b" stroke-width="2"/>
					<circle cx="145" cy="225" r="14" fill="#0f172a" stroke="#64748b" stroke-width="2"/>
					<text x="100" y="165" text-anchor="middle" fill="#94a3b8" font-size="11" font-family="sans-serif"><?php echo esc_html__( 'AUTÓ', 'ki-mit-adna-erte' ); ?></text>
				</svg>
				<?php
				$i = 0;
				foreach ( $all_slots as $key => $info ) :
					$i++;
					$aid     = $slot_map[ $key ] ?? 0;
					$filled  = ! empty( $aid );
					$req     = ! empty( $info['required'] );
					$x       = isset( $info['x'] ) ? (float) $info['x'] : 50;
					$y       = isset( $info['y'] ) ? (float) $info['y'] : 50;
					$classes = 'kmae-car-dot';
					if ( $req ) {
						$classes .= ' kmae-car-dot--required';
					} else {
						$classes .= ' kmae-car-dot--optional';
					}
					if ( $filled ) {
						$classes .= ' is-filled';
					}
					?>
					<button
						type="button"
						class="<?php echo esc_attr( $classes ); ?>"
						style="left:<?php echo esc_attr( $x ); ?>%;top:<?php echo esc_attr( $y ); ?>%;"
						data-kmae-goto-slot="<?php echo esc_attr( $key ); ?>"
						title="<?php echo esc_attr( $info['label'] ); ?>"
						aria-label="<?php echo esc_attr( $info['label'] ); ?>"
					>
						<span class="kmae-car-dot__num"><?php echo esc_html( (string) $i ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<p class="kmae-car-diagram__legend">
				<span class="kmae-car-dot-sample kmae-car-dot-sample--required"></span> <?php esc_html_e( 'Kötelező', 'ki-mit-adna-erte' ); ?>
				<span class="kmae-car-dot-sample kmae-car-dot-sample--optional"></span> <?php esc_html_e( 'Ajánlott', 'ki-mit-adna-erte' ); ?>
				<span class="kmae-car-dot-sample kmae-car-dot-sample--filled"></span> <?php esc_html_e( 'Kész', 'ki-mit-adna-erte' ); ?>
			</p>

			<div class="kmae-photo-motor__slots kmae-photo-motor__slots--required" id="kmae-photo-slots-required">
				<h4><?php esc_html_e( 'Kötelező (7)', 'ki-mit-adna-erte' ); ?></h4>
				<?php
				$step = 0;
				foreach ( $required_slots as $key => $info ) :
					$step++;
					$aid    = $slot_map[ $key ] ?? 0;
					$thumb  = $aid ? KMAE_Images::get_url( $aid, 'medium_large' ) : '';
					$filled = (bool) $thumb;
					?>
					<div class="kmae-photo-slot <?php echo $filled ? 'is-filled' : ''; ?>" id="kmae-slot-<?php echo esc_attr( $key ); ?>" data-slot="<?php echo esc_attr( $key ); ?>" data-required="1">
						<div class="kmae-photo-slot__head">
							<span class="kmae-photo-slot__step"><?php echo esc_html( $step . '/' . $required_total ); ?></span>
							<strong class="kmae-photo-slot__label"><?php echo esc_html( $info['label'] ); ?></strong>
						</div>
						<p class="kmae-photo-slot__hint"><?php echo esc_html( $info['hint'] ); ?></p>
						<div class="kmae-photo-slot__preview">
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
							<?php else : ?>
								<span class="kmae-photo-slot__placeholder"><?php esc_html_e( 'Még nincs fotó', 'ki-mit-adna-erte' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="kmae-photo-slot__picker">
							<button type="button" class="kmae-btn kmae-btn-secondary kmae-photo-slot__select-btn" data-kmae-photo-slot="<?php echo esc_attr( $key ); ?>">
								<span class="kmae-photo-slot__select-label"><?php echo $filled ? esc_html__( 'Fotó cseréje', 'ki-mit-adna-erte' ) : esc_html__( 'Fotó kiválasztása', 'ki-mit-adna-erte' ); ?></span>
							</button>
							<input
								type="file"
								name="kmae_slot_photo[<?php echo esc_attr( $key ); ?>]"
								accept="image/*"
								hidden
								tabindex="-1"
								aria-hidden="true"
								data-kmae-slot-input="<?php echo esc_attr( $key ); ?>"
							/>
						</div>
						<p class="kmae-photo-slot__error" hidden></p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="kmae-photo-motor__required-done" id="kmae-photo-required-done" <?php echo $photos_ready ? '' : 'hidden'; ?>>
				<div class="kmae-notice kmae-notice-success">
					<p><strong><?php esc_html_e( 'A 7 kötelező fotó megvan.', 'ki-mit-adna-erte' ); ?></strong></p>
					<p><?php esc_html_e( 'Az alábbi 6 ajánlott ponttal összesen 13 fotót adhatsz — így a vevő nagyobb bizalommal jön.', 'ki-mit-adna-erte' ); ?></p>
				</div>
			</div>

			<div class="kmae-photo-motor__recommended" id="kmae-photo-recommended">
				<h4><?php esc_html_e( 'Ajánlott (további 6 — összesen 13)', 'ki-mit-adna-erte' ); ?></h4>
				<p class="kmae-hint">
					<?php esc_html_e( 'Nem kötelezők a feladáshoz, de erősen ajánlottak. Minél többet mutatsz, annál kisebb a meglepetés a helyszínen.', 'ki-mit-adna-erte' ); ?>
				</p>
				<div class="kmae-photo-motor__slots kmae-photo-motor__slots--recommended">
					<?php foreach ( $recommended_slots as $key => $info ) :
						$aid    = $slot_map[ $key ] ?? 0;
						$thumb  = $aid ? KMAE_Images::get_url( $aid, 'medium_large' ) : '';
						$filled = (bool) $thumb;
						?>
						<div class="kmae-photo-slot <?php echo $filled ? 'is-filled' : ''; ?>" id="kmae-slot-<?php echo esc_attr( $key ); ?>" data-slot="<?php echo esc_attr( $key ); ?>" data-required="0">
							<div class="kmae-photo-slot__head">
								<strong class="kmae-photo-slot__label"><?php echo esc_html( $info['label'] ); ?></strong>
							</div>
							<p class="kmae-photo-slot__hint"><?php echo esc_html( $info['hint'] ); ?></p>
							<div class="kmae-photo-slot__preview">
								<?php if ( $thumb ) : ?>
									<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
								<?php else : ?>
									<span class="kmae-photo-slot__placeholder"><?php esc_html_e( 'Ajánlott', 'ki-mit-adna-erte' ); ?></span>
								<?php endif; ?>
							</div>
							<div class="kmae-photo-slot__picker">
								<button type="button" class="kmae-btn kmae-btn-secondary kmae-photo-slot__select-btn" data-kmae-photo-slot="<?php echo esc_attr( $key ); ?>">
									<span class="kmae-photo-slot__select-label"><?php echo $filled ? esc_html__( 'Fotó cseréje', 'ki-mit-adna-erte' ) : esc_html__( 'Fotó kiválasztása', 'ki-mit-adna-erte' ); ?></span>
								</button>
								<input
									type="file"
									name="kmae_slot_photo[<?php echo esc_attr( $key ); ?>]"
									accept="image/*"
									hidden
									tabindex="-1"
									aria-hidden="true"
									data-kmae-slot-input="<?php echo esc_attr( $key ); ?>"
								/>
							</div>
							<p class="kmae-photo-slot__error" hidden></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="kmae-photo-step__footer">
			<button type="button" class="kmae-btn kmae-btn-primary" id="kmae-photo-done-btn" <?php echo $photos_ready ? '' : 'disabled'; ?>>
				<?php esc_html_e( 'Kész — vissza a hirdetéshez', 'ki-mit-adna-erte' ); ?>
			</button>
			<p class="kmae-hint" id="kmae-photo-done-hint" <?php echo $photos_ready ? 'hidden' : ''; ?>>
				<?php esc_html_e( 'Töltsd fel mind a 7 kötelező nézetet a folytatáshoz.', 'ki-mit-adna-erte' ); ?>
			</p>
		</div>
	</div>
</div>
