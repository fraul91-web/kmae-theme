<?php
/**
 * Félbehagyott hirdetések (wizard automatikus mentése).
 *
 * A piszkozat egy nem nyilvános `kmae_listing` bejegyzés (`post_status = draft`,
 * `_kmae_status = draft`). A wizard mezőinek értékeit és azt a lépést, ahol a
 * felhasználó tartott, egyetlen JSON-ként tárolja — a hirdetés rendes adatai
 * (meta) csak a végleges feladáskor jönnek létre.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Listing_Drafts {

	const META_PAYLOAD = '_kmae_wizard_draft';
	const NONCE_ACTION = 'kmae_listing_draft';
	const MAX_PAYLOAD  = 200000;

	public static function init() {
		add_action( 'wp_ajax_kmae_save_listing_draft', array( __CLASS__, 'ajax_save' ) );
	}

	/**
	 * A felhasználó egy saját piszkozata, vagy null.
	 *
	 * @param int $draft_id Draft post ID.
	 * @param int $user_id  User ID.
	 * @return WP_Post|null
	 */
	public static function get_user_draft( $draft_id, $user_id ) {
		$draft_id = absint( $draft_id );
		if ( ! $draft_id || ! $user_id ) {
			return null;
		}

		$post = get_post( $draft_id );
		if (
			! $post
			|| 'kmae_listing' !== $post->post_type
			|| 'draft' !== $post->post_status
			|| (int) $post->post_author !== (int) $user_id
			|| KMAE_Meta::STATUS_DRAFT !== get_post_meta( $post->ID, '_kmae_status', true )
		) {
			return null;
		}

		return $post;
	}

	/**
	 * A legutóbb módosított piszkozat.
	 *
	 * @param int $user_id User ID.
	 * @return WP_Post|null
	 */
	public static function get_latest_draft( $user_id ) {
		if ( ! $user_id ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'post_status'    => 'draft',
				'author'         => (int) $user_id,
				'posts_per_page' => 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_key'       => '_kmae_status',
				'meta_value'     => KMAE_Meta::STATUS_DRAFT,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * A wizardnak átadott adatcsomag.
	 *
	 * @param WP_Post $post Draft post.
	 * @return array
	 */
	public static function to_client( $post ) {
		$raw     = get_post_meta( $post->ID, self::META_PAYLOAD, true );
		$payload = is_string( $raw ) ? json_decode( $raw, true ) : null;

		return array(
			'id'      => (int) $post->ID,
			'title'   => get_the_title( $post ),
			'savedAt' => sprintf(
				/* translators: %s: human readable time difference */
				__( '%s ezelőtt', 'ki-mit-adna-erte' ),
				human_time_diff( get_post_modified_time( 'U', true, $post ), time() )
			),
			'payload' => is_array( $payload ) ? $payload : array(),
		);
	}

	/**
	 * Mentés AJAX-on (a wizard lépésváltáskor, mezőváltozáskor és az oldal
	 * elhagyásakor hívja).
	 */
	public static function ajax_save() {
		if ( ! is_user_logged_in() || ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		$user_id = get_current_user_id();
		$raw     = isset( $_POST['payload'] ) ? (string) wp_unslash( $_POST['payload'] ) : '';

		if ( '' === $raw || strlen( $raw ) > self::MAX_PAYLOAD ) {
			wp_send_json_error( array( 'code' => 'invalid_payload' ), 400 );
		}

		$payload = json_decode( $raw, true );
		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'code' => 'invalid_payload' ), 400 );
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			$title = __( 'Félbehagyott hirdetés', 'ki-mit-adna-erte' );
		}

		$draft_id = isset( $_POST['draft_id'] ) ? absint( $_POST['draft_id'] ) : 0;
		$draft    = $draft_id ? self::get_user_draft( $draft_id, $user_id ) : null;

		if ( $draft ) {
			wp_update_post(
				array(
					'ID'         => $draft->ID,
					'post_title' => $title,
				)
			);
			$draft_id = (int) $draft->ID;
		} else {
			$draft_id = wp_insert_post(
				array(
					'post_type'   => 'kmae_listing',
					'post_status' => 'draft',
					'post_author' => $user_id,
					'post_title'  => $title,
				),
				true
			);
			if ( is_wp_error( $draft_id ) ) {
				wp_send_json_error( array( 'code' => 'create_failed' ), 500 );
			}
			update_post_meta( $draft_id, '_kmae_status', KMAE_Meta::STATUS_DRAFT );
		}

		update_post_meta( $draft_id, self::META_PAYLOAD, wp_slash( wp_json_encode( $payload ) ) );

		wp_send_json_success( array( 'draftId' => (int) $draft_id ) );
	}

	/**
	 * Sikeres feladás után a piszkozat törlődik, hogy ne legyen dupla hirdetés.
	 *
	 * @param int $user_id User ID.
	 */
	public static function discard_submitted( $user_id ) {
		$draft_id = isset( $_POST['kmae_draft_id'] ) ? absint( $_POST['kmae_draft_id'] ) : 0;
		$draft    = $draft_id ? self::get_user_draft( $draft_id, $user_id ) : null;
		if ( $draft ) {
			wp_delete_post( $draft->ID, true );
		}
	}
}
