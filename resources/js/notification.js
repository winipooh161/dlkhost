import { showBrowserNotification, showModalNotification } from './firebase-init';

document.addEventListener('DOMContentLoaded', () => {
    if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
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
            });
        });

        document.getElementById('close-notification-banner').addEventListener('click', () => {
            notificationBanner.remove();
        });
    }
});

export function showChatNotification(message, chatId = null, chatType = null) {
    showModalNotification('Новое сообщение', message, chatId, chatType);
}

export function checkForNewMessages(csrfToken, notifiedChats) {
    fetch('/chats/unread-counts', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.unread_counts && data.unread_counts.length > 0) {
            data.unread_counts.forEach(chat => {
                if (chat.unread_count > 0 && !notifiedChats.has(chat.id)) {
                    const message = `У вас ${chat.unread_count} новых сообщений в чате ${chat.name}`;
                    showBrowserNotification('Новое сообщение', message, { chatId: chat.id, chatType: chat.type });
                    notifiedChats.add(chat.id);
                }
            });
        }
    })
    .catch(e => console.error('Ошибка при проверке новых сообщений:', e));
}
