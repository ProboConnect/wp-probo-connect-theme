/**
 * Categorietegels block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/category-grid', {
		edit: function ( props ) {
			var a = props.attributes;

			function text( key, label, help ) {
				return el( components.TextControl, {
					label: label,
					help: help,
					value: a[ key ],
					onChange: function ( value ) {
						var patch = {};
						patch[ key ] = value;
						props.setAttributes( patch );
					},
				} );
			}

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Content', 'probo-connect-theme' ) },
						text( 'heading', __( 'Title', 'probo-connect-theme' ) ),
						text( 'linkText', __( 'Link top right', 'probo-connect-theme' ) ),
						text( 'linkUrl', __( 'Link URL', 'probo-connect-theme' ), __( 'Leave empty = the WooCommerce shop page.', 'probo-connect-theme' ) )
					),
					el(
						components.PanelBody,
						{ title: __( 'Callout', 'probo-connect-theme' ), initialOpen: false },
						el( components.ToggleControl, {
							label: __( 'Show category callouts', 'probo-connect-theme' ),
							help: __(
								'Een categorie met een eigen callout (Producten → Categorieën) krijgt die als tegel achter zich.',
								'probo-connect-theme'
							),
							checked: a.showTermCallouts,
							onChange: function ( value ) {
								props.setAttributes( { showTermCallouts: value } );
							},
						} ),
						el( 'hr', { style: { margin: '16px 0' } } ),
						el( components.ToggleControl, {
							label: __( 'Show own callout', 'probo-connect-theme' ),
							help: __( 'A standalone tile for this block, not linked to a category.', 'probo-connect-theme' ),
							checked: a.showCallout,
							onChange: function ( value ) {
								props.setAttributes( { showCallout: value } );
							},
						} ),
						a.showCallout
							? el(
									wp.element.Fragment,
									null,
									text( 'calloutTitle', __( 'Title', 'probo-connect-theme' ) ),
									el( components.TextareaControl, {
										label: __( 'Text', 'probo-connect-theme' ),
										rows: 4,
										value: a.calloutText,
										onChange: function ( value ) {
											props.setAttributes( { calloutText: value } );
										},
									} ),
									text( 'calloutCta', __( 'Button text', 'probo-connect-theme' ) ),
									text( 'calloutUrl', __( 'Button URL', 'probo-connect-theme' ), __( 'Leave empty = same link as top right.', 'probo-connect-theme' ) ),
									el( components.SelectControl, {
										label: __( 'Color', 'probo-connect-theme' ),
										value: a.calloutTone,
										options: [
											{ label: __( 'Accent', 'probo-connect-theme' ), value: 'Accent' },
											{ label: __( 'Secondary', 'probo-connect-theme' ), value: 'Secondary' },
										],
										onChange: function ( value ) {
											props.setAttributes( { calloutTone: value } );
										},
									} ),
									el( components.SelectControl, {
										label: __( 'Position', 'probo-connect-theme' ),
										value: a.calloutPosition,
										options: [
											{ label: __( 'At the end', 'probo-connect-theme' ), value: 'Eind' },
											{ label: __( 'At the start', 'probo-connect-theme' ), value: 'Begin' },
											{ label: __( 'Between the tiles', 'probo-connect-theme' ), value: 'Interval' },
										],
										onChange: function ( value ) {
											props.setAttributes( { calloutPosition: value } );
										},
									} ),
									a.calloutPosition === 'Interval'
										? el( components.RangeControl, {
												label: __( 'After every … tiles', 'probo-connect-theme' ),
												help: __(
													'De callout wordt herhaald. Zet dit gelijk aan het aantal kolommen voor één callout per rij.',
													'probo-connect-theme'
												),
												value: a.calloutInterval,
												min: 1,
												max: 12,
												onChange: function ( value ) {
													props.setAttributes( {
														calloutInterval: value === undefined ? 4 : value,
													} );
												},
										  } )
										: null
							  )
							: null
					),
					el(
						components.PanelBody,
						{ title: __( 'Selection', 'probo-connect-theme' ) },
						text( 'slugs', __( 'Category slugs', 'probo-connect-theme' ), __( 'Comma-separated. Leave empty = top-level categories, most populated first.', 'probo-connect-theme' ) ),
						el( components.RangeControl, {
							label: __( 'Number of tiles', 'probo-connect-theme' ),
							min: 2,
							max: 12,
							value: a.count,
							onChange: function ( value ) {
								props.setAttributes( { count: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/category-grid', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
