<?php
/**
 * Colour helpers.
 *
 * Straight ports of the design prototype's JS helpers so the Customizer derives
 * exactly the same tokens the mockup did.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Perceived luminance of a hex colour on a 0..1 scale.
 *
 * Mirrors lum() in the prototype: 0.299 R + 0.587 G + 0.114 B, divided by 255.
 * Returns 0 for anything unparseable, which is what the prototype did too.
 *
 * @param string $hex Hex colour, with or without leading #, 3 or 6 digits.
 * @return float
 */
function probo_lum( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
		return 0.0;
	}

	$value = hexdec( $hex );
	$red   = ( $value >> 16 ) & 255;
	$green = ( $value >> 8 ) & 255;
	$blue  = $value & 255;

	return ( 0.299 * $red + 0.587 * $green + 0.114 * $blue ) / 255;
}

/**
 * Very light tint of a colour — 92% of the way to white.
 *
 * Mirrors tint() in the prototype; used for --pp-accent-soft.
 *
 * @param string $hex Hex colour.
 * @return string An rgb() string.
 */
function probo_tint( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
		return 'rgb(238,241,255)';
	}

	$value = hexdec( $hex );
	$red   = ( $value >> 16 ) & 255;
	$green = ( $value >> 8 ) & 255;
	$blue  = $value & 255;
	$mix   = 0.92;

	return sprintf(
		'rgb(%d,%d,%d)',
		(int) round( $red + ( 255 - $red ) * $mix ),
		(int) round( $green + ( 255 - $green ) * $mix ),
		(int) round( $blue + ( 255 - $blue ) * $mix )
	);
}

/**
 * Whether a colour is dark enough to need light text on top of it.
 *
 * @param string $hex Hex colour.
 * @return bool
 */
function probo_is_dark( $hex ) {
	return probo_lum( $hex ) <= 0.6;
}

/**
 * Black or white, whichever reads better on the given background.
 *
 * @param string $hex Hex colour.
 * @return string
 */
function probo_contrast_fg( $hex ) {
	return probo_is_dark( $hex ) ? '#FFFFFF' : '#0B0B0C';
}

/**
 * The accent colour, but only where it still reads against a background.
 *
 * Accent-coloured eyebrows, links and CTA chips sit on panels the shop owner
 * also controls, so the two can collide: an accent footer paints accent text on
 * accent, and a dark accent on the dark secondary is just as unreadable. When
 * the two are within $threshold luminance of each other the panel's own
 * foreground colour is used instead, which is guaranteed to contrast because it
 * was derived from that same background.
 *
 * The default threshold is deliberately just under the distance between the
 * stock accent (#1B4DFF) and the stock secondary (#0B0B0C), so the design's own
 * blue-on-black eyebrows survive untouched.
 *
 * @param string $accent     Accent colour.
 * @param string $background Background the accent is drawn on.
 * @param string $fallback   Colour to use when the accent does not contrast.
 * @param float  $threshold  Minimum luminance distance.
 * @return string
 */
function probo_readable_accent( $accent, $background, $fallback, $threshold = 0.25 ) {
	if ( ! $accent ) {
		return $fallback;
	}

	return abs( probo_lum( $accent ) - probo_lum( $background ) ) >= $threshold ? $accent : $fallback;
}
