<?php

use App\Http\Controllers\ArsipKartuController;
use App\Http\Controllers\DusArsipLabelController;
use App\Http\Controllers\DusArsipLacakController;
use App\Http\Controllers\DusArsipRangkumanController;
use App\Http\Controllers\RepoDokController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Halaman depan publik (navbar + hero + kartu login + fitur + about) —
// dulu '/' langsung redirect ke /admin, sekarang menampilkan landing page
// SATU sesuai desain yang diminta. Login panel Filament (/admin/login)
// tetap ada & tidak diubah — ini alur TERPISAH buat login dari halaman
// depan, pakai Email/NIP (lihat UserController::login).
//
// PENTING: user yang SUDAH login tetap dilempar ke /admin kalau buka '/'
// (persis perilaku lama sebelum ada landing page ini). Ini bukan cuma soal
// UX — beberapa package termasuk stechstudio/filament-impersonate
// redirect balik ke root URL ('/') setelah aksi selesai. Tanpa pengecekan
// ini, user yang sudah login (termasuk hasil impersonate) akan disodori
// balik halaman login publik dan terlihat seperti "ke-logout" padahal
// sebenarnya cuma salah landing page.
Route::get('/', function () {
    return auth()->check() ? redirect('/admin') : view('welcome');
})->name('welcome');

Route::get('/login', function () {
    return auth()->check() ? redirect('/admin') : view('welcome');
})->name('login');

Route::post('/login', [UserController::class, 'login'])
    // Rate limit ditambahkan (kajian keamanan Sept 2026): rute ini SEBELUMNYA
    // tidak dibatasi sama sekali (beda dari /admin/login milik Filament yang
    // sudah punya rate limit bawaan) -- terbukti lewat pengujian bisa dicoba
    // brute force tanpa batas. 6 percobaan/menit per kombinasi IP+rute, sama
    // seperti default throttle login Laravel.
    ->middleware('throttle:6,1')
    ->name('login.post');

// Halaman cetak "Kartu Deskripsi Arsip" per arsip (lihat tombol "Cetak
// Kartu" di InaktifResource). Sengaja route biasa (bukan resource/page
// Filament) karena tampilannya polos tanpa shell panel -- cuma dilindungi
// middleware 'auth' bawaan Laravel (guard 'web', sama dengan yang dipakai
// panel Filament), dan otorisasi per-record lewat ArsipPolicy::view() di
// ArsipKartuController.
Route::middleware('auth')
    ->get('/arsip/{arsip}/kartu', [ArsipKartuController::class, 'show'])
    ->name('arsip.kartu');

// Halaman cetak "Label Dus Arsip" (satu/banyak sekaligus, lewat query
// ?ids=1,2,3 -- lihat tombol "Cetak Label" di DusArsipResource). Sama pola
// dengan 'arsip.kartu' di atas: alat bantu cetak internal admin, DILINDUNGI
// middleware 'auth' -- beda dari 'dus-arsip.rangkuman'/'dus-arsip.qrcode'
// di bawah yang sengaja publik.
Route::middleware('auth')
    ->get('/dus/label/cetak', [DusArsipLabelController::class, 'cetak'])
    ->name('dus-arsip.label');

// Halaman publik "Lacak Dus Arsip" -- TANPA LOGIN, lihat
// App\Http\Controllers\DusArsipLacakController. Tempat input ID Dus atau
// tempel URL hasil scan QR code, lalu diarahkan ke halaman rangkuman di
// bawah. Ditautkan dari navbar welcome.blade.php.
Route::get('/lacak-dus', [DusArsipLacakController::class, 'index'])->name('dus-arsip.lacak');
Route::post('/lacak-dus', [DusArsipLacakController::class, 'cari'])
    // Rate limit ditambahkan (kajian keamanan Sept 2026) -- memperlambat upaya
    // enumerasi ID Dus lewat form ini (lihat juga throttle di route GET
    // 'dus-arsip.rangkuman' & 'dus-arsip.qrcode' di bawah, yang bisa diakses
    // langsung tanpa lewat form ini).
    ->middleware('throttle:15,1')
    ->name('dus-arsip.lacak.cari');

// Halaman "Rangkuman Berkas" per Dus Arsip + gambar QR code-nya (lihat
// App\Http\Controllers\DusArsipRangkumanController). QR code isinya link
// ke route 'dus-arsip.rangkuman' -- ditempel/dicetak di dus fisik biar
// tinggal discan buat lihat daftar arsip di dalamnya.
//
// SENGAJA PUBLIK (TIDAK dibungkus middleware 'auth', beda dari
// 'arsip.kartu' di atas) -- tujuan awal QR code ini memang supaya staf yang
// cuma pegang dus fisik (tanpa akun panel admin) tetap bisa lihat isinya
// cukup dengan scan/lewat 'dus-arsip.lacak' di atas, tanpa perlu login.
// Kalau suatu saat perlu dibatasi lagi, tinggal bungkus balik dengan
// middleware('auth') + otorisasi (lihat catatan di
// DusArsipRangkumanController).
Route::get('/dus/{dusArsip}/rangkuman', [DusArsipRangkumanController::class, 'show'])
    // Rate limit ditambahkan (kajian keamanan Sept 2026): route ini publik &
    // TANPA auth (sengaja, lihat catatan di DusArsipRangkumanController), tapi
    // pengujian membuktikan ID-nya bisa di-enumerasi berurutan (1, 2, 3, ...)
    // tanpa hambatan. Throttle di sini TIDAK menghilangkan enumerasi (itu
    // keputusan desain/bisnis, lihat catatan controller), cuma memperlambat
    // scraping otomatis massal. 30 request/menit per IP -- longgar supaya QR
    // fisik yang di-scan berkali-kali tetap normal dipakai.
    ->middleware('throttle:30,1')
    ->name('dus-arsip.rangkuman');

Route::get('/dus/{dusArsip}/qrcode', [DusArsipRangkumanController::class, 'qrcode'])
    ->middleware('throttle:30,1')
    ->name('dus-arsip.qrcode');

// Halaman publik "Kebijakan Privasi" (Privacy Policy) untuk kepatuhan Google Play Store & transparansi data
Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy-policy');
Route::view('/kebijakan-privasi', 'legal.privacy-policy')->name('legal.kebijakan-privasi');

// Halaman publik "Syarat dan Ketentuan" (Terms & Conditions)
Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');
Route::view('/syarat-ketentuan', 'legal.terms')->name('legal.syarat-ketentuan');
Route::view('/terms', 'legal.terms')->name('legal.terms.short');


// Halaman "Repositori Dokumentasi" (/repo_dok) -- fitur baru: dokumentasi
// penggunaan aplikasi berjenjang (folder + sub-folder) dengan pencarian,
// lihat App\Http\Controllers\RepoDokController. TERBUKA untuk SEMUA user
// yang SUDAH LOGIN (Superadmin, Admin Dinas, Pengolah -- sengaja sama
// middleware 'auth' seperti 'arsip.kartu'/'dus-arsip.label' di atas,
// BUKAN publik seperti 'dus-arsip.lacak'). Struktur folder & isinya
// dikelola dinamis oleh Superadmin lewat
// App\Filament\Resources\RepoDokFolderResource (panel /admin) --
// route di sini BACA SAJA.
Route::middleware('auth')->prefix('repo_dok')->name('repo-dok.')->group(function () {
    Route::get('/', [RepoDokController::class, 'index'])->name('index');
    Route::get('/cari', [RepoDokController::class, 'cari'])->name('cari');
    Route::get('/folder/{folder}', [RepoDokController::class, 'folder'])->name('folder');
    Route::get('/berkas/{file}/unduh', [RepoDokController::class, 'unduh'])->name('unduh');
});
