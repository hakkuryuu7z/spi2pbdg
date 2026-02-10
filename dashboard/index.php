<div class="container-fluid">

    <div class="row pb-2">
        <div class="col-12">
            <div class="d-flex justify-content-end">
                <div class="info-box mb-0" style="background-color: #ec01013a; min-width: 250px;">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-wifi"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-danger"><b>LIVE</b></span>
                        <span class="info-box-number">
                            <?= date('l, d-m-y') ?>
                            <small id="jam-digital" class="ml-1"></small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-info mr-3"><i class="fas fa-wallet fa-lg"></i></div>
                    <div>
                        <div class="text-muted">Sales</div>
                        <div class="h4 font-weight-bold" id="sales">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-danger mr-3"><i class="fas fa-money-bill fa-lg"></i></div>
                    <div>
                        <div class="text-muted">Margin</div>
                        <div class="h4 font-weight-bold" id="margin">Memuat....</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card" id="btn-detail-aktivasi" style="cursor: pointer; transition: transform 0.2s;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-white mr-3"><i class="fas fa-bolt fa-lg"></i></div>
                    <div>
                        <div class="text-muted">Aktivasi Bln Ini</div>
                        <div class="h4 font-weight-bold" id="aktivasi_bulan_berjalan">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-primary mr-3"><i class="fas fa-shopping-cart fa-lg"></i></div>
                    <div>
                        <div class="text-muted">Member Belanja</div>
                        <div class="h4 font-weight-bold" id="member-belanja-bulan">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-warning mr-3"><i class="fas fa-address-card fa-lg"></i></div>
                    <div>
                        <div class="text-muted">Member Register</div>
                        <div class="h4 font-weight-bold" id="member-count">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-success mr-3"><i class="fas fa-money-check fa-lg"></i></div>
                    <div>
                        <div class="text-muted">STD</div>
                        <div class="h4 font-weight-bold" id="std">Memuat....</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Grafik Sales SPI 2P Bulan berjalan</h5>
                </div>
                <div class="card-body">
                    <div id="apex-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-lg-6">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Komposisi Member</h5>
                </div>
                <div class="card-body">
                    <div id="member-pie-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Member Berdasarkan Jarak</h5>
                </div>
                <div class="card-body">
                    <div id="distance-donut-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Grafik Sales & Margin Per Bulan</h5>
                </div>
                <div class="card-body">
                    <div id="monthly-bar-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="card-title mb-0 font-weight-bold">
                                <i class="fas fa-chart-area text-primary mr-2"></i>Sales & Margin
                            </h5>
                        </div>

                        <div class="col-md-8">
                            <div class="d-flex justify-content-end align-items-center flex-wrap">

                                <div class="d-flex align-items-center mr-3 bg-dark rounded p-1" style="border: 1px solid #444;">
                                    <input type="date" id="filter-start-date" class="form-control form-control-sm bg-transparent text-white border-0" style="width: 130px;">
                                    <span class="text-muted mx-1">-</span>
                                    <input type="date" id="filter-end-date" class="form-control form-control-sm bg-transparent text-white border-0" style="width: 130px;">
                                    <button id="btn-apply-filter" class="btn btn-sm btn-primary ml-2 px-3">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>

                                <div class="btn-group btn-group-sm" role="group" id="comparison-period-filter">
                                    <button type="button" class="btn btn-outline-light active" data-period="daily">Harian</button>
                                    <button type="button" class="btn btn-outline-light" data-period="weekly">Mingguan</button>
                                    <button type="button" class="btn btn-outline-light" data-period="monthly">Bulanan</button>
                                </div>
                            </div>
                            <div class="text-right mt-1">
                                <small class="text-warning" id="active-date-label" style="font-style: italic; font-size: 11px;"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-2 pb-4">
                    <div class="row mb-4 px-2">
                        <div class="col-md-4 col-12 mb-2">
                            <div class="p-3 rounded" style="background: linear-gradient(90deg, rgba(46, 147, 250, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #2E93fA;">
                                <h6 class="text-uppercase text-muted mb-1" style="font-size: 12px; letter-spacing: 1px;">Total Sales (Periode Ini)</h6>
                                <div class="d-flex align-items-baseline">
                                    <h2 class="font-weight-bold mb-0 text-white mr-2" id="big-sales-val">Rp 0</h2>
                                    <span id="big-sales-growth"></span>
                                </div>
                                <small class="text-muted">vs Lalu: <span id="big-sales-prev" class="text-info">Rp 0</span></small>
                            </div>
                        </div>

                        <div class="col-md-4 col-12 mb-2">
                            <div class="p-3 rounded" style="background: linear-gradient(90deg, rgba(255, 69, 96, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FF4560;">
                                <h6 class="text-uppercase text-muted mb-1" style="font-size: 12px; letter-spacing: 1px;">Total Margin (Periode Ini)</h6>
                                <div class="d-flex align-items-baseline">
                                    <h2 class="font-weight-bold mb-0 text-white mr-2" id="big-margin-val">Rp 0</h2>
                                    <span id="big-margin-growth"></span>
                                </div>
                                <small class="text-muted">vs Lalu: <span id="big-margin-prev" class="text-warning">Rp 0</span></small>
                            </div>
                        </div>

                        <div class="col-md-4 col-12 mb-2">
                            <div class="p-3 rounded" style="background: linear-gradient(90deg, rgba(254, 176, 25, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FEB019;">
                                <h6 class="text-uppercase text-muted mb-1" style="font-size: 12px; letter-spacing: 1px;">Total Ongkir (Periode Ini)</h6>
                                <div class="d-flex align-items-baseline">
                                    <h2 class="font-weight-bold mb-0 text-white mr-2" id="big-ongkir-val">Rp 0</h2>
                                    <span id="big-ongkir-growth"></span>
                                </div>
                                <small class="text-muted">vs Lalu: <span id="big-ongkir-prev" class="text-warning">Rp 0</span></small>
                            </div>
                        </div>
                    </div>

                    <div id="combined-trend-chart" style="min-height: 550px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- <br>
<br><br><br> -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header" style="background: #212529; color: white; border-bottom: 1px solid #444;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0 font-weight-bold" style="color: #f8f9fa; letter-spacing: 1px;">
                            <i class="fas fa-chart-line mr-2 text-info"></i>PERBANDINGAN PB
                        </h5>
                        <div class="d-flex align-items-center p-1" style="background: #343a40; border-radius: 6px; border: 1px solid #495057;">
                            <input type="date" id="filter-pb-start" class="form-control form-control-sm mr-1" style="background: #212529; color: #fff; border: none; height: 25px;">
                            <span class="mx-1 text-secondary">-</span>
                            <input type="date" id="filter-pb-end" class="form-control form-control-sm mr-2" style="background: #212529; color: #fff; border: none; height: 25px;">
                            <button id="btn-filter-pb" class="btn btn-sm btn-info py-0" style="height: 25px;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-md-4 border-right" style="border-color: #495057 !important;">
                            <h6 class="mb-3 font-weight-bold" style="font-size: 12px; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px;">TOTAL DOKUMEN PB</h6>
                            <div class="d-flex justify-content-center align-items-center">
                                <h1 class="mb-0 font-weight-bold mr-3" id="val-pb-curr" style="color: #00ffbf; font-size: 2.5rem; text-shadow: 0 0 20px rgba(0, 255, 191, 0.3);">0</h1>
                                <div class="text-left" style="line-height: 1.3;">
                                    <div style="font-size: 0.95rem; color: #e0e0e0;">Lalu: <span id="val-pb-prev" style="color: #ffc107; font-weight: bold;">0</span></div>
                                    <div id="label-growth-month" class="font-weight-bold" style="font-size: 0.95rem;">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 border-right" style="border-color: #495057 !important;">
                            <h6 class="mb-3 font-weight-bold" style="font-size: 12px; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px;">TOTAL PB BATAL</h6>
                            <div class="d-flex justify-content-center align-items-center">
                                <h1 class="mb-0 font-weight-bold mr-3" id="val-pb-batal" style="color: #ff4d4d; font-size: 2.5rem; text-shadow: 0 0 20px rgba(255, 77, 77, 0.3);">0</h1>
                                <div class="text-left" style="line-height: 1.3;">
                                    <div style="font-size: 0.95rem; color: #e0e0e0;">Lalu: <span id="val-pb-batal-prev" style="color: #ffc107; font-weight: bold;">0</span></div>
                                    <div id="label-growth-batal" class="font-weight-bold" style="font-size: 0.95rem;">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-3 font-weight-bold" style="font-size: 12px; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px;">HARIAN (Vs Kemarin)</h6>
                            <div class="d-flex justify-content-center align-items-center">
                                <h1 class="mb-0 font-weight-bold mr-3" id="val-pb-today" style="color: #ffffff; font-size: 2.5rem; text-shadow: 0 0 15px rgba(255,255,255,0.3);">0</h1>
                                <div class="text-left" style="line-height: 1.3;">
                                    <div id="label-daily-title" style="font-size: 0.85rem; color: #adb5bd;">Hari Ini</div>
                                    <div id="label-growth-day" class="font-weight-bold" style="font-size: 0.95rem;">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="background: #282c31;">
                    <div id="pb-comparison-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header" style="background: #212529; color: white; border-bottom: 1px solid #444;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-weight-bold" style="color: #f8f9fa; letter-spacing: 1px;">
                            <i class="fas fa-users mr-2 text-warning"></i>PERBANDINGAN PB PER MR
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-sm mr-3" role="group">
                                <button type="button" class="btn btn-primary" id="btn-mr-valid">
                                    <i class="fas fa-check-circle"></i> Valid
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btn-mr-all">
                                    <i class="fas fa-layer-group"></i> Total
                                </button>
                            </div>
                            <div class="d-flex align-items-center p-1" style="background: #343a40; border-radius: 6px; border: 1px solid #495057;">
                                <input type="date" id="filter-mr-start" class="form-control form-control-sm mr-1" style="background: #212529; color: #fff; border: none; height: 25px;">
                                <span class="mx-1 text-secondary">-</span>
                                <input type="date" id="filter-mr-end" class="form-control form-control-sm mr-2" style="background: #212529; color: #fff; border: none; height: 25px;">
                                <button id="btn-filter-mr" class="btn btn-sm btn-warning py-0" style="height: 25px;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="background: #282c31;">
                    <div id="mr-chart-summary" class="d-flex justify-content-between align-items-center mb-3 px-2">
                    </div>
                    <div id="mr-comparison-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header" style="background: #212529; color: white; border-bottom: 1px solid #444;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-weight-bold" style="color: #f8f9fa; letter-spacing: 1px;">
                            <i class="fas fa-chart-line mr-2 text-success"></i>PERBANDINGAN SALES PER MR
                        </h5>

                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-toggle mr-3" data-toggle="buttons">
                                <label class="btn btn-sm btn-outline-success active" id="btn-mode-monthly">
                                    <input type="radio" name="options" autocomplete="off" checked> <i class="fas fa-calendar-alt mr-1"></i> Bulan ini
                                </label>
                                <label class="btn btn-sm btn-outline-info" id="btn-mode-daily">
                                    <input type="radio" name="options" autocomplete="off"> <i class="fas fa-history mr-1"></i> Hari ini
                                </label>
                            </div>

                            <div class="d-flex align-items-center p-1" style="background: #343a40; border-radius: 6px; border: 1px solid #495057;">
                                <input type="date" id="filter-sales-mr-start" class="form-control form-control-sm mr-1" style="background: #212529; color: #fff; border: none; height: 25px;">
                                <span class="mx-1 text-secondary">-</span>
                                <input type="date" id="filter-sales-mr-end" class="form-control form-control-sm mr-2" style="background: #212529; color: #fff; border: none; height: 25px;">
                                <button id="btn-filter-sales-mr" class="btn btn-sm btn-success py-0" style="height: 25px;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-2 pb-4" style="background: #282c31; min-height: 500px;">
                    <div id="sales-mr-chart-summary"></div>

                    <div id="sales-mr-comparison-chart"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header" style="background: #212529; color: white; border-bottom: 1px solid #444;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-weight-bold" style="color: #f8f9fa; letter-spacing: 1px;">
                            <i class="fas fa-shipping-fast mr-2 text-danger"></i>ONGKIR VS MARGIN
                        </h5>

                        <div class="d-flex align-items-center p-1" style="background: #343a40; border-radius: 6px; border: 1px solid #495057;">
                            <input type="date" id="filter-ongkir-start" class="form-control form-control-sm mr-1" style="background: #212529; color: #fff; border: none; height: 25px;">
                            <span class="mx-1 text-secondary">-</span>
                            <input type="date" id="filter-ongkir-end" class="form-control form-control-sm mr-2" style="background: #212529; color: #fff; border: none; height: 25px;">
                            <button id="btn-filter-ongkir" class="btn btn-sm btn-danger py-0" style="height: 25px;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-2 pb-4" style="background: #282c31;">
                    <div id="ongkir-summary-container" class="row w-100 mx-0 mt-2 mb-4">
                        <div class="col-12 text-center text-muted">Loading Summary...</div>
                    </div>

                    <div id="ongkir-vs-margin-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include "modal.php" ?>
</div>

<script src="jquery/jquery-3.7.1.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="dist/js/adminlte.js"></script>
<script src="dist/chart/apexcharts.js"></script>
<script src="dashboard/script.js"></script>