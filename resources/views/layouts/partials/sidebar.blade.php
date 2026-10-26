@php
    $user = auth()->user();
    $isAdmin = (bool) $user?->role?->isAdmin();
    $isViewer = $user?->role === \App\Enums\UserRole::Pengasuh;

    // Menu utama panel. Setiap menu bisa punya submenu (dropdown).
    // 'match' memakai pola nama route untuk penanda aktif; 'query' memastikan
    // submenu dengan parameter tertentu (mis. ?tambah=1) disorot dengan tepat.
    $menu = [
        [
            'label' => 'Dashboard', 'icon' => 'fa-chart-pie',
            'route' => 'admin.dashboard', 'match' => ['admin.dashboard'],
        ],
        // Pengasuh (lihat saja) tidak mencatat kas masuk, jadi menu ini langsung
        // membuka laporan Kas Masuk tanpa submenu kasir/pemasukan.
        $isViewer
            ? [
                'label' => 'Kas Masuk', 'hint' => 'Laporan penerimaan', 'icon' => 'fa-wallet',
                'route' => 'admin.reports.cash', 'match' => ['admin.reports.cash', 'admin.reports.cash.*'],
            ]
            : [
                'label' => 'Kas Masuk', 'hint' => 'Kasir & pemasukan', 'icon' => 'fa-wallet',
                'route' => 'admin.cashier', 'match' => ['admin.cashier*', 'admin.incomes*'],
                'children' => [
                    ['label' => 'Kasir Santri', 'icon' => 'fa-cash-register', 'url' => route('admin.cashier'), 'match' => ['admin.cashier*']],
                    ['label' => 'Pemasukan Lainnya', 'icon' => 'fa-hand-holding-heart', 'url' => route('admin.incomes'), 'match' => ['admin.incomes*']],
                ],
            ],
        [
            'label' => 'Tagihan Santri', 'icon' => 'fa-file-invoice-dollar',
            'route' => 'admin.invoices', 'match' => ['admin.invoices*'],
            'children' => [
                ['label' => 'Daftar Tagihan', 'icon' => 'fa-list-check', 'url' => route('admin.invoices'), 'match' => ['admin.invoices'], 'query' => []],
                ['label' => 'Buat Tagihan Baru', 'icon' => 'fa-file-circle-plus', 'url' => route('admin.invoices', ['tambah' => 1]), 'match' => ['admin.invoices'], 'query' => ['tambah' => '1'], 'write' => true],
                ['label' => 'Terbitkan SPP Bulanan', 'icon' => 'fa-calendar-plus', 'url' => route('admin.dashboard').'#generate-spp', 'match' => [], 'write' => true],
            ],
        ],
        [
            'label' => 'Riwayat Transaksi', 'icon' => 'fa-clock-rotate-left',
            'route' => 'admin.transactions', 'match' => ['admin.transactions*'],
            'children' => [
                ['label' => 'Semua Transaksi', 'icon' => 'fa-layer-group', 'url' => route('admin.transactions', ['status' => 'all']), 'match' => ['admin.transactions'], 'query' => ['status' => 'all']],
                ['label' => 'Transaksi Aktif', 'icon' => 'fa-circle-check', 'url' => route('admin.transactions'), 'match' => ['admin.transactions'], 'query' => []],
                ['label' => 'Transaksi Dibatalkan', 'icon' => 'fa-ban', 'url' => route('admin.transactions', ['status' => 'voided']), 'match' => ['admin.transactions'], 'query' => ['status' => 'voided']],
            ],
        ],
        [
            'label' => 'Kas Keluar', 'hint' => 'Operasional', 'icon' => 'fa-money-bill-trend-up',
            'route' => 'admin.expenses', 'match' => ['admin.expenses*'],
            'children' => [
                ['label' => 'Daftar Kas Keluar', 'icon' => 'fa-list-ul', 'url' => route('admin.expenses'), 'match' => ['admin.expenses']],
                ['label' => 'Catat Pengeluaran', 'icon' => 'fa-pen-to-square', 'url' => route('admin.expenses').'#catat', 'match' => [], 'write' => true],
            ],
        ],
        [
            'label' => 'Laporan Keuangan', 'icon' => 'fa-chart-column',
            // Untuk Pengasuh, laporan Kas Masuk sudah diwakili menu "Kas Masuk" di atas.
            'route' => $isViewer ? 'admin.reports.cash-flow' : 'admin.reports.cash',
            'match' => $isViewer
                ? ['admin.reports.cash-flow', 'admin.reports.arrears*', 'admin.reports.closing*']
                : ['admin.reports.*'],
            'children' => [
                ...($isViewer ? [] : [
                    ['label' => 'Kas Masuk', 'icon' => 'fa-money-bill-wave', 'url' => route('admin.reports.cash'), 'match' => ['admin.reports.cash', 'admin.reports.cash.*']],
                ]),
                ['label' => 'Arus Kas', 'icon' => 'fa-scale-balanced', 'url' => route('admin.reports.cash-flow'), 'match' => ['admin.reports.cash-flow']],
                ['label' => 'Rekap Tunggakan', 'icon' => 'fa-clock-rotate-left', 'url' => route('admin.reports.arrears'), 'match' => ['admin.reports.arrears*']],
                ['label' => 'Tutup Buku', 'icon' => 'fa-lock', 'url' => route('admin.reports.closing'), 'match' => ['admin.reports.closing*']],
            ],
        ],
        [
            'label' => 'Data Santri', 'icon' => 'fa-user-graduate',
            'route' => 'admin.students', 'match' => ['admin.students*'],
            'children' => [
                ['label' => 'Daftar Santri', 'icon' => 'fa-users', 'url' => route('admin.students'), 'match' => ['admin.students', 'admin.students.card*'], 'query' => []],
                ['label' => 'Tambah Santri Baru', 'icon' => 'fa-user-plus', 'url' => route('admin.students', ['tambah' => 1]), 'match' => ['admin.students'], 'query' => ['tambah' => '1'], 'write' => true],
            ],
        ],
        [
            'label' => 'Data Guru & Bisyarah', 'icon' => 'fa-chalkboard-user',
            'route' => 'admin.teachers', 'match' => ['admin.teachers*', 'admin.bisyarah*'],
            'children' => [
                ['label' => 'Data Guru', 'icon' => 'fa-user-tie', 'url' => route('admin.teachers'), 'match' => ['admin.teachers*'], 'query' => []],
                ['label' => 'Tambah Guru', 'icon' => 'fa-user-plus', 'url' => route('admin.teachers', ['tambah' => 1]), 'match' => ['admin.teachers'], 'query' => ['tambah' => '1'], 'write' => true],
                ['label' => 'Bisyarah Bulanan', 'icon' => 'fa-money-bill-wave', 'url' => route('admin.bisyarah'), 'match' => ['admin.bisyarah*']],
            ],
        ],
    ];

    $adminMenu = [
        ['label' => 'Log Aktivitas', 'icon' => 'fa-clipboard-list', 'route' => 'admin.logs', 'match' => ['admin.logs']],
        ['label' => 'Akun Pengurus', 'icon' => 'fa-users-gear', 'route' => 'admin.accounts', 'match' => ['admin.accounts*']],
    ];

    // Submenu aktif bila route cocok dan (jika ditentukan) parameter query cocok persis.
    $childIsActive = function (array $child): bool {
        if ($child['match'] === [] || ! request()->routeIs(...$child['match'])) {
            return false;
        }

        if (! array_key_exists('query', $child)) {
            return true;
        }

        $watched = ['tambah', 'status'];
        $current = array_intersect_key(request()->query(), array_flip($watched));

        return $current == $child['query'];
    };

    $parentClasses = fn (bool $active) => $active
        ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-900/40'
        : 'text-slate-300 hover:bg-slate-800 hover:text-white';
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
            @php
                $active = request()->routeIs(...$item['match']);
                $children = collect($item['children'] ?? [])
                    ->reject(fn (array $child) => $isViewer && ($child['write'] ?? false))
                    ->values();
                $hasChildren = $children->isNotEmpty();
                $menuId = 'submenu-'.\Illuminate\Support\Str::slug($item['label']);
            @endphp

            @if(! $hasChildren)
                <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif data-sidebar-link
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $parentClasses($active) }}" title="{{ $item['label'] }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-white/15' : 'bg-slate-800/70 group-hover:bg-slate-700' }}">
                        <i class="fas {{ $item['icon'] }} text-base"></i>
                    </span>
                    <span data-sidebar-label class="flex min-w-0 flex-col leading-tight">
                        <span class="truncate">{{ $item['label'] }}</span>
                        @isset($item['hint'])
                            <span class="truncate text-[10px] font-normal {{ $active ? 'text-blue-100' : 'text-slate-500' }}">{{ $item['hint'] }}</span>
                        @endisset
                    </span>
                </a>
            @else
                <div data-submenu class="{{ $active ? 'is-open' : '' }}">
                    <button type="button" data-submenu-toggle data-submenu-href="{{ route($item['route']) }}"
                            aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="{{ $menuId }}"
                            data-sidebar-link
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition {{ $parentClasses($active) }}" title="{{ $item['label'] }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-white/15' : 'bg-slate-800/70 group-hover:bg-slate-700' }}">
                            <i class="fas {{ $item['icon'] }} text-base"></i>
                        </span>
                        <span data-sidebar-label class="flex min-w-0 flex-1 flex-col leading-tight">
                            <span class="truncate">{{ $item['label'] }}</span>
                            @isset($item['hint'])
                                <span class="truncate text-[10px] font-normal {{ $active ? 'text-blue-100' : 'text-slate-500' }}">{{ $item['hint'] }}</span>
                            @endisset
                        </span>
                        <i data-sidebar-label data-submenu-chevron class="fas fa-chevron-down text-[10px] transition-transform {{ $active ? 'rotate-180' : '' }} {{ $active ? 'text-blue-100' : 'text-slate-500' }}"></i>
                    </button>

                    <ul id="{{ $menuId }}" data-submenu-list class="{{ $active ? '' : 'hidden' }} mt-1 space-y-0.5 border-l border-slate-700/70 pl-3 ml-7" role="menu">
                        @foreach($children as $child)
                            @php $childActive = $childIsActive($child); @endphp
                            <li role="none">
                                <a href="{{ $child['url'] }}" role="menuitem" @if($childActive) aria-current="page" @endif
                                   class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ $childActive ? 'bg-slate-800 font-semibold text-white' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white' }}">
                                    <i class="fas {{ $child['icon'] }} w-4 text-center text-[11px] {{ $childActive ? 'text-blue-400' : 'text-slate-500' }}"></i>
                                    <span class="truncate">{{ $child['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        @if($isAdmin)
            <p data-sidebar-label class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Administrasi</p>
            @foreach($adminMenu as $item)
                @php $active = request()->routeIs(...$item['match']); @endphp
                <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif data-sidebar-link
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $parentClasses($active) }}" title="{{ $item['label'] }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-white/15' : 'bg-slate-800/70 group-hover:bg-slate-700' }}">
                        <i class="fas {{ $item['icon'] }} text-base"></i>
                    </span>
                    <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
                </a>
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
