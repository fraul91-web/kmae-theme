<?php
/**
 * Messages inbox: every conversation of the viewer, newest first.
 *
 * @package KMAE
 * @var array $inbox Rows from KMAE_Conversations::get_inbox_for_user().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inbox = isset( $inbox ) && is_array( $inbox ) ? $inbox : array();
$today = wp_date( 'Y-m-d' );

$inbox_success = isset( $_GET['kmae_success'] ) ? sanitize_key( wp_unslash( $_GET['kmae_success'] ) ) : '';
$inbox_error   = isset( $_GET['kmae_error'] ) ? sanitize_key( wp_unslash( $_GET['kmae_error'] ) ) : '';
?>
<div class="kmae-inbox"
	data-kmae-live-page="messages_inbox"
	data-kmae-live-fingerprint="<?php echo esc_attr( KMAE_Live_Page::get_fingerprint( 'messages_inbox' ) ); ?>">
	<?php
	$inbox_total  = count( $inbox );
	$inbox_unread = count( array_filter( $inbox, static function ( $row ) {
		return ! empty( $row['unread'] );
	} ) );
	?>
	<header class="kmae-inbox__hero">
		<span class="kmae-inbox__hero-icon" aria-hidden="true">
			<?php echo KMAE_Icons::render( 'message-square', array( 'size' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
		<div class="kmae-inbox__hero-text">
			<span class="kmae-inbox__eyebrow"><?php esc_html_e( 'Beszélgetéseid', 'ki-mit-adna-erte' ); ?></span>
			<h1 class="kmae-inbox__title"><?php esc_html_e( 'Üzenetek', 'ki-mit-adna-erte' ); ?></h1>
		</div>
		<?php if ( $inbox_total ) : ?>
			<div class="kmae-inbox__stats">
				<span class="kmae-inbox__stat">
					<strong><?php echo esc_html( number_format_i18n( $inbox_total ) ); ?></strong>
					<span><?php esc_html_e( 'beszélgetés', 'ki-mit-adna-erte' ); ?></span>
				</span>
				<span class="kmae-inbox__stat kmae-inbox__stat--unread<?php echo $inbox_unread ? ' is-active' : ''; ?>">
					<strong><?php echo esc_html( number_format_i18n( $inbox_unread ) ); ?></strong>
					<span><?php esc_html_e( 'olvasatlan', 'ki-mit-adna-erte' ); ?></span>
				</span>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( 'conversation_hidden' === $inbox_success ) : ?>
		<p class="kmae-inbox__flash"><?php esc_html_e( 'A beszélgetést törölted. A másik félnél megmarad.', 'ki-mit-adna-erte' ); ?></p>
	<?php elseif ( 'conversation_locked' === $inbox_error ) : ?>
		<p class="kmae-inbox__flash is-error"><?php esc_html_e( 'Foglalt állapotban a beszélgetés nem törölhető.', 'ki-mit-adna-erte' ); ?></p>
	<?php elseif ( '' !== $inbox_error ) : ?>
		<p class="kmae-inbox__flash is-error"><?php esc_html_e( 'A törlés nem sikerült. Próbáld újra.', 'ki-mit-adna-erte' ); ?></p>
	<?php endif; ?>

	<?php if ( empty( $inbox ) ) : ?>
		<div class="kmae-inbox__empty">
			<div class="kmae-inbox__empty-mascot">
				<img src="<?php echo esc_url( KMAE_Brand::email_sad_mascot_url() ); ?>" alt="" width="220" height="250" decoding="async" />
				<span class="kmae-inbox__empty-badge" aria-hidden="true">
					<?php echo KMAE_Icons::render( 'message-square', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>
			<div class="kmae-inbox__empty-body">
				<h2 class="kmae-inbox__empty-title"><?php esc_html_e( 'Még nincs', 'ki-mit-adna-erte' ); ?> <span><?php esc_html_e( 'beszélgetésed', 'ki-mit-adna-erte' ); ?></span></h2>
				<p class="kmae-inbox__empty-text"><?php esc_html_e( 'Egy hirdetésnél az „Üzenet küldése” gombbal írhatsz az eladónak. A beszélgetéseid itt jelennek meg.', 'ki-mit-adna-erte' ); ?></p>
				<a class="kmae-inbox__empty-btn" href="<?php echo esc_url( KMAE_Pages::browse_page_url() ); ?>">
					<?php echo KMAE_Icons::render( 'car', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Hirdetések böngészése', 'ki-mit-adna-erte' ); ?></span>
					<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<ul class="kmae-inbox__list">
			<?php foreach ( $inbox as $row ) : ?>
				<?php
				$listing_id = (int) $row['listing_id'];
				$gone       = ! empty( $row['listing_gone'] );
				$title      = $gone ? (string) ( $row['conversation']->listing_title ?? '' ) : KMAE_Vehicle::get_display_title( $listing_id );
				$thumb      = $gone ? '' : KMAE_Query::get_listing_thumbnail_url( $listing_id, 'medium' );
				$last_ts    = strtotime( (string) $row['last_at'] );
				$when       = $last_ts
					? ( wp_date( 'Y-m-d', $last_ts ) === $today ? wp_date( 'H:i', $last_ts ) : wp_date( 'Y. m. d.', $last_ts ) )
					: '';
				$role_label = $row['is_buyer'] ? __( 'Eladó', 'ki-mit-adna-erte' ) : __( 'Vevő', 'ki-mit-adna-erte' );
				$role_key   = $row['is_buyer'] ? 'seller' : 'buyer';
				$initial    = '' !== $row['other_name'] ? mb_strtoupper( mb_substr( $row['other_name'], 0, 1 ) ) : '?';
				?>
				<li class="kmae-inbox__item kmae-inbox__item--<?php echo esc_attr( $role_key ); ?><?php echo $row['unread'] ? ' is-unread' : ''; ?><?php echo $gone ? ' is-listing-gone' : ''; ?>">
					<a class="kmae-inbox__link" href="<?php echo esc_url( $row['url'] ); ?>">
						<span class="kmae-inbox__photo">
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" width="132" height="92" loading="lazy" />
							<?php else : ?>
								<span class="kmae-inbox__photo-empty" aria-hidden="true">
									<?php echo KMAE_Icons::render( 'car', array( 'size' => 28 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
							<?php endif; ?>
							<span class="kmae-inbox__avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
						</span>
						<span class="kmae-inbox__body">
							<span class="kmae-inbox__head">
								<span class="kmae-inbox__car"><?php echo esc_html( $title ); ?></span>
								<?php if ( $gone ) : ?>
									<span class="kmae-inbox__gone"><?php esc_html_e( 'Törölt hirdetés', 'ki-mit-adna-erte' ); ?></span>
								<?php elseif ( '' !== $row['state_label'] ) : ?>
									<span class="kmae-inbox__state kmae-inbox__state--<?php echo esc_attr( $row['state'] ); ?>"><?php echo esc_html( $row['state_label'] ); ?></span>
								<?php endif; ?>
								<?php if ( $when ) : ?>
									<time class="kmae-inbox__time" datetime="<?php echo esc_attr( mysql2date( DATE_W3C, (string) $row['last_at'], false ) ); ?>"><?php echo KMAE_Icons::render( 'clock', array( 'size' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $when ); ?></time>
								<?php endif; ?>
							</span>
							<span class="kmae-inbox__party">
								<span class="kmae-inbox__role kmae-inbox__role--<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_label ); ?></span>
								<?php echo esc_html( $row['other_name'] ); ?>
							</span>
							<span class="kmae-inbox__preview">
								<?php if ( $row['last_is_mine'] ) : ?>
									<span class="kmae-inbox__you"><?php esc_html_e( 'Te:', 'ki-mit-adna-erte' ); ?></span>
								<?php endif; ?>
								<?php echo esc_html( wp_trim_words( (string) $row['preview'], 18, '…' ) ); ?>
							</span>
						</span>
						<span class="kmae-inbox__side">
							<?php if ( $row['unread'] ) : ?>
								<span class="kmae-inbox__new" aria-label="<?php esc_attr_e( 'Olvasatlan üzenet', 'ki-mit-adna-erte' ); ?>">
									<span class="kmae-inbox__dot" aria-hidden="true"></span>
									<?php esc_html_e( 'Új', 'ki-mit-adna-erte' ); ?>
								</span>
							<?php endif; ?>
							<span class="kmae-inbox__go" aria-hidden="true">
								<?php echo KMAE_Icons::render( 'chevron-right', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</span>
					</a>
					<?php if ( $row['delete_locked'] ) : ?>
						<span class="kmae-inbox__delete is-locked" tabindex="0" role="img" aria-label="<?php esc_attr_e( 'Foglalt állapotban nem törölhető', 'ki-mit-adna-erte' ); ?>">
							<?php echo KMAE_Icons::render( 'x', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="kmae-inbox__delete-tip" aria-hidden="true">
								<?php
								echo esc_html(
									$row['is_buyer']
										? __( 'Foglalt állapotban nem törölhető. Akkor lehet, ha az eladó megnyomja a „Mégse kelt el” gombot.', 'ki-mit-adna-erte' )
										: __( 'Foglalt állapotban nem törölhető. Ha mégsem kel el, előbb nyomd meg a „Mégse kelt el” gombot.', 'ki-mit-adna-erte' )
								);
								?>
							</span>
						</span>
					<?php else : ?>
						<button type="button" class="kmae-inbox__delete"
							data-kmae-inbox-delete="<?php echo esc_attr( (int) $row['conversation']->id ); ?>"
							data-kmae-inbox-delete-note="<?php echo $row['live_offer'] ? esc_attr__( 'Ha törlöd, az ajánlatod visszavonódik.', 'ki-mit-adna-erte' ) : ''; ?>"
							aria-label="<?php esc_attr_e( 'Beszélgetés törlése', 'ki-mit-adna-erte' ); ?>">
							<?php echo KMAE_Icons::render( 'x', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<dialog class="kmae-inbox-confirm" id="kmae-inbox-confirm" aria-labelledby="kmae-inbox-confirm-title">
			<form method="post" class="kmae-inbox-confirm__form">
				<?php wp_nonce_field( 'kmae_hide_conversation_nonce', 'kmae_hide_conversation_nonce' ); ?>
				<input type="hidden" name="kmae_action" value="hide_conversation" />
				<input type="hidden" name="kmae_conversation_id" value="" data-kmae-inbox-confirm-id />
				<p class="kmae-inbox-confirm__title" id="kmae-inbox-confirm-title"><?php esc_html_e( 'Biztosan törlöd?', 'ki-mit-adna-erte' ); ?></p>
				<p class="kmae-inbox-confirm__note" data-kmae-inbox-confirm-note hidden></p>
				<div class="kmae-inbox-confirm__actions">
					<button type="button" class="kmae-inbox-confirm__cancel" data-kmae-inbox-confirm-cancel><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
					<button type="submit" class="kmae-inbox-confirm__ok"><?php esc_html_e( 'Törlés', 'ki-mit-adna-erte' ); ?></button>
				</div>
			</form>
		</dialog>
	<?php endif; ?>
</div>
