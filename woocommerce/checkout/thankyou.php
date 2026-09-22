<?php
/**
 * Thank-you page — the plugin's own template, inside the theme's container.
 *
 * Overrides woocommerce/templates/checkout/thankyou.php.
 *
 * The order-received page is the checkout page with an endpoint on it, and
 * page.php prints checkout pages bare because the checkout form brings its own
 * full-width layout. The thank-you markup brings none, so it ran flush against
 * the edge of #pp-content. The container is added here rather than in page.php
 * so it holds whatever template renders the page — a child theme's page.php
 * included.
 *
 * The markup itself is not copied: WooCommerce's template is included as is, so
 * the overview, payment instructions and order details keep following the
 * plugin. theme.css styles them (see "Order details").
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Probo_Connect
 * @version 11.2.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

$probo_thankyou_template = WC()->plugin_path() . '/templates/checkout/thankyou.php';
?>
<div class="pp-container pp-order-received py-12">
	<div class="mx-auto max-w-[880px]">
		<h1 class="mb-8 text-3xl font-extrabold tracking-[-0.035em] lg:text-4xl"><?php echo esc_html( WC()->query->get_endpoint_title( 'order-received' ) ); ?></h1>
		<?php
		if ( is_readable( $probo_thankyou_template ) ) {
			include $probo_thankyou_template;
		}
		?>
	</div>
</div>
