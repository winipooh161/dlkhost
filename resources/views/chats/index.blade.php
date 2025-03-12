<body>
    <!-- Передаем данные пользователя -->
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js"></script>
    <script>
        window.Laravel = {
            user: @json([
                'id' => auth()->id(),
                'name' => auth()->user()->name ?? 'Anon',
            ]),
        };

        window.pinImgUrl = "{{ asset('storage/icon/pin.svg') }}";
        window.unpinImgUrl = "{{ asset('storage/icon/unpin.svg') }}";
        window.deleteImgUrl = "{{ asset('storage/icon/deleteMesg.svg') }}";

        // Регистрация service worker и получение токена FCM
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/firebase-messaging-sw.js')
                .then(function(registration) {
                    console.log('Service Worker зарегистрирован с областью:', registration.scope);
                    return registration;
                }).catch(function(err) {
                    console.error('Ошибка регистрации Service Worker:', err);
                });
        }

        // Удалена инициализация Firebase и проверка разрешений на уведомления
    </script>

    @if (isset($supportChat) && $supportChat)
        <!-- Чат технической поддержки -->
        <div class="chat-container support-chat">
            <div class="support-chat-block-skiter">
                <img src="{{ asset('img/support/support.png') }}" alt="Поддержка">
                <span>Время работы:</span> <br>
                <p>Пн-пт: 9:00-18:00</p>
            </div>
            <div class="chat-box">
                <div class="chat-header">

                    Техническая поддержка
                    <!-- Кнопка фильтра закреплённых сообщений -->
                    <button id="toggle-pinned" class="toggle-pinned" style="margin-left:10px;">Показать только
                        закрепленные</button>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <ul></ul>
                </div>
                <div class="chat-input" style="position: relative;">
                    <textarea id="chat-message" placeholder="Введите сообщение..." maxlength="500"></textarea>
                    <input type="file" class="file-input" style="display: none;" multiple>
                    <button type="button" class="attach-file">
                        <img src="{{ asset('storage/icon/Icon__file.svg') }}" alt="Прикрепить файл" width="24"
                            height="24">
                    </button>
                    <button id="send-message">
                        <img src="{{ asset('storage/icon/send_mesg.svg') }}" alt="Отправить" width="24"
                            height="24">
                    </button>
                </div>
            </div>
        </div>
    @elseif(isset($dealChat))
        <div class="chat-container">
            <div class="chat-box">
                <div class="chat-header">

                    {{ $dealChat->name }}
                </div>
                <div class="chat-messages" id="chat-messages">
                    <ul></ul>
                </div>
                <div class="chat-input" style="position: relative;">
                    <textarea id="chat-message" placeholder="Введите сообщение..." maxlength="500"></textarea>
                    <input type="file" class="file-input" style="display: none;" multiple>
                    <button type="button" class="attach-file">
                        <img src="{{ asset('storage/icon/Icon__file.svg') }}" alt="Прикрепить файл" width="24"
                            height="24">
                    </button>
                    <button id="send-message">
                        <img src="{{ asset('storage/icon/send_mesg.svg') }}" alt="Отправить" width="24"
                            height="24">
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="chat-container">
            <div class="user-list" id="chat-list-container">
                <h4>Все чаты</h4>
                <input type="text" id="search-chats" placeholder="Поиск по чатам и сообщениям..." />
                @if (auth()->user()->status == 'coordinator' || auth()->user()->status == 'admin')
                    <a href="{{ route('chats.group.create') }}" class="create__group">Создать групповой чат</a>
                @endif
                <ul id="chat-list">
                    @if (isset($chats) && count($chats))
                        @foreach ($chats as $chat)
                            <li data-chat-id="{{ $chat['id'] }}" data-chat-type="{{ $chat['type'] }}"
                                style="position: relative; display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                                <div class="user-list__avatar">
                                    @if($chat['type'] == 'group')
                                        @if(!empty($chat['avatar_url']) && file_exists(public_path($chat['avatar_url'])))
                                            <img src="{{ asset($chat['avatar_url']) }}" alt="{{ $chat['name'] }}" width="40" height="40">
                                        @else
                                            <img src="{{ asset('storage/avatars/group_default.png') }}" alt="{{ $chat['name'] }}" width="40" height="40">
                                        @endif
                                    @else
                                        @if(!empty($chat['avatar_url']) && file_exists(public_path($chat['avatar_url'])))
                                            <img src="{{ asset($chat['avatar_url']) }}" alt="{{ $chat['name'] }}" width="40" height="40">
                                        @else
                                            <img src="{{ asset('storage/avatars/user_default.png') }}" alt="{{ $chat['name'] }}" width="40" height="40">
                                        @endif
                                    @endif
                                </div>
                                <div class="user-list__info" style="margin-left: 10px; width: 100%;">
                                    <h5>
                                        {{ $chat['name'] }}
                                        @if ($chat['unread_count'] > 0)
                                            <span class="unread-count">{{ $chat['unread_count'] }}</span>
                                        @endif
                                    </h5>
                                </div>
                            </li>
                        @endforeach
                    @else
                        <p>Чатов пока нет</p>
                    @endif
                </ul>
                <div class="search-results" id="search-results" style="display: none;"></div>
            </div>
            <div class="chat-box">
                <div class="chat-header">

                    <span id="chat-header">Выберите чат для общения</span>

                    <!-- Кнопка фильтра для стандартного режима -->
                    <button id="toggle-pinned" class="toggle-pinned" style="margin-left:10px;">Показать только
                        закрепленные</button>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <ul></ul>
                </div>
                <div class="chat-input" style="position: relative;">
                    <textarea id="chat-message" placeholder="Введите сообщение..." maxlength="500"></textarea>
                    <input type="file" class="file-input" style="display: none;" multiple>
                    <button type="button" class="attach-file">
                        <img src="{{ asset('storage/icon/Icon__file.svg') }}" alt="Прикрепить файл" width="24"
                            height="24">
                    </button>
                    <button id="send-message">
                        <img src="{{ asset('storage/icon/send_mesg.svg') }}" alt="Отправить" width="24"
                            height="24">
                    </button>
                </div>
            </div>
        </div>

    @endif

</body>

<style>
    .image-collage {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .collage-item {
        flex: 1 1 calc(33.333% - 10px);
        max-width: calc(33.333% - 10px);
    }

    .collage-item img {
        width: 100%;
        height: auto;
        border-radius: 4px;
    }

    .attachment-file {
        display: flex;
        align-items: center;
        padding: 8px;
        background-color: #f5f5f5;
        border-radius: 4px;
        margin: 5px 0;
    }

    .attachment-file a {
        margin-left: 10px;
        color: #007bff;
        text-decoration: none;
        word-break: break-all;
    }

    .attachment-icon {
        font-size: 20px;
    }
</style>
