<?php
/**
 * Hero I — search hero, light with tags.
 *
 * The light counterpart of A. The search bar is the theme's own
 * probo_search_form(), so it posts where every other search on the site posts,
 * and the tags below it are the same shortcut list variant A uses.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$chips = array_filter( array_map( 'trim', explode( ',', (string) $attributes['chips'] ) ) );
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-surface-soft py-16 text-center lg:py-22' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="mx-auto max-w-[720px] px-6 lg:px-10">
		<?php probo_hero_title( $attributes['title'], 'mb-5 text-[40px] leading-none font-extrabold tracking-[-0.035em] text-balance text-ink lg:text-[56px]' ); ?>

		<?php if ( $attributes['subtitle'] ) : ?>
			<p class="mx-auto mb-8 max-w-[520px] text-lg leading-[1.55] text-pretty text-ink-3"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
		<?php endif; ?>

		<?php if ( $attributes['showSearch'] ) : ?>
			<div class="mx-auto max-w-[600px] shadow-[0_12px_28px_rgba(0,0,0,.06)]">
				<?php probo_search_form( 'hero' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $chips ) : ?>
			<div class="mt-5.5 flex flex-wrap justify-center gap-2.5">
				<?php foreach ( $chips as $chip ) : ?>
					<a
						class="rounded-full border border-line px-4 py-2 text-[13px] font-semibold text-ink no-underline hover:border-ink"
						href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $chip ), home_url( '/' ) ) ); ?>"
					>
						<?php echo esc_html( $chip ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
