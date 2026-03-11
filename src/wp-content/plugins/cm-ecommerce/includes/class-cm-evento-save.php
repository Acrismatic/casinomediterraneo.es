<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Save {
	public static function init() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_shortcode( 'PRODUCTOS_EVENTO', array( __CLASS__, 'render_productos_evento_shortcode' ) );
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

		$evento_id   = absint( wp_unslash( $_POST['_cm_evento_id'] ) );
		$evento_type = '';
		if ( $evento_id > 0 && 'product' === get_post_type( $evento_id ) ) {
			$evento_type = WC_Product_Factory::get_product_type( $evento_id );
		}

		if ( $evento_id > 0 && $evento_id !== $product_id && 'evento' === $evento_type ) {
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
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'order'          => $order,
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

		ob_start();
		echo '<div class="cm-eventos-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px;">';

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

		$permalink  = get_permalink( $product_id );
		$title      = get_the_title( $product_id );
		$price_html = $product->get_price_html();
		$image_html = has_post_thumbnail( $product_id )
			? get_the_post_thumbnail( $product_id, 'woocommerce_thumbnail', array( 'class' => 'cm-evento-card__image', 'alt' => esc_attr( $title ) ) )
			: wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'cm-evento-card__image' ) );

		echo '<div class="product type-product cm-evento-card" style="display: flex; flex-direction: column; align-items: center; justify-content: space-between; text-align: center;">';
		echo '<a href="' . esc_url( $permalink ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link" style="display: flex; flex-direction: column; align-items: center;">';
		echo $image_html;
		echo '<h2 class="woocommerce-loop-product__title" style="margin: 0.5rem 0;">' . esc_html( $title ) . '</h2>';
		if ( ! empty( $price_html ) ) {
			echo '<span class="price" style="margin-bottom: 1rem;">' . wp_kses_post( $price_html ) . '</span>';
		}
		echo '</a>';
		echo '<form class="cart" method="post" enctype="multipart/form-data" style="width: 100%; margin-top: auto;">';
		echo '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product_id ) . '" />';
		echo '<button type="submit" class="button product_type_simple add_to_cart_button ajax_add_to_cart Ticket__add" style="width: 100%;">' . esc_html__( 'Añadir al carrito', 'woocommerce' ) . '</button>';
		echo '</form>';
		echo '</div>';
	}
}
