<?php
/**
 * Category tiles block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$terms = probo_get_category_terms( $attributes['slugs'], (int) $attributes['count'] );

// The design alternates four tile treatments; keep cycling through them so any
// number of categories still reads as the same rhythm.
$tones = array(
	array( 'bg-accent', 'text-accent-fg' ),
	array( 'bg-secondary', 'text-ink-3' ),
	array( 'bg-surface-2', 'text-ink-5' ),
	array( 'bg-accent-soft', 'text-accent-ink' ),
);

$shop_url = $attributes['linkUrl'];

if ( ! $shop_url && function_exists( 'wc_get_page_permalink' ) ) {
	$shop_url = wc_get_page_permalink( 'shop' );
}

// Two kinds of callout can appear in this grid, both drawn by
// probo_callout_tile() so they cannot drift apart:
//
// 1. the block's own, filled in on this block instance;
// 2. a category's own — possibly several — filled in on the term under
//    Producten → Callouts, which follow that category into every grid that
//    lists it.
$callout       = ! empty( $attributes['showCallout'] ) && $attributes['calloutTitle']
	? array(
		'title' => $attributes['calloutTitle'],
		'text'  => $attributes['calloutText'],
		'cta'   => $attributes['calloutCta'],
		'url'   => $attributes['calloutUrl'] ? $attributes['calloutUrl'] : $shop_url,
		'tone'  => $attributes['calloutTone'],
	)
	: null;
$position      = (string) $attributes['calloutPosition'];
$callout_first = 'Begin' === $position;
$interval      = 'Interval' === $position ? max( 1, (int) $attributes['calloutInterval'] ) : 0;
$term_callouts = ! empty( $attributes['showTermCallouts'] );
?>
<section <?php echo probo_block_wrapper( $attributes, 'pt-16 lg:pt-18' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
			<?php if ( $attributes['heading'] ) : ?>
				<h2 class="text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]"><?php echo esc_html( $attributes['heading'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $attributes['linkText'] && $shop_url ) : ?>
				<a class="text-sm font-bold no-underline" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html( $attributes['linkText'] ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( $terms || $callout ) : ?>
			<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<?php
				if ( $callout && $callout_first ) {
					probo_callout_tile( $callout );
				}

				foreach ( $terms as $index => $term ) :
					$tone      = $tones[ $index % count( $tones ) ];
					$thumb_id  = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
					$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
					$term_link = get_term_link( $term );
					?>
					<a class="pp-card block text-ink no-underline transition-shadow hover:shadow-lg" href="<?php echo esc_url( is_wp_error( $term_link ) ? home_url( '/' ) : $term_link ); ?>">
						<?php if ( $thumb_url ) : ?>
							<img class="h-[150px] w-full object-cover" src="<?php echo esc_url( $thumb_url ); ?>" alt="" />
						<?php else : ?>
							<div class="pp-eyebrow flex h-[150px] items-end p-4 <?php echo esc_attr( $tone[0] . ' ' . $tone[1] ); ?>">
								<?php esc_html_e( 'tile visual', 'probo-connect' ); ?>
							</div>
						<?php endif; ?>

						<div class="p-4">
							<div class="text-base font-bold"><?php echo esc_html( $term->name ); ?></div>
							<?php probo_configure_cta(); ?>
						</div>
					</a>

					<?php
					if ( $term_callouts ) {
						foreach ( probo_category_callouts( $term ) as $term_callout ) {
							// Every callout is a grid tile here, whatever template it uses
							// on the category archive — a band template has nowhere to go
							// in this grid, so it falls back to the built-in tile look.
							if ( 'band' === probo_callout_placement( $term_callout ) ) {
								probo_callout_tile( $term_callout );
							} else {
								probo_callout_render( $term_callout );
							}
						}
					}

					// Repeat the block's own callout every N tiles. The count runs
					// over the category tiles only, so a category's own callout
					// slipping into the grid does not shift the rhythm. The last
					// position is skipped: a callout there is the "Achteraan"
					// setting, not an interval.
					if ( $callout && $interval && 0 === ( $index + 1 ) % $interval && $index + 1 < count( $terms ) ) {
						probo_callout_tile( $callout );
					}
					?>
				<?php endforeach; ?>

				<?php
				if ( $callout && ! $callout_first && ! $interval ) {
					probo_callout_tile( $callout );
				}
				?>
			</div>
		<?php else : ?>
			<p class="text-[15px] text-ink-3">
				<?php esc_html_e( 'No product categories found yet. Add categories in WooCommerce, or enter slugs above yourself.', 'probo-connect' ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
