<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( class_exists( 'CM_Plugin_Bootstrap' ) ) {
	CM_Plugin_Bootstrap::init();
}

$results = array(
	'gate_1_environment' => array(),
	'gate_2_smoke'       => array(),
	'gate_3_rollback'    => array(),
);

$pass = static function ( $value ) {
	return true === $value;
};

$contains = static function ( $haystack, $needle ) {
	return is_string( $haystack ) && false !== strpos( $haystack, $needle );
};

$extract_attr = static function ( $html, $attr ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return '';
	}

	$pattern = '/\b' . preg_quote( $attr, '/' ) . '="([^"]*)"/';
	if ( preg_match( $pattern, $html, $matches ) ) {
		return isset( $matches[1] ) ? (string) $matches[1] : '';
	}

	return '';
};

$count_occurrences = static function ( $html, $needle ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return 0;
	}

	return substr_count( $html, $needle );
};

$ensure_product = static function ( $slug, $title, $type, $days_offset = 0 ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $post ) {
		$id = (int) $post->ID;
	} else {
		$date_gmt = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days_offset ) . ' days' ) );
		$id       = wp_insert_post(
			array(
				'post_type'     => 'product',
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_name'     => $slug,
				'post_content'  => $title,
				'post_date_gmt' => $date_gmt,
				'post_date'     => get_date_from_gmt( $date_gmt ),
			)
		);
	}

	if ( is_wp_error( $id ) || $id <= 0 ) {
		return 0;
	}

	wp_set_object_terms( $id, $type, 'product_type', false );
	update_post_meta( $id, '_price', '10' );
	update_post_meta( $id, '_regular_price', '10' );
	update_post_meta( $id, '_stock_status', 'instock' );
	update_post_meta( $id, '_manage_stock', 'no' );

	return (int) $id;
};

$ensure_page = static function () {
	$post = get_page_by_path( 'sdd-productos', OBJECT, 'page' );
	if ( $post ) {
		$id = (int) $post->ID;
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'SDD Productos',
				'post_name'    => 'sdd-productos',
				'post_content' => "[PRODUCTOS]\n[productos_torneo cantidad=\"3\"]\n[PRODUCTOS tipo=\"evento\" limit=\"1\"]",
			)
		);
	}

	if ( ! is_wp_error( $id ) && $id > 0 ) {
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => "[PRODUCTOS]\n[productos_torneo cantidad=\"3\"]\n[PRODUCTOS tipo=\"evento\" limit=\"1\"]",
			)
		);
	}

	return is_wp_error( $id ) ? 0 : (int) $id;
};

$torneo_products = array();
for ( $i = 1; $i <= 15; $i++ ) {
	$torneo_products[] = $ensure_product( 'sdd-torneo-poker-' . $i, 'SDD Torneo Poker ' . $i, 'torneo-poker', $i );
}

$evento_id = $ensure_product( 'sdd-evento', 'SDD Evento', 'evento', 2 );
$simple_id = $ensure_product( 'sdd-simple', 'SDD Simple', 'simple', 3 );
$page_id   = $ensure_page();

$torneo_ref = get_page_by_path( 'sdd-torneo-ref', OBJECT, CM_Product_Torneo_Query::POST_TYPE_TORNEO );
if ( $torneo_ref ) {
	$torneo_ref_id = (int) $torneo_ref->ID;
} else {
	$torneo_ref_id = wp_insert_post(
		array(
			'post_type'   => CM_Product_Torneo_Query::POST_TYPE_TORNEO,
			'post_status' => 'publish',
			'post_title'  => 'SDD Torneo Ref',
			'post_name'   => 'sdd-torneo-ref',
		)
	);
}

if ( ! is_wp_error( $torneo_ref_id ) && $torneo_ref_id > 0 ) {
	update_post_meta( $torneo_ref_id, 'torneo_fecha', gmdate( 'Y-m-d' ) );
	update_post_meta( $torneo_ref_id, 'torneo_hora', '20:00' );
	foreach ( $torneo_products as $torneo_product_id ) {
		if ( $torneo_product_id > 0 ) {
			update_post_meta( $torneo_product_id, CM_Product_Torneo_Query::META_TORNEO_ID, (int) $torneo_ref_id );
		}
	}
}

$torneo_id = ! empty( $torneo_products ) ? max( array_map( 'absint', $torneo_products ) ) : 0;

$torneo_url = $torneo_id > 0 ? get_permalink( $torneo_id ) : '';
$evento_url = $evento_id > 0 ? get_permalink( $evento_id ) : '';
$page_url   = $page_id > 0 ? get_permalink( $page_id ) : '';

$results['gate_1_environment'] = array(
	'php_version'        => PHP_VERSION,
	'wp_loaded'          => defined( 'ABSPATH' ),
	'wc_active'          => is_plugin_active( 'woocommerce/woocommerce.php' ),
	'cm_active'          => is_plugin_active( 'cm-ecommerce/cm-ecommerce.php' ),
	'bootstrap_class'    => class_exists( 'CM_Plugin_Bootstrap' ),
	'torneo_count'       => count( array_filter( $torneo_products ) ),
	'evento_id'          => $evento_id,
	'simple_id'          => $simple_id,
	'page_id'            => $page_id,
	'torneo_url'         => $torneo_url,
	'evento_url'         => $evento_url,
	'page_url'           => $page_url,
	'environment_passed' => $pass( count( array_filter( $torneo_products ) ) >= 14 && $evento_id > 0 && $simple_id > 0 && $page_id > 0 && class_exists( 'CM_Plugin_Bootstrap' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'cm-ecommerce/cm-ecommerce.php' ) ),
);

$types = apply_filters( 'product_type_selector', array() );
$tabs  = apply_filters( 'woocommerce_product_data_tabs', array() );

$_GET['post_type']  = 'product';
$GLOBALS['pagenow'] = 'post-new.php';
do_action( 'admin_enqueue_scripts', 'post-new.php' );

$registered_script = wp_scripts()->registered['cm-wc-product-type'] ?? null;
$script_deps       = $registered_script ? $registered_script->deps : array();

$shortcode_default        = do_shortcode( '[PRODUCTOS limit="1"]' );
$shortcode_torneo_new     = do_shortcode( '[productos_torneo]' );
$shortcode_torneo_custom  = do_shortcode( '[productos_torneo cantidad="8"]' );
$shortcode_torneo_invalid = do_shortcode( '[productos_torneo cantidad="abc"]' );
$shortcode_torneo_legacy  = do_shortcode( '[PRODUCTOS tipo="torneo" limit="1"]' );

$initial_count_default = $count_occurrences( $shortcode_torneo_new, 'cm-productos__item--torneo' );
$initial_count_custom  = $count_occurrences( $shortcode_torneo_custom, 'cm-productos__item--torneo' );
$initial_count_invalid = $count_occurrences( $shortcode_torneo_invalid, 'cm-productos__item--torneo' );

$cursor_date = $extract_attr( $shortcode_torneo_new, 'data-next-cursor-date' );
$cursor_id   = absint( $extract_attr( $shortcode_torneo_new, 'data-next-cursor-id' ) );

$ajax_success = array();
$ajax_invalid = array();
$ajax_exhaust = array();

$ajax_url = admin_url( 'admin-ajax.php' );
$nonce    = wp_create_nonce( CM_Shortcode_Productos_Ajax::NONCE_ACTION );

if ( '' !== $ajax_url && '' !== $cursor_date && $cursor_id > 0 ) {
	$ajax_res = wp_remote_post(
		$ajax_url,
		array(
			'timeout' => 20,
			'body'    => array(
				'action'          => CM_Shortcode_Productos_Ajax::ACTION,
				'nonce'           => $nonce,
				'cursor_date_gmt' => $cursor_date,
				'cursor_id'       => $cursor_id,
				'already_loaded'  => 3,
			),
		)
	);

	if ( ! is_wp_error( $ajax_res ) ) {
		$ajax_success = json_decode( (string) wp_remote_retrieve_body( $ajax_res ), true );
	}

	$ajax_invalid_res = wp_remote_post(
		$ajax_url,
		array(
			'timeout' => 20,
			'body'    => array(
				'action'          => CM_Shortcode_Productos_Ajax::ACTION,
				'nonce'           => 'invalid',
				'cursor_date_gmt' => $cursor_date,
				'cursor_id'       => $cursor_id,
				'already_loaded'  => 3,
			),
		)
	);

	if ( ! is_wp_error( $ajax_invalid_res ) ) {
		$ajax_invalid = json_decode( (string) wp_remote_retrieve_body( $ajax_invalid_res ), true );
	}

	$next_cursor = isset( $ajax_success['data']['next_cursor'] ) && is_array( $ajax_success['data']['next_cursor'] ) ? $ajax_success['data']['next_cursor'] : array();
	$next_date   = isset( $next_cursor['date_gmt'] ) ? (string) $next_cursor['date_gmt'] : $cursor_date;
	$next_id     = isset( $next_cursor['id'] ) ? absint( $next_cursor['id'] ) : $cursor_id;
	$has_more    = isset( $ajax_success['data']['has_more'] ) ? (bool) $ajax_success['data']['has_more'] : false;

	for ( $attempt = 0; $attempt < 4 && $has_more && '' !== $next_date && $next_id > 0; $attempt++ ) {
		$ajax_exhaust_res = wp_remote_post(
			$ajax_url,
			array(
				'timeout' => 20,
				'body'    => array(
					'action'          => CM_Shortcode_Productos_Ajax::ACTION,
					'nonce'           => $nonce,
					'cursor_date_gmt' => $next_date,
					'cursor_id'       => $next_id,
					'already_loaded'  => 3 + ( $attempt + 1 ) * 10,
				),
			)
		);

		if ( is_wp_error( $ajax_exhaust_res ) ) {
			break;
		}

		$ajax_exhaust = json_decode( (string) wp_remote_retrieve_body( $ajax_exhaust_res ), true );
		if ( ! is_array( $ajax_exhaust ) || empty( $ajax_exhaust['success'] ) ) {
			break;
		}

		$next_cursor = isset( $ajax_exhaust['data']['next_cursor'] ) && is_array( $ajax_exhaust['data']['next_cursor'] ) ? $ajax_exhaust['data']['next_cursor'] : array();
		$next_date   = isset( $next_cursor['date_gmt'] ) ? (string) $next_cursor['date_gmt'] : '';
		$next_id     = isset( $next_cursor['id'] ) ? absint( $next_cursor['id'] ) : 0;
		$has_more    = isset( $ajax_exhaust['data']['has_more'] ) ? (bool) $ajax_exhaust['data']['has_more'] : false;
	}
}

$ajax_success_loaded = isset( $ajax_success['data']['loaded_count'] ) ? absint( $ajax_success['data']['loaded_count'] ) : 0;
$ajax_success_items  = isset( $ajax_success['data']['items_html'] ) ? (string) $ajax_success['data']['items_html'] : '';
$ajax_success_more   = isset( $ajax_success['data']['has_more'] ) ? (bool) $ajax_success['data']['has_more'] : false;

$ajax_invalid_code = isset( $ajax_invalid['data']['code'] ) ? (string) $ajax_invalid['data']['code'] : '';
$ajax_end_has_more = isset( $ajax_exhaust['data']['has_more'] ) ? (bool) $ajax_exhaust['data']['has_more'] : true;

$page_response   = '';
$torneo_response = '';
$evento_response = '';

if ( '' !== $page_url ) {
	$page_http     = wp_remote_get( $page_url );
	$page_response = is_wp_error( $page_http ) ? '' : (string) wp_remote_retrieve_body( $page_http );
}

if ( '' !== $torneo_url ) {
	$torneo_http     = wp_remote_get( $torneo_url );
	$torneo_response = is_wp_error( $torneo_http ) ? '' : (string) wp_remote_retrieve_body( $torneo_http );
}

if ( '' !== $evento_url ) {
	$evento_http     = wp_remote_get( $evento_url );
	$evento_response = is_wp_error( $evento_http ) ? '' : (string) wp_remote_retrieve_body( $evento_http );
}

$results['gate_2_smoke'] = array(
	'admin_types_torneo_evento'         => $pass( isset( $types['torneo-poker'] ) && isset( $types['evento'] ) ),
	'admin_tab_torneo_info'             => $pass( isset( $tabs['torneo_info']['target'] ) && 'torneo_info_product_data' === $tabs['torneo_info']['target'] ),
	'admin_handle_registered'           => $pass( null !== $registered_script ),
	'admin_handle_dependencies'         => $script_deps,
	'admin_handle_deps_ok'              => $pass( in_array( 'jquery', $script_deps, true ) && in_array( 'wc-enhanced-select', $script_deps, true ) && in_array( 'wc-admin-product-meta-boxes', $script_deps, true ) ),
	'shortcode_default_class'           => $contains( $shortcode_default, 'cm-productos--default' ),
	'shortcode_torneo_new_class'        => $contains( $shortcode_torneo_new, 'cm-productos--torneo' ),
	'shortcode_torneo_default_3'        => $pass( 3 === $initial_count_default ),
	'shortcode_torneo_custom_8'         => $pass( 8 === $initial_count_custom ),
	'shortcode_torneo_invalid_fallback' => $pass( 3 === $initial_count_invalid ),
	'shortcode_legacy_torneo_stopped'   => ! $contains( $shortcode_torneo_legacy, 'cm-productos--torneo' ),
	'shortcode_add_to_cart_buttons'     => $contains( $shortcode_torneo_new . $shortcode_default, 'ajax_add_to_cart' ),
	'shortcode_style_enqueued'          => wp_style_is( 'cm-shortcode-productos', 'enqueued' ),
	'shortcode_torneo_style_enqueued'   => wp_style_is( 'cm-shortcode-productos-torneo-css', 'enqueued' ),
	'shortcode_torneo_js_enqueued'      => wp_script_is( 'cm-shortcode-productos-torneo-js', 'enqueued' ),
	'shortcode_wc_add_to_cart_queue'    => wp_script_is( 'wc-add-to-cart', 'enqueued' ),
	'ajax_success_contract'             => $pass( ! empty( $ajax_success['success'] ) && 10 === $ajax_success_loaded && $contains( $ajax_success_items, 'cm-productos__item--torneo' ) && true === $ajax_success_more ),
	'ajax_invalid_nonce'                => $pass( empty( $ajax_invalid['success'] ) && 'invalid_nonce' === $ajax_invalid_code ),
	'ajax_end_of_list'                  => $pass( false === $ajax_end_has_more ),
	'page_contains_shortcode_markup'    => $contains( $page_response, 'cm-productos-torneo' ),
	'torneo_single_status_ok'           => $contains( $torneo_response, 'woocommerce' ) || $contains( $torneo_response, 'product' ),
	'evento_single_status_ok'           => $contains( $evento_response, 'woocommerce' ) || $contains( $evento_response, 'product' ),
	'single_style_handle_present'       => $contains( $torneo_response . $evento_response, 'cm-single-product-css' ),
);

$module_template = CM_WC_EXT_PATH . 'includes/Modules/Catalog/SingleProduct/Templates/woocommerce/single-product.php';
$module_backup   = $module_template . '.bak_sdd';
$rollback_ok     = false;
$restore_ok      = false;

if ( file_exists( $module_template ) ) {
	$renamed = @rename( $module_template, $module_backup );
	if ( $renamed ) {
		$test_http = '';
		if ( '' !== $torneo_url ) {
			$http      = wp_remote_get( $torneo_url );
			$test_http = is_wp_error( $http ) ? '' : (string) wp_remote_retrieve_body( $http );
		}
		$rollback_ok = '' !== $test_http;
		$restore_ok  = @rename( $module_backup, $module_template );
	}
}

$add_to_cart_ok = false;
if ( $torneo_id > 0 && '' !== $torneo_url ) {
	$add_url = add_query_arg( 'add-to-cart', $torneo_id, home_url( '/' ) );
	$add_res = wp_remote_get( $add_url );
	if ( ! is_wp_error( $add_res ) ) {
		$code           = (int) wp_remote_retrieve_response_code( $add_res );
		$add_to_cart_ok = $code >= 200 && $code < 400;
	}
}

$results['gate_3_rollback'] = array(
	'module_template'             => $module_template,
	'shim_template_exists'        => file_exists( CM_WC_EXT_PATH . 'templates/woocommerce/single-product.php' ),
	'simulated_template_rollback' => $rollback_ok,
	'module_template_restored'    => $restore_ok,
	'add_to_cart_http_ok'         => $add_to_cart_ok,
	'rollback_gate_passed'        => $pass( $rollback_ok && $restore_ok ),
);

$results['overall'] = array(
	'gate_1' => $results['gate_1_environment']['environment_passed'],
	'gate_2' => $pass(
		$results['gate_2_smoke']['admin_types_torneo_evento']
		&& $results['gate_2_smoke']['admin_tab_torneo_info']
		&& $results['gate_2_smoke']['admin_handle_registered']
		&& $results['gate_2_smoke']['admin_handle_deps_ok']
		&& $results['gate_2_smoke']['shortcode_default_class']
		&& $results['gate_2_smoke']['shortcode_torneo_new_class']
		&& $results['gate_2_smoke']['shortcode_torneo_default_3']
		&& $results['gate_2_smoke']['shortcode_torneo_custom_8']
		&& $results['gate_2_smoke']['shortcode_torneo_invalid_fallback']
		&& $results['gate_2_smoke']['shortcode_legacy_torneo_stopped']
		&& $results['gate_2_smoke']['shortcode_add_to_cart_buttons']
		&& $results['gate_2_smoke']['shortcode_style_enqueued']
		&& $results['gate_2_smoke']['shortcode_torneo_style_enqueued']
		&& $results['gate_2_smoke']['shortcode_torneo_js_enqueued']
		&& $results['gate_2_smoke']['shortcode_wc_add_to_cart_queue']
		&& $results['gate_2_smoke']['ajax_success_contract']
		&& $results['gate_2_smoke']['ajax_invalid_nonce']
		&& $results['gate_2_smoke']['ajax_end_of_list']
	),
	'gate_3' => $results['gate_3_rollback']['rollback_gate_passed'],
);

$results['overall']['all_gates_passed'] = $results['overall']['gate_1'] && $results['overall']['gate_2'] && $results['overall']['gate_3'];

echo wp_json_encode( $results, JSON_PRETTY_PRINT ) . PHP_EOL;
