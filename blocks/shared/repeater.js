/**
 * Repeater control for the theme's line-based block attributes.
 *
 * Several blocks store repeating content as one line per item, fields separated
 * by a pipe — the shape inc/blocks.php's probo_parse_lines() reads. That is a
 * fine storage format and PHP stays its only parser, but it is a poor editing
 * experience: one textarea where a stray pipe silently reshuffles a column.
 *
 * This turns that same string into proper per-field inputs with add, remove and
 * reorder, and writes it straight back. Nothing about the stored value changes,
 * so existing pages keep working and render.php is untouched.
 *
 * Plain browser JS against the wp.* globals, like the blocks themselves.
 *
 * Usage:
 *   probo.repeater( {
 *     value: attributes.items,
 *     fields: [ { label: 'Titel' }, { label: 'Toelichting' } ],
 *     addLabel: 'USP toevoegen',
 *     onChange: function ( next ) { setAttributes( { items: next } ); },
 *   } )
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;

	/**
	 * Split the stored string into rows of field values.
	 *
	 * Mirrors probo_parse_lines(): blank lines are dropped, each row is padded
	 * to the field count, and anything past the last field is discarded.
	 *
	 * @param {string} value  Stored value.
	 * @param {number} fields Number of fields per row.
	 * @return {string[][]} Rows.
	 */
	function parse( value, fields ) {
		return String( value || '' )
			.split( /\r\n|\r|\n/ )
			.map( function ( line ) {
				return line.trim();
			} )
			.filter( function ( line ) {
				return line !== '';
			} )
			.map( function ( line ) {
				var parts = line.split( '|' ).map( function ( part ) {
					return part.trim();
				} );

				return Array.from( { length: fields }, function ( _, i ) {
					return parts[ i ] || '';
				} );
			} );
	}

	/**
	 * Join rows back into the stored string.
	 *
	 * Trailing empty fields are dropped so an unfilled optional field does not
	 * leave a dangling pipe in the content.
	 *
	 * @param {string[][]} rows Rows.
	 * @return {string}
	 */
	function serialize( rows ) {
		return rows
			.map( function ( row ) {
				var trimmed = row.slice();

				while ( trimmed.length > 1 && trimmed[ trimmed.length - 1 ] === '' ) {
					trimmed.pop();
				}

				return trimmed.join( ' | ' );
			} )
			.filter( function ( line ) {
				return line.replace( /\|/g, '' ).trim() !== '';
			} )
			.join( '\n' );
	}

	/**
	 * The control itself.
	 *
	 * It keeps its own copy of the rows rather than deriving them from the
	 * stored string on every render. A freshly added row is still empty, and
	 * serialize() drops empty rows — so a derived list would delete the new row
	 * the instant it appeared. The local copy lets a row exist while it is being
	 * filled in, and only what is actually filled reaches the attribute.
	 *
	 * @param {Object} config Control configuration.
	 * @return {Object} Element.
	 */
	function RepeaterControl( config ) {
		var fields = config.fields;
		var state = wp.element.useState( function () {
			return parse( config.value, fields.length );
		} );
		var rows = state[ 0 ];
		var setRows = state[ 1 ];

		// What this control last wrote out. Anything else arriving in
		// config.value came from outside — an undo, a template switch — and
		// should replace what is on screen.
		var emitted = wp.element.useRef( config.value );

		wp.element.useEffect(
			function () {
				if ( config.value !== emitted.current ) {
					emitted.current = config.value;
					setRows( parse( config.value, fields.length ) );
				}
			},
			[ config.value ]
		);

		function commit( next ) {
			var value = serialize( next );

			setRows( next );
			emitted.current = value;
			config.onChange( value );
		}

		function move( index, offset ) {
			var next = rows.slice();
			var moved = next.splice( index, 1 )[ 0 ];

			next.splice( index + offset, 0, moved );
			commit( next );
		}

		var items = rows.map( function ( row, index ) {
			var inputs = fields.map( function ( field, position ) {
				return el( components.TextControl, {
					key: 'field-' + position,
					label: field.label,
					value: row[ position ],
					help: field.help,
					onChange: function ( value ) {
						var next = rows.map( function ( existing ) {
							return existing.slice();
						} );

						next[ index ][ position ] = value;
						commit( next );
					},
				} );
			} );

			var buttons = el(
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
					disabled: index === rows.length - 1,
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
							rows.filter( function ( _, i ) {
								return i !== index;
							} )
						);
					},
				} )
			);

			return el(
				'div',
				{
					key: 'row-' + index,
					style: {
						border: '1px solid #e0e0e0',
						borderRadius: '2px',
						padding: '12px',
						marginBottom: '12px',
					},
				},
				buttons,
				inputs
			);
		} );

		return el(
			'div',
			null,
			items,
			el(
				components.Button,
				{
					variant: 'secondary',
					onClick: function () {
						commit(
							rows.concat( [
								fields.map( function () {
									return '';
								} ),
							] )
						);
					},
				},
				config.addLabel || __( 'Add item', 'probo-connect-theme' )
			)
		);
	}

	window.probo = window.probo || {};

	/**
	 * Build the control as an element, so its hooks belong to it and not to
	 * whichever block happens to render it.
	 *
	 * @param {Object} config Control configuration.
	 * @return {Object} Element.
	 */
	window.probo.repeater = function ( config ) {
		return el( RepeaterControl, config );
	};
} )( window.wp );
