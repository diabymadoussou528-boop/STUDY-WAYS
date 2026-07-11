const CACHE_NAME = 'studways-v1';
const OFFLINE_URLS = [
  '/',
  '/catalogue',
  '/manifest.json',
  '/css/tokens.css',
  '/css/brand.css',
  '/css/admin.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(OFFLINE_URLS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      const networkFetch = fetch(event.request)
        .then((response) => {
          if (response && response.status === 200 && event.request.url.startsWith(self.location.origin)) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => cached);

      return cached || networkFetch;
    })
  );
});

self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : { title: 'StudyWays', body: 'Nouvelle notification' };
  event.waitUntil(
    self.registration.showNotification(data.title || 'StudyWays', {
      body: data.body || '',
      icon: '/images/pwa-icon-192.png',
      badge: '/images/pwa-icon-192.png',
    })
  );
});
