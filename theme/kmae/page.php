<?php
/**
 * Page template.
 *
 * @package KMAE
 */

get_header();

$is_hub_menu       = false;
$is_listing_create = false;
$is_my_listings    = false;
$is_raw_shortcode  = false;
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$content = (string) get_post_field( 'post_content', get_the_ID() );
		$slug    = (string) get_post_field( 'post_name', get_the_ID() );
		$is_hub_menu = has_shortcode( $content, 'kmae_hub_sell' )
			|| has_shortcode( $content, 'kmae_hub_buy' )
			|| has_shortcode( $content, 'kmae_hub_stats' )
			|| false !== strpos( $content, '[kmae_hub_sell' )
			|| false !== strpos( $content, '[kmae_hub_buy' )
			|| false !== strpos( $content, '[kmae_hub_stats' )
			|| in_array( $slug, array( 'eladni', 'venni', 'statisztikak' ), true );
		$is_listing_create = has_shortcode( $content, 'kmae_create_listing' )
			|| false !== strpos( $content, '[kmae_create_listing' )
			|| 'hirdetes-feladasa' === $slug;
		$is_my_listings = has_shortcode( $content, 'kmae_my_listings' )
			|| false !== strpos( $content, '[kmae_my_listings' )
			|| 'sajat-hirdetesek' === $slug;
		/* A profil és a beállítások kimenete rácsokból és üvegpanelekből áll.
		   A `the_content()` szűrőlánca (wpautop) bekezdéseket és sortöréseket
		   szúrna bele, ami a rács gyerekeit összekeveri — ezért ezeken az
		   oldalakon a shortcode-ot közvetlenül futtatjuk, ahogy a
		   hirdetésfeladásnál is. */
		$is_raw_shortcode = has_shortcode( $content, 'kmae_profile' )
			|| false !== strpos( $content, '[kmae_profile' )
			|| 'profil' === $slug
			|| has_shortcode( $content, 'kmae_settings' )
			|| false !== strpos( $content, '[kmae_settings' )
			|| 'beallitasok' === $slug
			|| has_shortcode( $content, 'kmae_messages' )
			|| false !== strpos( $content, '[kmae_messages' )
			|| 'uzenetek' === $slug
			|| has_shortcode( $content, 'kmae_notifications' )
			|| false !== strpos( $content, '[kmae_notifications' )
			|| 'ertesitesek' === $slug
			|| has_shortcode( $content, 'kmae_login' )
			|| false !== strpos( $content, '[kmae_login' )
			|| 'belepes' === $slug
			|| has_shortcode( $content, 'kmae_register' )
			|| false !== strpos( $content, '[kmae_register' )
			|| 'regisztracio' === $slug
			|| has_shortcode( $content, 'kmae_favorites' )
			|| false !== strpos( $content, '[kmae_favorites' )
			|| 'kedvencek' === $slug
			/* GYIK: a wpautop a kereső inline scriptjébe <p>-t szúrt (szintaxishiba). */
			|| has_shortcode( $content, 'kmae_faq' )
			|| false !== strpos( $content, '[kmae_faq' );
		break;
	}
	rewind_posts();
}

$page_mod = '';
if ( $is_hub_menu ) {
	$page_mod = ' kmae-page--hub-menu';
} elseif ( $is_listing_create ) {
	$page_mod = ' kmae-page--listing-create';
} elseif ( $is_my_listings ) {
	$page_mod = ' kmae-page--my-listings';
}
?>

<section class="kmae-page<?php echo esc_attr( $page_mod ); ?>">
	<?php if ( $is_hub_menu ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="kmae-hub-menu-shell" style="width:min(100%,1180px);max-width:1180px;margin:0 auto;box-sizing:border-box;">
				<?php the_content(); ?>
			</div>
		<?php endwhile; ?>
	<?php elseif ( $is_listing_create ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php echo do_shortcode( (string) get_post_field( 'post_content', get_the_ID() ) ); ?>
		<?php endwhile; ?>
	<?php elseif ( $is_my_listings ) : ?>
		<?php /* Közvetlen shortcode (wpautop nélkül): a kártyák és a törlés-ablak rácsait
			a beszúrt <p>/<br> szétszedné — ahogy a hirdetésfeladásnál is. */ ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php echo do_shortcode( (string) get_post_field( 'post_content', get_the_ID() ) ); ?>
		<?php endwhile; ?>
	<?php else : ?>
		<div class="kmae-container">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'kmae-page__article' ); ?>>
					<h1 class="kmae-page__title"><?php the_title(); ?></h1>
					<div class="kmae-page__content">
						<?php
						if ( $is_raw_shortcode ) {
							echo do_shortcode( (string) get_post_field( 'post_content', get_the_ID() ) );
						} else {
							the_content();
						}
						?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
