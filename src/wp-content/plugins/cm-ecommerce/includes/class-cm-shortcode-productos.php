<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [PRODUCTOS] shortcode.
 *
 * Usage:
 *   [PRODUCTOS tipo="torneo"]  — torneo-poker products + linked torneo data.
 *   [PRODUCTOS tipo="evento"]  — evento products + linked evento data.
 *   [PRODUCTOS]                — all other WC products (excludes torneo-poker & evento).
 *   [PRODUCTOS limit="8"]      — optional: override products per page.
 *   [PRODUCTOS orderby="date"] — optional: override sort (default: date).
 */
class CM_Shortcode_Productos {

	const CUSTOM_TYPES = array( 'torneo-poker', 'evento' );

	const TIPO_MAP = array(
		'torneo' => 'torneo-poker',
		'evento' => 'evento',
	);

	public static function init() {
		add_shortcode( 'PRODUCTOS', array( __CLASS__, 'render' ) );
	}

	public static function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_style(
			'cm-shortcode-productos',
			CM_WC_EXT_URL . 'assets/css/cm-shortcode-productos.css',
			array(),
			CM_WC_EXT_VERSION
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

		if ( 'torneo-poker' === $resolved_type ) {
			return self::render_torneo_products( $query_args );
		}

		if ( 'evento' === $resolved_type ) {
			return self::render_evento_products( $query_args );
		}

		return self::render_default_products( $query_args );
	}

	// ------------------------------------------------------------------
	// Renderers
	// ------------------------------------------------------------------

	private static function render_torneo_products( array $query_args ) {
		$query_args['cm_has_torneo'] = true;

		$items = CM_Product_Torneo_Query::get_products_with_torneo_data( $query_args );

		if ( empty( $items ) ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'No hay torneos disponibles.', 'cm-wc-extensions' )
				. '</p>';
		}

		ob_start();
		?>
		<div class="cm-productos cm-productos--torneo">
		<?php foreach ( $items as $item ) :
			$product   = $item['product'];
			$torneo    = $item['torneo'];
			$modalidad = $item['modalidad'];
			$product_link = get_permalink( $product['id'] );
			$has_bounty   = $torneo && ! empty( $torneo['bounty'] );

			$fecha_hora = '';
			if ( $torneo && ! empty( $torneo['fecha'] ) ) {
				$fecha_hora = (string) $torneo['fecha'];
				if ( ! empty( $torneo['hora'] ) ) {
					$fecha_hora .= ' · ' . $torneo['hora'];
				}
			}
			?>
			<article class="cm-productos__item cm-productos__item--torneo" data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
				<a class="cm-productos__image-link" href="<?php echo esc_url( $product_link ); ?>">
					<?php echo self::get_product_thumbnail( $product['id'] ); ?>
				</a>

				<?php if ( '' !== $fecha_hora ) : ?>
					<p class="cm-productos__date"><?php echo esc_html( $fecha_hora ); ?></p>
				<?php endif; ?>

				<?php if ( $modalidad && ! empty( $modalidad['name'] ) ) : ?>
					<p class="cm-productos__modalidad"
						<?php if ( ! empty( $modalidad['color'] ) ) : ?>
							style="border-color: <?php echo esc_attr( $modalidad['color'] ); ?>; color: <?php echo esc_attr( $modalidad['color'] ); ?>;"
						<?php endif; ?>
					>
						<?php echo esc_html( $modalidad['name'] ); ?>
					</p>
				<?php endif; ?>

				<h3 class="cm-productos__name">
					<a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product['name'] ); ?></a>
				</h3>

				<div class="cm-productos__price-row">
					<p class="cm-productos__price"><?php echo wp_kses_post( $product['price_html'] ); ?></p>
					<?php if ( $has_bounty ) : ?>
						<span class="cm-productos__bounty-inline">
							<?php
							printf(
								esc_html__( 'Bounty: %s€', 'cm-wc-extensions' ),
								esc_html( $torneo['bounty'] )
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( $has_bounty ) : ?>
					<div class="cm-productos__bounty-notice">
						<p class="cm-productos__bounty-notice-text">
							<?php
							printf(
								esc_html__( 'La cantidad correspondiente al Boundy ( %s€ ) se deberá abonarn en caja.', 'cm-wc-extensions' ),
								esc_html( $torneo['bounty'] )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<div class="cm-productos__actions">
					<?php echo self::get_add_to_cart_button( $product['id'] ); ?>
				</div>
			</article>
		<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
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

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	private static function get_add_to_cart_button( $product_id ) {
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
				esc_attr( sprintf(
					/* translators: %s: Product name. */
					__( 'Añadir &ldquo;%s&rdquo; al carrito', 'cm-wc-extensions' ),
					$product->get_name()
				) ),
				esc_html__( 'Añadir al carrito', 'cm-wc-extensions' )
			);
		}

		return sprintf(
			'<a href="%s" class="button cm-productos__cart-btn" aria-label="%s">%s</a>',
			esc_url( $product->add_to_cart_url() ),
			esc_attr( sprintf(
				/* translators: %s: Product name. */
				__( 'Ver opciones de &ldquo;%s&rdquo;', 'cm-wc-extensions' ),
				$product->get_name()
			) ),
			esc_html__( 'Ver opciones', 'cm-wc-extensions' )
		);
	}

	private static function get_product_thumbnail( $product_id ) {
		$image = get_the_post_thumbnail( $product_id, 'woocommerce_thumbnail', array(
			'class'   => 'cm-productos__image',
			'loading' => 'lazy',
		) );

		if ( empty( $image ) ) {
			$image = wc_placeholder_img( 'woocommerce_thumbnail', array(
				'class' => 'cm-productos__image',
			) );
		}

		return $image;
	}
}
