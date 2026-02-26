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
<div class="row mt-4 mb-5">
    <div class="col-12">
        <div class="card" style="background-color: #343a40; color: white;">
            <div class="card-header border-bottom-0">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-calculator mr-2 text-warning"></i> Simulasi Ongkir vs Margin by Jarak
                </h3>
            </div>
            <div class="card-body">

                <form id="form-simulasi" class="form-inline mb-4 p-3 rounded" style="background-color: #2b2f33; border: 1px solid #444;">

                    <div class="form-group mr-3 mb-2">
                        <label for="sim_kode_member" class="mr-2 text-info font-weight-bold">Member:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="sim_kode_member" placeholder="Pilih Member..." readonly required style="width: 120px; background-color: #1c1e22; color: #fff; border-color: #555; cursor: pointer;" onclick="openModalMember()">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="button" onclick="openModalMember()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mr-3 mb-2 border-left pl-3" style="border-color: #555 !important;">
                        <label for="sim_tarif" class="mr-2">Tarif/KM (Rp):</label>
                        <input type="number" class="form-control form-control-sm" id="sim_tarif" value="7500" required style="width: 90px;">
                    </div>
                    <div class="form-group mr-3 mb-2">
                        <label for="sim_margin" class="mr-2">Margin (%):</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="sim_margin" value="2.50" required style="width: 70px;">
                    </div>

                    <div class="form-group mr-3 mb-2 border-left pl-3" style="border-color: #555 !important;">
                        <label for="sim_mode" class="mr-2 text-warning">Metode:</label>
                        <select class="form-control form-control-sm bg-dark text-warning border-secondary" id="sim_mode" onchange="toggleSimMode()">
                            <option value="cari_margin">Set Sales ➔ Cari Margin</option>
                            <option value="cari_sales">Set Margin ➔ Cari Sales</option>
                        </select>
                    </div>

                    <div class="form-group mr-3 mb-2" id="wrap_sim_sales">
                        <input type="number" class="form-control form-control-sm border-info" id="sim_sales" value="100000" placeholder="Nominal Sales" style="width: 120px;">
                    </div>

                    <div class="form-group mr-3 mb-2" id="wrap_sim_margin_rp" style="display: none;">
                        <input type="number" class="form-control form-control-sm border-success" id="sim_margin_rp" value="50000" placeholder="Nominal Margin" style="width: 120px;">
                        <button type="button" class="btn btn-sm btn-outline-warning ml-2 py-0" onclick="setSimulasiBEP()" title="Hitung sales minimum agar Margin sama dengan Ongkir (Tutup Ongkir)">
                            <i class="fas fa-balance-scale"></i> BEP
                        </button>
                    </div>

                    <button type="submit" class="btn btn-sm btn-success mb-2 ml-auto" id="btn-tarik-simulasi">
                        <i class="fas fa-sync-alt"></i> Tarik Data DB
                    </button>
                </form>

                <div class="table-responsive">
                    <table class="table table-dark table-bordered table-hover m-0" style="font-size: 13px; text-align: center; vertical-align: middle;">
                        <thead style="background-color: #23272b;">
                            <tr>
                                <th colspan="9" class="text-center" style="border-bottom: 2px solid #555;">Data Member Aktual (Dari Transaksi)</th>
                                <th colspan="6" class="text-center text-warning" style="border-bottom: 2px solid #555; background-color: #1c1e22;">
                                    <i class="fas fa-bolt"></i> Output Simulasi Real-time
                                </th>
                            </tr>
                            <tr>
                                <th>Kode Member</th>
                                <th>Nama Member</th>
                                <th>MR</th>
                                <th>Jarak</th>
                                <th>Struk</th>
                                <th>Gross (Rp)</th>
                                <th>Avg Gross/Struk</th>
                                <th>Margin %</th>
                                <th>Total Ongkir (Rp)</th>

                                <th style="background-color: #1c1e22;">Satuan / KM</th>
                                <th style="background-color: #1c1e22;">Ongkir per Jarak</th>
                                <th style="background-color: #1c1e22;">Simulasi Sales</th>
                                <th style="background-color: #1c1e22;">Set Margin %</th>
                                <th style="background-color: #1c1e22;">Margin Rupiah</th>
                                <th style="background-color: #1c1e22;">Ongkir vs Margin</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-simulasi">
                            <tr>
                                <td colspan="15" class="text-center text-muted py-4">
                                    Silakan pilih Member lalu klik tombol <b>Tarik Data DB</b>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "ongkir_vs_margin/modal.php" ?>