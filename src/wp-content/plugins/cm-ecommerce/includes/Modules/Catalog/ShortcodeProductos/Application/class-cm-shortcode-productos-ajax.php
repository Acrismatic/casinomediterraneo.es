<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Shortcode_Productos_Ajax {
	const ACTION       = 'cm_productos_torneo_load_more';
	const NONCE_ACTION = 'cm_productos_torneo_load_more';

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle_load_more' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'handle_load_more' ) );
	}

	public static function handle_load_more() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::send_error( 'invalid_nonce', __( 'No autorizado.', 'cm-wc-extensions' ), 403 );
		}

		$normalized = CM_Shortcode_Productos_Query::normalize_ajax_payload( $_POST );

		if ( is_wp_error( $normalized ) ) {
			self::send_error( 'invalid_payload', __( 'Solicitud invalida.', 'cm-wc-extensions' ), 400 );
		}

		$result = CM_Shortcode_Productos_Query::get_more_payload( $normalized );

		if ( is_wp_error( $result ) ) {
			self::send_error( 'query_error', __( 'No pudimos obtener mas torneos.', 'cm-wc-extensions' ), 500 );
		}

		$items_html = CM_Shortcode_Productos_Renderer::render_items_html( $result['items'] );

		wp_send_json_success(
			array(
				'items_html'    => $items_html,
				'loaded_count'  => $result['loaded_count'],
				'has_more'      => $result['has_more'],
				'next_cursor'   => $result['next_cursor'],
				'already_loaded' => isset( $normalized['already_loaded'] ) ? absint( $normalized['already_loaded'] ) : 0,
				'message'       => '',
			)
		);
	}

	private static function send_error( $code, $message, $status ) {
		wp_send_json_error(
			array(
				'code'    => sanitize_key( (string) $code ),
				'message' => (string) $message,
			),
			absint( $status )
		);
	}
}
