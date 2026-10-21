/**
 * Beviteli szabályok a hirdetés űrlapon.
 *
 * 1) Ahol számot várunk, oda ne kerülhessen betű vagy szimbólum; ahol szöveget,
 *    oda ne kerülhessen szám. A tiltás a beírásnál történik, így a felhasználó
 *    azonnal látja, hogy a karakter nem elfogadott.
 * 2) A műszaki dátuma csak akkor kötelező és látható, ha van érvényes műszaki.
 */
(function () {
	'use strict';

	/* Csak számjegy engedett.
	   Az árak (kmae_fixed_price, kmae_guide_price) szándékosan nincsenek itt:
	   azokat a listing-form-numbers.js kezeli ezredes tagolással, és ott már
	   megtörténik a nem számjegyek kiszűrése — ne legyen két forrás. */
	var DIGIT_FIELDS = [
		'kmae_vehicle_year',
		'kmae_vehicle_mileage',
		'kmae_vehicle_power_hp',
		'kmae_vehicle_engine_cc'
	];

	/* Számjegy nem engedett.
	   A márka, modell, hirdetéscím és leírás szándékosan NEM szerepel itt:
	   ezekben a szám jogos (Fiat 500, Audi A4, „BMW 320d, 2018, automata”). */
	var TEXT_FIELDS = [
		'kmae_vehicle_color',
		'kmae_city'
	];

	function guard( control, isAllowed, clean ) {
		if ( ! control ) {
			return;
		}

		/* Gépelés és beillesztés: a nem engedett karakter be sem kerül. */
		control.addEventListener( 'beforeinput', function ( event ) {
			if ( ! event.data ) {
				return; /* törlés, kurzormozgás, sortörés */
			}
			if ( isAllowed( event.data ) ) {
				return;
			}
			event.preventDefault();
		} );

		/* Biztonsági háló: automatikus kitöltés, húzd-és-ejtsd, régebbi böngésző. */
		control.addEventListener( 'input', function () {
			var value = control.value;
			if ( '' === value || isAllowed( value ) ) {
				return;
			}
			control.value = clean( value );
		} );
	}

	function onlyDigits( value ) {
		return /^\d*$/.test( value );
	}

	function stripNonDigits( value ) {
		return value.replace( /\D/g, '' );
	}

	function noDigits( value ) {
		return ! /\d/.test( value );
	}

	function stripDigits( value ) {
		return value.replace( /\d/g, '' );
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	/**
	 * A műszaki dátuma csak „Igen” válasznál kötelező és látható. Nélküle a
	 * rejtett, üres mezők blokkolnák a hirdetés beküldését.
	 */
	function syncMotDate( form ) {
		var has = form.elements[ 'kmae_vehicle_mot_has' ];
		var year = form.elements[ 'kmae_vehicle_mot_year' ];
		var month = form.elements[ 'kmae_vehicle_mot_month' ];

		if ( ! has || ! year || ! month ) {
			return;
		}

		var on = 'yes' === has.value;
		var wrap = year.closest( '.kmae-mot-date' );

		if ( wrap ) {
			wrap.hidden = ! on;
		}

		year.required = on;
		month.required = on;

		if ( ! on ) {
			year.value = '';
			month.value = '';
		}
	}

	ready( function () {
		var form = document.getElementById( 'kmae-listing-form' );
		if ( ! form ) {
			return;
		}

		DIGIT_FIELDS.forEach( function ( name ) {
			guard( form.elements[ name ], onlyDigits, stripNonDigits );
		} );

		TEXT_FIELDS.forEach( function ( name ) {
			guard( form.elements[ name ], noDigits, stripDigits );
		} );

		var motHas = form.elements[ 'kmae_vehicle_mot_has' ];
		if ( motHas ) {
			motHas.addEventListener( 'change', function () {
				syncMotDate( form );
			} );
			syncMotDate( form );
		}

		var page = form.closest( '.kmae-create-page.is-simple' );
		if ( ! page ) {
			return;
		}

		page.classList.add( 'kmae-simple-ready' );

		/* A „Hirdetés szövege” linkről (#kmae-section-ad): a betöltés végén, az
		   átrendeződés után a 02-es szekciót görgeti a tetejére, nem a leírás mezőt. */
		if ( window.location.hash === '#kmae-section-ad' ) {
			window.addEventListener( 'load', function () {
				var adSection = document.getElementById( 'kmae-section-ad' );
				if ( adSection ) {
					adSection.closest( '.kmae-create-section' ).scrollIntoView( { block: 'start', behavior: 'instant' } );
				}
			} );
		}

		var bodySelect = document.getElementById( 'kmae_vehicle_body' );
		var bodies = page.querySelectorAll( '.kmae-simple-body' );
		bodies.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! bodySelect ) {
					return;
				}
				bodySelect.value = btn.getAttribute( 'data-value' ) || '';
				bodySelect.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				bodies.forEach( function ( other ) {
					other.classList.toggle( 'is-selected', other === btn );
				} );
			} );
		} );

		/* Jellemzők: legfeljebb data-kmae-max (3) jelölhető; a limitnél a többi letiltva. */
		var traitsField = document.getElementById( 'kmae-vehicle-traits' );
		if ( traitsField ) {
			var traitMax = parseInt( traitsField.getAttribute( 'data-kmae-max' ), 10 ) || 3;
			var traitBoxes = traitsField.querySelectorAll( 'input[type="checkbox"]' );
			var syncTraits = function () {
				var picked = traitsField.querySelectorAll( 'input[type="checkbox"]:checked' ).length;
				traitBoxes.forEach( function ( box ) {
					var locked = ! box.checked && picked >= traitMax;
					box.disabled = locked;
					var option = box.closest( '.kmae-trait-option' );
					if ( option ) {
						option.classList.toggle( 'is-locked', locked );
					}
				} );
			};
			traitBoxes.forEach( function ( box ) {
				box.addEventListener( 'change', syncTraits );
			} );
			syncTraits();
		}

		page.querySelectorAll( '[data-kmae-count-for]' ).forEach( function ( out ) {
			var field = document.getElementById( out.getAttribute( 'data-kmae-count-for' ) );
			var max = out.getAttribute( 'data-kmae-count-max' ) || '';
			if ( ! field ) {
				return;
			}
			var paint = function () {
				out.textContent = String( field.value.length ) + ' / ' + max;
			};
			field.addEventListener( 'input', paint );
			paint();
		} );

		var emblem = page.querySelector( '[data-kmae-simple-emblem]' );
		var brand = document.getElementById( 'kmae_vehicle_brand' );
		if ( emblem && brand && window.kmaeVehicleCatalog && window.kmaeVehicleCatalog.logos ) {
			var emblemImg = emblem.querySelector( '[data-kmae-simple-emblem-img]' );
			var emblemName = emblem.querySelector( '[data-kmae-simple-emblem-name]' );
			var emblemEdit = emblem.querySelector( '[data-kmae-simple-emblem-edit]' );
			var paintLogo = function () {
				var logos = window.kmaeVehicleCatalog.logos || {};
				var url = logos[ brand.value ] || '';
				var name = brand.value;
				if ( ! url && name ) {
					var needle = String( name ).trim().toLowerCase();
					Object.keys( logos ).some( function ( key ) {
						if ( String( key ).toLowerCase() === needle ) {
							url = logos[ key ];
							name = key;
							return true;
						}
						return false;
					} );
				}
				if ( emblemImg ) {
					emblemImg.hidden = ! url;
					emblemImg.src = url || '';
					emblemImg.alt = name;
				}
				if ( emblemName ) {
					emblemName.textContent = url ? name : '';
				}
			};
			brand.addEventListener( 'change', paintLogo );
			brand.addEventListener( 'input', paintLogo );
			if ( emblemEdit ) {
				emblemEdit.addEventListener( 'click', function () {
					brand.focus();
					var opener = brand.parentNode ? brand.parentNode.querySelector( '[data-kmae-catalog-showall]' ) : null;
					if ( opener && window.getComputedStyle( opener ).display !== 'none' ) {
						opener.click();
					}
				} );
			}
		}

		/* Fotók: nagy kép + bélyegképsor. A forrás a rejtett kezelő
		   (#kmae-gallery-manager = mentett képek, #kmae-upload-preview = új képek);
		   a műveletek az ottani gombokat nyomják meg, így a mentés logikája egy helyen marad. */
		var photos = page.querySelector( '[data-kmae-photos]' );
		if ( photos ) {
			var pv = {
				main: photos.querySelector( '[data-kmae-photos-main]' ),
				empty: photos.querySelector( '[data-kmae-photos-empty]' ),
				badge: photos.querySelector( '[data-kmae-photos-badge]' ),
				tools: photos.querySelector( '[data-kmae-photos-tools]' ),
				cover: photos.querySelector( '[data-kmae-photos-cover]' ),
				remove: photos.querySelector( '[data-kmae-photos-remove]' ),
				prev: photos.querySelector( '[data-kmae-photos-prev]' ),
				next: photos.querySelector( '[data-kmae-photos-next]' ),
				open: photos.querySelector( '[data-kmae-photos-open]' ),
				thumbs: photos.querySelector( '[data-kmae-photos-thumbs]' ),
				count: photos.querySelector( '[data-kmae-photos-count]' ),
				source: photos.querySelector( '[data-kmae-photos-source]' )
			};
			var pvItems = [];
			var pvCurrent = null;

			var pvCollect = function () {
				var saved = Array.prototype.slice.call( photos.querySelectorAll( '#kmae-gallery-manager .kmae-gallery-manager-item:not(.is-removed)' ) ).map( function ( el ) {
					var img = el.querySelector( 'img' );
					var view = el.querySelector( '.kmae-gallery-manager-view' );
					return { el: el, isNew: false, src: img ? img.src : '', full: view ? ( view.getAttribute( 'data-full' ) || '' ) : '' };
				} );
				var fresh = Array.prototype.slice.call( photos.querySelectorAll( '#kmae-upload-preview .kmae-shot' ) ).map( function ( el ) {
					var img = el.querySelector( 'img' );
					return { el: el, isNew: true, src: img ? img.src : '', full: img ? img.src : '', stop: el.classList.contains( 'is-stop' ), hint: el.title || '', doc: el.classList.contains( 'is-document' ) };
				} );
				var coverNew = photos.querySelector( 'input[name="kmae_cover_new"]' );
				/* Ugyanaz a sorrend, mint mentés után (KMAE_Listings::handle_image_uploads). */
				if ( coverNew && '1' === coverNew.value && fresh.length ) {
					return [ fresh[ 0 ] ].concat( saved, fresh.slice( 1 ) );
				}
				return saved.concat( fresh );
			};

			var pvIndex = function () {
				for ( var i = 0; i < pvItems.length; i++ ) {
					if ( pvItems[ i ].el === pvCurrent ) {
						return i;
					}
				}
				return pvItems.length ? 0 : -1;
			};

			var pvRender = function () {
				pvItems = pvCollect();
				var index = pvIndex();
				pvCurrent = index >= 0 ? pvItems[ index ].el : null;
				var item = index >= 0 ? pvItems[ index ] : null;
				var many = pvItems.length > 1;

				pv.main.hidden = ! item;
				pv.empty.hidden = !! item;
				pv.tools.hidden = ! item;
				pv.open.hidden = ! item;
				pv.prev.hidden = ! many;
				pv.next.hidden = ! many;
				pv.thumbs.hidden = ! pvItems.length;
				pv.count.textContent = pvItems.length ? pvItems.length + ' fotó' : '';

				if ( item ) {
					if ( pv.main.getAttribute( 'src' ) !== item.src ) {
						pv.main.src = item.src;
					}
					var label = '';
					var kind = '';
					if ( item.stop ) {
						label = 'Cseréld ki';
						kind = 'stop';
					} else if ( 0 === index ) {
						label = 'Borítókép';
						kind = 'cover';
					} else if ( item.isNew ) {
						label = item.doc ? 'Új dokumentum' : 'Új fotó';
						kind = 'new';
					}
					pv.badge.textContent = label;
					pv.badge.className = 'kmae-simple-photos__badge' + ( kind ? ' is-' + kind : '' );
					pv.badge.title = item.stop ? item.hint : '';
					pv.badge.hidden = ! label;
					pv.cover.hidden = 0 === index;
				}

				pv.thumbs.innerHTML = '';
				pvItems.forEach( function ( entry, i ) {
					var thumb = document.createElement( 'button' );
					thumb.type = 'button';
					thumb.className = 'kmae-simple-photos__thumb'
						+ ( i === index ? ' is-active' : '' )
						+ ( 0 === i ? ' is-cover' : '' )
						+ ( entry.isNew ? ' is-new' : '' )
						+ ( entry.stop ? ' is-stop' : '' );
					thumb.setAttribute( 'aria-label', ( i + 1 ) + '. fotó' );
					var img = document.createElement( 'img' );
					img.src = entry.src;
					img.alt = '';
					thumb.appendChild( img );
					thumb.addEventListener( 'click', function () {
						pvCurrent = entry.el;
						pvRender();
					} );
					pv.thumbs.appendChild( thumb );
				} );
				var active = pv.thumbs.querySelector( '.is-active' );
				if ( active ) {
					pv.thumbs.scrollLeft = Math.max( 0, active.offsetLeft - ( pv.thumbs.clientWidth - active.offsetWidth ) / 2 );
				}

				/* A minőségi összegzés (photo-motor) a bélyegképek alatt látszódjon. */
				var summary = pv.source.querySelector( '.kmae-shots__summary' );
				if ( summary ) {
					pv.thumbs.parentNode.insertBefore( summary, pv.source );
				}
				/* Az álló fotókhoz tartozó tipp (photo-motor) az összegzés alatt. */
				var tip = pv.source.querySelector( '.kmae-shots__tip' );
				if ( tip ) {
					pv.thumbs.parentNode.insertBefore( tip, pv.source );
				}
			};

			var pvStep = function ( delta ) {
				if ( ! pvItems.length ) {
					return;
				}
				var next = ( pvIndex() + delta + pvItems.length ) % pvItems.length;
				pvCurrent = pvItems[ next ].el;
				pvRender();
			};

			pv.prev.addEventListener( 'click', function () {
				pvStep( -1 );
			} );
			pv.next.addEventListener( 'click', function () {
				pvStep( 1 );
			} );

			pv.cover.addEventListener( 'click', function () {
				var item = pvItems[ pvIndex() ];
				if ( ! item ) {
					return;
				}
				var btn = item.isNew ? item.el.querySelector( '.kmae-shot__tool:not(.kmae-shot__tool--remove)' ) : item.el.querySelector( '.kmae-gallery-manager-cover' );
				if ( btn ) {
					btn.click();
				}
				pvCurrent = null; /* a borító lesz az első, arra állunk */
				pvRender();
			} );

			pv.remove.addEventListener( 'click', function () {
				var index = pvIndex();
				var item = pvItems[ index ];
				if ( ! item ) {
					return;
				}
				var neighbour = pvItems[ index + 1 ] || pvItems[ index - 1 ] || null;
				var btn = item.isNew ? item.el.querySelector( '.kmae-shot__tool--remove' ) : item.el.querySelector( '.kmae-gallery-manager-remove' );
				if ( btn ) {
					btn.click();
				}
				pvItems = pvCollect();
				var still = pvItems.some( function ( entry ) {
					return entry.el === item.el;
				} );
				if ( ! still && neighbour ) {
					pvCurrent = neighbour.el;
				}
				pvRender();
			} );

			/* „Fotók megtekintése”: teljes képernyős lapozó az összes fotóval. */
			pv.open.addEventListener( 'click', function () {
				if ( ! pvItems.length ) {
					return;
				}
				var at = Math.max( 0, pvIndex() );
				var box = document.createElement( 'div' );
				box.className = 'kmae-simple-lightbox';
				box.setAttribute( 'role', 'dialog' );
				box.setAttribute( 'aria-modal', 'true' );
				box.setAttribute( 'aria-label', 'Fotók' );
				box.innerHTML = '<img class="kmae-simple-lightbox__img" alt="" />'
					+ '<button type="button" class="kmae-simple-lightbox__close" aria-label="Bezárás">&times;</button>'
					+ '<button type="button" class="kmae-simple-lightbox__nav kmae-simple-lightbox__nav--prev" aria-label="Előző">&#8249;</button>'
					+ '<button type="button" class="kmae-simple-lightbox__nav kmae-simple-lightbox__nav--next" aria-label="Következő">&#8250;</button>'
					+ '<span class="kmae-simple-lightbox__count"></span>';
				var img = box.querySelector( 'img' );
				var counter = box.querySelector( '.kmae-simple-lightbox__count' );
				var show = function () {
					var entry = pvItems[ at ];
					img.src = entry.full || entry.src;
					counter.textContent = ( at + 1 ) + ' / ' + pvItems.length;
				};
				var move = function ( delta ) {
					at = ( at + delta + pvItems.length ) % pvItems.length;
					show();
				};
				var onKey = function ( event ) {
					if ( 'Escape' === event.key ) {
						shut();
					} else if ( 'ArrowLeft' === event.key ) {
						move( -1 );
					} else if ( 'ArrowRight' === event.key ) {
						move( 1 );
					}
				};
				var shut = function () {
					document.removeEventListener( 'keydown', onKey );
					box.remove();
					pvCurrent = pvItems[ at ] ? pvItems[ at ].el : pvCurrent;
					pvRender();
					pv.open.focus();
				};
				box.querySelector( '.kmae-simple-lightbox__close' ).addEventListener( 'click', shut );
				box.querySelector( '.kmae-simple-lightbox__nav--prev' ).addEventListener( 'click', function () {
					move( -1 );
				} );
				box.querySelector( '.kmae-simple-lightbox__nav--next' ).addEventListener( 'click', function () {
					move( 1 );
				} );
				box.addEventListener( 'click', function ( event ) {
					if ( event.target === box ) {
						shut();
					}
				} );
				if ( pvItems.length < 2 ) {
					box.classList.add( 'is-single' );
				}
				document.addEventListener( 'keydown', onKey );
				document.body.appendChild( box );
				show();
				box.querySelector( '.kmae-simple-lightbox__close' ).focus();
			} );

			/* A forrás változásait (új kép, törlés, sorrend, minőségjelzés) követjük. */
			var pvQueued = false;
			new MutationObserver( function () {
				if ( pvQueued ) {
					return;
				}
				pvQueued = true;
				window.requestAnimationFrame( function () {
					pvQueued = false;
					pvRender();
				} );
			} ).observe( pv.source, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class', 'hidden', 'value', 'title' ] } );
			[ 'kmae:gallery-changed', 'kmae:cover-new', 'kmae:cover-new-cleared' ].forEach( function ( type ) {
				document.addEventListener( type, function () {
					window.requestAnimationFrame( pvRender );
				} );
			} );
			pvRender();
		}

		var yearInput = document.getElementById( 'kmae_vehicle_year' );
		var yearField = yearInput ? yearInput.closest( '.kmae-year-field' ) : null;
		if ( yearInput && yearField ) {
			var yearOpen = yearField.querySelector( '[data-kmae-year-open]' );
			var yearPanel = yearField.querySelector( '[data-kmae-year-panel]' );
			var yearGrid = yearField.querySelector( '[data-kmae-year-grid]' );
			var yearLabel = yearField.querySelector( '[data-kmae-year-label]' );
			var yearPrev = yearField.querySelector( '[data-kmae-year-prev]' );
			var yearNext = yearField.querySelector( '[data-kmae-year-next]' );
			var minYear = parseInt( yearInput.getAttribute( 'min' ), 10 ) || 1950;
			var maxYear = parseInt( yearInput.getAttribute( 'max' ), 10 ) || ( new Date().getFullYear() + 1 );
			var yearPage = Math.floor( maxYear / 10 ) * 10;

			var decadeOf = function ( year ) {
				var start = Math.floor( year / 10 ) * 10;
				var oldest = Math.floor( minYear / 10 ) * 10;
				var newest = Math.floor( maxYear / 10 ) * 10;
				if ( start < oldest ) {
					return oldest;
				}
				if ( start > newest ) {
					return newest;
				}
				return start;
			};

			var closeYears = function () {
				if ( ! yearPanel || ! yearOpen ) {
					return;
				}
				yearPanel.hidden = true;
				yearOpen.setAttribute( 'aria-expanded', 'false' );
			};

			var paintYears = function () {
				var from = Math.max( yearPage, minYear );
				var to = Math.min( yearPage + 9, maxYear );
				if ( yearLabel ) {
					yearLabel.textContent = String( from ) + ' – ' + String( to );
				}
				if ( ! yearGrid ) {
					return;
				}
				var current = parseInt( yearInput.value, 10 );
				yearGrid.innerHTML = '';
				var year;
				for ( year = from; year <= to; year++ ) {
					var choice = document.createElement( 'button' );
					choice.type = 'button';
					choice.textContent = String( year );
					if ( year === current ) {
						choice.className = 'is-selected';
					}
					choice.addEventListener( 'click', function () {
						yearInput.value = this.textContent;
						yearInput.dispatchEvent( new Event( 'input', { bubbles: true } ) );
						yearInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
						closeYears();
					} );
					yearGrid.appendChild( choice );
				}
				if ( yearPrev ) {
					yearPrev.disabled = yearPage <= Math.floor( minYear / 10 ) * 10;
				}
				if ( yearNext ) {
					yearNext.disabled = yearPage + 10 > maxYear;
				}
			};

			if ( yearOpen && yearPanel ) {
				yearOpen.addEventListener( 'click', function () {
					if ( yearPanel.hidden ) {
						var current = parseInt( yearInput.value, 10 );
						yearPage = decadeOf( isNaN( current ) ? maxYear : current );
						paintYears();
						yearPanel.hidden = false;
						yearOpen.setAttribute( 'aria-expanded', 'true' );
					} else {
						closeYears();
					}
				} );
				if ( yearPrev ) {
					yearPrev.addEventListener( 'click', function () {
						yearPage = Math.max( Math.floor( minYear / 10 ) * 10, yearPage - 10 );
						paintYears();
					} );
				}
				if ( yearNext ) {
					yearNext.addEventListener( 'click', function () {
						if ( yearPage + 10 <= maxYear ) {
							yearPage += 10;
							paintYears();
						}
					} );
				}
				document.addEventListener( 'mousedown', function ( event ) {
					if ( ! yearField.contains( event.target ) ) {
						closeYears();
					}
				} );
				document.addEventListener( 'keydown', function ( event ) {
					if ( 'Escape' === event.key ) {
						closeYears();
					}
				} );
			}
		}
	} );
}() );
