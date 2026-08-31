/**
 * Compra Captación - Service Worker Oficial (PWA v1.6)
 * https://compracaptacion.com/
 */

const CACHE_NAME = 'captacion-pwa-v1.6';
const PRECACHE_ASSETS = [
  '/',
  '/manifest.json',
  '/offline.html',
  '/assets/media/apple-touch-icon.png',
  '/assets/media/icon-192.png',
  '/assets/media/icon-512.png',
  '/assets/media/favicon-compra-captacion.png',
  '/assets/media/og-share-landing.jpg'
];

// 1. INSTALACIÓN: Precarga de recursos estáticos críticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// 2. ACTIVACIÓN: Limpieza de cachés antiguas
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// 3. FETCH: Estrategias de red inteligentes
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Ignorar peticiones de esquemas no soportados o extensiones
  if (!event.request.url.startsWith('http')) return;

  // A. Peticiones de API dinámica (/api/): Network First con bypass de caché
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(JSON.stringify({ ok: false, error: 'Sin conexión a internet. Modo sin conexión activo.', offline: true }), {
          headers: { 'Content-Type': 'application/json' }
        });
      })
    );
    return;
  }

  // B. Navegación HTML: Network First con fallback a /offline.html
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
          }
          return networkResponse;
        })
        .catch(async () => {
          const cachedResponse = await caches.match(event.request);
          if (cachedResponse) return cachedResponse;
          const offlinePage = await caches.match('/offline.html');
          return offlinePage || new Response('<h1>Sin conexión</h1><p>Por favor reconecta a internet para acceder a Compra Captación.</p>', {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
          });
        })
    );
    return;
  }

  // C. Recursos Estáticos (JS, CSS, Imágenes, Fuentes): Stale While Revalidate
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      const fetchPromise = fetch(event.request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && event.request.method === 'GET') {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
          }
          return networkResponse;
        })
        .catch(() => cachedResponse);

      return cachedResponse || fetchPromise;
    })
  );
});

// 4. MENSAJERÍA ENTRE CLIENTES Y SERVICE WORKER
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
