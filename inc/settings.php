<?php
/**
 * Theme settings: defaults, accessor, and the font catalogue.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default value for every Customizer setting the theme owns.
 *
 * These match the design prototype's starting state, so an unconfigured install
 * looks exactly like the mockup.
 *
 * @return array<string, mixed>
 */
function probo_defaults() {
	return array(
		'accent_color'       => '#1B4DFF',
		'secondary_color'    => '#0B0B0C',
		'radius'             => 4,
		'title_font'         => 'Archivo',
		'body_font'          => 'Archivo',
		'header_variant'     => 'ruim',
		'bar_style'          => 'Zwart',
		'bar_color'          => '',
		'footer_style'       => 'Zwart',
		'hero_style'         => 'Zwart',
		'hero_title_color'   => '',
		'card_style'         => 'Rand',
		'checkout_style'     => 'Eén pagina',
		'logo_light'         => '',
		'topbar_usp_1'       => 'Voor 23:00 besteld, morgen geleverd',
		'topbar_usp_2'       => 'Gratis bestandscontrole',
		'topbar_usp_3'       => '9,1 / 10 · 12.480 reviews',
		'search_placeholder' => 'Zoek op product, formaat of materiaal…',
		'checkout_phone'     => '0519 24 12 00',
		'footer_description' => 'Grootformaat drukwerk uit eigen productie. Bestel vandaag, morgen op locatie.',
		'footer_payments'    => 'iDEAL, Bancontact, Op rekening',
		'footer_legal'       => '© 2026 Probo Connect · KvK 12345678 · BTW NL0012.34.567.B01',
	);
}

/**
 * Read one theme setting, falling back to its default.
 *
 * @param string $key Setting key, without the theme_mod prefix.
 * @return mixed
 */
function probo_get( $key ) {
	$defaults = probo_defaults();
	$default  = $defaults[ $key ] ?? '';

	return get_theme_mod( 'probo_' . $key, $default );
}

/**
 * Read one theme colour setting, falling back to its default.
 *
 * `probo_get()` only substitutes the default when the mod is *absent* —
 * `get_theme_mod()`'s own rule. A stored-but-empty or invalid hex value
 * passes straight through instead, which for a colour is never a value
 * worth rendering. This wrapper closes that gap for colours specifically;
 * it does not touch `probo_get()` because other settings (`checkout_phone`)
 * rely on empty-means-"leave it out", not empty-means-default.
 *
 * @param string $key Setting key, without the theme_mod prefix.
 * @return string A valid `#rrggbb` hex colour.
 */
function probo_get_color( $key ) {
	$value    = probo_get( $key );
	$defaults = probo_defaults();

	return sanitize_hex_color( $value ) ? $value : ( $defaults[ $key ] ?? '' );
}

/**
 * Fonts offered in the Customizer, with the Google Fonts query for each.
 *
 * Title and body pick from this same catalogue; the prototype offered a
 * slightly shorter list for body text, which is preserved in probo_font_choices().
 *
 * @return array<string, string>
 */
function probo_font_catalogue() {
	return array(
		'Archivo'             => 'Archivo:wght@400;500;600;700;800;900',
		'Inter'               => 'Inter:wght@400;500;600;700;800;900',
		'Space Grotesk'       => 'Space+Grotesk:wght@400;500;600;700',
		'Bricolage Grotesque' => 'Bricolage+Grotesque:wght@400;600;700;800',
		'Manrope'             => 'Manrope:wght@400;500;600;700;800',
		'DM Sans'             => 'DM+Sans:wght@400;500;600;700;800;900',
		'Plus Jakarta Sans'   => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
		'Figtree'             => 'Figtree:wght@400;500;600;700;800;900',
		'Source Serif 4'      => 'Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700',
	);
}

/**
 * Selectable fonts per role.
 *
 * @param string $role Either 'title' or 'body'.
 * @return array<string, string> Value => label.
 */
function probo_font_choices( $role = 'title' ) {
	$names = array_keys( probo_font_catalogue() );

	if ( 'body' === $role ) {
		// The prototype left Bricolage Grotesque out of the body list — it is a
		// display face and reads poorly at 14–15px.
		$names = array_values( array_diff( $names, array( 'Bricolage Grotesque' ) ) );
	}

	return array_combine( $names, $names );
}

/**
 * Google Fonts URL for the two selected faces plus IBM Plex Mono.
 *
 * @return string
 */
function probo_fonts_url() {
	$catalogue = probo_font_catalogue();
	$families  = array();

	foreach ( array( probo_get( 'title_font' ), probo_get( 'body_font' ) ) as $font ) {
		if ( isset( $catalogue[ $font ] ) ) {
			$families[ $catalogue[ $font ] ] = true;
		}
	}

	// Specs, prices and micro-labels are always IBM Plex Mono by design.
	$families['IBM+Plex+Mono:wght@400;500'] = true;

	// Built by hand rather than with add_query_arg(): the family strings already
	// carry their own URL encoding (+, ; and @), which add_query_arg would
	// double-encode into a 400 from Google Fonts.
	return 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', array_keys( $families ) ) . '&display=swap';
}
