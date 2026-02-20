<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\Tier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SignalController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'signals' => Signal::query()
                ->with(['tiers:id,name', 'creator:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stock_code' => ['required', 'string', 'max:20'],
            'signal_type' => ['required', Rule::in(['buy', 'sell', 'hold'])],
            'entry_price' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'tier_target' => ['nullable', 'string'],
            'tier_ids' => ['nullable', 'array', 'min:1'],
            'tier_ids.*' => ['integer', 'exists:tiers,id'],
        ]);

        $tierIds = $this->resolveTierIdsFromPayload($data);
        unset($data['tier_ids'], $data['tier_target']);

        $signal = Signal::create([
            ...$data,
            'created_by' => $request->user()->id,
            'published_at' => $data['published_at'] ?? now(),
            'push_sent_at' => null,
        ]);

        $signal->tiers()->sync($tierIds);

        return response()->json([
            'message' => 'Signal berhasil dibuat.',
            'signal' => $signal->load(['tiers:id,name', 'creator:id,name']),
        ], 201);
    }

    public function show(Signal $signal): JsonResponse
    {
        return response()->json([
            'signal' => $signal->load(['tiers:id,name', 'creator:id,name']),
        ]);
    }

    public function update(Request $request, Signal $signal): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stock_code' => ['required', 'string', 'max:20'],
            'signal_type' => ['required', Rule::in(['buy', 'sell', 'hold'])],
            'entry_price' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'tier_target' => ['nullable', 'string'],
            'tier_ids' => ['nullable', 'array', 'min:1'],
            'tier_ids.*' => ['integer', 'exists:tiers,id'],
        ]);

        $tierIds = $this->resolveTierIdsFromPayload($data);
        unset($data['tier_ids'], $data['tier_target']);

        $signal->update([
            ...$data,
            'push_sent_at' => null,
        ]);
        $signal->tiers()->sync($tierIds);

        return response()->json([
            'message' => 'Signal berhasil diupdate.',
            'signal' => $signal->load(['tiers:id,name', 'creator:id,name']),
        ]);
    }

    public function destroy(Signal $signal): JsonResponse
    {
        $signal->delete();

        return response()->json([
            'message' => 'Signal berhasil dihapus.',
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function resolveTierIdsFromPayload(array $data): array
    {
        $tierTarget = $data['tier_target'] ?? null;

        if ($tierTarget === 'all') {
            $ids = Tier::query()->pluck('id')->toArray();
            if (empty($ids)) {
                throw ValidationException::withMessages([
                    'tier_target' => 'Tier belum tersedia.',
                ]);
            }

            return $ids;
        }

        if (is_string($tierTarget) && ctype_digit($tierTarget)) {
            $tierId = (int) $tierTarget;
            if (! Tier::query()->whereKey($tierId)->exists()) {
                throw ValidationException::withMessages([
                    'tier_target' => 'Tier target tidak ditemukan.',
                ]);
            }

            return [$tierId];
        }

        if (! empty($data['tier_ids']) && is_array($data['tier_ids'])) {
            return array_values(array_unique(array_map('intval', $data['tier_ids'])));
        }

        throw ValidationException::withMessages([
            'tier_target' => 'Pilih tier target atau isi tier_ids.',
        ]);
    }
}
