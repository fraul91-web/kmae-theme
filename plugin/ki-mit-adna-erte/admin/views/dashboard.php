<?php
/**
 * Admin dashboard view.
 *
 * @package KMAE
 * @var object $listings_count
 * @var int    $active
 * @var array  $users_count
 * @var array  $recent
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listings_url = admin_url( 'edit.php?post_type=kmae_listing' );
$new_url      = admin_url( 'post-new.php?post_type=kmae_listing' );
?>
<div class="wrap kmae-dashboard">
	<h1><?php esc_html_e( 'Albira — Áttekintés', 'ki-mit-adna-erte' ); ?></h1>

	<p class="kmae-tagline"><?php echo esc_html( KMAE_Launch::site_tagline() ); ?></p>

	<?php if ( in_array( $notice, array( 'listing_deleted', 'status_updated', 'bulk_done' ), true ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Művelet sikeres.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php elseif ( 'delete_failed' === $notice ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'A művelet sikertelen.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php endif; ?>

	<div class="kmae-admin-quick-actions">
		<a href="<?php echo esc_url( $listings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Hirdetések kezelése', 'ki-mit-adna-erte' ); ?></a>
		<a href="<?php echo esc_url( $new_url ); ?>" class="button"><?php esc_html_e( 'Új hirdetés (admin)', 'ki-mit-adna-erte' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=kmae-settings' ) ); ?>" class="button"><?php esc_html_e( 'Beállítások', 'ki-mit-adna-erte' ); ?></a>
	</div>

	<div class="kmae-stats">
		<div class="kmae-stat-card">
			<span class="kmae-stat-number"><?php echo esc_html( $listings_count->publish ?? 0 ); ?></span>
			<span class="kmae-stat-label"><?php esc_html_e( 'Összes hirdetés', 'ki-mit-adna-erte' ); ?></span>
		</div>
		<div class="kmae-stat-card">
			<span class="kmae-stat-number"><?php echo esc_html( $active ); ?></span>
			<span class="kmae-stat-label"><?php esc_html_e( 'Aktív hirdetés', 'ki-mit-adna-erte' ); ?></span>
		</div>
		<div class="kmae-stat-card">
			<span class="kmae-stat-number"><?php echo esc_html( $users_count['total_users'] ?? 0 ); ?></span>
			<span class="kmae-stat-label"><?php esc_html_e( 'Felhasználók', 'ki-mit-adna-erte' ); ?></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Legutóbbi hirdetések', 'ki-mit-adna-erte' ); ?></h2>
	<table class="widefat striped kmae-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Cím', 'ki-mit-adna-erte' ); ?></th>
				<?php if ( KMAE_Vehicle::is_vehicle_launch() ) : ?>
					<th><?php esc_html_e( 'Autó', 'ki-mit-adna-erte' ); ?></th>
				<?php endif; ?>
				<th><?php esc_html_e( 'Felhasználó', 'ki-mit-adna-erte' ); ?></th>
				<th><?php esc_html_e( 'Státusz', 'ki-mit-adna-erte' ); ?></th>
				<th><?php esc_html_e( 'Műveletek', 'ki-mit-adna-erte' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $recent ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Még nincs hirdetés.', 'ki-mit-adna-erte' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $recent as $listing ) : ?>
					<?php
					$user = get_userdata( (int) $listing->post_author );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></strong>
						</td>
						<?php if ( KMAE_Vehicle::is_vehicle_launch() ) : ?>
							<td><?php echo esc_html( KMAE_Vehicle::get_swipe_subtitle( $listing->ID ) ?: '—' ); ?></td>
						<?php endif; ?>
						<td><?php echo esc_html( $user ? $user->display_name : '—' ); ?></td>
						<td><?php echo wp_kses_post( KMAE_Admin::status_badge( KMAE_Meta::get_status( $listing->ID ) ) ); ?></td>
						<td class="kmae-admin-actions">
							<a href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>" class="button button-small"><?php esc_html_e( 'Szerkesztés', 'ki-mit-adna-erte' ); ?></a>
							<a href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>" class="button button-small" target="_blank" rel="noopener"><?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?></a>
							<a href="<?php echo esc_url( KMAE_Admin::admin_action_url( 'kmae_admin_delete_listing', array( 'listing_id' => $listing->ID, 'redirect' => rawurlencode( admin_url( 'admin.php?page=kmae-dashboard' ) ) ) ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Biztosan törlöd?', 'ki-mit-adna-erte' ) ); ?>');"><?php esc_html_e( 'Törlés', 'ki-mit-adna-erte' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo esc_url( $listings_url ); ?>"><?php esc_html_e( 'Összes hirdetés', 'ki-mit-adna-erte' ); ?></a></p>

	<h2><?php esc_html_e( 'Frontend oldalak (shortcode-ok)', 'ki-mit-adna-erte' ); ?></h2>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Oldal', 'ki-mit-adna-erte' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'ki-mit-adna-erte' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr><td><?php esc_html_e( 'Bejelentkezés', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_login]</code></td></tr>
			<tr><td><?php esc_html_e( 'Regisztráció', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_register]</code></td></tr>
			<tr><td><?php esc_html_e( 'Főoldal hero (USP)', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_hero]</code></td></tr>
			<tr><td><?php esc_html_e( 'Autó hirdetés feladása', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_create_listing]</code></td></tr>
			<tr><td><?php esc_html_e( 'Böngészés (Tinder swipe)', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_browse]</code></td></tr>
			<tr><td><?php esc_html_e( 'Saját hirdetéseim', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_my_listings]</code></td></tr>
			<tr><td><?php esc_html_e( 'Profil', 'ki-mit-adna-erte' ); ?></td><td><code>[kmae_profile]</code></td></tr>
		</tbody>
	</table>
</div>
