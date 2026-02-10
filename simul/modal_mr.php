<div class="modal fade" id="modalDetailMember" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content modal-content-modern">

            <div class="modal-header modal-header-modern">
                <div>
                    <h5 class="modal-title font-weight-bold text-white" id="modalDetailTitle">Detail Member</h5>
                    <small class="text-muted">Daftar toko yang telah dicover</small>
                </div>
                <button type="button" class="btn-close-modern" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body modal-body-custom">
                <div class="table-responsive">
                    <table class="table table-detail" width="100%">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="15%">Kode</th>
                                <th width="25%">Nama Member</th>
                                <th width="40%">Alamat</th>
                                <th width="15%" class="text-center">Tgl Reg</th>
                            </tr>
                        </thead>
                        <tbody id="body_detail_member">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top-0 d-flex justify-content-between align-items-center" style="padding: 15px 25px; background-color: #1e1e2d;">
                <div class="text-muted small" id="pageInfo">
                    Menampilkan 0 - 0 dari 0 data
                </div>

                <div id="paginationControls" class="pagination-container">
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>