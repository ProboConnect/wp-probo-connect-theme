<?php
/**
 * Callout Template: Banner
 *
 * The same wide band as category_top/top.php, below the products instead of
 * above them — a closing pitch once someone has looked through the range.
 *
 * @package Probo_Connect
 *
 * @var array $callout Title, text, image, cta, url and tone.
 */

defined( 'ABSPATH' ) || exit;

$probo_image_url = ! empty( $callout['image'] ) ? wp_get_attachment_image_url( (int) $callout['image'], 'thumbnail' ) : '';
?>
<div class="rounded-pp flex flex-col gap-5 p-7 sm:flex-row sm:items-center sm:justify-between lg:px-9 <?php echo esc_attr( probo_callout_tone_classes( $callout['tone'] ?? '' ) ); ?>">
	<div class="flex flex-col gap-5 sm:flex-row sm:items-center">
		<?php if ( $probo_image_url ) : ?>
			<img class="rounded-pp h-16 w-16 flex-none object-cover sm:h-20 sm:w-20" src="<?php echo esc_url( $probo_image_url ); ?>" alt="" />
		<?php endif; ?>

		<div class="max-w-[620px]">
			<div class="text-xl leading-tight font-extrabold tracking-[-0.02em]">
				<?php echo esc_html( $callout['title'] ); ?>
			</div>

			<?php if ( ! empty( $callout['text'] ) ) : ?>
				<p class="mt-2 text-[15px] leading-relaxed opacity-85"><?php echo esc_html( $callout['text'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $callout['cta'] ) && ! empty( $callout['url'] ) ) : ?>
		<a
			class="rounded-pp inline-flex w-fit flex-none items-center bg-white px-5.5 py-3 text-sm font-bold text-ink no-underline hover:bg-ink hover:text-white"
			href="<?php echo esc_url( $callout['url'] ); ?>"
		>
			<?php echo esc_html( $callout['cta'] ); ?>
		</a>
	<?php endif; ?>
</div>
