<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Torneo_Ajax {
	const NONCE_ACTION = 'cm_search_torneos';
	const POSTS_PER_PAGE = 20;

	public static function init() {
		add_action( 'wp_ajax_cm_search_torneos', array( __CLASS__, 'search_torneos' ) );
		add_action( 'save_post_casino_poker_torneo', array( __CLASS__, 'clear_month_cache' ) );
		add_action( 'delete_post', array( __CLASS__, 'clear_month_cache_on_delete' ) );
	}

	public static function search_torneos() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'cm-wc-extensions' ) ), 403 );
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'No autorizado.', 'cm-wc-extensions' ) ), 403 );
		}

		$term = '';
		if ( isset( $_GET['term'] ) ) {
			$term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		}

		$page = 1;
		if ( isset( $_GET['page'] ) ) {
			$page = max( 1, absint( wp_unslash( $_GET['page'] ) ) );
		}

		if ( '' === $term ) {
			$payload = self::get_current_month_results( $page );
			wp_send_json_success( $payload );
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'casino_poker_torneo',
				'post_status'            => 'publish',
				'posts_per_page'         => self::POSTS_PER_PAGE,
				'paged'                  => $page,
				's'                      => $term,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		wp_send_json_success(
			array(
				'results'    => self::map_results( $query->posts ),
				'pagination' => array(
					'more' => $page < (int) $query->max_num_pages,
				),
			)
		);
	}

	private static function get_current_month_results( $page ) {
		$current_month = gmdate( 'Y-m' );
		$cache_key     = 'cm_torneos_month_' . $current_month . '_p' . $page;
		$cached        = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$month_start = gmdate( 'Y-m-01' );
		$month_end   = gmdate( 'Y-m-t' );

		$query = new WP_Query(
			array(
				'post_type'              => 'casino_poker_torneo',
				'post_status'            => 'publish',
				'posts_per_page'         => self::POSTS_PER_PAGE,
				'paged'                  => $page,
				'orderby'                => 'meta_value',
				'order'                  => 'ASC',
				'meta_key'               => 'torneo_fecha',
				'meta_query'             => array(
					array(
						'key'     => 'torneo_fecha',
						'value'   => array( $month_start, $month_end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$payload = array(
			'results'    => self::map_results( $query->posts ),
			'pagination' => array(
				'more' => $page < (int) $query->max_num_pages,
			),
		);

		set_transient( $cache_key, $payload, HOUR_IN_SECONDS );

		return $payload;
	}

	private static function map_results( $posts ) {
		$results = array();

		if ( empty( $posts ) ) {
			return $results;
		}

		foreach ( $posts as $torneo ) {
			$post_id = (int) $torneo->ID;
			$date    = get_post_meta( $post_id, 'torneo_fecha', true );

			$label = get_the_title( $post_id );
			if ( ! empty( $date ) ) {
				$timestamp = strtotime( (string) $date );
				if ( false !== $timestamp ) {
					$label .= ' - ' . wp_date( 'd/m/Y', $timestamp );
				}
			}

			$results[] = array(
				'id'   => $post_id,
				'text' => $label,
			);
		}

		return $results;
	}

	public static function clear_month_cache() {
		global $wpdb;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_cm_torneos_month_%'
			)
		);

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $option_name ) {
			$transient_key = str_replace( '_transient_', '', (string) $option_name );
			delete_transient( $transient_key );
		}
	}

	public static function clear_month_cache_on_delete( $post_id ) {
		if ( 'casino_poker_torneo' !== get_post_type( $post_id ) ) {
			return;
		}

		self::clear_month_cache();
	}
}
