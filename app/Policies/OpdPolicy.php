<?php

namespace App\Policies;

use App\Models\Opd;
use App\Models\User;

/**
 * Siapa boleh mengelola data Bidang (opds / menu "Bidang").
 *
 * - Superadmin: kelola SEMUA bidang di semua dinas (perilaku lama).
 * - Admin Dinas: cuma boleh lihat, tambah, ubah, & hapus bidang di
 *   DINASNYA SENDIRI (opd_induk_id sama). Daftar record dibatasi di
 *   OpdResource::getEloquentQuery(), dan dinas yang tersimpan dipaksa di
 *   server lewat OpdResource::enforceDinasScope() (Create/EditOpd) --
 *   policy ini lapis penjaga terakhir per-record (URL edit langsung, hapus).
 * - Pengolah: tidak punya akses. Pengolah tetap bisa MELIHAT nama bidangnya
 *   lewat relasi di menu lain (mis. kolom "Bidang" di Arsip/Rak/Dus).
 *
 * Bidang yang masih punya pengguna/periode/rak/dus/arsip tetap tidak bisa
 * dihapus (siapa pun) -- dijaga di UI OpdResource/EditOpd (tombol Delete
 * dikunci + tooltip alasan) & bulk delete (dicek per baris), lihat
 * Opd::punyaDataTerkait(). Sengaja TIDAK ditaruh di delete() di sini supaya
 * tombolnya tetap tampil dengan tooltip penjelasan, bukan hilang begitu saja.
 */
class OpdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function view(User $user, Opd $opd): bool
    {
        return $this->canManage($user, $opd);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function update(User $user, Opd $opd): bool
    {
        return $this->canManage($user, $opd);
    }

    public function delete(User $user, Opd $opd): bool
    {
        return $this->canManage($user, $opd);
    }

    /**
     * Bulk delete -- tiap baris tetap dicek ulang di OpdResource
     * (authorizeIndividualRecords: delete() di atas + tidak punya data
     * terkait), jadi bidang dinas lain / yang masih dipakai otomatis dilewati.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function restore(User $user, Opd $opd): bool
    {
        return $user->isSuperadmin();
    }

    public function forceDelete(User $user, Opd $opd): bool
    {
        return $user->isSuperadmin();
    }

    private function canManage(User $user, Opd $opd): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $opd->opd_induk_id !== null && $opd->opd_induk_id === $user->opd_induk_id;
        }

        return false;
    }
}
