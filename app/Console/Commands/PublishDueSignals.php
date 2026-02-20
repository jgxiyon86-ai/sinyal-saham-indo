<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishDueSignals extends Command
{
    protected $signature = 'signals:publish-due';

    protected $description = 'Kirim push untuk sinyal yang sudah masuk waktu publikasi dan belum pernah dipush.';

    public function __construct(private readonly FcmService $fcmService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $signals = Signal::query()
            ->with('tiers:id')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->whereNull('push_sent_at')
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->orderBy('published_at')
            ->limit(100)
            ->get();

        if ($signals->isEmpty()) {
            $this->info('Tidak ada sinyal yang perlu dipublish.');

            return self::SUCCESS;
        }

        $sentSignals = 0;
        foreach ($signals as $signal) {
            try {
                $result = $this->fcmService->pushSignalToTierClients($signal);
                $signal->forceFill(['push_sent_at' => now()])->save();
                $sentSignals++;

                $this->line("Signal #{$signal->id} diproses. Sent: {$result['sent']}, failed: {$result['failed']}");
            } catch (Throwable $e) {
                Log::error('Gagal publish due signal', [
                    'signal_id' => $signal->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Signal #{$signal->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Publish due signals selesai. Total diproses: {$sentSignals}.");

        return self::SUCCESS;
    }
}

