(function () {
	'use strict';

	function byId( id ) {
		return document.getElementById( id );
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var form = byId( 'kmae-listing-form' );
		var preview = byId( 'kmae-wizard-preview' );

		if ( ! form || ! preview ) {
			return;
		}

		var brandEl = byId( 'kmae-wizard-preview-brand' );
		var modelEl = byId( 'kmae-wizard-preview-model' );
		var yearEl = byId( 'kmae-wizard-preview-year' );
		var mileageEl = byId( 'kmae-wizard-preview-mileage' );
		var fuelEl = byId( 'kmae-wizard-preview-fuel' );
		var transmissionEl = byId( 'kmae-wizard-preview-transmission' );
		var bodyEl = byId( 'kmae-wizard-preview-body' );
		var powerEl = byId( 'kmae-wizard-preview-power' );
		var engineEl = byId( 'kmae-wizard-preview-engine' );
		var conditionEl = byId( 'kmae-wizard-preview-condition' );
		var traitsEl = byId( 'kmae-wizard-preview-traits' );
		var extrasEl = byId( 'kmae-wizard-preview-extras' );
		var doneEl = byId( 'kmae-wizard-preview-done' );
		var totalEl = byId( 'kmae-wizard-preview-total' );
		var barEl = byId( 'kmae-wizard-preview-bar' );

		/* A még ki nem töltött mező jele. Mondat helyett gondolatjel: a panel így
		   nyugodt marad, és nem tűnik tele hibaüzenettel. */
		var EMPTY_TEXT = '–';

		/* A márka sorában az embléma váltja az autó-ikont, ha a katalógusban van
		   hozzá kép. Kézzel beírt (katalóguson kívüli) márkánál marad az ikon.
		   Az eredeti ikont megőrizzük, hogy vissza tudjunk állni rá. */
		var brandRow = brandEl ? brandEl.closest( '.kmae-wizard-preview__row' ) : null;
		var brandIconEl = brandRow ? brandRow.querySelector( '.kmae-wizard-preview__row-icon' ) : null;
		var brandIconHtml = brandIconEl ? brandIconEl.innerHTML : '';

		function brandLogoUrl( brand ) {
			var catalog = window.kmaeVehicleCatalog || {};
			var logos = catalog.logos || {};
			var needle = String( brand || '' ).trim().toLowerCase();
			var found = '';

			if ( ! needle ) {
				return '';
			}

			Object.keys( logos ).some( function ( key ) {
				if ( key.toLowerCase() === needle ) {
					found = logos[ key ];
					return true;
				}
				return false;
			} );

			return found;
		}

		function restoreBrandIcon() {
			brandIconEl.innerHTML = brandIconHtml;
			brandIconEl.classList.remove( 'has-logo' );
		}

		function syncBrandIcon( brand ) {
			if ( ! brandIconEl ) {
				return;
			}

			var logo = brandLogoUrl( brand );
			var shown = brandIconEl.querySelector( 'img' );

			if ( ! logo ) {
				if ( shown ) {
					restoreBrandIcon();
				}
				return;
			}

			if ( shown && shown.getAttribute( 'src' ) === logo ) {
				return;
			}

			var img = document.createElement( 'img' );
			img.className = 'kmae-wizard-preview__row-logo';
			img.alt = '';
			img.setAttribute( 'aria-hidden', 'true' );
			img.setAttribute( 'decoding', 'async' );
			/* Ha az embléma nem tölthető be, visszaáll az autó-ikon. */
			img.onerror = restoreBrandIcon;
			img.src = logo;

			brandIconEl.innerHTML = '';
			brandIconEl.appendChild( img );
			brandIconEl.classList.add( 'has-logo' );
		}

		var motEl = byId( 'kmae-wizard-preview-mot' );

		function fieldValue( name ) {
			var el = form.elements[ name ];
			if ( ! el ) {
				return '';
			}
			if ( el.tagName === 'SELECT' ) {
				/* Üres választásnál a felirat a „Válassz..." lenne — az nem válasz. */
				if ( ! el.value ) {
					return '';
				}
				return el.selectedIndex > -1 ? el.options[ el.selectedIndex ].text.trim() : '';
			}
			return ( el.value || '' ).trim();
		}

		function formatNumber( value ) {
			var digits = value.replace( /[^\d]/g, '' );
			if ( ! digits ) {
				return '';
			}
			return digits.replace( /\B(?=(\d{3})+(?!\d))/g, ' ' );
		}

		function specSvg( field, code ) {
			var pack = ( window.kmaeListingWizard && window.kmaeListingWizard.icons ) || {};
			if ( 'fuel' === field ) {
				return ( pack.fuel || {} )[ code ] || ( pack.fuel || {} ).benzin || '';
			}
			if ( 'transmission' === field ) {
				return ( pack.transmission || {} )[ code ] || ( pack.transmission || {} ).manual || '';
			}
			if ( 'condition' === field ) {
				return ( pack.condition || {} )[ code ] || ( pack.condition || {} ).good || '';
			}
			if ( 'mot' === field ) {
				return ( pack.mot || {} )[ 'no' === code ? 'no' : 'yes' ] || '';
			}
			return '';
		}

		function paintSpecIcon( valueEl, field, code ) {
			if ( ! valueEl || ! code ) {
				return;
			}
			var row = valueEl.closest( '.kmae-wizard-preview__row' );
			var slot = row ? row.querySelector( '[data-kmae-vehicle-icon="' + field + '"]' ) : null;
			var svg = specSvg( field, code );
			if ( slot && svg ) {
				slot.innerHTML = svg;
			}
		}

		function update() {
			var brand = fieldValue( 'kmae_vehicle_brand' );
			var model = fieldValue( 'kmae_vehicle_model' );
			var year = fieldValue( 'kmae_vehicle_year' );
			var mileage = fieldValue( 'kmae_vehicle_mileage' );
			var fuel = fieldValue( 'kmae_vehicle_fuel' );
			var transmission = fieldValue( 'kmae_vehicle_transmission' );
			var body = fieldValue( 'kmae_vehicle_body' );
			var power = fieldValue( 'kmae_vehicle_power_hp' );
			var engine = fieldValue( 'kmae_vehicle_engine_cc' );
			var condition = fieldValue( 'kmae_condition' );

			/* A jellemzők jelölőnégyzetek — a bejelöltek feliratát fűzzük össze. */
			var traits = [];
			Array.prototype.forEach.call(
				document.querySelectorAll( '#kmae-vehicle-traits input[type="checkbox"]:checked' ),
				function ( box ) {
					var option = box.closest( '.kmae-trait-option' );
					var label = option ? option.querySelector( '.kmae-trait-option__label' ) : null;
					traits.push( label ? label.textContent.trim() : box.value );
				}
			);
			var motHas = ( form.elements[ 'kmae_vehicle_mot_has' ] || {} ).value || '';
			var motYear = ( form.elements[ 'kmae_vehicle_mot_year' ] || {} ).value || '';
			var motMonth = ( form.elements[ 'kmae_vehicle_mot_month' ] || {} ).value || '';
			var motDay = ( form.elements[ 'kmae_vehicle_mot_day' ] || {} ).value || '';

			brandEl.textContent = brand || EMPTY_TEXT;
			syncBrandIcon( brand );
			modelEl.textContent = model || EMPTY_TEXT;
			yearEl.textContent = year || EMPTY_TEXT;
			mileageEl.textContent = mileage ? formatNumber( mileage ) + ' km' : EMPTY_TEXT;
			fuelEl.textContent = fuel || EMPTY_TEXT;
			transmissionEl.textContent = transmission || EMPTY_TEXT;
			paintSpecIcon( fuelEl, 'fuel', ( form.elements.kmae_vehicle_fuel || {} ).value || '' );
			paintSpecIcon( transmissionEl, 'transmission', ( form.elements.kmae_vehicle_transmission || {} ).value || '' );
			paintSpecIcon( conditionEl, 'condition', ( form.elements.kmae_condition || {} ).value || '' );
			paintSpecIcon( motEl, 'mot', motHas );
			bodyEl.textContent = body || EMPTY_TEXT;
			powerEl.textContent = power ? power + ' LE' : EMPTY_TEXT;
			engineEl.textContent = engine ? formatNumber( engine ) + ' cm³' : EMPTY_TEXT;

			conditionEl.textContent = condition || EMPTY_TEXT;

			/* A kiemelések nem adatsorok: külön blokkban, jelvényként, és a blokk
			   csak akkor jelenik meg, ha tényleg választott. */
			if ( traitsEl && extrasEl ) {
				traitsEl.innerHTML = '';
				traits.forEach( function ( text ) {
					var chip = document.createElement( 'span' );
					chip.className = 'kmae-wizard-preview__chip';
					chip.textContent = text;
					traitsEl.appendChild( chip );
				} );
				extrasEl.hidden = ! traits.length;
			}

			if ( 'no' === motHas ) {
				motEl.textContent = 'Nincs';
			} else if ( motYear && motMonth ) {
				motEl.textContent = motDay
					? motYear + '. ' + motMonth + '. ' + motDay + '.'
					: motYear + '. ' + motMonth + '.';
			} else {
				motEl.textContent = EMPTY_TEXT;
			}

			/* Kitöltött / üres állapot, haladásjelző és a hosszú értékek kezelése.
			   Az üres mező nem „—", hanem kimondja, hogy még nincs megadva — így
			   a panel adatlapnak látszik, nem menünek. */
			var filledCount = 0;
			var rows = [ brandEl, modelEl, yearEl, transmissionEl, powerEl, engineEl, fuelEl, motEl, mileageEl, bodyEl, conditionEl ];

			rows.forEach( function ( el ) {
				if ( ! el ) {
					return;
				}

				var row = el.closest( '.kmae-wizard-preview__row' );
				var value = ( el.textContent || '' ).trim();
				var filled = '' !== value && EMPTY_TEXT !== value;

				if ( ! filled ) {
					el.textContent = EMPTY_TEXT;
				} else {
					filledCount += 1;
				}

				/* A teljes szöveg egérrel fölé állva olvasható. */
				el.title = filled ? value : '';

				if ( row ) {
					row.classList.toggle( 'is-filled', filled );
					row.classList.toggle( 'is-empty', ! filled );
				}
			} );

			if ( doneEl ) {
				doneEl.textContent = String( filledCount );
			}
			if ( totalEl ) {
				totalEl.textContent = String( rows.length );
			}
			if ( barEl ) {
				barEl.style.width = Math.round( ( filledCount / rows.length ) * 100 ) + '%';
			}
		}

		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );
		update();
	} );
}() );
