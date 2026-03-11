<?php

define( 'WP_ADMIN', true );

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

if ( class_exists( 'CM_Plugin_Bootstrap' ) ) {
	CM_Plugin_Bootstrap::init();
}

$_GET['post_type'] = 'product';
set_current_screen( 'product' );
do_action( 'admin_enqueue_scripts', 'post-new.php' );

$types = apply_filters( 'product_type_selector', array() );
$tabs  = apply_filters( 'woocommerce_product_data_tabs', array() );

$registered_script = wp_scripts()->registered['cm-wc-product-type'] ?? null;
$script_deps       = $registered_script ? $registered_script->deps : array();

$result = array(
	'admin_types_torneo_evento' => isset( $types['torneo-poker'] ) && isset( $types['evento'] ),
	'admin_tab_torneo_info'     => isset( $tabs['torneo_info']['target'] ) && 'torneo_info_product_data' === $tabs['torneo_info']['target'],
	'admin_handle_registered'   => null !== $registered_script,
	'admin_handle_dependencies' => $script_deps,
	'admin_handle_deps_ok'      => in_array( 'jquery', $script_deps, true )
		&& in_array( 'wc-enhanced-select', $script_deps, true )
		&& in_array( 'wc-admin-product-meta-boxes', $script_deps, true ),
);

echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;
