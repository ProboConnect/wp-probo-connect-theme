<?php
/**
 * Products per customer.
 *
 * A product is on offer to everyone until someone switches "Limit to selected
 * customers" on. From that moment it works the other way round: only the
 * customers named on the product can see it, open it and order it — for
 * everyone else it is a product that simply does not exist. It drops out of
 * the shop, out of search, out of related products and out of the sitemap, its
 * own URL stops resolving, and it cannot be added to a cart even by someone who
 * kept the link.
 *
 * The list lives on the product itself, as one `_probo_access_user` meta row per
 * customer. One row each rather than one array is what keeps the reverse lookup
 * — "which products may this customer see?" — an ordinary indexed meta query,
 * and that is what lets the customer's own profile screen edit the same list
 * from the other side without inventing a second source of truth.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

// Meta key: 'yes' when the product is limited to selected customers.
define( 'PROBO_ACCESS_RESTRICTED_META', '_probo_access_restricted' );

// Meta key: one row per customer id that may access the product.
define( 'PROBO_ACCESS_USER_META', '_probo_access_user' );

// Transient holding every restricted product id in the shop.
define( 'PROBO_ACCESS_TRANSIENT', 'probo_restricted_products' );

/* ---------------------------------------------------------------------------
   Reading the rules.

   Everything below answers one question — may this customer have this product —
   and every front-end guard in this file goes through probo_customer_can_access_product().
--------------------------------------------------------------------------- */

/**
 * The product id an access rule hangs off.
 *
 * A variation is never restricted on its own: it inherits whatever its parent
 * product allows, so both a variation object and a raw variation id resolve to
 * the parent here.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @return int Product id, or 0 when it cannot be resolved.
 */
function probo_product_access_id( $product = null ) {
	if ( null === $product && isset( $GLOBALS['product'] ) ) {
		$product = $GLOBALS['product'];
	}

	if ( $product instanceof WC_Product ) {
		return $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	}

	if ( $product instanceof WP_Post ) {
		$product = $product->ID;
	}

	$product_id = absint( $product );

	if ( $product_id && 'product_variation' === get_post_type( $product_id ) ) {
		$product_id = (int) wp_get_post_parent_id( $product_id );
	}

	return $product_id;
}

/**
 * Whether a product is limited to selected customers.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @return bool
 */
function probo_product_is_restricted( $product = null ) {
	$product_id = probo_product_access_id( $product );

	return $product_id && 'yes' === get_post_meta( $product_id, PROBO_ACCESS_RESTRICTED_META, true );
}

/**
 * The customers listed on a product.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @return int[] User ids.
 */
function probo_product_access_users( $product = null ) {
	$product_id = probo_product_access_id( $product );

	if ( ! $product_id ) {
		return array();
	}

	$ids = get_post_meta( $product_id, PROBO_ACCESS_USER_META, false );

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * The capability that sees — and manages — every restricted product.
 *
 * Whoever may edit products has to be able to browse the shop the way it really
 * is, otherwise the products they just restricted disappear from under them.
 *
 * @return string
 */
function probo_product_access_manage_cap() {
	/**
	 * Filters the capability that bypasses product access rules.
	 *
	 * @param string $capability Capability name.
	 */
	return (string) apply_filters( 'probo_product_access_manage_cap', 'edit_products' );
}

/**
 * Whether a customer may see and buy a product.
 *
 * An unrestricted product is available to everyone, logged in or not. A
 * restricted one is available to the customers listed on it and to shop staff.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @param int|null                    $user_id User to test, defaults to the current one.
 * @return bool
 */
function probo_customer_can_access_product( $product = null, $user_id = null ) {
	$product_id = probo_product_access_id( $product );
	$user_id    = null === $user_id ? get_current_user_id() : absint( $user_id );
	$allowed    = true;

	if ( $product_id && probo_product_is_restricted( $product_id ) ) {
		$allowed = false;

		if ( $user_id ) {
			$allowed = in_array( $user_id, probo_product_access_users( $product_id ), true )
				|| user_can( $user_id, probo_product_access_manage_cap() );
		}
	}

	/**
	 * Filters whether a customer may access a product.
	 *
	 * @param bool $allowed    Whether access is granted.
	 * @param int  $product_id Product id, 0 when it could not be resolved.
	 * @param int  $user_id    User id, 0 for a logged-out visitor.
	 */
	return (bool) apply_filters( 'probo_customer_can_access_product', $allowed, $product_id, $user_id );
}

/**
 * The per-request memo behind the two lookups below.
 *
 * Handed out by reference so both of them read and write the same store, and so
 * probo_product_access_flush_cache() can empty it in one call — a rule changed
 * halfway through a request has to be visible to whatever runs after it.
 *
 * @param bool $reset Whether to empty the memo first.
 * @return array Reference to the memo.
 */
function &probo_product_access_memo( $reset = false ) {
	static $memo = array();

	if ( $reset ) {
		$memo = array();
	}

	return $memo;
}

/**
 * Every restricted product in the shop.
 *
 * Restricted products are the exception rather than the rule, so the whole set
 * is cheap to hold and the per-request work becomes a capability check per id
 * instead of a meta query per listing. The query carries `probo_access_bypass`
 * so the front-end filter below leaves it alone — without it this would call
 * itself.
 *
 * @return int[] Product ids.
 */
function probo_restricted_product_ids() {
	$memo = &probo_product_access_memo();

	if ( isset( $memo['restricted'] ) ) {
		return $memo['restricted'];
	}

	$cached = get_transient( PROBO_ACCESS_TRANSIENT );

	if ( is_array( $cached ) ) {
		$memo['restricted'] = $cached;

		return $memo['restricted'];
	}

	$ids = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key'               => PROBO_ACCESS_RESTRICTED_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one query per cache miss over a small set, held in a transient.
			'meta_value'             => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- see above.
			'probo_access_bypass'    => true,
		)
	);

	$memo['restricted'] = array_map( 'absint', (array) $ids );

	set_transient( PROBO_ACCESS_TRANSIENT, $memo['restricted'], DAY_IN_SECONDS );

	return $memo['restricted'];
}

/**
 * The products a given customer may not see.
 *
 * @param int|null $user_id User to test, defaults to the current one.
 * @return int[] Product ids.
 */
function probo_hidden_product_ids( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	$memo    = &probo_product_access_memo();

	if ( isset( $memo['hidden'][ $user_id ] ) ) {
		return $memo['hidden'][ $user_id ];
	}

	$hidden = array();

	foreach ( probo_restricted_product_ids() as $product_id ) {
		if ( ! probo_customer_can_access_product( $product_id, $user_id ) ) {
			$hidden[] = $product_id;
		}
	}

	$memo['hidden'][ $user_id ] = $hidden;

	return $hidden;
}

/**
 * Forget the cached list of restricted products.
 */
function probo_product_access_flush_cache() {
	delete_transient( PROBO_ACCESS_TRANSIENT );
	probo_product_access_memo( true );
}

/**
 * Flush the cache whenever a product changes.
 *
 * The post object is used when the hook hands one over: on `deleted_post` the
 * row is already gone, so looking the type up by id would come back empty and
 * the deleted product would stay in the cached list.
 *
 * @param int          $post_id Post id.
 * @param WP_Post|null $post    Post, when the hook passes one.
 */
function probo_product_access_flush_for_post( $post_id, $post = null ) {
	$post_type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );

	if ( 'product' === $post_type ) {
		probo_product_access_flush_cache();
	}
}
add_action( 'save_post_product', 'probo_product_access_flush_cache' );
add_action( 'deleted_post', 'probo_product_access_flush_for_post', 10, 2 );
add_action( 'trashed_post', 'probo_product_access_flush_for_post' );
add_action( 'untrashed_post', 'probo_product_access_flush_for_post' );

/**
 * Drop a deleted user's grants.
 *
 * WordPress reuses user ids, so a grant left behind on a product would hand the
 * next account to take that id an access it was never given. The products are
 * looked up directly — no meta query finds a value across posts — but the rows
 * are then deleted through the meta API, so the post meta caches follow.
 *
 * @param int $user_id Deleted user id.
 */
function probo_product_access_delete_user_grants( $user_id ) {
	global $wpdb;

	$user_id = absint( $user_id );

	if ( ! $user_id ) {
		return;
	}

	$product_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- finding meta rows by value; no core API covers it.
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
			PROBO_ACCESS_USER_META,
			(string) $user_id
		)
	);

	foreach ( $product_ids as $product_id ) {
		delete_post_meta( (int) $product_id, PROBO_ACCESS_USER_META, $user_id );
	}

	if ( $product_ids ) {
		probo_product_access_flush_cache();
	}
}
add_action( 'deleted_user', 'probo_product_access_delete_user_grants' );

/* ---------------------------------------------------------------------------
   The shop front.

   A restricted product has to be missing, not merely unbuyable: it is taken out
   of every listing, its own page stops resolving, and the cart refuses it.
--------------------------------------------------------------------------- */

/**
 * Take the products this visitor may not see out of every front-end query.
 *
 * Runs late enough that WooCommerce has already set `post_type` on the shop's
 * main query, and hangs off pre_get_posts rather than a WooCommerce hook so it
 * also covers search, blocks, shortcodes and any plugin's own product loop.
 *
 * @param WP_Query $query Query about to run.
 */
function probo_product_access_filter_query( $query ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( $query->get( 'probo_access_bypass' ) ) {
		return;
	}

	// A single product is left to probo_product_access_guard_single(): excluding
	// it here would turn every refusal into a bare 404, and a visitor who is not
	// logged in yet deserves the login form instead.
	if ( $query->is_singular() ) {
		return;
	}

	$types = array_filter( (array) $query->get( 'post_type' ) );

	$targets_products = in_array( 'product', $types, true )
		|| in_array( 'any', $types, true )
		|| ( ! $types && $query->is_search() );

	if ( ! $targets_products ) {
		return;
	}

	$hidden = probo_hidden_product_ids();

	if ( ! $hidden ) {
		return;
	}

	$query->set(
		'post__not_in',
		array_unique( array_merge( array_map( 'absint', (array) $query->get( 'post__not_in' ) ), $hidden ) )
	);
}
add_action( 'pre_get_posts', 'probo_product_access_filter_query', 20 );

/**
 * A restricted product is not visible to a customer who was not given it.
 *
 * Covers the loops that ask the product itself rather than running a query of
 * their own.
 *
 * @param bool $visible    Whether the product is visible.
 * @param int  $product_id Product id.
 * @return bool
 */
function probo_product_access_is_visible( $visible, $product_id ) {
	return $visible && probo_customer_can_access_product( $product_id );
}
add_filter( 'woocommerce_product_is_visible', 'probo_product_access_is_visible', 10, 2 );

/**
 * Keep restricted products out of the related-products strip.
 *
 * @param int[] $related_ids Related product ids.
 * @return int[]
 */
function probo_product_access_related_products( $related_ids ) {
	$hidden = probo_hidden_product_ids();

	return $hidden ? array_values( array_diff( (array) $related_ids, $hidden ) ) : $related_ids;
}
add_filter( 'woocommerce_related_products', 'probo_product_access_related_products' );

/**
 * Keep restricted products out of the XML sitemap.
 *
 * The sitemap is one public document for every visitor, so it cannot be cut per
 * customer: every restricted product stays out of it, listed or not.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type being listed.
 * @return array
 */
function probo_product_access_sitemap_args( $args, $post_type ) {
	if ( 'product' !== $post_type ) {
		return $args;
	}

	$restricted = probo_restricted_product_ids();

	if ( $restricted ) {
		$args['post__not_in'] = array_unique( array_merge( array_map( 'absint', (array) ( $args['post__not_in'] ?? array() ) ), $restricted ) );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'probo_product_access_sitemap_args', 10, 2 );

/**
 * The message a customer gets when a product is not theirs.
 *
 * @return string
 */
function probo_product_access_denied_message() {
	$message = is_user_logged_in()
		? __( 'This product is not available for your account. Contact us if you think it should be.', 'probo-connect-theme' )
		: __( 'This product is only available to logged-in customers. Log in to continue.', 'probo-connect-theme' );

	/**
	 * Filters the message shown when a customer is refused a product.
	 *
	 * @param string $message Message text.
	 */
	return (string) apply_filters( 'probo_product_access_denied_message', $message );
}

/**
 * Show that message, if WooCommerce has somewhere to put it.
 *
 * @param string $type Notice type.
 */
function probo_product_access_notice( $type = 'error' ) {
	if ( function_exists( 'wc_add_notice' ) && WC()->session ) {
		wc_add_notice( probo_product_access_denied_message(), $type );
	}
}

/**
 * Guard the product page itself.
 *
 * A customer who kept a link, or guessed one, gets the same shop everyone else
 * in their position gets: logged out they are sent to the login form with an
 * explanation, logged in the product is a 404 — telling them a product exists
 * that they may not have would leak the catalogue one URL at a time.
 */
function probo_product_access_guard_single() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = get_queried_object_id();

	if ( probo_customer_can_access_product( $product_id ) ) {
		return;
	}

	/**
	 * Filters what happens when a customer opens a product that is not theirs.
	 *
	 * 'login' redirects to the account page with a notice, 'shop' to the shop
	 * page with the same notice, and anything else renders a 404.
	 *
	 * @param string $action     One of 'login', 'shop' or 'not-found'.
	 * @param int    $product_id Product id.
	 */
	$action = (string) apply_filters(
		'probo_product_access_denied_action',
		is_user_logged_in() ? 'not-found' : 'login',
		$product_id
	);

	if ( in_array( $action, array( 'login', 'shop' ), true ) ) {
		$page = 'login' === $action ? 'myaccount' : 'shop';
		$url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( $page ) : '';

		if ( $url ) {
			probo_product_access_notice( 'notice' );
			wp_safe_redirect( $url );
			exit;
		}
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'probo_product_access_guard_single', 5 );

/**
 * A product that is not this customer's cannot be bought.
 *
 * @param bool       $purchasable Whether the product is purchasable.
 * @param WC_Product $product     Product.
 * @return bool
 */
function probo_product_access_is_purchasable( $purchasable, $product ) {
	return $purchasable && probo_customer_can_access_product( $product );
}
add_filter( 'woocommerce_is_purchasable', 'probo_product_access_is_purchasable', 10, 2 );

/**
 * Refuse the add-to-cart request itself.
 *
 * @param bool $passed       Whether validation passed so far.
 * @param int  $product_id   Product id.
 * @param int  $quantity     Quantity.
 * @param int  $variation_id Variation id, when adding a variation.
 * @return bool
 */
function probo_product_access_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = 0 ) {
	if ( probo_customer_can_access_product( $variation_id ? $variation_id : $product_id ) ) {
		return $passed;
	}

	probo_product_access_notice();

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'probo_product_access_add_to_cart_validation', 10, 4 );

/**
 * Drop cart lines the customer is no longer entitled to.
 *
 * A cart outlives the session it was filled in — a customer logs out, access is
 * withdrawn, an order is placed days later — so what was allowed at add-to-cart
 * time is checked again before the cart and the checkout are drawn.
 */
function probo_product_access_check_cart_items() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$removed = false;

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : ( $cart_item['product_id'] ?? 0 );

		if ( probo_customer_can_access_product( $product_id ) ) {
			continue;
		}

		WC()->cart->remove_cart_item( $cart_item_key );
		$removed = true;
	}

	if ( $removed ) {
		probo_product_access_notice();
	}
}
add_action( 'woocommerce_check_cart_items', 'probo_product_access_check_cart_items' );

/* ---------------------------------------------------------------------------
   Managing it from the product.

   The product's own edit screen owns the rule: whether it is restricted at all,
   and which customers are named on it.
--------------------------------------------------------------------------- */

/**
 * Add the "Customer access" tab to the product data box.
 *
 * @param array $tabs Product data tabs.
 * @return array
 */
function probo_product_access_data_tab( $tabs ) {
	$tabs['probo_access'] = array(
		'label'    => __( 'Customer access', 'probo-connect-theme' ),
		'target'   => 'probo_product_access_data',
		'class'    => array(),
		'priority' => 65,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'probo_product_access_data_tab' );

/**
 * Render that tab.
 *
 * The customer picker is WooCommerce's own `wc-customer-search` select, so it
 * searches the customer base over AJAX instead of printing every account into
 * the page, and it looks like every other customer field in the admin.
 */
function probo_product_access_data_panel() {
	global $post;

	$product_id = isset( $post->ID ) ? (int) $post->ID : 0;
	$users      = probo_product_access_users( $product_id );
	?>
	<div id="probo_product_access_data" class="panel woocommerce_options_panel hidden">
		<input type="hidden" name="probo_access_submitted" value="1" />

		<div class="options_group">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'          => PROBO_ACCESS_RESTRICTED_META,
					'value'       => probo_product_is_restricted( $product_id ) ? 'yes' : 'no',
					'label'       => __( 'Limit to selected customers', 'probo-connect-theme' ),
					'description' => __( 'Hide this product from the shop, search and the sitemap, and let only the customers below see and order it.', 'probo-connect-theme' ),
				)
			);
			?>
		</div>

		<div class="options_group">
			<p class="form-field">
				<label for="probo_access_users"><?php esc_html_e( 'Customers', 'probo-connect-theme' ); ?></label>
				<select
					class="wc-customer-search"
					id="probo_access_users"
					name="probo_access_users[]"
					multiple="multiple"
					style="width:50%;"
					data-placeholder="<?php esc_attr_e( 'Search for a customer…', 'probo-connect-theme' ); ?>"
					data-allow_clear="true"
				>
					<?php foreach ( $users as $user_id ) : ?>
						<?php $user = get_userdata( $user_id ); ?>
						<?php if ( $user ) : ?>
							<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected">
								<?php
								printf(
									/* translators: 1: customer name, 2: customer id, 3: e-mail address. */
									esc_html__( '%1$s (#%2$d – %3$s)', 'probo-connect-theme' ),
									esc_html( $user->display_name ),
									(int) $user_id,
									esc_html( $user->user_email )
								);
								?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<span class="description">
					<?php esc_html_e( 'Only these customers see this product and can order it. Shop staff always can. The same list can be edited from a customer’s own profile screen.', 'probo-connect-theme' ); ?>
				</span>
			</p>
		</div>

	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'probo_product_access_data_panel' );

/**
 * Store the rule.
 *
 * The `probo_access_submitted` marker is only on the product edit form, so a
 * Quick Edit, a bulk edit or a programmatic save leaves the access rule alone
 * instead of reading its absent fields as "nobody at all". WooCommerce has
 * already verified its own meta box nonce before this hook fires.
 *
 * @param int $product_id Product being saved.
 */
function probo_product_access_save( $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC_Admin_Meta_Boxes::save_meta_boxes() checks woocommerce_meta_nonce before this hook runs.
	if ( ! isset( $_POST['probo_access_submitted'] ) || ! current_user_can( 'edit_product', $product_id ) ) {
		return;
	}

	$restricted = ! empty( $_POST[ PROBO_ACCESS_RESTRICTED_META ] );
	$users      = isset( $_POST['probo_access_users'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['probo_access_users'] ) ) : array();
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	probo_product_access_set_restricted( $product_id, $restricted );
	probo_product_access_set_users( $product_id, $users );
}
add_action( 'woocommerce_process_product_meta', 'probo_product_access_save' );

/**
 * Switch a product's restriction on or off.
 *
 * @param int  $product_id Product id.
 * @param bool $restricted Whether it is limited to selected customers.
 */
function probo_product_access_set_restricted( $product_id, $restricted ) {
	if ( $restricted ) {
		update_post_meta( $product_id, PROBO_ACCESS_RESTRICTED_META, 'yes' );
	} else {
		delete_post_meta( $product_id, PROBO_ACCESS_RESTRICTED_META );
	}

	probo_product_access_flush_cache();
}

/**
 * Replace the customers listed on a product.
 *
 * Stored one meta row per customer — that is what makes "which products may
 * this customer see?" a single meta query on the other side.
 *
 * @param int   $product_id Product id.
 * @param int[] $user_ids   Customers to list.
 */
function probo_product_access_set_users( $product_id, $user_ids ) {
	$product_id = absint( $product_id );
	$user_ids   = array_values( array_unique( array_filter( array_map( 'absint', (array) $user_ids ) ) ) );
	$current    = probo_product_access_users( $product_id );

	foreach ( array_diff( $current, $user_ids ) as $user_id ) {
		delete_post_meta( $product_id, PROBO_ACCESS_USER_META, $user_id );
	}

	foreach ( array_diff( $user_ids, $current ) as $user_id ) {
		add_post_meta( $product_id, PROBO_ACCESS_USER_META, $user_id );
	}

	probo_product_access_flush_cache();
}

/**
 * Flag restricted products in the products list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function probo_product_access_admin_column( $columns ) {
	$inserted = array();

	foreach ( $columns as $key => $label ) {
		$inserted[ $key ] = $label;

		if ( 'price' === $key ) {
			$inserted['probo_access'] = __( 'Customer access', 'probo-connect-theme' );
		}
	}

	if ( ! isset( $inserted['probo_access'] ) ) {
		$inserted['probo_access'] = __( 'Customer access', 'probo-connect-theme' );
	}

	return $inserted;
}
add_filter( 'manage_edit-product_columns', 'probo_product_access_admin_column', 20 );

/**
 * Draw that column.
 *
 * @param string $column     Column key.
 * @param int    $product_id Product id.
 */
function probo_product_access_admin_column_content( $column, $product_id ) {
	if ( 'probo_access' !== $column ) {
		return;
	}

	if ( ! probo_product_is_restricted( $product_id ) ) {
		echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'Everyone', 'probo-connect-theme' ) . '</span>';

		return;
	}

	$users = count( probo_product_access_users( $product_id ) );

	if ( ! $users ) {
		echo esc_html__( 'Nobody', 'probo-connect-theme' );

		return;
	}

	/* translators: %d: number of customers. */
	echo esc_html( sprintf( _n( '%d customer', '%d customers', $users, 'probo-connect-theme' ), $users ) );
}
add_action( 'manage_product_posts_custom_column', 'probo_product_access_admin_column_content', 20, 2 );

/* ---------------------------------------------------------------------------
   Managing it from the customer.

   The same grants, edited from the other end: open a customer and tick the
   restricted products they are allowed. It writes the product's own meta, so
   there is one list, readable from either screen.
--------------------------------------------------------------------------- */

/**
 * How many restricted products the profile screen will list as checkboxes.
 *
 * Past this it stops being a form and starts being a catalogue; the product
 * screen's customer search is the right tool at that size.
 *
 * @return int
 */
function probo_product_access_profile_limit() {
	/**
	 * Filters how many restricted products the customer profile screen lists.
	 *
	 * @param int $limit Number of products.
	 */
	return max( 1, (int) apply_filters( 'probo_product_access_profile_limit', 200 ) );
}

/**
 * The restricted products, titled and sorted, for the profile screen.
 *
 * @return array<int, string> Product id => title.
 */
function probo_product_access_restricted_choices() {
	$ids = probo_restricted_product_ids();

	if ( ! $ids ) {
		return array();
	}

	$products = get_posts(
		array(
			'post_type'           => 'product',
			'post_status'         => 'any',
			'post__in'            => $ids,
			'posts_per_page'      => probo_product_access_profile_limit(),
			'orderby'             => 'title',
			'order'               => 'ASC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'probo_access_bypass' => true,
		)
	);

	$choices = array();

	foreach ( $products as $product ) {
		$choices[ (int) $product->ID ] = get_the_title( $product );
	}

	return $choices;
}

/**
 * The "Product access" section on a customer's profile screen.
 *
 * @param WP_User $user User being edited.
 */
function probo_product_access_profile_fields( $user ) {
	if ( ! current_user_can( probo_product_access_manage_cap() ) ) {
		return;
	}

	$choices = probo_product_access_restricted_choices();
	$total   = count( probo_restricted_product_ids() );
	?>
	<h2 id="probo-product-access"><?php esc_html_e( 'Product access', 'probo-connect-theme' ); ?></h2>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Products for this customer', 'probo-connect-theme' ); ?></th>
			<td>
				<?php if ( ! $choices ) : ?>
					<p class="description">
						<?php esc_html_e( 'No product is limited to selected customers yet. Open a product, switch “Limit to selected customers” on under Customer access, and it will show up here.', 'probo-connect-theme' ); ?>
					</p>
				<?php else : ?>
					<?php wp_nonce_field( 'probo_product_access_user_' . $user->ID, 'probo_product_access_nonce' ); ?>
					<input type="hidden" name="probo_access_user_submitted" value="1" />

					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Products for this customer', 'probo-connect-theme' ); ?></legend>
						<?php foreach ( $choices as $product_id => $title ) : ?>
							<p style="margin:0 0 4px;">
								<label for="probo-access-product-<?php echo esc_attr( $product_id ); ?>">
									<input
										type="checkbox"
										id="probo-access-product-<?php echo esc_attr( $product_id ); ?>"
										name="probo_access_products[]"
										value="<?php echo esc_attr( $product_id ); ?>"
										<?php checked( in_array( (int) $user->ID, probo_product_access_users( $product_id ), true ) ); ?>
									/>
									<?php echo esc_html( $title ); ?>
								</label>
								<?php // Outside the label on purpose: a link inside one swallows the click that should tick the box. ?>
								<a class="description" href="<?php echo esc_url( (string) get_edit_post_link( $product_id ) ); ?>">
									<?php esc_html_e( 'edit product', 'probo-connect-theme' ); ?>
								</a>
							</p>
						<?php endforeach; ?>
					</fieldset>

					<p class="description">
						<?php esc_html_e( 'Only products that are limited to selected customers are listed.', 'probo-connect-theme' ); ?>
					</p>

					<?php if ( $total > count( $choices ) ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: 1: number of products listed, 2: total number of restricted products. */
								esc_html__( 'Showing %1$d of %2$d restricted products; manage the rest from the product’s own Customer access tab.', 'probo-connect-theme' ),
								(int) count( $choices ),
								(int) $total
							);
							?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'probo_product_access_profile_fields' );
add_action( 'edit_user_profile', 'probo_product_access_profile_fields' );

/**
 * Store what that section posted, onto the products themselves.
 *
 * Only the products the screen actually offered are touched, so a grant on a
 * product further down a truncated list survives a save here.
 *
 * @param int $user_id Customer being saved.
 */
function probo_product_access_save_profile( $user_id ) {
	$user_id = absint( $user_id );

	if ( ! isset( $_POST['probo_access_user_submitted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked immediately below.
		return;
	}

	if ( ! current_user_can( probo_product_access_manage_cap() ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	check_admin_referer( 'probo_product_access_user_' . $user_id, 'probo_product_access_nonce' );

	$checked = isset( $_POST['probo_access_products'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['probo_access_products'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked just above.

	foreach ( array_keys( probo_product_access_restricted_choices() ) as $product_id ) {
		$listed = in_array( $user_id, probo_product_access_users( $product_id ), true );
		$wanted = in_array( $product_id, $checked, true );

		if ( $wanted && ! $listed ) {
			add_post_meta( $product_id, PROBO_ACCESS_USER_META, $user_id );
		} elseif ( ! $wanted && $listed ) {
			delete_post_meta( $product_id, PROBO_ACCESS_USER_META, $user_id );
		}
	}

	probo_product_access_flush_cache();
}
add_action( 'personal_options_update', 'probo_product_access_save_profile' );
add_action( 'edit_user_profile_update', 'probo_product_access_save_profile' );
