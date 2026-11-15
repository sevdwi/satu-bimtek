@extends('layouts.head_depan')
@section('content')
{{--
    Halaman publik "Lacak Dus Arsip" -- TANPA LOGIN, lihat
    App\Http\Controllers\DusArsipLacakController. Tujuannya: staf yang
    pegang dus fisik tapi tidak punya akun panel admin tetap bisa lihat
    daftar berkas di dalamnya, cukup mengetik KODE QR (muncul sebagai teks
    saat QR fisik discan, format "id-dinas-id-rak-tanggal"), ATAU ID Dus
    polos, ATAU tempel URL lengkap hasil scan QR versi lama -- ketiganya
    diproses sama lewat DusArsipLacakController::resolveDusArsipId().

    Dipakai style CSS yang SAMA dengan welcome.blade.php (resources/css/
    front.css, class .login-card dkk) biar terasa satu kesatuan tampilan
    dengan halaman depan, walau ini route/controller yang terpisah.
--}}
<nav class="navbar-custom">
  <div class="navbar-inner">
    <a href="{{ route('welcome') }}" class="nav-brand">
      <div class="nav-brand-mark">
        <img src="{{ asset('images/satu.png') }}" width="40" alt="Logo SATU">
      </div>
      <div>
        <div class="nav-brand-wordmark">SATU</div>
        <span class="nav-brand-sub">Sistem Informasi Kearsipan Terpadu</span>
      </div>
    </a>
  </div>
</nav>

<section class="hero" id="lacak-dus">
  <div class="hero-inner">

    <div class="col-5">
      <div class="hero-badge">
        <span class="hero-badge-dot"></span>
        Tanpa Login &nbsp;·&nbsp; Cukup Scan/Ketik Kode
      </div>
      <div class="hero-system-name">Sistem Informasi Kearsipan Terpadu</div>
      <h1 class="hero-wordmark">Lacak <span class="wordmark-thin">Dus</span></h1>
      <p class="hero-desc">
        Scan QR code yang tertempel di dus fisik -- HP Anda akan menampilkan
        sebuah kode singkat sebagai teks. Ketik kode itu di sini (atau ID Dus
        secara manual) untuk langsung melihat daftar berkas di dalamnya.
        Fitur ini terbuka untuk siapa saja yang memegang dus fisiknya, tanpa
        perlu akun panel admin.
      </p>
      <div class="hero-note">
        <i class="bi bi-qr-code-scan"></i>
        Kode QR juga tercetak sebagai teks tepat di bawah gambar QR-nya.
      </div>
    </div>

    <div class="col-7">
      <div class="login-wrapper">
        <div class="login-card">
          <div class="card-band"></div>

          <div class="card-head">
            <div class="brand-mark">
              <img src="{{ asset('images/satu.png') }}" width="40" alt="Logo SATU">
            </div>
            <span class="card-wordmark">Lacak Dus</span>
            <p class="card-greeting">Ketik kode hasil scan QR, atau ID Dus</p>
          </div>

          <div class="card-form">
            @if (session('lacak_error'))
              <div class="text-danger mb-3" style="color:#c0392b;font-size:0.85rem;">
                {{ session('lacak_error') }}
              </div>
            @endif

            <form action="{{ route('dus-arsip.lacak.cari') }}" method="POST">
              @csrf

              <div class="form-group">
                <label class="form-label-custom">
                  <i class="bi bi-qr-code-scan"></i> Kode QR, ID Dus, atau URL
                </label>

                <div class="input-wrap">
                  <i class="bi bi-box-seam i-icon"></i>
                  <input type="text"
                         name="kode"
                         class="form-input"
                         placeholder="Contoh: 3-12-20260915 atau 12"
                         value="{{ old('kode') }}"
                         required autofocus>
                </div>
                @error('kode')
                  <div class="text-danger mt-1" style="color:#c0392b;font-size:0.8rem;">{{ $message }}</div>
                @enderror
              </div>

              <hr class="form-divider">

              <button type="submit" class="btn-submit">
                <i class="bi bi-search"></i> Lihat Rangkuman Berkas
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

@include('layouts.footer_depan')
@endsection
