/**
 * Böngészés — rendezőmenü és „Távolság szerint” ablak.
 * Sablon: templates/browse-swipe.php (eszköztár), templates/partials/browse-distance.php.
 * Szerver: KMAE_Browse::current_sort() / apply_sort() — `?rendez=tavolsag&lat&lon&km`.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  /* ---------- Rendezőmenü ---------- */
  function bindMenu(sort) {
    var toggle = sort.querySelector('[data-kmae-sort-toggle]');
    var menu = sort.querySelector('[data-kmae-sort-menu]');
    if (!toggle || !menu) {
      return;
    }

    function setOpen(on) {
      menu.hidden = !on;
      toggle.setAttribute('aria-expanded', on ? 'true' : 'false');
      sort.classList.toggle('is-open', on);
      if (on) {
        var current = menu.querySelector('.is-current') || menu.querySelector('[role="menuitem"]');
        if (current) {
          current.focus({ preventScroll: true });
        }
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(menu.hidden);
    });
    document.addEventListener('mousedown', function (event) {
      if (!menu.hidden && !sort.contains(event.target)) {
        setOpen(false);
      }
    });
    sort.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !menu.hidden) {
        setOpen(false);
        toggle.focus();
        return;
      }
      if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && !menu.hidden) {
        event.preventDefault();
        var items = Array.prototype.slice.call(menu.querySelectorAll('[role="menuitem"]'));
        var at = items.indexOf(document.activeElement);
        var next = event.key === 'ArrowDown' ? at + 1 : at - 1;
        items[(next + items.length) % items.length].focus();
      }
    });

    return setOpen;
  }

  /* ---------- Távolság ablak ---------- */
  function bindDistance(dist, closeMenu) {
    document.body.appendChild(dist);
    var card = dist.querySelector('.kmae-dist__card');
    var views = {};
    Array.prototype.forEach.call(dist.querySelectorAll('[data-kmae-dist-view]'), function (view) {
      views[view.getAttribute('data-kmae-dist-view')] = view;
    });
    var status = dist.querySelector('[data-kmae-dist-status]');
    var range = dist.querySelector('[data-kmae-dist-range]');
    var valueEl = dist.querySelector('[data-kmae-dist-value]');
    var chips = Array.prototype.slice.call(dist.querySelectorAll('[data-kmae-dist-chip]'));
    var base = dist.getAttribute('data-base') || window.location.href;
    var coords = null;
    var km = parseInt(dist.getAttribute('data-km'), 10);
    var lastFocus = null;
    var waitingForGrant = false;

    if (dist.getAttribute('data-lat') && dist.getAttribute('data-lon')) {
      coords = { lat: parseFloat(dist.getAttribute('data-lat')), lon: parseFloat(dist.getAttribute('data-lon')) };
    }
    if (isNaN(km)) {
      km = 50;
    }

    function show(name) {
      Object.keys(views).forEach(function (key) {
        views[key].hidden = key !== name;
      });
      var primary = views[name].querySelector('.kmae-dist__btn--primary') || card;
      window.requestAnimationFrame(function () {
        primary.focus({ preventScroll: true });
      });
    }

    function open(view) {
      lastFocus = document.activeElement;
      dist.hidden = false;
      document.documentElement.classList.add('kmae-dist-open');
      show(view);
      window.requestAnimationFrame(function () {
        dist.classList.add('is-open');
      });
    }

    function close() {
      waitingForGrant = false;
      dist.classList.remove('is-open');
      document.documentElement.classList.remove('kmae-dist-open');
      dist.hidden = true;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus({ preventScroll: true });
      }
    }

    function paintRadius() {
      valueEl.textContent = km > 0 ? km + ' km-es körben' : 'Egész ország';
      dist.classList.toggle('is-country', km === 0);
      if (km > 0) {
        range.value = String(km);
      }
      var pct = km > 0 ? ((km - range.min) / (range.max - range.min)) * 100 : 100;
      range.style.setProperty('--fill', pct + '%');
      chips.forEach(function (chip) {
        chip.classList.toggle('is-active', parseInt(chip.getAttribute('data-kmae-dist-chip'), 10) === km);
      });
    }

    function permissionState() {
      if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
        return Promise.resolve('unknown');
      }
      return navigator.permissions.query({ name: 'geolocation' }).then(function (perm) {
        return perm.state;
      }).catch(function () {
        return 'unknown';
      });
    }

    function locate() {
      if (!navigator.geolocation) {
        status.textContent = 'A böngésződ nem támogatja a helymeghatározást.';
        status.className = 'kmae-dist__status is-error';
        return;
      }
      status.textContent = 'Helyzet meghatározása…';
      status.className = 'kmae-dist__status';
      navigator.geolocation.getCurrentPosition(function (pos) {
        waitingForGrant = false;
        coords = { lat: pos.coords.latitude, lon: pos.coords.longitude };
        if (coords.lat < 45.6 || coords.lat > 48.7 || coords.lon < 16 || coords.lon > 23) {
          status.textContent = 'A helyzeted alapján nem Magyarországon vagy — a távolság szerinti rendezés itt nem használható.';
          status.className = 'kmae-dist__status is-error';
          coords = null;
          return;
        }
        paintRadius();
        show('radius');
      }, function (err) {
        if (err && err.code === 1) {
          permissionState().then(function (state) {
            if (state === 'denied' || state === 'unknown') {
              show('denied');
            } else {
              /* Chrome „csendes” kérdés: a címsorban maradt. Ha ott engedélyez, folytatjuk. */
              waitingForGrant = true;
              status.textContent = 'Kattints a címsor bal szélén megjelent kérdésre, és engedélyezd a helyadatokat — utána magától folytatjuk.';
              status.className = 'kmae-dist__status is-error';
            }
          });
          return;
        }
        status.textContent = 'Most nem sikerült meghatározni a helyzetedet. Próbáld újra egy kicsit később.';
        status.className = 'kmae-dist__status is-error';
      }, { enableHighAccuracy: false, timeout: 12000, maximumAge: 600000 });
    }

    if (navigator.permissions && typeof navigator.permissions.query === 'function') {
      navigator.permissions.query({ name: 'geolocation' }).then(function (perm) {
        perm.addEventListener('change', function () {
          if (perm.state === 'granted' && waitingForGrant && !dist.hidden) {
            locate();
          }
        });
      }).catch(function () {});
    }

    function start() {
      if (closeMenu) {
        closeMenu(false);
      }
      if (coords) {
        open('radius');
        paintRadius();
        return;
      }
      open('locating');
      permissionState().then(function (state) {
        if (state === 'denied') {
          show('denied');
        } else {
          locate();
        }
      });
    }

    range.addEventListener('input', function () {
      km = parseInt(range.value, 10) || 50;
      paintRadius();
    });
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        km = parseInt(chip.getAttribute('data-kmae-dist-chip'), 10) || 0;
        paintRadius();
      });
    });

    dist.querySelector('[data-kmae-dist-apply]').addEventListener('click', function () {
      if (!coords) {
        return;
      }
      var url = new URL(base, window.location.href);
      url.searchParams.set('rendez', 'tavolsag');
      /* Két tizedes (~1 km) elég — pontos helyet nem küldünk tovább. */
      url.searchParams.set('lat', coords.lat.toFixed(2));
      url.searchParams.set('lon', coords.lon.toFixed(2));
      url.searchParams.set('km', String(km));
      url.searchParams.delete('oldal');
      window.location.href = url.toString();
    });

    Array.prototype.forEach.call(dist.querySelectorAll('[data-kmae-dist-close]'), function (el) {
      el.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !dist.hidden) {
        close();
      }
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-kmae-sort-distance], [data-kmae-sort-radius]'), function (btn) {
      btn.addEventListener('click', start);
    });
  }

  ready(function () {
    var sort = document.querySelector('[data-kmae-sort]');
    var setOpen = sort ? bindMenu(sort) : null;
    var dist = document.querySelector('[data-kmae-dist]');
    if (dist) {
      bindDistance(dist, setOpen);
    }
  });
}());
