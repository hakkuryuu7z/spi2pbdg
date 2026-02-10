<!-- Modal Detail PB Salesman (UPDATED) -->
<div class="modal fade" id="modalDetailPB" tabindex="-1" role="dialog" aria-labelledby="modalDetailPBLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content bg-dark text-white">

            <!-- Header -->
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-list-alt text-primary mr-2"></i>Detail PB Salesman: <span id="modal-salesman-name" class="text-warning font-weight-bold">...</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <!-- Toolbar: Search & Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-secondary border-0 text-white"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" id="search-pb-input" class="form-control bg-dark text-white border-secondary" placeholder="Cari No PB, Member, atau Status...">
                        </div>
                    </div>
                    <div class="col-md-8 text-right">
                        <button class="btn btn-outline-danger mr-2" id="btn-filter-batal">
                            <i class="fas fa-ban mr-1"></i> Hanya Batal
                        </button>
                        <button class="btn btn-outline-secondary mr-2" id="btn-reset-filter" style="display:none;">
                            <i class="fas fa-sync-alt"></i> Reset
                        </button>
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                        </button>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-striped table-bordered mb-0" id="table-detail-pb">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center" style="width: 5%">No</th>
                                <th style="width: 20%">No PB</th>
                                <th style="width: 15%">Tgl PB</th>
                                <th style="width: 10%">Kode</th>
                                <th style="width: 25%">Nama Member</th>
                                <th style="width: 10%">No Trans</th>
                                <th class="text-center" style="width: 15%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data dimuat via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer: Pagination & Rows Settings -->
            <div class="modal-footer border-top border-secondary d-flex justify-content-between align-items-center py-2">

                <!-- Bagian Kiri: Dropdown Rows & Info -->
                <div class="d-flex align-items-center">
                    <label class="mr-2 mb-0 text-muted small">Baris:</label>
                    <select id="rowsPerPageSelect" class="form-control form-control-sm bg-dark text-white border-secondary mr-3" style="width: 70px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-white small" id="info-total-row">Total: 0</span>
                </div>

                <!-- Bagian Kanan: Pagination -->
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination-container">
                        <!-- Tombol Pagination akan muncul di sini -->
                    </ul>
                </nav>

                <!-- Tombol Tutup (Optional, bisa dihapus jika mau layout lebih bersih) -->
                <!-- <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button> -->
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailSalesMember" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="background: #282c31; color: #fff; border: 1px solid #444;">
            <div class="modal-header border-bottom border-secondary" style="background: #212529;">
                <h5 class="modal-title">
                    <i class="fas fa-users text-info mr-2"></i>
                    Detail Member: <span id="modal-sales-mr-name" class="text-warning font-weight-bold"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex justify-content-between align-items-center p-3" style="background: #343a40;">
                    <input type="text" id="search-sales-member" class="form-control form-control-sm w-25" placeholder="Cari Member..." style="background: #212529; color: #fff; border: 1px solid #555;">
                    <div id="info-total-sales-member" class="font-weight-bold"></div>
                    <button class="btn btn-sm btn-success" onclick="exportSalesMemberToExcel()">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-striped mb-0" id="table-detail-sales-member" style="font-size: 13px;">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th width="10%">Kode</th>
                                <th width="25%">Nama Member</th>
                                <th class="text-center" width="5%">Item</th>
                                <th class="text-right text-muted" width="15%" id="th-sales-prev">Sales Lalu</th>
                                <th class="text-right" width="15%">Sales Sekarang</th>
                                <th class="text-center" width="10%">Growth</th>
                                <th class="text-right" width="15%">Margin</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary py-2" style="background: #212529;">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="pagination-sales-container"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailAktivasi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content modal-premium-content">

            <div class="modal-header modal-header-clean align-items-center">
                <div>
                    <h5 class="modal-title font-weight-bold text-white mb-1">
                        <i class="fas fa-bolt text-warning mr-2"></i>Detail Aktivasi
                    </h5>
                    <small class="text-muted">Daftar member baru bulan ini</small>
                </div>
                <button type="button" class="close text-secondary" data-dismiss="modal" aria-label="Close" style="opacity: 1; text-shadow: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                            <i class="fas fa-users mr-1"></i> Total: <span id="info-total-aktivasi-badge">0</span>
                        </span>
                    </div>
                    <div class="input-group search-premium" style="width: 300px;">
                        <input type="text" id="search-aktivasi" class="form-control" placeholder="Cari Nama / Kode...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive rounded border border-secondary" style="border-color: #2f353a !important;">
                    <table class="table table-modern" id="table-detail-aktivasi">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th style="width: 15%;">Tgl Aktivasi</th>
                                <th style="width: 15%;">Kode</th>
                                <th style="width: 25%;">Nama Member</th>
                                <th>Alamat</th>
                                <th>Advisor</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end align-items-center mt-4">
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination-aktivasi-container"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalDetailOngkirMargin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="min-width: 95%;">
        <div class="modal-content" style="background: #282c31; color: #fff; border: 1px solid #444;">

            <div class="modal-header border-bottom border-secondary" style="background: #212529;">
                <h5 class="modal-title">
                    <i class="fas fa-shipping-fast text-info mr-2"></i>
                    Detail OBI Sales & Ongkir: <span id="modal-ongkir-mr-name" class="text-warning font-weight-bold">...</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-0">

                <div class="d-flex justify-content-between align-items-center p-3" style="background: #343a40;">

                    <div class="d-flex align-items-center w-50">
                        <input type="text" id="search-ongkir-margin" class="form-control form-control-sm w-50 mr-3" placeholder="Cari Kode / Nama / No PB..." style="background: #212529; color: #fff; border: 1px solid #555;">
                        <span id="info-periode-ongkir" class="text-muted small font-italic mr-3"></span>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="btn-group btn-group-sm btn-group-toggle mr-3" data-toggle="buttons">
                            <label class="btn btn-outline-info active" onclick="switchOngkirMode('member')">
                                <input type="radio" name="options" id="opt-member" autocomplete="off" checked>
                                <i class="fas fa-users"></i> Per Member
                            </label>
                            <label class="btn btn-outline-warning" onclick="switchOngkirMode('struk')">
                                <input type="radio" name="options" id="opt-struk" autocomplete="off">
                                <i class="fas fa-file-invoice"></i> Per No PB
                            </label>
                        </div>

                        <div id="info-total-ongkir-margin" class="font-weight-bold mr-3 small text-white"></div>

                        <button class="btn btn-sm btn-success" onclick="exportOngkirMarginToExcel()">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover table-striped mb-0" id="table-detail-ongkir-margin" style="font-size: 13px;">

                        <thead class="thead-dark" id="thead-ongkir-margin">
                        </thead>

                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top border-secondary py-2" style="background: #212529;">
                <div class="mr-auto text-muted small">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span class="text-warning">Ratio O/M</span>: (Ongkir / Margin) x 100. Semakin <b>kecil</b> semakin efisien.
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="pagination-ongkir-container"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>