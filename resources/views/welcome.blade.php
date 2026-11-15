@extends('layouts.head_depan')
@section('content')
<!-- ═══════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════ -->
<nav class="navbar-custom">
  <div class="navbar-inner">
    <a href="{{ route('welcome') }}" class="nav-brand">
      <div class="nav-brand-mark">
        <img src="{{ asset('images/new-satu.png') }}" width="40" alt="Logo SATU">
      </div>
      <div>
        <!-- <div class="nav-brand-wordmark">SATU</div> -->
        <span class="nav-brand-sub">Sistem Informasi Kearsipan Terpadu - VERSI BIMTEK</span>
      </div>
    </a>

    {{-- Link ke halaman publik "Lacak Dus Arsip" (tanpa login) -- lihat
         routes/web.php & App\Http\Controllers\DusArsipLacakController. --}}
    <a href="{{ route('dus-arsip.lacak') }}"
       style="display:flex;align-items:center;gap:.4rem;color:#fff;text-decoration:none;font-weight:600;font-size:.9rem;margin-left:auto;">
      <i class="bi bi-qr-code-scan"></i> Lacak Dus Arsip
    </a>
  </div>
</nav>


<!-- ═══════════════════════════════════════════════
     HERO + LOGIN
════════════════════════════════════════════════ -->
<section class="hero" id="login">
  <div class="hero-inner">

    <!-- Text Column -->
    <div class="col-5">
      <div class="hero-badge">
        <span class="hero-badge-dot"></span>
        Efisien &nbsp;·&nbsp; Simple &nbsp;·&nbsp; Modern
      </div>
      <div class="hero-system-name">Sistem Informasi Kearsipan Terpadu - VERSI BIMTEK</div>
      {{-- Wordmark teks "SATU" diganti logo gambar (public/images/satu.png).
           Tetap dibungkus <h1> biar judul halaman tetap ada buat SEO &
           pembaca layar -- teksnya diwakili atribut alt. --}}
      <h1 class="hero-wordmark hero-wordmark--logo">
        <img src="{{ asset('images/new-satu.png') }}" alt="SATU" class="hero-logo">
      </h1>
      <p class="hero-desc">
        Platform digital terpadu untuk pengelolaan arsip inaktif, musnah, dan statis
        di lingkungan Dinas Arsip dan Perpustakaan Kabupaten Cilacap.
      </p>
      <div class="hero-note">
        <i class="bi bi-lock"></i>
        Akses terbatas untuk pegawai yang telah terdaftar.
      </div>
    </div>

    <!-- Visual / Login Column -->
    <div class="col-7">
      <div class="login-wrapper">
        <div class="login-card">

          <!-- Top accent band -->
          <div class="card-band"></div>

          <!-- Card Header / Branding -->
          <div class="card-head">
            <div class="brand-mark">
              <img src="{{ asset('images/new-satu.png') }}" width="40" alt="Logo SATU">
            </div>
            <!-- <span class="card-wordmark">SATU</span> -->
            <p class="card-greeting">Selamat datang kembali</p>
          </div>

          <!-- Form -->
          <div class="card-form">
            @if (session('login_error'))
              <div class="text-danger mb-3" style="color:#c0392b;font-size:0.85rem;">
                {{ session('login_error') }}
              </div>
            @endif

            <!-- Google reCAPTCHA v3 (skor tak terlihat, dipasang lewat RECAPTCHA_SITE_KEY) -->
            @if (config('services.recaptcha.site_key'))
              <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
            @endif

            {{--
                Google reCAPTCHA v2 ("saya bukan robot", dipasang lewat
                NOCAPTCHA_SITEKEY) -- SENGAJA saling eksklusif dengan v3 di
                atas, isi salah satu pasangan site key/secret saja di .env.
                Beda dari v3: widget v2 otomatis bikin field tersembunyi
                bernama "g-recaptcha-response" sendiri di dalam <form> begini
                saat usernya centang, jadi form ini TIDAK butuh JS apa pun
                buat kirim token-nya -- submit form biasa sudah cukup, dan
                kalau script Google gagal dimuat form tetap bisa disubmit
                (cuma checkbox-nya saja yang tidak muncul).
            --}}
            {{-- "recaptcha.net" (bukan "google.com") -- domain resmi
                 alternatif dari Google, dipakai supaya widget tetap bisa
                 dimuat/diklik di jaringan yang memblokir google.com
                 langsung (umum di jaringan kantor/pemda).

                 Skrip & widgetnya SENGAJA tidak dimuat sama sekali kalau
                 diakses dari localhost/domain dev (lihat
                 App\Support\Recaptcha::isLocalHost(), dipakai juga di
                 UserController::verifyRecaptcha() buat lewatin
                 verifikasinya) -- daripada nampilin checkbox yang toh tidak
                 akan bisa diklik di jaringan lokal. --}}
            @if (config('services.no_captcha.site_key') && ! \App\Support\Recaptcha::isLocalHost())
              <script src="https://www.recaptcha.net/recaptcha/api.js" async defer></script>
            @endif

            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
              @csrf

              <!-- Email / NIP -->
              <div class="form-group">
                <label class="form-label-custom">
                  <i class="bi bi-person-badge"></i> Email / NIP
                </label>

                <div class="input-wrap">
                  <i class="bi bi-person-badge i-icon"></i>
                  <input type="text"
                         name="login"
                         class="form-input"
                         placeholder="nama@email.com atau NIP"
                         value="{{ old('login') }}"
                         required autofocus>
                </div>
                @error('login')
                  <div class="text-danger mt-1" style="color:#c0392b;font-size:0.8rem;">{{ $message }}</div>
                @enderror
              </div>

              <!-- Password -->
              <div class="form-group">
                <label class="form-label-custom">
                  <i class="bi bi-lock"></i> Password
                </label>

                <div class="input-wrap">
                  <i class="bi bi-lock i-icon"></i>
                  <input type="password"
                         name="password"
                         id="passwordInput"
                         class="form-input has-toggle"
                         placeholder="Masukkan password"
                         required>

                  <button type="button"
                          class="toggle-pw-btn"
                          id="togglePw"
                          title="Tampilkan password">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                  </button>
                </div>
                @error('password')
                  <div class="text-danger mt-1" style="color:#c0392b;font-size:0.8rem;">{{ $message }}</div>
                @enderror
              </div>

              <!-- Input hidden untuk menyimpan token reCAPTCHA v3 (diisi lewat JS di bawah) -->
              @if (config('services.recaptcha.site_key'))
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
              @endif

              <!-- Widget reCAPTCHA v2 ("saya bukan robot") -- disembunyikan
                   otomatis kalau diakses dari localhost, lihat catatan di
                   atas dekat <script recaptcha.net> -->
              @if (config('services.no_captcha.site_key') && ! \App\Support\Recaptcha::isLocalHost())
                <div class="g-recaptcha my-3" data-sitekey="{{ config('services.no_captcha.site_key') }}"></div>
              @endif

              @error('g-recaptcha-response')
                <div class="text-danger mt-2" style="color:#c0392b;font-size:0.8rem;">
                  {{ $message }}
                </div>
              @enderror

              <hr class="form-divider">

              <!-- Tombol Submit -->
              <button type="submit" class="btn-submit" id="loginButton">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
              </button>

              <!-- Teks Pemberitahuan reCAPTCHA (wajib karena badge bawaan disembunyikan) -->
              @if (config('services.recaptcha.site_key'))
                <div class="recaptcha-info">
                  <span>Protected by reCAPTCHA</span>
                  -
                  <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy</a>
                  -
                  <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms</a>
                </div>
              @endif
            </form>

            @if (config('services.recaptcha.site_key'))
              <style>
                /* Sembunyikan badge bawaan Google yang melayang di pojok kanan bawah —
                   karena sudah ada teks kreditnya sendiri di .recaptcha-info di atas. */
                .grecaptcha-badge { visibility: hidden !important; }
              </style>

              <script>
              // Catatan penting: server (UserController::login) TIDAK memvalidasi
              // g-recaptcha-response sama sekali kalau RECAPTCHA_SECRET_KEY belum
              // diisi — token ini murni informatif dalam kondisi itu. Dulu di versi
              // lama, kalau grecaptcha gagal load/execute (site key belum
              // di-whitelist untuk domain yang dipakai, koneksi ke Google diblokir
              // firewall/ad-blocker, dll), form INI TERKUNCI TOTAL: event.preventDefault()
              // sudah terlanjur jalan lalu promise-nya gagal, jadi submit tidak pernah
              // terjadi — user cuma lihat "tidak bisa login" tanpa request pernah
              // sampai ke server. Sekarang: kalau recaptcha gagal dengan cara apa pun,
              // form tetap dikirim sebagai fallback.
              (function () {
                  const form = document.getElementById('loginForm');
                  const button = document.getElementById('loginButton');
                  const siteKey = @json(config('services.recaptcha.site_key'));

                  function submitWithoutRecaptcha() {
                      if (button) button.disabled = false;
                      form.submit();
                  }

                  if (typeof grecaptcha === 'undefined') {
                      // Script reCAPTCHA gagal dimuat sama sekali — biarkan form
                      // submit normal (native), jangan pasang handler apa pun.
                      console.warn('reCAPTCHA tidak termuat, lanjut tanpa verifikasi.');
                      return;
                  }

                  grecaptcha.ready(function () {
                      form.addEventListener('submit', function (event) {
                          event.preventDefault(); // hentikan submit bawaan sementara

                          if (button) button.disabled = true; // cegah klik dobel

                          grecaptcha.execute(siteKey, { action: 'login' }).then(function (token) {
                              document.getElementById('g-recaptcha-response').value = token;
                              form.submit();
                          }).catch(function (error) {
                              console.error('reCAPTCHA error, lanjut submit tanpa token:', error);
                              submitWithoutRecaptcha();
                          });
                      });
                  });
              })();
              </script>
            @endif
          </div>
          <!-- /card-form -->

        </div>
      </div>

      {{-- Widget "Lacak Dus Arsip" tertanam langsung, TANPA perlu login --
           sama partial yang dipakai di bawah form login panel Filament
           (admin/login), lihat resources/views/filament/auth/lacak-dus-link.blade.php.
           Dibungkus kartu putih sendiri (meniru .login-card) supaya
           standalone => true tidak dobel border sama kartu login di atas. --}}
      <div class="login-wrapper" style="margin-top: 1.5rem;">
        <div class="login-card">
          @include('filament.auth.lacak-dus-link', ['standalone' => true])
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════════
     FEATURES
════════════════════════════════════════════════ -->
<section class="features-section">
  <div class="features-inner">
    <div class="features-header">
      <div class="section-eyebrow">Mengapa SATU?</div>
      <h2 class="section-heading">Dirancang untuk Kemudahan</h2>
      <p class="section-sub">Satu sistem, tiga modul utama, semua kebutuhan arsip terpenuhi.</p>
    </div>
    <div class="features-grid">
      <div class="feat-card feat-c1">
        <div class="feat-icon feat-icon-blue"><i class="bi bi-lightning-charge-fill"></i></div>
        <div class="feat-title">Efisien</div>
        <p class="feat-desc">Pencatatan dan pengelolaan arsip yang cepat. Tidak perlu formulir kertas — semua dilakukan digital dari satu dasbor.</p>
      </div>
      <div class="feat-card feat-c2">
        <div class="feat-icon feat-icon-green"><i class="bi bi-layout-text-window-reverse"></i></div>
        <div class="feat-title">Simple</div>
        <p class="feat-desc">Antarmuka bersih dan navigasi yang intuitif. Staf dapat langsung bekerja tanpa perlu pelatihan teknis panjang.</p>
      </div>
      <div class="feat-card feat-c3">
        <div class="feat-icon feat-icon-amber"><i class="bi bi-diagram-3-fill"></i></div>
        <div class="feat-title">Terpadu</div>
        <p class="feat-desc">Arsip inaktif, musnah, dan statis dikelola dalam satu platform dengan alur kerja yang terintegrasi dan konsisten.</p>
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════
     ABOUT
════════════════════════════════════════════════ -->
<section class="about-section" id="about">
  <div class="about-inner">

    <!-- Text -->
    <div class="about-text">
      <div class="section-eyebrow">Tentang Kami</div>
      <h2 class="section-heading">ARPUS Cilacap</h2>
      <div class="about-body">
        <p>
          Perpustakaan di Kabupaten Cilacap merupakan perpustakaan yang berada di
          lingkungan Sekretariat Daerah Kabupaten Cilacap. Sesuai PERDA No. 2 tahun 1998
          tepatnya tanggal 28 Oktober 1998, Kantor Perpustakaan Daerah Cilacap
          beralamatkan di Jl. Jend. Sudirman No. 12 Cilacap.
        </p>
        <p>
          Sesuai PERDA No. 31 tahun 2004 berganti menjadi Kantor Arsip dan Perpusda
          Cilacap. Dalam sejarahnya telah terjadi dinamika pengorganisasian, namun tidak
          merubah fungsi perpustakaan dan kearsipan itu sendiri.
        </p>
        <p>
          Aplikasi SATU dikembangkan sebagai bagian dari transformasi digital layanan
          kearsipan untuk mendukung tata kelola pemerintahan yang lebih transparan,
          terstruktur, dan akuntabel.
        </p>
      </div>
      <div class="about-socials">
        <a href="#" class="social-btn"><i class="bi bi-twitter-x"></i> Twitter</a>
        <a href="#" class="social-btn"><i class="bi bi-linkedin"></i> LinkedIn</a>
        <a href="#" class="social-btn"><i class="bi bi-github"></i> GitHub</a>
        <a href="#" class="social-btn"><i class="bi bi-globe2"></i> Website</a>
      </div>
    </div>

  </div>
</section>

@include('layouts.footer_depan')

<script>
  // Toggle show/hide password
  const pwInput = document.getElementById('passwordInput');
  const toggleBtn = document.getElementById('togglePw');
  const toggleIcon = document.getElementById('toggleIcon');

  toggleBtn.addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type = isHidden ? 'text' : 'password';
    toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    toggleBtn.title = isHidden ? 'Sembunyikan password' : 'Tampilkan password';
  });
</script>
@endsection
