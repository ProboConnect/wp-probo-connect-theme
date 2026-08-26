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
 * @param array<string, mixed> $overrides Optional per-instance overrides, used
 *                                        by the hero block to preview its own
 *                                        style choice.
 * @return array<string, string> Property name => value.
 */
function probo_tokens( array $overrides = array() ) {
	$get = static function ( $key ) use ( $overrides ) {
		return array_key_exists( $key, $overrides ) && '' !== $overrides[ $key ]
			? $overrides[ $key ]
			: probo_get( $key );
	};

	$accent = $get( 'accent_color' ) ? $get( 'accent_color' ) : '#1B4DFF';
	$sec    = $get( 'secondary_color' ) ? $get( 'secondary_color' ) : '#0B0B0C';
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
		'--pp-secondary-fg'   => $lum > 0.6 ? '#0B0B0C' : '#FFFFFF',
		'--pp-secondary-line' => $lum > 0.85 ? '#C9C9CE' : $sec,
		'--pp-radius'         => (int) $get( 'radius' ) . 'px',
		// Quoted so multi-word families such as "Source Serif 4" resolve.
		'--pp-font-title'     => '"' . $get( 'title_font' ) . '"',
		'--pp-font-body'      => '"' . $get( 'body_font' ) . '"',
	);

	// Kaartstijl: Rand keeps the hairline, Schaduw swaps it for a soft stack,
	// Vlak drops both but keeps a transparent border so layout does not shift.
	$card                        = $get( 'card_style' );
	$tokens['--pp-card-border']  = 'Rand' === $card ? '1px solid #E4E4E7' : '1px solid transparent';
	$tokens['--pp-card-shadow']  = 'Schaduw' === $card
		? '0 2px 4px rgba(11,11,12,.06), 0 12px 28px rgba(11,11,12,.07)'
		: 'none';

	$tokens += probo_bar_tokens( $get( 'bar_style' ), $get( 'bar_color' ), $accent, $sec, $lum );
	$tokens += probo_footer_tokens( $get( 'footer_style' ), $accent, $sec );

	/**
	 * Filters the theme's runtime design tokens.
	 *
	 * @param array<string, string> $tokens    Property name => value.
	 * @param array<string, mixed>  $overrides Per-instance overrides.
	 */
	return apply_filters( 'probo_tokens', $tokens, $overrides );
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
 * @param float  $lum    Luminance of the secondary colour.
 * @return array<string, string>
 */
function probo_bar_tokens( $style, $custom, $accent, $sec, $lum ) {
	if ( $custom ) {
		$bar_lum = probo_lum( $custom );

		return array(
			'--pp-bar-bg'     => $custom,
			'--pp-bar-fg'     => $bar_lum > 0.6 ? '#0B0B0C' : '#FFFFFF',
			'--pp-bar-muted'  => $bar_lum > 0.6 ? '#4A4A50' : 'rgba(255,255,255,.75)',
			'--pp-bar-line'   => $bar_lum > 0.85 ? '#E4E4E7' : 'transparent',
			'--pp-bar-accent' => probo_readable_accent( $accent, $custom, $bar_lum > 0.6 ? '#0B0B0C' : '#FFFFFF' ),
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
		$fg   = probo_contrast_fg( $accent );
		$dark = '#0B0B0C' === $fg;

		return array(
			'--pp-bar-bg'     => $accent,
			'--pp-bar-fg'     => $fg,
			'--pp-bar-muted'  => $dark ? 'rgba(11,11,12,.74)' : 'rgba(255,255,255,.82)',
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

	$dark = $lum <= 0.6;

	return array(
		'--pp-bar-bg'     => $sec,
		'--pp-bar-fg'     => $dark ? '#FFFFFF' : '#0B0B0C',
		'--pp-bar-muted'  => $dark ? 'rgba(255,255,255,.7)' : '#4A4A50',
		'--pp-bar-line'   => $lum > 0.85 ? '#E4E4E7' : 'transparent',
		'--pp-bar-accent' => probo_readable_accent( $accent, $sec, $dark ? '#FFFFFF' : '#0B0B0C' ),
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
		$fg   = probo_contrast_fg( $accent );
		$dark = '#0B0B0C' === $fg;

		return array(
			'--pp-footer-bg'     => $accent,
			'--pp-footer-fg'     => $fg,
			'--pp-footer-muted'  => $dark ? 'rgba(11,11,12,.72)' : 'rgba(255,255,255,.8)',
			'--pp-footer-link'   => $dark ? 'rgba(11,11,12,.86)' : 'rgba(255,255,255,.92)',
			'--pp-footer-line'   => $dark ? 'rgba(11,11,12,.18)' : 'rgba(255,255,255,.22)',
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

	return array(
		'--pp-footer-bg'     => $sec,
		'--pp-footer-fg'     => $dark ? '#FFFFFF' : '#0B0B0C',
		'--pp-footer-muted'  => $dark ? '#9A9A9E' : '#6B6B70',
		'--pp-footer-link'   => $dark ? '#C9C9CE' : '#4A4A50',
		'--pp-footer-line'   => $dark ? '#1E1E22' : '#E4E4E7',
		'--pp-footer-accent' => probo_readable_accent( $accent, $sec, $dark ? '#FFFFFF' : '#0B0B0C' ),
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
		$bg    = $accent;
		$fg    = probo_contrast_fg( $accent );
		$muted = '#0B0B0C' === $fg ? 'rgba(11,11,12,.7)' : 'rgba(255,255,255,.78)';
	} elseif ( 'Licht' === $style ) {
		$bg    = '#F7F7F5';
		$fg    = '#0B0B0C';
		$muted = '#6B6B70';
	} else {
		$hero_lum = probo_lum( $sec );
		$bg       = $sec;
		$fg       = $hero_lum > 0.6 ? '#0B0B0C' : '#FFFFFF';
		$muted    = $hero_lum > 0.6 ? '#6B6B70' : '#A6A6AC';
	}

	$title = $title_color;

	if ( ! $title || abs( probo_lum( $title ) - probo_lum( $bg ) ) < 0.12 ) {
		$title = $fg;
	}

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
 * @param array<string, string> $tokens Property name => value.
 * @return string
 */
function probo_tokens_to_css( array $tokens ) {
	$out = '';

	foreach ( $tokens as $property => $value ) {
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
