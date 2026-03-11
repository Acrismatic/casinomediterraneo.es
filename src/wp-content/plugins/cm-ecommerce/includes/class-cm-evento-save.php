<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Save {
	public static function init() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_shortcode( 'PRODUCTOS_EVENTO', array( __CLASS__, 'render_productos_evento_shortcode' ) );
	}

	public static function save_fields( $product_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		$previous_evento_id = absint( get_post_meta( $product_id, '_cm_evento_id', true ) );

		if ( ! isset( $_POST['_cm_evento_id'] ) ) {
			delete_post_meta( $product_id, '_cm_evento_id' );
			if ( $previous_evento_id > 0 ) {
				delete_post_meta( $previous_evento_id, 'evento_producto_id' );
			}

			return;
		}

		$evento_id   = absint( wp_unslash( $_POST['_cm_evento_id'] ) );
		$evento_type = '';
		if ( $evento_id > 0 && 'product' === get_post_type( $evento_id ) ) {
			$evento_type = WC_Product_Factory::get_product_type( $evento_id );
		}

		if ( $evento_id > 0 && $evento_id !== $product_id && 'evento' === $evento_type ) {
			update_post_meta( $product_id, '_cm_evento_id', $evento_id );
			update_post_meta( $evento_id, 'evento_producto_id', $product_id );

			if ( $previous_evento_id > 0 && $previous_evento_id !== $evento_id ) {
				delete_post_meta( $previous_evento_id, 'evento_producto_id' );
			}

			return;
		}

		delete_post_meta( $product_id, '_cm_evento_id' );
		if ( $previous_evento_id > 0 ) {
			delete_post_meta( $previous_evento_id, 'evento_producto_id' );
		}
	}

	/**
	 * Renderizar el shortcode [PRODUCTOS_EVENTO]
	 */
	public static function render_productos_evento_shortcode( $atts = array() ) {
		// Verificar si WooCommerce está activo y el carrito está disponible
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '<p>' . esc_html__( 'WooCommerce no está disponible en este momento.', 'cm-wc-extensions' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'limit'   => 12,
				'columns' => 4,
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$atts,
			'PRODUCTOS_EVENTO'
		);

		$limit   = max( 1, absint( $atts['limit'] ) );
		$columns = max( 1, absint( $atts['columns'] ) );
		$order   = 'ASC' === strtoupper( (string) $atts['order'] ) ? 'ASC' : 'DESC';

		$allowed_orderby = array( 'date', 'title', 'menu_order', 'rand', 'ID' );
		$orderby         = in_array( $atts['orderby'], $allowed_orderby, true ) ? $atts['orderby'] : 'date';

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'order'          => $order,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( 'evento' ),
					),
				),
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No hay productos de tipo evento disponibles.', 'cm-wc-extensions' ) . '</p>';
		}

		ob_start();

		wc_set_loop_prop( 'columns', $columns );
		woocommerce_product_loop_start();

		while ( $query->have_posts() ) {
			$query->the_post();
			wc_get_template_part( 'content', 'product' );
		}

		woocommerce_product_loop_end();
		wp_reset_postdata();

		return ob_get_clean();
	}
}
