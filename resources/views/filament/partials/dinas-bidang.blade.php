@php
    // Menampilkan nama dinas (opd_induk) & nama bidang (opd) punya user yang lagi login
    // di topbar, khusus buat role Admin Dinas & Pengolah. Superadmin nggak perlu ini
    // karena dia nggak terikat ke satu dinas/bidang tertentu.
    $user = auth()->user();
@endphp

@if ($user && ! $user->isSuperadmin() && ($user->opdInduk || $user->opd))
    <div class="fi-dinas-bidang-info hidden sm:flex items-center justify-end mr-3 text-end text-xs font-medium text-gray-600 dark:text-gray-300">
        @if ($user->opdInduk)
            <span>{{ $user->opdInduk->instansi }}</span>
        @endif

        @if ($user->opdInduk && $user->opd)
            <span class="mx-1 text-gray-400 dark:text-gray-500">-</span>
        @endif

        @if ($user->opd)
            <span>{{ $user->opd->unit_kerja }}</span>
        @endif
    </div>
@endif
