<?php
/**
 * "Zo werkt het" block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$steps = probo_parse_lines( $attributes['steps'], 3 );

if ( ! $steps ) {
	return;
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'py-16 lg:py-18' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<div class="rounded-pp bg-surface px-8 py-12 lg:px-12 lg:py-14">
			<?php if ( $attributes['heading'] ) : ?>
				<h2 class="mb-10 text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]">
					<?php echo esc_html( $attributes['heading'] ); ?>
				</h2>
			<?php endif; ?>

			<div class="grid gap-10 lg:grid-cols-3">
				<?php foreach ( $steps as $step ) : ?>
					<div>
						<div class="text-[64px] leading-none font-black tracking-[-0.05em] text-accent-ink"><?php echo esc_html( $step[0] ); ?></div>
						<div class="mt-3.5 mb-2 text-xl font-bold"><?php echo esc_html( $step[1] ?? '' ); ?></div>
						<p class="max-w-[320px] text-[15px] leading-relaxed text-ink-2"><?php echo esc_html( $step[2] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
