/**
 * Albira — böngészőértesítések service workere (KMAE_Push).
 *
 * Push érkezésekor:
 * - jelez minden nyitott Albira fülnek (azonnal frissítik magukat),
 * - és felugró értesítést mutat, hacsak nem épp az érintett chat van előtérben
 *   (máshol böngészve is szól).
 * Kattintásra a megadott oldalt nyitja meg (vagy a már nyitott fület hozza előre).
 */
self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

/* Ugyanaz az oldal-e (útvonal + beszélgetés), a kmae_success stb. nélkül. */
function samePage(a, b) {
  try {
    var x = new URL(a);
    var y = new URL(b, self.location.origin);
    return x.origin === y.origin
      && x.pathname.replace(/\/+$/, '') === y.pathname.replace(/\/+$/, '')
      && (x.searchParams.get('kmae_conversation') || '') === (y.searchParams.get('kmae_conversation') || '');
  } catch (e) {
    return false;
  }
}

self.addEventListener('push', function (event) {
  var data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = {};
  }

  var title = data.title || 'Albira';
  var options = {
    body: data.body || '',
    icon: data.icon || undefined,
    badge: data.icon || undefined,
    tag: data.tag || 'kmae',
    renotify: true,
    data: { url: data.url || '/' }
  };

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
      var focused = false;
      clients.forEach(function (client) {
        client.postMessage({ type: 'kmae-push' });
        if (client.focused && client.visibilityState === 'visible' && samePage(client.url, options.data.url)) {
          focused = true;
        }
      });
      try {
        var channel = new BroadcastChannel('kmae-push');
        channel.postMessage({ type: 'kmae-push' });
        channel.close();
      } catch (e) {}

      /* Ha épp ezt a chatet nézi, az oldal maga frissül — nem zavarunk. */
      if (focused) {
        return undefined;
      }
      return self.registration.showNotification(title, options);
    })
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
      for (var i = 0; i < clients.length; i++) {
        var client = clients[i];
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      return self.clients.openWindow ? self.clients.openWindow(url) : undefined;
    })
  );
});
