<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Torneo_Save {
	public static function init() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_action( 'save_post_product', array( __CLASS__, 'sync_on_product_save' ), 20, 3 );
	}

	public static function sync_on_product_save( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $update && 'publish' !== $post->post_status ) {
			return;
		}

		$torneo_id = absint( get_post_meta( $post_id, '_cm_torneo_id', true ) );
		self::sync_product_featured_image_from_torneo_modalidad( $post_id, $torneo_id );
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

		delete_post_meta( $product_id, 'product_evento' );

		$current_torneo_id = absint( get_post_meta( $product_id, '_cm_torneo_id', true ) );
		self::sync_product_featured_image_from_torneo_modalidad( $product_id, $current_torneo_id );
	}

	private static function sync_product_featured_image_from_torneo_modalidad( $product_id, $torneo_id ) {
		if ( $torneo_id <= 0 || 'casino_poker_torneo' !== get_post_type( $torneo_id ) ) {
			delete_post_thumbnail( $product_id );
			return;
		}

		$modalidad_id = self::resolve_torneo_modalidad_id( $torneo_id );
		if ( $modalidad_id <= 0 ) {
			delete_post_thumbnail( $product_id );
			return;
		}

		$image_id = self::resolve_modalidad_image_attachment_id( $modalidad_id );
		if ( $image_id > 0 ) {
			set_post_thumbnail( $product_id, $image_id );
			return;
		}

		delete_post_thumbnail( $product_id );
	}

	private static function resolve_torneo_modalidad_id( $torneo_id ) {
		$raw_modalidad = get_post_meta( $torneo_id, 'torneo_modalidad', true );

		if ( is_numeric( $raw_modalidad ) ) {
			return absint( $raw_modalidad );
		}

		if ( is_array( $raw_modalidad ) && ! empty( $raw_modalidad ) ) {
			$first = reset( $raw_modalidad );

			if ( is_numeric( $first ) ) {
				return absint( $first );
			}

			if ( is_array( $first ) && isset( $first['ID'] ) && is_numeric( $first['ID'] ) ) {
				return absint( $first['ID'] );
			}
		}

		return 0;
	}

	private static function resolve_modalidad_image_attachment_id( $modalidad_id ) {
		$thumbnail_id = get_post_thumbnail_id( $modalidad_id );
		if ( $thumbnail_id > 0 ) {
			return absint( $thumbnail_id );
		}

		$raw_logo = get_post_meta( $modalidad_id, 'modalidad_logo', true );

		if ( is_numeric( $raw_logo ) ) {
			return absint( $raw_logo );
		}

		if ( is_array( $raw_logo ) && ! empty( $raw_logo ) ) {
			$first = reset( $raw_logo );

			if ( is_numeric( $first ) ) {
				return absint( $first );
			}

			if ( is_array( $first ) && isset( $first['ID'] ) && is_numeric( $first['ID'] ) ) {
				return absint( $first['ID'] );
			}
		}

		return 0;
	}
}
