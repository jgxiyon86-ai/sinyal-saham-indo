<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\Tier;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\FonnteService;
use App\Support\GatewaySetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SignalWaBlastPageController extends Controller
{
    public function __construct(private readonly FonnteService $fonnteService)
    {
    }

    public function index(): View
    {
        $logs = WaBlastLog::with('admin')
            ->where('blast_type', 'general')
            ->where(function ($query) {
                $query->where('filters->source', 'signal-batch-web')
                    ->orWhere('filters->source', 'signal-batch-api');
            })
            ->latest()
            ->paginate(20);

        return view('admin.signal-wa-blast', [
            'signals' => $this->availableSignals(),
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
            'preview' => null,
            'selectedSignalIds' => [],
            'selectedTierId' => null,
            'settings' => [
                'delay_seconds' => 12,
                'max_recipients' => 40,
                'opening_text' => 'Halo {name}, berikut update sinyal saham kamu hari ini:',
                'closing_text' => 'Gunakan manajemen risiko. Bukan ajakan beli/jual.',
                'image_url' => '',
            ],
            'logs' => $logs,
        ]);
    }

    public function preview(Request $request): View
    {
        try {
            [$payload, $targets] = $this->buildPayload($request);
        } catch (Throwable $e) {
            Log::error('Signal WA blast preview error', [
                'message' => $e->getMessage(),
            ]);

            return view('admin.signal-wa-blast', [
                'signals' => $this->availableSignals(),
                'tiers' => Tier::query()->orderBy('min_capital')->get(),
                'preview' => null,
                'selectedSignalIds' => [],
                'selectedTierId' => null,
                'settings' => [
                    'delay_seconds' => 12,
                    'max_recipients' => 40,
                    'opening_text' => 'Halo {name}, berikut update sinyal saham kamu hari ini:',
                    'closing_text' => 'Gunakan manajemen risiko. Bukan ajakan beli/jual.',
                    'image_url' => '',
                ],
                'logs' => WaBlastLog::with('admin')
                    ->where('blast_type', 'general')
                    ->where(function ($query) {
                        $query->where('filters->source', 'signal-batch-web')
                            ->orWhere('filters->source', 'signal-batch-api');
                    })
                    ->latest()
                    ->paginate(20),
            ])->with('status', 'Preview gagal: '.$e->getMessage());
        }

        return view('admin.signal-wa-blast', [
            'signals' => $this->availableSignals(),
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
            'preview' => $targets,
            'selectedSignalIds' => $payload['signal_ids'],
            'selectedTierId' => $payload['tier_id'],
            'settings' => [
                'delay_seconds' => $payload['delay_seconds'],
                'max_recipients' => $payload['max_recipients'],
                'opening_text' => $payload['opening_text'],
                'closing_text' => $payload['closing_text'],
                'image_url' => $payload['image_url'] ?? '',
            ],
            'logs' => WaBlastLog::with('admin')
                ->where('blast_type', 'general')
                ->where(function ($query) {
                    $query->where('filters->source', 'signal-batch-web')
                        ->orWhere('filters->source', 'signal-batch-api');
                })
                ->latest()
                ->paginate(20),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        try {
            [$payload, $targets] = $this->buildPayload($request);
        } catch (Throwable $e) {
            Log::error('Signal WA blast build payload error', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('signal-wa-blast.page')
                ->with('status', 'WA Blast gagal menyiapkan data: '.$e->getMessage());
        }

        if (GatewaySetting::appApiKey() === '') {
            return redirect()->route('signal-wa-blast.page')
                ->with('status', 'Gateway API Key belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if (GatewaySetting::sessionId() === '') {
            return redirect()->route('signal-wa-blast.page')
                ->with('status', 'Gateway Session ID belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if ($targets->isEmpty()) {
            return redirect()->route('signal-wa-blast.page')->with('status', 'Tidak ada target blast.');
        }

        $delaySeconds = (int) $payload['delay_seconds'];
        $results = [];
        $success = 0;
        $failed = 0;
        $targetsCount = $targets->count();
        $sentMessagesCount = 0;

        try {
            foreach ($targets as $target) {
                foreach ($target['signal_items'] as $itemIndex => $signalItem) {
                    $imageToSend = $signalItem['image_url'] ?: ($payload['image_url'] ?: null);
                    try {
                        $response = $this->fonnteService->sendMessage(
                            (string) $target['whatsapp_number'],
                            (string) $signalItem['message'],
                            $imageToSend
                        );
                        $success++;
                        $sentMessagesCount++;
                        $results[] = [
                            'name' => $target['name'],
                            'whatsapp_number' => $target['whatsapp_number'],
                            'tier' => $target['tier'],
                            'signal_id' => $signalItem['signal_id'],
                            'signal_title' => $signalItem['signal_title'],
                            'message' => $signalItem['message'],
                            'image_url' => $imageToSend,
                            'status' => 'sent',
                            'response' => $response,
                        ];
                    } catch (RuntimeException $e) {
                        $failed++;
                        $results[] = [
                            'name' => $target['name'],
                            'whatsapp_number' => $target['whatsapp_number'],
                            'tier' => $target['tier'],
                            'signal_id' => $signalItem['signal_id'],
                            'signal_title' => $signalItem['signal_title'],
                            'message' => $signalItem['message'],
                            'image_url' => $imageToSend,
                            'status' => 'failed',
                            'response' => $e->getMessage(),
                        ];
                    }

                    $isLastMessage = $itemIndex === (count($target['signal_items']) - 1);
                    if (! $isLastMessage && $delaySeconds > 0) {
                        sleep($delaySeconds);
                    }
                }

                if ($delaySeconds > 0) {
                    sleep($delaySeconds);
                }
            }
        } catch (Throwable $e) {
            Log::error('Signal WA blast send fatal error', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('signal-wa-blast.page')
                ->with('status', 'WA Blast gagal: '.$e->getMessage());
        }

        WaBlastLog::create([
            'admin_id' => $request->user()->id,
            'message_template_id' => null,
            'blast_type' => 'general',
            'filters' => [
                'source' => 'signal-batch-web',
                'tier_id' => $payload['tier_id'],
                'signal_ids' => $payload['signal_ids'],
                'delay_seconds' => $payload['delay_seconds'],
                'max_recipients' => $payload['max_recipients'],
                'sent_messages' => $sentMessagesCount,
            ],
            'recipients_count' => $targetsCount,
            'rendered_messages' => collect($results)->toJson(),
            'status' => $failed > 0 ? 'partial' : 'sent',
            'blasted_at' => now(),
        ]);

        return redirect()->route('signal-wa-blast.page')
            ->with('status', "WA Blast Sinyal selesai. Berhasil: {$success}, Gagal: {$failed}, Target: {$targetsCount}, Pesan terkirim: {$sentMessagesCount}.");
    }

    private function availableSignals(): Collection
    {
        $now = now();

        return Signal::query()
            ->with('tiers:id,name')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->latest('published_at')
            ->limit(120)
            ->get();
    }

    /**
     * @return array{0: array, 1: \Illuminate\Support\Collection}
     */
    private function buildPayload(Request $request): array
    {
        $data = $request->validate([
            'signal_ids' => ['required', 'array', 'min:1'],
            'signal_ids.*' => ['required', 'integer', 'exists:signals,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
            'delay_seconds' => ['nullable', 'integer', 'min:3', 'max:120'],
            'max_recipients' => ['nullable', 'integer', 'min:1', 'max:300'],
            'opening_text' => ['nullable', 'string', 'max:300'],
            'closing_text' => ['nullable', 'string', 'max:300'],
            'image_url' => ['nullable', 'url', 'max:500'],
        ]);

        $payload = [
            'signal_ids' => array_values(array_unique(array_map('intval', $data['signal_ids']))),
            'tier_id' => isset($data['tier_id']) ? (int) $data['tier_id'] : null,
            'delay_seconds' => (int) ($data['delay_seconds'] ?? 12),
            'max_recipients' => (int) ($data['max_recipients'] ?? 40),
            'opening_text' => trim((string) ($data['opening_text'] ?? 'Halo {name}, berikut update sinyal saham kamu hari ini:')),
            'closing_text' => trim((string) ($data['closing_text'] ?? 'Gunakan manajemen risiko. Bukan ajakan beli/jual.')),
            'image_url' => (string) ($data['image_url'] ?? ''),
        ];

        $signals = Signal::query()
            ->with('tiers:id,name')
            ->whereIn('id', $payload['signal_ids'])
            ->get()
            ->sortByDesc(fn (Signal $signal) => optional($signal->published_at)?->timestamp ?? 0)
            ->values();

        $clientsQuery = User::query()
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('whatsapp_number');

        if (! empty($payload['tier_id'])) {
            $clientsQuery->where('tier_id', $payload['tier_id']);
        }

        $clients = $clientsQuery->with('tier:id,name')->limit(1500)->get();

        $targets = $clients->map(function (User $client) use ($signals, $payload) {
            $matchedSignals = $signals->filter(function (Signal $signal) use ($client) {
                return $signal->tiers->contains('id', $client->tier_id);
            })->values();

            if ($matchedSignals->isEmpty()) {
                return null;
            }

            $signalLines = $matchedSignals->map(function (Signal $signal) {
                $type = strtoupper((string) $signal->signal_type);
                $code = strtoupper((string) $signal->stock_code);
                $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 2, '.', ',') : '-';
                $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 2, '.', ',') : '-';
                $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 2, '.', ',') : '-';

                return "- {$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
            })->implode("\n");

            $header = str_replace('{name}', $client->name, $payload['opening_text']);
            $footer = str_replace('{name}', $client->name, $payload['closing_text']);
            $message = trim($header)."\n\n".$signalLines."\n\n".trim($footer);

            $signalItems = $matchedSignals->map(function (Signal $signal) use ($client, $payload) {
                $type = strtoupper((string) $signal->signal_type);
                $code = strtoupper((string) $signal->stock_code);
                $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 2, '.', ',') : '-';
                $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 2, '.', ',') : '-';
                $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 2, '.', ',') : '-';
                $line = "{$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
                $header = str_replace('{name}', $client->name, $payload['opening_text']);
                $footer = str_replace('{name}', $client->name, $payload['closing_text']);
                $singleMessage = trim($header)."\n\n- {$line}\n\n".trim($footer);

                return [
                    'signal_id' => $signal->id,
                    'signal_title' => $signal->title,
                    'message' => $singleMessage,
                    'image_url' => (string) ($signal->image_url ?? ''),
                ];
            })->values()->all();

            return [
                'name' => $client->name,
                'whatsapp_number' => $client->whatsapp_number,
                'tier' => optional($client->tier)->name,
                'signals_count' => $matchedSignals->count(),
                'signal_ids' => $matchedSignals->pluck('id')->values()->all(),
                'message' => $message,
                'signal_items' => $signalItems,
            ];
        })
            ->filter()
            ->take($payload['max_recipients'])
            ->values();

        return [$payload, $targets];
    }
}
