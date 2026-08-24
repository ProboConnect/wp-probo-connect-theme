<?php
/**
 * 404.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="pp-container py-24 text-center">
	<div class="pp-eyebrow text-accent-ink">404</div>
	<h1 class="mx-auto mt-4 max-w-[640px] text-5xl font-extrabold tracking-[-0.035em]">
		<?php esc_html_e( 'This page does not exist (anymore)', 'probo-connect' ); ?>
	</h1>
	<p class="mx-auto mt-4 max-w-[520px] text-[17px] leading-relaxed text-ink-2">
		<?php esc_html_e( 'Search below, or go back to the range.', 'probo-connect' ); ?>
	</p>
	<div class="mx-auto mt-8 max-w-[560px]"><?php probo_search_form( 'header' ); ?></div>
</main>

<?php
get_footer();
