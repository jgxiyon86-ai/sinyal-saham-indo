<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Services\ClientTierRemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TierController extends Controller
{
    public function __construct(private readonly ClientTierRemapService $clientTierRemapService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_capital' => ['required', 'numeric', 'min:0'],
            'max_capital' => ['nullable', 'numeric', 'gte:min_capital'],
            'description' => ['nullable', 'string'],
            'wa_blast_limit' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $tier = Tier::create($data);
        $result = $this->clientTierRemapService->remapAllClients();

        return response()->json([
            'message' => 'Tier berhasil dibuat.',
            'tier' => $tier,
            'remap' => $result,
        ], 201);
    }

    public function show(Tier $tier): JsonResponse
    {
        return response()->json([
            'tier' => $tier->loadCount('clients'),
        ]);
    }

    public function update(Request $request, Tier $tier): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_capital' => ['required', 'numeric', 'min:0'],
            'max_capital' => ['nullable', 'numeric', 'gte:min_capital'],
            'description' => ['nullable', 'string'],
            'wa_blast_limit' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $tier->update($data);
        $result = $this->clientTierRemapService->remapAllClients();

        return response()->json([
            'message' => 'Tier berhasil diupdate.',
            'tier' => $tier,
            'remap' => $result,
        ]);
    }

    public function destroy(Tier $tier): JsonResponse
    {
        if ($tier->clients()->exists()) {
            return response()->json([
                'message' => 'Tier masih dipakai client, tidak bisa dihapus.',
            ], 422);
        }

        $tier->delete();

        return response()->json([
            'message' => 'Tier berhasil dihapus.',
        ]);
    }
}
