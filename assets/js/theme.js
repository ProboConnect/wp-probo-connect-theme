/**
 * Theme behaviour.
 *
 * Small and dependency-free on purpose: the configurator, gallery and checkout
 * interactions all belong to WooCommerce or to the configurator plugin.
 */
( function () {
	'use strict';

	/**
	 * Mobile navigation toggle.
	 *
	 * The nav is hidden below the lg breakpoint and revealed by the burger, so
	 * the class list mirrors what Tailwind applies at that breakpoint. A single
	 * setOpen() is the only writer of both the `hidden` class and
	 * `aria-expanded`, so the two can never drift apart the way they used to
	 * when initNav() and initNavReset() each poked one representation.
	 */
	function initNav() {
		var toggle = document.querySelector( '[data-pp-nav-toggle]' );
		var nav = document.querySelector( '[data-pp-nav]' );

		if ( ! toggle || ! nav ) {
			return;
		}

		function setOpen( open ) {
			nav.classList.toggle( 'hidden', ! open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		toggle.addEventListener( 'click', function () {
			setOpen( nav.classList.contains( 'hidden' ) );
		} );

		// Keep the burger state honest when the viewport crosses the
		// breakpoint, in either direction: the panel is reset to closed so a
		// disclosure left open on one side of the breakpoint can never
		// reappear open on the other.
		if ( window.matchMedia ) {
			window.matchMedia( '(min-width: 1024px)' ).addEventListener( 'change', function () {
				setOpen( false );
			} );
		}

		setOpen( false );
	}

	/**
	 * Flyout panels in the primary navigation.
	 *
	 * CSS already opens a panel on hover and on focus-within, so the menu works
	 * without this. What the script adds is the part CSS cannot do: closing on
	 * Escape, and a first tap that opens the panel instead of following the
	 * parent link on touch devices — where there is no hover to reveal it.
	 */
	function initFlyouts() {
		var parents = document.querySelectorAll( '.pp-nav-menu > li.menu-item-has-children' );
		var coarse = window.matchMedia ? window.matchMedia( '(hover: none)' ) : null;
		var desktop = window.matchMedia ? window.matchMedia( '(min-width: 1024px)' ) : null;

		if ( ! parents.length ) {
			return;
		}

		function close( item ) {
			item.classList.remove( 'pp-flyout-open' );

			var link = item.querySelector( ':scope > a' );

			if ( link ) {
				link.setAttribute( 'aria-expanded', 'false' );
			}
		}

		function closeAll() {
			Array.prototype.forEach.call( parents, close );
		}

		Array.prototype.forEach.call( parents, function ( item ) {
			var link = item.querySelector( ':scope > a' );

			if ( ! link ) {
				return;
			}

			link.setAttribute( 'aria-expanded', 'false' );
			link.setAttribute( 'aria-haspopup', 'true' );

			link.addEventListener( 'click', function ( event ) {
				// Only hijack the first tap, and only where hovering is not a
				// thing. Everywhere else the parent link stays a plain link.
				if ( ! coarse || ! coarse.matches || ! desktop || ! desktop.matches ) {
					return;
				}

				if ( ! item.classList.contains( 'pp-flyout-open' ) ) {
					event.preventDefault();
					closeAll();
					item.classList.add( 'pp-flyout-open' );
					link.setAttribute( 'aria-expanded', 'true' );
				}
			} );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key !== 'Escape' ) {
				return;
			}

			var open = document.querySelector( '.pp-nav-menu > li.pp-flyout-open' );
			var focused = document.activeElement;

			closeAll();

			// Escape inside a panel should land the caret back on the item that
			// opened it, not nowhere.
			if ( open && focused && open.contains( focused ) ) {
				var link = open.querySelector( ':scope > a' );

				if ( link ) {
					link.focus();
				}
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.pp-nav-menu > li.menu-item-has-children' ) ) {
				closeAll();
			}
		} );
	}

	/**
	 * The products megamenu on the compact header (Variant B).
	 *
	 * Unlike the burger — which only hides the nav below lg and hands it back on
	 * desktop — this panel stays behind its "Producten" trigger at every width,
	 * so it has its own toggle rather than sharing initNav()'s. Closes on Escape
	 * and on a click outside the panel or its trigger.
	 */
	function initProductsMenu() {
		var toggle = document.querySelector( '[data-pp-products-toggle]' );
		var panel = document.querySelector( '[data-pp-products]' );

		if ( ! toggle || ! panel ) {
			return;
		}

		function setOpen( open ) {
			panel.classList.toggle( 'hidden', ! open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		toggle.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			setOpen( panel.classList.contains( 'hidden' ) );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				// Escape inside the panel should land the caret back on the
				// trigger that opened it, not nowhere.
				var returnFocus = panel.contains( document.activeElement );

				setOpen( false );

				if ( returnFocus ) {
					toggle.focus();
				}
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if (
				! event.target.closest( '[data-pp-products]' ) &&
				! event.target.closest( '[data-pp-products-toggle]' )
			) {
				setOpen( false );
			}
		} );
	}

	function start() {
		initNav();
		initFlyouts();
		initProductsMenu();
	}

	if ( document.readyState !== 'loading' ) {
		start();
	} else {
		document.addEventListener( 'DOMContentLoaded', start );
	}
} )();
