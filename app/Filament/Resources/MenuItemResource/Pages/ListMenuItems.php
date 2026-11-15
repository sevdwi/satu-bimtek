<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Halaman index MenuItemResource -- tabel, bukan form. Urutan (drag di
 * kolom paling kiri) & tiap toggle role tersimpan LANGSUNG per aksi
 * (lihat MenuItemResource::table()), tidak ada tombol "Simpan" terpisah
 * seperti versi lama (App\Filament\Pages\ManajemenMenu).
 *
 * Tombol "Tambah Menu (Link)" di bawah CUMA bikin menu LINK KUSTOM (lihat
 * Pages\CreateMenuItem & catatan class di App\Models\MenuItem) -- bukan
 * modul/fitur baru dengan tabel sendiri.
 */
class ListMenuItems extends ListRecords
{
    protected static string $resource = MenuItemResource::class;

    /**
     * Reorder lewat drag-handle tabel TIDAK otomatis flush cache
     * MenuItem (beda dari lewat save()/toggle role, yang sudah kepasang
     * lewat model event MenuItem::booted() / MenuPermission::booted()) --
     * reorder Filament update kolom sort_order lewat query builder
     * langsung, bukan lewat Eloquent save(), jadi event 'saved' TIDAK
     * ikut kepicu. Override di sini biar cache selalu ikut fresh abis
     * di-drag.
     */
    public function reorderTable(array $order, int | string | null $draggedRecordKey = null): void
    {
        parent::reorderTable($order, $draggedRecordKey);

        MenuItem::flushCache();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Menu (Link)')
                ->modalHeading('Tambah Menu Link Baru'),
        ];
    }
}
