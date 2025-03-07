import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

// Глобальные переменные для Firebase
let firebaseApp = null;
let messaging = null;
let tokenSaved = false;

// Инициализация Firebase при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    // Проверка, что Firebase еще не инициализирован
    if (firebaseApp) return;

    console.log('Инициализация Firebase...');
    
    const firebaseConfig = {
        apiKey: "AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",
        authDomain: "dlk-diz.firebaseapp.com",
        projectId: "dlk-diz",
        storageBucket: "dlk-diz.firebasestorage.app",
        messagingSenderId: "209164982906",
        appId: "1:209164982906:web:0836fbb02e7effd80679c3"
    };

    // Инициализация Firebase - только один раз
    firebaseApp = initializeApp(firebaseConfig);
    messaging = getMessaging(firebaseApp);

    // Настройка обработчика для входящих сообщений в активном окне
    setupMessageHandler();
    
    // Инициализация службы уведомлений только при соответствующем разрешении
    initializeNotifications();
});

// Настройка обработки входящих сообщений 
function setupMessageHandler() {
    if (!messaging) return;

    // Обработка сообщений, полученных, когда приложение находится в фокусе
    onMessage(messaging, (payload) => {
        console.log('Получено сообщение в активном окне:', payload);
        
        // Показываем уведомление, даже если приложение в фокусе
        showBrowserNotification(
            payload.notification?.title || 'Новое сообщение', 
            payload.notification?.body || '',
            payload.data
        );
        
        // Отображаем встроенное уведомление на странице
        showModalNotification(
            payload.notification?.title || 'Новое сообщение', 
            payload.notification?.body || '',
            payload.data?.chatId,
            payload.data?.chatType
        );
    });
}

// Единая точка инициализации для уведомлений
function initializeNotifications() {
    // Проверяем поддержку уведомлений браузером
    if (!('Notification' in window)) {
        console.log('Этот браузер не поддерживает уведомления');
        return;
    }

    // Проверяем текущие разрешения
    const notificationPermission = Notification.permission;
    console.log('Текущее разрешение для уведомлений:', notificationPermission);

    // Если разрешение уже выдано - регистрируем токен
    if (notificationPermission === 'granted') {
        registerServiceWorker();
    } 
    // Если разрешение не запрошено - показываем баннер
    else if (notificationPermission !== 'denied') {
        showNotificationPermissionBanner();
    }
}

// Показываем баннер для запроса разрешений на уведомления
function showNotificationPermissionBanner() {
    // Проверяем, не отображается ли уже баннер
    if (document.querySelector('.notification-permission-banner')) {
        return;
    }
    
    const notificationBanner = document.createElement('div');
    notificationBanner.className = 'notification-permission-banner';
    notificationBanner.innerHTML = `
        <div class="notification-banner-content">
            <p>Разрешите уведомления, чтобы быть в курсе новых сообщений</p>
            <button id="allow-notifications" class="btn btn-primary">Разрешить</button>
            <button id="close-notification-banner" class="btn btn-secondary">Позже</button>
        </div>
    `;
    document.body.appendChild(notificationBanner);

    document.getElementById('allow-notifications').addEventListener('click', () => {
        Notification.requestPermission().then(permission => {
            console.log('Пользователь ' + (permission === 'granted' ? 'разрешил' : 'не разрешил') + ' уведомления');
            notificationBanner.remove();
            
            if (permission === 'granted') {
                registerServiceWorker();
            }
        });
    });

    document.getElementById('close-notification-banner').addEventListener('click', () => {
        notificationBanner.remove();
    });
}

// Регистрация Service Worker и получение токена
function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.error('Service Worker не поддерживается в этом браузере');
        return;
    }
    
    // Проверка, что токен еще не сохранен
    if (tokenSaved) {
        console.log('Токен уже был сохранен ранее');
        return;
    }

    console.log('Регистрация Service Worker...');
    
    navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then((registration) => {
            console.log('Service Worker зарегистрирован:', registration.scope);
            
            // Получаем токен FCM с привязкой к сервис-воркеру
            return getToken(messaging, { 
                vapidKey: 'BLf08mEO3lePyBvZCwTzaSNX9R981qwESUblCemdDVZUT_cs4G3GD2YY38CN8ELIcPmgVRZ92G7ePzY187d4Dh4',
                serviceWorkerRegistration: registration 
            });
        })
        .then((token) => {
            if (token) {
                console.log('Получен FCM токен:', token.substring(0, 20) + '...');
                saveTokenToServer(token);
                tokenSaved = true;
            } else {
                console.error('Не удалось получить токен FCM');
                
                // Диагностика проблемы
                if (Notification.permission !== 'granted') {
                    console.error('Причина: не получено разрешение на уведомления');
                }
            }
        })
        .catch((err) => {
            console.error('Ошибка при регистрации сервис-воркера или получении токена:', err);
        });
}

// Сохранение токена на сервере
function saveTokenToServer(token) {
    // Предотвращаем повторную отправку токена
    if (tokenSaved) return;
    
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
            tokenSaved = true;
        } else {
            console.error('Ошибка при сохранении токена на сервере');
        }
    })
    .catch(err => {
        console.error('Ошибка при отправке токена:', err);
    });
}

// Функция для показа браузерного уведомления
function showBrowserNotification(title, body, data = {}) {
    // Проверяем поддержку уведомлений браузером
    if (!('Notification' in window)) {
        console.log('Этот браузер не поддерживает уведомления');
        return;
    }

    // Проверяем разрешение на отправку уведомлений
    if (Notification.permission === 'granted') {
        try {
            // Создаем и показываем уведомление
            const notification = new Notification(title, {
                body: body,
                icon: '/path/to/icon.png', // Замените на путь к вашей иконке
                tag: `chat-${data?.chatId || 'general'}`, // Группировка уведомлений
                data: data,
                requireInteraction: true, // Уведомление не исчезает автоматически
                renotify: true // Уведомлять даже если предыдущее уведомление не закрыто
            });

            notification.onclick = function() {
                window.focus();
                // Если есть ID и тип чата, переключаем на нужный чат
                if (data?.chatId && data?.chatType) {
                    if (window.location.pathname.includes('/chats')) {
                        const chatElement = document.querySelector(
                            `[data-chat-id="${data.chatId}"][data-chat-type="${data.chatType}"]`
                        );
                        if (chatElement) {
                            chatElement.click();
                        }
                    } else {
                        // Если не на странице чатов, перенаправляем
                        window.location.href = `/chats?chatId=${data.chatId}&chatType=${data.chatType}`;
                    }
                }
                notification.close();
            };
            
            // Обработка ошибок при создании уведомления
            notification.onerror = function(e) {
                console.error('Ошибка показа уведомления:', e);
            };
            
            return notification;
        } catch (error) {
            console.error('Ошибка при создании браузерного уведомления:', error);
        }
    }
}

// Функция для отображения стилизованного уведомления на странице
function showModalNotification(title, body, chatId, chatType) {
    // Создаем элемент уведомления
    const modal = document.createElement('div');
    modal.classList.add('firebase-notification');
    
    // HTML содержимое уведомления
    modal.innerHTML = `
        <div class="notification-content">
            <div class="notification-header">
                <h4>${title}</h4>
                <button class="close-notification">&times;</button>
            </div>
            <div class="notification-body">
                <p>${body}</p>
            </div>
            <div class="notification-footer">
                <button class="view-message-btn">Перейти к сообщению</button>
            </div>
        </div>
    `;
    
    // Добавляем на страницу
    document.body.appendChild(modal);
    
    // Добавляем стили, если они еще не добавлены
    if (!document.getElementById('firebase-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'firebase-notification-styles';
        style.textContent = `
            .firebase-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                width: 320px;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                overflow: hidden;
                border-left: 4px solid #4285F4;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .notification-content {
                padding: 15px;
            }
            
            .notification-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            
            .notification-header h4 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }
            
            .close-notification {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #999;
                padding: 0;
            }
            
            .notification-body p {
                margin: 0 0 15px 0;
                color: #555;
                font-size: 14px;
            }
            
            .view-message-btn {
                background-color: #4285F4;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                transition: background-color 0.2s;
            }
            
            .view-message-btn:hover {
                background-color: #3367D6;
            }
            
            .notification-permission-banner {
                position: fixed;
                top: 10px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                z-index: 1001;
                width: 90%;
                max-width: 500px;
            }
            .notification-permission-banner .notification-banner-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .notification-permission-banner p {
                margin: 0 0 10px 0;
                flex: 100%;
            }
            .notification-permission-banner .btn {
                margin-right: 10px;
                padding: 5px 15px;
                border-radius: 3px;
                cursor: pointer;
            }
            .btn-primary {
                background-color: #007bff;
                color: white;
                border: none;
            }
            .btn-secondary {
                background-color: #6c757d;
                color: white;
                border: none;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Обработчик закрытия уведомления
    const closeBtn = modal.querySelector('.close-notification');
    closeBtn.addEventListener('click', () => {
        modal.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => modal.remove(), 300);
    });
    
    // Обработчик кнопки "Перейти к сообщению"
    const viewBtn = modal.querySelector('.view-message-btn');
    viewBtn.addEventListener('click', () => {
        // Если мы уже на странице чатов
        if (window.location.pathname.includes('/chats')) {
            // Если есть ID и тип чата, переключаем на нужный чат
            if (chatId && chatType) {
                const chatElement = document.querySelector(`[data-chat-id="${chatId}"][data-chat-type="${chatType}"]`);
                if (chatElement) {
                    chatElement.click();
                }
            }
        } else {
            // Иначе перенаправляем на страницу чатов
            let url = '/chats';
            if (chatId && chatType) {
                url += `?chatId=${chatId}&chatType=${chatType}`;
            }
            window.location.href = url;
        }
        modal.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => modal.remove(), 300);
    });
    
    // Автоматическое закрытие через 8 секунд
    setTimeout(() => {
        if (document.body.contains(modal)) {
            modal.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => {
                if (document.body.contains(modal)) {
                    modal.remove();
                }
            }, 300);
        }
    }, 8000);
}

// Экспорт функций для возможности использования в других модулях
export {
    showBrowserNotification,
    showModalNotification
};
