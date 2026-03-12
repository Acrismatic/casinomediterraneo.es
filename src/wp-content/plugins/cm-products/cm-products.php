<?php
/**
 * Plugin Name: CM Products
 * Description: Extensiones personalizadas.
 * Version: 1.0.0
 * Author: Casino Mediterráneo
 * Text Domain: cm-products
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;  
}

add_filter( 'woocommerce_product_data_tabs', 'cm_products_add_more_info_tab' );
add_action( 'woocommerce_product_data_panels', 'cm_products_render_more_info_panel' );
add_action( 'woocommerce_process_product_meta', 'cm_products_save_more_info_fields' );

/**
 * Add a custom product data tab for simple products.
 *
 * @param array $tabs Existing tabs.
 *
 * @return array
 */
function cm_products_add_more_info_tab( $tabs ) {
    $tabs['cm_more_info'] = array(
        'label'    => __( 'More information', 'cm-products' ),
        'target'   => 'cm_more_info_product_data',
        'class'    => array( 'show_if_simple' ),
        'priority' => 80,
    );

    return $tabs;
}

/**
 * Render custom fields inside product data tab panel.
 */
function cm_products_render_more_info_panel() {
    echo '<div id="cm_more_info_product_data" class="panel woocommerce_options_panel hidden">';

    woocommerce_wp_text_input(
        array(
            'id'          => '_cm_start_date',
            'label'       => __( 'Fecha de inicio', 'cm-products' ),
            'type'        => 'date',
            'description' => __( 'Selecciona la fecha de inicio.', 'cm-products' ),
            'desc_tip'    => true,
        )
    );

    woocommerce_wp_text_input(
        array(
            'id'          => '_cm_end_date',
            'label'       => __( 'Fecha de fin', 'cm-products' ),
            'type'        => 'date',
            'description' => __( 'Selecciona la fecha de fin.', 'cm-products' ),
            'desc_tip'    => true,
        )
    );

    echo '</div>';
}

/**
 * Save custom product fields.
 *
 * @param int $post_id Product ID.
 */
function cm_products_save_more_info_fields( $post_id ) {
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $start_date = isset( $_POST['_cm_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_cm_start_date'] ) ) : '';
    $end_date   = isset( $_POST['_cm_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_cm_end_date'] ) ) : '';

    update_post_meta( $post_id, '_cm_start_date', $start_date );
    update_post_meta( $post_id, '_cm_end_date', $end_date );
}