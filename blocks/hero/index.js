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
		{ value: 'A', label: __( 'A — Search hero, dark', 'probo-connect-theme' ) },
		{ value: 'B', label: __( 'B — Editorial, large image', 'probo-connect-theme' ) },
		{ value: 'C', label: __( 'C — Full-bleed image, centred', 'probo-connect-theme' ) },
		{ value: 'D', label: __( 'D — Showroom, category tiles', 'probo-connect-theme' ) },
		{ value: 'E', label: __( 'E — Minimal, centred, light', 'probo-connect-theme' ) },
		{ value: 'F', label: __( 'F — USP rail, dark, B2B', 'probo-connect-theme' ) },
		{ value: 'G', label: __( 'G — Split with review card', 'probo-connect-theme' ) },
		{ value: 'H', label: __( 'H — Promotion, accent', 'probo-connect-theme' ) },
		{ value: 'I', label: __( 'I — Search hero, light with tags', 'probo-connect-theme' ) },
		{ value: 'J', label: __( 'J — Showreel, image with play', 'probo-connect-theme' ) },
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
				{ title: __( 'Image', 'probo-connect-theme' ) },
				uses( 'overlay' )
					? field( components.SelectControl, 'overlay', __( 'Gradient over the image', 'probo-connect-theme' ), {
							options: [
								{ label: __( 'None', 'probo-connect-theme' ), value: '' },
								{ label: __( 'From the bottom', 'probo-connect-theme' ), value: 'Onder' },
								{ label: __( 'From the left', 'probo-connect-theme' ), value: 'Links' },
								{ label: __( 'All around (vignette)', 'probo-connect-theme' ), value: 'Rondom' },
							],
							help: __( 'Blends into the hero background color, so it adapts to the style.', 'probo-connect-theme' ),
					  } )
					: null,
				uses( 'overlay' ) && a.overlay
					? el( components.RangeControl, {
							label: __( 'Strength', 'probo-connect-theme' ),
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
									set( { imageId: 0 } );
								},
							},
							__( 'Remove image', 'probo-connect-theme' )
					  )
					: null
			);

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				el(
					components.PanelBody,
					{ title: __( 'Variant', 'probo-connect-theme' ) },
					field( components.SelectControl, 'variant', __( 'Hero variant', 'probo-connect-theme' ), {
						options: VARIANTS,
						help: __( 'Every variant reads the same content; the fields below are the ones this one uses.', 'probo-connect-theme' ),
					} ),
					'B' === variant
						? field( components.SelectControl, 'heroMedia', __( 'Image position', 'probo-connect-theme' ), {
								options: [
									{ label: __( 'Right', 'probo-connect-theme' ), value: 'Rechts' },
									{ label: __( 'Left', 'probo-connect-theme' ), value: 'Links' },
									{ label: __( 'None', 'probo-connect-theme' ), value: 'Geen' },
								],
						  } )
						: null
				),
				el(
					components.PanelBody,
					{ title: __( 'Content', 'probo-connect-theme' ) },
					'I' === variant
						? null
						: field( components.TextControl, 'eyebrow', __( 'Label above the title', 'probo-connect-theme' ) ),
					field( components.TextareaControl, 'title', __( 'Title', 'probo-connect-theme' ), {
						help: __( 'A new line becomes a line break.', 'probo-connect-theme' ),
					} ),
					field( components.TextareaControl, 'subtitle', __( 'Intro text', 'probo-connect-theme' ) ),
					uses( 'search' )
						? el( components.ToggleControl, {
								label: __( 'Show search bar', 'probo-connect-theme' ),
								checked: a.showSearch,
								onChange: function ( value ) {
									set( { showSearch: value } );
								},
						  } )
						: null,
					uses( 'chips' )
						? field( components.TextControl, 'chips', __( 'Shortcuts', 'probo-connect-theme' ), {
								help: __( 'Comma-separated.', 'probo-connect-theme' ),
						  } )
						: null
				),
				uses( 'buttons' ) || uses( 'promoButton' ) || uses( 'link' )
					? el(
							components.PanelBody,
							{ title: __( 'Buttons', 'probo-connect-theme' ), initialOpen: false },
							uses( 'buttons' ) || uses( 'promoButton' )
								? field( components.TextControl, 'primaryLabel', __( 'Primary button', 'probo-connect-theme' ) )
								: null,
							uses( 'buttons' ) || uses( 'promoButton' )
								? field( components.TextControl, 'primaryUrl', __( 'Primary button link', 'probo-connect-theme' ) )
								: null,
							uses( 'secondButton' )
								? field( components.TextControl, 'secondaryLabel', __( 'Secondary button', 'probo-connect-theme' ) )
								: null,
							uses( 'secondButton' )
								? field( components.TextControl, 'secondaryUrl', __( 'Secondary button link', 'probo-connect-theme' ) )
								: null,
							uses( 'link' ) ? field( components.TextControl, 'linkLabel', __( 'Overview link', 'probo-connect-theme' ) ) : null,
							uses( 'link' ) ? field( components.TextControl, 'linkUrl', __( 'Overview link URL', 'probo-connect-theme' ) ) : null
					  )
					: null,
				uses( 'usps' )
					? el(
							components.PanelBody,
							{ title: __( 'USPs', 'probo-connect-theme' ), initialOpen: false },
							lines( 'usps', [ { label: __( 'USP', 'probo-connect-theme' ) } ], __( 'Add USP', 'probo-connect-theme' ) )
					  )
					: null,
				uses( 'tiles' )
					? el(
							components.PanelBody,
							{ title: __( 'Category tiles', 'probo-connect-theme' ), initialOpen: false },
							lines(
								'tiles',
								[
									{ label: __( 'Name', 'probo-connect-theme' ) },
									{ label: __( 'Link', 'probo-connect-theme' ) },
									{ label: __( 'From price', 'probo-connect-theme' ) },
								],
								__( 'Add tile', 'probo-connect-theme' )
							),
							el(
								'p',
								{ style: { fontSize: '12px', color: '#6B6B70' } },
								'B' === variant
									? __( 'Variant B shows the first two tiles.', 'probo-connect-theme' )
									: __( 'Variant D shows four tiles; the last one is drawn in the accent colour.', 'probo-connect-theme' )
							)
					  )
					: null,
				uses( 'stats' )
					? el(
							components.PanelBody,
							{ title: __( 'Figures', 'probo-connect-theme' ), initialOpen: false },
							lines(
								'stats',
								[ { label: __( 'Figure', 'probo-connect-theme' ) }, { label: __( 'Explanation', 'probo-connect-theme' ) } ],
								__( 'Add figure', 'probo-connect-theme' )
							)
					  )
					: null,
				uses( 'review' )
					? el(
							components.PanelBody,
							{ title: __( 'Review card', 'probo-connect-theme' ), initialOpen: false },
							field( components.TextareaControl, 'reviewQuote', __( 'Quote', 'probo-connect-theme' ) ),
							field( components.TextControl, 'reviewAuthor', __( 'Name and company', 'probo-connect-theme' ) )
					  )
					: null,
				uses( 'countdown' )
					? el(
							components.PanelBody,
							{ title: __( 'Campaign', 'probo-connect-theme' ), initialOpen: false },
							field( components.TextControl, 'countdownUntil', __( 'Runs until', 'probo-connect-theme' ), {
								type: 'datetime-local',
								help: __( 'Leave empty to hide the countdown.', 'probo-connect-theme' ),
							} )
					  )
					: null,
				uses( 'video' )
					? el(
							components.PanelBody,
							{ title: __( 'Film', 'probo-connect-theme' ), initialOpen: false },
							field( components.TextControl, 'videoUrl', __( 'Link to the film', 'probo-connect-theme' ), {
								help: __( 'Without a link the play button is decoration only.', 'probo-connect-theme' ),
							} )
					  )
					: null,
				el(
					components.PanelBody,
					{ title: __( 'Display', 'probo-connect-theme' ), initialOpen: false },
					uses( 'heroTokens' )
						? field( components.SelectControl, 'heroStyle', __( 'Hero style', 'probo-connect-theme' ), {
								options: [
									{ label: __( 'Follow secondary', 'probo-connect-theme' ), value: 'Zwart' },
									{ label: __( 'Accent', 'probo-connect-theme' ), value: 'Accent' },
									{ label: __( 'Light', 'probo-connect-theme' ), value: 'Licht' },
								],
								help: __( 'The band this hero sits on, derived from the brand colours.', 'probo-connect-theme' ),
						  } )
						: el(
								'p',
								{ style: { fontSize: '12px', color: '#6B6B70' } },
								__( 'This variant has a band of its own, so the hero style does not apply to it.', 'probo-connect-theme' )
						  ),
					uses( 'heroTokens' ) ? el( 'p', { style: { marginBottom: '4px' } }, __( 'Title color', 'probo-connect-theme' ) ) : null,
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
								__( 'A color too close to the background is ignored, so the title stays readable.', 'probo-connect-theme' )
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
										? __( 'Replace image', 'probo-connect-theme' )
										: __( 'Choose image', 'probo-connect-theme' ),
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
