<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Plugin {
	public static function init() {
		do_action( 'cm_ecommerce_legacy_path_used', 'legacy-wrapper', array( 'wrapper' => __FILE__ ) );
		require_once CM_WC_EXT_PATH . 'includes/Bootstrap/class-cm-plugin-bootstrap.php';
		CM_Plugin_Bootstrap::init();
	}
}
