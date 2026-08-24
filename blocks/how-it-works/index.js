/**
 * "Zo werkt het" block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/how-it-works', {
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
						el( 'p', { style: { margin: '16px 0 8px', fontWeight: 500 } }, __( 'Steps', 'probo-connect' ) ),
						window.probo.repeater( {
							value: a.steps,
							fields: [
								{ label: __( 'Number', 'probo-connect' ) },
								{ label: __( 'Title', 'probo-connect' ) },
								{ label: __( 'Description', 'probo-connect' ) },
							],
							addLabel: __( 'Add step', 'probo-connect' ),
							onChange: function ( value ) {
								props.setAttributes( { steps: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/how-it-works', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
