<?php
/**
 * Cart.
 *
 * Overrides woocommerce/templates/cart/cart.php. Line items are cards showing
 * the full configuration summary and the artwork thumbnail. The design
 * deliberately has no "Samenstelling wijzigen" link and no quantity stepper,
 * because both belong to the configurator, not to the cart — and no upload
 * button of its own either: Probo Connect prints the real one on
 * woocommerce_after_cart_item_name.
 *
 * @package Probo_Connect
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: woocommerce_before_cart.
 */
do_action( 'woocommerce_before_cart' );
?>

<main class="pp-container py-10 pb-18">
	<h1 class="mb-7 text-4xl font-extrabold tracking-[-0.035em] lg:text-[44px]"><?php esc_html_e( 'Cart', 'probo-connect' ); ?></h1>

	<div class="grid items-start gap-8 lg:grid-cols-[1fr_380px]">
		<form class="woocommerce-cart-form flex flex-col gap-3.5" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
			<?php
			/**
			 * Hook: woocommerce_before_cart_table.
			 */
			do_action( 'woocommerce_before_cart_table' );

			/**
			 * Hook: woocommerce_before_cart_contents.
			 */
			do_action( 'woocommerce_before_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}

				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$summary           = probo_cart_item_summary( $cart_item );
				?>
				<div class="pp-card grid gap-6 p-5.5 sm:grid-cols-[120px_1fr_auto] sm:items-start <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div>
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail', array( 'class' => 'rounded-pp h-24 w-full object-cover' ) ), $cart_item, $cart_item_key );

						if ( $product_permalink ) {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
						} else {
							echo wp_kses_post( $thumbnail );
						}
						?>
					</div>

					<div>
						<div class="text-lg font-bold">
							<?php
							// The filter is given the finished name exactly as core gives
							// it, so plugins that decorate it get what they expect.
							$product_name = $product_permalink
								? sprintf(
									'<a class="text-ink no-underline hover:text-accent-ink" href="%s">%s</a>',
									esc_url( $product_permalink ),
									esc_html( $_product->get_name() )
								)
								: esc_html( $_product->get_name() );

							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product_name, $cart_item, $cart_item_key ) );

							/**
							 * Hook: woocommerce_after_cart_item_name.
							 *
							 * Where plugins hang extra per-line markup. The item data
							 * itself is not printed here: probo_cart_item_summary()
							 * already lays it out as the design's spec grid below.
							 */
							do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

							if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
								echo wp_kses_post(
									apply_filters(
										'woocommerce_cart_item_backorder_notification',
										'<p class="backorder_notification mt-2 text-[13px] font-medium text-ink-3">' . esc_html__( 'Available on backorder', 'probo-connect' ) . '</p>',
										$product_id
									)
								);
							}
							?>
						</div>

						<?php if ( $summary ) : ?>
							<div class="mt-3.5 grid max-w-[560px] gap-2 text-[13px] sm:grid-cols-2 lg:grid-cols-3">
								<?php foreach ( $summary as $entry ) : ?>
									<span class="text-ink-3">
										<?php echo esc_html( $entry['key'] ); ?>
										<span class="font-semibold text-ink"><?php echo esc_html( $entry['value'] ); ?></span>
									</span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php
						// No upload button of the theme's own here: Probo Connect prints
						// the real one on woocommerce_after_cart_item_name, above. An
						// earlier guessed "Open uploader" link sat next to it and led
						// nowhere.
						?>
						<div class="mt-4 flex flex-wrap items-center gap-3.5 text-[13px] font-bold">
							<?php
							// Deliberately not WooCommerce's own `remove` class: that class is
							// what pulls in its 1em red disc, and the disc's colour is
							// !important — which no theme rule can outrank, because an
							// !important declaration in a lower cascade layer beats one in a
							// higher layer. Sidestepping the class is the only clean way.
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce builds and escapes this link.
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="pp-cart-remove text-ink-3 no-underline hover:text-ink hover:underline" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									esc_attr( sprintf( __( 'Remove %s from cart', 'probo-connect' ), $_product->get_name() ) ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() ),
									esc_html__( 'Remove', 'probo-connect' )
								),
								$cart_item_key
							);
							?>
						</div>
					</div>

					<div class="text-right">
						<div class="text-[22px] font-extrabold tracking-[-0.02em]">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
						</div>
						<?php if ( wc_tax_enabled() ) : ?>
							<div class="font-mono mt-1 text-xs font-medium text-ink-4">
								<?php
								echo esc_html(
									WC()->cart->display_prices_including_tax()
										? WC()->countries->inc_tax_or_vat()
										: WC()->countries->ex_tax_or_vat()
								);
								?>
							</div>
						<?php endif; ?>

						<?php
						// Quantity is part of the configuration, so it is shown, not edited.
						// The hidden field keeps it intact when the form posts a coupon.
						printf(
							'<input type="hidden" name="cart[%s][qty]" value="%s" />',
							esc_attr( $cart_item_key ),
							esc_attr( $cart_item['quantity'] )
						);
						?>
					</div>
				</div>
				<?php
			}

			/**
			 * Hook: woocommerce_cart_contents.
			 */
			do_action( 'woocommerce_cart_contents' );
			?>

			<div class="hidden">
				<?php
				/**
				 * Hook: woocommerce_cart_actions.
				 */
				do_action( 'woocommerce_cart_actions' );
				wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' );
				?>
			</div>

			<?php
			/**
			 * Hook: woocommerce_after_cart_contents.
			 */
			do_action( 'woocommerce_after_cart_contents' );

			/**
			 * Hook: woocommerce_after_cart_table.
			 */
			do_action( 'woocommerce_after_cart_table' );
			?>
		</form>

		<div class="lg:sticky lg:top-[76px]">
			<?php
			/**
			 * Hook: woocommerce_before_cart_collaterals.
			 */
			do_action( 'woocommerce_before_cart_collaterals' );
			?>

			<div class="cart-collaterals">
				<?php
				/**
				 * Hook: woocommerce_cart_collaterals.
				 *
				 * Renders cart totals and cross-sells.
				 */
				do_action( 'woocommerce_cart_collaterals' );
				?>
			</div>
		</div>
	</div>
</main>

<?php
/**
 * Hook: woocommerce_after_cart.
 */
do_action( 'woocommerce_after_cart' );
