<?php
/**
 * Logged-in home hub — greeting and the two main cards (Eladni / Venni).
 *
 * @package KMAE
 * @var array $dash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'kmae-home-greeting', KMAE_PLUGIN_URL . 'assets/js/home-greeting.js', array(), KMAE_VERSION, true );

$u = isset( $dash['urls'] ) && is_array( $dash['urls'] ) ? $dash['urls'] : array();

$home_cards = array(
	array(
		'mod'       => 'sell',
		'icon'      => 'car',
		'badge'     => 'plus',
		'title'     => __( 'Hirdetést feladok', 'ki-mit-adna-erte' ),
		'lead'      => array(
			__( 'Add el az autód úgy,', 'ki-mit-adna-erte' ),
			__( 'ahogy neked megfelel.', 'ki-mit-adna-erte' ),
		),
		'cta_url'   => $u['create'] ?? home_url( '/hirdetes-feladasa/' ),
		'cta_label' => __( 'Hirdetés feladása', 'ki-mit-adna-erte' ),
		'options'   => array(
			array(
				'icon'  => 'tag',
				'lines' => array( __( 'Fix ár', 'ki-mit-adna-erte' ) ),
			),
			array(
				'icon'  => 'crosshair',
				'lines' => array( __( 'Irányár', 'ki-mit-adna-erte' ) ),
			),
			array(
				'icon'  => 'users',
				'lines' => array(
					__( 'Ki mit', 'ki-mit-adna-erte' ),
					__( 'adna érte', 'ki-mit-adna-erte' ),
				),
			),
		),
	),
	array(
		'mod'       => 'buy',
		'icon'      => 'search',
		'badge'     => '',
		'title'     => __( 'Autót keresek', 'ki-mit-adna-erte' ),
		'lead'      => array(
			__( 'Böngéssz az autók között.', 'ki-mit-adna-erte' ),
		),
		'cta_url'   => $u['browse'] ?? home_url( '/hirdetesek/' ),
		'cta_label' => __( 'Autók böngészése', 'ki-mit-adna-erte' ),
		'options'   => array(
			array(
				'icon'  => 'car',
				'lines' => array(
					__( 'Széles', 'ki-mit-adna-erte' ),
					__( 'kínálat', 'ki-mit-adna-erte' ),
				),
			),
			array(
				'icon'  => 'shield',
				'lines' => array(
					__( 'Biztonságos', 'ki-mit-adna-erte' ),
					__( 'vétel', 'ki-mit-adna-erte' ),
				),
			),
			array(
				'icon'  => 'chat',
				'lines' => array(
					__( 'Közvetlen', 'ki-mit-adna-erte' ),
					__( 'kapcsolat', 'ki-mit-adna-erte' ),
				),
			),
		),
	),
);


$kmae_home_greet = KMAE_Dashboard::take_home_greeting();
$kmae_home       = KMAE_Dashboard::get_home_state( get_current_user_id(), $u );
/* Step 0: új, hirdetés nélküli felhasználó — a köszöntő fejléc helyett
	az „Első lépéseid” doboz látszik, benne a két kártyával. */
$kmae_home_start = $kmae_home['start'];
/* Az első hirdetés utáni gratuláló doboz: amíg látszik, csak ő van a lapon. */
$kmae_home_first = ! empty( $kmae_home['first'] );
/* Élő főoldal: a hirdetései statisztikával (a gratuláló doboz utáni végleges állapot). */
$kmae_home_live  = $kmae_home['live'];

if ( $kmae_home_live || $kmae_home_first ) {
	wp_enqueue_script( 'kmae-home-live', KMAE_PLUGIN_URL . 'assets/js/home-live.js', array(), KMAE_VERSION, true );
}

?>
<section class="kmae-hub" aria-label="<?php echo esc_attr__( 'Főmenü', 'ki-mit-adna-erte' ); ?>">
	<?php if ( ! $kmae_home_start && ! $kmae_home_first && ! $kmae_home_live ) : ?>
	<header class="kmae-home-greeting">
		<?php /* Belépés utáni első köszönéskor a integető manó, visszatéréskor
			a „közben vártalak" változat. */ ?>
		<img class="kmae-home-greeting__mascot" data-kmae-home-mascot data-kmae-mascot-return="https://albira.hu/wp-content/uploads/2026/09/idokozben1.webp" src="<?php echo esc_url( $kmae_home_greet ? 'https://albira.hu/wp-content/uploads/2026/10/szia3.webp' : 'https://albira.hu/wp-content/uploads/2026/09/idokozben1.webp' ); ?>" alt="" aria-hidden="true" width="200" height="200" decoding="async" />
		<div class="kmae-home-greeting__bubble">
			<h1 class="kmae-home-greeting__message">
				<?php if ( $kmae_home_greet ) : ?>
					<span class="kmae-home-greeting__welcome" data-kmae-home-welcome><?php printf( esc_html__( 'Szia, %s!', 'ki-mit-adna-erte' ), esc_html( $dash['first_name'] ?? $dash['name'] ?? '' ) ); ?></span>
				<?php endif; ?>
				<?php
				/* Köszönés után a kérdés szólítja nevén a felhasználót. */
				$kmae_home_name = trim( (string) ( $dash['first_name'] ?? $dash['name'] ?? '' ) );
				$kmae_home_ask  = $kmae_home_name
					? sprintf( __( 'Mit szeretnél ma csinálni, %s?', 'ki-mit-adna-erte' ), $kmae_home_name )
					: __( 'Mit szeretnél ma csinálni?', 'ki-mit-adna-erte' );
				?>
				<span data-kmae-home-ask data-kmae-ask-return="<?php echo esc_attr( $kmae_home_ask ); ?>"><?php echo esc_html( $kmae_home_greet ? __( 'Mit szeretnél ma csinálni?', 'ki-mit-adna-erte' ) : $kmae_home_ask ); ?></span>
			</h1>
		</div>
	</header>
	<?php endif; ?>

	<?php if ( $kmae_home_first ) : ?>
		<?php include KMAE_PLUGIN_DIR . 'templates/partials/home-first-listing.php'; ?>
	<?php elseif ( $kmae_home_live ) : ?>
		<?php include KMAE_PLUGIN_DIR . 'templates/partials/home-live.php'; ?>
	<?php endif; ?>

	<?php /* Step 0-ban a két kártya az „Első lépéseid” dobozba kerül. */ ?>
	<?php if ( $kmae_home_start ) : ?>
		<section class="kmae-home-start" aria-labelledby="kmae-home-start-label">
			<div class="kmae-home-start__top">
				<div class="kmae-home-start__copy">
					<h2 class="kmae-home-start__label" id="kmae-home-start-label"><?php esc_html_e( 'Első lépéseid', 'ki-mit-adna-erte' ); ?></h2>
					<p class="kmae-home-start__title"><?php echo esc_html( $kmae_home_start['title'] ); ?></p>
					<p class="kmae-home-start__text"><?php echo esc_html( $kmae_home_start['text'] ); ?></p>
				</div>
				<div class="kmae-home-start__hello">
					<img class="kmae-home-start__mascot" src="https://albira.hu/wp-content/uploads/2026/10/szia3.webp" alt="" aria-hidden="true" width="200" height="200" decoding="async" />
					<p class="kmae-home-start__bubble"><?php printf( esc_html__( 'Üdv az oldalon, %s!', 'ki-mit-adna-erte' ), esc_html( trim( (string) ( $dash['first_name'] ?? $dash['name'] ?? '' ) ) ) ); ?></p>
				</div>
			</div>
	<?php endif; ?>

	<?php /* A két kártya csak Step 0-ban (az „Első lépéseid” dobozban) és az
		állapot nélküli főoldalon látszik; a hirdetés feladása utáni élő
		főoldalon nem. */ ?>
	<?php if ( ! $kmae_home_first && ! $kmae_home_live ) : ?>
	<div class="kmae-home-cards">
		<?php foreach ( $home_cards as $card ) : ?>
			<article class="kmae-home-card kmae-home-card--<?php echo esc_attr( $card['mod'] ); ?>">
				<span class="kmae-home-card__ring" aria-hidden="true">
					<?php
					echo class_exists( 'KMAE_Icons' )
						? KMAE_Icons::render( $card['icon'], array( 'size' => 27 ) )
						: '';
					?>
					<?php if ( ! empty( $card['badge'] ) ) : ?>
						<span class="kmae-home-card__badge">
							<?php
							echo class_exists( 'KMAE_Icons' )
								? KMAE_Icons::render( $card['badge'], array( 'size' => 10 ) )
								: '';
							?>
						</span>
					<?php endif; ?>
				</span>

				<?php /* A cím és a leírás közös dobozban: a kártya háttérképe
					mintás, azon a szabadon álló szöveg elveszne. */ ?>
				<div class="kmae-home-card__copy">
					<h2 class="kmae-home-card__title"><?php echo esc_html( $card['title'] ); ?></h2>

					<p class="kmae-home-card__lead">
						<?php echo esc_html( $card['lead'][0] ); ?><br />
						<?php echo esc_html( $card['lead'][1] ); ?>
					</p>
				</div>

				<div class="kmae-home-card__opts" aria-hidden="true">
					<?php foreach ( $card['options'] as $opt ) : ?>
						<div class="kmae-home-card__opt">
							<span class="kmae-home-card__opt-icon">
								<?php
								echo class_exists( 'KMAE_Icons' )
									? KMAE_Icons::render( $opt['icon'], array( 'size' => 19 ) )
									: '';
								?>
							</span>
							<span class="kmae-home-card__opt-label">
								<?php foreach ( $opt['lines'] as $line ) : ?>
									<span class="kmae-home-card__opt-line"><?php echo esc_html( $line ); ?></span>
								<?php endforeach; ?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>

				<a class="kmae-home-card__cta" href="<?php echo esc_url( $card['cta_url'] ); ?>">
					<?php echo esc_html( $card['cta_label'] ); ?>
				</a>
			</article>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<?php if ( $kmae_home_start ) : ?>
		</section>
	<?php endif; ?>

</section>
