<?php
/**
 * Hero D — showroom, category tiles.
 *
 * A heading row and four category tiles: the fastest route from the front page
 * to a configurator. The tiles come from the block's own list rather than from
 * product_cat, because a hero picks four to lead with — a shop with forty
 * categories does not want the four WooCommerce happens to return first.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$tiles = probo_parse_lines( $attributes['tiles'], 3 );
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-surface py-14 lg:py-14' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<div class="mb-8 flex flex-wrap items-end justify-between gap-6">
			<div>
				<?php probo_hero_title( $attributes['title'], 'mb-2.5 text-[34px] leading-none font-extrabold tracking-[-0.03em] text-balance text-ink lg:text-[48px]' ); ?>

				<?php if ( $attributes['subtitle'] ) : ?>
					<p class="max-w-[460px] text-base text-ink-3"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
				<?php endif; ?>
			</div>

			<?php probo_hero_button( $attributes['linkLabel'], $attributes['linkUrl'], 'pp-btn-ghost h-12 flex-none px-5.5' ); ?>
		</div>

		<?php if ( $tiles ) : ?>
			<div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
				<?php foreach ( $tiles as $index => $tile ) : ?>
					<?php
					// The last tile is the accent one in the design: four dark
					// photo tiles in a row read as a wall, one in colour gives
					// the eye somewhere to land.
					$is_accent = count( $tiles ) - 1 === $index;
					?>
					<a
						class="rounded-pp flex min-h-[260px] flex-col justify-end p-5.5 text-white no-underline <?php echo $is_accent ? 'bg-accent' : 'pp-stripe-dark'; ?>"
						href="<?php echo esc_url( $tile[1] ? $tile[1] : '#' ); ?>"
					>
						<span class="pp-eyebrow <?php echo $is_accent ? 'opacity-80' : 'opacity-70'; ?>"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="mt-2 text-[22px] font-extrabold tracking-[-0.02em] text-white"><?php echo esc_html( $tile[0] ); ?></span>
						<?php if ( $tile[2] ) : ?>
							<span class="mt-1 text-[13px] <?php echo $is_accent ? 'text-white/80' : 'text-white/70'; ?>"><?php echo esc_html( $tile[2] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
