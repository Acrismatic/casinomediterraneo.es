<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Products_Save {
    public static function init() {
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_more_info_fields' ) );
    }

    /**
     * Save custom product fields.
     *
     * @param int $post_id Product ID.
     */
    public static function save_more_info_fields( $post_id ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $start_date = isset( $_POST['_cm_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_cm_start_date'] ) ) : '';
        $end_date   = isset( $_POST['_cm_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_cm_end_date'] ) ) : '';

        update_post_meta( $post_id, '_cm_start_date', $start_date );
        update_post_meta( $post_id, '_cm_end_date', $end_date );
    }
}