<?php
/**
 * WordPress admin integration.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_kmae_admin_delete_listing', array( $this, 'handle_delete_listing' ) );
		add_action( 'admin_post_kmae_admin_set_status', array( $this, 'handle_set_status' ) );
		add_action( 'admin_post_kmae_admin_reset_listing', array( $this, 'handle_reset_listing' ) );
		add_action( 'admin_post_kmae_report_update', array( $this, 'handle_report_update' ) );
		add_filter( 'manage_kmae_listing_posts_columns', array( $this, 'listing_columns' ) );
		add_action( 'manage_kmae_listing_posts_custom_column', array( $this, 'listing_column_content' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'listing_row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-kmae_listing', array( $this, 'listing_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-kmae_listing', array( $this, 'handle_listing_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		KMAE_Settings::init();
	}

	public function add_menu() {
		/* Piros számláló az új bejelentésekhez (oldalbetöltéskor számolva, nincs poll;
		   az új bejelentésről Web Push megy az adminnak). */
		$new_reports = class_exists( 'KMAE_Reports' ) ? KMAE_Reports::count_new() : 0;
		$bubble      = $new_reports > 0
			? ' <span class="awaiting-mod count-' . (int) $new_reports . '"><span class="pending-count">' . (int) $new_reports . '</span></span>'
			: '';

		add_menu_page(
			__( 'Albira', 'ki-mit-adna-erte' ),
			__( 'Albira', 'ki-mit-adna-erte' ) . $bubble,
			'manage_options',
			'kmae-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-store',
			30
		);

		add_submenu_page(
			'kmae-dashboard',
			__( 'Áttekintés', 'ki-mit-adna-erte' ),
			__( 'Áttekintés', 'ki-mit-adna-erte' ),
			'manage_options',
			'kmae-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'kmae-dashboard',
			__( 'Jelentések', 'ki-mit-adna-erte' ),
			__( 'Jelentések', 'ki-mit-adna-erte' ) . $bubble,
			'manage_options',
			'kmae-reports',
			array( $this, 'render_reports' )
		);

		add_submenu_page(
			'kmae-dashboard',
			__( 'Beállítások', 'ki-mit-adna-erte' ),
			__( 'Beállítások', 'ki-mit-adna-erte' ),
			'manage_options',
			'kmae-settings',
			array( $this, 'render_settings' )
		);
	}

	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) || empty( $_GET['kmae_notice'] ) ) {
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET['kmae_notice'] ) );
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'kmae_listing' !== $screen->post_type && ! str_contains( $screen->id ?? '', 'kmae' ) ) ) {
			return;
		}

		if ( in_array( $notice, array( 'listing_deleted', 'status_updated', 'bulk_done' ), true ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Művelet sikeres.', 'ki-mit-adna-erte' ) . '</p></div>';
		} elseif ( 'delete_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'A művelet sikertelen.', 'ki-mit-adna-erte' ) . '</p></div>';
		}
	}

	public function render_settings() {
		include KMAE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function render_reports() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}
		$report_id     = isset( $_GET['report'] ) ? absint( $_GET['report'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$report        = $report_id ? KMAE_Reports::get( $report_id ) : null;
		$filter_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : KMAE_Reports::STATUS_NEW; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'all' !== $filter_status && ! array_key_exists( $filter_status, KMAE_Reports::statuses() ) ) {
			$filter_status = KMAE_Reports::STATUS_NEW;
		}
		$counts  = KMAE_Reports::count_by_status();
		$reports = $report ? array() : KMAE_Reports::get_list( 'all' === $filter_status ? '' : $filter_status );
		$saved   = isset( $_GET['kmae_notice'] ) && 'report_saved' === $_GET['kmae_notice']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		include KMAE_PLUGIN_DIR . 'admin/views/reports.php';
	}

	public function handle_report_update() {
		$report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! $report_id ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}
		check_admin_referer( 'kmae_report_update_' . $report_id );

		$status = isset( $_POST['report_status'] ) ? sanitize_key( wp_unslash( $_POST['report_status'] ) ) : '';
		$note   = isset( $_POST['admin_note'] ) ? wp_unslash( $_POST['admin_note'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		KMAE_Reports::update_decision( $report_id, $status, $note, get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'kmae_notice', 'report_saved', KMAE_Reports::admin_url( $report_id ) ) );
		exit;
	}

	public function render_dashboard() {
		global $wpdb;

		$listings_count = wp_count_posts( 'kmae_listing' );
		$active         = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type = 'kmae_listing' AND pm.meta_key = '_kmae_status' AND pm.meta_value = 'active'"
		);
		$users_count  = count_users();
		$recent       = get_posts(
			array(
				'post_type'      => 'kmae_listing',
				'posts_per_page' => 10,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$notice = isset( $_GET['kmae_notice'] ) ? sanitize_key( wp_unslash( $_GET['kmae_notice'] ) ) : '';

		include KMAE_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function handle_delete_listing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}

		check_admin_referer( 'kmae_admin_delete_listing' );

		$listing_id = isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$redirect   = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=kmae_listing' );

		$result = KMAE_Listings::force_delete_listing( $listing_id );
		$notice = is_wp_error( $result ) ? 'delete_failed' : 'listing_deleted';

		wp_safe_redirect( add_query_arg( 'kmae_notice', $notice, $redirect ) );
		exit;
	}

	public function handle_reset_listing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}

		check_admin_referer( 'kmae_admin_reset_listing' );

		$listing_id = isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$redirect   = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=kmae_listing' );

		$result = KMAE_Listings::reset_listing( $listing_id );
		$notice = is_wp_error( $result ) ? 'delete_failed' : 'status_updated';

		wp_safe_redirect( add_query_arg( 'kmae_notice', $notice, $redirect ) );
		exit;
	}

	public function handle_set_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'ki-mit-adna-erte' ) );
		}

		check_admin_referer( 'kmae_admin_set_status' );

		$listing_id = isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$status     = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$redirect   = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=kmae_listing' );

		$allowed = array(
			KMAE_Meta::STATUS_ACTIVE,
			KMAE_Meta::STATUS_AWAITING_PHOTOS,
			KMAE_Meta::STATUS_CLOSED,
			KMAE_Meta::STATUS_ARCHIVED,
		);
		if ( $listing_id && in_array( $status, $allowed, true ) ) {
			$prev = KMAE_Meta::get_status( $listing_id );
			update_post_meta( $listing_id, '_kmae_status', $status );
			if ( KMAE_Meta::STATUS_ACTIVE === $status ) {
				update_post_meta( $listing_id, '_kmae_published_at', current_time( 'mysql' ) );
			}
			if ( KMAE_Meta::STATUS_ARCHIVED === $status && KMAE_Meta::STATUS_ARCHIVED !== $prev ) {
				update_post_meta( $listing_id, '_kmae_archived_at', current_time( 'mysql' ) );
			} elseif ( KMAE_Meta::STATUS_ARCHIVED !== $status && KMAE_Meta::STATUS_ARCHIVED === $prev ) {
				delete_post_meta( $listing_id, '_kmae_archived_at' );
			}
			$notice = 'status_updated';
		} else {
			$notice = 'delete_failed';
		}

		wp_safe_redirect( add_query_arg( 'kmae_notice', $notice, $redirect ) );
		exit;
	}

	public function listing_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['kmae_thumb'] = __( 'Kép', 'ki-mit-adna-erte' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				if ( KMAE_Vehicle::is_vehicle_launch() ) {
					$new['kmae_vehicle'] = __( 'Autó adatok', 'ki-mit-adna-erte' );
				}
				$new['kmae_author']      = __( 'Felhasználó', 'ki-mit-adna-erte' );
				$new['kmae_status']      = __( 'Státusz', 'ki-mit-adna-erte' );
			}
		}
		return $new;
	}

	public function listing_column_content( $column, $post_id ) {
		if ( 'kmae_thumb' === $column ) {
			$thumb = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
			if ( $thumb ) {
				echo '<img src="' . esc_url( $thumb ) . '" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;" />';
			} else {
				echo '—';
			}
		}
		if ( 'kmae_vehicle' === $column ) {
			echo esc_html( KMAE_Vehicle::get_swipe_subtitle( $post_id ) ?: '—' );
		}
		if ( 'kmae_author' === $column ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$user = get_userdata( $post->post_author );
				echo esc_html( $user ? $user->display_name : '—' );
			}
		}
		if ( 'kmae_status' === $column ) {
			echo wp_kses_post( self::status_badge( KMAE_Meta::get_status( $post_id ) ) );
		}
	}

	public function listing_row_actions( $actions, $post ) {
		if ( 'kmae_listing' !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$status   = KMAE_Meta::get_status( $post->ID );
		$base_url = admin_url( 'admin-post.php' );

		if ( KMAE_Meta::STATUS_ACTIVE !== $status ) {
			$actions['kmae_activate'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'     => 'kmae_admin_set_status',
								'listing_id' => $post->ID,
								'status'     => KMAE_Meta::STATUS_ACTIVE,
								'redirect'   => rawurlencode( admin_url( 'edit.php?post_type=kmae_listing' ) ),
							),
							$base_url
						),
						'kmae_admin_set_status'
					)
				),
				esc_html__( 'Aktiválás', 'ki-mit-adna-erte' )
			);
		}

		if ( KMAE_Meta::STATUS_ARCHIVED !== $status ) {
			$actions['kmae_archive'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'     => 'kmae_admin_set_status',
								'listing_id' => $post->ID,
								'status'     => KMAE_Meta::STATUS_ARCHIVED,
								'redirect'   => rawurlencode( admin_url( 'edit.php?post_type=kmae_listing' ) ),
							),
							$base_url
						),
						'kmae_admin_set_status'
					)
				),
				esc_html__( 'Archiválás', 'ki-mit-adna-erte' )
			);
		}

		if ( KMAE_Meta::STATUS_DRAFT !== $status ) {
			$actions['kmae_reset'] = sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'     => 'kmae_admin_reset_listing',
								'listing_id' => $post->ID,
								'redirect'   => rawurlencode( admin_url( 'edit.php?post_type=kmae_listing' ) ),
							),
							$base_url
						),
						'kmae_admin_reset_listing'
					)
				),
				esc_js( __( 'Újraindítod tiszta lappal? A hirdetés megmarad, de minden hozzá tartozó chat, ajánlat, eladás, bejelentés és kedvenc végleg törlődik, és újként, aktívan indul.', 'ki-mit-adna-erte' ) ),
				esc_html__( 'Újraindítás tiszta lappal', 'ki-mit-adna-erte' )
			);
		}

		$actions['kmae_delete'] = sprintf(
			'<a href="%s" style="color:#b32d2e;" onclick="return confirm(\'%s\');">%s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'kmae_admin_delete_listing',
							'listing_id' => $post->ID,
							'redirect'   => rawurlencode( admin_url( 'edit.php?post_type=kmae_listing' ) ),
						),
						$base_url
					),
					'kmae_admin_delete_listing'
				)
			),
			esc_js( __( 'Biztosan törlöd a hirdetést?', 'ki-mit-adna-erte' ) ),
			esc_html__( 'Végleges törlés', 'ki-mit-adna-erte' )
		);

		return $actions;
	}

	public function listing_bulk_actions( $actions ) {
		$actions['kmae_bulk_activate'] = __( 'Aktiválás', 'ki-mit-adna-erte' );
		$actions['kmae_bulk_archive']  = __( 'Archiválás', 'ki-mit-adna-erte' );
		$actions['kmae_bulk_delete']   = __( 'Végleges törlés', 'ki-mit-adna-erte' );
		return $actions;
	}

	public function handle_listing_bulk_actions( $redirect, $action, $post_ids ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $redirect;
		}

		$count = 0;
		foreach ( (array) $post_ids as $post_id ) {
			$post_id = absint( $post_id );
			if ( 'kmae_bulk_activate' === $action ) {
				$prev = KMAE_Meta::get_status( $post_id );
				update_post_meta( $post_id, '_kmae_status', KMAE_Meta::STATUS_ACTIVE );
				if ( KMAE_Meta::STATUS_ARCHIVED === $prev ) {
					delete_post_meta( $post_id, '_kmae_archived_at' );
				}
				++$count;
			} elseif ( 'kmae_bulk_archive' === $action ) {
				$prev = KMAE_Meta::get_status( $post_id );
				update_post_meta( $post_id, '_kmae_status', KMAE_Meta::STATUS_ARCHIVED );
				if ( KMAE_Meta::STATUS_ARCHIVED !== $prev ) {
					update_post_meta( $post_id, '_kmae_archived_at', current_time( 'mysql' ) );
				}
				++$count;
			} elseif ( 'kmae_bulk_delete' === $action ) {
				if ( ! is_wp_error( KMAE_Listings::force_delete_listing( $post_id ) ) ) {
					++$count;
				}
			}
		}

		if ( $count > 0 ) {
			$redirect = add_query_arg( 'kmae_notice', 'bulk_done', $redirect );
		}
		return $redirect;
	}

	public static function status_badge( $status ) {
		$labels = array(
			'active'          => __( 'Aktív', 'ki-mit-adna-erte' ),
			'awaiting_photos' => __( 'Fotó feltöltésre vár', 'ki-mit-adna-erte' ),
			'closed'          => __( 'Lezárva', 'ki-mit-adna-erte' ),
			'archived'        => __( 'Archivált', 'ki-mit-adna-erte' ),
			'expired'         => __( 'Lejárt', 'ki-mit-adna-erte' ),
		);
		$label = $labels[ $status ] ?? $status;
		return '<span class="kmae-admin-badge kmae-admin-badge-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
	}

	public static function admin_action_url( $action, $args = array() ) {
		$defaults = array(
			'action' => $action,
		);
		return wp_nonce_url(
			add_query_arg( array_merge( $defaults, $args ), admin_url( 'admin-post.php' ) ),
			$action
		);
	}

	public function enqueue( $hook ) {
		if ( str_contains( $hook, 'kmae' ) || in_array( $hook, array( 'edit.php', 'post.php', 'user-edit.php', 'profile.php' ), true ) ) {
			wp_enqueue_style( 'kmae-admin', KMAE_PLUGIN_URL . 'assets/css/admin.css', array(), KMAE_VERSION );
		}
	}
}
