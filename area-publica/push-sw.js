self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (_) {}
  const title = data.title || 'IPIKK';
  const options = {
    body: data.body || 'Nova atualização disponível.',
    icon: data.icon || '/area-publica/uploads/favicon.png',
    badge: data.badge || '/area-publica/uploads/favicon.png',
    image: data.image || undefined,
    data: { url: data.url || '/area-publica/noticias.php' }
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification?.data?.url || '/area-publica/noticias.php';
  event.waitUntil(clients.openWindow(url));
});