<?php
/**
 * Category visibility per customer — the closed portal's campaign categories.
 *
 * A product category can name the users allowed to see it. A category that
 * names nobody is the standard assortment: visible to every logged-in
 * customer, exactly as before. A category that names users is a campaign —
 * only those users see the category, its subcategories, and the products in
 * it.
 *
 * Product visibility is derived from the categories, never stored on the
 * product itself, so the merchant manages one list in one place. The rule that
 * follows from that: a product is hidden as soon as ONE of its categories is
 * hidden, so campaign products belong in their campaign category only. A
 * standard product dropped into a campaign category disappears for everyone
 * outside that campaign.
 *
 * Hiding a category is not the same as hiding its products, so the filters
 * below cover both, plus the ways a product is reachable without a listing:
 * its own URL, the cart and the checkout.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Term meta key holding the user ids a category is limited to.
 *
 * @return string
 */
function probo_portal_meta_key() {
	return '_probo_portal_users';
}

/**
 * Guard against the module's own queries running back through its filters.
 *
 * probo_portal_restricted_map() asks get_terms() which categories are
 * restricted; without this flag that call would be filtered by the very list
 * it is building.
 *
 * @param bool|null $set New state, or null to read the current one.
 * @return bool
 */
function probo_portal_building( $set = null ) {
	static $building = false;

	if ( null !== $set ) {
		$building = (bool) $set;
	}

	return $building;
}

/**
 * Whether the current user manages the shop and therefore sees everything.
 *
 * @return bool
 */
function probo_portal_user_can_manage() {
	/**
	 * Filters who bypasses category visibility.
	 *
	 * @param bool $can_manage Default: anyone who can edit other people's products.
	 */
	return (bool) apply_filters( 'probo_portal_user_can_manage', current_user_can( 'edit_others_products' ) );
}

/**
 * Every restricted category, mapped to the user ids allowed to see it.
 *
 * One get_terms() call for the whole taxonomy, cached until a category is
 * saved or deleted — the list changes only when the merchant edits it, while
 * it is read on every request.
 *
 * @return array<int, int[]> Term id => allowed user ids.
 */
function probo_portal_restricted_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	$cached = get_transient( 'probo_portal_restricted' );

	if ( is_array( $cached ) ) {
		$map = $cached;

		return $map;
	}

	$map = array();

	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $map;
	}

	probo_portal_building( true );

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_key'   => probo_portal_meta_key(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the whole point of the query; cached below.
		)
	);

	probo_portal_building( false );

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term_id ) {
			$users = array_values( array_filter( wp_parse_id_list( (array) get_term_meta( (int) $term_id, probo_portal_meta_key(), true ) ) ) );

			// An empty list would hide the category from everyone, which is
			// never what the merchant meant — that is what deleting the meta
			// is for. Treat it as unrestricted.
			if ( $users ) {
				$map[ (int) $term_id ] = $users;
			}
		}
	}

	set_transient( 'probo_portal_restricted', $map, WEEK_IN_SECONDS );

	return $map;
}

/**
 * Drop the cached restriction map.
 */
function probo_portal_flush() {
	delete_transient( 'probo_portal_restricted' );
}
add_action( 'edited_product_cat', 'probo_portal_flush' );
add_action( 'created_product_cat', 'probo_portal_flush' );
add_action( 'delete_product_cat', 'probo_portal_flush' );

/**
 * Categories the current user may not see, including their subcategories.
 *
 * A restricted parent hides its children too, whatever the children say — the
 * campaign is the parent, so that is the level the merchant thinks in.
 *
 * @return int[] Term ids. Empty when nothing is restricted.
 */
function probo_portal_hidden_category_ids() {
	static $hidden = null;
	static $for_user = null;

	$user_id = get_current_user_id();

	if ( null !== $hidden && $for_user === $user_id ) {
		return $hidden;
	}

	$hidden   = array();
	$for_user = $user_id;

	foreach ( probo_portal_restricted_map() as $term_id => $allowed ) {
		if ( $user_id && in_array( $user_id, $allowed, true ) ) {
			continue;
		}

		$hidden[] = $term_id;

		foreach ( get_term_children( $term_id, 'product_cat' ) as $child_id ) {
			$hidden[] = (int) $child_id;
		}
	}

	$hidden = array_values( array_unique( array_map( 'intval', $hidden ) ) );

	return $hidden;
}

/**
 * Whether this request's front-end queries should be filtered at all.
 *
 * @return bool
 */
function probo_portal_filters_apply() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}

	if ( probo_portal_building() || probo_portal_user_can_manage() ) {
		return false;
	}

	return (bool) probo_portal_hidden_category_ids();
}

/**
 * Whether a product is visible to the current user.
 *
 * @param int $product_id Product (or variation parent) id.
 * @return bool
 */
function probo_portal_product_is_visible( $product_id ) {
	if ( ! probo_portal_filters_apply() ) {
		return true;
	}

	// get_the_terms() rather than wp_get_object_terms(): WP_Query primes the
	// term cache for a whole listing in one query, and only this one reads it.
	// The direct call would be a query per product on every shop page.
	$terms = get_the_terms( (int) $product_id, 'product_cat' );

	if ( ! is_array( $terms ) ) {
		return true;
	}

	$term_ids = wp_list_pluck( $terms, 'term_id' );

	return ! array_intersect( array_map( 'intval', $term_ids ), probo_portal_hidden_category_ids() );
}

/**
 * Keep hidden categories out of every get_terms() call.
 *
 * This one filter covers the navigation (probo_build_menu_fallback_items()),
 * the Category Tiles block, WooCommerce's own category widgets and anything
 * else that lists categories, because they all go through get_terms().
 *
 * @param array    $args       Term query arguments.
 * @param string[] $taxonomies Taxonomies being queried.
 * @return array
 */
function probo_portal_filter_terms_args( $args, $taxonomies ) {
	if ( ! in_array( 'product_cat', (array) $taxonomies, true ) || ! probo_portal_filters_apply() ) {
		return $args;
	}

	// "Which categories does this product have?" is a question about the data,
	// not a listing to filter — and it is the question this module's own
	// visibility check asks. Filtering it would answer "none that are hidden"
	// and quietly make every product visible.
	if ( ! empty( $args['object_ids'] ) ) {
		return $args;
	}

	// 'include' beats 'exclude' in WP_Term_Query, so those queries are left to
	// probo_portal_filter_terms() below instead.
	if ( ! empty( $args['include'] ) ) {
		return $args;
	}

	$exclude = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : array();

	$args['exclude'] = array_values( array_unique( array_merge( $exclude, probo_portal_hidden_category_ids() ) ) );

	return $args;
}
add_filter( 'get_terms_args', 'probo_portal_filter_terms_args', 10, 2 );

/**
 * Backstop for term queries the 'exclude' argument could not cover.
 *
 * @param array|int|string $terms      Found terms.
 * @param string[]|null    $taxonomies Taxonomies queried.
 * @param array            $args       Term query arguments.
 * @return array|int|string
 */
function probo_portal_filter_terms( $terms, $taxonomies, $args ) {
	if ( ! is_array( $terms ) || ! in_array( 'product_cat', (array) $taxonomies, true ) || ! probo_portal_filters_apply() ) {
		return $terms;
	}

	// Object-term queries are excluded here for the same reason as in
	// probo_portal_filter_terms_args(): they are the input to the visibility
	// check, not a listing.
	if ( ! empty( $args['object_ids'] ) ) {
		return $terms;
	}

	$hidden = probo_portal_hidden_category_ids();

	foreach ( $terms as $index => $term ) {
		if ( $term instanceof WP_Term ) {
			$term_id = $term->term_id;
		} elseif ( is_numeric( $term ) ) {
			$term_id = (int) $term;
		} else {
			// 'names' or 'slugs': nothing to match on, and the 'exclude'
			// argument above already handled those queries.
			continue;
		}

		if ( in_array( $term_id, $hidden, true ) ) {
			unset( $terms[ $index ] );
		}
	}

	return array_values( $terms );
}
add_filter( 'get_terms', 'probo_portal_filter_terms', 10, 3 );

/**
 * Keep products from hidden categories out of every product query.
 *
 * Runs on pre_get_posts rather than on woocommerce_product_query, so it covers
 * the shop, search, the REST and Store API, the theme's own block queries and
 * the flyout's product lists in one place.
 *
 * @param WP_Query $query Query about to run.
 */
function probo_portal_filter_product_query( $query ) {
	if ( ! probo_portal_filters_apply() ) {
		return;
	}

	// An unset post_type reads as array( '' ), which would look like a typed
	// query; the empties go first so "no post type at all" is recognisable.
	$post_types = array_filter( (array) $query->get( 'post_type' ) );

	$is_product_query = in_array( 'product', $post_types, true )
		|| in_array( 'any', $post_types, true )
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_tax( 'product_cat' )
		|| $query->is_tax( 'product_tag' )
		|| ( $query->is_search() && ! $post_types );

	if ( ! $is_product_query ) {
		return;
	}

	$clause = array(
		'taxonomy'         => 'product_cat',
		'field'            => 'term_id',
		'terms'            => probo_portal_hidden_category_ids(),
		'operator'         => 'NOT IN',
		'include_children' => true,
	);

	$tax_query = (array) $query->get( 'tax_query' );

	// Appending to a set of OR'd clauses would make the exclusion optional, so
	// that set is nested and AND'ed with it instead.
	if ( isset( $tax_query['relation'] ) && 'OR' === strtoupper( (string) $tax_query['relation'] ) ) {
		$tax_query = array(
			'relation' => 'AND',
			$tax_query,
			$clause,
		);
	} else {
		$tax_query[] = $clause;
	}

	$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the exclusion is the query.
}
// After WooCommerce's own pre_get_posts, which is what sets the shop page's
// post type in the first place.
add_action( 'pre_get_posts', 'probo_portal_filter_product_query', 20 );

/**
 * A hidden product or category is a 404, not a listing that happens to be empty.
 *
 * Product and category URLs are guessable, so leaving them reachable would
 * make the whole filtering above cosmetic.
 */
function probo_portal_guard_requests() {
	if ( ! probo_portal_filters_apply() ) {
		return;
	}

	$blocked = false;

	if ( is_singular( 'product' ) ) {
		$blocked = ! probo_portal_product_is_visible( get_queried_object_id() );
	} elseif ( is_tax( 'product_cat' ) ) {
		$blocked = in_array( (int) get_queried_object_id(), probo_portal_hidden_category_ids(), true );
	}

	if ( ! $blocked ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'probo_portal_guard_requests' );

/**
 * A hidden product cannot be bought, however it ended up in front of someone.
 *
 * @param bool       $purchasable Whether WooCommerce considers it purchasable.
 * @param WC_Product $product     Product being checked.
 * @return bool
 */
function probo_portal_is_purchasable( $purchasable, $product ) {
	if ( ! $purchasable || ! $product instanceof WC_Product ) {
		return $purchasable;
	}

	$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

	return probo_portal_product_is_visible( $product_id );
}
add_filter( 'woocommerce_is_purchasable', 'probo_portal_is_purchasable', 10, 2 );

/**
 * Hide the product from WooCommerce's own catalogue-visibility checks.
 *
 * Related products, up-sells and cross-sells are not WP_Query listings — they
 * come out of their own lookups and are then filtered on is_visible(), so this
 * is the hook that covers them. Everything that does run through WP_Query is
 * already handled by probo_portal_filter_product_query().
 *
 * @param bool $visible    Whether WooCommerce considers it visible.
 * @param int  $product_id Product being checked.
 * @return bool
 */
function probo_portal_product_is_visible_filter( $visible, $product_id ) {
	if ( ! $visible ) {
		return $visible;
	}

	return probo_portal_product_is_visible( $product_id );
}
add_filter( 'woocommerce_product_is_visible', 'probo_portal_product_is_visible_filter', 10, 2 );

/**
 * Refuse an add-to-cart for a product this user may not see.
 *
 * @param bool $passed     Validation so far.
 * @param int  $product_id Product being added.
 * @return bool
 */
function probo_portal_validate_add_to_cart( $passed, $product_id ) {
	if ( ! $passed || probo_portal_product_is_visible( $product_id ) ) {
		return $passed;
	}

	wc_add_notice( __( 'This product is not available for your account.', 'probo-connect-theme' ), 'error' );

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'probo_portal_validate_add_to_cart', 10, 2 );

/**
 * Drop cart items that are no longer available to this account.
 *
 * A cart outlives a campaign: the customer can fill it in week 52 and reach
 * the checkout after the merchant has unlinked the category.
 */
function probo_portal_check_cart_items() {
	if ( ! probo_portal_filters_apply() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
		$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;

		if ( $product_id && ! probo_portal_product_is_visible( $product_id ) ) {
			WC()->cart->remove_cart_item( $cart_key );

			wc_add_notice(
				sprintf(
					/* translators: %s: product name. */
					__( '%s is no longer available for your account and was removed from your cart.', 'probo-connect-theme' ),
					get_the_title( $product_id )
				),
				'error'
			);
		}
	}
}
add_action( 'woocommerce_check_cart_items', 'probo_portal_check_cart_items' );

/**
 * Vary the cached fallback navigation per set of hidden categories.
 *
 * The menu is assembled once and cached for a day; without this the first
 * visitor's menu — campaign categories and all — would be served to everyone.
 *
 * @param string $suffix Cache key suffix.
 * @return string
 */
function probo_portal_menu_cache_suffix( $suffix ) {
	$hidden = probo_portal_filters_apply() ? probo_portal_hidden_category_ids() : array();

	return $suffix . ( $hidden ? '_' . md5( implode( ',', $hidden ) ) : '' );
}
add_filter( 'probo_menu_fallback_cache_suffix', 'probo_portal_menu_cache_suffix' );

/**
 * The capability that guards editing a category's user list.
 *
 * @return string
 */
function probo_portal_capability() {
	$tax = get_taxonomy( 'product_cat' );

	return $tax ? $tax->cap->edit_terms : 'manage_categories';
}

/**
 * Users offered in the category's picker.
 *
 * Capped, because a multi-select stops being usable somewhere in the hundreds
 * and rendering every account of a large site would be worse than saying so.
 * The picker warns when it is truncated; raise the cap with the filter, or
 * replace the picker if a shop really outgrows it.
 *
 * @return array<int, string> User id => label, ordered by display name.
 */
function probo_portal_user_choices() {
	$users = get_users(
		array(
			'number'  => probo_portal_user_choice_limit(),
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => array( 'ID', 'display_name', 'user_email' ),
		)
	);

	$choices = array();

	foreach ( $users as $user ) {
		$choices[ (int) $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
	}

	return $choices;
}

/**
 * How many users the category picker lists.
 *
 * @return int
 */
function probo_portal_user_choice_limit() {
	/**
	 * Filters how many users the category picker will list.
	 *
	 * @param int $limit Maximum number of users.
	 */
	return max( 1, (int) apply_filters( 'probo_portal_user_choice_limit', 500 ) );
}

/**
 * Render the picker's inner controls, shared by the add and edit forms.
 *
 * @param int[] $selected User ids currently stored on the category.
 */
function probo_portal_field_control( $selected ) {
	$choices = probo_portal_user_choices();
	$limit   = probo_portal_user_choice_limit();
	?>
	<?php wp_nonce_field( 'probo_portal_save', 'probo_portal_nonce' ); ?>

	<select name="probo_portal_users[]" multiple size="10" style="min-width:320px;max-width:100%;">
		<?php foreach ( $choices as $user_id => $label ) : ?>
			<option value="<?php echo esc_attr( $user_id ); ?>" <?php selected( in_array( $user_id, $selected, true ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="probo_portal_users_submitted" value="1" />
	<p class="description">
		<?php esc_html_e( 'Hold ctrl (⌘ on Mac) to select several. Select nobody to show this category to every logged-in customer — that is the standard assortment. Select users to make it a campaign only they see, together with its subcategories and the products in it.', 'probo-connect-theme' ); ?>
	</p>

	<?php if ( count( $choices ) >= $limit ) : ?>
		<p class="description">
			<strong>
				<?php
				printf(
					/* translators: %d: number of users listed. */
					esc_html__( 'Only the first %d users are listed here.', 'probo-connect-theme' ),
					(int) $limit
				);
				?>
			</strong>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * The picker on the "add category" form.
 */
function probo_portal_add_field() {
	if ( ! current_user_can( probo_portal_capability() ) ) {
		return;
	}
	?>
	<div class="form-field">
		<label><?php esc_html_e( 'Visible to', 'probo-connect-theme' ); ?></label>
		<?php probo_portal_field_control( array() ); ?>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'probo_portal_add_field' );

/**
 * The picker on the category's own edit screen, above the callouts.
 *
 * @param WP_Term $term Product category being edited.
 */
function probo_portal_edit_field( $term ) {
	if ( ! $term instanceof WP_Term || ! current_user_can( probo_portal_capability() ) ) {
		return;
	}

	$selected = wp_parse_id_list( (array) get_term_meta( $term->term_id, probo_portal_meta_key(), true ) );
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Visible to', 'probo-connect-theme' ); ?></label>
		</th>
		<td><?php probo_portal_field_control( $selected ); ?></td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'probo_portal_edit_field', 15 );

/**
 * Save the category's user list.
 *
 * Neither field is present on a Quick Edit or a programmatic term update, so
 * those leave the stored list alone rather than clearing it.
 *
 * @param int $term_id Product category id being saved.
 */
function probo_portal_save_term( $term_id ) {
	if ( ! isset( $_POST['probo_portal_users_submitted'] ) ) {
		return;
	}

	if ( ! current_user_can( probo_portal_capability() ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	check_admin_referer( 'probo_portal_save', 'probo_portal_nonce' );

	$users = isset( $_POST['probo_portal_users'] )
		? wp_parse_id_list( wp_unslash( $_POST['probo_portal_users'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_parse_id_list() casts to ints.
		: array();

	// Only users that still exist, so a deleted account cannot leave a
	// category restricted to nobody.
	$users = array_values(
		array_unique(
			array_filter(
				$users,
				static function ( $user_id ) {
					return (bool) get_user_by( 'id', $user_id );
				}
			)
		)
	);

	if ( $users ) {
		update_term_meta( $term_id, probo_portal_meta_key(), $users );
	} else {
		delete_term_meta( $term_id, probo_portal_meta_key() );
	}

	probo_portal_flush();
}
add_action( 'edited_product_cat', 'probo_portal_save_term' );
add_action( 'created_product_cat', 'probo_portal_save_term' );

/**
 * Mark restricted categories in the category list table.
 *
 * @param string[] $columns Existing columns.
 * @return string[]
 */
function probo_portal_term_columns( $columns ) {
	$columns['probo_portal'] = __( 'Visible to', 'probo-connect-theme' );

	return $columns;
}
add_filter( 'manage_edit-product_cat_columns', 'probo_portal_term_columns' );

/**
 * Fill the "Visible to" column.
 *
 * @param string $content Column content so far.
 * @param string $column  Column name.
 * @param int    $term_id Product category id.
 * @return string
 */
function probo_portal_term_column( $content, $column, $term_id ) {
	if ( 'probo_portal' !== $column ) {
		return $content;
	}

	$users = wp_parse_id_list( (array) get_term_meta( $term_id, probo_portal_meta_key(), true ) );

	if ( ! $users ) {
		return esc_html__( 'Everyone', 'probo-connect-theme' );
	}

	return esc_html(
		sprintf(
			/* translators: %d: number of customers. */
			_n( '%d customer', '%d customers', count( $users ), 'probo-connect-theme' ),
			count( $users )
		)
	);
}
add_filter( 'manage_product_cat_custom_column', 'probo_portal_term_column', 10, 3 );
