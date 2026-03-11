<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Modules_Catalog_Module {

	public static function get_id() {
		return 'catalog';
	}

	public static function init() {
		require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/ShortcodeProductos/Application/class-cm-shortcode-productos.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/ShortcodeProductos/Application/class-cm-shortcode-productos-query.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/ShortcodeProductos/Application/class-cm-shortcode-productos-renderer.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/ShortcodeProductos/Application/class-cm-shortcode-productos-ajax.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/SingleProduct/Application/class-cm-single-product-template.php';

		CM_Shortcode_Productos::init();
		CM_Shortcode_Productos_Ajax::init();
		CM_Single_Product_Template::init();
	}
}
