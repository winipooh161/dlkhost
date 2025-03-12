<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Chat;
use App\Models\MessagePinLog; // Добавляем импорт класса
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Str; // Добавляем импорт класса
use Illuminate\Support\Facades\Http; // Добавляем импорт класса
use Illuminate\Support\Facades\Cache; // Добавлено

class ChatController extends Controller
{
    // Новый приватный метод для получения личных чатов
    private function getPersonalChats($user)
    {
        $userId = $user->id;
        switch ($user->status) {
            case 'admin':
            case 'coordinator':
                return User::where('id', '<>', $userId)
                    ->where('status', '<>', 'user')
                    ->with(['chats' => fn($q)=> $q->where('type', 'personal')])
                    ->get();
            case 'support':
                return User::where('id', '<>', $userId)
                    ->with(['chats' => fn($q)=> $q->where('type', 'personal')])
                    ->get();
            case 'user':
                $relatedDealIds = $user->deals()->pluck('deals.id');
                return User::whereIn('status', ['support','coordinator'])
                    ->whereHas('deals', fn($q)=> $q->whereIn('deals.id', $relatedDealIds))
                    ->where('id', '<>', $userId)
                    ->with(['chats' => fn($q)=> $q->where('type', 'personal')])
                    ->get();
            default:
                return collect();
        }
    }

    /**
     * Отображает список чатов (личных и групповых), в которых участвует пользователь.
     */
    public function index()
    {
        $title_site = "Чаты | Личный кабинет Экспресс-дизайн";
        $user = Auth::user();
        $userId = $user->id;

        $chats = collect();

        // Личные чаты
        try {
            $personalUsers = $this->getPersonalChats($user);
            foreach ($personalUsers as $chatUser) {
                $unreadCount = Message::where('sender_id', $chatUser->id)
                    ->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->count();

                $lastMessage = Message::where(function ($query) use ($chatUser, $userId) {
                        $query->where('sender_id', $userId)
                              ->where('receiver_id', $chatUser->id);
                    })
                    ->orWhere(function ($query) use ($chatUser, $userId) {
                        $query->where('sender_id', $chatUser->id)
                              ->where('receiver_id', $userId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                $chats->push([
                    'id'                => $chatUser->id,
                    'type'              => 'personal',
                    'name'              => $chatUser->name,
                    'avatar_url'        => $chatUser->avatar_url,
                    'unread_count'      => $unreadCount,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                ]);
            }

            // Групповые чаты
            $groupChats = Chat::where('type', 'group')
                ->whereHas('users', function($query) use ($userId) {
                    $query->where('users.id', $userId);
                })
                ->with(['messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(50);
                }])
                ->get();

            foreach ($groupChats as $chat) {
                $pivot = $chat->users->find($userId)->pivot;
                $lastReadAt = $pivot->last_read_at ?? null;
                $unreadCount = $lastReadAt
                    ? $chat->messages->where('created_at', '>', $lastReadAt)->count()
                    : $chat->messages->count();
                $lastMessage = $chat->messages->first();
                
                // Проверка и установка аватара по умолчанию для групповых чатов
                $avatarUrl = $chat->avatar_url;
                if (empty($avatarUrl) || !file_exists(public_path($avatarUrl))) {
                    $avatarUrl = 'storage/avatars/group_default.svg';
                }

                $chats->push([
                    'id'                => $chat->id,
                    'type'              => 'group',
                    'name'              => $chat->name,
                    'avatar_url'        => $avatarUrl,
                    'unread_count'      => $unreadCount,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                ]);
            }
        } catch (\Exception $e) {
            // Улучшенное логирование
            Log::error('Ошибка при формировании списка чатов', ['error'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            return response()->json(['error' => 'Ошибка при формировании списка чатов.'], 500);
        }

        // Сортировка
        $sorted = $chats->sortByDesc(function ($chat) {
            return $chat['unread_count'] > 0 ? 1 : 0;
        })->sortByDesc('last_message_time')->values();

        return view('chats', compact('chats', 'user', 'title_site'));
    }

    /**
     * Загружает сообщения для выбранного чата (личного или группового).
     */
    public function chatMessages($type, $id)
    {
        $currentUserId = Auth::id();
        $perPage = 50;
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        try {
            if ($type === 'personal') {
                $recipient = User::findOrFail($id);
                $query = Message::with('sender') // eager loading для уменьшения количества запросов
                    ->where(function ($q) use ($recipient, $currentUserId) {
                        $q->where('sender_id', $currentUserId)
                          ->where('receiver_id', $recipient->id);
                    })
                    ->orWhere(function ($q) use ($recipient, $currentUserId) {
                        $q->where('sender_id', $recipient->id)
                          ->where('receiver_id', $currentUserId);
                    })
                    ->orWhere(function ($q) use ($recipient, $currentUserId) {
                        // Добавляем системные уведомления для этого чата
                        $q->where('message_type', 'notification')
                          ->where(function ($sq) use ($recipient, $currentUserId) {
                              $sq->where('sender_id', $currentUserId)
                                 ->where('receiver_id', $recipient->id)
                                 ->orWhere(function ($sq2) use ($recipient, $currentUserId) {
                                     $sq2->where('sender_id', $recipient->id)
                                         ->where('receiver_id', $currentUserId);
                                 });
                          });
                    });
            } elseif ($type === 'group') {
                $chat = Chat::where('type', 'group')->findOrFail($id);
                if (!$chat->users->contains($currentUserId)) {
                    $chat->users()->attach($currentUserId);
                }
                $query = Message::with('sender')->where('chat_id', $chat->id);
            } else {
                return response()->json(['error' => 'Неверный тип чата.'], 400);
            }

            $messages = $query->orderBy('created_at', 'desc')
                ->skip($offset)
                ->take($perPage)
                ->get()
                ->reverse();

            // Обновляем статус сообщений в зависимости от типа чата
            if ($type === 'personal') {
                Message::where('sender_id', $recipient->id)
                    ->where('receiver_id', $currentUserId)
                    ->whereNull('read_at')
                    ->update(['is_read' => true, 'read_at' => now()]);
            } elseif ($type === 'group') {
                Message::where('chat_id', $chat->id)
                    ->where('sender_id', '!=', $currentUserId)
                    ->whereNull('read_at')
                    ->update(['is_read' => true, 'read_at' => now()]);
            }

            $messages->each(function ($message) {
                $message->sender_name = optional($message->sender)->name ?? 'Unknown';
            });

            $formattedMessages = MessageResource::collection($messages);
            return response()->json([
                'current_user_id' => $currentUserId,
                'messages'        => $formattedMessages,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке сообщений: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка загрузки сообщений.'], 500);
        }
    }

    /**
     * Отправляет сообщение (текст или файл) в чат.
     */
    public function sendMessage(Request $request, $type, $id)
    {
        if (!in_array($type, ['personal', 'group'])) {
            return response()->json(['error' => 'Неверный тип чата.'], 400);
        }

        try {
            $validated = $request->validate([
                'message' => 'nullable|string|max:1000',
                'files.*' => 'nullable|file|max:10240', // Максимальный размер файла 10MB
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка валидации отправки сообщения: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка валидации.'], 422);
        }

        // Проверка на пустое сообщение и отсутствие файлов
        if (empty($validated['message']) && !$request->hasFile('files')) {
            return response()->json(['error' => 'Сообщение не может быть пустым.'], 400);
        }

        $currentUserId = Auth::id();
        $attachments = [];
        $messageType = 'text';

        try {
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                foreach ($files as $file) {
                    $chatFolder = $type === 'personal' ? "personal_chat_{$id}" : "group_chat_{$id}";
                    $fileName = time().'_'.$file->getClientOriginalName();
                    $filePathStored = $file->storeAs("uploads/{$chatFolder}", $fileName, 'public');
                    $url = asset('storage/'.$filePathStored);
                    $attachments[] = [
                        'url' => $url,
                        'original_file_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ];
                }
                $messageType = 'file';
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке файлов: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка загрузки файла.'], 500);
        }

        DB::beginTransaction();
        try {
            // Убедимся, что attachments всегда массив или null
            $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
            
            $sender = Auth::user();
            $messageContent = $validated['message'] ?? '';
            
            if ($type === 'personal') {
                $receiver = User::findOrFail($id);
                $message = Message::create([
                    'sender_id'    => $currentUserId,
                    'receiver_id'  => $receiver->id,
                    'chat_id'      => null,
                    'message'      => $messageContent,
                    'message_type' => $messageType,
                    'attachments'  => $attachmentsJson,
                ]);
                
                // Удаление отправки уведомлений через Firebase
            } elseif ($type === 'group') {
                $chat = Chat::where('type', 'group')->findOrFail($id);
                if (!$chat->users->contains($currentUserId)) {
                    $chat->users()->attach($currentUserId);
                }
                $message = Message::create([
                    'sender_id'    => $currentUserId,
                    'chat_id'      => $chat->id,
                    'message'      => $messageContent,
                    'message_type' => $messageType,
                    'attachments'  => $attachmentsJson,
                ]);
                
                // Удаление отправки уведомлений через Firebase
            }

            DB::commit();

            broadcast(new MessageSent($message))->toOthers();
            $message->sender_name = optional($message->sender)->name ?? 'Unknown';

            return response()->json(['message' => new MessageResource($message)], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при отправке сообщения: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Возвращает новые сообщения, отправленные после указанного ID.
     */
    public function getNewMessages(Request $request, $type, $id)
    {
        if (!in_array($type, ['personal', 'group'])) {
            return response()->json(['error' => 'Неверный тип чата.'], 400);
        }
        try {
            $validated = $request->validate([
                'last_message_id' => 'nullable|integer',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка валидации getNewMessages: ' . $е->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка валидации.'], 422);
        }
        $currentUserId = Auth::id();
        $lastMessageId = $validated['last_message_id'] ?? 0;

        try {
            if ($type === 'personal') {
                $otherUser = User::findOrFail($id);
                $query = Message::where(function ($q) use ($otherUser, $currentUserId) {
                        $q->where('sender_id', $currentUserId)
                          ->where('receiver_id', $otherUser->id);
                    })
                    ->orWhere(function ($q) use ($otherUser, $currentUserId) {
                        $q->where('sender_id', $otherUser->id)
                          ->where('receiver_id', $currentUserId);
                    });
            } elseif ($type === 'group') {
                $chat = Chat::where('type', 'group')->findOrFail($id);
                if (!$chat->users->contains($currentUserId)) {
                    $chat->users()->attach($currentUserId);
                }
                $query = Message::where('chat_id', $chat->id);
            }
            if ($lastMessageId) {
                $query->where('id', '>', $lastMessageId);
            }
            $newMessages = $query->orderBy('created_at', 'asc')
                ->with('sender')
                ->get();

            $newMessages->each(function ($message) {
                $message->sender_name = optional($message->sender)->name;
            });
        } catch (\Exception $e) {
            Log::error('Ошибка при получении новых сообщений: ' . $е->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка получения сообщений.'], 500);
        }

        return response()->json([
            'current_user_id' => $currentUserId,
            'messages'        => MessageResource::collection($newMessages),
        ], 200);
    }

    /**
     * Помечает сообщения как прочитанные в выбранном чате.
     */
    public function markMessagesAsRead($type, $id)
    {
        $currentUserId = Auth::id();

        try {
            if ($type === 'personal') {
                $otherUser = User::findOrFail($id);
                Message::where('sender_id', $otherUser->id)
                    ->where('receiver_id', $currentUserId)
                    ->where('is_read', false)
                    ->update(['is_read' => true, 'read_at' => now()]);
                event(new MessagesRead($id, $currentUserId, $type));
            } elseif ($type === 'group') {
                $chat = Chat::where('type', 'group')->findOrFail($id);
                if (!$chat->users->contains($currentUserId)) {
                    $chat->users()->attach($currentUserId);
                }
                Message::where('chat_id', $chat->id)
                    ->where('sender_id', '!=', $currentUserId)
                    ->where('is_read', false)
                    ->update(['is_read' => true, 'read_at' => now()]);
                $chat->users()->updateExistingPivot($currentUserId, ['last_read_at' => now()]);
                event(new MessagesRead($id, $currentUserId, $type));
            } else {
                return response()->json(['error' => 'Неверный тип чата.'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при пометке сообщений как прочитанных: ' . $е->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Внутренняя ошибка сервера.'], 500);
        }

        return response()->json(['success' => true], 200);
    }

     /**
     * Удаляет сообщение, если текущий пользователь является его отправителем 
     * или имеет права администратора/координатора.
     */
    public function deleteMessage(Request $request, $chatType, $chatId, $messageId)
    {
        $currentUserId = Auth::id();
        $message = Message::findOrFail($messageId);
        $user = Auth::user();

        // Запрещаем удаление системных уведомлений
        if ($message->message_type === 'notification' || $message->is_system) {
            return response()->json(['error' => 'Системные уведомления нельзя удалить.'], 403);
        }

        // Разрешаем удаление только автору сообщения или координатору
        if ($message->sender_id != $currentUserId && !in_array($user->status, ['coordinator', 'admin'])) {
            return response()->json(['error' => 'Доступ запрещён.'], 403);
        }

        try {
            $message->delete();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении сообщения: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка удаления сообщения.'], 500);
        }
    }

    /**
     * Закрепляет сообщение.
     */
    public function pinMessage(Request $request, $chatType, $chatId, $messageId)
    {
        $currentUserId = Auth::id();
        $message = Message::findOrFail($messageId);

        // Запрещаем закрепление системных уведомлений
        if ($message->message_type === 'notification' || $message->is_system) {
            return response()->json(['error' => 'Системные уведомления нельзя закрепить.'], 403);
        }
        
        try {
            $message->is_pinned = true;
            $message->save();
    
            // Создаем текст уведомления с превью сообщения
            $messagePreview = Str::limit($message->message, 50);
            $notificationText = '<div class="notification-message">
                <strong>' . Auth::user()->name . '</strong> закрепил сообщение: 
                "<a href="#message-' . $messageId . '" data-message-id="' . $messageId . '">' 
                . htmlspecialchars($messagePreview) . '</a>"
            </div>';
    
            // Создаем уведомление как новое сообщение
            $notification = Message::create([
                'sender_id' => $currentUserId,
                'chat_id' => $chatType === 'group' ? $chatId : null,
                'receiver_id' => $chatType === 'personal' ? $chatId : null,
                'message' => $notificationText,
                'message_type' => 'notification',
                'is_system' => true
            ]);
    
            // Отправляем уведомление через веб-сокет
            broadcast(new MessageSent($notification))->toOthers();
    
            return response()->json([
                'success' => true,
                'message' => new MessageResource($message)
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Ошибка при закреплении сообщения: ' . $e->getMessage(), [
                'chatType' => $chatType,
                'chatId' => $chatId,
                'messageId' => $messageId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка закрепления сообщения.'], 500);
        }
    }
    
    /**
     * Открепляет сообщение.
     */
    public function unpinMessage(Request $request, $chatType, $chatId, $messageId)
    {
        $currentUserId = Auth::id();
        $message = Message::findOrFail($messageId);
    
        try {
            $message->is_pinned = false;
            $message->save();
    
            // Создаем текст уведомления с превью сообщения
            $messagePreview = Str::limit($message->message, 50);
            $notificationText = '<div class="notification-message">
                <strong>' . Auth::user()->name . '</strong> открепил сообщение: 
                "<a href="#message-' . $messageId . '" data-message-id="' . $messageId . '">' 
                . htmlspecialchars($messagePreview) . '</a>"
            </div>';
    
            // Создаем уведомление как новое сообщение
            $notification = Message::create([
                'sender_id' => $currentUserId,
                'chat_id' => $chatType === 'group' ? $chatId : null,
                'receiver_id' => $chatType === 'personal' ? $chatId : null,
                'message' => $notificationText,
                'message_type' => 'notification',
                'is_system' => true
            ]);
    
            // Отправляем уведомление через веб-сокет
            broadcast(new MessageSent($notification))->toOthers();
    
            return response()->json([
                'success' => true,
                'message' => new MessageResource($message)
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Ошибка при откреплении сообщения: ' . $е->getMessage());
            return response()->json(['error' => 'Ошибка открепления сообщения.'], 500);
        }
    }

    protected function getChatPinLogs($chatId)
    {
        return MessagePinLog::whereHas('message', function($query) use ($chatId) {
                $query->where(function($q) use ($chatId) {
                    $q->where('chat_id', $chatId)  // Для групповых чатов
                      ->orWhere(function($sq) use ($chatId) { // Для личных чатов
                          $sq->where(function($inner) use ($chatId) {
                              $inner->where('sender_id', auth()->id())
                                    ->where('receiver_id', $chatId);
                          })->orWhere(function($inner) use ($chatId) {
                              $inner->where('sender_id', $chatId)
                                    ->where('receiver_id', auth()->id());
                          });
                      });
                });
            })
            ->with(['user:id,name', 'message:id,message'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'user' => $log->user->name,
                    'action' => $log->action,
                    'message' => Str::limit($log->message->message, 30),
                    'time' => $log->created_at->format('d.m.Y H:i')
                ];
            });
    }

    /**
     * Поиск по чатам и сообщениям.
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        $userId = Auth::id();

        try {
            $allChats = $this->getUserChats($userId);
            $matchedChats = $allChats->filter(function ($chat) use ($query) {
                return stripos($chat['name'], $query) !== false;
            })->values();

            $matchedMessages = Message::where('message', 'like', "%{$query}%")
                ->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId)
                      ->orWhereHas('chat.users', function ($q2) use ($userId) {
                          $q2->where('users.id', $userId);
                      });
                })
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $matchedMessages->each(function ($message) {
                $message->sender_name = optional($message->sender)->name;
            });
        } catch (\Exception $e) {
            Log::error('Ошибка при поиске в чатах: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка поиска.'], 500);
        }

        return response()->json([
            'chats'    => $matchedChats,
            'messages' => $matchedMessages,
        ], 200);
    }

    /**
     * Возвращает все чаты пользователя (используется для поиска).
     */
    public function getUserChats($userId = null)
    {
        if (!$userId) {
            $userId = Auth::id();
        }
        $cacheKey = "user_chats_{$userId}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $user = User::find($userId);
        if (!$user) {
            return collect();
        }
        try {
            $personalChats = User::where('id', '<>', $userId)
                ->with(['chats' => function($query) {
                    $query->where('type', 'personal');
                }])
                ->get();

            $chats = collect();
            foreach ($personalChats as $chatUser) {
                $unreadCount = Message::where('sender_id', $chatUser->id)
                    ->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->count();

                $lastMessage = Message::where(function ($query) use ($chatUser, $userId) {
                        $query->where('sender_id', $userId)
                              ->where('receiver_id', $chatUser->id);
                    })
                    ->orWhere(function ($query) use ($chatUser, $userId) {
                        $query->where('sender_id', $chatUser->id)
                              ->where('receiver_id', $userId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                $chats->push([
                    'id'                => $chatUser->id,
                    'type'              => 'personal',
                    'name'              => $chatUser->name,
                    'avatar_url'        => $chatUser->avatar_url,
                    'unread_count'      => $unreadCount,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                ]);
            }

            $groupChats = Chat::where('type', 'group')
                ->whereHas('users', function($query) use ($userId) {
                    $query->where('users.id', $userId);
                })
                ->with(['messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(50);
                }])
                ->get();

            foreach ($groupChats as $chat) {
                $pivot = $chat->users->find($userId)->pivot;
                $lastReadAt = $pivot->last_read_at ?? null;
                $unreadCount = $lastReadAt
                    ? $chat->messages->where('created_at', '>', $lastReadAt)->count()
                    : $chat->messages->count();
                $lastMessage = $chat->messages->first();
                
                // Проверка и установка аватара по умолчанию для групповых чатов
                $avatarUrl = $chat->avatar_url;
                if (empty($avatarUrl) || !file_exists(public_path($avatarUrl))) {
                    $avatarUrl = 'storage/avatars/group_default.svg';
                }

                $chats->push([
                    'id'                => $chat->id,
                    'type'              => 'group',
                    'name'              => $chat->name,
                    'avatar_url'        => $avatarUrl,
                    'unread_count'      => $unreadCount,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при формировании списка чатов: ' . $е->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }

        $sorted = $chats->sortByDesc(function ($chat) {
            return $chat['unread_count'] > 0 ? 1 : 0;
        })->sortByDesc('last_message_time')->values();

        Cache::put($cacheKey, $sorted, 10);
        return $sorted;
    }

    public function createGroupChatForm()
    {
        $title_site = "Чаты | Личный кабинет Экспресс-дизайн";
        $users = User::whereIn('status', ['coordinator', 'admin', 'partner', 'designer'])->get();
        return view('create_group', compact('users', 'title_site'));
    }

    public function getUnreadCounts()
    {
        $userId = Auth::id();
        $cacheKey = "chat_unread_counts_user_{$userId}";
        if (Cache::has($cacheKey)) {
            return response()->json(['unread_counts' => Cache::get($cacheKey)], 200);
        }
        $unreadCounts = [];

        try {
            // Личные чаты
            $personalChats = User::where('id', '<>', $userId)
                ->with(['chats' => function($query) {
                    $query->where('type', 'personal');
                }])
                ->get();

            foreach ($personalChats as $chatUser) {
                $unreadCount = Message::where('sender_id', $chatUser->id)
                    ->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->count();

                if ($unreadCount > 0) {
                    $unreadCounts[] = [
                        'id' => $chatUser->id,
                        'type' => 'personal',
                        'name' => $chatUser->name,
                        'unread_count' => $unreadCount,
                    ];
                }
            }

            // Групповые чаты
            $groupChats = Chat::where('type', 'group')
                ->whereHas('users', function($query) use ($userId) {
                    $query->where('users.id', $userId);
                })
                ->with(['messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(50);
                }])
                ->get();

            foreach ($groupChats as $chat) {
                $pivot = $chat->users->find($userId)->pivot;
                $lastReadAt = $pivot->last_read_at ?? null;
                $unreadCount = $lastReadAt
                    ? $chat->messages->where('created_at', '>', $lastReadAt)->count()
                    : $chat->messages->count();

                if ($unreadCount > 0) {
                    $unreadCounts[] = [
                        'id' => $chat->id,
                        'type' => 'group',
                        'name' => $chat->name,
                        'unread_count' => $unreadCount,
                    ];
                }
            }

            Cache::put($cacheKey, $unreadCounts, 5);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении количества непрочитанных сообщений: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка получения данных.'], 500);
        }

        return response()->json(['unread_counts' => $unreadCounts], 200);
    }

    /**
     * Обновляет токен FCM для текущего пользователя.
     */
    public function updateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json(['success' => true]);
    }
}
