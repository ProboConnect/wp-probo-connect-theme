/**
 * Logo reel block — editor.
 *
 * Logos are picked as a multi-select from the media library, so a set of them
 * lands in one go instead of one dialog per logo.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/logo-reel', {
		edit: function ( props ) {
			var a = props.attributes;
			var logos = a.logos || [];

			function commit( next ) {
				props.setAttributes( { logos: next } );
			}

			function move( index, offset ) {
				var next = logos.slice();
				var moved = next.splice( index, 1 )[ 0 ];

				next.splice( index + offset, 0, moved );
				commit( next );
			}

			var rows = logos.map( function ( logo, index ) {
				return el(
					'div',
					{
						key: 'logo-' + index,
						style: {
							border: '1px solid #e0e0e0',
							borderRadius: '2px',
							padding: '12px',
							marginBottom: '12px',
						},
					},
					el(
						'div',
						{ style: { display: 'flex', gap: '4px', marginBottom: '8px' } },
						el( components.Button, {
							icon: 'arrow-up-alt2',
							size: 'small',
							disabled: index === 0,
							label: __( 'Up', 'probo-connect-theme' ),
							onClick: function () {
								move( index, -1 );
							},
						} ),
						el( components.Button, {
							icon: 'arrow-down-alt2',
							size: 'small',
							disabled: index === logos.length - 1,
							label: __( 'Down', 'probo-connect-theme' ),
							onClick: function () {
								move( index, 1 );
							},
						} ),
						el( components.Button, {
							icon: 'trash',
							size: 'small',
							isDestructive: true,
							label: __( 'Remove', 'probo-connect-theme' ),
							onClick: function () {
								commit(
									logos.filter( function ( _, i ) {
										return i !== index;
									} )
								);
							},
						} )
					),
					el( components.TextControl, {
						label: __( 'Name', 'probo-connect-theme' ),
						help: __( 'Becomes the logo alt text.', 'probo-connect-theme' ),
						value: logo.name || '',
						onChange: function ( value ) {
							commit(
								logos.map( function ( item, i ) {
									return i === index ? Object.assign( {}, item, { name: value } ) : item;
								} )
							);
						},
					} ),
					el( components.TextControl, {
						label: __( 'Link', 'probo-connect-theme' ),
						help: __( 'Optional.', 'probo-connect-theme' ),
						value: logo.url || '',
						onChange: function ( value ) {
							commit(
								logos.map( function ( item, i ) {
									return i === index ? Object.assign( {}, item, { url: value } ) : item;
								} )
							);
						},
					} )
				);
			} );

			var picker = el(
				blockEditor.MediaUploadCheck,
				null,
				el( blockEditor.MediaUpload, {
					allowedTypes: [ 'image' ],
					multiple: true,
					value: logos.map( function ( logo ) {
						return logo.id;
					} ),
					onSelect: function ( media ) {
						commit(
							media.map( function ( item ) {
								// Keep whatever was already typed for a logo that is
								// still in the selection.
								var existing = logos.filter( function ( logo ) {
									return logo.id === item.id;
								} )[ 0 ];

								return {
									id: item.id,
									name: existing ? existing.name : item.alt || item.title || '',
									url: existing ? existing.url : '',
								};
							} )
						);
					},
					render: function ( opener ) {
						return el(
							components.Button,
							{ variant: 'primary', onClick: opener.open },
							logos.length
								? __( 'Edit selection', 'probo-connect-theme' )
								: __( 'Choose logos', 'probo-connect-theme' )
						);
					},
				} )
			);

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Logos', 'probo-connect-theme' ) },
						picker,
						el( 'div', { style: { marginTop: '16px' } }, rows )
					),
					el(
						components.PanelBody,
						{ title: __( 'Display', 'probo-connect-theme' ), initialOpen: false },
						el( components.TextControl, {
							label: __( 'Label', 'probo-connect-theme' ),
							help: __( 'Leave empty to hide the label.', 'probo-connect-theme' ),
							value: a.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						el( components.ToggleControl, {
							label: __( 'Grayscale', 'probo-connect-theme' ),
							help: __( 'Logos gain color when you hover over them.', 'probo-connect-theme' ),
							checked: a.grayscale,
							onChange: function ( value ) {
								props.setAttributes( { grayscale: value } );
							},
						} ),
						el( components.RangeControl, {
							label: __( 'Logo height', 'probo-connect-theme' ),
							value: a.height,
							min: 16,
							max: 96,
							step: 2,
							onChange: function ( value ) {
								props.setAttributes( { height: value === undefined ? 32 : value } );
							},
						} )
					)
				),
				logos.length
					? el( wp.serverSideRender, { block: 'probo/logo-reel', attributes: a } )
					: el(
							components.Placeholder,
							{
								icon: 'images-alt2',
								label: __( 'Logo bar', 'probo-connect-theme' ),
								instructions: __( 'Choose logos in the sidebar.', 'probo-connect-theme' ),
							},
							picker
					  )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
