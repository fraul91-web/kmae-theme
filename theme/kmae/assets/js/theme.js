/**
 * Albira theme — header dropdowns, drawer, bottom nav helpers, lightbox
 */
(function () {
	'use strict';

	var header = document.getElementById('site-header');
	var drawer = document.getElementById('kmae-drawer');
	var backdrop = document.getElementById('kmae-drawer-backdrop');
	var drawerOpenBtn = document.getElementById('kmae-drawer-open');
	var drawerCloseBtn = document.getElementById('kmae-drawer-close');

	function onScroll() {
		if (!header) return;
		header.classList.toggle('is-scrolled', window.scrollY > 8);
	}

	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });

	/* ---------- Drawer ---------- */
	function setDrawerOpen(open) {
		if (!drawer || !backdrop) return;
		document.body.classList.toggle('kmae-drawer-open', open);
		drawer.hidden = !open;
		backdrop.hidden = !open;
		drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
		if (drawerOpenBtn) {
			drawerOpenBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		if (open && drawerCloseBtn) {
			drawerCloseBtn.focus();
		} else if (open && drawerOpenBtn) {
			drawerOpenBtn.focus();
		}
	}

	if (drawerOpenBtn) {
		drawerOpenBtn.addEventListener('click', function () {
			setDrawerOpen(drawer.hidden);
		});
	}
	if (drawerCloseBtn) {
		drawerCloseBtn.addEventListener('click', function () {
			setDrawerOpen(false);
		});
	}
	if (backdrop) {
		backdrop.addEventListener('click', function () {
			setDrawerOpen(false);
		});
	}

	/* ---------- Header dropdowns (desktop) ---------- */
	var dropdowns = document.querySelectorAll('[data-kmae-dropdown]');

	function closeDropdown(wrap) {
		var btn = wrap.querySelector('button[aria-expanded]');
		var panel = wrap.querySelector('.kmae-header__panel');
		if (btn) btn.setAttribute('aria-expanded', 'false');
		if (panel) panel.hidden = true;
		wrap.classList.remove('is-open');
	}

	function closeAllDropdowns(except) {
		dropdowns.forEach(function (wrap) {
			if (except && wrap === except) return;
			closeDropdown(wrap);
		});
	}

	function openDropdown(wrap) {
		closeAllDropdowns(wrap);
		var btn = wrap.querySelector('button[aria-expanded]');
		var panel = wrap.querySelector('.kmae-header__panel');
		if (btn) btn.setAttribute('aria-expanded', 'true');
		if (panel) panel.hidden = false;
		wrap.classList.add('is-open');
	}

	dropdowns.forEach(function (wrap) {
		var btn = wrap.querySelector('button[aria-expanded]');
		if (!btn) return;

		wrap.addEventListener('click', function (event) {
			event.stopPropagation();
		});

		btn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			if (wrap.classList.contains('is-open')) {
				closeDropdown(wrap);
			} else {
				openDropdown(wrap);
			}
		});
	});

	document.addEventListener('click', function () {
		closeAllDropdowns();
	});

	/* ---------- Footer collapse ---------- */
	var footer = document.getElementById('site-footer');
	var footerToggle = document.getElementById('kmae-footer-toggle');
	var footerPanel = document.getElementById('kmae-footer-panel');

	function setFooterOpen(open) {
		if (!footer || !footerToggle || !footerPanel) return;
		footer.classList.toggle('is-open', open);
		footerPanel.hidden = !open;
		var label = open
			? footerToggle.getAttribute('data-label-open') || 'Lábléc bezárása'
			: footerToggle.getAttribute('data-label-closed') || 'Lábléc megnyitása';
		footerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		footerToggle.setAttribute('aria-label', label);
	}

	if (footerToggle) {
		footerToggle.addEventListener('click', function () {
			setFooterOpen(!footer.classList.contains('is-open'));
		});
	}

	/* ---------- Phone sheet tabs ---------- */
	(function initSheetTabs() {
		var sheet = document.querySelector('[data-kmae-sheet]');
		if (!sheet) return;
		var tabs = sheet.querySelectorAll('[data-sheet-tab]');
		var panels = sheet.querySelectorAll('[data-sheet-panel]');

		function showTab(id) {
			tabs.forEach(function (tab) {
				var on = tab.getAttribute('data-sheet-tab') === id;
				tab.classList.toggle('is-active', on);
				tab.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			panels.forEach(function (panel) {
				var on = panel.getAttribute('data-sheet-panel') === id;
				panel.classList.toggle('is-active', on);
				panel.hidden = !on;
			});
		}

		sheet.addEventListener('click', function (event) {
			var tab = event.target.closest('[data-sheet-tab]');
			if (!tab) return;
			event.preventDefault();
			showTab(tab.getAttribute('data-sheet-tab'));
		});
	})();

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeAllDropdowns();
			setDrawerOpen(false);
			setFooterOpen(false);
		}
	});

	/* ---------- Bottom nav active state ---------- */
	var bottomItems = document.querySelectorAll('.kmae-bottom-nav__item');
	if (bottomItems.length) {
		var path = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
		bottomItems.forEach(function (item) {
			try {
				var href = item.getAttribute('href') || '';
				var itemPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
				if (itemPath === path) {
					item.classList.add('is-active');
				}
			} catch (e) {
				/* ignore */
			}
		});
	}

	/* ---------- Guest marketing nav: current page + hash sections ---------- */
	var guestNav = document.querySelector('[data-kmae-guest-nav]');
	if (guestNav) {
		var guestLinks = guestNav.querySelectorAll('[data-nav]');
		var homePath = (document.body.getAttribute('data-home') || '/').replace(/\/+$/, '') || '/';
		var pathNow = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
		var onHome = pathNow === homePath;

		function setGuestActive(key) {
			guestLinks.forEach(function (link) {
				link.classList.toggle('is-active', link.getAttribute('data-nav') === key);
			});
		}

		function syncGuestFromHash() {
			if (!onHome) return;
			var hash = (window.location.hash || '').replace(/^#/, '');
			if (!hash) {
				setGuestActive('home');
			}
		}

		syncGuestFromHash();
		window.addEventListener('hashchange', syncGuestFromHash);

		guestLinks.forEach(function (link) {
			link.addEventListener('click', function () {
				var key = link.getAttribute('data-nav');
				if (!key) return;
				setGuestActive(key);
			});
		});
	}

	/* ---------- YouTube lightbox ---------- */
	var lightbox = document.getElementById('kmae-video-lightbox');
	var iframe = document.getElementById('kmae-video-iframe');
	if (!lightbox || !iframe) {
		return;
	}

	var lastFocus = null;
	var closeBtns = lightbox.querySelectorAll('[data-kmae-video-close]');

	function embedUrl(videoId) {
		return (
			'https://www.youtube-nocookie.com/embed/' +
			encodeURIComponent(videoId) +
			'?autoplay=1&rel=0&modestbranding=1&playsinline=1'
		);
	}

	function openVideo(videoId, trigger) {
		if (!videoId) return;
		lastFocus = trigger || document.activeElement;
		iframe.src = embedUrl(videoId);
		lightbox.hidden = false;
		document.body.classList.add('kmae-video-open');
		var closeBtn = lightbox.querySelector('.kmae-video-lightbox__close');
		if (closeBtn) closeBtn.focus();
	}

	function closeVideo() {
		iframe.src = '';
		lightbox.hidden = true;
		document.body.classList.remove('kmae-video-open');
		if (lastFocus && typeof lastFocus.focus === 'function') {
			lastFocus.focus();
		}
		lastFocus = null;
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-kmae-video-id]');
		if (trigger) {
			event.preventDefault();
			openVideo(trigger.getAttribute('data-kmae-video-id'), trigger);
		}
	});

	closeBtns.forEach(function (btn) {
		btn.addEventListener('click', function (event) {
			event.preventDefault();
			closeVideo();
		});
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !lightbox.hidden) {
			closeVideo();
		}
	});
})();

/* Alsó menüsor: az oldal legalján eltűnik, hogy a lábléc nyila elérhető legyen. */
(function () {
	'use strict';

	var ticking = false;

	function update() {
		ticking = false;
		var doc = document.documentElement;
		var scrollable = doc.scrollHeight > window.innerHeight + 8;
		var atBottom = scrollable && window.innerHeight + window.pageYOffset >= doc.scrollHeight - 48;
		document.body.classList.toggle('kmae-at-bottom', atBottom);
	}

	function request() {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(update);
		}
	}

	window.addEventListener('scroll', request, { passive: true });
	window.addEventListener('resize', request);
	window.addEventListener('load', request);
	request();
})();
