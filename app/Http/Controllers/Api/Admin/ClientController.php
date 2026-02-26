<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Models\User;
use App\Support\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    private array $religions;

    public function __construct()
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('tier')->where('role', 'client');

        if ($request->filled('tier_id')) {
            $query->where('tier_id', $request->integer('tier_id'));
        }

        if ($request->filled('religion')) {
            $query->where('religion', $request->string('religion'));
        }

        return response()->json([
            'clients' => $query->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:'.WaNumber::validationRegex()],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'capital_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['whatsapp_number'] = WaNumber::normalize($data['whatsapp_number'] ?? null);

        $tier = $this->findTierByCapital((float) $data['capital_amount']);

        $client = User::create([
            ...$data,
            'role' => 'client',
            'tier_id' => $tier?->id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Client berhasil dibuat.',
            'client' => $client->load('tier'),
        ], 201);
    }

    public function show(User $client): JsonResponse
    {
        $this->ensureClientRole($client);

        return response()->json([
            'client' => $client->load('tier'),
        ]);
    }

    public function update(Request $request, User $client): JsonResponse
    {
        $this->ensureClientRole($client);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:'.WaNumber::validationRegex()],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'capital_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['whatsapp_number'] = WaNumber::normalize($data['whatsapp_number'] ?? null);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $tier = $this->findTierByCapital((float) $data['capital_amount']);
        $data['tier_id'] = $tier?->id;

        $client->update($data);

        return response()->json([
            'message' => 'Client berhasil diupdate.',
            'client' => $client->load('tier'),
        ]);
    }

    public function pindah(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to_tier_id' => ['required', 'exists:tiers,id'],
            'client_ids' => ['required_without:from_tier_id', 'array'],
            'client_ids.*' => ['integer', 'exists:users,id'],
            'from_tier_id' => ['required_without:client_ids', 'integer', 'exists:tiers,id'],
        ]);

        $query = User::query()->where('role', 'client');

        if ($request->has('client_ids')) {
            $query->whereIn('id', $request->input('client_ids'));
        } else {
            $query->where('tier_id', $request->input('from_tier_id'));
        }

        $count = $query->count();
        $query->update(['tier_id' => $data['to_tier_id']]);

        return response()->json([
            'message' => "Berhasil memindahkan $count client ke tier baru.",
            'count' => $count,
        ]);
    }

    public function destroy(User $client): JsonResponse
    {
        $this->ensureClientRole($client);
        $client->delete();

        return response()->json([
            'message' => 'Client berhasil dihapus.',
        ]);
    }

    private function ensureClientRole(User $user): void
    {
        if ($user->role !== 'client') {
            abort(404);
        }
    }

    private function findTierByCapital(float $capital): ?Tier
    {
        return Tier::query()
            ->where('min_capital', '<=', $capital)
            ->where(function ($query) use ($capital) {
                $query->whereNull('max_capital')
                    ->orWhere('max_capital', '>=', $capital);
            })
            ->orderByDesc('min_capital')
            ->first();
    }
}
