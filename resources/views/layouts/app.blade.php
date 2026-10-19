<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Keuangan PTQ Amsa001')</title>
    <!-- Tailwind CSS (di-host lokal agar demo tetap berjalan tanpa internet) -->
    <script src="{{ asset('vendor/tailwind/tailwind.min.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        gold: {
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
</head>
<body class="bg-gray-50 text-gray-800 antialiased font-sans min-h-screen flex flex-col justify-between">

    <!-- Top Navigation Bar -->
    <header class="bg-brand-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="w-10 h-10 object-contain rounded-full bg-white p-0.5 shadow">
                    <div>
                        <a href="{{ url('/') }}" class="text-sm sm:text-base font-bold tracking-tight hover:text-gold-400 transition">
                            Pesantren Tadabbur Al-Qur'an Amsa001
                        </a>
                        <p class="text-xs text-blue-200 hidden sm:block">
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

                <!-- Navigation Links (Terpisah berdasarkan area) -->
                <nav class="flex items-center space-x-1 sm:space-x-3 text-xs sm:text-sm font-medium">
                    @if(request()->is('portal*'))
                        {{-- NAV KHUSUS PORTAL WALI SANTRI --}}
                        @if(session('portal_student_id'))
                            <a href="{{ route('portal.dashboard') }}" class="px-3 py-1.5 rounded-lg bg-brand-800 hover:bg-brand-700 text-gold-400 border border-brand-700">
                                <i class="fa-solid fa-user-graduate mr-1"></i> Dashboard Santri
                            </a>
                            <a href="{{ route('portal.logout') }}" class="px-3 py-1.5 rounded-lg text-gray-300 hover:text-white hover:bg-brand-800">
                                <i class="fa-solid fa-right-from-bracket mr-1"></i> Keluar
                            </a>
                        @else
                            <a href="{{ url('/') }}" class="px-2.5 py-1.5 rounded-lg text-gray-200 hover:text-white hover:bg-brand-800">
                                <i class="fa-solid fa-house mr-1"></i> Beranda Utama
                            </a>
                        @endif
                    @elseif(request()->is('admin*'))
                        {{-- NAV KHUSUS ADMIN / BENDAHARA / PENGASUH --}}
                        @auth
                            <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-brand-800 text-white font-bold' : 'text-gray-200 hover:text-white hover:bg-brand-800' }}">
                                <i class="fa-solid fa-gauge mr-1"></i> Dashboard
                            </a>
                            @if(auth()->user()->role?->canManageFinances())
                                <a href="{{ route('admin.cashier') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('admin.cashier') ? 'bg-gold-400 text-brand-900 font-bold' : 'text-gray-200 hover:text-white hover:bg-brand-800' }}">
                                    <i class="fa-solid fa-cash-register mr-1"></i> Kasir
                                </a>
                                <a href="{{ route('admin.invoices') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('admin.invoices') ? 'bg-brand-800 text-white font-bold' : 'text-gray-200 hover:text-white hover:bg-brand-800' }}">
                                    <i class="fa-solid fa-list-check mr-1"></i> Tagihan
                                </a>
                            @endif
                            <a href="{{ route('admin.reports.cash') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-emerald-600 text-white font-bold' : 'text-gray-200 hover:text-white hover:bg-brand-800' }} transition">
                                <i class="fa-solid fa-chart-column mr-1"></i> Laporan
                            </a>
                            @if(auth()->user()->role?->isAdmin())
                                <a href="{{ route('admin.logs') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('admin.logs') ? 'bg-rose-600 text-white font-bold' : 'text-gray-200 hover:text-white hover:bg-brand-800' }} transition">
                                    <i class="fa-solid fa-clipboard-list mr-1"></i> Log Aktivitas
                                </a>
                            @endif
                            <span class="hidden sm:flex flex-col items-end leading-tight px-2 text-right">
                                <span class="text-[11px] font-semibold text-white">{{ auth()->user()->name }}</span>
                                <span class="text-[10px] text-gold-400 font-medium">{{ auth()->user()->displayTitle() }}</span>
                            </span>
                            <form method="POST" action="{{ route('admin.logout') }}" class="ml-1">
                                @csrf
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs text-gray-300 hover:text-white hover:bg-brand-800 border border-brand-700/60 transition" title="Keluar dari panel">
                                    <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Keluar
                                </button>
                            </form>
                        @else
                            <a href="{{ url('/') }}" class="px-3 py-1.5 rounded-lg text-gray-200 hover:text-white hover:bg-brand-800">
                                <i class="fa-solid fa-house mr-1"></i> Beranda Utama
                            </a>
                        @endauth
                    @else
                        {{-- NAV BERANDA DEPAN / PUBLIK --}}
                        <a href="{{ route('admin.login') }}" class="px-3.5 py-1.5 rounded-lg bg-gold-400 text-brand-900 font-bold hover:bg-gold-500 shadow-sm transition">
                            <i class="fa-solid fa-lock mr-1"></i> Login Pengurus / Kasir
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Flash Alert Messages -->
        @if(session('success'))
            <div class="mb-5 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm flex items-start space-x-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xl mt-0.5"></i>
                <div class="flex-1 text-sm text-emerald-800">
                    <p class="font-semibold">Berhasil!</p>
                    <p>{{ session('success') }}</p>
                    @if(session('wa_direct_url'))
                        <div class="mt-3 flex items-center space-x-2">
                            <a href="{{ session('wa_direct_url') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-medium text-xs shadow transition">
                                <i class="fa-brands fa-whatsapp text-sm mr-1.5"></i> Kirim Notifikasi Kuitansi via WhatsApp
                            </a>
                            @if(session('receipt_id'))
                                <a href="{{ route('receipt.download', session('receipt_id')) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded font-medium text-xs shadow transition">
                                    <i class="fa-solid fa-file-pdf text-sm mr-1.5"></i> Unduh PDF Kuitansi
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-5 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm flex items-start space-x-3">
                <i class="fa-solid fa-circle-info text-blue-600 text-xl mt-0.5"></i>
                <div class="text-sm text-blue-800">{{ session('info') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm flex items-start space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-xl mt-0.5"></i>
                <div class="text-sm text-rose-800">
                    <p class="font-semibold">Periksa kembali data Anda:</p>
                    <ul class="list-disc list-inside mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12 text-center text-xs text-gray-500">
        <div class="max-w-7xl mx-auto px-4 space-y-2">
            <p class="font-medium text-gray-700">Pesantren Tadabbur Al-Qur'an Amsa001 (PTQ Amsa001)</p>
            <p>Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor, Jawa Barat 16770</p>
            <p>Layanan Keuangan WhatsApp: <a href="https://wa.me/6285692253516" target="_blank" class="text-brand-700 font-semibold hover:underline">+62 856-9225-3516</a> | Website: <a href="https://www.ptqamsa.id" target="_blank" class="text-brand-700 font-semibold hover:underline">www.ptqamsa.id</a></p>
        </div>
    </footer>

</body>
</html>
