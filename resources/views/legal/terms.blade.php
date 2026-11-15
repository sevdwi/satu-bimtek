@extends('layouts.head_depan')

@section('title', 'Syarat & Ketentuan Penggunaan (Terms of Service) — SATU ARPUS Cilacap')
@section('meta_description', 'Syarat dan Ketentuan Penggunaan Layanan Platform SATU Web dan Aplikasi SATU Mobile Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap.')

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
      <span>Syarat &amp; Ketentuan</span>
    </div>

    <div class="legal-title-wrap">
      <div class="legal-badge-pill">
        <i class="bi bi-file-earmark-check"></i>
        Pedoman Ketentuan Layanan
      </div>
      <h1>Syarat &amp; Ketentuan Penggunaan</h1>
      <p class="legal-header-desc">
        Ketentuan dan aturan penggunaan platform <strong>SATU</strong> (Sistem Informasi Kearsipan Terpadu) berbasis web serta aplikasi seluler <strong>SATU Mobile</strong> di lingkungan Pemerintah Kabupaten Cilacap.
      </p>

      <div class="legal-nav-tabs">
        <a href="{{ route('legal.privacy-policy') }}" class="legal-tab-btn">
          <i class="bi bi-shield-lock"></i> Kebijakan Privasi
        </a>
        <a href="{{ route('legal.terms') }}" class="legal-tab-btn active">
          <i class="bi bi-file-text-fill"></i> Syarat &amp; Ketentuan
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
          <li><a href="#sec-1"><span class="toc-num">1</span> Ketentuan Umum &amp; Definisi</a></li>
          <li><a href="#sec-2"><span class="toc-num">2</span> Ruang Lingkup Layanan</a></li>
          <li><a href="#sec-3"><span class="toc-num">3</span> Akun &amp; Hak Akses Kedinasan</a></li>
          <li><a href="#sec-4"><span class="toc-num">4</span> Kewajiban &amp; Kode Etik</a></li>
          <li><a href="#sec-5"><span class="toc-num">5</span> Hak Kekayaan Intelektual</a></li>
          <li><a href="#sec-6"><span class="toc-num">6</span> Penggunaan Pemindai QR</a></li>
          <li><a href="#sec-7"><span class="toc-num">7</span> Batasan Tanggung Jawab</a></li>
          <li><a href="#sec-8"><span class="toc-num">8</span> Penangguhan &amp; Pemutusan</a></li>
          <li><a href="#sec-9"><span class="toc-num">9</span> Hukum yang Mengatur</a></li>
          <li><a href="#sec-10"><span class="toc-num">10</span> Kontak &amp; Dukungan</a></li>
        </ul>
      </div>

      <div class="legal-quick-info">
        <h4><i class="bi bi-info-circle-fill" style="color:var(--navy-700);"></i> Rangkuman Penting</h4>
        <p style="margin:0 0 10px;">
          • Pengguna wajib menjaga kerahasiaan NIP dan kata sandi.<br>
          • Seluruh data arsip adalah dokumen kedinasan resmi Pemkab Cilacap.<br>
          • Penyalahgunaan sistem dapat dikenakan sanksi sesuai disiplin ASN &amp; UU ITE.
        </p>
      </div>
    </aside>

    <!-- Main Content Document -->
    <article class="legal-content-card">

      <div class="legal-meta-summary">
        <div class="meta-item">
          <span class="meta-label">Penerbit Layanan</span>
          <span class="meta-value">Disarpus Kab. Cilacap</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Cakupan Platform</span>
          <span class="meta-value">Web &amp; SATU Mobile</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Status Dokumen</span>
          <span class="meta-value">Resmi &amp; Mengikat</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Tanggal Pembaruan</span>
          <span class="meta-value">28 Oktober 2026</span>
        </div>
      </div>

      <!-- SECTION 1 -->
      <section id="sec-1" class="legal-section">
        <h2><span class="sec-badge">1</span> Ketentuan Umum &amp; Definisi</h2>
        <p>
          Dokumen Syarat dan Ketentuan ini merupakan perjanjian yang mengikat secara hukum antara Anda ("Pengguna") dan <strong>Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap</strong> ("Pengelola" atau "Disarpus") mengenai tata cara akses dan pemanfaatan sistem <strong>SATU</strong>.
        </p>
        <p>Dalam Syarat dan Ketentuan ini, istilah-istilah di bawah memiliki arti sebagai berikut:</p>
        <ul>
          <li><strong>SATU:</strong> Sistem Informasi Kearsipan Terpadu, platform otomasi tata kelola arsip dinamis inaktif, pemusnahan, dan arsip statis milik Pemerintah Kabupaten Cilacap.</li>
          <li><strong>SATU Mobile:</strong> Aplikasi berbasis Android (<code>com.arpus.satumobile</code>) yang dirancang untuk membantu mobilitas petugas dalam inventarisasi, pencarian, dan pemindaian fisik dus arsip menggunakan kode QR.</li>
          <li><strong>Dus / Box Arsip:</strong> Wadah penyimpanan fisik berstandar kearsipan yang memuat sekumpulan berkas arsip yang teridentifikasi melalui kode barcode/QR unik dan nomor rak penyimpanan.</li>
          <li><strong>Pengguna Terdaftar:</strong> Pegawai Negeri Sipil (PNS), PPPK, atau staf aparatur di lingkungan Pemerintah Kabupaten Cilacap yang telah diverifikasi dan memiliki akun kredensial (NIP dan kata sandi).</li>
        </ul>
      </section>

      <!-- SECTION 2 -->
      <section id="sec-2" class="legal-section">
        <h2><span class="sec-badge">2</span> Ruang Lingkup Layanan</h2>
        <p>Sistem SATU menyediakan fitur-fitur kearsipan terintegrasi, antara lain:</p>
        <ol>
          <li>Pencatatan dan klasifikasi arsip inaktif berdasarkan Kode Klasifikasi Kearsipan resmi;</li>
          <li>Pemetaan lokasi fisik dus arsip di ruang depo (Gedung, Ruang, Baris, Rak, Tingkat, Box);</li>
          <li>Pemindaian kode QR pada dus fisik secara cepat lewat kamera ponsel (SATU Mobile);</li>
          <li>Pencarian status dan ringkasan berkas arsip secara transparan;</li>
          <li>Pengelolaan alur retensi, pemindahan (manufer), usul musnah, dan penyerahan arsip statis ke Lembaga Kearsipan Daerah (LKD).</li>
        </ol>
      </section>

      <!-- SECTION 3 -->
      <section id="sec-3" class="legal-section">
        <h2><span class="sec-badge">3</span> Akun Kedinasan &amp; Hak Akses</h2>
        <p>
          Untuk mengakses fitur-fitur manajerial arsip, pengguna harus masuk menggunakan kredensial resmi. Ketentuan akun meliputi:
        </p>
        <ul>
          <li><strong>Kerahasiaan Akun:</strong> Anda bertanggung jawab penuh untuk menjaga kerahasiaan Nomor Induk Pegawai (NIP), kata sandi, dan token akses Anda. Dilarang keras meminjamkan akun kepada pihak lain yang tidak berhak.</li>
          <li><strong>Kebenaran Data:</strong> Anda wajib memastikan identitas dan data yang terdaftar adalah benar sesuai Surat Keputusan atau penugasan kedinasan yang sah.</li>
          <li><strong>Notifikasi Keamanan:</strong> Apabila Anda menduga adanya penyalahgunaan akun atau akses tidak sah, Anda wajib segera melapor kepada Administrator Disarpus Cilacap untuk dilakukan pemblokiran sesi.</li>
        </ul>
      </section>

      <!-- SECTION 4 -->
      <section id="sec-4" class="legal-section">
        <h2><span class="sec-badge">4</span> Kewajiban &amp; Kode Etik Pengguna</h2>
        <p>Selama menggunakan SATU Web maupun SATU Mobile, Pengguna dilarang:</p>
        <ul>
          <li>Mengunggah data arsip palsu, rekayasa, atau dokumen yang bertentangan dengan peraturan perundang-undangan;</li>
          <li>Menghapus atau merusak label barcode/QR fisik dus arsip yang terpasang di depo penyimpanan;</li>
          <li>Mencoba meretas, menembus otentikasi (bypass), melakukan eksploitasi kerentanan keamanan, atau melancarkan serangan Denial of Service (DoS) ke server SATU;</li>
          <li>Menyalin, mendistribusikan, atau menyebarluaskan dokumen atau arsip yang bertaraf <strong>Rahasia</strong> atau <strong>Sangat Rahasia</strong> kepada pihak luar tanpa persetujuan Pejabat Pengelola Informasi dan Dokumentasi (PPID) atau pimpinan instansi terkait.</li>
        </ul>
      </section>

      <!-- SECTION 5 -->
      <section id="sec-5" class="legal-section">
        <h2><span class="sec-badge">5</span> Hak Kekayaan Intelektual</h2>
        <p>
          Seluruh hak kekayaan intelektual atas sistem SATU — termasuk namun tidak terbatas pada kode sumber, algoritma aplikasi, tampilan antarmuka (UI/UX), logo kearsipan Cilacap, ikon, grafik, dan dokumentasi teknis — adalah hak milik tunggal <strong>Pemerintah Kabupaten Cilacap</strong> yang dilindungi oleh Undang-Undang Hak Cipta Republik Indonesia.
        </p>
        <p>
          Pengguna tidak diberikan hak untuk menjual, merekayasa balik (reverse engineering), membongkar kode sumber, atau mendistribusikan ulang aplikasi SATU untuk kepentingan komersial pribadi.
        </p>
      </section>

      <!-- SECTION 6 -->
      <section id="sec-6" class="legal-section">
        <h2><span class="sec-badge">6</span> Penggunaan Pemindai Kode QR (SATU Mobile)</h2>
        <div class="legal-alert legal-alert-amber">
          <i class="bi bi-qr-code-scan legal-alert-icon"></i>
          <div>
            <strong>Pemanfaatan Fitur Scanner</strong>
            <br>
            Fitur pemindai QR code pada aplikasi SATU Mobile ditujukan khusus untuk memudahkan verifikasi isi dus arsip fisik di lokasi penyimpanan/depo. Pengguna bertanggung jawab memastikan kode QR yang dipindai adalah kode resmi yang diterbitkan oleh sistem SATU.
          </div>
        </div>
      </section>

      <!-- SECTION 7 -->
      <section id="sec-7" class="legal-section">
        <h2><span class="sec-badge">7</span> Batasan Tanggung Jawab (Disclaimer)</h2>
        <p>
          Aplikasi dan layanan SATU disediakan berdasarkan prinsip "sebagaimana adanya" (<em>as is</em>) dan "sebagaimana tersedia" (<em>as available</em>). Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap senantiasa berupaya menjaga keandalan dan ketersediaan sistem, namun tidak menjamin bahwa layanan akan bebas dari interupsi sementara akibat pemeliharaan jaringan, peningkatan infrastruktur pusat data, atau kejadian di luar kendali yang wajar (<em>force majeure</em>).
        </p>
      </section>

      <!-- SECTION 8 -->
      <section id="sec-8" class="legal-section">
        <h2><span class="sec-badge">8</span> Penangguhan &amp; Pemutusan Hak Akses</h2>
        <p>
          Disarpus Cilacap berhak menangguhkan atau mencabut hak akses pengguna secara sepihak apabila:
        </p>
        <ul>
          <li>Terbukti melakukan pelanggaran terhadap Syarat dan Ketentuan ini atau Kebijakan Privasi;</li>
          <li>Pegawai bersangkutan telah mengalami mutasi keluar dari unit pengolah kearsipan, pensiun, atau diberhentikan;</li>
          <li>Ditemukan indikasi anomali keamanan jaringan yang membahayakan integritas basis data kearsipan daerah.</li>
        </ul>
      </section>

      <!-- SECTION 9 -->
      <section id="sec-9" class="legal-section">
        <h2><span class="sec-badge">9</span> Hukum yang Mengatur &amp; Yurisdiksi</h2>
        <p>
          Syarat dan Ketentuan ini diatur, ditafsirkan, dan tunduk pada hukum Negara Kesatuan Republik Indonesia. Setiap perselisihan yang timbul dari atau sehubungan dengan penggunaan sistem SATU akan diselesaikan terlebih dahulu melalui musyawarah kedinasan, dan bila tidak tercapai kesepakatan akan diselesaikan melalui yurisdiksi Pengadilan Negeri di wilayah hukum Kabupaten Cilacap.
        </p>
      </section>

      <!-- SECTION 10 -->
      <section id="sec-10" class="legal-section">
        <h2><span class="sec-badge">10</span> Kontak Resmi Pengelola</h2>
        <p>
          Untuk pertanyaan teknis, pelaporan kendala, atau permohonan bantuan terkait Syarat dan Ketentuan ini, silakan hubungi:
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