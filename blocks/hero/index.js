/**
 * Hero block — editor.
 *
 * Plain browser JS against the wp.* globals: no JSX and no build step. The
 * preview is a ServerSideRender of render.php, so the editor canvas shows
 * exactly what the front end will output.
 *
 * The ten variants share one attribute set, and most of them use a subset of
 * it. Rather than show every field for every variant and let the editor guess
 * which ones do anything, each panel below declares the variants it applies to
 * and is left out for the rest.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;
	var ServerSideRender = wp.serverSideRender;

	// Mirrors probo_hero_variants() in inc/blocks.php. The letters are the
	// design handoff's own names for the variants.
	var VARIANTS = [
		{ value: 'A', label: __( 'A — Search hero, dark', 'probo-connect' ) },
		{ value: 'B', label: __( 'B — Editorial, large image', 'probo-connect' ) },
		{ value: 'C', label: __( 'C — Full-bleed image, centred', 'probo-connect' ) },
		{ value: 'D', label: __( 'D — Showroom, category tiles', 'probo-connect' ) },
		{ value: 'E', label: __( 'E — Minimal, centred, light', 'probo-connect' ) },
		{ value: 'F', label: __( 'F — USP rail, dark, B2B', 'probo-connect' ) },
		{ value: 'G', label: __( 'G — Split with review card', 'probo-connect' ) },
		{ value: 'H', label: __( 'H — Promotion, accent', 'probo-connect' ) },
		{ value: 'I', label: __( 'I — Search hero, light with tags', 'probo-connect' ) },
		{ value: 'J', label: __( 'J — Showreel, image with play', 'probo-connect' ) },
	];

	// Which variants read which attribute. Anything not listed here is read by
	// every variant that has a place for it.
	var USES = {
		search: 'AI',
		chips: 'AI',
		buttons: 'BCEFGJ',
		secondButton: 'BCFGJ',
		promoButton: 'H',
		link: 'D',
		usps: 'BE',
		tiles: 'BD',
		stats: 'F',
		review: 'G',
		countdown: 'H',
		video: 'J',
		media: 'ABCGJ',
		heroTokens: 'A',
		overlay: 'A',
	};

	wp.blocks.registerBlockType( 'probo/hero', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var variant = String( a.variant || 'A' ).toUpperCase();

			function uses( key ) {
				return USES[ key ].indexOf( variant ) !== -1;
			}

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

			function lines( key, fields, addLabel ) {
				return window.probo.repeater( {
					value: a[ key ],
					fields: fields,
					addLabel: addLabel,
					onChange: function ( value ) {
						var patch = {};
						patch[ key ] = value;
						set( patch );
					},
				} );
			}

			var mediaPanel = el(
				components.PanelBody,
				{ title: __( 'Image', 'probo-connect' ) },
				uses( 'overlay' )
					? field( components.SelectControl, 'overlay', __( 'Gradient over the image', 'probo-connect' ), {
							options: [
								{ label: __( 'None', 'probo-connect' ), value: '' },
								{ label: __( 'From the bottom', 'probo-connect' ), value: 'Onder' },
								{ label: __( 'From the left', 'probo-connect' ), value: 'Links' },
								{ label: __( 'All around (vignette)', 'probo-connect' ), value: 'Rondom' },
							],
							help: __( 'Blends into the hero background color, so it adapts to the style.', 'probo-connect' ),
					  } )
					: null,
				uses( 'overlay' ) && a.overlay
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
			);

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				el(
					components.PanelBody,
					{ title: __( 'Variant', 'probo-connect' ) },
					field( components.SelectControl, 'variant', __( 'Hero variant', 'probo-connect' ), {
						options: VARIANTS,
						help: __( 'Every variant reads the same content; the fields below are the ones this one uses.', 'probo-connect' ),
					} ),
					'B' === variant
						? field( components.SelectControl, 'heroMedia', __( 'Image position', 'probo-connect' ), {
								options: [
									{ label: __( 'Right', 'probo-connect' ), value: 'Rechts' },
									{ label: __( 'Left', 'probo-connect' ), value: 'Links' },
									{ label: __( 'None', 'probo-connect' ), value: 'Geen' },
								],
						  } )
						: null
				),
				el(
					components.PanelBody,
					{ title: __( 'Content', 'probo-connect' ) },
					'I' === variant
						? null
						: field( components.TextControl, 'eyebrow', __( 'Label above the title', 'probo-connect' ) ),
					field( components.TextareaControl, 'title', __( 'Title', 'probo-connect' ), {
						help: __( 'A new line becomes a line break.', 'probo-connect' ),
					} ),
					field( components.TextareaControl, 'subtitle', __( 'Intro text', 'probo-connect' ) ),
					uses( 'search' )
						? el( components.ToggleControl, {
								label: __( 'Show search bar', 'probo-connect' ),
								checked: a.showSearch,
								onChange: function ( value ) {
									set( { showSearch: value } );
								},
						  } )
						: null,
					uses( 'chips' )
						? field( components.TextControl, 'chips', __( 'Shortcuts', 'probo-connect' ), {
								help: __( 'Comma-separated.', 'probo-connect' ),
						  } )
						: null
				),
				uses( 'buttons' ) || uses( 'promoButton' ) || uses( 'link' )
					? el(
							components.PanelBody,
							{ title: __( 'Buttons', 'probo-connect' ), initialOpen: false },
							uses( 'buttons' ) || uses( 'promoButton' )
								? field( components.TextControl, 'primaryLabel', __( 'Primary button', 'probo-connect' ) )
								: null,
							uses( 'buttons' ) || uses( 'promoButton' )
								? field( components.TextControl, 'primaryUrl', __( 'Primary button link', 'probo-connect' ) )
								: null,
							uses( 'secondButton' )
								? field( components.TextControl, 'secondaryLabel', __( 'Secondary button', 'probo-connect' ) )
								: null,
							uses( 'secondButton' )
								? field( components.TextControl, 'secondaryUrl', __( 'Secondary button link', 'probo-connect' ) )
								: null,
							uses( 'link' ) ? field( components.TextControl, 'linkLabel', __( 'Overview link', 'probo-connect' ) ) : null,
							uses( 'link' ) ? field( components.TextControl, 'linkUrl', __( 'Overview link URL', 'probo-connect' ) ) : null
					  )
					: null,
				uses( 'usps' )
					? el(
							components.PanelBody,
							{ title: __( 'USPs', 'probo-connect' ), initialOpen: false },
							lines( 'usps', [ { label: __( 'USP', 'probo-connect' ) } ], __( 'Add USP', 'probo-connect' ) )
					  )
					: null,
				uses( 'tiles' )
					? el(
							components.PanelBody,
							{ title: __( 'Category tiles', 'probo-connect' ), initialOpen: false },
							lines(
								'tiles',
								[
									{ label: __( 'Name', 'probo-connect' ) },
									{ label: __( 'Link', 'probo-connect' ) },
									{ label: __( 'From price', 'probo-connect' ) },
								],
								__( 'Add tile', 'probo-connect' )
							),
							el(
								'p',
								{ style: { fontSize: '12px', color: '#6B6B70' } },
								'B' === variant
									? __( 'Variant B shows the first two tiles.', 'probo-connect' )
									: __( 'Variant D shows four tiles; the last one is drawn in the accent colour.', 'probo-connect' )
							)
					  )
					: null,
				uses( 'stats' )
					? el(
							components.PanelBody,
							{ title: __( 'Figures', 'probo-connect' ), initialOpen: false },
							lines(
								'stats',
								[ { label: __( 'Figure', 'probo-connect' ) }, { label: __( 'Explanation', 'probo-connect' ) } ],
								__( 'Add figure', 'probo-connect' )
							)
					  )
					: null,
				uses( 'review' )
					? el(
							components.PanelBody,
							{ title: __( 'Review card', 'probo-connect' ), initialOpen: false },
							field( components.TextareaControl, 'reviewQuote', __( 'Quote', 'probo-connect' ) ),
							field( components.TextControl, 'reviewAuthor', __( 'Name and company', 'probo-connect' ) )
					  )
					: null,
				uses( 'countdown' )
					? el(
							components.PanelBody,
							{ title: __( 'Campaign', 'probo-connect' ), initialOpen: false },
							field( components.TextControl, 'countdownUntil', __( 'Runs until', 'probo-connect' ), {
								type: 'datetime-local',
								help: __( 'Leave empty to hide the countdown.', 'probo-connect' ),
							} )
					  )
					: null,
				uses( 'video' )
					? el(
							components.PanelBody,
							{ title: __( 'Film', 'probo-connect' ), initialOpen: false },
							field( components.TextControl, 'videoUrl', __( 'Link to the film', 'probo-connect' ), {
								help: __( 'Without a link the play button is decoration only.', 'probo-connect' ),
							} )
					  )
					: null,
				el(
					components.PanelBody,
					{ title: __( 'Display', 'probo-connect' ), initialOpen: false },
					uses( 'heroTokens' )
						? field( components.SelectControl, 'heroStyle', __( 'Hero style', 'probo-connect' ), {
								options: [
									{ label: __( 'Follow secondary', 'probo-connect' ), value: 'Zwart' },
									{ label: __( 'Accent', 'probo-connect' ), value: 'Accent' },
									{ label: __( 'Light', 'probo-connect' ), value: 'Licht' },
								],
								help: __( 'The band this hero sits on, derived from the brand colours.', 'probo-connect' ),
						  } )
						: el(
								'p',
								{ style: { fontSize: '12px', color: '#6B6B70' } },
								__( 'This variant has a band of its own, so the hero style does not apply to it.', 'probo-connect' )
						  ),
					uses( 'heroTokens' ) ? el( 'p', { style: { marginBottom: '4px' } }, __( 'Title color', 'probo-connect' ) ) : null,
					uses( 'heroTokens' )
						? el( components.ColorPalette, {
								value: a.titleColor,
								onChange: function ( value ) {
									set( { titleColor: value || '' } );
								},
						  } )
						: null,
					uses( 'heroTokens' )
						? el(
								'p',
								{ style: { fontSize: '12px', color: '#6B6B70' } },
								__( 'A color too close to the background is ignored, so the title stays readable.', 'probo-connect' )
						  )
						: null
				),
				uses( 'media' ) ? mediaPanel : null
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
				uses( 'media' ) ? toolbar : null,
				inspector,
				el( ServerSideRender, { block: 'probo/hero', attributes: a } )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
