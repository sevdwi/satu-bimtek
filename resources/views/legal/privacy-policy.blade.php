@extends('layouts.head_depan')

@section('title', 'Kebijakan Privasi (Privacy Policy) — SATU ARPUS Cilacap')
@section('meta_description', 'Kebijakan Privasi dan Pelindungan Data Aplikasi SATU (Sistem Informasi Kearsipan Terpadu) & SATU Mobile Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap.')

@section('content')
<link rel="stylesheet" href="{{ asset('css/legal.css') }}">

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
        <span class="nav-brand-sub">Sistem Informasi Kearsipan Terpadu</span>
      </div>
    </a>

    <div style="display:flex;align-items:center;gap:1rem;margin-left:auto;">
      <a href="{{ route('welcome') }}" style="color:#fff;text-decoration:none;font-size:0.9rem;font-weight:600;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-house-door"></i> Beranda
      </a>
      <a href="{{ route('dus-arsip.lacak') }}" style="color:#fff;text-decoration:none;font-size:0.9rem;font-weight:600;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-qr-code-scan"></i> Lacak Dus
      </a>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════════════════════
     HEADER / BANNER
════════════════════════════════════════════════ -->
<header class="legal-header">
  <div class="legal-header-inner">
    <div class="legal-breadcrumb">
      <a href="{{ route('welcome') }}"><i class="bi bi-house-door"></i> Beranda</a>
      <span class="legal-breadcrumb-sep">/</span>
      <span>Kebijakan Privasi</span>
    </div>

    <div class="legal-title-wrap">
      <div class="legal-badge-pill">
        <i class="bi bi-shield-check"></i>
        Dokumen Resmi Pelindungan Data
      </div>
      <h1>Kebijakan Privasi (Privacy Policy)</h1>
      <p class="legal-header-desc">
        Pemberitahuan privasi ini menjelaskan bagaimana Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap mengumpulkan, menggunakan, menyimpan, dan melindungi data Anda saat mengakses platform web <strong>SATU</strong> serta aplikasi <strong>SATU Mobile</strong>.
      </p>

      <div class="legal-nav-tabs">
        <a href="{{ route('legal.privacy-policy') }}" class="legal-tab-btn active">
          <i class="bi bi-shield-lock-fill"></i> Kebijakan Privasi
        </a>
        <a href="{{ route('legal.terms') }}" class="legal-tab-btn">
          <i class="bi bi-file-text"></i> Syarat &amp; Ketentuan
        </a>
      </div>
    </div>
  </div>
</header>

<!-- ═══════════════════════════════════════════════
     BODY & CONTENT
════════════════════════════════════════════════ -->
<main class="legal-body">
  <div class="legal-layout">

    <!-- Sidebar Sticky Navigation -->
    <aside class="legal-sidebar">
      <div class="legal-toc-card">
        <div class="legal-toc-header">
          <i class="bi bi-list-nested"></i> Daftar Isi
        </div>
        <ul class="legal-toc-list">
          <li><a href="#sec-1"><span class="toc-num">1</span> Pendahuluan &amp; Identitas</a></li>
          <li><a href="#sec-2"><span class="toc-num">2</span> Izin Perangkat (Camera dll)</a></li>
          <li><a href="#sec-3"><span class="toc-num">3</span> Data yang Kami Kumpulkan</a></li>
          <li><a href="#sec-4"><span class="toc-num">4</span> Tujuan Penggunaan Data</a></li>
          <li><a href="#sec-5"><span class="toc-num">5</span> Keamanan &amp; Enkripsi</a></li>
          <li><a href="#sec-6"><span class="toc-num">6</span> Pihak Ketiga &amp; Iklan</a></li>
          <li><a href="#sec-7"><span class="toc-num">7</span> Retensi &amp; Hapus Akun</a></li>
          <li><a href="#sec-8"><span class="toc-num">8</span> Privasi Anak-Anak</a></li>
          <li><a href="#sec-9"><span class="toc-num">9</span> Dasar Hukum Kearsipan</a></li>
          <li><a href="#sec-10"><span class="toc-num">10</span> Kontak &amp; Pengaduan</a></li>
        </ul>
      </div>

      <div class="legal-quick-info">
        <h4><i class="bi bi-info-circle-fill" style="color:var(--navy-700);"></i> Rangkuman Cepat</h4>
        <p style="margin:0 0 10px;">
          • Aplikasi ditujukan khusus untuk keperluan kedinasan kearsipan Pemkab Cilacap.<br>
          • Izin kamera <strong>hanya</strong> untuk memindai QR code box/dus arsip.<br>
          • <strong>Bebas iklan</strong> &amp; tidak ada data yang dijual ke pihak komersial.
        </p>
      </div>
    </aside>

    <!-- Main Content Document -->
    <article class="legal-content-card">

      <div class="legal-meta-summary">
        <div class="meta-item">
          <span class="meta-label">Nama Aplikasi</span>
          <span class="meta-value">SATU / SATU Mobile</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">ID Paket (Package Name)</span>
          <span class="meta-value">com.arpus.satumobile</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Pengembang / Pengelola</span>
          <span class="meta-value">Disarpus Kab. Cilacap</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Tanggal Efektif</span>
          <span class="meta-value">28 Oktober 2026</span>
        </div>
      </div>

      <!-- SECTION 1 -->
      <section id="sec-1" class="legal-section">
        <h2><span class="sec-badge">1</span> Pendahuluan &amp; Identitas Pengelola</h2>
        <p>
          Selamat datang di <strong>SATU</strong> (Sistem Informasi Kearsipan Terpadu), platform resmi pengelolaan kearsipan yang dikembangkan dan dikelola oleh <strong>Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap</strong>, Pemerintah Kabupaten Cilacap, Jawa Tengah ("kami").
        </p>
        <p>
          Kebijakan Privasi ini mengatur bagaimana platform SATU versi Web (<code>http://satu.arpus.cilacapkab.go.id</code>) dan aplikasi seluler <strong>SATU Mobile</strong> (<code>com.arpus.satumobile</code>) mengumpulkan, memproses, menyimpan, dan menjaga kerahasiaan informasi yang diberikan oleh pengguna ("Anda"), baik pegawai pemerintah daerah, arsiparis, pengelola arsip Organisasi Perangkat Daerah (OPD), maupun masyarakat yang menggunakan fasilitas pelacakan publik.
        </p>
        <p>
          Dengan mengunduh, memasang, mendaftar, atau menggunakan aplikasi SATU, Anda menyetujui ketentuan pemrosesan data sebagaimana diuraikan dalam dokumen ini.
        </p>
      </section>

      <!-- SECTION 2 -->
      <section id="sec-2" class="legal-section">
        <h2><span class="sec-badge">2</span> Izin Perangkat (Device Permissions)</h2>
        <p>
          Untuk menjalankan fungsinya secara optimal, aplikasi SATU Mobile meminta persetujuan terhadap izin perangkat berikut:
        </p>

        <div class="legal-alert legal-alert-amber">
          <i class="bi bi-camera-fill legal-alert-icon"></i>
          <div>
            <strong>Izin Kamera (<code>android.permission.CAMERA</code>)</strong>
            <br>
            Izin ini digunakan <strong>semata-mata untuk memindai (scan) kode QR atau barcode fisik</strong> yang tertempel pada Dus/Box Arsip dan berkas arsip. 
            <ul style="margin:6px 0 0;padding-left:18px;">
              <li>Aplikasi <strong>tidak</strong> mengambil foto pribadi, tidak merekam video di latar belakang, dan tidak mengakses galeri media Anda.</li>
              <li>Citra kamera diproses secara lokal di memori perangkat secara instan saat pemindaian dan <strong>tidak diunggah</strong> sebagai file gambar ke server.</li>
            </ul>
          </div>
        </div>

        <div class="legal-alert legal-alert-blue">
          <i class="bi bi-wifi legal-alert-icon"></i>
          <div>
            <strong>Akses Jaringan &amp; Internet (<code>android.permission.INTERNET</code> &amp; <code>ACCESS_NETWORK_STATE</code>)</strong>
            <br>
            Izin ini diperlukan untuk melakukan komunikasi data antara aplikasi di perangkat seluler dengan server basis data resmi kearsipan Pemerintah Kabupaten Cilacap guna memverifikasi akun pengguna dan memuat detail data dus arsip.
          </div>
        </div>
      </section>

      <!-- SECTION 3 -->
      <section id="sec-3" class="legal-section">
        <h2><span class="sec-badge">3</span> Data yang Kami Kumpulkan</h2>
        <p>
          Kami membatasi pengumpulan data hanya pada parameter yang relevan dan dibutuhkan untuk kelancaran tugas tata kelola kearsipan:
        </p>

        <div class="legal-table-wrap">
          <table class="legal-table">
            <thead>
              <tr>
                <th style="width:28%;">Kategori Data</th>
                <th style="width:36%;">Jenis Data yang Dikumpulkan</th>
                <th style="width:36%;">Sumber &amp; Keperluan</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Data Identitas &amp; Akun</strong></td>
                <td>Nomor Induk Pegawai (NIP), Nama Lengkap Staf, Alamat Email Kedinasan, Unit Kerja / OPD, dan Kata Sandi (tersimpan dalam bentuk hash terenkripsi).</td>
                <td>Diberikan saat pendaftaran oleh administrator kearsipan OPD untuk keperluan otentikasi login kedinasan.</td>
              </tr>
              <tr>
                <td><strong>Token Sesi Perangkat</strong></td>
                <td>Token otentikasi sesi (Bearer Token) dan preferensi aplikasi lokal.</td>
                <td>Disimpan di penyimpanan terproteksi perangkat (AsyncStorage/Encrypted Storage) agar pengguna tidak perlu login berulang saat bertugas.</td>
              </tr>
              <tr>
                <td><strong>Data Log Kearsipan</strong></td>
                <td>Riwayat pencarian/pemindaian dus arsip, nomor berkas yang diakses, stempel waktu (timestamp), dan riwayat mutasi/manufer arsip.</td>
                <td>Dihasilkan secara otomatis oleh sistem untuk kebutuhan audit trail dan akuntabilitas pengelolaan arsip pemerintah.</td>
              </tr>
              <tr>
                <td><strong>Informasi Teknis Perangkat</strong></td>
                <td>Versi sistem operasi Android, model perangkat, dan status koneksi jaringan.</td>
                <td>Digunakan untuk mendiagnosis kendala teknis dan menjamin kompatibilitas aplikasi.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p>
          <strong>Data yang TIDAK Kami Kumpulkan:</strong> Kami <em>tidak mengumpulkan</em> data pelacakan lokasi presisi (GPS), buku telepon/kontak, pesan teks/SMS, rekaman mikrofon suara, atau data keuangan/perbankan.
        </p>
      </section>

      <!-- SECTION 4 -->
      <section id="sec-4" class="legal-section">
        <h2><span class="sec-badge">4</span> Tujuan Penggunaan Data</h2>
        <p>Data yang dikumpulkan dipergunakan secara eksklusif untuk tujuan-tujuan berikut:</p>
        <ol>
          <li><strong>Manajemen Siklus Arsip:</strong> Memfasilitasi pendataan, penelusuran lokasi rak fisik, pemindahan (manufer), peminjaman, serta penyusutan arsip inaktif, statis, dan musnah.</li>
          <li><strong>Otentikasi &amp; Kontrol Hak Akses:</strong> Memastikan hanya pegawai yang berwenang dari OPD terkait atau Dinas Kearsipan dan Perpustakaan yang dapat mengakses informasi arsip sensitif.</li>
          <li><strong>Pelayanan Publik Cepat:</strong> Memberikan kemudahan bagi petugas lapangan untuk mengenali isi dus arsip secara instan melalui pemindaian kode QR.</li>
          <li><strong>Keamanan Sistem &amp; Audit Trail:</strong> Memenuhi standar kepatuhan tata kelola pemerintahan yang baik (Good Governance) dengan mencatat jejak aktivitas administrasi arsip.</li>
        </ol>
      </section>

      <!-- SECTION 5 -->
      <section id="sec-5" class="legal-section">
        <h2><span class="sec-badge">5</span> Keamanan &amp; Perlindungan Data</h2>
        <p>
          Kami menaruh prioritas tertinggi pada pelindungan data dan berkas kearsipan daerah. Langkah-langkah pengamanan yang diterapkan meliputi:
        </p>
        <ul>
          <li><strong>Enkripsi Lalu Lintas Data:</strong> Semua pertukaran data antara aplikasi mobile dan server menggunakan protokol terenkripsi (HTTPS/TLS) untuk mencegah intersepsi pihak ketiga.</li>
          <li><strong>Penyimpanan Kata Sandi:</strong> Kata sandi pengguna tidak disimpan dalam bentuk teks biasa (plain text), melainkan di-hash menggunakan algoritma kriptografi satu arah (Bcrypt).</li>
          <li><strong>Pembatasan Hak Akses (RBAC):</strong> Hak akses dibatasi secara ketat berdasarkan peran pengguna (Superadmin, Admin Dinas, Pengolah OPD).</li>
          <li><strong>Proteksi Sesi:</strong> Token autentikasi otomatis divalidasi dan dapat dicabut sewaktu-waktu oleh administrator bila terindikasi adanya akses mencurigakan.</li>
        </ul>
      </section>

      <!-- SECTION 6 -->
      <section id="sec-6" class="legal-section">
        <h2><span class="sec-badge">6</span> Kebijakan Pihak Ketiga &amp; Iklan</h2>
        <div class="legal-alert legal-alert-green">
          <i class="bi bi-shield-fill-check legal-alert-icon"></i>
          <div>
            <strong>Komitmen Bebas Iklan &amp; Tanpa Monetisasi Data Komersial</strong>
            <br>
            Aplikasi SATU dikembangkan sepenuhnya sebagai layanan publik pemerintah daerah. Aplikasi ini <strong>tidak mengandung iklan pihak ketiga</strong> (ad networks), tidak menyematkan tracker periklanan komersial, dan <strong>tidak pernah menjual, menyewakan, atau memperdagangkan data pengguna</strong> kepada pihak komersial mana pun.
          </div>
        </div>
        <p>
          Data hanya dapat diakses oleh pihak internal Pemerintah Kabupaten Cilacap atau instansi pengawas berwenang sesuai dengan peraturan perundang-undangan kearsipan yang berlaku di Republik Indonesia.
        </p>
      </section>

      <!-- SECTION 7 -->
      <section id="sec-7" class="legal-section">
        <h2><span class="sec-badge">7</span> Retensi &amp; Permohonan Penghapusan Akun/Data</h2>
        <p>
          <strong>Retensi Data:</strong> Data berkas kearsipan disimpan dan dikelola mengikuti Jadwal Retensi Arsip (JRA) yang ditetapkan oleh peraturan perundang-undangan dan Keputusan Bupati Cilacap.
        </p>
        <p>
          <strong>Penghapusan Akun &amp; Data Pribadi (Data Deletion Request):</strong>
        </p>
        <p>
          Sesuai kebijakan Google Play Store dan Undang-Undang Pelindungan Data Pribadi:
        </p>
        <ul>
          <li>Pegawai atau staf yang dimutasi, purna tugas, atau tidak lagi memerlukan akses ke SATU dapat mengajukan permohonan penonaktifan akun dan penghapusan kredensial login kepada Administrator Disarpus Cilacap.</li>
          <li>Untuk mengajukan permohonan penghapusan akun atau koreksi data pribadi, silakan kirimkan email resmi kedinasan ke <a href="mailto:disarpus@cilacapkab.go.id" style="color:var(--navy-700);font-weight:700;">disarpus@cilacapkab.go.id</a> dengan subjek <em>"Permohonan Penghapusan Akun SATU - [Nama/NIP]"</em>.</li>
          <li>Tim Administrator akan memproses penonaktifan akun dalam waktu maksimal 3x24 jam kerja setelah verifikasi identitas kedinasan selesai.</li>
        </ul>
      </section>

      <!-- SECTION 8 -->
      <section id="sec-8" class="legal-section">
        <h2><span class="sec-badge">8</span> Privasi Anak-Anak</h2>
        <p>
          Layanan dan aplikasi SATU dirancang khusus untuk keperluan pekerjaan kedinasan aparatur sipil negara dan staf pengelola kearsipan perangkat daerah. Kami tidak secara sengaja mengumpulkan data identifikasi pribadi dari anak-anak di bawah usia 13 tahun (atau batas usia anak yang berlaku menurut hukum setempat). Jika ditemukan adanya data anak di bawah umur yang tersimpan tanpa persetujuan sah, kami akan segera mengambil langkah penghapusan.
        </p>
      </section>

      <!-- SECTION 9 -->
      <section id="sec-9" class="legal-section">
        <h2><span class="sec-badge">9</span> Dasar Hukum &amp; Kepatuhan Regulasi</h2>
        <p>Pengelolaan data dan kearsipan dalam sistem SATU tunduk pada:</p>
        <ul>
          <li>Undang-Undang Nomor 43 Tahun 2009 tentang Kearsipan;</li>
          <li>Undang-Undang Nomor 11 Tahun 2008 jo. UU Nomor 1 Tahun 2024 tentang Informasi dan Transaksi Elektronik (ITE);</li>
          <li>Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP);</li>
          <li>Peraturan Pemerintah Nomor 28 Tahun 2012 tentang Pelaksanaan UU No. 43 Tahun 2009 tentang Kearsipan;</li>
          <li>Peraturan Bupati Cilacap tentang Tata Kelola Kearsipan dan Sistem Pemerintahan Berbasis Elektronik (SPBE).</li>
        </ul>
      </section>

      <!-- SECTION 10 -->
      <section id="sec-10" class="legal-section">
        <h2><span class="sec-badge">10</span> Kontak Resmi &amp; Layanan Pengaduan</h2>
        <p>
          Apabila Anda memiliki pertanyaan, saran, atau keluhan seputar Kebijakan Privasi ini maupun pengelolaan data pada aplikasi SATU, silakan hubungi kami melalui:
        </p>

        <div style="background:var(--bg-soft);border:1px solid var(--border);border-radius:12px;padding:22px;margin-top:16px;">
          <h4 style="margin:0 0 12px;color:var(--navy-900);font-size:1.05rem;">
            <i class="bi bi-building"></i> Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap
          </h4>
          <p style="margin:0 0 8px;font-size:0.9rem;">
            <strong>Alamat:</strong> Jl. Jend. Sudirman No. 12, Kandang Macan, Sidanegara, Kec. Cilacap Tengah, Kabupaten Cilacap, Jawa Tengah 53223
          </p>
          <p style="margin:0 0 8px;font-size:0.9rem;">
            <strong>Telepon:</strong> (0282) 534490
          </p>
          <p style="margin:0 0 8px;font-size:0.9rem;">
            <strong>Email Kedinasan:</strong> <a href="mailto:disarpus@cilacapkab.go.id" style="color:var(--navy-700);font-weight:600;">disarpus@cilacapkab.go.id</a>
          </p>
          <p style="margin:0;font-size:0.9rem;">
            <strong>Portal Kearsipan SATU:</strong> <a href="http://satu.arpus.cilacapkab.go.id" target="_blank" style="color:var(--navy-700);font-weight:600;">http://satu.arpus.cilacapkab.go.id</a>
          </p>
        </div>
      </section>

    </article>

  </div>
</main>

<!-- ═══════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ -->
@include('layouts.footer_depan')

@endsection