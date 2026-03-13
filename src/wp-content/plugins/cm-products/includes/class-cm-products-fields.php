<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Products_Fields {
    public static function init() {
        add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_more_info_panel' ) );
    }

    /**
     * Render custom fields inside product data tab panel.
     */
    public static function render_more_info_panel() {
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
}