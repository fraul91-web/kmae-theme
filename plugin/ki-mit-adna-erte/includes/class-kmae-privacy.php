<?php
/**
 * Felhasználói sorszám (user ID) és felhasználónév elrejtése.
 *
 * A WordPress alapból több helyen kiadja, ki melyik sorszámú felhasználó
 * (REST /wp/v2/users, ?author=N → /author/<login>/, felhasználói sitemap,
 * a bejegyzések/képek REST "author" mezője, oEmbed). Ezekből bárki
 * végigléptethetné a fiókokat, ezért adminon kívül mind le van zárva.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Privacy {

	public static function init() {
		add_filter( 'rest_endpoints', array( __CLASS__, 'filter_rest_endpoints' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'hide_rest_authors' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'block_author_archives' ), 0 );
		add_filter( 'author_link', array( __CLASS__, 'author_link' ) );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'drop_user_sitemap' ), 10, 2 );
		add_filter( 'oembed_response_data', array( __CLASS__, 'oembed_data' ) );
		add_action( 'init', array( __CLASS__, 'purge_old_cached_links' ), 99 );
	}

	const PURGED_OPTION = 'kmae_privacy_cache_purged';

	/**
	 * Egyszer: a LiteSpeed oldal-cache-ben még a régi, sorszámos profil-linkek ülnek.
	 */
	public static function purge_old_cached_links() {
		if ( '1' === get_option( self::PURGED_OPTION ) ) {
			return;
		}
		update_option( self::PURGED_OPTION, '1', false );
		do_action( 'litespeed_purge_all' );
	}

	/**
	 * Csak az lát felhasználói sorszámot, aki a WP adminban is listázhatja a fiókokat.
	 *
	 * @return bool
	 */
	private static function can_see_users() {
		return current_user_can( 'list_users' );
	}

	/**
	 * /wp/v2/users* végpontok vendégnek és sima felhasználónak nem léteznek.
	 *
	 * @param array $endpoints Endpoints.
	 * @return array
	 */
	public static function filter_rest_endpoints( $endpoints ) {
		if ( self::can_see_users() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * A REST bejegyzés/kép/hozzászólás válaszokból kiveszi a szerző sorszámát.
	 */
	public static function hide_rest_authors() {
		if ( self::can_see_users() ) {
			return;
		}
		foreach ( get_post_types( array( 'show_in_rest' => true ) ) as $type ) {
			add_filter( 'rest_prepare_' . $type, array( __CLASS__, 'strip_rest_author' ), 99 );
		}
		add_filter( 'rest_prepare_comment', array( __CLASS__, 'strip_rest_author' ), 99 );
	}

	/**
	 * @param WP_REST_Response $response Response.
	 * @return WP_REST_Response
	 */
	public static function strip_rest_author( $response ) {
		if ( ! $response instanceof WP_REST_Response ) {
			return $response;
		}
		$data = $response->get_data();
		if ( is_array( $data ) ) {
			unset( $data['author'], $data['author_name'], $data['author_url'], $data['author_avatar_urls'] );
			$response->set_data( $data );
		}
		$response->remove_link( 'author' );
		return $response;
	}

	/**
	 * ?author=N és /author/<login>/ → 404 (még a kanonikus átirányítás előtt,
	 * mert az a felhasználónevet tenné a címsorba).
	 */
	public static function block_author_archives() {
		if ( self::can_see_users() ) {
			return;
		}
		if ( ! is_author() && ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		remove_action( 'template_redirect', 'redirect_canonical' );
	}

	/**
	 * @return string
	 */
	public static function author_link() {
		return home_url( '/' );
	}

	/**
	 * @param WP_Sitemaps_Provider $provider Provider.
	 * @param string               $name     Provider name.
	 * @return WP_Sitemaps_Provider|false
	 */
	public static function drop_user_sitemap( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * @param array $data oEmbed data.
	 * @return array
	 */
	public static function oembed_data( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
}
