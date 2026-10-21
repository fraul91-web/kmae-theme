<?php
/**
 * Front page — vendégnek a marketing landing, bejelentkezve az app főoldal.
 *
 * @package KMAE
 */

get_header();

if ( is_user_logged_in() ) {
	get_template_part( 'template-parts/simple-mode/app', 'home' );
} else {
	get_template_part( 'template-parts/simple-mode/landing' );
}

get_footer();
