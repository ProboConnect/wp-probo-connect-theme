<?php
/**
 * Checkout payment section — gateways only.
 *
 * Overrides woocommerce/templates/checkout/payment.php. The design splits this
 * template in two: the gateway list stays in the "Betaalmethode" step in the
 * main column, and the place-order block — terms, submit button and nonce —
 * moves under "Te betalen" in the order summary, where the amount it confirms
 * is actually visible.
 *
 * The moved half is printed by probo_checkout_place_order() in
 * inc/woocommerce.php, which keeps every hook this template fires around the
 * button. Both halves sit inside the same <form name="checkout">, so the submit
 * still posts the whole checkout.
 *
 * @package Probo_Connect
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li>';
				wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
