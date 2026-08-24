<?php
/**
 * Empty cart page.
 *
 * Overrides woocommerce/templates/cart/cart-empty.php. The stock template is
 * just a bare notice and a link with no page chrome — on this theme that left
 * "Je winkelwagen is leeg" and its button jammed against the top-left edge,
 * because the theme's cart.php draws its own <main class="pp-container"> and the
 * empty state never got one. This gives the empty cart the same centred band as
 * 404.php, and dresses the return link as the theme's own button.
 *
 * @package Probo_Connect
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;
?>

<main class="pp-container py-24 text-center">
	<?php
	/**
	 * Hook: woocommerce_cart_is_empty.
	 *
	 * @hooked wc_empty_cart_message - 10
	 *
	 * wc_empty_cart_message() prints the notice inside .cart-empty; the stylesheet
	 * gives that its own centred, chrome-free type so it does not read as an error.
	 */
	do_action( 'woocommerce_cart_is_empty' );
	?>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<p class="mt-8">
			<a class="pp-btn-accent return-to-shop wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<?php
					/**
					 * Filter "Return To Shop" text.
					 *
					 * @since 4.6.0
					 * @param string $default_text Default text.
					 */
					echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'probo-connect' ) ) );
				?>
			</a>
		</p>
	<?php endif; ?>
</main>
