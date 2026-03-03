<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Services\ClientTierRemapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TierPageController extends Controller
{
    public function __construct(private readonly ClientTierRemapService $clientTierRemapService)
    {
    }

    public function index(): View
    {
        return view('admin.tiers', [
            'tiers' => Tier::query()->withCount('clients')->orderBy('min_capital')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tiers-form', [
            'tier' => null,
        ]);
    }

    public function edit(Tier $tier): View
    {
        return view('admin.tiers-form', [
            'tier' => $tier,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        Tier::create($data);
        $result = $this->clientTierRemapService->remapAllClients();

        return redirect()->route('tiers.page')->with(
            'status',
            "Tier berhasil ditambahkan. Remap klient: {$result['changed']} berubah, {$result['no_tier']} tanpa tier."
        );
    }

    public function update(Request $request, Tier $tier): RedirectResponse
    {
        $tier->update($this->validatedData($request));
        $result = $this->clientTierRemapService->remapAllClients();

        return redirect()->route('tiers.page')->with(
            'status',
            "Tier berhasil diupdate. Remap klient: {$result['changed']} berubah, {$result['no_tier']} tanpa tier."
        );
    }

    public function syncAll(): RedirectResponse
    {
        $result = $this->clientTierRemapService->remapAllClients();

        return redirect()->route('tiers.page')->with(
            'status',
            "Sinkronisasi selesai. {$result['changed']} klient tiernya berubah, {$result['no_tier']} klient tanpa tier (modal di bawah batas)."
        );
    }

    public function destroy(Tier $tier): RedirectResponse
    {
        if ($tier->clients()->exists()) {
            return redirect()->route('tiers.page')->with('status', 'Tier dipakai klient, tidak bisa dihapus.');
        }

        $tier->delete();

        return redirect()->route('tiers.page')->with('status', 'Tier berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_capital' => ['required', 'numeric', 'min:0'],
            'max_capital' => ['nullable', 'numeric', 'gte:min_capital'],
            'description' => ['nullable', 'string'],
            'wa_blast_limit' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);
    }
}
