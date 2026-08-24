<?php
/**
 * Product archive — the category page from the design.
 *
 * Overrides woocommerce/templates/archive-product.php. All of the stock hooks
 * are preserved; only the layout around them is the theme's.
 *
 * @package Probo_Connect
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 */
do_action( 'woocommerce_before_main_content' );

$probo_term        = is_product_taxonomy() ? get_queried_object() : null;
$probo_description = $probo_term ? term_description( $probo_term ) : '';
?>

<main class="pb-16">
	<div class="pp-container pt-6"><?php probo_breadcrumb(); ?></div>

	<div class="pp-container grid gap-14 pt-5.5 pb-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
		<div>
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="mb-3.5 text-4xl font-extrabold tracking-[-0.035em] lg:text-5xl"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>

			<?php if ( $probo_description ) : ?>
				<div class="max-w-[560px] text-[17px] leading-relaxed text-ink-2"><?php echo wp_kses_post( $probo_description ); ?></div>
			<?php endif; ?>
		</div>

		<?php if ( is_active_sidebar( 'shop-tip' ) ) : ?>
			<div class="rounded-pp bg-surface px-6 py-5.5">
				<?php dynamic_sidebar( 'shop-tip' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php
	// The category's own callouts, filled in on the term itself. The same text
	// follows this category into any Categorietegels block that lists it.
	//
	// Each callout picks its own template, and the template's directory decides
	// where it lands: 'category_top' full-width above the products, 'grid' as a
	// grid item between them (once, after the product number it names), and
	// 'category_bottom' full-width below them.
	$probo_callouts = $probo_term ? probo_category_callouts( $probo_term ) : array();

	$probo_by_placement = static function ( $placement ) use ( $probo_callouts ) {
		return array_values(
			array_filter(
				$probo_callouts,
				static function ( $callout ) use ( $placement ) {
					return $placement === probo_callout_placement( $callout );
				}
			)
		);
	};

	$probo_top_callouts    = $probo_by_placement( 'category_top' );
	$probo_tile_callouts   = $probo_by_placement( 'grid' );
	$probo_bottom_callouts = $probo_by_placement( 'category_bottom' );

	if ( $probo_top_callouts ) :
		?>
		<div class="pp-container flex flex-col gap-4 pb-9">
			<?php foreach ( $probo_top_callouts as $probo_top_callout ) : ?>
				<?php probo_callout_render( $probo_top_callout ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php $probo_has_filters = is_active_sidebar( 'shop-filters' ); ?>

	<div class="border-t border-line">
		<div class="pp-container grid gap-10 <?php echo $probo_has_filters ? 'lg:grid-cols-[248px_1fr] lg:items-start' : ''; ?>">
			<?php if ( $probo_has_filters ) : ?>
				<aside class="pp-shop-filters border-line py-8 lg:border-r lg:pr-8 lg:pb-16">
					<div class="pp-eyebrow mb-4 text-ink-4"><?php esc_html_e( 'Filter', 'probo-connect' ); ?></div>
					<?php dynamic_sidebar( 'shop-filters' ); ?>
				</aside>
			<?php endif; ?>

			<div class="py-8 lg:pb-16">
				<?php if ( woocommerce_product_loop() ) : ?>
					<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
						<div class="text-[13px] font-medium text-ink-3"><?php woocommerce_result_count(); ?></div>
						<div class="flex items-center gap-2"><?php woocommerce_catalog_ordering(); ?></div>
					</div>

					<?php
					/**
					 * Hook: woocommerce_before_shop_loop.
					 *
					 * Result count and ordering are printed above by hand, so the stock
					 * callbacks for them are unhooked here rather than duplicated.
					 */
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
					do_action( 'woocommerce_before_shop_loop' );

					woocommerce_product_loop_start();

					if ( wc_get_loop_prop( 'total' ) ) {
						$probo_index = 0;
						$probo_total = (int) wc_get_loop_prop( 'total' );

						while ( have_posts() ) {
							the_post();

							/**
							 * Hook: woocommerce_shop_loop.
							 */
							do_action( 'woocommerce_shop_loop' );

							wc_get_template_part( 'content', 'product' );

							++$probo_index;

							// A sibling of the product tiles, so it lands in the same
							// grid cell rhythm. Each one appears exactly once, right
							// after the product number it names — not after the very
							// last product, where it would just be a band with extra
							// steps.
							foreach ( $probo_tile_callouts as $probo_tile_callout ) {
								if (
									$probo_index === $probo_tile_callout['interval']
									&& $probo_index < $probo_total
								) {
									echo '<li class="pp-callout-tile">';
									probo_callout_render( $probo_tile_callout );
									echo '</li>';
								}
							}
						}
					}

					woocommerce_product_loop_end();

					/**
					 * Hook: woocommerce_after_shop_loop.
					 */
					do_action( 'woocommerce_after_shop_loop' );
					?>
				<?php else : ?>
					<?php
					/**
					 * Hook: woocommerce_no_products_found.
					 */
					do_action( 'woocommerce_no_products_found' );
					?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( $probo_bottom_callouts ) : ?>
		<div class="border-t border-line">
			<div class="pp-container flex flex-col gap-4 py-9">
				<?php foreach ( $probo_bottom_callouts as $probo_bottom_callout ) : ?>
					<?php probo_callout_render( $probo_bottom_callout ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</main>

<?php
/**
 * Hook: woocommerce_after_main_content.
 */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
