<?php
/**
 * Hero G — split with review card, light.
 *
 * Text on the left, photo on the right with a review card overlapping its
 * lower-left corner. Below lg the card stops overlapping and sits under the
 * photo: a card hanging off the left edge of a full-width phone screen is a
 * horizontal scrollbar, not an accent.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-surface' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid items-center gap-14 lg:min-h-[520px] lg:grid-cols-2">
		<div class="py-12 lg:py-18">
			<?php if ( $attributes['eyebrow'] ) : ?>
				<div class="rounded-pp pp-eyebrow mb-6.5 inline-flex items-center gap-2 bg-accent-soft px-3.5 py-1.5 text-accent-ink">
					<?php echo esc_html( $attributes['eyebrow'] ); ?>
				</div>
			<?php endif; ?>

			<?php probo_hero_title( $attributes['title'], 'mb-5.5 text-[40px] leading-[0.98] font-extrabold tracking-[-0.035em] text-balance text-ink lg:text-[58px]' ); ?>

			<?php if ( $attributes['subtitle'] ) : ?>
				<p class="mb-8.5 max-w-[440px] text-lg leading-[1.55] text-pretty text-ink-3"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
			<?php endif; ?>

			<div class="flex flex-wrap gap-3">
				<?php
				probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-secondary h-[54px] px-6.5 text-[15px]' );
				probo_hero_button( $attributes['secondaryLabel'], $attributes['secondaryUrl'], 'pp-btn-ghost h-[54px] px-6.5 text-[15px]' );
				?>
			</div>
		</div>

		<div class="relative pb-12 lg:py-11">
			<?php probo_hero_media( $attributes['imageId'], 'rounded-pp h-[280px] w-full lg:h-full lg:min-h-[400px]', __( 'hero photo', 'probo-connect-theme' ) ); ?>

			<?php if ( $attributes['reviewQuote'] ) : ?>
				<div class="pp-card mt-[-40px] ml-4 max-w-[290px] bg-white p-5 shadow-[0_24px_48px_rgba(0,0,0,.14)] lg:absolute lg:bottom-19 lg:-left-6 lg:mt-0 lg:ml-0">
					<div class="text-[15px] font-bold tracking-[0.04em] text-accent-ink" aria-hidden="true">★★★★★</div>
					<p class="my-2.5 text-sm leading-[1.5] font-semibold text-pretty text-ink"><?php echo esc_html( $attributes['reviewQuote'] ); ?></p>
					<?php if ( $attributes['reviewAuthor'] ) : ?>
						<div class="text-xs text-ink-4"><?php echo esc_html( $attributes['reviewAuthor'] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
