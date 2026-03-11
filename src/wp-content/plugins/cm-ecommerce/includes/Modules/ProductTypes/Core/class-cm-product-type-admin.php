<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Product_Type_Admin {

	public static function init() {
		add_filter( 'product_type_selector', array( __CLASS__, 'add_product_types' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'register_torneo_info_tab' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'extend_simple_tabs_for_torneo' ), 20 );
		add_filter( 'product_type_options', array( __CLASS__, 'extend_simple_options_for_torneo' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function add_product_types( $types ) {
		$types['torneo-poker'] = __( 'Torneo Póker', 'cm-wc-extensions' );
		$types['evento']       = __( 'Evento', 'cm-wc-extensions' );

		return $types;
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

	public static function extend_simple_tabs_for_torneo( $tabs ) {
		foreach ( $tabs as $key => $tab ) {
			$classes = array();

			if ( ! empty( $tab['class'] ) ) {
				if ( is_array( $tab['class'] ) ) {
					$classes = $tab['class'];
				} elseif ( is_string( $tab['class'] ) ) {
					$classes = preg_split( '/\s+/', trim( $tab['class'] ) );
				}
			}

			$classes = array_filter( (array) $classes );

			if ( 'general' === $key && ! in_array( 'show_if_simple', $classes, true ) ) {
				$classes[] = 'show_if_simple';
			}

			if ( in_array( 'show_if_simple', $classes, true ) ) {
				foreach ( CM_Product_Types::TYPES as $custom_type ) {
					$visibility_class = 'show_if_' . $custom_type;
					if ( ! in_array( $visibility_class, $classes, true ) ) {
						$classes[] = $visibility_class;
					}
				}
			}

			$tabs[ $key ]['class'] = array_values( array_unique( $classes ) );
		}

		return $tabs;
	}

	public static function extend_simple_options_for_torneo( $options ) {
		foreach ( $options as $key => $option ) {
			if ( empty( $option['wrapper_class'] ) || ! is_string( $option['wrapper_class'] ) ) {
				continue;
			}

			if ( false === strpos( $option['wrapper_class'], 'show_if_simple' ) ) {
				continue;
			}

			foreach ( CM_Product_Types::TYPES as $custom_type ) {
				$visibility_class = 'show_if_' . $custom_type;
				if ( false === strpos( $option['wrapper_class'], $visibility_class ) ) {
					$options[ $key ]['wrapper_class'] .= ' ' . $visibility_class;
				}
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

		$enqueued = CM_Asset_Enqueuer::enqueue( 'cm-wc-product-type' );
		if ( ! $enqueued ) {
			return;
		}

		wp_localize_script(
			'cm-wc-product-type',
			'cmTorneoSearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'cm_search_torneos',
				'nonce'   => wp_create_nonce( 'cm_search_torneos' ),
			)
		);
	}
}
