<div class="container-fluid px-4 py-5">
    <div class="row g-4">
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <h5 class="card-header d-flex justify-content-between align-items-center">
                    <span class="glitch-text" data-text="CEK STATUS IAS">CEK STATUS IAS</span>
                    <span class="badge bg-secondary" id="ias-last-updated"></span>
                </h5>
                <div class="card-body d-flex flex-column">
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Submenu</th>
                                    <th scope="col">Endtime</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody id="status-ias"></tbody>
                        </table>
                    </div>
                    <a href="#" id="refresh_status_ias" class="btn btn-primary mt-auto"> <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-arrow-clockwise"></i> <span class="button-text">Refresh</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <h5 class="card-header d-flex justify-content-between align-items-center">
                    <span class="glitch-text" data-text="CEK SETTING PAGI">CEK SETTING PAGI</span>
                    <span class="badge bg-secondary" id="pagi-last-updated"></span>
                </h5>
                <div class="card-body d-flex flex-column">
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody id="status-setting-pagi">
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Awaiting Command...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="#" id="setting_pagi" class="btn btn-primary mt-auto">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-card-list"></i> <span class="button-text">Execute</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <h5 class="card-header d-flex justify-content-between align-items-center">
                    <span class="glitch-text" data-text="DTA4 JOB LOG">DTA4 JOB LOG</span>
                    <span class="badge bg-secondary" id="dta-last-updated"></span>
                </h5>
                <div class="card-body d-flex flex-column">
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Job Name</th>
                                    <th scope="col">Execution Time</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody id="status-dta"></tbody>
                        </table>
                    </div>
                    <a href="#" id="refresh_dta" class="btn btn-primary mt-auto">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-arrow-clockwise"></i> <span class="button-text">Refresh</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-12 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <h5 class="card-header d-flex justify-content-between align-items-center">
                    <span class="glitch-text" data-text="TF FILE">TF FILE</span>
                    <span class="badge bg-secondary" id="tf-file-last-updated"></span>
                </h5>
                <div class="card-body d-flex flex-column">
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Namaprog</th>
                                    <th scope="col">Namafile</th>
                                    <th scope="col">Namadbf</th>
                                    <th scope="col">flag</th>
                                    <th scope="col">Jam mulai</th>
                                    <th scope="col">Create by</th>
                                    <th scope="col">Create dt</th>
                                    <th scope="col">Modify by</th>
                                    <th scope="col">Msodify dt</th>
                                </tr>
                            </thead>
                            <tbody id="TF_FILE">
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Awaiting Command...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="#" id="tf_exec" class="btn btn-primary mt-auto">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-card-list"></i> <span class="button-text">Execute</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css">
<script src="opredp/scriptcekpagi.js"></script>