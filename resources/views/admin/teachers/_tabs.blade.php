@php
    // Dua halaman guru berbagi navigasi agar Bendahara mudah berpindah
    // antara data induk (sekali isi) dan pencatatan bisyarah bulanan.
    $teacherTabs = [
        [
            'route' => 'admin.teachers',
            'label' => 'Data Guru',
            'icon' => 'fa-user-tie',
            'iconColor' => 'text-amber-600',
            'active' => request()->routeIs('admin.teachers'),
        ],
        [
            'route' => 'admin.bisyarah',
            'label' => 'Bisyarah Bulanan',
            'icon' => 'fa-money-bill-wave',
            'iconColor' => 'text-emerald-600',
            'active' => request()->routeIs('admin.bisyarah*'),
        ],
    ];
@endphp

<nav class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl self-start md:self-auto text-xs font-semibold overflow-x-auto whitespace-nowrap max-w-full"
     aria-label="Navigasi data guru">
    @foreach($teacherTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           @if($tab['active']) aria-current="page" @endif
           class="inline-flex items-center px-3.5 py-2 rounded-lg transition {{ $tab['active'] ? 'bg-white text-brand-900 shadow-sm' : 'text-gray-600 hover:text-brand-900 hover:bg-white/60' }}">
            <i class="fa-solid {{ $tab['icon'] }} mr-1.5 {{ $tab['iconColor'] }}"></i> {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
