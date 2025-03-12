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
                    @foreach ($statuses as $option)
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
                        @foreach ($deals as $dealItem)
                            <tr>
                                <td>{{ $dealItem->name }}</td>
                                <td>
                                    <a href="tel:{{ $dealItem->client_phone }}">
                                        {{ $dealItem->client_phone }}
                                    </a>
                                </td>
                                <td>{{ $dealItem->total_sum ?? 'Отсутствует' }}</td>
                                <td>{{ $dealItem->status }}</td>
                                <td class="link__deistv">
                                    <a href="{{ $dealItem->registration_token_url ?: '#' }}">
                                        <img src="/storage/icon/write-link.svg" alt="Ссылка">
                                    </a>
                                    <a href="{{ url('/chats') }}">
                                        <img src="/storage/icon/write-chat.svg" alt="Чат">
                                    </a>
                                    <a href="{{ $dealItem->link ? url($dealItem->link) : '#' }}">
                                        <img src="/storage/icon/write-brif.svg" alt="Бриф">
                                    </a>
                                    @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                                        <a href="{{ route('deal.change_logs.deal', ['deal' => $dealItem->id]) }}"
                                            class="btn btn-info btn-sm">Логи</a>
                                    @endif
                                    <!-- Кнопка редактирования с data-атрибутом, содержащим JSON сделки -->
                                    <button type="button" class="edit-deal-btn"
                                        data-deal='@json($dealItem->getAttributes())'>
                                        <img src="/storage/icon/create.svg" alt="Редактировать">
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <!-- Блочный вид -->
                <div class="faq__body__deal" id="all-deals-container">
                    <h4 class="flex">Все сделки</h4>
                    @if ($deals->isEmpty())
                        <div class="faq_block__deal faq_block-blur">
                            @if (in_array(Auth::user()->status, ['coordinator', 'admin', 'partner']))
                                <div class="brifs__button__create flex"
                                    onclick="window.location.href='{{ route('deals.create') }}'">
                                    <button>
                                        <img src="/storage/icon/add.svg" alt="Создать сделку">
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="faq_block__deal faq_block-blur brifs__button__create-faq_block__deal">
                            @if (in_array(Auth::user()->status, ['coordinator', 'admin', 'partner']))
                                <button onclick="window.location.href='{{ route('deals.create') }}'">
                                    <img src="/storage/icon/add.svg" alt="Создать сделку">
                                </button>
                            @endif
                        </div>
                        @foreach ($deals as $dealItem)
                            <div class="faq_block__deal">
                                <div class="faq_item__deal">
                                    <div class="faq_question__deal flex between">
                                        <div class="faq_question__deal__info">
                                            @if ($dealItem->avatar_path)
                                                <div class="deal__avatar deal__avatar__cardinator">
                                                    <img src="{{ asset('storage/' . $dealItem->avatar_path) }}"
                                                        alt="Avatar">
                                                </div>
                                            @endif
                                            <div class="deal__cardinator__info">
                                                <div class="ctatus__deal___info">
                                                    <div class="div__status_info">{{ $dealItem->status }}</div>
                                                </div>
                                                <h4>{{ $dealItem->name }}</h4>
                                                <p>Телефон:
                                                    <a href="tel:{{ $dealItem->client_phone }}">
                                                        {{ $dealItem->client_phone }}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                        <ul>
                                            <li>
                                                @php
                                                    $groupChat = \App\Models\Chat::where('type', 'group')
                                                        ->where('deal_id', $dealItem->id)
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
                                                    data-deal='@json($dealItem->getAttributes())'>
                                                    <img src="/storage/icon/create__blue.svg" alt="">
                                                    <span>Изменить</span>
                                                </button>
                                            </li>
                                            <li>
                                                <a class="copy-link"
                                                    href="{{ isset($deal) && $deal->link ? url($deal->link) : '#' }}">
                                                    <img src="/storage/icon/link.svg" alt="Ссылка">
                                                </a>
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
<div class="modal modal__deal" id="editModal" style="display: none;">
    <div class="modal-content">
        <!-- Кнопка закрытия -->
        <span class="close-modal" id="closeModalBtn">&times;</span>
        <!-- Навигация по модулям и дополнительные ссылки -->
        <div class="button__points">
            <button data-target="Лента" class="active-button">Лента</button>
            <button data-target="Заказ">Заказ</button>
            <button data-target="Работа над проектом">Работа над проектом</button>
            @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                <button data-target="Финал проекта">Финал проекта</button>
            @endif

            <button data-target="Аватар сделки">Аватар сделки</button>
            <ul>
                <li>
                    <a href="{{ isset($deal) && $deal->link ? url($deal->link) : '#' }}">
                        <img src="/storage/icon/link.svg" alt="Ссылка">
                    </a>
                </li>
                @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                    <li>
                        <!-- Заменяем использование $dealItem->id на "#" -->
                        <a href="#" class="btn btn-info btn-sm">
                            <img src="/storage/icon/log.svg" alt="Логи">
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
        <!-- Модуль: Лента -->
        <fieldset class="module__deal" id="module-feed">
            <legend>Лента</legend>
            <div class="feed-posts" id="feed-posts-container">
                @foreach ($feeds as $feed)
                    <div class="feed-post">
                        <div class="feed-post-avatar">
                            <img src="{{ $feed->avatar_url ?? '/storage/default-avatar.png' }}"
                                alt="{{ $feed->user_name }}">
                        </div>
                        <div class="feed-post-text">
                            <div class="feed-author">{{ $feed->user_name }}</div>
                            <div class="feed-content">{{ $feed->content }}</div>
                            <div class="feed-date">{{ $feed->date }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <form id="feed-form" class="feed-form-post" action="#" method="POST">
                @csrf
                <textarea id="feed-content" name="content" placeholder="Введите ваш комментарий" rows="3"></textarea>
                <button type="submit">Отправить</button>
            </form>
        </fieldset>
        <!-- Форма редактирования сделки -->
        <form id="editForm" method="POST" enctype="multipart/form-data" action="{{ url('/deal/update') }}/">
            @csrf
            @method('PUT')
            <input type="hidden" name="deal_id" id="dealIdField" value="">

            <!-- Модуль: Заказ -->
            <fieldset class="module__deal" id="module-zakaz">
                <legend>Заказ</legend>
                <div class="form-group-deal">
                    <label>№ проекта:
                        <input type="text" name="project_number" id="projectNumberField" value="">
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
                        <input type="number" name="rooms_count_pricing" id="roomsCountField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Комментарий к Заказу:
                        <textarea name="execution_order_comment" id="executionOrderCommentField" maxlength="1000"></textarea>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Пакет:
                        <input type="text" name="package" id="packageField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>ФИО клиента:
                        <input type="text" name="name" id="nameField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Телефон клиента: <span class="required">*</span>
                        <input type="text" name="client_phone" id="phoneField" value="" required>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Город:
                        <select name="client_city" id="cityField"></select>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Email клиента:
                        <input type="email" name="client_email" id="emailField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Офис/Партнер:
                        <select name="office_partner_id" id="officePartnerField" class="select2-field">
                            <option value="">-- Не выбрано --</option>
                            @foreach (\App\Models\User::where('status', 'partner')->get() as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Кто делает комплектацию:
                        <input type="text" name="completion_responsible" id="completionResponsibleField"
                            value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Координатор:
                        <select name="coordinator_id" id="coordinatorField" class="select2-field">
                            <option value="">-- Не выбрано --</option>
                            @foreach (\App\Models\User::where('status', 'coordinator')->get() as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </fieldset>

            <!-- Модуль: Работа над проектом -->
            <fieldset class="module__deal" id="module-rabota">
                <legend>Работа над проектом</legend>
                <div class="form-group-deal">
                    <label>Замеры (файл):
                        <input type="file" name="measurements_file" id="measurementsFileField"
                            accept=".pdf,.dwg,image/*">
                    </label>
                    <a id="measurementsFileName" href="#" style="display:none;">Просмотр файла</a>
                </div>
                <div class="form-group-deal">
                    <label>Дата старта:
                        <input type="date" name="start_date" id="startDateField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Общий срок проекта (в днях):
                        <input type="number" name="project_duration" id="projectDurationField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Дата завершения:
                        <input type="date" name="project_end_date" id="projectEndDateField" value="">
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Архитектор:
                        <select name="architect_id" id="architectField">
                            <option value="">-- Не выбрано --</option>
                            @foreach (\App\Models\User::where('status', 'architect')->get() as $architect)
                                <option value="{{ $architect->id }}">{{ $architect->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Дизайнер:
                        <select name="designer_id" id="designerField">
                            <option value="">-- Не выбрано --</option>
                            @foreach (\App\Models\User::where('status', 'designer')->get() as $designer)
                                <option value="{{ $designer->id }}">{{ $designer->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Коллаж финал (PDF):
                        <input type="file" name="final_collage" id="finalCollageField" accept="application/pdf">
                    </label>
                    <a id="finalCollageFileName" href="#" style="display:none;">Просмотр файла</a>
                </div>
                <div class="form-group-deal">
                    <label>Визуализатор:
                        <select name="visualizer_id" id="visualizerField">
                            <option value="">-- Не выбрано --</option>
                            @foreach (\App\Models\User::where('status', 'visualizer')->get() as $visualizer)
                                <option value="{{ $visualizer->id }}">{{ $visualizer->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-group-deal">
                    <label>Финал проекта (PDF):
                        <input type="file" name="final_project_file" id="finalProjectFileField"
                            accept="application/pdf">
                    </label>
                    <a id="finalProjectFileName" href="#" style="display:none;">Просмотр файла</a>
                </div>
            </fieldset>

            @if (in_array(Auth::user()->status, ['coordinator', 'admin']))
                <!-- Модуль: Финал проекта -->
                <fieldset class="module__deal" id="module-final">
                    <legend>Финал проекта</legend>
                    <div class="form-group-deal">
                        <label>Акт выполненных работ (PDF):
                            <input type="file" name="work_act" id="workActField" accept="application/pdf">
                        </label>
                        <a id="workActFileName" href="#" style="display:none;">Просмотр файла</a>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка за проект (от клиента):
                            <input type="number" name="client_project_rating" id="clientProjectRatingField"
                                value="" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Клиент):
                            <input type="number" name="architect_rating_client" id="architectRatingClientField"
                                value="" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Партнер):
                            <input type="number" name="architect_rating_partner" id="architectRatingPartnerField"
                                value="" min="0" max="10" step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Оценка архитектора (Координатор):
                            <input type="number" name="architect_rating_coordinator"
                                id="architectRatingCoordinatorField" value="" min="0" max="10"
                                step="0.5">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Скрин чата с оценкой и актом (JPEG):
                            <input type="file" name="chat_screenshot" id="chatScreenshotField"
                                accept="image/jpeg,image/jpg,image/png">
                        </label>
                        <a id="chatScreenshotFileName" href="#" style="display:none;">Просмотр файла</a>
                    </div>
                    <div class="form-group-deal">
                        <label>Комментарий координатора:
                            <textarea name="coordinator_comment" id="coordinatorCommentField" maxlength="1000"></textarea>
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Исходный файл архикад (pln, dwg):
                            <input type="file" name="archicad_file" id="archicadFileField" accept=".pln,.dwg">
                        </label>
                        <a id="archicadFileName" href="#" style="display:none;">Просмотр файла</a>
                    </div>
                    <div class="form-group-deal">
                        <label>№ договора:
                            <input type="text" name="contract_number" id="contractNumberField" value="">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата создания сделки:
                            <input type="date" name="created_date" id="createdDateField" value="">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Дата оплаты:
                            <input type="date" name="payment_date" id="paymentDateField" value="">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Сумма Заказа:
                            <input type="number" name="total_sum" id="totalSumField" value=""
                                step="0.01">
                        </label>
                    </div>
                    <div class="form-group-deal">
                        <label>Приложение договора:
                            <input type="file" name="contract_attachment" id="contractAttachmentField"
                                accept="application/pdf,image/jpeg,image/jpg,image/png">
                        </label>
                        <a id="contractAttachmentFileName" href="#" style="display:none;">Просмотр файла</a>
                    </div>
                    <div class="form-group-deal">
                        <label>Примечание:
                            <textarea name="deal_note" id="dealNoteField"></textarea>
                        </label>
                    </div>
                </fieldset>
            @endif


            <!-- Модуль: Аватар сделки -->
            <fieldset class="module__deal" id="module-avatar">
                <legend>Аватар сделки</legend>
                <div class="form-group-deal">
                    <label>Аватар сделки:
                        <input type="file" name="avatar" id="avatarField" accept="image/*">
                    </label>
                    <a id="avatarFileName" href="#" style="display:none;">Просмотр файла</a>
                </div>
                <div id="avatar-preview" class="avatar-preview">
                    <!-- Превью аватара -->
                </div>
            </fieldset>

            <!-- Кнопки управления формой -->
            <div class="form-buttons">
                <button type="submit" id="saveButton" disabled>Сохранить</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/jquery.simplePagination.min.js"></script>
<script>
    $(function() {
        // Инициализация DataTable для табличного вида
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

        // Пагинация для блочного вида
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

        // Маппинг ключей сделки к id полей формы
        var fieldMapping = {
            id: "dealIdField",
            name: "nameField",
            client_phone: "phoneField",
            project_number: "projectNumberField",
            status: "statusField",
            price_service: "priceServiceField", // значение из БД хранится в price_service
            rooms_count_pricing: "roomsCountField",
            execution_order_comment: "executionOrderCommentField",
            package: "packageField",
            client_city: "cityField",
            client_email: "emailField",
            office_partner_id: "officePartnerField",
            completion_responsible: "completionResponsibleField",
            coordinator_id: "coordinatorField",
            total_sum: "totalSumField",
            start_date: "startDateField",
            project_duration: "projectDurationField",
            project_end_date: "projectEndDateField",
            architect_id: "architectField",
            designer_id: "designerField",
            final_collage: "finalCollageField",
            visualizer_id: "visualizerField",
            final_project_file: "finalProjectFileField",
            contract_number: "contractNumberField",
            created_date: "createdDateField",
            payment_date: "paymentDateField",
            deal_note: "dealNoteField"
        };

        // Открытие модального окна и заполнение формы данными сделки
        $('.edit-deal-btn').on('click', function() {
            var dealData = $(this).data('deal');
            if (typeof dealData === 'string') {
                dealData = JSON.parse(dealData);
            }
            $.each(fieldMapping, function(key, fieldId) {
                var $field = $('#' + fieldId);
                if ($field.length && !$field.is("[type='file']")) { // исключаем file-поля
                    var value = dealData[key] !== undefined ? dealData[key] : "";
                    $field.val(value).trigger('change');
                }
            });
            // Обновляем action формы
            $editForm.attr('action', "{{ url('/deal/update') }}/" + dealData.id);
            $editModal.show().addClass('show');
            // Новое: отключаем все поля и кнопку "Сохранить" при открытии модального окна
            // $editForm.find('input,select,textarea,button[type="submit"]').prop('disabled', true);
            // Обеспечиваем, чтобы кнопка переключения редактирования оставалась активной
            $editForm.find('.toggle-edit-btn').prop('disabled', false);

            // Новое: если пользователь изменил хоть одно поле — включаем кнопку "Сохранить"
            $editForm.find('input,select,textarea').off('change.enableSave input.enableSave')
                .on('change.enableSave input.enableSave', function() {
                    $("#saveButton").prop("disabled", false);
                });

            // Новое: формирование ссылок на прикреплённые файлы
            var fileFields = {
                execution_order_file: 'Файл заказа',
                measurements_file: 'Замеры',
                final_collage: 'Коллаж финал',
                final_project_file: 'Финальный проект',
                work_act: 'Акт выполненных работ',
                chat_screenshot: 'Скрин чата',
                archicad_file: 'Файл архикад',
                contract_attachment: 'Приложение договора'
            };
            var filesHtml = "";
            $.each(fileFields, function(field, label) {
                if (dealData[field] && dealData[field] !== "") {
                    filesHtml += '<p><a href="/storage/' + dealData[field] +
                        '" target="_blank">' + label + '</a></p>';
                }
            });
            $('#attachedFilesContainer').html(filesHtml ? filesHtml :
            '<p>Нет прикрепленных файлов</p>');

            // Новое: обновление ссылки под каждым file-полем
            var fileInputFields = {
                measurements_file: 'measurementsFileName',
                final_collage: 'finalCollageFileName',
                final_project_file: 'finalProjectFileName',
                work_act: 'workActFileName',
                chat_screenshot: 'chatScreenshotFileName',
                archicad_file: 'archicadFileName',
                contract_attachment: 'contractAttachmentFileName',
                avatar: 'avatarFileName'
            };
            $.each(fileInputFields, function(fileKey, linkId) {
                if (dealData[fileKey] && dealData[fileKey] !== "") {
                    $('#' + linkId)
                        .text('Просмотр файла')
                        .attr('href', '/storage/' + dealData[fileKey])
                        .show();
                } else {
                    $('#' + linkId).hide();
                }
            });

            // AJAX‑запрос для загрузки ленты комментариев по ID дела
            $.ajax({
                url: "{{ url('/deal') }}/" + dealData.id + "/feeds",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    var feedHtml = '';
                    if (response.length > 0) {
                        $.each(response, function(index, feed) {
                            feedHtml += '<div class="feed-post">' +
                                '<div class="feed-post-avatar">' +
                                '<img src="' + (feed.avatar_url ? feed.avatar_url :
                                    '/storage/default-avatar.png') + '" alt="' +
                                feed.user_name + '">' +
                                '</div>' +
                                '<div class="feed-post-text">' +
                                '<div class="feed-author">' + feed.user_name +
                                '</div>' +
                                '<div class="feed-content">' + feed.content +
                                '</div>' +
                                '<div class="feed-date">' + feed.date + '</div>' +
                                '</div>' +
                                '</div>';
                        });
                    } else {
                        feedHtml = '<p>Нет записей в ленте</p>';
                    }
                    $('#feed-posts-container').html(feedHtml);
                },
                error: function() {
                    $('#feed-posts-container').html('<p>Ошибка загрузки ленты</p>');
                }
            });

            // Обновляем ссылку на логи для сделки
            if (dealData.id) {
                $("#editModal .button__points li a.btn-info").attr("href", "/deal/" + dealData.id +
                    "/change-logs");
            }
        });

        $('#closeModalBtn').on('click', function() {
            $editModal.removeClass('show').hide();
        });

        $editModal.on('click', function(e) {
            if (e.target === this) $editModal.removeClass('show').hide();
        });

        // Переключение режима редактирования (блокировка/разблокировка полей)
        $('.toggle-edit-btn').on('click', function() {
            var disabled = $editForm.find('#nameField').prop('disabled');
            $editForm.find('input,select,textarea,button[type="submit"]').prop('disabled', !disabled);
            $(this).text(disabled ? 'Отменить' : 'Изменить');
        });

        // Переключение модулей в модальном окне
        var modules = $("#editModal fieldset.module__deal");
        var buttons = $("#editModal .button__points button");
        modules.css({
            display: "none",
            opacity: "0",
            transition: "opacity 0.3s ease-in-out"
        });
        if (modules.length > 0) {
            $(modules[0]).css({
                display: "flex"
            });
            setTimeout(function() {
                $(modules[0]).css({
                    opacity: "1"
                });
            }, 10);
        }
        buttons.on('click', function() {
            var targetText = $(this).data('target').trim();
            buttons.removeClass("buttonSealaActive");
            $(this).addClass("buttonSealaActive");
            modules.css({
                opacity: "0"
            });
            setTimeout(function() {
                modules.css({
                    display: "none"
                });
            }, 300);
            setTimeout(function() {
                modules.each(function() {
                    var legend = $(this).find("legend").text().trim();
                    if (legend === targetText) {
                        $(this).css({
                            display: "flex"
                        });
                        setTimeout(function() {
                            $(this).css({
                                opacity: "1"
                            });
                        }.bind(this), 10);
                    }
                });
            }, 300);
        });

        // Инициализация Select2 для городов с указанием dropdownParent для модального окна
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
                allowClear: true,
                dropdownParent: $('#editModal')
            });
        }).fail(function(err) {
            console.error("Ошибка загрузки городов", err);
        });

        // Инициализация дополнительных Select2 с dropdownParent
        $('#responsiblesField').select2({
            placeholder: "Выберите ответственных",
            allowClear: true,
            dropdownParent: $('#editModal')
        });
        $('.select2-field').select2({
            width: '100%',
            placeholder: "Выберите значение",
            allowClear: true,
            dropdownParent: $('#editModal')
        });

        // Обработка отправки формы "Лента"
        $("#feed-form").on("submit", function(e) {
            e.preventDefault();
            var content = $("#feed-content").val().trim();
            if (!content) {
                alert("Введите текст сообщения!");
                return;
            }
            var dealId = $("#dealIdField").val();
            if (dealId) {
                $.ajax({
                    url: "{{ url('/deal') }}/" + dealId + "/feed",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        content: content
                    },
                    success: function(response) {
                        $("#feed-content").val("");
                        var avatarUrl = response.avatar_url ? response.avatar_url :
                            "/storage/default-avatar.png";
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
                    error: function(xhr) {
                        alert("Ошибка при добавлении записи: " + xhr.responseText);
                    }
                });
            } else {
                alert("Не удалось определить сделку. Пожалуйста, обновите страницу.");
            }
        });

        // При выборе файла, обновляем текст и href соответствующего элемента
        $('input[type="file"]').on('change', function() {
            var file = this.files[0];
            var fileName = file ? file.name : "";
            var linkId = $(this).attr('id') + "FileName";
            if (fileName) {
                $('#' + linkId)
                    .text(fileName)
                    .attr('href', URL.createObjectURL(file))
                    .show();
            } else {
                $('#' + linkId).hide();
            }
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const linkElement = document.querySelector(
            'a[href="{{ isset($deal) && $deal->link ? url($deal->link) : '#' }}"]');

        if (linkElement) {
            linkElement.addEventListener('click', function(event) {
                event.preventDefault();
                const link = linkElement.getAttribute('href');
                if (link !== '#') {
                    navigator.clipboard.writeText(link).then(function() {
                        alert('Ссылка скопирована: ' + link);
                    }, function(err) {
                        console.error('Ошибка копирования: ', err);
                    });
                }
            });
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("a.copy-link").forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                var url = this.getAttribute("href");
                if (url !== "#") {
                    navigator.clipboard.writeText(url)
                        .then(function() {
                            alert("Ссылка скопирована: " + url);
                        })
                        .catch(function(err) {
                            console.error("Ошибка копирования: ", err);
                        });
                }
            });
        });
    });
</script>
