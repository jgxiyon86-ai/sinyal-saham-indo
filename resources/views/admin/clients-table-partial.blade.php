@forelse($clients as $client)
    <tr>
        <td>{{ $client->client_code ?: '-' }}</td>
        <td>{{ $client->name }}</td>
        <td>{{ $client->email }}</td>
        <td>{{ $client->tier->name ?? '-' }}</td>
        <td>{{ number_format((float)$client->capital_amount, 0, ',', '.') }}</td>
        <td>{{ $religions[$client->religion] ?? '-' }}</td>
        <td>{{ $client->birth_date ? \Illuminate\Support\Carbon::parse($client->birth_date)->format('d-m-Y') : '-' }}</td>
        <td>{{ $client->whatsapp_number ?? '-' }}</td>
        <td>{{ $client->is_active ? 'Aktif' : 'Nonaktif' }}</td>
        <td>
            <div class="actions">
                <a class="btn btn-muted" href="{{ route('clients.edit', $client) }}" style="text-decoration:none;">Edit</a>
                <form method="POST" action="{{ route('clients.toggle-active', $client) }}" onsubmit="return confirm('{{ $client->is_active ? 'Nonaktifkan klient ini? Klient tidak akan menerima WA/sinyal aplikasi.' : 'Aktifkan kembali klient ini?' }}')">
                    @csrf
                    <input type="hidden" name="is_active" value="{{ $client->is_active ? 0 : 1 }}">
                    <button class="btn {{ $client->is_active ? 'btn-danger' : '' }}" type="submit">
                        {{ $client->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('clients.send-credentials', $client) }}" onsubmit="return confirm('Kirim password baru ke email klient ini?')">
                    @csrf
                    <button class="btn" type="submit">Kirim Kredensial</button>
                </form>
                <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Hapus klient ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Hapus</button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="10" style="text-align:center;">Belum ada data klient.</td></tr>
@endforelse

<tr style="background:transparent;border:0;">
    <td colspan="10" style="padding:0;border:0;">
        <div class="pagination-container" data-ajax-pagination>
            {{ $clients->links() }}
        </div>
    </td>
</tr>
