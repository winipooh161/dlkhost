<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Chat;
use Illuminate\Support\Facades\Validator;

class FirebaseController extends Controller
{
    /**
     * Обновляет токен FCM для текущего пользователя.
     */
    public function updateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $user->fcm_token = $request->token;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Токен успешно сохранен']);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении FCM токена: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'token' => substr($request->token, 0, 20) . '...',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Не удалось сохранить токен'], 500);
        }
    }

    /**
     * Отправляет уведомление через Firebase Cloud Messaging.
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'tokens' => 'required|array',
            'tokens.*' => 'string|max:1000',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
            $serverKey = env('BLf08mEO3lePyBvZCwTzaSNX9R981qwESUblCemdDVZUT_cs4G3GD2YY38CN8ELIcPmgVRZ92G7ePzY187d4Dh4');

            // Формируем уведомление
            $notification = [
                'title' => $request->title,
                'body' => $request->body,
                'icon' => '/firebase-logo.png',
                'sound' => 'default',
                'badge' => '1'
            ];

            // Данные для дополнительной обработки в приложении
            $data = $request->data ?? [];
            
            // Проставляем timestamp для избежания кэширования
            $data['timestamp'] = now()->timestamp;

            // Формируем полное сообщение для FCM
            $fcmNotification = [
                'registration_ids' => $request->tokens, // Массив токенов для отправки
                'notification' => $notification,
                'data' => $data,
                'priority' => 'high',
                // Параметры Android
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                        'default_light_settings' => true,
                    ]
                ],
                // Параметры iOS
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10'
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ],
                // Параметры Web
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high'
                    ],
                    'notification' => [
                        'requireInteraction' => true
                    ]
                ]
            ];

            // Отправляем запрос к FCM
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json'
            ])->post($fcmUrl, $fcmNotification);

            // Логируем результат
            Log::info('FCM запрос отправлен', [
                'user_id' => Auth::id(),
                'tokens_count' => count($request->tokens),
                'response_status' => $response->status(),
                'response_body' => $response->json()
            ]);

            return response()->json([
                'success' => $response->successful(),
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке FCM уведомления: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'tokens_count' => count($request->tokens),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Не удалось отправить уведомление: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Отправляет уведомление всем участникам чата.
     */
    public function sendChatNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'chat_id' => 'required|integer',
            'chat_type' => 'required|in:personal,group',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $chatId = $request->chat_id;
            $chatType = $request->chat_type;
            $currentUserId = Auth::id();
            $tokens = [];

            // Получаем токены пользователей в зависимости от типа чата
            if ($chatType === 'personal') {
                $recipient = User::find($chatId);
                if ($recipient && !empty($recipient->fcm_token)) {
                    $tokens[] = $recipient->fcm_token;
                }
            } else if ($chatType === 'group') {
                $chat = Chat::with('users')->find($chatId);
                if ($chat) {
                    foreach ($chat->users as $user) {
                        // Пропускаем текущего пользователя
                        if ($user->id !== $currentUserId && !empty($user->fcm_token)) {
                            $tokens[] = $user->fcm_token;
                        }
                    }
                }
            }

            if (empty($tokens)) {
                return response()->json(['message' => 'Нет получателей для уведомления'], 200);
            }

            // Добавляем информацию о чате в данные
            $data = $request->data ?? [];
            $data['chatId'] = $chatId;
            $data['chatType'] = $chatType;
            $data['url'] = "/chats?chat_id={$chatId}&chat_type={$chatType}";

            // Отправляем уведомление через основной метод
            $notificationRequest = new Request([
                'title' => $request->title,
                'body' => $request->body,
                'tokens' => $tokens,
                'data' => $data
            ]);

            return $this->sendNotification($notificationRequest);
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке уведомления о чате: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'chat_id' => $request->chat_id,
                'chat_type' => $request->chat_type,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Не удалось отправить уведомление: ' . $e->getMessage()], 500);
        }
    }
}
