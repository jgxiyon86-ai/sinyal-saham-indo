<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Models\WaBlastLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillSignalWaBlastedAt extends Command
{
    protected $signature = 'signals:backfill-wa-blasted {--dry-run : Hanya tampilkan hasil tanpa update database}';

    protected $description = 'Isi wa_blasted_at dari riwayat WA blast sinyal lama.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $logs = WaBlastLog::query()
            ->where('blast_type', 'general')
            ->where(function ($query) {
                $query->where('filters->source', 'signal-batch-web')
                    ->orWhere('filters->source', 'signal-batch-api');
            })
            ->whereNotNull('rendered_messages')
            ->orderBy('id')
            ->get(['id', 'rendered_messages', 'blasted_at', 'created_at']);

        if ($logs->isEmpty()) {
            $this->info('Tidak ada log signal blast yang bisa dibackfill.');
            return self::SUCCESS;
        }

        $candidateUpdates = [];
        $messagesCount = 0;

        foreach ($logs as $log) {
            $payload = $this->decodeMessages($log->rendered_messages);
            if (! is_array($payload)) {
                continue;
            }

            foreach ($payload as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $signalId = isset($row['signal_id']) ? (int) $row['signal_id'] : 0;
                $status = strtolower((string) ($row['status'] ?? ''));

                if ($signalId <= 0 || $status !== 'sent') {
                    continue;
                }

                $messagesCount++;
                $timestamp = $this->resolveTimestamp($log->blasted_at, $log->created_at);
                if (! isset($candidateUpdates[$signalId]) || $candidateUpdates[$signalId]->lt($timestamp)) {
                    $candidateUpdates[$signalId] = $timestamp;
                }
            }
        }

        if (empty($candidateUpdates)) {
            $this->info('Tidak ada pasangan signal_id/status=sent yang ditemukan di log lama.');
            return self::SUCCESS;
        }

        $signalIds = array_keys($candidateUpdates);
        $existingSignals = Signal::query()
            ->whereIn('id', $signalIds)
            ->get(['id', 'wa_blasted_at'])
            ->keyBy('id');

        $updateRows = [];
        $skippedAlreadyFilled = 0;

        foreach ($candidateUpdates as $signalId => $blastAt) {
            $signal = $existingSignals->get($signalId);
            if (! $signal) {
                continue;
            }

            if ($signal->wa_blasted_at !== null) {
                $skippedAlreadyFilled++;
                continue;
            }

            $updateRows[$signalId] = $blastAt;
        }

        $this->line('Total log dipindai: '.$logs->count());
        $this->line('Total item pesan sent ditemukan: '.$messagesCount);
        $this->line('Calon signal terdeteksi: '.count($candidateUpdates));
        $this->line('Signal sudah punya wa_blasted_at (skip): '.$skippedAlreadyFilled);
        $this->line('Signal siap diupdate: '.count($updateRows));

        if ($isDryRun) {
            $preview = collect($updateRows)->take(20)->map(function (Carbon $at, int $id) {
                return "#{$id} => ".$at->toDateTimeString();
            });
            if ($preview->isNotEmpty()) {
                $this->newLine();
                $this->info('Preview (maks 20 baris):');
                foreach ($preview as $line) {
                    $this->line($line);
                }
            }

            $this->comment('Dry-run selesai, tidak ada perubahan database.');
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($updateRows as $signalId => $blastAt) {
            $updated += Signal::query()
                ->where('id', $signalId)
                ->whereNull('wa_blasted_at')
                ->update(['wa_blasted_at' => $blastAt]);
        }

        $this->info("Backfill selesai. Total signal terupdate: {$updated}");

        return self::SUCCESS;
    }

    /**
     * @param mixed $messages
     * @return array<int, mixed>|null
     */
    private function decodeMessages(mixed $messages): ?array
    {
        if (is_array($messages)) {
            return $messages;
        }

        if (! is_string($messages) || trim($messages) === '') {
            return null;
        }

        $decoded = json_decode($messages, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function resolveTimestamp(mixed $blastedAt, mixed $createdAt): Carbon
    {
        if ($blastedAt instanceof Carbon) {
            return $blastedAt;
        }

        if (is_string($blastedAt) && trim($blastedAt) !== '') {
            return Carbon::parse($blastedAt);
        }

        if ($createdAt instanceof Carbon) {
            return $createdAt;
        }

        if (is_string($createdAt) && trim($createdAt) !== '') {
            return Carbon::parse($createdAt);
        }

        return now();
    }
}

