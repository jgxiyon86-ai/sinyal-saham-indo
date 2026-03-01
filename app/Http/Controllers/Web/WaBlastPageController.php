<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Tier;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\AlimaGatewayService;
use App\Support\GatewaySetting;
use App\Support\WaNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class WaBlastPageController extends Controller
{
    private array $religions;

    public function __construct(private readonly AlimaGatewayService $alimaGatewayService)
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

        if (GatewaySetting::appApiKey() === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Gateway API Key belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if (GatewaySetting::sessionId() === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Gateway Session ID belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if ($messages->isEmpty()) {
            return redirect()->route('wa-blast.page')->with('status', 'Tidak ada target untuk dikirim.');
        }

        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($messages as $item) {
            try {
                $response = $this->alimaGatewayService->sendMessage(
                    (string) $item['whatsapp_number'],
                    (string) $item['message'],
                    $template->image_url
                );
                $success++;
                $results[] = [
                    ...$item,
                    'image_url' => $template->image_url,
                    'status' => 'sent',
                    'response' => $response,
                ];
            } catch (RuntimeException $e) {
                $failed++;
                $results[] = [
                    ...$item,
                    'image_url' => $template->image_url,
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
            'whatsapp_number' => ['required', 'string', 'max:30', 'regex:'.WaNumber::validationRegex()],
            'message' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'max:4096'],
        ]);
        $data['whatsapp_number'] = WaNumber::normalize($data['whatsapp_number']) ?? '';

        if (GatewaySetting::appApiKey() === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Gateway API Key belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        if (GatewaySetting::sessionId() === '') {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Gateway Session ID belum diisi. Isi dari menu Pengaturan Gateway atau file .env.');
        }

        $resolvedImageUrl = $data['image_url'] ?? null;
        if ($request->hasFile('image_file')) {
            $resolvedImageUrl = $this->storeImageAndGetUrl($request->file('image_file'));
        }
        $resolvedImageUrl = $this->normalizeOutgoingImageUrl($resolvedImageUrl);
        if (!blank($resolvedImageUrl) && str_ends_with(strtolower((string) parse_url($resolvedImageUrl, PHP_URL_HOST)), '.coi')) {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Image URL terdeteksi typo domain (.coi). Gunakan domain .com atau kosongkan URL jika pakai upload.');
        }

        if (blank($data['message'] ?? null) && blank($resolvedImageUrl)) {
            return redirect()->route('wa-blast.page')
                ->with('status', 'Isi pesan atau image URL wajib diisi.');
        }

        try {
            $response = $this->alimaGatewayService->sendMessage(
                $data['whatsapp_number'],
                (string) ($data['message'] ?? ''),
                $resolvedImageUrl
            );

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
                        'message' => (string) ($data['message'] ?? ''),
                        'image_url' => $resolvedImageUrl,
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
                        'message' => (string) ($data['message'] ?? ''),
                        'image_url' => $resolvedImageUrl,
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

    public function uploadImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clipboard_image' => ['required', 'image', 'max:4096'],
        ]);

        $url = $this->storeImageAndGetUrl($data['clipboard_image']);

        return response()->json([
            'status' => true,
            'url' => $url,
        ]);
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

        $clients = $clientsQuery->with('tier')->limit(1500)->get();

        // Filter valid numbers in PHP
        $clients = $clients->filter(function ($client) {
            return preg_match('/^(\+62|62|0)?8[0-9]{7,13}$/', (string) $client->whatsapp_number);
        });

        $limitedClients = $this->applyTierBlastLimit($clients);

        $messages = $limitedClients->map(function (User $client) use ($template, $date) {
            return [
                'name' => $client->name,
                'whatsapp_number' => $client->whatsapp_number,
                'tier' => optional($client->tier)->name,
                'religion' => $client->religion,
                'message' => $this->renderTemplate($template->content, $client, $date),
                'image_url' => $template->image_url,
            ];
        });

        return [$data, $template, $date, $messages];
    }

    private function applyTierBlastLimit(Collection $clients): Collection
    {
        $tierLimits = Tier::query()
            ->pluck('wa_blast_limit', 'id')
            ->map(fn ($value) => max(1, (int) $value))
            ->all();

        $bucket = [];
        $accepted = [];

        foreach ($clients as $client) {
            $tierId = (int) ($client->tier_id ?? 0);
            if ($tierId <= 0) {
                continue;
            }

            $limit = $tierLimits[$tierId] ?? 60;
            $current = $bucket[$tierId] ?? 0;
            if ($current >= $limit) {
                continue;
            }

            $accepted[] = $client;
            $bucket[$tierId] = $current + 1;
        }

        return collect($accepted)->values();
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

    private function storeImageAndGetUrl(UploadedFile $file): string
    {
        $path = $file->store('wa-manual-images', 'public');
        $url = Storage::disk('public')->url($path);

        return $this->normalizeOutgoingImageUrl($url) ?? $url;
    }

    private function normalizeOutgoingImageUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $trimmed = trim($url);
        if (preg_match('/^https?:\/\//i', $trimmed) === 1) {
            return $trimmed;
        }

        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            return $trimmed;
        }

        return $base.'/'.ltrim($trimmed, '/');
    }
}
