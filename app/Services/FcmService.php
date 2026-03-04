<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class FcmService
{
    protected string $endpoint = 'https://fcm.googleapis.com/fcm/send';

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) {
            return false;
        }
        $serverKey = Config::get('services.fcm.server_key', env('FCM_SERVER_KEY'));
        if (! $serverKey) {
            return false;
        }
        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];
        $res = Http::withHeaders([
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ])->post($this->endpoint, $payload);

        return $res->successful();
    }
}
