<!-- Заголовок и фильтр -->
<div class="brifs" id="brifs">
    <h1 class="flex">Ваши сделки</h1>
    <div class="filter">
        <form method="GET" action="{{ route('deal.cardinator') }}">
            <div class="search">
                <div class="search__input">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Поиск (имя, телефон, email, № проекта, примечание, город, сумма, даты)">
                    <img src="/storage/icon/search.svg" alt="Поиск">
                </div>
                <select name="status">
                    <option value="">Все статусы</option>
                    @foreach (['Ждем ТЗ', 'Планировка', 'Коллажи', 'Визуализация', 'Рабочка/сбор ИП', 'Проект готов', 'Проект завершен', 'Проект на паузе', 'Возврат', 'В работе', 'Завершенный', 'На потом', 'Регистрация', 'Бриф прикриплен', 'Поддержка', 'Активный'] as $option)
                        <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="variate__view">
                <button type="submit" name="view_type" value="blocks"
                    class="{{ $viewType === 'blocks' ? 'active-button' : '' }}">
                    <img src="/storage/icon/deal_card.svg" alt="Блоки">
                </button>
                <button type="submit" name="view_type" value="table"
                    class="{{ $viewType === 'table' ? 'active-button' : '' }}">
                    <img src="/storage/icon/deal__table.svg" alt="Таблица">
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Основной контент -->
<div class="deal" id="deal">
    <div class="deal__body">
        <div class="deal__cardinator__lists">
            @if ($viewType === 'table')
                <!-- Табличный вид: выводим сделки в таблице -->
                <table id="dealTable" border="1" class="deal-table">
                    <thead>
                        <tr>
                            <th>Имя клиента</th>
                            <th>Номер клиента</th>
                            <th>Сумма сделки</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody class="flex_table__format_table">
                        @foreach ($deals as $deal)
                            <tr>
                                <td>{{ $deal->name }}</td>
                                <td><a href="tel:{{ $deal->client_phone }}">{{ $deal->client_phone }}</a></td>
                                <td>{{ $deal->total_sum ?? 'Отсутствует' }}</td>
                                <td>{{ $deal->status }}</td>
                                <td class="link__deistv">
                                    <a href="{{ $deal->registration_token_url ?: '#' }}">
                                        <img src="/storage/icon/write-link.svg" alt="Ссылка">
                                    </a>
                                    <a href="{{ url('/chats') }}">
                                        <img src="/storage/icon/write-chat.svg" alt="Чат">
                                    </a>
                                    <a href="{{ $deal->link ? url($deal->link) : '#' }}">
                                        <img src="/storage/icon/write-brif.svg" alt="Бриф">
                                    </a>
                                    @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                                        <a href="{{ route('deal.change_logs.deal', ['deal' => $deal->id]) }}"
                                            class="btn btn-info btn-sm">Логи</a>
                                    @endif
                                    <!-- Кнопка редактирования с передачей всех переменных сделки -->
                                    <button type="button" class="edit-deal-btn" 
                                        data-id="{{ $deal->id }}"
                                        data-name="{{ $deal->name }}"
                                        data-client_phone="{{ $deal->client_phone }}"
                                        data-status="{{ $deal->status }}"
                                        data-priority="{{ $deal->priority }}"
                                        data-package="{{ $deal->package }}"
                                        data-project_number="{{ $deal->project_number }}"
                                        data-price_service="{{ $deal->price_service }}"
                                        data-rooms_count_pricing="{{ $deal->rooms_count_pricing }}"
                                        data-execution_order_comment="{{ $deal->execution_order_comment }}"
                                        data-office_partner_id="{{ $deal->office_partner_id }}"
                                        data-coordinator_id="{{ $deal->coordinator_id }}"
                                        data-total_sum="{{ $deal->total_sum }}"
                                        data-measuring_cost="{{ $deal->measuring_cost }}"
                                        data-project_budget="{{ $deal->project_budget }}"
                                        data-client_info="{{ $deal->client_info }}"
                                        data-payment_date="{{ $deal->payment_date }}"
                                        data-execution_comment="{{ $deal->execution_comment }}"
                                        data-comment="{{ $deal->comment }}"
                                        data-office_equipment="{{ $deal->office_equipment }}"
                                        data-measurement_comments="{{ $deal->measurement_comments }}"
                                        data-start_date="{{ $deal->start_date }}"
                                        data-project_duration="{{ $deal->project_duration }}"
                                        data-project_end_date="{{ $deal->project_end_date }}"
                                        data-architect_id="{{ $deal->architect_id }}"
                                        data-designer_id="{{ $deal->designer_id }}"
                                        data-visualizer_id="{{ $deal->visualizer_id }}"
                                        data-visualization_link="{{ $deal->visualization_link }}"
                                        data-client_project_rating="{{ $deal->client_project_rating }}"
                                        data-architect_rating_client="{{ $deal->architect_rating_client }}"
                                        data-architect_rating_partner="{{ $deal->architect_rating_partner }}"
                                        data-architect_rating_coordinator="{{ $deal->architect_rating_coordinator }}"
                                        data-designer_rating_client="{{ $deal->designer_rating_client }}"
                                        data-designer_rating_partner="{{ $deal->designer_rating_partner }}"
                                        data-designer_rating_coordinator="{{ $deal->designer_rating_coordinator }}"
                                        data-visualizer_rating_client="{{ $deal->visualizer_rating_client }}"
                                        data-visualizer_rating_partner="{{ $deal->visualizer_rating_partner }}"
                                        data-visualizer_rating_coordinator="{{ $deal->visualizer_rating_coordinator }}"
                                        data-coordinator_rating_client="{{ $deal->coordinator_rating_client }}"
                                        data-coordinator_rating_partner="{{ $deal->coordinator_rating_partner }}"
                                        data-coordinator_comment="{{ $deal->coordinator_comment }}"
                                        data-contract_number="{{ $deal->contract_number }}"
                                        data-deal_note="{{ $deal->deal_note }}"
                                        data-client_timezone="{{ $deal->client_timezone }}"
                                        data-client_city="{{ $deal->client_city }}"
                                        data-client_email="{{ $deal->client_email }}"
                                        data-completion_responsible="{{ $deal->completion_responsible }}"
                                    >
                                        <img src="/storage/icon/create.svg" alt="Редактировать">
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <!-- Блочный вид: выводим все сделки в одном общем списке -->
                <div class="faq__body__deal" id="all-deals-container">
                    <h4 class="flex">Все сделки</h4>
                    @if ($deals->isEmpty())
                        <div class="faq_block__deal faq_block-blur">
                            @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                                <div class="brifs__button__create flex"onclick="window.location.href='{{ route('deals.create') }}'">
                                    <button >
                                        <img src="/storage/icon/add.svg" alt="Создать сделку">
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="faq_block__deal faq_block-blur brifs__button__create-faq_block__deal">
                            @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                              
                                    <button onclick="window.location.href='{{ route('deals.create') }}'">
                                        <img src="/storage/icon/add.svg" alt="Создать сделку">
                                    </button>
                             
                            @endif
                        </div>
                        @foreach ($deals as $deal)
                            <div class="faq_block__deal">
                                <div class="faq_item__deal">
                                    <div class="faq_question__deal flex between">
                                        <div class="faq_question__deal__info">
                                            @if ($deal->avatar_path)
                                                <div class="deal__avatar deal__avatar__cardinator">
                                                    <img src="{{ asset('storage/' . $deal->avatar_path) }}"
                                                        alt="Avatar">
                                                </div>
                                            @endif
                                            <div class="deal__cardinator__info">
                                                <div class="ctatus__deal___info">
                                                    <div class="div__status_info">{{ $deal->status }}</div>
                                                </div>
                                                <h4>{{ $deal->name }}</h4>
                                                <p>Телефон:
                                                    <a
                                                        href="tel:{{ $deal->client_phone }}">{{ $deal->client_phone }}</a>
                                                </p>
                                            </div>
                                        </div>
                                        <ul>
                                            <li>
                                                @php
                                                    $groupChat = \App\Models\Chat::where('type', 'group')
                                                        ->where('deal_id', $deal->id)
                                                        ->first();
                                                @endphp
                                                <a
                                                    href="{{ $groupChat ? url('/chats?active_chat=' . $groupChat->id) : '#' }}">
                                                    <img src="/storage/icon/chat.svg" alt="Чат">
                                                    <div class="icon">Чат</div>
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="edit-deal-btn"
                                                    data-id="{{ $deal->id }}"
                                                    data-name="{{ $deal->name }}"
                                                    data-client_phone="{{ $deal->client_phone }}"
                                                    data-status="{{ $deal->status }}"
                                                    data-priority="{{ $deal->priority }}"
                                                    data-package="{{ $deal->package }}"
                                                    data-project_number="{{ $deal->project_number }}"
                                                    data-price_service="{{ $deal->price_service }}"
                                                    data-rooms_count_pricing="{{ $deal->rooms_count_pricing }}"
                                                    data-execution_order_comment="{{ $deal->execution_order_comment }}"
                                                    data-office_partner_id="{{ $deal->office_partner_id }}"
                                                    data-coordinator_id="{{ $deal->coordinator_id }}"
                                                    data-total_sum="{{ $deal->total_sum }}"
                                                    data-measuring_cost="{{ $deal->measuring_cost }}"
                                                    data-project_budget="{{ $deal->project_budget }}"
                                                    data-client_info="{{ $deal->client_info }}"
                                                    data-payment_date="{{ $deal->payment_date }}"
                                                    data-execution_comment="{{ $deal->execution_comment }}"
                                                    data-comment="{{ $deal->comment }}"
                                                    data-office_equipment="{{ $deal->office_equipment }}"
                                                    data-measurement_comments="{{ $deal->measurement_comments }}"
                                                    data-start_date="{{ $deal->start_date }}"
                                                    data-project_duration="{{ $deal->project_duration }}"
                                                    data-project_end_date="{{ $deal->project_end_date }}"
                                                    data-architect_id="{{ $deal->architect_id }}"
                                                    data-designer_id="{{ $deal->designer_id }}"
                                                    data-visualizer_id="{{ $deal->visualizer_id }}"
                                                    data-visualization_link="{{ $deal->visualization_link }}"
                                                    data-client_project_rating="{{ $deal->client_project_rating }}"
                                                    data-architect_rating_client="{{ $deal->architect_rating_client }}"
                                                    data-architect_rating_partner="{{ $deal->architect_rating_partner }}"
                                                    data-architect_rating_coordinator="{{ $deal->architect_rating_coordinator }}"
                                                    data-designer_rating_client="{{ $deal->designer_rating_client }}"
                                                    data-designer_rating_partner="{{ $deal->designer_rating_partner }}"
                                                    data-designer_rating_coordinator="{{ $deal->designer_rating_coordinator }}"
                                                    data-visualizer_rating_client="{{ $deal->visualizer_rating_client }}"
                                                    data-visualizer_rating_partner="{{ $deal->visualizer_rating_partner }}"
                                                    data-visualizer_rating_coordinator="{{ $deal->visualizer_rating_coordinator }}"
                                                    data-coordinator_rating_client="{{ $deal->coordinator_rating_client }}"
                                                    data-coordinator_rating_partner="{{ $deal->coordinator_rating_partner }}"
                                                    data-coordinator_comment="{{ $deal->coordinator_comment }}"
                                                    data-contract_number="{{ $deal->contract_number }}"
                                                    data-deal_note="{{ $deal->deal_note }}"
                                                    data-client_timezone="{{ $deal->client_timezone }}"
                                                    data-client_city="{{ $deal->client_city }}"
                                                    data-client_email="{{ $deal->client_email }}"
                                                    data-completion_responsible="{{ $deal->completion_responsible }}"
                                                >
                                                    <img src="/storage/icon/create__blue.svg" alt=""> <span>
                                                        Изменить</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                    <div class="pagination" id="all-deals-pagination"></div>
                </div>
            @endif
        </div>
    </div>
</div>
<!-- Модальное окно редактирования сделки -->
<div class="modal modal__deal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" id="closeModalBtn">&times;</span>
        <div class="button__points">
            <button data-target="Лента">Лента</button>
            <button data-target="Заказ">Заказ</button>
            <button data-target="Работа над проектом">Работа над проектом</button>
            <button data-target="Финал проекта">Финал проекта</button>
            <button data-target="О сделке">О сделке</button>
            <button data-target="Аватар сделки">Аватар сделки</button>
            <ul>
                <li>
                    <a href="{{ isset($deal) && $deal->link ? url($deal->link) : '#' }}">
                        <img src="/storage/icon/link.svg" alt="Чат">
                    </a>
                </li>
                @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                    <li>
                        <a href="{{ isset($deal) ? route('deal.change_logs.deal', ['deal' => $deal->id]) : '#' }}" class="btn btn-info btn-sm">
                            <img src="/storage/icon/log.svg" alt="Чат">
                        </a>
                    </li>
                @endif
                <li>
                   <a href="{{ isset($groupChat) ? url('/chats?active_chat=' . $groupChat->id) : '#' }}">
                        <img src="/storage/icon/chat.svg" alt="Чат">
                    </a>
                </li>
            </ul>
        </div>

        <!-- Модуль: Лента (отдельно, не внутри формы) -->
        <fieldset class="module__deal " id="module-feed">
            <legend>Лента</legend>
            <!-- Список записей ленты -->
            <div class="feed-posts" id="feed-posts-container">
                @if(isset($deal) && $deal && isset($deal->dealFeeds))
                    @foreach ($deal->dealFeeds as $feed)
                        <div class="feed-post">
                            <div class="feed-post-avatar">
                                <img src="{{ $feed->user->avatar_url ?? asset('storage/default-avatar.png') }}" 
                                     alt="{{ $feed->user->name }}">
                            </div>
                            <div class="feed-post-text">
                                <div class="feed-author">{{ $feed->user->name }}</div>
                                <div class="feed-content">{{ $feed->content }}</div>
                                <div class="feed-date">{{ $feed->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>Нет записей в ленте</p>
                @endif
                
                @if (isset($deal) && in_array(Auth::user()->status, ['coordinator', 'admin']))
                    <form id="feed-form">
                        @csrf
                        <div class="feed-form-post">
                            <textarea name="content" id="feed-content" placeholder="Добавьте запись в ленту" required maxlength="1990"></textarea>
                            <button type="submit"><img src="{{ asset('/storage/icon/send_mesg.svg') }}" alt="Отправить"></button>
                        </div>
                    </form>
                @endif
            </div>
        </fieldset>

        <script>
           document.addEventListener("DOMContentLoaded", function () {
    $("#feed-form").on("submit", function (e) {
        e.preventDefault();

        let content = $("#feed-content").val().trim();
        if (!content) {
            alert("Введите текст сообщения!");
            return;
        }

        @if(isset($deal) && $deal)
        $.ajax({
            url: "{{ route('deal.feed.store', $deal->id) }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                content: content
            },
            success: function (response) {
                $("#feed-content").val(""); // Очистить поле ввода
                let avatarUrl = response.avatar_url ? response.avatar_url : "/storage/default-avatar.png";
                $("#feed-posts-container").prepend(`
                    <div class="feed-post">
                        <div class="feed-post-avatar">
                            <img src="${avatarUrl}" alt="${response.user_name}">
                        </div>
                        <div class="feed-post-text">
                            <div class="feed-author">${response.user_name}</div>
                            <div class="feed-content">${response.content}</div>
                            <div class="feed-date">${response.date}</div>
                        </div>
                    </div>
                `);
            },
            error: function (xhr) {
                alert("Ошибка при добавлении записи: " + xhr.responseText);
            }
        });
        @else
        alert("Не удалось определить сделку. Пожалуйста, обновите страницу.");
        @endif
    });
});

        </script>

        <!-- Форма редактирования сделки (остальные модули) -->
        <form id="editForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <!-- Скрытое поле для ID сделки -->
            <input type="hidden" name="deal_id" id="dealIdField" value="">

            @if (Auth::user()->status == 'coordinator' || Auth::user()->status == 'admin')
                <!-- Модуль: Заказ -->
                <fieldset class="module__deal module__deal-deal" id="module-zakaz">
                    <legend>Заказ</legend>
                    <div class="form-group-deal">
                        <label>№ проекта:
                            <input type="text" name="project_number" id="projectNumberField" value="{{ isset($deal) ? $deal->project_number : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Статус:
                            <select name="status" id="statusField">
                                <option value="">-- Выберите статус --</option>
                                @foreach (['Ждем ТЗ', 'Планировка', 'Коллажи', 'Визуализация', 'Рабочка/сбор ИП', 'Проект готов', 'Проект завершен', 'Проект на паузе', 'Возврат', 'В работе', 'Завершенный', 'На потом', 'Регистрация', 'Бриф прикриплен', 'Поддержка', 'Активный'] as $statusOption)
                                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Услуга по прайсу:
                            <select name="price_service_option" id="priceServiceField">
                                <option value="">-- Выберите услугу --</option>
                                @foreach (['экспресс планировка', 'экспресс планировка с коллажами', 'экспресс проект с электрикой', 'экспресс планировка с электрикой и коллажами', 'экспресс проект с электрикой и визуализацией', 'экспресс рабочий проект', 'экспресс эскизный проект с рабочей документацией', 'экспресс 3Dвизуализация', 'экспресс полный дизайн-проект', '360 градусов'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Количество комнат по прайсу:
                            <input type="number" name="rooms_count_pricing" id="roomsCountField" value="{{ isset($deal) ? $deal->rooms_count_pricing : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Комментарий к Заказу:
                            <textarea name="execution_order_comment" id="executionOrderCommentField" maxlength="1000">{{ isset($deal) ? $deal->execution_order_comment : '' }}</textarea>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Пакет:
                            <input type="text" name="package" id="packageField" value="{{ isset($deal) ? $deal->package : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>ФИО клиента:
                            <input type="text" name="name" id="nameField" value="{{ isset($deal) ? $deal->name : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Телефон клиента: <span class="required">*</span>
                            <input type="text" name="client_phone" id="phoneField" 
                                value="{{ isset($deal) ? $deal->client_phone : '' }}" 
                                class="form-control"
                                required>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Город:
                            <select name="client_city" id="cityField"></select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Email клиента:
                            <input type="email" name="client_email" id="emailField" 
                                value="{{ isset($deal) ? $deal->client_email : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Офис/Партнер:
                            <select name="office_partner_id" id="officePartnerField" class="select2-field">
                                <option value="">-- Не выбрано --</option>
                                @foreach (\App\Models\User::where('status', 'partner')->get() as $partner)
                                    <option value="{{ $partner->id }}" 
                                        {{ isset($deal) && $deal->office_partner_id == $partner->id ? 'selected' : '' }}>
                                        {{ $partner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Кто делает комплектацию:
                            <input type="text" name="completion_responsible" id="completionResponsibleField"
                                value="{{ isset($deal) ? $deal->completion_responsible : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Координатор:
                            <select name="coordinator_id" id="coordinatorField" class="select2-field">
                                <option value="">-- Не выбрано --</option>
                                @foreach (\App\Models\User::where('status', 'coordinator')->get() as $coordinator)
                                    <option value="{{ $coordinator->id }}" 
                                        {{ isset($deal) && $deal->coordinator_id == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Ответственные:
                            <select name="responsibles[]" id="responsiblesField" class="select2-field" multiple>
                                @foreach (\App\Models\User::whereIn('status', ['designer', 'coordinator'])->get() as $responsible)
                                    <option value="{{ $responsible->id }}" 
                                        {{ isset($deal) && in_array($responsible->id, $deal->users->pluck('id')->toArray()) ? 'selected' : '' }}>
                                        {{ $responsible->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>

                <!-- Модуль: Работа над проектом -->
                <fieldset class="module__deal module__deal-deal" id="module-rabota">
                    <legend>Работа над проектом</legend>
                    <div class="form-group-deal">
                        <label>Комментарии по замерам:
                            <textarea name="measurement_comments" id="measurementCommentsField" maxlength="1000">{{ isset($deal) ? $deal->measurement_comments : '' }}</textarea>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Замеры (файл):
                            <input type="file" name="measurements_file" id="measurementsFileField"
                                accept=".pdf,.dwg,image/*">
                            @if(isset($deal) && $deal->measurements_file)
                                <a href="{{ asset('storage/' . $deal->measurements_file) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата старта:
                            <input type="date" name="start_date" id="startDateField" value="{{ isset($deal) ? $deal->start_date : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Общий срок проекта (в днях):
                            <input type="number" name="project_duration" id="projectDurationField" value="{{ isset($deal) ? $deal->project_duration : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата завершения:
                            <input type="date" name="project_end_date" id="projectEndDateField" value="{{ isset($deal) ? $deal->project_end_date : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Архитектор:
                            <select name="architect_id" id="architectField">
                                <option value="">-- Не выбрано --</option>
                                @foreach (\App\Models\User::where('status', 'architect')->get() as $architect)
                                    <option value="{{ $architect->id }}" {{ isset($deal) && $deal->architect_id == $architect->id ? 'selected' : '' }}>{{ $architect->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Планировка финал (PDF):
                            <input type="file" name="final_floorplan" id="finalFloorplanField"
                                accept="application/pdf">
                            @if(isset($deal) && $deal->final_floorplan)
                                <a href="{{ asset('storage/' . $deal->final_floorplan) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дизайнер:
                            <select name="designer_id" id="designerField">
                                <option value="">-- Не выбрано --</option>
                                @foreach (\App\Models\User::where('status', 'designer')->get() as $designer)
                                    <option value="{{ $designer->id }}" {{ isset($deal) && $deal->designer_id == $designer->id ? 'selected' : '' }}>{{ $designer->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Коллаж финал (PDF):
                            <input type="file" name="final_collage" id="finalCollageField"
                                accept="application/pdf">
                            @if(isset($deal) && $deal->final_collage)
                                <a href="{{ asset('storage/' . $deal->final_collage) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Визуализатор:
                            <select name="visualizer_id" id="visualizerField">
                                <option value="">-- Не выбрано --</option>
                                @foreach (\App\Models\User::where('status', 'visualizer')->get() as $visualizer)
                                    <option value="{{ $visualizer->id }}" {{ isset($deal) && $deal->visualizer_id == $visualizer->id ? 'selected' : '' }}>{{ $visualizer->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Ссылка на визуализацию:
                            <input type="url" name="visualization_link" id="visualizationLinkField"
                                value="{{ isset($deal) ? $deal->visualization_link : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Финал проекта (PDF):
                            <input type="file" name="final_project_file" id="finalProjectFileField"
                                accept="application/pdf">
                            @if(isset($deal) && $deal->final_project_file)
                                <a href="{{ asset('storage/' . $deal->final_project_file) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                </fieldset>

                <!-- Модуль: Финал проекта -->
                <fieldset class="module__deal module__deal-deal" id="module-final">
                    <legend>Финал проекта</legend>
                    <div class="form-group-deal">
                        <label>Акт выполненных работ (PDF):
                            <input type="file" name="work_act" id="workActField" accept="application/pdf">
                            @if(isset($deal) && $deal->work_act)
                                <a href="{{ asset('storage/' . $deal->work_act) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка за проект (от клиента):
                            <input type="number" name="client_project_rating" id="clientProjectRatingField"
                                value="{{ isset($deal) ? $deal->client_project_rating : '' }}" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Клиент):
                            <input type="number" name="architect_rating_client" id="architectRatingClientField"
                                value="{{ isset($deal) ? $deal->architect_rating_client : '' }}" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Партнер):
                            <input type="number" name="architect_rating_partner" id="architectRatingPartnerField"
                                value="{{ isset($deal) ? $deal->architect_rating_partner : '' }}" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Координатор):
                            <input type="number" name="architect_rating_coordinator"
                                id="architectRatingCoordinatorField" value="{{ isset($deal) ? $deal->architect_rating_coordinator : '' }}" min="0" max="10"
                                step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Скрин чата с оценкой и актом (JPEG):
                            <input type="file" name="chat_screenshot" id="chatScreenshotField"
                                accept="image/jpeg,image/jpg,image/png">
                            @if(isset($deal) && $deal->chat_screenshot)
                                <a href="{{ asset('storage/' . $deal->chat_screenshot) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Комментарий координатора:
                            <textarea name="coordinator_comment" id="coordinatorCommentField" maxlength="1000">{{ isset($deal) ? $deal->coordinator_comment : '' }}</textarea>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Исходный файл архикад (pln, dwg):
                            <input type="file" name="archicad_file" id="archicadFileField" accept=".pln,.dwg">
                            @if(isset($deal) && $deal->archicad_file)
                                <a href="{{ asset('storage/' . $deal->archicad_file) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                </fieldset>

                <!-- Модуль: О сделке -->
                <fieldset class="module__deal module__deal-deal" id="module-o-sdelke">
                    <legend>О сделке</legend>
                    <div class="form-group-deal">
                        <label>№ договора:
                            <input type="text" name="contract_number" id="contractNumberField" value="{{ isset($deal) ? $deal->contract_number : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата создания сделки:
                            <input type="date" name="created_date" id="createdDateField" value="{{ isset($deal) ? $deal->created_date : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата оплаты:
                            <input type="date" name="payment_date" id="paymentDateField" value="{{ isset($deal) ? $deal->payment_date : '' }}">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Сумма Заказа:
                            <input type="number" name="total_sum" id="totalSumField" value="{{ isset($deal) ? $deal->total_sum : '' }}"
                                step="0.01">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Приложение договора:
                            <input type="file" name="contract_attachment" id="contractAttachmentField"
                                accept="application/pdf,image/jpeg,image/jpg,image/png">
                            @if(isset($deal) && $deal->contract_attachment)
                                <a href="{{ asset('storage/' . $deal->contract_attachment) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Примечание:
                            <textarea name="deal_note" id="dealNoteField">{{ isset($deal) ? $deal->deal_note : '' }}</textarea>
                        </label>
                    </div>
                </fieldset>

                <!-- Модуль: Аватар сделки -->
                <fieldset class="module__deal module__deal-deal" id="module-avatar">
                    <legend>Аватар сделки</legend>
                    <div class="form-group-deal">
                        <label>Аватар сделки:
                            <input type="file" name="avatar" id="avatarField" accept="image/*">
                            @if(isset($deal) && $deal->avatar_path)
                                <a href="{{ asset('storage/' . $deal->avatar_path) }}" target="_blank">Просмотр файла</a>
                            @endif
                        </label>
                    </div>
                    <div id="avatar-preview" class="avatar-preview">
                        <!-- Превью аватара -->
                    </div>
                </fieldset>
            @endif

            <!-- Кнопки управления формой -->
            <div class="form-buttons">
                <button type="button" class="toggle-edit-btn">Изменить</button>
                <button type="submit" id="saveButton" disabled>Сохранить</button>
                
            </div>
        </form>
    </div>
</div>
<!-- Скрипты и подключения -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var modules = document.querySelectorAll("#editModal fieldset.module__deal");
        var buttons = document.querySelectorAll("#editModal .button__points button");
        modules.forEach(function(module__deal) {
            module__deal.style.display = "none";
            module__deal.style.opacity = "0";
            module__deal.style.transition = "opacity 0.3s ease-in-out";
        });
        if (modules.length > 0) {
            modules[0].style.display = "flex";
            setTimeout(function() {
                modules[0].style.opacity = "1";
            }, 10);
        }
        buttons.forEach(function(button) {
            button.addEventListener("click", function() {
                var targetText = this.getAttribute("data-target").trim();
                buttons.forEach(function(btn) {
                    btn.classList.remove("buttonSealaActive");
                });
                this.classList.add("buttonSealaActive");
                modules.forEach(function(module__deal) {
                    module__deal.style.opacity = "0";
                    setTimeout(function() {
                        module__deal.style.display = "none";
                    }, 300);
                });
                setTimeout(function() {
                    modules.forEach(function(module__deal) {
                        var legend = module__deal.querySelector("legend");
                        if (legend && legend.textContent.trim() ===
                            targetText) {
                            module__deal.style.display = "flex";
                            setTimeout(function() {
                                module__deal.style.opacity = "1";
                            }, 10);
                        }
                    });
                }, 300);
            });
        });
    });
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/simplePagination.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/jquery.simplePagination.min.js"></script>
<script>
    $(function() {
        $('.copy-link').on('click', function(e) {
            e.preventDefault();
            var link = $(this).data('link') || $(this).attr('href');
            if (link) {
                navigator.clipboard.writeText(link).then(function() {
                    alert('Ссылка скопирована: ' + link);
                });
            }
        });
        if ($('#dealTable').length) {
            $('#dealTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/ru.json'
                },
                paging: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true
            });
        }

        function paginateContainer(container, paginationContainer, perPage = 6) {
            var $container = $(container);
            var $blocks = $container.find('.faq_block__deal');
            var total = $blocks.length;
            if (total <= perPage) {
                $blocks.show();
                return;
            }
            $blocks.hide();
            $blocks.slice(0, perPage).show();
            $(paginationContainer).pagination({
                items: total,
                itemsOnPage: perPage,
                cssStyle: 'light-theme',
                prevText: 'Предыдущая',
                nextText: 'Следующая',
                onPageClick: function(pageNumber, event) {
                    var start = (pageNumber - 1) * perPage;
                    var end = start + perPage;
                    $blocks.hide().slice(start, end).show();
                }
            });
        }
        paginateContainer('#all-deals-container', '#all-deals-pagination', 6);
        var $editModal = $('#editModal'),
            $editForm = $('#editForm');
        $('.edit-deal-btn').on('click', function() {
            var data = $(this).data();
            $.each(data, function(key, value) {
                $editForm.find('#' + key + 'Field').val(value);
            });
            $editForm.attr('action', "{{ url('/deal/update') }}/" + data.id)
                .find('input,select,textarea,button[type="submit"]').prop('disabled', true);
            $editModal.show().addClass('show');
        });
        $('#closeModalBtn').on('click', function() {
            $editModal.removeClass('show').hide();
        });
        $editModal.on('click', function(e) {
            if (e.target === this) $(this).removeClass('show').hide();
        });
        $('.toggle-edit-btn').on('click', function() {
            var disabled = $editForm.find('#nameField').prop('disabled');
            $editForm.find('input,select,textarea,button[type="submit"]').prop('disabled', !disabled);
            $(this).text(disabled ? 'Отменить' : 'Изменить');
        });
        $.getJSON('/cities.json', function(data) {
            var grouped = {};
            $.each(data, function(i, item) {
                grouped[item.region] = grouped[item.region] || [];
                grouped[item.region].push({
                    id: item.city,
                    text: item.city
                });
            });
            var selectData = $.map(grouped, function(cities, region) {
                return {
                    text: region,
                    children: cities
                };
            });
            $('#client_timezone, #cityField').select2({
                data: selectData,
                placeholder: "-- Выберите город --",
                allowClear: true
            });
        }).fail(function(err) {
            console.error("Ошибка загрузки городов", err);
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#responsiblesField').select2({
            placeholder: "Выберите ответственных",
            allowClear: true
        });
    });
</script>
<script>
$(document).ready(function() {
    // Инициализация Select2
    $('.select2-field').select2({
        width: '100%',
        placeholder: "Выберите значение",
        allowClear: true
    });

    // Обработка открытия модального окна редактирования
    $('.edit-deal-btn').on('click', function() {
        var data = $(this).data();
        
        // Обновляем поля формы
        $.each(data, function(key, value) {
            var $field = $('#' + key + 'Field');
            if($field.length) {
                if($field.hasClass('select2-field')) {
                    $field.val(value).trigger('change');
                } else if($field.is(':checkbox')) {
                    $field.prop('checked', value == 1);
                } else {
                    $field.val(value);
                }
            }
        });

        // Обновляем множественный выбор ответственных
        if(data.responsibles) {
            var responsibles = data.responsibles.split(',');
            $('#responsiblesField').val(responsibles).trigger('change');
        }

        // Открываем модальное окно
        $('#editModal').show();
    });

    // Закрытие модального окна
    $('#closeModalBtn').on('click', function() {
        $('#editModal').hide();
    });

    // Закрытие модального окна при клике вне его
    $(window).on('click', function(event) {
        if ($(event.target).is('#editModal')) {
            $('#editModal').hide();
        }
    });
});
</script>
<script>
$(document).ready(function() {
    // Обработка открытия модального окна редактирования
    $('.edit-deal-btn').on('click', function() {
        var data = $(this).data();
        
        // Обновляем поля формы
        $.each(data, function(key, value) {
            var $field = $('#' + key + 'Field');
            if($field.length) {
                if($field.hasClass('select2-field')) {
                    $field.val(value).trigger('change');
                } else if($field.is(':checkbox')) {
                    $field.prop('checked', value == 1);
                } else {
                    $field.val(value);
                }
            }
        });
    });
});
</script>

<style>
.select2-container {
    width: 100% !important;
}

.select2-selection--multiple {
    min-height: 38px !important;
}

.select2-selection__choice {
    padding: 2px 5px !important;
    margin: 2px !important;
    background-color: #e4e4e4 !important;
    border: none !important;
    border-radius: 3px !important;
}
</style>

