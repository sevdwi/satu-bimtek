@extends('layouts.repo_dok')

@section('title', $current?->nama ?? 'Semua Dokumentasi')

@section('content')

    <nav class="rd-breadcrumb">
        <a href="{{ route('repo-dok.index') }}" class="rd-a"><i class="bi bi-house-door-fill"></i> Repositori</a>
        @foreach ($breadcrumb as $node)
            <span class="sep">/</span>
            @if ($node->id === $current?->id)
                <span>{{ $node->nama }}</span>
            @else
                <a href="{{ route('repo-dok.folder', $node) }}" class="rd-a">{{ $node->nama }}</a>
            @endif
        @endforeach
    </nav>

    <div class="rd-panel">
        <div class="rd-panel-heading">
            <h2>
                <i class="bi bi-folder2-open"></i>
                {{ $current?->nama ?? 'Semua Dokumentasi' }}
            </h2>
        </div>

        @if ($current?->deskripsi)
            <p class="rd-current-desc">{{ $current->deskripsi }}</p>
        @endif

        @if ($subFolders->isEmpty() && $files->isEmpty())
            <div class="rd-empty">
                <i class="bi bi-inbox"></i>
                Folder ini masih kosong.
            </div>
        @endif

        @if ($subFolders->isNotEmpty())
            <div class="rd-folder-grid">
                @foreach ($subFolders as $sub)
                    <a href="{{ route('repo-dok.folder', $sub) }}" class="rd-a rd-folder-card">
                        <i class="bi bi-folder-fill"></i>
                        <div class="nama">{{ $sub->nama }}</div>
                        <div class="meta">
                            {{ $sub->children_count }} sub-folder · {{ $sub->files_count }} berkas/tautan
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($files->isNotEmpty())
            <div class="rd-section-label">Berkas &amp; Tautan di Folder Ini</div>
            <div class="rd-file-list">
                @foreach ($files as $file)
                    @include('repo-dok.partials.file-row', ['file' => $file])
                @endforeach
            </div>
        @endif
    </div>

@endsection
