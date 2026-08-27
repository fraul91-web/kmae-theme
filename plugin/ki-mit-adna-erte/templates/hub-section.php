<?php
/**
 * Hub submenu — Eladni / Venni / Statisztikák card pages.
 * Cards reuse the logged-in home hub markup (.kmae-hub__*).
 *
 * @package KMAE
 * @var array $hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mod   = isset( $hub['mod'] ) ? $hub['mod'] : 'sell';
$title = isset( $hub['title'] ) ? $hub['title'] : '';
$lead  = isset( $hub['lead'] ) ? $hub['lead'] : '';
$items = isset( $hub['items'] ) && is_array( $hub['items'] ) ? $hub['items'] : array();
?>
<section class="kmae-hub-menu kmae-hub-menu--<?php echo esc_attr( $mod ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
	<header class="kmae-hub-menu__intro">
		<?php if ( $title ) : ?>
			<h1 class="kmae-hub-menu__title">
				<?php echo esc_html( 'sell' === $mod ? __( 'Eladói központ', 'ki-mit-adna-erte' ) : $title ); ?>
			</h1>
		<?php endif; ?>
		<?php if ( 'sell' === $mod ) : ?>
			<p class="kmae-hub-menu__lead">
				<?php esc_html_e( 'Tedd közzé a hirdetésed, nézd meg, mit adnának érte, és dönts akkor, amikor neked megéri.', 'ki-mit-adna-erte' ); ?>
			</p>
		<?php elseif ( $lead ) : ?>
			<p class="kmae-hub-menu__lead"><?php echo esc_html( $lead ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( 'sell' === $mod ) : ?>
		<div class="kmae-hub-menu__section-head">
			<h2><?php echo esc_html( $lead ); ?></h2>
		</div>
	<?php endif; ?>

	<div class="kmae-hub__grid">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$icon = isset( $item['icon'] ) ? $item['icon'] : 'sparkles';
			$card_mod = isset( $item['tone'] ) ? $item['tone'] : $mod;
			?>
			<a
				class="kmae-hub__card kmae-hub__card--<?php echo esc_attr( $card_mod ); ?>"
				href="<?php echo esc_url( $item['url'] ); ?>"
				aria-label="<?php echo esc_attr( $item['label'] ); ?>"
			>
				<span class="kmae-hub__card-icon" aria-hidden="true">
					<?php echo class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $icon, array( 'size' => 28 ) ) : ''; ?>
				</span>
				<span class="kmae-hub__card-copy">
					<span class="kmae-hub__card-title">
						<?php echo esc_html( $item['label'] ); ?>
						<?php if ( ! empty( $item['badge'] ) ) : ?>
							<span class="kmae-hub-menu__badge"><?php echo esc_html( (string) $item['badge'] ); ?></span>
						<?php endif; ?>
					</span>
					<span class="kmae-hub__card-lead"><?php echo esc_html( $item['desc'] ); ?></span>
					<span class="kmae-hub__card-cta"><?php echo esc_html( $item['cta'] ?? __( 'Megnyitás', 'ki-mit-adna-erte' ) ); ?> →</span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
