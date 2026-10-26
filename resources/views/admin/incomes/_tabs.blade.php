@php
    // Menu Kas Masuk punya dua halaman: Kasir (pembayaran santri) dan
    // Pemasukan Lainnya (donasi, infaq, unit usaha, dsb.).
    $incomeTabs = [
        [
            'route' => 'admin.cashier',
            'label' => 'Kasir Santri',
            'icon' => 'fa-cash-register',
            'iconColor' => 'text-gold-500',
            'active' => request()->routeIs('admin.cashier*'),
        ],
        [
            'route' => 'admin.incomes',
            'label' => 'Pemasukan Lainnya',
            'icon' => 'fa-hand-holding-heart',
            'iconColor' => 'text-emerald-600',
            'active' => request()->routeIs('admin.incomes*'),
        ],
    ];
@endphp

<nav class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl self-start md:self-auto text-xs font-semibold overflow-x-auto whitespace-nowrap max-w-full"
     aria-label="Navigasi kas masuk">
    @foreach($incomeTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           @if($tab['active']) aria-current="page" @endif
           class="inline-flex items-center px-3.5 py-2 rounded-lg transition {{ $tab['active'] ? 'bg-white text-brand-900 shadow-sm' : 'text-gray-600 hover:text-brand-900 hover:bg-white/60' }}">
            <i class="fa-solid {{ $tab['icon'] }} mr-1.5 {{ $tab['iconColor'] }}"></i> {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
