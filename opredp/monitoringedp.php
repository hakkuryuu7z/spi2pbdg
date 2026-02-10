<div class="monitoring-group">
    <h2 class="group-title">> CEK MONTHEND - <span id="BULANTAHUN_SEKARANG"></span></h2>
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-search-dollar card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">CEK CASHBACK</div>
                        <div class="h4 data-value" id="CEK_CASHBACK">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-tag card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">BASEPRICE 0 / NULL</div>
                        <div class="h4 data-value" id="BASEPRICE">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-toggle-off card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">STATUS Z KOSONG</div>
                        <div class="h4 data-value" id="STATUS_Z">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-hashtag card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">RECORDID ID = 2</div>
                        <div class="h4 data-value" id="RECORDID_ID">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-dolly card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">INTRANSIT ≠ 0</div>
                        <div class="h4 data-value" id="INTRANSIT">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-check-circle card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">INTRANSIT ≠ 0 + RECID BERES</div>
                        <div class="h4 data-value" id="INTRANSIT_BERES">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-chart-line card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">MARGIN MINUS</div>
                        <div class="h4 data-value" id="MARGIN_MINUS">LOADING...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="monitoring-group">
    <h2 class="group-title">> DAILY SECURITY CHECK</h2>
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-shield-alt card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">LOCKING</div>
                        <div class="h4 data-value" id="locking">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-file-contract card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">INTRUSION LOGS</div>
                        <div class="h4 data-value" id="intrusionLogs">0 Alerts</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <i class="fas fa-database card-icon"></i>
                    <div class="info-text">
                        <div class="text-label">DB PGSQL (172.31.147.216)</div>
                        <div class="h4 data-value" id="DB_PGSQL_STATUS">CHECKING...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('modalmonitoringedp.php') ?>

<script>
    const options = {
        year: 'numeric',
        month: 'long'
    };
    document.getElementById('BULANTAHUN_SEKARANG').textContent = new Date().toLocaleDateString('id-ID', options).toUpperCase();
</script>
<script src="opredp/scriptmonitoringedp.js"></script>