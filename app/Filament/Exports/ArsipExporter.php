<?php

namespace App\Filament\Exports;

use App\Enums\ArsipKeterangan;
use App\Enums\ArsipStatus;
use App\Enums\NasibAkhir;
use App\Models\Arsip;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * Exporter Excel untuk menu Arsip (InaktifResource), juga dipakai di
 * Daftar Usul Musnah & Daftar Usul Permanen (UsulMusnahResource,
 * UsulPermanenResource). Kolomnya mengikuti
 * kolom fisik tabel arsips, ditambah beberapa label relasi (kode
 * klasifikasi, dinas, bidang, periode, rak/dus, pembuat) biar hasil
 * Excel-nya langsung terbaca tanpa perlu buka data lain.
 *
 * Query yang diekspor otomatis sudah terbatas per role, karena
 * ExportAction/ExportBulkAction dipasang di tabel InaktifResource yang
 * getEloquentQuery()-nya sudah discoping per dinas/bidang (Superadmin
 * semua, Admin Dinas se-dinas, Pengolah se-bidang) -- lihat
 * InaktifResource::getEloquentQuery().
 */
class ArsipExporter extends Exporter
{
    protected static ?string $model = Arsip::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nomor')
                ->label('Nomor Definitif'),
            ExportColumn::make('masterKode.kode')
                ->label('Kode Klasifikasi'),
            ExportColumn::make('masterKode.nama')
                ->label('Nama Klasifikasi'),
            ExportColumn::make('judul')
                ->label('Redaksi / Perihal'),
            ExportColumn::make('deskripsi')
                ->label('Deskripsi'),
            ExportColumn::make('tanggal')
                ->label('Tanggal Arsip')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->translatedFormat('d M Y')),
            ExportColumn::make('tahun')
                ->label('Tahun'),
            ExportColumn::make('jumlah_isi_berkas')
                ->label('Jumlah Berkas'),
            ExportColumn::make('nomor_sementara')
                ->label('Nomor sementara'),
            ExportColumn::make('opdInduk.instansi')
                ->label('Dinas'),
            ExportColumn::make('opd.unit_kerja')
                ->label('Bidang'),
            ExportColumn::make('rakArsip.nomor_rak')
                ->label('Nomor Rak'),
            ExportColumn::make('dusArsip.nomor_dus')
                ->label('Nomor Dus'),
            ExportColumn::make('aktif')
                ->label('Retensi Aktif (Thn)'),
            ExportColumn::make('inaktif')
                ->label('Retensi Inaktif (Thn)'),
            ExportColumn::make('keterangan')
                ->label('Keterangan')
                ->formatStateUsing(fn (?ArsipKeterangan $state): ?string => $state?->getLabel()),
            ExportColumn::make('tanggal_musnah')
                ->label('Tanggal Musnah / Retensi Berakhir')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->translatedFormat('d M Y')),
            ExportColumn::make('nasib_akhir')
                ->label('Nasib Akhir')
                ->formatStateUsing(fn (?NasibAkhir $state): ?string => $state?->getLabel()),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (?ArsipStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('korektor')
                ->label('Korektor'),
            ExportColumn::make('periode.tahun')
                ->label('Tahun Periode'),
            ExportColumn::make('periode.tahap')
                ->label('Tahap Periode'),
            ExportColumn::make('creator.name')
                ->label('Dibuat Oleh'),
            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->translatedFormat('d M Y H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'File Excel arsip kamu sudah selesai dibuat, ' . Number::format($export->successful_rows) . ' baris berhasil diekspor.';

        $failedRowsCount = $export->getFailedRowsCount();

        if ($failedRowsCount > 0) {
            $body .= ' ' . Number::format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
