/**
 * Theme behaviour.
 *
 * Small and dependency-free on purpose: the configurator, gallery and checkout
 * interactions all belong to WooCommerce or to Probo Connect.
 */
( function () {
	'use strict';

	/**
	 * Mobile navigation toggle.
	 *
	 * The nav is hidden below the lg breakpoint and revealed by the burger, so
	 * the class list mirrors what Tailwind applies at that breakpoint.
	 */
	function initNav() {
		var toggle = document.querySelector( '[data-probo-nav-toggle]' );
		var nav = document.querySelector( '[data-probo-nav]' );

		if ( ! toggle || ! nav ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var open = nav.classList.toggle( 'hidden' ) === false;

			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	}

	/**
	 * Keep the burger state honest when the viewport crosses the breakpoint.
	 */
	function initNavReset() {
		var nav = document.querySelector( '[data-probo-nav]' );
		var toggle = document.querySelector( '[data-probo-nav-toggle]' );

		if ( ! nav || ! toggle || ! window.matchMedia ) {
			return;
		}

		var desktop = window.matchMedia( '(min-width: 1024px)' );

		function sync() {
			if ( desktop.matches ) {
				nav.classList.remove( 'hidden' );
			} else if ( toggle.getAttribute( 'aria-expanded' ) !== 'true' ) {
				nav.classList.add( 'hidden' );
			}
		}

		desktop.addEventListener( 'change', sync );
		sync();
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
		var coarse = window.matchMedia && window.matchMedia( '(hover: none)' ).matches;
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
				if ( ! coarse || ! desktop || ! desktop.matches ) {
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

	function start() {
		initNav();
		initNavReset();
		initFlyouts();
	}

	if ( document.readyState !== 'loading' ) {
		start();
	} else {
		document.addEventListener( 'DOMContentLoaded', start );
	}
} )();
