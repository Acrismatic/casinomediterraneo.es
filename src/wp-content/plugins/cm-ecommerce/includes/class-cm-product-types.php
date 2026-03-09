<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Product_Types {
	public static function init() {
		add_filter( 'product_type_selector', array( __CLASS__, 'add_product_types' ) );
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'map_product_classes' ), 10, 2 );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'register_torneo_info_tab' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'register_evento_info_tab' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'extend_simple_tabs_for_torneo' ), 20 );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'extend_simple_tabs_for_evento' ), 20 );
		add_filter( 'product_type_options', array( __CLASS__, 'extend_simple_options_for_torneo' ) );
		add_filter( 'product_type_options', array( __CLASS__, 'extend_simple_options_for_evento' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function add_product_types( $types ) {
		$types['torneo-poker'] = __( 'Torneo Póker', 'cm-wc-extensions' );
		$types['evento']       = __( 'Evento', 'cm-wc-extensions' );

		return $types;
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

	public static function register_torneo_info_tab( $tabs ) {
		$tabs['torneo_info'] = array(
			'label'    => __( 'Información torneo', 'cm-wc-extensions' ),
			'target'   => 'torneo_info_product_data',
			'class'    => array( 'show_if_torneo-poker' ),
			'priority' => 70,
		);

		return $tabs;
	}

	public static function register_evento_info_tab( $tabs ) {
		$tabs['evento_info'] = array(
			'label'    => __( 'Información evento', 'cm-wc-extensions' ),
			'target'   => 'evento_info_product_data',
			'class'    => array( 'show_if_evento' ),
			'priority' => 71,
		);

		return $tabs;
	}

	public static function extend_simple_tabs_for_torneo( $tabs ) {
		foreach ( $tabs as $key => $tab ) {
			if ( empty( $tab['class'] ) || ! is_array( $tab['class'] ) ) {
				continue;
			}

			if ( in_array( 'show_if_simple', $tab['class'], true ) && ! in_array( 'show_if_torneo-poker', $tab['class'], true ) ) {
				$tabs[ $key ]['class'][] = 'show_if_torneo-poker';
			}
		}

		return $tabs;
	}

	public static function extend_simple_tabs_for_evento( $tabs ) {
		foreach ( $tabs as $key => $tab ) {
			if ( empty( $tab['class'] ) || ! is_array( $tab['class'] ) ) {
				continue;
			}

			if ( in_array( 'show_if_simple', $tab['class'], true ) && ! in_array( 'show_if_evento', $tab['class'], true ) ) {
				$tabs[ $key ]['class'][] = 'show_if_evento';
			}
		}

		return $tabs;
	}

	public static function extend_simple_options_for_torneo( $options ) {
		foreach ( $options as $key => $option ) {
			if ( empty( $option['wrapper_class'] ) || ! is_string( $option['wrapper_class'] ) ) {
				continue;
			}

			if ( false !== strpos( $option['wrapper_class'], 'show_if_simple' ) && false === strpos( $option['wrapper_class'], 'show_if_torneo-poker' ) ) {
				$options[ $key ]['wrapper_class'] .= ' show_if_torneo-poker';
			}
		}

		return $options;
	}

	public static function extend_simple_options_for_evento( $options ) {
		foreach ( $options as $key => $option ) {
			if ( empty( $option['wrapper_class'] ) || ! is_string( $option['wrapper_class'] ) ) {
				continue;
			}

			if ( false !== strpos( $option['wrapper_class'], 'show_if_simple' ) && false === strpos( $option['wrapper_class'], 'show_if_evento' ) ) {
				$options[ $key ]['wrapper_class'] .= ' show_if_evento';
			}
		}

		return $options;
	}

	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( empty( $_GET['post_type'] ) && empty( $_GET['post'] ) ) {
			return;
		}

		$post_id = 0;
		if ( ! empty( $_GET['post'] ) ) {
			$post_id = absint( wp_unslash( $_GET['post'] ) );
		}

		if ( 0 !== $post_id && 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! empty( $_GET['post_type'] ) && 'product' !== sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
			return;
		}

		wp_enqueue_script(
			'cm-wc-product-type',
			CM_WC_EXT_URL . 'assets/js/cm-product-type.js',
			array( 'jquery', 'wc-enhanced-select' ),
			CM_WC_EXT_VERSION,
			true
		);

		wp_localize_script(
			'cm-wc-product-type',
			'cmTorneoSearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'cm_search_torneos',
				'nonce'   => wp_create_nonce( 'cm_search_torneos' ),
			)
		);

		wp_localize_script(
			'cm-wc-product-type',
			'cmEventoSearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'cm_search_eventos',
				'nonce'   => wp_create_nonce( 'cm_search_eventos' ),
			)
		);
	}
}

class WC_Product_Torneo_Poker extends WC_Product_Simple {
	public function get_type() {
		return 'torneo-poker';
	}
}

class WC_Product_Evento extends WC_Product_Simple {
	public function get_type() {
		return 'evento';
	}
}
