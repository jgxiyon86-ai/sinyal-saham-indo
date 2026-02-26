<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\SignalWaBlastBatch;
use App\Models\SignalWaBlastTarget;
use App\Models\Tier;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\FonnteService;
use App\Support\GatewaySetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        [$batchRows, $activeBatch, $targetRows] = $this->queueDashboardData();

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
            'queueBatches' => $batchRows,
            'activeBatch' => $activeBatch,
            'queueTargets' => $targetRows,
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return redirect()->route('signal-wa-blast.page');
        }

        try {
            [$payload, $targets] = $this->buildPayload($request);
        } catch (Throwable $e) {
            Log::error('Signal WA blast preview error', [
                'message' => $e->getMessage(),
            ]);
            [$batchRows, $activeBatch, $targetRows] = $this->queueDashboardData();

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
                'queueBatches' => $batchRows,
                'activeBatch' => $activeBatch,
                'queueTargets' => $targetRows,
            ])->with('status', 'Preview gagal: '.$e->getMessage());
        }

        [$batchRows, $activeBatch, $targetRows] = $this->queueDashboardData();

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
            'queueBatches' => $batchRows,
            'activeBatch' => $activeBatch,
            'queueTargets' => $targetRows,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->isMethod('get')) {
            return redirect()->route('signal-wa-blast.page');
        }

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

        $jobs = collect();
        foreach ($targets as $target) {
            foreach ($target['signal_items'] as $signalItem) {
                $jobs->push([
                    'client_name' => $target['name'],
                    'whatsapp_number' => $target['whatsapp_number'],
                    'tier_id' => $target['tier_id'],
                    'client_id' => $target['client_id'],
                    'signal_id' => $signalItem['signal_id'],
                    'signal_title' => $signalItem['signal_title'],
                    'message' => $signalItem['message'],
                    'image_url' => $signalItem['image_url'] ?: ($payload['image_url'] ?: null),
                ]);
            }
        }

        if ($jobs->isEmpty()) {
            return redirect()->route('signal-wa-blast.page')->with('status', 'Tidak ada job blast yang bisa dimasukkan ke antrian.');
        }

        try {
            $batch = DB::transaction(function () use ($request, $payload, $jobs, $targets) {
                $batch = SignalWaBlastBatch::query()->create([
                    'admin_id' => $request->user()->id,
                    'tier_id' => $payload['tier_id'],
                    'signal_ids' => $payload['signal_ids'],
                    'delay_seconds' => $payload['delay_seconds'],
                    'max_recipients' => $payload['max_recipients'],
                    'opening_text' => $payload['opening_text'],
                    'closing_text' => $payload['closing_text'],
                    'image_url' => $payload['image_url'] ?: null,
                    'status' => 'queued',
                    'total_targets' => $jobs->count(),
                    'pending_count' => $jobs->count(),
                    'sent_count' => 0,
                    'failed_count' => 0,
                ]);

                foreach ($jobs->chunk(300) as $chunk) {
                    $rows = $chunk->map(fn (array $job) => [
                        'batch_id' => $batch->id,
                        'client_id' => $job['client_id'],
                        'tier_id' => $job['tier_id'],
                        'signal_id' => $job['signal_id'],
                        'client_name' => $job['client_name'],
                        'signal_title' => $job['signal_title'],
                        'whatsapp_number' => $job['whatsapp_number'],
                        'message' => $job['message'],
                        'image_url' => $job['image_url'],
                        'status' => 'pending',
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all();
                    SignalWaBlastTarget::query()->insert($rows);
                }

                WaBlastLog::query()->create([
                    'admin_id' => $request->user()->id,
                    'message_template_id' => null,
                    'blast_type' => 'general',
                    'filters' => [
                        'source' => 'signal-batch-web',
                        'tier_id' => $payload['tier_id'],
                        'signal_ids' => $payload['signal_ids'],
                        'delay_seconds' => $payload['delay_seconds'],
                        'max_recipients' => $payload['max_recipients'],
                        'queue_batch_id' => $batch->id,
                    ],
                    'recipients_count' => $targets->count(),
                    'rendered_messages' => $jobs->toJson(),
                    'status' => 'queued',
                    'blasted_at' => now(),
                ]);

                return $batch;
            });
        } catch (Throwable $e) {
            Log::error('Signal WA blast enqueue failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('signal-wa-blast.page')
                ->with('status', 'Gagal memasukkan antrian blast: '.$e->getMessage());
        }

        try {
            \Artisan::call('signals:process-wa-queue', ['--batch_id' => $batch->id]);
        } catch (Throwable $e) {
            Log::warning('Signal WA queue immediate processing failed', [
                'batch_id' => $batch->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->route('signal-wa-blast.page', ['batch_id' => $batch->id])
            ->with('status', "WA Blast masuk antrian. Batch #{$batch->id}, total job: {$batch->total_targets}. Proses bertahap sesuai limit tier.");
    }

    public function resendFailed(SignalWaBlastBatch $batch): RedirectResponse
    {
        $failedCount = SignalWaBlastTarget::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'failed')
            ->count();

        if ($failedCount <= 0) {
            return redirect()
                ->route('signal-wa-blast.page', ['batch_id' => $batch->id])
                ->with('status', 'Tidak ada target failed untuk di-resend.');
        }

        DB::transaction(function () use ($batch): void {
            SignalWaBlastTarget::query()
                ->where('batch_id', $batch->id)
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'last_error' => null,
                    'response_payload' => null,
                    'sent_at' => null,
                    'updated_at' => now(),
                ]);

            $this->refreshBatchStats($batch);
        });

        try {
            \Artisan::call('signals:process-wa-queue', ['--batch_id' => $batch->id]);
        } catch (Throwable $e) {
            Log::warning('Signal WA resend failed batch immediate processing failed', [
                'batch_id' => $batch->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('signal-wa-blast.page', ['batch_id' => $batch->id])
            ->with('status', "Resend failed dijalankan untuk batch #{$batch->id} ({$failedCount} target).");
    }

    public function resendTarget(SignalWaBlastTarget $target): RedirectResponse
    {
        if ((string) $target->status !== 'failed') {
            return redirect()
                ->route('signal-wa-blast.page', ['batch_id' => $target->batch_id])
                ->with('status', 'Hanya target FAILED yang bisa di-resend.');
        }

        DB::transaction(function () use ($target): void {
            $target->forceFill([
                'status' => 'pending',
                'last_error' => null,
                'response_payload' => null,
                'sent_at' => null,
                'updated_at' => now(),
            ])->save();

            $batch = SignalWaBlastBatch::query()->find($target->batch_id);
            if ($batch) {
                $this->refreshBatchStats($batch);
            }
        });

        try {
            \Artisan::call('signals:process-wa-queue', ['--batch_id' => $target->batch_id]);
        } catch (Throwable $e) {
            Log::warning('Signal WA resend target immediate processing failed', [
                'target_id' => $target->id,
                'batch_id' => $target->batch_id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('signal-wa-blast.page', ['batch_id' => $target->batch_id])
            ->with('status', "Target #{$target->id} dimasukkan ulang ke antrian.");
    }

    private function availableSignals(): Collection
    {
        $now = now();

        return Signal::query()
            ->with('tiers:id,name')
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
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
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', 'regexp', '^(\\+62|62|0)?8[0-9]{7,13}$');

        if (! empty($payload['tier_id'])) {
            $clientsQuery->where('tier_id', $payload['tier_id']);
        }

        $clients = $clientsQuery->with('tier:id,name')->limit(1500)->get();
        $clients = $this->applyTierBlastLimit($clients);

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
                $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 0, '.', ',') : '-';
                $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 0, '.', ',') : '-';
                $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 0, '.', ',') : '-';

                return "- {$code} {$type} | Entry {$entry} | TP {$tp} | SL {$sl}";
            })->implode("\n");

            $header = str_replace('{name}', $client->name, $payload['opening_text']);
            $footer = str_replace('{name}', $client->name, $payload['closing_text']);
            $message = trim($header)."\n\n".$signalLines."\n\n".trim($footer);

            $signalItems = $matchedSignals->map(function (Signal $signal) use ($client, $payload) {
                $type = strtoupper((string) $signal->signal_type);
                $code = strtoupper((string) $signal->stock_code);
                $entry = $signal->entry_price !== null ? number_format((float) $signal->entry_price, 0, '.', ',') : '-';
                $tp = $signal->take_profit !== null ? number_format((float) $signal->take_profit, 0, '.', ',') : '-';
                $sl = $signal->stop_loss !== null ? number_format((float) $signal->stop_loss, 0, '.', ',') : '-';
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
                'client_id' => $client->id,
                'whatsapp_number' => $client->whatsapp_number,
                'tier_id' => $client->tier_id,
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

    private function applyTierBlastLimit(Collection $clients): \Illuminate\Support\Collection
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

        return collect($accepted)->values();
    }

    private function queueDashboardData(?int $batchId = null): array
    {
        $batchId = $batchId ?? (int) request('batch_id', 0);

        $batchRows = SignalWaBlastBatch::query()
            ->with('admin:id,name')
            ->latest()
            ->limit(20)
            ->get();

        $activeBatch = $batchId > 0
            ? SignalWaBlastBatch::query()->with('admin:id,name')->find($batchId)
            : $batchRows->first();

        $targetRows = SignalWaBlastTarget::query()
            ->when($activeBatch, fn ($query) => $query->where('batch_id', $activeBatch->id))
            ->orderBy('id')
            ->paginate(40, ['*'], 'targets_page');

        return [$batchRows, $activeBatch, $targetRows];
    }

    private function refreshBatchStats(SignalWaBlastBatch $batch): void
    {
        $base = SignalWaBlastTarget::query()->where('batch_id', $batch->id);
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $sentCount = (clone $base)->where('status', 'sent')->count();
        $failedCount = (clone $base)->where('status', 'failed')->count();

        $batch->forceFill([
            'pending_count' => $pendingCount,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $pendingCount > 0 ? 'queued' : ($failedCount > 0 ? 'partial' : 'completed'),
            'finished_at' => $pendingCount > 0 ? null : now(),
        ])->save();
    }
}
