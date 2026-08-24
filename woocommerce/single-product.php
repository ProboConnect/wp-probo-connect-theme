<?php
/**
 * Single product — gallery and summary, then the configurator band.
 *
 * Overrides woocommerce/templates/single-product.php. The add-to-cart form is
 * moved out of the summary column (unhooked in inc/woocommerce.php) and printed
 * inside the grey configurator band, where whatever Probo Connect renders picks
 * up the skin in assets/css/print-connect.css.
 *
 * @package Probo_Connect
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 */
do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) :
	the_post();
	global $product;

	if ( ! $product ) {
		$product = wc_get_product( get_the_ID() );
	}
	?>
	<main id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
		<?php
		/**
		 * Hook: woocommerce_before_single_product.
		 */
		do_action( 'woocommerce_before_single_product' );
		?>

		<div class="pp-container pt-6"><?php probo_breadcrumb(); ?></div>

		<div class="pp-container grid gap-14 pt-5 pb-11 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
			<?php // Positioned, so WooCommerce's absolutely placed sale flash anchors to the gallery instead of the page. ?>
			<div class="relative">
				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary.
				 *
				 * Renders the product gallery, including the theme-supported zoom,
				 * lightbox and thumbnail slider.
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>
			</div>

			<div class="summary entry-summary">
				<h1 class="mb-3 text-3xl leading-[1.02] font-extrabold tracking-[-0.035em] lg:text-[44px]"><?php the_title(); ?></h1>

				<?php if ( wc_review_ratings_enabled() && $product->get_review_count() ) : ?>
					<?php
					// Mirrors blocks/testimonials/render.php:46-49 — filled/empty stars from
					// the rounded average, kept aria-hidden because the numeric text beside
					// it already carries the meaning for screen readers.
					$pp_avg_rating = (float) $product->get_average_rating();
					$pp_filled     = max( 0, min( 5, (int) round( $pp_avg_rating ) ) );
					?>
					<div class="mb-6 flex items-center gap-3.5">
						<span class="font-mono text-[13px] font-medium text-accent-ink" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $pp_filled ) . str_repeat( '☆', 5 - $pp_filled ) ); ?></span>
						<span class="text-[13px] text-ink-3">
							<?php
							printf(
								/* translators: 1: average rating, 2: review count. */
								esc_html__( '%1$s / 5 · %2$s reviews', 'probo-connect' ),
								esc_html( number_format_i18n( (float) $product->get_average_rating(), 1 ) ),
								esc_html( number_format_i18n( $product->get_review_count() ) )
							);
							?>
						</span>
					</div>
				<?php endif; ?>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="mb-6.5 text-[17px] leading-relaxed text-ink-2"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<?php
				$probo_usps = array_filter(
					array(
						probo_get( 'topbar_usp_1' ),
						probo_get( 'topbar_usp_2' ),
						probo_get( 'topbar_usp_3' ),
					)
				);

				if ( $probo_usps ) :
					?>
					<div class="mb-6 flex flex-col gap-3 border-y border-line py-5.5">
						<?php foreach ( $probo_usps as $probo_usp ) : ?>
							<span class="flex items-center gap-3 text-[15px] text-ink">
								<span class="text-accent-ink" aria-hidden="true">✓</span><?php echo esc_html( $probo_usp ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( probo_is_configurable_product( $product ) ) : ?>
					<?php // Jumps to the band below, which is where the configurator lives. ?>
					<a class="pp-btn-accent mb-6 w-full" href="#pp-configurator">
						<?php esc_html_e( 'Configure your product', 'probo-connect' ); ?>
						<span aria-hidden="true">→</span>
					</a>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'shop-tip' ) ) : ?>
					<div class="rounded-pp bg-surface px-5.5 py-5"><?php dynamic_sidebar( 'shop-tip' ); ?></div>
				<?php endif; ?>

				<?php if ( ! probo_is_configurable_product( $product ) && ( $product->is_purchasable() || $product->get_price_html() ) ) : ?>
					<?php
					/**
					 * A product without a configurator keeps WooCommerce's own price
					 * and add-to-cart form, right here in the summary column. Both
					 * callbacks are unhooked in inc/woocommerce.php because they are
					 * meaningless for a configurable product, so they are called back
					 * by hand rather than left out.
					 */
					?>
					<div class="mt-6.5">
						<?php woocommerce_template_single_price(); ?>
						<?php woocommerce_template_single_add_to_cart(); ?>
					</div>
				<?php endif; ?>

				<?php
				/**
				 * Hook: woocommerce_single_product_summary.
				 *
				 * Title, price, add-to-cart and meta are unhooked in
				 * inc/woocommerce.php; anything a plugin adds here still runs.
				 */
				do_action( 'woocommerce_single_product_summary' );
				?>
			</div>
		</div>

		<?php if ( probo_is_configurable_product( $product ) ) : ?>
			<div id="pp-configurator" class="scroll-mt-24 border-t border-line bg-surface">
				<div class="pp-container py-11">
					<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
						<h2 class="text-2xl font-extrabold tracking-[-0.03em] lg:text-3xl">
							<?php esc_html_e( 'Configure your product', 'probo-connect' ); ?>
						</h2>
					</div>

					<?php
					probo_configurator_open();

					/**
					 * Hook: probo_configurator_band.
					 *
					 * Where Probo Connect's configurator is rendered — its callback is
					 * moved here from woocommerce_single_product_summary by
					 * probo_move_configurator() in inc/woocommerce.php, so the design's
					 * full-width band gets it instead of the summary column.
					 */
					do_action( 'probo_configurator_band' );

					/**
					 * WooCommerce's own add-to-cart template still runs, so every hook
					 * the plugin relies on stays intact for product types that use it.
					 */
					woocommerce_template_single_add_to_cart();

					probo_configurator_close();
					?>
				</div>
			</div>
		<?php endif; ?>

		<div class="pp-container py-14">
			<?php
			/**
			 * Hook: woocommerce_after_single_product_summary.
			 *
			 * Product tabs, upsells and related products.
			 */
			do_action( 'woocommerce_after_single_product_summary' );
			?>
		</div>

		<?php
		/**
		 * Hook: woocommerce_after_single_product.
		 */
		do_action( 'woocommerce_after_single_product' );
		?>
	</main>
	<?php
endwhile;

/**
 * Hook: woocommerce_after_main_content.
 */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
