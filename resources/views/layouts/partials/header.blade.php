@inject('headerNotifications', 'App\Services\HeaderNotificationService')
@php
    $user = auth()->user();
    $notifications = $headerNotifications->build($user);
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter(fn ($part) => $part !== '' && preg_match('/^[A-Za-z]/', $part))
        ->reject(fn ($part) => in_array(strtolower(rtrim($part, '.')), ['ust', 'usth', 'ustadz', 'ustadzah', 'kh', 'drs', 'h', 'hj'], true))
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'PT';
    $shortName = $user ? preg_replace('/\s*\(.*\)\s*$/', '', $user->name) : '';
    $canManage = (bool) $user?->role?->canManageFinances();
    $isAdmin = (bool) $user?->role?->isAdmin();

    $notifColor = [
        'rose' => 'bg-rose-100 text-rose-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'violet' => 'bg-violet-100 text-violet-600',
        'slate' => 'bg-slate-200 text-slate-600',
    ];
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-slate-200 bg-white/95 px-3 shadow-sm backdrop-blur sm:gap-3 sm:px-5">

    <!-- Hamburger -->
    <button type="button" data-sidebar-toggle
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
            aria-label="Buka / tutup menu" aria-controls="app-sidebar">
        <i class="fas fa-bars text-lg"></i>
    </button>

    <!-- A. Global Quick Search -->
    <div id="quick-search" class="relative min-w-0 flex-1 sm:max-w-xl">
        <label for="quick-search-input" class="sr-only">Pencarian cepat</label>
        <div class="relative">
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <input type="search" id="quick-search-input" autocomplete="off" spellcheck="false"
                   data-search-url="{{ route('admin.search') }}"
                   placeholder="Cari nama santri, NIS, no. kuitansi..."
                   class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-20 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30">
            <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold text-slate-400 sm:inline-flex">
                <span data-search-shortcut>Ctrl</span> + K
            </kbd>
        </div>

        <!-- Dropdown hasil -->
        <div id="quick-search-results"
             class="absolute left-0 right-0 top-full z-40 mt-2 hidden max-h-[70vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl"
             role="listbox" aria-label="Hasil pencarian">
            <div data-search-state="hint" class="px-4 py-6 text-center text-xs text-slate-400">
                Ketik minimal 2 huruf. Contoh: nama santri, NIS, nomor kuitansi, atau nama guru.
            </div>
            <div data-search-state="loading" class="hidden px-4 py-6 text-center text-xs text-slate-400">
                <i class="fas fa-circle-notch fa-spin mr-1.5"></i> Mencari...
            </div>
            <div data-search-state="empty" class="hidden px-4 py-6 text-center text-xs text-slate-400">
                <i class="fas fa-face-frown mr-1.5"></i> Tidak ada hasil untuk "<span data-search-term></span>".
            </div>
            <div data-search-state="error" class="hidden px-4 py-6 text-center text-xs text-rose-500">
                Pencarian gagal. Coba lagi beberapa saat.
            </div>
            <div data-search-state="results" class="hidden divide-y divide-slate-100"></div>
        </div>
    </div>

    <div class="ml-auto flex items-center gap-1 sm:gap-2">

        <!-- B. Notification Center -->
        <div class="relative" data-dropdown>
            <button type="button" data-dropdown-toggle
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Pusat informasi kasir" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell text-lg"></i>
                @if($notifications['count'] > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">
                        {{ $notifications['count'] > 99 ? '99+' : $notifications['count'] }}
                    </span>
                @endif
            </button>
            <div data-dropdown-menu class="absolute right-0 top-full z-40 mt-2 hidden w-80 origin-top-right rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-96">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Pusat Informasi Kasir</p>
                        <p class="text-[11px] text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                    </div>
                    @if($notifications['count'] > 0)
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-600">{{ $notifications['count'] }} perlu tindakan</span>
                    @else
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600">Aman</span>
                    @endif
                </div>
                <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                    @forelse($notifications['items'] as $item)
                        <a href="{{ $item['url'] }}" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $notifColor[$item['color']] ?? $notifColor['slate'] }}">
                                <i class="fas {{ $item['icon'] }}"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-800">{{ $item['title'] }}</span>
                                <span class="block text-xs leading-snug text-slate-500">{{ $item['text'] }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-xs text-slate-400">
                            <i class="fas fa-mug-hot mb-2 block text-2xl text-slate-300"></i>
                            Tidak ada yang perlu ditindak hari ini.
                        </div>
                    @endforelse
                </div>
                <div class="border-t border-slate-100 px-4 py-2.5 text-right">
                    <a href="{{ route('admin.reports.arrears') }}" class="text-xs font-semibold text-blue-600 hover:underline">Lihat rekap tunggakan &rarr;</a>
                </div>
            </div>
        </div>

        <!-- C. User Profile & Role -->
        <div class="relative" data-dropdown>
            <button type="button" data-dropdown-toggle
                    class="flex h-10 items-center gap-2.5 rounded-xl px-1.5 transition hover:bg-slate-100 sm:pr-3"
                    aria-haspopup="true" aria-expanded="false">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-xs font-bold text-white shadow">
                    {{ $initials }}
                </span>
                <span class="hidden min-w-0 flex-col items-start leading-tight text-left md:flex">
                    <span class="max-w-[11rem] truncate text-sm font-semibold text-slate-800">{{ $shortName }}</span>
                    <span class="max-w-[11rem] truncate text-[11px] text-slate-500">{{ $user?->displayTitle() }}</span>
                </span>
                <i class="fas fa-chevron-down hidden text-[10px] text-slate-400 md:block"></i>
            </button>
            <div data-dropdown-menu class="absolute right-0 top-full z-40 mt-2 hidden w-64 origin-top-right rounded-2xl border border-slate-200 bg-white py-1.5 shadow-2xl">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="truncate text-sm font-bold text-slate-800">{{ $user?->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user?->displayTitle() }}</p>
                    <span class="mt-1.5 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700">
                        {{ $user?->role?->label() ?? '-' }}
                    </span>
                </div>
                <a href="{{ route('admin.password.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-shield-halved w-4 text-center text-slate-400"></i> Ubah Sandi / Keamanan Akun
                </a>
                @if($isAdmin)
                    <a href="{{ route('admin.logs') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                        <i class="fas fa-clipboard-list w-4 text-center text-slate-400"></i> Log Aktivitas Sistem
                    </a>
                @else
                    <a href="{{ route('admin.transactions') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                        <i class="fas fa-clipboard-list w-4 text-center text-slate-400"></i> Log Aktivitas Kasir
                    </a>
                @endif
                <form method="POST" action="{{ route('admin.logout') }}" data-allow-readonly class="mt-1 border-t border-slate-100 pt-1">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50" title="Keluar dari panel">
                        <i class="fas fa-arrow-right-from-bracket w-4 text-center"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
