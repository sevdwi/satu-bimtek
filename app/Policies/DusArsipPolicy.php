<?php

namespace App\Policies;

use App\Models\DusArsip;
use App\Models\User;

/**
 * Sama seperti RakArsipPolicy: dus arsip itu milik Bidang (Pengolah) &
 * Dinas (Admin Dinas) tempat dus fisiknya berada — bukan milik
 * Superadmin. Nomor Rak di Dus Arsip OPSIONAL (lihat
 * DusArsipResource::form() & resolveLokasi()) — policy ini cuma mengatur
 * PERAN mana yang boleh apa, berdasarkan opd_id/opd_induk_id dus.
 */
class DusArsipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DusArsip $dusArsip): bool
    {
        return $this->canAccess($user, $dusArsip);
    }

    public function create(User $user): bool
    {
        return $user->isAdminDinas() || $user->isPengolah();
    }

    public function update(User $user, DusArsip $dusArsip): bool
    {
        return $this->canAccess($user, $dusArsip);
    }

    public function delete(User $user, DusArsip $dusArsip): bool
    {
        return $this->canAccess($user, $dusArsip);
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Superadmin: bebas semua dus.
     * Admin Dinas: dus milik bidang-bidang di bawah dinasnya sendiri
     * (opd_induk_id sama).
     * Pengolah: cuma dus milik bidangnya sendiri.
     */
    private function canAccess(User $user, DusArsip $dusArsip): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $dusArsip->opd_induk_id !== null && $dusArsip->opd_induk_id === $user->opd_induk_id;
        }

        if ($user->isPengolah()) {
            return $dusArsip->opd_id !== null && $dusArsip->opd_id === $user->opd_id;
        }

        return false;
    }
}
