<?php
/**
 * Profil — fejléc a fiók azonosságával és adatlap.
 *
 * A saját osztálynevek (`kmae-profile-*`) szándékosak: a régi, közös
 * `kmae-hint` / `kmae-badge` osztályokat a plugin alapstílusa fehérre festi,
 * ami a világos üveg felületen olvashatatlan. Az oldal így a saját,
 * egyetlen stílusforrásából él (theme/assets/css/profile.css).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user    = wp_get_current_user();
$phone   = get_user_meta( $user->ID, 'kmae_phone', true );
$profile = KMAE_Auth::get_profile( $user->ID );
$success = isset( $_GET['kmae_success'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_success'] ) ) : '';
$error   = isset( $_GET['kmae_error'] ) ? sanitize_text_field( wp_unslash( $_GET['kmae_error'] ) ) : '';

$profile_url = get_permalink();
$is_company  = KMAE_Auth::is_company( $user->ID );
$account     = KMAE_Auth::account_type_label( $user->ID );

$profile_name = $is_company ? KMAE_Company::get_company_name( $user->ID ) : $user->display_name;
$profile_name = $profile_name ? $profile_name : $user->user_login;

/* Monogram a névből — ugyanaz a logika, mint a fejléc avatarjánál. */
$initials = '';
$parts    = preg_split( '/\s+/u', trim( (string) $profile_name ) );
if ( is_array( $parts ) && count( $parts ) >= 2 ) {
	$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[1], 0, 1 ) );
} else {
	$initials = mb_strtoupper( mb_substr( (string) $profile_name, 0, 2 ) );
}

/* Teljes dátum, magyar alakban: „2026. szeptember 16." */
$member_since = $user->user_registered
	? date_i18n( 'Y. F j.', strtotime( $user->user_registered ) )
	: '';

/* Visszajelzések: egy helyen, egy formában. */
$alerts   = array();
$messages = array(
	'success' => array(
		'profile_saved'      => __( 'Profil mentve.', 'ki-mit-adna-erte' ),
		'email_verified'     => __( 'E-mail cím megerősítve — mehet a hirdetésfeladás.', 'ki-mit-adna-erte' ),
		'verification_sent'  => __( 'Elküldtük a megerősítő levelet — nézd meg a postaládád.', 'ki-mit-adna-erte' ),
		'already_verified'   => __( 'Ez az e-mail cím már megerősítve.', 'ki-mit-adna-erte' ),
	),
	'error'   => array(
		'nonce_failed'          => __( 'A mentés nem sikerült — frissítsd az oldalt és próbáld újra.', 'ki-mit-adna-erte' ),
	'company_name_required' => __( 'Add meg a cégnevet.', 'ki-mit-adna-erte' ),
	'company_location_required' => __( 'Add meg a céges telephelyet.', 'ki-mit-adna-erte' ),
		'phone_invalid'         => __( 'A telefonszám kilenc számjegy, például 30 111 11 11.', 'ki-mit-adna-erte' ),
		'delete_confirm_failed' => __( 'A törléshez írd be pontosan: TÖRLÖM.', 'ki-mit-adna-erte' ),
		'delete_admin_blocked'  => __( 'Adminisztrátori fiók itt nem törölhető.', 'ki-mit-adna-erte' ),
		'delete_failed'         => __( 'A profil törlése nem sikerült — próbáld újra, vagy írj nekünk.', 'ki-mit-adna-erte' ),
		'invalid_token'         => __( 'Érvénytelen vagy már felhasznált megerősítő link.', 'ki-mit-adna-erte' ),
		'expired_token'         => __( 'A link lejárt — kérj újat lent.', 'ki-mit-adna-erte' ),
	),
);
if ( $success && isset( $messages['success'][ $success ] ) ) {
	$alerts[] = array(
		'tone' => 'ok',
		'icon' => 'circle-check',
		'text' => $messages['success'][ $success ],
	);
}
if ( $error && isset( $messages['error'][ $error ] ) ) {
	$alerts[] = array(
		'tone' => 'warn',
		'icon' => 'triangle-alert',
		'text' => $messages['error'][ $error ],
	);
}

/* Hitelesítési állapotok — a fejlécben, jelvényként. */
/* A telefonszámnál nincs SMS-ellenőrzés, és nem is tervezünk — ezért itt
   nem „megerősítve", hanem egyszerűen a szám megléte a jó hír. */
$checks = array(
	array(
		'key'        => 'email',
		'done'       => ! empty( $profile->email_verified ),
		'icon'       => 'mail',
		'label_done' => __( 'E-mail megerősítve', 'ki-mit-adna-erte' ),
		'label_todo' => __( 'E-mail nincs megerősítve — Küldd újra', 'ki-mit-adna-erte' ),
	),
	array(
		'key'        => 'phone',
		'done'       => '' !== trim( (string) $phone ),
		'icon'       => 'smartphone',
		'label_done' => __( 'Telefonszám hozzáadva', 'ki-mit-adna-erte' ),
		'label_todo' => __( 'Telefonszám hiányzik', 'ki-mit-adna-erte' ),
	),
);

/* Profil kitöltöttsége — a fejléc gyűrűje ebből számol. */
$completion = array(
	'' !== trim( (string) $profile_name ),
	! empty( $profile->email_verified ),
	'' !== trim( (string) $phone ),
);
if ( $is_company ) {
	$completion[] = '' !== trim( (string) get_user_meta( $user->ID, KMAE_Company::META_COMPANY_INTRO, true ) );
	$completion[] = '' !== trim( (string) KMAE_Company::get_address( $user->ID ) );
}
$completion_pct = (int) round( 100 * count( array_filter( $completion ) ) / max( 1, count( $completion ) ) );

$sold_count = class_exists( 'KMAE_Chat_Offers' ) ? KMAE_Chat_Offers::count_sold_for_seller( $user->ID ) : 0;
$days_in    = $user->user_registered ? max( 1, (int) floor( ( time() - strtotime( $user->user_registered ) ) / DAY_IN_SECONDS ) + 1 ) : 0;

/* Gyors elérés a fiókhoz tartozó oldalakra. */
$shortcuts = array(
	array(
		'url'   => KMAE_Pages::url( 'sajat-hirdetesek' ),
		'icon'  => 'car',
		'title' => __( 'Hirdetéseim', 'ki-mit-adna-erte' ),
		'sub'   => __( 'Feladott autóid egy helyen', 'ki-mit-adna-erte' ),
		'tone'  => 'blue',
	),
	array(
		'url'   => KMAE_Pages::messages_page_url(),
		'icon'  => 'message-square',
		'title' => __( 'Üzenetek', 'ki-mit-adna-erte' ),
		'sub'   => __( 'Beszélgetéseid a vevőkkel, eladókkal', 'ki-mit-adna-erte' ),
		'tone'  => 'orange',
	),
	array(
		'url'   => KMAE_Pages::public_profile_url( $user->ID ),
		'icon'  => 'eye',
		'title' => __( 'Nyilvános profilom', 'ki-mit-adna-erte' ),
		'sub'   => __( 'Így látnak a többiek', 'ki-mit-adna-erte' ),
		'tone'  => 'blue',
	),
	array(
		'url'   => KMAE_Pages::url( 'beallitasok' ),
		'icon'  => 'settings',
		'title' => __( 'Beállítások', 'ki-mit-adna-erte' ),
		'sub'   => __( 'Jelszó, e-mail, értesítések', 'ki-mit-adna-erte' ),
		'tone'  => 'orange',
	),
);


$icon = static function ( $name, $size = 18 ) {
	return class_exists( 'KMAE_Icons' ) ? KMAE_Icons::render( $name, array( 'size' => $size ) ) : '';
};
?>
<div class="kmae-profile"<?php if ( empty( $profile->email_verified ) ) : ?> data-kmae-live-page="email_verify" data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'email_verify' ) ); ?>"<?php endif; ?>>

	<?php if ( $alerts ) : ?>
		<div class="kmae-profile__alerts">
			<?php foreach ( $alerts as $alert ) : ?>
				<p class="kmae-profile-alert kmae-profile-alert--<?php echo esc_attr( $alert['tone'] ); ?>">
					<span class="kmae-profile-alert__icon" aria-hidden="true"><?php echo $icon( $alert['icon'], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( $alert['text'] ); ?></span>
				</p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<header class="kmae-profile-hero">
		<span class="kmae-profile-hero__glow" aria-hidden="true"></span>

		<div class="kmae-profile-hero__main">
			<span class="kmae-profile-hero__avatar" aria-hidden="true">
				<span class="kmae-profile-hero__initials"><?php echo esc_html( $initials ?: '??' ); ?></span>
				<?php if ( ! empty( $profile->email_verified ) ) : ?>
					<span class="kmae-profile-hero__verified"><?php echo $icon( 'check', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
			</span>

			<div class="kmae-profile-hero__id">
				<p class="kmae-profile-hero__eyebrow"><?php esc_html_e( 'A fiókom', 'ki-mit-adna-erte' ); ?></p>
				<h2 class="kmae-profile-hero__name"><?php echo esc_html( $profile_name ); ?></h2>

				<p class="kmae-profile-hero__tags">
					<span class="kmae-profile-tag kmae-profile-tag--type">
						<?php echo $icon( $is_company ? 'users' : 'user', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo esc_html( $account ); ?>
					</span>

					<?php foreach ( $checks as $check ) : ?>
						<?php if ( 'email' === $check['key'] && ! $check['done'] ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-profile-tag-form">
								<?php wp_nonce_field( 'kmae_resend_verification', 'kmae_resend_nonce' ); ?>
								<input type="hidden" name="action" value="kmae_resend_verification" />
								<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $profile_url ); ?>" />
								<button type="submit" class="kmae-profile-tag kmae-profile-tag--todo kmae-profile-tag--action" title="<?php esc_attr_e( 'Kattints a megerősítő levél újraküldéséhez', 'ki-mit-adna-erte' ); ?>">
									<?php echo $icon( 'mail', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo esc_html( $check['label_todo'] ); ?>
								</button>
							</form>
						<?php else : ?>
							<span class="kmae-profile-tag kmae-profile-tag--<?php echo $check['done'] ? 'ok' : 'todo'; ?>">
								<?php echo $icon( $check['done'] ? 'circle-check' : $check['icon'], 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo esc_html( $check['done'] ? $check['label_done'] : $check['label_todo'] ); ?>
							</span>
						<?php endif; ?>
					<?php endforeach; ?>
				</p>
			</div>

			<div class="kmae-profile-ring" style="--pf-pct: <?php echo esc_attr( (string) $completion_pct ); ?>;" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: percent */ __( 'A profilod %d%%-ban kész', 'ki-mit-adna-erte' ), $completion_pct ) ); ?>">
				<span class="kmae-profile-ring__value"><?php echo esc_html( $completion_pct ); ?><small>%</small></span>
				<span class="kmae-profile-ring__label"><?php echo 100 === $completion_pct ? esc_html__( 'Teljes profil', 'ki-mit-adna-erte' ) : esc_html__( 'Profil kész', 'ki-mit-adna-erte' ); ?></span>
			</div>

		</div>

		<ul class="kmae-profile-stats">
			<li class="kmae-profile-stat">
				<span class="kmae-profile-stat__icon"><?php echo $icon( 'calendar', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-profile-stat__copy">
					<strong><?php echo esc_html( $member_since ? $member_since : '—' ); ?></strong>
					<span><?php esc_html_e( 'Ekkor csatlakoztál', 'ki-mit-adna-erte' ); ?></span>
				</span>
			</li>
			<li class="kmae-profile-stat">
				<span class="kmae-profile-stat__icon"><?php echo $icon( 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-profile-stat__copy">
					<strong>
						<?php
						/* translators: %s: number of days */
						echo esc_html( sprintf( __( '%s napja', 'ki-mit-adna-erte' ), number_format_i18n( $days_in ) ) );
						?>
					</strong>
					<span><?php esc_html_e( 'vagy velünk', 'ki-mit-adna-erte' ); ?></span>
				</span>
			</li>
			<li class="kmae-profile-stat kmae-profile-stat--orange">
				<span class="kmae-profile-stat__icon"><?php echo $icon( 'handshake', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="kmae-profile-stat__copy">
					<strong><?php echo esc_html( number_format_i18n( $sold_count ) ); ?></strong>
					<span><?php esc_html_e( 'eladott autó', 'ki-mit-adna-erte' ); ?></span>
				</span>
			</li>
		</ul>
	</header>

	<div class="kmae-profile__grid">

		<section class="kmae-profile-panel kmae-profile-panel--form">
			<header class="kmae-profile-panel__head">
				<span class="kmae-profile-panel__mark" aria-hidden="true"><?php echo $icon( 'pencil', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<p class="kmae-profile-panel__eyebrow"><?php esc_html_e( 'Szerkesztés', 'ki-mit-adna-erte' ); ?></p>
					<h3 class="kmae-profile-panel__title"><?php esc_html_e( 'Adataim', 'ki-mit-adna-erte' ); ?></h3>
				</div>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-profile-form">
				<?php wp_nonce_field( 'kmae_save_profile', 'kmae_profile_nonce' ); ?>
				<input type="hidden" name="action" value="kmae_save_profile" />
				<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $profile_url ); ?>" />

				<div class="kmae-profile-field">
					<label for="kmae_display_name"><?php echo $is_company ? esc_html__( 'Cégnév', 'ki-mit-adna-erte' ) : esc_html__( 'Megjelenített név', 'ki-mit-adna-erte' ); ?></label>
					<div class="kmae-profile-input">
						<span class="kmae-profile-input__icon" aria-hidden="true"><?php echo $icon( $is_company ? 'users' : 'user', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php if ( $is_company ) : ?>
							<input type="hidden" name="kmae_display_name" value="<?php echo esc_attr( KMAE_Company::get_company_name( $user->ID ) ); ?>" />
							<input type="text" name="kmae_company_name" id="kmae_display_name" value="<?php echo esc_attr( KMAE_Company::get_company_name( $user->ID ) ); ?>" required />
						<?php else : ?>
							<input type="text" name="kmae_display_name" id="kmae_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required />
						<?php endif; ?>
					</div>
					<p class="kmae-profile-note">
						<?php
						echo $is_company
							? esc_html__( 'Ez a név jelenik meg a kereskedői profilodon és a hirdetéseiden.', 'ki-mit-adna-erte' )
							: esc_html__( 'Ezen a néven látnak a vevők és az eladók.', 'ki-mit-adna-erte' );
						?>
					</p>
				</div>

				<div class="kmae-profile-field">
					<label for="kmae_email_readonly"><?php esc_html_e( 'E-mail cím', 'ki-mit-adna-erte' ); ?></label>
					<div class="kmae-profile-input is-readonly">
						<span class="kmae-profile-input__icon" aria-hidden="true"><?php echo $icon( 'mail', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="email" id="kmae_email_readonly" value="<?php echo esc_attr( $user->user_email ); ?>" readonly tabindex="-1" />
					</div>
					<p class="kmae-profile-note">
						<?php esc_html_e( 'Az e-mail címedet a', 'ki-mit-adna-erte' ); ?>
						<a href="<?php echo esc_url( KMAE_Pages::url( 'beallitasok' ) ); ?>"><?php esc_html_e( 'Beállításokban', 'ki-mit-adna-erte' ); ?></a>
						<?php esc_html_e( 'módosíthatod.', 'ki-mit-adna-erte' ); ?>
					</p>
				</div>

				<?php if ( $is_company ) : ?>
					<?php $company_intro = (string) get_user_meta( $user->ID, KMAE_Company::META_COMPANY_INTRO, true ); ?>
					<div class="kmae-profile-field kmae-profile-intro">
						<label for="kmae_company_intro"><?php esc_html_e( 'Bemutatkozás', 'ki-mit-adna-erte' ); ?></label>
						<textarea name="kmae_company_intro" id="kmae_company_intro" rows="5" maxlength="<?php echo esc_attr( (string) KMAE_Company::INTRO_MAX ); ?>" placeholder="<?php esc_attr_e( 'Pl. Mióta foglalkoztok autókkal, mire figyeltek az átadásnál, adtok-e garanciát, hogyan lehet megtekinteni az autókat…', 'ki-mit-adna-erte' ); ?>"><?php echo esc_textarea( $company_intro ); ?></textarea>
						<p class="kmae-profile-note">
							<?php esc_html_e( 'A hirdetéseid oldalán a vevők elolvashatják. Ha üresen hagyod, nem jelenik meg.', 'ki-mit-adna-erte' ); ?>
							<span class="kmae-profile-intro__count" data-kmae-intro-count><?php echo esc_html( ( function_exists( 'mb_strlen' ) ? mb_strlen( $company_intro ) : strlen( $company_intro ) ) . ' / ' . KMAE_Company::INTRO_MAX ); ?></span>
						</p>
						<script>
						(function () {
							var area = document.getElementById('kmae_company_intro');
							var out = document.querySelector('[data-kmae-intro-count]');
							if (!area || !out) { return; }
							area.addEventListener('input', function () {
								out.textContent = area.value.length + ' / ' + area.maxLength;
							});
						}());
						</script>
					</div>

					<p class="kmae-profile-subhead">
						<?php echo $icon( 'map-pin', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Telephely', 'ki-mit-adna-erte' ); ?>
					</p>
					<div class="kmae-profile-field-row">
						<div class="kmae-profile-field">
							<label for="kmae_company_county"><?php esc_html_e( 'Telephely megyéje *', 'ki-mit-adna-erte' ); ?></label>
							<select name="kmae_company_county" id="kmae_company_county" required>
								<option value=""><?php esc_html_e( 'Válassz megyét', 'ki-mit-adna-erte' ); ?></option>
								<?php foreach ( KMAE_Counties::get_all() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( KMAE_Company::get_county( $user->ID ), $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="kmae-profile-field">
							<label for="kmae_company_city"><?php esc_html_e( 'Telephely városa *', 'ki-mit-adna-erte' ); ?></label>
							<input type="text" name="kmae_company_city" id="kmae_company_city" value="<?php echo esc_attr( KMAE_Company::get_city( $user->ID ) ); ?>" required />
						</div>
					</div>
					<div class="kmae-profile-field">
						<label for="kmae_company_address"><?php esc_html_e( 'Cím *', 'ki-mit-adna-erte' ); ?></label>
						<input type="text" name="kmae_company_address" id="kmae_company_address" value="<?php echo esc_attr( KMAE_Company::get_address( $user->ID ) ); ?>" placeholder="<?php esc_attr_e( 'utca, házszám', 'ki-mit-adna-erte' ); ?>" required />
					</div>
				<?php endif; ?>

				<div class="kmae-profile-field">
					<label for="kmae_phone"><?php esc_html_e( 'Telefonszám', 'ki-mit-adna-erte' ); ?></label>
					<?php
					/* A mezőbe csak a kilenc számjegy megy; a +36 előtag mellette
					   áll fixen, ezért a tárolt értékből levágjuk. */
					$phone_local = preg_replace( '/^\+?36\s*/', '', (string) $phone );
					?>
					<div class="kmae-phone-input">
						<span class="kmae-phone-input__prefix" aria-hidden="true">+36</span>
						<input
							type="tel"
							name="kmae_phone"
							id="kmae_phone"
							value="<?php echo esc_attr( $phone_local ); ?>"
							data-kmae-phone
							inputmode="numeric"
							autocomplete="tel-national"
							maxlength="12"
							placeholder="30 111 11 11"
						/>
					</div>
					<p class="kmae-profile-note">
						<span class="kmae-profile-note__lock" aria-hidden="true"><?php echo $icon( 'lock', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php esc_html_e( 'Privát.', 'ki-mit-adna-erte' ); ?>
					</p>
				</div>

				<div class="kmae-profile-form__actions">
					<button type="submit" class="kmae-profile-btn kmae-profile-btn--accent">
						<?php echo $icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Mentés', 'ki-mit-adna-erte' ); ?>
					</button>
				</div>
			</form>
		</section>

		<aside class="kmae-profile__side">
			<section class="kmae-profile-panel kmae-profile-panel--links">
				<header class="kmae-profile-panel__head">
					<span class="kmae-profile-panel__mark" aria-hidden="true"><?php echo $icon( 'zap', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div>
						<p class="kmae-profile-panel__eyebrow"><?php esc_html_e( 'Gyors elérés', 'ki-mit-adna-erte' ); ?></p>
						<h3 class="kmae-profile-panel__title"><?php esc_html_e( 'A fiókod oldalai', 'ki-mit-adna-erte' ); ?></h3>
					</div>
				</header>

				<ul class="kmae-profile-links">
					<?php foreach ( $shortcuts as $shortcut ) : ?>
						<li>
							<a class="kmae-profile-link kmae-profile-link--<?php echo esc_attr( $shortcut['tone'] ); ?>" href="<?php echo esc_url( $shortcut['url'] ); ?>">
								<span class="kmae-profile-link__icon" aria-hidden="true"><?php echo $icon( $shortcut['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="kmae-profile-link__copy">
									<strong><?php echo esc_html( $shortcut['title'] ); ?></strong>
									<span><?php echo esc_html( $shortcut['sub'] ); ?></span>
								</span>
								<span class="kmae-profile-link__arrow" aria-hidden="true"><?php echo $icon( 'chevron-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<?php if ( ! current_user_can( 'manage_options' ) ) : ?>
			<section class="kmae-profile-panel kmae-profile-danger">
				<details class="kmae-profile-danger__details">
					<summary class="kmae-profile-danger__summary">
						<span class="kmae-profile-danger__icon" aria-hidden="true"><?php echo $icon( 'trash', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'Profilom törlése', 'ki-mit-adna-erte' ); ?></span>
						<span class="kmae-profile-danger__chevron" aria-hidden="true"><?php echo $icon( 'chevron-right', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kmae-profile-danger__form">
						<?php wp_nonce_field( 'kmae_delete_account', 'kmae_delete_account_nonce' ); ?>
						<input type="hidden" name="action" value="kmae_delete_account" />
						<input type="hidden" name="kmae_redirect" value="<?php echo esc_url( $profile_url ); ?>" />
						<p class="kmae-profile-danger__text"><?php esc_html_e( 'A törlés végleges: a profilod, a hirdetéseid (képekkel együtt), a beszélgetéseid és az ajánlataid is megszűnnek, és nem állíthatók vissza.', 'ki-mit-adna-erte' ); ?></p>
						<div class="kmae-profile-field">
							<label for="kmae_delete_confirm"><?php esc_html_e( 'A megerősítéshez írd be: TÖRLÖM', 'ki-mit-adna-erte' ); ?></label>
							<input type="text" name="kmae_delete_confirm" id="kmae_delete_confirm" autocomplete="off" required />
						</div>
						<div class="kmae-profile-form__actions">
							<button type="submit" class="kmae-profile-btn kmae-profile-btn--danger"><?php esc_html_e( 'Profilom végleges törlése', 'ki-mit-adna-erte' ); ?></button>
						</div>
					</form>
				</details>
			</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
