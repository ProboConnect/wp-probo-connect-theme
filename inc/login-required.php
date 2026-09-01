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
 *   'Hele site'    a closed order portal: every page, feed, sitemap and REST
 *                  call sends a logged-out visitor to the login form. Only the
 *                  account page itself stays open, because that is where the
 *                  login form and the password reset live.
 *
 * The first two leave browsing open on purpose — who may see which product is
 * inc/product-access.php's job, and that is a different decision from who may
 * buy. 'Hele site' is the one that answers "nobody sees anything without an
 * account", and it answers it for the whole site rather than per product.
 *
 * Every wall is checked again server-side. The redirect is a courtesy — the
 * refusal that counts sits on woocommerce_checkout_process, on the add-to-cart
 * validation and on rest_authentication_errors, where a hand-made request
 * lands too.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * How high the wall stands.
 *
 * @return string One of 'off', 'checkout', 'cart' or 'site'.
 */
function probo_login_required_scope() {
	$scopes = array(
		'Uit'         => 'off',
		'Kassa'       => 'checkout',
		'Winkelwagen' => 'cart',
		'Hele site'   => 'site',
	);

	$scope = $scopes[ (string) probo_get( 'require_login' ) ] ?? 'off';

	/**
	 * Filters how high the login wall stands, whatever the Customizer says.
	 *
	 * @param string $scope One of 'off', 'checkout', 'cart' or 'site'.
	 */
	$scope = (string) apply_filters( 'probo_login_required_scope', $scope );

	return in_array( $scope, array( 'off', 'checkout', 'cart', 'site' ), true ) ? $scope : 'off';
}

/**
 * Whether this visitor has to log in before a given step.
 *
 * Every setting above 'off' walls the checkout; the cart is walled from
 * 'Winkelwagen' up.
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

	return 'cart' === $stage ? in_array( $scope, array( 'cart', 'site' ), true ) : true;
}

/**
 * Whether the whole site is closed to this visitor.
 *
 * @return bool
 */
function probo_login_required_site_closed() {
	return ! is_user_logged_in() && 'site' === probo_login_required_scope();
}

/**
 * The login page, carrying where to come back to.
 *
 * @param string $redirect_to Absolute URL to return to after logging in.
 * @return string
 */
function probo_login_required_url( $redirect_to = '' ) {
	// The empty fallback matters: left to itself wc_get_page_permalink() hands
	// back the home page when there is no account page, and the site guard would
	// then bounce every request to a page that bounces it straight back.
	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount', '' ) : '';

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
	if ( 'site' === $stage ) {
		$message = __( 'This portal is for account holders. Log in to continue.', 'probo-connect-theme' );
	} elseif ( 'cart' === $stage ) {
		$message = __( 'Log in with your account to order this product.', 'probo-connect-theme' );
	} else {
		$message = __( 'Log in with your account to complete this order. Your cart comes with you.', 'probo-connect-theme' );
	}

	/**
	 * Filters the message a visitor gets when the login wall stops them.
	 *
	 * @param string $message Message text.
	 * @param string $stage   One of 'cart', 'checkout' or 'site'.
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
 * @param string      $message     What to tell the customer.
 * @param string|null $redirect_to Absolute URL to return to after logging in,
 *                                 or null for the message alone — above the
 *                                 login form a button pointing at it would be
 *                                 pointing at itself.
 */
function probo_login_required_prompt( $message, $redirect_to = null ) {
	?>
	<div class="rounded-pp bg-surface mb-6 flex flex-wrap items-center justify-between gap-4 px-6 py-5.5">
		<p class="text-[15px] text-ink-3"><?php echo esc_html( $message ); ?></p>
		<?php if ( null !== $redirect_to ) : ?>
			<a class="pp-btn-accent shrink-0" href="<?php echo esc_url( probo_login_required_url( $redirect_to ) ); ?>">
				<?php esc_html_e( 'Log in', 'probo-connect-theme' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Say why they are looking at a login form, above the login form.
 *
 * Printed from the redirect's own `probo_redirect` argument rather than added
 * as a WooCommerce notice: a notice has to be parked in a session, and a closed
 * portal turns away every crawler that ever finds it — one session row and one
 * cookie each, for a message nobody reads.
 */
function probo_login_required_login_prompt() {
	if ( is_user_logged_in() || ! probo_login_redirect_target() ) {
		return;
	}

	$scope = probo_login_required_scope();

	if ( 'off' === $scope ) {
		return;
	}

	probo_login_required_prompt( probo_login_required_message( 'site' === $scope ? 'site' : 'checkout' ) );
}
add_action( 'woocommerce_before_customer_login_form', 'probo_login_required_login_prompt' );

/* ---------------------------------------------------------------------------
   The closed portal.

   'Hele site' is not a stronger version of the order wall — it is a different
   shape: nothing is public, so the question stops being "may this visitor buy"
   and becomes "is this request the login itself". Everything that is not gets
   turned away, front end, feeds, sitemaps and REST alike.
--------------------------------------------------------------------------- */

/**
 * The URL this request asked for, if it is one of ours.
 *
 * Built from the raw request rather than from the queried object, so a search,
 * a paged archive or a filtered listing all come back intact after the login.
 * wp_validate_redirect() drops anything whose host is not this site's, so a
 * spoofed Host header cannot turn the login link into an off-site one.
 *
 * @return string Absolute URL, or '' when there is nothing safe to use.
 */
function probo_login_required_current_url() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	if ( ! $host || ! $path ) {
		return '';
	}

	return (string) wp_validate_redirect( set_url_scheme( 'http://' . $host . $path ), '' );
}

/**
 * The requests a closed portal still answers to a logged-out visitor.
 *
 * Only the account page: it carries the login form, the registration form and
 * the password reset, so closing it would close the portal to its own members.
 * Everything else — the shop, the front page, a search, a feed — is behind the
 * login.
 *
 * @return bool
 */
function probo_login_required_public_request() {
	// robots.txt is served through template_redirect too. Turning a crawler away
	// from it says nothing useful and loses the one file that tells it to stay
	// out; it carries no content of its own to protect.
	$public = is_robots() || ( function_exists( 'is_account_page' ) && is_account_page() );

	// Without WooCommerce's account page there is only wp-login.php, which never
	// reaches template_redirect, so nothing on the front end has to stay open.

	/**
	 * Filters which requests a closed portal still answers when logged out.
	 *
	 * Where a portal needs a public page of its own — a contact page, a privacy
	 * statement, a page explaining how to get an account — this is where it is
	 * opened up.
	 *
	 * @param bool $public Whether this request stays open.
	 */
	return (bool) apply_filters( 'probo_login_required_public_request', $public );
}

/**
 * Send a logged-out visitor to the login form, wherever they came in.
 *
 * Priority 1: ahead of the theme's own checkout guard and of everything that
 * renders on template_redirect — the sitemap and the feeds among them — so a
 * closed portal does not publish its catalogue on the way out.
 */
function probo_login_required_guard_site() {
	if ( ! probo_login_required_site_closed() || probo_login_required_public_request() ) {
		return;
	}

	$login = probo_login_required_url( probo_login_required_current_url() );

	wp_safe_redirect( $login ? $login : wp_login_url() );
	exit;
}
add_action( 'template_redirect', 'probo_login_required_guard_site', 1 );

/**
 * Close the REST API to anonymous callers too.
 *
 * The front end is only one door. Left open, wp-json hands out posts, products
 * and users to anyone who asks, which in a closed portal is the whole point
 * being missed.
 *
 * @param WP_Error|null|true $result Whatever authentication has decided so far.
 * @return WP_Error|null|true
 */
function probo_login_required_rest( $result ) {
	// Someone has already allowed or refused this caller; not ours to overrule.
	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( ! probo_login_required_site_closed() ) {
		return $result;
	}

	return new WP_Error(
		'probo_login_required',
		probo_login_required_message( 'site' ),
		array( 'status' => rest_authorization_required_code() )
	);
}
add_filter( 'rest_authentication_errors', 'probo_login_required_rest' );

/**
 * A closed portal is not for search engines.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function probo_login_required_robots( $robots ) {
	if ( 'site' === probo_login_required_scope() ) {
		return wp_robots_no_robots( $robots );
	}

	return $robots;
}
add_filter( 'wp_robots', 'probo_login_required_robots' );

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

	// No notice to park in a session: the login form says why itself, from the
	// argument this redirect carries.
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
