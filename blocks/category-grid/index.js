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
						{ title: __( 'Content', 'probo-connect' ) },
						text( 'heading', __( 'Title', 'probo-connect' ) ),
						text( 'linkText', __( 'Link top right', 'probo-connect' ) ),
						text( 'linkUrl', __( 'Link URL', 'probo-connect' ), __( 'Leave empty = the WooCommerce shop page.', 'probo-connect' ) )
					),
					el(
						components.PanelBody,
						{ title: __( 'Callout', 'probo-connect' ), initialOpen: false },
						el( components.ToggleControl, {
							label: __( 'Show category callouts', 'probo-connect' ),
							help: __(
								'Een categorie met een eigen callout (Producten → Categorieën) krijgt die als tegel achter zich.',
								'probo-connect'
							),
							checked: a.showTermCallouts,
							onChange: function ( value ) {
								props.setAttributes( { showTermCallouts: value } );
							},
						} ),
						el( 'hr', { style: { margin: '16px 0' } } ),
						el( components.ToggleControl, {
							label: __( 'Show own callout', 'probo-connect' ),
							help: __( 'A standalone tile for this block, not linked to a category.', 'probo-connect' ),
							checked: a.showCallout,
							onChange: function ( value ) {
								props.setAttributes( { showCallout: value } );
							},
						} ),
						a.showCallout
							? el(
									wp.element.Fragment,
									null,
									text( 'calloutTitle', __( 'Title', 'probo-connect' ) ),
									el( components.TextareaControl, {
										label: __( 'Text', 'probo-connect' ),
										rows: 4,
										value: a.calloutText,
										onChange: function ( value ) {
											props.setAttributes( { calloutText: value } );
										},
									} ),
									text( 'calloutCta', __( 'Button text', 'probo-connect' ) ),
									text( 'calloutUrl', __( 'Button URL', 'probo-connect' ), __( 'Leave empty = same link as top right.', 'probo-connect' ) ),
									el( components.SelectControl, {
										label: __( 'Color', 'probo-connect' ),
										value: a.calloutTone,
										options: [
											{ label: __( 'Accent', 'probo-connect' ), value: 'Accent' },
											{ label: __( 'Secondary', 'probo-connect' ), value: 'Secondary' },
										],
										onChange: function ( value ) {
											props.setAttributes( { calloutTone: value } );
										},
									} ),
									el( components.SelectControl, {
										label: __( 'Position', 'probo-connect' ),
										value: a.calloutPosition,
										options: [
											{ label: __( 'At the end', 'probo-connect' ), value: 'Eind' },
											{ label: __( 'At the start', 'probo-connect' ), value: 'Begin' },
											{ label: __( 'Between the tiles', 'probo-connect' ), value: 'Interval' },
										],
										onChange: function ( value ) {
											props.setAttributes( { calloutPosition: value } );
										},
									} ),
									a.calloutPosition === 'Interval'
										? el( components.RangeControl, {
												label: __( 'After every … tiles', 'probo-connect' ),
												help: __(
													'De callout wordt herhaald. Zet dit gelijk aan het aantal kolommen voor één callout per rij.',
													'probo-connect'
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
						{ title: __( 'Selection', 'probo-connect' ) },
						text( 'slugs', __( 'Category slugs', 'probo-connect' ), __( 'Comma-separated. Leave empty = top-level categories, most populated first.', 'probo-connect' ) ),
						el( components.RangeControl, {
							label: __( 'Number of tiles', 'probo-connect' ),
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
