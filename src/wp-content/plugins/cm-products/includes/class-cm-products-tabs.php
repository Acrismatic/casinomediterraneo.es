<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Products_Tabs {
    public static function init() {
        add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_more_info_tab' ) );
    }

    /**
     * Add a custom product data tab for simple products.
     *
     * @param array $tabs Existing tabs.
     *
     * @return array
     */
    public static function add_more_info_tab( $tabs ) {
        $tabs['cm_more_info'] = array(
            'label'    => __( 'More information', 'cm-products' ),
            'target'   => 'cm_more_info_product_data',
            'class'    => array( 'show_if_simple' ),
            'priority' => 80,
        );

        return $tabs;
    }
}