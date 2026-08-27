/**
 * FAQ block — editor.
 *
 * Questions and answers use the theme's line format, so the shared repeater
 * from blocks/shared/repeater.js provides the per-field inputs.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;

	wp.blocks.registerBlockType( 'probo/faq', {
		edit: function ( props ) {
			var a = props.attributes;

			function field( Control, key, label ) {
				return el( Control, {
					label: label,
					value: a[ key ],
					onChange: function ( value ) {
						var patch = {};
						patch[ key ] = value;
						props.setAttributes( patch );
					},
				} );
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
						field( components.TextControl, 'linkLabel', __( 'Link', 'probo-connect-theme' ) ),
						field( components.TextControl, 'linkUrl', __( 'Link URL', 'probo-connect-theme' ) )
					),
					el(
						components.PanelBody,
						{ title: __( 'Questions', 'probo-connect-theme' ) },
						window.probo.repeater( {
							value: a.items,
							fields: [
								{ label: __( 'Question', 'probo-connect-theme' ) },
								{ label: __( 'Answer', 'probo-connect-theme' ) },
							],
							addLabel: __( 'Add question', 'probo-connect-theme' ),
							onChange: function ( value ) {
								props.setAttributes( { items: value } );
							},
						} )
					)
				),
				el( wp.serverSideRender, { block: 'probo/faq', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
