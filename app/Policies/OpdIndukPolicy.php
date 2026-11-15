<?php

namespace App\Policies;

use App\Models\User;

/**
 * Cuma Superadmin yang boleh buka menu "Dinas" (opd_induks) di panel
 * Filament — Admin Dinas & Pengolah nggak dapat menu ini sama sekali
 * (disembunyikan dari navigasi) DAN kalau nekat buka URL-nya langsung
 * kena 403. Sama pola persis dengan UserPolicy. Filament otomatis pakai
 * policy ini untuk OpdIndukResource karena konvensi nama
 * App\Models\OpdInduk -> App\Policies\OpdIndukPolicy.
 */
class OpdIndukPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function restore(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function forceDelete(User $user): bool
    {
        return $user->isSuperadmin();
    }
}
