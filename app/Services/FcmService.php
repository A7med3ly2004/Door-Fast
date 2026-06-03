<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(private Messaging $messaging)
    {
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) {
            return;
        }

        // ✅ تأكد إن كل الـ values strings
        $stringData = array_map('strval', array_merge([
            'title' => $title,
            'body' => $body,
        ], $data));

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'orders_channel',
                            'default_vibrate_timings' => true,
                            'default_sound' => true,
                        ],
                    ])
                )
                ->withData($stringData);

            $this->messaging->send($message);

            Log::info('FCM sent successfully', [
                'token_preview' => substr($token, 0, 20) . '...',
                'title' => $title,
            ]);

        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);

            if (str_contains($e->getMessage(), 'Requested entity was not found')) {
                \App\Models\User::where('fcm_token', $token)
                    ->update(['fcm_token' => null]);

                Log::info('FCM token removed (expired)', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            }
        }
    }

    /**
     * إرسال إشعار FCM لمستخدم بناءً على كائن User مباشرةً
     * يتحقق من وجود التوكن قبل الإرسال — لا يُعدّل sendToToken()
     */
    public function sendToUser(\App\Models\User $user, string $title, string $body, array $data = []): void
    {
        $token = $user->fcm_token;

        if (empty($token)) {
            return;
        }

        $this->sendToToken($token, $title, $body, $data);
    }
}