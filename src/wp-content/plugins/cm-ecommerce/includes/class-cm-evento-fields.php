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

		if ( $selected > 0 && 'product' === get_post_type( $selected ) ) {
			$selected_type = WC_Product_Factory::get_product_type( $selected );
			if ( 'evento' === $selected_type ) {
				$option = sprintf(
					'<option value="%1$d" selected="selected">%2$s</option>',
					$selected,
					esc_html( get_the_title( $selected ) )
				);
			}
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
