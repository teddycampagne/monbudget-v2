/**
 * Service Worker - MonBudget PWA
 * Gestion du cache et mode offline
 * 
 * @version 2.1.0
 */

const CACHE_NAME = 'monbudget-v2.1.0';
const OFFLINE_URL = '/monbudgetV2/offline.html';

// Assets à mettre en cache lors de l'installation
const STATIC_CACHE = [
    '/monbudgetV2/',
    '/monbudgetV2/offline.html',
    '/monbudgetV2/assets/css/app.css',
    '/monbudgetV2/assets/js/app.js',
    '/monbudgetV2/assets/icons/icon-192x192.png',
    '/monbudgetV2/assets/icons/icon-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installation...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Cache ouvert');
                return cache.addAll(STATIC_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activation...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Suppression ancien cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Stratégie de cache : Network First, fallback sur Cache
self.addEventListener('fetch', (event) => {
    // Ignorer les requêtes non-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignorer les requêtes vers d'autres domaines (sauf CDN)
    const url = new URL(event.request.url);
    if (url.origin !== location.origin && !url.host.includes('cdn.jsdelivr.net')) {
        return;
    }

    event.respondWith(
        // Essayer le réseau d'abord
        fetch(event.request)
            .then((response) => {
                // Si réponse OK, cloner et mettre en cache
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                
                return response;
            })
            .catch(() => {
                // Si échec réseau, chercher dans le cache
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        
                        // Si page HTML et pas en cache, retourner offline.html
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match(OFFLINE_URL);
                        }
                        
                        // Sinon, retourner une erreur
                        return new Response('Offline - Ressource non disponible', {
                            status: 503,
                            statusText: 'Service Unavailable'
                        });
                    });
            })
    );
});

// Écouter les messages du client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.delete(CACHE_NAME).then(() => {
                return self.clients.matchAll().then((clients) => {
                    clients.forEach((client) => client.postMessage({ type: 'CACHE_CLEARED' }));
                });
            })
        );
    }
});
