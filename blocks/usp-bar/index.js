/**
 * USP-balk block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/usp-bar', {
		edit: function ( props ) {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'USPs', 'probo-connect' ) },
						window.probo.repeater( {
							value: props.attributes.items,
							fields: [
								{ label: __( 'Title', 'probo-connect' ) },
								{ label: __( 'Description', 'probo-connect' ) },
							],
							addLabel: __( 'Add USP', 'probo-connect' ),
							onChange: function ( value ) {
								props.setAttributes( { items: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/usp-bar', attributes: props.attributes } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
