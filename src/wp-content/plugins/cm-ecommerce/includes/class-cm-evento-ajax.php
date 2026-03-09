<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Ajax {
	const NONCE_ACTION = 'cm_search_eventos';
	const POSTS_PER_PAGE = 20;

	public static function init() {
		add_action( 'wp_ajax_cm_search_eventos', array( __CLASS__, 'search_eventos' ) );
	}

	public static function search_eventos() {
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

		$query = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => self::POSTS_PER_PAGE,
				'paged'                  => $page,
				's'                      => $term,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'tax_query'              => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( 'evento' ),
					),
				),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$results = array();
		foreach ( $query->posts as $evento ) {
			$results[] = array(
				'id'   => (int) $evento->ID,
				'text' => get_the_title( $evento->ID ),
			);
		}

		wp_send_json_success(
			array(
				'results'    => $results,
				'pagination' => array(
					'more' => $page < (int) $query->max_num_pages,
				),
			)
		);
	}
}
