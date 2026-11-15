{{--
    Konten modal "Unit Kerja" di widget InstansiOverviewTable -- daftar
    polos (bukan tabel Filament interaktif, sengaja disederhanakan karena
    cuma buat lihat rincian sekilas, bukan CRUD) semua Opd (unit kerja) di
    bawah satu Instansi ($opdInduk), plus jumlah arsip (total, Musnah,
    Permanen) masing-masing & link ke daftar arsip unit kerja itu
    (InaktifResource ter-filter opd_id [+ nasib_akhir buat kolom Musnah/
    Permanen]).

    Kolom "Singkatan" DIHAPUS di sini (permintaan user) -- beda dari tabel
    utama widget yang masih menampilkan singkatan INSTANSI, singkatan UNIT
    KERJA (kolom singkatan_uk) ternyata sering diisi sama persis dengan
    nama lengkapnya di data seed, jadi cuma bikin tabel modal ini lebih
    lebar tanpa nambah informasi.
--}}
<div class="fi-modal-content-wrapper">
    @if ($opds->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Belum ada unit kerja terdaftar untuk instansi ini.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Unit Kerja</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Jumlah Arsip</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Musnah</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Permanen</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($opds as $opd)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $opd->unit_kerja }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="fi-badge inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
                                    {{ $opd->arsips_count }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a
                                    href="{{ \App\Filament\Resources\InaktifResource::getUrl('index', ['tableFilters' => ['opd_id' => ['value' => $opd->id], 'nasib_akhir' => ['value' => \App\Enums\NasibAkhir::Musnah->value]]]) }}"
                                    class="fi-badge inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-danger-50 text-danger-700 hover:underline dark:bg-danger-500/10 dark:text-danger-400"
                                >
                                    {{ $opd->arsips_musnah_count }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a
                                    href="{{ \App\Filament\Resources\InaktifResource::getUrl('index', ['tableFilters' => ['opd_id' => ['value' => $opd->id], 'nasib_akhir' => ['value' => \App\Enums\NasibAkhir::Permanen->value]]]) }}"
                                    class="fi-badge inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-success-50 text-success-700 hover:underline dark:bg-success-500/10 dark:text-success-400"
                                >
                                    {{ $opd->arsips_permanen_count }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a
                                    href="{{ \App\Filament\Resources\InaktifResource::getUrl('index', ['tableFilters' => ['opd_id' => ['value' => $opd->id]]]) }}"
                                    class="text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    Lihat Arsip
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
