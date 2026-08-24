<?php
/**
 * Front page.
 *
 * The homepage is composed of blocks (probo/hero, probo/usp-bar,
 * probo/category-grid, probo/bestsellers, probo/how-it-works), so this
 * template is deliberately thin: it prints the page's block content and lets
 * each block own its own full-width section.
 *
 * When the front page is set to "latest posts" — or to a page that has no
 * content yet — the default composition is rendered instead, so a fresh
 * install still lands on the designed homepage.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

get_header();

$probo_rendered = false;

if ( is_page() && have_posts() ) {
	while ( have_posts() ) {
		the_post();

		if ( trim( get_the_content() ) ) {
			the_content();
			$probo_rendered = true;
		}
	}
}

if ( ! $probo_rendered ) {
	echo do_blocks( probo_homepage_blocks() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output is escaped by each block's render callback.
}

get_footer();
