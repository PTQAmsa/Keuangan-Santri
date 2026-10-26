@php
    // Nilai old() hanya dipakai oleh modal yang terakhir gagal validasi agar
    // isian tidak bocor ke modal guru lain.
    $isActiveContext = old('_form_context') === $context;
    $value = fn (string $field, $default = null) => $isActiveContext
        ? old($field, $teacher?->{$field} ?? $default)
        : ($teacher?->{$field} ?? $default);
@endphp

<input type="hidden" name="_form_context" value="{{ $context }}">

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_lengkap" value="{{ $value('nama_lengkap') }}" maxlength="100" required
               placeholder="Contoh: Ust. Ahmad Fauzi"
               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Asal <span class="text-rose-500">*</span></label>
        <input type="text" name="kota_asal" value="{{ $value('kota_asal') }}" maxlength="50" required
               placeholder="Kota/kabupaten, contoh: Garut"
               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">No. WhatsApp <span class="text-rose-500">*</span></label>
        <input type="text" name="no_whatsapp" value="{{ $value('no_whatsapp') }}" maxlength="25" required
               inputmode="tel" placeholder="Contoh: 081234567890"
               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
        <p class="text-[11px] text-gray-400 mt-1">Nomor Indonesia aktif, dipakai untuk komunikasi bisyarah.</p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Status <span class="text-rose-500">*</span></label>
        <select name="status" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            @foreach($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" {{ $value('status', 'guru_tetap') === $statusValue ? 'selected' : '' }}>
                    {{ $statusLabel }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan <span class="text-gray-400 font-normal">(opsional)</span></label>
        <textarea name="notes" rows="2" maxlength="500" placeholder="Contoh: pengampu kitab Jurumiyah kelas 8 putra"
                  class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">{{ $value('notes') }}</textarea>
    </div>

    <div class="sm:col-span-2 bg-blue-50 border border-blue-100 rounded-xl p-3.5 flex items-start space-x-2.5">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-[11px] sm:text-xs text-blue-800 leading-relaxed">
            Data ini cukup diisi <strong>satu kali</strong>. Nominal bisyarah, metode pembayaran, dan rekening
            diisi setiap bulan di tab <strong>Bisyarah Bulanan</strong>.
        </p>
    </div>
</div>
