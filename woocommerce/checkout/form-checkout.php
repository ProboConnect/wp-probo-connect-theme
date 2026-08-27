<?php
/**
 * Checkout — one page, or three steps.
 *
 * Overrides woocommerce/templates/checkout/form-checkout.php.
 *
 * Which of the two it draws is a Customizer choice (Thema-instellingen →
 * Componenten → Checkout-stijl), because not every shop wants the same
 * checkout:
 *
 *  - **Eén pagina** (the default) is the classic layout: five numbered sections
 *    under each other — Contactgegevens, Bezorgadres, Levermoment, Vervoerder,
 *    Betaalmethode — with the order button under "Te betalen" in the summary.
 *  - **Stappen** is the accordion: 1 Gegevens & adres, 2 Bezorging, 3 Betalen,
 *    one open and the rest collapsed to a summary line with "Wijzig". A
 *    logged-in repeat customer arrives with step 1 already done, so the checkout
 *    starts at the only real question — when it has to be there. The order
 *    button moves to step 3, where it cannot be pressed on a half-made delivery
 *    choice, and the page loses its search, navigation and footer columns.
 *
 * The collapsing itself is progressive enhancement: this template renders every
 * step open, and assets/js/checkout-steps.js closes the ones the customer is not
 * in. Everything a collapsed step shows — the summary lines, the amount on the
 * order button — is built in PHP (inc/woocommerce.php), so it is right again
 * after a server-side validation round.
 *
 * Two placements hold in both layouts:
 *  - Carriers are rendered in the main column rather than inside the order-review
 *    table, and kept in sync by the .pp-checkout-shipping fragment registered in
 *    inc/woocommerce.php.
 *  - Payment stays in WooCommerce's own #payment container (replaced wholesale
 *    by the .woocommerce-checkout-payment fragment), so gateway scripts keep
 *    working untouched. Wherever the order button ends up, it is inside
 *    <form name="checkout">.
 *
 * @package Probo_Connect
 * @version 9.4.0
 *
 * @var WC_Checkout $checkout Checkout object.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: woocommerce_before_checkout_form.
 */
do_action( 'woocommerce_before_checkout_form', $checkout );

// Checkout is blocked for logged-out customers when registration is disabled.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo '<p class="pp-container py-10 text-[17px] text-ink-2">' . esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to check out.', 'probo-connect-theme' ) ) ) . '</p>';
	return;
}

$probo_stepped = probo_checkout_is_stepped();
$probo_steps   = probo_checkout_steps();

/**
 * Opens one step: the container, the badge, the title and the intro line.
 *
 * @param int $number Step number.
 */
$probo_step_open = static function ( $number ) use ( $probo_steps ) {
	?>
	<section class="pp-step" data-pp-step="<?php echo esc_attr( (string) $number ); ?>">
		<?php probo_checkout_step_summary_row( $number ); ?>

		<div class="pp-step-body">
			<div class="pp-step-head">
				<span class="pp-step-badge" aria-hidden="true"><?php echo esc_html( (string) $number ); ?></span>
				<h2 class="pp-step-title"><?php echo esc_html( $probo_steps[ $number ]['title'] ); ?></h2>
			</div>

			<?php if ( $probo_steps[ $number ]['intro'] ) : ?>
				<p class="pp-step-intro"><?php echo esc_html( $probo_steps[ $number ]['intro'] ); ?></p>
			<?php endif; ?>
	<?php
};

/**
 * Closes a step, with the primary action that advances to the next one.
 *
 * The "zelf een vervoerder kiezen" escape the design puts beside this button is
 * the <summary> of the carrier fold just above it: a <summary> has to be the
 * first child of its own <details>, and a fold that opens without JavaScript is
 * worth more than the last 40px of alignment.
 *
 * @param int $number Step number.
 */
$probo_step_close = static function ( $number ) use ( $probo_steps ) {
	if ( $probo_steps[ $number ]['next'] ) :
		?>
			<div class="pp-step-foot">
				<button type="button" class="pp-btn-secondary pp-step-next" data-pp-step-next="<?php echo esc_attr( (string) $number ); ?>">
					<?php echo esc_html( $probo_steps[ $number ]['next'] ); ?>
				</button>
			</div>
		<?php
	endif;
	?>
		</div>
	</section>
	<?php
};

/**
 * Renders a numbered section heading — the classic layout's own chrome.
 *
 * @param int    $number Section number.
 * @param string $title  Section title.
 */
$probo_heading = static function ( $number, $title ) {
	?>
	<div class="mb-4 flex items-center gap-3">
		<span class="font-mono flex h-6 w-6 items-center justify-center rounded-full bg-secondary text-xs font-medium text-secondary-fg"><?php echo esc_html( (string) $number ); ?></span>
		<h2 class="text-xl font-extrabold tracking-[-0.02em]"><?php echo esc_html( $title ); ?></h2>
	</div>
	<?php
};

// The delivery block is Probo Connect's own selector: delivery dates first, then
// the carriers that can make that date. probo_move_shipping_selector() in
// inc/woocommerce.php moves the plugin's renderer onto this hook, so it lands
// where the template puts it rather than between the address fields.
ob_start();
do_action( 'probo_checkout_shipping_selector' );
$probo_delivery = trim( (string) ob_get_clean() );

// Handing the rendered markup to probo_checkout_delivery_state() is what makes
// this the same ownership decision inc/woocommerce.php's step-2 functions
// make — see that docblock. This is the one place that has the markup itself
// to test.
$probo_delivery_state = probo_checkout_delivery_state( $probo_delivery );

// WooCommerce's own shipping rates. With Probo Connect active these are driven
// by the selector above and show a placeholder until a date is picked, so they
// are only rendered as the fallback for a shop without the plugin.
$probo_shipping = 'woo' === $probo_delivery_state->source ? probo_checkout_shipping_html() : '';

/**
 * The delivery choice, wherever the layout puts it.
 */
$probo_render_delivery = static function () use ( $probo_delivery, $probo_stepped ) {
	// .pp-delivery means "this block has the Bezorgen/Ophalen tabs above it",
	// and it is what every rearranging rule in print-connect.css hangs off. The
	// classic layout keeps the plugin's own grouped list, headings and all, so
	// it only gets the plain wrapper.
	echo $probo_stepped
		? '<div class="pp-checkout-delivery pp-delivery">'
		: '<div class="pp-checkout-delivery">';

	// Inside the same .pp-delivery as the plugin's block, so the stylesheet can
	// switch the panes it renders with :has(:checked). The pickup count is
	// filled in by the script once the plugin has loaded the pickup points for
	// the chosen day.
	if ( $probo_stepped ) {
		probo_checkout_mode_tabs( 'connect' );
	}

	echo $probo_delivery . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin output, escaped at its source.
};
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout pp-container grid items-start gap-11 py-9 pb-18 lg:grid-cols-[1fr_400px]" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<?php if ( $probo_stepped ) : ?>

		<div class="pp-steps" data-pp-steps data-pp-initial-step="<?php echo esc_attr( (string) probo_checkout_initial_step() ); ?>">
			<?php
			/**
			 * Hook: woocommerce_checkout_before_customer_details.
			 */
			do_action( 'woocommerce_checkout_before_customer_details' );

			$probo_step_open( 1 );
			?>
				<div id="customer_details">
					<?php
					/**
					 * Hook: woocommerce_checkout_billing.
					 */
					do_action( 'woocommerce_checkout_billing' );

					/**
					 * Hook: woocommerce_checkout_shipping.
					 */
					do_action( 'woocommerce_checkout_shipping' );
					?>
				</div>
			<?php
			$probo_step_close( 1 );

			/**
			 * Hook: woocommerce_checkout_after_customer_details.
			 */
			do_action( 'woocommerce_checkout_after_customer_details' );

			$probo_step_open( 2 );

			if ( $probo_delivery ) {
				$probo_render_delivery();
			} elseif ( $probo_shipping ) {
				echo $probo_shipping; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in probo_checkout_shipping_html().
			} else {
				echo '<p class="text-[15px] text-ink-3">' . esc_html__( 'This order will not be shipped.', 'probo-connect-theme' ) . '</p>';
			}

			$probo_step_close( 2 );

			$probo_step_open( 3 );

			/**
			 * Hook: woocommerce_checkout_order_review is intentionally not called
			 * here — the review table lives in the summary column below. This
			 * renders WooCommerce's payment container, which carries the gateway
			 * fields.
			 */
			woocommerce_checkout_payment();

			// Terms, the order button and the nonce, in step 3 rather than under
			// the summary total.
			probo_checkout_place_order();

			$probo_step_close( 3 );

			probo_checkout_reassurance();
			?>
		</div>

	<?php else : ?>

		<div>
			<h1 class="mb-5.5 text-3xl font-extrabold tracking-[-0.035em] lg:text-[40px]"><?php esc_html_e( 'Checkout', 'probo-connect-theme' ); ?></h1>

			<?php probo_checkout_step_bar(); ?>

			<?php $probo_section = 0; ?>

			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php
				/**
				 * Hook: woocommerce_checkout_before_customer_details.
				 */
				do_action( 'woocommerce_checkout_before_customer_details' );
				?>

				<div id="customer_details">
					<section class="mb-8.5">
						<?php $probo_heading( ++$probo_section, __( 'Contact details', 'probo-connect-theme' ) ); ?>
						<?php
						/**
						 * Hook: woocommerce_checkout_billing.
						 */
						do_action( 'woocommerce_checkout_billing' );
						?>
					</section>

					<section class="mb-8.5">
						<?php $probo_heading( ++$probo_section, __( 'Delivery address', 'probo-connect-theme' ) ); ?>
						<?php
						/**
						 * Hook: woocommerce_checkout_shipping.
						 */
						do_action( 'woocommerce_checkout_shipping' );
						?>
					</section>
				</div>

				<?php
				/**
				 * Hook: woocommerce_checkout_after_customer_details.
				 */
				do_action( 'woocommerce_checkout_after_customer_details' );
				?>
			<?php endif; ?>

			<?php if ( $probo_delivery ) : ?>
				<section class="mb-8.5">
					<?php $probo_heading( ++$probo_section, __( 'Delivery', 'probo-connect-theme' ) ); ?>
					<p class="mb-3.5 max-w-[560px] text-sm text-ink-3">
						<?php esc_html_e( 'First choose a delivery day; the carriers that deliver then will appear.', 'probo-connect-theme' ); ?>
					</p>
					<?php $probo_render_delivery(); ?>
				</section>
			<?php endif; ?>

			<?php if ( $probo_shipping ) : ?>
				<section class="mb-8.5">
					<?php $probo_heading( ++$probo_section, __( 'Carrier', 'probo-connect-theme' ) ); ?>
					<p class="mb-3.5 max-w-[560px] text-sm text-ink-3">
						<?php esc_html_e( 'Large or long shipments always go by freight carrier.', 'probo-connect-theme' ); ?>
					</p>
					<?php echo $probo_shipping; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in probo_checkout_shipping_html(). ?>
				</section>
			<?php endif; ?>

			<section>
				<?php $probo_heading( ++$probo_section, __( 'Payment method', 'probo-connect-theme' ) ); ?>
				<?php
				/**
				 * Hook: woocommerce_checkout_order_review is intentionally not
				 * called here either; only the payment container is rendered.
				 */
				woocommerce_checkout_payment();
				?>
			</section>
		</div>

	<?php endif; ?>

	<div class="pp-checkout-summary lg:sticky lg:top-6">
		<?php
		/**
		 * Hook: woocommerce_checkout_before_order_review_heading.
		 */
		do_action( 'woocommerce_checkout_before_order_review_heading' );
		?>

		<div class="rounded-pp border-2 border-secondary-line p-6.5">
			<h2 id="order_review_heading" class="mb-4.5 text-xl font-extrabold tracking-[-0.02em]"><?php esc_html_e( 'Your order', 'probo-connect-theme' ); ?></h2>

			<?php
			/**
			 * Hook: woocommerce_checkout_before_order_review.
			 */
			do_action( 'woocommerce_checkout_before_order_review' );
			?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php
				// Fire the stock action so third-party additions still run, minus
				// the payment container, which the main column already rendered.
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

				/**
				 * Hook: woocommerce_checkout_order_review.
				 */
				do_action( 'woocommerce_checkout_order_review' );

				add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
				?>
			</div>

			<?php
			// In the classic layout the submit button sits right under "Te
			// betalen": the amount someone confirms should be in view when they
			// confirm it. The stepped layout has already printed it in step 3.
			if ( ! $probo_stepped ) {
				probo_checkout_place_order();
			}

			/**
			 * Hook: woocommerce_checkout_after_order_review.
			 */
			do_action( 'woocommerce_checkout_after_order_review' );
			?>
		</div>
	</div>
</form>

<?php
/**
 * Hook: woocommerce_after_checkout_form.
 */
do_action( 'woocommerce_after_checkout_form', $checkout );
