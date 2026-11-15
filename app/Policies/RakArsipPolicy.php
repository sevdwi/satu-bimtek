<?php

namespace App\Policies;

use App\Models\RakArsip;
use App\Models\User;

/**
 * Sama seperti PeriodePolicy/ArsipPolicy: rak arsip itu milik Bidang
 * (Pengolah) & Dinas (Admin Dinas) tempat rak fisiknya berada — bukan
 * milik Superadmin, jadi Superadmin sengaja TIDAK dikasih tombol tambah
 * rak baru, meskipun tetap bisa lihat/ubah/hapus semua rak buat keperluan
 * pengawasan/perbaikan data.
 */
class RakArsipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RakArsip $rakArsip): bool
    {
        return $this->canAccess($user, $rakArsip);
    }

    public function create(User $user): bool
    {
        return $user->isAdminDinas() || $user->isPengolah();
    }

    public function update(User $user, RakArsip $rakArsip): bool
    {
        return $this->canAccess($user, $rakArsip);
    }

    public function delete(User $user, RakArsip $rakArsip): bool
    {
        return $this->canAccess($user, $rakArsip);
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Superadmin: bebas semua rak (tetap bisa lihat/ubah/hapus buat jaga-jaga,
     * cuma tidak bisa bikin baru — lihat create()).
     * Admin Dinas: rak milik bidang-bidang di bawah dinasnya sendiri
     * (opd_induk_id sama).
     * Pengolah: cuma rak milik bidangnya sendiri.
     */
    private function canAccess(User $user, RakArsip $rakArsip): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $rakArsip->opd_induk_id !== null && $rakArsip->opd_induk_id === $user->opd_induk_id;
        }

        if ($user->isPengolah()) {
            return $rakArsip->opd_id !== null && $rakArsip->opd_id === $user->opd_id;
        }

        return false;
    }
}
