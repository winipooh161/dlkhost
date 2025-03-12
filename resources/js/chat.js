import { initializeEmojiPicker } from './emoji-picker';
import { attachMessageActionListeners } from './message-actions';
import { formatTime, escapeHtml, scrollToBottom, filterMessages, renderMessages } from './chat-utils';
import { showChatNotification, checkForNewMessages } from './notification';

document.addEventListener('DOMContentLoaded', () => {
    // Удалены проверка и запрос разрешений на уведомления Firebase

    const currentUserId = window.Laravel.user.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const pinImgUrl = window.pinImgUrl;
    const unpinImgUrl = window.unpinImgUrl;
    const deleteImgUrl = window.deleteImgUrl;
    let currentChatId = null;
    let currentChatType = null;
    let loadedMessageIds = new Set();
    let pinnedOnly = false;
    let notifiedChats = new Set();

    function showChatBox() {
        document.querySelector('.user-list').classList.remove('active');
        document.querySelector('.chat-box').classList.add('active');
    }

    // Обновляем глобальную переменную lastLoadedMessageId при загрузке сообщений
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
                // Очищаем loadedMessageIds для нового чата
                loadedMessageIds.clear();
                
                // Обновляем lastLoadedMessageId на основе полученных сообщений
                if (data.messages && data.messages.length > 0) {
                    const lastMsg = data.messages[data.messages.length - 1];
                    window.lastLoadedMessageId = lastMsg.id;
                }
                
                renderMessages(data.messages, currentUserId, loadedMessageIds, csrfToken, currentChatType, currentChatId);
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
                renderMessages([data.message], data.message.sender_id, loadedMessageIds, csrfToken, currentChatType, currentChatId);
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
        // Функция оставлена пустой, так как Pusher и Echo были удалены в оригинале
    }

    // Изменяем функцию периодической проверки новых сообщений
    setInterval(() => {
        if (currentChatId && currentChatType) {
            const chatMessagesContainer = document.getElementById('chat-messages');
            if (!chatMessagesContainer) return;
            
            const chatMessagesList = chatMessagesContainer.querySelector('ul');
            if (!chatMessagesList) return;
            
            // Используем lastLoadedMessageId вместо поиска последнего элемента
            const lastMessageId = window.lastLoadedMessageId || 0;
            
            fetch(`/chats/${currentChatType}/${currentChatId}/new-messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    last_message_id: lastMessageId
                }),
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok');
                }
                return r.json();
            })
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    // Обновляем lastLoadedMessageId на основе новых сообщений
                    const lastMsg = data.messages[data.messages.length - 1];
                    window.lastLoadedMessageId = lastMsg.id;
                    
                    renderMessages(data.messages, data.current_user_id, loadedMessageIds, csrfToken, currentChatType, currentChatId);
                    markMessagesAsRead(currentChatId, currentChatType);
                }
            })
            .catch(e => {
                console.error('Ошибка при получении новых сообщений:', e);
            });
        }
    }, 1000); // Проверка новых сообщений каждую секунду

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

    const togglePinnedButton = document.getElementById('toggle-pinned');
    if (togglePinnedButton) {
        togglePinnedButton.addEventListener('click', () => {
            pinnedOnly = !pinnedOnly;
            togglePinnedButton.textContent = pinnedOnly ? 'Показать все сообщения' : 'Показать только закрепленные';
            filterMessages(pinnedOnly);  // Передаем параметр pinnedOnly
        });
    }

    checkForNewMessages();

});
