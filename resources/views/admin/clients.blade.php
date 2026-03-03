@extends('layouts.admin')

@section('title', 'Manajemen Klient')

@section('content')
    <h2 style="margin:0 0 4px;">Manajemen Klient</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Tampilan list untuk data besar. Form dipisah ke halaman lain.</p>

    <div class="panel">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
            <div style="background:#fff;border:1px solid #d5e6fb;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#4d6b8f;">Total Klient</div>
                <div style="font-size:20px;font-weight:700;">{{ number_format((int) ($summary['total'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div style="background:#ecfdf3;border:1px solid #9de7bf;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#166534;">Klient Aktif</div>
                <div style="font-size:20px;font-weight:700;color:#166534;">{{ number_format((int) ($summary['active'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div style="background:#fff8e1;border:1px solid #f6dd8f;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#8a5b00;">Klient Nonaktif</div>
                <div style="font-size:20px;font-weight:700;color:#8a5b00;">{{ number_format((int) ($summary['inactive'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('clients.page') }}">
            <div class="field-grid">
                <div><label>Cari</label><input name="q" value="{{ request('q') }}" placeholder="IDCUST, nama, email, no HP"></div>
                <div>
                    <label>Tier</label>
                    <select name="tier_id">
                        <option value="">Semua tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected((string)request('tier_id') === (string)$tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Agama</label>
                    <select name="religion">
                        <option value="">Semua agama</option>
                        @foreach($religions as $key => $label)
                            <option value="{{ $key }}" @selected(request('religion') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="is_active">
                        <option value="">Semua</option>
                        <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                        <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label>Tgl Lahir</label>
                    <input type="date" name="birth_date" value="{{ request('birth_date') }}">
                </div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">Filter</button>
                <a class="btn btn-muted" href="{{ route('clients.page') }}" style="text-decoration:none;">Reset</a>
                <a class="btn" href="{{ route('clients.create') }}" style="text-decoration:none;">Tambah Klient</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>IDCUST</th><th>Nama</th><th>Email</th><th>Tier</th><th>Modal</th><th>Agama</th><th>Tgl Lahir</th><th>Nomor HP</th><th>Status</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody id="client-table-body">
                @include('admin.clients-table-partial')
            </tbody>
        </table>
    </div>
    <div id="pagination-wrapper" class="pagination">
        {{-- Pagination is now included in the partial for consistency, but we keep the wrapper if needed --}}
    </div>

    @push('scripts')
    <script>
    (function () {
        const tableBody = document.getElementById('client-table-body');
        const searchInputs = document.querySelectorAll('.panel form input, .panel form select');
        const filterForm = document.querySelector('.panel form');
        let searchTimeout = null;

        function fetchClients(url = null) {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            const fetchUrl = url || `{{ route('clients.page') }}?${params.toString()}`;

            tableBody.style.opacity = '0.5';

            fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                tableBody.innerHTML = html;
                tableBody.style.opacity = '1';
                
                // Re-bind pagination links
                const paginationLinks = tableBody.querySelectorAll('.pagination a');
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        fetchClients(this.href);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                });
            })
            .catch(err => {
                console.error('Fetch error:', err);
                tableBody.style.opacity = '1';
            });
        }

        searchInputs.forEach(input => {
            const eventType = input.tagName === 'SELECT' || input.type === 'date' ? 'change' : 'keyup';
            input.addEventListener(eventType, () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchClients();
                }, 400); // 400ms debounce
            });
        });

        // Intercept manual filter button
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchClients();
        });

        // Handle initial pagination links if any exist outside
        document.addEventListener('click', function(e) {
            if (e.target.closest('.pagination a')) {
                const link = e.target.closest('.pagination a');
                if (link.closest('#client-table-body')) {
                    // already handled above, but just in case
                } else {
                    e.preventDefault();
                    fetchClients(link.href);
                }
            }
        });
    })();
    </script>
    @endpush
@endsection
