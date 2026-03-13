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

        <?php
        // 2. ROLE: MR
        elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'mr') :
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

        <?php
        // 3. ROLE: ADMIN
        else :
          $dashboard_active = ($current_page == 'index.php') ? 'active' : '';

          // Grup MR
          $mr_pages = ['memberdetail.php', 'targetmr.php'];
          $mr_active = in_array($current_page, $mr_pages) ? 'active' : '';
          $mr_open = in_array($current_page, $mr_pages) ? 'menu-open' : '';

          // Grup Kasir
          $kasir_pages = ['salesdetail.php', 'cek_data_struk.php', 'cekhjk.php'];
          $kasir_active = in_array($current_page, $kasir_pages) ? 'active' : '';
          $kasir_open = in_array($current_page, $kasir_pages) ? 'menu-open' : '';

          // Grup Gudang
          $gudang_pages = ['realisasislp.php', 'monitoring_pareto.php'];
          $gudang_active = in_array($current_page, $gudang_pages) ? 'active' : '';
          $gudang_open = in_array($current_page, $gudang_pages) ? 'menu-open' : '';

          // Grup Evaluasi
          $evaluasi_pages = ['ongkir_vs_margin.php'];
          $evaluasi_active = in_array($current_page, $evaluasi_pages) ? 'active' : '';
          $evaluasi_open = in_array($current_page, $evaluasi_pages) ? 'menu-open' : '';

          // Grup OPR EDP
          $edp_pages = ['cekpagi.php', 'monitoringoperasi.php'];
          $edp_active = in_array($current_page, $edp_pages) ? 'active' : '';
          $edp_open = in_array($current_page, $edp_pages) ? 'menu-open' : '';

          // Grup Product EDP
          $is_product_edp = (strpos($_SERVER['REQUEST_URI'], 'spotifypremiumindogrosir') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'tarik_laporan') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'test_ping') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'FTP_HO') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'bot_wa') !== false);
          $product_edp_open = $is_product_edp ? 'menu-open' : '';
          $product_edp_active = $is_product_edp ? 'active' : '';

          // Definisi Halaman Individual
          $member_active = ($current_page == 'memberdetail.php') ? 'active' : '';
          $target_mr_active = ($current_page == 'targetmr.php') ? 'active' : '';
          $sales_active = ($current_page == 'salesdetail.php') ? 'active' : '';
          $struk_active = ($current_page == 'cek_data_struk.php') ? 'active' : '';
          $hjk_active = ($current_page == 'cekhjk.php') ? 'active' : '';
          $realisasi_active = ($current_page == 'realisasislp.php') ? 'active' : '';
          $pareto_active = ($current_page == 'monitoring_pareto.php') ? 'active' : '';
          $cek_pagi_active = ($current_page == 'cekpagi.php') ? 'active' : '';
          $monitoring_db_active = ($current_page == 'monitoringoperasi.php') ? 'active' : '';
          $ongkir_vs_margin = ($current_page == 'ongkir_vs_margin.php') ? 'active' : '';

        ?>

          <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $dashboard_active; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item has-treeview <?php echo $mr_open; ?>">
            <a href="#" class="nav-link <?php echo $mr_active; ?>">
              <i class="nav-icon fas fa-users"></i>
              <p>MR <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="memberdetail.php" class="nav-link confirm-link <?php echo $member_active; ?>">
                  <i class="far fa-address-card nav-icon"></i>
                  <p>Detail Member</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="targetmr.php" class="nav-link confirm-link <?php echo $target_mr_active; ?>">
                  <i class="fas fa-headset nav-icon"></i>
                  <p>Monitoring MR</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $kasir_open; ?>">
            <a href="#" class="nav-link <?php echo $kasir_active; ?>">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>Kasir <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="salesdetail.php" class="nav-link confirm-link <?php echo $sales_active; ?>">
                  <i class="fas fa-wallet nav-icon"></i>
                  <p>Detail Sales Kasir</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="cek_data_struk.php" class="nav-link confirm-link <?php echo $struk_active; ?>">
                  <i class="fas fa-receipt nav-icon"></i>
                  <p>Monitoring Struk</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="cekhjk.php" class="nav-link confirm-link <?php echo $hjk_active; ?>">
                  <i class="fas fa-star nav-icon"></i>
                  <p>Cek Item HJK</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $gudang_open; ?>">
            <a href="#" class="nav-link <?php echo $gudang_active; ?>">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>Gudang <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="realisasislp.php" class="nav-link confirm-link <?php echo $realisasi_active; ?>">
                  <i class="fas fa-barcode nav-icon"></i>
                  <p>Monitoring SLP</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="monitoring_pareto.php" class="nav-link confirm-link <?php echo $pareto_active; ?>">
                  <i class="fas fa-flask nav-icon"></i>
                  <p>Monitoring Pareto</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $evaluasi_open; ?>">
            <a href="#" class="nav-link <?php echo $evaluasi_active; ?>">
              <i class="nav-icon fas fa-search-dollar"></i>
              <p>Evaluasi <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="ongkir_vs_margin.php" class="nav-link confirm-link <?php echo $ongkir_vs_margin; ?>">
                  <i class="fas fa-hand-holding-usd nav-icon"></i>
                  <p>Ongkir VS Margin</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $edp_open; ?>">
            <a href="#" class="nav-link <?php echo $edp_active; ?>">
              <i class="nav-icon fas fa-shield-alt"></i>
              <p>OPR EDP <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" data-url="cekpagi.php" class="nav-link confirm-link <?php echo $cek_pagi_active; ?>">
                  <i class="fas fa-wrench nav-icon"></i>
                  <p>CEK SETING</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" data-url="monitoringoperasi.php" class="nav-link confirm-link <?php echo $monitoring_db_active; ?>">
                  <i class="fas fa-cogs nav-icon"></i>
                  <p>MONITORING DB</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview <?php echo $product_edp_open; ?>">
            <a href="#" class="nav-link <?php echo $product_edp_active; ?>">
              <i class="nav-icon fas fa-boxes"></i>
              <p>ProductEDP <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="../spotifypremiumindogrosir/index.php" target="_blank" class="nav-link"><i class="nav-icon fas fa-music"></i>
                  <p>Music (SPI)</p>
                </a></li>
              <li class="nav-item"><a href="../tarik_laporan/index.php" target="_blank" class="nav-link"><i class="nav-icon fas fa-download"></i>
                  <p>Tarik Data Laporan</p>
                </a></li>
              <li class="nav-item"><a href="../test_ping/index.php" target="_blank" class="nav-link"><i class="nav-icon fas fa-satellite-dish"></i>
                  <p>Test Ping</p>
                </a></li>
              <li class="nav-item"><a href="../FTP_HO_BY白竜7z/index.php" target="_blank" class="nav-link"><i class="nav-icon fas fa-hdd"></i>
                  <p>FTP HO</p>
                </a></li>
              <li class="nav-item"><a href="../bot_wa/admin.html" target="_blank" class="nav-link"><i class="nav-icon fas fa-bullhorn"></i>
                  <p>WA BLAST</p>
                </a></li>
            </ul>
          </li>

        <?php
        endif;
        ?>

        <?php if (isset($_SESSION['role'])) : ?>
          <li class="nav-header">AKUN</li>
          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>