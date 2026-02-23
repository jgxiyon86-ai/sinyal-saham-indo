<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TierPageController extends Controller
{
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

        return redirect()->route('tiers.page')->with('status', 'Tier berhasil ditambahkan.');
    }

    public function update(Request $request, Tier $tier): RedirectResponse
    {
        $tier->update($this->validatedData($request));

        return redirect()->route('tiers.page')->with('status', 'Tier berhasil diupdate.');
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
