<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Evento_Fields {
	public static function init() {
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_evento_panel' ) );
	}

	public static function render_evento_panel() {
		global $post;

		$product_id = isset( $post->ID ) ? (int) $post->ID : 0;
		$selected   = absint( get_post_meta( $product_id, '_cm_evento_id', true ) );
		$option     = '';

		if ( $selected > 0 && 'casino_evento' === get_post_type( $selected ) ) {
			$title = get_the_title( $selected );
			$date  = get_post_meta( $selected, 'evento_fecha_inicio', true );
			if ( empty( $date ) ) {
				$date = get_post_meta( $selected, 'evento_fecha', true );
			}

			if ( ! empty( $date ) ) {
				$timestamp = strtotime( (string) $date );
				if ( false !== $timestamp ) {
					$title .= ' - ' . wp_date( 'd/m/Y', $timestamp );
				}
			}

			$terms = get_the_terms( $selected, 'casino_taxonomy_casinos' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$names = array();
				foreach ( $terms as $term ) {
					if ( ! empty( $term->name ) ) {
						$names[] = $term->name;
					}
				}

				if ( ! empty( $names ) ) {
					$title .= ' - ' . implode( ', ', $names );
				}
			}

			$option = sprintf(
				'<option value="%1$d" selected="selected">%2$s</option>',
				$selected,
				esc_html( $title )
			);
		}
		?>
		<div id="evento_info_product_data" class="panel woocommerce_options_panel hidden show_if_evento">
			<div class="options_group">
				<p class="form-field _cm_evento_id_field">
					<label for="_cm_evento_id"><?php esc_html_e( 'Evento vinculado', 'cm-wc-extensions' ); ?></label>
					<select
						id="_cm_evento_id"
						name="_cm_evento_id"
						class="wc-enhanced-select cm-evento-select"
						style="width: 50%;"
						data-placeholder="<?php echo esc_attr__( 'Selecciona un evento', 'cm-wc-extensions' ); ?>"
					>
						<option value=""><?php esc_html_e( 'Selecciona un evento', 'cm-wc-extensions' ); ?></option>
						<?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</select>
					<span class="description"><?php esc_html_e( 'Aqui se vinculara un producto de tipo evento.', 'cm-wc-extensions' ); ?></span>
				</p>
			</div>
		</div>
		<?php
	}
}
