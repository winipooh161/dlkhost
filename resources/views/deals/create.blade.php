<h1>{{ $title_site }}</h1>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="create-deal-form" action="{{ route('deals.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- БЛОК: Основная информация -->
    <fieldset class="module">
        <legend>Информация о сделке</legend>
        <div class="form-group-deal">
            <label for="project_number">№ проекта (пример: Проект 6303): <span class="required">*</span></label>
            <input type="text" id="project_number" name="project_number" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="status">Статус: <span class="required">*</span></label>
            <select id="status" name="status" class="form-control" required>
                <option value="">-- Выберите статус --</option>
                <option value="Ждем ТЗ">Ждем ТЗ</option>
                <option value="Планировка">Планировка</option>
                <option value="Коллажи">Коллажи</option>
                <option value="Визуализация">Визуализация</option>
                <option value="Рабочка/сбор ИП">Рабочка/сбор ИП</option>
                <option value="Проект готов">Проект готов</option>
                <option value="Проект завершен">Проект завершен</option>
                <option value="Проект на паузе">Проект на паузе</option>
                <option value="Возврат">Возврат</option>
                <option value="В работе">В работе</option>
                <option value="Завершенный">Завершенный</option>
                <option value="На потом">На потом</option>
                <option value="Регистрация">Регистрация</option>
                <option value="Бриф прикриплен">Бриф прикриплен</option>
                <option value="Поддержка">Поддержка</option>
                <option value="Активный">Активный</option>
            </select>
        </div>
        <div class="form-group-deal">
            <label for="price_service_option">Услуга по прайсу: <span class="required">*</span></label>
            <select id="price_service_option" name="price_service_option" class="form-control" required>
                <option value="">-- Выберите услугу --</option>
                <option value="экспресс планировка">Экспресс планировка</option>
                <option value="экспресс планировка с коллажами">Экспресс планировка с коллажами</option>
                <option value="экспресс проект с электрикой">Экспресс проект с электрикой</option>
                <option value="экспресс планировка с электрикой и коллажами">Экспресс планировка с электрикой и коллажами</option>
                <option value="экспресс проект с электрикой и визуализацией">Экспресс проект с электрикой и визуализацией</option>
                <option value="экспресс рабочий проект">Экспресс рабочий проект</option>
                <option value="экспресс эскизный проект с рабочей документацией">Экспресс эскизный проект с рабочей документацией</option>
                <option value="экспресс 3Dвизуализация">Экспресс 3Dвизуализация</option>
                <option value="экспресс полный дизайн-проект">Экспресс полный дизайн-проект</option>
                <option value="360 градусов">360 градусов</option>
            </select>
        </div>  
        <div class="form-group-deal">
            <label for="rooms_count_pricing">Количество комнат по прайсу:</label>
            <input type="number" id="rooms_count_pricing" name="rooms_count_pricing" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="execution_order_comment">Комментарий к Заказу для отдела исполнения:</label>
            <textarea id="execution_order_comment" name="execution_order_comment" class="form-control" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="form-group-deal">
            <label for="execution_order_file">Прикрепить файл (PDF, JPG, PNG):</label>
            <input type="file" id="execution_order_file" name="execution_order_file" accept=".pdf,image/*">
        </div>
        <div class="form-group-deal">
            <label for="package">Пакет (1, 2 или 3): <span class="required">*</span></label>
            <input type="text" id="package" name="package" class="form-control" required>
        </div>
        <div class="form-group-deal">
            <label for="name">ФИО клиента: <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="form-group-deal">
            <label for="client_phone">Телефон: <span class="required">*</span></label>
            <input type="text" id="client_phone" name="client_phone"  class="form-control maskphone" required>
        </div>
        <div class="form-group-deal">
            <label for="client_timezone">Город/часовой пояс:</label>
            <select id="client_timezone" name="client_timezone" class="form-control">
                 <option value="">-- Выберите город --</option>
            </select>
        </div>
        <div class="form-group-deal">
            <label for="start_date">Дата начала проекта:</label>
            <!-- Значение устанавливается автоматически, поле недоступно -->
            <input type="date" id="start_date" name="start_date" readonly>
        </div>
        <div class="form-group-deal">
            <label for="project_duration">Общий срок проекта (в днях):</label>
            <!-- Поле редактируемое -->
            <input type="number" id="project_duration" name="project_duration">
        </div>
        <div class="form-group-deal">
            <label for="project_end_date">Дата завершения проекта:</label>
            <!-- Вычисляемое поле, недоступное для редактирования -->
            <input type="date" id="project_end_date" name="project_end_date" readonly>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function(){
                // Автоматически устанавливаем сегодняшнюю дату в поле "Дата начала проекта"
                var today = new Date().toISOString().split("T")[0];
                document.getElementById("start_date").value = today;
                
                // При изменении срока проекта обновляем "Дата завершения проекта"
                document.getElementById("project_duration").addEventListener("input", function(){
                     var duration = parseInt(this.value, 10);
                     if (!isNaN(duration)) {
                         var startDate = new Date(document.getElementById("start_date").value);
                         startDate.setDate(startDate.getDate() + duration);
                         var endDate = startDate.toISOString().split("T")[0];
                         document.getElementById("project_end_date").value = endDate;
                     } else {
                         document.getElementById("project_end_date").value = "";
                     }
                });
            });
        </script>
        <div class="form-group-deal">
            <label for="office_partner_id">Офис/Партнер:</label>
            <select id="office_partner_id" name="office_partner_id" class="form-control">
                <option value="">-- Не выбрано --</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group-deal">
            <label for="completion_responsible">Кто делает комплектацию:</label>
            <input type="text" id="completion_responsible" name="completion_responsible" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="coordinator_id">Отв. координатор:</label>
            <select id="coordinator_id" name="coordinator_id" class="form-control">
                <option value="">-- Не выбрано (по умолчанию текущий пользователь) --</option>
                @foreach($coordinators as $coord)
                    <option value="{{ $coord->id }}">{{ $coord->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group-deal">
            <label for="total_sum">Общая сумма:</label>
            <input type="number" step="0.01" id="total_sum" name="total_sum" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="measuring_cost">Стоимость замеров:</label>
            <input type="number" step="0.01" id="measuring_cost" name="measuring_cost" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="project_budget">Бюджет проекта:</label>
            <input type="number" step="0.01" id="project_budget" name="project_budget" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="client_info">Информация о клиенте:</label>
            <textarea id="client_info" name="client_info" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group-deal">
            <label for="payment_date">Дата оплаты:</label>
            <input type="date" id="payment_date" name="payment_date" class="form-control">
        </div>
        <div class="form-group-deal">
            <label for="execution_comment">Комментарий (исполнение):</label>
            <textarea id="execution_comment" name="execution_comment" class="form-control" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="form-group-deal">
            <label for="comment">Общий комментарий:</label>
            <textarea id="comment" name="comment" class="form-control" rows="3" maxlength="1000"></textarea>
        </div>

    </fieldset>

    <fieldset class="module">
        <legend>Аватар сделки</legend>
        <div class="form-group-deal">
            <div class="upload__files">
                <h6>Загру (не более 25 МБ суммарно):</h6>
                <div id="drop-zone">
                    <p id="drop-zone-text">Перетащите файл сюда или нажмите, чтобы выбрать</p>
                    <input id="fileInput" type="file" name="avatar" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png,.heic,.heif">
                </div>
                <p class="error-message" style="color: red;"></p>
                <small>Допустимые форматы: .pdf, .xlsx, .xls, .doc, .docx, .jpg, .jpeg, .png, .heic, .heif</small><br>
                <small>Максимальный суммарный размер: 25 МБ</small>
            </div>
            <style>
                .upload__files {
                    margin: 20px 0;
                    font-family: Arial, sans-serif;
                }
                /* Стилизация области перетаскивания */
                #drop-zone {
                    border: 2px dashed #ccc;
                    border-radius: 6px;
                    padding: 30px;
                    text-align: center;
                    cursor: pointer;
                    position: relative;
                    transition: background-color 0.3s ease;
                }
                #drop-zone.dragover {
                    background-color: #f0f8ff;
                    border-color: #007bff;
                }
                #drop-zone p {
                    margin: 0;
                    font-size: 16px;
                    color: #666;
                }
                /* Скрываем нативное поле выбора файлов, но оставляем его доступным */
                #fileInput {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    cursor: pointer;
                }
            </style>
            
            <script>
                const dropZone = document.getElementById('drop-zone');
                const fileInput = document.getElementById('fileInput');
                const dropZoneText = document.getElementById('drop-zone-text');
            
                // Функция обновления текста в drop zone
                function updateDropZoneText() {
                    const files = fileInput.files;
                    if (files && files.length > 0) {
                        const names = [];
                        for (let i = 0; i < files.length; i++) {
                            names.push(files[i].name);
                        }
                        dropZoneText.textContent = names.join(', ');
                    } else {
                        dropZoneText.textContent = "Перетащите файлы сюда или нажмите, чтобы выбрать";
                    }
                }
            
                // Предотвращаем поведение по умолчанию для событий drag-and-drop
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });
            
                // Добавляем класс при перетаскивании
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.add('dragover');
                    }, false);
                });
            
                // Удаляем класс, когда файлы покидают область или сброшены
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.remove('dragover');
                    }, false);
                });
            
                // Обработка события сброса (drop)
                dropZone.addEventListener('drop', function(e) {
                    let files = e.dataTransfer.files;
                    fileInput.files = files;
                    updateDropZoneText();
                });
            
                // При изменении поля выбора файлов обновляем текст
                fileInput.addEventListener('change', function() {
                    updateDropZoneText();
                });
            </script>
        </div>
        <div id="avatar-preview" class="avatar-preview"></div>
    </fieldset>
    
    <button type="submit" class="btn btn-primary">Создать сделку</button>
</form>

<!-- Подключение необходимых библиотек (jQuery и Select2) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Укажите корректный путь к вашему JSON-файлу
    var jsonFilePath = '/cities.json';

    // Загружаем JSON-файл
    $.getJSON(jsonFilePath, function(data) {
        // Группируем города по региону
        var groupedOptions = {};
        data.forEach(function(item) {
            var region = item.region;
            var city = item.city;
            if (!groupedOptions[region]) {
                groupedOptions[region] = [];
            }
            // Форматируем данные для Select2
            groupedOptions[region].push({
                id: city,
                text: city
            });
        });

        // Преобразуем сгруппированные данные в массив для Select2
        var select2Data = [];
        for (var region in groupedOptions) {
            select2Data.push({
                text: region,
                children: groupedOptions[region]
            });
        }

        // Инициализируем Select2 с полученными данными
        $('#client_timezone').select2({
            data: select2Data,
            placeholder: "-- Выберите город --",
            allowClear: true
        });
    })
    .fail(function(jqxhr, textStatus, error) {
        console.error("Ошибка загрузки JSON файла: " + textStatus + ", " + error);
    });
});

// Маска для поля "№ проекта"
$("input.maskproject").on("input", function() {
    var value = this.value;
    if (!value.startsWith("Проект ")) {
        value = "Проект " + value.replace(/[^0-9]/g, "");
    } else {
        var digits = value.substring(7).replace(/[^0-9]/g, "");
        digits = digits.substring(0, 4);
        value = "Проект " + digits;
    }
    this.value = value;
});
document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll("input.maskphone");
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        input.addEventListener("input", mask);
        input.addEventListener("focus", mask);
        input.addEventListener("blur", mask);
    }
    function mask(event) {
        var blank = "+_ (___) ___-__-__";
        var i = 0;
        var val = this.value.replace(/\D/g, "").replace(/^8/, "7").replace(/^9/, "79");
        this.value = blank.replace(/./g, function (char) {
            if (/[_\d]/.test(char) && i < val.length) return val.charAt(i++);
            return i >= val.length ? "" : char;
        });
        if (event.type == "blur") {
            if (this.value.length == 2) this.value = "";
        } else {
            setCursorPosition(this, this.value.length);
        }
    }
    function setCursorPosition(elem, pos) {
        elem.focus();
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
            return;
        }
        if (elem.createTextRange) {
            var range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd("character", pos);
            range.moveStart("character", pos);
            range.select();
            return;
        }
    }
});
// Маска для поля "Пакет": разрешаем только одну цифру
$("#package").on("input", function() {
    var val = this.value.replace(/\D/g, "");
    if(val.length > 1) { val = val.substring(0, 1); }
    this.value = val;
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var formChanged = false;
        var form = document.getElementById("create-deal-form");
    
        // Отслеживаем изменения в форме
        form.addEventListener("input", function () {
            formChanged = true;
        });
    
        // Предупреждение при попытке закрытия вкладки или перезагрузки страницы
        window.addEventListener("beforeunload", function (event) {
            if (formChanged) {
                event.preventDefault();
                event.returnValue = "Вы уверены, что хотите покинуть страницу? Все несохраненные данные будут потеряны.";
            }
        });
    
        // Убираем предупреждение при отправке формы (если пользователь сохраняет данные)
        form.addEventListener("submit", function () {
            formChanged = false;
        });
    });
</script>
