<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Shortcode_Productos_Renderer {

	/**
	 * @param array<string,mixed> $payload
	 */
	public static function render_shortcode_container( array $payload ) {
		$items        = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : array();
		$loaded_count = isset( $payload['loaded_count'] ) ? absint( $payload['loaded_count'] ) : 0;
		$has_more     = ! empty( $payload['has_more'] );
		$next_cursor  = isset( $payload['next_cursor'] ) && is_array( $payload['next_cursor'] ) ? $payload['next_cursor'] : null;

		if ( empty( $items ) ) {
			return '<p class="cm-productos--empty">'
				. esc_html__( 'No hay torneos disponibles.', 'cm-wc-extensions' )
				. '</p>';
		}

		$cursor_date = $next_cursor && isset( $next_cursor['date_gmt'] ) ? (string) $next_cursor['date_gmt'] : '';
		$cursor_id   = $next_cursor && isset( $next_cursor['id'] ) ? absint( $next_cursor['id'] ) : 0;

		ob_start();
		?>
		<section class="cm-productos-torneo js-cm-productos-torneo">
			<div
				class="cm-productos cm-productos--torneo js-cm-productos-torneo-grid"
				data-loaded-count="<?php echo esc_attr( $loaded_count ); ?>"
				data-has-more="<?php echo esc_attr( $has_more ? '1' : '0' ); ?>"
				data-next-cursor-date="<?php echo esc_attr( $cursor_date ); ?>"
				data-next-cursor-id="<?php echo esc_attr( $cursor_id ); ?>"
			>
				<?php echo wp_kses_post( self::render_items_html( $items ) ); ?>
			</div>

			<div class="cm-productos-torneo__footer">
				<button
					type="button"
					class="cm-productos-torneo__load-more js-cm-productos-torneo-load-more"
					<?php disabled( ! $has_more ); ?>
				>
					<?php echo esc_html__( 'Mostrar mas', 'cm-wc-extensions' ); ?>
				</button>
				<p class="cm-productos-torneo__feedback js-cm-productos-torneo-feedback" aria-live="polite"></p>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 */
	public static function render_items_html( array $items ) {
		if ( empty( $items ) ) {
			return '';
		}

		ob_start();
		foreach ( $items as $item ) {
			echo self::render_item_html( $item );
		}

		return ob_get_clean();
	}

	/**
	 * @param array<string,mixed> $item
	 */
	private static function render_item_html( array $item ) {
		$product = isset( $item['product'] ) && is_array( $item['product'] ) ? $item['product'] : array();
		$torneo  = isset( $item['torneo'] ) && is_array( $item['torneo'] ) ? $item['torneo'] : null;
		$mod     = isset( $item['modalidad'] ) && is_array( $item['modalidad'] ) ? $item['modalidad'] : null;

		$product_id   = isset( $product['id'] ) ? absint( $product['id'] ) : 0;
		$product_name = isset( $product['name'] ) ? (string) $product['name'] : '';
		$product_link = isset( $product['permalink'] ) ? (string) $product['permalink'] : get_permalink( $product_id );
		$price_html   = isset( $product['price_html'] ) ? (string) $product['price_html'] : '';

		$fecha_hora = '';
		if ( $torneo && ! empty( $torneo['fecha'] ) ) {
			$fecha_hora = (string) $torneo['fecha'];
			if ( ! empty( $torneo['hora'] ) ) {
				$fecha_hora .= ' · ' . (string) $torneo['hora'];
			}
		}

		$has_bounty = $torneo && ! empty( $torneo['bounty'] );

		ob_start();
		?>
		<article class="cm-productos__item cm-productos__item--torneo" data-product-id="<?php echo esc_attr( $product_id ); ?>">
			<a class="cm-productos__image-link" href="<?php echo esc_url( $product_link ); ?>">
				<?php echo CM_Shortcode_Productos::get_product_thumbnail( $product_id ); ?>
			</a>

			<?php if ( '' !== $fecha_hora ) : ?>
				<p class="cm-productos__date"><?php echo esc_html( $fecha_hora ); ?></p>
			<?php endif; ?>

			<?php if ( $mod && ! empty( $mod['name'] ) ) : ?>
				<p class="cm-productos__modalidad"
					<?php if ( ! empty( $mod['color'] ) ) : ?>
						style="border-color: <?php echo esc_attr( $mod['color'] ); ?>; color: <?php echo esc_attr( $mod['color'] ); ?>;"
					<?php endif; ?>
				>
					<?php echo esc_html( $mod['name'] ); ?>
				</p>
			<?php endif; ?>

			<h3 class="cm-productos__name">
				<a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
			</h3>

			<div class="cm-productos__price-row">
				<p class="cm-productos__price"><?php echo wp_kses_post( $price_html ); ?></p>
				<?php if ( $has_bounty ) : ?>
					<span class="cm-productos__bounty-inline">
						<?php
						printf(
							esc_html__( 'Bounty: %sEUR', 'cm-wc-extensions' ),
							esc_html( (string) $torneo['bounty'] )
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
							esc_html__( 'La cantidad correspondiente al bounty (%sEUR) se debera abonar en caja.', 'cm-wc-extensions' ),
							esc_html( (string) $torneo['bounty'] )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="cm-productos__actions">
				<?php echo CM_Shortcode_Productos::get_add_to_cart_button( $product_id ); ?>
			</div>
		</article>
		<?php

		return ob_get_clean();
	}
}
