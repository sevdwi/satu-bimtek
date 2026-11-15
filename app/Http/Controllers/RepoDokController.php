<?php

namespace App\Http\Controllers;

use App\Enums\RepoDokFileType;
use App\Models\RepoDokFile;
use App\Models\RepoDokFolder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman "Repositori Dokumentasi" (/repo_dok) -- browse folder berjenjang
 * + pencarian, TERBUKA untuk SEMUA user yang login (Superadmin, Admin
 * Dinas, Pengolah -- sengaja TANPA scoping per role/dinas, beda dari
 * modul arsip lainnya, karena ini dokumentasi aplikasi milik bersama,
 * bukan data operasional per instansi). Struktur folder & isinya
 * (berkas/tautan) dikelola Superadmin lewat
 * App\Filament\Resources\RepoDokFolderResource -- controller ini
 * BACA SAJA.
 */
class RepoDokController extends Controller
{
    public function index(): View
    {
        return $this->tampilkanFolder(null);
    }

    public function folder(RepoDokFolder $folder): View
    {
        return $this->tampilkanFolder($folder);
    }

    protected function tampilkanFolder(?RepoDokFolder $folder): View
    {
        $subFolders = RepoDokFolder::query()
            ->where('parent_id', $folder?->id)
            ->withCount(['children', 'files'])
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $files = $folder
            ? $folder->files()->with('uploader')->get()
            : collect();

        // Seluruh tree (buat sidebar navigasi) -- dimuat sekali di sini
        // (bukan lewat View Composer global) supaya jelas dari mana
        // datanya & tidak query berulang kalau nanti ada partial lain
        // yang butuh data folder tapi TIDAK butuh tree-nya.
        $seluruhFolder = RepoDokFolder::query()
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $activeIds = $folder ? $folder->breadcrumbTrail()->pluck('id')->all() : [];

        return view('repo-dok.index', [
            'current' => $folder,
            'breadcrumb' => $folder ? $folder->breadcrumbTrail() : collect(),
            'subFolders' => $subFolders,
            'files' => $files,
            'seluruhFolder' => $seluruhFolder,
            'activeIds' => $activeIds,
        ]);
    }

    public function cari(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $kata = trim((string) ($data['q'] ?? ''));

        $folderHasil = collect();
        $fileHasil = collect();

        if ($kata !== '') {
            $folderHasil = RepoDokFolder::query()
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('deskripsi', 'like', "%{$kata}%")
                ->orderBy('nama')
                ->limit(50)
                ->get();

            $fileHasil = RepoDokFile::query()
                ->with('folder')
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('deskripsi', 'like', "%{$kata}%")
                ->orderBy('nama')
                ->limit(100)
                ->get();
        }

        $seluruhFolder = RepoDokFolder::query()
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return view('repo-dok.cari', [
            'kata' => $kata,
            'folderHasil' => $folderHasil,
            'fileHasil' => $fileHasil,
            'seluruhFolder' => $seluruhFolder,
            'activeIds' => [],
            'current' => null,
        ]);
    }

    public function unduh(RepoDokFile $file): StreamedResponse
    {
        abort_unless($file->isFile() && $file->file_path, 404);
        abort_unless(Storage::disk('public')->exists($file->file_path), 404, 'Berkas tidak ditemukan di server.');

        return Storage::disk('public')->download($file->file_path, $file->nama);
    }
}
