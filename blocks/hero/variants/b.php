<?php
/**
 * Hero B — editorial, large image.
 *
 * A text column beside a media column of one tall photo and two category tiles.
 * Where the media sits is the block's own choice (Rechts / Links / Geen), which
 * is the design's `heroMedia` prop: the prototype switched it with CSS custom
 * properties because it had no server to ask, the theme picks the classes here.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$media = (string) $attributes['heroMedia'];
$usps  = probo_parse_lines( $attributes['usps'], 1 );
$tiles = array_slice( probo_parse_lines( $attributes['tiles'], 3 ), 0, 2 );

if ( 'Geen' === $media ) {
	$grid      = 'lg:grid-cols-1';
	$text_pad  = 'lg:py-18';
	$text_sort = '';
} elseif ( 'Links' === $media ) {
	$grid      = 'lg:grid-cols-[1.08fr_0.92fr]';
	$text_pad  = 'lg:py-18 lg:pl-14';
	$text_sort = 'lg:order-2';
} else {
	$grid      = 'lg:grid-cols-[0.92fr_1.08fr]';
	$text_pad  = 'lg:py-18 lg:pr-14';
	$text_sort = 'lg:order-1';
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-surface' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid items-stretch lg:min-h-[540px] <?php echo esc_attr( $grid ); ?>">
		<div class="flex flex-col justify-center py-12 <?php echo esc_attr( $text_pad . ' ' . $text_sort ); ?>">
			<?php if ( $attributes['eyebrow'] ) : ?>
				<div class="rounded-pp pp-eyebrow mb-6.5 inline-flex items-center gap-2 self-start bg-accent-soft px-3.5 py-1.5 text-accent-ink">
					<?php echo esc_html( $attributes['eyebrow'] ); ?>
				</div>
			<?php endif; ?>

			<?php probo_hero_title( $attributes['title'], 'mb-5.5 text-[40px] leading-[0.98] font-extrabold tracking-[-0.035em] text-balance text-ink lg:text-[60px]' ); ?>

			<?php if ( $attributes['subtitle'] ) : ?>
				<p class="mb-8.5 max-w-[440px] text-lg leading-[1.55] text-pretty text-ink-3"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
			<?php endif; ?>

			<div class="mb-8 flex flex-wrap gap-3">
				<?php
				probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-secondary h-[54px] px-6.5 text-[15px]' );
				probo_hero_button( $attributes['secondaryLabel'], $attributes['secondaryUrl'], 'pp-btn-ghost h-[54px] px-6.5 text-[15px]' );
				?>
			</div>

			<?php if ( $usps ) : ?>
				<div class="flex flex-wrap gap-6.5 border-t border-line pt-6.5">
					<?php foreach ( $usps as $usp ) : ?>
						<div class="flex items-center gap-2.5">
							<span class="rounded-pp flex h-5.5 w-5.5 flex-none items-center justify-center bg-accent-soft text-xs text-accent-ink" aria-hidden="true">✓</span>
							<span class="text-[13px] font-semibold"><?php echo esc_html( $usp[0] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( 'Geen' !== $media ) : ?>
			<div class="grid grid-cols-[1.4fr_1fr] grid-rows-2 gap-3.5 py-8 <?php echo 'Links' === $media ? 'lg:order-1 lg:pr-2' : 'lg:order-2 lg:pl-2'; ?>">
				<div class="row-span-2 min-h-[260px]">
					<?php probo_hero_media( $attributes['imageId'], 'rounded-pp h-full w-full', __( 'hero photo', 'probo-connect-theme' ) ); ?>
				</div>

				<?php foreach ( $tiles as $index => $tile ) : ?>
					<?php
					$tone = 0 === $index
						? 'bg-accent text-accent-fg'
						: 'bg-white text-ink pp-card';
					?>
					<a class="rounded-pp flex min-h-0 flex-col justify-between p-4.5 no-underline <?php echo esc_attr( $tone ); ?>" href="<?php echo esc_url( $tile[1] ? $tile[1] : '#' ); ?>">
						<span class="pp-eyebrow <?php echo 0 === $index ? 'opacity-80' : 'text-ink-4'; ?>"><?php esc_html_e( 'category', 'probo-connect-theme' ); ?></span>
						<span class="text-[19px] font-extrabold tracking-[-0.02em]"><?php echo esc_html( $tile[0] ); ?> →</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
