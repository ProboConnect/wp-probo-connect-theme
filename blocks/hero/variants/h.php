<?php
/**
 * Hero H — promotion, accent band.
 *
 * A campaign strip: the offer on the left, a countdown and one white CTA on the
 * right. The countdown is rendered from the block's end date in PHP, so a page
 * cached at the CDN still shows a number that was right when it was cached, and
 * assets/js/theme.js only keeps it ticking. Without an end date the tiles are
 * left out entirely rather than frozen at a made-up number.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$until     = trim( (string) $attributes['countdownUntil'] );
$timestamp = $until ? strtotime( $until ) : 0;
$remaining = $timestamp ? max( 0, $timestamp - time() ) : 0;

$countdown = $timestamp
	? array(
		array( (int) floor( $remaining / DAY_IN_SECONDS ), __( 'days', 'probo-connect-theme' ) ),
		array( (int) floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ), __( 'hours', 'probo-connect-theme' ) ),
		array( (int) floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ), __( 'min', 'probo-connect-theme' ) ),
	)
	: array();
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-accent text-accent-fg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid items-center gap-12 py-14 lg:grid-cols-[1.3fr_0.7fr] lg:py-16">
		<div>
			<?php if ( $attributes['eyebrow'] ) : ?>
				<div class="rounded-pp pp-eyebrow mb-6 inline-flex items-center gap-2 bg-white/[0.16] px-3.5 py-1.5">
					<?php echo esc_html( $attributes['eyebrow'] ); ?>
				</div>
			<?php endif; ?>

			<?php probo_hero_title( $attributes['title'], 'mb-4.5 text-[36px] leading-none font-extrabold tracking-[-0.035em] text-balance lg:text-[56px]' ); ?>

			<?php if ( $attributes['subtitle'] ) : ?>
				<p class="max-w-[520px] text-lg leading-[1.55] text-pretty text-white/85"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="flex flex-col items-start gap-3.5">
			<?php if ( $countdown ) : ?>
				<div class="flex gap-2.5" data-pp-countdown="<?php echo esc_attr( gmdate( 'c', $timestamp ) ); ?>">
					<?php foreach ( $countdown as $index => $unit ) : ?>
						<div class="rounded-pp min-w-16 bg-black/[0.22] px-4 py-3 text-center">
							<div class="text-[28px] leading-none font-extrabold" data-pp-countdown-value="<?php echo esc_attr( (string) $index ); ?>">
								<?php echo esc_html( str_pad( (string) $unit[0], 2, '0', STR_PAD_LEFT ) ); ?>
							</div>
							<div class="pp-eyebrow mt-1 opacity-75"><?php echo esc_html( $unit[1] ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn h-14 bg-white px-7.5 text-base font-extrabold text-accent-ink hover:bg-ink hover:text-white' ); ?>
		</div>
	</div>
</section>
