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

        $payload = [
            'sessionId' => $sessionId,
            'to' => $target,
            'message' => $message,
            'imageUrl' => $imageUrl ?: null,
        ];

        $headers = [
            'x-api-key' => $appApiKey,
            'Accept' => 'application/json',
        ];

        $baseUrl = rtrim($baseUrl, '/');
        $endpoints = [
            $baseUrl.'/messages/send',
            $baseUrl.'/api/messages/send',
        ];

        $lastStatus = 0;
        $lastBody = '';
        foreach ($endpoints as $endpoint) {
            $response = Http::withHeaders($headers)->post($endpoint, $payload);
            if ($response->successful()) {
                return $response->json() ?? [
                    'status' => true,
                    'message' => 'Pesan dikirim.',
                ];
            }

            $lastStatus = $response->status();
            $lastBody = $response->body();

            // Endpoint berikutnya dicoba hanya jika route tidak cocok.
            if (! in_array($lastStatus, [404, 405], true)) {
                break;
            }
        }

        throw new RuntimeException('ALIMA Gateway error HTTP '.$lastStatus.': '.$lastBody);
    }
}
