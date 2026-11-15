<?php

namespace App\Policies;

use App\Models\Arsip;
use App\Models\User;

/**
 * Sama seperti PeriodePolicy: arsip itu milik Bidang (Pengolah) & Dinas
 * (Admin Dinas), bukan milik Superadmin -- jadi Superadmin sengaja TIDAK
 * dikasih tombol tambah arsip baru, meskipun tetap bisa lihat semua arsip
 * buat keperluan pengawasan.
 *
 * Tambahan: kalau periode arsip sudah dimanufer (dikunci, lihat
 * Periode::isManufer()), update/delete diblokir buat SIAPA PUN -- termasuk
 * Superadmin. Superadmin tidak dikasih jalan pintas buat ngedit langsung;
 * dia harus buka kuncinya dulu lewat action "Buka Kembali" di halaman
 * Periode (lihat PeriodeResource), baru arsipnya bisa diubah lagi.
 */
class ArsipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Arsip $arsip): bool
    {
        return $this->canAccess($user, $arsip);
    }

    public function create(User $user): bool
    {
        return $user->isAdminDinas() || $user->isPengolah();
    }

    public function update(User $user, Arsip $arsip): bool
    {
        return $this->canAccess($user, $arsip) && ! $this->isLocked($arsip);
    }

    public function delete(User $user, Arsip $arsip): bool
    {
        return $this->canAccess($user, $arsip) && ! $this->isLocked($arsip);
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Superadmin: bebas semua arsip.
     * Admin Dinas: arsip milik bidang-bidang di bawah dinasnya sendiri
     * (opd_induk_id sama).
     * Pengolah: cuma arsip milik bidangnya sendiri.
     */
    private function canAccess(User $user, Arsip $arsip): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $arsip->opd_induk_id === $user->opd_induk_id;
        }

        if ($user->isPengolah()) {
            return $arsip->opd_id !== null && $arsip->opd_id === $user->opd_id;
        }

        return false;
    }

    /**
     * Lock cuma berlaku buat update/delete -- bukan buat view(), soalnya
     * arsip yang udah dimanufer tetap harus kelihatan di daftar, cuma
     * nggak boleh diubah/dihapus.
     */
    private function isLocked(Arsip $arsip): bool
    {
        return (bool) $arsip->periode?->isManufer();
    }
}
