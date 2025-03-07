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
        icon: '/path/to/icon.png', // Замените на путь к вашей иконке
        badge: '/path/to/badge.png', // Опционально
        tag: `chat-${payload.data?.chatId || 'general'}`,
        data: payload.data || {},
        requireInteraction: true, // Уведомление не исчезает автоматически
        renotify: true, // Уведомлять даже если предыдущее уведомление не закрыто
        vibrate: [200, 100, 200] // Добавлен паттерн вибрации для мобильных устройств
    };

    // Показываем уведомление через service worker API
    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Обработка клика по уведомлению
self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw.js] Клик по уведомлению', event);

    // Закрываем уведомление при клике
    event.notification.close();

    // Получаем данные из уведомления
    const payload = event.notification.data;
    const chatId = payload?.chatId || event.notification.tag?.replace('chat-', '');
    const chatType = payload?.chatType || 'personal';

    // Формируем URL для перехода
    let url = '/chats';
    if (chatId && chatType) {
        url = `/chats?chatId=${chatId}&chatType=${chatType}`;
    }

    // Обрабатываем клик по уведомлению
    event.waitUntil(
        clients.matchAll({type: 'window', includeUncontrolled: true}).then(windowClients => {
            // Проверяем, есть ли уже открытые окна
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                // Если окно уже открыто, переходим к нему и фокусируемся
                if ('focus' in client) {
                    client.postMessage({
                        type: 'NOTIFICATION_CLICK',
                        chatId: chatId,
                        chatType: chatType
                    });
                    client.navigate(url);
                    return client.focus();
                }
            }
            // Если нет открытых окон, открываем новое
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
