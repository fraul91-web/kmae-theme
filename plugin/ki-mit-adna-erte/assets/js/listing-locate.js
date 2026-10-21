/**
 * Hirdetés űrlap (wizard és egyszerű szerkesztő): „Jelenlegi helyzetem megadása”.
 * Konfiguráció: window.kmaeListingLocate { ajaxUrl, locateNonce }.
 */
(function () {
  'use strict';

  var cfg = window.kmaeListingLocate || {};

  /**
   * „Jelenlegi helyzetem megadása”: böngészős helymeghatározás, majd a szerver
   * (KMAE_Counties::ajax_locate, OpenStreetMap) megyét és települést ad vissza.
   * A mezők utána is szabadon átírhatók.
   */
  function bindLocate() {
    var form = document.getElementById('kmae-listing-form');
    var btn = form ? form.querySelector('[data-kmae-locate]') : null;
    if (!btn) {
      return;
    }
    var status = form.querySelector('[data-kmae-locate-status]');
    var county = document.getElementById('kmae_county');
    var city = document.getElementById('kmae_city');

    function say(text, kind) {
      if (!status) {
        return;
      }
      status.textContent = text || '';
      status.className = 'kmae-locate__status' + (kind ? ' is-' + kind : '');
    }

    function setField(field, value) {
      if (!field || !value) {
        return;
      }
      field.value = value;
      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function done() {
      btn.disabled = false;
      btn.classList.remove('is-loading');
    }

    /* Saját engedélykérő ablak: a <body>-ba kerül, hogy az űrlap
       transzformált konténerei ne torzítsák a fix pozíciót. */
    var dialog = form.querySelector('[data-kmae-locate-dialog]');
    var lastFocus = null;
    if (dialog) {
      document.body.appendChild(dialog);
    }

    function openDialog(view) {
      if (!dialog) {
        locate();
        return;
      }
      Array.prototype.forEach.call(dialog.querySelectorAll('[data-kmae-locate-view]'), function (el) {
        el.hidden = el.getAttribute('data-kmae-locate-view') !== view;
      });
      lastFocus = document.activeElement;
      dialog.hidden = false;
      document.documentElement.classList.add('kmae-locate-open');
      window.requestAnimationFrame(function () {
        dialog.classList.add('is-open');
        var focusEl = dialog.querySelector('[data-kmae-locate-view="' + view + '"] .kmae-locate-dialog__primary');
        if (focusEl) {
          focusEl.focus();
        }
      });
    }

    function closeDialog() {
      if (!dialog || dialog.hidden) {
        return;
      }
      dialog.classList.remove('is-open');
      document.documentElement.classList.remove('kmae-locate-open');
      dialog.hidden = true;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }

    if (dialog) {
      Array.prototype.forEach.call(dialog.querySelectorAll('[data-kmae-locate-close]'), function (el) {
        el.addEventListener('click', closeDialog);
      });
      var allow = dialog.querySelector('[data-kmae-locate-allow]');
      if (allow) {
        allow.addEventListener('click', function () {
          closeDialog();
          locate();
        });
      }
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !dialog.hidden) {
          closeDialog();
        }
      });
    }

    /* Tényleges engedélyállapot: 'granted' | 'denied' | 'prompt' | 'unknown'. */
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

    /* Ha a felhasználó később mégis engedélyez a böngészőben (pl. a Chrome
       címsorában maradt „Engedélyezi a helyadatok használatát?” feliratnál),
       az ablakunk bezárul és a kitöltés magától elindul. */
    var waitingForGrant = false;
    if (navigator.permissions && typeof navigator.permissions.query === 'function') {
      navigator.permissions.query({ name: 'geolocation' }).then(function (perm) {
        perm.addEventListener('change', function () {
          if (perm.state === 'granted' && (waitingForGrant || (dialog && !dialog.hidden))) {
            waitingForGrant = false;
            closeDialog();
            locate();
          }
        });
      }).catch(function () {});
    }

    function locate() {
      btn.disabled = true;
      btn.classList.add('is-loading');
      say('Helyzet meghatározása…');

      navigator.geolocation.getCurrentPosition(function (pos) {
        var body = new FormData();
        body.append('action', 'kmae_locate');
        body.append('nonce', cfg.locateNonce || '');
        body.append('lat', String(pos.coords.latitude));
        body.append('lon', String(pos.coords.longitude));

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
          .then(function (res) { return res.json(); })
          .then(function (json) {
            done();
            if (!json || !json.success) {
              say((json && json.data && json.data.message) || 'Most nem sikerült — add meg kézzel.', 'error');
              return;
            }
            var data = json.data || {};
            if (county && data.county && county.querySelector('option[value="' + data.county + '"]')) {
              setField(county, data.county);
            }
            setField(city, data.city);
            btn.classList.add('is-done');
            say('Kitöltöttük — ha nem pontos, nyugodtan írd át.', 'ok');
          })
          .catch(function () {
            done();
            say('Most nem sikerült — add meg kézzel.', 'error');
          });
      }, function (err) {
        done();
        if (err && err.code === 1) {
          /* A böngésző ezt a kódot akkor is adja, ha a kérdését csak bezárták
             vagy nem válaszoltak rá — a „letiltva” ablak csak valódi tiltásnál jön. */
          permissionState().then(function (state) {
            if (state === 'denied' || state === 'unknown') {
              say('');
              openDialog('blocked');
            } else if (state !== 'granted') {
              /* Chrome „csendes” mód: azonnal elutasít, és csak a címsorban hagy
                 egy kérdést. Ha ott engedélyez, a fenti figyelő folytatja. */
              waitingForGrant = true;
              say('Kattints a címsor bal szélén megjelent „Engedélyezi a helyadatok használatát?” feliratra, és válaszd az engedélyezést — utána magától kitöltjük.', 'error');
            }
          });
          return;
        }
        say('Nem sikerült meghatározni a helyzetedet — add meg kézzel.', 'error');
      }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 300000 });
    }

    btn.addEventListener('click', function () {
      if (!navigator.geolocation || !cfg.ajaxUrl) {
        say('A böngésződ nem támogatja a helymeghatározást — add meg kézzel.', 'error');
        return;
      }
      say('');

      /* Letiltott → útmutató ablak; minden más esetben rögtön a böngésző
         kérdez (egyetlen kérdés — saját előzetes ablak nincs). */
      permissionState().then(function (state) {
        if (state === 'denied') {
          openDialog('blocked');
        } else {
          locate();
        }
      });
    });
  }

  if (document.readyState !== 'loading') {
    bindLocate();
  } else {
    document.addEventListener('DOMContentLoaded', bindLocate);
  }
}());
