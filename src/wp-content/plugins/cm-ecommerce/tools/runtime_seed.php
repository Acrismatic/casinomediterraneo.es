<?php

require '/var/www/html/wp-load.php';

if ( class_exists( 'CM_Plugin_Bootstrap' ) ) {
	CM_Plugin_Bootstrap::init();
}

$ensure_product = static function ( $slug, $title, $type ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $post ) {
		$id = (int) $post->ID;
	} else {
		$id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
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

	return $id;
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

$torneo_product_id = $ensure_product( 'sdd-torneo-poker', 'SDD Torneo Poker', 'torneo-poker' );
$evento_product_id = $ensure_product( 'sdd-evento', 'SDD Evento', 'evento' );
$simple_product_id = $ensure_product( 'sdd-simple', 'SDD Simple', 'simple' );
$page_id           = $ensure_page();

$torneo_post = get_page_by_path( 'sdd-torneo-ref', OBJECT, CM_Product_Torneo_Query::POST_TYPE_TORNEO );
if ( $torneo_post ) {
	$torneo_ref_id = (int) $torneo_post->ID;
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

if ( ! is_wp_error( $torneo_ref_id ) && $torneo_ref_id > 0 && $torneo_product_id > 0 ) {
	update_post_meta( $torneo_product_id, '_cm_torneo_id', (int) $torneo_ref_id );
	update_post_meta( $torneo_ref_id, 'torneo_fecha', gmdate( 'Y-m-d' ) );
	update_post_meta( $torneo_ref_id, 'torneo_hora', '20:00' );
}

echo wp_json_encode(
	array(
		'torneo_product_id' => $torneo_product_id,
		'evento_product_id' => $evento_product_id,
		'simple_product_id' => $simple_product_id,
		'page_id'           => $page_id,
		'torneo_ref_id'     => is_wp_error( $torneo_ref_id ) ? 0 : (int) $torneo_ref_id,
	),
	JSON_PRETTY_PRINT
) . PHP_EOL;
