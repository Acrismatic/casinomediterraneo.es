<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Single_Product_Template {

	const SUPPORTED_PRODUCT_TYPES = array( 'torneo-poker', 'evento' );

	public static function init() {
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_template' ), 20, 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function locate_template( $template, $template_name, $template_path ) {
		if ( 'single-product.php' !== $template_name || ! self::is_supported_product_context() ) {
			return $template;
		}

		if ( self::has_theme_override( $template, $template_name, $template_path ) ) {
			return $template;
		}

		$custom_template = self::resolve_plugin_template( $template_name );
		if ( '' !== $custom_template ) {
			return $custom_template;
		}

		return $template;
	}

	private static function has_theme_override( $template, $template_name, $template_path ) {
		unset( $template_path );

		if ( ! is_string( $template ) || '' === $template || ! file_exists( $template ) ) {
			return false;
		}

		$resolved_template = wp_normalize_path( $template );
		$plugin_templates  = wp_normalize_path( trailingslashit( CM_WC_EXT_PATH ) );

		if ( 0 === strpos( $resolved_template, $plugin_templates ) ) {
			return false;
		}

		if ( ! function_exists( 'WC' ) || ! WC() ) {
			return true;
		}

		$wc_default_template = wp_normalize_path( trailingslashit( WC()->plugin_path() ) . 'templates/' . ltrim( $template_name, '/' ) );

		return $resolved_template !== $wc_default_template;
	}

	private static function resolve_plugin_template( $template_name ) {
		$template_name = ltrim( (string) $template_name, '/' );

		$candidates = array(
			CM_WC_EXT_PATH . 'includes/Modules/Catalog/SingleProduct/Templates/woocommerce/' . $template_name,
			CM_WC_EXT_PATH . 'templates/woocommerce/' . $template_name,
			CM_WC_EXT_PATH . 'templates/' . $template_name,
		);

		foreach ( $candidates as $candidate ) {
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return '';
	}

	public static function enqueue_assets() {
		if ( ! self::is_supported_product_context() ) {
			return;
		}

		CM_Asset_Enqueuer::enqueue( 'cm-single-product' );
	}

	private static function is_supported_product_context() {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		$product_id = get_queried_object_id();
		if ( empty( $product_id ) ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( ! ( $product instanceof WC_Product ) ) {
			return false;
		}

		return in_array( $product->get_type(), self::SUPPORTED_PRODUCT_TYPES, true );
	}
}
