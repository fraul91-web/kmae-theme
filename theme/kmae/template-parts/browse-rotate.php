<?php
/**
 * Fekvő telefonon, minden oldalon: állóba forgatást kérő ábra.
 * Stílusát a theme CSS adja; itt csak a jelölés és a kapcsoló van.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="kmae-browse-rotate" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="kmae-browse-rotate-title" tabindex="-1">
	<div class="kmae-browse-rotate__card">
		<div class="kmae-browse-rotate__art" aria-hidden="true">
			<!-- kör alakú forgató nyilak, a telefon KÖRÜL -->
			<svg class="kmae-browse-rotate__arrows" viewBox="0 0 240 240" fill="none" style="transform-box:view-box;transform-origin:center">
				<defs>
					<linearGradient id="kmae-browse-rotate-arc-blue" gradientUnits="userSpaceOnUse" x1="32.5" y1="91.6" x2="209.7" y2="87">
						<stop offset="0" stop-color="#007fb8"/>
						<stop offset="1" stop-color="#00c7f7"/>
					</linearGradient>
					<linearGradient id="kmae-browse-rotate-arc-orange" gradientUnits="userSpaceOnUse" x1="207.5" y1="148.4" x2="30.3" y2="153">
						<stop offset="0" stop-color="#ffad41"/>
						<stop offset="1" stop-color="#ff6e25"/>
					</linearGradient>
				</defs>
				<path d="M32.5 91.6A92 92 0 0 1 198 71.2" stroke="url(#kmae-browse-rotate-arc-blue)" stroke-width="11" stroke-linecap="round"/>
				<path d="M209.7 87 184.7 73 204.1 58.9Z" fill="url(#kmae-browse-rotate-arc-blue)"/>
				<path d="M207.5 148.4A92 92 0 0 1 42 168.8" stroke="url(#kmae-browse-rotate-arc-orange)" stroke-width="11" stroke-linecap="round"/>
				<path d="M30.3 153 55.3 167 35.9 181.1Z" fill="url(#kmae-browse-rotate-arc-orange)"/>
			</svg>
			<!-- telefon középen, a nyilak gyűrűjén belül -->
			<svg class="kmae-browse-rotate__phone" viewBox="0 0 90 160" fill="none">
				<defs>
					<linearGradient id="kmae-browse-rotate-photo" x1="17" y1="32" x2="73" y2="70" gradientUnits="userSpaceOnUse">
						<stop offset="0" stop-color="#007fb8"/>
						<stop offset="1" stop-color="#00c7f7"/>
					</linearGradient>
					<linearGradient id="kmae-browse-rotate-price" x1="23" y1="80" x2="51" y2="80" gradientUnits="userSpaceOnUse">
						<stop offset="0" stop-color="#ffad41"/>
						<stop offset="1" stop-color="#ff6e25"/>
					</linearGradient>
					<clipPath id="kmae-browse-rotate-card">
						<rect x="17" y="32" width="56" height="76" rx="8"/>
					</clipPath>
				</defs>
				<rect x="2" y="2" width="86" height="156" rx="20" fill="#ffffff" stroke="#b9d7ec" stroke-width="2.5"/>
				<rect x="9" y="18" width="72" height="118" rx="9" fill="#f4f9fc"/>
				<rect x="33" y="8" width="24" height="4" rx="2" fill="#c5d8ea"/>
				<rect x="31" y="143.5" width="28" height="3.2" rx="1.6" fill="#c5d8ea"/>
				<g clip-path="url(#kmae-browse-rotate-card)">
					<rect x="17" y="32" width="56" height="76" fill="#ffffff"/>
					<rect x="17" y="32" width="56" height="38" fill="url(#kmae-browse-rotate-photo)"/>
					<rect x="23" y="80" width="28" height="6" rx="3" fill="url(#kmae-browse-rotate-price)"/>
				</g>
				<rect x="17" y="32" width="56" height="76" rx="8" stroke="#d5e8f5"/>
			</svg>
		</div>
		<div class="kmae-browse-rotate__copy">
			<p class="kmae-browse-rotate__title" id="kmae-browse-rotate-title"><?php esc_html_e( 'Fordítsd állóba', 'kmae' ); ?></p>
			<p class="kmae-browse-rotate__lead"><?php esc_html_e( 'Kérlek, állóban használd a telefont.', 'kmae' ); ?></p>
		</div>
	</div>
</div>
<script>
(function () {
	var box = document.currentScript ? document.currentScript.previousElementSibling : null;
	if (!box || !box.classList || !box.classList.contains('kmae-browse-rotate')) {
		box = document.querySelector('.kmae-browse-rotate');
	}
	if (!box || !window.matchMedia) {
		return;
	}

	var query = window.matchMedia('(max-width: 1024px) and (orientation: landscape)');
	var held = [];

	function lock() {
		var kids = document.body.children;
		var i;
		var el;
		for (i = 0; i < kids.length; i++) {
			el = kids[i];
			if (el === box || held.indexOf(el) !== -1 || el.hasAttribute('inert')) {
				continue;
			}
			el.setAttribute('inert', '');
			held.push(el);
		}
	}

	function unlock() {
		var i;
		for (i = 0; i < held.length; i++) {
			held[i].removeAttribute('inert');
		}
		held = [];
	}

	function apply() {
		if (query.matches) {
			box.setAttribute('aria-hidden', 'false');
			lock();
			if (document.activeElement !== box) {
				box.focus();
			}
		} else {
			box.setAttribute('aria-hidden', 'true');
			unlock();
			if (document.activeElement === box) {
				box.blur();
			}
		}
	}

	apply();
	if (query.addEventListener) {
		query.addEventListener('change', apply);
	} else if (query.addListener) {
		query.addListener(apply);
	}
	document.addEventListener('DOMContentLoaded', apply);
})();
</script>
