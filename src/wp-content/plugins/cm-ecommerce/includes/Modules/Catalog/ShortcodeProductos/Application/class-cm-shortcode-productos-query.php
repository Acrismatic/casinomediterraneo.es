<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Shortcode_Productos_Query {
	const DEFAULT_INITIAL_LIMIT = 3;
	const LOAD_MORE_BATCH       = 10;
	const MAX_INITIAL_LIMIT     = 50;

	/**
	 * @var array{date_gmt:string,id:int}|null
	 */
	private static $active_cursor = null;

	public static function normalize_initial_limit( $raw_cantidad ) {
		$cantidad = absint( $raw_cantidad );

		if ( $cantidad <= 0 ) {
			return self::DEFAULT_INITIAL_LIMIT;
		}

		return min( $cantidad, self::MAX_INITIAL_LIMIT );
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize_ajax_payload( array $raw_payload ) {
		$payload = array(
			'already_loaded' => isset( $raw_payload['already_loaded'] ) ? absint( $raw_payload['already_loaded'] ) : 0,
			'cursor_date_gmt' => isset( $raw_payload['cursor_date_gmt'] ) ? sanitize_text_field( wp_unslash( $raw_payload['cursor_date_gmt'] ) ) : '',
			'cursor_id'      => isset( $raw_payload['cursor_id'] ) ? absint( $raw_payload['cursor_id'] ) : 0,
		);

		if ( '' === $payload['cursor_date_gmt'] || $payload['cursor_id'] <= 0 ) {
			return new WP_Error( 'invalid_payload', __( 'Cursor invalido.', 'cm-wc-extensions' ) );
		}

		$cursor = self::normalize_cursor( $payload['cursor_date_gmt'], $payload['cursor_id'] );

		if ( is_wp_error( $cursor ) ) {
			return $cursor;
		}

		$payload['cursor'] = $cursor;

		return $payload;
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public static function get_initial_payload( $raw_cantidad ) {
		$limit = self::normalize_initial_limit( $raw_cantidad );

		return self::run_query( $limit, null );
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>|WP_Error
	 */
	public static function get_more_payload( array $payload ) {
		if ( empty( $payload['cursor'] ) || ! is_array( $payload['cursor'] ) ) {
			return new WP_Error( 'invalid_payload', __( 'Cursor invalido.', 'cm-wc-extensions' ) );
		}

		return self::run_query( self::LOAD_MORE_BATCH, $payload['cursor'] );
	}

	/**
	 * @param array{date_gmt:string,id:int}|null $cursor
	 * @return array<string,mixed>|WP_Error
	 */
	private static function run_query( $limit, $cursor ) {
		$limit = absint( $limit );

		if ( $limit <= 0 ) {
			return new WP_Error( 'invalid_limit', __( 'Limite invalido.', 'cm-wc-extensions' ) );
		}

		self::$active_cursor = $cursor;

		add_filter( 'posts_orderby', array( __CLASS__, 'filter_orderby' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'filter_where' ), 10, 2 );

		$query = new WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'fields'              => 'ids',
				'posts_per_page'      => $limit + 1,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'cm_torneo_cursor'    => true,
				'tax_query'           => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( 'torneo-poker' ),
					),
				),
				'meta_query'          => array(
					array(
						'key'     => CM_Product_Torneo_Query::META_TORNEO_ID,
						'value'   => '0',
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		remove_filter( 'posts_orderby', array( __CLASS__, 'filter_orderby' ), 10 );
		remove_filter( 'posts_where', array( __CLASS__, 'filter_where' ), 10 );

		self::$active_cursor = null;

		if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
			$product_ids = array_map( 'absint', $query->posts );
		} else {
			$product_ids = array();
		}

		$has_more = count( $product_ids ) > $limit;

		if ( $has_more ) {
			$product_ids = array_slice( $product_ids, 0, $limit );
		}

		$items       = self::build_items( $product_ids );
		$next_cursor = self::build_next_cursor( $product_ids );

		return array(
			'items'        => $items,
			'loaded_count' => count( $items ),
			'has_more'     => $has_more,
			'next_cursor'  => $next_cursor,
		);
	}

	public static function filter_orderby( $orderby, $query ) {
		global $wpdb;

		if ( ! ( $query instanceof WP_Query ) || ! $query->get( 'cm_torneo_cursor' ) ) {
			return $orderby;
		}

		return "{$wpdb->posts}.post_date_gmt DESC, {$wpdb->posts}.ID DESC";
	}

	public static function filter_where( $where, $query ) {
		global $wpdb;

		if ( ! ( $query instanceof WP_Query ) || ! $query->get( 'cm_torneo_cursor' ) ) {
			return $where;
		}

		if ( empty( self::$active_cursor ) || ! is_array( self::$active_cursor ) ) {
			return $where;
		}

		$date_gmt = (string) self::$active_cursor['date_gmt'];
		$id       = absint( self::$active_cursor['id'] );

		if ( '' === $date_gmt || $id <= 0 ) {
			return $where;
		}

		$where .= $wpdb->prepare(
			" AND ( {$wpdb->posts}.post_date_gmt < %s OR ( {$wpdb->posts}.post_date_gmt = %s AND {$wpdb->posts}.ID < %d ) )",
			$date_gmt,
			$date_gmt,
			$id
		);

		return $where;
	}

	/**
	 * @param array<int> $product_ids
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_items( array $product_ids ) {
		if ( empty( $product_ids ) ) {
			return array();
		}

		$items = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}

			$linked  = CM_Product_Torneo_Query::get_product_torneo_data( $product_id );

			$items[] = array(
				'product'   => array(
					'id'         => $product_id,
					'name'       => $product->get_name(),
					'price_html' => $product->get_price_html(),
					'permalink'  => $product->get_permalink(),
				),
				'torneo'    => isset( $linked['torneo'] ) ? $linked['torneo'] : null,
				'modalidad' => isset( $linked['modalidad'] ) ? $linked['modalidad'] : null,
			);
		}

		return $items;
	}

	/**
	 * @param array<int> $product_ids
	 * @return array{date_gmt:string,id:int}|null
	 */
	private static function build_next_cursor( array $product_ids ) {
		if ( empty( $product_ids ) ) {
			return null;
		}

		$last_id   = (int) end( $product_ids );
		$date_gmt  = get_post_field( 'post_date_gmt', $last_id );
		$date_gmt  = is_string( $date_gmt ) ? $date_gmt : '';

		if ( '' === $date_gmt || '0000-00-00 00:00:00' === $date_gmt ) {
			$date_gmt = gmdate( 'Y-m-d H:i:s' );
		}

		return array(
			'date_gmt' => $date_gmt,
			'id'       => $last_id,
		);
	}

	/**
	 * @return array{date_gmt:string,id:int}|WP_Error
	 */
	private static function normalize_cursor( $raw_date_gmt, $raw_id ) {
		$date_gmt = sanitize_text_field( (string) $raw_date_gmt );
		$id       = absint( $raw_id );

		if ( '' === $date_gmt || $id <= 0 ) {
			return new WP_Error( 'invalid_payload', __( 'Cursor invalido.', 'cm-wc-extensions' ) );
		}

		$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_gmt, new DateTimeZone( 'UTC' ) );

		if ( ! $date || $date->format( 'Y-m-d H:i:s' ) !== $date_gmt ) {
			return new WP_Error( 'invalid_payload', __( 'Cursor invalido.', 'cm-wc-extensions' ) );
		}

		return array(
			'date_gmt' => $date_gmt,
			'id'       => $id,
		);
	}
}
