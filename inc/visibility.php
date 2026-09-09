<?php
/**
 * Who may see which product.
 *
 * Two rules answer that question, and they are written down in two different
 * places because merchants think about them differently:
 *
 *   per product   A product with "Limit to selected customers" switched on is
 *                 visible only to the customers named on it. Stored as one
 *                 `_probo_access_restricted` flag plus one `_probo_access_user`
 *                 row per customer on the product itself. One row each rather
 *                 than one array is what keeps the reverse lookup — "which
 *                 products may this customer see?" — an ordinary indexed meta
 *                 query, which is what the customer's own "my products" page
 *                 and their profile screen both read.
 *
 *   per category  A product category that names users is a campaign: only those
 *                 users see the category, its subcategories and the products in
 *                 it. Stored as `_probo_portal_users` term meta. A category that
 *                 names nobody is the standard assortment, visible to everyone.
 *                 Because it is derived from the category, a product is hidden
 *                 as soon as ONE of its categories is hidden — so campaign
 *                 products belong in their campaign category only.
 *
 * A product is visible when both rules allow it. That single question —
 * probo_can_see_product() — is what every guard below goes through, and the
 * guards are what make a hidden product genuinely missing rather than merely
 * unbuyable: it drops out of every listing, out of search, out of related
 * products and out of the sitemap, its own URL stops resolving, and it cannot be
 * added to a cart even by someone who kept the link.
 *
 * The admin screens that edit both lists live in inc/visibility-admin.php.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/** Product meta: 'yes' when the product is limited to selected customers. */
const PROBO_PRODUCT_RESTRICTED_META = '_probo_access_restricted';

/** Product meta: one row per customer id that may access the product. */
const PROBO_PRODUCT_USER_META = '_probo_access_user';

/** Term meta: the user ids a product category is limited to. */
const PROBO_CATEGORY_USER_META = '_probo_portal_users';

/** Transient holding every product-restricted product id in the shop. */
const PROBO_RESTRICTED_PRODUCTS_TRANSIENT = 'probo_restricted_products';

/** Transient holding every restricted category and who may see it. */
const PROBO_RESTRICTED_CATEGORIES_TRANSIENT = 'probo_portal_restricted';

/* ---------------------------------------------------------------------------
   Reading the rules.
--------------------------------------------------------------------------- */

/**
 * Per-request memo, shared by the lookups below.
 *
 * Pass no key to empty it: a rule changed halfway through a request has to be
 * visible to whatever runs after it.
 *
 * @param string|null $key   Memo key, or null to flush.
 * @param mixed       $value Value to store; omit to read.
 * @return mixed Stored value, or null when the key is unset.
 */
function probo_visibility_memo( $key = null, $value = null ) {
	static $memo = array();

	if ( null === $key ) {
		$memo = array();

		return null;
	}

	if ( null !== $value ) {
		$memo[ $key ] = $value;
	}

	return $memo[ $key ] ?? null;
}

/**
 * Forget everything cached about the rules.
 *
 * The category hierarchy goes with it. WordPress caches "which term is whose
 * child" in the `product_cat_children` option, rebuilt from a get_terms() call
 * that used to run through the filters below — so an install that ran an older
 * version of this file can be carrying a hierarchy built from a customer's
 * filtered view of the taxonomy. Dropping it here makes the next request
 * rebuild it from the real taxonomy, and costs one delete on a rule change.
 */
function probo_visibility_flush() {
	delete_transient( PROBO_RESTRICTED_PRODUCTS_TRANSIENT );
	delete_transient( PROBO_RESTRICTED_CATEGORIES_TRANSIENT );
	delete_option( 'product_cat_children' );
	probo_visibility_memo();
}

/**
 * Guard against this module's own queries running back through its filters.
 *
 * probo_restricted_category_map() asks get_terms() which categories are
 * restricted, and probo_hidden_category_ids() then asks get_term_children()
 * which categories hang under them; without this flag those calls would be
 * filtered by the very list they are building — and because the second sits
 * inside the first, a plain boolean would be switched off halfway. Hence a
 * depth counter: it is only really off once the outermost caller says so.
 *
 * @param bool|null $set True to enter the guarded section, false to leave it,
 *                       null to read the current state.
 * @return bool Whether a guarded section is running.
 */
function probo_visibility_building( $set = null ) {
	static $depth = 0;

	if ( true === $set ) {
		++$depth;
	} elseif ( false === $set ) {
		$depth = max( 0, $depth - 1 );
	}

	return $depth > 0;
}

/**
 * Whether a user is shop staff, and therefore sees the shop as it really is.
 *
 * Whoever may edit products has to be able to browse the shop the way it really
 * is, otherwise the products and categories they just restricted disappear from
 * under them.
 *
 * @param int|null $user_id User to test, defaults to the current one.
 * @return bool
 */
function probo_visibility_is_staff( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );

	return $user_id && user_can( $user_id, 'edit_products' );
}

/**
 * The product id a rule hangs off.
 *
 * A variation is never restricted on its own: it inherits whatever its parent
 * product allows, so both a variation object and a raw variation id resolve to
 * the parent here.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @return int Product id, or 0 when it cannot be resolved.
 */
function probo_product_id( $product = null ) {
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
	$product_id = probo_product_id( $product );

	return $product_id && 'yes' === get_post_meta( $product_id, PROBO_PRODUCT_RESTRICTED_META, true );
}

/**
 * The customers listed on a product.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @return int[] User ids.
 */
function probo_product_access_users( $product = null ) {
	$product_id = probo_product_id( $product );

	if ( ! $product_id ) {
		return array();
	}

	$ids = get_post_meta( $product_id, PROBO_PRODUCT_USER_META, false );

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * The customers a product category is limited to.
 *
 * Empty for the standard assortment — the category everyone sees. Note the
 * array_filter: an unset term meta reads back as '', which wp_parse_id_list()
 * turns into a list holding a single 0, and a caller that only asks "is this
 * empty?" would read that as one customer.
 *
 * @param int $term_id Product category id.
 * @return int[] User ids.
 */
function probo_category_access_users( $term_id ) {
	$stored = get_term_meta( absint( $term_id ), PROBO_CATEGORY_USER_META, true );

	return array_values( array_filter( wp_parse_id_list( (array) $stored ) ) );
}

/**
 * Every product-restricted product in the shop.
 *
 * Restricted products are the exception rather than the rule, so the whole set
 * is cheap to hold and the per-request work becomes a capability check per id
 * instead of a meta query per listing. The query carries `probo_visibility_bypass`
 * so the query filter below leaves it alone — without it this would call itself.
 *
 * @return int[] Product ids.
 */
function probo_restricted_product_ids() {
	$memo = probo_visibility_memo( 'restricted_products' );

	if ( null !== $memo ) {
		return $memo;
	}

	$cached = get_transient( PROBO_RESTRICTED_PRODUCTS_TRANSIENT );

	if ( ! is_array( $cached ) ) {
		$cached = array_map(
			'absint',
			(array) get_posts(
				array(
					'post_type'              => 'product',
					'post_status'            => 'any',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_key'               => PROBO_PRODUCT_RESTRICTED_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one query per cache miss over a small set, held in a transient.
					'meta_value'             => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- see above.
					'probo_visibility_bypass' => true,
				)
			)
		);

		set_transient( PROBO_RESTRICTED_PRODUCTS_TRANSIENT, $cached, DAY_IN_SECONDS );
	}

	return probo_visibility_memo( 'restricted_products', $cached );
}

/**
 * Every restricted category, mapped to the user ids allowed to see it.
 *
 * One get_terms() call for the whole taxonomy, cached until a category is saved
 * or deleted — the list changes only when the merchant edits it, while it is
 * read on every request.
 *
 * @return array<int, int[]> Term id => allowed user ids.
 */
function probo_restricted_category_map() {
	$memo = probo_visibility_memo( 'restricted_categories' );

	if ( null !== $memo ) {
		return $memo;
	}

	$cached = get_transient( PROBO_RESTRICTED_CATEGORIES_TRANSIENT );

	if ( ! is_array( $cached ) ) {
		$cached = array();

		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $cached;
		}

		probo_visibility_building( true );

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => PROBO_CATEGORY_USER_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the whole point of the query; cached below.
			)
		);

		probo_visibility_building( false );

		foreach ( is_wp_error( $terms ) ? array() : (array) $terms as $term_id ) {
			$users = probo_category_access_users( (int) $term_id );

			// An empty list would hide the category from everyone, which is never
			// what the merchant meant — that is what deleting the meta is for.
			if ( $users ) {
				$cached[ (int) $term_id ] = $users;
			}
		}

		set_transient( PROBO_RESTRICTED_CATEGORIES_TRANSIENT, $cached, WEEK_IN_SECONDS );
	}

	return probo_visibility_memo( 'restricted_categories', $cached );
}

/**
 * The products a given customer may not see, by the per-product rule.
 *
 * Category-hidden products are not in here: there is no cheap way to enumerate
 * them, and there is no need to — probo_visibility_filter_query() excludes them
 * with a tax_query instead.
 *
 * @param int|null $user_id User to test, defaults to the current one.
 * @return int[] Product ids.
 */
function probo_hidden_product_ids( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	$memo    = probo_visibility_memo( 'hidden_products' ) ?? array();

	if ( isset( $memo[ $user_id ] ) ) {
		return $memo[ $user_id ];
	}

	$hidden = array();
	$staff  = probo_visibility_is_staff( $user_id );
	$listed = probo_restricted_product_ids();

	if ( ! $staff ) {
		foreach ( $listed as $product_id ) {
			if ( ! in_array( $user_id, probo_product_access_users( $product_id ), true ) ) {
				$hidden[] = $product_id;
			}
		}
	}

	$memo[ $user_id ] = $hidden;
	probo_visibility_memo( 'hidden_products', $memo );

	return $hidden;
}

/**
 * The categories a given customer may not see, including their subcategories.
 *
 * A restricted parent hides its children too, whatever the children say — the
 * campaign is the parent, so that is the level the merchant thinks in.
 *
 * @param int|null $user_id User to test, defaults to the current one.
 * @return int[] Term ids. Empty when nothing is restricted.
 */
function probo_hidden_category_ids( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	$memo    = probo_visibility_memo( 'hidden_categories' ) ?? array();

	if ( isset( $memo[ $user_id ] ) ) {
		return $memo[ $user_id ];
	}

	$hidden = array();

	if ( ! probo_visibility_is_staff( $user_id ) ) {
		// get_term_children() may have to rebuild the taxonomy's hierarchy, which
		// is another get_terms() call — and that one has to see the taxonomy as it
		// really is, or the children of a hidden parent are the very thing it
		// cannot find.
		probo_visibility_building( true );

		foreach ( probo_restricted_category_map() as $term_id => $allowed ) {
			if ( $user_id && in_array( $user_id, $allowed, true ) ) {
				continue;
			}

			$hidden[] = (int) $term_id;

			foreach ( get_term_children( $term_id, 'product_cat' ) as $child_id ) {
				$hidden[] = (int) $child_id;
			}
		}

		probo_visibility_building( false );

		$hidden = array_values( array_unique( $hidden ) );
	}

	$memo[ $user_id ] = $hidden;
	probo_visibility_memo( 'hidden_categories', $memo );

	return $hidden;
}

/**
 * Whether a customer may see and buy a product.
 *
 * The one question every guard in this file asks. Both rules have to allow it:
 * a product nobody restricted, in categories nobody restricted, is available to
 * everyone, logged in or not.
 *
 * @param int|WP_Post|WC_Product|null $product Product, post, or id.
 * @param int|null                    $user_id User to test, defaults to the current one.
 * @return bool
 */
function probo_can_see_product( $product = null, $user_id = null ) {
	$product_id = probo_product_id( $product );
	$user_id    = null === $user_id ? get_current_user_id() : absint( $user_id );

	if ( ! $product_id ) {
		return true;
	}

	if ( in_array( $product_id, probo_hidden_product_ids( $user_id ), true ) ) {
		return false;
	}

	$hidden_categories = probo_hidden_category_ids( $user_id );

	if ( ! $hidden_categories ) {
		return true;
	}

	// get_the_terms() rather than wp_get_object_terms(): WP_Query primes the term
	// cache for a whole listing in one query, and only this one reads it. The
	// direct call would be a query per product on every shop page.
	$terms = get_the_terms( $product_id, 'product_cat' );

	if ( ! is_array( $terms ) ) {
		return true;
	}

	return ! array_intersect( array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) ), $hidden_categories );
}

/**
 * Whether this request's front-end queries should be filtered at all.
 *
 * @return bool
 */
function probo_visibility_applies() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}

	if ( probo_visibility_building() ) {
		return false;
	}

	return (bool) ( probo_hidden_product_ids() || probo_hidden_category_ids() );
}

/* ---------------------------------------------------------------------------
   Keeping the cache honest.
--------------------------------------------------------------------------- */

/**
 * Flush when a product changes.
 *
 * The post object is used when the hook hands one over: on `deleted_post` the
 * row is already gone, so looking the type up by id would come back empty and
 * the deleted product would stay in the cached list.
 *
 * @param int          $post_id Post id.
 * @param WP_Post|null $post    Post, when the hook passes one.
 */
function probo_visibility_flush_for_post( $post_id, $post = null ) {
	$post_type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );

	if ( 'product' === $post_type ) {
		probo_visibility_flush();
	}
}
add_action( 'save_post_product', 'probo_visibility_flush' );
add_action( 'deleted_post', 'probo_visibility_flush_for_post', 10, 2 );
add_action( 'trashed_post', 'probo_visibility_flush_for_post' );
add_action( 'untrashed_post', 'probo_visibility_flush_for_post' );
add_action( 'edited_product_cat', 'probo_visibility_flush' );
add_action( 'created_product_cat', 'probo_visibility_flush' );
add_action( 'delete_product_cat', 'probo_visibility_flush' );

/**
 * Drop a deleted user's product grants.
 *
 * WordPress reuses user ids, so a grant left behind on a product would hand the
 * next account to take that id an access it was never given. The products are
 * looked up directly — no meta query finds a value across posts — but the rows
 * are then deleted through the meta API, so the post meta caches follow.
 *
 * Category grants are cleaned up on save instead (see
 * probo_visibility_save_category()), because they are one serialised row rather
 * than one row per user.
 *
 * @param int $user_id Deleted user id.
 */
function probo_visibility_delete_user_grants( $user_id ) {
	global $wpdb;

	$user_id = absint( $user_id );

	if ( ! $user_id ) {
		return;
	}

	$product_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- finding meta rows by value; no core API covers it.
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
			PROBO_PRODUCT_USER_META,
			(string) $user_id
		)
	);

	foreach ( $product_ids as $product_id ) {
		delete_post_meta( (int) $product_id, PROBO_PRODUCT_USER_META, $user_id );
	}

	if ( $product_ids ) {
		probo_visibility_flush();
	}
}
add_action( 'deleted_user', 'probo_visibility_delete_user_grants' );

/* ---------------------------------------------------------------------------
   The shop front.
--------------------------------------------------------------------------- */

/**
 * The message a customer gets when a product is not theirs.
 *
 * @return string
 */
function probo_visibility_denied_message() {
	return is_user_logged_in()
		? __( 'This product is not available for your account. Contact us if you think it should be.', 'probo-connect-theme' )
		: __( 'This product is only available to logged-in customers. Log in to continue.', 'probo-connect-theme' );
}

/**
 * Show that message, if WooCommerce has somewhere to put it.
 *
 * @param string $message Message text, defaults to probo_visibility_denied_message().
 * @param string $type    Notice type.
 */
function probo_visibility_notice( $message = '', $type = 'error' ) {
	if ( function_exists( 'wc_add_notice' ) && WC()->session ) {
		wc_add_notice( $message ? $message : probo_visibility_denied_message(), $type );
	}
}

/**
 * Take everything this visitor may not see out of every front-end query.
 *
 * Runs late enough that WooCommerce has already set `post_type` on the shop's
 * main query, and hangs off pre_get_posts rather than a WooCommerce hook so it
 * also covers search, blocks, shortcodes, the REST and Store API and any
 * plugin's own product loop.
 *
 * The two rules need two different exclusions: a handful of product ids is a
 * `post__not_in`, while "everything in these categories" is a tax_query — which
 * is also what makes it cheap, because the category list is short and the
 * product list behind it never has to be enumerated.
 *
 * @param WP_Query $query Query about to run.
 */
function probo_visibility_filter_query( $query ) {
	// The bypass flag is tested before anything else: probo_visibility_applies()
	// reaches probo_restricted_product_ids(), whose own get_posts() lands right
	// back here, and asking the expensive question first would recurse.
	if ( $query->get( 'probo_visibility_bypass' ) || ! probo_visibility_applies() ) {
		return;
	}

	// A single product is left to probo_visibility_guard_request(): excluding it
	// here would turn every refusal into a bare 404, and a visitor who is not
	// logged in yet deserves the login form instead.
	if ( $query->is_singular() ) {
		return;
	}

	// An unset post_type reads as array( '' ), which would look like a typed
	// query; the empties go first so "no post type at all" is recognisable.
	$post_types = array_filter( (array) $query->get( 'post_type' ) );

	$targets_products = in_array( 'product', $post_types, true )
		|| in_array( 'any', $post_types, true )
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_tax( 'product_cat' )
		|| $query->is_tax( 'product_tag' )
		|| ( $query->is_search() && ! $post_types );

	if ( ! $targets_products ) {
		return;
	}

	$hidden_products = probo_hidden_product_ids();

	if ( $hidden_products ) {
		$query->set(
			'post__not_in',
			array_unique( array_merge( array_map( 'absint', (array) $query->get( 'post__not_in' ) ), $hidden_products ) )
		);
	}

	$hidden_categories = probo_hidden_category_ids();

	if ( ! $hidden_categories ) {
		return;
	}

	$clause = array(
		'taxonomy'         => 'product_cat',
		'field'            => 'term_id',
		'terms'            => $hidden_categories,
		'operator'         => 'NOT IN',
		'include_children' => true,
	);

	$tax_query = (array) $query->get( 'tax_query' );

	// Appending to a set of OR'd clauses would make the exclusion optional, so
	// that set is nested and AND'ed with it instead.
	if ( isset( $tax_query['relation'] ) && 'OR' === strtoupper( (string) $tax_query['relation'] ) ) {
		$tax_query = array( 'relation' => 'AND', $tax_query, $clause );
	} else {
		$tax_query[] = $clause;
	}

	$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the exclusion is the query.
}
add_action( 'pre_get_posts', 'probo_visibility_filter_query', 20 );

/**
 * Whether a term query asks about the shape of the taxonomy rather than for a
 * listing to show someone.
 *
 * Two of those, and filtering either one is worse than useless:
 *
 *   object_ids  "which categories does this product have?" — the question
 *               probo_can_see_product() asks. Filtering it would answer "none
 *               that are hidden" and quietly make every product visible.
 *
 *   id=>parent  the query _get_term_hierarchy() rebuilds `product_cat_children`
 *               from. Its answer is cached in an option every visitor shares,
 *               so one customer's filtered view would become everybody's idea of
 *               which category is whose child — and it is also what
 *               probo_hidden_category_ids() needs in order to hide a campaign's
 *               subcategories at all.
 *
 * @param array $args Term query arguments.
 * @return bool
 */
function probo_visibility_structural_term_query( $args ) {
	return ! empty( $args['object_ids'] ) || 'id=>parent' === ( $args['fields'] ?? '' );
}

/**
 * Keep hidden categories out of every get_terms() call.
 *
 * This one filter covers the navigation (probo_build_menu_fallback_items()), the
 * Category Tiles block, WooCommerce's own category widgets and anything else
 * that lists categories, because they all go through get_terms().
 *
 * @param array    $args       Term query arguments.
 * @param string[] $taxonomies Taxonomies being queried.
 * @return array
 */
function probo_visibility_filter_terms_args( $args, $taxonomies ) {
	if ( ! in_array( 'product_cat', (array) $taxonomies, true ) || ! probo_visibility_applies() ) {
		return $args;
	}

	if ( probo_visibility_structural_term_query( $args ) ) {
		return $args;
	}

	// 'include' beats 'exclude' in WP_Term_Query, so those queries are left to
	// probo_visibility_filter_terms() below instead.
	if ( ! empty( $args['include'] ) ) {
		return $args;
	}

	$hidden = probo_hidden_category_ids();

	if ( $hidden ) {
		$exclude         = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : array();
		$args['exclude'] = array_values( array_unique( array_merge( $exclude, $hidden ) ) );
	}

	return $args;
}
add_filter( 'get_terms_args', 'probo_visibility_filter_terms_args', 10, 2 );

/**
 * Backstop for term queries the 'exclude' argument could not cover.
 *
 * @param array|int|string $terms      Found terms.
 * @param string[]|null    $taxonomies Taxonomies queried.
 * @param array            $args       Term query arguments.
 * @return array|int|string
 */
function probo_visibility_filter_terms( $terms, $taxonomies, $args ) {
	if ( ! is_array( $terms ) || ! in_array( 'product_cat', (array) $taxonomies, true ) || ! probo_visibility_applies() ) {
		return $terms;
	}

	// Structural queries are left alone here for the same reason as above: they
	// are the input to the visibility check, not a listing.
	if ( probo_visibility_structural_term_query( $args ) ) {
		return $terms;
	}

	// 'id=>name' and 'id=>slug' hand back a map keyed by term id, and the id is
	// the whole point of asking that way — reindexing it would silently turn
	// every term id into its position in the list.
	$keyed  = is_string( $args['fields'] ?? '' ) && str_starts_with( (string) ( $args['fields'] ?? '' ), 'id=>' );
	$hidden = probo_hidden_category_ids();

	foreach ( $terms as $index => $term ) {
		if ( $term instanceof WP_Term ) {
			$term_id = $term->term_id;
		} elseif ( $keyed ) {
			$term_id = (int) $index;
		} elseif ( is_numeric( $term ) ) {
			$term_id = (int) $term;
		} else {
			// 'names' or 'slugs': nothing to match on, and the 'exclude' argument
			// above already handled those queries.
			continue;
		}

		if ( in_array( $term_id, $hidden, true ) ) {
			unset( $terms[ $index ] );
		}
	}

	return $keyed ? $terms : array_values( $terms );
}
add_filter( 'get_terms', 'probo_visibility_filter_terms', 10, 3 );

/**
 * A hidden product or category is a 404, not a listing that happens to be empty.
 *
 * Product and category URLs are guessable, so leaving them reachable would make
 * all the filtering above cosmetic. A customer who kept a link gets the same
 * shop everyone else in their position gets: logged out they are sent to the
 * login form with an explanation, logged in it is a 404 — telling them a product
 * exists that they may not have would leak the catalogue one URL at a time.
 */
function probo_visibility_guard_request() {
	if ( ! probo_visibility_applies() ) {
		return;
	}

	if ( is_singular( 'product' ) ) {
		$blocked = ! probo_can_see_product( get_queried_object_id() );
	} elseif ( is_tax( 'product_cat' ) ) {
		$blocked = in_array( (int) get_queried_object_id(), probo_hidden_category_ids(), true );
	} else {
		return;
	}

	if ( ! $blocked ) {
		return;
	}

	$account = ! is_user_logged_in() && function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'myaccount', '' )
		: '';

	if ( $account ) {
		probo_visibility_notice( '', 'notice' );
		wp_safe_redirect( $account );
		exit;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'probo_visibility_guard_request', 5 );

/**
 * Hide the product from WooCommerce's own visibility checks.
 *
 * Related products, up-sells and cross-sells are not WP_Query listings — they
 * come out of their own lookups and are then filtered on is_visible(), so this
 * is the hook that covers them.
 *
 * @param bool $visible    Whether WooCommerce considers it visible.
 * @param int  $product_id Product id.
 * @return bool
 */
function probo_visibility_is_visible( $visible, $product_id ) {
	return $visible && probo_can_see_product( $product_id );
}
add_filter( 'woocommerce_product_is_visible', 'probo_visibility_is_visible', 10, 2 );

/**
 * Keep hidden products out of the related-products strip.
 *
 * @param int[] $related_ids Related product ids.
 * @return int[]
 */
function probo_visibility_related_products( $related_ids ) {
	return array_values( array_filter( (array) $related_ids, 'probo_can_see_product' ) );
}
add_filter( 'woocommerce_related_products', 'probo_visibility_related_products' );

/**
 * Keep restricted products out of the XML sitemap.
 *
 * The sitemap is one public document for every visitor, so it cannot be cut per
 * customer: everything either rule restricts stays out of it, for everyone.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type being listed.
 * @return array
 */
function probo_visibility_sitemap_args( $args, $post_type ) {
	if ( 'product' !== $post_type ) {
		return $args;
	}

	$restricted = probo_restricted_product_ids();

	if ( $restricted ) {
		$args['post__not_in'] = array_unique( array_merge( array_map( 'absint', (array) ( $args['post__not_in'] ?? array() ) ), $restricted ) );
	}

	$categories = array_keys( probo_restricted_category_map() );

	if ( $categories ) {
		$args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the exclusion is the query.
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => $categories,
			'operator'         => 'NOT IN',
			'include_children' => true,
		);
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'probo_visibility_sitemap_args', 10, 2 );

/**
 * A product that is not this customer's cannot be bought.
 *
 * @param bool       $purchasable Whether the product is purchasable.
 * @param WC_Product $product     Product.
 * @return bool
 */
function probo_visibility_is_purchasable( $purchasable, $product ) {
	return $purchasable && probo_can_see_product( $product );
}
add_filter( 'woocommerce_is_purchasable', 'probo_visibility_is_purchasable', 10, 2 );

/**
 * Refuse the add-to-cart request itself.
 *
 * @param bool $passed       Whether validation passed so far.
 * @param int  $product_id   Product id.
 * @param int  $quantity     Quantity.
 * @param int  $variation_id Variation id, when adding a variation.
 * @return bool
 */
function probo_visibility_add_to_cart_validation( $passed, $product_id, $quantity = 1, $variation_id = 0 ) {
	if ( ! $passed || probo_can_see_product( $variation_id ? $variation_id : $product_id ) ) {
		return $passed;
	}

	probo_visibility_notice();

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'probo_visibility_add_to_cart_validation', 10, 4 );

/**
 * Drop cart lines the customer is no longer entitled to.
 *
 * A cart outlives the session it was filled in, and a campaign outlives a cart:
 * a customer logs out, access is withdrawn, a category is unlinked, an order is
 * placed days later. So what was allowed at add-to-cart time is checked again
 * before the cart and the checkout are drawn.
 */
function probo_visibility_check_cart_items() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : ( $cart_item['product_id'] ?? 0 );

		if ( ! $product_id || probo_can_see_product( $product_id ) ) {
			continue;
		}

		WC()->cart->remove_cart_item( $cart_item_key );

		probo_visibility_notice(
			sprintf(
				/* translators: %s: product name. */
				__( '%s is no longer available for your account and was removed from your cart.', 'probo-connect-theme' ),
				get_the_title( probo_product_id( $product_id ) )
			)
		);
	}
}
add_action( 'woocommerce_check_cart_items', 'probo_visibility_check_cart_items' );

/**
 * Vary the cached fallback navigation per set of hidden categories.
 *
 * The menu is assembled once and cached; without this the first visitor's menu —
 * campaign categories and all — would be served to everyone.
 *
 * @param string $suffix Cache key suffix.
 * @return string
 */
function probo_visibility_menu_cache_suffix( $suffix ) {
	$hidden = probo_visibility_applies() ? probo_hidden_category_ids() : array();

	return $suffix . ( $hidden ? '_' . md5( implode( ',', $hidden ) ) : '' );
}
add_filter( 'probo_menu_fallback_cache_suffix', 'probo_visibility_menu_cache_suffix' );

/**
 * Take hidden categories and products out of a hand-built navigation menu.
 *
 * The fallback navigation is covered by the get_terms() filter above, but a menu
 * the merchant assembled in Weergave → Menu's is a set of posts, not a term
 * query — so a campaign category linked there would stay in every customer's
 * menu and hand them a 404 for their trouble.
 *
 * A dropped item takes its submenu with it: leaving orphans behind would put a
 * campaign's subcategories straight into the top level.
 *
 * @param array $items Sorted menu items, parents before their children.
 * @return array
 */
function probo_visibility_filter_nav_menu_objects( $items ) {
	if ( ! is_array( $items ) || ! probo_visibility_applies() ) {
		return $items;
	}

	$hidden_categories = probo_hidden_category_ids();
	$dropped           = array();

	foreach ( $items as $index => $item ) {
		$parent = (int) ( $item->menu_item_parent ?? 0 );
		$type   = (string) ( $item->type ?? '' );
		$object = (string) ( $item->object ?? '' );

		if ( $parent && isset( $dropped[ $parent ] ) ) {
			$drop = true;
		} elseif ( 'taxonomy' === $type && 'product_cat' === $object ) {
			$drop = in_array( (int) $item->object_id, $hidden_categories, true );
		} elseif ( 'post_type' === $type && 'product' === $object ) {
			$drop = ! probo_can_see_product( (int) $item->object_id );
		} else {
			$drop = false;
		}

		if ( $drop ) {
			$dropped[ (int) $item->ID ] = true;
			unset( $items[ $index ] );
		}
	}

	return array_values( $items );
}
add_filter( 'wp_nav_menu_objects', 'probo_visibility_filter_nav_menu_objects' );

/* ---------------------------------------------------------------------------
   The customer's own list.

   The shop already shows a customer only what they may see. This is the other
   page a portal wants: not "everything you may buy" but "the products opened up
   for you" — the shortcode a shop drops on a page of its own.
--------------------------------------------------------------------------- */

/**
 * The restricted products a customer was given, by name.
 *
 * This is the lookup the per-product storage was chosen for: one
 * `_probo_access_user` row per customer makes it an ordinary indexed meta query
 * instead of a scan through every product's serialised list.
 *
 * Only products that are actually limited are listed. A product that was opened
 * back up to the whole shop is in the catalogue like any other, and listing it
 * here as well would tell the customer it is theirs alone when it is not.
 *
 * @param int|null $user_id Customer, defaults to the current one.
 * @param array    $args    Optional query overrides: limit, orderby, order.
 * @return int[] Product ids.
 */
function probo_customer_product_ids( $user_id = null, $args = array() ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );

	if ( ! $user_id || ! post_type_exists( 'product' ) ) {
		return array();
	}

	$args = wp_parse_args(
		$args,
		array(
			'limit'   => -1,
			'orderby' => 'title',
			'order'   => 'ASC',
		)
	);

	$ids = get_posts(
		array(
			'post_type'               => 'product',
			'post_status'             => 'publish',
			'posts_per_page'          => (int) $args['limit'],
			'orderby'                 => $args['orderby'],
			'order'                   => $args['order'],
			'fields'                  => 'ids',
			'no_found_rows'           => true,
			'ignore_sticky_posts'     => true,
			'update_post_term_cache'  => false,
			'meta_key'                => PROBO_PRODUCT_USER_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the indexed lookup this storage exists for.
			'meta_value'              => (string) $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- see above.
			'probo_visibility_bypass' => true,
		)
	);

	return array_values( array_filter( array_map( 'absint', (array) $ids ), 'probo_product_is_restricted' ) );
}

/**
 * Render a customer's own products as the theme's product tiles.
 *
 * The tiles are drawn here rather than through content-product.php on purpose.
 * That template skips a product whose catalogue visibility is "hidden", which is
 * exactly what a shop reaches for out of habit on a product it is restricting —
 * and the page that promises "your products" would then quietly come up empty.
 * The markup and the two loop hooks are the template's own, so the tiles are the
 * same tiles.
 *
 * @param int[] $product_ids Products to draw.
 */
function probo_render_product_grid( $product_ids ) {
	if ( ! $product_ids ) {
		return;
	}

	echo wp_kses_post( probo_product_loop_start() );

	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		echo '<li class="' . esc_attr( implode( ' ', wc_get_product_class( '', $product ) ) ) . '">';

		/** This hook is documented in woocommerce/content-product.php. */
		do_action( 'woocommerce_before_shop_loop_item' );

		probo_product_card( $product );

		/** This hook is documented in woocommerce/content-product.php. */
		do_action( 'woocommerce_after_shop_loop_item' );

		echo '</li>';
	}

	echo '</ul>';
}

/**
 * [probo_my_products] — the logged-in customer's own products, as a grid.
 *
 * Drop it on any page ("Mijn producten", a dashboard, the front page of a
 * portal) and it draws the same tiles the shop does.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function probo_my_products_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit'   => -1,
			'orderby' => 'title',
			'order'   => 'ASC',
			'empty'   => __( 'No products have been set up for your account yet. Get in touch and we will add them.', 'probo-connect-theme' ),
		),
		$atts,
		'probo_my_products'
	);

	ob_start();

	if ( ! is_user_logged_in() ) {
		// In a closed portal nobody reaches this page logged out; on an open shop
		// it is the one thing worth saying here.
		probo_login_required_prompt(
			__( 'Log in to see the products set up for your account.', 'probo-connect-theme' ),
			(string) get_permalink()
		);

		return (string) ob_get_clean();
	}

	$products = probo_customer_product_ids(
		null,
		array(
			'limit'   => (int) $atts['limit'],
			'orderby' => sanitize_key( $atts['orderby'] ),
			'order'   => 'DESC' === strtoupper( (string) $atts['order'] ) ? 'DESC' : 'ASC',
		)
	);

	if ( $products ) {
		probo_render_product_grid( $products );
	} elseif ( $atts['empty'] ) {
		echo '<p class="text-[15px] text-ink-3">' . esc_html( $atts['empty'] ) . '</p>';
	}

	return (string) ob_get_clean();
}
add_shortcode( 'probo_my_products', 'probo_my_products_shortcode' );
