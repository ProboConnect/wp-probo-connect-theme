<?php
/**
 * Hero E — minimal, centred, light.
 *
 * One column, one action. The USP line under the button is the same list every
 * other variant draws, set inline instead of in a row of chips.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$usps = probo_parse_lines( $attributes['usps'], 1 );
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-surface-soft py-16 text-center lg:py-24' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="mx-auto max-w-[680px] px-6 lg:px-10">
		<?php if ( $attributes['eyebrow'] ) : ?>
			<div class="pp-eyebrow mb-5.5 tracking-[0.14em] text-accent-ink"><?php echo esc_html( $attributes['eyebrow'] ); ?></div>
		<?php endif; ?>

		<?php probo_hero_title( $attributes['title'], 'mb-6 text-[40px] leading-none font-extrabold tracking-[-0.035em] text-balance text-ink lg:text-[58px]' ); ?>

		<?php if ( $attributes['subtitle'] ) : ?>
			<p class="mx-auto mb-9 max-w-[520px] text-lg leading-[1.6] text-pretty text-ink-3"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
		<?php endif; ?>

		<?php probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-secondary h-14 px-8.5 text-base' ); ?>

		<?php if ( $usps ) : ?>
			<div class="mt-10 flex flex-wrap justify-center gap-7">
				<?php foreach ( $usps as $usp ) : ?>
					<span class="text-[13px] text-ink-3">✓ <?php echo esc_html( $usp[0] ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
