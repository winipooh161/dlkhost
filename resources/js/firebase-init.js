import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

document.addEventListener('DOMContentLoaded', () => {
    const firebaseConfig = {
        apiKey: "AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",
        authDomain: "dlk-diz.firebaseapp.com",
        projectId: "dlk-diz",
        storageBucket: "dlk-diz.firebasestorage.app",
        messagingSenderId: "209164982906",
        appId: "1:209164982906:web:0836fbb02e7effd80679c3"
    };

    // Инициализация Firebase
    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    // Запрос разрешения на уведомления и регистрация токена
    requestAndRegisterToken(messaging);

    // Обработка входящих сообщений в активном окне
    onMessage(messaging, (payload) => {
        console.log('Получено сообщение в активном окне:', payload);
        
        // Отображаем уведомление через Notifications API
        if (Notification.permission === 'granted') {
            const notificationTitle = payload.notification.title;
            const notificationOptions = {
                body: payload.notification.body,
                icon: '/path/to/icon.png',
                tag: 'notification-' + Date.now(), // Уникальный тег для каждого уведомления
                data: payload.data // Сохраняем данные для использования при клике
            };
            
            const notification = new Notification(notificationTitle, notificationOptions);
            
            // Обработка клика по уведомлению
            notification.onclick = function() {
                window.focus(); // Фокус на окне браузера
                notification.close();
                
                // Перенаправляем на нужную страницу
                const chatId = payload.data?.chatId;
                const chatType = payload.data?.chatType;
                
                if (chatId && chatType) {
                    window.location.href = `/chats?chatId=${chatId}&chatType=${chatType}`;
                } else {
                    window.location.href = '/chats';
                }
            };
        }
    });
});

function requestAndRegisterToken(messaging) {
    // Запрашиваем разрешение на отображение уведомлений
    if ('Notification' in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    // Получаем токен
                    registerServiceWorker(messaging);
                }
            });
        } else if (Notification.permission === 'granted') {
            // Уже есть разрешение, получаем токен
            registerServiceWorker(messaging);
        }
    }
}

function registerServiceWorker(messaging) {
    navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then((registration) => {
            // Получаем токен FCM с привязкой к сервис-воркеру
            return getToken(messaging, { 
                vapidKey: 'BLf08mEO3lePyBvZCwTzaSNX9R981qwESUblCemdDVZUT_cs4G3GD2YY38CN8ELIcPmgVRZ92G7ePzY187d4Dh4',
                serviceWorkerRegistration: registration 
            });
        })
        .then((token) => {
            if (token) {
                saveTokenToServer(token);
            } else {
                console.log('Не удалось получить токен FCM');
            }
        })
        .catch((err) => {
            console.error('Ошибка при регистрации сервис-воркера или получении токена:', err);
        });
}

function saveTokenToServer(token) {
    // Отправляем токен на сервер
    fetch('/firebase/update-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ token })
    })
    .then(response => {
        if (response.ok) {
            console.log('Токен успешно сохранен на сервере');
        } else {
            console.error('Ошибка при сохранении токена на сервере');
        }
    })
    .catch(err => {
        console.error('Ошибка при отправке токена:', err);
    });
}
