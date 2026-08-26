<?php
/**
 * WooCommerce integration.
 *
 * The theme styles WooCommerce and the Probo Connect plugin; it does not
 * reimplement either. Prices deliberately appear only where they really come
 * out of the plugin — configurator, cart and checkout — while listings end in
 * a "Configureer nu →" call to action.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare support and image sizes.
 */
function probo_woocommerce_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1000,
			'product_grid'          => array(
				'default_columns' => 3,
				'default_rows'    => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'probo_woocommerce_setup' );

/**
 * Three columns on archives, matching the design's category page.
 *
 * @return int
 */
function probo_loop_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'probo_loop_columns' );

/**
 * Strip the pieces of WooCommerce's default loop the design does not use.
 *
 * The tiles are rendered by probo_product_card() from content-product.php, so
 * the stock title/price/add-to-cart hooks would only duplicate them. Related
 * products and other loops that still run the stock hooks lose their price for
 * the same reason: an unconfigured price is meaningless here.
 */
function probo_trim_woocommerce_loop() {
	// Every loop in the shop renders through the theme's content-product.php,
	// which draws the whole tile itself. The stock callbacks that would build a
	// second tile around it are unhooked here — including the link open/close
	// pair, which would otherwise nest an <a> inside the card's own <a>.
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

	// The single product summary is laid out by the template, not by hooks: the
	// price belongs to the configurator, and the add-to-cart form is moved into
	// the configurator band further down the page.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

	// Title, rating and short description are drawn by single-product.php in the
	// design's own type scale, so the stock callbacks printed each of them a
	// second time underneath.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
}
add_action( 'init', 'probo_trim_woocommerce_loop' );

/**
 * WooCommerce's own page chrome, which the theme's templates already draw.
 *
 * archive-product.php and single-product.php open their own <main> and print
 * the breadcrumb inside a pp-container. The stock callbacks on the same hooks
 * added a second, unstyled breadcrumb above it plus a #primary/#main wrapper
 * around the theme's own. The hooks themselves keep firing, so plugins that
 * hang off them are unaffected.
 */
function probo_unhook_woocommerce_wrappers() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
}
add_action( 'template_redirect', 'probo_unhook_woocommerce_wrappers' );

/**
 * Drop WooCommerce's float-based layout sheets.
 *
 * woocommerce-layout.css floats every ul.products li.product and gives it
 * width:22.05%, which squeezed the tiles to a fraction of their grid column —
 * the theme lays the loop out with a Tailwind grid instead. smallscreen.css
 * goes with it: it restacks the same float layout and the cart/checkout tables
 * the theme rewrites in its own templates. woocommerce.css stays, because the
 * star ratings, gallery and notices still come from it.
 */
function probo_dequeue_woocommerce_layout() {
	wp_dequeue_style( 'woocommerce-layout' );
	wp_dequeue_style( 'woocommerce-smallscreen' );
}
add_action( 'wp_enqueue_scripts', 'probo_dequeue_woocommerce_layout', 20 );

/**
 * The handles that must not reach the page at all.
 *
 * The dequeue above is not enough on its own: on the cart and checkout the two
 * layout sheets came back after it had run, and woocommerce-layout.css floats
 * .cart_totals at width:48% — which is what shrank the totals column to 182px
 * inside its own 380px grid cell. Dropping them at print time cannot be beaten
 * by a late enqueue.
 *
 * @return string[]
 */
function probo_dropped_style_handles() {
	/**
	 * Filters the stylesheet handles the theme refuses to print.
	 *
	 * @param string[] $handles Style handles.
	 */
	return apply_filters( 'probo_dropped_style_handles', array( 'woocommerce-layout', 'woocommerce-smallscreen' ) );
}

/**
 * Move the plugin's stylesheets into the theme's `plugins` layer.
 *
 * WooCommerce ships its CSS unlayered. The theme's own rules live in the layers
 * Tailwind emits, and unlayered CSS beats layered CSS whatever the specificity —
 * so every plugin default silently outranked the design: purple buttons, green
 * prices, square thumbnails. Rather than fight that rule by rule, each sheet's
 * <link> is rewritten into an @import inside layer(plugins), which
 * assets/css/src/theme.css declares ahead of its own layers. The plugin keeps
 * supplying its star ratings, gallery and notices; the theme now wins every
 * collision by construction.
 *
 * This runs on style_loader_tag rather than on an enqueue hook so it does not
 * matter when — or by whom — the sheet was enqueued.
 *
 * @param string $tag    The <link> tag WordPress is about to print.
 * @param string $handle Style handle.
 * @param string $href   Resolved stylesheet URL.
 * @param string $media  Media attribute.
 * @return string
 */
function probo_layer_plugin_styles( $tag, $handle, $href, $media ) {
	/**
	 * Filters which stylesheet handles are demoted to the `plugins` layer.
	 *
	 * A shop that adds another plugin with the same habit can push it into the
	 * same layer from here.
	 *
	 * @param string[] $handles Style handles.
	 */
	$handles = apply_filters( 'probo_layered_style_handles', array( 'woocommerce-general', 'wc-blocks-style' ) );

	if ( in_array( $handle, probo_dropped_style_handles(), true ) ) {
		return '';
	}

	if ( ! in_array( $handle, $handles, true ) || ! $href ) {
		return $tag;
	}

	return sprintf(
		"<style id='%s-css'>@import url(\"%s\") layer(plugins)%s;</style>\n",
		esc_attr( $handle ),
		esc_url( $href ),
		'all' === $media || ! $media ? '' : ' ' . esc_attr( $media )
	);
}
add_filter( 'style_loader_tag', 'probo_layer_plugin_styles', 10, 4 );

/**
 * Breadcrumb defaults matching the design's mono trail.
 *
 * @param array $args Breadcrumb args.
 * @return array
 */
function probo_breadcrumb_defaults( $args ) {
	$args['delimiter']   = ' / ';
	$args['wrap_before'] = '';
	$args['wrap_after']  = '';
	$args['before']      = '';
	$args['after']       = '';

	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'probo_breadcrumb_defaults' );

/**
 * The configuration summary shown per cart line.
 *
 * Uses WooCommerce's own item data, which is where Probo Connect puts the
 * chosen size, material, finishing and delivery date.
 *
 * @param array $cart_item Cart item.
 * @return array<int, array{key: string, value: string}>
 */
function probo_cart_item_summary( $cart_item ) {
	if ( ! function_exists( 'wc_get_formatted_cart_item_data' ) ) {
		return array();
	}

	$data    = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );
	$summary = array();

	foreach ( $data as $entry ) {
		$summary[] = array(
			'key'   => wp_strip_all_tags( $entry['key'] ?? '' ),
			'value' => wp_strip_all_tags( $entry['display'] ?? ( $entry['value'] ?? '' ) ),
		);
	}

	return $summary;
}

/**
 * Whether a product is actually configured through Probo Connect.
 *
 * Only these products get the "Stel je product samen" band and the price that
 * comes out of the configurator. A plain simple product has a price and an
 * add-to-cart button of its own, and nothing to configure.
 *
 * @param WC_Product|null $product Product, defaults to the global one.
 * @return bool
 */
function probo_is_configurable_product( $product = null ) {
	$product = $product ? $product : ( isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null );

	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	/**
	 * Filters whether a product is treated as a Probo Connect configurable.
	 *
	 * @param bool       $is_configurable Whether the configurator applies.
	 * @param WC_Product $product         Product.
	 */
	return (bool) apply_filters( 'probo_is_configurable_product', $product->is_type( 'probo_configurable' ), $product );
}

/**
 * Move Probo Connect's configurator into the theme's own band.
 *
 * The plugin renders it on woocommerce_single_product_summary at priority 25 —
 * the right-hand column, beside the gallery. The design puts it full width in
 * the grey "Stel je product samen" band further down, so its callback is taken
 * off that hook and hung on probo_configurator_band, which single-product.php
 * fires inside the band.
 *
 * The hook it is taken off is read from the plugin's own
 * probo_configurator_render_action option, so a shop that has already pointed
 * the configurator somewhere else keeps working.
 */
function probo_move_configurator() {
	if ( ! class_exists( 'Probo_Hooks' ) ) {
		return;
	}

	$action = (string) get_option( 'probo_configurator_render_action', 'woocommerce_single_product_summary' );

	if ( 'probo_configurator_band' === $action ) {
		return;
	}

	global $wp_filter;

	if ( empty( $wp_filter[ $action ] ) ) {
		return;
	}

	foreach ( $wp_filter[ $action ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$function = $callback['function'];

			// The plugin registers it as [ $instance, 'probo_add_configurator' ],
			// so the instance has to come from the hook itself — there is no
			// public accessor for it.
			if (
				! is_array( $function )
				|| ! isset( $function[0], $function[1] )
				|| ! is_object( $function[0] )
				|| ! $function[0] instanceof Probo_Hooks
				|| 'probo_add_configurator' !== $function[1]
			) {
				continue;
			}

			remove_action( $action, $function, $priority );
			add_action( 'probo_configurator_band', $function, $priority );
		}
	}
}
add_action( 'template_redirect', 'probo_move_configurator' );

/**
 * Move the plugin's shipping selector into the checkout's own step.
 *
 * Probo Connect renders its delivery dates and carrier picker on
 * woocommerce_checkout_shipping — the hook this theme fires inside the
 * "Bezorgadres" section — so the block landed between the address fields and
 * the numbered steps below it. Its callback is moved to
 * probo_checkout_shipping_selector, which form-checkout.php fires as a step of
 * its own.
 *
 * The source hook comes from the plugin's own probo_shipping_render_action
 * option, so a shop that repointed it keeps working.
 */
function probo_move_shipping_selector() {
	if ( ! class_exists( 'Probo_Shipping_Checkout' ) ) {
		return;
	}

	$action = (string) get_option( 'probo_shipping_render_action', 'woocommerce_checkout_shipping' );

	if ( 'probo_checkout_shipping_selector' === $action ) {
		return;
	}

	global $wp_filter;

	if ( empty( $wp_filter[ $action ] ) ) {
		return;
	}

	foreach ( $wp_filter[ $action ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$function = $callback['function'];

			if (
				! is_array( $function )
				|| ! isset( $function[0], $function[1] )
				|| ! is_object( $function[0] )
				|| ! $function[0] instanceof Probo_Shipping_Checkout
				|| 'render_shipping_selector' !== $function[1]
			) {
				continue;
			}

			remove_action( $action, $function, $priority );
			add_action( 'probo_checkout_shipping_selector', $function, $priority );
		}
	}
}
add_action( 'template_redirect', 'probo_move_shipping_selector' );

/**
 * Wrap whatever Probo Connect renders in the add-to-cart area.
 *
 * The wrapper is what assets/css/print-connect.css hangs its skin off, and it
 * also carries the design tokens into the component — the only styling channel
 * that survives if the configurator turns out to use shadow DOM.
 */
function probo_configurator_open() {
	echo '<div class="pp-configurator">';
}

/**
 * Close the configurator wrapper.
 */
function probo_configurator_close() {
	echo '</div>';
}

/**
 * Product loops are a Tailwind grid rather than WooCommerce's float columns.
 *
 * @return string
 */
function probo_product_loop_start() {
	return '<ul class="products m-0 grid list-none gap-4 p-0 sm:grid-cols-2 lg:grid-cols-3">';
}
add_filter( 'woocommerce_product_loop_start', 'probo_product_loop_start' );

/**
 * The shipping methods that are pickup points rather than deliveries.
 *
 * Split on method and instance id, never on the word "afhalen" in the label:
 * a label is a shop setting and changes the day someone rewords it, while the
 * instance id is what the rate actually is. Both `method_id` and
 * `method_id:instance_id` are accepted, so a shop can name one specific zone's
 * pickup instance without catching every local_pickup in the store.
 *
 * @return string[]
 */
function probo_checkout_pickup_instance_ids() {
	/**
	 * Filters which shipping methods count as pickup.
	 *
	 * @param string[] $ids Method ids, optionally suffixed with :instance_id.
	 */
	return array_map( 'strval', (array) apply_filters( 'probo_checkout_pickup_instance_ids', array( 'local_pickup' ) ) );
}

/**
 * Whether a rate is one of those pickup methods.
 *
 * @param WC_Shipping_Rate $rate Rate.
 * @return bool
 */
function probo_rate_is_pickup( $rate ) {
	$ids = probo_checkout_pickup_instance_ids();

	return in_array( (string) $rate->get_method_id(), $ids, true )
		|| in_array( $rate->get_method_id() . ':' . $rate->get_instance_id(), $ids, true );
}

/**
 * Preselect the cheapest delivery rate rather than whichever came back first.
 *
 * Step 2 of the checkout asks one question — when it has to be there — and
 * answers the carrier question itself. That only holds up if the answer it
 * fills in is the cheapest one; core's default is simply the first rate in the
 * package. Pickup rates are skipped: pickup is a deliberate choice, not
 * something to be handed to someone who never asked for it.
 *
 * Core only consults this filter while nothing has been chosen yet, so a
 * customer who picks a carrier keeps it.
 *
 * @param string             $default Rate id core would choose.
 * @param WC_Shipping_Rate[] $rates   Rates in the package, keyed by rate id.
 * @return string
 */
function probo_checkout_preselect_cheapest_rate( $default, $rates ) {
	if ( ! probo_checkout_is_stepped() ) {
		return $default;
	}

	$cheapest = '';

	foreach ( (array) $rates as $rate_id => $rate ) {
		if ( ! $rate instanceof WC_Shipping_Rate || probo_rate_is_pickup( $rate ) ) {
			continue;
		}

		if ( '' === $cheapest || (float) $rate->get_cost() < (float) $rates[ $cheapest ]->get_cost() ) {
			$cheapest = (string) $rate_id;
		}
	}

	return '' !== $cheapest ? $cheapest : $default;
}
add_filter( 'woocommerce_shipping_chosen_method', 'probo_checkout_preselect_cheapest_rate', 10, 2 );

/**
 * One carrier option card.
 *
 * @param WC_Shipping_Rate $rate     Rate.
 * @param int|string       $index    Package index.
 * @param string           $selected Chosen rate id for this package.
 */
function probo_checkout_rate_card( $rate, $index, $selected ) {
	$rate_id  = $rate->get_id();
	$input_id = 'shipping_method_' . $index . '_' . sanitize_title( $rate_id );
	$meta     = array_filter( array_map( 'wp_strip_all_tags', (array) $rate->get_meta_data() ) );
	?>
	<li>
		<input
			type="radio"
			name="shipping_method[<?php echo esc_attr( $index ); ?>]"
			id="<?php echo esc_attr( $input_id ); ?>"
			value="<?php echo esc_attr( $rate_id ); ?>"
			class="shipping_method"
			<?php checked( $selected, $rate_id ); ?>
		/>
		<label for="<?php echo esc_attr( $input_id ); ?>">
			<span class="block text-[15px] font-bold"><?php echo wp_kses_post( $rate->get_label() ); ?></span>
			<?php if ( $meta ) : ?>
				<span class="font-mono mt-1 block text-xs font-medium text-ink-2">
					<?php echo esc_html( implode( ' · ', $meta ) ); ?>
				</span>
			<?php endif; ?>
		</label>
		<span class="amount">
			<?php
			if ( $rate->get_cost() > 0 ) {
				echo wp_kses_post( wc_price( $rate->get_cost() ) );
			} else {
				esc_html_e( 'free', 'probo-connect' );
			}
			?>
		</span>
	</li>
	<?php
}

/**
 * Carrier ("Vervoerder") options for step 2 of the checkout.
 *
 * This is the fallback path: a shop without Probo Connect, where WooCommerce's
 * own rates are the whole delivery choice. With the plugin active, step 2 is
 * the plugin's own dates and methods and this returns nothing (see
 * form-checkout.php).
 *
 * Same shape as the design either way: pickup is a tab rather than six rows at
 * the bottom of the carrier list, and the carrier list itself shows the
 * cheapest rate with the rest folded into "Zelf een vervoerder kiezen (N)".
 * The tabs are two radios with a :has(:checked) rule, so switching between them
 * needs no JavaScript.
 *
 * Kept in sync over AJAX by probo_shipping_fragment().
 *
 * @return string
 */
function probo_checkout_shipping_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
		return '';
	}

	$packages = WC()->shipping()->get_packages();
	$chosen   = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();

	ob_start();

	echo '<div class="pp-checkout-shipping pp-delivery">';

	foreach ( $packages as $index => $package ) {
		$rates    = $package['rates'] ?? array();
		$selected = $chosen[ $index ] ?? '';

		if ( ! $rates ) {
			echo '<p class="text-[15px] text-ink-3">' . esc_html__( 'No carriers are available for this address.', 'probo-connect' ) . '</p>';
			continue;
		}

		// The classic checkout gets the flat list it always had: one card per
		// rate, in the order WooCommerce returned them.
		if ( ! probo_checkout_is_stepped() ) {
			echo '<ul class="pp-shipping-methods">';

			foreach ( $rates as $rate ) {
				probo_checkout_rate_card( $rate, $index, $selected );
			}

			echo '</ul>';

			foreach ( $rates as $rate ) {
				/** This action is documented below. */
				do_action( 'woocommerce_after_shipping_rate', $rate, $index );
			}

			continue;
		}

		$ship   = array();
		$pickup = array();

		foreach ( $rates as $rate ) {
			if ( probo_rate_is_pickup( $rate ) ) {
				$pickup[] = $rate;
			} else {
				$ship[] = $rate;
			}
		}

		// Cheapest first: the visible row is the one the checkout preselected,
		// and the fold below it is the exception, not the default.
		$by_cost = static function ( $a, $b ) {
			return (float) $a->get_cost() <=> (float) $b->get_cost();
		};

		usort( $ship, $by_cost );
		usort( $pickup, $by_cost );

		if ( $ship && $pickup ) {
			probo_checkout_mode_tabs( $index, count( $pickup ), probo_checkout_rates_contain( $pickup, $selected ) );
		}

		if ( $ship ) :
			$rest = array_slice( $ship, 1 );
			?>
			<div class="pp-delivery-pane pp-delivery-pane--ship">
				<ul class="pp-shipping-methods">
					<?php probo_checkout_rate_card( $ship[0], $index, $selected ); ?>
				</ul>

				<?php if ( $rest ) : ?>
					<details class="pp-carriers-more"<?php echo probo_checkout_rates_contain( $rest, $selected ) ? ' open' : ''; ?>>
						<summary>
							<?php
							printf(
								/* translators: %s: number of carriers. */
								esc_html__( 'Choose a carrier yourself (%s)', 'probo-connect' ),
								esc_html( number_format_i18n( count( $ship ) ) )
							);
							?>
						</summary>
						<ul class="pp-shipping-methods">
							<?php
							foreach ( $rest as $rate ) {
								probo_checkout_rate_card( $rate, $index, $selected );
							}
							?>
						</ul>
					</details>
				<?php endif; ?>
			</div>
			<?php
		endif;

		if ( $pickup ) :
			$visible = array_slice( $pickup, 0, 3 );
			$folded  = array_slice( $pickup, 3 );
			?>
			<div class="pp-delivery-pane pp-delivery-pane--pickup">
				<ul class="pp-shipping-methods">
					<?php
					foreach ( $visible as $rate ) {
						probo_checkout_rate_card( $rate, $index, $selected );
					}
					?>
				</ul>

				<?php if ( $folded ) : ?>
					<details class="pp-carriers-more"<?php echo probo_checkout_rates_contain( $folded, $selected ) ? ' open' : ''; ?>>
						<summary>
							<?php
							printf(
								/* translators: %s: number of pickup points. */
								esc_html__( 'All %s pickup points', 'probo-connect' ),
								esc_html( number_format_i18n( count( $pickup ) ) )
							);
							?>
						</summary>
						<ul class="pp-shipping-methods">
							<?php
							foreach ( $folded as $rate ) {
								probo_checkout_rate_card( $rate, $index, $selected );
							}
							?>
						</ul>
					</details>
				<?php endif; ?>
			</div>
			<?php
		endif;

		/**
		 * Hook: woocommerce_after_shipping_rate — fired per package so plugins
		 * that annotate rates keep working.
		 */
		foreach ( $rates as $rate ) {
			do_action( 'woocommerce_after_shipping_rate', $rate, $index );
		}
	}

	echo '</div>';

	return (string) ob_get_clean();
}

/**
 * Whether the chosen rate is one of the rates in a folded group.
 *
 * A <details> that hides the customer's own choice would look like the choice
 * was lost, so the fold opens when it holds the selection.
 *
 * @param WC_Shipping_Rate[] $rates    Rates.
 * @param string             $selected Chosen rate id.
 * @return bool
 */
function probo_checkout_rates_contain( $rates, $selected ) {
	foreach ( $rates as $rate ) {
		if ( $rate->get_id() === $selected ) {
			return true;
		}
	}

	return false;
}

/**
 * The "Bezorgen / Ophalen" tabs above the delivery choice.
 *
 * Two radios and a :has(:checked) rule rather than a script: switching tabs is
 * a view state, and a view state that needs JavaScript is a view state that is
 * broken for the one customer whose script did not load.
 *
 * With Probo Connect the pickup points only arrive once a day has been picked,
 * so the count is not known when the tabs are printed. Passing null renders the
 * tab without it and leaves an element for assets/js/checkout-steps.js to fill
 * in from the rendered list; the stylesheet hides the whole tab strip while
 * there is no pickup pane beside it.
 *
 * @param int|string $group         Suffix for the radio group, so several blocks can coexist.
 * @param int|null   $pickup_count  Number of pickup points, or null when not yet known.
 * @param bool       $pickup_active Whether the chosen option is a pickup point.
 */
function probo_checkout_mode_tabs( $group, $pickup_count = null, $pickup_active = false ) {
	$name = 'pp-delivery-mode-' . $group;
	?>
	<div class="pp-step-tabs">
		<input type="radio" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>-ship" class="pp-tab-input pp-tab-input--ship" <?php checked( ! $pickup_active ); ?> />
		<label for="<?php echo esc_attr( $name ); ?>-ship"><?php esc_html_e( 'Delivery', 'probo-connect' ); ?></label>

		<input type="radio" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>-pickup" class="pp-tab-input pp-tab-input--pickup" <?php checked( $pickup_active ); ?> />
		<label for="<?php echo esc_attr( $name ); ?>-pickup">
			<?php esc_html_e( 'Pickup', 'probo-connect' ); ?>
			<span class="pp-tab-count" data-pp-pickup-label>
				<?php
				if ( null !== $pickup_count ) {
					printf(
						/* translators: %s: number of pickup locations. */
						esc_html__( '(%s locations)', 'probo-connect' ),
						esc_html( number_format_i18n( $pickup_count ) )
					);
				}
				?>
			</span>
		</label>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
   The accordion checkout.

   Three steps, one open at a time: 1 Gegevens & adres, 2 Bezorging, 3 Betalen.
   The accordion itself is progressive enhancement — assets/js/checkout-steps.js
   collapses the steps a customer is not in — but everything the collapsed state
   shows is built here, in PHP. A summary line assembled in JavaScript would be
   empty on a non-JS refresh and wrong after a server-side validation round; the
   same line built from the session survives both.
--------------------------------------------------------------------------- */

/**
 * Whether the checkout runs as three steps rather than as one long page.
 *
 * Opt-in: a shop that was happy with the one-page checkout keeps it, and the
 * accordion — with everything that hangs off it, from the bare header to the
 * rearranged delivery list — is a single Customizer choice away
 * (Thema-instellingen → Componenten → Checkout-stijl).
 *
 * @return bool
 */
function probo_checkout_is_stepped() {
	/**
	 * Filters whether the stepped checkout is used.
	 *
	 * @param bool $stepped Whether the accordion checkout is on.
	 */
	return (bool) apply_filters( 'probo_checkout_is_stepped', 'Stappen' === probo_get( 'checkout_style' ) );
}

/**
 * Whether this request is the stepped checkout flow itself.
 *
 * The order-received page and the account endpoints run through the same
 * template but are not part of the flow, so they keep the ordinary header,
 * navigation and footer.
 *
 * @return bool
 */
function probo_is_checkout_flow() {
	return function_exists( 'is_checkout' )
		&& probo_checkout_is_stepped()
		&& is_checkout()
		&& ! is_order_received_page()
		&& ! is_wc_endpoint_url();
}

/**
 * The three steps, in order.
 *
 * @return array<int, array{title: string, intro: string}>
 */
function probo_checkout_steps() {
	return array(
		1 => array(
			'title' => __( 'Details & address', 'probo-connect' ),
			'short' => __( 'Details', 'probo-connect' ),
			'intro' => __( 'Enter your business details once; next time they will already be filled in.', 'probo-connect' ),
			'next'  => __( 'Continue to delivery', 'probo-connect' ),
		),
		2 => array(
			'title' => __( 'Delivery', 'probo-connect' ),
			'short' => __( 'Delivery', 'probo-connect' ),
			'intro' => __( 'Fast or affordable — we will pick the day and carrier for you. Prefer to choose yourself? That works too.', 'probo-connect' ),
			'next'  => __( 'Continue to payment', 'probo-connect' ),
		),
		3 => array(
			'title' => __( 'Payment', 'probo-connect' ),
			'short' => __( 'Payment', 'probo-connect' ),
			'intro' => '',
			'next'  => '',
		),
	);
}

/**
 * The step bar above section 1 of the one-page checkout.
 *
 * The accordion has its own progress line in the checkout header, where the
 * step that is open is the page. The one-page layout shows everything at once,
 * so this is orientation rather than navigation: it names the three things that
 * are about to be asked, and marks the first one that is not answered yet —
 * which for a returning customer with an address on file is already "Bezorging".
 *
 * The steps come from probo_checkout_steps(), so the two layouts cannot drift
 * apart in what they call them.
 */
function probo_checkout_step_bar() {
	$steps   = probo_checkout_steps();
	$current = probo_checkout_initial_step();
	?>
	<ol class="pp-checkout-steps">
		<?php foreach ( $steps as $number => $step ) : ?>
			<li
				class="pp-checkout-steps-step"
				data-state="<?php echo esc_attr( $number === $current ? 'current' : 'todo' ); ?>"
			>
				<span class="pp-checkout-steps-badge" aria-hidden="true"><?php echo esc_html( (string) $number ); ?></span>
				<span><?php echo esc_html( $step['short'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
}

/**
 * Whether the address step has everything it needs.
 *
 * Checks the fields WooCommerce itself marks required, so a shop that adds or
 * drops a field through woocommerce_checkout_fields does not have to come back
 * here. The shipping group only counts when the customer actually ships
 * somewhere else.
 *
 * @param WC_Checkout|null $checkout Checkout object.
 * @return bool
 */
function probo_checkout_step_1_complete( $checkout = null ) {
	$checkout = $checkout ? $checkout : ( function_exists( 'WC' ) ? WC()->checkout() : null );

	if ( ! $checkout ) {
		return false;
	}

	$groups = array( 'billing' );

	if ( WC()->cart && WC()->cart->needs_shipping_address() && WC()->customer && WC()->customer->get_shipping_address_1() ) {
		$groups[] = 'shipping';
	}

	$fields = $checkout->get_checkout_fields();

	foreach ( $groups as $group ) {
		foreach ( (array) ( $fields[ $group ] ?? array() ) as $key => $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			if ( '' === trim( (string) $checkout->get_value( $key ) ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Whether a delivery choice has been made.
 *
 * With Probo Connect that is its own date-and-method pair in the session — the
 * plugin stays the authority on what was chosen. Without the plugin it is
 * simply WooCommerce's chosen rate.
 *
 * @return bool
 */
function probo_checkout_step_2_complete() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	if ( ! WC()->cart->needs_shipping() ) {
		return true;
	}

	if ( class_exists( 'Probo_Meta_Keys' ) && WC()->session ) {
		return (bool) WC()->session->get( Probo_Meta_Keys::SHIPPING_DELIVERY_DATE )
			&& (bool) WC()->session->get( Probo_Meta_Keys::SHIPPING_METHOD_CODE );
	}

	$chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();

	return (bool) array_filter( $chosen );
}

/**
 * The step the checkout opens on.
 *
 * The first step that is not done yet — which for a logged-in repeat customer
 * with a complete profile is step 2, the only question they actually have to
 * answer.
 *
 * @return int
 */
function probo_checkout_initial_step() {
	if ( ! probo_checkout_step_1_complete() ) {
		return 1;
	}

	return probo_checkout_step_2_complete() ? 3 : 2;
}

/**
 * The one-line summary a collapsed step shows.
 *
 * @param int $step Step number.
 * @return string Plain text, empty when the step has nothing decided yet.
 */
function probo_checkout_step_summary( $step ) {
	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return '';
	}

	$parts = array();

	if ( 1 === $step ) {
		$customer = WC()->customer;
		$street   = trim( $customer->get_shipping_address_1() ? $customer->get_shipping_address_1() : $customer->get_billing_address_1() );
		$postcode = trim( $customer->get_shipping_postcode() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode() );
		$city     = trim( $customer->get_shipping_city() ? $customer->get_shipping_city() : $customer->get_billing_city() );

		$parts[] = $customer->get_billing_email();
		$parts[] = trim( $street . ( $postcode || $city ? ', ' . trim( $postcode . ' ' . $city ) : '' ), ' ,' );

		/**
		 * Filters the extras appended to the address summary.
		 *
		 * The design shows a purchase reference here. There is no order-level
		 * reference field in the checkout yet — Probo Connect stores one per
		 * cart line — so this is the seam to add it through once there is.
		 *
		 * @param string[] $extra Additional summary fragments.
		 */
		$parts = array_merge( $parts, (array) apply_filters( 'probo_checkout_address_summary_extra', array() ) );
	}

	if ( 2 === $step ) {
		$parts = probo_checkout_delivery_summary_parts();
	}

	if ( 3 === $step ) {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		$chosen   = WC()->session ? (string) WC()->session->get( 'chosen_payment_method' ) : '';

		if ( isset( $gateways[ $chosen ] ) ) {
			$parts[] = wp_strip_all_tags( $gateways[ $chosen ]->get_title() );
		}
	}

	return implode( ' · ', array_filter( array_map( 'trim', $parts ) ) );
}

/**
 * The delivery summary: day, carrier and amount.
 *
 * Read from Probo Connect's own session values where the plugin is active —
 * the date, the method name and the shipping cost are exactly what it stamps on
 * the order — and from WooCommerce's chosen rate otherwise.
 *
 * @return string[]
 */
function probo_checkout_delivery_summary_parts() {
	$parts = array();

	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return $parts;
	}

	if ( class_exists( 'Probo_Meta_Keys' ) && WC()->session && WC()->session->get( Probo_Meta_Keys::SHIPPING_DELIVERY_DATE ) ) {
		$date      = (string) WC()->session->get( Probo_Meta_Keys::SHIPPING_DELIVERY_DATE );
		$name      = (string) WC()->session->get( Probo_Meta_Keys::SHIPPING_METHOD_NAME );
		$cost      = WC()->session->get( Probo_Meta_Keys::SHIPPING_COST );
		$surcharge = (float) WC()->session->get( Probo_Meta_Keys::SHIPPING_RUSH_SURCHARGE );
		$timestamp = strtotime( $date );

		if ( $timestamp ) {
			$parts[] = wp_date( 'D j M', $timestamp );
		}

		$parts[] = $name;

		if ( null !== $cost ) {
			$parts[] = wp_strip_all_tags( wc_price( (float) $cost ) );
		}

		if ( $surcharge > 0 ) {
			$parts[] = sprintf(
				/* translators: %s: rush surcharge amount. */
				__( 'incl. %s rush fee', 'probo-connect' ),
				wp_strip_all_tags( wc_price( $surcharge ) )
			);
		}

		return $parts;
	}

	$packages = WC()->shipping() ? WC()->shipping()->get_packages() : array();
	$chosen   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();

	foreach ( $packages as $index => $package ) {
		$rate = $package['rates'][ $chosen[ $index ] ?? '' ] ?? null;

		if ( ! $rate ) {
			continue;
		}

		$meta = array_filter( array_map( 'wp_strip_all_tags', (array) $rate->get_meta_data() ) );

		$parts[] = wp_strip_all_tags( $rate->get_label() );

		if ( $meta ) {
			$parts[] = implode( ' · ', $meta );
		}

		$parts[] = $rate->get_cost() > 0 ? wp_strip_all_tags( wc_price( $rate->get_cost() ) ) : __( 'free', 'probo-connect' );
	}

	return $parts;
}

/**
 * The collapsed state of one step: badge, title, summary line and "Wijzig".
 *
 * Always printed, always in the markup — the script only decides which of the
 * two states of a step is visible, so a checkout without JavaScript shows every
 * step open and never a stray summary line.
 *
 * @param int $step Step number.
 */
function probo_checkout_step_summary_row( $step ) {
	$steps = probo_checkout_steps();
	?>
	<div class="pp-step-summary">
		<span class="pp-step-badge" aria-hidden="true"><?php echo esc_html( (string) $step ); ?></span>
		<div class="pp-step-summary-body">
			<div class="pp-step-summary-title"><?php echo esc_html( $steps[ $step ]['title'] ); ?></div>
			<div class="pp-step-summary-text pp-summary-<?php echo esc_attr( (string) $step ); ?>"><?php echo esc_html( probo_checkout_step_summary( $step ) ); ?></div>
		</div>
		<button type="button" class="pp-step-edit" data-pp-step-edit="<?php echo esc_attr( (string) $step ); ?>">
			<?php esc_html_e( 'Change', 'probo-connect' ); ?>
		</button>
	</div>
	<?php
}

/**
 * The amount that rides along on the order button's label.
 *
 * Plain text, not an element: WooCommerce's own checkout script resets
 * #place_order with .text( data-value ) every time the payment method list is
 * initialised, which would eat any markup nested inside the button. So the
 * amount lives in the label itself and in data-value, and
 * assets/js/checkout-steps.js keeps both in step with the total — working with
 * that reset instead of against it.
 *
 * @return string
 */
function probo_checkout_order_total_suffix() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! probo_checkout_is_stepped() ) {
		return '';
	}

	return ' · ' . wp_strip_all_tags( WC()->cart->get_total() );
}

/**
 * The place-order block, printed inside step 3.
 *
 * This is the second half of woocommerce/checkout/payment.php, which the theme
 * overrides to render only the gateway list. Every hook core fires around the
 * button is fired here in the same order, so terms checkboxes, trust badges and
 * gateway scripts that hook them keep working.
 *
 * The design moves it out of the summary column and into step 3: with the
 * button in the last step there is no way to order on a half-made delivery
 * choice. It has to stay inside <form name="checkout">, which it does — step 3
 * is part of that form.
 */
function probo_checkout_place_order() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'probo-connect' ) );
	?>
	<div class="form-row place-order pp-place-order">
		<noscript>
			<?php
			printf(
				/* translators: 1: opening emphasis tag, 2: closing emphasis tag. */
				esc_html__( 'Your browser does not support JavaScript. Please click %1$sUpdate totals%2$s first, otherwise the amount may differ from what is shown above.', 'probo-connect' ),
				'<em>',
				'</em>'
			);
			?>
			<br/>
			<button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'probo-connect' ); ?>">
				<?php esc_html_e( 'Update totals', 'probo-connect' ); ?>
			</button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php
		/**
		 * Hook: woocommerce_review_order_before_submit.
		 */
		do_action( 'woocommerce_review_order_before_submit' );

		// The amount is part of the button label: what someone confirms should be
		// written on the thing they press. data-pp-label keeps the bare text
		// around so the script can rebuild the label after an amount change.
		$probo_label = $order_button_text . probo_checkout_order_total_suffix();

		echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled and escaped here, then filtered as core does.
			'woocommerce_order_button_html',
			'<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $probo_label ) . '" data-value="' . esc_attr( $probo_label ) . '" data-pp-label="' . esc_attr( $order_button_text ) . '">' . esc_html( $probo_label ) . '</button>'
		);

		/**
		 * Hook: woocommerce_review_order_after_submit.
		 */
		do_action( 'woocommerce_review_order_after_submit' );

		wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
		?>
	</div>
	<?php
}

/**
 * Keep the carrier cards and the step summaries fresh when the checkout updates
 * over AJAX.
 *
 * Both are small, targeted fragments. The summaries change on every address
 * edit and every delivery choice, and they live outside #order_review — which
 * is the only part WooCommerce refreshes by itself — so without these selectors
 * a collapsed step would keep showing whatever was true when the page loaded.
 * The amount on the order button is not a fragment; see
 * probo_checkout_order_total_suffix() for why.
 *
 * @param array $fragments Existing fragments, keyed by selector.
 * @return array
 */
function probo_shipping_fragment( $fragments ) {
	$fragments['.pp-checkout-shipping'] = probo_checkout_shipping_html();

	if ( ! probo_checkout_is_stepped() ) {
		return $fragments;
	}

	foreach ( array_keys( probo_checkout_steps() ) as $step ) {
		$fragments[ '.pp-summary-' . $step ] = sprintf(
			'<div class="pp-step-summary-text pp-summary-%1$d">%2$s</div>',
			$step,
			esc_html( probo_checkout_step_summary( $step ) )
		);
	}

	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'probo_shipping_fragment' );

/**
 * Hand Probo Connect's own section templates back when the checkout is classic.
 *
 * The theme overrides two of the plugin's templates —
 * probo-connect/checkout/sections/shipping-blocks-shipping-{dates,methods}.php —
 * to turn the day strip into one decision and to fold the carriers away. That
 * only makes sense inside the stepped checkout, so with the classic one selected
 * the plugin's defaults are pointed at again rather than the theme's copies.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name, relative to its path.
 * @param array  $args          Template args.
 * @param string $template_path Plugin template path ('probo-connect' here).
 * @param string $default_path  The plugin's own templates directory.
 * @return string
 */
function probo_restore_connect_templates( $template, $template_name, $args, $template_path, $default_path ) {
	if ( 'probo-connect' !== $template_path || probo_checkout_is_stepped() ) {
		return $template;
	}

	$overridden = array(
		'checkout/sections/shipping-blocks-shipping-dates.php',
		'checkout/sections/shipping-blocks-shipping-methods.php',
	);

	if ( ! in_array( $template_name, $overridden, true ) || ! $default_path ) {
		return $template;
	}

	$default = trailingslashit( $default_path ) . $template_name;

	return file_exists( $default ) ? $default : $template;
}
add_filter( 'wc_get_template', 'probo_restore_connect_templates', 10, 5 );
