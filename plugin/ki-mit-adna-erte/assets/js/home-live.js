(function () {
  'use strict';

  // Élő főoldal (templates/partials/home-live.php): a számlálók felpörögnek,
  // és ha az előző látogatás óta nőttek, „+N” jelzést kapnak. Az előző
  // értékek csak ebben a böngészőben élnek (localStorage) — ha nincs, 0-ról indul.
  var STORE = 'kmae_home_live';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function readStore() {
    try {
      var raw = window.localStorage.getItem(STORE);
      var data = raw ? JSON.parse(raw) : null;
      return data && typeof data === 'object' ? data : null;
    } catch (e) {
      return null;
    }
  }

  function writeStore(data) {
    try {
      window.localStorage.setItem(STORE, JSON.stringify(data));
    } catch (e) {
      /* privát ablak / tiltott tárolás: nincs „+N”, minden más működik */
    }
  }

  function fmt(n) {
    try {
      return Math.round(n).toLocaleString('hu-HU');
    } catch (e) {
      return String(Math.round(n));
    }
  }

  function countUp(el, from, to, delay) {
    if (reduce || from === to) {
      el.textContent = fmt(to);
      return;
    }
    var duration = Math.min(1600, 700 + Math.abs(to - from) * 40);
    el.textContent = fmt(from);
    window.setTimeout(function () {
      var start = null;
      function step(ts) {
        if (start === null) {
          start = ts;
        }
        var p = Math.min(1, (ts - start) / duration);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(from + (to - from) * eased);
        if (p < 1) {
          window.requestAnimationFrame(step);
        }
      }
      window.requestAnimationFrame(step);
    }, delay);
  }

  function initCounters(root) {
    var prev = readStore();
    var next = {};
    Array.prototype.forEach.call(root.querySelectorAll('[data-kmae-count]'), function (el, i) {
      var to = parseInt(el.getAttribute('data-kmae-count'), 10) || 0;
      var key = el.getAttribute('data-kmae-count-key') || '';
      next[key] = to;
      var had = prev && Object.prototype.hasOwnProperty.call(prev, key);
      var from = had ? (parseInt(prev[key], 10) || 0) : 0;
      if (from > to) {
        from = 0;
      }
      countUp(el, from, to, 250 + i * 60);

      if (had && to > from) {
        var tile = el.closest('.kmae-live-tile');
        if (tile) {
          var plus = document.createElement('span');
          plus.className = 'kmae-live-tile__plus';
          plus.setAttribute('aria-hidden', 'true');
          plus.textContent = '+' + fmt(to - from);
          tile.appendChild(plus);
          tile.classList.add('is-new');
        }
      }
    });
    writeStore(next);
  }

  // „most frissült” → „N perce frissült”: a lap az élő frissítéssel (public.js) magától újratölt.
  function initAgo(root) {
    var el = root.querySelector('[data-kmae-live-ago]');
    if (!el) {
      return;
    }
    var loaded = Date.now();
    window.setInterval(function () {
      var min = Math.floor((Date.now() - loaded) / 60000);
      el.textContent = min < 1 ? 'most frissült' : min + ' perce frissült';
    }, 30000);
  }

  // Gratuláló doboz: a „Köszönöm” / „Köszönöm, nem” után finoman eltűnik,
  // majd a szerver a főoldalra visz vissza, ahol már az élő statisztika jön.
  function initDismiss() {
    var form = document.querySelector('.kmae-first__dismiss');
    var box = document.querySelector('.kmae-first');
    if (!form || !box) {
      return;
    }
    form.addEventListener('submit', function (event) {
      if (form.getAttribute('data-kmae-leaving')) {
        return;
      }
      event.preventDefault();
      form.setAttribute('data-kmae-leaving', '1');
      box.classList.add('is-leaving');
      window.setTimeout(function () {
        form.submit();
      }, reduce ? 0 : 450);
    });
  }

  function init() {
    initDismiss();
    var root = document.querySelector('.kmae-live');
    if (!root) {
      return;
    }
    initCounters(root);
    initAgo(root);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
