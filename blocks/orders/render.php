<?php
/**
 * Orders block.
 *
 * The customer's own order list — from the ThemeHeader Portal design handoff.
 * The card itself is probo_render_orders_table() in inc/orders.php, so a
 * template can draw the same table without going through a block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$probo_orders = is_user_logged_in()
	? probo_customer_orders( null, (int) $attributes['count'] )
	: array();

// Same as the Mijn producten block: in the editor this renders as whoever is
// editing the page, and their own order history says nothing about what a
// customer will see. A customer holds neither REST_REQUEST nor edit_posts.
$probo_is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' );

$probo_all_url = '';

if ( ! empty( $attributes['showAll'] ) && function_exists( 'wc_get_account_endpoint_url' ) ) {
	$probo_all_url = wc_get_account_endpoint_url( 'orders' );
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'py-12 lg:py-14' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<?php
		if ( ! is_user_logged_in() ) {
			probo_login_required_prompt(
				__( 'Log in to see your orders.', 'probo-connect-theme' ),
				(string) get_permalink()
			);
		} elseif ( $probo_orders ) {
			probo_render_orders_table(
				$probo_orders,
				array(
					'heading'  => (string) $attributes['heading'],
					'all_url'  => $probo_all_url,
					'all_text' => (string) $attributes['allText'],
				)
			);
		} elseif ( $probo_is_preview ) {
			?>
			<div class="rounded-pp bg-surface px-6 py-5.5">
				<p class="text-[15px] text-ink-3">
					<?php esc_html_e( 'Every customer sees their own orders here, newest first, with a track & trace link once the order has a status page. You are seeing this note because your own account has no orders; it does not appear on the page itself.', 'probo-connect-theme' ); ?>
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
						: __( 'You have not placed an order yet.', 'probo-connect-theme' )
				);
				?>
			</p>
			<?php
		}
		?>
	</div>
</section>
