/**
 * Albira — böngészőértesítések (KMAE_Push).
 *
 * - Regisztráció utáni ablak: „Bekapcsolom” → böngésző engedély → feliratkozás.
 *   „Most nem” → többet nem jön fel magától.
 * - Beállítások → „Böngésző-értesítések” kapcsoló ([data-kmae-push-toggle]).
 * - Engedélyezett eszközön a feliratkozás magától frissen marad; a szervernek
 *   csak változáskor vagy naponta egyszer szólunk (nem minden oldalbetöltéskor).
 */
(function () {
  'use strict';

  var cfg = window.kmaePush;
  if (!cfg) {
    return;
  }

  var supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext;
  var SENT_KEY = 'kmae_push_sent';

  function store(key, value) {
    try {
      if (value === null) {
        window.localStorage.removeItem(key);
      } else {
        window.localStorage.setItem(key, value);
      }
    } catch (e) {}
  }

  function read(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (e) {
      return null;
    }
  }

  function post(action, data) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce', cfg.nonce);
    Object.keys(data || {}).forEach(function (k) {
      body.append(k, data[k]);
    });
    return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (res) {
      return res.json();
    });
  }

  function keyBytes(base64) {
    var padding = '='.repeat((4 - (base64.length % 4)) % 4);
    var raw = window.atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) {
      out[i] = raw.charCodeAt(i);
    }
    return out;
  }

  function b64(buffer) {
    var bytes = new Uint8Array(buffer);
    var str = '';
    for (var i = 0; i < bytes.length; i++) {
      str += String.fromCharCode(bytes[i]);
    }
    return window.btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function sameKey(sub) {
    var current = sub && sub.options && sub.options.applicationServerKey;
    return !current || b64(current) === cfg.publicKey;
  }

  function registration() {
    return navigator.serviceWorker.register(cfg.swUrl).then(function () {
      return navigator.serviceWorker.getRegistration(cfg.swUrl);
    }).then(function (reg) {
      return reg || navigator.serviceWorker.register(cfg.swUrl);
    });
  }

  /* Feliratkozás (vagy a meglévő ellenőrzése) + mentés a szerverre. */
  function subscribe(force) {
    return registration().then(function (reg) {
      return reg.pushManager.getSubscription().then(function (sub) {
        if (sub && !sameKey(sub)) {
          return sub.unsubscribe().then(function () {
            return null;
          });
        }
        return sub;
      }).then(function (sub) {
        return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(cfg.publicKey) });
      });
    }).then(function (sub) {
      var json = sub.toJSON();
      var marker = json.endpoint + '|' + new Date().toISOString().slice(0, 10);
      if (!force && read(SENT_KEY) === marker) {
        return true;
      }
      return post('kmae_push_subscribe', {
        endpoint: json.endpoint,
        p256dh: json.keys && json.keys.p256dh,
        auth: json.keys && json.keys.auth
      }).then(function (res) {
        if (res && res.success) {
          store(SENT_KEY, marker);
          cfg.enabled = true;
          cfg.prompted = true;
          return true;
        }
        return false;
      });
    });
  }

  function unsubscribe() {
    var local = navigator.serviceWorker.getRegistration(cfg.swUrl).then(function (reg) {
      return reg ? reg.pushManager.getSubscription() : null;
    }).then(function (sub) {
      return sub ? sub.unsubscribe() : true;
    }).catch(function () {});
    return local.then(function () {
      store(SENT_KEY, null);
      cfg.enabled = false;
      return post('kmae_push_unsubscribe', {});
    });
  }

  function askPermission() {
    if (Notification.permission === 'granted') {
      return Promise.resolve('granted');
    }
    return Promise.resolve(Notification.requestPermission());
  }

  /* ---- Regisztráció utáni ablak ---- */
  function initDialog() {
    var dialog = document.querySelector('[data-kmae-push-dialog]');
    if (!dialog) {
      return;
    }
    if (!supported || cfg.prompted) {
      return;
    }
    if (Notification.permission === 'granted') {
      subscribe(true).catch(function () {});
      return;
    }
    if (Notification.permission === 'denied') {
      post('kmae_push_prompted', {}).catch(function () {});
      return;
    }

    var status = dialog.querySelector('[data-kmae-push-status]');
    var allow = dialog.querySelector('[data-kmae-push-allow]');
    var later = dialog.querySelector('[data-kmae-push-later]');

    function close() {
      dialog.classList.remove('is-open');
      document.documentElement.classList.remove('kmae-push-open');
      dialog.hidden = true;
    }

    function open() {
      dialog.hidden = false;
      document.documentElement.classList.add('kmae-push-open');
      window.requestAnimationFrame(function () {
        dialog.classList.add('is-open');
        if (allow) {
          allow.focus();
        }
      });
    }

    if (allow) {
      allow.addEventListener('click', function () {
        allow.disabled = true;
        if (status) {
          status.textContent = 'Egy pillanat — a böngésződ is rákérdez…';
        }
        askPermission().then(function (permission) {
          if (permission === 'granted') {
            return subscribe(true).then(close);
          }
          return post('kmae_push_prompted', {}).then(close);
        }).catch(function () {
          allow.disabled = false;
          if (status) {
            status.textContent = 'Most nem sikerült — a Beállításokban később is bekapcsolhatod.';
          }
        });
      });
    }

    if (later) {
      later.addEventListener('click', function () {
        cfg.prompted = true;
        post('kmae_push_prompted', {}).catch(function () {});
        close();
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !dialog.hidden) {
        cfg.prompted = true;
        post('kmae_push_prompted', {}).catch(function () {});
        close();
      }
    });

    window.setTimeout(open, 1200);
  }

  /* ---- Beállítások kapcsoló ---- */
  function initToggle() {
    var toggle = document.querySelector('[data-kmae-push-toggle]');
    if (!toggle) {
      return;
    }
    var note = document.querySelector('[data-kmae-push-note]');

    function say(text) {
      if (note) {
        note.textContent = text;
      }
    }

    /* A kapcsoló a FIÓK beállítását mutatja (be/ki); a megjegyzés azt, hogy
       EBBEN a böngészőben mi a helyzet. Kikapcsolni mindig lehet. */
    var HOW_TO = 'Engedélyezés: kattints a címsor bal szélén lévő ikonra → Értesítések → Engedélyezés, majd frissítsd az oldalt.';

    function paint() {
      toggle.checked = !!cfg.enabled;
      if (!supported) {
        say(cfg.enabled
          ? 'A fiókodban be van kapcsolva, de ez a böngésző nem támogatja az értesítéseket, ezért itt nem kapsz. iPhone-on: Megosztás → „Főképernyőhöz adás”, és onnan nyisd meg az Albirát.'
          : 'Ez a böngésző nem támogatja az értesítéseket. iPhone-on: Megosztás → „Főképernyőhöz adás”, és onnan nyisd meg az Albirát.');
        return;
      }
      var permission = Notification.permission;
      if (!cfg.enabled) {
        say('Kikapcsolva — az oldalon belül továbbra is látod az újdonságokat.');
      } else if (permission === 'granted') {
        say('Bekapcsolva — azonnal szólunk, ha üzenetet kapsz.');
      } else if (permission === 'denied') {
        say('A fiókodban be van kapcsolva, de ebben a böngészőben le vannak tiltva az értesítések, ezért itt nem kapsz. ' + HOW_TO);
      } else {
        say('A fiókodban be van kapcsolva, de ebben a böngészőben még nem engedélyezted. Kapcsold ki, majd vissza, és a böngésző rákérdez.');
      }
    }

    paint();

    toggle.addEventListener('change', function () {
      toggle.disabled = true;
      var turningOn = toggle.checked;
      var job;

      if (!turningOn) {
        job = unsubscribe();
      } else if (!supported) {
        job = Promise.resolve();
      } else {
        job = askPermission().then(function (permission) {
          if (permission === 'granted') {
            return subscribe(true);
          }
          return null;
        });
      }

      job.catch(function () {}).then(function () {
        toggle.disabled = false;
        paint();
        if (turningOn && !cfg.enabled && supported && Notification.permission === 'denied') {
          say('Ebben a böngészőben le vannak tiltva az értesítések, ezért nem tudjuk bekapcsolni. ' + HOW_TO);
        }
      });
    });
  }

  function boot() {
    if (supported && cfg.enabled && Notification.permission === 'granted') {
      subscribe(false).catch(function () {});
    }
    initDialog();
    initToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
