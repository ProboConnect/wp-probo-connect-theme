<?php
/**
 * My products block.
 *
 * The products opened up for whoever is logged in — the portal's own page, next
 * to the shop's "everything you may buy". The list and the tiles come from
 * inc/product-access.php, the same two functions [probo_my_products] uses, so
 * the block is the placement and nothing else.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$probo_products = is_user_logged_in()
	? probo_customer_product_ids(
		null,
		array(
			'limit'   => $attributes['count'] > 0 ? (int) $attributes['count'] : -1,
			'orderby' => 'date' === $attributes['orderby'] ? 'date' : 'title',
			'order'   => 'date' === $attributes['orderby'] ? 'DESC' : 'ASC',
		)
	)
	: array();

// In the editor this renders as whoever is editing the page, and a shop manager
// has no products of their own — so the preview would be an empty state that
// says nothing about what a customer will actually see. The note replaces it
// there and only there: a customer never holds edit_posts.
$probo_is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' );
?>
<section <?php echo probo_block_wrapper( $attributes, 'py-12 lg:py-14' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<?php if ( $attributes['heading'] || $attributes['intro'] ) : ?>
			<div class="mb-7">
				<?php if ( $attributes['heading'] ) : ?>
					<h2 class="mb-3 text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]"><?php echo esc_html( $attributes['heading'] ); ?></h2>
				<?php endif; ?>

				<?php if ( $attributes['intro'] ) : ?>
					<p class="max-w-[620px] text-[15px] text-ink-3"><?php echo esc_html( $attributes['intro'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		if ( ! is_user_logged_in() ) {
			probo_login_required_prompt(
				__( 'Log in to see the products set up for your account.', 'probo-connect-theme' ),
				(string) get_permalink()
			);
		} elseif ( $probo_products ) {
			probo_render_product_grid( $probo_products );
		} elseif ( $probo_is_preview ) {
			?>
			<div class="rounded-pp bg-surface px-6 py-5.5">
				<p class="text-[15px] text-ink-3">
					<?php esc_html_e( 'Every customer sees their own products here — the ones limited to them under Customer access on the product. You are seeing this note because your own account has none; it does not appear on the page itself.', 'probo-connect-theme' ); ?>
				</p>
			</div>
			<?php
		} else {
			?>
			<p class="text-[15px] text-ink-3">
				<?php
				echo esc_html(
					$attributes['empty']
						? $attributes['empty']
						: __( 'No products have been set up for your account yet. Get in touch and we will add them.', 'probo-connect-theme' )
				);
				?>
			</p>
			<?php
		}
		?>
	</div>
</section>
