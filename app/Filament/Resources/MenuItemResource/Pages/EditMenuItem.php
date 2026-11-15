<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Halaman Edit CUMA buat menu link kustom (is_custom = true) -- baris
 * modul kode asli tidak pernah punya tombol "Edit" yang mengarah ke sini
 * (lihat MenuItemResource::table()), tapi mount() di bawah tetap jaga2
 * kalau URL-nya diketik manual langsung.
 */
class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        abort_unless($this->record instanceof MenuItem && $this->record->is_custom, 404);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('Menu link ini akan dihapus permanen dari sidebar SEMUA role yang sebelumnya bisa melihatnya. Tindakan ini tidak bisa dibatalkan.'),
        ];
    }

    protected function afterSave(): void
    {
        MenuItem::flushCache();
    }
}
