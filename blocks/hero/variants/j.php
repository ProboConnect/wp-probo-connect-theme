<?php
/**
 * Hero J — showreel, large image with play button.
 *
 * The play button is a real link to the film when the block has one, and only
 * decoration when it does not — a circle that looks pressable but is not is
 * worse than no circle at all.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$video = trim( (string) $attributes['videoUrl'] );
$play  = 'absolute top-1/2 left-1/2 flex h-22 w-22 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-white/[0.16] backdrop-blur-[4px]';
?>
<section <?php echo probo_block_wrapper( $attributes, 'relative flex min-h-[560px] flex-col justify-end overflow-hidden' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<?php probo_hero_media( $attributes['imageId'], 'absolute inset-0 h-full w-full', __( 'showreel still', 'probo-connect' ) ); ?>

	<div class="pointer-events-none absolute inset-0" style="background:linear-gradient(180deg,rgba(11,11,12,.15),rgba(11,11,12,.8))" aria-hidden="true"></div>

	<?php if ( $video ) : ?>
		<a class="<?php echo esc_attr( $play ); ?>" href="<?php echo esc_url( $video ); ?>">
			<span class="sr-only"><?php esc_html_e( 'Play the film', 'probo-connect' ); ?></span>
			<span class="pp-play-triangle" aria-hidden="true"></span>
		</a>
	<?php else : ?>
		<div class="<?php echo esc_attr( $play ); ?>" aria-hidden="true">
			<span class="pp-play-triangle"></span>
		</div>
	<?php endif; ?>

	<div class="pp-container relative w-full pb-16 text-white">
		<div class="flex flex-wrap items-end justify-between gap-8">
			<div>
				<?php if ( $attributes['eyebrow'] ) : ?>
					<div class="pp-eyebrow mb-4.5 tracking-[0.14em] text-white/70"><?php echo esc_html( $attributes['eyebrow'] ); ?></div>
				<?php endif; ?>

				<?php probo_hero_title( $attributes['title'], 'max-w-[640px] text-[36px] leading-[0.98] font-extrabold tracking-[-0.035em] text-balance lg:text-[60px]' ); ?>
			</div>

			<div class="flex flex-wrap gap-3">
				<?php
				probo_hero_button( $attributes['primaryLabel'], $attributes['primaryUrl'], 'pp-btn-accent h-[54px] px-7 text-[15px]' );
				probo_hero_button( $attributes['secondaryLabel'], $video ? $video : $attributes['secondaryUrl'], 'pp-btn h-[54px] border border-white/40 px-7 text-[15px] text-white hover:bg-white hover:text-ink' );
				?>
			</div>
		</div>
	</div>
</section>
