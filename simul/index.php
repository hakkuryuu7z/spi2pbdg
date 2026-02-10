<div class="row mb-3 align-items-center">
    <div class="col-md-3">
        <button id="export-excel-btn" class="btn btn-success btn-block">
            <i class="fas fa-file-excel"></i> Simpan ke Excel
        </button>
    </div>

    <div class="col-md-9">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text dark-bg text-light">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <input type="text" id="searchInput" class="form-control search-input" placeholder="Cari berdasarkan PLU atau Deskripsi Barang atau supp...">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="pareto-table">
                <thead class="thead-dark">
                    <tr class="text-center">
                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="0">
                            # <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="string" data-column="1" style="min-width: 100px;">
                            PLU <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="string" data-column="2" style="min-width: 300px;">
                            Deskripsi Barang <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="string" data-column="3">
                            Unit <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="4">
                            Frac <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="5">
                            Pkm <i class="fas fa-sort"></i>
                        </th>

                        <th colspan="2">AVG</th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="8" style="min-width: 120px;">
                            Acost <i class="fas fa-sort"></i>
                        </th>

                        <th colspan="2">PB</th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="11">
                            PO OUT <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="string" data-column="12">
                            NO PO <i class="fas fa-sort"></i>
                        </th>

                        <th colspan="2">STOK</th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="15">
                            LPP PCS <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="number" data-column="16" style="min-width: 80px;">
                            DSI <i class="fas fa-sort"></i>
                        </th>

                        <th rowspan="2" class="align-middle sortable" data-sort="string" data-column="17" style="min-width: 200px;">
                            SUPP <i class="fas fa-sort"></i>
                        </th>
                    </tr>
                    <tr class="text-center">
                        <th class="sortable" data-sort="number" data-column="6">
                            BLN <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="number" data-column="7">
                            HARI <i class="fas fa-sort"></i>
                        </th>

                        <th class="sortable" data-sort="input" data-column="9">
                            CTN <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="number" data-column="10">
                            PCS <i class="fas fa-sort"></i>
                        </th>

                        <th class="sortable" data-sort="number" data-column="13">
                            CTN <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="number" data-column="14">
                            PCS <i class="fas fa-sort"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="pareto-tbody">
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
<script src="jquery/jquery-3.7.1.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="simul/script.js"></script>