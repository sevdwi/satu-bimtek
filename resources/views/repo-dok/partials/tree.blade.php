{{--
    Partial rekursif buat sidebar tree /repo_dok -- dipanggil dari
    layouts.repo_dok.blade.php buat tiap folder tingkat atas, lalu
    memanggil dirinya sendiri buat sub-foldernya. $semua = koleksi SEMUA
    folder (dimuat sekali di controller) supaya tidak query berulang
    tiap level (lihat App\Http\Controllers\RepoDokController).
--}}
<li>
    <a href="{{ route('repo-dok.folder', $folder) }}"
       class="rd-tree-link {{ in_array($folder->id, $activeIds ?? [], true) ? 'is-active' : '' }}">
        <i class="bi bi-folder-fill"></i> {{ $folder->nama }}
    </a>

    @php($anak = $semua->where('parent_id', $folder->id))
    @if ($anak->isNotEmpty())
        <ul>
            @foreach ($anak as $sub)
                @include('repo-dok.partials.tree', ['folder' => $sub, 'semua' => $semua, 'activeIds' => $activeIds ?? []])
            @endforeach
        </ul>
    @endif
</li>
