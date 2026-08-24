<?php
/**
 * Callout Template: Tile
 *
 * A callout sized to sit in a category grid cell — between the products on a
 * category archive, and in the Categorietegels block.
 *
 * Copy this file to your child theme at the same path to change it, or add a
 * file of your own beside it: every .php file under templates/callouts/{placement}/
 * shows up in the Template picker on the product category screen.
 *
 * @package Probo_Connect
 *
 * @var array $callout Title, text, image, cta, url and tone.
 */

defined( 'ABSPATH' ) || exit;

$probo_image_url = ! empty( $callout['image'] ) ? wp_get_attachment_image_url( (int) $callout['image'], 'medium' ) : '';
?>
<div class="rounded-pp flex flex-col overflow-hidden <?php echo esc_attr( probo_callout_tone_classes( $callout['tone'] ?? '' ) ); ?>">
	<?php if ( $probo_image_url ) : ?>
		<img class="h-[130px] w-full object-cover" src="<?php echo esc_url( $probo_image_url ); ?>" alt="" />
	<?php endif; ?>

	<div class="flex flex-1 flex-col justify-between p-6">
		<div>
			<div class="text-lg leading-tight font-extrabold tracking-[-0.02em]">
				<?php echo esc_html( $callout['title'] ); ?>
			</div>

			<?php if ( ! empty( $callout['text'] ) ) : ?>
				<p class="mt-2.5 text-[13px] leading-relaxed opacity-85"><?php echo esc_html( $callout['text'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $callout['cta'] ) && ! empty( $callout['url'] ) ) : ?>
			<a
				class="rounded-pp mt-5 inline-flex w-fit items-center bg-white px-4.5 py-2.5 text-[13px] font-bold text-ink no-underline hover:bg-ink hover:text-white"
				href="<?php echo esc_url( $callout['url'] ); ?>"
			>
				<?php echo esc_html( $callout['cta'] ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
