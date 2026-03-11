<?php

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$product_id = get_the_ID();
$product    = wc_get_product( $product_id );
$entrada    = array();

if ( defined( 'CM_DIR_PATH' ) ) {
	$entrada_class_file = CM_DIR_PATH . 'public/services/Entrada/class-cm-entrada.php';
	$utils_file         = CM_DIR_PATH . 'public/services/cm-utils.php';

	if ( file_exists( $entrada_class_file ) ) {
		require_once $entrada_class_file;
	}

	if ( file_exists( $utils_file ) ) {
		require_once $utils_file;
	}

	if ( class_exists( 'CM_Entrada' ) ) {
		$entrada_class = new CM_Entrada();
		$entrada       = $entrada_class->get_entrada( $product_id );
	}
}

if ( empty( $entrada ) || ! is_array( $entrada ) ) {
	do_action( 'woocommerce_before_main_content' );

	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'single-product' );
	}

	do_action( 'woocommerce_after_main_content' );
	get_footer( 'shop' );
	return;
}

$torneo       = isset( $entrada['torneo'] ) && is_array( $entrada['torneo'] ) ? $entrada['torneo'] : array();
$modalidad    = isset( $entrada['modalidad'] ) && is_array( $entrada['modalidad'] ) ? $entrada['modalidad'] : array();
$casino_items = isset( $torneo['casinos'] ) && is_array( $torneo['casinos'] ) ? $torneo['casinos'] : array();

$torneo_color    = ! empty( $torneo['color'] ) ? (string) $torneo['color'] : '#114f43';
$modalidad_color = ! empty( $modalidad['color'] ) ? (string) $modalidad['color'] : '#255f51';
$product_name    = ! empty( $torneo['name'] ) ? 'Entrada - ' . $torneo['name'] : get_the_title( $product_id );
$image_url       = ! empty( $entrada['image'] ) ? (string) $entrada['image'] : wc_placeholder_img_src();
$price_label     = isset( $entrada['price'] ) ? (string) $entrada['price'] : '';
$stock_label     = isset( $entrada['stock'] ) ? (string) $entrada['stock'] : '';
$date_label      = ! empty( $torneo['fecha'] ) ? (string) $torneo['fecha'] : '';

if ( '' !== $date_label ) {
	$timestamp = strtotime( $date_label );
	if ( false !== $timestamp ) {
		$date_label = wp_date( 'l, d \\d\\e F \\d\\e Y', $timestamp );
	}
}

$first_casino_name = '';
if ( ! empty( $casino_items ) ) {
	$first_casino = reset( $casino_items );
	if ( is_object( $first_casino ) && isset( $first_casino->name ) ) {
		$first_casino_name = (string) $first_casino->name;
	}
}

$add_to_cart_url = '';
if ( $product instanceof WC_Product ) {
	$add_to_cart_url = $product->add_to_cart_url();
} elseif ( isset( $entrada['id'] ) ) {
	$add_to_cart_url = add_query_arg( 'add-to-cart', absint( $entrada['id'] ), home_url( '/' ) );
}
?>
<section class="cm-single-product" style="--cm-torneo-color: <?php echo esc_attr( $torneo_color ); ?>; --cm-modalidad-color: <?php echo esc_attr( $modalidad_color ); ?>;">
	<div class="cm-single-product__wrapper">
		<p class="cm-single-product__kicker"><?php echo esc_html( woocommerce_page_title( false ) ); ?></p>

		<div class="cm-single-product__grid">
			<div class="cm-single-product__media">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" class="cm-single-product__image" loading="lazy" />
				<?php if ( ! empty( $casino_items ) ) : ?>
					<div class="cm-single-product__casinos">
						<?php foreach ( $casino_items as $casino ) :
							$casino_name = '';
							if ( is_object( $casino ) && isset( $casino->name ) ) {
								$casino_name = (string) $casino->name;
							}
							if ( '' === $casino_name ) {
								continue;
							}
							?>
							<span class="cm-single-product__casino-tag"><?php echo esc_html( $casino_name ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="cm-single-product__content">
				<h1 class="cm-single-product__title"><?php echo esc_html( $product_name ); ?></h1>

				<?php if ( '' !== $price_label ) : ?>
					<div class="cm-single-product__price-row">
						<span class="cm-single-product__price"><?php echo esc_html( $price_label ); ?></span>
						<span class="cm-single-product__currency">EUR</span>
					</div>
				<?php endif; ?>

				<div class="cm-single-product__details">
					<?php if ( ! empty( $modalidad['name'] ) ) : ?>
						<p class="cm-single-product__detail"><strong><?php echo esc_html( $modalidad['name'] ); ?></strong> | Texas Hold'em no limit</p>
					<?php endif; ?>
					<?php if ( '' !== $date_label ) : ?>
						<p class="cm-single-product__detail"><?php echo esc_html( $date_label ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $first_casino_name ) : ?>
						<p class="cm-single-product__detail">Casino Mediterraneo <?php echo esc_html( $first_casino_name ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( '' !== $stock_label ) : ?>
					<div class="cm-single-product__stock">
						<?php
						printf(
							/* translators: %s: available units. */
							esc_html__( '%s disponible', 'cm-wc-extensions' ),
							esc_html( $stock_label )
						);
						?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $add_to_cart_url ) : ?>
					<div class="cm-single-product__actions">
						<a class="button cm-single-product__add-to-cart" href="<?php echo esc_url( $add_to_cart_url ); ?>">
							<?php echo esc_html__( 'Add to cart', 'woocommerce' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php
get_footer( 'shop' );
