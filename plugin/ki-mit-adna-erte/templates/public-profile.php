<?php
/**
 * Nyilvános profil.
 *
 * @package KMAE
 * @var int $history_user_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$history_user_id = (int) ( $history_user_id ?? 0 );
$profile_user    = $history_user_id ? get_userdata( $history_user_id ) : false;
$profile_name    = $profile_user ? trim( (string) $profile_user->display_name ) : '';
if ( '' === $profile_name || is_email( $profile_name ) ) {
	$profile_name = class_exists( 'KMAE_Auth' ) ? KMAE_Auth::first_name( $history_user_id ) : '';
}
$profile_type = class_exists( 'KMAE_Auth' ) ? KMAE_Auth::account_type_label( $history_user_id ) : '';
$is_self      = is_user_logged_in() && get_current_user_id() === $history_user_id;
/* Céges bemutatkozás — csak ha a cég kitöltötte (Profil → Bemutatkozás). */
$profile_intro = ( $history_user_id && class_exists( 'KMAE_Company' ) ) ? KMAE_Company::get_intro( $history_user_id ) : '';
?>
<article class="kmae-public-profile">
	<?php if ( $profile_name ) : ?>
		<h1 class="kmae-public-profile__name"><?php echo esc_html( $profile_name ); ?></h1>
	<?php endif; ?>
	<?php if ( $profile_type ) : ?>
		<p class="kmae-public-profile__type"><?php echo esc_html( $profile_type ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $profile_intro ) : ?>
		<section class="kmae-public-profile__intro">
			<h2 class="kmae-public-profile__intro-title">
				<span class="kmae-public-profile__intro-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'message-square', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Bemutatkozás', 'ki-mit-adna-erte' ); ?>
			</h2>
			<div class="kmae-public-profile__intro-text"><?php echo wp_kses_post( wpautop( esc_html( $profile_intro ) ) ); ?></div>
		</section>
	<?php endif; ?>

	<?php if ( $is_self ) : ?>
		<p class="kmae-public-profile__edit">
			<a href="<?php echo esc_url( KMAE_Pages::url( 'profil' ) ); ?>"><?php esc_html_e( 'Saját adatok szerkesztése', 'ki-mit-adna-erte' ); ?></a>
		</p>
	<?php endif; ?>
</article>
