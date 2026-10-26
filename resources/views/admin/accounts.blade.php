@extends('layouts.app')

@section('title', 'Kelola Akun Pengurus - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Kelola Akun Pengurus</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-users-gear text-brand-800 mr-2.5"></i> Kelola Akun Pengurus
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Tambah pengurus, reset kata sandi, dan atur akses. Khusus Administrator.
            </p>
        </div>
        <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
            <i class="fa-solid fa-user-plus mr-1.5"></i> Tambah Pengurus
        </button>
    </div>

    <!-- Daftar Akun -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                        <x-sort-th column="name" label="Nama & Jabatan" :sort-by="$sortBy" :sort-order="$sortOrder" class="py-3 px-4" />
                        <x-sort-th column="email" label="Email" :sort-by="$sortBy" :sort-order="$sortOrder" class="py-3 px-4" />
                        <x-sort-th column="role" label="Peran" :sort-by="$sortBy" :sort-order="$sortOrder" align="center" class="py-3 px-4" />
                        <x-sort-th column="status" label="Status" :sort-by="$sortBy" :sort-order="$sortOrder" align="center" class="py-3 px-4" />
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $u)
                        <tr class="hover:bg-gray-50/50 transition {{ Auth::id() === $u->id ? 'bg-blue-50/40' : '' }}">
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-gray-900">
                                    {{ $u->name }}
                                    @if(Auth::id() === $u->id)
                                        <span class="ml-1 text-[10px] font-bold text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded">Anda</span>
                                    @endif
                                </p>
                                <p class="text-[11px] text-gray-500">{{ $u->displayTitle() }}</p>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-gray-600">{{ $u->email }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $u->role?->value === 'admin' ? 'bg-purple-100 text-purple-800' : ($u->role?->value === 'bendahara' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $u->role?->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($u->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">AKTIF</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">NONAKTIF</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1.5">
                                    <button type="button"
                                            onclick='openResetPassword(@json(["id" => $u->id, "name" => $u->name]))'
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-semibold border border-amber-200 transition">
                                        <i class="fa-solid fa-key mr-1"></i> Reset Sandi
                                    </button>

                                    @if(Auth::id() !== $u->id)
                                        <form action="{{ route('admin.accounts.status', $u) }}" method="POST" class="inline"
                                              onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan akun '.$u->name.'? Pengurus ini tidak dapat login sampai diaktifkan kembali.' : 'Aktifkan kembali akun '.$u->name.'?' }}');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition
                                                {{ $u->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }}">
                                                <i class="fa-solid {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-1"></i>
                                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl text-xs text-blue-900">
        <p class="font-bold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Catatan keamanan</p>
        <p>Sandi minimal 8 karakter. Setiap penambahan akun, reset sandi, dan perubahan status tercatat di <strong>Log Aktivitas</strong>. Sampaikan sandi baru secara pribadi — jangan melalui grup WhatsApp.</p>
    </div>

</div>

<!-- Modal Tambah Pengurus -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Tambah Akun Pengurus</h3>
                <p class="text-xs text-blue-200">Administrator, Bendahara, atau Pengasuh</p>
            </div>
            <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-gray-300 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.accounts.store') }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Jabatan</label>
                    <input type="text" name="jabatan" maxlength="100" value="{{ old('jabatan') }}" placeholder="Contoh: Bendahara 1"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Email <span class="text-rose-500">*</span></label>
                <input type="email" name="email" required maxlength="150" value="{{ old('email') }}"
                       class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Peran <span class="text-rose-500">*</span></label>
                <select name="role" required class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                    <option value="bendahara" {{ old('role') === 'bendahara' ? 'selected' : '' }}>Bendahara — kasir, tagihan, laporan</option>
                    <option value="pengasuh" {{ old('role') === 'pengasuh' ? 'selected' : '' }}>Pengasuh — hanya melihat dashboard & laporan</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator — akses penuh</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Kata Sandi <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Ulangi Sandi <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Sandi -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Reset Kata Sandi</h3>
                <p class="text-xs text-amber-100" id="resetPasswordName">-</p>
            </div>
            <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('hidden')" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="resetPasswordForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <input type="password" name="password" required minlength="8"
                       class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
            </div>
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Ulangi Sandi Baru <span class="text-rose-500">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
            </div>

            <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-900">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Reset sandi akan tercatat di Log Aktivitas. Sampaikan sandi baru langsung kepada pemilik akun.
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Tutup</button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-key mr-1.5"></i> Reset Sandi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResetPassword(data) {
        document.getElementById('resetPasswordForm').action = "{{ url('admin/akun') }}/" + data.id + "/reset-password";
        document.getElementById('resetPasswordName').textContent = data.name || '-';
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }
</script>
@endsection
