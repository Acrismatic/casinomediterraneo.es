<?php

defined( 'ABSPATH' ) || exit;

$screaming_template = CM_WC_EXT_PATH . 'includes/Modules/Catalog/SingleProduct/Templates/woocommerce/single-product.php';

if ( file_exists( $screaming_template ) ) {
	do_action( 'cm_ecommerce_legacy_path_used', 'legacy-template-shim', array( 'shim' => __FILE__, 'target' => $screaming_template ) );
	require $screaming_template;
	return;
}

do_action( 'cm_ecommerce_legacy_path_used', 'legacy-template-fallback', array( 'shim' => __FILE__ ) );

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) {
	the_post();
	wc_get_template_part( 'content', 'single-product' );
}

do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
