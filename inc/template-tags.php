<?php
/**
 * Template tags shared across the theme.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site logo, in a dark or a light variant.
 *
 * Core only stores one custom logo, so the light variant comes from the
 * theme's own "Logo (licht)" setting and falls back to the core logo, which in
 * turn falls back to a text lockup built from the site title — "Probo Connect"
 * with everything from the first dot onwards in the accent colour, exactly as
 * the design draws it.
 *
 * @param string $variant   'dark' or 'light'.
 * @param string $size_class Tailwind classes for the text lockup size.
 */
function probo_logo( $variant = 'dark', $size_class = 'text-[23px]' ) {
	$light_logo  = probo_get( 'logo_light' );
	$custom_logo = get_theme_mod( 'custom_logo' );
	$src         = '';

	if ( 'light' === $variant && $light_logo ) {
		$src = $light_logo;
	} elseif ( $custom_logo ) {
		$src = wp_get_attachment_image_url( $custom_logo, 'full' );
	}

	$name = get_bloginfo( 'name' );
	$dot  = strpos( $name, '.' );

	// The light lockup takes its colour from whatever surface it sits on (the
	// dark bar, the footer) via currentColor. The base `a { color: accent-ink }`
	// rule would otherwise repaint the anchor and break that inheritance — which
	// on a light accent means a near-black wordmark on a dark bar — so the
	// pp-logo--light hook lets the anchor inherit its container's colour instead.
	$anchor_class = 'pp-logo flex items-center gap-2.5 no-underline';

	if ( 'light' === $variant ) {
		$anchor_class .= ' pp-logo--light';
	}

	echo '<a class="' . esc_attr( $anchor_class ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';

	if ( $src ) {
		printf(
			'<img class="h-10 w-auto max-w-[200px] object-contain" src="%s" alt="%s" />',
			esc_url( $src ),
			esc_attr( $name )
		);
	} elseif ( false !== $dot ) {
		printf(
			'<span class="font-title %s font-extrabold tracking-[-0.03em] %s">%s<span class="text-accent-ink">%s</span></span>',
			esc_attr( $size_class ),
			'light' === $variant ? 'text-current' : 'text-ink',
			esc_html( substr( $name, 0, $dot ) ),
			esc_html( substr( $name, $dot ) )
		);
	} else {
		printf(
			'<span class="font-title %s font-extrabold tracking-[-0.03em] %s">%s</span>',
			esc_attr( $size_class ),
			'light' === $variant ? 'text-current' : 'text-ink',
			esc_html( $name )
		);
	}

	echo '</a>';
}

/**
 * Number of items currently in the cart, or 0 without WooCommerce.
 *
 * @return int
 */
function probo_cart_count() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Cart URL, or the home URL without WooCommerce.
 *
 * @return string
 */
function probo_cart_url() {
	return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
}

/**
 * Account URL, or the login URL without WooCommerce.
 *
 * @return string
 */
function probo_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );

		if ( $url ) {
			return $url;
		}
	}

	return wp_login_url();
}

/**
 * Label for the account link. Both point at the same page, so the label is the
 * only thing that tells a signed-in customer they are not being asked to log in
 * again.
 *
 * @return string
 */
function probo_account_link_text() {
	return is_user_logged_in()
		? __( 'Dashboard', 'probo-connect-theme' )
		: __( 'Log in', 'probo-connect-theme' );
}

/**
 * The theme's search field: flush input with an accent submit button.
 *
 * @param string $size 'header', 'hero', or 'compact' (the borderless 44px field
 *                     on Variant B's dark bar).
 */
function probo_search_form( $size = 'header' ) {
	$is_hero    = 'hero' === $size;
	$is_compact = 'compact' === $size;

	if ( $is_compact ) {
		$height   = 'h-11';
		$border   = '';
		$in_pad   = 'px-4';
		$in_size  = 'text-sm';
		$btn_pad  = 'px-[22px] text-sm';
	} elseif ( $is_hero ) {
		$height   = 'h-[60px]';
		$border   = '';
		$in_pad   = 'px-5';
		$in_size  = 'text-base';
		$btn_pad  = 'px-8 text-[15px]';
	} else {
		$height   = 'h-[50px]';
		$border   = 'border-2 border-secondary-line';
		$in_pad   = 'px-5';
		$in_size  = 'text-[15px]';
		$btn_pad  = 'px-6.5 text-sm';
	}

	static $instance = 0;
	$uid = 'pp-search-' . $size . '-' . ++$instance;
	?>
	<form role="search" method="get" class="rounded-pp flex w-full overflow-hidden bg-white <?php echo esc_attr( $height . ' ' . $border ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="sr-only" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Search', 'probo-connect-theme' ); ?></label>
		<input
			id="<?php echo esc_attr( $uid ); ?>"
			class="min-w-0 flex-1 overflow-hidden border-0 bg-transparent text-ink text-ellipsis whitespace-nowrap outline-none <?php echo esc_attr( $in_pad . ' ' . $in_size ); ?>"
			type="search"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php echo esc_attr( probo_get( 'search_placeholder' ) ); ?>"
		/>
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<input type="hidden" name="post_type" value="product" />
		<?php endif; ?>
		<button class="bg-accent font-bold whitespace-nowrap text-accent-fg hover:bg-ink hover:text-white <?php echo esc_attr( $btn_pad ); ?>" type="submit"><?php esc_html_e( 'Search', 'probo-connect-theme' ); ?></button>
	</form>
	<?php
}

/**
 * Breadcrumb trail in the design's mono style.
 *
 * Uses WooCommerce's breadcrumb when it is available so shop hierarchy is
 * correct, and falls back to a Home / Title pair elsewhere.
 */
function probo_breadcrumb() {
	echo '<nav class="text-[13px] text-ink-3" aria-label="' . esc_attr__( 'Breadcrumb', 'probo-connect-theme' ) . '">';

	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		woocommerce_breadcrumb(
			array(
				'delimiter'   => ' / ',
				'wrap_before' => '',
				'wrap_after'  => '',
				'before'      => '',
				'after'       => '',
			)
		);
	} else {
		printf(
			'<a class="text-ink-4 no-underline" href="%s">%s</a> / <span class="text-ink">%s</span>',
			esc_url( home_url( '/' ) ),
			esc_html__( 'Home', 'probo-connect-theme' ),
			esc_html( wp_get_document_title() )
		);
	}

	echo '</nav>';
}

/**
 * The "Configureer nu →" call to action that replaced prices on tiles.
 *
 * Prices are shown only where they really come out of the plugin: the
 * configurator, the cart and the checkout.
 */
function probo_configure_cta() {
	echo '<div class="mt-3.5 text-sm font-bold text-accent-ink">' . esc_html__( 'Configure now →', 'probo-connect-theme' ) . '</div>';
}

/**
 * One-line spec summary under a product title, in mono.
 *
 * Prefers the product's short description, then its attribute summary.
 *
 * @param WC_Product|null $product Product object.
 * @return string
 */
function probo_product_spec_line( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$short = wp_strip_all_tags( $product->get_short_description() );

	if ( $short ) {
		return wp_trim_words( $short, 8, '' );
	}

	$parts = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible() ) {
			continue;
		}

		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
			: $attribute->get_options();

		if ( $values ) {
			$parts[] = $values[0];
		}

		if ( count( $parts ) >= 2 ) {
			break;
		}
	}

	return implode( ' · ', $parts );
}

/**
 * Badge text for a product tile, e.g. "Meest gekozen" or "Aanbieding".
 *
 * Featured products get the design's accent badge; on-sale products get the
 * dark one. Shops that want different wording can filter this.
 *
 * The sale badge says "Aanbieding", the same word WooCommerce puts on the
 * product page itself. It used to read "Budget", which claimed something the
 * price does not: a product marked down from €200 to €180 is on offer, not a
 * budget line — and the tile and the product page contradicted each other.
 *
 * @param WC_Product|null $product Product object.
 * @return array{label: string, tone: string}|null
 */
function probo_product_badge( $product = null ) {
	$badge = null;

	if ( $product instanceof WC_Product ) {
		if ( $product->is_featured() ) {
			$badge = array( 'label' => __( 'Most popular', 'probo-connect-theme' ), 'tone' => 'accent' );
		} elseif ( $product->is_on_sale() ) {
			$badge = array( 'label' => __( 'On sale', 'probo-connect-theme' ), 'tone' => 'secondary' );
		}
	}

	/**
	 * Filters the badge shown on a product tile.
	 *
	 * @param array|null      $badge   Label and tone, or null for no badge.
	 * @param WC_Product|null $product Product object.
	 */
	return apply_filters( 'probo_product_badge', $badge, $product );
}

/**
 * Tailwind classes for a callout's colour treatment.
 *
 * Both tones take their text colour from a contrast-guarded token, so a light
 * brand colour does not end up as white text on yellow.
 *
 * @param string $tone 'Accent' or 'Secondary'.
 * @return string
 */
function probo_callout_tone_classes( $tone ) {
	return 'Secondary' === $tone ? 'bg-secondary text-secondary-fg' : 'bg-accent text-accent-fg';
}

/**
 * Placeholder tile used wherever real photography is not in place yet.
 *
 * @param string $label   Text inside the placeholder.
 * @param string $classes Extra Tailwind classes (height, radius, …).
 */
function probo_placeholder( $label, $classes = 'h-[190px]' ) {
	printf(
		'<div class="pp-placeholder %s">%s</div>',
		esc_attr( $classes ),
		esc_html( $label )
	);
}

/**
 * Product categories the theme leaves out of automatic listings.
 *
 * WooCommerce's default category — "Uncategorized" unless renamed — is where
 * products land when nobody picked a category. It is a bucket, not a section of
 * the shop, so it has no place in the navigation.
 *
 * @return int[] Term ids.
 */
function probo_excluded_category_ids() {
	$excluded = array();
	$default  = (int) get_option( 'default_product_cat' );

	if ( $default ) {
		$excluded[] = $default;
	}

	/**
	 * Filters the product categories left out of the fallback menu.
	 *
	 * @param int[] $excluded Term ids.
	 */
	return array_map( 'intval', (array) apply_filters( 'probo_excluded_category_ids', $excluded ) );
}

/**
 * Products to list under a category in the fallback menu.
 *
 * @param WP_Term $term Product category.
 * @return WP_Post[]
 */
function probo_menu_products( $term ) {
	if ( ! post_type_exists( 'product' ) ) {
		return array();
	}

	/**
	 * Filters how many products are listed per category in the flyout.
	 *
	 * @param int     $count Number of products.
	 * @param WP_Term $term  Product category.
	 */
	$count = (int) apply_filters( 'probo_menu_products_per_category', 6, $term );

	if ( $count < 1 ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'           => 'product',
			'posts_per_page'      => $count,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the menu is the only place this runs.
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => true,
				),
			),
		)
	);
}

/**
 * Resolves a product category's link to a plain string.
 *
 * get_term_link() can return a WP_Error (an orphaned or malformed term); every
 * caller wants a usable href, so the fallback lives here once instead of at
 * each read site.
 *
 * @param WP_Term $term Product category.
 * @return string Absolute URL.
 */
function probo_menu_fallback_term_url( $term ) {
	$link = get_term_link( $term );

	return is_wp_error( $link ) ? home_url( '/' ) : (string) $link;
}

/**
 * Builds the fallback nav's data: product categories, two levels deep, with
 * the products under each subcategory.
 *
 * One get_terms() call for the whole product_cat tree, grouped into a
 * parent => children map, rather than a nested get_terms() per top-level
 * term. This shop's category tree is small and finite, so fetching it whole
 * is cheaper than the 1 + N queries it replaces — and the caller caches the
 * result (see probo_primary_menu_fallback()), so the cost is paid once per
 * day rather than once per request. `exclude` is applied on this single
 * query, so an excluded category is left out at every level — it cannot
 * reappear as someone else's child the way a parent-only exclude would allow.
 *
 * Every node has a stable shape: a top-level item always carries `term_id`,
 * `label`, `url` (a string, never a WP_Error) and `children` (an array,
 * possibly empty); a child group always carries `label`, `url` and `items`
 * (an array, possibly empty) of `label`/`url` product links.
 *
 * @return array[] Menu items. Empty when product_cat does not exist or has
 *                  no (non-excluded) top-level terms.
 */
function probo_build_menu_fallback_items() {
	$items = array();

	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $items;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'exclude'    => probo_excluded_category_ids(),
		)
	);

	if ( is_wp_error( $terms ) ) {
		return $items;
	}

	// Group by parent once, instead of re-querying children per top-level term.
	$by_parent = array();

	foreach ( $terms as $term ) {
		$by_parent[ $term->parent ][] = $term;
	}

	$parents = array_slice( isset( $by_parent[0] ) ? $by_parent[0] : array(), 0, 6 );

	foreach ( $parents as $term ) {
		// Only subcategories are listed; products with no subcategory of
		// their own do not get a column, so the panel only ever shows a
		// category -> subcategory -> product hierarchy.
		$children = array_slice( isset( $by_parent[ $term->term_id ] ) ? $by_parent[ $term->term_id ] : array(), 0, 12 );

		// The panel is built as groups, not a flat list: a subcategory is a
		// column heading with its own products underneath it. That is what
		// makes a wide flyout scannable — you read the heading, then the
		// products belonging to it.
		$groups = array();

		foreach ( $children as $child ) {
			$groups[] = array(
				'label' => $child->name,
				'url'   => probo_menu_fallback_term_url( $child ),
				'items' => array_map(
					static function ( $product ) {
						return array(
							'label' => get_the_title( $product ),
							'url'   => (string) get_permalink( $product ),
						);
					},
					probo_menu_products( $child )
				),
			);
		}

		$items[] = array(
			'term_id'  => $term->term_id,
			'label'    => $term->name,
			'url'      => probo_menu_fallback_term_url( $term ),
			'children' => $groups,
		);
	}

	return $items;
}

/**
 * Primary-menu fallback: product categories, so a fresh install still has a nav.
 */
function probo_primary_menu_fallback() {
	// The assembled data does not depend on the current request, so it is
	// cached; which item is "current" does, so that stays out of the cache
	// and is decided fresh below, on every render.
	$key   = 'probo_menu_fallback_' . wp_cache_get_last_changed( 'terms' ) . '_' . wp_cache_get_last_changed( 'posts' );
	$items = get_transient( $key );

	if ( false === $items ) {
		$items = probo_build_menu_fallback_items();

		set_transient( $key, $items, DAY_IN_SECONDS );
	}

	if ( ! $items ) {
		$items = array(
			array(
				'label'    => __( 'All products', 'probo-connect-theme' ),
				'url'      => home_url( '/' ),
				'children' => array(),
			),
		);
	}

	$current_term = is_tax( 'product_cat' ) ? get_queried_object_id() : 0;

	echo '<ul class="pp-nav-menu">';

	foreach ( $items as $item ) {
		$children = $item['children'];
		$classes  = array();

		if ( $current_term && isset( $item['term_id'] ) && $current_term === $item['term_id'] ) {
			$classes[] = 'current-menu-item';
		}

		if ( $children ) {
			// The same class wp_nav_menu() puts on a parent item, so the flyout
			// CSS and script do not need to know which of the two produced it.
			$classes[] = 'menu-item-has-children';
		}

		printf(
			'<li class="%s"><a href="%s">%s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);

		if ( $children ) {
			echo '<ul class="sub-menu">';

			foreach ( $children as $child ) {
				echo '<li class="pp-flyout-group">';

				printf(
					'<a class="pp-flyout-heading" href="%s">%s</a>',
					esc_url( $child['url'] ),
					esc_html( $child['label'] )
				);

				if ( $child['items'] ) {
					echo '<ul class="pp-flyout-links">';

					foreach ( $child['items'] as $link ) {
						printf(
							'<li><a href="%s">%s</a></li>',
							esc_url( $link['url'] ),
							esc_html( $link['label'] )
						);
					}

					echo '</ul>';
				}

				echo '</li>';
			}

			echo '</ul>';
		}

		echo '</li>';
	}

	echo '</ul>';
}

/**
 * A product tile, as used on the homepage, the category grid and the archive.
 *
 * Deliberately price-free: tiles end in "Configureer nu →" because the real
 * price only exists once the configurator has been filled in.
 *
 * @param WC_Product|null $product      Product object.
 * @param string          $image_height Tailwind height class for the image area.
 */
function probo_product_card( $product = null, $image_height = 'h-[190px]' ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$badge = probo_product_badge( $product );
	$specs = probo_product_spec_line( $product );
	?>
	<a class="pp-card block text-ink no-underline transition-shadow hover:shadow-lg" href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<div class="relative">
			<?php
			$image = $product->get_image_id()
				? wp_get_attachment_image(
					$product->get_image_id(),
					'woocommerce_thumbnail',
					false,
					array( 'class' => 'w-full object-cover ' . $image_height )
				)
				: '';

			if ( $image ) {
				echo wp_kses_post( $image );
			} else {
				probo_placeholder( __( 'product photo', 'probo-connect-theme' ), $image_height );
			}

			if ( $badge ) :
				?>
				<span class="rounded-pp font-mono absolute top-3 left-3 px-2.5 py-1.5 text-[10px] tracking-[0.06em] <?php echo 'accent' === $badge['tone'] ? 'bg-accent text-accent-fg' : 'bg-secondary text-secondary-fg'; ?>">
					<?php echo esc_html( $badge['label'] ); ?>
				</span>
			<?php endif; ?>
		</div>

		<div class="border-t border-line p-4.5">
			<div class="text-base font-bold"><?php echo esc_html( $product->get_name() ); ?></div>

			<?php if ( $specs ) : ?>
				<div class="font-mono mt-1.5 text-xs leading-relaxed font-medium text-ink-4"><?php echo esc_html( $specs ); ?></div>
			<?php endif; ?>

			<?php probo_configure_cta(); ?>
		</div>
	</a>
	<?php
}

/**
 * The checkout's own header: logo, progress, phone number.
 *
 * Everything that pulls out of the flow is gone here — no search, no
 * navigation, no account links, no cart button. What is left is where you are,
 * and who to call when it goes wrong.
 *
 * The step indicator renders the server's idea of the current step;
 * assets/js/checkout-steps.js keeps it in sync as the customer moves.
 */
function probo_checkout_header() {
	$steps   = probo_checkout_steps();
	$current = probo_checkout_initial_step();
	$phone   = probo_get( 'checkout_phone' );
	?>
	<header class="border-b border-line bg-white text-ink">
		<div class="pp-container flex flex-wrap items-center gap-x-6 gap-y-4 py-5">
			<?php probo_logo(); ?>

			<ol class="pp-checkout-progress" data-pp-progress>
				<?php foreach ( $steps as $number => $step ) : ?>
					<li
						class="pp-checkout-progress-step"
						data-pp-progress-step="<?php echo esc_attr( (string) $number ); ?>"
						data-state="<?php echo esc_attr( $number < $current ? 'done' : ( $number === $current ? 'current' : 'todo' ) ); ?>"
					>
						<span class="pp-checkout-progress-badge" aria-hidden="true"><?php echo esc_html( (string) $number ); ?></span>
						<span><?php echo esc_html( $step['short'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( $phone ) : ?>
				<a class="ml-auto text-sm font-semibold text-ink-3 no-underline hover:text-ink" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
					<?php
					printf(
						/* translators: %s: phone number. */
						esc_html__( 'Need help? %s', 'probo-connect-theme' ),
						'<span data-pp-partial="checkout_phone">' . esc_html( $phone ) . '</span>'
					);
					?>
				</a>
			<?php endif; ?>
		</div>
	</header>
	<?php
}

/**
 * The reassurance line under the checkout steps.
 *
 * The checkout header drops the USP top bar, so the same three lines are shown
 * once, at the bottom of the column, where they answer "is this the right shop"
 * rather than competing with the step that is open.
 */
function probo_checkout_reassurance() {
	$usps = array_filter( array_map( 'probo_get', array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3' ) ) );

	if ( ! $usps ) {
		return;
	}
	?>
	<div class="mt-6.5 flex flex-wrap gap-x-6.5 gap-y-2 text-[13px] text-ink-3">
		<?php foreach ( $usps as $usp ) : ?>
			<span class="flex items-center gap-2">
				<span aria-hidden="true">✓</span><?php echo esc_html( $usp ); ?>
			</span>
		<?php endforeach; ?>
	</div>
	<?php
}
