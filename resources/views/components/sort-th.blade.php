@props([
    'column',
    'label',
    'sortBy',
    'sortOrder' => 'asc',
    'align' => 'left',
])

@php
    // Judul kolom yang bisa diklik untuk mengurutkan data (server-side).
    // Klik pertama mengurutkan naik, klik berikutnya membalik arah.
    $isActive = $sortBy === $column;
    $nextOrder = ($isActive && $sortOrder === 'asc') ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort_by' => $column, 'sort_order' => $nextOrder]);

    $icon = ! $isActive
        ? 'fa-sort text-gray-300'
        : ($sortOrder === 'asc' ? 'fa-arrow-up-short-wide text-brand-900' : 'fa-arrow-down-wide-short text-brand-900');

    $justify = match ($align) {
        'right' => 'justify-end',
        'center' => 'justify-center',
        default => '',
    };
@endphp

<th {{ $attributes->merge(['class' => 'select-none text-'.$align]) }} @if($isActive) aria-sort="{{ $sortOrder === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="inline-flex items-center {{ $justify }} hover:text-brand-900 transition whitespace-nowrap" title="Urutkan {{ $label }}">
        <span>{{ $label }}</span><i class="fa-solid {{ $icon }} ml-1 text-[10px]"></i>
    </a>
</th>
