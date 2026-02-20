<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function __construct(private readonly FcmService $fcmService)
    {
    }

    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
        ]);

        $result = $this->fcmService->broadcastToTierClients(
            title: $data['title'],
            body: $data['body'],
            tierIds: isset($data['tier_id']) ? [(int) $data['tier_id']] : null,
        );

        return response()->json([
            'message' => 'Broadcast push diproses.',
            'result' => $result,
        ]);
    }
}

