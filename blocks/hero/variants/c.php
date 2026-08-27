<?php
/**
 * Hero C — full-bleed image, centred text.
 *
 * One statement over a photo. The scrim is fixed rather than derived from the
 * hero tokens: this band is always a photo, so it is always dark underneath.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;
?>
<section <?php echo probo_block_wrapper( $attributes, 'relative flex min-h-[520px] items-center justify-center overflow-hidden' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<?php probo_hero_media( $attributes['imageId'], 'absolute inset-0 h-full w-full', __( 'hero photo', 'probo-connect' ) ); ?>

	<div class="pointer-events-none absolute inset-0" style="background:linear-gradient(180deg,rgba(11,11,12,.35),rgba(11,11,12,.72))" aria-hidden="true"></div>

	<div class="relative max-w-[760px] px-6 py-20 text-center text-white lg:px-10">
		<?php if ( $attributes['eyebrow'] ) : ?>
			<div class="rounded-pp pp-eyebrow mb-6.5 inline-flex items-center gap-2 bg-accent px-3.5 py-1.5 text-accent-fg">
				<?php echo esc_html( $attributes['eyebrow'] ); ?>
			</div>
		<?php endif; ?>

		<?php probo_hero_title( $attributes['title'], 'mb-5.5 text-[40px] leading-[0.96] font-extrabold tracking-[-0.035em] text-balance lg:text-[68px]' ); ?>

		<?php if ( $attributes['subtitle'] ) : ?>
			<p class="mx-auto mb-8.5 max-w-[520px] text-[19px] leading-[1.55] text-pretty text-white/[0.78]"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
		<?php endif; ?>

		<div class="flex flex-wrap justify-center gap-3">
			<?php
			probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-accent h-[54px] px-7 text-[15px]' );
			probo_hero_button( $attributes['secondaryLabel'], $attributes['secondaryUrl'], 'pp-btn h-[54px] border border-white/[0.35] px-7 text-[15px] text-white hover:bg-white hover:text-ink' );
			?>
		</div>
	</div>
</section>
