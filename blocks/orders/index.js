/**
 * Orders block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/orders', {
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
						el( components.ToggleControl, {
							label: __( 'Link to all orders', 'probo-connect-theme' ),
							help: __( 'Points at the Orders tab of My account.', 'probo-connect-theme' ),
							checked: a.showAll,
							onChange: function ( value ) {
								props.setAttributes( { showAll: value } );
							},
						} ),
						a.showAll
							? el( components.TextControl, {
									label: __( 'Link text', 'probo-connect-theme' ),
									value: a.allText,
									onChange: function ( value ) {
										props.setAttributes( { allText: value } );
									},
							  } )
							: null,
						el( components.TextareaControl, {
							label: __( 'Text when the customer has no orders', 'probo-connect-theme' ),
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
							label: __( 'Number of orders', 'probo-connect-theme' ),
							min: 1,
							max: 25,
							value: a.count,
							onChange: function ( value ) {
								props.setAttributes( { count: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/orders', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
