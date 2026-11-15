{{--
    Widget "Lacak Dus Arsip" TERTANAM LANGSUNG (bukan cuma tautan) --
    dipakai di DUA tempat: (1) ditempel di bawah form login panel Filament
    lewat render hook 'panels::auth.login.form.after', didaftarkan di
    App\Providers\AppServiceProvider::boot(); (2) di-@include langsung di
    resources/views/welcome.blade.php ('/' & '/login' publik) di dalam
    kartu putihnya sendiri -- pass ['standalone' => true] di situ supaya
    border/jarak atas widget ini tidak dobel sama border kartu pembungkusnya.

    Form di sini SENGAJA bukan Livewire (form HTML polos, @csrf manual) --
    posting langsung ke route 'dus-arsip.lacak.cari'
    (App\Http\Controllers\DusArsipLacakController), SAMA PERSIS logikanya
    dengan halaman publik penuhnya di /lacak-dus
    (resources/views/dus/lacak.blade.php) -- halaman itu tetap ada sendiri
    buat yang scan QR & tidak lewat halaman ini dulu.
--}}
<div @unless($standalone ?? false) style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid rgb(229 231 235);" @endunless>
    <div style="text-align: center; margin-bottom: .75rem;">
        <p style="display: flex; align-items: center; justify-content: center; gap: .35rem; font-weight: 700; font-size: .9rem; margin: 0 0 .35rem; color: rgb(17 24 39);">
            <x-heroicon-o-qr-code style="width: 1.1rem; height: 1.1rem;" />
            Lacak Dus Arsip
        </p>
        <p style="font-size: .75rem; line-height: 1.4; color: rgb(107 114 128); margin: 0;">
            Scan QR code yang tertempel di dus fisik -- HP Anda akan menampilkan sebuah kode singkat sebagai teks. Ketik kode itu di sini (atau ID Dus secara manual) untuk langsung melihat daftar berkas di dalamnya, tanpa perlu login.
        </p>
    </div>

    @if (session('lacak_error'))
        <p style="color: rgb(220 38 38); font-size: .75rem; text-align: center; margin: 0 0 .5rem;">
            {{ session('lacak_error') }}
        </p>
    @endif

    <form action="{{ route('dus-arsip.lacak.cari') }}" method="POST" style="display: flex; gap: .5rem;">
        @csrf

        <input
            type="text"
            name="kode"
            placeholder="Contoh: 3-12-20260915 atau 12"
            value="{{ old('kode') }}"
            required
            style="flex: 1; min-width: 0; padding: .5rem .75rem; border: 1px solid rgb(209 213 219); border-radius: .5rem; font-size: .8125rem;"
        >

        <button
            type="submit"
            style="padding: .5rem .9rem; background: rgb(37 99 235); color: #fff; border: none; border-radius: .5rem; font-size: .8125rem; font-weight: 600; cursor: pointer; white-space: nowrap;"
        >
            Lihat
        </button>
    </form>
</div>
