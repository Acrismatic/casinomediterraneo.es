<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Torneo_Save {
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

		$previous_torneo_id = absint( get_post_meta( $product_id, '_cm_torneo_id', true ) );

		if ( ! isset( $_POST['_cm_torneo_id'] ) ) {
			delete_post_meta( $product_id, '_cm_torneo_id' );
			if ( $previous_torneo_id > 0 ) {
				delete_post_meta( $previous_torneo_id, 'torneo_producto_id' );
			}
		} else {
			$torneo_id = absint( wp_unslash( $_POST['_cm_torneo_id'] ) );

			if ( $torneo_id > 0 && 'casino_poker_torneo' === get_post_type( $torneo_id ) ) {
				update_post_meta( $product_id, '_cm_torneo_id', $torneo_id );
				update_post_meta( $torneo_id, 'torneo_producto_id', $product_id );

				if ( $previous_torneo_id > 0 && $previous_torneo_id !== $torneo_id ) {
					delete_post_meta( $previous_torneo_id, 'torneo_producto_id' );
				}
			} else {
				delete_post_meta( $product_id, '_cm_torneo_id' );

				if ( $previous_torneo_id > 0 ) {
					delete_post_meta( $previous_torneo_id, 'torneo_producto_id' );
				}
			}
		}

	}
}
