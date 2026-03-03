<?php

namespace App\Services;

use App\Models\Tier;
use App\Models\User;

class ClientTierRemapService
{
    /**
     * Remap tier client berdasarkan capital_amount.
     * Rule:
     * - modal <= 0 => tier_id NULL
     * - modal > 0  => cari tier sesuai range min/max
     */
    public function remapAllClients(): array
    {
        $tiers = Tier::query()->orderBy('min_capital')->get();
        if ($tiers->isEmpty()) {
            return [
                'total_clients' => 0,
                'changed' => 0,
                'no_tier' => 0,
            ];
        }

        $changed = 0;
        $total = 0;

        User::query()
            ->where('role', 'client')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($tiers, &$changed, &$total): void {
                foreach ($rows as $client) {
                    $total++;
                    $capital = (float) ($client->capital_amount ?? 0);
                    $newTierId = $this->resolveTierId($capital, $tiers);

                    if ((int) ($client->tier_id ?? 0) !== (int) ($newTierId ?? 0)) {
                        $client->tier_id = $newTierId;
                        $client->save();
                        $changed++;
                    }
                }
            });

        $noTier = User::query()
            ->where('role', 'client')
            ->whereNull('tier_id')
            ->count();

        return [
            'total_clients' => $total,
            'changed' => $changed,
            'no_tier' => $noTier,
        ];
    }

    private function resolveTierId(float $capital, $tiers): ?int
    {
        if ($capital <= 0) {
            return null;
        }

        foreach ($tiers as $tier) {
            $min = (float) ($tier->min_capital ?? 0);
            $max = $tier->max_capital !== null ? (float) $tier->max_capital : null;
            if ($capital >= $min && ($max === null || $capital <= $max)) {
                return (int) $tier->id;
            }
        }

        $highest = $tiers->sortByDesc('min_capital')->first();
        return $highest ? (int) $highest->id : null;
    }
}

