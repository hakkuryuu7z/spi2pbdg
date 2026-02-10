<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        Filter Data Transaksi
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-3">
                <label for="filterTanggal" class="form-label fw-bold">Pilih Tanggal</label>
                <input type="date" id="filterTanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="col-md-5">
                <label for="filterMember" class="form-label fw-bold">Kode Member</label>
                <input type="text" id="filterMember" class="form-control" placeholder="Contoh: MEM001, MEM002 (Pisahkan dengan koma)">
                <small class="text-muted">Kosongkan untuk melihat semua transaksi hari itu.</small>
            </div>

            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" onclick="loadDataTransaksi()">
                    <i class="fa fa-search"></i> Cari Data
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <table id="tableKasir" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th>Cashier ID</th>
                    <th>No Transaksi</th>
                    <th>Tanggal (Waktu)</th>
                    <th>Kode Member</th>
                    <th class="text-right">Total Belanja (Rp)</th>
                </tr>
            </thead>
            <tbody id="tableBodyKasir">
            </tbody>
        </table>

    </div>
</div>