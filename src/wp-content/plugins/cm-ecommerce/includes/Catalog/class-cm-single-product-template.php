<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'cm_ecommerce_legacy_path_used', 'legacy-wrapper', array( 'wrapper' => __FILE__ ) );

require_once CM_WC_EXT_PATH . 'includes/Modules/Catalog/SingleProduct/Application/class-cm-single-product-template.php';
