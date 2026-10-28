{{-- Navbar atas untuk halaman login pengurus dan portal wali santri (gaya sama dengan halaman depan). --}}
@php
    $isPortal = request()->is('portal*');
    $isAdminArea = request()->is('admin*');
    $portalLoggedIn = $isPortal && session('portal_student_id');
    $portalLink = fn (bool $active) => $active
        ? 'inline-flex items-center gap-1.5 rounded-xl bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-900 sm:text-sm'
        : 'inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:text-sm';
@endphp
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="h-10 w-10 shrink-0 rounded-full bg-white object-contain p-0.5 shadow ring-1 ring-slate-200">
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-bold tracking-tight text-slate-900 sm:text-base">Pesantren Tadabbur Al-Qur'an Amsa001</span>
                <span class="hidden truncate text-[11px] text-slate-500 sm:block">
                    @if($isAdminArea)
                        Panel Bendahara & Kasir Pesantren
                    @elseif($isPortal)
                        Portal Khusus Wali Santri
                    @else
                        SIKA PTQ &middot; Sistem Informasi Keuangan Amanah
                    @endif
                </span>
            </span>
        </a>

        <nav class="flex shrink-0 items-center gap-1 sm:gap-2" aria-label="Navigasi">
            @if($portalLoggedIn)
                <a href="{{ route('portal.dashboard') }}" class="{{ $portalLink(request()->routeIs('portal.dashboard')) }}">
                    <i class="fa-solid fa-user-graduate"></i> <span class="hidden sm:inline">Dashboard Santri</span>
                </a>
                <a href="{{ route('portal.card') }}" class="{{ $portalLink(request()->routeIs('portal.card')) }}">
                    <i class="fa-solid fa-address-card"></i> <span class="hidden sm:inline">Kartu Keuangan</span>
                </a>
                <a href="{{ route('portal.profile') }}" class="{{ $portalLink(request()->routeIs('portal.profile', 'portal.change-pin')) }}">
                    <i class="fa-solid fa-user"></i> Profil
                </a>
                <a href="{{ route('portal.logout') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100 sm:text-sm">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            @elseif($isPortal)
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-house text-xs"></i> Halaman Depan
                </a>
            @elseif($isAdminArea)
                <a href="{{ route('portal.login') }}" class="hidden items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 sm:inline-flex">
                    <i class="fa-solid fa-user-shield text-xs"></i> Portal Wali Santri
                </a>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-house text-xs"></i> Halaman Depan
                </a>
            @else
                <a href="{{ route('admin.login') }}" class="hidden items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 sm:inline-flex">
                    <i class="fa-solid fa-lock text-xs"></i> Login Pengurus / Bendahara
                </a>
                <a href="{{ route('portal.login') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-user-shield text-xs"></i> Masuk Portal Wali Santri
                </a>
            @endif
        </nav>
    </div>
</header>
