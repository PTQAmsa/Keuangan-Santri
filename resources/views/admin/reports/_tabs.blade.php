@php
    // Navigasi antar laporan disimpan di satu partial agar urutan dan
    // penanda halaman aktif selalu sama di semua halaman laporan.
    $reportTabs = [
        [
            'route' => 'admin.reports.cash',
            'label' => 'Kas Masuk',
            'icon' => 'fa-money-bill-wave',
            'iconColor' => 'text-emerald-600',
            'active' => request()->routeIs('admin.reports.cash') || request()->routeIs('admin.reports.cash.csv') || request()->routeIs('admin.reports.cash.pdf'),
        ],
        [
            'route' => 'admin.reports.cash-flow',
            'label' => 'Arus Kas',
            'icon' => 'fa-scale-balanced',
            'iconColor' => 'text-brand-800',
            'active' => request()->routeIs('admin.reports.cash-flow'),
        ],
        [
            'route' => 'admin.reports.arrears',
            'label' => 'Rekap Tunggakan',
            'icon' => 'fa-clock-rotate-left',
            'iconColor' => 'text-rose-500',
            'active' => request()->routeIs('admin.reports.arrears*'),
        ],
        [
            'route' => 'admin.reports.closing',
            'label' => 'Tutup Buku',
            'icon' => 'fa-lock',
            'iconColor' => 'text-slate-600',
            'active' => request()->routeIs('admin.reports.closing*'),
        ],
    ];
@endphp

<nav class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl self-start md:self-auto text-xs font-semibold overflow-x-auto whitespace-nowrap max-w-full"
     aria-label="Navigasi laporan keuangan">
    @foreach($reportTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           @if($tab['active']) aria-current="page" @endif
           class="inline-flex items-center px-3.5 py-2 rounded-lg transition {{ $tab['active'] ? 'bg-white text-brand-900 shadow-sm' : 'text-gray-600 hover:text-brand-900 hover:bg-white/60' }}">
            <i class="fa-solid {{ $tab['icon'] }} mr-1.5 {{ $tab['iconColor'] }}"></i> {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
