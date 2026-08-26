<?php
/**
 * Hero F — USP rail, dark, B2B.
 *
 * Split band: the pitch on the left, three numbers on the right. The numbers
 * are the block's own `stats` list, because they are claims a shop has to be
 * able to keep true without a deploy.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$stats = probo_parse_lines( $attributes['stats'], 2 );
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-secondary text-secondary-fg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid items-stretch lg:min-h-[500px] lg:grid-cols-[1.1fr_0.9fr]">
		<div class="flex flex-col justify-center py-12 lg:py-18 lg:pr-12">
			<?php if ( $attributes['eyebrow'] ) : ?>
				<div class="rounded-pp pp-eyebrow mb-6.5 inline-flex items-center gap-2 self-start bg-white/10 px-3.5 py-1.5">
					<?php echo esc_html( $attributes['eyebrow'] ); ?>
				</div>
			<?php endif; ?>

			<?php probo_hero_title( $attributes['title'], 'mb-5.5 text-[40px] leading-[0.98] font-extrabold tracking-[-0.035em] text-balance lg:text-[60px]' ); ?>

			<?php if ( $attributes['subtitle'] ) : ?>
				<p class="mb-8.5 max-w-[460px] text-lg leading-[1.55] text-pretty text-white/[0.72]"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
			<?php endif; ?>

			<div class="flex flex-wrap gap-3">
				<?php
				probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-accent h-[54px] px-7 text-[15px]' );
				probo_hero_button( $attributes['secondaryLabel'], $attributes['secondaryUrl'], 'pp-btn h-[54px] border border-white/[0.28] px-7 text-[15px] text-white hover:bg-white hover:text-ink' );
				?>
			</div>
		</div>

		<?php if ( $stats ) : ?>
			<div class="flex flex-col justify-center border-white/[0.12] lg:border-l">
				<?php foreach ( $stats as $index => $stat ) : ?>
					<div class="py-7 <?php echo count( $stats ) - 1 === $index ? '' : 'border-b border-white/[0.12]'; ?> lg:pl-12">
						<div class="text-[34px] font-extrabold tracking-[-0.02em]"><?php echo esc_html( $stat[0] ); ?></div>
						<div class="mt-0.5 text-[13px] text-white/60"><?php echo esc_html( $stat[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
