<?php
/**
 * The screens that edit who may see which product.
 *
 * Four of them, for the two rules in inc/visibility.php:
 *
 *   product      a "Customer access" tab in the product data box, plus a column
 *                in the products list table.
 *   customer     a "Product access" section on a customer's own profile screen,
 *                editing the same grants from the other end.
 *   category     a "Visible to" picker on the product category screen, plus a
 *                column in the categories list table.
 *
 * The product tab and the customer profile write the same `_probo_access_user`
 * rows, so there is one list readable from either screen.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/** How many restricted products the customer profile screen lists as checkboxes. */
const PROBO_VISIBILITY_PROFILE_LIMIT = 200;

/** How many users the category picker lists. */
const PROBO_VISIBILITY_USER_LIMIT = 500;

/**
 * The capability that guards editing either list.
 *
 * @return string
 */
function probo_visibility_manage_cap() {
	return 'edit_products';
}

/* ---------------------------------------------------------------------------
   Per product: the product's own edit screen.
--------------------------------------------------------------------------- */

/**
 * Add the "Customer access" tab to the product data box.
 *
 * @param array $tabs Product data tabs.
 * @return array
 */
function probo_visibility_product_tab( $tabs ) {
	$tabs['probo_access'] = array(
		'label'    => __( 'Customer access', 'probo-connect-theme' ),
		'target'   => 'probo_product_access_data',
		'class'    => array(),
		'priority' => 65,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'probo_visibility_product_tab' );

/**
 * Render that tab.
 *
 * The customer picker is WooCommerce's own `wc-customer-search` select, so it
 * searches the customer base over AJAX instead of printing every account into
 * the page, and it looks like every other customer field in the admin.
 */
function probo_visibility_product_panel() {
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
					'id'          => PROBO_PRODUCT_RESTRICTED_META,
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
add_action( 'woocommerce_product_data_panels', 'probo_visibility_product_panel' );

/**
 * Replace the customers listed on a product.
 *
 * Stored one meta row per customer — that is what makes "which products may this
 * customer see?" a single meta query on the other side.
 *
 * @param int   $product_id Product id.
 * @param int[] $user_ids   Customers to list.
 */
function probo_visibility_set_product_users( $product_id, $user_ids ) {
	$product_id = absint( $product_id );
	$user_ids   = array_values( array_unique( array_filter( array_map( 'absint', (array) $user_ids ) ) ) );
	$current    = probo_product_access_users( $product_id );

	foreach ( array_diff( $current, $user_ids ) as $user_id ) {
		delete_post_meta( $product_id, PROBO_PRODUCT_USER_META, $user_id );
	}

	foreach ( array_diff( $user_ids, $current ) as $user_id ) {
		add_post_meta( $product_id, PROBO_PRODUCT_USER_META, $user_id );
	}
}

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
function probo_visibility_save_product( $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC_Admin_Meta_Boxes::save_meta_boxes() checks woocommerce_meta_nonce before this hook runs.
	if ( ! isset( $_POST['probo_access_submitted'] ) || ! current_user_can( 'edit_product', $product_id ) ) {
		return;
	}

	$restricted = ! empty( $_POST[ PROBO_PRODUCT_RESTRICTED_META ] );
	$users      = isset( $_POST['probo_access_users'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['probo_access_users'] ) ) : array();
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $restricted ) {
		update_post_meta( $product_id, PROBO_PRODUCT_RESTRICTED_META, 'yes' );
	} else {
		delete_post_meta( $product_id, PROBO_PRODUCT_RESTRICTED_META );
	}

	probo_visibility_set_product_users( $product_id, $users );
	probo_visibility_flush();
}
add_action( 'woocommerce_process_product_meta', 'probo_visibility_save_product' );

/**
 * Flag restricted products in the products list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function probo_visibility_product_column( $columns ) {
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
add_filter( 'manage_edit-product_columns', 'probo_visibility_product_column', 20 );

/**
 * Draw that column.
 *
 * @param string $column     Column key.
 * @param int    $product_id Product id.
 */
function probo_visibility_product_column_content( $column, $product_id ) {
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
add_action( 'manage_product_posts_custom_column', 'probo_visibility_product_column_content', 20, 2 );

/**
 * Give that column room.
 *
 * WooCommerce hands out percentage widths to its own product columns that
 * already add up to the whole table, so an extra one is squeezed to nothing and
 * the header ends up spelled downwards, one letter per line.
 */
function probo_visibility_product_column_style() {
	$screen = get_current_screen();

	if ( $screen && 'product' === $screen->post_type ) {
		echo '<style>.wp-list-table .column-probo_access{width:11ch}</style>';
	}
}
add_action( 'admin_head-edit.php', 'probo_visibility_product_column_style' );

/* ---------------------------------------------------------------------------
   Per product, from the customer's side.
--------------------------------------------------------------------------- */

/**
 * The restricted products, titled and sorted, for the profile screen.
 *
 * Capped: past PROBO_VISIBILITY_PROFILE_LIMIT this stops being a form and starts
 * being a catalogue, and the product screen's customer search is the right tool
 * at that size.
 *
 * @return array<int, string> Product id => title.
 */
function probo_visibility_restricted_choices() {
	$ids = probo_restricted_product_ids();

	if ( ! $ids ) {
		return array();
	}

	$products = get_posts(
		array(
			'post_type'               => 'product',
			'post_status'             => 'any',
			'post__in'                => $ids,
			'posts_per_page'          => PROBO_VISIBILITY_PROFILE_LIMIT,
			'orderby'                 => 'title',
			'order'                   => 'ASC',
			'no_found_rows'           => true,
			'ignore_sticky_posts'     => true,
			'probo_visibility_bypass' => true,
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
function probo_visibility_profile_fields( $user ) {
	if ( ! current_user_can( probo_visibility_manage_cap() ) ) {
		return;
	}

	$choices = probo_visibility_restricted_choices();
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
add_action( 'show_user_profile', 'probo_visibility_profile_fields' );
add_action( 'edit_user_profile', 'probo_visibility_profile_fields' );

/**
 * Store what that section posted, onto the products themselves.
 *
 * Only the products the screen actually offered are touched, so a grant on a
 * product further down a truncated list survives a save here.
 *
 * @param int $user_id Customer being saved.
 */
function probo_visibility_save_profile( $user_id ) {
	$user_id = absint( $user_id );

	if ( ! isset( $_POST['probo_access_user_submitted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked immediately below.
		return;
	}

	if ( ! current_user_can( probo_visibility_manage_cap() ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	check_admin_referer( 'probo_product_access_user_' . $user_id, 'probo_product_access_nonce' );

	$checked = isset( $_POST['probo_access_products'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['probo_access_products'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked just above.

	foreach ( array_keys( probo_visibility_restricted_choices() ) as $product_id ) {
		$listed = in_array( $user_id, probo_product_access_users( $product_id ), true );
		$wanted = in_array( $product_id, $checked, true );

		if ( $wanted && ! $listed ) {
			add_post_meta( $product_id, PROBO_PRODUCT_USER_META, $user_id );
		} elseif ( ! $wanted && $listed ) {
			delete_post_meta( $product_id, PROBO_PRODUCT_USER_META, $user_id );
		}
	}

	probo_visibility_flush();
}
add_action( 'personal_options_update', 'probo_visibility_save_profile' );
add_action( 'edit_user_profile_update', 'probo_visibility_save_profile' );

/* ---------------------------------------------------------------------------
   Per category: the category's own edit screen.
--------------------------------------------------------------------------- */

/**
 * The capability that guards editing a category's user list.
 *
 * @return string
 */
function probo_visibility_term_cap() {
	$tax = get_taxonomy( 'product_cat' );

	return $tax ? $tax->cap->edit_terms : 'manage_categories';
}

/**
 * Render the picker's inner controls, shared by the add and edit forms.
 *
 * The user list is capped, because a multi-select stops being usable somewhere
 * in the hundreds and rendering every account of a large site would be worse
 * than saying so. The picker warns when it is truncated.
 *
 * Whoever is already on the category is fetched on top of that cap. The save
 * below replaces the stored list with what the form posted, so a selected user
 * who fell outside the first page would be dropped by a save that never meant
 * to touch them — and the merchant would have no way to see it happen.
 *
 * @param int[] $selected User ids currently stored on the category.
 */
function probo_visibility_term_control( $selected ) {
	$fields = array( 'ID', 'display_name', 'user_email' );
	$users  = get_users(
		array(
			'number'  => PROBO_VISIBILITY_USER_LIMIT,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => $fields,
		)
	);

	$truncated = count( $users ) >= PROBO_VISIBILITY_USER_LIMIT;
	$missing   = array_diff( $selected, wp_list_pluck( $users, 'ID' ) );

	if ( $missing ) {
		$users = array_merge(
			get_users(
				array(
					'include' => $missing,
					'orderby' => 'display_name',
					'order'   => 'ASC',
					'fields'  => $fields,
				)
			),
			$users
		);
	}
	?>
	<?php wp_nonce_field( 'probo_portal_save', 'probo_portal_nonce' ); ?>

	<select name="probo_portal_users[]" multiple size="10" style="min-width:320px;max-width:100%;">
		<?php foreach ( $users as $user ) : ?>
			<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( (int) $user->ID, $selected, true ) ); ?>>
				<?php echo esc_html( sprintf( '%s (%s)', $user->display_name, $user->user_email ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="probo_portal_users_submitted" value="1" />
	<p class="description">
		<?php esc_html_e( 'Hold ctrl (⌘ on Mac) to select several. Select nobody to show this category to every logged-in customer — that is the standard assortment. Select users to make it a campaign only they see, together with its subcategories and the products in it.', 'probo-connect-theme' ); ?>
	</p>

	<?php if ( $truncated ) : ?>
		<p class="description">
			<strong>
				<?php
				printf(
					/* translators: %d: number of users listed. */
					esc_html__( 'Only the first %d users are listed here, plus everyone already selected.', 'probo-connect-theme' ),
					(int) PROBO_VISIBILITY_USER_LIMIT
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
function probo_visibility_term_add_field() {
	if ( ! current_user_can( probo_visibility_term_cap() ) ) {
		return;
	}
	?>
	<div class="form-field">
		<label><?php esc_html_e( 'Visible to', 'probo-connect-theme' ); ?></label>
		<?php probo_visibility_term_control( array() ); ?>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'probo_visibility_term_add_field' );

/**
 * The picker on the category's own edit screen, above the callouts.
 *
 * @param WP_Term $term Product category being edited.
 */
function probo_visibility_term_edit_field( $term ) {
	if ( ! $term instanceof WP_Term || ! current_user_can( probo_visibility_term_cap() ) ) {
		return;
	}

	$selected = probo_category_access_users( $term->term_id );
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Visible to', 'probo-connect-theme' ); ?></label>
		</th>
		<td><?php probo_visibility_term_control( $selected ); ?></td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'probo_visibility_term_edit_field', 15 );

/**
 * Save the category's user list.
 *
 * Neither field is present on a Quick Edit or a programmatic term update, so
 * those leave the stored list alone rather than clearing it.
 *
 * @param int $term_id Product category id being saved.
 */
function probo_visibility_save_category( $term_id ) {
	if ( ! isset( $_POST['probo_portal_users_submitted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked immediately below.
		return;
	}

	if ( ! current_user_can( probo_visibility_term_cap() ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	check_admin_referer( 'probo_portal_save', 'probo_portal_nonce' );

	$users = isset( $_POST['probo_portal_users'] )
		? wp_parse_id_list( wp_unslash( $_POST['probo_portal_users'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_parse_id_list() casts to ints.
		: array();

	// Only users that still exist, so a deleted account cannot leave a category
	// restricted to nobody.
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
		update_term_meta( $term_id, PROBO_CATEGORY_USER_META, $users );
	} else {
		delete_term_meta( $term_id, PROBO_CATEGORY_USER_META );
	}

	probo_visibility_flush();
}
add_action( 'edited_product_cat', 'probo_visibility_save_category' );
add_action( 'created_product_cat', 'probo_visibility_save_category' );

/**
 * Mark restricted categories in the category list table.
 *
 * @param string[] $columns Existing columns.
 * @return string[]
 */
function probo_visibility_term_columns( $columns ) {
	$columns['probo_portal'] = __( 'Visible to', 'probo-connect-theme' );

	return $columns;
}
add_filter( 'manage_edit-product_cat_columns', 'probo_visibility_term_columns' );

/**
 * Fill the "Visible to" column.
 *
 * @param string $content Column content so far.
 * @param string $column  Column name.
 * @param int    $term_id Product category id.
 * @return string
 */
function probo_visibility_term_column( $content, $column, $term_id ) {
	if ( 'probo_portal' !== $column ) {
		return $content;
	}

	$users = probo_category_access_users( $term_id );

	if ( ! $users ) {
		// A subcategory of a campaign is restricted too, and reading "Everyone"
		// next to it is how a merchant ends up filing a bug against the shop.
		foreach ( get_ancestors( $term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
			if ( ! probo_category_access_users( $ancestor_id ) ) {
				continue;
			}

			$ancestor = get_term( $ancestor_id, 'product_cat' );

			return esc_html(
				sprintf(
					/* translators: %s: name of the parent category the restriction comes from. */
					__( 'Via %s', 'probo-connect-theme' ),
					$ancestor instanceof WP_Term ? $ancestor->name : ''
				)
			);
		}

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
add_filter( 'manage_product_cat_custom_column', 'probo_visibility_term_column', 10, 3 );
