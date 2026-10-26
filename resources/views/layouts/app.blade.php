@php
    // Panel pengurus (sudah login) memakai sidebar kiri + header atas.
    // Halaman publik, login, dan portal wali tetap memakai navbar atas.
    $usesAdminShell = request()->is('admin*') && auth()->check();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Keuangan PTQ Amsa001')</title>
    <!-- Favicon: logo resmi pesantren format SVG (di-host lokal) -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/png" sizes="64x64" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <!-- Tailwind CSS v3 (di-host lokal agar demo tetap berjalan tanpa internet) -->
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
    @if($usesAdminShell)
        <script>
            // Terapkan status "ringkas" sebelum render agar sidebar tidak berkedip.
            try {
                if (window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('ptq.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        </script>
        <style>
            .sidebar-collapsed #app-sidebar { width: 5rem; }
            .sidebar-collapsed #app-sidebar [data-sidebar-label] { display: none; }
            .sidebar-collapsed #app-sidebar [data-submenu-list] { display: none; }
            @media (min-width: 1024px) { .sidebar-collapsed #app-shell { padding-left: 5rem; } }

            /* Mode lihat-saja (Pengasuh): sembunyikan tombol yang mengubah data.
               Form GET (filter/pencarian) dan form yang ditandai data-allow-readonly tetap aktif. */
            .readonly-mode form:not([method="GET"]):not([method="get"]):not([data-allow-readonly]) button[type="submit"],
            .readonly-mode button[onclick^="open"],
            .readonly-mode [data-write] { display: none !important; }
        </style>
    @endif
</head>

@php $isReadOnlyViewer = $usesAdminShell && auth()->user()?->role === \App\Enums\UserRole::Pengasuh; @endphp

@if($usesAdminShell)
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased {{ $isReadOnlyViewer ? 'readonly-mode' : '' }}">

    @include('layouts.partials.sidebar')

    <!-- Area konten: bergeser mengikuti lebar sidebar di desktop -->
    <div id="app-shell" class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-64">

        @include('layouts.partials.header')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-7xl">
                @include('layouts.partials.flash')
                @yield('content')
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center text-[11px] text-slate-500 sm:px-6">
            <p class="font-medium text-slate-700">Pesantren Tadabbur Al-Qur'an Amsa001 (PTQ Amsa001)</p>
            <p>Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor, Jawa Barat 16770</p>
            <p>Layanan Keuangan WhatsApp: <a href="https://wa.me/6285692253516" target="_blank" class="font-semibold text-brand-700 hover:underline">+62 856-9225-3516</a> | Website: <a href="https://www.ptqamsa.id" target="_blank" class="font-semibold text-brand-700 hover:underline">www.ptqamsa.id</a></p>
        </footer>
    </div>

    @include('layouts.partials.scripts')
    @include('layouts.partials.table-sort')
    @include('layouts.partials.back-to-top')
</body>
@else
<body class="flex min-h-screen flex-col justify-between bg-slate-50 font-sans text-slate-800 antialiased">

    @include('layouts.partials.topbar')

    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-6 sm:px-6 lg:px-8">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-gray-200 bg-white py-6 text-center text-xs text-gray-500">
        <div class="mx-auto max-w-7xl space-y-2 px-4">
            <p class="font-medium text-gray-700">Pesantren Tadabbur Al-Qur'an Amsa001 (PTQ Amsa001)</p>
            <p>Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor, Jawa Barat 16770</p>
            <p>Layanan Keuangan WhatsApp: <a href="https://wa.me/6285692253516" target="_blank" class="font-semibold text-brand-700 hover:underline">+62 856-9225-3516</a> | Website: <a href="https://www.ptqamsa.id" target="_blank" class="font-semibold text-brand-700 hover:underline">www.ptqamsa.id</a></p>
        </div>
    </footer>

    @include('layouts.partials.table-sort')
    @include('layouts.partials.back-to-top')
</body>
@endif
</html>
