<?php
/**
 * Archive template — redirect to browse wizard on front page.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="kmae-page-wrap">
	<?php echo do_shortcode( '[kmae_browse]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php
get_footer();
