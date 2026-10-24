<?php
/**
 * Header — PC full nav + mobile top bar (drawer + bottom nav in footer).
 *
 * @package KMAE
 */

$kmae_logged_in = is_user_logged_in();
/* Vendégnek is a belépett fejléc a böngészésen és a hirdetés-adatlapon (kmae_is_guest_app_view). */
$kmae_app_shell = function_exists( 'kmae_uses_app_shell' ) ? kmae_uses_app_shell() : $kmae_logged_in;
$kmae_user     = $kmae_logged_in ? wp_get_current_user() : null;
$kmae_name      = $kmae_user ? ( $kmae_user->display_name ?: $kmae_user->user_login ) : '';
$kmae_greeting  = $kmae_name
	? sprintf(
		/* translators: %s: user display name */
		__( 'Üdv, %s', 'kmae' ),
		$kmae_name
	)
	: __( 'Üdv', 'kmae' );
$kmae_initials  = '';
if ( $kmae_name ) {
	$parts = preg_split( '/\s+/u', trim( $kmae_name ) );
	if ( is_array( $parts ) && count( $parts ) >= 2 ) {
		$kmae_initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[1], 0, 1 ) );
	} else {
		$kmae_initials = mb_strtoupper( mb_substr( $kmae_name, 0, 2 ) );
	}
}
$kmae_path      = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
$kmae_unseen    = ( $kmae_logged_in && class_exists( 'KMAE_Database' ) )
	? (int) KMAE_Database::count_unread_conversations( get_current_user_id() )
	: 0;
$kmae_fav_unseen = ( $kmae_logged_in && class_exists( 'KMAE_Browse' ) )
	? (int) KMAE_Browse::count_unseen_favorites( get_current_user_id() )
	: 0;
/* Értesítések (csengő): csak ha a kereskedői megkeresés be van kapcsolva; a jelvény a cég válaszra váró megkeresései. */
$kmae_notif_on      = $kmae_logged_in && class_exists( 'KMAE_Dealer_Leads' ) && KMAE_Dealer_Leads::is_enabled();
$kmae_notif_pending = ( $kmae_notif_on && method_exists( 'KMAE_Dealer_Leads', 'bell_count' ) )
	? (int) KMAE_Dealer_Leads::bell_count( get_current_user_id() )
	: 0;
$kmae_nav_active = static function ( $slug ) use ( $kmae_path ) {
	return false !== strpos( $kmae_path, '/' . $slug );
};
/* Saját hirdetés megtekintésekor a „Hirdetéseim" marad a kiemelt menüpont:
   a hirdetés URL-je a /hirdetesek/ alá esik, ezért egyébként a „Böngészés"
   villanna fel, pedig a felhasználó a saját hirdetései felől érkezett. */
$kmae_own_listing = (
	is_singular( 'kmae_listing' )
	&& is_user_logged_in()
	&& (int) get_post_field( 'post_author', get_queried_object_id() ) === get_current_user_id()
);
$kmae_on_create_page      = $kmae_nav_active( 'hirdetes-feladasa' );
$kmae_create_listing_flow = function_exists( 'kmae_is_listing_create_nav_context' ) && kmae_is_listing_create_nav_context();
$kmae_edit_listing_nav    = $kmae_on_create_page && ! $kmae_create_listing_flow;
$kmae_guest_nav_active = static function ( $item ) use ( $kmae_nav_active ) {
	switch ( $item ) {
		case 'home':
			return is_front_page();
		case 'browse':
			return ( is_page( 'hirdetesek' ) || $kmae_nav_active( 'hirdetesek' ) ) && ! $kmae_nav_active( 'sajat-hirdetesek' );
		case 'kapcsolat':
			return is_page( 'kapcsolat' ) || $kmae_nav_active( 'kapcsolat' );
		default:
			return false;
	}
};
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-home="<?php echo esc_attr( trailingslashit( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' ) ); ?>">
<?php wp_body_open(); ?>

<a class="kmae-skip-link" href="#main"><?php esc_html_e( 'Ugrás a tartalomhoz', 'kmae' ); ?></a>

<header class="kmae-header<?php echo $kmae_app_shell ? ' kmae-pc-topbar' : ''; ?>" id="site-header">
	<div class="kmae-header__inner">
		<?php /* ---- Mobile / tablet top ---- */ ?>
		<div class="kmae-header__mobile-top">
			<?php if ( $kmae_logged_in ) : ?>
				<a class="kmae-logo kmae-logo--header kmae-logo--mobile kmae-logo--phone" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Albira', 'kmae' ); ?>">
					<?php kmae_logo_img(); ?>
				</a>

				<div class="kmae-phone-header__tools">
					<a
						class="kmae-header__icon-btn kmae-phone-header__bell"
						href="<?php echo esc_url( kmae_page_url( 'ertesitesek' ) ); ?>"
						aria-label="<?php echo esc_attr__( 'Értesítések', 'kmae' ); ?>"
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
							<path d="M18 10a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 22h4"/>
						</svg>
						<?php if ( $kmae_notif_on ) : ?>
							<span class="kmae-phone-header__badge" data-kmae-notif-badge="<?php echo esc_attr( $kmae_notif_pending ); ?>" aria-hidden="true"<?php echo $kmae_notif_pending > 0 ? '' : ' hidden'; ?>><?php echo esc_html( $kmae_notif_pending ); ?></span>
						<?php endif; ?>
					</a>
					<a
						class="kmae-header__icon-btn"
						href="<?php echo esc_url( kmae_page_url( 'uzenetek' ) ); ?>"
						aria-label="<?php echo esc_attr__( 'Üzenetek', 'kmae' ); ?>"
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
							<path d="M20 15a4 4 0 0 1-4 4H8l-4 3v-3a4 4 0 0 1-2-3.5V8a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M7 11h10M7 15h6"/>
						</svg>
						<?php if ( $kmae_unseen > 0 ) : ?>
							<span class="kmae-phone-header__dot" aria-hidden="true"></span>
						<?php endif; ?>
					</a>
					<a
						class="kmae-phone-header__avatar"
						href="<?php echo esc_url( kmae_page_url( 'profil' ) ); ?>"
						aria-label="<?php echo esc_attr( $kmae_name ?: __( 'Profil', 'kmae' ) ); ?>"
					>
						<?php echo esc_html( $kmae_initials ?: '??' ); ?>
					</a>
					<button
						type="button"
						class="kmae-header__icon-btn kmae-header__drawer-open"
						id="kmae-drawer-open"
						aria-expanded="false"
						aria-controls="kmae-drawer"
						aria-label="<?php echo esc_attr__( 'Menü megnyitása', 'kmae' ); ?>"
					>
						<span class="kmae-header__menu-bars" aria-hidden="true">
							<span></span><span></span><span></span>
						</span>
					</button>
				</div>
			<?php else : ?>
				<button
					type="button"
					class="kmae-header__icon-btn kmae-header__drawer-open"
					id="kmae-drawer-open"
					aria-expanded="false"
					aria-controls="kmae-drawer"
					aria-label="<?php echo esc_attr__( 'Menü megnyitása', 'kmae' ); ?>"
				>
					<span class="kmae-header__menu-bars" aria-hidden="true">
						<span></span><span></span><span></span>
					</span>
				</button>

				<a class="kmae-logo kmae-logo--header kmae-logo--mobile" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Albira', 'kmae' ); ?>">
					<?php kmae_logo_img(); ?>
				</a>

				<a class="kmae-header__icon-btn kmae-header__login-icon" href="<?php echo esc_url( kmae_page_url( 'belepes' ) ); ?>" aria-label="<?php echo esc_attr__( 'Belépés', 'kmae' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
						<circle cx="12" cy="7" r="4"/>
					</svg>
				</a>
			<?php endif; ?>
		</div>

		<?php /* ---- Desktop logo ---- */ ?>
		<a class="kmae-logo kmae-logo--header kmae-logo--desktop" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Albira', 'kmae' ); ?>">
			<?php kmae_logo_img(); ?>
		</a>

		<?php if ( $kmae_logged_in ) : ?>
			<a class="kmae-pc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Albira', 'kmae' ); ?>">
				<?php kmae_logo_img( 'kmae-pc-logo__img' ); ?>
			</a>

			<span class="kmae-header__sep" aria-hidden="true"></span>

			<nav class="kmae-header__nav kmae-header__nav--desktop kmae-pc-topnav" aria-label="<?php echo esc_attr__( 'Fő navigáció', 'kmae' ); ?>">
				<?php
				$kmae_header_nav = array(
					array(
						'label'  => __( 'Főoldal', 'kmae' ),
						'url'    => home_url( '/' ),
						'active' => is_front_page(),
						'icon'   => 'home',
					),
					array(
						'label'  => __( 'Hirdetés feladása', 'kmae' ),
						'url'    => kmae_page_url( 'hirdetes-feladasa' ),
						'active' => $kmae_on_create_page && $kmae_create_listing_flow,
						'icon'   => 'plus',
					),
					array(
						'label'  => __( 'Böngészés', 'kmae' ),
						'url'    => kmae_page_url( 'hirdetesek' ),
						'active' => ( $kmae_nav_active( 'hirdetesek' ) || $kmae_nav_active( 'venni' ) ) && ! $kmae_nav_active( 'sajat-hirdetesek' ) && ! $kmae_own_listing,
						'icon'   => 'search',
					),
					array(
						'label'  => __( 'Hirdetéseim', 'kmae' ),
						'url'    => kmae_page_url( 'sajat-hirdetesek' ),
						'active' => $kmae_nav_active( 'sajat-hirdetesek' ) || $kmae_own_listing || $kmae_edit_listing_nav,
						'icon'   => 'clipboard-list',
					),
				);
				/* Ikon-csoport (a profilnév mellett): Kedvencek, Üzenetek, Értesítések. */
				$kmae_header_icons = array(
					array(
						'label'      => __( 'Kedvencek', 'kmae' ),
						'url'        => kmae_page_url( 'kedvencek' ),
						'active'     => $kmae_nav_active( 'kedvencek' ),
						'icon'       => 'heart',
						'badge'      => (int) $kmae_fav_unseen,
						'badge_attr' => 'data-kmae-favorites-unseen',
					),
					array(
						'label'  => __( 'Üzenetek', 'kmae' ),
						'url'    => kmae_page_url( 'uzenetek' ),
						'active' => $kmae_nav_active( 'uzenetek' ),
						'icon'   => 'message-square',
						'badge'  => (int) $kmae_unseen,
					),
				);
				/* Értesítések (csengő): csak ha a kereskedői megkeresés be van kapcsolva; a jelvény a cég válaszra váró megkereséseit mutatja. */
				if ( $kmae_notif_on ) {
					$kmae_header_icons[] = array(
						'label'      => __( 'Értesítések', 'kmae' ),
						'url'        => kmae_page_url( 'ertesitesek' ),
						'active'     => $kmae_nav_active( 'ertesitesek' ),
						'icon'       => 'bell',
						'badge'      => $kmae_notif_pending,
						'badge_attr' => 'data-kmae-notif-badge',
					);
				}
				foreach ( $kmae_header_nav as $kmae_nav_item ) :
					?>
					<a
						class="kmae-header__nav-item<?php echo $kmae_nav_item['active'] ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $kmae_nav_item['url'] ); ?>"
					>
					<span class="kmae-header__nav-icon" aria-hidden="true">
						<?php
						if ( class_exists( 'KMAE_Icons' ) ) {
							echo KMAE_Icons::render( $kmae_nav_item['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</span>
					<span class="kmae-header__nav-label"><?php echo esc_html( $kmae_nav_item['label'] ); ?></span>
					<?php if ( isset( $kmae_nav_item['badge'] ) ) : ?>
						<?php $kmae_badge_attr = ! empty( $kmae_nav_item['badge_attr'] ) ? $kmae_nav_item['badge_attr'] : 'data-kmae-unseen'; ?>
						<span
							class="kmae-pc-badge"
							<?php echo esc_attr( $kmae_badge_attr ); ?>="<?php echo esc_attr( (int) $kmae_nav_item['badge'] ); ?>"
							<?php echo (int) $kmae_nav_item['badge'] > 0 ? '' : 'hidden'; ?>
						><?php echo esc_html( (int) $kmae_nav_item['badge'] ); ?></span>
					<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<span class="kmae-header__sep" aria-hidden="true"></span>

			<div class="kmae-header__tools kmae-header__tools--desktop kmae-pc-topbar-right">
				<div class="kmae-header__icons">
					<?php foreach ( $kmae_header_icons as $kmae_icon_item ) : ?>
						<a
							class="kmae-header__icon-link kmae-header__icon-link--<?php echo esc_attr( $kmae_icon_item['icon'] ); ?><?php echo $kmae_icon_item['active'] ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $kmae_icon_item['url'] ); ?>"
							aria-label="<?php echo esc_attr( $kmae_icon_item['label'] ); ?>"
							title="<?php echo esc_attr( $kmae_icon_item['label'] ); ?>"
						>
							<span class="kmae-header__nav-icon" aria-hidden="true">
								<?php
								if ( class_exists( 'KMAE_Icons' ) ) {
									echo KMAE_Icons::render( $kmae_icon_item['icon'], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</span>
							<span
								class="kmae-pc-badge"
								<?php echo esc_attr( ! empty( $kmae_icon_item['badge_attr'] ) ? $kmae_icon_item['badge_attr'] : 'data-kmae-unseen' ); ?>="<?php echo esc_attr( (int) $kmae_icon_item['badge'] ); ?>"
								<?php echo (int) $kmae_icon_item['badge'] > 0 ? '' : 'hidden'; ?>
							><?php echo esc_html( (int) $kmae_icon_item['badge'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<div class="kmae-header__dropdown kmae-header__dropdown--profile" data-kmae-dropdown>
					<button type="button" class="kmae-header__profile-btn" aria-expanded="false" aria-haspopup="true" aria-controls="kmae-panel-profile" aria-label="<?php echo esc_attr( $kmae_greeting ); ?>">
						<span class="kmae-header__profile-greeting"><?php echo esc_html( $kmae_greeting ); ?></span>
						<span class="kmae-header__caret" aria-hidden="true"></span>
					</button>
					<div class="kmae-header__panel kmae-header__panel--menu" id="kmae-panel-profile" hidden>
						<a href="<?php echo esc_url( kmae_page_url( 'profil' ) ); ?>"><?php esc_html_e( 'Profilom', 'kmae' ); ?></a>
						<a href="<?php echo esc_url( kmae_page_url( 'beallitasok' ) ); ?>"><?php esc_html_e( 'Beállítások', 'kmae' ); ?></a>
						<hr class="kmae-header__panel-sep" />
						<a class="kmae-header__logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Kijelentkezés', 'kmae' ); ?></a>
					</div>
				</div>
			</div>
		<?php elseif ( $kmae_app_shell ) : ?>
			<a class="kmae-pc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Albira', 'kmae' ); ?>">
				<?php kmae_logo_img( 'kmae-pc-logo__img' ); ?>
			</a>

			<span class="kmae-header__sep" aria-hidden="true"></span>

			<nav class="kmae-header__nav kmae-header__nav--desktop kmae-pc-topnav" aria-label="<?php echo esc_attr__( 'Fő navigáció', 'kmae' ); ?>">
				<?php
				$kmae_guest_app_nav = array(
					array( 'home', __( 'Főoldal', 'kmae' ), home_url( '/' ), 'home' ),
					array( 'browse', __( 'Autók böngészése', 'kmae' ), kmae_page_url( 'hirdetesek' ), 'search' ),
					array( 'kapcsolat', __( 'Kapcsolat', 'kmae' ), kmae_page_url( 'kapcsolat' ), 'mail' ),
				);
				foreach ( $kmae_guest_app_nav as $kmae_nav_item ) :
					?>
					<a
						class="kmae-header__nav-item<?php echo $kmae_guest_nav_active( $kmae_nav_item[0] ) ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $kmae_nav_item[2] ); ?>"
					>
					<span class="kmae-header__nav-icon" aria-hidden="true">
						<?php
						if ( class_exists( 'KMAE_Icons' ) ) {
							echo KMAE_Icons::render( $kmae_nav_item[3], array( 'size' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</span>
					<span class="kmae-header__nav-label"><?php echo esc_html( $kmae_nav_item[1] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<span class="kmae-header__sep" aria-hidden="true"></span>

			<div class="kmae-header__actions kmae-pc-topbar-right">
				<a class="kmae-btn kmae-btn--login kmae-btn--sm" href="<?php echo esc_url( kmae_page_url( 'belepes' ) ); ?>"><?php esc_html_e( 'Belépés', 'kmae' ); ?></a>
				<a class="kmae-btn kmae-btn--register kmae-btn--sm" href="<?php echo esc_url( kmae_page_url( 'regisztracio' ) ); ?>"><?php esc_html_e( 'Regisztráció', 'kmae' ); ?></a>
			</div>
		<?php else : ?>
			<nav class="kmae-header__nav kmae-header__nav--desktop kmae-header__nav--guest" data-kmae-guest-nav aria-label="<?php echo esc_attr__( 'Fő navigáció', 'kmae' ); ?>">
				<a data-nav="home" class="<?php echo $kmae_guest_nav_active( 'home' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Főoldal', 'kmae' ); ?></a>
				<a data-nav="browse" class="kmae-header__nav-browse<?php echo $kmae_guest_nav_active( 'browse' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( kmae_page_url( 'hirdetesek' ) ); ?>"><?php esc_html_e( 'Autók böngészése', 'kmae' ); ?></a>
				<a data-nav="kapcsolat" class="<?php echo $kmae_guest_nav_active( 'kapcsolat' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( kmae_page_url( 'kapcsolat' ) ); ?>"><?php esc_html_e( 'Kapcsolat', 'kmae' ); ?></a>
			</nav>
			<div class="kmae-header__actions kmae-header__actions--desktop">
				<a class="kmae-btn kmae-btn--login kmae-btn--sm" href="<?php echo esc_url( kmae_page_url( 'belepes' ) ); ?>"><?php esc_html_e( 'Belépés', 'kmae' ); ?></a>
				<a class="kmae-btn kmae-btn--register kmae-btn--sm" href="<?php echo esc_url( kmae_page_url( 'regisztracio' ) ); ?>"><?php esc_html_e( 'Regisztráció', 'kmae' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</header>

<?php /* Drawer — all devices under 1025px */ ?>
<div class="kmae-drawer-backdrop" id="kmae-drawer-backdrop" hidden></div>
<aside class="kmae-drawer<?php echo $kmae_logged_in ? ' kmae-drawer--sheet' : ''; ?>" id="kmae-drawer" hidden aria-hidden="true" aria-labelledby="kmae-drawer-title">
	<div class="kmae-drawer__head">
		<p class="kmae-drawer__brand" id="kmae-drawer-title"><?php echo $kmae_logged_in ? esc_html__( 'Menü', 'kmae' ) : esc_html__( 'Albira', 'kmae' ); ?></p>
		<button type="button" class="kmae-header__icon-btn" id="kmae-drawer-close" aria-label="<?php echo esc_attr__( 'Menü bezárása', 'kmae' ); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M18 6 6 18"/><path d="m6 6 12 12"/>
			</svg>
		</button>
	</div>
	<nav class="kmae-drawer__nav" aria-label="<?php echo esc_attr__( 'Mobil menü', 'kmae' ); ?>">
		<?php if ( $kmae_logged_in ) : ?>
			<?php get_template_part( 'template-parts/app-menu-panels' ); ?>
			<a class="kmae-drawer__logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Kijelentkezés', 'kmae' ); ?></a>
		<?php else : ?>
			<a href="<?php echo esc_url( kmae_page_url( 'gyik' ) ); ?>"><?php esc_html_e( 'Gyakori kérdések', 'kmae' ); ?></a>
			<a href="<?php echo esc_url( kmae_page_url( 'kapcsolat' ) ); ?>"><?php esc_html_e( 'Kapcsolat', 'kmae' ); ?></a>
		<?php endif; ?>
	</nav>
</aside>

<?php if ( $kmae_app_shell ) : ?>
	<div class="kmae-app-layout is-panels-closed">
		<div class="kmae-app-content">
<?php endif; ?>

<main id="main" class="kmae-main">
