<?php
/**
 * Plugin Name: CM WC Extensions
 * Description: Extensiones personalizadas para tipos de producto en WooCommerce.
 * Version: 1.0.0
 * Author: Casino Mediterráneo
 * Text Domain: cm-wc-extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CM_WC_EXT_VERSION', '1.0.0' );
define( 'CM_WC_EXT_PATH', plugin_dir_path( __FILE__ ) );
define( 'CM_WC_EXT_URL', plugin_dir_url( __FILE__ ) );

if ( ! function_exists( 'cm_wc_extensions_is_woocommerce_active' ) ) {
	function cm_wc_extensions_is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) || defined( 'WC_ABSPATH' );
	}
}

add_filter( 'woocommerce_locate_template', 'cm_ecommerce_override_templates', 10, 3 );

/**
 * Override WooCommerce templates from this plugin.
 */
function cm_ecommerce_override_templates( $template, $template_name, $template_path ) {
	$plugin_path     = plugin_dir_path( __FILE__ ) . 'templates/';
	$custom_template = $plugin_path . $template_name;

	if ( file_exists( $custom_template ) ) {
		return $custom_template;
	}

	return $template;
}

add_action(
	'plugins_loaded',
	function() {
		if ( ! cm_wc_extensions_is_woocommerce_active() ) {
			add_action(
				'admin_notices',
				function() {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}

					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'CM WC Extensions requiere WooCommerce activo.', 'cm-wc-extensions' );
					echo '</p></div>';
				}
			);

			return;
		}

		require_once CM_WC_EXT_PATH . 'includes/Bootstrap/class-cm-plugin-bootstrap.php';

		if ( did_action( 'woocommerce_loaded' ) ) {
			CM_Plugin_Bootstrap::init();
			return;
		}

		add_action( 'woocommerce_loaded', array( 'CM_Plugin_Bootstrap', 'init' ), 20 );
	}
);
