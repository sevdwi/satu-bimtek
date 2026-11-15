<footer class="site-footer">
  <div class="site-footer-inner">
    <div class="footer-top">

      <!-- Brand & Info -->
      <div class="footer-brand">
        <div class="footer-brand-logo">
          <img src="{{ asset('images/new-satu.png') }}" width="42" alt="Logo SATU">
          <div>
            <div class="footer-brand-title">SATU ARPUS</div>
            <div class="footer-brand-sub">Sistem Informasi Kearsipan Terpadu</div>
          </div>
        </div>
        <p>
          Platform digital tata kelola arsip inaktif, statis, dan pemusnahan arsip terintegrasi untuk mewujudkan tertib arsip di lingkungan Pemerintah Kabupaten Cilacap.
        </p>
      </div>

      <!-- Tautan Cepat -->
      <div class="footer-col">
        <h4>Navigasi</h4>
        <ul class="footer-links">
          <li>
            <a href="{{ route('welcome') }}">
              <i class="bi bi-chevron-right"></i> Beranda Publik
            </a>
          </li>
          <li>
            <a href="{{ route('dus-arsip.lacak') }}">
              <i class="bi bi-chevron-right"></i> Lacak Dus Arsip
            </a>
          </li>
          <li>
            <a href="{{ url('/admin/login') }}">
              <i class="bi bi-chevron-right"></i> Panel Admin Filament
            </a>
          </li>
        </ul>
      </div>

      <!-- Legalitas & Kebijakan -->
      <div class="footer-col">
        <h4>Kebijakan &amp; Legal</h4>
        <ul class="footer-links">
          <li>
            <a href="{{ route('legal.privacy-policy') }}">
              <i class="bi bi-shield-check"></i> Kebijakan Privasi (Privacy Policy)
            </a>
          </li>
          <li>
            <a href="{{ route('legal.terms') }}">
              <i class="bi bi-file-earmark-text"></i> Syarat &amp; Ketentuan (Terms)
            </a>
          </li>
          <li>
            <a href="http://satu.arpus.cilacapkab.go.id" target="_blank">
              <i class="bi bi-globe2"></i> Portal Kearsipan Cilacap
            </a>
          </li>
        </ul>
      </div>

    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom">
      <div>
        &copy; {{ date('Y') }} <strong>Dinas Kearsipan dan Perpustakaan Kabupaten Cilacap</strong>. Seluruh hak cipta dilindungi.
      </div>
      <div>
        Pemerintah Kabupaten Cilacap &bull; Jawa Tengah, Indonesia
      </div>
    </div>
  </div>
</footer>
