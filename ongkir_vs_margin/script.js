document.addEventListener("DOMContentLoaded", function () {
    // 1. ================= HELPER FUNCTION =================
    function formatRingkas(val) {
        if (typeof val !== 'number') val = parseFloat(val);
        if (isNaN(val)) return "Rp 0";
        if (val >= 1000000000) return "Rp " + (val / 1000000000).toFixed(2) + " M";
        if (val >= 1000000) return "Rp " + (val / 1000000).toFixed(2) + " Jt";
        return "Rp " + Math.round(val).toLocaleString('id-ID');
    }

    function formatDetail(val) {
        if (typeof val !== 'number') val = parseFloat(val);
        if (isNaN(val)) return "Rp 0";
        return "Rp " + Math.round(val).toLocaleString('id-ID');
    }


    // 2. ================= KONFIGURASI CHART =================
    let ongkirMarginChart;
    const ongkirMarginOptions = {
        series: [],
        chart: {
            type: 'bar', height: 450, background: 'transparent', toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    let selectedIndex = config.dataPointIndex;
                    let salesmanName = config.w.config.xaxis.categories[selectedIndex];
                    if(salesmanName) {
                        window.showDetailOngkirMargin(salesmanName);
                    }
                }
            }
        },
        colors: ['#FF4560', '#008FFB'], // Merah (Ongkir), Biru (Margin)
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4, dataLabels: { position: 'top' } } },
        dataLabels: {
            enabled: true,
            formatter: function (val) { if(val > 0) return formatRingkas(val).replace("Rp ", ""); return ""; },
            style: { fontSize: '10px', colors: ["#fff"] }, offsetY: -20,
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.8 }
        },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: [], labels: { style: { colors: '#E0E0E0', fontSize: '11px', fontWeight: 'bold' }, rotate: -45 }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { colors: '#A0A0A0' }, formatter: (val) => formatRingkas(val).replace("Rp ", "") }, title: { text: 'Nominal (Rp)', style: { color: '#777' } } },
        grid: { borderColor: 'rgba(255, 255, 255, 0.05)', xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } } },
        theme: { mode: 'dark' }, legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { theme: 'dark', shared: true, intersect: false, y: { formatter: function (val) { return formatDetail(val); } } }
    };

    // Render Chart Kosong Pertama Kali
    if (document.querySelector("#ongkir-vs-margin-chart")) {
        ongkirMarginChart = new ApexCharts(document.querySelector("#ongkir-vs-margin-chart"), ongkirMarginOptions);
        ongkirMarginChart.render();
    }


    // 3. ================= FETCH API DATA =================
    // Fungsi Load Data Tabel Jarak
    function loadTableData(startDate, endDate) {
        const tbody = document.getElementById("tbody-ongkir-margin");
        tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data tabel...</td></tr>`;

        // URL API Jarak
        fetch(`ongkir_vs_margin/api_get_perbandingan_jarak_margin_ongkir.php?start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(res => {
                tbody.innerHTML = "";
                if (res.status === "success" && res.data.length > 0) {
                    let rows = "";
                    res.data.forEach(item => {
                        let textJarak = item.jarak === ">20" ? "> 20" : `<= ${item.jarak}`;
                        
                        // Deteksi warna untuk margin bersih (merah jika rugi)
                        let colorNetMargin = item.rasio_net < 0 ? 'text-danger font-weight-bold' : 'text-info font-weight-bold';
                        let badgeRasioNet  = item.rasio_net < 0 ? 'badge-danger' : 'badge-info';

                        rows += `
                            <tr>
                                <td class="text-center align-middle font-weight-bold">${textJarak}</td>
                                <td class="text-center align-middle">${item.juml_mm}</td>
                                <td class="text-right align-middle">${item.member_belanja}</td>
                                <td class="text-right align-middle text-success font-weight-bold">${item.margin}</td>
                                <td class="text-right align-middle text-warning">${item.ongkir}</td>
                                <td class="text-right align-middle ${colorNetMargin}">${item.net_margin}</td>
                                <td class="text-right align-middle">${item.avg_sales}</td>
                                <td class="text-center align-middle"><span class="badge badge-success px-2 py-1">${item.rasio}%</span></td>
                                <td class="text-center align-middle"><span class="badge ${badgeRasioNet} px-2 py-1">${item.rasio_net}%</span></td>
                                <td class="text-center align-middle">
                                    <button class="btn btn-xs btn-outline-info" onclick="showDetailJarak('${item.jarak}')" title="Lihat Detail">
                                        <i class="fas fa-search"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = rows;
                } else {
                    tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">Data tabel tidak ditemukan.</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger py-4">Gagal memuat tabel.</td></tr>`;
            });
    }

    // Fungsi Load Data Chart MR (Salesman)
    function loadChartData(startDate, endDate) {
        $('#ongkir-summary-container, #ongkir-vs-margin-chart').css('opacity', '0.5');

        // URL API Chart MR
        fetch(`ongkir_vs_margin/api_get_perbandingan_ongkir_vs_margin_mr.php?start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(res => {
                $('#ongkir-summary-container, #ongkir-vs-margin-chart').css('opacity', '1');

                if (res.status === 'success') {
                    // Update Grafik
                    ongkirMarginChart.updateOptions({ xaxis: { categories: res.chart.categories } });
                    ongkirMarginChart.updateSeries(res.chart.series);

                   // Update Box Summary
                    let sum = res.summary;
                    let ratioColor = parseFloat(sum.total_ratio_persen) > 10 ? 'text-danger' : 'text-success'; 

                    let html = `
                        <div class="col-lg col-md-4 col-sm-6 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(255, 69, 96, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FF4560;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">TOTAL ONGKIR</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_ongkir).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_ongkir)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg col-md-4 col-sm-6 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(0, 143, 251, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #008FFB;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">TOTAL MARGIN</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_margin).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_margin)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg col-md-4 col-sm-6 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(0, 227, 150, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #00E396;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">MARGIN TIPE KIRIM</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_margin_kirim).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_margin_kirim)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg col-md-4 col-sm-6 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(254, 176, 25, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FEB019;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">MARGIN TIPE ADT</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_margin_at).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_margin_at)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg col-md-4 col-sm-6 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: #1c1e22; border: 1px solid #444;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">RATIO (ONGKIR / MARGIN)</span>
                                <div class="d-flex align-items-center mt-1">
                                    <h3 class="mb-0 font-weight-bold ${ratioColor}">${sum.total_ratio_persen}</h3>
                                    <i class="fas fa-percent ml-auto text-secondary" style="opacity: 0.3; font-size: 1.5rem;"></i>
                                </div>
                                <div class="mt-2 text-secondary" style="font-size: 11px;">
                                    Semakin kecil %, semakin efisien.
                                </div>
                            </div>
                        </div>
                    `;
                    $('#ongkir-summary-container').html(html);
                }
            })
            .catch(err => {
                console.error("Error loading Chart:", err);
                $('#ongkir-summary-container').html('<div class="col-12 text-center text-danger">Gagal memuat grafik.</div>');
            });
    }

    // 4. ================= TRIGGER FUNGSI (FILTER & LOAD) =================
    
    // Fungsi pemanggil utama
    function refreshAllData() {
        const startDate = document.getElementById("start_date").value;
        const endDate = document.getElementById("end_date").value;
        
        loadTableData(startDate, endDate);
        loadChartData(startDate, endDate);
    }

    // Panggil saat halaman pertama dibuka
    refreshAllData();

    // Panggil saat tombol filter di-klik
    document.getElementById("form-filter-ongkir").addEventListener("submit", function (e) {
        e.preventDefault();
        refreshAllData();
    });
    // ===================================================================
    // LOGIKA MODAL DETAIL SALES & MARGIN (MEMBER vs NO PB)
    // ===================================================================

    let cachedOngkirData = [];
    let currentFilteredOngkirData = [];
    let ongkirPage = 1;
    const ongkirPerPage = 10;
    let currentOngkirMode = 'member';
    let currentSalesmanName = '';

    // State Sorting
    let currentSortKey = 'margin'; 
    let currentSortDir = 'desc';   

    // FUNGSI BUKA MODAL
    window.showDetailOngkirMargin = function(salesmanName) {
        currentSalesmanName = salesmanName;
        currentSortKey = 'margin';
        currentSortDir = 'desc';
        currentOngkirMode = 'member';
        
        $('#btn-mode-member').addClass('active');
        $('#btn-mode-struk').removeClass('active');
        $('#search-ongkir-margin').val('');
        
        fetchDataOngkir();
        $('#modalDetailOngkirMargin').modal('show');
    }

    // FUNGSI GANTI MODE (Member / Struk)
    window.switchOngkirMode = function(mode) {
        if (currentOngkirMode === mode) return;
        currentOngkirMode = mode;
        
        if(mode === 'member') {
            $('#btn-mode-member').addClass('active');
            $('#btn-mode-struk').removeClass('active');
        } else {
            $('#btn-mode-struk').addClass('active');
            $('#btn-mode-member').removeClass('active');
        }

        currentSortKey = 'margin';
        currentSortDir = 'desc';
        fetchDataOngkir();
    }

    // FUNGSI KLIK SORTING HEADER
    window.handleSort = function(key) {
        if (currentSortKey === key) {
            currentSortDir = (currentSortDir === 'asc') ? 'desc' : 'asc';
        } else {
            currentSortKey = key;
            currentSortDir = 'desc';
        }
        applyFilterOngkir(); 
    }

    // FETCH DATA KE API
    function fetchDataOngkir() {
        ongkirPage = 1;
        let s = $('#start_date').val(); // Ambil dari input filter halaman
        let e = $('#end_date').val();

        $('#modal-ongkir-mr-name').text(currentSalesmanName);
        $('#info-periode-ongkir').text(`Periode: ${s} s/d ${e}`);
        
        renderTableHeader(); 
        $('#table-detail-ongkir-margin tbody').html('<tr><td colspan="100%" class="text-center py-5"><div class="spinner-border text-info mb-3"></div><h5 class="text-muted">Memuat data...</h5></td></tr>');

        let url = `ongkir_vs_margin/api_get_detail_perbandingan_ongkir_vs_margin_mr.php?salesman=${encodeURIComponent(currentSalesmanName)}&start_date=${s}&end_date=${e}&mode=${currentOngkirMode}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cachedOngkirData = res.data;
                    applyFilterOngkir();
                } else {
                    cachedOngkirData = [];
                    currentFilteredOngkirData = [];
                    renderTableOngkir();
                }
            })
            .catch(err => {
                console.error(err);
                $('#table-detail-ongkir-margin tbody').html('<tr><td colspan="100%" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data dari server.</td></tr>');
            });
    }

    // FILTER PENCARIAN & SORTING
    $('#search-ongkir-margin').keyup(function() {
        applyFilterOngkir();
    });

    function applyFilterOngkir() {
        let term = $('#search-ongkir-margin').val().toLowerCase();
        
        // Filter
        currentFilteredOngkirData = cachedOngkirData.filter(item => {
            let text = `${item.kode_member} ${item.nama_member} ${item.outlet}`.toLowerCase();
            if (currentOngkirMode === 'struk') text += ` ${item.nopb}`;
            return term === '' || text.includes(term);
        });

        // Sorting
        currentFilteredOngkirData.sort((a, b) => {
            let valA = a[currentSortKey];
            let valB = b[currentSortKey];

            let numA = parseFloat(valA);
            let numB = parseFloat(valB);
            
            if (!isNaN(numA) && !isNaN(numB) && isFinite(valA) && isFinite(valB)) {
                valA = numA; valB = numB;
            } else {
                valA = valA ? valA.toString().toLowerCase() : '';
                valB = valB ? valB.toString().toLowerCase() : '';
            }

            if (valA < valB) return currentSortDir === 'asc' ? -1 : 1;
            if (valA > valB) return currentSortDir === 'asc' ? 1 : -1;
            return 0;
        });

        ongkirPage = 1;
        renderTableOngkir();
    }

    // RENDER HEADER TABEL
    function renderTableHeader() {
        let thead = $('#thead-ongkir-margin');
        
        const th = (key, label, align = 'text-center', width='') => {
            let sortIcon = '<i class="fas fa-sort" style="opacity:0.3; margin-left:5px;"></i>';
            if (currentSortKey === key) {
                sortIcon = currentSortDir === 'asc' 
                    ? '<i class="fas fa-sort-up" style="opacity:1; color:#17a2b8; margin-left:5px;"></i>' 
                    : '<i class="fas fa-sort-down" style="opacity:1; color:#17a2b8; margin-left:5px;"></i>';
            }
            return `<th class="${align} align-middle" width="${width}" onclick="handleSort('${key}')" style="cursor:pointer; user-select:none;">
                        ${label} ${sortIcon}
                    </th>`;
        };

        let html = '<tr><th class="text-center align-middle" width="4%">No</th>'; 
        if (currentOngkirMode === 'member') {
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '20%');
            html += th('struk', 'Struk', 'text-center', '6%');
            html += th('netto', 'Netto (Rp)', 'text-right', '12%');
            html += th('margin', 'Margin (Rp)', 'text-right', '12%');
            html += th('margin_persen', 'Mgn %', 'text-center', '6%');
            html += th('ongkir', 'Ongkir (Rp)', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '8%');
            html += th('outlet', 'Outlet', 'text-center', '8%');
        } else {
            html += th('nopb', 'No PB', 'text-left', '12%');
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '15%');
            html += th('netto', 'Netto (Rp)', 'text-right', '12%');
            html += th('margin', 'Margin (Rp)', 'text-right', '12%');
            html += th('margin_persen', 'Mgn %', 'text-center', '6%');
            html += th('ongkir', 'Ongkir (Rp)', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '8%');
            html += th('status', 'Status', 'text-center', '8%');
            html += th('armada', 'Armada', 'text-center', '10%');
        }
        html += '</tr>';
        thead.html(html);
    }

    // RENDER ISI TABEL
    function renderTableOngkir() {
        renderTableHeader(); 
        let tbody = $('#table-detail-ongkir-margin tbody').empty();

        let totalData = currentFilteredOngkirData.length;
        let labelTotal = currentOngkirMode === 'member' ? 'Member' : 'No PB';
        $('#info-total-ongkir-margin').text(`Total: ${totalData} ${labelTotal}`);

        if (totalData === 0) {
            tbody.html('<tr><td colspan="100%" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr>');
            $('#pagination-ongkir-container').empty();
            return;
        }

        const start = (ongkirPage - 1) * ongkirPerPage;
        const pageData = currentFilteredOngkirData.slice(start, start + ongkirPerPage);
        let rows = '';

        const fmt = (val) => parseFloat(val || 0).toLocaleString('id-ID');

        pageData.forEach((item, i) => {
            let marginPct = parseFloat(item.margin_persen || 0);
            let ongkirVal = parseFloat(item.ongkir || 0);
            let pctColor = marginPct < 0 ? 'text-danger' : (marginPct > 15 ? 'text-success' : 'text-warning');
            
            rows += `<tr><td class="text-center text-muted align-middle">${start + i + 1}</td>`;

            if (currentOngkirMode === 'member') {
                rows += `
                    <td class="align-middle"><span class="badge badge-dark border border-secondary p-1">${item.kode_member}</span></td>
                    <td class="align-middle font-weight-bold text-truncate" style="max-width: 200px;" title="${item.nama_member}">${item.nama_member || '-'}</td>
                    <td class="align-middle text-center">${item.struk}</td>
                    <td class="align-middle text-right text-info font-weight-bold">${fmt(item.netto)}</td>
                    <td class="align-middle text-right text-success font-weight-bold">${fmt(item.margin)}</td>
                    <td class="align-middle text-center font-weight-bold ${pctColor}">${marginPct}%</td>
                    <td class="align-middle text-right text-danger">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="align-middle text-center font-weight-bold">${item.ratio_om}%</td> 
                    <td class="align-middle text-center"><small>${item.outlet} (${item.suboutlet})</small></td>
                `;
            } else {
                rows += `
                    <td class="align-middle"><span class="text-info font-weight-bold">${item.nopb}</span></td>
                    <td class="align-middle"><small>${item.kode_member}</small></td>
                    <td class="align-middle text-truncate" style="max-width: 150px;">${item.nama_member}</td>
                    <td class="align-middle text-right text-info font-weight-bold">${fmt(item.netto)}</td>
                    <td class="align-middle text-right text-success font-weight-bold">${fmt(item.margin)}</td>
                    <td class="align-middle text-center font-weight-bold ${pctColor}">${marginPct}%</td>
                    <td class="align-middle text-right text-danger">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="align-middle text-center font-weight-bold">${item.ratio_om}%</td> 
                    <td class="align-middle text-center"><span class="badge badge-secondary px-2 py-1">${item.status}</span></td>
                    <td class="align-middle text-center"><small>${item.armada || '-'}</small></td>
                `;
            }
            rows += `</tr>`;
        });
        tbody.html(rows);
        renderOngkirPagination();
    }

    // PAGINATION
    window.changeOngkirPage = function(p) {
        const total = Math.ceil(currentFilteredOngkirData.length / ongkirPerPage);
        if (p < 1 || p > total) return;
        ongkirPage = p;
        renderTableOngkir();
    }

    function renderOngkirPagination() {
        const totalPages = Math.ceil(currentFilteredOngkirData.length / ongkirPerPage);
        const container = $('#pagination-ongkir-container').empty();
        if (totalPages <= 1) return;

        let prevDis = ongkirPage === 1 ? 'disabled' : '';
        container.append(`<li class="page-item ${prevDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeOngkirPage(${ongkirPage-1})">&laquo;</a></li>`);

        let startPage = Math.max(1, ongkirPage - 2);
        let endPage = Math.min(totalPages, ongkirPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            let act = i === ongkirPage ? 'active bg-info border-info' : ''; 
            container.append(`<li class="page-item ${act}"><a class="page-link ${i === ongkirPage ? 'bg-info text-white' : 'bg-dark text-white'} border-secondary" href="javascript:void(0)" onclick="changeOngkirPage(${i})">${i}</a></li>`);
        }

        let nextDis = ongkirPage === totalPages ? 'disabled' : '';
        container.append(`<li class="page-item ${nextDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeOngkirPage(${ongkirPage+1})">&raquo;</a></li>`);
    }

    // EXPORT TO EXCEL
    window.exportOngkirMarginToExcel = function() {
        if(currentFilteredOngkirData.length === 0) { alert("Tidak ada data untuk diexport!"); return; }
        
        let headers = '';
        if (currentOngkirMode === 'member') {
            headers = `<th>Kode</th><th>Nama</th><th>Struk</th><th>Netto</th><th>Margin</th><th>Mgn %</th><th>Ongkir</th><th>Ratio</th><th>Outlet</th>`;
        } else {
            headers = `<th>No PB</th><th>Kode</th><th>Nama</th><th>Netto</th><th>Margin</th><th>Mgn %</th><th>Ongkir</th><th>Ratio</th><th>Status</th><th>Armada</th>`;
        }

        let html = `<table border="1"><thead><tr style="background:#f2f2f2"><th>No</th>${headers}</tr></thead><tbody>`;
        
        currentFilteredOngkirData.forEach((d, i) => {
            html += `<tr><td>${i+1}</td>`;
            if (currentOngkirMode === 'member') {
                html += `<td style="mso-number-format:'\@'">${d.kode_member}</td>
                        <td>${d.nama_member}</td>
                        <td>${d.struk}</td>
                        <td style="mso-number-format:'0.00'">${d.netto}</td>
                        <td style="mso-number-format:'0.00'">${d.margin}</td>
                        <td style="mso-number-format:'0.00'">${d.margin_persen}</td>
                        <td style="mso-number-format:'0.00'">${d.ongkir}</td>
                        <td style="mso-number-format:'0.00'">${d.ratio_om}</td>
                        <td>${d.outlet}</td>`;
            } else {
                html += `<td style="mso-number-format:'\@'">${d.nopb}</td>
                        <td style="mso-number-format:'\@'">${d.kode_member}</td>
                        <td>${d.nama_member}</td>
                        <td style="mso-number-format:'0.00'">${d.netto}</td>
                        <td style="mso-number-format:'0.00'">${d.margin}</td>
                        <td style="mso-number-format:'0.00'">${d.margin_persen}</td>
                        <td style="mso-number-format:'0.00'">${d.ongkir}</td>
                        <td style="mso-number-format:'0.00'">${d.ratio_om}</td>
                        <td>${d.status}</td>
                        <td>${d.armada}</td>`;
            }
            html += `</tr>`;
        });
        html += `</tbody></table>`;
        
        let blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Detail_Ongkir_${currentOngkirMode.toUpperCase()}_${currentSalesmanName}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }


    // ===================================================================
    // LOGIKA MODAL DETAIL JARAK
    // ===================================================================

    let cachedJarakData = [];
    let currentFilteredJarakData = [];
    let jarakPage = 1;
    const jarakPerPage = 10;
    let currentJarakMode = 'member';
    let currentJarakValue = '';

    // State Sorting Jarak
    let sortJarakKey = 'margin'; 
    let sortJarakDir = 'desc';   

    // 1. FUNGSI BUKA MODAL
    window.showDetailJarak = function(jarakValue) {
        currentJarakValue = jarakValue;
        sortJarakKey = 'margin';
        sortJarakDir = 'desc';
        currentJarakMode = 'member';
        
        $('#btn-mode-jarak-member').addClass('active');
        $('#btn-mode-jarak-struk').removeClass('active');
        $('#search-jarak').val('');
        
        let labelName = jarakValue === '>20' ? 'Lebih dari 20 KM' : `<= ${jarakValue} KM`;
        $('#modal-jarak-name').text(labelName);
        
        fetchDataJarak();
        $('#modalDetailJarak').modal('show');
    }

    // 2. FUNGSI GANTI MODE
    window.switchJarakMode = function(mode) {
        if (currentJarakMode === mode) return;
        currentJarakMode = mode;
        
        if(mode === 'member') {
            $('#btn-mode-jarak-member').addClass('active');
            $('#btn-mode-jarak-struk').removeClass('active');
        } else {
            $('#btn-mode-jarak-struk').addClass('active');
            $('#btn-mode-jarak-member').removeClass('active');
        }

        sortJarakKey = 'margin';
        sortJarakDir = 'desc';
        fetchDataJarak();
    }

    // 3. FUNGSI SORTING HEADER
    window.handleSortJarak = function(key) {
        if (sortJarakKey === key) {
            sortJarakDir = (sortJarakDir === 'asc') ? 'desc' : 'asc';
        } else {
            sortJarakKey = key;
            sortJarakDir = 'desc';
        }
        applyFilterJarak(); 
    }

    // 4. FETCH API JARAK
    function fetchDataJarak() {
        jarakPage = 1;
        let s = $('#start_date').val(); 
        let e = $('#end_date').val();

        $('#info-periode-jarak').text(`Periode: ${s} s/d ${e}`);
        
        renderTableJarakHeader(); 
        $('#table-detail-jarak tbody').html('<tr><td colspan="100%" class="text-center py-5"><div class="spinner-border text-success mb-3"></div><h5 class="text-muted">Memuat data jarak...</h5></td></tr>');

        // Note: URL di-encode agar string '>20' aman saat dikirim via GET
        let url = `ongkir_vs_margin/api_get_detail_perbandingan_jarak.php?jarak=${encodeURIComponent(currentJarakValue)}&start_date=${s}&end_date=${e}&mode=${currentJarakMode}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cachedJarakData = res.data;
                    applyFilterJarak();
                } else {
                    cachedJarakData = [];
                    currentFilteredJarakData = [];
                    renderTableJarak();
                }
            })
            .catch(err => {
                console.error(err);
                $('#table-detail-jarak tbody').html('<tr><td colspan="100%" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data dari server.</td></tr>');
            });
    }

    // 5. FILTER & SORTING (Search Bar)
    $('#search-jarak').keyup(function() {
        applyFilterJarak();
    });

    function applyFilterJarak() {
        let term = $('#search-jarak').val().toLowerCase();
        
        currentFilteredJarakData = cachedJarakData.filter(item => {
            let text = `${item.kode_member} ${item.nama_member} ${item.outlet}`.toLowerCase();
            if (currentJarakMode === 'struk') text += ` ${item.nopb}`;
            return term === '' || text.includes(term);
        });

        currentFilteredJarakData.sort((a, b) => {
            let valA = a[sortJarakKey];
            let valB = b[sortJarakKey];
            let numA = parseFloat(valA);
            let numB = parseFloat(valB);
            
            if (!isNaN(numA) && !isNaN(numB) && isFinite(valA) && isFinite(valB)) {
                valA = numA; valB = numB;
            } else {
                valA = valA ? valA.toString().toLowerCase() : '';
                valB = valB ? valB.toString().toLowerCase() : '';
            }
            if (valA < valB) return sortJarakDir === 'asc' ? -1 : 1;
            if (valA > valB) return sortJarakDir === 'asc' ? 1 : -1;
            return 0;
        });

        jarakPage = 1;
        renderTableJarak();
    }

    // 6. RENDER HEADER TABEL
    function renderTableJarakHeader() {
        let thead = $('#thead-jarak');
        
        const th = (key, label, align = 'text-center', width='') => {
            let sortIcon = '<i class="fas fa-sort" style="opacity:0.3; margin-left:5px;"></i>';
            if (sortJarakKey === key) {
                sortIcon = sortJarakDir === 'asc' 
                    ? '<i class="fas fa-sort-up" style="opacity:1; color:#28a745; margin-left:5px;"></i>' 
                    : '<i class="fas fa-sort-down" style="opacity:1; color:#28a745; margin-left:5px;"></i>';
            }
            return `<th class="${align} align-middle" width="${width}" onclick="handleSortJarak('${key}')" style="cursor:pointer; user-select:none;">
                        ${label} ${sortIcon}
                    </th>`;
        };

        let html = '<tr><th class="text-center align-middle" width="4%">No</th>'; 
        if (currentJarakMode === 'member') {
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '15%');
            html += th('jarak_member', 'Jarak (KM)', 'text-center', '8%');
            html += th('struk', 'Struk', 'text-center', '6%');
            html += th('netto', 'Netto (Rp)', 'text-right', '10%');
            html += th('margin', 'Margin (Rp)', 'text-right', '10%');
            html += th('ongkir', 'Ongkir (Rp)', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '8%');
            html += th('outlet', 'Outlet', 'text-center', '8%');
        } else {
            html += th('nopb', 'No PB', 'text-left', '12%');
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '15%');
            html += th('jarak_member', 'KM', 'text-center', '5%');
            html += th('netto', 'Netto (Rp)', 'text-right', '10%');
            html += th('margin', 'Margin (Rp)', 'text-right', '10%');
            html += th('ongkir', 'Ongkir (Rp)', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '6%');
            html += th('status', 'Status', 'text-center', '8%');
        }
        html += '</tr>';
        thead.html(html);
    }

    // 7. RENDER ISI TABEL
    function renderTableJarak() {
        renderTableJarakHeader(); 
        let tbody = $('#table-detail-jarak tbody').empty();
        let totalData = currentFilteredJarakData.length;
        let labelTotal = currentJarakMode === 'member' ? 'Member' : 'No PB';
        $('#info-total-jarak').text(`Total: ${totalData} ${labelTotal}`);

        if (totalData === 0) {
            tbody.html('<tr><td colspan="100%" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr>');
            $('#pagination-jarak-container').empty();
            return;
        }

        const start = (jarakPage - 1) * jarakPerPage;
        const pageData = currentFilteredJarakData.slice(start, start + jarakPerPage);
        let rows = '';

        const fmt = (val) => parseFloat(val || 0).toLocaleString('id-ID');

        pageData.forEach((item, i) => {
            let ongkirVal = parseFloat(item.ongkir || 0);
            
            rows += `<tr><td class="text-center text-muted align-middle">${start + i + 1}</td>`;

            if (currentJarakMode === 'member') {
                rows += `
                    <td class="align-middle"><span class="badge badge-dark border border-secondary p-1">${item.kode_member}</span></td>
                    <td class="align-middle font-weight-bold text-truncate" style="max-width: 200px;" title="${item.nama_member}">${item.nama_member || '-'}</td>
                    <td class="align-middle text-center text-info font-weight-bold">${item.jarak_member}</td>
                    <td class="align-middle text-center">${item.struk}</td>
                    <td class="align-middle text-right">${fmt(item.netto)}</td>
                    <td class="align-middle text-right text-success font-weight-bold">${fmt(item.margin)}</td>
                    <td class="align-middle text-right text-danger">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="align-middle text-center font-weight-bold">${item.ratio_om}%</td> 
                    <td class="align-middle text-center"><small>${item.outlet} (${item.suboutlet})</small></td>
                `;
            } else {
                rows += `
                    <td class="align-middle"><span class="text-info font-weight-bold">${item.nopb}</span></td>
                    <td class="align-middle"><small>${item.kode_member}</small></td>
                    <td class="align-middle text-truncate" style="max-width: 150px;">${item.nama_member}</td>
                    <td class="align-middle text-center text-info">${item.jarak_member}</td>
                    <td class="align-middle text-right">${fmt(item.netto)}</td>
                    <td class="align-middle text-right text-success font-weight-bold">${fmt(item.margin)}</td>
                    <td class="align-middle text-right text-danger">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="align-middle text-center font-weight-bold">${item.ratio_om}%</td> 
                    <td class="align-middle text-center"><span class="badge badge-secondary px-2 py-1">${item.status}</span></td>
                `;
            }
            rows += `</tr>`;
        });
        tbody.html(rows);
        renderJarakPagination();
    }

    // 8. PAGINATION JARAK
    window.changeJarakPage = function(p) {
        const total = Math.ceil(currentFilteredJarakData.length / jarakPerPage);
        if (p < 1 || p > total) return;
        jarakPage = p;
        renderTableJarak();
    }

    function renderJarakPagination() {
        const totalPages = Math.ceil(currentFilteredJarakData.length / jarakPerPage);
        const container = $('#pagination-jarak-container').empty();
        if (totalPages <= 1) return;

        let prevDis = jarakPage === 1 ? 'disabled' : '';
        container.append(`<li class="page-item ${prevDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeJarakPage(${jarakPage-1})">&laquo;</a></li>`);

        let startPage = Math.max(1, jarakPage - 2);
        let endPage = Math.min(totalPages, jarakPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            let act = i === jarakPage ? 'active bg-success border-success' : ''; 
            container.append(`<li class="page-item ${act}"><a class="page-link ${i === jarakPage ? 'bg-success text-white' : 'bg-dark text-white'} border-secondary" href="javascript:void(0)" onclick="changeJarakPage(${i})">${i}</a></li>`);
        }

        let nextDis = jarakPage === totalPages ? 'disabled' : '';
        container.append(`<li class="page-item ${nextDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeJarakPage(${jarakPage+1})">&raquo;</a></li>`);
    }

    // 9. EXPORT KE EXCEL
    window.exportJarakToExcel = function() {
        if(currentFilteredJarakData.length === 0) { alert("Tidak ada data untuk diexport!"); return; }
        
        let headers = '';
        if (currentJarakMode === 'member') {
            headers = `<th>Kode</th><th>Nama</th><th>Jarak (KM)</th><th>Struk</th><th>Netto</th><th>Margin</th><th>Mgn %</th><th>Ongkir</th><th>Ratio</th><th>Outlet</th>`;
        } else {
            headers = `<th>No PB</th><th>Kode</th><th>Nama</th><th>Jarak (KM)</th><th>Netto</th><th>Margin</th><th>Mgn %</th><th>Ongkir</th><th>Ratio</th><th>Status</th>`;
        }

        let html = `<table border="1"><thead><tr style="background:#f2f2f2"><th>No</th>${headers}</tr></thead><tbody>`;
        
        currentFilteredJarakData.forEach((d, i) => {
            html += `<tr><td>${i+1}</td>`;
            if (currentJarakMode === 'member') {
                html += `<td style="mso-number-format:'\@'">${d.kode_member}</td>
                        <td>${d.nama_member}</td>
                        <td>${d.jarak_member}</td>
                        <td>${d.struk}</td>
                        <td style="mso-number-format:'0.00'">${d.netto}</td>
                        <td style="mso-number-format:'0.00'">${d.margin}</td>
                        <td style="mso-number-format:'0.00'">${d.margin_persen}</td>
                        <td style="mso-number-format:'0.00'">${d.ongkir}</td>
                        <td style="mso-number-format:'0.00'">${d.ratio_om}</td>
                        <td>${d.outlet}</td>`;
            } else {
                html += `<td style="mso-number-format:'\@'">${d.nopb}</td>
                        <td style="mso-number-format:'\@'">${d.kode_member}</td>
                        <td>${d.nama_member}</td>
                        <td>${d.jarak_member}</td>
                        <td style="mso-number-format:'0.00'">${d.netto}</td>
                        <td style="mso-number-format:'0.00'">${d.margin}</td>
                        <td style="mso-number-format:'0.00'">${d.margin_persen}</td>
                        <td style="mso-number-format:'0.00'">${d.ongkir}</td>
                        <td style="mso-number-format:'0.00'">${d.ratio_om}</td>
                        <td>${d.status}</td>`;
            }
            html += `</tr>`;
        });
        html += `</tbody></table>`;
        
        let blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        
        // Bersihkan karakter aneh pada nama file jika jaraknya >20
        let safeName = currentJarakValue.replace('>', 'Lebih_Dari_');
        link.download = `Detail_Jarak_${currentJarakMode.toUpperCase()}_${safeName}KM.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});