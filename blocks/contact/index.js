/**
 * Contact block — editor.
 *
 * The contact rows use the theme's line format, so the shared repeater from
 * blocks/shared/repeater.js provides the per-field inputs.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/contact', {
		edit: function ( props ) {
			var a = props.attributes;

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
								props.setAttributes( patch );
							},
						},
						extra || {}
					)
				);
			}

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Content', 'probo-connect-theme' ) },
						field( components.TextControl, 'heading', __( 'Title', 'probo-connect-theme' ) ),
						field( components.TextareaControl, 'intro', __( 'Intro text', 'probo-connect-theme' ) ),
						el( 'p', { style: { margin: '16px 0 8px', fontWeight: 500 } }, __( 'Contact details', 'probo-connect-theme' ) ),
						window.probo.repeater( {
							value: a.rows,
							fields: [
								{ label: __( 'Icon', 'probo-connect-theme' ) },
								{ label: __( 'Value', 'probo-connect-theme' ) },
								{ label: __( 'Note', 'probo-connect-theme' ) },
							],
							addLabel: __( 'Add row', 'probo-connect-theme' ),
							onChange: function ( value ) {
								props.setAttributes( { rows: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Form', 'probo-connect-theme' ), initialOpen: false },
						field( components.TextControl, 'submitLabel', __( 'Button text', 'probo-connect-theme' ) ),
						field( components.TextControl, 'shortcode', __( 'Form shortcode', 'probo-connect-theme' ), {
							help: __(
								'Leave empty for the theme’s own form, which mails the site administrator. Fill in a shortcode to render a form plugin here instead.',
								'probo-connect-theme'
							),
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/contact', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
