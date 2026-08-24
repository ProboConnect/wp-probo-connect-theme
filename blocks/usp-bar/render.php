<?php
/**
 * USP bar block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$items = probo_parse_lines( $attributes['items'], 2 );

if ( ! $items ) {
	return;
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'border-b border-line' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid sm:grid-cols-2 lg:grid-cols-4">
		<?php foreach ( $items as $index => $item ) : ?>
			<div class="flex items-start gap-3.5 py-6.5 <?php echo 0 === $index ? 'lg:pr-6' : 'border-line px-0 sm:px-6 lg:border-l'; ?>">
				<span class="rounded-pp flex h-6.5 w-6.5 flex-none items-center justify-center bg-accent-soft text-sm text-accent-ink" aria-hidden="true">✓</span>
				<div>
					<div class="text-sm font-bold"><?php echo esc_html( $item[0] ); ?></div>
					<?php if ( isset( $item[1] ) && $item[1] ) : ?>
						<div class="mt-1 text-[13px] text-ink-3"><?php echo esc_html( $item[1] ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
