<?php
/**
 * Plugin settings admin page.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved = isset( $_GET['settings-updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap kmae-admin-wrap">
	<h1><?php esc_html_e( 'KMAE beállítások', 'ki-mit-adna-erte' ); ?></h1>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Beállítások mentve.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'kmae_settings' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatikus hirdetés-ellenőrzés', 'ki-mit-adna-erte' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="kmae_auto_moderation" value="1" <?php checked( KMAE_Settings::auto_moderation_enabled() ); ?> />
						<?php esc_html_e( 'Bekapcsolva — közzététel előtt szabályalapú ellenőrzés (mezők, leírás hossz, képek, elérhetőség tiltás).', 'ki-mit-adna-erte' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Autó launch módban: kötelező mezők, min. 3 kép, min. 80 karakter leírás.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Böngészés v2', 'ki-mit-adna-erte' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( KMAE_Browse_Feed::OPTION ); ?>" value="1" <?php checked( (bool) get_option( KMAE_Browse_Feed::OPTION ) ); ?> />
						<?php esc_html_e( 'Bekapcsolva — az új, gyorsítótárazható böngészés mindenkinek.', 'ki-mit-adna-erte' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Kikapcsolva a régi böngészés fut változatlanul; adminként a böngészés oldalon a ?uj=1 címmel kipróbálható.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Csoportos kereskedői megkeresés', 'ki-mit-adna-erte' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( KMAE_Dealer_Leads::OPTION ); ?>" value="1" <?php checked( (bool) get_option( KMAE_Dealer_Leads::OPTION ) ); ?> />
						<?php esc_html_e( 'Bekapcsolva — a magánszemély eladók egy gombbal megkereshetik a környékbeli felvásárló / beszámító cégeket.', 'ki-mit-adna-erte' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Kikapcsolva csak az adminisztrátorok látják (élőben kipróbálható), a többi felhasználónak rejtve marad.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_seller_video_url"><?php esc_html_e( 'Eladóknak — YouTube videó', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="url" id="kmae_seller_video_url" name="kmae_seller_video_url" value="<?php echo esc_attr( KMAE_Settings::seller_video_url() ); ?>" class="regular-text" placeholder="https://www.youtube.com/watch?v=..." />
					<p class="description"><?php esc_html_e( 'Az „Oktatás” oldal eladói videója. Üresen hagyva: „Videó hamarosan”.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_buyer_video_url"><?php esc_html_e( 'Vevőknek — YouTube videó', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="url" id="kmae_buyer_video_url" name="kmae_buyer_video_url" value="<?php echo esc_attr( KMAE_Settings::buyer_video_url() ); ?>" class="regular-text" placeholder="https://www.youtube.com/watch?v=..." />
					<p class="description"><?php esc_html_e( 'Az „Oktatás” oldal vevői videója. Üresen hagyva: „Videó hamarosan”.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_contact_form_email"><?php esc_html_e( 'Kapcsolat űrlap e-mail', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="email" id="kmae_contact_form_email" name="kmae_contact_form_email" value="<?php echo esc_attr( KMAE_Settings::contact_form_email() ); ?>" class="regular-text" placeholder="te@pelda.hu" />
					<p class="description"><?php esc_html_e( 'Ide érkeznek a /kapcsolat/ oldalról küldött üzenetek.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_google_client_id"><?php esc_html_e( 'Google belépés — Client ID', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="text" id="kmae_google_client_id" name="kmae_google_client_id" value="<?php echo esc_attr( get_option( 'kmae_google_client_id', '' ) ); ?>" class="large-text" placeholder="123456789-abc.apps.googleusercontent.com" />
					<p class="description">
						<?php esc_html_e( 'A Google Cloud Console → API-k és szolgáltatások → Hitelesítő adatok menüben létrehozott OAuth ügyfél-azonosító. Amíg üres, a Google gomb nem jelenik meg.', 'ki-mit-adna-erte' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_google_client_secret"><?php esc_html_e( 'Google belépés — Client Secret', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="password" id="kmae_google_client_secret" name="kmae_google_client_secret" value="<?php echo esc_attr( get_option( 'kmae_google_client_secret', '' ) ); ?>" class="large-text" autocomplete="off" />
					<p class="description">
						<?php
						printf(
							/* translators: %s: the redirect URI to paste into Google Console */
							esc_html__( 'Az ügyfél-azonosítóhoz tartozó titok. A Google Console-ban engedélyezett átirányítási címnek ezt kell beállítani: %s', 'ki-mit-adna-erte' ),
							'<code>' . esc_html( class_exists( 'KMAE_Google_Auth' ) ? KMAE_Google_Auth::redirect_uri() : '' ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
			<?php
			$kmae_scan_key_const = defined( 'KMAE_OPENAI_API_KEY' ) && '' !== trim( (string) KMAE_OPENAI_API_KEY );
			$kmae_scan_key_saved = '' !== trim( (string) get_option( KMAE_Registration_Scan::OPTION_KEY, '' ) );
			?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Forgalmi beolvasása', 'ki-mit-adna-erte' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( KMAE_Registration_Scan::OPTION_ENABLED ); ?>" value="1" <?php checked( (bool) get_option( KMAE_Registration_Scan::OPTION_ENABLED, 1 ) ); ?> />
						<?php esc_html_e( 'A hirdetésfeladásnál felajánljuk a forgalmi engedély fotóiból való kitöltést', 'ki-mit-adna-erte' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Csak akkor működik, ha alább meg van adva az OpenAI API-kulcs.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_openai_api_key"><?php esc_html_e( 'OpenAI API-kulcs', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<?php if ( $kmae_scan_key_const ) : ?>
						<p><strong><?php esc_html_e( 'A kulcs a wp-config.php fájlban van megadva (KMAE_OPENAI_API_KEY).', 'ki-mit-adna-erte' ); ?></strong></p>
					<?php else : ?>
						<input type="password" id="kmae_openai_api_key" name="<?php echo esc_attr( KMAE_Registration_Scan::OPTION_KEY ); ?>" value="" class="large-text" autocomplete="new-password" placeholder="<?php echo $kmae_scan_key_saved ? esc_attr__( '•••••••• elmentve — csak csere esetén írd be az újat', 'ki-mit-adna-erte' ) : 'sk-…'; ?>" />
						<p class="description">
							<?php echo $kmae_scan_key_saved ? esc_html__( 'Állapot: kulcs elmentve. Üresen hagyva a mentett kulcs megmarad.', 'ki-mit-adna-erte' ) : esc_html__( 'Állapot: nincs kulcs — a forgalmi beolvasása nem jelenik meg.', 'ki-mit-adna-erte' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kmae_scan_model"><?php esc_html_e( 'Forgalmi — modellek', 'ki-mit-adna-erte' ); ?></label></th>
				<td>
					<input type="text" id="kmae_scan_model" name="<?php echo esc_attr( KMAE_Registration_Scan::OPTION_MODEL ); ?>" value="<?php echo esc_attr( get_option( KMAE_Registration_Scan::OPTION_MODEL, KMAE_Registration_Scan::DEFAULT_MODEL ) ); ?>" class="regular-text" />
					<input type="text" id="kmae_scan_fallback_model" name="<?php echo esc_attr( KMAE_Registration_Scan::OPTION_FALLBACK ); ?>" value="<?php echo esc_attr( get_option( KMAE_Registration_Scan::OPTION_FALLBACK, KMAE_Registration_Scan::DEFAULT_FALLBACK ) ); ?>" class="regular-text" aria-label="<?php esc_attr_e( 'Tartalék modell', 'ki-mit-adna-erte' ); ?>" />
					<p class="description"><?php esc_html_e( 'Első: gyors, olcsó modell. Második: csak akkor fut, ha az első hiányos vagy bizonytalan eredményt adott.', 'ki-mit-adna-erte' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<?php
	$kmae_bi_counts = KMAE_Browse_Index::counts();
	$kmae_bi_state  = KMAE_Browse_Index::rebuild_state();
	$kmae_bi_ran    = isset( $_GET['kmae_bi'] ) ? sanitize_key( wp_unslash( $_GET['kmae_bi'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<h2><?php esc_html_e( 'Böngészés v2 — hirdetés-index', 'ki-mit-adna-erte' ); ?></h2>
	<?php if ( 'locked' === $kmae_bi_ran ) : ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Már fut egy adag (legfeljebb 5 percig tart a zár). Próbáld újra kicsit később.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php elseif ( 'running' === $kmae_bi_ran ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Egy adag kész, a többi a háttérben folytatódik (vagy nyomd meg újra a gombot).', 'ki-mit-adna-erte' ); ?></p></div>
	<?php elseif ( 'done' === $kmae_bi_ran ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Az index újraépítése kész.', 'ki-mit-adna-erte' ); ?></p></div>
	<?php endif; ?>
	<table class="widefat striped" style="max-width:520px;">
		<tbody>
			<tr><td><?php esc_html_e( 'Sorok / aktív hirdetés', 'ki-mit-adna-erte' ); ?></td><td><strong><?php echo (int) $kmae_bi_counts['rows']; ?> / <?php echo (int) $kmae_bi_counts['active']; ?></strong></td></tr>
			<tr><td><?php esc_html_e( 'Újraépítés', 'ki-mit-adna-erte' ); ?></td><td>
				<?php
				if ( $kmae_bi_state['running'] ) {
					/* translators: %d: processed listings */
					echo esc_html( sprintf( __( 'folyamatban (%d hirdetés kész)', 'ki-mit-adna-erte' ), (int) $kmae_bi_state['done'] ) );
				} elseif ( $kmae_bi_state['finished'] ) {
					echo esc_html( sprintf( /* translators: %s: date */ __( 'kész: %s', 'ki-mit-adna-erte' ), wp_date( 'Y.m.d. H:i', (int) $kmae_bi_state['finished'] ) ) );
				} else {
					echo '—';
				}
				?>
			</td></tr>
		</tbody>
	</table>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
		<?php wp_nonce_field( 'kmae_browse_index_rebuild' ); ?>
		<input type="hidden" name="action" value="kmae_browse_index_rebuild" />
		<button type="submit" class="button"><?php echo $kmae_bi_state['running'] ? esc_html__( 'Következő adag most', 'ki-mit-adna-erte' ) : esc_html__( 'Index teljes újraépítése', 'ki-mit-adna-erte' ); ?></button>
	</form>
</div>
