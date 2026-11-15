<?php

namespace App\Models;

use App\Enums\RepoDokFileType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris isi folder Repositori Dokumentasi -- berkas terunggah ATAU
 * tautan eksternal (lihat App\Enums\RepoDokFileType & migration
 * create_repo_dok_files_table). Dikelola lewat
 * App\Filament\Resources\RepoDokFolderResource\RelationManagers\
 * FilesRelationManager (Superadmin saja), ditampilkan ke SEMUA user
 * login lewat App\Http\Controllers\RepoDokController.
 */
#[Fillable(['folder_id', 'nama', 'deskripsi', 'tipe', 'file_path', 'link_url', 'uploaded_by', 'urutan'])]
class RepoDokFile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipe' => RepoDokFileType::class,
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(RepoDokFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFile(): bool
    {
        return $this->tipe === RepoDokFileType::File;
    }

    public function isLink(): bool
    {
        return $this->tipe === RepoDokFileType::Link;
    }
}
