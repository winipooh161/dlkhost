<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FirebaseController extends Controller
{
    /**
     * Сохранение FCM токена пользователя.
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            // Сохраните токен в базе данных
            // Предполагается, что у вас есть таблица user_tokens с полями user_id и token
            $user->tokens()->updateOrCreate(
                ['token' => $request->token],
                ['user_id' => $user->id]
            );

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении FCM токена:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Обновляет токен Firebase для текущего пользователя.
     */
    public function updateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            if ($user) {
                $user->firebase_token = $request->token;
                $user->save();
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        } catch (\Exception $e) {
            Log::error('Error updating Firebase token: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Отправляет уведомление через Firebase Cloud Messaging.
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'title' => 'required',
            'body' => 'required',
            'data' => 'nullable|array'
        ]);

        try {
            $userId = $request->user_id;
            $user = \App\Models\User::find($userId);
            
            if (!$user || !$user->firebase_token) {
                return response()->json(['success' => false, 'message' => 'User not found or has no Firebase token'], 404);
            }

            $firebaseToken = $user->firebase_token;
            
            $serverKey = env('FIREBASE_SERVER_KEY');
            if (!$serverKey) {
                throw new \Exception('FIREBASE_SERVER_KEY not configured');
            }
            
            $data = [
                'to' => $firebaseToken,
                'notification' => [
                    'title' => $request->title,
                    'body' => $request->body,
                    'icon' => '/path/to/icon.png',
                    'click_action' => url('/chats')
                ],
                'data' => $request->data ?: []
            ];

            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json'
            ];

            // Отправляем запрос в FCM
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            curl_close($ch);

            return response()->json(['success' => true, 'response' => json_decode($response)]);
        } catch (\Exception $e) {
            Log::error('Error sending Firebase notification: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
