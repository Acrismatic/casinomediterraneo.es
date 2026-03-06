<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Torneo_Fields {
	public static function init() {
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_torneo_panel' ) );
	}

	public static function render_torneo_panel() {
		global $post;

		$product_id = isset( $post->ID ) ? (int) $post->ID : 0;
		$selected   = absint( get_post_meta( $product_id, '_cm_torneo_id', true ) );
		$option     = '';

		if ( $selected > 0 && 'casino_poker_torneo' === get_post_type( $selected ) ) {
			$title = get_the_title( $selected );
			$date  = get_post_meta( $selected, 'torneo_fecha', true );

			if ( ! empty( $date ) ) {
				$timestamp = strtotime( (string) $date );
				if ( false !== $timestamp ) {
					$title .= ' - ' . wp_date( 'd/m/Y', $timestamp );
				}
			}

			$option = sprintf(
				'<option value="%1$d" selected="selected">%2$s</option>',
				$selected,
				esc_html( $title )
			);
		}
		?>
		<div id="torneo_info_product_data" class="panel woocommerce_options_panel hidden show_if_torneo-poker">
			<div class="options_group">
				<p class="form-field _cm_torneo_id_field">
					<label for="_cm_torneo_id"><?php esc_html_e( 'Torneo vinculado', 'cm-wc-extensions' ); ?></label>
					<select
						id="_cm_torneo_id"
						name="_cm_torneo_id"
						class="wc-enhanced-select cm-torneo-select"
						style="width: 50%;"
						data-placeholder="<?php echo esc_attr__( 'Torneos del mes actual — escribe para buscar pasados', 'cm-wc-extensions' ); ?>"
					>
						<option value=""><?php esc_html_e( 'Selecciona un torneo', 'cm-wc-extensions' ); ?></option>
						<?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</select>
					<span class="description"><?php esc_html_e( 'Muestra torneos del mes actual y permite buscar torneos pasados.', 'cm-wc-extensions' ); ?></span>
				</p>
			</div>
		</div>
		<?php
	}
}
