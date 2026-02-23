<?php

namespace App\Services;

use App\Support\GatewaySetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteService
{
    public function sendMessage(string $target, string $message, ?string $imageUrl = null): array
    {
        $appApiKey = GatewaySetting::appApiKey();
        $baseUrl = GatewaySetting::baseUrl();
        $sessionId = GatewaySetting::sessionId();

        if ($appApiKey === '') {
            throw new RuntimeException('Gateway API Key belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if ($sessionId === '') {
            throw new RuntimeException('Gateway Session ID belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
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
