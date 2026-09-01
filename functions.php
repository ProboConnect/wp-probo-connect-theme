<?php
/**
 * Probo Connect theme bootstrap.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

define( 'PROBO_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/color.php';
require_once get_template_directory() . '/inc/settings.php';
require_once get_template_directory() . '/inc/dynamic-css.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/category-callout.php';
require_once get_template_directory() . '/inc/blocks.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/product-access.php';

/**
 * Theme supports, menus and image sizes.
 */
function probo_setup() {
	load_theme_textdomain( 'probo-connect-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 40,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
			'header-text' => array( 'site-title' ),
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'probo-connect-theme' ),
			'topbar'  => __( 'Top bar (right)', 'probo-connect-theme' ),
			'legal'   => __( 'Footer — legal', 'probo-connect-theme' ),
		)
	);

	// No add_editor_style() for theme.css: probo_enqueue_block_assets() already
	// loads it into the editor canvas, with the Customizer tokens inlined right
	// after it. Registering it as an editor style as well injected a second copy
	// further down the canvas, and its static :root defaults then overrode those
	// tokens — the editor drew the stock blue accent while the front end was
	// yellow.
}
add_action( 'after_setup_theme', 'probo_setup' );

/**
 * Footer link columns are widget areas so the shop can edit them without code.
 */
function probo_widgets_init() {
	foreach ( array(
		// The brand column under the logo. Left empty it falls back to the
		// Customizer's intro text, so an existing site keeps what it had until
		// someone drops a widget in here.
		'footer-intro' => __( 'Footer brand column (below the logo)', 'probo-connect-theme' ),
		'footer-1'     => __( 'Footer column 1 (Products)', 'probo-connect-theme' ),
		'footer-2'     => __( 'Footer column 2 (Service)', 'probo-connect-theme' ),
		'footer-3'     => __( 'Footer column 3 (Business)', 'probo-connect-theme' ),
		// The category page's filter column and its "twijfel je over de maat?"
		// tip card, both editable as widgets.
		'shop-filters' => __( 'Shop filters', 'probo-connect-theme' ),
		'shop-tip'     => __( 'Shop tip block', 'probo-connect-theme' ),
		// The "Zakelijk account →" link at the end of the primary nav bar. Left
		// empty it falls back to that link, so an existing site keeps what it had.
		'nav-account'  => __( 'Primary navigation: account link', 'probo-connect-theme' ),
	) as $id => $name ) {
		$is_shop   = str_starts_with( $id, 'shop-' );
		$is_nav    = str_starts_with( $id, 'nav-' );
		$is_column = in_array( $id, array( 'footer-1', 'footer-2', 'footer-3' ), true );

		register_sidebar(
			array(
				'id'            => $id,
				// The nav bar has no room for a stacked, titled column, so its
				// widget sits inline with no wrapper margin and no title markup.
				'before_widget' => $is_nav ? '<div class="lg:ml-auto">' : '<div class="mb-6">',
				'after_widget'  => '</div>',
				'name'          => $name,
				// Footer column headings are accent eyebrows; the shop sidebar's
				// are plain bold labels, as the category page draws them; the nav
				// bar has no room for a title at all; the footer columns' heading
				// is printed by the template itself (footer.php), always, so it
				// does not disappear if a widget's own title field is left blank.
				'before_title'  => ( $is_nav || $is_column ) ? '' : ( $is_shop ? '<div class="mb-3 text-sm font-bold text-ink">' : '<div class="pp-eyebrow mb-4.5 text-footer-fg">' ),
				'after_title'   => ( $is_nav || $is_column ) ? '' : '</div>',
			)
		);
	}
}
add_action( 'widgets_init', 'probo_widgets_init' );

/**
 * Front-end styles and scripts.
 */
function probo_enqueue_assets() {
	$dir = get_template_directory();

	wp_enqueue_style( 'pp-fonts', probo_fonts_url(), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts is versioned by its own URL.

	wp_enqueue_style(
		'pp-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'pp-fonts' ),
		probo_asset_version( '/assets/css/theme.css' )
	);

	// The theme's own style.css carries only the theme header, but WordPress
	// expects it to be registered under the stylesheet handle.
	wp_enqueue_style( 'pp-style', get_stylesheet_uri(), array( 'pp-theme' ), PROBO_VERSION );

	if ( file_exists( $dir . '/assets/css/print-connect.css' ) && probo_is_configurator_context() ) {
		wp_enqueue_style(
			'print-connect',
			get_template_directory_uri() . '/assets/css/print-connect.css',
			array( 'pp-theme' ),
			probo_asset_version( '/assets/css/print-connect.css' )
		);
	}

	wp_enqueue_script(
		'pp-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		probo_asset_version( '/assets/js/theme.js' ),
		true
	);

	// The accordion only exists on the checkout itself, so it is not loaded on
	// the order-received page or anywhere else in the shop.
	if ( function_exists( 'probo_is_checkout_flow' ) && probo_is_checkout_flow() ) {
		wp_enqueue_script(
			'pp-checkout-steps',
			get_template_directory_uri() . '/assets/js/checkout-steps.js',
			array(),
			probo_asset_version( '/assets/js/checkout-steps.js' ),
			true
		);
	}

	// Threaded comments need core's script to move the reply form instead of
	// jumping the page to the bottom.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'probo_enqueue_assets' );

/**
 * Editor canvas gets the same compiled stylesheet as the front end.
 */
function probo_enqueue_block_assets() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_style( 'pp-fonts', probo_fonts_url(), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- see above.
	wp_enqueue_style(
		'pp-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'pp-fonts' ),
		probo_asset_version( '/assets/css/theme.css' )
	);
}
add_action( 'enqueue_block_assets', 'probo_enqueue_block_assets' );

/**
 * File-mtime cache buster, so a rebuilt stylesheet is picked up immediately.
 *
 * @param string $relative_path Path relative to the theme root, with leading slash.
 * @return string
 */
function probo_asset_version( $relative_path ) {
	$file = get_template_directory() . $relative_path;

	return file_exists( $file ) ? (string) filemtime( $file ) : PROBO_VERSION;
}

/**
 * Whether the current request can contain the Probo Connect configurator.
 *
 * Without WooCommerce the configurator provably cannot exist, so this must
 * return false rather than fall back to a broader guard.
 *
 * @return bool
 */
function probo_is_configurator_context() {
	return function_exists( 'is_product' ) && ( is_product() || is_cart() || is_checkout() );
}

/**
 * Body classes that templates and the configurator skin hang off.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function probo_body_class( $classes ) {
	$classes[] = 'font-body';
	$classes[] = 'text-ink';
	$classes[] = 'bg-white';

	// WooCommerce guards its own button colours — the purple #7f54b3 and its
	// grey secondary — with :where(body:not(.woocommerce-block-theme-has-button-styles)).
	// That class is the plugin's supported opt-out for themes that style buttons
	// themselves, and it is the only one that works here: woocommerce.css is
	// unlayered, so the theme's own button rules in @layer components lose to it
	// no matter how specific they are.
	$classes[] = 'woocommerce-block-theme-has-button-styles';

	return $classes;
}
add_filter( 'body_class', 'probo_body_class' );
