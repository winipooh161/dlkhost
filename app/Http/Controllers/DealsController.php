<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Chat;
use App\Models\User;
use App\Models\DealChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Str;

class DealsController extends Controller
{
    public function __construct()
    {
        // При необходимости добавьте middleware для аутентификации
    }

    /**
     * Отображение списка сделок.
     * В выборку включаются только те сделки, к которым привязан текущий пользователь.
     */
    public function dealCardinator(Request $request)
    {
        $title_site = "Сделки | Личный кабинет Экспресс-дизайн";
        $user = Auth::user();
        
        // Получение параметров фильтрации
        $search = $request->input('search');
        $status = $request->input('status');
        $view_type = $request->input('view_type', 'blocks'); // блоки или таблица
        $viewType = $view_type; // Переименовываем для использования в представлении
        
        // Базовый запрос
        $query = Deal::query();
        
        // Добавляем условие для пользователей
        if ($user->status !== 'admin' && $user->status !== 'coordinator') {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        
        // Применяем поиск, если есть
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('client_phone', 'LIKE', "%{$search}%")
                  ->orWhere('client_email', 'LIKE', "%{$search}%")
                  ->orWhere('project_number', 'LIKE', "%{$search}%")
                  ->orWhere('package', 'LIKE', "%{$search}%")
                  ->orWhere('deal_note', 'LIKE', "%{$search}%")
                  ->orWhere('client_city', 'LIKE', "%{$search}%")
                  ->orWhere('total_sum', 'LIKE', "%{$search}%");
            });
        }
        
        // Применяем фильтр по статусу, если есть
        if ($status && $status !== 'null') {
            $query->where('status', $status);
        }
        
        // Получаем сделки
        $deals = $query->get();
        
        // Здесь `$deal` не определена, но мы используем её в представлении
        // Поэтому нам нужно или предотвратить использование, или установить значение по умолчанию
        $deal = null; // Устанавливаем значение по умолчанию
        
        return view('cardinators', compact('deals', 'title_site', 'search', 'status', 'viewType', 'deal'));
    }
    
    /**
     * Отображение информации о сделках для клиента.
     * Сделки выбираются по связи: только те, к которым привязан пользователь.
     */
    public function dealUser()
    {
        $user = Auth::user();

        // Если пользователь – партнер, перенаправляем на другой маршрут
        if ($user->status === 'partner') {
            return redirect()->route('deal.cardinator');
        }

        $title_site = "Информация о сделке";
        $userDeals = Deal::with('coordinator', 'users', 'briefs')
            ->whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

        // Для каждой сделки проверяем и создаём групповой чат, если его ещё нет
        foreach ($userDeals as $deal) {
            $groupChat = Chat::where('deal_id', $deal->id)
                ->where('type', 'group')
                ->first();

            if (!$groupChat) {
                // Собираем ID всех пользователей, привязанных к сделке
                $responsibleIds = $deal->users->pluck('id')->toArray();

                // Если создатель сделки ещё не входит, добавляем его
                if (!in_array($deal->user_id, $responsibleIds)) {
                    $responsibleIds[] = $deal->user_id;
                }

                // Создаём групповой чат для сделки
                $groupChat = Chat::create([
                    'name'    => "Групповой чат сделки: {$deal->name}",
                    'type'    => 'group',
                    'deal_id' => $deal->id,
                ]);
                // Прикрепляем участников к чату
                $groupChat->users()->attach($responsibleIds);
            }
            // Добавляем объект чата в модель сделки для удобства отображения в шаблоне
            $deal->groupChat = $groupChat;
        }

        return view('user', compact('title_site', 'user', 'userDeals'));
    }
    
    /**
     * Форма создания сделки – доступна для координатора, администратора и партнёра.
     */
    public function createDeal()
    {
        $user = Auth::user();
        if (!in_array($user->status, ['coordinator', 'admin'])) {
            return redirect()->route('deal.cardinator')
                ->with('error', 'Только кардинатор, администратор или партнер могут создавать сделку.');
        }
        $title_site = "Создание сделки";

        $citiesFile = public_path('cities.json');
        if (file_exists($citiesFile)) {
            $citiesJson = file_get_contents($citiesFile);
            $russianCities = json_decode($citiesJson, true);
        } else {
            $russianCities = [];
        }

        $responsibleUsers = User::whereIn('status', ['designer', 'coordinator'])->get();
        $coordinators = User::where('status', 'coordinator')->get();
        $partners = User::where('status', 'partner')->get();
        $architects = User::where('status', 'architect')->get();
        $designers   = User::where('status', 'designer')->get();
        $visualizers = User::where('status', 'visualizer')->get();

        return view('create_deal', compact(
            'title_site',
            'user',
            'responsibleUsers',
            'coordinators',
            'partners',
            'architects',
            'designers',
            'visualizers',
            'russianCities'
        ));
    }

    /**
     * Сохранение сделки с автоматическим созданием группового чата для ответственных.
     */
    public function storeDeal(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->status, ['coordinator', 'admin'])) {
            return redirect()->route('deal.cardinator')
                ->with('error', 'Только кардинатор или администратор могут создавать сделку.');
        }

        try {
            $validated = $request->validate([
                'name'         => 'required|string|max:255',
                'client_phone' => ['required', 'regex:/^\+7\s\(\д{3}\)\с\d{3}\-\д{2}\-\д{2}$/'],
                'status'       => 'required|in:Ждем ТЗ,Планировка,Коллажи,Визуализация,Рабочка/сбор ИП,Проект готов,Проект завершен,Проект на паузе,Возврат,В работе,Завершенный,На потом,Регистрация,Бриф прикриплен,Поддержка,Активный',
                'priority'     => 'required|in:высокий,средний,низкий',
                'package'      => 'required|string|max:255',
                'project_number'        => 'nullable|string|max:21',
                'price_service_option'  => 'required|string|max:255',
                'rooms_count_pricing'   => 'nullable|integer|min:1',
                'execution_order_comment' => 'nullable|string|max:1000',
                'execution_order_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'office_partner_id'     => 'nullable|exists:users,id',
                'coordinator_id'        => 'nullable|exists:users,id',
                'total_sum'      => 'nullable|numeric',
                'measuring_cost' => 'nullable|numeric',
                'project_budget' => 'nullable|numeric',
                'client_info'    => 'nullable|string',
                'payment_date'   => 'nullable|date',
                'execution_comment' => 'nullable|string',
                'comment'        => 'nullable|string',
                'office_equipment' => 'nullable|boolean',
                'measurement_comments' => 'nullable|string|max:1000',
                'measurements_file'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,dwg|max:5120',
                'start_date'           => 'nullable|date',
                'project_duration'     => 'nullable|integer',
                'project_end_date'     => 'nullable|date',
                'architect_id'         => 'nullable|exists:users,id',
                'final_floorplan'      => 'nullable|file|mimes:pdf|max:20480',
                'designer_id'          => 'nullable|exists:users,id',
                'final_collage'        => 'nullable|file|mimes:pdf|max:204800',
                'visualizer_id'        => 'nullable|exists:users,id',
                'visualization_link'   => 'nullable|url',
                'final_project_file'   => 'nullable|file|mimes:pdf|max:204800',
                'work_act'             => 'nullable|file|mimes:pdf|max:10240',
                'client_project_rating'=> 'nullable|numeric',
                'architect_rating_client' => 'nullable|numeric',
                'architect_rating_partner' => 'nullable|numeric',
                'architect_rating_coordinator' => 'nullable|numeric',
                'designer_rating_client'    => 'nullable|numeric',
                'designer_rating_partner'   => 'nullable|numeric',
                'designer_rating_coordinator' => 'nullable|numeric',
                'visualizer_rating_client'  => 'nullable|numeric',
                'visualizer_rating_partner' => 'nullable|numeric',
                'visualizer_rating_coordinator' => 'nullable|numeric',
                'coordinator_rating_client'  => 'nullable|numeric',
                'coordinator_rating_partner' => 'nullable|numeric',
                'coordinator_comment'        => 'nullable|string',
                'chat_screenshot'        => 'nullable|image|mimes:jpeg,jpg,image/png|max:5120',
                'archicad_file'          => 'nullable|file|mimes:pln,dwg|max:307200',
                'contract_number'   => 'required|string|max:100',
                'contract_attachment' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120',
                'deal_note'         => 'nullable|string',
                'avatar'            => 'required|image|mimes:jpg,jpeg,png,gif|max:5120',
                'responsibles'      => 'nullable|array',
                'responsibles.*'    => 'exists:users,id',
            ]);

            $office_equipment = $request->has('office_equipment') ? 1 : 0;
            $coordinatorId = $validated['coordinator_id'] ?? auth()->id();

            $deal = Deal::create([
                'name'         => $validated['name'],
                'client_phone' => $validated['client_phone'],
                'status'       => $validated['status'],
                'priority'     => $validated['priority'],
                'package'      => $validated['package'],
                'client_name'  => $validated['name'],
                'project_number' => $validated['project_number'] ?? null,
                'price_service' => $validated['price_service_option'],
                'rooms_count_pricing' => $validated['rooms_count_pricing'] ?? null,
                'execution_order_comment' => $validated['execution_order_comment'] ?? null,
                'office_partner_id' => $validated['office_partner_id'] ?? null,
                'coordinator_id' => $coordinatorId,
                'total_sum' => $validated['total_sum'] ?? null,
                'measuring_cost' => $validated['measuring_cost'] ?? null,
                'project_budget' => $validated['project_budget'] ?? null,
                'client_info' => $validated['client_info'] ?? null,
                'payment_date' => $validated['payment_date'] ?? null,
                'execution_comment' => $validated['execution_comment'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'office_equipment' => $office_equipment,
                'measurement_comments' => $validated['measurement_comments'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'project_duration' => $validated['project_duration'] ?? null,
                'project_end_date' => $validated['project_end_date'] ?? null,
                'architect_id' => $validated['architect_id'] ?? null,
                'designer_id' => $validated['designer_id'] ?? null,
                'visualizer_id' => $validated['visualizer_id'] ?? null,
                'visualization_link' => $validated['visualization_link'] ?? null,
                'final_floorplan' => null,
                'final_collage' => null,
                'final_project_file' => null,
                'client_project_rating' => $validated['client_project_rating'] ?? null,
                'architect_rating_client' => $validated['architect_rating_client'] ?? null,
                'architect_rating_partner' => $validated['architect_rating_partner'] ?? null,
                'architect_rating_coordinator' => $validated['architect_rating_coordinator'] ?? null,
                'designer_rating_client' => $validated['designer_rating_client'] ?? null,
                'designer_rating_partner' => $validated['designer_rating_partner'] ?? null,
                'designer_rating_coordinator' => $validated['designer_rating_coordinator'] ?? null,
                'visualizer_rating_client' => $validated['visualizer_rating_client'] ?? null,
                'visualizer_rating_partner' => $validated['visualizer_rating_partner'] ?? null,
                'visualizer_rating_coordinator' => $validated['visualizer_rating_coordinator'] ?? null,
                'coordinator_rating_client' => $validated['coordinator_rating_client'] ?? null,
                'coordinator_rating_partner' => $validated['coordinator_rating_partner'] ?? null,
                'coordinator_comment' => $validated['coordinator_comment'] ?? null,
                'contract_number' => $validated['contract_number'],
                'deal_note' => $validated['deal_note'] ?? null,
                'user_id' => auth()->id(),
                'registration_token' => Str::random(32),
                'registration_token_expiry' => now()->addDays(7),
            ]);

            // Загрузка файлов
            $fileFields = [
                'avatar',
                'execution_order_file',
                'measurements_file',
                'final_floorplan',
                'final_collage',
                'final_project_file',
                'work_act',
                'chat_screenshot',
                'archicad_file',
                'contract_attachment',
            ];

            foreach ($fileFields as $field) {
                $uploadData = $this->handleFileUpload($request, $deal, $field, $field === 'avatar' ? 'avatar_path' : $field);
                if (!empty($uploadData)) {
                    $deal->update($uploadData);
                }
            }

            $responsibles = $request->input('responsibles', []);
            $validResponsibles = User::whereIn('id', $responsibles)->pluck('id')->toArray();
            if (!in_array(auth()->id(), $validResponsibles)) {
                $validResponsibles[] = auth()->id();
            }
            $deal->users()->attach([auth()->id() => ['role' => 'coordinator']]);
            foreach ($validResponsibles as $respId) {
                if ($respId != auth()->id()) {
                    $deal->users()->attach([$respId => ['role' => 'responsible']]);
                }
            }

            // Создаем групповой чат для сделки
            $allResponsibleIds = $responsibles;
            if (!in_array(auth()->id(), $allResponsibleIds)) {
                $allResponsibleIds[] = auth()->id();
            }
            $this->createGroupChatForDeal($deal, $allResponsibleIds);

            $this->sendSmsNotification($deal, $deal->registration_token);

            return redirect()->route('deal.cardinator')->with('success', 'Сделка успешно создана.');
        } catch (\Exception $e) {
            Log::error("Ошибка при создании сделки: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка при создании сделки.');
        }
    }

    /**
     * Создание группового чата для сделки.
     */
    private function createGroupChatForDeal($deal, $userIds)
    {
        $chat = Chat::create([
            'name'    => "Групповой чат сделки: {$deal->name}",
            'type'    => 'group',
            'deal_id' => $deal->id,
        ]);
    
        $validUserIds = User::whereIn('id', $userIds)->pluck('id')->toArray();
        if (!in_array($deal->user_id, $validUserIds)) {
            $validUserIds[] = $deal->user_id;
        }
        $chat->users()->attach($validUserIds);
    }
    
    // Редактирование сделки (метод можно реализовать по аналогии)
    public function updateDeal(Request $request, $id)
    {
        try {
            $deal = Deal::with(['coordinator', 'responsibles'])->findOrFail($id);
            $original = $deal->getOriginal();

           
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'client_phone' => 'nullable|string', 
                'status' => 'nullable|in:Ждем ТЗ,Планировка,Коллажи,Визуализация,Рабочка/сбор ИП,Проект готов,Проект завершен,Проект на паузе,Возврат,В работе,Завершенный,На потом,Регистрация,Бриф прикриплен,Поддержка,Активный',
                'priority' => 'nullable|in:высокий,средний,низкий',
                'package' => 'nullable|string|max:255',
                'project_number' => 'nullable|string|max:21',
                'price_service_option' => 'nullable|string|max:255',
                'rooms_count_pricing' => 'nullable|integer|min:1',
                'execution_order_comment' => 'nullable|string|max:1000',
                'execution_order_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'office_partner_id' => 'nullable|exists:users,id',
                'coordinator_id' => 'nullable|exists:users,id',
                'total_sum' => 'nullable|numeric',
                'measuring_cost' => 'nullable|numeric',
                'project_budget' => 'nullable|numeric',
                'client_info' => 'nullable|string',
                'payment_date' => 'nullable|date',
                'execution_comment' => 'nullable|string',
                'comment' => 'nullable|string',
                'office_equipment' => 'nullable|boolean',
                'measurement_comments' => 'nullable|string|max:1000',
                'measurements_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,dwg|max:5120',
                'start_date' => 'nullable|date',
                'project_duration' => 'nullable|integer',
                'project_end_date' => 'nullable|date',
                'architect_id' => 'nullable|exists:users,id',
                'final_floorplan' => 'nullable|file|mimes:pdf|max:20480',
                'designer_id' => 'nullable|exists:users,id',
                'final_collage' => 'nullable|file|mimes:pdf|max:204800',
                'visualizer_id' => 'nullable|exists:users,id',
                'visualization_link' => 'nullable|url',
                'final_project_file' => 'nullable|file|mimes:pdf|max:204800',
                'work_act' => 'nullable|file|mimes:pdf|max:10240',
                'client_project_rating' => 'nullable|numeric',
                'architect_rating_client' => 'nullable|numeric',
                'architect_rating_partner' => 'nullable|numeric',
                'architect_rating_coordinator' => 'nullable|numeric',
                'designer_rating_client' => 'nullable|numeric',
                'designer_rating_partner' => 'nullable|numeric',
                'designer_rating_coordinator' => 'nullable|numeric',
                'visualizer_rating_client' => 'nullable|numeric',
                'visualizer_rating_partner' => 'nullable|numeric',
                'visualizer_rating_coordinator' => 'nullable|numeric',
                'coordinator_rating_client' => 'nullable|numeric',
                'coordinator_rating_partner' => 'nullable|numeric',
                'coordinator_comment' => 'nullable|string',
                'chat_screenshot' => 'nullable|image|mimes:jpeg,jpg,image/png|max:5120',
                'archicad_file' => 'nullable|file|mimes:pln,dwg|max:307200',
                'contract_number' => 'nullable|string|max:100',
                'contract_attachment' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120',
                'deal_note' => 'nullable|string',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
                'responsibles' => 'nullable|array',
                'responsibles.*' => 'exists:users,id',
            ]);

            // Убираем форматирование телефона
            $updateData = collect($validated)
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->map(function ($value, $key) use ($deal) {
                    return $value ?? $deal->$key;
                })
                ->toArray();

            // Обновление основных полей
            $deal->update($updateData);

            // Логирование изменений
            $this->logDealChanges($deal, $original, $deal->getAttributes());

            // Обработка файлов и связей
            $fileFields = [
                'avatar',
                'execution_order_file',
                'measurements_file',
                'final_floorplan',
                'final_collage',
                'final_project_file',
                'work_act',
                'chat_screenshot',
                'archicad_file',
                'contract_attachment',
            ];

            foreach ($fileFields as $field) {
                $uploadData = $this->handleFileUpload($request, $deal, $field, $field === 'avatar' ? 'avatar_path' : $field);
                if (!empty($uploadData)) {
                    $deal->update($uploadData);
                }
            }

            // Обновление связей с ответственными
            if ($request->has('responsibles')) {
                $responsibles = collect($request->input('responsibles'))->map(function($id) {
                    return ['role' => 'responsible'];
                })->toArray();
                
                $validResponsibles = User::whereIn('id', array_keys($responsibles))->pluck('id')->toArray();
                $responsibles = array_intersect_key($responsibles, array_flip($validResponsibles));
                $responsibles[Auth::id()] = ['role' => 'coordinator'];
                
                $deal->users()->sync($responsibles);
            }

            $this->sendSmsNotification($deal, $deal->registration_token);

            return redirect()->route('deal.cardinator')->with('success', 'Сделка успешно обновлена.');
        } catch (\Exception $e) {
            Log::error("Ошибка при обновлении сделки: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Ошибка при обновлении сделки.');
        }
    }

    /**
     * Логирование изменений сделки.
     */
    protected function logDealChanges($deal, $original, $new)
    {
        foreach (['updated_at', 'created_at'] as $key) {
            unset($original[$key], $new[$key]);
        }

        $changes = [];
        foreach ($new as $key => $newValue) {
            if (array_key_exists($key, $original) && $original[$key] != $newValue) {
                $changes[$key] = [
                    'old' => $original[$key],
                    'new' => $newValue,
                ];
            }
        }

        if (!empty($changes)) {
            DealChangeLog::create([
                'deal_id'   => $deal->id,
                'user_id'   => Auth::id(),
                'user_name' => Auth::user()->name,
                'changes'   => $changes,
            ]);
        }
    }

    /**
     * Вывод логов изменений по сделке.
     */
    public function changeLogsForDeal(Request $request, $dealId)
    {
        $deal = Deal::findOrFail($dealId);

        if (!in_array(Auth::user()->status, ['coordinator', 'admin'])) {
            return redirect()->back()->with('error', 'Доступ запрещён.');
        }

        $logs = DealChangeLog::where('deal_id', $deal->id)->orderBy('created_at', 'desc')->get();
        $title_site = "Аудит изменений сделки: " . $deal->name;

        return view('deal_change_logs', compact('logs', 'title_site', 'deal'));
    }

    /**
     * Вывод логов изменений по всем сделкам.
     */
    public function changeLogs(Request $request)
    {
        if (!in_array(Auth::user()->status, ['coordinator', 'admin'])) {
            return redirect()->back()->with('error', 'Доступ запрещён.');
        }

        $logs = DealChangeLog::orderBy('created_at', 'desc')->paginate(20);
        $title_site = "Аудит изменений сделок";

        return view('deal_change_logs', compact('logs', 'title_site'));
    }

    /**
     * Удаление просроченных сделок.
     */
    public function removeExpiredDeals()
    {
        Deal::where('registration_token_expiry', '<', now())->each(function($deal) {
            $deal->delete();
        });
        return redirect()->route('deal.cardinator')->with('success', 'Просроченные сделки удалены.');
    }

    /**
     * Отправка SMS-уведомления с регистрационной ссылкой.
     */
    private function sendSmsNotification($deal, $registrationToken)
    {
        if (!$registrationToken) {
            Log::error("Отсутствует регистрационный токен для сделки ID: {$deal->id}");
            throw new \Exception('Отсутствует регистрационный токен для сделки.');
        }
        $rawPhone = preg_replace('/\D/', '', $deal->client_phone);
        $registrationLinkUrl = route('register_by_deal', ['token' => $registrationToken]);
        $apiKey = '6CDCE0B0-6091-278C-5145-360657FF0F9B';
        $response = Http::get("https://sms.ru/sms/send", [
            'api_id'    => $apiKey,
            'to'        => $rawPhone,
            'msg'       => "Здравствуйте! Для регистрации пройдите по ссылке: $registrationLinkUrl",
            'partner_id'=> 1,
        ]);
        if ($response->failed()) {
            Log::error("Ошибка при отправке SMS для сделки ID: {$deal->id}. Ответ сервера: " . $response->body());
            throw new \Exception('Ошибка при отправке SMS.');
        }
    }

    /**
     * Обработка загрузки файлов.
     */
    private function handleFileUpload(Request $request, $deal, $field, $targetField = null)
    {
        if ($request->hasFile($field) && $request->file($field)->isValid()) {
            $dir = "dels/{$deal->id}";
            $extension = $request->file($field)->getClientOriginalExtension();
            $fileName = $field . '.' . $extension;
            $filePath = $request->file($field)->storeAs($dir, $fileName, 'public');
            return [$targetField ?? $field => $filePath];
        }
        return [];
    }
    public function showDealChat($dealId)
{
    // Загружаем сделку вместе с групповой беседой (если она существует)
    $deal = \App\Models\Deal::with('groupChat')->findOrFail($dealId);
    return view('deal_chat', compact('deal'));
}

public function editDeal($id)
{
    $deal = Deal::findOrFail($id);
    $user = Auth::user();

    if (!in_array($user->status, ['coordinator', 'admin'])) {
        return redirect()->route('deal.cardinator')
            ->with('error', 'Только координатор или администратор могут редактировать сделку.');
    }

    $title_site = "Редактирование сделки";

    $citiesFile = public_path('cities.json');
    if (file_exists($citiesFile)) {
        $citiesJson = file_get_contents($citiesFile);
        $russianCities = json_decode($citiesJson, true);
    } else {
        $russianCities = [];
    }

    $responsibleUsers = User::whereIn('status', ['designer', 'coordinator'])->get();
    $coordinators = User::where('status', 'coordinator')->get();
    $partners = User::where('status', 'partner')->get();
    $architects = User::where('status', 'architect')->get();
    $designers = User::where('status', 'designer')->get();
    $visualizers = User::where('status', 'visualizer')->get();

    return view('edit_deal', compact(
        'title_site',
        'user',
        'deal',
        'responsibleUsers',
        'coordinators',
        'partners',
        'architects',
        'designers',
        'visualizers',
        'russianCities'
    ));
}

}
