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

if ( ! function_exists( 'cm_wc_extensions_bootstrap' ) ) {
	function cm_wc_extensions_bootstrap() {
		static $bootstrapped = false;

		if ( $bootstrapped ) {
			return;
		}

		require_once CM_WC_EXT_PATH . 'includes/class-cm-product-types.php';
		require_once CM_WC_EXT_PATH . 'includes/class-cm-torneo-fields.php';
		require_once CM_WC_EXT_PATH . 'includes/class-cm-evento-fields.php';
		require_once CM_WC_EXT_PATH . 'includes/class-cm-torneo-ajax.php';
		require_once CM_WC_EXT_PATH . 'includes/class-cm-evento-ajax.php';
		require_once CM_WC_EXT_PATH . 'includes/class-cm-torneo-save.php';

		CM_Product_Types::init();
		CM_Torneo_Fields::init();
		CM_Evento_Fields::init();
		CM_Torneo_Ajax::init();
		CM_Evento_Ajax::init();
		CM_Torneo_Save::init();

		$bootstrapped = true;
	}
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

		if ( did_action( 'woocommerce_loaded' ) ) {
			cm_wc_extensions_bootstrap();
			return;
		}

		add_action( 'woocommerce_loaded', 'cm_wc_extensions_bootstrap', 20 );
	}
);
