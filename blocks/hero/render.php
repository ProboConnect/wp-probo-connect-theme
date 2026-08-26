<?php
/**
 * Hero block.
 *
 * Ten variants share one block rather than ten: a front page has exactly one
 * hero, and swapping between them should not mean losing the copy that was
 * already written. The variant only decides which of blocks/hero/variants/*.php
 * draws it; every variant reads the same attributes, and ignores the ones its
 * layout has no place for.
 *
 * @package Probo_Connect
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$variants = probo_hero_variants();
$variant  = strtoupper( (string) $attributes['variant'] );
$variant  = isset( $variants[ $variant ] ) ? $variant : 'A';

// Variant A is the one with a band of its own to choose — dark, accent or
// light, with a title colour to match. That is a decision about this block on
// this page, so it lives on the block; the Customizer only supplies the brand
// colours the band is derived from. The other nine variants are bands the
// design fixes, and read the theme's tokens directly.
$tokens = probo_hero_tokens(
	$attributes['heroStyle'],
	$attributes['titleColor'],
	probo_get( 'accent_color' ),
	probo_get( 'secondary_color' )
);

require get_template_directory() . '/blocks/hero/variants/' . strtolower( $variant ) . '.php';
