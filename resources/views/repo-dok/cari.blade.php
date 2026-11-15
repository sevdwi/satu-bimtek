@extends('layouts.repo_dok')

@section('title', 'Hasil Pencarian')

@section('content')

    <nav class="rd-breadcrumb">
        <a href="{{ route('repo-dok.index') }}" class="rd-a"><i class="bi bi-house-door-fill"></i> Repositori</a>
        <span class="sep">/</span>
        <span>Hasil pencarian{{ $kata !== '' ? ": \"{$kata}\"" : '' }}</span>
    </nav>

    <div class="rd-panel">
        @if ($kata === '')
            <div class="rd-empty">
                <i class="bi bi-search"></i>
                Ketik kata kunci pada kolom pencarian di atas untuk mulai mencari.
            </div>
        @elseif ($folderHasil->isEmpty() && $fileHasil->isEmpty())
            <div class="rd-empty">
                <i class="bi bi-emoji-frown"></i>
                Tidak ada folder, berkas, atau tautan yang cocok dengan "{{ $kata }}".
            </div>
        @else
            @if ($folderHasil->isNotEmpty())
                <div class="rd-search-result-group">
                    <div class="rd-panel-heading">
                        <h2><i class="bi bi-folder2-open"></i> Folder ({{ $folderHasil->count() }})</h2>
                    </div>
                    <div class="rd-folder-grid">
                        @foreach ($folderHasil as $sub)
                            <a href="{{ route('repo-dok.folder', $sub) }}" class="rd-a rd-folder-card">
                                <i class="bi bi-folder-fill"></i>
                                <div class="nama">{{ $sub->nama }}</div>
                                @if ($sub->deskripsi)
                                    <div class="meta">{{ $sub->deskripsi }}</div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($fileHasil->isNotEmpty())
                <div class="rd-search-result-group">
                    <div class="rd-panel-heading">
                        <h2><i class="bi bi-file-earmark-text"></i> Berkas &amp; Tautan ({{ $fileHasil->count() }})</h2>
                    </div>
                    <div class="rd-file-list">
                        @foreach ($fileHasil as $file)
                            @include('repo-dok.partials.file-row', ['file' => $file, 'withFolderPath' => true])
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

@endsection
