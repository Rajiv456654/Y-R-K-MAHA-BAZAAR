// Service Worker for Y R K MAHA BAZAAR PWA
const CACHE_NAME = 'yrk-bazaar-v1.0.0';
const urlsToCache = [
  '/Y R K MAHA BAZAAR/',
  '/Y R K MAHA BAZAAR/index.php',
  '/Y R K MAHA BAZAAR/products/product-list.php',
  '/Y R K MAHA BAZAAR/cart/cart.php',
  '/Y R K MAHA BAZAAR/login.php',
  '/Y R K MAHA BAZAAR/register.php',
  '/Y R K MAHA BAZAAR/contact.php',
  '/Y R K MAHA BAZAAR/about.php',
  '/Y R K MAHA BAZAAR/assets/css/style.css',
  '/Y R K MAHA BAZAAR/assets/js/script.js',
  '/Y R K MAHA BAZAAR/assets/images/default-product.jpg',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
];

// Install event
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.error('Cache installation failed:', error);
      })
  );
});

// Activate event
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - Network First strategy for dynamic content
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip requests with query parameters (likely dynamic)
  if (event.request.url.includes('?') || event.request.url.includes('add-to-cart') || 
      event.request.url.includes('remove-from-cart') || event.request.url.includes('update-quantity')) {
    return;
  }

  event.respondWith(
    // Try network first
    fetch(event.request)
      .then(response => {
        // If network request is successful, clone and cache the response
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseClone);
            });
        }
        return response;
      })
      .catch(() => {
        // If network fails, try to serve from cache
        return caches.match(event.request)
          .then(response => {
            if (response) {
              return response;
            }
            // If not in cache, return offline page for navigation requests
            if (event.request.mode === 'navigate') {
              return caches.match('/Y R K MAHA BAZAAR/offline.html');
            }
            // For other requests, return a generic offline response
            return new Response('Offline', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});

// Background sync for cart updates
self.addEventListener('sync', event => {
  if (event.tag === 'cart-sync') {
    event.waitUntil(syncCart());
  }
});

// Push notification handling
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New update from Y R K MAHA BAZAAR!',
    icon: '/Y R K MAHA BAZAAR/assets/images/icons/icon-192x192.png',
    badge: '/Y R K MAHA BAZAAR/assets/images/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Products',
        icon: '/Y R K MAHA BAZAAR/assets/images/icons/icon-96x96.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/Y R K MAHA BAZAAR/assets/images/icons/icon-96x96.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Y R K MAHA BAZAAR', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/Y R K MAHA BAZAAR/products/product-list.php')
    );
  } else if (event.action === 'close') {
    // Just close the notification
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.openWindow('/Y R K MAHA BAZAAR/')
    );
  }
});

// Helper function to sync cart data
async function syncCart() {
  try {
    // Get pending cart updates from IndexedDB
    const pendingUpdates = await getPendingCartUpdates();
    
    for (const update of pendingUpdates) {
      try {
        const response = await fetch('/Y R K MAHA BAZAAR/cart/sync-cart.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(update)
        });
        
        if (response.ok) {
          // Remove from pending updates
          await removePendingCartUpdate(update.id);
        }
      } catch (error) {
        console.error('Failed to sync cart update:', error);
      }
    }
  } catch (error) {
    console.error('Cart sync failed:', error);
  }
}

// Helper function to get pending cart updates (would integrate with IndexedDB)
async function getPendingCartUpdates() {
  // This would integrate with IndexedDB to get pending updates
  return [];
}

// Helper function to remove pending cart update
async function removePendingCartUpdate(id) {
  // This would remove the update from IndexedDB
  console.log('Removing pending cart update:', id);
}

// Message handling for communication with main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

console.log('Service Worker loaded successfully!');
