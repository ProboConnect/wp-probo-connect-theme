<?php
/**
 * Theme blocks.
 *
 * The homepage is assembled from these blocks rather than hardcoded in
 * front-page.php, so sections can be reordered, removed or reused on other
 * pages from the editor.
 *
 * Each block renders through PHP (render.php) and previews in the editor with
 * ServerSideRender, which keeps the editor canvas byte-for-byte identical to
 * the front end. The editor scripts are plain browser JS against the wp.*
 * globals — no JSX, so the theme needs no JavaScript build step to work.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block folders shipped by the theme.
 *
 * @return string[]
 */
function probo_block_names() {
	return array( 'hero', 'usp-bar', 'category-grid', 'bento-grid', 'testimonials', 'logo-reel', 'bestsellers', 'how-it-works', 'contact', 'faq' );
}

/**
 * Register the blocks.
 *
 * The shared repeater control is registered first, so a block that lists it in
 * its index.asset.php dependencies gets it loaded before its own script runs.
 */
function probo_register_blocks() {
	wp_register_script(
		'pp-block-repeater',
		get_template_directory_uri() . '/blocks/shared/repeater.js',
		array( 'wp-element', 'wp-components', 'wp-i18n' ),
		probo_asset_version( '/blocks/shared/repeater.js' ),
		true
	);

	foreach ( probo_block_names() as $name ) {
		$dir = get_template_directory() . '/blocks/' . $name;

		if ( file_exists( $dir . '/block.json' ) ) {
			register_block_type( $dir );
		}
	}
}
add_action( 'init', 'probo_register_blocks' );

/**
 * A dedicated inserter category, so the theme's blocks sit together.
 *
 * @param array $categories Existing categories.
 * @return array
 */
function probo_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'probo',
			'title' => __( 'Probo Connect', 'probo-connect' ),
			'icon'  => null,
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'probo_block_categories' );

/**
 * The default homepage composition, as block markup.
 *
 * Used by the registered pattern, by starter content, and by front-page.php as
 * a fallback when the front page has no content of its own.
 *
 * @return string
 */
function probo_homepage_blocks() {
	return "<!-- wp:probo/hero /-->\n"
		. "<!-- wp:probo/usp-bar /-->\n"
		. "<!-- wp:probo/category-grid /-->\n"
		. "<!-- wp:probo/bestsellers /-->\n"
		. "<!-- wp:probo/how-it-works /-->";
}

/**
 * Register the homepage pattern.
 */
function probo_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern(
		'probo/homepage',
		array(
			'title'       => __( 'Probo Connect homepage', 'probo-connect' ),
			'description' => __( 'Hero, USP bar, categories, bestsellers, and “How it works”.', 'probo-connect' ),
			'categories'  => array( 'featured' ),
			'content'     => probo_homepage_blocks(),
		)
	);
}
add_action( 'init', 'probo_register_patterns' );

/**
 * Starter content: a fresh install opens on the designed homepage.
 */
function probo_starter_content() {
	add_theme_support(
		'starter-content',
		array(
			'posts'   => array(
				'home' => array(
					'post_type'    => 'page',
					'post_title'   => _x( 'Home', 'Theme starter content', 'probo-connect' ),
					'post_content' => probo_homepage_blocks(),
				),
			),
			'options' => array(
				'show_on_front'  => 'page',
				'page_on_front'  => '{{home}}',
			),
		)
	);
}
add_action( 'after_setup_theme', 'probo_starter_content', 20 );

/**
 * Shared helper: the wrapper attributes for a block's outer section.
 *
 * @param array  $attributes Block attributes.
 * @param string $classes    Tailwind classes for the section.
 * @param string $style      Inline style string, already escaped.
 * @return string
 */
function probo_block_wrapper( $attributes, $classes, $style = '' ) {
	$args = array( 'class' => $classes );

	if ( $style ) {
		$args['style'] = $style;
	}

	return get_block_wrapper_attributes( $args );
}

/**
 * Products for the bestsellers block.
 *
 * @param string $source One of best_selling, featured, recent.
 * @param int    $count  How many products.
 * @return WP_Post[]
 */
function probo_get_products( $source, $count ) {
	if ( ! post_type_exists( 'product' ) ) {
		return array();
	}

	$args = array(
		'post_type'           => 'product',
		'posts_per_page'      => max( 1, (int) $count ),
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( 'best_selling' === $source ) {
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the standard WooCommerce best-sellers ordering.
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
	} elseif ( 'featured' === $source ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- unavoidable for featured products.
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
			),
		);
	}

	return get_posts( $args );
}

/**
 * Parse a "one item per line, fields separated by |" attribute.
 *
 * Repeatable content is stored as plain text rather than as a nested attribute
 * array, which keeps it readable in the post content and diffable. The editor
 * side is not a textarea: blocks/shared/repeater.js turns this same format into
 * per-field inputs with add, remove and reorder.
 *
 * @param string $raw    Raw attribute value.
 * @param int    $fields Expected number of fields per line.
 * @return array<int, string[]> One array of trimmed fields per non-empty line.
 */
function probo_parse_lines( $raw, $fields = 2 ) {
	$rows = array();

	foreach ( preg_split( '/\R/', (string) $raw ) as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		$parts = array_map( 'trim', explode( '|', $line ) );
		$rows[] = array_pad( array_slice( $parts, 0, $fields ), $fields, '' );
	}

	return $rows;
}

/**
 * Categories for the category-grid block.
 *
 * @param string $slugs Comma-separated product category slugs; empty means
 *                      "top-level categories, most populated first".
 * @param int    $count Maximum number of terms.
 * @return WP_Term[]
 */
function probo_get_category_terms( $slugs, $count ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$slugs = array_filter( array_map( 'trim', explode( ',', (string) $slugs ) ) );

	$args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => max( 1, (int) $count ),
	);

	if ( $slugs ) {
		$args['slug']    = $slugs;
		$args['orderby'] = 'slug__in';
	} else {
		$args['parent']  = 0;
		$args['orderby'] = 'count';
		$args['order']   = 'DESC';
	}

	$terms = get_terms( $args );

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * The hero variants, keyed by the letter the design handoff names them by.
 *
 * The letters are the shared vocabulary between the design file and this
 * theme — renaming them to something descriptive would only break that link.
 * Each key has a matching blocks/hero/variants/{letter}.php.
 *
 * @return array<string, string> Letter => editor label.
 */
function probo_hero_variants() {
	return array(
		'A' => __( 'A — Search hero, dark', 'probo-connect' ),
		'B' => __( 'B — Editorial, large image', 'probo-connect' ),
		'C' => __( 'C — Full-bleed image, centred', 'probo-connect' ),
		'D' => __( 'D — Showroom, category tiles', 'probo-connect' ),
		'E' => __( 'E — Minimal, centred, light', 'probo-connect' ),
		'F' => __( 'F — USP rail, dark, B2B', 'probo-connect' ),
		'G' => __( 'G — Split with review card', 'probo-connect' ),
		'H' => __( 'H — Promotion, accent', 'probo-connect' ),
		'I' => __( 'I — Search hero, light with tags', 'probo-connect' ),
		'J' => __( 'J — Showreel, image with play', 'probo-connect' ),
	);
}

/**
 * The hero's photo, or the striped placeholder that stands in for it.
 *
 * Every hero in the design shows photography that has not been shot yet, so a
 * variant that has no image still has to draw something the size of the one it
 * is waiting for — otherwise the whole band collapses in the editor.
 *
 * @param int    $image_id Attachment id, 0 for none.
 * @param string $classes  Classes for the image or the placeholder alike.
 * @param string $label    Placeholder caption.
 * @param string $style    Inline style, already escaped.
 */
function probo_hero_media( $image_id, $classes, $label, $style = '' ) {
	$attr = $style ? ' style="' . esc_attr( $style ) . '"' : '';

	if ( $image_id ) {
		echo wp_get_attachment_image(
			(int) $image_id,
			'full',
			false,
			array(
				'class' => $classes . ' object-cover',
				'style' => $style,
				'alt'   => '',
			)
		);

		return;
	}

	printf(
		'<div class="pp-placeholder-dark %s"%s>%s</div>',
		esc_attr( $classes ),
		$attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		esc_html( $label )
	);
}

/**
 * One hero button.
 *
 * A hero button without a link is still drawn: the design's own prototype has
 * no destinations either, and a half-finished front page should show the shape
 * it is going to have. Without a URL it renders as a <span>, so it cannot be
 * tabbed to or clicked.
 *
 * @param string $label   Button text; empty prints nothing.
 * @param string $url     Destination.
 * @param string $classes Classes.
 */
function probo_hero_button( $label, $url, $classes ) {
	if ( ! $label ) {
		return;
	}

	if ( $url ) {
		printf(
			'<a class="%s" href="%s">%s</a>',
			esc_attr( $classes ),
			esc_url( $url ),
			esc_html( $label )
		);

		return;
	}

	printf( '<span class="%s">%s</span>', esc_attr( $classes ), esc_html( $label ) );
}

/**
 * The hero's title, with its line breaks kept.
 *
 * @param string  $title   Raw title.
 * @param string  $classes Classes.
 */
function probo_hero_title( $title, $classes ) {
	if ( ! $title ) {
		return;
	}

	printf( '<h1 class="%s">%s</h1>', esc_attr( $classes ), nl2br( esc_html( $title ) ) );
}
