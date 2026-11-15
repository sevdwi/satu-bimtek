<?php

namespace App\Policies;

use App\Models\MasterKode;
use App\Models\User;

/**
 * Menu Kode Klasifikasi ini READ ONLY dan cuma buat Superadmin — sesuai
 * permintaan langsung: "hanya read only dan filter saja". Semua
 * kemampuan tulis (create/update/delete) sengaja diblok total, bukan
 * cuma disembunyikan dari UI.
 */
class MasterKodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, MasterKode $masterKode): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MasterKode $masterKode): bool
    {
        return false;
    }

    public function delete(User $user, MasterKode $masterKode): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MasterKode $masterKode): bool
    {
        return false;
    }

    public function forceDelete(User $user, MasterKode $masterKode): bool
    {
        return false;
    }
}
