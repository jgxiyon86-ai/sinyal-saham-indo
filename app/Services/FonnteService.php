<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteService
{
    public function sendMessage(string $target, string $message, ?string $imageUrl = null): array
    {
        $appApiKey = (string) config('services.alima_gateway.app_api_key');
        $baseUrl = rtrim((string) config('services.alima_gateway.base_url'), '/');
        $sessionId = (string) config('services.alima_gateway.session_id');

        if ($appApiKey === '') {
            throw new RuntimeException('ALIMA_GATEWAY_APP_API_KEY belum diset di file .env');
        }

        if ($sessionId === '') {
            throw new RuntimeException('ALIMA_GATEWAY_SESSION_ID belum diset di file .env');
        }

        $response = Http::withHeaders([
            'x-api-key' => $appApiKey,
            'Accept' => 'application/json',
        ])->post($baseUrl.'/messages/send', [
            'sessionId' => $sessionId,
            'to' => $target,
            'message' => $message,
            'imageUrl' => $imageUrl ?: null,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('ALIMA Gateway error HTTP '.$response->status().': '.$response->body());
        }

        return $response->json() ?? [
            'status' => true,
            'message' => 'Pesan dikirim.',
        ];
    }
}
