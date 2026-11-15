{{--
    Satu baris berkas/tautan (dipakai di repo-dok.index & repo-dok.cari) --
    $file = App\Models\RepoDokFile, $withFolderPath (opsional, bool) buat
    nampilin lokasi foldernya (dipakai di halaman hasil pencarian, karena
    hasilnya bisa dari folder mana pun).
--}}
@php($isFile = $file->isFile())
<div class="rd-file-row">
    <div class="rd-file-icon {{ $isFile ? 'file' : 'link' }}">
        <i class="bi {{ $isFile ? 'bi-file-earmark-text-fill' : 'bi-link-45deg' }}"></i>
    </div>

    <div class="rd-file-info">
        <div class="nama">
            {{ $file->nama }}
            <span class="rd-badge {{ $isFile ? 'file' : 'link' }}">{{ $file->tipe->getLabel() }}</span>
        </div>
        @if ($file->deskripsi)
            <div class="desk">{{ $file->deskripsi }}</div>
        @endif
        @if (! empty($withFolderPath) && $file->folder)
            <div class="path">
                <i class="bi bi-folder2"></i>
                @foreach ($file->folder->breadcrumbTrail() as $node)
                    @if (! $loop->first) <span>/</span> @endif
                    <a href="{{ route('repo-dok.folder', $node) }}" class="rd-a">{{ $node->nama }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($isFile)
        <a href="{{ route('repo-dok.unduh', $file) }}" class="rd-a rd-file-action">
            <i class="bi bi-download"></i> Unduh
        </a>
    @else
        <a href="{{ $file->link_url }}" target="_blank" rel="noopener noreferrer" class="rd-a rd-file-action">
            <i class="bi bi-box-arrow-up-right"></i> Buka Tautan
        </a>
    @endif
</div>
