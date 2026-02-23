<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\Tier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SignalPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Signal::query()->with(['tiers', 'creator'])->latest();

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('stock_code', 'like', "%{$term}%");
            });
        }

        if ($request->filled('tier_id')) {
            $tierId = (int) $request->input('tier_id');
            $query->whereHas('tiers', fn ($q) => $q->where('tiers.id', $tierId));
        }

        return view('admin.signals', [
            'signals' => $query->paginate(25)->withQueryString(),
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.signals-form', [
            'signal' => null,
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
        ]);
    }

    public function edit(Signal $signal): View
    {
        return view('admin.signals-form', [
            'signal' => $signal->load('tiers'),
            'tiers' => Tier::query()->orderBy('min_capital')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $data = $this->validatedData($request);
            $tierIds = $this->resolveTierIds($data['tier_target']);
            unset($data['tier_target']);

            $signal = Signal::create([
                ...$data,
                'created_by' => $request->user()->id,
                'published_at' => $data['published_at'] ?? now(),
                'push_sent_at' => null,
            ]);
            $signal->tiers()->sync($tierIds);

            return redirect()->route('signals.create')->with(
                'status',
                'Sinyal berhasil ditambahkan. Form sudah dibersihkan, lanjut input sinyal berikutnya.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal simpan sinyal web', [
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'signal' => 'Gagal simpan sinyal: '.$e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, Signal $signal): RedirectResponse
    {
        try {
            $data = $this->validatedData($request);
            $tierIds = $this->resolveTierIds($data['tier_target']);
            unset($data['tier_target']);

            $signal->update([
                ...$data,
                'push_sent_at' => null,
            ]);
            $signal->tiers()->sync($tierIds);

            return redirect()->route('signals.page')->with(
                'status',
                'Sinyal berhasil diupdate. Push dijadwalkan ulang sesuai tanggal publikasi.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal update sinyal web', [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'signal' => 'Gagal update sinyal: '.$e->getMessage(),
            ]);
        }
    }

    public function destroy(Signal $signal): RedirectResponse
    {
        $signal->delete();
        return redirect()->route('signals.page')->with('status', 'Sinyal berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stock_code' => ['required', 'string', 'max:20'],
            'signal_type' => ['required', Rule::in(['buy', 'sell', 'hold'])],
            'entry_price' => ['nullable', 'integer', 'min:0'],
            'take_profit' => ['nullable', 'integer', 'min:0'],
            'stop_loss' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'tier_target' => ['required', 'string'],
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function resolveTierIds(string $tierTarget): array
    {
        if ($tierTarget === 'all') {
            $ids = Tier::query()->pluck('id')->toArray();
            if (empty($ids)) {
                throw ValidationException::withMessages([
                    'tier_target' => 'Tier belum tersedia, tambahkan tier dulu.',
                ]);
            }

            return $ids;
        }

        if (! ctype_digit($tierTarget)) {
            throw ValidationException::withMessages([
                'tier_target' => 'Tier target tidak valid.',
            ]);
        }

        $tierId = (int) $tierTarget;
        if (! Tier::query()->whereKey($tierId)->exists()) {
            throw ValidationException::withMessages([
                'tier_target' => 'Tier target tidak ditemukan.',
            ]);
        }

        return [$tierId];
    }
}
