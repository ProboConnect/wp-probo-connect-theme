<?php
/**
 * Runtime design tokens.
 *
 * Ports the prototype's JS token logic (Probo Print Theme.dc.html, class
 * Component) to PHP so the Customizer drives the same CSS custom properties
 * Tailwind's @theme block is mapped onto.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every runtime token, derived from the current Customizer values.
 *
 * The hero block does *not* go through this function — it derives its own
 * tokens per instance via probo_hero_tokens() so a per-block style override
 * can win over the Customizer's hero_style. This builds the page-level
 * :root block only.
 *
 * @return array<string, string> Property name => value.
 */
function probo_tokens() {
	$accent = probo_get_color( 'accent_color' );
	$sec    = probo_get_color( 'secondary_color' );
	$lum    = probo_lum( $sec );

	$tokens = array(
		'--pp-accent'         => $accent,
		'--pp-accent-soft'    => probo_tint( $accent ),
		// Text and icons drawn on top of a filled accent surface. Hardcoded
		// white breaks the moment someone picks a light accent — a yellow
		// brand turns every button label invisible.
		'--pp-accent-fg'      => probo_contrast_fg( $accent ),
		// The accent as *text* on the theme's white and near-white surfaces.
		// Same guard as the footer and hero: a light accent falls back to ink
		// rather than washing out on white.
		'--pp-accent-ink'     => probo_readable_accent( $accent, '#FFFFFF', '#0B0B0C' ),
		'--pp-secondary'      => $sec,
		'--pp-secondary-fg'   => probo_contrast_fg( $sec ),
		'--pp-secondary-line' => $lum > 0.85 ? '#C9C9CE' : $sec,
		'--pp-radius'         => (int) probo_get( 'radius' ) . 'px',
		// Quoted so multi-word families such as "Source Serif 4" resolve.
		'--pp-font-title'     => '"' . probo_get( 'title_font' ) . '"',
		'--pp-font-body'      => '"' . probo_get( 'body_font' ) . '"',
	);

	// Kaartstijl: Rand keeps the hairline, Schaduw swaps it for a soft stack,
	// Vlak drops both but keeps a transparent border so layout does not shift.
	$card                        = probo_get( 'card_style' );
	$tokens['--pp-card-border']  = 'Rand' === $card ? '1px solid #E4E4E7' : '1px solid transparent';
	$tokens['--pp-card-shadow']  = 'Schaduw' === $card
		? '0 2px 4px rgba(11,11,12,.06), 0 12px 28px rgba(11,11,12,.07)'
		: 'none';

	$tokens += probo_bar_tokens( probo_get( 'bar_style' ), probo_get( 'bar_color' ), $accent, $sec );
	$tokens += probo_footer_tokens( probo_get( 'footer_style' ), $accent, $sec );
	$tokens += probo_hero_tokens( probo_get( 'hero_style' ), probo_get( 'hero_title_color' ), $accent, $sec );

	/**
	 * Filters the theme's runtime design tokens.
	 *
	 * @param array<string, string> $tokens    Property name => value.
	 * @param array<string, mixed>  $overrides Reserved; always an empty array. The
	 *                                         per-instance override machinery this
	 *                                         used to carry was dead code (the
	 *                                         only two callers of probo_tokens()
	 *                                         are argument-less) and has been
	 *                                         removed. The parameter itself is
	 *                                         kept for one release so a
	 *                                         third-party callback declaring it
	 *                                         does not fatal.
	 */
	return apply_filters( 'probo_tokens', $tokens, array() );
}

/**
 * Top-bar tokens.
 *
 * A custom bar colour wins over the style dropdown; otherwise "Zwart" means
 * "follow the secondary colour", with text and rule flipped to whatever
 * contrasts. That two-step is what keeps the bar readable when someone picks a
 * pale secondary.
 *
 * @param string $style  Bar style.
 * @param string $custom Optional bar colour override.
 * @param string $accent Accent colour.
 * @param string $sec    Secondary colour.
 * @return array<string, string>
 */
function probo_bar_tokens( $style, $custom, $accent, $sec ) {
	if ( $custom ) {
		$bar_dark = probo_is_dark( $custom );

		return array(
			'--pp-bar-bg'     => $custom,
			'--pp-bar-fg'     => probo_contrast_fg( $custom ),
			'--pp-bar-muted'  => $bar_dark ? 'rgba(255,255,255,.75)' : '#4A4A50',
			'--pp-bar-line'   => probo_lum( $custom ) > 0.85 ? '#E4E4E7' : 'transparent',
			'--pp-bar-accent' => probo_readable_accent( $accent, $custom, probo_contrast_fg( $custom ) ),
		);
	}

	if ( 'Licht' === $style ) {
		return array(
			'--pp-bar-bg'     => '#F7F7F5',
			'--pp-bar-fg'     => '#0B0B0C',
			'--pp-bar-muted'  => '#6B6B70',
			'--pp-bar-line'   => '#E4E4E7',
			'--pp-bar-accent' => probo_readable_accent( $accent, '#F7F7F5', '#0B0B0C' ),
		);
	}

	if ( 'Accent' === $style ) {
		// Dark text (and so the darker muted tone) is chosen precisely when
		// the accent itself is *not* dark — mirrors probo_contrast_fg()'s own
		// rule instead of string-comparing the hex it just returned.
		$fg        = probo_contrast_fg( $accent );
		$text_dark = ! probo_is_dark( $accent );

		return array(
			'--pp-bar-bg'     => $accent,
			'--pp-bar-fg'     => $fg,
			'--pp-bar-muted'  => $text_dark ? 'rgba(11,11,12,.74)' : 'rgba(255,255,255,.82)',
			'--pp-bar-line'   => 'transparent',
			'--pp-bar-accent' => $fg,
		);
	}

	if ( 'Geen' === $style ) {
		return array(
			'--pp-bar-bg'     => '#FFFFFF',
			'--pp-bar-fg'     => '#0B0B0C',
			'--pp-bar-muted'  => '#6B6B70',
			'--pp-bar-line'   => '#E4E4E7',
			'--pp-bar-accent' => probo_readable_accent( $accent, '#FFFFFF', '#0B0B0C' ),
		);
	}

	$dark = probo_is_dark( $sec );
	$fg   = probo_contrast_fg( $sec );

	return array(
		'--pp-bar-bg'     => $sec,
		'--pp-bar-fg'     => $fg,
		'--pp-bar-muted'  => $dark ? 'rgba(255,255,255,.7)' : '#4A4A50',
		'--pp-bar-line'   => probo_lum( $sec ) > 0.85 ? '#E4E4E7' : 'transparent',
		'--pp-bar-accent' => probo_readable_accent( $accent, $sec, $fg ),
	);
}

/**
 * Footer tokens.
 *
 * @param string $style  Footer style.
 * @param string $accent Accent colour.
 * @param string $sec    Secondary colour.
 * @return array<string, string>
 */
function probo_footer_tokens( $style, $accent, $sec ) {
	if ( 'Licht' === $style || 'Wit' === $style ) {
		$bg = 'Wit' === $style ? '#FFFFFF' : '#F7F7F5';

		return array(
			'--pp-footer-bg'     => $bg,
			'--pp-footer-fg'     => '#0B0B0C',
			'--pp-footer-muted'  => '#6B6B70',
			'--pp-footer-link'   => '#4A4A50',
			'--pp-footer-line'   => '#E4E4E7',
			'--pp-footer-accent' => probo_readable_accent( $accent, $bg, '#0B0B0C' ),
		);
	}

	if ( 'Accent' === $style ) {
		// White was hardcoded here, which is fine on a dark brand colour and
		// unreadable on a light one — a yellow accent gave a white-on-yellow
		// footer. Everything is derived from the accent's own contrast colour
		// instead, and the softer tones are that colour at reduced alpha so the
		// hierarchy holds either way.
		// Dark text is chosen precisely when the accent itself is not dark —
		// mirrors probo_contrast_fg()'s own rule instead of string-comparing
		// the hex it just returned.
		$fg        = probo_contrast_fg( $accent );
		$text_dark = ! probo_is_dark( $accent );

		return array(
			'--pp-footer-bg'     => $accent,
			'--pp-footer-fg'     => $fg,
			'--pp-footer-muted'  => $text_dark ? 'rgba(11,11,12,.72)' : 'rgba(255,255,255,.8)',
			'--pp-footer-link'   => $text_dark ? 'rgba(11,11,12,.86)' : 'rgba(255,255,255,.92)',
			'--pp-footer-line'   => $text_dark ? 'rgba(11,11,12,.18)' : 'rgba(255,255,255,.22)',
			// Accent on accent is invisible, so headings and hovers borrow the
			// footer's own foreground here.
			'--pp-footer-accent' => $fg,
		);
	}

	// "Zwart" means "follow secondary". The prototype hardcoded white text here,
	// which turns the whole footer invisible as soon as someone picks a pale
	// secondary — the same trap that was fixed for the top bar and the hero, so
	// the footer gets the same luminance guard.
	$dark = probo_is_dark( $sec );
	$fg   = probo_contrast_fg( $sec );

	return array(
		'--pp-footer-bg'     => $sec,
		'--pp-footer-fg'     => $fg,
		'--pp-footer-muted'  => $dark ? '#9A9A9E' : '#6B6B70',
		'--pp-footer-link'   => $dark ? '#C9C9CE' : '#4A4A50',
		'--pp-footer-line'   => $dark ? '#1E1E22' : '#E4E4E7',
		'--pp-footer-accent' => probo_readable_accent( $accent, $sec, $fg ),
	);
}

/**
 * Hero tokens, including the title-colour guard.
 *
 * A hand-picked title colour is ignored when it sits within 0.12 luminance of
 * the hero background, so the title can never disappear into it — the fix the
 * user asked for after the first round of tweaks.
 *
 * @param string $style       Hero style.
 * @param string $title_color Optional hand-picked title colour.
 * @param string $accent      Accent colour.
 * @param string $sec         Secondary colour.
 * @return array<string, string>
 */
function probo_hero_tokens( $style, $title_color, $accent, $sec ) {
	if ( 'Accent' === $style ) {
		// Dark text is chosen precisely when the accent itself is not dark —
		// mirrors probo_contrast_fg()'s own rule instead of string-comparing
		// the hex it just returned.
		$bg    = $accent;
		$fg    = probo_contrast_fg( $accent );
		$muted = ! probo_is_dark( $accent ) ? 'rgba(11,11,12,.7)' : 'rgba(255,255,255,.78)';
	} elseif ( 'Licht' === $style ) {
		$bg    = '#F7F7F5';
		$fg    = '#0B0B0C';
		$muted = '#6B6B70';
	} else {
		$bg    = $sec;
		$fg    = probo_contrast_fg( $sec );
		$muted = probo_is_dark( $sec ) ? '#A6A6AC' : '#6B6B70';
	}

	// Same "is it readable here" decision as the accent chip below, just
	// with a tighter threshold and the hero's own fg as fallback.
	$title = probo_readable_accent( $title_color, $bg, $fg, 0.12 );

	// The eyebrow badge is a filled accent chip. On an accent hero it would
	// disappear into the background, so it flips to the hero's foreground with
	// the accent moving to the text.
	$chip = probo_readable_accent( $accent, $bg, $fg );

	return array(
		'--pp-hero-bg'        => $bg,
		'--pp-hero-fg'        => $fg,
		'--pp-hero-muted'     => $muted,
		'--pp-hero-title'     => $title,
		'--pp-hero-accent'    => $chip,
		'--pp-hero-accent-fg' => probo_contrast_fg( $chip ),
	);
}

/**
 * Render tokens as a CSS declaration list.
 *
 * Values land inside a `:root{ … }` block, so a value carrying a `}` would
 * close the rule and let whatever follows through as arbitrary CSS. Nothing
 * the theme itself produces can do that — every value here is a hex, an
 * rgb()/rgba(), a pixel length, `transparent` or a quoted font name — but
 * `probo_tokens` is a documented filter, so a third party's value reaches
 * this line too. Strip the characters that could break out rather than trust
 * the producer.
 *
 * @param array<string, string> $tokens Property name => value.
 * @return string
 */
function probo_tokens_to_css( array $tokens ) {
	$out = '';

	foreach ( $tokens as $property => $value ) {
		// Custom properties only: anything else is not ours to print.
		if ( ! preg_match( '/^--[A-Za-z0-9_-]+$/', (string) $property ) ) {
			continue;
		}

		// Drop comment openers first, then the characters that would end the
		// declaration or the rule, or open a tag on the way out of a <style>.
		$value = str_replace( array( '/*', '*/' ), '', (string) $value );
		$value = trim( str_replace( array( ';', '{', '}', '<', '>', '\\' ), '', $value ) );

		// No token the theme produces is a url(), and a custom property that
		// carries one becomes a request the moment anything resolves it with
		// var() — which is the exfiltration half of the problem, not just the
		// defacement half.
		if ( false !== stripos( $value, 'url(' ) ) {
			continue;
		}

		if ( '' === $value ) {
			continue;
		}

		$out .= $property . ':' . $value . ';';
	}

	return $out;
}

/**
 * Attach the :root token block to the compiled stylesheet.
 *
 * It has to ride along with theme.css rather than being printed on wp_head:
 * theme.css ends with its own unlayered :root defaults, and wp_print_styles
 * runs at wp_head priority 8. Anything printed earlier — the old priority 5 —
 * loses to those defaults on document order, which is why the Customizer
 * colours never reached the front end. wp_add_inline_style always emits after
 * its handle's tag, so the Customizer values win.
 */
function probo_print_tokens() {
	wp_add_inline_style( 'pp-theme', ':root{' . probo_tokens_to_css( probo_tokens() ) . '}' );
}
add_action( 'wp_enqueue_scripts', 'probo_print_tokens', 20 );

/**
 * Same tokens, inlined for the editor canvas.
 */
function probo_editor_tokens() {
	if ( ! is_admin() ) {
		return;
	}

	wp_add_inline_style( 'pp-theme', ':root{' . probo_tokens_to_css( probo_tokens() ) . '}' );
}
add_action( 'enqueue_block_assets', 'probo_editor_tokens', 20 );
