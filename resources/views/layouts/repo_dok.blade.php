<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Repositori Dokumentasi')  — {{ config('app.name', 'SATU') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/repo-dok.css') }}">
</head>
<body class="rd-body">

    <header class="rd-topbar">
        <div class="rd-topbar-inner">
            <a href="{{ route('repo-dok.index') }}" class="rd-a rd-brand">
                <img src="{{ asset('images/satu.png') }}" alt="Logo SATU">
                <span>
                    Repositori Dokumentasi
                    <span class="rd-brand-sub">Sistem Informasi Kearsipan Terpadu</span>
                </span>
            </a>

            <div class="rd-topbar-actions">
                @auth
                    <span class="rd-user-chip">
                        {{ auth()->user()->name }}
                        <small>{{ auth()->user()->role?->getLabel() }}</small>
                    </span>
                @endauth

                <a href="{{ url('/admin') }}" class="rd-a">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button type="submit" class="rd-a">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="rd-searchbar-wrap">
        <div class="rd-searchbar">
            <p class="rd-searchbar-title">
                Cari dokumen, panduan, atau referensi
                <small>Telusuri seluruh folder tanpa perlu membuka satu per satu.</small>
            </p>
            <form action="{{ route('repo-dok.cari') }}" method="GET">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Ketik nama dokumen, folder, atau kata kunci…" autocomplete="off">
                <button type="submit">Cari</button>
            </form>
        </div>
    </div>

    <div class="rd-shell">
        <aside class="rd-sidebar">
            <div class="rd-sidebar-title">Struktur Folder</div>
            <ul class="rd-tree">
                <li>
                    <a href="{{ route('repo-dok.index') }}" class="rd-tree-link {{ empty($current) ? 'is-active' : '' }}">
                        <i class="bi bi-archive-fill"></i> Semua Dokumentasi
                    </a>
                </li>
                @foreach ($seluruhFolder->where('parent_id', null) as $akar)
                    @include('repo-dok.partials.tree', ['folder' => $akar, 'semua' => $seluruhFolder, 'activeIds' => $activeIds ?? []])
                @endforeach
            </ul>
        </aside>

        <main class="rd-content">
            @yield('content')
        </main>
    </div>

</body>
</html>
