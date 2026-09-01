<?php
/**
 * Login before ordering.
 *
 * Off by default: the shop sells to whoever walks in. Switched on under
 * Customizer → Theme settings → Components, it puts a wall in front of the
 * order, at one of two heights:
 *
 *   'Kassa'        a visitor browses and fills a cart as before, but has to log
 *                  in to order it. The cart survives the login, so nobody loses
 *                  what they configured.
 *   'Winkelwagen'  nothing goes into a cart without an account — the wall a
 *                  trade shop with account-only pricing wants.
 *
 * Browsing is never walled either way: that is what inc/product-access.php is
 * for, and hiding the whole catalogue behind a login is a different decision
 * from requiring one to buy.
 *
 * Every wall is checked again server-side. The redirect is a courtesy — the
 * refusal that counts sits on woocommerce_checkout_process and on the
 * add-to-cart validation, where a hand-made POST lands too.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * How high the wall stands.
 *
 * @return string One of 'off', 'checkout' or 'cart'.
 */
function probo_login_required_scope() {
	$scopes = array(
		'Uit'         => 'off',
		'Kassa'       => 'checkout',
		'Winkelwagen' => 'cart',
	);

	$scope = $scopes[ (string) probo_get( 'require_login' ) ] ?? 'off';

	/**
	 * Filters how high the login wall stands, whatever the Customizer says.
	 *
	 * @param string $scope One of 'off', 'checkout' or 'cart'.
	 */
	$scope = (string) apply_filters( 'probo_login_required_scope', $scope );

	return in_array( $scope, array( 'off', 'checkout', 'cart' ), true ) ? $scope : 'off';
}

/**
 * Whether this visitor has to log in before a given step.
 *
 * The checkout is walled by both settings; the cart only by the higher one.
 *
 * @param string $stage Either 'cart' (adding to the cart) or 'checkout'.
 * @return bool
 */
function probo_login_required_for( $stage = 'checkout' ) {
	if ( is_user_logged_in() ) {
		return false;
	}

	$scope = probo_login_required_scope();

	if ( 'off' === $scope ) {
		return false;
	}

	return 'cart' === $stage ? 'cart' === $scope : true;
}

/**
 * The login page, carrying where to come back to.
 *
 * @param string $redirect_to Absolute URL to return to after logging in.
 * @return string
 */
function probo_login_required_url( $redirect_to = '' ) {
	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';

	// Without a My account page there is only wp-login, which carries its own
	// redirect argument and never sees the filters below.
	if ( ! $account ) {
		return $redirect_to ? wp_login_url( $redirect_to ) : wp_login_url();
	}

	// WooCommerce's login form posts to the URL it was served from, query string
	// and all, so the argument is still there when the login is processed.
	return $redirect_to ? add_query_arg( 'probo_redirect', $redirect_to, $account ) : $account;
}

/**
 * Where the customer asked to be sent back to, if it is ours to send them to.
 *
 * @return string Absolute URL, or '' when there is nothing safe to use.
 */
function probo_login_redirect_target() {
	if ( empty( $_GET['probo_redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a navigation hint, validated against this host below.
		return '';
	}

	$url = esc_url_raw( wp_unslash( $_GET['probo_redirect'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.

	// Anything pointing off this host is dropped rather than followed.
	return (string) wp_validate_redirect( $url, '' );
}

/**
 * Send the customer back where the wall stopped them.
 *
 * @param string $redirect Where WooCommerce was going to send them.
 * @return string
 */
function probo_login_redirect( $redirect ) {
	$target = probo_login_redirect_target();

	return $target ? $target : $redirect;
}
add_filter( 'woocommerce_login_redirect', 'probo_login_redirect' );
add_filter( 'woocommerce_registration_redirect', 'probo_login_redirect' );

/**
 * What the customer is told.
 *
 * @param string $stage Either 'cart' or 'checkout'.
 * @return string
 */
function probo_login_required_message( $stage = 'checkout' ) {
	$message = 'cart' === $stage
		? __( 'Log in with your account to order this product.', 'probo-connect-theme' )
		: __( 'Log in with your account to complete this order. Your cart comes with you.', 'probo-connect-theme' );

	/**
	 * Filters the message a visitor gets when the login wall stops them.
	 *
	 * @param string $message Message text.
	 * @param string $stage   Either 'cart' or 'checkout'.
	 */
	return (string) apply_filters( 'probo_login_required_message', $message, $stage );
}

/**
 * Add a WooCommerce notice, if there is a session to hold it.
 *
 * @param string $message Message text.
 * @param string $type    Notice type.
 */
function probo_login_required_notice( $message, $type = 'error' ) {
	if ( function_exists( 'wc_add_notice' ) && WC()->session ) {
		wc_add_notice( $message, $type );
	}
}

/**
 * The in-page prompt: what is being asked, and the button that does it.
 *
 * Printed as ordinary markup rather than as a WooCommerce notice — it is a
 * standing condition of the page, not something that just went wrong, and a
 * notice would read as an error every time the page is drawn.
 *
 * @param string $message     What to tell the customer.
 * @param string $redirect_to Absolute URL to return to after logging in.
 */
function probo_login_required_prompt( $message, $redirect_to = '' ) {
	?>
	<div class="rounded-pp bg-surface mb-6 flex flex-wrap items-center justify-between gap-4 px-6 py-5.5">
		<p class="text-[15px] text-ink-3"><?php echo esc_html( $message ); ?></p>
		<a class="pp-btn-accent shrink-0" href="<?php echo esc_url( probo_login_required_url( $redirect_to ) ); ?>">
			<?php esc_html_e( 'Log in', 'probo-connect-theme' ); ?>
		</a>
	</div>
	<?php
}

/**
 * Send a logged-out visitor away from the checkout, to the login form.
 *
 * The order-received page and the pay-for-order page are deliberately left
 * open: both belong to an order that already exists, and a customer following
 * a payment link is not placing a new one.
 */
function probo_login_required_guard_checkout() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	if ( is_order_received_page() || is_checkout_pay_page() ) {
		return;
	}

	if ( ! probo_login_required_for( 'checkout' ) ) {
		return;
	}

	// A shop that points My account and Checkout at the same page would be sent
	// round in circles; the refusal below still stands, so nothing is lost.
	if ( wc_get_page_id( 'myaccount' ) === wc_get_page_id( 'checkout' ) ) {
		return;
	}

	probo_login_required_notice( probo_login_required_message( 'checkout' ), 'notice' );
	wp_safe_redirect( probo_login_required_url( wc_get_checkout_url() ) );
	exit;
}
add_action( 'template_redirect', 'probo_login_required_guard_checkout', 5 );

/**
 * Refuse the order itself.
 *
 * The redirect above is what a customer sees; this is what a POST straight at
 * the checkout meets. An error notice here is how WooCommerce is told to stop.
 */
function probo_login_required_checkout_process() {
	if ( probo_login_required_for( 'checkout' ) ) {
		wc_add_notice( probo_login_required_message( 'checkout' ), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'probo_login_required_checkout_process' );

/**
 * Refuse add-to-cart when the wall stands at the cart.
 *
 * @param bool $passed Whether validation passed so far.
 * @return bool
 */
function probo_login_required_add_to_cart_validation( $passed ) {
	if ( ! probo_login_required_for( 'cart' ) ) {
		return $passed;
	}

	probo_login_required_notice( probo_login_required_message( 'cart' ) );

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'probo_login_required_add_to_cart_validation' );

/**
 * Tell them at the cart, not first at the checkout.
 *
 * A cart that cannot be ordered is worth saying out loud while it is being
 * filled, rather than after the "Proceed to checkout" button turns out to lead
 * to a login form.
 */
function probo_login_required_cart_prompt() {
	if ( probo_login_required_for( 'checkout' ) ) {
		probo_login_required_prompt( probo_login_required_message( 'checkout' ), wc_get_cart_url() );
	}
}
add_action( 'woocommerce_before_cart', 'probo_login_required_cart_prompt', 5 );

/**
 * And on the product page, where the button they are about to press lives.
 *
 * Only when the wall stands at the cart: with the checkout wall the button
 * still works, and the cart prompt says the rest.
 *
 * Hooked twice because a configurable product's add-to-cart sits in the
 * configurator band and a plain one's in the summary column; the static keeps
 * the second hook from printing a second copy on the same page.
 */
function probo_login_required_product_prompt() {
	static $printed = false;

	if ( $printed || ! probo_login_required_for( 'cart' ) ) {
		return;
	}

	$printed = true;

	probo_login_required_prompt( probo_login_required_message( 'cart' ), (string) get_permalink() );
}
add_action( 'probo_configurator_band', 'probo_login_required_product_prompt', 5 );
add_action( 'woocommerce_before_add_to_cart_form', 'probo_login_required_product_prompt' );
