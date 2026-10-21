<?php
/**
 * Bejelentés gomb + ablak (hirdetés oldal és chat).
 *
 * @package KMAE
 * @var string $report_context         KMAE_Reports::CONTEXT_LISTING|CONTEXT_CHAT.
 * @var int    $report_listing_id      Hirdetés.
 * @var int    $report_conversation_id Beszélgetés (chatnél).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$report_is_chat   = KMAE_Reports::CONTEXT_CHAT === $report_context;
$report_dialog_id = 'kmae-report-' . $report_context;
$report_reasons   = KMAE_Reports::reasons( $report_context );
?>
<div class="kmae-report-entry kmae-report-entry--<?php echo esc_attr( $report_context ); ?>">
	<?php if ( ! is_user_logged_in() ) : ?>
		<a class="kmae-report-open" href="<?php echo esc_url( KMAE_Auth::login_url( get_permalink( (int) $report_listing_id ) ) ); ?>">
			<span class="kmae-report-open__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'flag', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php esc_html_e( 'Hirdetés bejelentése', 'ki-mit-adna-erte' ); ?>
		</a>
	<?php else : ?>
		<button type="button" class="kmae-report-open" data-kmae-report-open="<?php echo esc_attr( $report_dialog_id ); ?>">
			<span class="kmae-report-open__icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'flag', array( 'size' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php echo $report_is_chat ? esc_html__( 'Bejelentés', 'ki-mit-adna-erte' ) : esc_html__( 'Hirdetés bejelentése', 'ki-mit-adna-erte' ); ?>
		</button>

		<dialog class="kmae-report" id="<?php echo esc_attr( $report_dialog_id ); ?>" aria-labelledby="<?php echo esc_attr( $report_dialog_id ); ?>-title">
			<form method="post" class="kmae-report__form">
				<?php wp_nonce_field( 'kmae_report_nonce', 'kmae_report_nonce' ); ?>
				<input type="hidden" name="kmae_action" value="submit_report" />
				<input type="hidden" name="kmae_report_context" value="<?php echo esc_attr( $report_context ); ?>" />
				<input type="hidden" name="kmae_listing_id" value="<?php echo esc_attr( (string) (int) $report_listing_id ); ?>" />
				<input type="hidden" name="kmae_conversation_id" value="<?php echo esc_attr( (string) (int) ( $report_conversation_id ?? 0 ) ); ?>" />

				<div class="kmae-report__head">
					<span class="kmae-report__head-icon" aria-hidden="true"><?php echo KMAE_Icons::render( 'flag', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="kmae-report__head-copy">
						<h2 class="kmae-report__title" id="<?php echo esc_attr( $report_dialog_id ); ?>-title">
							<?php echo $report_is_chat ? esc_html__( 'Beszélgetőtárs bejelentése', 'ki-mit-adna-erte' ) : esc_html__( 'Hirdetés bejelentése', 'ki-mit-adna-erte' ); ?>
						</h2>
						<p class="kmae-report__lead"><?php esc_html_e( 'A bejelentést mi nézzük át, a másik fél nem látja, ki küldte.', 'ki-mit-adna-erte' ); ?></p>
					</div>
					<button type="button" class="kmae-report__x" data-kmae-report-close aria-label="<?php esc_attr_e( 'Bezárás', 'ki-mit-adna-erte' ); ?>"><?php echo KMAE_Icons::render( 'x', array( 'size' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>

				<fieldset class="kmae-report__reasons">
					<legend class="kmae-report__label"><?php esc_html_e( 'Mi a probléma?', 'ki-mit-adna-erte' ); ?></legend>
					<?php foreach ( $report_reasons as $reason_key => $reason_label ) : ?>
						<label class="kmae-report__reason<?php echo KMAE_Reports::REASON_OTHER === $reason_key ? ' kmae-report__reason--other' : ''; ?>">
							<input type="radio" name="kmae_report_reason" value="<?php echo esc_attr( $reason_key ); ?>" required />
							<span class="kmae-report__reason-text"><?php echo esc_html( $reason_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>

				<label class="kmae-report__label" for="<?php echo esc_attr( $report_dialog_id ); ?>-details"><?php esc_html_e( 'Miért jelented be?', 'ki-mit-adna-erte' ); ?></label>
				<textarea class="kmae-report__details" id="<?php echo esc_attr( $report_dialog_id ); ?>-details" name="kmae_report_details" rows="4" maxlength="<?php echo esc_attr( (string) KMAE_Reports::DETAILS_MAX_LENGTH ); ?>" placeholder="<?php esc_attr_e( 'Írd le röviden, mi történt. A „Más” választásakor kötelező.', 'ki-mit-adna-erte' ); ?>"></textarea>

				<div class="kmae-report__actions">
					<button type="button" class="kmae-report__cancel" data-kmae-report-close><?php esc_html_e( 'Mégse', 'ki-mit-adna-erte' ); ?></button>
					<button type="submit" class="kmae-report__submit"><?php esc_html_e( 'Bejelentés küldése', 'ki-mit-adna-erte' ); ?></button>
				</div>
			</form>
		</dialog>
	<?php endif; ?>
</div>
