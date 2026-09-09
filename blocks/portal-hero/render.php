<?php
/**
 * Portal hero block.
 *
 * The welcome band above the portal's own pages — from the ThemeHeader Portal
 * design handoff. Its title carries a {name} placeholder rather than a fixed
 * string, filled in with probo_portal_account_name() so the block still knows
 * whose name to show while a shop keeps its own wording around it. The
 * placeholderName attribute stands in for that name whenever there isn't one
 * to show yet — a logged-out visitor, or the shop manager previewing the
 * block in the editor with no billing company of their own.
 *
 * The right-hand panel is a photo, same as the rest of the hero variants: pick
 * one from the block's own Image panel, or leave it and probo_hero_media()
 * draws the same diagonal placeholder those variants use for a photo that
 * hasn't been shot yet.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$probo_name = is_user_logged_in() ? probo_portal_account_name() : '';
$probo_name = '' !== $probo_name ? $probo_name : (string) $attributes['placeholderName'];

$probo_title = str_replace( '{name}', $probo_name, (string) $attributes['title'] );
?>
<section <?php echo probo_block_wrapper( $attributes, 'relative overflow-hidden bg-secondary text-secondary-fg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="pointer-events-none absolute inset-y-0 right-0 left-[45%] overflow-hidden" aria-hidden="true">
		<?php probo_hero_media( (int) $attributes['imageId'], 'h-full w-full', __( 'portal photo', 'probo-connect-theme' ) ); ?>
	</div>
	<div class="pointer-events-none absolute inset-y-0 right-0 left-[45%]" style="background:linear-gradient(90deg,var(--color-secondary),transparent)" aria-hidden="true"></div>

	<div class="relative mx-auto flex h-[160px] max-w-[1120px] flex-col justify-center px-6 lg:px-10">
		<?php if ( $attributes['eyebrow'] ) : ?>
			<div class="pp-eyebrow mb-2 max-w-[50%] text-white/65"><?php echo esc_html( $attributes['eyebrow'] ); ?></div>
		<?php endif; ?>

		<?php probo_hero_title( $probo_title, 'm-0 max-w-[50%] text-[28px] font-extrabold tracking-[-0.02em]' ); ?>
	</div>
</section>
