<?php
/**
 * Cart totals — the sticky summary card.
 *
 * Overrides woocommerce/templates/cart/cart-totals.php. Same totals helpers and
 * hooks as the stock template, laid out as the design's bordered card, with the
 * coupon field moved in here as a standalone form (the cart form itself no
 * longer carries an actions row).
 *
 * @package Probo_Connect
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

$probo_usps = array_filter(
	array(
		probo_get( 'topbar_usp_1' ),
		probo_get( 'topbar_usp_2' ),
		probo_get( 'topbar_usp_3' ),
	)
);
?>
<div class="cart_totals rounded-pp border-2 border-secondary-line p-6.5 <?php echo WC()->customer->has_calculated_shipping() ? 'calculated_shipping' : ''; ?>">
	<?php
	/**
	 * Hook: woocommerce_before_cart_totals.
	 */
	do_action( 'woocommerce_before_cart_totals' );
	?>

	<h2 class="mb-4.5 text-xl font-extrabold tracking-[-0.02em]"><?php esc_html_e( 'Total', 'probo-connect' ); ?></h2>

	<div class="flex flex-col gap-3 border-b border-line pb-4 text-sm">
		<div class="cart-subtotal flex justify-between">
			<span class="text-ink-3"><?php esc_html_e( 'Subtotal', 'probo-connect' ); ?></span>
			<span class="font-semibold"><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex justify-between">
				<span class="text-ink-3"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span class="font-semibold text-accent-ink"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>
			<div class="pp-shipping-totals flex flex-col gap-2 text-sm"><?php wc_cart_totals_shipping_html(); ?></div>
			<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="fee flex justify-between">
				<span class="text-ink-3"><?php echo esc_html( $fee->name ); ?></span>
				<span class="font-semibold"><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
		<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
			<div class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex justify-between border-b border-line py-4 text-sm">
				<span class="text-ink-3"><?php echo esc_html( $tax->label ); ?></span>
				<span class="font-semibold"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

	<div class="order-total flex items-baseline justify-between py-4.5 pb-5.5">
		<span class="text-base font-bold">
			<?php
			if ( wc_tax_enabled() ) {
				printf(
					/* translators: %s: tax suffix from WooCommerce, e.g. "(incl. VAT)" or "(excl. VAT)", already translated. */
					esc_html__( 'Total %s', 'probo-connect' ),
					esc_html(
						WC()->cart->display_prices_including_tax()
							? WC()->countries->inc_tax_or_vat()
							: WC()->countries->ex_tax_or_vat()
					)
				);
			} else {
				esc_html_e( 'Total', 'probo-connect' );
			}
			?>
		</span>
		<span class="text-3xl font-extrabold tracking-[-0.025em]"><?php wc_cart_totals_order_total_html(); ?></span>
	</div>

	<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

	<div class="wc-proceed-to-checkout">
		<?php
		/**
		 * Hook: woocommerce_proceed_to_checkout.
		 */
		do_action( 'woocommerce_proceed_to_checkout' );
		?>
	</div>

	<?php if ( wc_coupons_enabled() ) : ?>
		<details class="mt-2.5">
			<summary class="rounded-pp cursor-pointer border border-line-strong px-4 py-3 text-center text-sm font-semibold">
				<?php esc_html_e( 'Enter discount code', 'probo-connect' ); ?>
			</summary>

			<form class="mt-2.5 flex gap-2" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<label class="sr-only" for="pp-coupon"><?php esc_html_e( 'Discount code', 'probo-connect' ); ?></label>
				<input class="pp-field" id="pp-coupon" type="text" name="coupon_code" value="" placeholder="<?php esc_attr_e( 'Code', 'probo-connect' ); ?>" />
				<button class="pp-btn-secondary shrink-0" type="submit" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'probo-connect' ); ?>">
					<?php esc_html_e( 'Apply', 'probo-connect' ); ?>
				</button>
				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</form>
		</details>
	<?php endif; ?>

	<?php if ( $probo_usps ) : ?>
		<div class="mt-5 flex flex-col gap-2.5 text-[13px] text-ink-2">
			<?php foreach ( $probo_usps as $probo_usp ) : ?>
				<span class="flex gap-2.5"><span class="text-accent-ink" aria-hidden="true">✓</span><?php echo esc_html( $probo_usp ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_after_cart_totals.
	 */
	do_action( 'woocommerce_after_cart_totals' );
	?>
</div>
