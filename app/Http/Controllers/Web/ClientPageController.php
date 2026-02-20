<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ClientCredentialsMail;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ClientPageController extends Controller
{
    private array $religions;

    public function __construct()
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(Request $request): View
    {
        $query = User::query()
            ->with('tier')
            ->where('role', 'client')
            ->latest();

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('whatsapp_number', 'like', "%{$term}%");
            });
        }

        if ($request->filled('tier_id')) {
            $query->where('tier_id', $request->integer('tier_id'));
        }

        if ($request->filled('religion')) {
            $query->where('religion', $request->string('religion'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->integer('is_active'));
        }

        return view('admin.clients', [
            'clients' => $query->paginate(25)->withQueryString(),
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
            'religions' => config('religions.options', []),
        ]);
    }

    public function create(): View
    {
        return view('admin.clients-form', [
            'client' => null,
            'religions' => config('religions.options', []),
        ]);
    }

    public function edit(User $client): View
    {
        abort_unless($client->role === 'client', 404);

        return view('admin.clients-form', [
            'client' => $client,
            'religions' => config('religions.options', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $tier = $this->findTierByCapital((float) $data['capital_amount']);
        $plainPassword = $data['password'];

        $client = User::create([
            ...$data,
            'role' => 'client',
            'tier_id' => $tier?->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $status = 'Klient berhasil ditambahkan.';
        if ($request->boolean('send_email_credentials', true)) {
            $status .= $this->trySendCredentialsEmail($client, $plainPassword)
                ? ' Email kredensial berhasil dikirim.'
                : ' Klient tersimpan, tapi email kredensial gagal dikirim.';
        }

        return redirect()->route('clients.page')->with('status', $status);
    }

    public function update(Request $request, User $client): RedirectResponse
    {
        abort_unless($client->role === 'client', 404);

        $data = $this->validatedData($request, $client->id);
        $plainPassword = $data['password'] ?? null;
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $tier = $this->findTierByCapital((float) $data['capital_amount']);
        $data['tier_id'] = $tier?->id;
        $data['is_active'] = $request->boolean('is_active');

        $client->update($data);

        $status = 'Data klient berhasil diupdate.';
        if (! empty($plainPassword) && $request->boolean('send_email_credentials')) {
            $status .= $this->trySendCredentialsEmail($client, $plainPassword)
                ? ' Email kredensial berhasil dikirim.'
                : ' Data tersimpan, tapi email kredensial gagal dikirim.';
        }

        return redirect()->route('clients.page')->with('status', $status);
    }

    public function destroy(User $client): RedirectResponse
    {
        abort_unless($client->role === 'client', 404);
        $client->delete();

        return redirect()->route('clients.page')->with('status', 'Klient berhasil dihapus.');
    }

    public function sendCredentials(User $client): RedirectResponse
    {
        abort_unless($client->role === 'client', 404);

        $temporaryPassword = $this->generateTemporaryPassword();
        $client->update(['password' => $temporaryPassword]);

        $ok = $this->trySendCredentialsEmail($client, $temporaryPassword);
        $status = $ok
            ? 'Password baru dibuat dan email kredensial berhasil dikirim.'
            : 'Password baru sudah dibuat, tapi email gagal dikirim.';

        return redirect()->route('clients.page')->with('status', $status);
    }

    private function validatedData(Request $request, ?int $ignoreUserId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'capital_amount' => ['required', 'numeric', 'min:0'],
        ]);
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

    private function trySendCredentialsEmail(User $client, string $plainPassword): bool
    {
        try {
            Mail::to($client->email)->send(new ClientCredentialsMail($client, $plainPassword));
            return true;
        } catch (Throwable $e) {
            Log::error('Gagal kirim email kredensial klient', [
                'client_id' => $client->id,
                'email' => $client->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(Str::random(4)).random_int(1000, 9999);
    }
}
