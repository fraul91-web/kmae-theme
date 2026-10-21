<?php
/**
 * Main fallback template.
 *
 * @package KMAE
 */

get_header();
?>

<section class="kmae-page">
	<div class="kmae-container">
		<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
				<article <?php post_class('kmae-page__article'); ?>>
					<h1 class="kmae-page__title"><?php the_title(); ?></h1>
					<div class="kmae-page__content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e('Nincs megjeleníthető tartalom.', 'kmae'); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
