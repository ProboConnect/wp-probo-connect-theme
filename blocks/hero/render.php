<?php
/**
 * Hero block.
 *
 * @package Probo_Connect
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// The block may override the Customizer's hero style, so the hero tokens are
// derived per instance and scoped to this section with inline properties.
$hero_style  = $attributes['heroStyle'] ? $attributes['heroStyle'] : probo_get( 'hero_style' );
$title_color = $attributes['titleColor'] ? $attributes['titleColor'] : probo_get( 'hero_title_color' );
$tokens      = probo_hero_tokens(
	$hero_style,
	$title_color,
	probo_get_color( 'accent_color' ),
	probo_get_color( 'secondary_color' )
);

$chips = array_filter( array_map( 'trim', explode( ',', (string) $attributes['chips'] ) ) );
$image = $attributes['imageId'] ? wp_get_attachment_image_url( $attributes['imageId'], 'full' ) : '';

// The overlay is mixed from the hero's own background rather than plain black,
// so it keeps working when the hero is light or accent-coloured — and it stays
// tied to the Customizer instead of baking in a colour here.
$overlay        = (string) $attributes['overlay'];
$overlay_amount = max( 0, min( 100, (int) $attributes['overlayStrength'] ) );
$overlay_colour = sprintf( 'color-mix(in srgb, var(--pp-hero-bg) %d%%, transparent)', $overlay_amount );
$overlays       = array(
	// Bottom-up: the classic scrim, keeps the top of the photo clean.
	'Onder'  => 'linear-gradient(to top, %1$s 0%%, transparent 65%%)',
	// Left-to-right: fades the photo into the text column beside it.
	'Links'  => 'linear-gradient(to right, %1$s 0%%, transparent 55%%)',
	// Vignette: darkens the edges and leaves the middle of the photo alone.
	'Rondom' => 'radial-gradient(ellipse at center, transparent 35%%, %1$s 100%%)',
);

$overlay_style = isset( $overlays[ $overlay ] ) && $overlay_amount
	? sprintf( $overlays[ $overlay ], $overlay_colour )
	: '';
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-hero-bg text-hero-fg', esc_attr( probo_tokens_to_css( $tokens ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container grid items-center gap-14 lg:min-h-[520px] lg:grid-cols-[1.05fr_0.95fr]">
		<div class="py-12 lg:py-16">
			<?php if ( $attributes['eyebrow'] ) : ?>
				<div class="rounded-pp pp-eyebrow mb-6.5 inline-flex items-center gap-2 bg-hero-accent px-3.5 py-1.5 text-hero-accent-fg">
					<?php echo esc_html( $attributes['eyebrow'] ); ?>
				</div>
			<?php endif; ?>

			<h1 class="mb-5.5 text-[40px] leading-[0.98] font-extrabold tracking-[-0.035em] text-hero-title lg:text-[66px] lg:leading-[0.96]">
				<?php echo nl2br( esc_html( $attributes['title'] ) ); ?>
			</h1>

			<?php if ( $attributes['subtitle'] ) : ?>
				<p class="mb-8.5 max-w-[480px] text-lg leading-relaxed text-hero-muted">
					<?php echo esc_html( $attributes['subtitle'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $attributes['showSearch'] ) : ?>
				<div class="max-w-[560px]"><?php probo_search_form( 'hero' ); ?></div>
			<?php endif; ?>

			<?php if ( $chips ) : ?>
				<div class="mt-4.5 flex flex-wrap gap-2.5">
					<?php foreach ( $chips as $chip ) : ?>
						<a
							class="rounded-full border px-3.5 py-2 text-[13px] text-hero-muted no-underline hover:text-hero-fg"
							style="border-color:color-mix(in srgb, var(--pp-hero-fg) 22%, transparent)"
							href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $chip ), home_url( '/' ) ) ); ?>"
						>
							<?php echo esc_html( $chip ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $image ) : ?>
			<div class="relative">
				<img class="h-[320px] w-full object-cover lg:h-[520px]" src="<?php echo esc_url( $image ); ?>" alt="" />

				<?php if ( $overlay_style ) : ?>
					<div class="pointer-events-none absolute inset-0" style="background:<?php echo esc_attr( $overlay_style ); ?>" aria-hidden="true"></div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="pp-placeholder-dark h-[320px] lg:h-[520px]">
				<?php esc_html_e( 'hero photo', 'probo-connect' ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
