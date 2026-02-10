<div class="card card-modern mb-4">
    <div class="card-header card-header-modern d-flex flex-row align-items-center justify-content-between">
        <div>
            <h5 id="judul_mr" class="m-0 font-weight-bold text-white">Target & Realisasi MR</h5>
            <small class="text-muted">Monitoring performa harian sales</small>
        </div>

        <div class="form-inline">
            <div class="input-group input-group-sm mr-3">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark border-0 text-white"><i class="far fa-calendar-alt"></i></span>
                </div>
                <input type="date" id="filter_tanggal" class="form-control bg-dark border-0 text-white">
            </div>

            <button class="btn btn-sm btn-outline-warning mr-2" onclick="showEditTargetModal()">
                <i class="fas fa-edit"></i> Atur Target
            </button>
            <button class="btn btn-sm btn-outline-success mr-2" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="loadMemberData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-modern" id="table_target_mr">
                <thead>
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
                <tfoot id="foot_target_mr"></tfoot>
            </table>
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