{{-- Navbar atas untuk halaman publik, login pengurus, dan portal wali santri. --}}
<header class="sticky top-0 z-50 bg-brand-900 text-white shadow-md">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="h-10 w-10 rounded-full bg-white object-contain p-0.5 shadow">
                <div>
                    <a href="{{ url('/') }}" class="text-sm font-bold tracking-tight transition hover:text-gold-400 sm:text-base">
                        Pesantren Tadabbur Al-Qur'an Amsa001
                    </a>
                    <p class="hidden text-xs text-blue-200 sm:block">
                        @if(request()->is('admin*'))
                            Panel Bendahara & Kasir Pesantren
                        @elseif(request()->is('portal*'))
                            Portal Khusus Wali Santri
                        @else
                            Sistem Keuangan & Tagihan Santri
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-1 text-xs font-medium sm:space-x-2 sm:text-sm">
                @if(request()->is('portal*'))
                    @if(session('portal_student_id'))
                        <a href="{{ route('portal.dashboard') }}" class="rounded-lg px-3 py-1.5 {{ request()->routeIs('portal.dashboard') ? 'border border-brand-700 bg-brand-800 text-gold-400' : 'text-gray-300 hover:bg-brand-800 hover:text-white' }}">
                            <i class="fa-solid fa-user-graduate mr-1"></i> Dashboard Santri
                        </a>
                        <a href="{{ route('portal.card') }}" class="rounded-lg px-3 py-1.5 {{ request()->routeIs('portal.card') ? 'border border-brand-700 bg-brand-800 text-gold-400' : 'text-gray-300 hover:bg-brand-800 hover:text-white' }}">
                            <i class="fa-solid fa-address-card mr-1"></i> Kartu Keuangan
                        </a>
                        <a href="{{ route('portal.profile') }}" class="rounded-lg px-3 py-1.5 {{ request()->routeIs('portal.profile', 'portal.change-pin') ? 'border border-brand-700 bg-brand-800 text-gold-400' : 'text-gray-300 hover:bg-brand-800 hover:text-white' }}">
                            <i class="fa-solid fa-user mr-1"></i> Profil
                        </a>
                        <a href="{{ route('portal.logout') }}" class="rounded-lg px-3 py-1.5 text-gray-300 hover:bg-brand-800 hover:text-white">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Keluar
                        </a>
                    @endif
                @elseif(request()->is('admin*'))
                    <a href="{{ url('/') }}" class="rounded-lg px-3 py-1.5 text-gray-200 hover:bg-brand-800 hover:text-white">
                        <i class="fa-solid fa-house mr-1"></i> Beranda Utama
                    </a>
                @elseif(request()->is('/'))
                    {{-- Login pengurus hanya ditawarkan di beranda, bukan di halaman publik lain. --}}
                    <a href="{{ route('admin.login') }}" class="rounded-lg bg-gold-400 px-3.5 py-1.5 font-bold text-brand-900 shadow-sm transition hover:bg-gold-500">
                        <i class="fa-solid fa-lock mr-1"></i> Login Pengurus / Bendahara
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
