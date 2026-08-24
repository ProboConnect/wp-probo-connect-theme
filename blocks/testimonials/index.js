/**
 * Testimonials block — editor.
 *
 * Quotes use the theme's line format, so the shared repeater from
 * blocks/shared/repeater.js provides the per-field inputs.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/testimonials', {
		edit: function ( props ) {
			var a = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Content', 'probo-connect' ) },
						el( components.TextControl, {
							label: __( 'Title', 'probo-connect' ),
							value: a.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						el( 'p', { style: { margin: '16px 0 8px', fontWeight: 500 } }, __( 'Quotes', 'probo-connect' ) ),
						window.probo.repeater( {
							value: a.items,
							fields: [
								{ label: __( 'Quote', 'probo-connect' ) },
								{ label: __( 'Name', 'probo-connect' ) },
								{ label: __( 'Company', 'probo-connect' ) },
								{ label: __( 'Score (0–5)', 'probo-connect' ) },
							],
							addLabel: __( 'Add quote', 'probo-connect' ),
							onChange: function ( value ) {
								props.setAttributes( { items: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Display', 'probo-connect' ), initialOpen: false },
						el( components.SelectControl, {
							label: __( 'Background', 'probo-connect' ),
							value: a.tone,
							options: [
								{ label: __( 'Light', 'probo-connect' ), value: 'Licht' },
								{ label: __( 'Dark (follow secondary)', 'probo-connect' ), value: 'Donker' },
							],
							onChange: function ( value ) {
								props.setAttributes( { tone: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/testimonials', attributes: a } )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
