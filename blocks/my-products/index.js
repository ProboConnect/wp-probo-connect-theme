/**
 * My products block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/my-products', {
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
						{ title: __( 'Content', 'probo-connect-theme' ) },
						el( components.TextControl, {
							label: __( 'Title', 'probo-connect-theme' ),
							value: a.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						el( components.TextareaControl, {
							label: __( 'Intro', 'probo-connect-theme' ),
							value: a.intro,
							onChange: function ( value ) {
								props.setAttributes( { intro: value } );
							},
						} ),
						el( components.TextareaControl, {
							label: __( 'Text when the customer has no products', 'probo-connect-theme' ),
							help: __( 'Leave empty for the default line.', 'probo-connect-theme' ),
							value: a.empty,
							onChange: function ( value ) {
								props.setAttributes( { empty: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Selection', 'probo-connect-theme' ) },
						el( components.RangeControl, {
							label: __( 'Maximum number of products', 'probo-connect-theme' ),
							help: __( '0 shows every product this customer has.', 'probo-connect-theme' ),
							min: 0,
							max: 24,
							value: a.count,
							onChange: function ( value ) {
								props.setAttributes( { count: value } );
							},
						} ),
						el( components.SelectControl, {
							label: __( 'Order', 'probo-connect-theme' ),
							value: a.orderby,
							options: [
								{ label: __( 'Name', 'probo-connect-theme' ), value: 'title' },
								{ label: __( 'Newest first', 'probo-connect-theme' ), value: 'date' },
							],
							onChange: function ( value ) {
								props.setAttributes( { orderby: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/my-products', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
