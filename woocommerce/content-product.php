<?php
/**
 * Product tile inside a loop.
 *
 * Overrides woocommerce/templates/content-product.php. The design's tile has no
 * price: listings end in "Configureer nu →" because a print product has no
 * meaningful price until it has been configured.
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
