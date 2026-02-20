<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteService
{
    public function sendMessage(string $target, string $message): array
    {
        $token = (string) config('services.fonnte.token');
        $baseUrl = rtrim((string) config('services.fonnte.base_url'), '/');
        $countryCode = (string) config('services.fonnte.country_code', '62');

        if ($token === '') {
            throw new RuntimeException('FONNTE_TOKEN belum diset di file .env');
        }

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->asForm()->post($baseUrl.'/send', [
            'target' => $target,
            'message' => $message,
            'countryCode' => $countryCode,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Fonnte error HTTP '.$response->status().': '.$response->body());
        }

        return $response->json() ?? [
            'status' => true,
            'message' => 'Pesan dikirim.',
        ];
    }
}
