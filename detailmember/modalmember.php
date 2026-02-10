<div class="modal fade" id="detailKecamatanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modalSubTitle"></p>
                <div class="table-responsive">
                    <table class="table table-hover table-dark table-striped mb-0" style="background: transparent;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.2);">
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Member</th>
                                <th>Salesman</th>
                                <th>Status</th>
                                <th>Tgl Aktif</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailKecamatanjarakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalTitlejarak"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modalSubTitlejarak"></p>
                <div class="table-responsive">
                    <table class="table table-hover table-dark table-striped mb-0" style="background: transparent;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.2);">
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kecamatan</th>
                                <th>Salesman</th>
                                <th>Status</th>
                                <th>Tgl Aktif</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBodyjarak"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailMrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content modal-content-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalTitleMr"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modalSubTitleMr"></p>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-modern-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="mrSearchInput" class="input-modern" placeholder="Cari nama atau kode member...">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="custom-control custom-checkbox text-white">
                            <input type="checkbox" class="custom-control-input" id="mrFilterCheckbox">
                            <label class="custom-control-label" for="mrFilterCheckbox">Hanya "No Transaksi"</label>
                        </div>
                    </div>
                </div>
                <div id="modalBodyContentMr"></div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <a href="#" id="downloadMrExcelBtn" class="btn btn-success btn-sm"><i class="fas fa-file-excel mr-1"></i> Excel</a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>