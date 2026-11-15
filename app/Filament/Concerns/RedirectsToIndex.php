<?php

namespace App\Filament\Concerns;

/**
 * Dipakai di semua page Create/Edit Filament biar setelah create/edit
 * sukses, user selalu diarahkan balik ke halaman depan (index/list) resource
 * terkait — bukan ke halaman Edit record yang baru dibuat (default Filament
 * setelah create) atau tetap diam di halaman Edit (default Filament setelah
 * save).
 */
trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
