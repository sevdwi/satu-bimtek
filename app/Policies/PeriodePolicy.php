<?php

namespace App\Policies;

use App\Models\Periode;
use App\Models\User;

class PeriodePolicy
{
    /**
     * Semua role boleh LIHAT daftar periode — Admin Dinas & Pengolah buat
     * kelola punya mereka, dan siapa pun butuh tahu tahap mana yang lagi
     * buka buat dasar input arsip. RECORD mana yang kelihatan dibatasi di
     * PeriodeResource::getEloquentQuery(), bukan di sini.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Periode $periode): bool
    {
        return $this->canAccess($user, $periode);
    }

    /**
     * Periode itu milik Dinas (Admin Dinas/sekretariat) & Bidang (Pengolah) —
     * bukan milik Superadmin. Superadmin sengaja TIDAK dikasih tombol tambah
     * periode baru, karena dia tidak terikat ke satu dinas/bidang tertentu.
     */
    public function create(User $user): bool
    {
        return $user->isAdminDinas() || $user->isPengolah();
    }

    /**
     * Periode yang sudah dimanufer (dikunci) tidak boleh diedit lewat form
     * biasa — kalau tahun/tahap/bidangnya bisa diubah setelah arsip di
     * dalamnya sudah dikasih nomor urut definitif, urutan itu jadi nggak
     * konsisten lagi. Satu-satunya cara ubah status kunci ini adalah action
     * "Buka Kembali" (khusus Superadmin, lihat PeriodeResource), bukan lewat
     * EditAction standar.
     */
    public function update(User $user, Periode $periode): bool
    {
        return $this->canAccess($user, $periode) && ! $periode->isManufer();
    }

    /**
     * Periode yang sudah dimanufer juga tidak boleh dihapus — periode_id di
     * arsips itu nullOnDelete, jadi hapus periode yang sudah punya nomor
     * urut definitif bakal bikin arsip-arsip itu "nyasar" tanpa tahap yang
     * jelas asalnya.
     */
    public function delete(User $user, Periode $periode): bool
    {
        return $this->canAccess($user, $periode) && ! $periode->isManufer();
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Superadmin: bebas semua periode (tetap bisa lihat/ubah/hapus buat jaga-jaga,
     * cuma tidak bisa bikin baru — lihat create()).
     * Admin Dinas: periode milik bidang-bidang di bawah dinasnya sendiri
     * (opd_induk_id sama).
     * Pengolah: cuma periode milik bidangnya sendiri.
     */
    private function canAccess(User $user, Periode $periode): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $periode->opd?->opd_induk_id !== null
                && $periode->opd->opd_induk_id === $user->opd_induk_id;
        }

        if ($user->isPengolah()) {
            return $periode->opd_id !== null && $periode->opd_id === $user->opd_id;
        }

        return false;
    }
}
