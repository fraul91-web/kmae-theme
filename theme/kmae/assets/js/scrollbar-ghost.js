/* PC: a „szellem” görgetősáv már akkor mutatja magát, ha az egér az ablak
   jobb szélének közelébe ér (nem kell magára a sávra állni).
   A sáv stílusa: assets/css/scrollbar-ghost.css (html.kmae-sb-near). */
(function () {
	'use strict';

	var EDGE = 48;
	var root = document.documentElement;
	var mq = window.matchMedia ? window.matchMedia('(min-width: 1025px)') : null;
	var near = false;

	function set(on) {
		if (on === near) {
			return;
		}
		near = on;
		root.classList.toggle('kmae-sb-near', on);
	}

	document.addEventListener('mousemove', function (e) {
		if (mq && !mq.matches) {
			set(false);
			return;
		}
		set(e.clientX >= root.clientWidth - EDGE);
	}, { passive: true });

	/* Ha az egér a sávra (a dokumentumon kívülre) lép, a jelzés marad; ha az ablakot
	   máshol hagyja el, eltűnik. */
	document.addEventListener('mouseout', function (e) {
		if (!e.relatedTarget && e.clientX < root.clientWidth - 2) {
			set(false);
		}
	});
}());
