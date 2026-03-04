<div class="row pb-4">
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-circle bg-info mr-3">
                    <i class="fas fa-address-card"></i>
                </div>
                <div>
                    <div class="text-muted small font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">Registered</div>
                    <div class="h3 font-weight-bold text-white mb-0" id="member-count">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-circle bg-danger mr-3">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <div class="text-muted small font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">Members Aktif</div>
                    <div class="h3 font-weight-bold text-white mb-0" id="member-aktif">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-circle bg-success mr-3">
                    <i class="fas fa-phone-slash"></i>
                </div>
                <div>
                    <div class="text-muted small font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">Non Transaksi</div>
                    <div class="h3 font-weight-bold text-white mb-0" id="member-nontransaksi">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-circle bg-warning mr-3">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div>
                    <div class="text-muted small font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">Coverage Area</div>
                    <div class="h3 font-weight-bold text-white mb-0" id="member-coverage">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-body p-4">
    <div class="row mb-4">
        <div class="col-md-5 mb-2">
            <div class="input-modern-group">
                <i class="fas fa-search"></i>
                <input type="text" class="input-modern" id="searchMember" placeholder="Cari Kode atau Nama Member...">
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="input-modern-group">
                <i class="fas fa-filter"></i>
                <select class="input-modern" id="filterMemberStatus">
                    <option value="all" selected>Semua Status</option>
                    <option value="non_transaksi">Non-Transaksi</option>
                    <option value="aktif">Aktif</option>
                </select>
            </div>
        </div>

        <div class="col-md-2 mb-2">
            <div class="input-modern-group">
                <i class="fas fa-list-ol"></i>
                <select class="input-modern" id="rowsPerPage">
                    <option value="10">10 Baris</option>
                    <option value="15" selected>15 Baris</option>
                    <option value="25">25 Baris</option>
                    <option value="50">50 Baris</option>
                    <option value="100">100 Baris</option>
                </select>
            </div>
        </div>

        <div class="col-md-2 mb-2 text-right d-flex align-items-center justify-content-end">
            <span class="text-muted small" id="totalDataInfo">Loading...</span>
        </div>
    </div>

    <div class="table-modern-wrapper table-responsive">
        <table id="tabel-member" class="table-modern">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kode</th>
                    <th>Nama Member</th>
                    <th>No HP</th>
                    <th>Email</th>
                    <th>Salesman</th>
                    <th>Status</th>
                    <th>Tgl Regis</th>
                    <th>Tgl Mulai</th>
                    <th>Sales</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-4 pt-2">
        <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationWrappermm"></ul>
        </nav>
    </div>
</div>

<div class="row mb-4">

    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card-modern h-100">
            <div class="card-header-modern py-3">
                <div class="title-modern" style="font-size: 1rem;">
                    <div class="title-icon"><i class="fas fa-map-marker-alt"></i></div>
                    Per Kecamatan
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tabel-pb" class="table-modern">
                        <thead>
                            <tr>
                                <th class="pl-4">Kecamatan</th>
                                <th>Jumlah</th>
                                <th class="text-center pr-4">Detail</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="p-3 d-flex justify-content-center border-top border-secondary">
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationWrapperPb"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card-modern h-100">
            <div class="card-header-modern py-3">
                <div class="title-modern" style="font-size: 1rem;">
                    <div class="title-icon"><i class="fas fa-route"></i></div>
                    Per Jarak
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tabel-jarak" class="table-modern">
                        <thead>
                            <tr>
                                <th class="pl-4">Kategori Jarak</th>
                                <th>Total</th>
                                <th class="text-center pr-4">Detail</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card-modern h-100">
            <div class="card-header-modern py-3">
                <div class="title-modern" style="font-size: 1rem;">
                    <div class="title-icon"><i class="fas fa-user-tie"></i></div>
                    Per Sales (MR)
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tabel-MR" class="table-modern">
                        <thead>
                            <tr>
                                <th class="pl-4">Total Member</th>
                                <th>Kode MR</th>
                                <th class="text-center pr-4">Detail</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header-modern">
                <div class="title-modern">
                    <div class="title-icon text-warning"><i class="fas fa-bed"></i></div>
                    <div>
                        Member Sleeper
                        <div class="text-muted small font-weight-normal mt-1" id="sleeperSubtitle">Memuat data...</div>
                    </div>
                </div>
                <div class="toggle-container d-flex align-items-center">
                    <button type="button" class="btn-toggle-modern btn-excel mr-3" onclick="exportSleeperToExcel()">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>

                    <div class="d-flex align-items-center bg-dark rounded border border-secondary" style="padding: 2px 4px;">
                        <span class="text-muted small px-2">Tidak Transaksi:</span>
                        <input type="number" id="inputSleeperInterval" value="2" min="1" class="form-control form-control-sm text-center text-white bg-transparent border-0 font-weight-bold" style="width: 50px; outline: none; box-shadow: none;">
                        <span class="text-muted small px-2">Bulan</span>
                        <button type="button" class="btn btn-sm btn-primary rounded ml-1" id="btnApplySleeper">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="toggle-container">
                    <button type="button" class="btn-toggle-modern btn-excel mr-2" onclick="exportSleeperToExcel()">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>

                    <button type="button" class="btn-toggle-modern" onclick="loadSleeper(2)" id="btnSleeper2">-2 Bulan</button>
                    <button type="button" class="btn-toggle-modern" onclick="loadSleeper(3)" id="btnSleeper3">-3 Bulan</button>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-5 mb-2">
                        <div class="input-modern-group">
                            <i class="fas fa-search"></i>
                            <input type="text" class="input-modern" id="searchSleeper" placeholder="Cari Kode atau Nama Member...">
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="input-modern-group">
                            <i class="fas fa-user-tag"></i>
                            <select class="input-modern" id="filterSalesmanSleeper">
                                <option value="all">Semua Salesman</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 text-right mb-2">
                        <span class="badge badge-soft-light px-3 py-2" id="sleeperCountBadge">0 Data Ditemukan</span>
                    </div>
                </div>

                <div class="table-modern-wrapper table-responsive">
                    <table id="tabel-sleeper" class="table-modern">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Kode</th>
                                <th>Nama Member</th>
                                <th>Alamat</th>
                                <th>No HP</th>
                                <th>Salesman</th>
                                <th>Belanja Terakhir</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
                    <div class="text-muted small">Halaman <span class="text-white" id="sleeperPageInfo">1</span></div>
                    <nav>
                        <ul class="pagination pagination-sm justify-content-end mb-0" id="paginationSleeper"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'modalmember.php' ?>

<script src="jquery/jquery-3.7.1.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="detailmember/script.js"></script>
<script src="dist/js/xlsx.full.min.js"></script>