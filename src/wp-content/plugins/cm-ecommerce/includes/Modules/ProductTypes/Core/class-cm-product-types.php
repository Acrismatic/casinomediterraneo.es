<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Product_Types {

	const TYPES = array( 'torneo-poker', 'evento' );

	public static function init() {
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'map_product_classes' ), 10, 2 );
	}

	public static function map_product_classes( $class_name, $product_type ) {
		if ( 'torneo-poker' === $product_type ) {
			return 'WC_Product_Torneo_Poker';
		}

		if ( 'evento' === $product_type ) {
			return 'WC_Product_Evento';
		}

		return $class_name;
	}
}
