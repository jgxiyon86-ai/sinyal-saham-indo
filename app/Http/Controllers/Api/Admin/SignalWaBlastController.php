<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\FonnteService;
use App\Support\GatewaySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SignalWaBlastController extends Controller
{
    public function __construct(private readonly FonnteService $fonnteService)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signal_ids' => ['required', 'array', 'min:1'],
            'signal_ids.*' => ['required', 'integer', 'exists:signals,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
        ]);

        if (GatewaySetting::appApiKey() === '') {
            return response()->json(['message' => 'Gateway API Key belum diisi.'], 422);
        }
        if (GatewaySetting::sessionId() === '') {
            return response()->json(['message' => 'Gateway Session ID belum diisi.'], 422);
        }

        $delaySeconds = (int) env('SIGNAL_WA_DELAY_SECONDS', 12);
        $maxRecipients = (int) env('SIGNAL_WA_MAX_RECIPIENTS', 40);
        $opening = (string) env('SIGNAL_WA_OPENING', 'Halo {name}, berikut update sinyal saham kamu hari ini:');
        $closing = (string) env('SIGNAL_WA_CLOSING', 'Gunakan manajemen risiko. Bukan ajakan beli/jual.');

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

        $clients = $clientsQuery->limit($maxRecipients)->get();

        $success = 0;
        $failed = 0;
        $results = [];

        foreach ($clients as $client) {
            $matched = $signals->filter(fn (Signal $signal) => $signal->tiers->contains('id', $client->tier_id))->values();
            foreach ($matched as $index => $signal) {
                $type = strtoupper((string) $signal->signal_type);
                $code = strtoupper((string) $signal->stock_code);
                $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 2, '.', ',') : '-';
                $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 2, '.', ',') : '-';
                $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 2, '.', ',') : '-';
                $line = "{$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
                $message = str_replace('{name}', $client->name, $opening)."\n\n- {$line}\n\n".str_replace('{name}', $client->name, $closing);

                try {
                    $resp = $this->fonnteService->sendMessage(
                        (string) $client->whatsapp_number,
                        $message,
                        (string) ($signal->image_url ?? '')
                    );
                    $success++;
                    $results[] = [
                        'client' => $client->name,
                        'whatsapp_number' => $client->whatsapp_number,
                        'signal_id' => $signal->id,
                        'status' => 'sent',
                        'response' => $resp,
                    ];
                } catch (RuntimeException $e) {
                    $failed++;
                    $results[] = [
                        'client' => $client->name,
                        'whatsapp_number' => $client->whatsapp_number,
                        'signal_id' => $signal->id,
                        'status' => 'failed',
                        'response' => $e->getMessage(),
                    ];
                }

                if ($delaySeconds > 0 && $index < ($matched->count() - 1)) {
                    sleep($delaySeconds);
                }
            }
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
            ],
            'recipients_count' => $clients->count(),
            'rendered_messages' => collect($results)->toJson(),
            'status' => $failed > 0 ? 'partial' : 'sent',
            'blasted_at' => now(),
        ]);

        return response()->json([
            'message' => 'WA blast sinyal diproses.',
            'sent' => $success,
            'failed' => $failed,
            'targets' => $clients->count(),
            'settings' => [
                'delay_seconds' => $delaySeconds,
                'max_recipients' => $maxRecipients,
            ],
            'results' => $results,
        ]);
    }
}
