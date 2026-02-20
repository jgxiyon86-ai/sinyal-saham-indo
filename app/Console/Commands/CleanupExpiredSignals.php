<?php

namespace App\Console\Commands;

use App\Models\Signal;
use Illuminate\Console\Command;

class CleanupExpiredSignals extends Command
{
    protected $signature = 'signals:cleanup-expired {--grace-days=30}';

    protected $description = 'Hapus sinyal yang sudah expired melewati masa grace period.';

    public function handle(): int
    {
        $graceDays = max(0, (int) $this->option('grace-days'));
        $threshold = now()->subDays($graceDays);

        $deleted = Signal::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $threshold)
            ->delete();

        $this->info("Cleanup selesai. Dihapus: {$deleted} sinyal.");

        return self::SUCCESS;
    }
}

