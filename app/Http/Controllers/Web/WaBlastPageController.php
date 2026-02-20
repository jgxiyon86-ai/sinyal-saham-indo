<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Tier;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\FonnteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class WaBlastPageController extends Controller
{
    private array $religions;

    public function __construct(private readonly FonnteService $fonnteService)
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(): View
    {
        return view('admin.wa-blast', [
            'templates' => MessageTemplate::where('is_active', true)->orderBy('name')->get(),
            'tiers' => Tier::orderBy('min_capital')->get(),
            'religions' => config('religions.options', []),
            'preview' => null,
            'logs' => WaBlastLog::with('template')->latest()->paginate(20),
        ]);
    }

    public function preview(Request $request): View
    {
        [$data, $template, $date, $messages] = $this->buildMessages($request);

        WaBlastLog::create([
            'admin_id' => $request->user()->id,
            'message_template_id' => $template->id,
            'blast_type' => $template->event_type,
            'filters' => [
                'tier_id' => $data['tier_id'] ?? null,
                'religion' => $data['religion'] ?? null,
                'date' => $date->toDateString(),
            ],
            'recipients_count' => $messages->count(),
            'rendered_messages' => $messages->toJson(),
            'status' => 'preview',
        ]);

        return view('admin.wa-blast', [
            'templates' => MessageTemplate::where('is_active', true)->orderBy('name')->get(),
            'tiers' => Tier::orderBy('min_capital')->get(),
            'religions' => config('religions.options', []),
            'preview' => [
                'template' => $template,
                'date' => $date->toDateString(),
                'count' => $messages->count(),
                'filters' => [
                    'tier_id' => $data['tier_id'] ?? null,
                    'religion' => $data['religion'] ?? null,
                ],
                'messages' => $messages,
            ],
            'logs' => WaBlastLog::with('template')->latest()->paginate(20),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        [$data, $template, $date, $messages] = $this->buildMessages($request);

        if ((string) config('services.fonnte.token') === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'FONNTE_TOKEN belum diisi di file .env');
        }

        if ($messages->isEmpty()) {
            return redirect()->route('wa-blast.page')->with('status', 'Tidak ada target untuk dikirim.');
        }

        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($messages as $item) {
            try {
                $response = $this->fonnteService->sendMessage((string) $item['whatsapp_number'], (string) $item['message']);
                $success++;
                $results[] = [
                    ...$item,
                    'status' => 'sent',
                    'response' => $response,
                ];
            } catch (RuntimeException $e) {
                $failed++;
                $results[] = [
                    ...$item,
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ];
            }
        }

        WaBlastLog::create([
            'admin_id' => $request->user()->id,
            'message_template_id' => $template->id,
            'blast_type' => $template->event_type,
            'filters' => [
                'tier_id' => $data['tier_id'] ?? null,
                'religion' => $data['religion'] ?? null,
                'date' => $date->toDateString(),
            ],
            'recipients_count' => $messages->count(),
            'rendered_messages' => collect($results)->toJson(),
            'status' => $failed > 0 ? 'partial' : 'sent',
            'blasted_at' => now(),
        ]);

        return redirect()->route('wa-blast.page')
            ->with('status', "Kirim WA selesai. Berhasil: {$success}, Gagal: {$failed}.");
    }

    public function manualSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string'],
        ]);

        if ((string) config('services.fonnte.token') === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'FONNTE_TOKEN belum diisi di file .env');
        }

        try {
            $response = $this->fonnteService->sendMessage($data['whatsapp_number'], $data['message']);

            WaBlastLog::create([
                'admin_id' => $request->user()->id,
                'message_template_id' => null,
                'blast_type' => 'general',
                'filters' => ['source' => 'manual-send'],
                'recipients_count' => 1,
                'rendered_messages' => collect([
                    [
                        'name' => 'Manual',
                        'whatsapp_number' => $data['whatsapp_number'],
                        'message' => $data['message'],
                        'status' => 'sent',
                        'response' => $response,
                    ],
                ])->toJson(),
                'status' => 'manual-sent',
                'blasted_at' => now(),
            ]);

            return redirect()->route('wa-blast.page')->with('status', 'Manual send berhasil dikirim.');
        } catch (RuntimeException $e) {
            WaBlastLog::create([
                'admin_id' => $request->user()->id,
                'message_template_id' => null,
                'blast_type' => 'general',
                'filters' => ['source' => 'manual-send'],
                'recipients_count' => 1,
                'rendered_messages' => collect([
                    [
                        'name' => 'Manual',
                        'whatsapp_number' => $data['whatsapp_number'],
                        'message' => $data['message'],
                        'status' => 'failed',
                        'response' => $e->getMessage(),
                    ],
                ])->toJson(),
                'status' => 'manual-failed',
                'blasted_at' => now(),
            ]);

            return redirect()->route('wa-blast.page')->with('status', 'Manual send gagal: '.$e->getMessage());
        }
    }

    private function buildMessages(Request $request): array
    {
        $data = $request->validate([
            'message_template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'date' => ['nullable', 'date'],
        ]);

        $template = MessageTemplate::findOrFail($data['message_template_id']);
        $date = ! empty($data['date']) ? Carbon::parse($data['date']) : now();

        $clientsQuery = $this->baseClientQuery();
        if (! empty($data['tier_id'])) {
            $clientsQuery->where('tier_id', $data['tier_id']);
        }

        if (! empty($template->religion)) {
            $clientsQuery->where('religion', $template->religion);
        } elseif (! empty($data['religion'])) {
            $clientsQuery->where('religion', $data['religion']);
        }

        if ($template->event_type === 'birthday') {
            $clientsQuery->whereMonth('birth_date', $date->month)
                ->whereDay('birth_date', $date->day);
        }

        $clients = $clientsQuery->with('tier')->limit(200)->get();
        $messages = $clients->map(function (User $client) use ($template, $date) {
            return [
                'name' => $client->name,
                'whatsapp_number' => $client->whatsapp_number,
                'tier' => optional($client->tier)->name,
                'religion' => $client->religion,
                'message' => $this->renderTemplate($template->content, $client, $date),
            ];
        });

        return [$data, $template, $date, $messages];
    }

    private function baseClientQuery(): Builder
    {
        return User::query()
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('whatsapp_number');
    }

    private function renderTemplate(string $content, User $client, Carbon $date): string
    {
        return strtr($content, [
            '{name}' => $client->name,
            '{religion}' => (string) $client->religion,
            '{birth_date}' => (string) $client->birth_date?->format('Y-m-d'),
            '{capital_amount}' => (string) $client->capital_amount,
            '{tier}' => (string) optional($client->tier)->name,
            '{date}' => $date->toDateString(),
        ]);
    }
}
