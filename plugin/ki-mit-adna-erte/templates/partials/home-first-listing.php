<?php
/**
 * Az első sikeresen feladott hirdetés utáni gratuláló felület a kezdőoldalon.
 * A tartalmat a KMAE_Dashboard::get_home_state() adja (csak a mentett adatból).
 *
 * @package KMAE
 * @var array $kmae_home
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kmae_first = $kmae_home['first'];
$kmae_stars = (int) $kmae_first['stars'];

$kmae_first_icon = static function ( $name, $size = 20 ) {
	return class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $name, array( 'size' => $size ) ) : '';
};
?>
<section class="kmae-first" aria-labelledby="kmae-first-title">
	<header class="kmae-first__hero">
		<div class="kmae-first__stage" aria-hidden="true">
			<span class="kmae-first__confetti"></span>
			<span class="kmae-first__confetti"></span>
			<span class="kmae-first__confetti"></span>
			<span class="kmae-first__confetti"></span>
			<span class="kmae-first__confetti"></span>
			<span class="kmae-first__confetti"></span>
			<img class="kmae-first__mascot" src="https://albira.hu/wp-content/uploads/2026/10/ugyesvoltal-3.webp" alt="" width="200" height="406" decoding="async" />
		</div>

		<div class="kmae-first__intro">
			<p class="kmae-first__eyebrow"><span class="kmae-first__live" aria-hidden="true"></span><?php esc_html_e( 'A hirdetésed már látható', 'ki-mit-adna-erte' ); ?></p>
			<h2 class="kmae-first__title" id="kmae-first-title"><?php esc_html_e( 'Gratulálok az', 'ki-mit-adna-erte' ); ?> <span class="kmae-first__accent"><?php esc_html_e( 'első hirdetésedhez!', 'ki-mit-adna-erte' ); ?></span></h2>
			<p class="kmae-first__lead"><?php echo esc_html( ! empty( $kmae_first['praise'] ) ? __( 'Megtetted az első lépést afelé, hogy megtaláld autód új gazdáját, és a hirdetésed minden fontos részletet tartalmaz.', 'ki-mit-adna-erte' ) : __( 'Már megtetted az első lépést afelé, hogy megtaláld autód új gazdáját. Mutatunk néhány egyszerű tippet, amelyekkel még vonzóbbá teheted a hirdetésedet.', 'ki-mit-adna-erte' ) ); ?></p>
		</div>

		<div class="kmae-first__rating" style="--kf-pct: <?php echo esc_attr( $kmae_stars * 20 ); ?>;">
			<h3 class="kmae-first__rating-title"><?php esc_html_e( 'Mennyire felkészült a hirdetésed?', 'ki-mit-adna-erte' ); ?></h3>
			<div class="kmae-first__ring" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star count out of 5 */ __( '%d csillag az ötből', 'ki-mit-adna-erte' ), $kmae_stars ) ); ?>">
				<span class="kmae-first__score" aria-hidden="true"><?php echo esc_html( $kmae_stars ); ?><small>/5</small></span>
			</div>
			<p class="kmae-first__stars" aria-hidden="true">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="kmae-first__star<?php echo $i <= $kmae_stars ? ' is-on' : ''; ?>">★</span>
				<?php endfor; ?>
			</p>
			<p class="kmae-first__rating-text"><?php echo esc_html( $kmae_first['stars_text'] ); ?></p>
		</div>
	</header>

	<?php if ( ! empty( $kmae_first['praise'] ) ) : ?>
		<div class="kmae-first__tips">
			<article class="kmae-first__tip kmae-first__praise">
				<div class="kmae-first__tip-head">
					<span class="kmae-first__tip-icon" aria-hidden="true"><?php echo $kmae_first_icon( 'sparkles', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<h3 class="kmae-first__tip-title"><?php echo esc_html( $kmae_first['praise']['title'] ); ?></h3>
				</div>
				<p class="kmae-first__tip-text"><?php echo esc_html( $kmae_first['praise']['text'] ); ?></p>
			</article>
		</div>
	<?php else : ?>
	<div class="kmae-first__tips">
		<?php foreach ( $kmae_first['tips'] as $kmae_tip_i => $kmae_tip ) : ?>
			<article class="kmae-first__tip">
				<div class="kmae-first__tip-head">
					<span class="kmae-first__tip-icon" aria-hidden="true"><?php echo $kmae_first_icon( $kmae_tip['icon'] ?? 'sparkles', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div>
						<p class="kmae-first__tip-step"><?php echo esc_html( sprintf( /* translators: %d: tip number */ __( '%d. tipp', 'ki-mit-adna-erte' ), $kmae_tip_i + 1 ) ); ?></p>
						<h3 class="kmae-first__tip-title"><?php echo esc_html( $kmae_tip['title'] ); ?></h3>
					</div>
				</div>
				<p class="kmae-first__tip-text"><?php echo esc_html( $kmae_tip['text'] ); ?></p>
				<?php if ( ! empty( $kmae_tip['items'] ) ) : ?>
					<ul class="kmae-first__items">
						<?php foreach ( $kmae_tip['items'] as $kmae_item ) : ?>
							<li><?php echo $kmae_first_icon( 'plus', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $kmae_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( '' !== $kmae_tip['note'] ) : ?>
					<p class="kmae-first__note"><?php echo $kmae_first_icon( 'lightbulb', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $kmae_tip['note'] ); ?></span></p>
				<?php endif; ?>
				<?php if ( ! empty( $kmae_tip['metrics'] ) ) : ?>
					<div class="kmae-first__metrics">
						<ul class="kmae-first__scale">
							<?php foreach ( $kmae_tip['metrics']['scale'] as $kmae_metric ) : ?>
								<li class="kmae-first__metric is-<?php echo esc_attr( $kmae_metric['tone'] ); ?>">
									<span><?php echo esc_html( $kmae_metric['label'] ); ?></span>
									<strong><?php echo esc_html( $kmae_metric['value'] ); ?></strong>
								</li>
							<?php endforeach; ?>
						</ul>
						<p class="kmae-first__current is-<?php echo esc_attr( $kmae_tip['metrics']['current']['tone'] ); ?>">
							<span><?php echo esc_html( $kmae_tip['metrics']['current']['label'] ); ?></span>
							<strong><?php echo esc_html( $kmae_tip['metrics']['current']['value'] ); ?></strong>
						</p>
					</div>
				<?php endif; ?>
				<a class="kmae-first__cta" href="<?php echo esc_url( $kmae_tip['cta_url'] ); ?>"><span><?php echo esc_html( $kmae_tip['cta_label'] ); ?></span><?php echo $kmae_first_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</article>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<form method="post" class="kmae-first__dismiss">
		<?php wp_nonce_field( 'kmae_dismiss_first_tips_nonce', 'kmae_dismiss_first_tips_nonce' ); ?>
		<input type="hidden" name="kmae_action" value="dismiss_first_listing_tips" />
		<input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( $kmae_first['listing_id'] ); ?>" />
		<button type="submit" class="kmae-first__skip"><?php echo esc_html( ! empty( $kmae_first['praise'] ) ? __( 'Köszönöm', 'ki-mit-adna-erte' ) : __( 'Köszönöm, nem', 'ki-mit-adna-erte' ) ); ?></button>
	</form>
</section>
