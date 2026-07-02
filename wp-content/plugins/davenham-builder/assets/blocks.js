( function () {
	'use strict';

	function initTabs( root ) {
		root.querySelectorAll( '.db-tabs' ).forEach( function ( tabs ) {
			const buttons = Array.from( tabs.querySelectorAll( '[data-db-tab-target]' ) );
			const panels = Array.from( tabs.querySelectorAll( '[data-db-tab-panel]' ) );
			if ( ! buttons.length || ! panels.length ) {
				return;
			}

			const activate = function ( id ) {
				buttons.forEach( function ( button ) {
					const isActive = button.getAttribute( 'data-db-tab-target' ) === id;
					button.classList.toggle( 'is-active', isActive );
					button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
				} );

				panels.forEach( function ( panel ) {
					const isActive = panel.getAttribute( 'data-db-tab-panel' ) === id;
					panel.hidden = ! isActive;
					panel.classList.toggle( 'is-active', isActive );
				} );
			};

			buttons.forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					activate( button.getAttribute( 'data-db-tab-target' ) );
				} );
			} );

			activate( buttons[0].getAttribute( 'data-db-tab-target' ) );
		} );
	}

	function initPopups( root ) {
		root.querySelectorAll( '[data-db-popup-trigger]' ).forEach( function ( trigger ) {
			const target = trigger.getAttribute( 'data-db-popup-trigger' );
			const dialog = root.querySelector( '[data-db-popup="' + target + '"]' );
			if ( ! dialog ) {
				return;
			}

			trigger.addEventListener( 'click', function () {
				if ( typeof dialog.showModal === 'function' ) {
					dialog.showModal();
				} else {
					dialog.setAttribute( 'open', 'open' );
				}
			} );
		} );

		root.querySelectorAll( '[data-db-popup-close]' ).forEach( function ( closer ) {
			closer.addEventListener( 'click', function () {
				const dialog = closer.closest( 'dialog' );
				if ( dialog ) {
					dialog.close();
				}
			} );
		} );

		root.querySelectorAll( '.db-popup-modal' ).forEach( function ( dialog ) {
			dialog.addEventListener( 'click', function ( event ) {
				const rect = dialog.getBoundingClientRect();
				const inside =
					event.clientX >= rect.left &&
					event.clientX <= rect.right &&
					event.clientY >= rect.top &&
					event.clientY <= rect.bottom;

				if ( ! inside && typeof dialog.close === 'function' ) {
					dialog.close();
				}
			} );
		} );
	}

	function initReveal( root ) {
		// The <head> failsafe adds `db-reveal` to <html> before paint (unless
		// reduced-motion). If it's not there, do nothing — content shows as-is.
		if ( ! document.documentElement.classList.contains( 'db-reveal' ) ) {
			return;
		}

		const items = Array.prototype.slice.call( root.querySelectorAll( '[data-db-reveal]' ) );
		if ( ! items.length ) {
			return;
		}

		// No IntersectionObserver (very old browsers) → just show everything.
		if ( ! ( 'IntersectionObserver' in window ) ) {
			items.forEach( function ( el ) { el.classList.add( 'is-revealed' ); } );
			return;
		}

		const observer = new IntersectionObserver( function ( entries, obs ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-revealed' );
					obs.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 } );

		items.forEach( function ( el ) {
			// Anything already in (or above) the viewport on load reveals
			// immediately — no wait, no flash for above-the-fold content.
			if ( el.getBoundingClientRect().top < window.innerHeight * 0.92 ) {
				el.classList.add( 'is-revealed' );
			} else {
				observer.observe( el );
			}
		} );
	}

	function init() {
		initTabs( document );
		initPopups( document );
		initReveal( document );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
