/**
 * Tier prices block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/tiers', {
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
						{ title: __( 'Tier prices', 'probo-connect-theme' ) },
						el( components.TextControl, {
							label: __( 'Title', 'probo-connect-theme' ),
							value: a.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						el( components.SelectControl, {
							label: __( 'Layout', 'probo-connect-theme' ),
							value: a.view,
							options: [
								{ label: __( 'Price list — every material', 'probo-connect-theme' ), value: 'table' },
								{ label: __( 'One row — cheapest material', 'probo-connect-theme' ), value: 'summary' },
							],
							onChange: function ( value ) {
								props.setAttributes( { view: value } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Product ID', 'probo-connect-theme' ),
							help: __( '0 uses the product of the page.', 'probo-connect-theme' ),
							type: 'number',
							min: 0,
							value: a.productId,
							onChange: function ( value ) {
								props.setAttributes( { productId: parseInt( value, 10 ) || 0 } );
							},
						} ),
						'summary' === a.view
							? el( components.TextControl, {
									label: __( 'Material (API code)', 'probo-connect-theme' ),
									help: __( 'Leave empty for the cheapest material.', 'probo-connect-theme' ),
									value: a.apiCode,
									onChange: function ( value ) {
										props.setAttributes( { apiCode: value } );
									},
							  } )
							: el( components.TextControl, {
									label: __( 'Material group', 'probo-connect-theme' ),
									help: __( 'Optional, for example MA.', 'probo-connect-theme' ),
									value: a.group,
									onChange: function ( value ) {
										props.setAttributes( { group: value } );
									},
							  } ),
						el( components.ToggleControl, {
							label: __( 'Show discount percentages', 'probo-connect-theme' ),
							checked: a.showDiscount,
							onChange: function ( value ) {
								props.setAttributes( { showDiscount: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/tiers', attributes: props.attributes } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
