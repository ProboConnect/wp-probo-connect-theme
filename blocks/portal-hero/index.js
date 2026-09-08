/**
 * Portal hero block — editor.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/portal-hero', {
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
							label: __( 'Eyebrow', 'probo-connect-theme' ),
							value: a.eyebrow,
							onChange: function ( value ) {
								props.setAttributes( { eyebrow: value } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Title', 'probo-connect-theme' ),
							help: __( 'Use {name} where the signed-in customer’s name should appear.', 'probo-connect-theme' ),
							value: a.title,
							onChange: function ( value ) {
								props.setAttributes( { title: value } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Placeholder name', 'probo-connect-theme' ),
							help: __( 'Shown instead of {name} when nobody is signed in.', 'probo-connect-theme' ),
							value: a.placeholderName,
							onChange: function ( value ) {
								props.setAttributes( { placeholderName: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Image', 'probo-connect-theme' ) },
						el(
							blockEditor.MediaUploadCheck,
							null,
							el( blockEditor.MediaUpload, {
								allowedTypes: [ 'image' ],
								value: a.imageId,
								onSelect: function ( media ) {
									props.setAttributes( { imageId: media.id } );
								},
								render: function ( opener ) {
									return el(
										components.Button,
										{ variant: 'secondary', onClick: opener.open },
										a.imageId ? __( 'Replace image', 'probo-connect-theme' ) : __( 'Choose image', 'probo-connect-theme' )
									);
								},
							} )
						),
						a.imageId
							? el(
									components.Button,
									{
										variant: 'link',
										isDestructive: true,
										onClick: function () {
											props.setAttributes( { imageId: 0 } );
										},
									},
									__( 'Remove image', 'probo-connect-theme' )
							  )
							: null
					)
				),
				el( wp.serverSideRender, { block: 'probo/portal-hero', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
