<?php
/**
 * Logo reel block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$logos = is_array( $attributes['logos'] ) ? $attributes['logos'] : array();

if ( ! $logos ) {
	return;
}

$height = max( 16, min( 96, (int) $attributes['height'] ) );
?>
<section <?php echo probo_block_wrapper( $attributes, 'border-y border-line py-10' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pp-container flex flex-col items-center gap-7 lg:flex-row lg:gap-12">
		<?php if ( $attributes['heading'] ) : ?>
			<div class="pp-eyebrow flex-none text-ink-4"><?php echo esc_html( $attributes['heading'] ); ?></div>
		<?php endif; ?>

		<ul class="m-0 flex list-none flex-wrap items-center justify-center gap-x-11 gap-y-6 p-0 lg:justify-start">
			<?php
			foreach ( $logos as $logo ) :
				$logo_id  = isset( $logo['id'] ) ? (int) $logo['id'] : 0;
				$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

				if ( ! $logo_url ) {
					continue;
				}

				$alt   = isset( $logo['name'] ) ? (string) $logo['name'] : (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true );
				$link  = isset( $logo['url'] ) ? (string) $logo['url'] : '';
				$image = sprintf(
					'<img class="w-auto object-contain%s" src="%s" alt="%s" style="height:%dpx" loading="lazy" />',
					// Grayscale is the house look; lifting it on hover keeps the row
					// from feeling dead without shouting for attention.
					$attributes['grayscale'] ? ' opacity-60 grayscale transition hover:opacity-100 hover:grayscale-0' : '',
					esc_url( $logo_url ),
					esc_attr( $alt ),
					$height
				);
				?>
				<li>
					<?php if ( $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" rel="noopener">
							<?php echo wp_kses_post( $image ); ?>
						</a>
					<?php else : ?>
						<?php echo wp_kses_post( $image ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
