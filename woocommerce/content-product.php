<?php
/**
 * Product tile inside a loop.
 *
 * Overrides woocommerce/templates/content-product.php. The tile shows the price
 * WooCommerce returns, if any — for a configurable product that is the plugin's
 * "from" price — and ends in "Configureer nu →".
 *
 * @package Probo_Connect
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( '', $product ); ?>>
	<?php
	/**
	 * Hook: woocommerce_before_shop_loop_item.
	 */
	do_action( 'woocommerce_before_shop_loop_item' );

	probo_product_card( $product );

	/**
	 * Hook: woocommerce_after_shop_loop_item.
	 */
	do_action( 'woocommerce_after_shop_loop_item' );
	?>
</li>
