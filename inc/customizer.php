<?php
/**
 * Customizer: the theme's "Tweaks" panel.
 *
 * Mirrors the design prototype's controls one for one, so what the shop owner
 * sees here is what was iterated on during the design session.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function probo_customize_register( $wp_customize ) {
	$defaults = probo_defaults();

	$wp_customize->add_panel(
		'probo_theme',
		array(
			'title'       => __( 'Theme settings', 'probo-connect' ),
			'description' => __( 'Colors, typography, and the structure of header, hero, and footer.', 'probo-connect' ),
			'priority'    => 20,
		)
	);

	$sections = array(
		'probo_brand'      => __( 'Brand', 'probo-connect' ),
		'probo_typography' => __( 'Typography', 'probo-connect' ),
		'probo_chrome'     => __( 'Header & footer', 'probo-connect' ),
		'probo_hero'       => __( 'Hero', 'probo-connect' ),
		'probo_components' => __( 'Components', 'probo-connect' ),
	);

	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'probo_theme' ) );
	}

	/**
	 * Adds a setting plus its control in one step.
	 *
	 * @param string $key      Setting key without the probo_ prefix.
	 * @param array  $control  Control arguments.
	 * @param string $sanitize Sanitize callback.
	 * @param string $transport Setting transport.
	 */
	$add = static function ( $key, array $control, $sanitize, $transport = 'refresh' ) use ( $wp_customize, $defaults ) {
		$wp_customize->add_setting(
			'probo_' . $key,
			array(
				'default'           => $defaults[ $key ] ?? '',
				'sanitize_callback' => $sanitize,
				'transport'         => $transport,
			)
		);

		$type = $control['type'] ?? 'text';
		unset( $control['type'] );

		if ( 'color' === $type ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'probo_' . $key, $control ) );
		} elseif ( 'image' === $type ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'probo_' . $key, $control ) );
		} else {
			$wp_customize->add_control( 'probo_' . $key, $control + array( 'type' => $type ) );
		}
	};

	// --- Merk -------------------------------------------------------------
	// Colours stay on 'refresh': every one of them feeds derived tokens
	// (contrast-aware bar, footer and hero values) that are computed in PHP.
	// Recomputing that logic in a preview script would mean maintaining it twice.
	$add(
		'accent_color',
		array(
			'type'    => 'color',
			'label'   => __( 'Accent color', 'probo-connect' ),
			'section' => 'probo_brand',
		),
		'sanitize_hex_color'
	);

	$add(
		'secondary_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Secondary', 'probo-connect' ),
			'description' => __( 'Controls all dark surfaces: hero, top bar, footer, cart button, price bars, and summary boxes.', 'probo-connect' ),
			'section'     => 'probo_brand',
		),
		'sanitize_hex_color'
	);

	$add(
		'radius',
		array(
			'type'        => 'number',
			'label'       => __( 'Radius', 'probo-connect' ),
			'section'     => 'probo_brand',
			'input_attrs' => array( 'min' => 0, 'max' => 16, 'step' => 1 ),
		),
		'probo_sanitize_radius'
	);

	// --- Typografie -------------------------------------------------------
	$add(
		'title_font',
		array(
			'type'    => 'select',
			'label'   => __( 'Heading font', 'probo-connect' ),
			'section' => 'probo_typography',
			'choices' => probo_font_choices( 'title' ),
		),
		'probo_sanitize_title_font'
	);

	$add(
		'body_font',
		array(
			'type'        => 'select',
			'label'       => __( 'Body font', 'probo-connect' ),
			'description' => __( 'Specifications and prices always stay IBM Plex Mono.', 'probo-connect' ),
			'section'     => 'probo_typography',
			'choices'     => probo_font_choices( 'body' ),
		),
		'probo_sanitize_body_font'
	);

	// --- Header & footer --------------------------------------------------
	$add(
		'header_variant',
		array(
			'type'    => 'radio',
			'label'   => __( 'Header style', 'probo-connect' ),
			'section' => 'probo_chrome',
			'choices' => array(
				'ruim'    => __( 'Spacious — logo, search and USPs on three rows', 'probo-connect' ),
				'compact' => __( 'Compact — one dark bar with a products megamenu', 'probo-connect' ),
			),
		),
		'probo_sanitize_header_variant'
	);

	$add(
		'bar_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Top bar', 'probo-connect' ),
			'section' => 'probo_chrome',
			'choices' => array(
				'Zwart'  => __( 'Follow secondary', 'probo-connect' ),
				'Licht'  => __( 'Light', 'probo-connect' ),
				'Accent' => __( 'Accent', 'probo-connect' ),
				'Geen'   => __( 'No color block', 'probo-connect' ),
			),
		),
		'probo_sanitize_bar_style'
	);

	$add(
		'bar_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Top bar color', 'probo-connect' ),
			'description' => __( 'Optional custom color. Leave empty = follow the choice above.', 'probo-connect' ),
			'section'     => 'probo_chrome',
		),
		'probo_sanitize_optional_hex'
	);

	$add(
		'footer_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Footer', 'probo-connect' ),
			'section' => 'probo_chrome',
			'choices' => array(
				'Zwart'  => __( 'Follow secondary', 'probo-connect' ),
				'Licht'  => __( 'Light', 'probo-connect' ),
				'Wit'    => __( 'White', 'probo-connect' ),
				'Accent' => __( 'Accent', 'probo-connect' ),
			),
		),
		'probo_sanitize_footer_style'
	);

	// WordPress core supports exactly one custom logo and has no light/dark
	// variant, so the second image lives here and is used wherever the
	// background is dark (footer, and the top bar when it is a dark block).
	$add(
		'logo_light',
		array(
			'type'        => 'image',
			'label'       => __( 'Logo (light)', 'probo-connect' ),
			'description' => __( 'For dark surfaces like the footer. Leave empty = the regular logo will be used.', 'probo-connect' ),
			'section'     => 'probo_chrome',
		),
		'esc_url_raw'
	);

	foreach ( array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3' ) as $index => $key ) {
		$add(
			$key,
			array(
				'type'    => 'text',
				/* translators: %d: position of the USP in the top bar. */
				'label'   => sprintf( __( 'Top bar USP %d', 'probo-connect' ), $index + 1 ),
				'section' => 'probo_chrome',
			),
			'sanitize_text_field',
			'postMessage'
		);
	}

	$add(
		'search_placeholder',
		array(
			'type'    => 'text',
			'label'   => __( 'Search field placeholder', 'probo-connect' ),
			'section' => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	$add(
		'checkout_phone',
		array(
			'type'        => 'text',
			'label'       => __( 'Phone number on checkout', 'probo-connect' ),
			'description' => __( 'Shown on the right in the plain checkout header. Leave empty to hide it.', 'probo-connect' ),
			'section'     => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	$add(
		'footer_description',
		array(
			'type'    => 'textarea',
			'label'   => __( 'Footer intro text', 'probo-connect' ),
			'section' => 'probo_chrome',
		),
		'sanitize_textarea_field',
		'postMessage'
	);

	$add(
		'footer_payments',
		array(
			'type'        => 'text',
			'label'       => __( 'Payment methods in the footer', 'probo-connect' ),
			'description' => __( 'Comma-separated, for example: iDEAL, Bancontact, Invoice', 'probo-connect' ),
			'section'     => 'probo_chrome',
		),
		'sanitize_text_field'
	);

	$add(
		'footer_legal',
		array(
			'type'    => 'text',
			'label'   => __( 'Footer legal line', 'probo-connect' ),
			'section' => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	// --- Hero -------------------------------------------------------------
	$add(
		'hero_style',
		array(
			'type'        => 'select',
			'label'       => __( 'Hero style', 'probo-connect' ),
			'description' => __( 'Default for the hero block; can be overridden per block.', 'probo-connect' ),
			'section'     => 'probo_hero',
			'choices'     => array(
				'Zwart'  => __( 'Follow secondary', 'probo-connect' ),
				'Accent' => __( 'Accent', 'probo-connect' ),
				'Licht'  => __( 'Light', 'probo-connect' ),
			),
		),
		'probo_sanitize_hero_style'
	);

	$add(
		'hero_title_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Title color', 'probo-connect' ),
			'description' => __( 'A color too close to the background is ignored, so the title stays readable.', 'probo-connect' ),
			'section'     => 'probo_hero',
		),
		'probo_sanitize_optional_hex'
	);

	// --- Componenten ------------------------------------------------------
	$add(
		'card_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Card style', 'probo-connect' ),
			'section' => 'probo_components',
			'choices' => array(
				'Rand'    => __( 'Border', 'probo-connect' ),
				'Schaduw' => __( 'Shadow', 'probo-connect' ),
				'Vlak'    => __( 'Flat', 'probo-connect' ),
			),
		),
		'probo_sanitize_card_style'
	);

	$add(
		'checkout_style',
		array(
			'type'        => 'select',
			'label'       => __( 'Checkout style', 'probo-connect' ),
			'description' => __( 'The step version collapses the checkout to one open step, turns the delivery choice into a single decision, and puts the order button in step 3. The classic version is the long page with all sections stacked.', 'probo-connect' ),
			'section'     => 'probo_components',
			'choices'     => array(
				'Eén pagina' => __( 'One page (classic)', 'probo-connect' ),
				'Stappen'    => __( 'Steps (accordion)', 'probo-connect' ),
			),
		),
		'probo_sanitize_checkout_style'
	);

	// Live-edit the text bits that carry no derived styling.
	foreach ( array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3', 'checkout_phone', 'footer_description', 'footer_legal' ) as $key ) {
		$setting = $wp_customize->get_setting( 'probo_' . $key );

		if ( $setting ) {
			$wp_customize->selective_refresh->add_partial(
				'probo_' . $key,
				array(
					'selector'        => '[data-probo-partial="' . $key . '"]',
					'render_callback' => static function () use ( $key ) {
						return esc_html( probo_get( $key ) );
					},
				)
			);
		}
	}
}
add_action( 'customize_register', 'probo_customize_register' );

/**
 * Clamp the radius to the range the design was drawn against.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function probo_sanitize_radius( $value ) {
	return max( 0, min( 16, (int) $value ) );
}

/**
 * Hex colour that is allowed to be empty ("follow the other setting").
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_optional_hex( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	return (string) sanitize_hex_color( $value );
}

/**
 * Sanitize the title font.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_title_font( $value ) {
	return isset( probo_font_choices( 'title' )[ $value ] ) ? $value : 'Archivo';
}

/**
 * Sanitize the body font.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_body_font( $value ) {
	return isset( probo_font_choices( 'body' )[ $value ] ) ? $value : 'Archivo';
}

/**
 * Sanitize the header variant.
 *
 * Only the two known variants are allowed; anything else falls back to the
 * spacious default, so the compact markup never loads by accident.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_header_variant( $value ) {
	return in_array( $value, array( 'ruim', 'compact' ), true ) ? $value : 'ruim';
}

/**
 * Sanitize the top-bar style.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_bar_style( $value ) {
	return in_array( $value, array( 'Zwart', 'Licht', 'Accent', 'Geen' ), true ) ? $value : 'Zwart';
}

/**
 * Sanitize the footer style.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_footer_style( $value ) {
	return in_array( $value, array( 'Zwart', 'Licht', 'Wit', 'Accent' ), true ) ? $value : 'Zwart';
}

/**
 * Sanitize the hero style.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_hero_style( $value ) {
	return in_array( $value, array( 'Zwart', 'Accent', 'Licht' ), true ) ? $value : 'Zwart';
}

/**
 * Sanitize the card style.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function probo_sanitize_card_style( $value ) {
	return in_array( $value, array( 'Rand', 'Schaduw', 'Vlak' ), true ) ? $value : 'Rand';
}

/**
 * Sanitize the checkout style.
 *
 * @param string $value Raw value.
 * @return string
 */
function probo_sanitize_checkout_style( $value ) {
	return in_array( $value, array( 'Eén pagina', 'Stappen' ), true ) ? $value : 'Eén pagina';
}
