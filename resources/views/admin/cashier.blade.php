@extends('layouts.app')

@section('title', 'Kasir Pembayaran Santri - PTQ Amsa001')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Kas Masuk</span>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Kasir Santri</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-cash-register text-gold-500 mr-2.5"></i> Kasir Penerimaan Pembayaran
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Input pembayaran santri, terbitkan kuitansi resmi & kirim notifikasi WhatsApp</p>
        </div>
        @include('admin.incomes._tabs')
    </div>

    <!-- Step 1: Pilih Santri -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h2 class="text-sm font-bold text-brand-900 uppercase tracking-wider mb-3 flex items-center">
            <span class="w-6 h-6 rounded-full bg-brand-900 text-white text-xs flex items-center justify-center mr-2">1</span>
            Pilih Data Santri
        </h2>

        <form action="{{ route('admin.cashier') }}" method="GET" id="studentFilterForm" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label for="filterKelompok" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">1. Marhalah</label>
                <select name="kelompok" id="filterKelompok" class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50">
                    <option value="">-- Pilih Marhalah --</option>
                    <option value="putra" {{ $selectedStudent?->santri_group === 'putra' ? 'selected' : '' }}>Putra</option>
                    <option value="putri" {{ $selectedStudent?->santri_group === 'putri' ? 'selected' : '' }}>Putri</option>
                </select>
            </div>

            <div>
                <label for="filterKelas" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">2. Kelas</label>
                <select name="kelas" id="filterKelas" disabled class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classesByKelompok as $kelompok => $daftarKelas)
                        @foreach($daftarKelas as $kelas)
                            <option value="{{ $kelas }}" data-kelompok="{{ $kelompok }}" {{ $selectedStudent?->kelas === $kelas ? 'selected' : '' }}>
                                Kelas {{ $kelas }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filterSantri" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">3. Nama Santri</label>
                <select name="student_id" id="filterSantri" disabled onchange="if (this.value) { document.getElementById('studentFilterForm').submit(); }" class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">-- Pilih Nama Santri --</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}"
                                data-kelompok="{{ $s->santri_group === 'putri' ? 'putri' : 'putra' }}"
                                data-kelas="{{ $s->kelas }}"
                                {{ $selectedStudent && $selectedStudent->id == $s->id ? 'selected' : '' }}>
                            {{ $s->nama_lengkap }} (NIS: {{ $s->nis }})
                        </option>
                    @endforeach
                </select>
            </div>

            <noscript>
                <div class="sm:col-span-3">
                    <button type="submit" class="px-4 py-2 bg-brand-900 text-white rounded-xl text-sm font-semibold">Tampilkan Santri</button>
                </div>
            </noscript>
        </form>

        <p class="text-[11px] text-gray-500 mt-3" id="filterHint">
            Pilih Marhalah, lalu Kelas, lalu Nama Santri. Rincian pembayaran akan muncul setelah ketiganya dipilih.
        </p>

        <!-- Informasi Santri Terpilih -->
        @if($selectedStudent)
            <div id="selectedStudentInfo" class="mt-4 p-4 rounded-xl bg-blue-50/70 border border-blue-100 text-xs sm:text-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <span class="text-gray-500 text-xs">Nama Lengkap:</span>
                    <p class="font-bold text-gray-900">{{ $selectedStudent->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500">NIS: {{ $selectedStudent->nis }} | Kelas: {{ $selectedStudent->kelas }} | Marhalah: {{ $selectedStudent->kelompok }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs">Wali Santri:</span>
                    <p class="font-bold text-gray-900">{{ $selectedStudent->nama_wali }}</p>
                    <p class="text-xs text-emerald-700 font-semibold"><i class="fa-brands fa-whatsapp mr-1"></i> {{ $selectedStudent->no_whatsapp_wali }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs">Status Beasiswa / Keringanan:</span>
                    @if($selectedStudent->diskon_beasiswa > 0)
                        <p class="font-bold text-emerald-700">Beasiswa {{ $selectedStudent->diskon_beasiswa }}% {{ $selectedStudent->bebas_wakaf ? '+ Bebas Wakaf' : '' }}</p>
                    @else
                        <p class="font-bold text-gray-700">Reguler (Biaya Penuh)</p>
                    @endif
                    <p class="text-xs text-rose-600 font-semibold mt-0.5">Total Tunggakan: Rp {{ number_format($selectedStudent->total_tunggakan, 0, ',', '.') }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Step 2: Form Input Transaksi -->
    @if($selectedStudent)
        <div id="paymentStep" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-sm font-bold text-brand-900 uppercase tracking-wider mb-4 flex items-center">
                <span class="w-6 h-6 rounded-full bg-brand-900 text-white text-xs flex items-center justify-center mr-2">2</span>
                Rincian Pembayaran
            </h2>

            @if($unpaidInvoices->isEmpty())
                <div class="p-6 text-center text-gray-500 text-sm">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2"></i>
                    <p class="font-semibold text-gray-800">Santri ini tidak memiliki tagihan aktif yang belum lunas.</p>
                </div>
            @else
                <form action="{{ route('admin.cashier.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <!-- Pilih Tagihan -->
                    <div>
                        <label for="invoice_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Pilih Pos Tagihan yang Dibayar <span class="text-rose-500">*</span>
                        </label>
                        <select name="invoice_id" id="invoice_id" required onchange="updateSuggestedAmount(this)"
                                class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-white shadow-sm">
                            <option value="">-- Pilih Tagihan --</option>
                            @foreach($unpaidInvoices as $inv)
                                <option value="{{ $inv->id }}" data-remaining="{{ $inv->remaining_amount }}" {{ old('invoice_id') == $inv->id ? 'selected' : '' }}>
                                    [{{ $inv->status_label }}] {{ $inv->title }} &mdash; Sisa: Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Telah Diterima Dari -->
                    <div>
                        <label for="received_from" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Telah Diterima Dari <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="received_from" id="received_from" value="{{ old('received_from') }}" required maxlength="150"
                               placeholder="Contoh: Bpk. Bambang Sutrisno (Ayah Kandung)"
                               class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-white shadow-sm">
                        <p class="text-[11px] text-gray-500 mt-1">Wajib diisi. Nama pihak yang menyerahkan uang ini &mdash; akan dicetak pada kuitansi.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nominal Bayar -->
                        <div>
                            <label for="amount_paid" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Nominal Pembayaran (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="amount_paid" value="{{ old('amount_paid') }}" required min="1000" step="1000"
                                   placeholder="Contoh: 1350000"
                                   class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 font-bold text-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Bisa diisi nominal penuh atau sebagian (cicilan).</p>
                        </div>

                        <!-- Metode Bayar -->
                        <div>
                            <label for="payment_method" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Metode Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_method" id="payment_method" required
                                    class="w-full text-sm rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 focus:border-brand-800 bg-white">
                                <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer Bank BSI (Rekening Pesantren)</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Tunai / Cash</option>
                            </select>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <label for="notes" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Catatan Transaksi (Opsional)
                        </label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes') }}"
                               placeholder="Contoh: Transfer BSI an Bambang / Pembayaran Cicil 1"
                               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm">
                            <i class="fa-solid fa-file-invoice text-gold-400"></i>
                            <span>Simpan Pembayaran & Terbitkan Kuitansi Digital</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

</div>

<script>
    (function () {
        const form = document.getElementById('studentFilterForm');
        const kelompokSelect = document.getElementById('filterKelompok');
        const kelasSelect = document.getElementById('filterKelas');
        const santriSelect = document.getElementById('filterSantri');
        const hint = document.getElementById('filterHint');

        function refreshFilters(preserveSelection) {
            const kelompok = kelompokSelect.value;

            // Kelas hanya menampilkan pilihan milik marhalah yang dipilih.
            Array.from(kelasSelect.options).forEach(function (option) {
                if (!option.value) return;
                const cocok = kelompok !== '' && option.dataset.kelompok === kelompok;
                option.hidden = !cocok;
                option.disabled = !cocok;
            });

            kelasSelect.disabled = kelompok === '';
            if (!preserveSelection && kelasSelect.selectedOptions[0]?.disabled) {
                kelasSelect.value = '';
            }

            const kelas = kelasSelect.value;

            // Nama santri baru bisa dipilih setelah marhalah dan kelas ditentukan.
            Array.from(santriSelect.options).forEach(function (option) {
                if (!option.value) return;
                const cocok = kelompok !== '' && kelas !== ''
                    && option.dataset.kelompok === kelompok
                    && option.dataset.kelas === kelas;
                option.hidden = !cocok;
                option.disabled = !cocok;
            });

            santriSelect.disabled = kelompok === '' || kelas === '';
            if (!preserveSelection && santriSelect.selectedOptions[0]?.disabled) {
                santriSelect.value = '';
            }

            const adaSantri = santriSelect.value !== '';

            // Saat pilihan diubah, sembunyikan sementara info & formulir santri lama
            // agar tidak terjadi salah input untuk santri yang berbeda.
            document.getElementById('selectedStudentInfo')?.classList.toggle('hidden', !adaSantri);
            document.getElementById('paymentStep')?.classList.toggle('hidden', !adaSantri);

            if (hint) {
                hint.classList.toggle('hidden', kelompok !== '' && kelas !== '' && adaSantri);
            }
        }

        kelompokSelect.addEventListener('change', function () { refreshFilters(false); });
        kelasSelect.addEventListener('change', function () { refreshFilters(false); });

        // Saat halaman dimuat kembali dengan santri terpilih, pertahankan pilihan.
        refreshFilters(santriSelect.value !== '');
    })();

    function updateSuggestedAmount(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const remaining = selectedOption.getAttribute('data-remaining');
        if (remaining) {
            document.getElementById('amount_paid').value = remaining;
        }
    }
</script>
@endsection
