<?php
/**
 * Tier prices block.
 *
 * A placement for the Probo Connect plugin's [connect_tiers] and
 * [connect_tiers_summary] shortcodes: the theme calculates nothing, it only
 * decides where the plugin's table goes and how it is framed. The table's own
 * look is in assets/css/src/theme.css, and the plugin's markup can be replaced
 * from probo-connect/tiers/ in the theme.
 *
 * Without a product id the shortcode uses the product of the current page. When
 * the plugin is inactive, or the product has no tiers, the block prints nothing
 * on the front end and a note in the editor.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$probo_summary = 'summary' === $attributes['view'];
$probo_tag     = $probo_summary ? 'connect_tiers_summary' : 'connect_tiers';
$probo_output  = '';

if ( shortcode_exists( $probo_tag ) ) {
	$probo_args = array( 'show_discount' => $attributes['showDiscount'] ? 'true' : 'false' );

	if ( $attributes['productId'] > 0 ) {
		$probo_args['product_id'] = (int) $attributes['productId'];
	}

	if ( $probo_summary && '' !== $attributes['apiCode'] ) {
		$probo_args['api_code'] = $attributes['apiCode'];
	} elseif ( ! $probo_summary && '' !== $attributes['group'] ) {
		$probo_args['group'] = $attributes['group'];
	}

	$probo_atts = '';

	foreach ( $probo_args as $probo_key => $probo_value ) {
		$probo_atts .= ' ' . $probo_key . '="' . esc_attr( $probo_value ) . '"';
	}

	$probo_output = trim( do_shortcode( '[' . $probo_tag . $probo_atts . ']' ) );
}

// In the editor an empty result would leave a block with nothing to select.
$probo_is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' );

if ( '' === $probo_output && ! $probo_is_preview ) {
	return;
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'py-8 lg:py-10' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<?php if ( $attributes['heading'] ) : ?>
			<h2 class="mb-5 text-2xl font-extrabold tracking-[-0.03em] lg:text-3xl"><?php echo esc_html( $attributes['heading'] ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== $probo_output ) : ?>
			<?php echo wp_kses_post( $probo_output ); ?>
		<?php else : ?>
			<p class="rounded-pp bg-surface px-5 py-4 text-sm text-ink-3">
				<?php esc_html_e( 'No tier prices to show: the Probo Connect plugin must be active and the product needs tier prices.', 'probo-connect-theme' ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
