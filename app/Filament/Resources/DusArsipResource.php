<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\DusArsipResource\Pages;
use App\Models\DusArsip;
use App\Models\Opd;
use App\Models\RakArsip;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Modul CRUD Dus Arsip — penyimpanan fisik level dus. Form-nya TIDAK punya
 * kolom Nomor Rak (permintaan user, Nov 2026): dus dibuat tanpa rak, lalu
 * otomatis ditautkan ke rak saat arsip ditempatkan ke rak + dus tersebut
 * lewat "Tempatkan ke Rak/Dus" di menu Arsip.
 *
 * opd_id/opd_induk_id dus ditentukan di server (resolveLokasi(), dipanggil
 * CreateDusArsip/EditDusArsip):
 * - Dus yang sudah punya rak -> SELALU ikut bidang/dinas rak-nya.
 * - Dus tanpa rak -> Pengolah: bidangnya sendiri; Admin Dinas/Superadmin:
 *   bidang yang dipilih di form (wajib).
 */
class DusArsipResource extends Resource
{
    use HasMenuPermission;

    /**
     * Key modul buat fitur "Manajemen Menu" (lihat
     * App\Filament\Concerns\HasMenuPermission & MenuPermissionSeeder) --
     * menggantikan visibilitas/urutan menu yang dulunya tidak diatur sama
     * sekali (semua role, urutan default Filament).
     */
    protected static string $menuKey = 'dus_arsip';

    protected static ?string $model = DusArsip::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Dus Arsip';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Dus Arsip';

    protected static ?string $pluralModelLabel = 'Dus Arsip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Dus')
                ->columns(2)
                ->components([
                    // Nomor Rak SENGAJA tidak ada lagi di form Dus (permintaan
                    // user, Nov 2026) -- dus dibuat tanpa rak; dus baru
                    // "masuk" ke rak saat arsip ditempatkan lewat "Tempatkan
                    // ke Rak/Dus" di menu Arsip (lihat
                    // InaktifResource::tautkanDusKeRak()).
                    //
                    // Bidang: Pengolah otomatis bidangnya sendiri (field
                    // disembunyikan); Admin Dinas/Superadmin wajib pilih.
                    // Dus lama yang SUDAH punya rak tetap ikut bidang rak-nya
                    // (field dikunci), lihat resolveLokasi().
                    Select::make('opd_id')
                        ->label('Bidang')
                        ->relationship(
                            name: 'opd',
                            titleAttribute: 'unit_kerja',
                            modifyQueryUsing: fn (Builder $query) => static::scopeBidangInputOptions($query),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Opd $record): string => $record->opdInduk
                            ? "{$record->unit_kerja} — {$record->opdInduk->instansi}"
                            : $record->unit_kerja)
                        ->searchable()
                        ->preload()
                        ->visible(fn (): bool => ! auth()->user()?->isPengolah())
                        ->required(fn (?DusArsip $record): bool => ! $record?->rak_arsip_id)
                        ->disabled(fn (?DusArsip $record): bool => (bool) $record?->rak_arsip_id)
                        ->helperText(fn (?DusArsip $record): ?string => $record?->rak_arsip_id
                            ? 'Dus ini sudah ada di Rak ' . ($record->rakArsip?->nomor_rak ?? '-') . ', bidang mengikuti rak tersebut.'
                            : null)
                        ->columnSpanFull(),
                    TextInput::make('nomor_dus')
                        ->label('Nomor Dus')
                        ->maxLength(255),
                    // Bukan input manual: kolom qrcode diisi OTOMATIS (lihat
                    // CreateDusArsip::afterCreate() / EditDusArsip::afterSave())
                    // dengan link halaman "Rangkuman Berkas" dus ini (route
                    // 'dus-arsip.rangkuman') -- cuma dipakai buat tombol
                    // "Rangkuman" di tabel & link "Unduh QR"/"Cetak" di halaman
                    // rangkuman itu sendiri, BUKAN isi yang di-encode ke
                    // gambar QR (lihat field kode_qrcode di bawah). Baru ada
                    // isinya setelah dus disimpan pertama kali (butuh ID
                    // record), makanya disembunyikan di form create.
                    TextInput::make('qrcode')
                        ->label('Link Rangkuman Berkas')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->visible(fn (?DusArsip $record): bool => $record !== null)
                        ->helperText('Terisi otomatis. Dipakai lewat tombol "Rangkuman" di daftar Dus Arsip.'),
                    // INI (bukan qrcode di atas) yang di-encode ke GAMBAR QR
                    // code (lihat DusArsipRangkumanController::qrcode()) --
                    // dibuat SEKALI lewat DusArsip::generateKodeQrcode() &
                    // TIDAK PERNAH berubah lagi setelahnya (sudah tercetak
                    // fisik di dus). Kode inilah yang diketikkan manual di
                    // halaman publik "Lacak Dus Arsip" (/lacak-dus, TANPA
                    // LOGIN) kalau usernya scan QR-nya tapi tidak bisa
                    // langsung buka link (mis. HP tidak satu jaringan dengan
                    // server, atau aplikasi masih di localhost).
                    TextInput::make('kode_qrcode')
                        ->label('Kode QR (buat diketik manual di Lacak Dus)')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->visible(fn (?DusArsip $record): bool => $record !== null)
                        ->helperText('Terisi otomatis, format "id-dinas—id-rak—tanggal". Ini kode yang MUNCUL kalau QR-nya discan, dan bisa diketik manual di halaman publik "Lacak Dus Arsip".'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_dus')
                    ->label('Nomor Dus')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('rakArsip.nomor_rak')
                    ->label('Nomor Rak')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('opd.opdInduk.instansi')
                    ->label('Dinas')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('arsips_count')
                    ->label('Jumlah Arsip')
                    ->counts('arsips')
                    ->sortable(),
                // Kode pendek yang MUNCUL kalau QR fisik dus ini discan
                // (lihat App\Models\DusArsip::generateKodeQrcode() &
                // DusArsipRangkumanController::qrcode()) -- searchable di
                // sini biar admin bisa cari dus lewat kode ini juga, bukan
                // cuma lewat halaman publik "Lacak Dus Arsip".
                TextColumn::make('kode_qrcode')
                    ->label('Kode QR')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode QR disalin.')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('opd_induk_id')
                    ->label('Dinas')
                    ->relationship('opdInduk', 'instansi'),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship('opd', 'unit_kerja', fn (Builder $query) => static::scopeOpdOptions($query)),
                SelectFilter::make('rak_arsip_id')
                    ->label('Nomor Rak')
                    ->relationship('rakArsip', 'nomor_rak', fn (Builder $query) => static::scopeRakOptions($query)),
            ])
            ->recordActions([
                // Buka halaman "Rangkuman Berkas" dus ini di tab baru --
                // halaman yang sama yang dituju kalau QR code di dus fisik
                // discan (lihat routes/web.php & DusArsipRangkumanController).
                Action::make('lihatRangkuman')
                    ->label('Rangkuman')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray')
                    ->url(fn (DusArsip $record): string => route('dus-arsip.rangkuman', $record))
                    ->openUrlInNewTab(),
                // Tombol "Generate QR" -- jawaban eksplisit buat data LAMA
                // yang kolom qrcode/kode_qrcode-nya masih kosong (dibuat
                // sebelum fitur ini ada, lewat seeder/tinker, dll). Sama
                // logikanya dengan CreateDusArsip::afterCreate() /
                // EditDusArsip::afterSave() & command artisan
                // dus-arsip:generate-kode-qrcode, cuma dipicu manual per
                // baris dari tabel -- kode_qrcode yang SUDAH terisi tidak
                // pernah ditimpa (sudah tercetak fisik di dus).
                Action::make('generateQrcode')
                    ->label('Generate QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('warning')
                    ->visible(fn (DusArsip $record): bool => blank($record->qrcode) || blank($record->kode_qrcode))
                    ->requiresConfirmation()
                    ->modalDescription('Buat kolom Link Rangkuman & Kode QR untuk Dus ini? Kode QR yang sudah ada tidak akan ditimpa.')
                    ->action(function (DusArsip $record): void {
                        $record->qrcode = route('dus-arsip.rangkuman', $record);

                        if (blank($record->kode_qrcode)) {
                            $record->kode_qrcode = DusArsip::generateKodeQrcode(
                                $record->opd_induk_id,
                                $record->rak_arsip_id,
                                $record->created_at,
                            );
                        }

                        $record->saveQuietly();

                        \Filament\Notifications\Notification::make()
                            ->title('QR Code berhasil dibuat')
                            ->success()
                            ->send();
                    }),
                // Buka gambar QR code dus ini (link-nya mengarah ke halaman
                // Rangkuman di atas) di tab baru -- bisa langsung diunduh/
                // dicetak dari sana buat ditempel di dus fisik.
                Action::make('lihatQrCode')
                    ->label('QR Code')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->url(fn (DusArsip $record): string => route('dus-arsip.qrcode', $record))
                    ->openUrlInNewTab(),
                // Buka halaman cetak LABEL DUS (format kartu instansi/nomor
                // berkas/nomor boks + QR code, lihat DusArsipLabelController
                // & resources/views/dus/label.blade.php) khusus dus ini.
                Action::make('cetakLabel')
                    ->label('Cetak Label')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (DusArsip $record): string => route('dus-arsip.label', ['ids' => $record->id]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Sama seperti aksi "Generate QR" per baris, cuma buat
                    // banyak Dus Arsip sekaligus (mis. setelah import data
                    // lama) -- kode_qrcode yang sudah terisi tetap TIDAK
                    // ditimpa.
                    BulkAction::make('generateQrcodeMassal')
                        ->label('Generate QR')
                        ->icon(Heroicon::OutlinedQrCode)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Buat kolom Link Rangkuman & Kode QR untuk semua Dus Arsip yang dicentang? Kode QR yang sudah ada tidak akan ditimpa.')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->qrcode = route('dus-arsip.rangkuman', $record);

                                if (blank($record->kode_qrcode)) {
                                    $record->kode_qrcode = DusArsip::generateKodeQrcode(
                                        $record->opd_induk_id,
                                        $record->rak_arsip_id,
                                        $record->created_at,
                                    );
                                }

                                $record->saveQuietly();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('QR Code berhasil dibuat untuk ' . $records->count() . ' Dus Arsip')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
                // Cetak label buat SEMUA dus yang dicentang sekaligus, satu
                // halaman berisi banyak kartu label (siap digunting/
                // ditempel) -- sama halaman dengan tombol "Cetak Label" per
                // baris di atas, cuma isinya lebih dari satu dus.
                BulkAction::make('cetakLabelMassal')
                    ->label('Cetak Label')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (Collection $records): string => route('dus-arsip.label', [
                        'ids' => $records->pluck('id')->implode(','),
                    ]))
                    ->openUrlInNewTab()
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Batasi daftar rekord Dus Arsip sesuai role — sama pola dengan
     * Rak Arsip & Arsip.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->isSuperadmin()) {
            return $query;
        }

        if ($user->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        if ($user->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Batasi opsi pilihan Rak (form & filter): Pengolah cuma rak
     * bidangnya sendiri, Admin Dinas cuma rak-rak di dinasnya, Superadmin
     * bebas — sama pola dengan InaktifResource::scopeRakOptions().
     */
    protected static function scopeRakOptions(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        if ($user?->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        return $query;
    }

    /**
     * Batasi opsi pilihan Bidang di filter: Admin Dinas & Pengolah cuma
     * bidang di dinasnya sendiri (Pengolah tetap lihat semua bidang SE-DINAS,
     * bukan cuma bidangnya sendiri, biar filter ini tetap berguna buat
     * dia). Superadmin bebas lihat semua dinas.
     */
    protected static function scopeOpdOptions(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isAdminDinas() || $user?->isPengolah()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        return $query;
    }

    /**
     * Opsi Bidang di form Dus: Admin Dinas cuma bidang di dinasnya,
     * Pengolah cuma bidangnya sendiri, Superadmin bebas.
     */
    protected static function scopeBidangInputOptions(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            return $query->whereKey($user->opd_id);
        }

        if ($user?->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        return $query;
    }

    /**
     * Tentukan bidang/dinas dus di server (dipanggil CreateDusArsip &
     * EditDusArsip) -- nilai dari form tidak dipercaya mentah-mentah:
     * - rak_arsip_id TIDAK PERNAH diambil dari form (kolomnya sudah tidak
     *   ada) -- dus baru selalu tanpa rak, dus lama tetap di rak-nya.
     * - Dus yang sudah punya rak -> opd_id & opd_induk_id ikut rak-nya.
     * - Dus tanpa rak -> Pengolah: bidangnya sendiri; role lain: bidang yang
     *   dipilih (wajib dalam jangkauan user); opd_induk_id ikut bidang itu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolveLokasi(array $data, ?DusArsip $record = null): array
    {
        $user = auth()->user();

        unset($data['rak_arsip_id']);

        if ($rak = $record?->rakArsip) {
            $data['opd_id'] = $rak->opd_id;
            $data['opd_induk_id'] = $rak->opd_induk_id;

            return $data;
        }

        $opd = $user?->isPengolah()
            ? $user->opd
            : (filled($data['opd_id'] ?? null) ? static::scopeBidangInputOptions(Opd::query())->find($data['opd_id']) : null);

        if (! $opd) {
            Notification::make()
                ->title('Bidang belum ditentukan')
                ->body($user?->isPengolah()
                    ? 'Akun Anda belum terhubung ke bidang mana pun. Hubungi administrator.'
                    : 'Pilih Bidang untuk dus ini.')
                ->danger()
                ->send();

            throw new Halt();
        }

        $data['opd_id'] = $opd->id;
        $data['opd_induk_id'] = $opd->opd_induk_id;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDusArsips::route('/'),
            'create' => Pages\CreateDusArsip::route('/create'),
            'edit' => Pages\EditDusArsip::route('/{record}/edit'),
        ];
    }
}
