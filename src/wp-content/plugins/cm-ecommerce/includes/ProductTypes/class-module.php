<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Module_ProductTypes {

	public static function get_id() {
		return 'product-types';
	}

	public static function init() {
		do_action( 'cm_ecommerce_legacy_path_used', 'legacy-wrapper', array( 'wrapper' => __FILE__ ) );
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/Bootstrap/class-module.php';
		CM_Modules_ProductTypes_Module::init();
	}
}
