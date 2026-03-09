<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Save {
	public static function init() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
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
}
