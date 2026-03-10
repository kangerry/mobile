<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class OneSignalService
{
    public function sendToPlayerIds(array $playerIds, string $title, string $body, array $data = []): bool
    {
        $playerIds = array_values(array_filter(array_unique($playerIds), fn ($t) => is_string($t) && strlen($t) > 5));
        if (empty($playerIds)) {
            return false;
        }
        $appId = (string) (Config::get('services.onesignal.app_id') ?? env('ONESIGNAL_APP_ID', ''));
        $apiKey = (string) (Config::get('services.onesignal.api_key') ?? env('ONESIGNAL_REST_API_KEY', ''));
        if ($appId === '' || $apiKey === '') {
            return false;
        }
        $payload = [
            'app_id' => $appId,
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ];
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Basic '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.onesignal.com/notifications', $payload);
            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
