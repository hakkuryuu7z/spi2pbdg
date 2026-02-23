<div class="row mb-3">
    <div class="col-12">
        <form id="form-filter-ongkir" class="form-inline">
            <div class="form-group mr-2">
                <label for="start_date" class="mr-2">Periode:</label>
                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="form-group mr-2">
                <label for="end_date" class="mr-2">s/d</label>
                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="background-color: #2b2f33; border: 1px solid #444;">
            <div class="card-body">
                <div class="row" id="ongkir-summary-container">
                    <div class="col-12 text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat ringkasan...
                    </div>
                </div>

                <div id="ongkir-vs-margin-chart" style="min-height: 450px; margin-top: 20px;"></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card" style="background-color: #343a40; color: white;">
            <div class="card-header border-bottom-0">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-table mr-2"></i> Perbandingan Jarak, Margin, dan Ongkir
                </h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-dark table-striped table-hover m-0">
                    <thead style="background-color: #23272b;">
                        <tr>
                            <th class="text-center align-middle">Jarak (KM)</th>
                            <th class="text-center align-middle">Juml MM</th>
                            <th class="text-right align-middle">Belanja (Rp)</th>
                            <th class="text-right align-middle text-success">Margin Kotor (Rp)</th>
                            <th class="text-right align-middle text-warning">Ongkir (Rp)</th>
                            <th class="text-right align-middle text-info">Net Margin (Rp)</th>
                            <th class="text-right align-middle">Avg Sales (Rp)</th>
                            <th class="text-center align-middle">Rasio Kotor</th>
                            <th class="text-center align-middle">Rasio Net</th>
                            <th class="text-center align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-ongkir-margin">
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data tabel...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "ongkir_vs_margin/modal.php" ?>