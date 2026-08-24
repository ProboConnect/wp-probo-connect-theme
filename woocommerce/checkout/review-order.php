<?php
/**
 * Checkout order review.
 *
 * Overrides woocommerce/templates/checkout/review-order.php. Still a table with
 * the class WooCommerce's AJAX fragment replaces, so plugins that inject rows
 * on the review hooks keep working; only the cell styling is the theme's.
 *
 * Carrier selection is not rendered here — the design puts it in the main
 * column (see form-checkout.php) — but the surrounding shipping hooks are still
 * fired so plugins that use them are unaffected.
 *
 * @package Probo_Connect
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table w-full text-sm">
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}

			$summary = probo_cart_item_summary( $cart_item );
			$specs   = array();

			foreach ( $summary as $entry ) {
				$specs[] = $entry['value'];
			}
			?>
			<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
				<td class="product-name py-3 align-top">
					<div class="flex gap-3.5">
						<div class="w-14 flex-none">
							<?php echo wp_kses_post( $_product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'rounded-pp h-11 w-14 object-cover' ) ) ); ?>
						</div>
						<div>
							<div class="text-sm font-bold">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
							</div>
							<div class="font-mono mt-1 text-[11px] leading-relaxed font-medium text-ink-4">
								<?php
								$specs[] = sprintf(
									/* translators: %s: quantity. */
									_n( '%s st', '%s st', $cart_item['quantity'], 'probo-connect' ),
									number_format_i18n( $cart_item['quantity'] )
								);

								echo esc_html( implode( ' · ', array_filter( $specs ) ) );
								?>
							</div>
						</div>
					</div>
				</td>
				<td class="product-total py-3 text-right align-top text-sm font-bold">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
				</td>
			</tr>
			<?php
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>

	<tfoot>
		<tr class="cart-subtotal border-t border-line">
			<th class="pt-4 pb-1.5 text-left font-normal text-ink-3"><?php esc_html_e( 'Subtotal excl. VAT', 'probo-connect' ); ?></th>
			<td class="pt-4 pb-1.5 text-right font-semibold"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th class="py-1.5 text-left font-normal text-ink-3"><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td class="py-1.5 text-right font-semibold text-accent-ink"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php
		// The carrier choice lives in step 2; what belongs here is what it
		// costs, because this column is where the total is assembled. The
		// surrounding hooks still fire so plugins that append rows around
		// shipping keep their place in the table.
		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
			do_action( 'woocommerce_review_order_before_shipping' );
			?>
			<tr class="shipping">
				<th class="py-1.5 text-left font-normal text-ink-3"><?php esc_html_e( 'Shipping', 'probo-connect' ); ?></th>
				<?php
				// The amount, not wc_cart_totals_shipping_html(): that prints a
				// rate picker of its own, and a second set of
				// shipping_method[] radios beside the ones in step 2 would fight
				// them for the selection.
				?>
				<td class="py-1.5 text-right font-semibold"><?php echo wp_kses_post( WC()->cart->get_cart_shipping_total() ); ?></td>
			</tr>
			<?php
			do_action( 'woocommerce_review_order_after_shipping' );
		}
		?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th class="py-1.5 text-left font-normal text-ink-3"><?php echo esc_html( $fee->name ); ?></th>
				<td class="py-1.5 text-right font-semibold"><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
				<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th class="py-1.5 text-left font-normal text-ink-3"><?php echo esc_html( $tax->label ); ?></th>
					<td class="py-1.5 text-right font-semibold"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total border-t border-line">
			<th class="pt-4.5 text-left text-base font-bold"><?php esc_html_e( 'To pay', 'probo-connect' ); ?></th>
			<td class="pt-4.5 text-right text-3xl font-extrabold tracking-[-0.025em]"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>
