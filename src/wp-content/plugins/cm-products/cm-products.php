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

define( 'CM_PRODUCTS_VERSION', '1.0.0' );
define( 'CM_PRODUCTS_PATH', plugin_dir_path( __FILE__ ) );

if ( ! function_exists( 'cm_products_bootstrap' ) ) {
    function cm_products_bootstrap() {
        static $bootstrapped = false;

        if ( $bootstrapped ) {
            return;
        }

        require_once CM_PRODUCTS_PATH . 'includes/class-cm-products-tabs.php';
        require_once CM_PRODUCTS_PATH . 'includes/class-cm-products-fields.php';
        require_once CM_PRODUCTS_PATH . 'includes/class-cm-products-save.php';

        CM_Products_Tabs::init();
        CM_Products_Fields::init();
        CM_Products_Save::init();

        $bootstrapped = true;
    }
}

add_action( 'plugins_loaded', 'cm_products_bootstrap', 20 );