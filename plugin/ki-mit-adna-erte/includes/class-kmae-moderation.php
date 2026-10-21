<?php
/**
 * Automated listing moderation (rule-based, no external AI).
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Moderation {

	const MIN_DESC_LENGTH = 80;
	const MIN_IMAGES      = 1;

	public static function is_enabled() {
		if ( defined( 'KMAE_AUTO_MODERATION' ) && ! KMAE_AUTO_MODERATION ) {
			return false;
		}
		return (bool) get_option( 'kmae_auto_moderation', 1 );
	}

	/**
	 * Validate listing before publish. Returns WP_Error on failure.
	 *
	 * @param array $data           Listing + vehicle data.
	 * @param int   $image_count    Uploaded image count.
	 * @param bool  $require_images Whether photo count is required now.
	 * @return true|WP_Error
	 */
	public static function validate( $data, $image_count = 0, $require_images = true ) {
		if ( ! self::is_enabled() ) {
			return true;
		}

		$errors = array();

		if ( KMAE_Vehicle::is_vehicle_launch() ) {
			foreach ( KMAE_Vehicle::get_missing_required( $data ) as $item ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( 'Hiányzó vagy hibás mező: %s', 'ki-mit-adna-erte' ),
					$item['label']
				);
			}
		}

		$desc = $data['description'] ?? '';
		if ( mb_strlen( trim( $desc ) ) < self::MIN_DESC_LENGTH ) {
			$errors[] = sprintf(
				/* translators: %d: minimum character count */
				__( 'A leírás túl rövid — legalább %d karakter kell.', 'ki-mit-adna-erte' ),
				self::MIN_DESC_LENGTH
			);
		}

		if ( $require_images ) {
			$min_images = KMAE_Photo_Motor::is_enabled() ? KMAE_Photo_Motor::required_count() : self::MIN_IMAGES;
			if ( $image_count < $min_images ) {
				$errors[] = sprintf(
					/* translators: %d: minimum image count */
					__( 'Legalább %d kötelező fotó szükséges (kamera nézetek).', 'ki-mit-adna-erte' ),
					$min_images
				);
			}
		}

		if ( self::contains_contact_info( $desc ) || self::contains_contact_info( $data['title'] ?? '' ) ) {
			$errors[] = __( 'Ne írj telefonszámot vagy e-mail címet a leírásba.', 'ki-mit-adna-erte' );
		}

		if ( empty( $data['title'] ?? '' ) ) {
			$errors[] = __( 'Add meg a hirdetés címét.', 'ki-mit-adna-erte' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'moderation_failed', implode( ' ', $errors ), array( 'reasons' => $errors ) );
		}

		return true;
	}

	public static function contains_contact_info( $text ) {
		$text = (string) $text;
		if ( preg_match( '/\b[\w.+-]+@[\w-]+\.[\w.-]+\b/u', $text ) ) {
			return true;
		}
		if ( preg_match( '/(\+36|06)[\s\-]?[\d\s\-]{7,}/u', $text ) ) {
			return true;
		}
		if ( preg_match( '/\b\d{2}[\s\-]?\d{3}[\s\-]?\d{4}\b/u', $text ) ) {
			return true;
		}
		return false;
	}

	public static function count_uploaded_images() {
		if ( KMAE_Photo_Motor::is_enabled() ) {
			return KMAE_Photo_Motor::count_required_uploads();
		}

		if ( empty( $_FILES['kmae_images']['name'] ) ) {
			return 0;
		}
		if ( ! is_array( $_FILES['kmae_images']['name'] ) ) {
			return empty( $_FILES['kmae_images']['name'] ) ? 0 : 1;
		}
		$count = 0;
		foreach ( $_FILES['kmae_images']['name'] as $name ) {
			if ( ! empty( $name ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Store rejection reasons in transient for display on form.
	 */
	public static function stash_errors_for_user( $user_id, $reasons ) {
		set_transient( 'kmae_mod_errors_' . $user_id, $reasons, 300 );
	}

	public static function pull_errors_for_user( $user_id ) {
		$key    = 'kmae_mod_errors_' . $user_id;
		$errors = get_transient( $key );
		delete_transient( $key );
		return is_array( $errors ) ? $errors : array();
	}
}
