/**
 * Hero block — editor.
 *
 * Plain browser JS against the wp.* globals: no JSX and no build step. The
 * preview is a ServerSideRender of render.php, so the editor canvas shows
 * exactly what the front end will output.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'probo/hero', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			function field( Control, key, label, extra ) {
				return el(
					Control,
					Object.assign(
						{
							label: label,
							value: a[ key ],
							onChange: function ( value ) {
								var patch = {};
								patch[ key ] = value;
								set( patch );
							},
						},
						extra || {}
					)
				);
			}

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				el(
					components.PanelBody,
					{ title: __( 'Content', 'probo-connect' ) },
					field( components.TextControl, 'eyebrow', __( 'Label above the title', 'probo-connect' ) ),
					field( components.TextareaControl, 'title', __( 'Title', 'probo-connect' ), {
						help: __( 'A new line becomes a line break.', 'probo-connect' ),
					} ),
					field( components.TextareaControl, 'subtitle', __( 'Intro text', 'probo-connect' ) ),
					field( components.TextControl, 'chips', __( 'Shortcuts', 'probo-connect' ), {
						help: __( 'Comma-separated.', 'probo-connect' ),
					} ),
					el( components.ToggleControl, {
						label: __( 'Show search bar', 'probo-connect' ),
						checked: a.showSearch,
						onChange: function ( value ) {
							set( { showSearch: value } );
						},
					} )
				),
				el(
					components.PanelBody,
					{ title: __( 'Display', 'probo-connect' ), initialOpen: false },
					field( components.SelectControl, 'heroStyle', __( 'Hero style', 'probo-connect' ), {
						options: [
							{ label: __( 'Follow the Customizer', 'probo-connect' ), value: '' },
							{ label: __( 'Follow secondary', 'probo-connect' ), value: 'Zwart' },
							{ label: __( 'Accent', 'probo-connect' ), value: 'Accent' },
							{ label: __( 'Light', 'probo-connect' ), value: 'Licht' },
						],
					} ),
					el( 'p', { style: { marginBottom: '4px' } }, __( 'Title color', 'probo-connect' ) ),
					el( components.ColorPalette, {
						value: a.titleColor,
						onChange: function ( value ) {
							set( { titleColor: value || '' } );
						},
					} ),
					el(
						'p',
						{ style: { fontSize: '12px', color: '#6B6B70' } },
						__( 'A color too close to the background is ignored, so the title stays readable.', 'probo-connect' )
					)
				),
				el(
					components.PanelBody,
					{ title: __( 'Image', 'probo-connect' ) },
					field( components.SelectControl, 'overlay', __( 'Gradient over the image', 'probo-connect' ), {
						options: [
							{ label: __( 'None', 'probo-connect' ), value: '' },
							{ label: __( 'From the bottom', 'probo-connect' ), value: 'Onder' },
							{ label: __( 'From the left', 'probo-connect' ), value: 'Links' },
							{ label: __( 'All around (vignette)', 'probo-connect' ), value: 'Rondom' },
						],
						help: __( 'Blends into the hero background color, so it adapts to the style.', 'probo-connect' ),
					} ),
					a.overlay
						? el( components.RangeControl, {
								label: __( 'Strength', 'probo-connect' ),
								value: a.overlayStrength,
								min: 0,
								max: 100,
								step: 5,
								onChange: function ( value ) {
									set( { overlayStrength: value === undefined ? 55 : value } );
								},
						  } )
						: null,
					el(
						blockEditor.MediaUploadCheck,
						null,
						el( blockEditor.MediaUpload, {
							allowedTypes: [ 'image' ],
							value: a.imageId,
							onSelect: function ( media ) {
								set( { imageId: media.id } );
							},
							render: function ( opener ) {
								return el(
									components.Button,
									{ variant: 'secondary', onClick: opener.open },
									a.imageId ? __( 'Replace image', 'probo-connect' ) : __( 'Choose image', 'probo-connect' )
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
										set( { imageId: 0 } );
									},
								},
								__( 'Remove image', 'probo-connect' )
						  )
						: null
				)
			);

			// The preview is a ServerSideRender, so nothing in the canvas is
			// directly editable. A toolbar button at least puts the image one
			// click away instead of three, the way core's media blocks do.
			var toolbar = el(
				blockEditor.BlockControls,
				null,
				el(
					components.ToolbarGroup,
					null,
					el(
						blockEditor.MediaUploadCheck,
						null,
						el( blockEditor.MediaUpload, {
							allowedTypes: [ 'image' ],
							value: a.imageId,
							onSelect: function ( media ) {
								set( { imageId: media.id } );
							},
							render: function ( opener ) {
								return el( components.ToolbarButton, {
									icon: 'format-image',
									label: a.imageId
										? __( 'Replace image', 'probo-connect' )
										: __( 'Choose image', 'probo-connect' ),
									onClick: opener.open,
								} );
							},
						} )
					)
				)
			);

			return el(
				'div',
				blockEditor.useBlockProps(),
				toolbar,
				inspector,
				el( ServerSideRender, { block: 'probo/hero', attributes: a } )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
