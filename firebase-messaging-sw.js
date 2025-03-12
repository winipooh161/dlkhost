importScripts('https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.0/firebase-messaging-compat.js');

// Инициализация Firebase с учетными данными
firebase.initializeApp({
    apiKey: "AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",
    authDomain: "dlk-diz.firebaseapp.com",
    projectId: "dlk-diz",
    storageBucket: "dlk-diz.firebasestorage.app",
    messagingSenderId: "209164982906",
    appId: "1:209164982906:web:0836fbb02e7effd80679c3"
});

const messaging = firebase.messaging();

// Отладочная информация при инициализации сервис-воркера
console.log('[firebase-messaging-sw.js] Сервис-воркер инициализирован');

// Обработчик фоновых уведомлений (когда страница не активна)
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Получено уведомление в фоне:', payload);

    // Извлекаем данные из payload
    const notificationTitle = payload.notification?.title || 'Новое сообщение';
    const notificationOptions = {
        body: payload.notification?.body || '',
        icon: '/storage/icon/notification-icon.png',
        badge: '/storage/icon/badge-icon.png',
        tag: `chat-${payload.data?.chatId || 'general'}`,
        data: payload.data || {},
        requireInteraction: true,
        renotify: true,
        vibrate: [200, 100, 200]
    };
    
    // Показываем уведомление
    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Обработчик клика по уведомлению
self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw.js] Клик по уведомлению', event);

    // Закрываем уведомление
    event.notification.close();

    // Получаем данные из уведомления
    const chatId = event.notification.data?.chatId;
    const chatType = event.notification.data?.chatType;
    
    // Формируем URL для открытия
    let url = '/chats';
    if (chatId && chatType) {
        url += `?chatId=${chatId}&chatType=${chatType}`;
    }
    
    // Открываем окно с указанным URL
    event.waitUntil(
        clients.matchAll({type: 'window'}).then(function(clientList) {
            // Если найдено существующее окно, фокусируем его
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf('/chats') !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            // Если нет существующего окна, открываем новое
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Событие при установке сервис-воркера
self.addEventListener('install', function(event) {
    console.log('[firebase-messaging-sw.js] Установка Service Worker');
    // Активируем сразу, не дожидаясь закрытия других вкладок
    event.waitUntil(self.skipWaiting());
});

// Событие при активации сервис-воркера
self.addEventListener('activate', function(event) {
    console.log('[firebase-messaging-sw.js] Активация Service Worker');
    // Захватываем контроль над всеми клиентами сразу
    event.waitUntil(self.clients.claim());
});

// Для отладки - выводим все события push
self.addEventListener('push', function(event) {
    console.log('[firebase-messaging-sw.js] Получено push событие:', event);
    // Если данных нет в event, не показываем уведомление
    if (!event.data) return;

    try {
        const data = event.data.json();
        console.log('[firebase-messaging-sw.js] Данные push события:', data);
        
        // Показываем уведомление из push-данных, если оно не пришло через FCM
        if (!data.notification && data.data) {
            const notificationTitle = data.data.title || 'Новое сообщение';
            const notificationOptions = {
                body: data.data.body || '',
                icon: '/path/to/icon.png',
                badge: '/path/to/badge.png',
                tag: `chat-${data.data.chatId || 'general'}`,
                data: data.data,
                requireInteraction: true,
                renotify: true,
                vibrate: [200, 100, 200]
            };
            
            event.waitUntil(
                self.registration.showNotification(notificationTitle, notificationOptions)
            );
        }
    } catch (e) {
        console.error('[firebase-messaging-sw.js] Ошибка обработки push события:', e);
    }
});
