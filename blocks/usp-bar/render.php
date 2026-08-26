<?php
/**
 * USP bar block.
 *
 * Two shapes for the same four trust signals: the hairline-divided bar that
 * sits directly under the hero, and the card strip ("Kaarten") the design uses
 * as a standalone block further down a page, where a bare row would read as
 * part of whatever is above it.
 *
 * Each item takes an optional third field: the glyph in its icon tile. Items
 * written before that field existed simply have none, and fall back to the
 * check mark the bar has always drawn.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$items = probo_parse_lines( $attributes['items'], 3 );

if ( ! $items ) {
	return;
}

$cards = 'Kaarten' === $attributes['style'];

if ( $cards ) :
	?>
	<section <?php echo probo_block_wrapper( $attributes, 'bg-surface py-11' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
		<div class="mx-auto grid max-w-[1120px] gap-5 px-6 sm:grid-cols-2 lg:grid-cols-4 lg:px-10">
			<?php foreach ( $items as $item ) : ?>
				<div class="pp-card bg-white p-6">
					<span class="rounded-pp mb-4 flex h-10 w-10 items-center justify-center bg-accent-soft text-lg text-accent-ink" aria-hidden="true">
						<?php echo esc_html( $item[2] ? $item[2] : '✓' ); ?>
					</span>
					<div class="text-base font-extrabold tracking-[-0.01em]"><?php echo esc_html( $item[0] ); ?></div>
					<?php if ( $item[1] ) : ?>
						<p class="mt-1.5 text-[13px] leading-[1.5] text-ink-3"><?php echo esc_html( $item[1] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return;
endif;
?>
<section <?php echo probo_block_wrapper( $attributes, 'border-b border-line' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid sm:grid-cols-2 lg:grid-cols-4">
		<?php foreach ( $items as $index => $item ) : ?>
			<div class="flex items-start gap-3.5 py-6.5 <?php echo 0 === $index ? 'lg:pr-6' : 'border-line px-0 sm:px-6 lg:border-l'; ?>">
				<span class="rounded-pp flex h-6.5 w-6.5 flex-none items-center justify-center bg-accent-soft text-sm text-accent-ink" aria-hidden="true">
					<?php echo esc_html( $item[2] ? $item[2] : '✓' ); ?>
				</span>
				<div>
					<div class="text-sm font-bold"><?php echo esc_html( $item[0] ); ?></div>
					<?php if ( $item[1] ) : ?>
						<div class="mt-1 text-[13px] text-ink-3"><?php echo esc_html( $item[1] ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
