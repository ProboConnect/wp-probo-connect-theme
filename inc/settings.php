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
		'page_color'         => '#FFFFFF',
		'radius'             => 4,
		'title_font'         => 'Archivo',
		'body_font'          => 'Archivo',
		'header_variant'     => 'ruim',
		'bar_style'          => 'secondary',
		'bar_color'          => '',
		'footer_style'       => 'secondary',
		'hero_style'         => 'secondary',
		'card_style'         => 'border',
		'checkout_style'     => 'onepage',
		'require_login'      => 'off',
		'logo_light'         => '',
		'topbar_usp_1'       => 'Voor 23:00 besteld, morgen geleverd',
		'topbar_usp_2'       => 'Gratis bestandscontrole',
		'topbar_usp_3'       => '9,1 / 10 · 12.480 reviews',
		'search_placeholder' => 'Zoek op product, formaat of materiaal…',
		'checkout_phone'     => '0519 24 12 00',
		'footer_description' => 'Grootformaat drukwerk uit eigen productie. Bestel vandaag, morgen op locatie.',
		'footer_col_1_title' => 'Products',
		'footer_col_2_title' => 'Service',
		'footer_col_3_title' => 'Business',
		'footer_legal'       => '© 2026 Probo Connect · KvK 12345678 · BTW NL0012.34.567.B01',
	);
}

/**
 * The values a multiple-choice setting accepts, with the label for each.
 *
 * One table drives three things that used to be written out separately: the
 * Customizer control's `choices`, the sanitize callback, and — through
 * probo_defaults() — what an unconfigured install gets. A new option is added
 * here and nowhere else.
 *
 * The values are slugs, not the labels next to them. The labels are translated
 * and can be reworded; the values are compared against all over the theme, so
 * they have to be stable strings that mean nothing to a translator.
 *
 * @param string $key Setting key, without the theme_mod prefix.
 * @return array<string, string> Value => label. Empty for a free-form setting.
 */
function probo_choices( $key ) {
	$choices = array(
		'header_variant' => array(
			'ruim'    => __( 'Spacious — logo, search and USPs on three rows', 'probo-connect-theme' ),
			'compact' => __( 'Compact — one dark bar with a products megamenu', 'probo-connect-theme' ),
			'portal'  => __( 'Portal — one light bar with account navigation, without search or cart', 'probo-connect-theme' ),
		),
		'bar_style'      => array(
			'secondary' => __( 'Follow secondary', 'probo-connect-theme' ),
			'light'     => __( 'Light', 'probo-connect-theme' ),
			'accent'    => __( 'Accent', 'probo-connect-theme' ),
			'none'      => __( 'No color block', 'probo-connect-theme' ),
		),
		'footer_style'   => array(
			'secondary' => __( 'Follow secondary', 'probo-connect-theme' ),
			'light'     => __( 'Light', 'probo-connect-theme' ),
			'white'     => __( 'White', 'probo-connect-theme' ),
			'accent'    => __( 'Accent', 'probo-connect-theme' ),
		),
		// Not a Customizer control of its own: the hero block carries this per
		// instance. It is listed here so the block's value goes through the same
		// normalisation as everything else.
		'hero_style'     => array(
			'secondary' => __( 'Follow secondary', 'probo-connect-theme' ),
			'accent'    => __( 'Accent', 'probo-connect-theme' ),
			'light'     => __( 'Light', 'probo-connect-theme' ),
		),
		'card_style'     => array(
			'border' => __( 'Border', 'probo-connect-theme' ),
			'shadow' => __( 'Shadow', 'probo-connect-theme' ),
			'flat'   => __( 'Flat', 'probo-connect-theme' ),
		),
		'checkout_style' => array(
			'onepage' => __( 'One page (classic)', 'probo-connect-theme' ),
			'steps'   => __( 'Steps (accordion)', 'probo-connect-theme' ),
		),
		'require_login'  => array(
			'off'      => __( 'Off — guests can order', 'probo-connect-theme' ),
			'checkout' => __( 'At the checkout', 'probo-connect-theme' ),
			'cart'     => __( 'From the cart', 'probo-connect-theme' ),
			'site'     => __( 'The whole site — a closed portal', 'probo-connect-theme' ),
		),
	);

	return $choices[ $key ] ?? array();
}

/**
 * Values these settings used to be stored as, and what they are called now.
 *
 * Until now a choice was stored as its own Dutch label — 'Zwart', 'Schaduw',
 * 'Hele site' — which is why the theme compared against display text in twenty
 * places and could not have its Customizer translated. The values are slugs
 * now; this table translates what is already in the database on the way out,
 * so no site has to be migrated and a hero block saved last year keeps the
 * style it was saved with.
 *
 * @param string $key Setting key, without the theme_mod prefix.
 * @return array<string, string> Stored value => current value.
 */
function probo_setting_aliases( $key ) {
	$aliases = array(
		'bar_style'      => array( 'Zwart' => 'secondary', 'Licht' => 'light', 'Accent' => 'accent', 'Geen' => 'none' ),
		'footer_style'   => array( 'Zwart' => 'secondary', 'Licht' => 'light', 'Wit' => 'white', 'Accent' => 'accent' ),
		'hero_style'     => array( 'Zwart' => 'secondary', 'Licht' => 'light', 'Accent' => 'accent' ),
		'card_style'     => array( 'Rand' => 'border', 'Schaduw' => 'shadow', 'Vlak' => 'flat' ),
		'checkout_style' => array( 'Eén pagina' => 'onepage', 'Stappen' => 'steps' ),
		'require_login'  => array( 'Uit' => 'off', 'Kassa' => 'checkout', 'Winkelwagen' => 'cart', 'Hele site' => 'site' ),
	);

	return $aliases[ $key ] ?? array();
}

/**
 * Normalise one multiple-choice value to a slug this theme knows.
 *
 * Used on the way in from anywhere a value can arrive that was not written by
 * today's code: a stored theme_mod, a block attribute, a Customizer post.
 *
 * @param string $key   Setting key, without the theme_mod prefix.
 * @param mixed  $value Raw value.
 * @return string A value from probo_choices(), or the setting's default.
 */
function probo_normalize_choice( $key, $value ) {
	$value   = (string) $value;
	$aliases = probo_setting_aliases( $key );
	$value   = $aliases[ $value ] ?? $value;
	$choices = probo_choices( $key );

	if ( isset( $choices[ $value ] ) ) {
		return $value;
	}

	$defaults = probo_defaults();

	return (string) ( $defaults[ $key ] ?? (string) key( $choices ) );
}

/**
 * Read one theme setting, falling back to its default.
 *
 * A multiple-choice setting is normalised on the way out, so every caller can
 * compare against a slug without knowing what happens to be in the database.
 *
 * @param string $key Setting key, without the theme_mod prefix.
 * @return mixed
 */
function probo_get( $key ) {
	$defaults = probo_defaults();
	$default  = $defaults[ $key ] ?? '';
	$value    = get_theme_mod( 'probo_' . $key, $default );

	return probo_choices( $key ) ? probo_normalize_choice( $key, $value ) : $value;
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
