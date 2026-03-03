<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ClientCredentialsMail;
use App\Models\Tier;
use App\Models\User;
use App\Support\WaNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
use ZipArchive;

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
                    ->orWhere('client_code', 'like', "%{$term}%")
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

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_import_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:20480'],
        ]);

        try {
            $rows = $this->parseImportRows($data['client_import_file']);
        } catch (Throwable $e) {
            return redirect()->route('clients.page')
                ->with('status', 'Gagal membaca file import: '.$e->getMessage());
        }

        if (empty($rows)) {
            return redirect()->route('clients.page')
                ->with('status', 'File import kosong atau format kolom tidak sesuai.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $line => $row) {
            try {
                $code = trim((string) ($row['idcust'] ?? ''));
                $name = trim((string) ($row['full_name'] ?? ''));
                $phone = WaNumber::normalize((string) ($row['hp'] ?? ''));
                $birthDate = $this->parseBirthDate($row['birthdate'] ?? null);
                $capital = $this->parseCapitalAmount($row['saldo'] ?? null);

                if ($code === '' && $name === '' && blank($phone)) {
                    $skipped++;
                    continue;
                }

                if ($name === '') {
                    $skipped++;
                    $errors[] = "Baris {$line}: nama kosong.";
                    continue;
                }

                $client = null;
                if ($code !== '') {
                    $client = User::query()
                        ->where('role', 'client')
                        ->where('client_code', $code)
                        ->first();
                }
                if (! $client && ! blank($phone)) {
                    $client = User::query()
                        ->where('role', 'client')
                        ->where('whatsapp_number', $phone)
                        ->first();
                }

                $tier = $this->findTierByCapital($capital);

                if ($client) {
                    $client->update([
                        'client_code' => $code !== '' ? $code : $client->client_code,
                        'name' => $name,
                        'whatsapp_number' => $phone,
                        'birth_date' => $birthDate,
                        'capital_amount' => $capital,
                        'tier_id' => $tier?->id,
                        'role' => 'client',
                        'is_active' => true,
                    ]);
                    $updated++;
                    continue;
                }

                $email = $this->generateImportEmail($code, $phone, $name);
                $password = $this->generateTemporaryPassword();

                User::create([
                    'client_code' => $code !== '' ? $code : null,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'client',
                    'tier_id' => $tier?->id,
                    'whatsapp_number' => $phone,
                    'birth_date' => $birthDate,
                    'capital_amount' => $capital,
                    'is_active' => true,
                ]);
                $created++;
            } catch (Throwable $e) {
                $skipped++;
                $errors[] = "Baris {$line}: ".$e->getMessage();
            }
        }

        $summary = "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
        if (! empty($errors)) {
            $summary .= ' Contoh error: '.implode(' | ', array_slice($errors, 0, 3));
        }

        return redirect()->route('clients.page')->with('status', $summary);
    }

    private function validatedData(Request $request, ?int $ignoreUserId = null): array
    {
        $data = $request->validate([
            'client_code' => ['nullable', 'string', 'max:120', Rule::unique('users', 'client_code')->ignore($ignoreUserId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:'.WaNumber::validationRegex()],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', Rule::in($this->religions)],
            'capital_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['whatsapp_number'] = WaNumber::normalize($data['whatsapp_number'] ?? null);

        return $data;
    }

    private function parseImportRows(UploadedFile $file): array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === 'csv' || $ext === 'txt') {
            return $this->parseCsvRows($file->getRealPath());
        }

        if ($ext !== 'xlsx') {
            throw new \RuntimeException('Format file harus XLSX atau CSV.');
        }

        return $this->parseXlsxRows($file->getRealPath());
    }

    private function parseCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('Tidak bisa membuka file CSV.');
        }

        $rows = [];
        $header = null;
        $lineNo = 0;
        $delimiter = $this->detectCsvDelimiter($path);
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNo++;
            if ($header === null) {
                $header = $this->mapImportHeader($line);
                continue;
            }
            $assoc = $this->mapImportRow($header, $line);
            if (! empty($assoc)) {
                $rows[$lineNo] = $assoc;
            }
        }
        fclose($handle);

        return $rows;
    }

    private function parseXlsxRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive tidak tersedia di server.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File XLSX tidak bisa dibuka.');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Sheet1 tidak ditemukan di XLSX.');
        }

        $sharedStrings = [];
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared && isset($shared->si)) {
                foreach ($shared->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet || ! isset($sheet->sheetData->row)) {
            return [];
        }

        $rows = [];
        $header = null;
        foreach ($sheet->sheetData->row as $row) {
            $lineNo = (int) $row['r'];
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colLetters = preg_replace('/\d+/', '', $ref);
                $colIndex = $this->columnLettersToIndex($colLetters);

                $value = '';
                $type = (string) $c['t'];
                if ($type === 's') {
                    $sIdx = (int) ((string) $c->v);
                    $value = (string) ($sharedStrings[$sIdx] ?? '');
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($c->is->t ?? '');
                } else {
                    $value = (string) ($c->v ?? '');
                }
                $cells[$colIndex] = $value;
            }

            if ($header === null) {
                ksort($cells);
                $header = $this->mapImportHeader($cells);
                continue;
            }

            if (empty($cells)) {
                continue;
            }
            ksort($cells);
            $assoc = $this->mapImportRow($header, $cells);
            if (! empty($assoc)) {
                $rows[$lineNo] = $assoc;
            }
        }

        return $rows;
    }

    private function detectCsvDelimiter(string $path): string
    {
        $firstLine = '';
        $handle = fopen($path, 'r');
        if ($handle) {
            $firstLine = (string) fgets($handle);
            fclose($handle);
        }

        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function mapImportHeader(array $headerRow): array
    {
        $mapped = [];
        foreach ($headerRow as $idx => $head) {
            $normalized = Str::lower(trim((string) $head));
            $normalized = str_replace([' ', '-', '.', '/'], '_', $normalized);

            if (in_array($normalized, ['idcust', 'id_cust', 'kode_klient', 'kode_client'], true)) {
                $mapped[$idx] = 'idcust';
            } elseif (in_array($normalized, ['full_nama_klient', 'full_nama_client', 'full_nama', 'nama_klient', 'nama_client', 'nama'], true)) {
                $mapped[$idx] = 'full_name';
            } elseif (in_array($normalized, ['hp', 'no_hp', 'nomor_hp', 'wa', 'whatsapp'], true)) {
                $mapped[$idx] = 'hp';
            } elseif (in_array($normalized, ['birthdate', 'birth_date', 'tanggal_lahir', 'tgl_lahir', 'tanggal'], true)) {
                $mapped[$idx] = 'birthdate';
            } elseif (in_array($normalized, ['saldo', 'capital', 'capital_amount', 'modal'], true)) {
                $mapped[$idx] = 'saldo';
            }
        }

        return $mapped;
    }

    private function mapImportRow(array $headerMap, array $row): array
    {
        $assoc = [];
        foreach ($headerMap as $idx => $key) {
            $assoc[$key] = $row[$idx] ?? null;
        }
        return $assoc;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    private function parseBirthDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            $days = (int) round((float) $value);
            return Carbon::create(1899, 12, 30)->addDays($days)->toDateString();
        }

        $raw = trim((string) $value);
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->toDateString();
            } catch (Throwable) {
                // ignore and continue
            }
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseCapitalAmount(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return max(0, (float) $value);
        }

        $raw = str_replace(['Rp', 'rp', ' '], '', (string) $value);
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);

        return max(0, (float) $raw);
    }

    private function generateImportEmail(string $code, ?string $phone, string $name): string
    {
        $base = $code !== '' ? Str::slug($code) : '';
        if ($base === '' && ! blank($phone)) {
            $base = 'hp'.preg_replace('/\D+/', '', (string) $phone);
        }
        if ($base === '') {
            $base = Str::slug($name);
        }
        if ($base === '') {
            $base = 'client';
        }

        $suffix = 0;
        do {
            $email = $suffix === 0
                ? "{$base}@client.local"
                : "{$base}{$suffix}@client.local";
            $exists = User::query()->where('email', $email)->exists();
            $suffix++;
        } while ($exists);

        return $email;
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
