<?php
/**
 * Élő főoldal: a felhasználó hirdetései hirdetésenkénti statisztikával
 * (megtekintés + heti görbe, érdeklődők, kedvencek, ajánlatok, lejárat;
 * adat nélkül „az első nézőkre vár” állapot).
 * Az adat a KMAE_Dashboard::get_home_state() `live` mezője; a számlálók
 * felpörgetését és a „+N” jelzést a home-live.js adja.
 *
 * @package KMAE
 * @var array $kmae_home
 * @var array $dash
 * @var array $u
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kmae_live  = $kmae_home['live'];
$kmae_say   = $kmae_live['say'];
$kmae_many  = count( $kmae_live['cards'] ) > 1;
$kmae_name  = trim( (string) ( $dash['first_name'] ?? $dash['name'] ?? '' ) );
$kmae_icon  = static function ( $name, $size = 20 ) {
	return class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $name, array( 'size' => $size ) ) : '';
};
$kmae_num   = static function ( $n ) {
	return number_format_i18n( (int) $n );
};
?>
<section class="kmae-live" aria-labelledby="kmae-live-title" data-kmae-live-page="dashboard_home" data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'dashboard_home' ) ); ?>">
	<header class="kmae-live__hero">
		<div class="kmae-live__intro">
			<p class="kmae-live__eyebrow">
				<span class="kmae-live__pulse" aria-hidden="true"></span>
				<?php esc_html_e( 'Élőben', 'ki-mit-adna-erte' ); ?>
				<span class="kmae-live__ago" data-kmae-live-ago><?php esc_html_e( 'most frissült', 'ki-mit-adna-erte' ); ?></span>
			</p>
			<h1 class="kmae-live__title" id="kmae-live-title">
				<?php if ( '' !== $kmae_name ) : ?>
					<?php printf( esc_html__( 'Szia, %s!', 'ki-mit-adna-erte' ), esc_html( $kmae_name ) ); ?>
				<?php endif; ?>
				<span class="kmae-live__accent"><?php echo esc_html( $kmae_many ? __( 'Így élnek a hirdetéseid.', 'ki-mit-adna-erte' ) : __( 'Így él a hirdetésed.', 'ki-mit-adna-erte' ) ); ?></span>
			</h1>
			<div class="kmae-live__bubble">
				<p class="kmae-live__say"><?php echo esc_html( $kmae_say['text'] ); ?></p>
				<?php if ( '' !== $kmae_say['url'] ) : ?>
					<a class="kmae-live__say-cta" href="<?php echo esc_url( $kmae_say['url'] ); ?>"><span><?php echo esc_html( $kmae_say['cta'] ); ?></span><?php echo $kmae_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<div class="kmae-live__cards">
		<?php foreach ( $kmae_live['cards'] as $kmae_i => $kmae_card ) : ?>
			<?php
			$kmae_on     = in_array( $kmae_card['state'], array( KMAE_Meta::STATUS_ACTIVE, 'reserved' ), true );
			$kmae_delta  = $kmae_card['week'] - $kmae_card['last_week'];
			$kmae_series = array_map( 'intval', $kmae_card['series'] );
			$kmae_peak   = max( $kmae_series );
			$kmae_last   = count( $kmae_series ) - 1;
			$kmae_key    = 'l' . $kmae_card['id'];
			$kmae_grad   = 'kmae-lc-' . (int) $kmae_card['id'];

			/* Heti görbe: 400×100-as rajzlap, hetenként egy pont az oszlop közepén,
			   a pont magassága = 6% + érték/csúcs × 80% (a pontok CSS-e ugyanezt számolja). */
			$kmae_line = '';
			$kmae_area = '';
			if ( $kmae_peak > 0 ) {
				$kmae_step = 400 / count( $kmae_series );
				$kmae_pts  = array();
				foreach ( $kmae_series as $kmae_w => $kmae_v ) {
					$kmae_pts[] = array( ( $kmae_w + 0.5 ) * $kmae_step, 94 - $kmae_v / $kmae_peak * 80 );
				}
				$kmae_line = sprintf( 'M0 %1$.2f L%2$.2f %1$.2f', $kmae_pts[0][1], $kmae_pts[0][0] );
				for ( $kmae_p = 1, $kmae_n = count( $kmae_pts ); $kmae_p < $kmae_n; $kmae_p++ ) {
					list( $kmae_x0, $kmae_y0 ) = $kmae_pts[ $kmae_p - 1 ];
					list( $kmae_x1, $kmae_y1 ) = $kmae_pts[ $kmae_p ];
					$kmae_line .= sprintf( ' C%.2f %.2f %.2f %.2f %.2f %.2f', $kmae_x0 + $kmae_step / 2, $kmae_y0, $kmae_x1 - $kmae_step / 2, $kmae_y1, $kmae_x1, $kmae_y1 );
				}
				$kmae_line .= sprintf( ' L400 %.2f', $kmae_pts[ $kmae_n - 1 ][1] );
				$kmae_area  = $kmae_line . ' L400 100 L0 100 Z';
			}

			$kmae_engage = array(
				array(
					'key'   => 'chats',
					'icon'  => 'message-square',
					'label' => __( 'Érdeklődő', 'ki-mit-adna-erte' ),
					'sub'   => __( 'üzenetet írt', 'ki-mit-adna-erte' ),
					'empty' => __( 'Itt látod, ha írnak', 'ki-mit-adna-erte' ),
					'hot'   => $kmae_card['unread'] > 0,
				),
				array(
					'key'   => 'favorites',
					'icon'  => 'heart',
					'label' => __( 'Kedvenc', 'ki-mit-adna-erte' ),
					'sub'   => __( 'elmentette', 'ki-mit-adna-erte' ),
					'empty' => __( 'Ha valaki elmenti', 'ki-mit-adna-erte' ),
					'hot'   => false,
				),
				array(
					'key'   => 'offers',
					'icon'  => 'banknote',
					'label' => __( 'Ajánlat', 'ki-mit-adna-erte' ),
					'sub'   => 'reserved' === $kmae_card['state'] ? __( 'elfogadva', 'ki-mit-adna-erte' ) : __( 'vár rád', 'ki-mit-adna-erte' ),
					'empty' => __( 'Ide érkeznek az ajánlatok', 'ki-mit-adna-erte' ),
					'hot'   => $kmae_card['offers'] > 0,
				),
			);
			?>
			<article class="kmae-live-card is-<?php echo esc_attr( $kmae_card['state'] ); ?>" style="--i: <?php echo (int) $kmae_i; ?>;">
				<div class="kmae-live-card__side">
					<a class="kmae-live-card__media" href="<?php echo esc_url( $kmae_card['url'] ); ?>">
						<?php if ( '' !== $kmae_card['photo'] ) : ?>
							<img src="<?php echo esc_url( $kmae_card['photo'] ); ?>" alt="<?php echo esc_attr( $kmae_card['title'] ); ?>" loading="<?php echo 0 === $kmae_i ? 'eager' : 'lazy'; ?>" decoding="async" />
						<?php else : ?>
							<span class="kmae-live-card__nophoto" aria-hidden="true"><?php echo $kmae_icon( 'camera', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<span class="kmae-live-card__state">
							<?php if ( KMAE_Meta::STATUS_ACTIVE === $kmae_card['state'] ) : ?>
								<span class="kmae-live-card__dot" aria-hidden="true"></span><?php esc_html_e( 'Látható', 'ki-mit-adna-erte' ); ?>
							<?php else : ?>
								<?php echo esc_html( $kmae_card['label'] ); ?>
							<?php endif; ?>
						</span>
					</a>

					<?php if ( null !== $kmae_card['days'] && 'reserved' !== $kmae_card['state'] ) : ?>
						<div class="kmae-live-card__life" style="--life: <?php echo (int) $kmae_card['life_pct']; ?>%;">
							<span class="kmae-live-card__life-icon" aria-hidden="true"><?php echo $kmae_icon( 'hourglass', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="kmae-live-card__life-text">
								<?php
								/* translators: %d: days left */
								echo esc_html( sprintf( _n( 'Még %d napig látható', 'Még %d napig látható', (int) $kmae_card['days'], 'ki-mit-adna-erte' ), (int) $kmae_card['days'] ) );
								?>
								<span class="kmae-live-card__lifebar" aria-hidden="true"><span></span></span>
							</span>
						</div>
					<?php endif; ?>

					<div class="kmae-live-card__actions">
						<?php if ( KMAE_Meta::STATUS_AWAITING_PHOTOS === $kmae_card['state'] ) : ?>
							<a class="kmae-live-card__btn is-main" href="<?php echo esc_url( $kmae_card['edit_url'] ); ?>"><?php echo $kmae_icon( 'camera', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Fotók feltöltése', 'ki-mit-adna-erte' ); ?></span></a>
						<?php elseif ( KMAE_Meta::STATUS_EXPIRED === $kmae_card['state'] ) : ?>
							<a class="kmae-live-card__btn is-main" href="<?php echo esc_url( $kmae_card['list_url'] ); ?>"><?php echo $kmae_icon( 'repeat', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Meghosszabbítás', 'ki-mit-adna-erte' ); ?></span></a>
						<?php else : ?>
							<a class="kmae-live-card__btn is-main" href="<?php echo esc_url( $kmae_card['url'] ); ?>"><?php echo $kmae_icon( 'eye', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Megnézem', 'ki-mit-adna-erte' ); ?></span></a>
							<?php if ( '' !== $kmae_card['edit_url'] ) : ?>
								<a class="kmae-live-card__btn" href="<?php echo esc_url( $kmae_card['edit_url'] ); ?>"><?php echo $kmae_icon( 'pencil', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Szerkesztés', 'ki-mit-adna-erte' ); ?></span></a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="kmae-live-card__main">
					<header class="kmae-live-card__head">
						<h2 class="kmae-live-card__title"><a href="<?php echo esc_url( $kmae_card['url'] ); ?>"><?php echo esc_html( $kmae_card['title'] ); ?></a></h2>
						<p class="kmae-live-card__facts">
							<?php if ( '' !== $kmae_card['meta'] ) : ?>
								<span class="kmae-live-card__meta"><?php echo $kmae_icon( 'car', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $kmae_card['meta'] ); ?></span>
							<?php endif; ?>
							<span class="kmae-live-card__price"><?php echo $kmae_icon( 'tag', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $kmae_card['price'] ); ?></span>
						</p>
					</header>

					<?php if ( $kmae_on ) : ?>
						<div class="kmae-live-card__pulse<?php echo $kmae_card['views'] < 1 ? ' is-quiet' : ''; ?>">
							<?php if ( $kmae_card['views'] > 0 ) : ?>
								<div class="kmae-live-metric kmae-live-tile">
									<span class="kmae-live-metric__label"><?php echo $kmae_icon( 'eye', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?></span>
									<strong class="kmae-live-metric__num" data-kmae-count="<?php echo (int) $kmae_card['views']; ?>" data-kmae-count-key="<?php echo esc_attr( $kmae_key . ':views' ); ?>"><?php echo esc_html( $kmae_num( $kmae_card['views'] ) ); ?></strong>
									<span class="kmae-live-metric__sub"><?php esc_html_e( 'összesen, mióta kint van', 'ki-mit-adna-erte' ); ?></span>
									<span class="kmae-live-week kmae-live-tile">
										<?php esc_html_e( 'Ezen a héten', 'ki-mit-adna-erte' ); ?>
										<strong data-kmae-count="<?php echo (int) $kmae_card['week']; ?>" data-kmae-count-key="<?php echo esc_attr( $kmae_key . ':week' ); ?>"><?php echo esc_html( $kmae_num( $kmae_card['week'] ) ); ?></strong>
									</span>
									<?php if ( $kmae_delta > 0 ) : ?>
										<span class="kmae-live-metric__trend is-up">
											<?php
											/* translators: %s: increase vs last week */
											echo esc_html( sprintf( __( '↑ %s a múlt héthez', 'ki-mit-adna-erte' ), $kmae_num( $kmae_delta ) ) );
											?>
										</span>
									<?php elseif ( $kmae_card['last_week'] > 0 ) : ?>
										<span class="kmae-live-metric__trend">
											<?php
											/* translators: %s: last week's views */
											echo esc_html( sprintf( __( 'múlt héten %s', 'ki-mit-adna-erte' ), $kmae_num( $kmae_card['last_week'] ) ) );
											?>
										</span>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<div class="kmae-live-metric is-waiting">
									<span class="kmae-live-radar" aria-hidden="true"><span></span><span></span><?php echo $kmae_icon( 'eye', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<strong class="kmae-live-metric__wait"><?php esc_html_e( 'Az első nézőkre vár', 'ki-mit-adna-erte' ); ?></strong>
									<span class="kmae-live-metric__sub"><?php esc_html_e( 'Kint van a kínálatban, itt látod majd, hányan nézik.', 'ki-mit-adna-erte' ); ?></span>
								</div>
							<?php endif; ?>

							<figure class="kmae-live-chart<?php echo 0 === $kmae_peak ? ' is-empty' : ''; ?>">
								<figcaption class="kmae-live-chart__title"><?php echo $kmae_icon( 'bar-chart', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Megtekintések hetente', 'ki-mit-adna-erte' ); ?></figcaption>
								<div class="kmae-live-chart__body">
									<svg class="kmae-live-chart__svg" viewBox="0 0 400 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
										<defs>
											<linearGradient id="<?php echo esc_attr( $kmae_grad ); ?>" x1="0" y1="0" x2="0" y2="1">
												<stop offset="0" stop-color="#00c7f7" stop-opacity="0.42" />
												<stop offset="1" stop-color="#00c7f7" stop-opacity="0" />
											</linearGradient>
										</defs>
										<?php if ( $kmae_peak > 0 ) : ?>
											<path class="kmae-live-chart__area" d="<?php echo esc_attr( $kmae_area ); ?>" fill="url(#<?php echo esc_attr( $kmae_grad ); ?>)" />
											<path class="kmae-live-chart__line" d="<?php echo esc_attr( $kmae_line ); ?>" />
										<?php else : ?>
											<path class="kmae-live-chart__area" d="M0 82 C50 82 50 66 100 66 S150 74 200 56 S300 34 400 42 L400 100 L0 100 Z" fill="url(#<?php echo esc_attr( $kmae_grad ); ?>)" />
											<path class="kmae-live-chart__line" d="M0 82 C50 82 50 66 100 66 S150 74 200 56 S300 34 400 42" />
										<?php endif; ?>
									</svg>
									<?php if ( 0 === $kmae_peak ) : ?>
										<p class="kmae-live-chart__hint"><?php echo $kmae_icon( 'sparkles', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Itt rajzolódik ki, hétről hétre hányan nézik', 'ki-mit-adna-erte' ); ?></p>
									<?php endif; ?>
									<ol class="kmae-live-chart__weeks">
										<?php foreach ( $kmae_series as $kmae_w => $kmae_v ) : ?>
											<?php
											$kmae_ago = $kmae_last - $kmae_w;
											$kmae_lab = 0 === $kmae_ago
												? __( 'Ez a hét', 'ki-mit-adna-erte' )
												: ( 1 === $kmae_ago
													? __( 'Múlt hét', 'ki-mit-adna-erte' )
													/* translators: %d: weeks ago */
													: sprintf( __( '%d hete', 'ki-mit-adna-erte' ), $kmae_ago ) );
											/* translators: 1: week label 2: view count */
											$kmae_tip = sprintf( __( '%1$s: %2$s megtekintés', 'ki-mit-adna-erte' ), $kmae_lab, $kmae_num( $kmae_v ) );
											?>
											<li class="kmae-live-chart__week<?php echo 0 === $kmae_ago ? ' is-now' : ''; ?>" style="--h: <?php echo $kmae_peak > 0 ? (int) round( $kmae_v / $kmae_peak * 100 ) : 0; ?>; --b: <?php echo (int) $kmae_w; ?>;" tabindex="0" aria-label="<?php echo esc_attr( $kmae_tip ); ?>">
												<span class="kmae-live-chart__zone">
													<?php if ( $kmae_peak > 0 ) : ?>
														<span class="kmae-live-chart__dot" aria-hidden="true"></span>
													<?php endif; ?>
													<span class="kmae-live-chart__tip" aria-hidden="true"><?php echo esc_html( $kmae_tip ); ?></span>
												</span>
												<span class="kmae-live-chart__lab" aria-hidden="true"><?php echo esc_html( 0 === $kmae_ago ? $kmae_lab : ( 1 === $kmae_ago ? __( 'múlt', 'ki-mit-adna-erte' ) : '-' . $kmae_ago ) ); ?></span>
											</li>
										<?php endforeach; ?>
									</ol>
								</div>
							</figure>
						</div>

						<ul class="kmae-live-engage">
							<?php foreach ( $kmae_engage as $kmae_e ) : ?>
								<?php $kmae_val = (int) $kmae_card[ $kmae_e['key'] ]; ?>
								<li class="kmae-live-tile<?php echo $kmae_val < 1 ? ' is-zero' : ''; ?><?php echo $kmae_e['hot'] ? ' is-hot' : ''; ?>">
									<span class="kmae-live-tile__icon" aria-hidden="true"><?php echo $kmae_icon( $kmae_e['icon'], 19 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="kmae-live-tile__text">
										<span class="kmae-live-tile__top">
											<strong class="kmae-live-tile__num" data-kmae-count="<?php echo $kmae_val; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-kmae-count-key="<?php echo esc_attr( $kmae_key . ':' . $kmae_e['key'] ); ?>"><?php echo esc_html( $kmae_num( $kmae_val ) ); ?></strong>
											<span class="kmae-live-tile__label"><?php echo esc_html( $kmae_e['label'] ); ?></span>
										</span>
										<?php if ( 'chats' === $kmae_e['key'] && $kmae_card['unread'] > 0 ) : ?>
											<a class="kmae-live-tile__sub is-link" href="<?php echo esc_url( $kmae_card['msg_url'] ); ?>">
												<?php
												/* translators: %s: unread conversation count */
												echo esc_html( sprintf( __( '%s olvasatlan', 'ki-mit-adna-erte' ), $kmae_num( $kmae_card['unread'] ) ) );
												?>
											</a>
										<?php else : ?>
											<span class="kmae-live-tile__sub"><?php echo esc_html( $kmae_val < 1 ? $kmae_e['empty'] : $kmae_e['sub'] ); ?></span>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<div class="kmae-live-card__notice">
							<span class="kmae-live-card__notice-icon" aria-hidden="true"><?php echo $kmae_icon( KMAE_Meta::STATUS_EXPIRED === $kmae_card['state'] ? 'hourglass' : 'camera', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<p class="kmae-live-card__notice-title">
								<?php echo esc_html( KMAE_Meta::STATUS_EXPIRED === $kmae_card['state'] ? __( 'A hirdetésed lejárt, most nem látja senki.', 'ki-mit-adna-erte' ) : __( 'A hirdetésed a fotóidra vár.', 'ki-mit-adna-erte' ) ); ?>
							</p>
							<p class="kmae-live-card__notice-text">
								<?php echo esc_html( KMAE_Meta::STATUS_EXPIRED === $kmae_card['state'] ? __( 'Egy kattintással újra élővé teheted, és a statisztika folytatódik.', 'ki-mit-adna-erte' ) : __( 'Amint feltöltöd őket, megjelenik a kínálatban, és itt látod, kik nézik.', 'ki-mit-adna-erte' ) ); ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<p class="kmae-live__foot">
		<?php if ( $kmae_live['more'] > 0 ) : ?>
		<a href="<?php echo esc_url( $u['listings'] ?? home_url( '/sajat-hirdetesek/' ) ); ?>">
			<?php
			/* translators: %d: hidden listing count */
			echo esc_html( sprintf( __( 'Minden hirdetésem (még %d)', 'ki-mit-adna-erte' ), (int) $kmae_live['more'] ) );
			?>
		</a>
		<?php endif; ?>
		<?php if ( ! empty( $kmae_home['sold'] ) && class_exists( 'KMAE_Chat_Offers' ) ) : ?>
			<span class="kmae-live__sold"><?php echo $kmae_icon( 'party-popper', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( KMAE_Chat_Offers::sold_label( (int) $kmae_home['sold'] ) ); ?></span>
		<?php endif; ?>
	</p>
</section>
