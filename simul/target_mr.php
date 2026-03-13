<div class="card card-modern mb-4">
    <div class="card-header card-header-modern d-flex flex-column flex-md-row align-items-center justify-content-between">
        <div class="mb-3 mb-md-0">
            <h5 id="judul_mr" class="m-0 font-weight-bold text-white">Target & Realisasi MR</h5>
            <small class="text-muted">Monitoring performa harian sales</small>
        </div>

        <div class="form-inline justify-content-center">
            <div class="input-group input-group-sm mr-3 mb-2 mb-sm-0">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark border-0 text-white"><i class="far fa-calendar-alt"></i></span>
                </div>
                <input type="date" id="filter_tanggal" class="form-control bg-dark border-0 text-white">
            </div>

            <input type="file" id="file_target" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" style="display: none;" onchange="handleFileUpload(event)">

            <button class="btn btn-sm btn-outline-warning mr-2 mb-2 mb-sm-0 btn-modern" onclick="document.getElementById('file_target').click()">
                <i class="fas fa-upload"></i> Upload Target
            </button>
            <button class="btn btn-sm btn-outline-success mr-2 mb-2 mb-sm-0 btn-modern" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-info mb-2 mb-sm-0 btn-modern" onclick="loadMemberData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row align-items-center">

            <div class="col-xl-3 col-lg-4 mb-4 mb-lg-0">
                <div class="chart-wrapper-modern">
                    <div style="position: relative; height: 220px; width: 100%;">
                        <canvas id="donutChartMR"></canvas>
                    </div>

                    <div class="chart-info text-center mt-3 w-100">
                        <h6 class="text-white font-weight-bold mb-2">Distribusi All-Time</h6>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background-color: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05);">
                            <div class="text-center w-50" style="border-right: 1px solid rgba(255,255,255,0.1);">
                                <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: bold; letter-spacing: 0.5px;">TOTAL MEMBER</small>
                                <span class="font-weight-bold text-info" id="info_total_member" style="font-size: 1.2rem;">0</span>
                            </div>
                            <div class="text-center w-50">
                                <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: bold; letter-spacing: 0.5px;">TARGET BULAN INI</small>
                                <span class="font-weight-bold text-warning" id="info_target_bulanan" style="font-size: 1.2rem;">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-lg-8">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-modern" id="table_target_mr">
                        <thead style="position: sticky; top: 0; z-index: 2; background-color: #1e1e2d;">
                            <tr class="text-left">
                                <th width="5%">NO</th>
                                <th width="20%">ADV (SALESMAN)</th>
                                <th width="15%" class="text-center">TARGET</th>
                                <th width="15%" class="text-center">GET MM</th>
                                <th width="25%">ACHIEVEMENT</th>
                                <th width="20%" class="text-center text-success" id="th_harian">GET HARI INI</th>
                            </tr>
                        </thead>
                        <tbody id="body_target_mr">
                        </tbody>
                        <tfoot id="foot_target_mr" style="position: sticky; bottom: 0; z-index: 2; background-color: #1e1e2d;"></tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>


    <div class="card card-modern mb-4">
        <div class="card-header card-header-modern d-flex flex-row align-items-center justify-content-between">
            <div>
                <h5 class="m-0 font-weight-bold text-white">Perbandingan PB per MR</h5>
                <small class="text-muted">Analisis Validasi vs Batal Order</small>
            </div>

            <div class="form-inline">
                <small class="text-muted mr-2">Filter Range:</small>
                <div class="input-group input-group-sm mr-2">
                    <input type="date" id="pb_start_date" class="form-control bg-dark border-0 text-white" title="Dari Tanggal">
                </div>
                <span class="text-muted mr-2">s/d</span>
                <div class="input-group input-group-sm mr-2">
                    <input type="date" id="pb_end_date" class="form-control bg-dark border-0 text-white" title="Sampai Tanggal">
                </div>
                <button class="btn btn-sm btn-outline-info" onclick="loadPBComparison()">
                    <i class="fas fa-search"></i> Cek
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-modern" id="table_pb_mr">
                    <thead>
                        <tr class="text-left">
                            <th width="5%">NO</th>
                            <th width="15%">SALESMAN</th>
                            <th width="15%" class="text-center text-warning" style="border-right: 1px solid #444;">
                                PB HARI INI <br><small style="font-size:0.6rem">(Selected End Date)</small>
                            </th>
                            <th width="12%" class="text-center text-info">TOTAL PB <br><small>(Range)</small></th>
                            <th width="12%" class="text-center text-success">VALID <br><small>(Range)</small></th>
                            <th width="12%" class="text-center text-danger">BATAL <br><small>(Range)</small></th>
                            <th width="29%">SUCCESS RATE</th>
                        </tr>
                    </thead>
                    <tbody id="body_pb_mr">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-modern mb-4">
        <div class="card-header card-header-modern d-flex flex-row align-items-center justify-content-between">
            <div>
                <h5 class="m-0 font-weight-bold text-white">Member Sleeper</h5>
                <small class="text-muted" id="sleeper_subtitle">Monitoring member tidak aktif belanja</small>
            </div>

            <div class="form-inline">
                <small class="text-muted mr-2">Interval (Bulan):</small>
                <div class="input-group input-group-sm mr-2">
                    <input type="number" id="sleeper_interval" class="form-control bg-dark border-0 text-white text-center" value="2" min="1" style="width: 60px;">
                </div>

                <small class="text-muted mr-2">Salesman:</small>
                <div class="input-group input-group-sm mr-2">
                    <select id="sleeper_salesman_filter" class="form-control bg-dark border-0 text-white" onchange="renderSleeperTable()">
                        <option value="ALL">SEMUA</option>
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-danger mr-2" onclick="loadMemberSleeper()">
                    <i class="fas fa-search"></i> Cek Data
                </button>

                <button class="btn btn-sm btn-outline-success" onclick="exportSleeperExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover" id="table_sleeper">
                    <thead>
                        <tr class="text-left">
                            <th width="5%" class="text-center">NO</th>
                            <th width="10%">KODE</th>
                            <th width="20%">NAMA MEMBER</th>
                            <th width="30%">ALAMAT</th>
                            <th width="15%" class="text-center text-danger">BELANJA TERAKHIR</th>
                            <th width="10%" class="text-center">SALESMAN</th>
                            <th width="10%" class="text-right">TOTAL SALES</th>
                        </tr>
                    </thead>
                    <tbody id="body_sleeper">
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small" id="sleeperPageInfo"></div>
                <div id="sleeperPaginationControls"></div>
            </div>
        </div>
    </div>
    <?php
    include "modal_mr.php";

    ?>

    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.js"></script>

    <!-- PAGE PLUGINS -->
    <!-- jQuery Mapael -->
    <script src="plugins/jquery-mousewheel/jquery.mousewheel.js"></script>
    <script src="plugins/raphael/raphael.min.js"></script>
    <script src="plugins/jquery-mapael/jquery.mapael.min.js"></script>
    <script src="plugins/jquery-mapael/maps/usa_states.min.js"></script>
    <!-- ChartJS -->
    <script src="plugins/chart.js/Chart.min.js"></script>

    <script src="dist/js/pages/dashboard2.js"></script>
    <script src="simul/scriptmr.js"></script>