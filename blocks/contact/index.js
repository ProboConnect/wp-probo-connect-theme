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
						{ title: __( 'Content', 'probo-connect' ) },
						field( components.TextControl, 'heading', __( 'Title', 'probo-connect' ) ),
						field( components.TextareaControl, 'intro', __( 'Intro text', 'probo-connect' ) ),
						el( 'p', { style: { margin: '16px 0 8px', fontWeight: 500 } }, __( 'Contact details', 'probo-connect' ) ),
						window.probo.repeater( {
							value: a.rows,
							fields: [
								{ label: __( 'Icon', 'probo-connect' ) },
								{ label: __( 'Value', 'probo-connect' ) },
								{ label: __( 'Note', 'probo-connect' ) },
							],
							addLabel: __( 'Add row', 'probo-connect' ),
							onChange: function ( value ) {
								props.setAttributes( { rows: value } );
							},
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Form', 'probo-connect' ), initialOpen: false },
						field( components.TextControl, 'submitLabel', __( 'Button text', 'probo-connect' ) ),
						field( components.TextControl, 'shortcode', __( 'Form shortcode', 'probo-connect' ), {
							help: __(
								'Leave empty for the theme’s own form, which mails the site administrator. Fill in a shortcode to render a form plugin here instead.',
								'probo-connect'
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
