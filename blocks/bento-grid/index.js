/**
 * Bento grid block — editor.
 *
 * Tiles are stored as an array of objects rather than the theme's pipe-separated
 * line format, because each one carries a media id: an attachment reference does
 * not belong in a string a shop owner edits by hand.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	var SPANS = [
		{ label: __( 'Normal', 'probo-connect-theme' ), value: 'Normaal' },
		{ label: __( 'Wide (2 columns)', 'probo-connect-theme' ), value: 'Breed' },
		{ label: __( 'Tall (2 rows)', 'probo-connect-theme' ), value: 'Hoog' },
		{ label: __( 'Large (2 × 2)', 'probo-connect-theme' ), value: 'Groot' },
	];

	wp.blocks.registerBlockType( 'probo/bento-grid', {
		edit: function ( props ) {
			var a = props.attributes;
			var tiles = a.tiles || [];

			function commit( next ) {
				props.setAttributes( { tiles: next } );
			}

			function patch( index, changes ) {
				commit(
					tiles.map( function ( tile, i ) {
						return i === index ? Object.assign( {}, tile, changes ) : tile;
					} )
				);
			}

			function move( index, offset ) {
				var next = tiles.slice();
				var moved = next.splice( index, 1 )[ 0 ];

				next.splice( index + offset, 0, moved );
				commit( next );
			}

			var rows = tiles.map( function ( tile, index ) {
				return el(
					'div',
					{
						key: 'tile-' + index,
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
							disabled: index === tiles.length - 1,
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
									tiles.filter( function ( _, i ) {
										return i !== index;
									} )
								);
							},
						} )
					),
					el(
						blockEditor.MediaUploadCheck,
						null,
						el( blockEditor.MediaUpload, {
							allowedTypes: [ 'image' ],
							value: tile.id,
							onSelect: function ( media ) {
								patch( index, { id: media.id } );
							},
							render: function ( opener ) {
								return el(
									components.Button,
									{ variant: 'secondary', onClick: opener.open },
									tile.id
										? __( 'Replace image', 'probo-connect-theme' )
										: __( 'Choose image', 'probo-connect-theme' )
								);
							},
						} )
					),
					el( components.SelectControl, {
						label: __( 'Size', 'probo-connect-theme' ),
						value: tile.span || 'Normaal',
						options: SPANS,
						onChange: function ( value ) {
							patch( index, { span: value } );
						},
					} ),
					el( components.TextControl, {
						label: __( 'Caption', 'probo-connect-theme' ),
						value: tile.caption || '',
						onChange: function ( value ) {
							patch( index, { caption: value } );
						},
					} ),
					el( components.TextControl, {
						label: __( 'Link', 'probo-connect-theme' ),
						help: __( 'Optional. Leave empty to make the tile unclickable.', 'probo-connect-theme' ),
						value: tile.url || '',
						onChange: function ( value ) {
							patch( index, { url: value } );
						},
					} )
				);
			} );

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
							label: __( 'Intro text', 'probo-connect-theme' ),
							rows: 3,
							value: a.intro,
							onChange: function ( value ) {
								props.setAttributes( { intro: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Tiles', 'probo-connect-theme' ) },
						rows,
						el(
							components.Button,
							{
								variant: 'secondary',
								onClick: function () {
									commit( tiles.concat( [ { id: 0, span: 'Normaal', caption: '', url: '' } ] ) );
								},
							},
							__( 'Add tile', 'probo-connect-theme' )
						)
					),
					el(
						components.PanelBody,
						{ title: __( 'Display', 'probo-connect-theme' ), initialOpen: false },
						el( components.RangeControl, {
							label: __( 'Row height', 'probo-connect-theme' ),
							help: __( 'Height of one row; large tiles are two rows tall.', 'probo-connect-theme' ),
							value: a.height,
							min: 120,
							max: 480,
							step: 10,
							onChange: function ( value ) {
								props.setAttributes( { height: value === undefined ? 240 : value } );
							},
						} )
					)
				),
				tiles.length
					? el( wp.serverSideRender, { block: 'probo/bento-grid', attributes: a } )
					: el(
							components.Placeholder,
							{
								icon: 'layout',
								label: __( 'Bento grid', 'probo-connect-theme' ),
								instructions: __(
									'Voeg tegels toe in de zijbalk onder "Tegels".',
									'probo-connect-theme'
								),
							}
					  )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
