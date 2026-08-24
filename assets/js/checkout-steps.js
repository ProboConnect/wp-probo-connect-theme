/**
 * The accordion checkout.
 *
 * Enhancement only. Every step is in the page already, open, and everything a
 * collapsed step shows was built in PHP; this file decides which one is open
 * and validates a step before it closes. If it never runs, the checkout is the
 * long one-page form it always was.
 *
 * What it deliberately does not do: touch a price, a delivery option or a
 * carrier. Those belong to WooCommerce and to Probo Connect, and are read back
 * out of the fragments those two refresh.
 */
( function () {
	'use strict';

	var steps = document.querySelector( '[data-probo-steps]' );

	if ( ! steps ) {
		return;
	}

	var STORAGE_KEY = 'probo-checkout-steps';
	var LAST = 3;

	var state = {
		current: parseInt( steps.getAttribute( 'data-probo-initial-step' ), 10 ) || 1,
		completed: [],
	};

	/**
	 * Restore the accordion across a server-side validation round.
	 *
	 * WooCommerce reloads the whole page when checkout validation fails, which
	 * would otherwise drop a customer back at step 1 with everything they had
	 * already answered folded away again.
	 *
	 * The server still has the last word on how far anyone can be: its number is
	 * the first step that is not answered yet, so a stored step beyond it is
	 * stale — an emptied cart, an address that no longer validates — and would
	 * open step 2 above a step 1 with nothing in it.
	 */
	function restore() {
		var serverStep = state.current;

		try {
			var stored = window.sessionStorage.getItem( STORAGE_KEY );

			if ( ! stored ) {
				return;
			}

			var parsed = JSON.parse( stored );

			if ( parsed && parsed.current ) {
				state.current = Math.min( parsed.current, serverStep );
				state.completed = parsed.completed || [];
			}
		} catch ( error ) {
			// A blocked or full sessionStorage is not a reason to fail.
		}
	}

	function persist() {
		try {
			window.sessionStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
		} catch ( error ) {}
	}

	function stepEl( number ) {
		return steps.querySelector( '[data-probo-step="' + number + '"]' );
	}

	function isCompleted( number ) {
		return state.completed.indexOf( number ) !== -1;
	}

	function complete( number ) {
		if ( ! isCompleted( number ) ) {
			state.completed.push( number );
		}
	}

	/**
	 * Paint the current state onto the steps and the header progress line.
	 */
	function render() {
		steps.classList.add( 'is-enhanced' );

		for ( var number = 1; number <= LAST; number++ ) {
			var el = stepEl( number );

			if ( ! el ) {
				continue;
			}

			// A step the checkout has already moved past counts as done even
			// when this session never clicked through it: on arrival the server
			// puts a returning customer straight on step 2 or 3, and the steps
			// behind that are answered — they need their check mark and their
			// "Wijzig", not the grey not-yet state.
			var open = number === state.current;
			var done = ! open && ( isCompleted( number ) || number < state.current );

			el.classList.toggle( 'is-open', open );
			el.classList.toggle( 'is-done', done );
			el.classList.toggle( 'is-todo', ! open && ! done );

			var progress = document.querySelector(
				'[data-probo-progress-step="' + number + '"]'
			);

			if ( progress ) {
				progress.setAttribute(
					'data-state',
					open ? 'current' : done ? 'done' : 'todo'
				);
			}
		}

		syncStickyBar();
		persist();
	}

	function go( number, focus ) {
		state.current = Math.min( Math.max( number, 1 ), LAST );
		render();

		var el = stepEl( state.current );

		if ( ! el ) {
			return;
		}

		if ( focus !== false ) {
			el.scrollIntoView( { block: 'start', behavior: 'smooth' } );
		}
	}

	/* --- Validation -------------------------------------------------------
	   Per field, on blur, and again before a step closes. The message says what
	   to do rather than that something is wrong: "Vul een huisnummer in" beats
	   "Dit veld is verplicht" for the one person who cannot see what is missing.
	*/

	var MESSAGES = {
		billing_email: 'Vul een e-mailadres in — daar sturen we de orderbevestiging naartoe.',
		billing_phone: 'Vul een telefoonnummer in — de vervoerder belt als hij je niet vindt.',
		billing_postcode: 'Vul een postcode in, bijvoorbeeld 9101 PE.',
		shipping_postcode: 'Vul een postcode in, bijvoorbeeld 9101 PE.',
		billing_address_1: 'Vul straat en huisnummer in — zonder nummer kan de vervoerder niet bezorgen.',
		shipping_address_1: 'Vul straat en huisnummer in — zonder nummer kan de vervoerder niet bezorgen.',
	};

	function fieldName( field ) {
		return field.getAttribute( 'name' ) || '';
	}

	function messageFor( field ) {
		var name = fieldName( field );

		if ( MESSAGES[ name ] ) {
			return MESSAGES[ name ];
		}

		if ( field.type === 'email' ) {
			return MESSAGES.billing_email;
		}

		var row = field.closest( '.form-row' );
		var label = row ? row.querySelector( 'label' ) : null;
		var text = label
			? label.textContent.replace( '*', '' ).trim().toLowerCase()
			: 'dit veld';

		return 'Vul ' + text + ' in om verder te kunnen.';
	}

	function isFilled( field ) {
		if ( field.type === 'checkbox' ) {
			return field.checked;
		}

		if ( field.type === 'email' ) {
			return /.+@.+\..+/.test( field.value.trim() );
		}

		return field.value.trim() !== '';
	}

	function clearError( row ) {
		row.classList.remove( 'pp-field-invalid' );

		var message = row.querySelector( '.pp-field-error' );

		if ( message ) {
			message.remove();
		}
	}

	function showError( row, field ) {
		row.classList.add( 'pp-field-invalid' );

		if ( row.querySelector( '.pp-field-error' ) ) {
			return;
		}

		var message = document.createElement( 'div' );

		message.className = 'pp-field-error';
		message.setAttribute( 'role', 'alert' );
		message.innerHTML = '<span aria-hidden="true">!</span><span></span>';
		message.lastChild.textContent = messageFor( field );

		row.appendChild( message );
	}

	function validateField( field ) {
		var row = field.closest( '.form-row' );

		if ( ! row || ! row.classList.contains( 'validate-required' ) ) {
			return true;
		}

		// A hidden field cannot be filled in — the shipping address rows stay
		// in the DOM (and keep their "validate-required" class) even when
		// "Ship to a different address" is unchecked, so without this a step
		// could never close: the first invalid field found would be one the
		// customer cannot see, and focus()/scrollIntoView() on it does nothing
		// visible, which reads as the button silently doing nothing.
		if ( field.offsetParent === null ) {
			return true;
		}

		if ( isFilled( field ) ) {
			clearError( row );

			return true;
		}

		showError( row, field );

		return false;
	}

	/**
	 * Validate a whole step, and land the caret on the first field that is
	 * still empty. A step with an error in it does not close.
	 *
	 * @param {number} number Step number.
	 * @return {boolean} Whether the step may close.
	 */
	function validateStep( number ) {
		var el = stepEl( number );

		if ( ! el ) {
			return true;
		}

		var fields = el.querySelectorAll(
			'.validate-required input, .validate-required select, .validate-required textarea'
		);
		var first = null;

		Array.prototype.forEach.call( fields, function ( field ) {
			if ( ! validateField( field ) && ! first ) {
				first = field;
			}
		} );

		if ( first ) {
			first.focus();
			first.scrollIntoView( { block: 'center', behavior: 'smooth' } );

			return false;
		}

		return true;
	}

	/* --- The sticky bar on small screens ---------------------------------- */

	var stickyBar = null;

	function currentAction() {
		var el = stepEl( state.current );

		if ( ! el ) {
			return null;
		}

		return (
			el.querySelector( '[data-probo-step-next]' ) ||
			el.querySelector( '#place_order' )
		);
	}

	function syncStickyBar() {
		if ( ! stickyBar ) {
			return;
		}

		var action = currentAction();
		var total = document.querySelector( '.order-total td' );

		stickyBar.hidden = ! action;

		if ( ! action ) {
			return;
		}

		stickyBar.querySelector( '.pp-sticky-total' ).textContent = total
			? total.textContent.trim()
			: '';
		stickyBar.querySelector( 'button' ).textContent = action
			.textContent.trim();
	}

	function initStickyBar() {
		stickyBar = document.createElement( 'div' );
		stickyBar.className = 'pp-sticky-bar';
		stickyBar.innerHTML =
			'<span class="pp-sticky-total"></span>' +
			'<button type="button" class="pp-btn-accent"></button>';

		stickyBar.querySelector( 'button' ).addEventListener( 'click', function () {
			var action = currentAction();

			if ( action ) {
				action.click();
			}
		} );

		document.body.appendChild( stickyBar );
	}

	/* --- Wiring ------------------------------------------------------------ */

	steps.addEventListener( 'click', function ( event ) {
		var next = event.target.closest( '[data-probo-step-next]' );

		if ( next ) {
			var number = parseInt( next.getAttribute( 'data-probo-step-next' ), 10 );

			if ( ! validateStep( number ) ) {
				return;
			}

			complete( number );
			go( number + 1 );

			return;
		}

		var edit = event.target.closest( '[data-probo-step-edit]' );

		if ( edit ) {
			// Reopening a step leaves the later ones completed: a repeat
			// customer who corrects a house number should not have to walk
			// through the delivery and payment choice again.
			go( parseInt( edit.getAttribute( 'data-probo-step-edit' ), 10 ) );
		}
	} );

	steps.addEventListener(
		'blur',
		function ( event ) {
			if ( event.target.matches( 'input, select, textarea' ) ) {
				validateField( event.target );
			}
		},
		true
	);

	steps.addEventListener( 'input', function ( event ) {
		var row = event.target.closest
			? event.target.closest( '.form-row.pp-field-invalid' )
			: null;

		if ( row && isFilled( event.target ) ) {
			clearError( row );
		}
	} );

	if ( window.jQuery ) {
		var $body = window.jQuery( document.body );

		// The summary lines are fragments, so they arrive already rendered. What
		// is left: the amount on the order button, the pickup count in the tab —
		// which only exists once the plugin has loaded the pickup points for the
		// chosen day — and the sticky bar's copy of the total.
		//
		// The timeout is deliberate. WooCommerce resets #place_order to its
		// data-value on this same event while initialising the payment methods,
		// so the label is rewritten after that has run rather than before it.
		$body.on( 'updated_checkout', function () {
			window.setTimeout( function () {
				syncOrderButton();
				syncPickupCount();
				syncStickyBar();
			}, 0 );
		} );

		// A server-side validation error reopens the step it belongs to.
		$body.on( 'checkout_error', function () {
			var invalid = document.querySelector(
				'.woocommerce-invalid input, .woocommerce-invalid select'
			);
			var step = invalid ? invalid.closest( '[data-probo-step]' ) : null;

			if ( step ) {
				go( parseInt( step.getAttribute( 'data-probo-step' ), 10 ) );
			}
		} );
	}

	/**
	 * Write the current total onto the order button.
	 *
	 * The amount is part of the label rather than an element inside it, because
	 * WooCommerce replaces the button's contents with its data-value whenever it
	 * re-initialises the payment methods. Both are set here, so its own reset
	 * restores the right string too.
	 */
	function syncOrderButton() {
		var button = document.querySelector( '#place_order[data-probo-label]' );
		var total = document.querySelector( '.order-total td' );

		if ( ! button || ! total ) {
			return;
		}

		var label =
			button.getAttribute( 'data-probo-label' ) +
			' · ' +
			total.textContent.trim();

		button.setAttribute( 'data-value', label );
		button.value = label;
		button.textContent = label;
	}

	/**
	 * Fill the pickup count into the tab label.
	 *
	 * The number is on the rendered list, which the plugin loads over its own
	 * AJAX after the page is done — so it is read back from there rather than
	 * guessed at render time.
	 */
	function syncPickupCount() {
		var list = document.querySelector( '[data-probo-pickup-count]' );
		var label = document.querySelector( '[data-probo-pickup-label]' );

		if ( ! list || ! label ) {
			return;
		}

		var count = parseInt( list.getAttribute( 'data-probo-pickup-count' ), 10 );

		label.textContent = count ? '(' + count + ' locaties)' : '';
	}

	// The plugin swaps its own sections in without touching the checkout, so
	// the count is re-read whenever it says it has.
	document.addEventListener( 'section:updated', syncPickupCount );

	// "Kies zelf" is a request, not a selection: it changes nothing the server
	// stores, so the server cannot know about it. Every re-render of the block
	// would otherwise hand back the matching preset and fold the lists away again
	// — with the carriers behind them. So the request is remembered here, and
	// re-applied to each freshly rendered block until a preset answers for the
	// customer again.
	var choosingOwn = false;

	function syncPreset() {
		if ( ! choosingOwn ) {
			return;
		}

		var custom = document.querySelector( '.pp-preset-input--custom' );

		if ( custom && ! custom.checked ) {
			custom.checked = true;
		}
	}

	/**
	 * Hand a preset — a day and a carrier at once — to the plugin.
	 *
	 * The plugin's own change handler answers one radio at a time: a day, then a
	 * carrier. A preset is both answers together, so it goes in through the
	 * plugin's state API instead of through its radios. store() persists the pair
	 * and triggers update_checkout, which is what makes the plugin re-render both
	 * blocks — so the checked preset that comes back is the server's word on what
	 * is selected, not this click's.
	 */
	document.addEventListener( 'change', function ( event ) {
		var input = event.target;

		if ( ! input || 'probo_delivery_preset' !== input.name ) {
			return;
		}

		var date = input.getAttribute( 'data-probo-date' );
		var method = input.getAttribute( 'data-probo-method' );
		var blocks = window.connectShippingBlocks;

		// No pair on it: this is "Kies zelf". Nothing to store — the lists it
		// reveals are a stylesheet rule — but the choice has to survive the next
		// render, which is what the flag is for.
		if ( ! date || ! method ) {
			choosingOwn = true;

			return;
		}

		choosingOwn = false;

		if ( ! blocks ) {
			return;
		}

		blocks.set( { delivery_date: date, delivery_method: method } );
		blocks.store();
	} );

	document.addEventListener( 'section:updated', syncPreset );

	restore();

	// Everything before the step the checkout opens on is answered already, so
	// it stays reopenable after the customer walks back into it.
	for ( var seed = 1; seed < state.current; seed++ ) {
		complete( seed );
	}

	initStickyBar();
	render();
	syncPickupCount();
} )();
