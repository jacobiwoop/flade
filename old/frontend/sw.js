// frontend/sw.js - Service Worker
const CACHE_NAME = "floade-chat-v1"
const urlsToCache = [
  "/",
  "/css/auth.css",
  "/css/dashboard.css",
  "/js/auth.js",
  "/js/dashboard.js",
  "/pages/login.html",
  "/pages/register.html",
  "/pages/dashboard.html",
  "/icons/icon-72x72.png",
  "/icons/icon-96x96.png",
  "/icons/icon-128x128.png",
  "/icons/floade-high-resolution-logo.png",
]

// Installation du service worker
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache)
    }),
  )
})

// Activation du service worker
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName)
          }
        }),
      )
    }),
  )
})

// Interception des requêtes
self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      // Retourner la réponse du cache si elle existe
      if (response) {
        return response
      }
      return fetch(event.request)
    }),
  )
})

// Gestion des notifications push
self.addEventListener("push", (event) => {
  if (event.data) {
    const data = event.data.json()
    const options = {
      body: data.body,
      icon: "/icons/icon-96x96.png",
      badge: "/icons/icon-72x72.png",
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey,
      },
    }

    event.waitUntil(self.registration.showNotification(data.title, options))
  }
})

// Gestion des clics sur les notifications
self.addEventListener("notificationclick", (event) => {
  event.notification.close()

  event.waitUntil(clients.openWindow("/pages/dashboard.html"))
})
