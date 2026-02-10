<?php
// SELALU MULAI SESSION DI BARIS PALING ATAS
session_start();

// Ambil nama file halaman saat ini
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="index.php" class="brand-link">
    <img src="dist/img/logo.jpeg" alt="freedom logo" class="brand-image img-circle elevation-3" style="opacity: .8;">
    <span class="brand-text font-weight-light">SPI BDG 2P</span>
  </a>

  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <?php
        // --- LOGIKA MENU BERDASARKAN ROLE ---

        // 1. ROLE: CHECKER
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'checker') :

          // Definisi variabel active KHUSUS Checker (Wajib ada biar gak error)
          $realisasi_active = ($current_page == 'realisasislp.php') ? 'active' : '';
          $sales_active     = ($current_page == 'salesdetail.php') ? 'active' : '';
        ?>

          <li class="nav-header">MENU CEKER</li>
          <li class="nav-item">
            <a href="realisasislp.php" class="nav-link <?php echo $realisasi_active; ?>">
              <i class="fas fa-barcode nav-icon"></i>
              <p>MONITORING SLP</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="salesdetail.php" class="nav-link <?php echo $sales_active; ?>">
              <i class="fas fa-barcode nav-icon"></i>
              <p>SALES DETAIL</p>
            </a>
          </li>

          <li class="nav-header">AKUN</li>
          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>

        <?php
        // 2. ROLE: MR (Medical Representative)
        elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'mr') :

          // Definisi variabel active KHUSUS MR (Wajib ada biar gak error)
          $target_mr_active = ($current_page == 'targetmr.php') ? 'active' : '';
          $member_active    = ($current_page == 'memberdetail.php') ? 'active' : '';
        ?>
          <li class="nav-header">MENU MR</li>
          <li class="nav-item">
            <a href="#" data-url="targetmr.php" class="nav-link confirm-link <?php echo $target_mr_active; ?>">
              <i class="fas fa-headset nav-icon"></i>
              <p>MONITORING MR</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" data-url="memberdetail.php" class="nav-link confirm-link <?php echo $member_active; ?>">
              <i class="far fa-address-card nav-icon"></i>
              <p>Detail Member</p>
            </a>
          </li>

          <li class="nav-header">AKUN</li>
          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>

        <?php
        // 3. ROLE: ADMIN (Default)
        else :

          // Definisi variabel active KHUSUS ADMIN
          // --- 1. SET AKTIF UNTUK MENU UTAMA (LEVEL 1) ---
          $dashboard_active = ($current_page == 'index.php') ? 'active' : '';

          $data_utama_pages = ['memberdetail.php', 'salesdetail.php'];
          $data_utama_active = in_array($current_page, $data_utama_pages) ? 'active' : '';
          $data_utama_open = in_array($current_page, $data_utama_pages) ? 'menu-open' : '';

          $edp_pages = ['cekpagi.php', 'monitoringoperasi.php', 'realisasislp.php', 'cek_data_struk.php', 'cekhjk.php'];
          $edp_page_active = in_array($current_page, $edp_pages) ? 'active' : '';
          $edp_page_open = in_array($current_page, $edp_pages) ? 'menu-open' : '';

          $simulasi_pages = ['monitoring_pareto.php', 'targetmr.php'];
          $simulasi_page_active = in_array($current_page, $simulasi_pages) ? 'active' : '';
          $simulasi_page_open = in_array($current_page, $simulasi_pages) ? 'menu-open' : '';

          $music_active = ($current_page == 'music.php') ? 'active' : '';

          // --- 2. SET AKTIF UNTUK SUB-MENU (LEVEL 2) ---
          $cek_pagi_active = ($current_page == 'cekpagi.php') ? 'active' : '';
          $monitoring_active = ($current_page == 'monitoringoperasi.php') ? 'active' : '';
          $target_mr_active = ($current_page == 'targetmr.php') ? 'active' : '';
          $struk_active = ($current_page == 'cek_data_struk.php') ? 'active' : '';
          $realisasi_active = ($current_page == 'realisasislp.php') ? 'active' : '';
          $member_active = ($current_page == 'memberdetail.php') ? 'active' : '';
          $DSI_SIMUL_active = ($current_page == 'monitoring_pareto.php') ? 'active' : '';
          $sales_active = ($current_page == 'salesdetail.php') ? 'active' : '';
          $hjk_active = ($current_page == 'cekhjk.php') ? 'active' : '';
        ?>

          <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $dashboard_active; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item has-treeview <?php echo $data_utama_open; ?>">
            <a href="#" class="nav-link <?php echo $data_utama_active; ?>">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Data Utama
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="memberdetail.php" class="nav-link confirm-link <?php echo $member_active; ?>">
                  <i class="far fa-address-card nav-icon"></i>
                  <p>Detail Member</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="salesdetail.php" class="nav-link confirm-link <?php echo $sales_active; ?>">
                  <i class="fas fa-wallet nav-icon"></i>
                  <p>Detail Sales Kasir</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $edp_page_open; ?>">
            <a href="#" class="nav-link <?php echo $edp_page_active; ?>">
              <i class="nav-icon fas fa-shield-alt"></i>
              <p>
                OPR EDP
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="cekpagi.php" class="nav-link confirm-link <?php echo $cek_pagi_active; ?>">
                  <i class="fas fa-wrench nav-icon"></i>
                  <p>CEK SETING</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="monitoringoperasi.php" class="nav-link confirm-link <?php echo $monitoring_active; ?>">
                  <i class="fas fa-cogs nav-icon"></i>
                  <p>MONITORING DB</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="realisasislp.php" class="nav-link confirm-link <?php echo $realisasi_active; ?>">
                  <i class="fas fa-barcode nav-icon"></i>
                  <p>MONITORING SLP</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="cek_data_struk.php" class="nav-link confirm-link <?php echo $struk_active; ?>">
                  <i class="fas fa-receipt nav-icon"></i>
                  <p>MONITORING STRUK</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="cekhjk.php" class="nav-link confirm-link <?php echo $hjk_active; ?>">
                  <i class="fas fa-star"></i>
                  <p>CEK ITEM HJK</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $simulasi_page_open; ?>">
            <a href="#" class="nav-link <?php echo $simulasi_page_active; ?>">
              <i class="nav-icon fa fa-robot"></i>
              <p>
                SIMULASI
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="monitoring_pareto.php" class="nav-link confirm-link <?php echo $DSI_SIMUL_active; ?>">
                  <i class="fas fa-flask nav-icon"></i>
                  <p>MONITORING PARETO</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="targetmr.php" class="nav-link confirm-link <?php echo $target_mr_active; ?>">
                  <i class="fas fa-headset nav-icon"></i>
                  <p>MONITORING MR</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="../spotifypremiumindogrosir/index.php" target="_blank" class="nav-link <?php echo $music_active; ?>">
              <i class="nav-icon fas fa-music"></i>
              <p>Music (SPI)</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../tarik_laporan/index.php" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-download"></i>
              <p>Tarik Data Laporan</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../test_ping/index.php" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-satellite-dish"></i>
              <p>Test Ping</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../FTP_HO_BY白竜7z/index.php" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-hdd"></i>
              <p>FTP HO</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../bot_wa/admin.html" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-bullhorn"></i>
              <p>WA BLAST</p>
            </a>
          </li>

        <?php
        endif; // Tutup IF utama
        ?>

      </ul>
    </nav>
  </div>
</aside>