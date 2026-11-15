<?php

namespace App\Models;

use App\Enums\ArsipKeterangan;
use App\Enums\ArsipStatus;
use App\Enums\NasibAkhir;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'judul', 'deskripsi', 'jumlah_isi_berkas', 'file', 'tahun', 'periode_id', 'tanggal', 'tanggal_musnah',
    'master_kode_id', 'created_by', 'opd_id', 'opd_induk_id', 'dus_arsip_id', 'rak_arsip_id',
    'aktif', 'inaktif', 'nomor', 'nomor_sementara', 'urut_sementara', 'status', 'nasib_akhir', 'korektor', 'keterangan',
])]
class Arsip extends Model
{
    use HasFactory;

    /**
     * Nomor sementara: "{alias_user pembuat}-{urut}" (contoh "UMI-3" =
     * arsip ke-3 di periodenya, diinput user beralias UMI). Terisi otomatis,
     * bukan input manual.
     *
     * - {alias_user}: alias milik user pembuat (diatur sendiri lewat halaman
     *   Profil); kalau belum diisi, pakai ID user sebagai gantinya.
     * - {urut}: nomor urut di dalam SATU periode -- mulai 1 lagi di tiap
     *   periode (tidak melanjutkan antar periode). Disimpan juga di kolom
     *   urut_sementara biar hitungan berikutnya tidak perlu parse teks.
     *
     * Diisi di event `creating` (sebelum INSERT, jadi cukup 1 query), dan
     * dihitung ulang di `updating` kalau arsip dipindah ke periode lain.
     */
    protected static function booted(): void
    {
        static::creating(function (Arsip $arsip): void {
            $arsip->created_by ??= auth()->id();
            $arsip->isiNomorSementara();
        });

        static::updating(function (Arsip $arsip): void {
            if ($arsip->isDirty('periode_id')) {
                $arsip->isiNomorSementara();
            }
        });
    }

    /**
     * Isi urut_sementara & nomor_sementara berdasarkan periode arsip ini.
     * Urut = urut terbesar yang sudah ada di periode itu + 1 (bukan jumlah
     * arsip + 1), biar tidak dobel kalau ada arsip yang pernah dihapus.
     */
    public function isiNomorSementara(): void
    {
        if (! $this->periode_id) {
            $this->urut_sementara = null;
            $this->nomor_sementara = null;

            return;
        }

        $urut = (int) static::query()
            ->where('periode_id', $this->periode_id)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->max('urut_sementara') + 1;

        $this->urut_sementara = $urut;
        $this->nomor_sementara = static::prefixNomorSementara($this->created_by) . '-' . $urut;
    }

    /**
     * Awalan nomor sementara: alias_user si pembuat, atau ID user-nya kalau
     * alias belum diisi.
     */
    public static function prefixNomorSementara(?int $userId): string
    {
        if (! $userId) {
            return '0';
        }

        $alias = User::query()->whereKey($userId)->value('alias_user');

        return filled($alias) ? $alias : (string) $userId;
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_musnah' => 'date',
            'status' => ArsipStatus::class,
            'nasib_akhir' => NasibAkhir::class,
            // Jenis berkas fisik (Asli/Tembusan/Fotocopy/dst) -- BUKAN
            // MasterKode::$keterangan (itu NasibAkhir, konsep beda meski
            // nama kolom kebetulan sama, lihat App\Enums\ArsipKeterangan).
            'keterangan' => ArsipKeterangan::class,
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function masterKode(): BelongsTo
    {
        return $this->belongsTo(MasterKode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function opdInduk(): BelongsTo
    {
        return $this->belongsTo(OpdInduk::class);
    }

    public function dusArsip(): BelongsTo
    {
        return $this->belongsTo(DusArsip::class);
    }

    public function rakArsip(): BelongsTo
    {
        return $this->belongsTo(RakArsip::class);
    }

    public function pemusnahanArsip(): HasMany
    {
        return $this->hasMany(PemusnahanArsip::class, 'id_arsip');
    }

    public function scopeLengkap($query)
    {
        return $query->with([
            'opd:id,opd_induk_id,unit_kerja,singkatan_uk,instansi',
            'opdInduk:id,kode_instansi,instansi',
            'masterKode:id,kode,nama',
            'creator:id,name,email',
            'dusArsip:id,nomor_dus',
            'rakArsip:id,nomor_rak',
            'periode:id,tahun,tahap,status',
        ]);
    }

    /**
     * Batasi query ke arsip yang "milik" user, sesuai role — dulu di sini
     * pakai deteksi strtolower($user->opd->unit_kerja) !== 'sekretariat',
     * persis anti-pattern yang diflag di audit V1 (3.3): hak akses
     * ditentukan dari STRING nama unit kerja, bukan role. Ganti nama unit
     * jadi "Sekretariat Daerah" atau typo satu huruf = hak akses berubah
     * diam-diam. Sekarang pakai role asli (isSuperadmin/isAdminDinas/
     * isPengolah), sama kayak scoping di Periode & MasterKode.
     */
    public function scopeMilikUser($query, ?User $user = null)
    {
        $user = $user ?: auth()->user();

        if (! $user || $user->isSuperadmin()) {
            return $query;
        }

        if ($user->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        if ($user->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function isActive(): bool
    {
        return is_null($this->nasib_akhir);
    }

    public function isExpired(): bool
    {
        if (! $this->tanggal_musnah) {
            return false;
        }

        return now()->greaterThan($this->tanggal_musnah);
    }
}
