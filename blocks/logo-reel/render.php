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

				$alt  = ! empty( $logo['name'] ) ? (string) $logo['name'] : (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true );
				$link = isset( $logo['url'] ) ? (string) $logo['url'] : '';
				?>
				<li>
					<?php if ( $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>">
					<?php endif; ?>

					<?php // Grayscale is the house look; lifting it on hover keeps the row from feeling dead without shouting for attention. ?>
					<img
						class="<?php echo $attributes['grayscale'] ? 'w-auto object-contain opacity-60 grayscale transition hover:opacity-100 hover:grayscale-0' : 'w-auto object-contain'; ?>"
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( $alt ); ?>"
						style="height:<?php echo (int) $height; ?>px"
						loading="lazy"
					/>

					<?php if ( $link ) : ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
