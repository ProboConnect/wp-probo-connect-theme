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
			'title'       => __( 'Theme settings', 'probo-connect-theme' ),
			'description' => __( 'Colors, typography, and the structure of header and footer.', 'probo-connect-theme' ),
			'priority'    => 20,
		)
	);

	$sections = array(
		'probo_brand'      => __( 'Brand', 'probo-connect-theme' ),
		'probo_typography' => __( 'Typography', 'probo-connect-theme' ),
		'probo_chrome'     => __( 'Header & footer', 'probo-connect-theme' ),
		'probo_components' => __( 'Components', 'probo-connect-theme' ),
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

	/**
	 * The sanitize callback for a multiple-choice setting.
	 *
	 * Every one of these used to be a named function that spelled its own
	 * options out a second time, next to the control that already listed them.
	 * They are all the same rule — "a value from probo_choices(), or the
	 * default" — so there is one of them now, closing over the key.
	 *
	 * @param string $key Setting key without the probo_ prefix.
	 * @return callable
	 */
	$choice = static function ( $key ) {
		return static function ( $value ) use ( $key ) {
			return probo_normalize_choice( $key, $value );
		};
	};

	// --- Merk -------------------------------------------------------------
	// Colours stay on 'refresh': every one of them feeds derived tokens
	// (contrast-aware bar, footer and hero values) that are computed in PHP.
	// Recomputing that logic in a preview script would mean maintaining it twice.
	$add(
		'accent_color',
		array(
			'type'    => 'color',
			'label'   => __( 'Accent color', 'probo-connect-theme' ),
			'section' => 'probo_brand',
		),
		'sanitize_hex_color'
	);

	$add(
		'secondary_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Secondary', 'probo-connect-theme' ),
			'description' => __( 'Controls all dark surfaces: hero, top bar, footer, cart button, price bars, and summary boxes.', 'probo-connect-theme' ),
			'section'     => 'probo_brand',
		),
		'sanitize_hex_color'
	);

	$add(
		'page_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Page background', 'probo-connect-theme' ),
			'description' => __( 'The canvas behind every page. The section bands, wells and hairlines follow it. Keep it light — cards, the header and the text colour are built for a light page.', 'probo-connect-theme' ),
			'section'     => 'probo_brand',
		),
		'sanitize_hex_color'
	);

	$add(
		'radius',
		array(
			'type'        => 'number',
			'label'       => __( 'Radius', 'probo-connect-theme' ),
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
			'label'   => __( 'Heading font', 'probo-connect-theme' ),
			'section' => 'probo_typography',
			'choices' => probo_font_choices( 'title' ),
		),
		'probo_sanitize_title_font'
	);

	$add(
		'body_font',
		array(
			'type'        => 'select',
			'label'       => __( 'Body font', 'probo-connect-theme' ),
			'description' => __( 'Specifications and prices always stay IBM Plex Mono.', 'probo-connect-theme' ),
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
			'label'   => __( 'Header style', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
			'choices' => probo_choices( 'header_variant' ),
		),
		$choice( 'header_variant' )
	);

	$add(
		'bar_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Top bar', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
			'choices' => probo_choices( 'bar_style' ),
		),
		$choice( 'bar_style' )
	);

	$add(
		'bar_color',
		array(
			'type'        => 'color',
			'label'       => __( 'Top bar color', 'probo-connect-theme' ),
			'description' => __( 'Optional custom color. Leave empty = follow the choice above.', 'probo-connect-theme' ),
			'section'     => 'probo_chrome',
		),
		'probo_sanitize_optional_hex'
	);

	$add(
		'footer_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Footer', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
			'choices' => probo_choices( 'footer_style' ),
		),
		$choice( 'footer_style' )
	);

	// WordPress core supports exactly one custom logo and has no light/dark
	// variant, so the second image lives here and is used wherever the
	// background is dark (footer, and the top bar when it is a dark block).
	$add(
		'logo_light',
		array(
			'type'        => 'image',
			'label'       => __( 'Logo (light)', 'probo-connect-theme' ),
			'description' => __( 'For dark surfaces like the footer. Leave empty = the regular logo will be used.', 'probo-connect-theme' ),
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
				'label'   => sprintf( __( 'Top bar USP %d', 'probo-connect-theme' ), $index + 1 ),
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
			'label'   => __( 'Search field placeholder', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	$add(
		'checkout_phone',
		array(
			'type'        => 'text',
			'label'       => __( 'Phone number on checkout', 'probo-connect-theme' ),
			'description' => __( 'Shown on the right in the plain checkout header. Leave empty to hide it.', 'probo-connect-theme' ),
			'section'     => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	$add(
		'footer_description',
		array(
			'type'    => 'textarea',
			'label'   => __( 'Footer intro text', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
		),
		'sanitize_textarea_field',
		'postMessage'
	);

	foreach ( array( 'footer_col_1_title', 'footer_col_2_title', 'footer_col_3_title' ) as $index => $key ) {
		$add(
			$key,
			array(
				'type'        => 'text',
				/* translators: %d: position of the link column in the footer. */
				'label'       => sprintf( __( 'Footer column %d heading', 'probo-connect-theme' ), $index + 1 ),
				'description' => 1 === $index + 1 ? __( 'Heading above the link columns. Leave empty to hide the heading.', 'probo-connect-theme' ) : '',
				'section'     => 'probo_chrome',
			),
			'sanitize_text_field',
			'postMessage'
		);
	}

	$add(
		'footer_legal',
		array(
			'type'    => 'text',
			'label'   => __( 'Footer legal line', 'probo-connect-theme' ),
			'section' => 'probo_chrome',
		),
		'sanitize_text_field',
		'postMessage'
	);

	// --- Componenten ------------------------------------------------------
	$add(
		'card_style',
		array(
			'type'    => 'select',
			'label'   => __( 'Card style', 'probo-connect-theme' ),
			'section' => 'probo_components',
			'choices' => probo_choices( 'card_style' ),
		),
		$choice( 'card_style' )
	);

	$add(
		'checkout_style',
		array(
			'type'        => 'select',
			'label'       => __( 'Checkout style', 'probo-connect-theme' ),
			'description' => __( 'The step version collapses the checkout to one open step, turns the delivery choice into a single decision, and puts the order button in step 3. The classic version is the long page with all sections stacked.', 'probo-connect-theme' ),
			'section'     => 'probo_components',
			'choices'     => probo_choices( 'checkout_style' ),
		),
		$choice( 'checkout_style' )
	);

	$add(
		'configurator_label',
		array(
			'type'        => 'text',
			'label'       => __( 'Configurator wording', 'probo-connect-theme' ),
			'description' => __( 'The button on the product page and the heading above the configurator. Leave empty for the theme\'s own wording, which follows the site language.', 'probo-connect-theme' ),
			'section'     => 'probo_components',
			'input_attrs' => array( 'placeholder' => __( 'Configure your product', 'probo-connect-theme' ) ),
		),
		'sanitize_text_field'
	);

	$add(
		'require_login',
		array(
			'type'        => 'select',
			'label'       => __( 'Login required', 'probo-connect-theme' ),
			'description' => __( 'How much of the shop needs an account. Off, it sells to anyone who walks in. At the checkout a visitor can still fill a cart but has to log in to order it — the cart survives the login. From the cart nothing goes in without an account. The whole site closes everything: every page sends a logged-out visitor to the login form, which is what a closed order portal is.', 'probo-connect-theme' ),
			'section'     => 'probo_components',
			'choices'     => probo_choices( 'require_login' ),
		),
		$choice( 'require_login' )
	);

	// Live-edit the text bits that carry no derived styling.
	foreach ( array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3', 'checkout_phone', 'footer_description', 'footer_col_1_title', 'footer_col_2_title', 'footer_col_3_title', 'footer_legal' ) as $key ) {
		$setting = $wp_customize->get_setting( 'probo_' . $key );

		if ( $setting ) {
			$wp_customize->selective_refresh->add_partial(
				'probo_' . $key,
				array(
					'selector'        => '[data-pp-partial="' . $key . '"]',
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






