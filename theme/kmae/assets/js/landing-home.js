/**
 * Vendég-főoldal: görgetéses megjelenés. A [data-lp-reveal] elemek akkor
 * kapják meg az is-in osztályt, amikor a képernyőre érnek; utána is-done,
 * hogy a kártyák hover-mozgása gyors legyen. JS nélkül minden látszik.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.kmae-lp' );
	if ( ! root || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var items = root.querySelectorAll( '[data-lp-reveal]' );
	root.classList.add( 'is-ready' );

	var observer = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( ! entry.isIntersecting ) {
				return;
			}
			var el = entry.target;
			var delay = parseInt( window.getComputedStyle( el ).getPropertyValue( '--lp-delay' ), 10 ) || 0;

			el.classList.add( 'is-in' );
			observer.unobserve( el );
			window.setTimeout( function () {
				el.classList.add( 'is-done' );
			}, delay + 900 );
		} );
	}, { threshold: 0.18, rootMargin: '0px 0px -40px 0px' } );

	items.forEach( function ( el ) {
		observer.observe( el );
	} );
}() );

/**
 * Mobil „Így működik” kártya: Eladóként / Vevőként váltó. JS nélkül
 * mindkét panel látszik, a váltó rejtve marad.
 */
( function () {
	'use strict';

	var card = document.querySelector( '.kmae-lp [data-lpm-tabs]' );
	if ( ! card ) {
		return;
	}

	var switcher = card.querySelector( '.kmae-lpm-switch' );
	var buttons = card.querySelectorAll( '.kmae-lpm-switch__btn' );

	function select( btn ) {
		buttons.forEach( function ( b ) {
			var on = b === btn;
			var panel = document.getElementById( b.getAttribute( 'aria-controls' ) );
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			if ( panel ) {
				panel.hidden = ! on;
			}
		} );
		switcher.classList.toggle( 'is-buyer', btn === buttons[ 1 ] );
	}

	switcher.hidden = false;
	card.classList.add( 'is-tabs' );
	select( buttons[ 0 ] );

	buttons.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			select( btn );
		} );
	} );
}() );
