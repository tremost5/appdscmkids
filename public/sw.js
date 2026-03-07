const CACHE_NAME = 'dscmkids-unified-v5';
const OFFLINE_URL = '/pwa/offline.html';

const APP_SHELL_ASSETS = [
  OFFLINE_URL,
  '/assets/adminlte/css/adminlte.min.css',
  '/assets/adminlte/plugins/fontawesome-free/css/all.min.css',
  '/assets/custom/ui-phase1.css',
];

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function shouldBypass(path) {
  return (
    path.includes('/ajax') ||
    path.includes('/count') ||
    path.includes('/notif') ||
    path.includes('/simpan') ||
    path.includes('/delete') ||
    path.includes('/logout')
  );
}

function isStaticAsset(path) {
  return (
    path.startsWith('/assets/') ||
    path.startsWith('/uploads/') ||
    path.startsWith('/pwa/icons/') ||
    path.endsWith('.css') ||
    path.endsWith('.js') ||
    path.endsWith('.png') ||
    path.endsWith('.jpg') ||
    path.endsWith('.jpeg') ||
    path.endsWith('.webp') ||
    path.endsWith('.svg') ||
    path.endsWith('.ico')
  );
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await Promise.all(APP_SHELL_ASSETS.map(async (asset) => {
      try {
        const response = await fetch(asset, { cache: 'no-cache' });
        if (response && response.ok) {
          await cache.put(asset, response.clone());
        }
      } catch (e) {
        // Skip broken assets instead of failing SW install.
      }
    }));
  })());
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

async function networkFirst(request, fallback) {
  const cache = await caches.open(CACHE_NAME);
  try {
    const response = await fetch(request);
    if (response && response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await cache.match(request);
    if (cached) return cached;
    return caches.match(fallback);
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);

  const fetchPromise = fetch(request)
    .then((response) => {
      if (response && response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => cached);

  return cached || fetchPromise;
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (!isSameOrigin(url)) return;

  const path = url.pathname;
  if (shouldBypass(path)) return;

  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request, OFFLINE_URL));
    return;
  }

  if (isStaticAsset(path)) {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }

  event.respondWith(networkFirst(request, OFFLINE_URL));
});

self.addEventListener('push', (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (error) {
    payload = { title: 'Dscmkids App', body: event.data ? event.data.text() : 'Ada notifikasi baru.' };
  }

  const title = payload.title || 'Dscmkids App';
  const options = {
    body: payload.body || 'Ada informasi baru.',
    icon: payload.icon || '/pwa/icons/icon-192.png',
    badge: payload.badge || '/pwa/icons/icon-192.png',
    tag: payload.tag || 'dscmkids-default',
    data: payload.data || { url: payload.url || '/dashboard' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || '/dashboard';

  event.waitUntil((async () => {
    const clientsList = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of clientsList) {
      if ('focus' in client) {
        client.navigate(targetUrl);
        return client.focus();
      }
    }

    if (clients.openWindow) {
      return clients.openWindow(targetUrl);
    }
  })());
});
