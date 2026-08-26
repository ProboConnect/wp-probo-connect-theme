<?php
/**
 * FAQ block.
 *
 * The rows are <details>, so they open and close before any script has loaded
 * and stay operable if none ever does. assets/js/theme.js adds the one thing
 * the element does not do on its own: closing the row that was open when
 * another is opened.
 *
 * The first row renders open, the way the design shows it — an accordion where
 * everything is shut looks like a list of links.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$items = probo_parse_lines( $attributes['items'], 2 );

if ( ! $items ) {
	return;
}
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-white py-14' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="mx-auto grid max-w-[1120px] items-start gap-12 px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-10">
		<div>
			<?php if ( $attributes['heading'] ) : ?>
				<h2 class="mb-3 text-[26px] leading-[1.05] font-extrabold tracking-[-0.03em] text-balance lg:text-[34px]">
					<?php echo esc_html( $attributes['heading'] ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $attributes['intro'] ) : ?>
				<p class="mb-5 text-[15px] leading-[1.6] text-pretty text-ink-3"><?php echo esc_html( $attributes['intro'] ); ?></p>
			<?php endif; ?>

			<?php probo_hero_button( $attributes['linkLabel'], $attributes['linkUrl'], 'pp-btn-ghost h-12 px-5.5' ); ?>
		</div>

		<div class="flex flex-col" data-pp-faq>
			<?php foreach ( $items as $index => $item ) : ?>
				<details class="pp-faq-row border-t border-line last:border-b"<?php echo 0 === $index ? ' open' : ''; ?>>
					<summary class="pp-faq-question"><?php echo esc_html( $item[0] ); ?></summary>
					<?php if ( $item[1] ) : ?>
						<p class="m-0 max-w-[520px] pb-5 text-sm leading-[1.55] text-ink-3"><?php echo esc_html( $item[1] ); ?></p>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
