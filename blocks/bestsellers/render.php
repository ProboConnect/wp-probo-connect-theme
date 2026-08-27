<?php
/**
 * Bestsellers block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$posts = probo_get_products( $attributes['source'], (int) $attributes['count'] );
?>
<section <?php echo probo_block_wrapper( $attributes, 'pt-16 lg:pt-18' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
			<?php if ( $attributes['heading'] ) : ?>
				<h2 class="text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]"><?php echo esc_html( $attributes['heading'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $attributes['meta'] ) : ?>
				<span class="font-mono text-xs font-medium tracking-[0.06em] text-ink-4"><?php echo esc_html( $attributes['meta'] ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $posts ) : ?>
			<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<?php
				foreach ( $posts as $post_object ) :
					$product = wc_get_product( $post_object );

					if ( ! $product ) {
						continue;
					}

					probo_product_card( $product, 'h-[200px]' );
				endforeach;
				?>
			</div>
		<?php else : ?>
			<p class="text-[15px] text-ink-3">
				<?php esc_html_e( 'No products found yet. As soon as there are products, they will appear here automatically.', 'probo-connect-theme' ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
