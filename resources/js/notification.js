import { initializeApp } from 'firebase/app';
import { getMessaging, onMessage, getToken } from 'firebase/messaging';

const firebaseConfig = {
    apiKey: "AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",
    authDomain: "dlk-diz.firebaseapp.com",
    projectId: "dlk-diz",
    storageBucket: "dlk-diz.firebasestorage.app",
    messagingSenderId: "209164982906",
    appId: "1:209164982906:web:0836fbb02e7effd80679c3"
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// Проверка разрешений на уведомления
Notification.requestPermission().then((permission) => {
    if (permission === 'granted') {
        getToken(messaging, { vapidKey: 'BLf08mEO3lePyBvZCwTzaSNX9R981qwESUblCemdDVZUT_cs4G3GD2YY38CN8ELIcPmgVRZ92G7ePzY187d4Dh4' })
            .then((currentToken) => {
                if (currentToken) {
                    console.log('FCM Token:', currentToken);
                    // Отправка токена на сервер для хранения
                    fetch('/firebase/update-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ token: currentToken })
                    });
                } else {
                    console.warn('Не удалось получить токен.');
                }
            }).catch((err) => {
                console.error('Ошибка получения токена:', err);
            });
    } else {
        console.warn('Разрешение на уведомления не предоставлено.');
    }
});

export function showChatNotification(title, body, data = {}) {
    console.log('Уведомление:', title, body);
    new Notification(title, {
        body: body,
        icon: '/firebase-logo.png',
        data: data
    });
}

export function checkForNewMessages() {
    onMessage(messaging, (payload) => {
        console.log('Message received. ', payload);
        showChatNotification(payload.notification.title, payload.notification.body, payload.data);
    });
}
