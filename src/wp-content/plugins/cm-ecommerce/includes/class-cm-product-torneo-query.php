<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query service for product -> torneo -> modalidad relations.
 *
 * Source of truth for product linkage is _cm_torneo_id saved by CM_Torneo_Save.
 */
class CM_Product_Torneo_Query {
	const META_TORNEO_ID    = '_cm_torneo_id';
	const META_TORNEO_FECHA = 'torneo_fecha';
	const META_MODALIDAD    = 'torneo_modalidad';
	const META_MOD_COLOR    = 'modalidad_color';
	const POST_TYPE_TORNEO  = 'casino_poker_torneo';

	public static function init() {
		add_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			array( __CLASS__, 'handle_custom_query_vars' ),
			10,
			2
		);
	}

	/**
	 * Extend wc_get_products() with custom vars:
	 * - cm_has_torneo:    bool  — only products with a linked torneo.
	 * - cm_torneo_id:     int   — products linked to a specific torneo.
	 * - cm_product_type:  string — filter by WC product type slug (e.g. 'torneo-poker').
	 * - cm_exclude_types: array  — exclude these product type slugs.
	 */
	public static function handle_custom_query_vars( $query, $query_vars ) {
		if ( ! empty( $query_vars['cm_has_torneo'] ) ) {
			$query['meta_query'][] = array(
				'key'     => self::META_TORNEO_ID,
				'value'   => '0',
				'compare' => '>',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $query_vars['cm_torneo_id'] ) ) {
			$query['meta_query'][] = array(
				'key'     => self::META_TORNEO_ID,
				'value'   => absint( $query_vars['cm_torneo_id'] ),
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $query_vars['cm_product_type'] ) ) {
			$type_slug = sanitize_key( $query_vars['cm_product_type'] );
			$query['tax_query'][] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $type_slug,
			);
		}

		if ( ! empty( $query_vars['cm_exclude_types'] ) && is_array( $query_vars['cm_exclude_types'] ) ) {
			$slugs = array_map( 'sanitize_key', $query_vars['cm_exclude_types'] );
			$query['tax_query'][] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $slugs,
				'operator' => 'NOT IN',
			);
		}

		return $query;
	}

	/**
	 * Returns products enriched with linked torneo and modalidad.
	 */
	public static function get_products_with_torneo_data( array $args = array() ) {
		$defaults = array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'objects',
		);

		$products = wc_get_products( wp_parse_args( $args, $defaults ) );

		if ( empty( $products ) ) {
			return array();
		}

		$torneo_map = array();
		$torneo_ids = array();

		foreach ( $products as $product ) {
			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}

			$product_id = $product->get_id();
			$torneo_id  = absint( $product->get_meta( self::META_TORNEO_ID, true ) );

			if ( $torneo_id <= 0 ) {
				continue;
			}

			$torneo_map[ $product_id ] = $torneo_id;
			$torneo_ids[]              = $torneo_id;
		}

		$torneo_ids = array_values( array_unique( $torneo_ids ) );

		if ( ! empty( $torneo_ids ) ) {
			self::prime_post_and_meta_caches( $torneo_ids );
		}

		$modalidad_ids = array();
		foreach ( $torneo_ids as $torneo_id ) {
			$modalidad_id = self::resolve_modalidad_id( $torneo_id );
			if ( $modalidad_id > 0 ) {
				$modalidad_ids[] = $modalidad_id;
			}
		}

		$modalidad_ids = array_values( array_unique( $modalidad_ids ) );
		if ( ! empty( $modalidad_ids ) ) {
			self::prime_post_and_meta_caches( $modalidad_ids );
		}

		$results = array();
		foreach ( $products as $product ) {
			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}

			$product_id     = $product->get_id();
			$linked_torneo  = isset( $torneo_map[ $product_id ] ) ? (int) $torneo_map[ $product_id ] : 0;
			$torneo_data    = null;
			$modalidad_data = null;

			if ( $linked_torneo > 0 && self::POST_TYPE_TORNEO === get_post_type( $linked_torneo ) ) {
				$torneo_data    = self::build_torneo_data( $linked_torneo );
				$modalidad_data = self::build_modalidad_data( $linked_torneo );
			}

			$results[] = array(
				'product'   => array(
					'id'     => $product_id,
					'name'   => $product->get_name(),
					'sku'    => $product->get_sku(),
					'type'   => $product->get_type(),
					'status' => $product->get_status(),
					'price_html' => $product->get_price_html(),
				),
				'torneo'    => $torneo_data,
				'modalidad' => $modalidad_data,
			);
		}

		return $results;
	}
   

	/**
	 * Single product helper.
	 */
	public static function get_product_torneo_data( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			return array(
				'torneo'    => null,
				'modalidad' => null,
			);
		}

		$torneo_id = absint( get_post_meta( $product_id, self::META_TORNEO_ID, true ) );
		if ( $torneo_id <= 0 || self::POST_TYPE_TORNEO !== get_post_type( $torneo_id ) ) {
			return array(
				'torneo'    => null,
				'modalidad' => null,
			);
		}

		return array(
			'torneo'    => self::build_torneo_data( $torneo_id ),
			'modalidad' => self::build_modalidad_data( $torneo_id ),
		);
	}

	private static function build_torneo_data( $torneo_id ) {
		return array(
			'id'     => (int) $torneo_id,
			'title'  => get_the_title( $torneo_id ),
			'slug'   => get_post_field( 'post_name', $torneo_id ),
			'link'   => get_permalink( $torneo_id ),
			'fecha'  => get_post_meta( $torneo_id, self::META_TORNEO_FECHA, true ),
			'hora'   => get_post_meta( $torneo_id, 'torneo_hora', true ),
			'buyin'  => get_post_meta( $torneo_id, 'torneo_buyin', true ),
			'bounty' => get_post_meta( $torneo_id, 'torneo_bounty', true ),
		);
	}

	private static function build_modalidad_data( $torneo_id ) {
		$modalidad_id = self::resolve_modalidad_id( $torneo_id );
		if ( $modalidad_id <= 0 ) {
			return null;
		}

		return array(
			'id'    => $modalidad_id,
			'name'  => get_the_title( $modalidad_id ),
			'slug'  => get_post_field( 'post_name', $modalidad_id ),
			'color' => get_post_meta( $modalidad_id, self::META_MOD_COLOR, true ),
		);
	}

	private static function resolve_modalidad_id( $torneo_id ) {
		$raw = get_post_meta( $torneo_id, self::META_MODALIDAD, true );

		if ( is_numeric( $raw ) ) {
			return absint( $raw );
		}

		if ( is_array( $raw ) && ! empty( $raw ) ) {
			$first = reset( $raw );

			if ( is_numeric( $first ) ) {
				return absint( $first );
			}

			if ( is_array( $first ) && isset( $first['ID'] ) && is_numeric( $first['ID'] ) ) {
				return absint( $first['ID'] );
			}
		}

		return 0;
	}

	private static function prime_post_and_meta_caches( array $post_ids ) {
		if ( empty( $post_ids ) ) {
			return;
		}

		_prime_post_caches( $post_ids, false, false );
		update_meta_cache( 'post', $post_ids );
	}
}
