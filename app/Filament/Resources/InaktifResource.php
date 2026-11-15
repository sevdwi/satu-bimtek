<?php

namespace App\Filament\Resources;

use App\Enums\ArsipKeterangan;
use App\Enums\ArsipStatus;
use App\Enums\NasibAkhir;
use App\Enums\PeriodeStatus;
use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Exports\ArsipExporter;
use App\Filament\Resources\InaktifResource\Pages;
use App\Models\Arsip;
use App\Models\RakArsip;
use App\Models\DusArsip;
use App\Models\MasterKode;
use App\Models\Opd;
use App\Models\Periode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Modul CRUD arsip inaktif (arsip yang sudah dipindah ke penyimpanan fisik
 * rak/dus, sesuai tampilan lama "Data Arsip" — lihat screenshot referensi
 * yang dikasih user). Nama kelas/file "Inaktif" sengaja beda dari label
 * menu "Arsip" yang tampil ke user (permintaan langsung: "buat menu dengan
 * nama Inaktif yang memiliki Label menu Arsip").
 */
class InaktifResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'arsip';

    protected static ?string $model = Arsip::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Arsip';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Arsip';

    protected static ?string $pluralModelLabel = 'Arsip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Utama')
                ->columns(2)
                ->components([
                    TextInput::make('nomor')
                        ->label('Nomor Definitif')
                        ->maxLength(255),
                    DatePicker::make('tanggal')
                        ->label('Tanggal Arsip')
                        ->native(false)
                        ->live()
                        // tanggal_musnah dihitung dari tanggal arsip + retensi, jadi
                        // harus ikut dihitung ulang kalau tanggalnya berubah setelah
                        // kode klasifikasi sudah dipilih.
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateTanggalMusnah($set, $get)),
                    TextInput::make('judul')
                        ->label('Redaksi / Perihal')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->columnSpanFull(),
                    TextInput::make('jumlah_isi_berkas')
                        ->label('Jumlah Berkas')
                        ->numeric()
                        ->integer()
                        ->minValue(0),
                    FileUpload::make('file')
                        ->label('Berkas / Hasil Scan Arsip')
                        ->disk('public')
                        ->directory('arsip')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                ]),

            Section::make('Klasifikasi & Retensi')
                ->columns(2)
                ->components([
                    Select::make('master_kode_id')
                        ->label('Kode Klasifikasi')
                        ->relationship(name: 'masterKode', titleAttribute: 'kode')
                        ->getOptionLabelFromRecordUsing(fn (MasterKode $record): string => "{$record->kode} — {$record->nama}")
                        ->searchable(['kode', 'nama'])
                        ->preload()
                        ->live()
                        // otomatis isi retensi aktif/inaktif + nasib akhir dari kode yang
                        // dipilih (nasib akhir ikut master_kodes.keterangan), lalu turunan
                        // tanggal_musnah dihitung ulang dari retensi barunya — tetap bisa
                        // diubah manual sesudahnya kalau ada pengecualian.
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            $masterKode = $state ? MasterKode::find($state) : null;

                            $set('aktif', $masterKode?->aktif);
                            $set('inaktif', $masterKode?->inaktif);
                            $set('nasib_akhir', $masterKode?->keterangan?->value);

                            static::recalculateTanggalMusnah($set, $get);
                        })
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('aktif')
                        ->label('Retensi Aktif (Thn)')
                        ->numeric()
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateTanggalMusnah($set, $get)),
                    TextInput::make('inaktif')
                        ->label('Retensi Inaktif (Thn)')
                        ->numeric()
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateTanggalMusnah($set, $get)),
                    Select::make('status')
                        ->label('Status')
                        // Superadmin cuma boleh pilih "Terverifikasi" (permintaan
                        // langsung: "pada role superadmin pilihan kolom status hanya
                        // 'terverifikasi' saja"). Status arsip yang SUDAH ada
                        // (misal masih "Draft"/"Input" bikinan Admin Dinas/Pengolah)
                        // tetap disertakan di opsi biar labelnya nggak "hilang" pas
                        // form edit dibuka -- Superadmin tetap bisa lihat nilainya,
                        // cuma pilihan barunya cuma "Terverifikasi". Role lain tetap
                        // lihat semua opsi (meski field-nya dikunci/disabled).
                        ->options(function (?Arsip $record): array {
                            if (! auth()->user()?->isSuperadmin()) {
                                return collect(ArsipStatus::cases())
                                    ->mapWithKeys(fn (ArsipStatus $case): array => [$case->value => $case->getLabel()])
                                    ->all();
                            }

                            $options = [ArsipStatus::Verify->value => ArsipStatus::Verify->getLabel()];

                            if ($record?->status) {
                                $options[$record->status->value] = $record->status->getLabel();
                            }

                            return $options;
                        })
                        ->default(fn (): string => auth()->user()?->isSuperadmin()
                            ? ArsipStatus::Verify->value
                            : ArsipStatus::Draft->value)
                        // Field ini sengaja tidak ->required(), tapi jangan sampai
                        // placeholder kosong "Select an option" ikut bisa dipilih --
                        // selalu harus ada nilai terisi, makanya placeholder-nya
                        // dimatikan di sini.
                        ->selectablePlaceholder(false)
                        ->helperText(fn (): string => auth()->user()?->isSuperadmin()
                            ? 'Superadmin cuma bisa mengubah status jadi "Terverifikasi".'
                            : 'Otomatis "Draft" -- bukan pilihan manual. Nanti otomatis jadi "Input" begitu tahapnya dimanufer.')
                        // Unit Pengolah & Admin Dinas selalu mulai dari "Draft" --
                        // tidak boleh pilih sendiri (permintaan langsung: "untuk unit
                        // pengolah langsung terinput draft saja" lalu diperluas ke
                        // "buat juga untuk akun admin dinas"). Cuma Superadmin yang
                        // tetap bisa pilih manual, dan pilihannya cuma "Terverifikasi"
                        // (lihat ->options() di atas). ->dehydrated() wajib biar
                        // field yang disabled tetap ikut disimpan ke database.
                        ->disabled(fn (): bool => ! (auth()->user()?->isSuperadmin()))
                        ->dehydrated(),
                    Select::make('nasib_akhir')
                        ->label('Nasib Akhir')
                        ->options(NasibAkhir::class)
                        ->helperText('Terisi otomatis dari kode klasifikasi yang dipilih — bukan pilihan manual.')
                        // dikunci: nilainya murni turunan dari master_kodes.keterangan
                        // (lihat afterStateUpdated di master_kode_id), tidak boleh
                        // dipilih sendiri lewat dropdown. ->dehydrated() wajib biar
                        // field yang disabled tetap ikut disimpan ke database —
                        // defaultnya Filament TIDAK menyimpan field yang disabled.
                        ->disabled()
                        ->dehydrated()
                        ->live(),
                    DatePicker::make('tanggal_musnah')
                        ->label('Tanggal Musnah / Retensi Berakhir')
                        ->native(false)
                        ->helperText('Terisi otomatis: tanggal arsip + retensi aktif + retensi inaktif.')
                        ->visible(fn (Get $get): bool => filled($get('nasib_akhir'))),
                    TextInput::make('korektor')
                        ->label('Korektor')
                        ->maxLength(255)
                        ->helperText('Nama yang memeriksa/mengoreksi data arsip ini (opsional).'),
                    Select::make('keterangan')
                        ->label('Keterangan')
                        ->options(ArsipKeterangan::class)
                        ->helperText('Jenis berkas fisik -- ditampilkan/dilingkari di Kartu Deskripsi Arsip.'),
                ]),

            Section::make('Instansi & Periode')
                ->columns(2)
                ->components([
                    // Info user & dinas -- cuma tampilan (tidak disimpan). Saat
                    // tambah diambil dari user yang login, saat edit dari data
                    // pembuat arsip / dinas milik arsip ini.
                    TextInput::make('penginput_info')
                        ->label('Diinput Oleh')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Arsip $record): ?string => $record?->creator?->name
                            ?? auth()->user()?->name),
                    TextInput::make('dinas_info')
                        ->label('Dinas')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Arsip $record): ?string => $record?->opdInduk?->instansi
                            ?? $record?->opd?->opdInduk?->instansi
                            ?? auth()->user()?->opdInduk?->instansi),
                    // Pengolah tidak melihat Select Bidang (disembunyikan), jadi
                    // bidangnya ditampilkan di sini sebagai info saja.
                    TextInput::make('bidang_info')
                        ->label('Bidang')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (): bool => (bool) auth()->user()?->isPengolah())
                        ->formatStateUsing(fn (?Arsip $record): ?string => $record?->opd?->unit_kerja
                            ?? auth()->user()?->opd?->unit_kerja),
                    // Pengolah cuma boleh punya arsip buat bidangnya sendiri — field ini
                    // disembunyikan buat dia, nilainya dipaksa dari data user login di
                    // CreateInaktif/EditInaktif (pola sama kayak PeriodeResource).
                    Select::make('opd_id')
                        ->label('Bidang')
                        ->relationship(
                            name: 'opd',
                            titleAttribute: 'unit_kerja',
                            modifyQueryUsing: fn (Builder $query) => static::scopeOpdOptions($query),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Opd $record): string => "{$record->unit_kerja} — {$record->opdInduk?->instansi}")
                        ->searchable(['unit_kerja'])
                        ->preload()
                        // Default ke bidang user yang login.
                        ->default(fn (): ?int => auth()->user()?->opd_id)
                        ->live()
                        // Ganti bidang -> periode otomatis pindah ke periode terbuka
                        // terkecil milik bidang baru (atau kosong kalau tidak ada).
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                            'periode_id',
                            static::periodeTerbukaTerkecil($state ? (int) $state : null),
                        ))
                        ->visible(fn (): bool => ! (auth()->user()?->isPengolah()))
                        ->required(fn (): bool => ! (auth()->user()?->isPengolah())),
                    Select::make('periode_id')
                        ->label('Periode')
                        ->relationship(
                            name: 'periode',
                            titleAttribute: 'tahun',
                            modifyQueryUsing: function (Builder $query, Get $get): Builder {
                                $opdId = auth()->user()?->isPengolah()
                                    ? auth()->user()->opd_id
                                    : $get('opd_id');

                                return $query
                                    ->when($opdId, fn (Builder $q) => $q->where('opd_id', $opdId))
                                    // Periode yang sudah dimanufer (dikunci) nggak boleh
                                    // kejatah arsip baru lagi -- lihat Periode::isManufer().
                                    ->whereNull('manufer_at');
                            },
                        )
                        ->getOptionLabelFromRecordUsing(fn (Periode $record): string => $record->label())
                        // Default: periode TERBUKA (status Buka & belum dimanufer)
                        // milik bidang user login; kalau ada lebih dari satu, pilih
                        // yang terkecil (tahun paling lama, lalu tahap terkecil).
                        ->default(fn (): ?int => static::periodeTerbukaTerkecil(auth()->user()?->opd_id))
                        // Selalu dikunci (tambah & edit) -- tidak bisa dipilih manual.
                        // Nilainya tetap ikut terkirim, tapi ditentukan ulang di server
                        // (CreateInaktif / EditInaktif::mutateFormData...).
                        ->disabled()
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Terisi otomatis dengan periode terbuka terkecil milik bidang ini.'
                            : 'Periode arsip ini dikunci. Kalau bidang diganti, periode ikut pindah ke periode terbuka terkecil bidang baru.'),
                ]),
        ]);
    }

    /**
     * ID periode terbuka terkecil milik satu bidang: status "Buka", belum
     * dimanufer, diurutkan tahun lalu tahap dari yang terkecil. Null kalau
     * bidang itu tidak punya periode terbuka sama sekali.
     * Contoh: Tahap 2 & Tahap 3 tahun 2026 sama-sama Buka -> dipilih Tahap 2.
     */
    public static function periodeTerbukaTerkecil(?int $opdId): ?int
    {
        if (! $opdId) {
            return null;
        }

        return Periode::query()
            ->where('opd_id', $opdId)
            ->where('status', PeriodeStatus::Buka->value)
            ->whereNull('manufer_at')
            ->orderBy('tahun')
            ->orderBy('tahap')
            ->value('id');
    }

    /**
     * Nilai default filter Periode di list arsip:
     * - ID semua periode yang belum terkunci (belum dimanufer) milik bidang
     *   user yang login.
     * - Kalau SEMUA periode bidang itu sudah terkunci, otomatis pilih SATU
     *   periode terkunci yang paling akhir (tahun terbesar, lalu tahap
     *   terbesar) -- supaya list tidak kosong/menampilkan semua arsip,
     *   tapi langsung ke tahap terakhir yang sudah dimanufer.
     * Superadmin (tidak terikat bidang) -> kosong, jadi semua arsip tampil
     * seperti biasa.
     *
     * @return array<int, int>
     */
    public static function periodeTidakTerkunciIds(): array
    {
        $opdId = auth()->user()?->opd_id;

        if (! $opdId || auth()->user()?->isSuperadmin()) {
            return [];
        }

        $terbuka = Periode::query()
            ->where('opd_id', $opdId)
            ->whereNull('manufer_at')
            ->orderBy('tahun')
            ->orderBy('tahap')
            ->pluck('id')
            ->all();

        if ($terbuka !== []) {
            return $terbuka;
        }

        $terakhirTerkunci = Periode::query()
            ->where('opd_id', $opdId)
            ->whereNotNull('manufer_at')
            ->orderByDesc('tahun')
            ->orderByDesc('tahap')
            ->orderByDesc('manufer_at')
            ->value('id');

        return $terakhirTerkunci ? [$terakhirTerkunci] : [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                // SENGAJA tanpa ->sortable() (tombol panah urut dikunci) --
                // urutan tabel arsip selalu otomatis ascending berdasarkan
                // Nomor Definitif, lihat ->defaultSort() di bawah.
                TextColumn::make('nomor')
                    ->label('Nomor Definitif')
                    ->searchable()
                    ->toggleable(),
                // Nomor sementara: terisi otomatis "{alias_user}-{urut per periode}"
                // begitu arsip diinput (lihat App\Models\Arsip::isiNomorSementara())
                // -- bukan input manual, cuma referensi sementara sebelum ada
                // Nomor Definitif hasil Manufer. Diurutkan lewat periode +
                // urut_sementara (angka), bukan teksnya, biar "UMI-10" tidak
                // muncul sebelum "UMI-9".
                TextColumn::make('nomor_sementara')
                    ->label('Nomor Sementara')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('periode_id', $direction)
                        ->orderBy('urut_sementara', $direction))
                    ->toggleable(),
                TextColumn::make('masterKode.kode')
                    ->label('Kode')
                    ->formatStateUsing(fn (Arsip $record): ?string => $record->masterKode
                        ? "{$record->masterKode->kode} — {$record->masterKode->nama}"
                        : null)
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'masterKode',
                        fn (Builder $q) => $q->where('kode', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%"),
                    ))
                    ->toggleable(),
                TextColumn::make('judul')
                    ->label('Redaksi')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->limit(150)
                    ->toggleable(),
                TextColumn::make('jumlah_isi_berkas')
                    ->label('Jumlah Isi')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
                // Link ke berkas hasil scan/upload arsip (kolom `file`, disimpan
                // FileUpload di form -- disk 'public', direktori 'arsip'). Kalau
                // arsipnya belum diupload berkas, tampil "-" (placeholder) dan
                // tidak ada link.
                TextColumn::make('file')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? 'Lihat Berkas' : null)
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->color('primary')
                    ->url(fn (Arsip $record): ?string => $record->file
                        ? Storage::disk('public')->url($record->file)
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->toggleable(),
                TextColumn::make('periode.tahap')
                    ->label('Tahap')
                    ->formatStateUsing(fn (Arsip $record): ?string => $record->periode
                        ? $record->periode->label() . ($record->periode->isManufer() ? ' (Terkunci)' : '')
                        : null)
                    ->color(fn (Arsip $record): ?string => $record->periode?->isManufer() ? 'danger' : null)
                    ->toggleable(),
                TextColumn::make('rakArsip.nomor_rak')
                    ->label('Nomor Rak')
                    // Arsip boleh masuk dus dulu tanpa rak -- dibedakan
                    // "Belum ada rak" (sudah di dus) vs "Belum ditempatkan".
                    ->formatStateUsing(fn (?string $state, Arsip $record): string => $state
                        ?? ($record->periode?->isManufer()
                            ? ($record->dus_arsip_id ? 'Belum ada rak' : 'Belum ditempatkan')
                            : '-'))
                    ->color(fn (?string $state, Arsip $record): ?string => (! $state && $record->periode?->isManufer())
                        ? 'danger'
                        : null)
                    ->toggleable(),
                TextColumn::make('dusArsip.nomor_dus')
                    ->label('Nomor Dus')
                    ->formatStateUsing(fn (?string $state, Arsip $record): string => $state
                        ?? ($record->periode?->isManufer() ? 'Belum ditempatkan' : '-'))
                    ->color(fn (?string $state, Arsip $record): ?string => (! $state && $record->periode?->isManufer())
                        ? 'danger'
                        : null)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('nasib_akhir')
                    ->label('Nasib Akhir')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Export Excel seluruh data arsip yang sedang tampil di tabel
                // (sudah otomatis kescoping per role lewat getEloquentQuery()
                // & ikut filter/pencarian yang aktif) -- lihat ArsipExporter.
                ExportAction::make()
                    ->label('Export Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exporter(ArsipExporter::class)
                    ->formats([ExportFormat::Xlsx]),
            ])
            ->filters([
                // Filter Periode -- saat list pertama dibuka, otomatis memilih
                // periode yang BELUM TERKUNCI (belum dimanufer) milik bidang
                // user yang login, jadi yang langsung tampil adalah arsip yang
                // sedang dikerjakan. Bisa dikosongkan/diganti manual.
                SelectFilter::make('periode_id')
                    ->label('Periode')
                    ->multiple()
                    ->options(fn (): array => Periode::query()
                        ->milikUser()
                        ->with('opd')
                        ->orderByDesc('tahun')
                        ->orderByDesc('tahap')
                        ->get()
                        ->mapWithKeys(fn (Periode $periode): array => [
                            $periode->id => $periode->label()
                                . ' — ' . ($periode->opd?->unit_kerja ?? '-')
                                . ($periode->isManufer() ? ' (Terkunci)' : ''),
                        ])
                        ->all())
                    ->default(fn (): array => static::periodeTidakTerkunciIds())
                    ->searchable()
                    ->preload(),
                // Replika filter "Pilih Tahap Arsip" di tampilan lama (Full Daftar /
                // Tahap 1-4) — filter lewat periode.tahap, bukan kolom arsips.tahap
                // (kolom itu sengaja dibuang, lihat audit 4.1/9.1: dulu ini sumber bug
                // karena ada 2 representasi tahap yang bisa nggak sinkron).
                // Filter::make('tahap')
                //     ->schema([
                //         Select::make('tahap')
                //             ->label('Tahap')
                //             ->options([
                //                 1 => 'Tahap 1',
                //                 2 => 'Tahap 2',
                //                 3 => 'Tahap 3',
                //                 4 => 'Tahap 4',
                //                 5 => 'Tahap 5',
                //                 6 => 'Tahap 6',
                //                 7 => 'Tahap 7',
                //                 8 => 'Tahap 8',
                //                 9 => 'Tahap 9',
                //                 10 => 'Tahap 10',
                //             ]),
                //     ])
                //     ->query(fn (Builder $query, array $data): Builder => $query->when(
                //         $data['tahap'] ?? null,
                //         fn (Builder $q, $tahap) => $q->whereHas('periode', fn (Builder $p) => $p->where('tahap', $tahap)),
                //     )),
                SelectFilter::make('status')->options(ArsipStatus::class),
                SelectFilter::make('nasib_akhir')->label('Nasib Akhir')->options(NasibAkhir::class),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship('opd', 'unit_kerja', fn (Builder $query) => static::scopeOpdOptions($query)),
                // Ditambahkan supaya widget dashboard "Arsip per Instansi"
                // (Superadmin) bisa deep-link ke sini dengan instansi
                // ter-filter (?tableFilters[opd_induk_id][value]=<id>) --
                // lihat App\Filament\Widgets\InstansiOverviewTable. Tidak
                // banyak berguna buat Admin Dinas/Pengolah (opsinya cuma
                // instansi mereka sendiri, sudah otomatis ter-scope lewat
                // getEloquentQuery()), tapi dibiarkan tampil biar konsisten
                // dengan filter 'opd_id' di atas yang juga tidak disembunyikan
                // per role.
                SelectFilter::make('opd_induk_id')
                    ->label('Instansi')
                    ->relationship('opdInduk', 'instansi'),
                // Arsip (tahap sudah dimanufer) yang Nomor Rak-nya masih
                // kosong -- termasuk yang sudah masuk dus tapi rak-nya belum
                // diisi. Dipakai buat mencari arsip yang perlu ditambahkan
                // rak-nya lewat "Ubah Rak/Dus".
                Filter::make('belumDitempatkan')
                    ->label('Belum Ada Nomor Rak')
                    ->schema([
                        Toggle::make('belum_ditempatkan')
                        ->label('Belum Ada Nomor Rak'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['belum_ditempatkan'] ?? false,
                        fn (Builder $q) => $q->whereNull('rak_arsip_id')
                            ->whereHas('periode', fn (Builder $p) => $p->whereNotNull('manufer_at')),
                    )),
            ])
            ->recordActions([
                // Buka halaman cetak "Kartu Deskripsi Arsip" di tab baru
                // (lihat routes/web.php & ArsipKartuController) -- halaman
                // itu di luar shell panel Filament, jadi dibuka terpisah
                // biar user nggak kehilangan posisi di tabel Arsip ini.
                Action::make('cetakKartu')
                    ->label('Cetak Kartu')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (Arsip $record): string => route('arsip.kartu', $record))
                    ->openUrlInNewTab(),
                // Rak/Dus SENGAJA bukan lewat EditAction (yang otomatis
                // kekunci ArsipPolicy::update() begitu periode-nya
                // dimanufer) -- permintaan client: rak/dus baru diisi
                // SETELAH manuver (nomor definitif harus pasti dulu sebelum
                // arsip fisik disusun ke dus). Action custom ini TIDAK
                // terikat policy update(), jadi tetap bisa dipakai walau
                // field lain di record itu sudah terkunci -- tapi cuma
                // nyentuh rak_arsip_id/dus_arsip_id, field lain tidak
                // disentuh sama sekali. Cuma Admin Dinas & Pengolah yang
                // boleh (sama seperti tombol Manuver) -- Superadmin
                // sengaja tidak dikasih, perannya cuma pengawasan/buka
                // kunci periode.
                Action::make('tempatkanFisik')
                    // Label menyesuaikan: belum ditempatkan -> "Tempatkan",
                    // sudah punya rak/dus -> "Ubah" (dipakai juga buat
                    // menambahkan Nomor Rak belakangan ke arsip yang baru
                    // dimasukkan ke dus).
                    ->label(fn (Arsip $record): string => ($record->rak_arsip_id || $record->dus_arsip_id)
                        ? 'Ubah Rak/Dus'
                        : 'Tempatkan ke Rak/Dus')
                    ->modalHeading(fn (Arsip $record): string => ($record->rak_arsip_id || $record->dus_arsip_id)
                        ? 'Ubah Rak/Dus Arsip'
                        : 'Tempatkan Arsip ke Rak/Dus')
                    ->modalDescription('Nomor Rak boleh dikosongkan dulu dan ditambahkan belakangan lewat tombol "Ubah Rak/Dus".')
                    ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->color('gray')
                    ->visible(fn (Arsip $record): bool => (bool) $record->periode?->isManufer()
                        && (auth()->user()?->isAdminDinas() || auth()->user()?->isPengolah()))
                    ->fillForm(fn (Arsip $record): array => [
                        'rak_arsip_id' => $record->rak_arsip_id,
                        'dus_arsip_id' => $record->dus_arsip_id,
                    ])
                    ->schema([
                        Select::make('rak_arsip_id')
                            ->label('Nomor Rak')
                            ->relationship(
                                name: 'rakArsip',
                                titleAttribute: 'nomor_rak',
                                modifyQueryUsing: fn (Builder $query) => static::scopeRakOptions($query),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Opsional -- boleh dikosongkan & ditambahkan belakangan.')
                            // Dus yang sudah dipilih tetap dipertahankan saat rak
                            // diisi/diganti, KECUALI dus itu sudah ada di rak lain.
                            ->afterStateUpdated(fn (Set $set, Get $get, $state) => static::dusMasihCocok($get('dus_arsip_id'), $state)
                                ? null
                                : $set('dus_arsip_id', null)),
                        Select::make('dus_arsip_id')
                            ->label('Nomor Dus')
                            ->relationship(
                                name: 'dusArsip',
                                titleAttribute: 'nomor_dus',
                                modifyQueryUsing: fn (Builder $query, Get $get) => static::scopeDusOptions($query, $get('rak_arsip_id')),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            // Rak masih kosong & dus yang dipilih sudah punya rak
                            // -> rak otomatis ikut rak dus tersebut.
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                if (blank($get('rak_arsip_id')) && $state) {
                                    $rakDus = DusArsip::query()->whereKey($state)->value('rak_arsip_id');

                                    if ($rakDus) {
                                        $set('rak_arsip_id', $rakDus);
                                    }
                                }
                            })
                            ->helperText('Kalau rak sudah dipilih, yang tampil dus di rak itu + dus yang belum punya rak (otomatis dimasukkan ke rak tersebut).'),
                    ])
                    ->action(function (Arsip $record, array $data): void {
                        $rakId = $data['rak_arsip_id'] ?? null;
                        $dusId = $data['dus_arsip_id'] ?? null;

                        // Dicek ulang di server: rak & dus wajib dalam
                        // jangkauan user; kalau rak diisi, dus wajib di rak
                        // itu atau belum punya rak.
                        $rakValid = ! $rakId || static::scopeRakOptions(RakArsip::query())->whereKey($rakId)->exists();
                        $dus = $dusId ? static::scopeDusOptions(DusArsip::query(), $rakId)->find($dusId) : null;

                        if (! $rakValid || ($dusId && ! $dus)) {
                            Notification::make()
                                ->title('Rak/Dus tidak valid')
                                ->body('Pilih rak & dus milik bidang/dinas Anda. Kalau rak diisi, dus harus ada di rak itu atau belum punya rak.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Rak kosong tapi dus-nya sudah ada di rak -> ikut rak dus.
                        $rakId = $rakId ?: $dus?->rak_arsip_id;

                        DB::transaction(function () use ($record, $rakId, $dus): void {
                            static::tautkanDusKeRak($dus, $rakId);

                            $record->forceFill([
                                'rak_arsip_id' => $rakId,
                                'dus_arsip_id' => $dus?->id,
                            ])->save();
                        });

                        Notification::make()
                            ->title('Lokasi simpan arsip diperbarui.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Export Excel cuma buat baris yang dicentang.
                    ExportBulkAction::make()
                        ->label('Export Excel (Terpilih)')
                        ->exporter(ArsipExporter::class)
                        ->formats([ExportFormat::Xlsx]),
                ]),
            ])
            // Urutan baku daftar arsip: Nomor Definitif ASCENDING (1, 2, 3, ...).
            // - Dikelompokkan per periode dulu, karena Nomor Definitif mulai
            //   dari 1 lagi di tiap tahap (hasil Manufer) -- kalau tidak,
            //   nomor dari tahap berbeda bakal campur (1, 1, 2, 2, ...).
            // - Kolom `nomor` bertipe teks, jadi di-CAST ke angka biar "10"
            //   tidak muncul sebelum "2".
            // - Arsip yang belum punya Nomor Definitif (tahap belum dimanufer)
            //   ditaruh di bawah, urut sesuai urutan input (urut_sementara).
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('arsips.periode_id')
                ->orderByRaw('arsips.nomor IS NULL')
                ->orderByRaw('CAST(arsips.nomor AS UNSIGNED)')
                ->orderBy('arsips.urut_sementara')
                ->orderBy('arsips.id'))
            ->searchOnBlur();
    }

    /**
     * Hitung ulang tanggal_musnah = 1 Januari tahun setelah tahun tanggal
     * arsip, lalu ditambah (retensi aktif + retensi inaktif) tahun. Sesuai
     * kaidah retensi: penghitungan jangka simpan dimulai dari awal tahun
     * BERIKUTNYA setelah tanggal arsip, bukan dari tanggal aslinya persis —
     * mis. arsip 10 Sep 2026 dihitung mulai 1 Jan 2027. Ini CUMA basis
     * hitung tanggal_musnah; kolom `tanggal` sendiri tetap disimpan apa
     * adanya sesuai input user, tidak ikut dinormalisasi.
     *
     * Dipanggil setiap kali salah satu input sumbernya berubah (kode
     * klasifikasi, tanggal arsip, atau retensi aktif/inaktif yang diubah
     * manual) — bukan cuma sekali saat kode dipilih, supaya hasilnya tetap
     * konsisten kalau user mengubah input lain sesudahnya.
     */
    protected static function recalculateTanggalMusnah(Set $set, Get $get): void
    {
        $tanggal = $get('tanggal');
        $aktif = $get('aktif');
        $inaktif = $get('inaktif');

        if (! $tanggal || ($aktif === null && $inaktif === null)) {
            $set('tanggal_musnah', null);

            return;
        }

        $basisRetensi = \Illuminate\Support\Carbon::parse($tanggal)
            ->addYear()
            ->startOfYear();

        $set('tanggal_musnah', $basisRetensi
            ->addYears((int) ($aktif ?? 0) + (int) ($inaktif ?? 0))
            ->toDateString());
    }

    /**
     * Batasi daftar rekord arsip sesuai role — sama pola dengan
     * PeriodeResource::getEloquentQuery().
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
     * Batasi opsi pilihan Bidang (form & filter): Admin Dinas & Pengolah
     * cuma boleh lihat/pilih bidang SE-DINAS sendiri -- bukan cuma
     * bidangnya sendiri, biar filter "Bidang" di tabel Arsip tetap berguna
     * buat Pengolah (bisa lihat bidang lain di dinas yang sama), tapi tidak
     * bocor ke dinas lain seperti sebelumnya. Superadmin bebas lihat semua
     * dinas.
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
     * Batasi opsi pilihan Rak: Pengolah cuma rak bidangnya sendiri, Admin
     * Dinas cuma rak-rak di dinasnya, Superadmin bebas.
     */
    public static function scopeRakOptions(Builder $query): Builder
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
     * Opsi Dus saat menempatkan arsip: dus dalam jangkauan user (Pengolah:
     * bidangnya, Admin Dinas: dinasnya).
     * - Rak dipilih  -> dus di rak itu + dus yang belum punya rak.
     * - Rak kosong   -> semua dus dalam jangkauan (Nomor Rak boleh
     *   dikosongkan, ditambahkan belakangan lewat "Ubah Rak/Dus").
     */
    public static function scopeDusOptions(Builder $query, mixed $rakId): Builder
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user?->isAdminDinas()) {
            $query->where('opd_induk_id', $user->opd_induk_id);
        }

        if (blank($rakId)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('rak_arsip_id', $rakId)
            ->orWhereNull('rak_arsip_id'));
    }

    /**
     * True kalau dus yang dipilih tetap valid untuk rak (baru) -- dus belum
     * punya rak, atau memang sudah di rak tersebut.
     */
    public static function dusMasihCocok(mixed $dusId, mixed $rakId): bool
    {
        if (blank($dusId)) {
            return true;
        }

        $rakDus = DusArsip::query()->whereKey($dusId)->value('rak_arsip_id');

        return blank($rakDus) || blank($rakId) || (int) $rakDus === (int) $rakId;
    }

    /**
     * Dus yang belum punya rak otomatis "dimasukkan" ke rak tempat arsipnya
     * ditempatkan (sekali saja -- dus yang sudah punya rak tidak dipindah).
     * Arsip LAIN di dus yang sama yang Nomor Rak-nya masih kosong ikut
     * diisi rak tersebut, biar data rak & dus tetap konsisten.
     */
    public static function tautkanDusKeRak(?DusArsip $dus, mixed $rakId): void
    {
        if (! $dus || blank($dus->rak_arsip_id) === false || blank($rakId)) {
            return;
        }

        $dus->forceFill(['rak_arsip_id' => $rakId])->save();

        Arsip::query()
            ->where('dus_arsip_id', $dus->id)
            ->whereNull('rak_arsip_id')
            ->update(['rak_arsip_id' => $rakId, 'updated_at' => now()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInaktif::route('/'),
            'create' => Pages\CreateInaktif::route('/create'),
            'edit' => Pages\EditInaktif::route('/{record}/edit'),
        ];
    }
}
