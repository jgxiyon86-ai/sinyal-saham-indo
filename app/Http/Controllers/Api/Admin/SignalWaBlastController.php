<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\SignalWaBlastBatch;
use App\Models\SignalWaBlastTarget;
use App\Models\Tier;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Support\GatewaySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SignalWaBlastController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'history' => WaBlastLog::query()
                ->with('admin:id,name')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signal_ids' => ['required', 'array', 'min:1'],
            'signal_ids.*' => ['required', 'integer', 'exists:signals,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
            'group_messages' => ['nullable', 'boolean'],
        ]);

        if (GatewaySetting::appApiKey() === '') {
            return response()->json(['message' => 'Gateway API Key belum diisi.'], 422);
        }
        if (GatewaySetting::sessionId() === '') {
            return response()->json(['message' => 'Gateway Session ID belum diisi.'], 422);
        }

        $delaySeconds = GatewaySetting::signalWaDelaySeconds();
        $maxRecipients = GatewaySetting::signalWaMaxRecipients();
        $opening = GatewaySetting::signalWaOpeningText();
        $closing = GatewaySetting::signalWaClosingText();

        $signals = Signal::query()
            ->with('tiers:id,name')
            ->whereIn('id', $data['signal_ids'])
            ->get();

        $clientsQuery = User::query()
            ->with('tier:id,name')
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('whatsapp_number');

        if (! empty($data['tier_id'])) {
            $clientsQuery->where('tier_id', $data['tier_id']);
        }

        $clients = $clientsQuery->limit(1500)->get();

        // Filter valid whatsapp numbers in PHP to avoid SQLite "regexp" error
        $clients = $clients->filter(function ($client) {
            return preg_match('/^(\+62|62|0)?8[0-9]{7,13}$/', (string) $client->whatsapp_number);
        });

        $clients = $this->applyTierBlastLimit($clients)->take($maxRecipients)->values();

        $groupByClient = (bool) ($data['group_messages'] ?? GatewaySetting::signalWaGroupMessages());
        $jobs = [];
        foreach ($clients as $client) {
            $matched = $signals->filter(fn (Signal $signal) => $signal->tiers->contains('id', $client->tier_id))->values();
            if ($matched->isEmpty()) {
                continue;
            }

            if ($groupByClient) {
                // Grouped Message
                $signalLines = $matched->map(function (Signal $signal) {
                    $type = strtoupper((string) $signal->signal_type);
                    $code = strtoupper((string) $signal->stock_code);
                    $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 0, '.', ',') : '-';
                    $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 0, '.', ',') : '-';
                    $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 0, '.', ',') : '-';
                    return "- {$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
                })->implode("\n");

                $message = str_replace('{name}', $client->name, $opening)."\n\n".$signalLines."\n\n".str_replace('{name}', $client->name, $closing);

                $jobs[] = [
                    'client_id' => $client->id,
                    'tier_id' => $client->tier_id,
                    'signal_id' => null,
                    'client_name' => $client->name,
                    'signal_title' => $matched->count() . ' Sinyal',
                    'whatsapp_number' => $client->whatsapp_number,
                    'message' => trim($message),
                    'image_url' => (string) ($matched->first()->image_url ?? ''),
                ];
            } else {
                // Individual Messages
                foreach ($matched as $signal) {
                    $type = strtoupper((string) $signal->signal_type);
                    $code = strtoupper((string) $signal->stock_code);
                    $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 0, '.', ',') : '-';
                    $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 0, '.', ',') : '-';
                    $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 0, '.', ',') : '-';
                    $line = "{$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
                    $message = str_replace('{name}', $client->name, $opening)."\n\n- {$line}\n\n".str_replace('{name}', $client->name, $closing);

                    $jobs[] = [
                        'client_id' => $client->id,
                        'tier_id' => $client->tier_id,
                        'signal_id' => $signal->id,
                        'client_name' => $client->name,
                        'signal_title' => $signal->title,
                        'whatsapp_number' => $client->whatsapp_number,
                        'message' => trim($message),
                        'image_url' => (string) ($signal->image_url ?? ''),
                    ];
                }
            }
        }

        if (empty($jobs)) {
            return response()->json([
                'message' => 'Tidak ada job blast yang cocok (tier tidak sesuai atau nomor tidak valid).',
                'targets' => $clients->count(),
                'sent' => 0,
                'failed' => 0,
            ]);
        }

        try {
            $batch = DB::transaction(function () use ($request, $data, $clients, $delaySeconds, $maxRecipients, $opening, $closing, $jobs) {
                $batch = SignalWaBlastBatch::query()->create([
                    'admin_id' => $request->user()->id,
                    'tier_id' => $data['tier_id'] ?? null,
                    'signal_ids' => array_values(array_unique($data['signal_ids'])),
                    'delay_seconds' => $delaySeconds,
                    'max_recipients' => $maxRecipients,
                    'opening_text' => $opening,
                    'closing_text' => $closing,
                    'group_messages' => $groupByClient,
                    'status' => 'queued',
                    'total_targets' => count($jobs),
                    'pending_count' => count($jobs),
                    'sent_count' => 0,
                    'failed_count' => 0,
                ]);

                foreach (array_chunk($jobs, 300) as $chunk) {
                    $rows = array_map(fn (array $job) => [
                        'batch_id' => $batch->id,
                        'client_id' => $job['client_id'],
                        'tier_id' => $job['tier_id'],
                        'signal_id' => $job['signal_id'],
                        'client_name' => $job['client_name'],
                        'signal_title' => $job['signal_title'],
                        'whatsapp_number' => $job['whatsapp_number'],
                        'message' => $job['message'],
                        'image_url' => $job['image_url'] ?: null,
                        'status' => 'pending',
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $chunk);
                    SignalWaBlastTarget::query()->insert($rows);
                }

                WaBlastLog::create([
                    'admin_id' => $request->user()->id,
                    'message_template_id' => null,
                    'blast_type' => 'general',
                    'filters' => [
                        'source' => 'signal-batch-api',
                        'signal_ids' => array_values(array_unique($data['signal_ids'])),
                        'tier_id' => $data['tier_id'] ?? null,
                        'delay_seconds' => $delaySeconds,
                        'max_recipients' => $maxRecipients,
                        'group_messages' => $groupByClient,
                        'queue_batch_id' => $batch->id,
                    ],
                    'recipients_count' => $clients->count(),
                    'rendered_messages' => collect($jobs)->toJson(),
                    'status' => 'queued',
                    'blasted_at' => now(),
                ]);

                return $batch;
            });
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat queue: '.$e->getMessage(),
            ], 500);
        }

        try {
            \Artisan::call('signals:process-wa-queue', ['--batch_id' => $batch->id]);
        } catch (Throwable) {
            // Silent: scheduler tetap akan memproses.
        }

        return response()->json([
            'message' => 'WA blast sinyal masuk queue.',
            'batch_id' => $batch->id,
            'sent' => count($jobs),
            'failed' => 0,
            'queued_targets' => count($jobs),
            'targets' => $clients->count(),
            'settings' => [
                'delay_seconds' => $delaySeconds,
                'max_recipients' => $maxRecipients,
            ],
        ]);
    }

    private function applyTierBlastLimit(Collection $clients): Collection
    {
        $tierLimits = Tier::query()
            ->pluck('wa_blast_limit', 'id')
            ->map(fn ($value) => max(1, (int) $value))
            ->all();

        $bucket = [];
        $accepted = [];

        foreach ($clients as $client) {
            $tierId = (int) ($client->tier_id ?? 0);
            if ($tierId <= 0) {
                continue;
            }

            $limit = $tierLimits[$tierId] ?? 60;
            $current = $bucket[$tierId] ?? 0;
            if ($current >= $limit) {
                continue;
            }

            $accepted[] = $client;
            $bucket[$tierId] = $current + 1;
        }

        return collect($accepted);
    }
}
