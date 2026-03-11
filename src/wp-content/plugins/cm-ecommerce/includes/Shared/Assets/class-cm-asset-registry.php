<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Asset_Registry {

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		$assets = array(
			'cm-wc-product-type'    => array(
				'type'             => 'script',
				'module'           => 'ProductTypes',
				'feature'          => 'Core',
				'relative_path'    => 'js/cm-product-type.js',
				'deps'             => array( 'jquery', 'wc-enhanced-select', 'wc-admin-product-meta-boxes' ),
				'context'          => 'admin',
				'in_footer'        => true,
				'version_strategy' => 'auto',
			),
			'cm-shortcode-productos' => array(
				'type'             => 'style',
				'module'           => 'Catalog',
				'feature'          => 'ShortcodeProductos',
				'relative_path'    => 'css/cm-shortcode-productos.css',
				'deps'             => array(),
				'context'          => 'frontend',
				'version_strategy' => 'plugin',
			),
			'cm-shortcode-productos-torneo-css' => array(
				'type'             => 'style',
				'module'           => 'Catalog',
				'feature'          => 'ShortcodeProductos',
				'relative_path'    => 'css/cm-shortcode-productos-torneo.css',
				'deps'             => array( 'cm-shortcode-productos' ),
				'context'          => 'frontend',
				'version_strategy' => 'plugin',
			),
			'cm-shortcode-productos-torneo-js' => array(
				'type'             => 'script',
				'module'           => 'Catalog',
				'feature'          => 'ShortcodeProductos',
				'relative_path'    => 'js/cm-shortcode-productos-torneo.js',
				'deps'             => array(),
				'context'          => 'frontend',
				'in_footer'        => true,
				'version_strategy' => 'plugin',
			),
			'cm-single-product'   => array(
				'type'             => 'style',
				'module'           => 'Catalog',
				'feature'          => 'SingleProduct',
				'relative_path'    => 'css/cm-single-product.css',
				'deps'             => array(),
				'context'          => 'frontend',
				'version_strategy' => 'plugin',
			),
		);

		/**
		 * Filters the asset descriptors map.
		 *
		 * @param array<string,array<string,mixed>> $assets Asset descriptors by handle.
		 */
		return apply_filters( 'cm_ecommerce_asset_map', $assets );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public static function get( $handle ) {
		$assets = self::all();

		return isset( $assets[ $handle ] ) ? $assets[ $handle ] : null;
	}
}
