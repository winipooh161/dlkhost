import { initializeApp } from "firebase/app";
import { getMessaging, onMessage } from "firebase/messaging";

document.addEventListener('DOMContentLoaded', () => {
    const currentUserId = window.Laravel.user.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const pinImgUrl = window.pinImgUrl;
    const unpinImgUrl = window.unpinImgUrl;
    const deleteImgUrl = window.deleteImgUrl;
    let currentChatId = null;
    let currentChatType = null;
    let loadedMessageIds = new Set();
    let pinnedOnly = false;

    function initializeEmojiPicker(textarea) {
        const container = textarea.parentElement;
        const emojiButton = document.createElement('button');
        const emojiPicker = document.createElement('div');
        emojiButton.textContent = "😉";
        emojiButton.type = "button";
        emojiButton.classList.add('emoji-button');
        emojiPicker.classList.add('emoji-picker');
        emojiPicker.style.position = 'absolute';
        emojiPicker.style.bottom = '50px';
        emojiPicker.style.left = '10px';
        const emojis = [
            "😀","😁","😂","🤣","😃","😄","😅","😆","😉","😊","😍","😘","😜","😎","😭","😡",
            "😇","😈","🙃","🤔","😥","😓","🤩","🥳","🤯","🤬","🤡","👻","💀","👽","🤖","🎃",
            "🐱","🐶","🐭","🐹","🐰","🦊","🐻","🐼","🦁","🐮","🐷","🐸","🐵","🐔","🐧","🐦",
            "🌹","🌻","🌺","🌷","🌼","🍎","🍓","🍒","🍇","🍉","🍋","🍊","🍌","🥝","🍍","🥭"
        ];
        let emojiHTML = '';
        emojis.forEach(emoji => { emojiHTML += `<span class="emoji-item">${emoji}</span>`; });
        emojiPicker.innerHTML = emojiHTML;
        emojiPicker.addEventListener('click', (e) => {
            if (e.target.classList.contains('emoji-item')) {
                const emoji = e.target.textContent;
                const cursorPos = textarea.selectionStart;
                const textBefore = textarea.value.substring(0, cursorPos);
                const textAfter = textarea.value.substring(cursorPos);
                textarea.value = textBefore + emoji + textAfter;
                const newPos = cursorPos + emoji.length;
                textarea.selectionStart = newPos;
                textarea.selectionEnd = newPos;
                textarea.focus();
            }
        });
        container.appendChild(emojiButton);
        container.appendChild(emojiPicker);
        emojiPicker.style.display = "none";
        emojiButton.addEventListener('click', (event) => {
            event.stopPropagation();
            emojiPicker.style.display = (emojiPicker.style.display === "none") ? "flex" : "none";
        });
        document.addEventListener('click', (event) => {
            if (!emojiPicker.contains(event.target) && !emojiButton.contains(event.target)) {
                emojiPicker.style.display = "none";
            }
        });
    }

    function showChatList() {
        document.querySelector('.user-list').classList.add('active');
        document.querySelector('.chat-box').classList.remove('active');
    }

    function showChatBox() {
        document.querySelector('.user-list').classList.remove('active');
        document.querySelector('.chat-box').classList.add('active');
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function scrollToBottom() {
        const chatMessagesContainer = document.getElementById('chat-messages');
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    }

    function filterMessages() {
        document.querySelectorAll('#chat-messages ul li').forEach(li => {
            li.style.display = pinnedOnly ? (li.classList.contains('pinned') ? '' : 'none') : '';
        });
    }

    function renderMessages(messages, currentUserId) {
        const chatMessagesContainer = document.getElementById('chat-messages');
        const chatMessagesList = chatMessagesContainer.querySelector('ul');
        let html = '';
        messages.forEach(message => {
            if (!loadedMessageIds.has(message.id)) {
                if (message.message_type === 'notification' || message.is_system) {
                    // Системные уведомления отображаются без возможности взаимодействия
                    html += `
                        <li class="system-notification" data-id="${message.id}">
                            ${message.message}
                            <span class="message-time">${formatTime(message.created_at)}</span>
                        </li>
                    `;
                } else {
                    const isMyMessage = (message.sender_id === currentUserId);
                    const liClass = message.message_type === 'notification' 
                        ? 'notification-message' 
                        : (isMyMessage ? 'my-message' : 'other-message');
                    const pinnedClass = message.is_pinned ? 'pinned' : '';
                    let readStatus = '';
                    if (isMyMessage && message.is_read) {
                        readStatus = '<span class="read-status">✓✓</span>';
                    }
                    let contentHtml = '';
                    if (message.message && message.message.trim() !== '') {
                        if (message.message_type === 'notification') {
                            contentHtml += message.message; // Вставляем HTML для уведомлений
                        } else {
                            contentHtml += `<div>${escapeHtml(message.message)}</div>`;
                        }
                    }
                    
                    // Проверяем, существуют ли вложения и являются ли они массивом
                    if (message.attachments && Array.isArray(message.attachments) && message.attachments.length > 0) {
                        message.attachments.forEach(attachment => {
                            if (attachment && attachment.mime && attachment.mime.startsWith('image/')) {
                                contentHtml += `<div><img src="${attachment.url}" alt="Image" style="max-width:100%; border-radius:4px;"></div>`;
                            } else if (attachment && attachment.url) {
                                contentHtml += `<div><a href="${attachment.url}" target="_blank">${escapeHtml(attachment.original_file_name || 'Файл')}</a></div>`;
                            }
                        });
                    }
                    
                    if(contentHtml.trim() === ''){
                        contentHtml = `<div style="color:#888;">[Пустое сообщение]</div>`;
                    }
                    let actionsHtml = '';
                    if (isMyMessage) {
                        actionsHtml = `
                            <div class="message-controls">
                                <button class="delete-message" data-id="${message.id}"><img src="${deleteImgUrl}" alt="Удалить"></button>
                                ${message.is_pinned 
                                    ? `<button class="unpin-message" data-id="${message.id}"><img src="${unpinImgUrl}" alt="Открепить"></button>`
                                    : `<button class="pin-message" data-id="${message.id}"><img src="${pinImgUrl}" alt="Закрепить"></button>`
                                }
                            </div>
                        `;
                    }
                    html += `
                        <li class="${liClass} ${pinnedClass}" data-id="${message.id}">
                            <div><strong>${isMyMessage ? 'Вы' : escapeHtml(message.sender_name || 'Неизвестно')}</strong></div>
                            ${contentHtml}
                            ${actionsHtml}
                            <span class="message-time">${formatTime(message.created_at)}</span>
                            ${readStatus}
                        </li>
                    `;
                }
                loadedMessageIds.add(message.id);
            }
        });
        if (html) {
            chatMessagesList.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
            attachMessageActionListeners();
            filterMessages();
        }
    }

    function attachMessageActionListeners() {
        document.querySelectorAll('.delete-message').forEach(button => {
            button.onclick = function() {
                const messageId = this.getAttribute('data-id');
                if (confirm('Удалить сообщение?')) {
                    fetch(`/chats/${currentChatType}/${currentChatId}/messages/${messageId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('li').remove();
                        } else {
                            alert(data.error || 'Ошибка удаления сообщения');
                        }
                    })
                    .catch(error => console.error('Ошибка:', error));
                }
            };
        });
        document.querySelectorAll('.pin-message').forEach(button => {
            button.onclick = function() {
                const messageId = this.getAttribute('data-id');
                fetch(`/chats/${currentChatType}/${currentChatId}/messages/${messageId}/pin`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = `<img src="${unpinImgUrl}" alt="Открепить">`;
                        this.classList.remove('pin-message');
                        this.classList.add('unpin-message');
                        let li = this.closest('li');
                        li.classList.add('pinned');
                        if (li && !li.querySelector('.pinned-label')) {
                            let span = document.createElement('span');
                            span.classList.add('pinned-label');
                            span.textContent = ' [Закреплено]';
                            li.querySelector('div').appendChild(span);
                        }
                        filterMessages();
                    } else {
                        alert(data.error || 'Ошибка закрепления сообщения');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
            };
        });
        document.querySelectorAll('.unpin-message').forEach(button => {
            button.onclick = function() {
                const messageId = this.getAttribute('data-id');
                fetch(`/chats/${currentChatType}/${currentChatId}/messages/${messageId}/unpin`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = `<img src="${pinImgUrl}" alt="Закрепить">`;
                        this.classList.remove('unpin-message');
                        this.classList.add('pin-message');
                        let li = this.closest('li');
                        li.classList.remove('pinned');
                        let pinnedLabel = li.querySelector('.pinned-label');
                        if (pinnedLabel) { pinnedLabel.remove(); }
                        filterMessages();
                    } else { alert(data.error || 'Ошибка открепления сообщения'); }
                })
                .catch(error => console.error('Ошибка:', error));
            };
        });
    }

    function loadMessages(chatId, chatType) {
        currentChatId = chatId;
        currentChatType = chatType;
        const chatMessagesContainer = document.getElementById('chat-messages');
        const chatMessagesList = chatMessagesContainer.querySelector('ul');
        chatMessagesList.innerHTML = '';
        loadedMessageIds.clear();
        const chatItem = document.querySelector(`[data-chat-id="${chatId}"][data-chat-type="${chatType}"] h5`);
        const chatHeader = document.getElementById('chat-header');
        chatHeader.textContent = chatItem ? chatItem.textContent : 'Выберите чат для общения';

        fetch(`/chats/${chatType}/${chatId}/messages`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                renderMessages(data.messages, currentUserId);
                markMessagesAsRead(chatId, chatType);
                subscribeToChat(chatId, chatType);
            })
            .catch(error => {
                console.error('Ошибка загрузки сообщений:', error);
            });
    }

    function sendMessage() {
        if (!currentChatId || (!chatMessageInput.value.trim() && !document.querySelector('.file-input').files[0])) return;
        const message = chatMessageInput.value.trim();
        const fileInput = document.querySelector('.file-input');
        const files = fileInput.files;
        let formData = new FormData();
        formData.append('message', message);
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        fetch(`/chats/${currentChatType}/${currentChatId}/messages`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData,
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => { throw new Error(text); });
            }
            return r.json();
        })
        .then(data => {
            if (data.message) {
                renderMessages([data.message], data.message.sender_id);
                chatMessageInput.value = '';
                document.querySelector('.file-input').value = '';
            }
        })
        .catch(e => console.error('Ошибка при отправке сообщения:', e));
    }

    function markMessagesAsRead(chatId, chatType) {
        fetch(`/chats/${chatType}/${chatId}/mark-read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        }).catch(e => console.error('Ошибка при пометке сообщений как прочитанных:', e));
    }

    function subscribeToChat(chatId, chatType) {
        // Удаление Pusher и Echo
        // if(window.Echo) {
        //     window.Echo.private(`chat.${chatType}.${chatId}`)
        //         .listen('MessageSent', (e) => {
        //             renderMessages([e.message], e.message.sender_id);
        //             markMessagesAsRead(chatId, chatType);
        //         });
        // }
    }

    function checkForNewMessages() {
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
            if (data.unread_counts) {
                data.unread_counts.forEach(chat => {
                    if (chat.unread_count > 0) {
                        console.log(`У вас ${chat.unread_count} новых сообщений в чате ${chat.name}`);
                    }
                });
            }
        })
        .catch(e => console.error('Ошибка при проверке новых сообщений:', e));
    }

    setInterval(checkForNewMessages, 5000); // Проверка новых сообщений каждые 5 секунд

    const chatList = document.getElementById('chat-list');
    if (chatList) {
        chatList.addEventListener('click', (event) => {
            const chatElement = event.target.closest('li');
            if (!chatElement) return;
            const chatId = chatElement.getAttribute('data-chat-id');
            const chatType = chatElement.getAttribute('data-chat-type');
            if (currentChatId === chatId && currentChatType === chatType) return;
            loadMessages(chatId, chatType);
        });
    }

    const searchInput = document.getElementById('search-chats');
    const searchResults = document.getElementById('search-results');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim().toLowerCase();
            if (query === '') {
                searchResults.style.display = 'none';
                Array.from(chatList.children).forEach(chat => { chat.style.display = 'flex'; });
            } else {
                Array.from(chatList.children).forEach(chat => {
                    const chatName = chat.querySelector('h5').textContent.toLowerCase();
                    chat.style.display = chatName.includes(query) ? 'flex' : 'none';
                });
                fetch(`/chats/search`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ query: query })
                })
                .then(response => response.json())
                .then(data => {
                    let resultsHTML = '';
                    if (data.chats && data.chats.length > 0) {
                        resultsHTML += '<h5>Чаты</h5><ul>';
                        data.chats.forEach(chat => {
                            resultsHTML += `<li data-chat-id="${chat.id}" data-chat-type="${chat.type}">${chat.name}</li>`;
                        });
                        resultsHTML += '</ul>';
                    }
                    if (data.messages && data.messages.length > 0) {
                        resultsHTML += '<h5>Сообщения</h5><ul>';
                        data.messages.forEach(msg => {
                            let chatId = msg.chat_id;
                            let chatType = "group";
                            if (!chatId) {
                                chatType = "personal";
                                chatId = (msg.sender_id == currentUserId ? msg.receiver_id : msg.sender_id);
                            }
                            resultsHTML += `<li data-chat-id="${chatId}" data-chat-type="${chatType}" data-message-id="${msg.id}">
                                <strong>${msg.sender_name}:</strong> ${msg.message.substring(0, 50)}...
                                <br><small>${formatTime(msg.created_at)}</small>
                            </li>`;
                        });
                        resultsHTML += '</ul>';
                    }
                    searchResults.innerHTML = resultsHTML;
                    searchResults.style.display = resultsHTML.trim() === '' ? 'none' : 'block';
                    Array.from(searchResults.querySelectorAll('li')).forEach(item => {
                        item.addEventListener('click', function() {
                            const chatId = this.getAttribute('data-chat-id');
                            const chatType = this.getAttribute('data-chat-type');
                            const messageId = this.getAttribute('data-message-id');
                            loadMessages(chatId, chatType);
                            searchInput.value = '';
                            searchResults.style.display = 'none';
                            if (messageId) {
                                setTimeout(() => {
                                    // Здесь можно реализовать выделение сообщения
                                }, 1000);
                            }
                        });
                    });
                })
                .catch(e => console.error('Ошибка поиска:', e));
            }
        });
    }

    function attachFileListener() {
        const attachButton = document.querySelector('.attach-file');
        const fileInput = document.querySelector('.file-input');
        if (attachButton && fileInput) {
            attachButton.addEventListener('click', () => { fileInput.click(); });
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) { sendMessage(); }
            });
        }
    }

    if (document.readyState !== 'loading') { attachFileListener(); }
    else { document.addEventListener('DOMContentLoaded', attachFileListener); }

    const sendMessageButton = document.getElementById('send-message');
    const chatMessageInput = document.getElementById('chat-message');
    if (sendMessageButton) {
        sendMessageButton.addEventListener('click', sendMessage);
    }
    if (chatMessageInput) {
        chatMessageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
    }

    initializeEmojiPicker(chatMessageInput);

    document.addEventListener('DOMContentLoaded', () => {
        const firstChat = chatList ? chatList.querySelector('li') : null;
        if (firstChat) firstChat.click();
    });

    setInterval(() => {
        if (currentChatId && currentChatType) {
            const chatMessagesContainer = document.getElementById('chat-messages');
            const chatMessagesList = chatMessagesContainer.querySelector('ul');
            fetch(`/chats/${currentChatType}/${currentChatId}/new-messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    last_message_id: chatMessagesList.lastElementChild ? parseInt(chatMessagesList.lastElementChild.getAttribute('data-id')) : 0
                }),
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok');
                }
                return r.json();
            })
            .then(data => {
                if (data.messages) {
                    renderMessages(data.messages, data.current_user_id);
                    markMessagesAsRead(currentChatId, currentChatType);
                }
            })
            .catch(e => {
                console.error('Ошибка при получении новых сообщений:', e);
            });
        }
    }, 1000); // Проверка новых сообщений каждую секунду

    // Добавляем обработчик для прокрутки к сообщению при клике на ссылку
    document.addEventListener('click', function(e) {
        if (e.target.matches('.notification-message a[data-message-id]')) {
            e.preventDefault();
            const messageId = e.target.dataset.messageId;
            const targetMessage = document.querySelector(`li[data-id="${messageId}"]`);
            if (targetMessage) {
                targetMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetMessage.style.backgroundColor = '#007ab6'; // Изменено с #fff3cd
                setTimeout(() => {
                    targetMessage.style.backgroundColor = '';
                }, 2000);
            }
        }
    });

    const togglePinnedButton = document.getElementById('toggle-pinned');
    if (togglePinnedButton) {
        togglePinnedButton.addEventListener('click', () => {
            pinnedOnly = !pinnedOnly;
            togglePinnedButton.textContent = pinnedOnly ? 'Показать все сообщения' : 'Показать только закрепленные';
            filterMessages();
        });
    }
});
