<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmService
{
    public function pushSignalToTierClients(Signal $signal): array
    {
        $tierIds = $signal->tiers()->pluck('tiers.id')->toArray();
        $result = $this->sendToUsers(
            title: 'Sinyal Baru Masuk',
            body: ($signal->stock_code ?? '-').' '.strtoupper((string) $signal->signal_type),
            tierIds: $tierIds,
            data: [
                'id' => (string) $signal->id,
                'title' => (string) $signal->title,
                'stock_code' => (string) $signal->stock_code,
                'signal_type' => (string) $signal->signal_type,
                'entry_price' => (string) $signal->entry_price,
                'take_profit' => (string) $signal->take_profit,
                'stop_loss' => (string) $signal->stop_loss,
                'note' => (string) $signal->note,
                'published_at' => (string) $signal->published_at,
                'type' => 'signal',
            ],
        );

        return $result;
    }

    public function broadcastToTierClients(string $title, string $body, ?array $tierIds = null, array $data = []): array
    {
        return $this->sendToUsers(
            title: $title,
            body: $body,
            tierIds: $tierIds,
            data: [
                'type' => 'broadcast',
                ...$data,
            ],
        );
    }

    private function sendToUsers(string $title, string $body, ?array $tierIds = null, array $data = []): array
    {
        $serverKey = (string) config('services.fcm.server_key');
        $legacyUrl = (string) config('services.fcm.url');
        $projectId = (string) config('services.fcm.project_id');
        $serviceAccountPath = (string) config('services.fcm.service_account_json');

        $usersQuery = User::query()
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('fcm_token');

        if (! empty($tierIds)) {
            $usersQuery->whereIn('tier_id', $tierIds);
        }

        $users = $usersQuery->get(['id', 'fcm_token']);
        if ($users->isEmpty()) {
            return [
                'enabled' => true,
                'sent' => 0,
                'failed' => 0,
                'message' => 'Tidak ada token client untuk target tier terpilih.',
            ];
        }

        $useV1 = $projectId !== '' && $serviceAccountPath !== '' && is_file($serviceAccountPath);
        if (! $useV1 && $serverKey === '') {
            return [
                'enabled' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'FCM belum dikonfigurasi. Isi FCM v1 (project+service account) atau FCM_SERVER_KEY legacy.',
            ];
        }

        $sent = 0;
        $failed = 0;
        $accessToken = null;

        if ($useV1) {
            try {
                $accessToken = $this->getV1AccessToken($serviceAccountPath);
            } catch (RuntimeException $e) {
                return [
                    'enabled' => false,
                    'sent' => 0,
                    'failed' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        foreach ($users as $user) {
            $response = $useV1
                ? $this->sendViaV1(
                    token: $user->fcm_token,
                    title: $title,
                    body: $body,
                    data: $data,
                    projectId: $projectId,
                    accessToken: (string) $accessToken,
                )
                : $this->sendViaLegacy(
                    token: $user->fcm_token,
                    title: $title,
                    body: $body,
                    data: $data,
                    serverKey: $serverKey,
                    url: $legacyUrl,
                );

            if ($response->successful()) {
                $sent++;
                continue;
            }

            $failed++;
            Log::warning('FCM push failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'use_v1' => $useV1,
            ]);
        }

        return [
            'enabled' => true,
            'sent' => $sent,
            'failed' => $failed,
            'message' => $useV1 ? 'Push v1 selesai diproses.' : 'Push legacy selesai diproses.',
        ];
    }

    private function sendViaLegacy(
        string $token,
        string $title,
        string $body,
        array $data,
        string $serverKey,
        string $url,
    ) {
        return Http::withHeaders([
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->stringifyData($data),
            'priority' => 'high',
        ]);
    }

    private function sendViaV1(
        string $token,
        string $title,
        string $body,
        array $data,
        string $projectId,
        string $accessToken,
    ) {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        return Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->stringifyData($data),
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ]);
    }

    /**
     * @throws RuntimeException
     */
    private function getV1AccessToken(string $serviceAccountPath): string
    {
        $cacheKey = 'fcm_v1_access_token';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if (! is_file($serviceAccountPath)) {
            throw new RuntimeException('File service account FCM tidak ditemukan.');
        }

        $json = json_decode((string) file_get_contents($serviceAccountPath), true);
        $clientEmail = (string) ($json['client_email'] ?? '');
        $privateKey = (string) ($json['private_key'] ?? '');

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('Isi service account tidak valid (client_email/private_key kosong).');
        }

        $jwt = $this->buildJwt($clientEmail, $privateKey);
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal ambil access token FCM v1: '.$response->body());
        }

        $token = (string) data_get($response->json(), 'access_token', '');
        if ($token === '') {
            throw new RuntimeException('Access token FCM v1 kosong.');
        }

        Cache::put($cacheKey, $token, now()->addMinutes(50));

        return $token;
    }

    /**
     * @throws RuntimeException
     */
    private function buildJwt(string $clientEmail, string $privateKey): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_UNESCAPED_SLASHES));

        $unsigned = $header.'.'.$payload;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Gagal sign JWT untuk FCM v1.');
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function stringifyData(array $data): array
    {
        return collect($data)->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])->all();
    }
}
