<?php

namespace App\Policies;

use App\Models\User;

/**
 * Siapa boleh mengelola akun pengguna (menu "Pengguna" / UserResource).
 *
 * - Superadmin: kelola SEMUA akun (semua role, semua dinas) -- perilaku lama.
 * - Admin Dinas: cuma boleh kelola akun PENGOLAH di DINASNYA SENDIRI
 *   (opd_induk_id sama). Tidak bisa membuat/melihat/mengubah akun
 *   Superadmin, sesama Admin Dinas, maupun Pengolah dinas lain. Pembatasan
 *   record di list ada di UserResource::getEloquentQuery(), dan role/dinas
 *   yang tersimpan dipaksa di server lewat
 *   UserResource::enforceAdminDinasScope() (Create/EditUser) -- policy ini
 *   lapis penjaga terakhir per-record (URL edit langsung, aksi hapus, dll).
 * - Pengolah: tidak punya akses sama sekali.
 *
 * Catatan: karena Admin Dinas sekarang bisa membuka menu Pengguna lagi,
 * fitur impersonate Admin Dinas -> Pengolah di dinasnya (lihat
 * User::canBeImpersonated()) otomatis ikut berfungsi kembali.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    /**
     * Bulk delete -- aman dibuka buat Admin Dinas karena baris yang bisa
     * dicentang di tabel sudah dibatasi UserResource::getEloquentQuery()
     * (cuma Pengolah se-dinas).
     */
    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdminDinas();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }

    private function canManage(User $user, User $model): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminDinas()) {
            return $model->isPengolah()
                && $model->opd_induk_id !== null
                && $model->opd_induk_id === $user->opd_induk_id;
        }

        return false;
    }
}
