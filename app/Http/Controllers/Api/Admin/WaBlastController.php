<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\WaBlastLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class WaBlastController extends Controller
{
    private array $religions;

    public function __construct()
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->baseClientQuery();

        if ($request->filled('tier_id')) {
            $query->where('tier_id', $request->integer('tier_id'));
        }

        if ($request->filled('religion')) {
            $query->where('religion', $request->string('religion'));
        }

        return response()->json([
            'targets' => $query->get($this->targetColumns()),
        ]);
    }

    public function birthdayTargets(Request $request): JsonResponse
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'))
            : now();

        $query = $this->baseClientQuery()
            ->whereMonth('birth_date', $date->month)
            ->whereDay('birth_date', $date->day);

        if ($request->filled('tier_id')) {
            $query->where('tier_id', $request->integer('tier_id'));
        }

        if ($request->filled('religion')) {
            $query->where('religion', $request->string('religion'));
        }

        return response()->json([
            'date' => $date->toDateString(),
            'targets' => $query->get($this->targetColumns()),
        ]);
    }

    public function holidayTargets(Request $request): JsonResponse
    {
        $data = $request->validate([
            'religion' => ['required', Rule::in($this->religions)],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
        ]);

        $query = $this->baseClientQuery()
            ->where('religion', $data['religion']);

        if (! empty($data['tier_id'])) {
            $query->where('tier_id', $data['tier_id']);
        }

        return response()->json([
            'religion' => $data['religion'],
            'targets' => $query->get($this->targetColumns()),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'exists:users,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'date' => ['nullable', 'date'],
        ]);

        $template = MessageTemplate::query()->findOrFail($data['message_template_id']);
        $query = $this->baseClientQuery();

        if (! empty($data['client_ids'])) {
            $query->whereIn('id', $data['client_ids']);
        }

        if (! empty($data['tier_id'])) {
            $query->where('tier_id', $data['tier_id']);
        }

        if (! empty($template->religion)) {
            $query->where('religion', $template->religion);
        } elseif (! empty($data['religion'])) {
            $query->where('religion', $data['religion']);
        }

        $date = ! empty($data['date']) ? Carbon::parse($data['date']) : now();

        if ($template->event_type === 'birthday') {
            $query->whereMonth('birth_date', $date->month)
                ->whereDay('birth_date', $date->day);
        }

        $targets = $query->get($this->targetColumns());
        $rendered = $targets->map(function (User $client) use ($template, $date) {
            return [
                'client_id' => $client->id,
                'name' => $client->name,
                'whatsapp_number' => $client->whatsapp_number,
                'message' => $this->renderTemplate($template->content, $client, $date),
            ];
        });

        WaBlastLog::create([
            'admin_id' => $request->user()->id,
            'message_template_id' => $template->id,
            'blast_type' => $template->event_type,
            'filters' => [
                'client_ids' => $data['client_ids'] ?? [],
                'tier_id' => $data['tier_id'] ?? null,
                'religion' => $data['religion'] ?? null,
                'date' => $date->toDateString(),
            ],
            'recipients_count' => $rendered->count(),
            'rendered_messages' => $rendered->toJson(),
            'status' => 'preview',
        ]);

        return response()->json([
            'template' => $template,
            'recipients_count' => $rendered->count(),
            'messages' => $rendered,
        ]);
    }

    private function baseClientQuery(): Builder
    {
        return User::query()
            ->with('tier:id,name')
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('whatsapp_number');
    }

    private function targetColumns(): array
    {
        return [
            'id',
            'name',
            'whatsapp_number',
            'tier_id',
            'capital_amount',
            'birth_date',
            'religion',
        ];
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
