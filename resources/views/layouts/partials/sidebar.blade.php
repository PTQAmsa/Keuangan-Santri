@php
    $user = auth()->user();
    $canManage = (bool) $user?->role?->canManageFinances();
    $isAdmin = (bool) $user?->role?->isAdmin();

    // Menu utama panel. 'match' memakai pola nama route untuk penanda aktif.
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard'], 'show' => true],
        ['label' => 'Kas Masuk', 'hint' => 'Kasir Pembayaran', 'icon' => 'fa-wallet', 'route' => 'admin.cashier', 'match' => ['admin.cashier*', 'admin.incomes*'], 'show' => $canManage],
        ['label' => 'Tagihan Santri', 'icon' => 'fa-file-invoice-dollar', 'route' => 'admin.invoices', 'match' => ['admin.invoices*'], 'show' => $canManage],
        ['label' => 'Riwayat Transaksi', 'icon' => 'fa-clock-rotate-left', 'route' => 'admin.transactions', 'match' => ['admin.transactions*'], 'show' => $canManage],
        ['label' => 'Kas Keluar', 'hint' => 'Operasional', 'icon' => 'fa-money-bill-trend-up', 'route' => 'admin.expenses', 'match' => ['admin.expenses*'], 'show' => $canManage],
        ['label' => 'Laporan Keuangan', 'icon' => 'fa-chart-column', 'route' => 'admin.reports.cash', 'match' => ['admin.reports.*'], 'show' => true],
        ['label' => 'Data Santri', 'icon' => 'fa-user-graduate', 'route' => 'admin.students', 'match' => ['admin.students*'], 'show' => $canManage],
        ['label' => 'Data Guru & Bisyarah', 'icon' => 'fa-chalkboard-user', 'route' => 'admin.teachers', 'match' => ['admin.teachers*', 'admin.bisyarah*'], 'show' => $canManage],
    ];

    $adminMenu = [
        ['label' => 'Log Aktivitas', 'icon' => 'fa-clipboard-list', 'route' => 'admin.logs', 'match' => ['admin.logs'], 'show' => $isAdmin],
        ['label' => 'Akun Pengurus', 'icon' => 'fa-users-gear', 'route' => 'admin.accounts', 'match' => ['admin.accounts*'], 'show' => $isAdmin],
    ];

    $renderItem = function (array $item): string {
        $active = request()->routeIs(...$item['match']);
        $classes = $active
            ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-900/40'
            : 'text-slate-300 hover:bg-slate-800 hover:text-white';

        return sprintf(
            '<a href="%s" %s data-sidebar-link class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition %s" title="%s">'
            .'<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg %s"><i class="fas %s text-base"></i></span>'
            .'<span data-sidebar-label class="flex min-w-0 flex-col leading-tight"><span class="truncate">%s</span>%s</span>'
            .'</a>',
            route($item['route']),
            $active ? 'aria-current="page"' : '',
            $classes,
            e($item['label']),
            $active ? 'bg-white/15' : 'bg-slate-800/70 group-hover:bg-slate-700',
            $item['icon'],
            e($item['label']),
            isset($item['hint']) ? '<span class="truncate text-[10px] font-normal '.($active ? 'text-blue-100' : 'text-slate-500').'">'.e($item['hint']).'</span>' : ''
        );
    };
@endphp

{{-- Overlay gelap saat sidebar dibuka di layar kecil --}}
<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

<aside id="app-sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-100 shadow-2xl transition-[width,transform] duration-200 ease-in-out lg:translate-x-0"
       aria-label="Navigasi utama">

    <!-- Brand -->
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-4">
        <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="h-9 w-9 shrink-0 rounded-full bg-white p-0.5 shadow">
        <div data-sidebar-label class="min-w-0 leading-tight">
            <p class="truncate text-sm font-bold tracking-tight">PTQ Amsa001</p>
            <p class="truncate text-[10px] text-slate-400">Sistem Keuangan Santri</p>
        </div>
        <button type="button" data-sidebar-close class="ml-auto flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" aria-label="Tutup menu">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <!-- Menu -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p data-sidebar-label class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Menu Utama</p>
        @foreach($menu as $item)
            @if($item['show'])
                {!! $renderItem($item) !!}
            @endif
        @endforeach

        @if($isAdmin)
            <p data-sidebar-label class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Administrasi</p>
            @foreach($adminMenu as $item)
                @if($item['show'])
                    {!! $renderItem($item) !!}
                @endif
            @endforeach
        @endif
    </nav>

    <!-- Kaki sidebar: ringkas/perluas (desktop) -->
    <div class="shrink-0 border-t border-slate-800 p-3">
        <button type="button" data-sidebar-collapse
                class="hidden w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white lg:flex"
                title="Ringkas / perluas menu">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800/70">
                <i class="fas fa-angles-left transition-transform" data-sidebar-collapse-icon></i>
            </span>
            <span data-sidebar-label>Ringkas Menu</span>
        </button>
        <p data-sidebar-label class="mt-2 px-3 text-[10px] text-slate-600">&copy; {{ now()->year }} PTQ Amsa001 Gadog</p>
    </div>
</aside>
