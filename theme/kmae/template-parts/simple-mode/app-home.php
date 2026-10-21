<?php
/**
 * Simple mode — logged-in app home (copy of full mode).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'KMAE_Dashboard' ) ) {
	$user = wp_get_current_user();
	$name = $user->display_name ?: $user->user_login;
	?>
	<section class="kmae-app-home">
		<div class="kmae-container">
			<h1 class="kmae-app-home__title">
				<?php
				printf(
					esc_html__( 'Szia, %s!', 'kmae' ),
					esc_html( $name )
				);
				?>
			</h1>
			<p class="kmae-app-home__lead"><?php esc_html_e( 'A dashboardhoz frissítsd a bővítményt.', 'kmae' ); ?></p>
		</div>
	</section>
	<?php
	return;
}

$dash = KMAE_Dashboard::get_data( get_current_user_id() );
include KMAE_PLUGIN_DIR . 'templates/partials/dashboard-home.php';
