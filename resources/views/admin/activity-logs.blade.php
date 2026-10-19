@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Log Aktivitas</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-clipboard-list text-brand-800 mr-2.5"></i> Log Aktivitas Sistem
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Riwayat perubahan tagihan &amp; pembayaran santri beserta aktivitas login pengurus</p>
        </div>

        <span class="inline-flex items-center px-3 py-2 rounded-xl bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200 self-start md:self-auto">
            <i class="fa-solid fa-shield-halved mr-1.5"></i> Khusus Administrator
        </span>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Entri Log</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalLogs }}</p>
            <p class="text-xs text-gray-400 mt-1">Seluruh aktivitas yang tercatat</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Perubahan Keuangan</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $totalFinancial }}</p>
            <p class="text-xs text-gray-400 mt-1">Tagihan &amp; pembayaran santri</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Aktivitas Hari Ini</p>
            <p class="text-2xl font-extrabold text-brand-900 mt-1">{{ $totalToday }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Cari Keterangan</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="mis. pembayaran, INV-2026..."
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Pengguna</label>
                <select name="user_id" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Pengguna --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Modul</label>
                <select name="module" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Modul --</option>
                    @foreach(\App\Enums\ActivityModule::cases() as $moduleOption)
                        <option value="{{ $moduleOption->value }}" {{ $module === $moduleOption ? 'selected' : '' }}>
                            {{ $moduleOption->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Aksi</label>
                <select name="action" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Aksi --</option>
                    @foreach(\App\Enums\ActivityAction::cases() as $actionOption)
                        <option value="{{ $actionOption->value }}" {{ $action === $actionOption ? 'selected' : '' }}>
                            {{ $actionOption->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
            </div>

            <div class="flex items-center space-x-2 sm:col-span-2">
                <button type="submit" class="py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.logs') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Log Aktivitas -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 bg-gray-50/70 border-b border-gray-200">
            <h2 class="text-sm font-bold text-gray-900">Riwayat Aktivitas</h2>
            <p class="text-xs text-gray-500">
                Menampilkan {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} entri
                @if($logs->total() > 0) &middot; halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }} @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-[11px] tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 whitespace-nowrap">Waktu</th>
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4 text-center">Modul</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                        <th class="py-3 px-4">Keterangan</th>
                        <th class="py-3 px-4">Rincian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-blue-50/40 transition align-top">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-900">{{ $log->created_at?->format('d/m/Y') }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $log->created_at?->format('H:i:s') }} WIB</p>
                            </td>
                            <td class="py-3 px-4">
                                @if($log->user)
                                    <p class="font-bold text-gray-900">{{ $log->user->name }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $log->user->displayTitle() }}</p>
                                @else
                                    <span class="text-gray-400 italic">Sistem</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold {{ $log->module->badgeClass() }}">
                                    {{ $log->module->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $log->action->badgeClass() }}">
                                    {{ $log->action->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 max-w-md">
                                <p class="text-gray-700">{{ $log->description }}</p>
                                @if($log->ip_address)
                                    <p class="text-[11px] text-gray-400 mt-0.5 font-mono">IP: {{ $log->ip_address }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if(!empty($log->properties))
                                    <details>
                                        <summary class="cursor-pointer text-brand-800 hover:text-brand-900 font-semibold text-[11px] select-none">
                                            <i class="fa-solid fa-chevron-down mr-1"></i> Lihat
                                        </summary>
                                        <dl class="mt-2 space-y-1.5 border-l-2 border-gray-200 pl-3">
                                            @foreach($log->properties as $label => $value)
                                                <div>
                                                    <dt class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">{{ $label }}</dt>
                                                    <dd class="text-gray-700">{{ $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </details>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-clock-rotate-left text-4xl mb-2 text-gray-300 block"></i>
                                Belum ada aktivitas yang sesuai dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-200 bg-gray-50/70">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
