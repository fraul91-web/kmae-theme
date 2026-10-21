<?php
/**
 * Albira → Jelentések (lista + egy bejelentés részletei a beszélgetéssel).
 *
 * @package KMAE
 * @var object|null $report
 * @var array       $reports
 * @var array       $counts
 * @var string      $filter_status
 * @var bool        $saved
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$statuses = KMAE_Reports::statuses();

$kmae_report_user = static function ( $user_id ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : null;
	if ( ! $user ) {
		return '<span class="kmae-reports-muted">' . esc_html__( 'Törölt felhasználó', 'ki-mit-adna-erte' ) . '</span>';
	}
	return '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html( $user->display_name ) . '</a> <span class="kmae-reports-muted">' . esc_html( $user->user_email ) . '</span>';
};

$kmae_report_status = static function ( $status ) use ( $statuses ) {
	return '<span class="kmae-reports-status is-' . esc_attr( $status ) . '">' . esc_html( $statuses[ $status ] ?? $status ) . '</span>';
};

$kmae_report_context = static function ( $context ) {
	return KMAE_Reports::CONTEXT_CHAT === $context ? __( 'Chat', 'ki-mit-adna-erte' ) : __( 'Hirdetés', 'ki-mit-adna-erte' );
};
?>
<div class="wrap kmae-reports">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Jelentések', 'ki-mit-adna-erte' ); ?></h1>
	<?php if ( $report ) : ?>
		<a class="page-title-action" href="<?php echo esc_url( KMAE_Reports::admin_url() ); ?>"><?php esc_html_e( '← Vissza a listához', 'ki-mit-adna-erte' ); ?></a>
	<?php endif; ?>
	<hr class="wp-header-end" />

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Döntés mentve.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $report ) : ?>
		<ul class="subsubsub">
			<?php
			$tabs = array_merge( $statuses, array( 'all' => __( 'Összes', 'ki-mit-adna-erte' ) ) );
			$last = array_key_last( $tabs );
			foreach ( $tabs as $tab_key => $tab_label ) :
				$tab_count = 'all' === $tab_key ? array_sum( $counts ) : (int) ( $counts[ $tab_key ] ?? 0 );
				?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( 'status', $tab_key, KMAE_Reports::admin_url() ) ); ?>"<?php echo $filter_status === $tab_key ? ' class="current" aria-current="page"' : ''; ?>>
						<?php echo esc_html( $tab_label ); ?> <span class="count">(<?php echo (int) $tab_count; ?>)</span>
					</a><?php echo $tab_key !== $last ? ' |' : ''; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<table class="wp-list-table widefat fixed striped kmae-reports-table">
			<thead>
				<tr>
					<th class="kmae-reports-col-date"><?php esc_html_e( 'Beküldve', 'ki-mit-adna-erte' ); ?></th>
					<th class="kmae-reports-col-where"><?php esc_html_e( 'Hol', 'ki-mit-adna-erte' ); ?></th>
					<th><?php esc_html_e( 'Ok', 'ki-mit-adna-erte' ); ?></th>
					<th><?php esc_html_e( 'Hirdetés', 'ki-mit-adna-erte' ); ?></th>
					<th><?php esc_html_e( 'Bejelentő', 'ki-mit-adna-erte' ); ?></th>
					<th><?php esc_html_e( 'Bejelentett', 'ki-mit-adna-erte' ); ?></th>
					<th class="kmae-reports-col-status"><?php esc_html_e( 'Állapot', 'ki-mit-adna-erte' ); ?></th>
					<th class="kmae-reports-col-open"></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $reports ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'Nincs bejelentés.', 'ki-mit-adna-erte' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $reports as $row ) : ?>
					<tr class="<?php echo KMAE_Reports::STATUS_NEW === $row->status ? 'kmae-reports-row-new' : ''; ?>">
						<td><?php echo esc_html( mysql2date( 'Y.m.d. H:i', $row->created_at ) ); ?></td>
						<td><?php echo esc_html( $kmae_report_context( $row->context ) ); ?></td>
						<td>
							<strong><?php echo esc_html( KMAE_Reports::reason_label( $row->reason ) ); ?></strong>
							<?php if ( '' !== (string) $row->details ) : ?>
								<div class="kmae-reports-muted kmae-reports-excerpt"><?php echo esc_html( wp_trim_words( $row->details, 16 ) ); ?></div>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row->listing_title ); ?></td>
						<td><?php echo $kmae_report_user( $row->reporter_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo $kmae_report_user( $row->reported_user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo $kmae_report_status( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( KMAE_Reports::admin_url( (int) $row->id ) ); ?>"><?php esc_html_e( 'Megnyitás', 'ki-mit-adna-erte' ); ?></a>
							<?php if ( (int) $row->conversation_id > 0 ) : ?>
								<a class="button button-small" href="<?php echo esc_url( KMAE_Reports::admin_url( (int) $row->id ) . '#kmae-report-chat' ); ?>"><?php esc_html_e( 'Beszélgetés', 'ki-mit-adna-erte' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php else : ?>
		<?php
		$listing_id      = (int) $report->listing_id;
		$listing_exists  = $listing_id && get_post( $listing_id );
		$conversation    = (int) $report->conversation_id > 0 ? KMAE_Conversations::get( (int) $report->conversation_id ) : null;
		$messages        = $conversation ? KMAE_Reports::get_conversation_messages( (int) $conversation->id ) : array();
		$history         = KMAE_Reports::get_against_user( (int) $report->reported_user_id, (int) $report->id );
		$reporter_stats  = KMAE_Reports::reporter_stats( (int) $report->reporter_id );
		$chat_offers     = $conversation ? KMAE_Chat_Offers::get_by_message( (int) $conversation->id ) : array();
		$resolver        = (int) $report->resolved_by ? get_userdata( (int) $report->resolved_by ) : null;
		?>
		<div class="kmae-reports-detail">
			<div class="kmae-reports-card">
				<h2>
					<?php echo esc_html( KMAE_Reports::reason_label( $report->reason ) ); ?>
					<?php echo $kmae_report_status( $report->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</h2>
				<table class="form-table kmae-reports-facts" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Beküldve', 'ki-mit-adna-erte' ); ?></th>
						<td><?php echo esc_html( mysql2date( 'Y.m.d. H:i', $report->created_at ) ); ?> — <?php echo esc_html( $kmae_report_context( $report->context ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Miért jelentette be', 'ki-mit-adna-erte' ); ?></th>
						<td class="kmae-reports-details-text"><?php echo '' !== (string) $report->details ? nl2br( esc_html( $report->details ) ) : '<span class="kmae-reports-muted">' . esc_html__( 'Nem írt indoklást.', 'ki-mit-adna-erte' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Hirdetés', 'ki-mit-adna-erte' ); ?></th>
						<td>
							<?php echo esc_html( $report->listing_title ); ?>
							<?php if ( $listing_exists ) : ?>
								— <a href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Megtekintés', 'ki-mit-adna-erte' ); ?></a>
								| <a href="<?php echo esc_url( get_edit_post_link( $listing_id ) ); ?>"><?php esc_html_e( 'Szerkesztés', 'ki-mit-adna-erte' ); ?></a>
							<?php else : ?>
								<span class="kmae-reports-muted">(<?php esc_html_e( 'a hirdetés már törölve', 'ki-mit-adna-erte' ); ?>)</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Bejelentő', 'ki-mit-adna-erte' ); ?></th>
						<td>
							<?php echo $kmae_report_user( $report->reporter_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<div class="kmae-reports-muted">
								<?php
								/* translators: 1: total reports sent, 2: reports judged false */
								echo esc_html( sprintf( __( 'Eddig %1$d bejelentést küldött, ebből %2$d lett „Nem igaz”.', 'ki-mit-adna-erte' ), $reporter_stats['total'], $reporter_stats['invalid'] ) );
								?>
							</div>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Bejelentett', 'ki-mit-adna-erte' ); ?></th>
						<td><?php echo $kmae_report_user( $report->reported_user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				</table>
			</div>

			<div class="kmae-reports-card">
				<h2><?php esc_html_e( 'Döntés', 'ki-mit-adna-erte' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'kmae_report_update_' . (int) $report->id ); ?>
					<input type="hidden" name="action" value="kmae_report_update" />
					<input type="hidden" name="report_id" value="<?php echo (int) $report->id; ?>" />
					<fieldset class="kmae-reports-decision">
						<?php foreach ( $statuses as $status_key => $status_label ) : ?>
							<label><input type="radio" name="report_status" value="<?php echo esc_attr( $status_key ); ?>"<?php checked( $report->status, $status_key ); ?> /> <?php echo esc_html( $status_label ); ?></label>
						<?php endforeach; ?>
					</fieldset>
					<p>
						<label for="kmae-report-note"><strong><?php esc_html_e( 'Saját jegyzet (csak te látod)', 'ki-mit-adna-erte' ); ?></strong></label><br />
						<textarea id="kmae-report-note" name="admin_note" rows="3" class="large-text"><?php echo esc_textarea( (string) $report->admin_note ); ?></textarea>
					</p>
					<?php if ( $resolver && $report->resolved_at ) : ?>
						<p class="kmae-reports-muted">
							<?php
							/* translators: 1: admin name, 2: date */
							echo esc_html( sprintf( __( 'Döntött: %1$s, %2$s', 'ki-mit-adna-erte' ), $resolver->display_name, mysql2date( 'Y.m.d. H:i', $report->resolved_at ) ) );
							?>
						</p>
					<?php endif; ?>
					<?php submit_button( __( 'Mentés', 'ki-mit-adna-erte' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<div class="kmae-reports-card" id="kmae-report-chat">
				<h2><?php esc_html_e( 'A beszélgetésük', 'ki-mit-adna-erte' ); ?></h2>
				<?php if ( ! $conversation ) : ?>
					<p class="kmae-reports-muted"><?php esc_html_e( 'A bejelentő nem írt az eladónak ennél a hirdetésnél, nincs beszélgetésük.', 'ki-mit-adna-erte' ); ?></p>
				<?php elseif ( empty( $messages ) ) : ?>
					<p class="kmae-reports-muted"><?php esc_html_e( 'A beszélgetésben nincs üzenet.', 'ki-mit-adna-erte' ); ?></p>
				<?php else : ?>
					<p class="kmae-reports-muted">
						<?php
						$buyer  = get_userdata( (int) $conversation->buyer_id );
						$seller = get_userdata( (int) $conversation->seller_id );
						/* translators: 1: buyer name, 2: seller name, 3: message count */
						echo esc_html( sprintf( __( 'Vevő: %1$s · Eladó: %2$s · %3$d üzenet', 'ki-mit-adna-erte' ), $buyer ? $buyer->display_name : '—', $seller ? $seller->display_name : '—', count( $messages ) ) );
						?>
					</p>
					<div class="kmae-reports-chat">
						<?php foreach ( $messages as $message ) : ?>
							<?php
							$is_system = KMAE_Chat_Offers::is_system_message( $message );
							$is_buyer  = (int) $message->sender_id === (int) $conversation->buyer_id;
							$sender    = get_userdata( (int) $message->sender_id );
							$role      = $is_buyer ? __( 'Vevő', 'ki-mit-adna-erte' ) : __( 'Eladó', 'ki-mit-adna-erte' );
							$is_offer  = isset( $chat_offers[ (int) $message->id ] );
							$file_url  = (int) $message->attachment_id ? wp_get_attachment_url( (int) $message->attachment_id ) : '';
							$classes   = 'kmae-reports-msg ' . ( $is_system ? 'is-system' : ( $is_buyer ? 'is-buyer' : 'is-seller' ) ) . ( (int) $message->sender_id === (int) $report->reported_user_id && ! $is_system ? ' is-reported' : '' );
							?>
							<div class="<?php echo esc_attr( $classes ); ?>">
								<div class="kmae-reports-msg__meta">
									<?php if ( $is_system ) : ?>
										<?php esc_html_e( 'Rendszerüzenet', 'ki-mit-adna-erte' ); ?>
									<?php else : ?>
										<strong><?php echo esc_html( $sender ? $sender->display_name : __( 'Törölt felhasználó', 'ki-mit-adna-erte' ) ); ?></strong> (<?php echo esc_html( $role ); ?>)
									<?php endif; ?>
									· <?php echo esc_html( mysql2date( 'Y.m.d. H:i', $message->created_at ) ); ?>
									<?php if ( $is_offer ) : ?>
										· <span class="kmae-reports-offer"><?php esc_html_e( 'Ajánlat', 'ki-mit-adna-erte' ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( '' !== trim( (string) $message->message_text ) ) : ?>
									<div class="kmae-reports-msg__text"><?php echo nl2br( esc_html( $message->message_text ) ); ?></div>
								<?php endif; ?>
								<?php if ( (int) $message->attachment_id ) : ?>
									<div class="kmae-reports-msg__file">
										<?php if ( $file_url ) : ?>
											<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Csatolt fájl megnyitása', 'ki-mit-adna-erte' ); ?></a>
										<?php else : ?>
											<span class="kmae-reports-muted"><?php esc_html_e( 'Csatolt fájl (48 óra után törlődött)', 'ki-mit-adna-erte' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="kmae-reports-card">
				<h2><?php esc_html_e( 'Előzmények: korábbi bejelentések erről a felhasználóról', 'ki-mit-adna-erte' ); ?></h2>
				<?php if ( empty( $history ) ) : ?>
					<p class="kmae-reports-muted"><?php esc_html_e( 'Nincs más bejelentés róla.', 'ki-mit-adna-erte' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<tbody>
							<?php foreach ( $history as $past ) : ?>
								<tr>
									<td><?php echo esc_html( mysql2date( 'Y.m.d. H:i', $past->created_at ) ); ?></td>
									<td><?php echo esc_html( $kmae_report_context( $past->context ) ); ?></td>
									<td><?php echo esc_html( KMAE_Reports::reason_label( $past->reason ) ); ?></td>
									<td><?php echo esc_html( $past->listing_title ); ?></td>
									<td><?php echo $kmae_report_status( $past->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
									<td><a href="<?php echo esc_url( KMAE_Reports::admin_url( (int) $past->id ) ); ?>"><?php esc_html_e( 'Megnyitás', 'ki-mit-adna-erte' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
