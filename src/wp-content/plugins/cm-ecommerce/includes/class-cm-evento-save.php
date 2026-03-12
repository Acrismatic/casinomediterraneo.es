<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Save {
	private const SHORTCODE_STYLE_HANDLE = 'cm-evento-shortcode';

	public static function init() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_shortcode( 'PRODUCTOS_EVENTO', array( __CLASS__, 'render_productos_evento_shortcode' ) );
	}

	private static function enqueue_shortcode_styles() {
		wp_enqueue_style(
			self::SHORTCODE_STYLE_HANDLE,
			CM_WC_EXT_URL . 'assets/css/cm-evento-shortcode.css',
			array(),
			CM_WC_EXT_VERSION
		);
	}

	public static function save_fields( $product_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		$previous_evento_id = absint( get_post_meta( $product_id, '_cm_evento_id', true ) );

		if ( ! isset( $_POST['_cm_evento_id'] ) ) {
			delete_post_meta( $product_id, '_cm_evento_id' );
			if ( $previous_evento_id > 0 ) {
				delete_post_meta( $previous_evento_id, 'evento_producto_id' );
			}

			return;
		}

		$evento_id = absint( wp_unslash( $_POST['_cm_evento_id'] ) );

		if ( $evento_id > 0 && $evento_id !== $product_id && 'casino_evento' === get_post_type( $evento_id ) ) {
			update_post_meta( $product_id, '_cm_evento_id', $evento_id );
			update_post_meta( $evento_id, 'evento_producto_id', $product_id );

			if ( $previous_evento_id > 0 && $previous_evento_id !== $evento_id ) {
				delete_post_meta( $previous_evento_id, 'evento_producto_id' );
			}

			return;
		}

		delete_post_meta( $product_id, '_cm_evento_id' );
		if ( $previous_evento_id > 0 ) {
			delete_post_meta( $previous_evento_id, 'evento_producto_id' );
		}
	}

	/**
	 * Obtiene el ID del evento vinculado a un producto.
	 */
	public static function get_linked_evento_id( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			return 0;
		}

		$evento_id = absint( get_post_meta( $product_id, '_cm_evento_id', true ) );
		if ( $evento_id <= 0 || 'casino_evento' !== get_post_type( $evento_id ) ) {
			return 0;
		}

		return $evento_id;
	}

	/**
	 * Devuelve datos basicos del evento vinculado desde un producto.
	 */
	public static function get_linked_evento_data( $product_id ) {
		$evento_id = self::get_linked_evento_id( $product_id );
		if ( $evento_id <= 0 ) {
			return null;
		}

		$date_raw   = self::get_evento_date_raw( $evento_id );
		$date_label = '';
		$casino     = self::get_evento_casino_label( $product_id );

		if ( ! empty( $date_raw ) ) {
			$timestamp = strtotime( (string) $date_raw );
			if ( false !== $timestamp ) {
				$date_label = wp_date( 'j \d\e F', $timestamp );
			}
		}

		$title_parts = array_filter(
			array(
				get_the_title( $evento_id ),
				$date_label,
				$casino,
			),
			'strval'
		);

		return array(
			'id'         => $evento_id,
			'title'      => get_the_title( $evento_id ),
			'full_title' => implode( ' - ', $title_parts ),
			'permalink'  => get_permalink( $evento_id ),
			'date'       => $date_raw,
			'date_label' => $date_label,
			'casino'     => $casino,
		);
	}

	private static function get_evento_date_raw( $evento_id ) {
		$meta_keys = array( 'evento_fecha_inicio', 'evento_fecha', 'evento_fecha_fin' );

		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( $evento_id, $meta_key, true );
			if ( ! empty( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	private static function get_evento_casino_label( $evento_id ) {
		$terms = get_the_terms( $evento_id, 'casino_taxonomy_casinos' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$names = array();
		foreach ( $terms as $term ) {
			if ( ! empty( $term->name ) ) {
				$names[] = $term->name;
			}
		}

		if ( empty( $names ) ) {
			return '';
		}

		return implode( ', ', $names );
	}

	/**
	 * Renderizar el shortcode [PRODUCTOS_EVENTO]
	 */
	public static function render_productos_evento_shortcode( $atts = array() ) {
		if ( ! function_exists( 'WC' ) ) {
			return '<p>' . esc_html__( 'WooCommerce no está disponible en este momento.', 'cm-wc-extensions' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'limit'   => 12,
				'columns' => 3,
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$atts,
			'PRODUCTOS_EVENTO'
		);

		$limit   = max( 1, absint( $atts['limit'] ) );
		$columns = max( 1, absint( $atts['columns'] ) );
		$order   = 'ASC' === strtoupper( (string) $atts['order'] ) ? 'ASC' : 'DESC';
		$orderby = sanitize_key( (string) $atts['orderby'] );
		if ( ! in_array( $orderby, array( 'date', 'title', 'menu_order', 'modified', 'ID', 'rand' ), true ) ) {
			$orderby = 'date';
		}

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => $orderby,
			'order'          => $order,
			'meta_query'     => array(
				array(
					'key'     => '_cm_evento_id',
					'compare' => 'EXISTS',
				),
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'evento' ),
				),
			),
		);

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No hay productos de tipo evento disponibles.', 'cm-wc-extensions' ) . '</p>';
		}

		self::enqueue_shortcode_styles();

		ob_start();
		echo '<div class="cm-eventos-grid" style="--cm-eventos-columns:' . (int) $columns . ';">';

		while ( $query->have_posts() ) {
			$query->the_post();
			self::render_evento_card( get_the_ID() );
		}

		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Renderizar una card de evento
	 */
	private static function render_evento_card( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$evento_data = self::get_linked_evento_data( $product_id );

		$permalink  = get_permalink( $product_id );
		$title      = get_the_title( $product_id );
		if ( is_array( $evento_data ) ) {
			$title_parts = array_filter(
				array(
					$title,
					! empty( $evento_data['date_label'] ) ? (string) $evento_data['date_label'] : '',
					! empty( $evento_data['casino'] ) ? (string) $evento_data['casino'] : '',
				),
				'strval'
			);

			if ( ! empty( $title_parts ) ) {
				$title = implode( ' - ', $title_parts );
			}
		}
		$price_html = $product->get_price_html();
		$image_html = has_post_thumbnail( $product_id )
			? get_the_post_thumbnail( $product_id, 'woocommerce_thumbnail', array( 'class' => 'cm-evento-card__image', 'alt' => esc_attr( $title ) ) )
			: wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'cm-evento-card__image' ) );

		echo '<div class="product type-product cm-evento-card">';
		echo '<a href="' . esc_url( $permalink ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link cm-evento-card__link">';
		echo $image_html;
		echo '<h2 class="woocommerce-loop-product__title cm-evento-card__title">' . esc_html( $title ) . '</h2>';
		if ( ! empty( $price_html ) ) {
			echo '<span class="price cm-evento-card__price">' . wp_kses_post( $price_html ) . '</span>';
		}
		echo '</a>';
		echo '<form class="cart cm-evento-card__form" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product_id ) . '" />';
		echo '<button type="submit" class="button product_type_simple add_to_cart_button ajax_add_to_cart Ticket__add cm-evento-card__button">' . esc_html__( 'Añadir al carrito', 'woocommerce' ) . '</button>';
		echo '</form>';
		echo '</div>';

	}
}

if ( ! function_exists( 'cm_get_producto_evento_data' ) ) {
	/**
	 * Helper publico para recuperar datos del evento vinculado desde un producto.
	 */
	function cm_get_producto_evento_data( $product_id ) {
		return CM_Evento_Save::get_linked_evento_data( $product_id );
	}
}
