importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js');

// Конфигурация Firebase
firebase.initializeApp({
    apiKey: "AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",
    authDomain: "dlk-diz.firebaseapp.com",
    projectId: "dlk-diz",
    storageBucket: "dlk-diz.firebasestorage.app",
    messagingSenderId: "209164982906",
    appId: "1:209164982906:web:0836fbb02e7effd80679c3"
});

const messaging = firebase.messaging();

// Обработка фоновых сообщений
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Получено фоновое сообщение ', payload);
    
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/path/to/icon.png'
    };
    
    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Обработка кликов по уведомлению
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification click Received.');
    
    event.notification.close();
    
    // При клике на уведомление перенаправляем на страницу чатов
    event.waitUntil(
        clients.openWindow('/chats')
    );
});
