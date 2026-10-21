/**
 * ÁSZF / Adatkezelési tájékoztató egyszeri elfogadása (KMAE_Legal::render_prompt).
 * Az ablak nem zárható be elfogadás nélkül; a másik kiút a kijelentkezés.
 */
(function () {
  'use strict';

  var cfg = window.kmaeTermsAccept || {};

  function init() {
    var root = document.querySelector('[data-kmae-terms-accept]');
    if (!root) {
      return;
    }
    var btn = root.querySelector('[data-kmae-terms-accept-btn]');
    var error = root.querySelector('[data-kmae-terms-accept-error]');

    document.documentElement.classList.add('kmae-terms-accept-open');
    window.requestAnimationFrame(function () {
      root.classList.add('is-open');
      if (btn) {
        btn.focus();
      }
    });

    /* A fókusz az ablakon belül marad. */
    root.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab') {
        return;
      }
      var items = root.querySelectorAll('a[href], button:not([disabled])');
      if (!items.length) {
        return;
      }
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    if (!btn) {
      return;
    }
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.classList.add('is-loading');
      if (error) {
        error.hidden = true;
      }
      var body = new FormData();
      body.append('action', 'kmae_accept_terms');
      body.append('nonce', cfg.nonce || '');
      fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (res) { return res.json(); })
        .then(function (json) {
          if (!json || !json.success) {
            throw new Error('fail');
          }
          root.classList.remove('is-open');
          document.documentElement.classList.remove('kmae-terms-accept-open');
          window.setTimeout(function () {
            if (root.parentNode) {
              root.parentNode.removeChild(root);
            }
          }, 220);
        })
        .catch(function () {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          if (error) {
            error.textContent = cfg.error || '';
            error.hidden = false;
          }
        });
    });
  }

  if (document.readyState !== 'loading') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
}());
