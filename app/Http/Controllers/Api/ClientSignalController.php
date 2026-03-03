<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSignalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Endpoint ini untuk client.',
            ], 403);
        }

        if (! (bool) $user->is_active) {
            return response()->json([
                'signals' => [],
                'message' => 'Akun klient nonaktif. Hubungi admin.',
            ], 403);
        }

        if (! $user->tier_id) {
            return response()->json([
                'signals' => [],
                'message' => 'Tier client belum ditentukan.',
            ]);
        }

        $signals = Signal::query()
            ->with(['tiers:id,name', 'creator:id,name'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->whereHas('tiers', fn ($query) => $query->where('tiers.id', $user->tier_id))
            ->latest('published_at')
            ->get();

        return response()->json([
            'signals' => $signals,
        ]);
    }
}
