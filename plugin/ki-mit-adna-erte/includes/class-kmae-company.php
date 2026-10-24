<?php
/**
 * Company / dealership seller profile fields.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Company {

	const META_COMPANY_NAME    = 'kmae_company_name';
	const META_COMPANY_ADDRESS = 'kmae_company_address';
	const META_COMPANY_COUNTY  = 'kmae_company_county';
	const META_COMPANY_CITY    = 'kmae_company_city';
	/* Bemutatkozás: a hirdetés oldalán a vevők olvassák; üresen nem jelenik meg. */
	const META_COMPANY_INTRO   = 'kmae_company_intro';
	const INTRO_MAX            = 1000;
	/* Autóátvétel (csoportos kereskedői megkeresés): '1' vagy nincs meta. */
	const META_COMPANY_BUYS    = 'kmae_company_buys';
	const META_COMPANY_TRADEIN = 'kmae_company_tradein';

	/**
	 * @param int $user_id User.
	 * @return string
	 */
	public static function get_company_name( $user_id ) {
		$name = get_user_meta( $user_id, self::META_COMPANY_NAME, true );
		if ( $name ) {
			return (string) $name;
		}
		$user = get_userdata( $user_id );
		return $user ? (string) $user->display_name : '';
	}

	/**
	 * @param int $user_id User.
	 * @return string
	 */
	public static function get_address( $user_id ) {
		return (string) get_user_meta( $user_id, self::META_COMPANY_ADDRESS, true );
	}

	/**
	 * Céges bemutatkozás (üres, ha nincs, vagy nem céges a fiók).
	 *
	 * @param int $user_id User.
	 * @return string
	 */
	public static function get_intro( $user_id ) {
		if ( ! KMAE_Auth::is_company( $user_id ) ) {
			return '';
		}
		return trim( (string) get_user_meta( $user_id, self::META_COMPANY_INTRO, true ) );
	}

	public static function get_county( $user_id ) {
		return (string) get_user_meta( $user_id, self::META_COMPANY_COUNTY, true );
	}

	public static function get_city( $user_id ) {
		return (string) get_user_meta( $user_id, self::META_COMPANY_CITY, true );
	}

	public static function has_listing_location( $user_id ) {
		return KMAE_Counties::validate_listing_location( self::get_county( $user_id ), self::get_city( $user_id ) );
	}

	public static function apply_listing_location( $post_id, $user_id ) {
		$problem = KMAE_Counties::location_error( self::get_county( $user_id ), self::get_city( $user_id ) );
		if ( 'mismatch' === $problem ) {
			return new WP_Error( 'company_location_mismatch', __( 'A céges telephely települése nem ebben a megyében van. Javítsd a profilodban: válaszd ki a listából vagy írd be az irányítószámot.', 'ki-mit-adna-erte' ) );
		}
		if ( '' !== $problem ) {
			return new WP_Error( 'company_location_required', __( 'Add meg a céges telephely megyéjét és városát a profilodban.', 'ki-mit-adna-erte' ) );
		}

		KMAE_Counties::save_listing_location( $post_id, self::get_county( $user_id ), self::get_city( $user_id ) );
		return true;
	}

	/**
	 * A telephely települése nem a választott megyében van (regisztráció / profil).
	 */
	public static function location_mismatch_message() {
		return __( 'Ez a település nem ebben a megyében van — válaszd ki a listából vagy írd be az irányítószámot.', 'ki-mit-adna-erte' );
	}

	/**
	 * Public seller label (company name or personal display name).
	 *
	 * @param int $user_id User.
	 * @return string
	 */
	public static function seller_display_name( $user_id ) {
		if ( KMAE_Auth::is_company( $user_id ) ) {
			return self::get_company_name( $user_id );
		}
		$user = get_userdata( $user_id );
		return $user ? (string) $user->display_name : '';
	}

	/**
	 * Vállal autófelvásárlást a cég? (nem céges felhasználónál mindig false)
	 *
	 * @param int $user_id User.
	 * @return bool
	 */
	public static function buys_cars( $user_id ) {
		return KMAE_Auth::is_company( $user_id ) && '1' === (string) get_user_meta( $user_id, self::META_COMPANY_BUYS, true );
	}

	/**
	 * Vállal beszámítást a cég? (nem céges felhasználónál mindig false)
	 *
	 * @param int $user_id User.
	 * @return bool
	 */
	public static function accepts_tradein( $user_id ) {
		return KMAE_Auth::is_company( $user_id ) && '1' === (string) get_user_meta( $user_id, self::META_COMPANY_TRADEIN, true );
	}

	/**
	 * Save company fields from POST (register / profile).
	 *
	 * @param int   $user_id User.
	 * @param array $data    Keys: company_name, address, county, city, intro;
	 *                       optional buys, tradein (csak ha a kulcs jelen van, mentődik).
	 * @return true|WP_Error
	 */
	public static function save_profile_fields( $user_id, $data ) {
		if ( ! KMAE_Auth::is_company( $user_id ) ) {
			return true;
		}

		$name    = isset( $data['company_name'] ) ? sanitize_text_field( (string) $data['company_name'] ) : '';
		$address = isset( $data['address'] ) ? sanitize_text_field( (string) $data['address'] ) : '';
		$county  = isset( $data['county'] ) ? sanitize_key( (string) $data['county'] ) : '';
		$city    = isset( $data['city'] ) ? sanitize_text_field( (string) $data['city'] ) : '';
		$intro   = isset( $data['intro'] ) ? trim( sanitize_textarea_field( (string) $data['intro'] ) ) : '';
		$intro   = preg_replace( "/\n{3,}/", "\n\n", $intro );
		if ( function_exists( 'mb_substr' ) ) {
			$intro = mb_substr( $intro, 0, self::INTRO_MAX );
		}

		if ( '' === $name ) {
			return new WP_Error( 'company_name_required', __( 'Add meg a cégnevet.', 'ki-mit-adna-erte' ) );
		}
		$problem = KMAE_Counties::location_error( $county, $city );
		if ( '' === $address || 'required' === $problem ) {
			return new WP_Error( 'company_location_required', __( 'Add meg a céges telephelyet.', 'ki-mit-adna-erte' ) );
		}
		if ( 'mismatch' === $problem ) {
			/* Csak akkor hiba, ha a megye/település MOST változott: a régi (szabad szöveges) telephelyű cég a többi adatát továbbra is mentheti; a profil-értesítő kéri a javítást. */
			$unchanged = $county === sanitize_key( self::get_county( $user_id ) ) && $city === self::get_city( $user_id );
			if ( ! $unchanged ) {
				return new WP_Error( 'company_location_mismatch', self::location_mismatch_message() );
			}
		}
		$city = KMAE_Counties::canonical_city( $county, $city );

		update_user_meta( $user_id, self::META_COMPANY_NAME, $name );
		update_user_meta( $user_id, self::META_COMPANY_ADDRESS, $address );
		update_user_meta( $user_id, self::META_COMPANY_COUNTY, $county );
		update_user_meta( $user_id, self::META_COMPANY_CITY, $city );
		if ( '' === $intro ) {
			delete_user_meta( $user_id, self::META_COMPANY_INTRO );
		} else {
			update_user_meta( $user_id, self::META_COMPANY_INTRO, $intro );
		}
		foreach ( array(
			'buys'    => self::META_COMPANY_BUYS,
			'tradein' => self::META_COMPANY_TRADEIN,
		) as $key => $meta_key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			if ( ! empty( $data[ $key ] ) ) {
				update_user_meta( $user_id, $meta_key, '1' );
			} else {
				delete_user_meta( $user_id, $meta_key );
			}
		}

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $name,
			)
		);
		if ( class_exists( 'KMAE_Auth' ) && method_exists( 'KMAE_Auth', 'adopt_public_name' ) ) {
			KMAE_Auth::adopt_public_name( $user_id, $name );
		}

		return true;
	}
}
