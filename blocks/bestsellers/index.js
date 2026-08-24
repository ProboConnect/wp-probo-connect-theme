/**
 * Bestsellers block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/bestsellers', {
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
						el( components.TextControl, {
							label: __( 'Label top right', 'probo-connect' ),
							value: a.meta,
							onChange: function ( value ) {
								props.setAttributes( { meta: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Selection', 'probo-connect' ) },
						el( components.SelectControl, {
							label: __( 'Source', 'probo-connect' ),
							value: a.source,
							options: [
								{ label: __( 'Best selling', 'probo-connect' ), value: 'best_selling' },
								{ label: __( 'Featured', 'probo-connect' ), value: 'featured' },
								{ label: __( 'Newest', 'probo-connect' ), value: 'recent' },
							],
							onChange: function ( value ) {
								props.setAttributes( { source: value } );
							},
						} ),
						el( components.RangeControl, {
							label: __( 'Number of products', 'probo-connect' ),
							min: 2,
							max: 12,
							value: a.count,
							onChange: function ( value ) {
								props.setAttributes( { count: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/bestsellers', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
