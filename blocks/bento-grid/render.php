<?php
/**
 * Bento grid block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$tiles = is_array( $attributes['tiles'] ) ? $attributes['tiles'] : array();

if ( ! $tiles ) {
	return;
}

// Tailwind only ships classes it finds as literal strings in the source, so the
// span options are a fixed map rather than something built from the attribute.
$spans = array(
	'Groot'   => 'sm:col-span-2 sm:row-span-2',
	'Breed'   => 'sm:col-span-2',
	'Hoog'    => 'sm:row-span-2',
	'Normaal' => '',
);

$row_height = max( 120, min( 480, (int) $attributes['height'] ) );

/**
 * The background and caption colour for one tile.
 *
 * A tile is a photo unless the shop picked a colour for it. The two brand
 * choices are the runtime tokens rather than resolved hexes, so a tile follows
 * the Customizer the way every other surface does; a hand-picked colour is
 * validated here, because it lands straight in a style attribute, and its
 * caption colour is derived so a light colour does not get white text on it.
 *
 * @param array $tile Tile row.
 * @return array{0: string, 1: string} Background and foreground, both '' for a photo tile.
 */
$probo_tile_fill = static function ( $tile ) {
	$fill = isset( $tile['fill'] ) ? (string) $tile['fill'] : 'image';

	if ( 'accent' === $fill ) {
		return array( 'var(--pp-accent)', 'var(--pp-accent-fg)' );
	}

	if ( 'secondary' === $fill ) {
		return array( 'var(--pp-secondary)', 'var(--pp-secondary-fg)' );
	}

	if ( 'custom' === $fill ) {
		$custom = isset( $tile['color'] ) ? sanitize_hex_color( (string) $tile['color'] ) : '';

		// A custom tile with no colour picked yet falls back to the accent, so it
		// is never a blank hole in the grid.
		return $custom
			? array( $custom, probo_contrast_fg( $custom ) )
			: array( 'var(--pp-accent)', 'var(--pp-accent-fg)' );
	}

	return array( '', '' );
};
?>
<section <?php echo probo_block_wrapper( $attributes, 'py-16 lg:py-18' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container">
		<?php if ( $attributes['heading'] || $attributes['intro'] ) : ?>
			<div class="mb-7 max-w-[620px]">
				<?php if ( $attributes['heading'] ) : ?>
					<h2 class="text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]"><?php echo esc_html( $attributes['heading'] ); ?></h2>
				<?php endif; ?>

				<?php if ( $attributes['intro'] ) : ?>
					<p class="mt-3 text-[17px] leading-relaxed text-ink-2"><?php echo esc_html( $attributes['intro'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div
			class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
			style="grid-auto-rows:<?php echo esc_attr( $row_height ); ?>px"
		>
			<?php
			foreach ( $tiles as $tile ) :
				$image_id = isset( $tile['id'] ) ? (int) $tile['id'] : 0;
				$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
				$span     = isset( $tile['span'], $spans[ $tile['span'] ] ) ? $spans[ $tile['span'] ] : '';
				$caption  = isset( $tile['caption'] ) ? (string) $tile['caption'] : '';
				$url      = isset( $tile['url'] ) ? (string) $tile['url'] : '';
				$tag      = $url ? 'a' : 'div';

				list( $background, $foreground ) = $probo_tile_fill( $tile );
				?>
				<<?php echo esc_attr( $tag ); ?>
					class="rounded-pp group relative block overflow-hidden bg-surface-2 no-underline <?php echo esc_attr( $span ); ?>"
					<?php echo $background ? 'style="background:' . esc_attr( $background ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline. ?>
					<?php echo $url ? 'href="' . esc_url( $url ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline. ?>
				>
					<?php // A colour tile has no content of its own: it is the colour, with the caption straight on it. ?>
					<?php if ( ! $background ) : ?>
						<?php if ( $image ) : ?>
							<img
								class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
								src="<?php echo esc_url( $image ); ?>"
								alt="<?php echo esc_attr( $caption ); ?>"
								loading="lazy"
							/>
						<?php else : ?>
							<div class="pp-placeholder h-full w-full"><?php esc_html_e( 'image', 'probo-connect-theme' ); ?></div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $caption ) : ?>
						<?php // On a photo, a scrim mixed from the secondary colour keeps the caption readable whatever the image. ?>
						<div
							class="pointer-events-none absolute inset-0 flex items-end p-4.5"
							<?php echo $background ? '' : 'style="background:linear-gradient(to top, color-mix(in srgb, var(--pp-secondary) 78%, transparent) 0%, transparent 55%)"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed string. ?>
						>
							<?php // White over the photo's scrim; on a colour tile, whichever of black or white reads on it. ?>
							<span
								class="text-sm font-bold<?php echo $foreground ? '' : ' text-white'; ?>"
								<?php echo $foreground ? 'style="color:' . esc_attr( $foreground ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline. ?>
							><?php echo esc_html( $caption ); ?></span>
						</div>
					<?php endif; ?>
				</<?php echo esc_attr( $tag ); ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
