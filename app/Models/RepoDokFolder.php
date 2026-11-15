<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Folder berjenjang (self-referencing tree) fitur "Repositori Dokumentasi"
 * (/repo_dok) -- lihat migration create_repo_dok_folders_table &
 * App\Filament\Resources\RepoDokFolderResource (satu-satunya cara
 * kelola strukturnya, Superadmin saja). Isi tiap folder ada di
 * App\Models\RepoDokFile lewat relasi files().
 */
#[Fillable(['parent_id', 'nama', 'deskripsi', 'urutan'])]
class RepoDokFolder extends Model
{
    use HasFactory;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan')->orderBy('nama');
    }

    public function files(): HasMany
    {
        return $this->hasMany(RepoDokFile::class, 'folder_id')->orderBy('urutan')->orderBy('nama');
    }

    /**
     * ID diri sendiri + SEMUA keturunan (anak, cucu, dst) -- dipakai
     * RepoDokFolderResource buat mencegah folder dijadikan induk buat
     * dirinya sendiri/keturunannya sendiri (bikin loop tak berujung).
     * Rekursi manual (bukan query CTE) sengaja dipilih karena jumlah
     * folder di fitur ini kecil (puluhan, bukan ribuan) -- lebih
     * portable daripada recursive CTE yang sintaksnya beda MySQL/SQLite.
     *
     * @return array<int>
     */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];

        foreach (self::query()->where('parent_id', $this->id)->get() as $child) {
            $ids = array_merge($ids, $child->selfAndDescendantIds());
        }

        return $ids;
    }

    /**
     * Jejak folder dari akar (root) sampai folder ini sendiri -- dipakai
     * breadcrumb di halaman /repo_dok & partial sidebar-nya. Query
     * berulang (bukan eager load) sengaja dipilih -- kedalaman folder di
     * fitur ini kecil (biasanya 2-3 level), jadi tidak jadi masalah
     * performa, dan tetap benar berapa pun kedalamannya (tidak dibatasi
     * jumlah level with() seperti eager load manual).
     *
     * @return Collection<int, self>
     */
    public function breadcrumbTrail(): Collection
    {
        $trail = collect([$this]);
        $node = $this;

        while ($node->parent_id) {
            $node = self::query()->find($node->parent_id);

            if (! $node) {
                break;
            }

            $trail->prepend($node);
        }

        return $trail;
    }
}
