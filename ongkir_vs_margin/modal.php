<div class="modal fade" id="modalDetailOngkirMargin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="background-color: #343a40; color: white;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-user-tie mr-2 text-info"></i> Detail Salesman: <span id="modal-ongkir-mr-name" class="text-warning"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <span id="info-periode-ongkir" class="badge badge-secondary p-2" style="font-size: 13px;"></span>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-info active" id="btn-mode-member" onclick="switchOngkirMode('member')">
                                <i class="fas fa-users mr-1"></i> Per Member
                            </button>
                            <button type="button" class="btn btn-outline-info" id="btn-mode-struk" onclick="switchOngkirMode('struk')">
                                <i class="fas fa-receipt mr-1"></i> Per Struk (PB)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-8 d-flex align-items-center">
                        <span id="info-total-ongkir-margin" class="font-weight-bold text-info" style="font-size: 14px;">Total: 0 Data</span>
                    </div>
                    <div class="col-md-4 text-right d-flex">
                        <input type="text" id="search-ongkir-margin" class="form-control form-control-sm mr-2" placeholder="Cari Kode / Nama / No PB...">
                        <button class="btn btn-sm btn-success" onclick="exportOngkirMarginToExcel()" title="Export ke Excel">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover table-sm text-nowrap" id="table-detail-ongkir-margin" style="min-height: 200px;">
                        <thead style="background-color: #23272b;" id="thead-ongkir-margin">
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <ul class="pagination pagination-sm mb-0" id="pagination-ongkir-container"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailJarak" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="background-color: #343a40; color: white;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-route mr-2 text-success"></i> Detail Jarak: <span id="modal-jarak-name" class="text-warning"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <span id="info-periode-jarak" class="badge badge-secondary p-2" style="font-size: 13px;"></span>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success active" id="btn-mode-jarak-member" onclick="switchJarakMode('member')">
                                <i class="fas fa-users mr-1"></i> Per Member
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btn-mode-jarak-struk" onclick="switchJarakMode('struk')">
                                <i class="fas fa-receipt mr-1"></i> Per Struk (PB)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-8 d-flex align-items-center">
                        <span id="info-total-jarak" class="font-weight-bold text-success" style="font-size: 14px;">Total: 0 Data</span>
                    </div>
                    <div class="col-md-4 text-right d-flex">
                        <input type="text" id="search-jarak" class="form-control form-control-sm mr-2" placeholder="Cari Kode / Nama / No PB...">
                        <button class="btn btn-sm btn-success" onclick="exportJarakToExcel()" title="Export ke Excel">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover table-sm text-nowrap" id="table-detail-jarak" style="min-height: 200px;">
                        <thead style="background-color: #23272b;" id="thead-jarak">
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <ul class="pagination pagination-sm mb-0" id="pagination-jarak-container"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalCariMember" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="background-color: #2b2f33; color: white;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-users text-info mr-2"></i> Cari & Pilih Member (Cabang 2P)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row mb-3">
                    <div class="col-md-8 mb-2">
                        <input type="text" id="input-search-member-modal" class="form-control bg-dark text-white border-secondary" placeholder="Ketik Kode atau Nama Member (Boleh Kosong)..." autocomplete="off">
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="input-group">
                            <input type="number" id="input-search-jarak-modal" class="form-control bg-dark text-white border-secondary" placeholder="Jarak (KM)" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="button" onclick="triggerSearchModal()"><i class="fas fa-search"></i> Cari</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-dark table-hover table-bordered mb-0" style="font-size: 12px; white-space: nowrap; text-align: center;">
                        <thead style="background-color: #1c1e22; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th>dtl_cusno</th>
                                <th class="text-left">dtl_namamember</th>
                                <th>mr</th>
                                <th>jarak</th>
                                <th>struk</th>
                                <th class="text-right">dtl_gross</th>
                                <th class="text-right">avg_gross_per_struk</th>
                                <th>dtl_margin_persen</th>
                                <th class="text-right">total_ongkir</th>
                                <th class="text-warning"><i class="fas fa-hand-pointer"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-list-member">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Memuat data member...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    <ul class="pagination pagination-sm mb-0" id="pagination-modal-member">
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>