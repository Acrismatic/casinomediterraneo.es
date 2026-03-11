<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes [PRODUCTOS] and [productos_torneo].
 */
class CM_Shortcode_Productos {

	const CUSTOM_TYPES = array( 'torneo-poker', 'evento' );

	const TIPO_MAP = array(
		'evento' => 'evento',
	);

	public static function init() {
		add_shortcode( 'PRODUCTOS', array( __CLASS__, 'render' ) );
		add_shortcode( 'productos_torneo', array( __CLASS__, 'render_productos_torneo' ) );
	}

	public static function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart' );
		CM_Asset_Enqueuer::enqueue( 'cm-shortcode-productos' );
	}

	public static function enqueue_torneo_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart' );
		CM_Asset_Enqueuer::enqueue( 'cm-shortcode-productos' );
		CM_Asset_Enqueuer::enqueue( 'cm-shortcode-productos-torneo-css' );
		CM_Asset_Enqueuer::enqueue( 'cm-shortcode-productos-torneo-js' );

		wp_localize_script(
			'cm-shortcode-productos-torneo-js',
			'cmProductosTorneo',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => CM_Shortcode_Productos_Ajax::ACTION,
				'nonce'     => wp_create_nonce( CM_Shortcode_Productos_Ajax::NONCE_ACTION ),
				'batchSize' => CM_Shortcode_Productos_Query::LOAD_MORE_BATCH,
				'messages'  => array(
					'loading' => __( 'Cargando torneos...', 'cm-wc-extensions' ),
					'error'   => __( 'No pudimos cargar mas torneos. Intenta nuevamente.', 'cm-wc-extensions' ),
					'end'     => __( 'No hay mas torneos.', 'cm-wc-extensions' ),
				),
			)
		);
	}

	public static function render( $atts ) {
		self::enqueue_assets();

		$atts = shortcode_atts(
			array(
				'tipo'    => '',
				'limit'   => 12,
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$atts,
			'PRODUCTOS'
		);

		$tipo    = sanitize_key( $atts['tipo'] );
		$limit   = absint( $atts['limit'] );
		$orderby = sanitize_key( $atts['orderby'] );
		$order   = in_array( strtoupper( $atts['order'] ), array( 'ASC', 'DESC' ), true )
			? strtoupper( $atts['order'] )
			: 'DESC';

		if ( $limit <= 0 ) {
			$limit = 12;
		}

		if ( 'torneo' === $tipo || 'torneo-poker' === $tipo ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'El flujo de torneos ahora usa [productos_torneo].', 'cm-wc-extensions' )
				. '</p>';
		}

		$query_args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => $orderby,
			'order'   => $order,
			'return'  => 'objects',
		);

		$resolved_type = isset( self::TIPO_MAP[ $tipo ] ) ? self::TIPO_MAP[ $tipo ] : '';

		if ( '' !== $resolved_type ) {
			$query_args['cm_product_type'] = $resolved_type;
		} else {
			$query_args['cm_exclude_types'] = self::CUSTOM_TYPES;
		}

		if ( 'evento' === $resolved_type ) {
			return self::render_evento_products( $query_args );
		}

		return self::render_default_products( $query_args );
	}

	public static function render_productos_torneo( $atts ) {
		self::enqueue_torneo_assets();

		$atts = shortcode_atts(
			array(
				'cantidad' => CM_Shortcode_Productos_Query::DEFAULT_INITIAL_LIMIT,
			),
			$atts,
			'productos_torneo'
		);

		$result = CM_Shortcode_Productos_Query::get_initial_payload( $atts['cantidad'] );

		if ( is_wp_error( $result ) ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'No pudimos obtener torneos por el momento.', 'cm-wc-extensions' )
				. '</p>';
		}

		return CM_Shortcode_Productos_Renderer::render_shortcode_container( $result );
	}

	private static function render_evento_products( array $query_args ) {
		$products = wc_get_products( $query_args );

		if ( empty( $products ) ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'No hay entradas de eventos disponibles.', 'cm-wc-extensions' )
				. '</p>';
		}

		ob_start();
		?>
		<div class="cm-productos cm-productos--evento">
		<?php foreach ( $products as $product ) :
			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}
			$product_id = $product->get_id();
			?>
			<div class="cm-productos__item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
				<div class="cm-productos__product">
					<h3 class="cm-productos__name">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<?php echo esc_html( $product->get_name() ); ?>
						</a>
					</h3>
					<?php echo self::get_product_thumbnail( $product_id ); ?>
					<span class="cm-productos__type"><?php echo esc_html( $product->get_type() ); ?></span>
					<span class="cm-productos__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php echo self::get_add_to_cart_button( $product_id ); ?>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function render_default_products( array $query_args ) {
		$products = wc_get_products( $query_args );

		if ( empty( $products ) ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'No hay productos disponibles.', 'cm-wc-extensions' )
				. '</p>';
		}

		ob_start();
		?>
		<div class="cm-productos cm-productos--default">
		<?php foreach ( $products as $product ) :
			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}
			$product_id = $product->get_id();
			?>
			<div class="cm-productos__item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
				<div class="cm-productos__product">
					<h3 class="cm-productos__name">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<?php echo esc_html( $product->get_name() ); ?>
						</a>
					</h3>
					<?php echo self::get_product_thumbnail( $product_id ); ?>
					<span class="cm-productos__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php echo self::get_add_to_cart_button( $product_id ); ?>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function get_add_to_cart_button( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return '<span class="cm-productos__cart-btn cm-productos__cart-btn--disabled">'
				. esc_html__( 'No disponible', 'cm-wc-extensions' )
				. '</span>';
		}

		if ( $product->is_type( 'simple' ) || $product->is_type( 'torneo-poker' ) || $product->is_type( 'evento' ) ) {
			return sprintf(
				'<a href="%s" data-quantity="1" class="%s" data-product_id="%s" data-product_sku="%s" aria-label="%s">%s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( 'button cm-productos__cart-btn add_to_cart_button ajax_add_to_cart' ),
				esc_attr( $product_id ),
				esc_attr( $product->get_sku() ),
				esc_attr(
					sprintf(
						/* translators: %s: Product name. */
						__( 'Añadir &ldquo;%s&rdquo; al carrito', 'cm-wc-extensions' ),
						$product->get_name()
					)
				),
				esc_html__( 'Añadir al carrito', 'cm-wc-extensions' )
			);
		}

		return sprintf(
			'<a href="%s" class="button cm-productos__cart-btn" aria-label="%s">%s</a>',
			esc_url( $product->add_to_cart_url() ),
			esc_attr(
				sprintf(
					/* translators: %s: Product name. */
					__( 'Ver opciones de &ldquo;%s&rdquo;', 'cm-wc-extensions' ),
					$product->get_name()
				)
			),
			esc_html__( 'Ver opciones', 'cm-wc-extensions' )
		);
	}

	public static function get_product_thumbnail( $product_id ) {
		$image = get_the_post_thumbnail(
			$product_id,
			'woocommerce_thumbnail',
			array(
				'class'   => 'cm-productos__image',
				'loading' => 'lazy',
			)
		);

		if ( empty( $image ) ) {
			$image = wc_placeholder_img(
				'woocommerce_thumbnail',
				array(
					'class' => 'cm-productos__image',
				)
			);
		}

		return $image;
	}
}
