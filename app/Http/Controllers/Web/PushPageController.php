<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Services\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PushPageController extends Controller
{
    public function __construct(private readonly FcmService $fcmService)
    {
    }

    public function index(): View
    {
        return view('admin.push-broadcast', [
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
        ]);
    }

    public function send(Request $request): RedirectResponse
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
            data: [
                'source' => 'web-admin',
                'sent_by' => (string) $request->user()->id,
            ],
        );

        $status = "Push diproses. Terkirim: {$result['sent']}, gagal: {$result['failed']}.";
        if (! $result['enabled']) {
            $status .= ' '.$result['message'];
        }

        return redirect()->route('push.page')->with('status', $status);
    }
}

