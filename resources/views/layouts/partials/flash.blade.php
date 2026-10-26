{{-- Pesan hasil aksi (error validasi, sukses, info) dipakai oleh semua layout. --}}
@if($errors->any())
    <div class="mb-5 flex items-start space-x-3 rounded-r-lg border-l-4 border-rose-500 bg-rose-50 p-4 shadow-sm">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xl text-rose-600"></i>
        <div class="flex-1 text-sm text-rose-800">
            <p class="font-semibold">Perubahan belum disimpan.</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="mb-5 flex items-start space-x-3 rounded-r-lg border-l-4 border-emerald-500 bg-emerald-50 p-4 shadow-sm">
        <i class="fa-solid fa-circle-check mt-0.5 text-xl text-emerald-600"></i>
        <div class="flex-1 text-sm text-emerald-800">
            <p class="font-semibold">Berhasil!</p>
            <p>{{ session('success') }}</p>
            @if(session('wa_direct_url'))
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a href="{{ session('wa_direct_url') }}" target="_blank" class="inline-flex items-center rounded bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow transition hover:bg-emerald-700">
                        <i class="fa-brands fa-whatsapp mr-1.5 text-sm"></i> Kirim Notifikasi Kuitansi via WhatsApp
                    </a>
                    @if(session('receipt_id'))
                        <a href="{{ route('receipt.download', session('receipt_id')) }}" target="_blank" class="inline-flex items-center rounded bg-brand-800 px-3 py-1.5 text-xs font-medium text-white shadow transition hover:bg-brand-900">
                            <i class="fa-solid fa-file-pdf mr-1.5 text-sm"></i> Unduh PDF Kuitansi
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif

@if(session('info'))
    <div class="mb-5 flex items-start space-x-3 rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 shadow-sm">
        <i class="fa-solid fa-circle-info mt-0.5 text-xl text-blue-600"></i>
        <div class="text-sm text-blue-800">{{ session('info') }}</div>
    </div>
@endif
