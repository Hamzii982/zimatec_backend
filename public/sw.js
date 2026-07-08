const CACHE_NAME = 'zimatech-pwa-cache-v5';
const OFFLINE_URL = '/offline.html';

const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    '/images/zimmermann-logo-192.png',
    '/images/zimmermann-logo-512.png',
    '/images/logo-team-zimmermann.png',
    '/bootstrap/css/bootstrap.min.css',
    '/bootstrap/js/bootstrap.bundle.min.js',
    '/bootstrap/icons/bootstrap-icons.css',
    '/css/custom.css',
    '/css/admin.css',
    '/js/custom.js'
];

// Install Event: Pre-cache core assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Pre-caching offline assets');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event: Clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: Implement caching strategies
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    const url = new URL(event.request.url);

    // ✅ Cache-first ONLY for static assets
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|otf|eot)$/) ||
        url.pathname.includes('/bootstrap/')
    ) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) return cachedResponse;
                return fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseToCache));
                    }
                    return networkResponse;
                }).catch(() => {});
            })
        );
        return;
    }

    // 🔥 HTML pages — NEVER cache, always network, fallback to offline page only
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Everything else — network only, no caching
    event.respondWith(fetch(event.request));
});

self.addEventListener('push', function (event) {
    console.log('[SW] Push event fired');
    
    if (!event.data) {
        console.log('[SW] No data in push event');
        // Show notification anyway so we know SW is receiving
        event.waitUntil(
            self.registration.showNotification('ZiMaTec', {
                body: 'Du hast neue Benachrichtigungen. Bitte öffne die App, um sie zu sehen.',
            })
        );
        return;
    }

    console.log('[SW] Raw push data:', event.data.text());

    let data;
    try {
        data = event.data.json();
        console.log('[SW] Parsed push data:', data);
    } catch (e) {
        console.log('[SW] JSON parse failed:', e);
        event.waitUntil(
            self.registration.showNotification('ZiMaTec', {
                body: event.data.text(),
            })
        );
        return;
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'ZiMaTec', {
            body:               data.message,
            icon:               '/images/zimmermann-logo-192.png',
            badge:              '/images/zimmermann-logo-192.png',
            data:               { url: data.url },
            requireInteraction: true,
        }).then(() => console.log('[SW] Notification shown'))
            .catch(e => console.log('[SW] showNotification failed:', e))
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});