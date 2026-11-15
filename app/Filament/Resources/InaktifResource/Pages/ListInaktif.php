<?php

namespace App\Filament\Resources\InaktifResource\Pages;

use App\Enums\ArsipStatus;
use App\Filament\Resources\InaktifResource;
use App\Models\Arsip;
use App\Models\DusArsip;
use App\Models\Periode;
use App\Models\RakArsip;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ListInaktif extends ListRecords
{
    protected static string $resource = InaktifResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            /**
             * Manufer: kasih nomor urut definitif ke semua arsip dalam SATU
             * tahap (Periode) sekaligus, urutan Kode -> Bulan -> Tanggal
             * (tahap & tahun-nya sendiri konstan karena sudah dipilih),
             * lalu KUNCI tahap itu -- arsip di dalamnya nggak bisa
             * ditambah/diedit/dihapus/dimanufer ulang sampai dibuka lagi
             * oleh Superadmin (lihat PeriodeResource action "Buka Kembali").
             *
             * Hanya Admin Dinas & Pengolah yang boleh manufer -- sama pola
             * hak akses dengan create Periode/Arsip (lihat PeriodePolicy &
             * ArsipPolicy). Superadmin sengaja tidak dikasih tombol ini,
             * perannya cuma buka kunci lewat halaman Periode.
             */
            Action::make('manufer')
                ->label('Manufer')
                ->icon(Heroicon::OutlinedArrowsUpDown)
                ->color('warning')
                ->visible(fn (): bool => $user?->isAdminDinas() || $user?->isPengolah())
                ->modalHeading('Manufer Arsip')
                ->modalDescription('Pilih satu tahap. Semua arsip di tahap itu akan diberi nomor urut definitif (urutan Kode -> Bulan -> Tanggal) lalu tahapnya DIKUNCI -- tidak bisa ditambah/diedit/dihapus/dimanufer ulang sampai dibuka lagi oleh Superadmin.')
                ->modalSubmitActionLabel('Jalankan Manufer')
                ->schema([
                    Select::make('periode_id')
                        ->label('Pilih Tahap')
                        ->options(fn (): array => $this->getManuferablePeriodeOptions())
                        ->searchable()
                        ->required()
                        ->helperText('Cuma tahap yang belum pernah dimanufer yang muncul di sini.'),
                ])
                ->action(function (array $data): void {
                    $this->runManufer((int) $data['periode_id']);
                }),
            /**
             * Tempatkan ke Rak/Dus secara INTERVAL: semua arsip di satu tahap
             * (yang sudah dimanufer) dengan Nomor Definitif dari X sampai Y
             * dimasukkan ke satu Rak (& Dus) sekaligus -- versi massal dari
             * action per baris "Tempatkan ke Rak/Dus" di InaktifResource.
             * Hak aksesnya sama: cuma Admin Dinas & Pengolah.
             */
            Action::make('tempatkanInterval')
                ->label('Tempatkan ke Rak/Dus (Interval)')
                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                ->color('info')
                ->visible(fn (): bool => $user?->isAdminDinas() || $user?->isPengolah())
                ->modalHeading('Tempatkan Arsip ke Rak/Dus Secara Interval')
                ->modalDescription('Pilih tahap yang sudah dimanufer, isi rentang Nomor Definitif, lalu pilih Rak dan/atau Dus tujuan. Nomor Rak boleh dikosongkan & ditambahkan belakangan (jalankan lagi dengan memilih rak-nya saja).')
                ->modalSubmitActionLabel('Tempatkan')
                ->schema([
                    Select::make('periode_id')
                        ->label('Tahap (sudah dimanufer)')
                        ->options(fn (): array => $this->getPeriodeTermanuferOptions())
                        ->searchable()
                        ->required()
                        ->live(),
                    TextInput::make('nomor_dari')
                        ->label('Nomor Definitif Dari')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true),
                    TextInput::make('nomor_sampai')
                        ->label('Nomor Definitif Sampai')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->gte('nomor_dari')
                        ->validationMessages(['gte' => 'Nomor "Sampai" harus sama atau lebih besar dari nomor "Dari".'])
                        ->live(onBlur: true)
                        // Pratinjau jumlah arsip yang kena, dihitung ulang tiap
                        // tahap/rentang nomor berubah.
                        ->helperText(function (Get $get): ?string {
                            if (! $get('periode_id') || ! $get('nomor_dari') || ! $get('nomor_sampai')) {
                                return null;
                            }

                            $jumlah = $this->queryArsipInterval(
                                (int) $get('periode_id'),
                                (int) $get('nomor_dari'),
                                (int) $get('nomor_sampai'),
                            )->count();

                            return "{$jumlah} arsip ditemukan di rentang ini.";
                        }),
                    Select::make('rak_arsip_id')
                        ->label('Nomor Rak')
                        ->options(fn (): array => InaktifResource::scopeRakOptions(RakArsip::query())
                            ->orderBy('nomor_rak')
                            ->pluck('nomor_rak', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->helperText('Opsional -- boleh dikosongkan kalau arsip baru dimasukkan ke dus.')
                        // Dus tetap dipertahankan, kecuali dus itu sudah di rak lain.
                        ->afterStateUpdated(fn (Set $set, Get $get, $state) => InaktifResource::dusMasihCocok($get('dus_arsip_id'), $state)
                            ? null
                            : $set('dus_arsip_id', null)),
                    Select::make('dus_arsip_id')
                        ->label('Nomor Dus')
                        // Dus di rak terpilih + dus yang belum punya rak
                        // (dus sekarang dibuat tanpa Nomor Rak) -- lihat
                        // InaktifResource::scopeDusOptions().
                        ->options(fn (Get $get): array => InaktifResource::scopeDusOptions(DusArsip::query(), $get('rak_arsip_id'))
                            ->orderBy('nomor_dus')
                            ->pluck('nomor_dus', 'id')
                            ->all())
                        ->searchable()
                        // Wajib salah satu: Rak atau Dus.
                        ->required(fn (Get $get): bool => blank($get('rak_arsip_id')))
                        ->validationMessages(['required' => 'Pilih Nomor Rak atau Nomor Dus (minimal salah satu).'])
                        ->helperText('Kalau rak dipilih, yang tampil dus di rak itu + dus yang belum punya rak (otomatis dimasukkan ke rak tersebut). Boleh dikosongkan kalau arsip cuma ditaruh di rak.'),
                    Toggle::make('timpa')
                        ->label('Timpa Rak/Dus yang sudah terisi')
                        ->helperText('Tidak dicentang: cuma mengisi kolom Rak/Dus yang masih kosong (cocok untuk menambahkan Nomor Rak belakangan); arsip yang sudah di rak/dus LAIN dilewati. Dicentang: Rak & Dus semua arsip di rentang ditimpa persis sesuai pilihan.')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $this->runTempatkanInterval($data);
                }),
            CreateAction::make(),
        ];
    }

    /**
     * Opsi tahap buat modal Tempatkan Interval: cuma yang SUDAH dimanufer
     * (nomor definitif sudah pasti), scope-nya sesuai role.
     *
     * @return array<int, string>
     */
    protected function getPeriodeTermanuferOptions(): array
    {
        $user = auth()->user();

        $query = Periode::query()->whereNotNull('manufer_at');

        if ($user?->isAdminDinas()) {
            $query->whereHas('opd', fn ($q) => $q->where('opd_induk_id', $user->opd_induk_id));
        } elseif ($user?->isPengolah()) {
            $query->where('opd_id', $user->opd_id);
        }

        return $query->with('opd')
            ->orderByDesc('tahun')
            ->orderBy('tahap')
            ->get()
            ->mapWithKeys(fn (Periode $periode): array => [
                $periode->id => "{$periode->label()} — {$periode->opd?->unit_kerja}",
            ])
            ->all();
    }

    /**
     * Query arsip di satu periode dengan Nomor Definitif dalam rentang.
     * Kolom `nomor` bertipe teks, jadi di-CAST ke angka biar "10" dihitung
     * sebagai 10 (bukan dibandingkan sebagai teks).
     */
    protected function queryArsipInterval(int $periodeId, int $dari, int $sampai): Builder
    {
        return Arsip::query()
            ->where('periode_id', $periodeId)
            ->whereNotNull('nomor')
            ->whereRaw('CAST(nomor AS UNSIGNED) BETWEEN ? AND ?', [$dari, $sampai]);
    }

    /**
     * Jalankan penempatan interval. Semua dicek ulang di server (tahap
     * sudah dimanufer & milik user, rak dalam jangkauan user, dus memang
     * ada di rak itu) biar nggak bisa diakali lewat request manual.
     */
    protected function runTempatkanInterval(array $data): void
    {
        $user = auth()->user();
        $gagal = fn (string $pesan) => Notification::make()->title($pesan)->danger()->send();

        $periode = Periode::query()->whereNotNull('manufer_at')->find($data['periode_id'] ?? null);

        $bolehPeriode = $periode && match (true) {
            $user?->isAdminDinas() => $periode->opd?->opd_induk_id === $user->opd_induk_id,
            $user?->isPengolah() => $periode->opd_id === $user->opd_id,
            default => false,
        };

        if (! $bolehPeriode) {
            $gagal('Tahap tidak ditemukan, belum dimanufer, atau bukan milik Anda.');

            return;
        }

        $rakIdInput = $data['rak_arsip_id'] ?? null;
        $rak = $rakIdInput ? InaktifResource::scopeRakOptions(RakArsip::query())->find($rakIdInput) : null;

        if ($rakIdInput && ! $rak) {
            $gagal('Rak tidak ditemukan atau bukan milik Anda.');

            return;
        }

        $dusId = $data['dus_arsip_id'] ?? null;
        $dus = $dusId ? InaktifResource::scopeDusOptions(DusArsip::query(), $rak?->id)->find($dusId) : null;

        if ($dusId && ! $dus) {
            $gagal('Dus yang dipilih tidak ada di rak tersebut atau bukan milik Anda.');

            return;
        }

        if (! $rak && ! $dus) {
            $gagal('Pilih Nomor Rak atau Nomor Dus (minimal salah satu).');

            return;
        }

        // Rak kosong tapi dus-nya sudah ada di rak -> ikut rak dus.
        $rakId = $rak?->id ?? $dus?->rak_arsip_id;

        $dari = (int) $data['nomor_dari'];
        $sampai = (int) $data['nomor_sampai'];

        $query = $this->queryArsipInterval($periode->id, $dari, $sampai);
        $totalDiRentang = (clone $query)->count();

        if ($totalDiRentang === 0) {
            $gagal("Tidak ada arsip dengan Nomor Definitif {$dari}–{$sampai} di tahap \"{$periode->label()}\".");

            return;
        }

        $timpa = (bool) ($data['timpa'] ?? false);

        if ($timpa) {
            // Ditimpa persis sesuai pilihan (yang dikosongkan ikut dikosongkan).
            $nilai = ['rak_arsip_id' => $rakId, 'dus_arsip_id' => $dus?->id];
        } else {
            // Cuma mengisi kolom yang masih kosong / yang memang sudah sama --
            // arsip yang sudah ada di rak/dus LAIN dilewati. Kolom yang tidak
            // dipilih tidak disentuh sama sekali.
            $nilai = [];

            if ($rakId) {
                $query->where(fn ($q) => $q->whereNull('rak_arsip_id')->orWhere('rak_arsip_id', $rakId));
                $nilai['rak_arsip_id'] = $rakId;
            }

            if ($dus) {
                $query->where(fn ($q) => $q->whereNull('dus_arsip_id')->orWhere('dus_arsip_id', $dus->id));
                $nilai['dus_arsip_id'] = $dus->id;
            }
        }

        // Update massal langsung (1 query), cuma kolom rak & dus -- field
        // lain arsip yang sudah terkunci manufer tidak disentuh.
        $diproses = DB::transaction(function () use ($query, $rakId, $dus, $nilai): int {
            // Dus yang belum punya rak otomatis masuk ke rak ini.
            InaktifResource::tautkanDusKeRak($dus, $rakId);

            return $query->update($nilai + ['updated_at' => now()]);
        });

        $dilewati = $totalDiRentang - $diproses;
        $tujuan = collect([
            $rakId ? 'Rak ' . (RakArsip::find($rakId)?->nomor_rak ?? '-') : null,
            $dus ? 'Dus ' . ($dus->nomor_dus ?? $dus->id) : null,
        ])->filter()->implode(' / ');

        Notification::make()
            ->title("{$diproses} arsip (No. {$dari}–{$sampai}) ditempatkan ke {$tujuan}.")
            ->body($dilewati > 0 ? "{$dilewati} arsip dilewati karena sudah ada di Rak/Dus lain (centang \"Timpa\" untuk memindahkannya)." : null)
            ->success()
            ->send();

        $this->resetTable();
    }

    /**
     * Opsi tahap yang boleh dipilih di modal Manufer: belum pernah
     * dimanufer (manufer_at masih null), dan scope-nya sesuai role user --
     * sama persis pola scoping di PeriodeResource::getEloquentQuery().
     *
     * @return array<int, string>
     */
    protected function getManuferablePeriodeOptions(): array
    {
        $user = auth()->user();

        $query = Periode::query()->whereNull('manufer_at');

        if ($user?->isAdminDinas()) {
            $query->whereHas('opd', fn ($q) => $q->where('opd_induk_id', $user->opd_induk_id));
        } elseif ($user?->isPengolah()) {
            $query->where('opd_id', $user->opd_id);
        }

        return $query->with('opd')
            ->orderByDesc('tahun')
            ->orderBy('tahap')
            ->get()
            ->mapWithKeys(fn (Periode $periode): array => [
                $periode->id => "{$periode->label()} — {$periode->opd?->unit_kerja}",
            ])
            ->all();
    }

    /**
     * Jalankan Manufer buat satu periode: urutkan arsipnya (Kode -> Tahun ->
     * Bulan -> Tanggal -- Kode lewat sort_key master_kodes, biar urutan
     * hierarkis kode klasifikasi bener sama seperti MasterKodeResource),
     * tulis ulang kolom `nomor` masing-masing arsip jadi 1, 2, 3, dst sesuai
     * urutan itu, ubah status semua arsipnya jadi "Input" (dari default
     * "Draft" saat pertama diinput -- lihat InaktifResource::form()), lalu
     * kunci periode-nya. Semua dalam satu transaksi DB biar nggak ada nomor
     * yang "nyangkut" separuh kalau ada error di tengah.
     */
    protected function runManufer(int $periodeId): void
    {
        $user = auth()->user();

        $periode = Periode::query()->whereNull('manufer_at')->find($periodeId);

        if (! $periode) {
            Notification::make()
                ->title('Tahap tidak ditemukan atau sudah keburu dimanufer orang lain.')
                ->danger()
                ->send();

            return;
        }

        // Jaga-jaga (defense in depth): opsi Select di modal sudah dibatasi
        // per role, dicek ulang di sini biar nggak bisa diakalin lewat
        // request manual.
        $allowed = match (true) {
            $user?->isAdminDinas() => $periode->opd?->opd_induk_id === $user->opd_induk_id,
            $user?->isPengolah() => $periode->opd_id === $user->opd_id,
            default => false,
        };

        if (! $allowed) {
            Notification::make()
                ->title('Anda tidak berhak manufer tahap ini.')
                ->danger()
                ->send();

            return;
        }

        $arsips = Arsip::query()
            ->where('arsips.periode_id', $periode->id)
            ->leftJoin('master_kodes', 'master_kodes.id', '=', 'arsips.master_kode_id')
            // Urutan manufer (permintaan langsung): Kode -> Tahun -> Bulan -> Tanggal.
            ->orderBy('master_kodes.sort_key')
            ->orderBy('arsips.tahun')
            ->orderByRaw('MONTH(arsips.tanggal)')
            ->orderBy('arsips.tanggal')
            ->orderBy('arsips.id')
            ->select('arsips.*')
            ->get();

        DB::transaction(function () use ($arsips, $periode, $user): void {
            $urutan = 1;

            foreach ($arsips as $arsip) {
                $arsip->forceFill([
                    'nomor' => (string) $urutan,
                    // Otomatis naik dari "Draft" ke "Input" begitu tahapnya
                    // dimanufer -- sesuai permintaan langsung: "saat klik fitur
                    // manuver otomatis data status ... berubah menjadi input".
                    'status' => ArsipStatus::Input->value,
                ])->save();
                $urutan++;
            }

            $periode->forceFill([
                'manufer_at' => now(),
                'manufer_by' => $user?->id,
            ])->save();
        });

        Notification::make()
            ->title("Manufer selesai — {$arsips->count()} arsip di tahap \"{$periode->label()}\" diberi nomor urut, tahap sekarang terkunci.")
            ->success()
            ->send();

        $this->resetTable();
    }
}
