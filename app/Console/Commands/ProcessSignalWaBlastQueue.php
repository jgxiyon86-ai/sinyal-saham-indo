<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Models\SignalWaBlastBatch;
use App\Models\SignalWaBlastTarget;
use App\Models\Tier;
use App\Services\FonnteService;
use App\Support\GatewaySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessSignalWaBlastQueue extends Command
{
    protected $signature = 'signals:process-wa-queue {--batch_id= : Proses hanya 1 batch id}';

    protected $description = 'Proses antrian WA Blast Sinyal bertahap sesuai batas per tier.';

    public function __construct(private readonly FonnteService $fonnteService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (GatewaySetting::appApiKey() === '' || GatewaySetting::sessionId() === '') {
            $this->warn('Gateway key/session belum diisi. Queue ditunda.');
            return self::SUCCESS;
        }

        $batchId = $this->option('batch_id');
        $batchesQuery = SignalWaBlastBatch::query()
            ->whereIn('status', ['queued', 'processing'])
            ->where('pending_count', '>', 0)
            ->orderBy('id');

        if ($batchId !== null && $batchId !== '') {
            $batchesQuery->where('id', (int) $batchId);
        } else {
            $batchesQuery->limit(3);
        }

        $batches = $batchesQuery->get();
        if ($batches->isEmpty()) {
            $this->info('Tidak ada queue signal WA yang perlu diproses.');
            return self::SUCCESS;
        }

        foreach ($batches as $batch) {
            $this->processBatch($batch);
        }

        return self::SUCCESS;
    }

    private function processBatch(SignalWaBlastBatch $batch): void
    {
        if ($batch->status === 'queued') {
            $batch->forceFill([
                'status' => 'processing',
                'started_at' => $batch->started_at ?? now(),
            ])->save();
        }

        $tierLimits = Tier::query()
            ->pluck('wa_blast_limit', 'id')
            ->map(fn ($value) => max(1, (int) $value))
            ->all();

        $pending = SignalWaBlastTarget::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->finalizeBatch($batch);
            return;
        }

        $picked = collect();
        foreach ($pending->groupBy('tier_id') as $tierId => $items) {
            $limit = $tierLimits[(int) $tierId] ?? 60;
            $picked = $picked->merge($items->take($limit));
        }
        $picked = $picked->sortBy('id')->values();

        if ($picked->isEmpty()) {
            $this->warn("Batch #{$batch->id}: tidak ada item terpilih.");
            return;
        }

        $delaySeconds = max(0, (int) $batch->delay_seconds);
        $lastIndex = $picked->count() - 1;

        foreach ($picked as $idx => $target) {
            try {
                $response = $this->fonnteService->sendMessage(
                    (string) $target->whatsapp_number,
                    (string) $target->message,
                    $target->image_url
                );

                $target->forceFill([
                    'status' => 'sent',
                    'attempts' => (int) $target->attempts + 1,
                    'response_payload' => $response,
                    'last_error' => null,
                    'sent_at' => now(),
                ])->save();
            } catch (RuntimeException $e) {
                $target->forceFill([
                    'status' => 'failed',
                    'attempts' => (int) $target->attempts + 1,
                    'last_error' => $e->getMessage(),
                ])->save();
            } catch (Throwable $e) {
                Log::error('Signal WA queue send unexpected error', [
                    'batch_id' => $batch->id,
                    'target_id' => $target->id,
                    'message' => $e->getMessage(),
                ]);
                $target->forceFill([
                    'status' => 'failed',
                    'attempts' => (int) $target->attempts + 1,
                    'last_error' => $e->getMessage(),
                ])->save();
            }

            if ($delaySeconds > 0 && $idx < $lastIndex) {
                sleep($delaySeconds);
            }
        }

        $this->refreshBatchCounters($batch);
        $this->finalizeBatch($batch);
    }

    private function refreshBatchCounters(SignalWaBlastBatch $batch): void
    {
        $base = SignalWaBlastTarget::query()->where('batch_id', $batch->id);
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $sentCount = (clone $base)->where('status', 'sent')->count();
        $failedCount = (clone $base)->where('status', 'failed')->count();

        $batch->forceFill([
            'pending_count' => $pendingCount,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $pendingCount > 0 ? 'processing' : ($failedCount > 0 ? 'partial' : 'completed'),
            'finished_at' => $pendingCount > 0 ? null : now(),
        ])->save();
    }

    private function finalizeBatch(SignalWaBlastBatch $batch): void
    {
        $batch->refresh();
        if ((int) $batch->pending_count > 0) {
            return;
        }

        $sentSignalIds = SignalWaBlastTarget::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'sent')
            ->whereNotNull('signal_id')
            ->pluck('signal_id')
            ->unique()
            ->values();

        if ($sentSignalIds->isNotEmpty()) {
            Signal::query()
                ->whereIn('id', $sentSignalIds->all())
                ->update(['wa_blasted_at' => now()]);
        }
    }
}

