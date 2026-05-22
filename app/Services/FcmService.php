<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(private Messaging $messaging) {}

    /**
     * إرسال إشعار FCM لـ token معين
     *
     * @param string $token   FCM device token
     * @param string $title   عنوان الإشعار
     * @param string $body    نص الإشعار
     * @param array  $data    بيانات إضافية تُرسل للتطبيق (key => string)
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) {
            return;
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withData(array_merge([
                    'title' => $title,
                    'body'  => $body,
                ], $data));

            $this->messaging->send($message);

            Log::info('FCM sent successfully', [
                'token_preview' => substr($token, 0, 20) . '...',
                'title'         => $title,
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'error'         => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
        }
    }
}
