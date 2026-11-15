{{--
    Widget reCAPTCHA v2 ("saya bukan robot") buat halaman login panel
    Filament. Dipasang lewat App\Filament\Pages\Auth\Login::form() sebagai
    Filament\Schemas\Components\View, JADI BUKAN field form biasa -- tidak
    ada input yang dikirim lewat request POST karena form login Filament
    disubmit via Livewire (AJAX), bukan submit HTML polos.

    Token dari widget diambil lewat callback JS (data-callback), lalu
    dikirim ke server dengan $wire->set('data.captcha', ...) langsung ke
    property publik $data milik komponen Livewire Login -- TIDAK perlu ada
    field Filament yang mem-bind ke 'captcha' karena $wire->set() bisa
    langsung menulis ke path array manapun di properti publik, dipakai lagi
    di Login::verifyCaptcha() lewat $this->data['captcha'].

    wire:ignore WAJIB di elemen pembungkus supaya Livewire tidak
    membongkar/render-ulang widget captcha (yang di-render Google lewat
    JS-nya sendiri) tiap kali ada morph DOM Livewire.

    Widget ini SENGAJA disembunyikan total kalau diakses dari localhost/
    domain dev (lihat App\Support\Recaptcha::isLocalHost(), dipakai juga di
    Login::verifyCaptcha() buat lewatin verifikasinya) -- daripada nampilin
    checkbox yang toh tidak akan bisa diklik (jaringan lokal sering
    memblokir akses ke Google/recaptcha.net).
--}}
@unless (\App\Support\Recaptcha::isLocalHost())
<div wire:ignore x-data x-init="
    window.satuBismillahRecaptchaSuccess = (token) => { $wire.set('data.captcha', token); };
    window.satuBismillahRecaptchaExpired = () => { $wire.set('data.captcha', null); };
">
    @if ($siteKey = config('services.no_captcha.site_key'))
        <div
            class="g-recaptcha"
            data-sitekey="{{ $siteKey }}"
            data-callback="satuBismillahRecaptchaSuccess"
            data-expired-callback="satuBismillahRecaptchaExpired"
        ></div>

        {{--
            SENGAJA pakai domain "recaptcha.net" (bukan "google.com") --
            keduanya resmi dari Google dan identik fungsinya, tapi
            recaptcha.net dipakai sebagai jalur alternatif buat jaringan
            yang memblokir/membatasi akses ke domain google.com langsung
            (umum terjadi di jaringan kantor/pemda). Kalau widget captcha
            tidak muncul/tidak bisa diklik sama sekali, ini penyebab paling
            umum -- lihat juga catatan yang sama di verifyCaptcha() di bawah
            (panggilan siteverify server-side).
        --}}
        @once
            <script src="https://www.recaptcha.net/recaptcha/api.js" async defer></script>
        @endonce

        @error('data.captcha')
            <p class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400 mt-1">
                {{ $message }}
            </p>
        @enderror
    @endif
</div>
@endunless
