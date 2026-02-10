$(document).ready(function() {
    // ===================================================================
    // 0. HELPER FUNCTION (FORMAT RUPIAH)
    // ===================================================================
    function formatRingkas(val) {
        if (typeof val !== 'number') return val;
        if (val >= 1000000000) return "Rp " + (val / 1000000000).toFixed(2) + " M";
        if (val >= 1000000) return "Rp " + (val / 1000000).toFixed(2) + " Jt";
        return "Rp " + val.toLocaleString('id-ID');
    }

    function formatDetail(val) {
        if (typeof val !== 'number') return val;
        return "Rp " + val.toLocaleString('id-ID');
    }

    // ===================================================================
    // 1. SETUP & VARIABEL GLOBAL
    // ===================================================================
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayString = `${yyyy}-${mm}-${dd}`;
    const firstDayString = `${yyyy}-${mm}-01`;

    // Set Default Date Input
    if($('#filter-start-date').length) { $('#filter-start-date').val(firstDayString); $('#filter-end-date').val(todayString); }
    if($('#filter-pb-start').length) { $('#filter-pb-start').val(firstDayString); $('#filter-pb-end').val(todayString); }
    if($('#filter-mr-start').length) { $('#filter-mr-start').val(firstDayString); $('#filter-mr-end').val(todayString); }
    // Input Date Baru untuk Sales MR
    if($('#filter-sales-mr-start').length) { $('#filter-sales-mr-start').val(firstDayString); $('#filter-sales-mr-end').val(todayString); }

    let apexAreaChart, memberDonutChart, distanceDonutChart, monthlyChart, combinedTrendChart, pbComparisonChart, mrComparisonChart, salesMrChart;
    let currentComparisonPeriod = 'daily';
    let mrChartFilterStatus = 'valid';
    let cachedPbData = [], currentFilteredData = [], isFilterBatalActive = false, currentPage = 1, rowsPerPage = 10;
    
    // Variabel untuk Modal Detail Sales Member
    let cachedSalesMemberData = [], currentFilteredSalesMemberData = [];
    let salesMemberPage = 1, salesMemberPerPage = 10;
    
    // [BARU] Variabel Mode Sales MR
    let salesMrMode = 'monthly'; // Default

    // [BARU] Variabel untuk Modal Detail Aktivasi
    let cachedAktivasiData = [], currentFilteredAktivasiData = [];
    let aktivasiPage = 1, aktivasiPerPage = 10;

    // --- GLOBAL VARIABLE BARU ---
    let ongkirMarginChart;

    // Set Default Date untuk Filter Ongkir (Awal Bulan - Hari Ini)
    if($('#filter-ongkir-start').length) { $('#filter-ongkir-start').val(firstDayString); $('#filter-ongkir-end').val(todayString); }

    // --- CONFIG CHART ONGKIR VS MARGIN ---
    const ongkirMarginOptions = {
        series: [],
        chart: {
            type: 'bar',
            height: 450,
            background: 'transparent',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            events: {
            dataPointSelection: function(event, chartContext, config) {
                // Ambil Nama Salesman dari kategori X-Axis
                let selectedIndex = config.dataPointIndex;
                let salesmanName = config.w.config.xaxis.categories[selectedIndex];
                
                if(salesmanName) {
                    // PANGGIL FUNGSI MODAL DI SINI
                    window.showDetailOngkirMargin(salesmanName);
                }
            }
        }
        },
        colors: ['#FF4560', '#008FFB'], // Merah (Ongkir), Biru (Margin)
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                // Tampilkan angka ringkas jika nilainya besar
                if(val > 0) return formatRingkas(val).replace("Rp ", "");
                return "";
            },
            style: { fontSize: '10px', colors: ["#fff"] },
            offsetY: -20,
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.8 }
        },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: [],
            labels: {
                style: { colors: '#E0E0E0', fontSize: '11px', fontWeight: 'bold' },
                rotate: -45
            },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#A0A0A0' },
                formatter: (val) => formatRingkas(val).replace("Rp ", "")
            },
            title: { text: 'Nominal (Rp)', style: { color: '#777' } }
        },
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.05)',
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } }
        },
        theme: { mode: 'dark' },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: {
            theme: 'dark',
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) { return formatDetail(val); }
            }
        }
    };
    // ===================================================================
    // 2. CONFIG CHART
    // ===================================================================
    
    // --- A. SALES SIMPLE (ATAS) ---
    const areaChartOptions = {
        series: [], colors: ['#00b894', '#f0932b'],
        chart: { type: 'area', height: 380, toolbar: { show: false }, background: 'transparent', animations: { enabled: true } },
        theme: { mode: 'dark' }, stroke: { curve: 'smooth', width: [3, 4] }, markers: { size: 5 }, xaxis: { categories: [] },
        yaxis: [{ min: 0, title: { text: "Sales", style: { color: '#00b894' } }, labels: { formatter: val => formatRingkas(val).replace("Rp ", "") } }, { min: 0, opposite: true, title: { text: "Margin", style: { color: '#f0932b' } } }],
        fill: { type: 'gradient', gradient: { shade: 'dark', type: "vertical", opacityFrom: 0.6, opacityTo: 0.1 } }, grid: { borderColor: 'rgba(255, 255, 255, 0.1)' }, legend: { position: 'bottom' }, dataLabels: { enabled: false },
        tooltip: { theme: 'dark', y: { formatter: function (val) { return formatDetail(val); } } }
    };
    
    // --- B. DONUT MEMBER (4 IRISAN) ---
    const memberDonutOptions = {
        series: [],
        labels: ['Register', 'Member Aktif', 'Non Transaksi', 'Member Belanja'],
        colors: ['#4e89f5', '#00c1a9', '#f76d6d', '#FEB019'],
        chart: { type: 'donut', height: 380, background: 'transparent' },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true, name: { show: true, offsetY: -10, color: '#fff' }, value: { show: true, color: '#fff', fontSize: '22px', fontWeight: 'bold', offsetY: 5 },
                        total: {
                            show: true, showAlways: true, label: 'Total Register', color: '#A0A0A0', fontSize: '14px',
                            formatter: function (w) {
                                const t = w.globals.seriesTotals;
                                if (!t || t.length === 0) return "0";
                                return w.globals.series[0].toLocaleString();
                            }
                        }
                    }
                }
            }
        },
        theme: { mode: 'dark' }, legend: { position: 'bottom', horizontalAlign: 'center' },
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
        tooltip: { theme: 'dark', y: { formatter: function(val) { return val + " Member"; } } }
    };

    const distanceDonutOptions = { series: [], labels: [], colors: ['#4e89f5', '#00c1a9', '#f4bc42', '#f76d6d', '#8a79f7', '#0090d1'], chart: { type: 'donut', height: 380, background: 'transparent' }, theme: { mode: 'dark' }, legend: { position: 'bottom' }, plotOptions: { pie: { donut: { size: '65%', labels: { show: true, name: { show: true }, value: { show: true, color: '#fff' }, total: { show: true, showAlways: true, label: 'Total', color: '#fff', formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0) } } } } } } };
    
    const monthlyChartOptions = { series: [], colors: ['#00c1a9', '#f0932b'], chart: { height: 380, type: 'bar', background: 'transparent' }, theme: { mode: 'dark' }, plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } }, dataLabels: { enabled: false }, stroke: { show: true, width: 2, colors: ['transparent'] }, xaxis: { categories: [] },
    yaxis: [
        { min: 0, seriesName: 'Sales', title: { text: 'Total Sales (Rp)', style: { color: '#00c1a9' } }, labels: { style: { colors: '#00c1a9' }, formatter: (val) => formatRingkas(val).replace("Rp ", "") } },
        { min: 0, seriesName: 'Margin', opposite: true, title: { text: 'Total Margin (Rp)', style: { color: '#f0932b' } }, labels: { style: { colors: '#f0932b' }, formatter: (val) => formatRingkas(val).replace("Rp ", "") } }
    ],
    grid: { borderColor: 'rgba(255,255,255,0.1)' }, legend: { position: 'bottom' }, tooltip: { shared: true, intersect: false, y: { formatter: function (val) { return formatDetail(val); } } } };
    
    // --- C. CHART TREND (BAWAH) ---
    const combinedTrendOptions = {
        series: [],
        chart: { type: 'area', height: 580, background: 'transparent', toolbar: { show: false }, zoom: { enabled: false }, parentHeightOffset: 0, redrawOnParentResize: true },
        theme: { mode: 'dark' },
        colors: ['#2E93fA', '#FF9800', '#546E7A', '#FF4560', '#7E57C2', '#A5A5A5'],
        stroke: { curve: 'monotoneCubic', width: 3, dashArray: [0, 0, 5, 0, 0, 5] },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 90, 100] } },
        markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
        dataLabels: { enabled: false },
        xaxis: {
            categories: [],
            labels: { offsetY: 0, style: { fontSize: '11px', colors: '#A0A0A0' }, rotate: -45, hideOverlappingLabels: true },
            tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: [],
        grid: {
            borderColor: 'rgba(255,255,255,0.1)',
            padding: { top: 10, right: 30, bottom: 60, left: 30 }
        },
        legend: {
            show: true, position: 'bottom', horizontalAlign: 'center', fontSize: '12px', fontWeight: 600, offsetY: 10,
            itemMargin: { horizontal: 15, vertical: 5 }, markers: { radius: 12, width: 10, height: 10 }, labels: { colors: '#b0b0b0' }
        },
        tooltip: {
            shared: true, intersect: false, theme: 'dark',
            x: {
                formatter: function(val) {
                    if (!val || val === 0) return "Periode Ini";
                    let dateObj = new Date(val);
                    if (dateObj instanceof Date && !isNaN(dateObj)) {
                        if (dateObj.getFullYear() < 2000) return val;
                        return dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                    }
                    return val;
                }
            },
            y: { formatter: function (y) { if(typeof y !== "undefined") return formatDetail(y); return y; } }
        }
    };
    
    // --- D. CHART PB (PB per MR) ---
    const pbChartOptions = { series: [], chart: { type: 'bar', height: 350, background: 'transparent', toolbar: { show: false } }, colors: ['#00ffbf', '#ffc107'], plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 2 } }, dataLabels: { enabled: false }, xaxis: { categories: [], labels: { style: { colors: '#E0E0E0' } } }, yaxis: { min: 0 }, grid: { borderColor: 'rgba(255, 255, 255, 0.1)' }, theme: { mode: 'dark' }, legend: { position: 'top', labels: { colors: '#E0E0E0' } },
        tooltip: { shared: true, intersect: false, theme: 'dark', y: { formatter: function (val) { return val + " Dokumen"; } } }
    };

    const mrChartOptions = {
        series: [],
        chart: {
            type: 'bar', height: 400, background: 'transparent', toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            events: { dataPointSelection: function(event, chartContext, config) { let selectedIndex = config.dataPointIndex; let salesmanName = config.w.config.xaxis.categories[selectedIndex]; if(salesmanName) window.showDetailPbSalesman(salesmanName); } }
        },
        colors: ['#00E396', '#008FFB', '#FEB019', '#FF4560', '#775DD0', '#3F51B5', '#546E7A', '#D4526E'],
        plotOptions: { bar: { horizontal: false, columnWidth: '50%', borderRadius: 4, distributed: true } },
        dataLabels: { enabled: true, style: { fontSize: '18px', fontWeight: '900', colors: ["#fff"] }, dropShadow: { enabled: true, top: 1, left: 1, blur: 2, opacity: 0.7 } },
        xaxis: { categories: [], labels: { style: { colors: '#E0E0E0', fontSize: '12px', fontWeight: 'bold' }, rotate: -45 }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, title: { text: 'Jumlah Dokumen', style: { color: '#E0E0E0' } }, labels: { style: { colors: '#E0E0E0' } } },
        grid: { borderColor: 'rgba(255, 255, 255, 0.1)', xaxis: { lines: { show: false } } },
        theme: { mode: 'dark' }, legend: { show: false },
        tooltip: { theme: 'dark', y: { formatter: val => val + " Dokumen" } }
    };

    // --- E. CHART SALES PER MR ---
    const salesMrOptions = {
        series: [],
        chart: {
            type: 'bar', height: 430, 
            background: 'transparent', toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    // Cek jika yang diklik adalah series "Periode Ini" (index 0)
                    if(config.seriesIndex === 0) {
                        let selectedIndex = config.dataPointIndex;
                        let salesmanName = config.w.config.xaxis.categories[selectedIndex];
                        if(salesmanName) window.showDetailSalesMember(salesmanName);
                    }
                }
            }
        },
        // [UPDATE] Warna: Hijau (Now), Abu-abu (Prev)
        colors: ['#00E396', '#546E7A'],
        plotOptions: {
            bar: {
                horizontal: false, columnWidth: '65%', borderRadius: 4, 
                distributed: false, // [UPDATE] Grouped bar
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                if(val > 1000000) return formatRingkas(val).replace("Rp ", "");
                return "";
            },
            style: { fontSize: '10px', fontWeight: 'bold', colors: ["#fff"] },
            offsetY: -20, 
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.8 }
        },
        xaxis: {
            categories: [],
            labels: {
                style: { colors: '#E0E0E0', fontSize: '11px', fontWeight: '600' },
                rotate: -45
            },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            show: true,
            labels: {
                style: { colors: '#A0A0A0' },
                formatter: (val) => formatRingkas(val).replace("Rp ", "")
            }
        },
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.05)',
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 30 } 
        },
        theme: { mode: 'dark' },
        legend: { show: true, position: 'top', horizontalAlign: 'right' },
        tooltip: {
            theme: 'dark',
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return formatDetail(val);
                }
            }
        }
    };

    // ===================================================================
    // 3. EVENT HANDLERS
    // ===================================================================
    
    $('#btn-mr-valid').click(function() { if (mrChartFilterStatus === 'valid') return; mrChartFilterStatus = 'valid'; $(this).addClass('btn-primary').removeClass('btn-outline-primary'); $('#btn-mr-all').addClass('btn-outline-primary').removeClass('btn-primary'); updateMrChartData(true); });
    $('#btn-mr-all').click(function() { if (mrChartFilterStatus === 'all') return; mrChartFilterStatus = 'all'; $(this).addClass('btn-primary').removeClass('btn-outline-primary'); $('#btn-mr-valid').addClass('btn-outline-primary').removeClass('btn-primary'); updateMrChartData(true); });
    
    $('#comparison-period-filter .btn').on('click', function() { if ($(this).hasClass('active')) return; $('#comparison-period-filter .btn').removeClass('active'); $(this).addClass('active'); currentComparisonPeriod = $(this).data('period'); updateCombinedTrendData(currentComparisonPeriod, false); });
    
    $('#btn-apply-filter').click(() => { $('#comparison-period-filter .btn').removeClass('active'); updateCombinedTrendData('daily', true); });
    $('#btn-filter-pb').click(() => updatePbChartData('custom', true));
    $('#btn-filter-mr').click(() => updateMrChartData(true));
    
    // Event Filter Sales MR
    $('#btn-filter-sales-mr').click(() => updateSalesMrChartData());
    
    // [UPDATE] Event Toggle Mode Sales MR (Bulanan / Harian)
    // Pastikan tombol ini ada di HTML Card Header, jika belum ada, tambahkan.
    $('#btn-sales-mr-month').click(function() { 
        salesMrMode = 'monthly';
        $('#filter-sales-mr-start').val(firstDayString); 
        $('#filter-sales-mr-end').val(todayString); 
        updateSalesMrChartData(); 
    });
    
    $('#btn-sales-mr-today').click(function() { 
        salesMrMode = 'daily';
        $('#filter-sales-mr-start').val(todayString); 
        $('#filter-sales-mr-end').val(todayString); 
        updateSalesMrChartData(); 
    });

    // ===================================================================
    // 4. LOGIKA MODAL & TABEL PB
    // ===================================================================
    $('#rowsPerPageSelect').change(function() { rowsPerPage = parseInt($(this).val()); currentPage = 1; renderTableAndPagination(); });
    $('#search-pb-input').keyup(function() { applyFilterAndRender(); if($(this).val() !== '' || isFilterBatalActive) $('#btn-reset-filter').show(); else $('#btn-reset-filter').hide(); });
    
    $('#btn-filter-batal').click(function() { isFilterBatalActive = !isFilterBatalActive; if(isFilterBatalActive) { $(this).addClass('active btn-danger').removeClass('btn-outline-danger').html('<i class="fas fa-check mr-1"></i> Aktif: Hanya Batal'); $('#btn-reset-filter').show(); } else { $(this).removeClass('active btn-danger').addClass('btn-outline-danger').html('<i class="fas fa-ban mr-1"></i> Hanya Batal'); if($('#search-pb-input').val() === '') $('#btn-reset-filter').hide(); } applyFilterAndRender(); });
    $('#btn-reset-filter').click(function() { $('#search-pb-input').val(''); isFilterBatalActive = false; $('#btn-filter-batal').removeClass('active btn-danger').addClass('btn-outline-danger').html('<i class="fas fa-ban mr-1"></i> Hanya Batal'); $(this).hide(); applyFilterAndRender(); });

    window.showDetailPbSalesman = function(salesman) {
        $('#search-pb-input').val(''); $('#rowsPerPageSelect').val(10); rowsPerPage = 10; currentPage = 1; isFilterBatalActive = false; $('#btn-filter-batal').removeClass('active btn-danger').addClass('btn-outline-danger').html('<i class="fas fa-ban mr-1"></i> Hanya Batal'); $('#btn-reset-filter').hide();
        let startDate = $('#filter-mr-start').val(); let endDate = $('#filter-mr-end').val();
        if(!startDate) startDate = $('#filter-start-date').val();
        if(!endDate) endDate = $('#filter-end-date').val();

        $('#modal-salesman-name').text(salesman);
        $('#table-detail-pb tbody').html('<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><h5 class="text-muted">Mengambil data terbaru...</h5></td></tr>');
        $('#modalDetailPB').modal('show');
        let url = `dashboard/api_get_detail_pb_mr.php?salesman=${salesman}&start_date=${startDate}&end_date=${endDate}&status=${mrChartFilterStatus}`;
        fetch(url).then(res => res.json()).then(res => { if (res.status === 'success' && res.data.length > 0) { cachedPbData = res.data; applyFilterAndRender(); } else { cachedPbData = []; currentFilteredData = []; renderTableEmpty(); } }).catch(err => { console.error(err); $('#table-detail-pb tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>'); });
    }

    function applyFilterAndRender() {
        let term = $('#search-pb-input').val().toLowerCase();
        currentFilteredData = cachedPbData.filter(item => { let matchBatal = isFilterBatalActive ? (item.status && item.status.toLowerCase().includes('batal')) : true; let combinedText = `${item.obi_nopb} ${item.cus_namamember} ${item.obi_kdmember} ${item.status} ${item.obi_notrans}`.toLowerCase(); return matchBatal && (term ? combinedText.includes(term) : true); });
        currentPage = 1; renderTableAndPagination();
    }

    function renderTableAndPagination() {
        let tbody = $('#table-detail-pb tbody'); tbody.empty();
        let totalData = currentFilteredData.length; let totalBatal = currentFilteredData.filter(i => i.status.toLowerCase().includes('batal')).length; let totalAktif = totalData - totalBatal;
        $('#info-total-row').html(`Total: <b>${totalData}</b> <span class="text-muted mx-1">|</span> <span class="text-success">Aktif: ${totalAktif}</span> <span class="text-muted mx-1">|</span> <span class="text-danger">Batal: ${totalBatal}</span>`);
        if (totalData === 0) { renderTableEmpty(); return; }
        const start = (currentPage - 1) * rowsPerPage; const pageData = currentFilteredData.slice(start, start + rowsPerPage);
        let rows = '';
        pageData.forEach((item, i) => {
            let badgeClass = 'badge-info'; let s = item.status ? item.status.toLowerCase() : '-';
            if(s.includes('batal')) badgeClass = 'badge-danger'; else if(s.includes('selesai')) badgeClass = 'badge-success'; else if(s.includes('siap')) badgeClass = 'badge-warning text-dark font-weight-bold';
            rows += `<tr><td class="text-center text-muted">${start + i + 1}</td><td class="font-weight-bold text-info">${item.obi_nopb}</td><td>${item.tgl_pb}</td><td class="font-weight-bold">${item.obi_kdmember}</td><td>${item.cus_namamember||'-'}</td><td class="text-monospace">${item.obi_notrans||'-'}</td><td class="text-center"><span class="badge ${badgeClass} p-2 w-100" style="font-size:12px;">${item.status}</span></td></tr>`;
        });
        tbody.html(rows); renderPaginationControls();
    }

    function renderTableEmpty() { $('#table-detail-pb tbody').html('<tr><td colspan="7" class="text-center text-muted py-3">Data tidak ditemukan.</td></tr>'); $('#pagination-container').empty(); $('#info-total-row').text('Total: 0'); }
    function renderPaginationControls() {
        const totalPages = Math.ceil(currentFilteredData.length / rowsPerPage); const container = $('#pagination-container'); container.empty(); if (totalPages <= 1) return;
        let prevDis = currentPage === 1 ? 'disabled' : ''; container.append(`<li class="page-item ${prevDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changePage(${currentPage-1})">&laquo;</a></li>`);
        let start = Math.max(1, currentPage - 1); let end = Math.min(totalPages, currentPage + 1); if (totalPages > 5) { if (currentPage < 3) end = 3; if (currentPage > totalPages - 2) start = totalPages - 2; } else { start = 1; end = totalPages; }
        if(start > 1) { container.append(`<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changePage(1)">1</a></li>`); if(start > 2) container.append(`<li class="page-item disabled"><span class="page-link bg-dark text-muted border-secondary">...</span></li>`); }
        for (let i = start; i <= end; i++) { let act = i === currentPage ? 'active bg-primary border-primary' : ''; container.append(`<li class="page-item ${act}"><a class="page-link ${i === currentPage ? 'bg-primary text-white' : 'bg-dark text-white'} border-secondary" href="javascript:void(0)" onclick="changePage(${i})">${i}</a></li>`); }
        if(end < totalPages) { if(end < totalPages - 1) container.append(`<li class="page-item disabled"><span class="page-link bg-dark text-muted border-secondary">...</span></li>`); container.append(`<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changePage(${totalPages})">${totalPages}</a></li>`); }
        let nextDis = currentPage === totalPages ? 'disabled' : ''; container.append(`<li class="page-item ${nextDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changePage(${currentPage+1})">&raquo;</a></li>`);
    }

    window.changePage = function(p) { const total = Math.ceil(currentFilteredData.length / rowsPerPage); if (p < 1 || p > total) return; currentPage = p; renderTableAndPagination(); $('#modalDetailPB .modal-body').animate({ scrollTop: 0 }, 'fast'); }
    window.exportToExcel = function() { if(currentFilteredData.length === 0) { alert("Tidak ada data"); return; } let html = `<table border="1"><thead><tr style="background:#f2f2f2"><th>No</th><th>No PB</th><th>Tgl PB</th><th>Kode</th><th>Nama</th><th>No Trans</th><th>Status</th></tr></thead><tbody>`; currentFilteredData.forEach((d, i) => { html += `<tr><td>${i+1}</td><td style="mso-number-format:'\@'">${d.obi_nopb}</td><td>${d.tgl_pb}</td><td>${d.obi_kdmember}</td><td>${d.cus_namamember||'-'}</td><td style="mso-number-format:'\@'">${d.obi_notrans||'-'}</td><td>${d.status}</td></tr>`; }); html += `</tbody></table>`; let blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' }); let link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'Data_PB_'+$('#modal-salesman-name').text()+'.xls'; document.body.appendChild(link); link.click(); document.body.removeChild(link); }

    // ===================================================================
    // 5. LOGIKA MODAL DETAIL SALES MEMBER (UPDATED)
    // ===================================================================
    
    // Trigger Modal dari Chart Click
    window.showDetailSalesMember = function(salesman) {
        $('#search-sales-member').val('');
        salesMemberPage = 1;

        let s = $('#filter-sales-mr-start').val();
        let e = $('#filter-sales-mr-end').val();
        if(!s) s = $('#filter-start-date').val();
        if(!e) e = $('#filter-end-date').val();

        $('#modal-sales-mr-name').text(salesman);
        
        // Update header tabel untuk indikasi mode
        let prevLabel = salesMrMode === 'daily' ? 'Sales Kemarin' : 'Sales Bln Lalu';
        $('#th-sales-prev').text(prevLabel); // Pastikan kolom Sales Lalu punya id="th-sales-prev"

        $('#table-detail-sales-member tbody').html('<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><h5 class="text-muted">Mengambil data member...</h5></td></tr>');
        $('#modalDetailSalesMember').modal('show');

        // Fetch Data Detail dengan parameter MODE
        let url = `dashboard/api_get_detail_sales_mr.php?salesman=${encodeURIComponent(salesman)}&start_date=${s}&end_date=${e}&mode=${salesMrMode}`;
        
        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data.length > 0) {
                    cachedSalesMemberData = res.data;
                    applyFilterSalesMember();
                } else {
                    cachedSalesMemberData = [];
                    currentFilteredSalesMemberData = [];
                    renderTableSalesMemberEmpty();
                }
            })
            .catch(err => {
                console.error(err);
                $('#table-detail-sales-member tbody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data.</td></tr>');
            });
    };

    // Filter Logic untuk Tabel Member
    $('#search-sales-member').keyup(function() {
        applyFilterSalesMember();
    });

    function applyFilterSalesMember() {
        let term = $('#search-sales-member').val().toLowerCase();
        currentFilteredSalesMemberData = cachedSalesMemberData.filter(item => {
            let combined = `${item.members} ${item.nama_member}`.toLowerCase();
            return term === '' || combined.includes(term);
        });
        salesMemberPage = 1;
        renderTableSalesMember();
    }

   // --- FUNGSI RENDER TABEL MODAL (ADA MARGIN PREV & NOW) ---
    function renderTableSalesMember() {
        let prevLabel = salesMrMode === 'daily' ? 'Lalu' : 'Bln Lalu';
        
        // Header Tabel
        let thead = `
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="10%">Kode</th>
                <th width="20%">Nama Member</th>
                <th class="text-center" width="5%">Item</th>
                <th class="text-right text-muted" width="12%">Sales ${prevLabel}</th>
                <th class="text-right" width="12%">Sales Ini</th>
                <th class="text-center" width="10%">Growth</th>
                <th class="text-right text-muted" width="12%">Margin ${prevLabel}</th>
                <th class="text-right text-warning" width="12%">Margin Ini</th>
            </tr>
        `;
        $('#table-detail-sales-member thead').html(thead);
        
        let tbody = $('#table-detail-sales-member tbody').empty();
        if(cachedSalesMemberData.length === 0) { renderTableSalesMemberEmpty(); return; }
        
        const start = (salesMemberPage - 1) * salesMemberPerPage;
        const pageData = cachedSalesMemberData.slice(start, start + salesMemberPerPage);
        
        pageData.forEach((item, i) => {
            let sNow = parseFloat(item.sales_now||0), sPrev = parseFloat(item.sales_prev||0);
            let mNow = parseFloat(item.margin_now||0), mPrev = parseFloat(item.margin_prev||0);
            
            let growth = '-';
            let color = 'text-muted';

            if (sPrev > 0) {
                let diff = ((sNow - sPrev) / sPrev) * 100;
                color = diff >= 0 ? 'text-success' : 'text-danger';
                let arrow = diff >= 0 ? 'up' : 'down';
                growth = `<span class="${color}"><i class="fas fa-caret-${arrow}"></i> ${Math.abs(diff).toFixed(0)}%</span>`;
            } else if (sNow > 0) {
                growth = `<span class="badge badge-primary">New</span>`;
            }

            tbody.append(`
                <tr>
                    <td class="text-center text-muted">${start+i+1}</td>
                    <td class="text-info font-weight-bold">${item.members}</td>
                    <td>${item.nama_member}</td>
                    <td class="text-center">${item.produk_beli||0}</td>
                    <td class="text-right text-muted">${formatDetail(sPrev)}</td>
                    <td class="text-right font-weight-bold text-white">${formatDetail(sNow)}</td>
                    <td class="text-center" style="font-size:11px;">${growth}</td>
                    <td class="text-right text-muted">${formatDetail(mPrev)}</td>
                    <td class="text-right text-warning font-weight-bold">${formatDetail(mNow)}</td>
                </tr>
            `);
        });
        renderSalesPagination();
    }
    function renderTableSalesMemberEmpty() {
        // [UPDATE] colspan jadi 8
        $('#table-detail-sales-member tbody').html('<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data member.</td></tr>');
        $('#pagination-sales-container').empty();
        $('#info-total-sales-member').text('Total: 0');
    }

    function renderSalesPagination() {
        const totalPages = Math.ceil(currentFilteredSalesMemberData.length / salesMemberPerPage);
        const container = $('#pagination-sales-container');
        container.empty();
        
        if (totalPages <= 1) return;

        // Tombol Prev
        let prevDis = salesMemberPage === 1 ? 'disabled' : '';
        container.append(`<li class="page-item ${prevDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeSalesPage(${salesMemberPage-1})">&laquo;</a></li>`);

        // Logic Simple Pagination
        let startPage = Math.max(1, salesMemberPage - 2);
        let endPage = Math.min(totalPages, salesMemberPage + 2);

        if(startPage > 1) {
             container.append(`<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeSalesPage(1)">1</a></li>`);
             if(startPage > 2) container.append(`<li class="page-item disabled"><span class="page-link bg-dark text-muted border-secondary">...</span></li>`);
        }

        for (let i = startPage; i <= endPage; i++) {
            let act = i === salesMemberPage ? 'active bg-info border-info' : '';
            container.append(`<li class="page-item ${act}"><a class="page-link ${i === salesMemberPage ? 'bg-info text-white' : 'bg-dark text-white'} border-secondary" href="javascript:void(0)" onclick="changeSalesPage(${i})">${i}</a></li>`);
        }

        if(endPage < totalPages) {
            if(endPage < totalPages - 1) container.append(`<li class="page-item disabled"><span class="page-link bg-dark text-muted border-secondary">...</span></li>`);
            container.append(`<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeSalesPage(${totalPages})">${totalPages}</a></li>`);
        }

        // Tombol Next
        let nextDis = salesMemberPage === totalPages ? 'disabled' : '';
        container.append(`<li class="page-item ${nextDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeSalesPage(${salesMemberPage+1})">&raquo;</a></li>`);
    }

    window.changeSalesPage = function(p) {
        const total = Math.ceil(currentFilteredSalesMemberData.length / salesMemberPerPage);
        if (p < 1 || p > total) return;
        salesMemberPage = p;
        renderTableSalesMember();
    }

    window.exportSalesMemberToExcel = function() {
        if(currentFilteredSalesMemberData.length === 0) { alert("Tidak ada data"); return; }
        // [UPDATE] Kolom Excel
        let html = `<table border="1"><thead><tr style="background:#f2f2f2"><th>No</th><th>Kode Member</th><th>Nama Member</th><th>Item</th><th>Sales Lalu</th><th>Sales Sekarang</th><th>Margin</th></tr></thead><tbody>`;
        currentFilteredSalesMemberData.forEach((d, i) => {
            html += `<tr><td>${i+1}</td><td>${d.members}</td><td>${d.nama_member||'-'}</td><td>${d.produk_beli}</td><td>${d.sales_prev}</td><td>${d.sales_now}</td><td>${d.margin_now}</td></tr>`;
        });
        html += `</tbody></table>`;
        let blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Detail_Sales_${$('#modal-sales-mr-name').text()}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ===================================================================
    // [BARU] 6. LOGIKA MODAL DETAIL AKTIVASI MEMBER (BULAN INI)
    // ===================================================================

    // 1. Event Klik Kartu Aktivasi
    $('#btn-detail-aktivasi').click(function() {
        $('#search-aktivasi').val('');
        aktivasiPage = 1;
        $('#modalDetailAktivasi').modal('show');
        
        // Tampilkan Loading
        $('#table-detail-aktivasi tbody').html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-white mb-3"></div><h5 class="text-muted">Mengambil data...</h5></td></tr>');

        // Fetch Data
        fetch('dashboard/api_get_detail_aktivasi.php')
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success' && res.data.length > 0) {
                    cachedAktivasiData = res.data;
                    applyFilterAktivasi();
                } else {
                    cachedAktivasiData = [];
                    currentFilteredAktivasiData = [];
                    renderTableAktivasiEmpty();
                }
            })
            .catch(err => {
                console.error(err);
                $('#table-detail-aktivasi tbody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
            });
    });

    // 2. Event Pencarian (Search)
    $('#search-aktivasi').keyup(function() {
        applyFilterAktivasi();
    });

    // 3. Fungsi Filter Data
    function applyFilterAktivasi() {
        let term = $('#search-aktivasi').val().toLowerCase();
        currentFilteredAktivasiData = cachedAktivasiData.filter(item => {
            let combined = `${item.kode} ${item.nama}`.toLowerCase();
            return term === '' || combined.includes(term);
        });
        aktivasiPage = 1;
        renderTableAktivasi();
    }

    // 4. Fungsi Render Tabel
    // Update fungsi renderTableAktivasi
    function renderTableAktivasi() {
        let tbody = $('#table-detail-aktivasi tbody');
        tbody.empty();

        let totalData = currentFilteredAktivasiData.length;
        // Update teks badge total
        $('#info-total-aktivasi-badge').text(totalData);

        if (totalData === 0) {
            renderTableAktivasiEmpty();
            return;
        }

        const start = (aktivasiPage - 1) * aktivasiPerPage;
        const pageData = currentFilteredAktivasiData.slice(start, start + aktivasiPerPage);

        let rows = '';
        pageData.forEach((item, i) => {
            // Mempercantik tampilan nama (Huruf kapital awal tiap kata / Title Case jika mau, atau biarkan UPPERCASE)
            let namaMember = item.nama || '-';
            
            rows += `
                <tr>
                    <td class="text-center text-muted font-weight-bold">${start + i + 1}</td>
                    
                    <td>
                        <div class="d-flex align-items-center text-warning">
                            <i class="far fa-calendar-alt mr-2" style="opacity:0.7"></i>
                            <span class="font-weight-bold">${item.tgl_aktif}</span>
                        </div>
                    </td>
                    
                    <td>
                        <span class="badge-code">${item.kode}</span>
                    </td>
                    
                    <td>
                        <div class="font-weight-bold text-white" style="font-size: 1rem;">${namaMember}</div>
                    </td>
                    
                    <td>
                        <div class="text-address">
                            <i class="fas fa-map-marker-alt text-secondary mr-1" style="font-size:10px;"></i> 
                            ${item.alamat || '-'}
                        </div>
                    </td>
                    <td>
                        <div class="text-address">
                            <i class="fas fa-user-tie text-secondary mr-1" style="font-size:10px;"></i>
                            ${item.adv || '-'}
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.html(rows);
        renderAktivasiPagination();
    }

    function renderTableAktivasiEmpty() {
        $('#table-detail-aktivasi tbody').html('<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data aktivasi bulan ini.</td></tr>');
        $('#pagination-aktivasi-container').empty();
        $('#info-total-aktivasi').text('Total: 0');
    }

    // 5. Fungsi Pagination (Simple)
    function renderAktivasiPagination() {
        const totalPages = Math.ceil(currentFilteredAktivasiData.length / aktivasiPerPage);
        const container = $('#pagination-aktivasi-container');
        container.empty();
        
        if (totalPages <= 1) return;

        // Prev
        let prevDis = aktivasiPage === 1 ? 'disabled' : '';
        container.append(`<li class="page-item ${prevDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeAktivasiPage(${aktivasiPage-1})">&laquo;</a></li>`);

        // Pages
        let startPage = Math.max(1, aktivasiPage - 2);
        let endPage = Math.min(totalPages, aktivasiPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            let act = i === aktivasiPage ? 'active bg-white text-dark border-white' : '';
            // Fix style for active state in dark mode if needed
            let style = i === aktivasiPage ? 'background-color: #fff !important; color: #000 !important;' : '';
            container.append(`<li class="page-item ${act}"><a class="page-link bg-dark text-white border-secondary" style="${style}" href="javascript:void(0)" onclick="changeAktivasiPage(${i})">${i}</a></li>`);
        }

        // Next
        let nextDis = aktivasiPage === totalPages ? 'disabled' : '';
        container.append(`<li class="page-item ${nextDis}"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="changeAktivasiPage(${aktivasiPage+1})">&raquo;</a></li>`);
    }

    // Fungsi Global agar bisa dipanggil dari onclick HTML
    window.changeAktivasiPage = function(p) {
        const total = Math.ceil(currentFilteredAktivasiData.length / aktivasiPerPage);
        if (p < 1 || p > total) return;
        aktivasiPage = p;
        renderTableAktivasi();
    }

    // ===================================================================
    // 7. API FETCH FUNCTION UTAMA
    // ===================================================================
    
    // --- UPDATE MEMBER DONUT (4 IRISAN) ---
    function updateMemberDonutData() {
        fetch("dashboard/api_get_member.php").then(r=>r.json()).then(d=>{
            let totalRegister = parseInt(d.total_member || 0);
            let belanja = parseInt(d.member_belanja_bulanberjalan || 0);
            let aktifTotal = parseInt(d.member_aktif_total || 0);
            let nonTransaksi = totalRegister - aktifTotal;
            if(nonTransaksi < 0) nonTransaksi = 0;

            memberDonutChart.updateSeries([totalRegister, aktifTotal, nonTransaksi, belanja]);
            memberDonutChart.updateOptions({
                plotOptions: { pie: { donut: { labels: { total: { formatter: function (w) { return totalRegister.toLocaleString(); } } } } } }
            });
        });
    }

  // --- UPDATE COMBINED TREND ---
    // --- UPDATE COMBINED TREND ---
    function updateCombinedTrendData(p='daily', c=false) {
        let url=`dashboard/api_get_perbandingan_sales.php?period=${p}`;
        let s=$('#filter-start-date').val();
        let e=$('#filter-end-date').val();
        
        if(c || (s && e)) {
            url += `&start_date=${s}&end_date=${e}`;
        }

        $('#active-date-label').html('<span class="spinner-border spinner-border-sm"></span>');
        
        fetch(url).then(r=>r.json()).then(r=>{
            if(r.status!=='success'||!r.data){ combinedTrendChart?.updateSeries([]); return; }
            if(r.info_date) $('#active-date-label').text(`Data: ${r.info_date.start} - ${r.info_date.end}`);
            
            let d = r.data;
            let tot = null;
            if(d.length > 0 && d[d.length-1].tanggal === 'Total') tot = d.pop();
            
            // POTONG DATA 0 DI AKHIR (Khusus Daily)
            if(p === 'daily') {
                const endOfToday = new Date(); endOfToday.setHours(23, 59, 59, 999);
                d = d.filter(item => {
                    let itemDate = new Date(item.tanggal);
                    if(isNaN(itemDate.getTime())) {
                        let startDate = new Date($('#filter-start-date').val());
                        let idx = r.data.indexOf(item);
                        itemDate = new Date(startDate); itemDate.setDate(startDate.getDate() + idx);
                    }
                    return itemDate <= endOfToday;
                });
                for (let i = d.length - 1; i >= 0; i--) {
                    let salesVal = parseFloat(d[i].sales_bulan_ini || 0);
                    let marginVal = parseFloat(d[i].margin_bulan_ini || 0);
                    // Cek Ongkir juga
                    let ongkirVal = parseFloat(d[i].ongkir_bulan_ini || 0);
                    
                    if (salesVal === 0 && marginVal === 0 && ongkirVal === 0) { d.pop(); } else { break; }
                }
            }

            // --- PERSIAPAN VARIABEL LABEL ---
            let dateObj = new Date(s); if(isNaN(dateObj.getTime())) dateObj = new Date();
            const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
            let currMonthName = months[dateObj.getMonth()];
            let currYear = dateObj.getFullYear();
            let prevDateObj = new Date(dateObj); prevDateObj.setMonth(dateObj.getMonth() - 1);
            let prevMonthName = months[prevDateObj.getMonth()];
            let lastYear = currYear - 1;

            let nameS_Ini = `Sales ${currMonthName}`; let nameS_Lalu = `Sales ${prevMonthName}`; let nameS_Thn = `Sales ${currMonthName} ${lastYear}`;
            let nameM_Ini = `Margin ${currMonthName}`; let nameM_Lalu = `Margin ${prevMonthName}`; let nameM_Thn = `Margin ${currMonthName} ${lastYear}`;
            // (Opsional) Label Chart Ongkir jika ingin ditampilkan di grafik nanti
            // let nameO_Ini = `Ongkir ${currMonthName}`;

            // --- UPDATE ANGKA BESAR & BADGE (LOGIKA BARU) ---
            // --- UPDATE ANGKA BESAR & BADGE (LOGIKA BARU DENGAN MODE EXPENSE) ---
            const grow = (c, l, isExpense = false) => {
                c = parseFloat(c || 0); 
                l = parseFloat(l || 0);
                if(l === 0) return '';
                
                let df = ((c - l) / l) * 100;
                
                // Tentukan apakah perubahannya "Bagus" atau "Jelek"
                // Kalau Expense (Ongkir): Negatif (turun) itu Bagus.
                // Kalau Revenue (Sales): Positif (naik) itu Bagus.
                let isGood = isExpense ? (df <= 0) : (df >= 0);
                
                // Set Warna berdasarkan isGood
                let col = isGood ? '#00E396' : '#FF4560'; // Hijau jika Bagus, Merah jika Jelek
                let bg  = isGood ? 'rgba(0, 227, 150, 0.1)' : 'rgba(255, 69, 96, 0.1)';
                
                // Icon panah tetap mengikuti arah angka (Naik=Up, Turun=Down)
                let icon = df >= 0 ? 'up' : 'down';
                
                return `<span style="color:${col}; background:${bg}; font-size:12px; font-weight:bold; padding: 3px 8px; border-radius: 4px; margin-left: 8px;">
                        <i class="fas fa-arrow-${icon}"></i> ${Math.abs(df).toFixed(1)}%
                        </span>`;
            };
            
            if(tot){
                // Sales & Margin (False / Default) -> Naik itu Hijau
                $('#big-sales-val').text(formatDetail(tot.sales_bulan_ini));
                $('#big-sales-prev').text(formatRingkas(tot.sales_bulan_lalu));
                $('#big-sales-growth').html(grow(tot.sales_bulan_ini, tot.sales_bulan_lalu, false)); 

                $('#big-margin-val').text(formatDetail(tot.margin_bulan_ini));
                $('#big-margin-prev').text(formatRingkas(tot.margin_bulan_lalu));
                $('#big-margin-growth').html(grow(tot.margin_bulan_ini, tot.margin_bulan_lalu, false));
                
                // Ongkir (True) -> Turun itu Hijau
                $('#big-ongkir-val').text(formatDetail(tot.ongkir_bulan_ini));
                $('#big-ongkir-prev').text(formatRingkas(tot.ongkir_bulan_lalu));
                $('#big-ongkir-growth').html(grow(tot.ongkir_bulan_ini, tot.ongkir_bulan_lalu, true)); // <--- Parameter TRUE agar logika warna dibalik
            }
            
            // Hitung Max Value untuk Y-Axis
            let allSalesValues = [], allMarginValues = [];
            d.forEach(row => {
                allSalesValues.push(parseFloat(row.sales_bulan_ini||0), parseFloat(row.sales_bulan_lalu||0), parseFloat(row.sales_tahun_lalu||0));
                allMarginValues.push(parseFloat(row.margin_bulan_ini||0), parseFloat(row.margin_bulan_lalu||0), parseFloat(row.margin_tahun_lalu||0));
            });
            let maxSales = (Math.max(...allSalesValues) || 100) * 1.1;
            let maxMargin = (Math.max(...allMarginValues) || 100) * 1.1;

            // =================================================================
            // LOGIKA DINAMIS CHART
            // =================================================================
            let dynamicChartConfig = {};

            if (p === 'monthly' || p === 'bulanan') {
                dynamicChartConfig = {
                    chart: { type: 'bar' },
                    plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 2 } },
                    stroke: { show: true, width: 2, colors: ['transparent'], curve: 'straight', dashArray: 0 },
                    fill: { type: 'solid', opacity: 1 }
                };
            } else {
                dynamicChartConfig = {
                    chart: { type: 'area' },
                    plotOptions: { bar: { columnWidth: '70%' } },
                    stroke: { curve: 'monotoneCubic', width: 3, dashArray: [0, 0, 5, 0, 0, 5], colors: undefined },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 90, 100] } }
                };
            }

            combinedTrendChart.updateOptions({
                ...dynamicChartConfig,
                xaxis: { categories: d.map(x=>x.tanggal) },
                yaxis: [
                    { show: true, min: 0, max: maxSales, forceNiceScale: true, title: { text: 'Sales', style: { color: '#2E93fA' } }, labels: { style: { colors: '#2E93fA' }, formatter: val => formatRingkas(val).replace("Rp ", "") } },
                    { show: false, min: 0, max: maxSales }, { show: false, min: 0, max: maxSales },
                    { show: true, opposite: true, min: 0, max: maxMargin, forceNiceScale: true, title: { text: 'Margin', style: { color: '#FF4560' } }, labels: { style: { colors: '#FF4560' }, formatter: val => formatRingkas(val).replace("Rp ", "") } },
                    { show: false, opposite: true, min: 0, max: maxMargin }, { show: false, opposite: true, min: 0, max: maxMargin }
                ],
                tooltip: {
                    x: {
                        formatter: function(val) {
                            if (!val || val === 0) return "Periode Ini";
                            if (!isNaN(val) && val.toString().length <= 2) {
                                return val + " " + currMonthName + " " + currYear;
                            }
                            let dObj = new Date(val);
                            if (dObj instanceof Date && !isNaN(dObj)) {
                                return dObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                            }
                            return val;
                        }
                    }
                }
            });

            // Update Series (Saya tidak memasukkan garis Ongkir ke chart agar tidak berantakan, 
            // karena nilainya biasanya jauh lebih kecil dari Sales/Margin, jadi akan terlihat rata di bawah)
            // Jika ingin ditampilkan, tinggal tambah object baru ke array ini.
            combinedTrendChart.updateSeries([
                { name: nameS_Ini,  type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.sales_bulan_ini),  yAxisIndex: 0 },
                { name: nameS_Lalu, type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.sales_bulan_lalu), yAxisIndex: 1 },
                { name: nameS_Thn,  type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.sales_tahun_lalu), yAxisIndex: 2 },
                { name: nameM_Ini,  type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.margin_bulan_ini),  yAxisIndex: 3 },
                { name: nameM_Lalu, type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.margin_bulan_lalu), yAxisIndex: 4 },
                { name: nameM_Thn,  type: (p==='monthly'?'bar':'area'), data:d.map(x=>x.margin_tahun_lalu), yAxisIndex: 5 }
            ]);

        }).catch(e=>console.error(e));
    }

    // --- MR CHART (PB) ---
    function updateMrChartData(isCustomDate = false) {
        let url = "dashboard/api_get_perbandingan_mr.php";
        let startDate = $('#filter-mr-start').val(); let endDate = $('#filter-mr-end').val();
        
        if(!startDate || !endDate) { startDate = $('#filter-start-date').val(); endDate = $('#filter-end-date').val(); }
        let params = []; if(startDate && endDate) { params.push(`start_date=${startDate}`); params.push(`end_date=${endDate}`); }
        params.push(`status=${mrChartFilterStatus}`);
        if(params.length > 0) url += "?" + params.join('&');
        
        fetch(url).then(res => res.json()).then(res => {
            if (res.status === 'success') {
                mrComparisonChart.updateOptions({ xaxis: { categories: res.categories } });
                mrComparisonChart.updateSeries(res.series);
                
                let total = 0;
                if(res.series && res.series.length > 0) { total = res.series[0].data.reduce((a, b) => a + b, 0); }
                
                let summaryHTML = `
                        <div class="row w-100 mx-0 mt-2 mb-4">
                            <div class="col-md-3 px-1 mb-2">
                                <div class="p-3 rounded d-flex flex-column justify-content-center h-100" style="background: linear-gradient(135deg, rgba(0, 227, 150, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #00E396;">
                                    <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 1px;">TOTAL SALES MR</span>
                                    <div class="d-flex align-items-baseline mt-1">
                                        <h3 class="mb-0 font-weight-bold text-white" style="font-size: 1.6rem;">${formatRingkas(res.summary.total_mr_now).replace("Rp ", "")}</h3>
                                        <span class="ml-1 text-success" style="font-size: 0.8rem;">Jt</span>
                                    </div>
                                    <div class="d-flex align-items-center mt-2 pt-2 border-top border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                                        <i class="fas fa-wallet text-secondary mr-1" style="font-size: 0.8rem;"></i>
                                        <span class="text-white font-weight-bold" style="font-size: 0.9rem;">${formatRingkas(res.summary.total_margin_mr)}</span>
                                        <small class="text-secondary ml-1" style="font-size: 9px;">(Margin)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 px-1 mb-2">
                                <div class="p-3 rounded d-flex flex-column justify-content-center h-100" style="background: linear-gradient(135deg, rgba(0, 143, 251, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #008FFB;">
                                    <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 1px;">TOTAL SALES (+HJK)</span>
                                    <div class="d-flex align-items-baseline mt-1">
                                        <h3 class="mb-0 font-weight-bold text-white" style="font-size: 1.6rem;">${formatRingkas(res.summary.total_now).replace("Rp ", "")}</h3>
                                        <span class="ml-1 text-info" style="font-size: 0.8rem;">Jt</span>
                                    </div>
                                    <div class="d-flex align-items-center mt-2 pt-2 border-top border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                                        <i class="fas fa-wallet text-secondary mr-1" style="font-size: 0.8rem;"></i>
                                        <span class="text-white font-weight-bold" style="font-size: 0.9rem;">${formatRingkas(res.summary.total_margin_all)}</span>
                                        <small class="text-secondary ml-1" style="font-size: 9px;">(Margin)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 px-1 mb-2">
                                <div class="p-3 rounded d-flex flex-column justify-content-center h-100" style="background: linear-gradient(135deg, rgba(254, 176, 25, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FEB019;">
                                    <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 1px;">STRUK MR</span>
                                    <div class="d-flex align-items-center mt-1">
                                        <h3 class="mb-0 font-weight-bold text-white" style="font-size: 1.8rem;">${res.summary.total_struk_mr}</h3>
                                        <i class="fas fa-receipt ml-auto text-warning" style="opacity: 0.5; font-size: 1.5rem;"></i>
                                    </div>
                                    <small class="text-secondary mt-1" style="font-size: 11px;">Transaksi Salesman</small>
                                </div>
                            </div>

                            <div class="col-md-3 px-1 mb-2">
                                <div class="p-3 rounded d-flex flex-column justify-content-center h-100" style="background: #2b2f33; border: 1px solid #444;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">STRUK TOTAL</span>
                                        <span class="text-white font-weight-bold" style="font-size: 1.2rem;">${res.summary.total_struk_all}</span>
                                    </div>
                                    <div class="btn-group w-100 mt-auto">
                                        <button id="btn-sales-mr-month" class="btn btn-xs btn-outline-secondary ${salesMrMode==='monthly'?'active':''}" style="font-size: 10px;">Bulan Ini</button>
                                        <button id="btn-sales-mr-today" class="btn btn-xs btn-outline-secondary ${salesMrMode==='daily'?'active':''}" style="font-size: 10px;">Hari Ini</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                
                if($('#mr-chart-summary').length) { $('#mr-chart-summary').html(summaryHTML); }
                else { $('<div id="mr-chart-summary" class="d-flex justify-content-between align-items-center mb-3 px-2"></div>').insertBefore('#mr-comparison-chart'); $('#mr-chart-summary').html(summaryHTML); }
                
                $('#btn-mr-today-action').off('click').on('click', function() { $('#filter-mr-start').val(todayString); $('#filter-mr-end').val(todayString); updateMrChartData(true); });
                $('#btn-mr-month-action').off('click').on('click', function() { $('#filter-mr-start').val(firstDayString); $('#filter-mr-end').val(todayString); updateMrChartData(true); });
            }
        }).catch(e => console.error('Error MR Chart:', e));
    }
    
 // --- FUNGSI UPDATE DATA SALES MR ---
function updateSalesMrChartData() {
    if (typeof salesMrChart === 'undefined') return;

    const dateNow = new Date();
    const todayString = dateNow.toISOString().split('T')[0];
    const firstDayString = new Date(dateNow.getFullYear(), dateNow.getMonth(), 1).toISOString().split('T')[0];
    const formatRupiahAsli = (angka) => {
    // Math.floor digunakan untuk menghilangkan angka di belakang koma
    let nominalBulat = Math.floor(parseFloat(angka || 0));
    return 'Rp ' + nominalBulat.toLocaleString('id-ID');
    };

    // --- TAMBAHAN: Handle Klik Tombol Header (Bulan ini / Hari ini) ---
    // Gunakan .off() agar event tidak menumpuk saat fungsi dipanggil ulang
    $('#btn-mode-monthly').off('click').on('click', function() {
        $('#filter-sales-mr-start').val(firstDayString);
        $('#filter-sales-mr-end').val(todayString);
        updateSalesMrChartData();
    });

    $('#btn-mode-daily').off('click').on('click', function() {
        $('#filter-sales-mr-start').val(todayString);
        $('#filter-sales-mr-end').val(todayString);
        updateSalesMrChartData();
    });
    // ---------------------------------------------------------------

    // Ambil tanggal dari input (untuk ditampilkan di tooltip)
    let s = $('#filter-sales-mr-start').val() || firstDayString;
    let e = $('#filter-sales-mr-end').val() || todayString;

    if($('#sales-mr-chart-summary').length) $('#sales-mr-chart-summary').css('opacity', '0.5');

    let url = `dashboard/api_get_sales_per_mr.php?start_date=${s}&end_date=${e}&mode=${salesMrMode}`;

    fetch(url)
        .then(res => res.json())
        .then(res => {
            if($('#sales-mr-chart-summary').length) $('#sales-mr-chart-summary').css('opacity', '1');

            if (res.status === 'success') {
                window.currentStrukData = res.struk_data || [];
                window.currentMarginData = res.margin_data || [];
                window.prevMarginData = res.margin_prev_data || [];

                // Label & Warna Dinamis
                let labelLalu = (salesMrMode === 'monthly') ? 'Bulan Lalu' : 'Hari Lalu';
                let colors = (salesMrMode === 'monthly') ? ['#00E396', '#008FFB'] : ['#FEB019', '#775DD0'];

                // Format tanggal untuk tampilan tooltip (DD/MM/YYYY)
                const fmtDate = (d) => d.split('-').reverse().join('/');

                if (salesMrChart) {
                    salesMrChart.updateOptions({ 
                        colors: colors,
                        xaxis: { categories: res.categories },
                        tooltip: {
                            custom: function({series, seriesIndex, dataPointIndex, w}) {
                                let salesNow = w.globals.series[0][dataPointIndex] || 0;
                                let salesPrev = w.globals.series[1][dataPointIndex] || 0;
                                let marginNow = window.currentMarginData[dataPointIndex] || 0;
                                let marginPrev = window.prevMarginData[dataPointIndex] || 0;
                                let salesman = w.globals.labels[dataPointIndex];
                                
                                let mgDiff = 0; if(marginPrev > 0) mgDiff = ((marginNow - marginPrev) / marginPrev) * 100;
                                let mgColor = mgDiff >= 0 ? '#00E396' : '#FF4560';
                                let mgIcon = mgDiff >= 0 ? '▲' : '▼';

                                return `
                                    <div class="p-3" style="background: #1e2226; border: 2px solid #444; font-size: 14px; min-width: 250px; border-radius: 8px;">
                                        <div class="mb-2 font-weight-bold text-white border-bottom pb-2" style="font-size: 16px;">
                                            <i class="fas fa-user-tie mr-2 text-warning"></i>${salesman}
                                        </div>
                                        
                                        <div class="mb-3 p-2 rounded" style="background: rgba(255,255,255,0.05); font-size: 11px; border: 1px dashed #555;">
                                            <div class="text-white">📅 Periode: <span class="text-success">${fmtDate(s)} - ${fmtDate(e)}</span></div>
                                            <div class="text-secondary">🔄 Banding: <span class="text-info">${labelLalu}</span></div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-1">
                                            <span style="color:${colors[0]}">● Sales Ini:</span>
                                            <span class="text-white font-weight-bold">${formatRupiahAsli(salesNow)}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span style="color:${colors[1]}">● ${labelLalu}:</span>
                                            <span class="text-muted">${formatRupiahAsli(salesPrev)}</span>
                                        </div>
                                        
                                        <div class="border-top pt-2 mt-2" style="border-color: #444 !important;"></div>
                                        
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-warning">Margin Ini:</span>
                                            <span class="text-white font-weight-bold">${formatRupiahAsli(marginNow)}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-secondary">Margin Lalu:</span>
                                            <div class="text-right">
                                                <div class="text-muted" style="font-size:12px;">${formatRupiahAsli(marginPrev)}</div>
                                                <div style="color:${mgColor}; font-weight:bold;">${mgIcon} ${Math.abs(mgDiff).toFixed(1)}%</div>
                                            </div>
                                        </div>
                                    </div>`;
                            }
                        }
                    });
                    salesMrChart.updateSeries(res.series);
                }

                // --- 3. Render Summary HTML ---
                const getGrowth = (n, p) => { n=parseFloat(n||0); p=parseFloat(p||0); if(p===0) return n>0?'New':'-'; let d=((n-p)/p)*100; let c=d>=0?'text-success':'text-danger'; return `<span class="${c}" style="font-size:14px; font-weight:900;"><i class="fas fa-arrow-${d>=0?'up':'down'}"></i> ${Math.abs(d).toFixed(1)}%</span>`; };

                let summaryHTML = `
    <div class="row w-100 mx-0 mt-2 mb-4">
        <div class="col-md-3 px-1 mb-2">
            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(0, 227, 150, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #00E396;">
                <div class="d-flex justify-content-between"><span>SALES MR</span> ${getGrowth(res.summary.total_mr_now, res.summary.total_mr_prev)}</div>
                <h3 class="mb-0 font-weight-bold" style="font-size: 1.4rem;">${formatRupiahAsli(res.summary.total_mr_now)}</h3>
                <div class="mt-2 border-top border-secondary pt-2 d-flex justify-content-between"><span>Lalu:</span> <b>${formatRupiahAsli(res.summary.total_mr_prev)}</b></div>
            </div>
        </div>
        <div class="col-md-3 px-1 mb-2">
            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(255, 69, 96, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FF4560;">
                <div class="d-flex justify-content-between"><span>MARGIN MR</span> ${getGrowth(res.summary.total_margin_mr_now, res.summary.total_margin_mr_prev)}</div>
                <h3 class="mb-0 font-weight-bold" style="font-size: 1.4rem;">${formatRupiahAsli(res.summary.total_margin_mr_now)}</h3>
                <div class="mt-2 border-top border-secondary pt-2 d-flex justify-content-between"><span>Lalu:</span> <b>${formatRupiahAsli(res.summary.total_margin_mr_prev)}</b></div>
            </div>
        </div>
        <div class="col-md-3 px-1 mb-2">
            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(0, 143, 251, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #008FFB;">
                <div class="d-flex justify-content-between"><span>TOTAL SALES (+HJK)</span> ${getGrowth(res.summary.total_now, res.summary.total_prev)}</div>
                <h3 class="mb-0 font-weight-bold" style="font-size: 1.4rem;">${formatRupiahAsli(res.summary.total_now)}</h3>
                <div class="mt-2 border-top border-secondary pt-2 d-flex justify-content-between"><span>Lalu:</span> <b>${formatRupiahAsli(res.summary.total_prev)}</b></div>
            </div>
        </div>
        <div class="col-md-3 px-1 mb-2">
            <div class="p-3 rounded h-100" style="background: #2b2f33; border: 1px solid #444;">
                <div class="d-flex justify-content-between"><span>STRUK MR</span> ${getGrowth(res.summary.total_struk_mr, res.summary.total_struk_mr_prev)}</div>
                <h3 class="mb-0 font-weight-bold" style="font-size: 1.8rem;">${parseInt(res.summary.total_struk_mr).toLocaleString('id-ID')}</h3>
                <div class="mt-2 pt-2 border-top border-secondary d-flex justify-content-between"><span>Lalu:</span> <b>${parseInt(res.summary.total_struk_mr_prev).toLocaleString('id-ID')} Struk</b></div>
                
                <div class="btn-group w-100 mt-2">
                    <button id="btn-action-monthly" class="btn btn-xs btn-outline-success ${salesMrMode==='monthly'?'active':''}">Bulanan</button>
                    <button id="btn-action-daily" class="btn btn-xs btn-outline-warning ${salesMrMode==='daily'?'active':''}">Kemarin</button>
                </div>
            </div>
        </div>
    </div>`;
                $('#sales-mr-chart-summary').html(summaryHTML);

                // --- 4. Bind Tombol Bawah ---
                $('#btn-action-monthly').off('click').on('click', function(e) {
                    e.preventDefault();
                    salesMrMode = 'monthly';
                    $('#filter-sales-mr-start').val(firstDayString);
                    $('#filter-sales-mr-end').val(todayString);
                    updateSalesMrChartData();
                });

                $('#btn-action-daily').off('click').on('click', function(e) {
                    e.preventDefault();
                    salesMrMode = 'daily';
                    $('#filter-sales-mr-start').val(todayString);
                    $('#filter-sales-mr-end').val(todayString);
                    updateSalesMrChartData();
                });

                // Sinkronisasi class active tombol header
                const isStartToday = $('#filter-sales-mr-start').val() === todayString;
                const isEndToday = $('#filter-sales-mr-end').val() === todayString;
                
                $('#btn-mode-daily').parent().removeClass('active');
                $('#btn-mode-monthly').parent().removeClass('active');

                if (isStartToday && isEndToday) {
                    $('#btn-mode-daily').parent().addClass('active');
                } else {
                    $('#btn-mode-monthly').parent().addClass('active');
                }
            }
        })
        .catch(err => console.error("Error:", err));
}

    // --- PB COMPARISON CHART ---
    function updatePbChartData(per='daily', cust=false) {
        let url = `dashboard/api_get_perbandingan_pb.php?period=${per}`; let s=$('#filter-pb-start').val(), e=$('#filter-pb-end').val(); if(cust||(s&&e)) url+=`&start_date=${s}&end_date=${e}`;
        $('#val-pb-curr, #val-pb-batal, #val-pb-today').css('opacity', '0.3');
        fetch(url).then(r=>r.json()).then(r=>{
            $('#val-pb-curr, #val-pb-batal, #val-pb-today').css('opacity', '1');
            if(r.status==='success'){
                pbComparisonChart.updateOptions({xaxis:{categories:r.categories}}); pbComparisonChart.updateSeries(r.series);
                if(r.totals) {
                    let t = r.totals; let td = new Date().toISOString().split('T')[0];
                    $('#label-daily-title').text(e===td?"Hari Ini":`Tgl ${e.split('-')[2]}`);
                    $('#val-pb-curr').text(t.current.toLocaleString()); $('#val-pb-prev').text(t.previous.toLocaleString());
                    const calc = (c,p,inv=false) => { if(p<=0)return'-'; let d=((c-p)/p)*100; let g=inv?d<0:d>=0; return `<span style="color:${g?'#00ffbf':'#ff4d4d'}"><i class="fas fa-caret-${d>=0?'up':'down'}"></i> ${Math.abs(d).toFixed(1)}%</span>`; };
                    $('#label-growth-month').html(calc(t.current,t.previous)); $('#val-pb-batal').text(t.batal.toLocaleString()); $('#val-pb-batal-prev').text(t.batal_prev.toLocaleString()); $('#label-growth-batal').html(calc(t.batal,t.batal_prev,true)); 
                    $('#val-pb-today').text(t.last_day.toLocaleString()); 
                    let textKemarin = `<span class="text-muted mr-1" style="font-size:16px">Kemarin: ${t.day_before.toLocaleString()}</span>`;
                    let percentGrowth = calc(t.last_day, t.day_before);
                    $('#label-growth-day').html(`${textKemarin} ${percentGrowth}`);
                }
            }
        }).catch(e=>console.error(e));
    }
    // --- INISIALISASI CHART BARU ---
    if(document.querySelector("#ongkir-vs-margin-chart")) {
        ongkirMarginChart = new ApexCharts(document.querySelector("#ongkir-vs-margin-chart"), ongkirMarginOptions);
        ongkirMarginChart.render();
        updateOngkirMarginData(); // Panggil fungsi fetch data
    }

    // --- EVENT LISTENER TOMBOL FILTER ---
    $('#btn-filter-ongkir').click(function() {
        updateOngkirMarginData();
    });

    // --- FUNGSI UPDATE DATA (FETCH API) ---
    function updateOngkirMarginData() {
        let s = $('#filter-ongkir-start').val();
        let e = $('#filter-ongkir-end').val();
        
        // Animasi Loading opacity
        $('#ongkir-summary-container, #ongkir-vs-margin-chart').css('opacity', '0.5');

        let url = `dashboard/api_get_perbandingan_ongkir_vs_margin_mr.php?start_date=${s}&end_date=${e}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                $('#ongkir-summary-container, #ongkir-vs-margin-chart').css('opacity', '1');

                if (res.status === 'success') {
                    // 1. Update Chart
                    ongkirMarginChart.updateOptions({ xaxis: { categories: res.chart.categories } });
                    ongkirMarginChart.updateSeries(res.chart.series);

                    // 2. Update Summary Cards (HTML)
                    let sum = res.summary;
                    let ratioColor = parseFloat(sum.total_ratio_persen) > 10 ? 'text-danger' : 'text-success'; // Merah jika ongkir > 10% margin

                    let html = `
                        <div class="col-md-4 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(255, 69, 96, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #FF4560;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">TOTAL ONGKIR</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_ongkir).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_ongkir)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: linear-gradient(135deg, rgba(0, 143, 251, 0.1) 0%, rgba(0,0,0,0) 100%); border-left: 4px solid #008FFB;">
                                <span class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">TOTAL MARGIN</span>
                                <h3 class="mb-0 font-weight-bold text-white mt-1">${formatRingkas(sum.total_margin).replace("Rp ", "")}</h3>
                                <div class="mt-2 pt-2 border-top border-secondary text-white" style="font-size: 12px; border-color: rgba(255,255,255,0.1)!important;">
                                    Detail: <b>${formatDetail(sum.total_margin)}</b>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 px-1 mb-2">
                            <div class="p-3 rounded h-100" style="background: #2b2f33; border: 1px solid #444;">
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
                console.error("Error loading Ongkir Data:", err);
                $('#ongkir-summary-container').html('<div class="col-12 text-center text-danger">Gagal memuat data.</div>');
            });
    }

    // Tambahkan ke interval auto-update (di dalam setInterval 30000ms yang sudah ada)
    // 
// ===================================================================
    // LOGIKA MODAL DETAIL SALES & MARGIN (MEMBER vs NO PB) - FIXED SORTING
    // ===================================================================

    let cachedOngkirData = [];
    let currentFilteredOngkirData = [];
    let ongkirPage = 1;
    const ongkirPerPage = 10;
    let currentOngkirMode = 'member';
    let currentSalesmanName = '';

    // STATE SORTING
    let currentSortKey = 'margin'; // Default sort
    let currentSortDir = 'desc';   // Default descending

    // -------------------------------------------------------------------
    // FUNGSI UTAMA (WINDOW SCOPE AGAR BISA DIKLIK DARI HTML)
    // -------------------------------------------------------------------

    window.showDetailOngkirMargin = function(salesmanName) {
        currentSalesmanName = salesmanName;
        
        // Reset Sorting & Mode setiap buka modal
        currentSortKey = 'margin';
        currentSortDir = 'desc';
        
        currentOngkirMode = 'member';
        $('#opt-member').parent().addClass('active');
        $('#opt-struk').parent().removeClass('active');
        
        $('#search-ongkir-margin').val('');
        
        // Render Header & Fetch Data
        fetchDataOngkir();
        $('#modalDetailOngkirMargin').modal('show');
    }

    window.switchOngkirMode = function(mode) {
        if (currentOngkirMode === mode) return;
        currentOngkirMode = mode;
        // Reset sort ke default saat ganti mode
        currentSortKey = 'margin';
        currentSortDir = 'desc';
        fetchDataOngkir();
    }

    // FUNGSI HANDLE SORT (WINDOW SCOPE)
    window.handleSort = function(key) {
        console.log("Sorting triggered for key: " + key); // Debugging

        // 1. Toggle Arah Sort
        if (currentSortKey === key) {
            currentSortDir = (currentSortDir === 'asc') ? 'desc' : 'asc';
        } else {
            currentSortKey = key;
            currentSortDir = 'desc'; // Default kolom baru selalu desc
        }
        
        // 2. Terapkan Filter & Sort ulang
        applyFilterOngkir(); 
    }

    function fetchDataOngkir() {
        ongkirPage = 1;
        let s = $('#filter-ongkir-start').val();
        let e = $('#filter-ongkir-end').val();

        $('#modal-ongkir-mr-name').text(currentSalesmanName);
        $('#info-periode-ongkir').text(`Periode: ${s} s/d ${e}`);
        
        // PENTING: Render Header SEBELUM fetch data agar terlihat dan event onclick terpasang
        renderTableHeader(); 
        
        // Tampilkan Loading di Body
        $('#table-detail-ongkir-margin tbody').html('<tr><td colspan="100%" class="text-center py-5"><div class="spinner-border text-info mb-3"></div><h5 class="text-muted">Memuat data...</h5></td></tr>');

        let url = `dashboard/api_get_detail_perbandingan_ongkir_vs_margin_mr.php?salesman=${encodeURIComponent(currentSalesmanName)}&start_date=${s}&end_date=${e}&mode=${currentOngkirMode}`;

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
                $('#table-detail-ongkir-margin tbody').html('<tr><td colspan="100%" class="text-center text-danger">Gagal memuat data.</td></tr>');
            });
    }

    // ===================================================================
    // LOGIKA SORTING & FILTERING
    // ===================================================================

    $('#search-ongkir-margin').keyup(function() {
        applyFilterOngkir();
    });

    function applyFilterOngkir() {
        let term = $('#search-ongkir-margin').val().toLowerCase();
        
        // 1. Filter Data
        currentFilteredOngkirData = cachedOngkirData.filter(item => {
            let text = `${item.kode_member} ${item.nama_member} ${item.outlet}`.toLowerCase();
            if (currentOngkirMode === 'struk') text += ` ${item.nopb}`;
            return term === '' || text.includes(term);
        });

        // 2. Sort Data
        sortData();

        // 3. Render Ulang Tabel
        ongkirPage = 1;
        renderTableOngkir();
    }

    function sortData() {
        currentFilteredOngkirData.sort((a, b) => {
            let valA = a[currentSortKey];
            let valB = b[currentSortKey];

            // Deteksi apakah angka atau string
            let numA = parseFloat(valA);
            let numB = parseFloat(valB);
            
            // Jika keduanya valid number, compare sebagai number
            if (!isNaN(numA) && !isNaN(numB) && isFinite(valA) && isFinite(valB)) {
                valA = numA;
                valB = numB;
            } else {
                // Jika string, lowercase agar case-insensitive
                valA = valA ? valA.toString().toLowerCase() : '';
                valB = valB ? valB.toString().toLowerCase() : '';
            }

            if (valA < valB) return currentSortDir === 'asc' ? -1 : 1;
            if (valA > valB) return currentSortDir === 'asc' ? 1 : -1;
            return 0;
        });
    }

    // ===================================================================
    // RENDER TABLE (HEADER & BODY)
    // ===================================================================

    function renderTableHeader() {
        let thead = $('#thead-ongkir-margin');
        
        // Helper: Buat Header dengan onclick
        // Style cursor:pointer PENTING agar user tahu bisa diklik
        const th = (key, label, align = 'text-center', width='') => {
            let sortIcon = '<i class="fas fa-sort sort-icon" style="opacity:0.3; margin-left:5px;"></i>';
            let activeClass = '';
            
            if (currentSortKey === key) {
                activeClass = 'active-sort'; // Class penanda (opsional buat CSS)
                // Ganti icon sesuai arah sort
                sortIcon = currentSortDir === 'asc' 
                    ? '<i class="fas fa-sort-up sort-icon" style="opacity:1; color:#17a2b8; margin-left:5px;"></i>' 
                    : '<i class="fas fa-sort-down sort-icon" style="opacity:1; color:#17a2b8; margin-left:5px;"></i>';
            }

            return `<th class="${align} th-sortable" width="${width}" onclick="handleSort('${key}')" style="cursor:pointer; user-select:none;">
                        ${label} ${sortIcon}
                    </th>`;
        };

        let html = '<tr>';
        html += '<th class="text-center" width="4%">No</th>'; 

        if (currentOngkirMode === 'member') {
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '25%');
            html += th('struk', 'Struk', 'text-center', '6%');
            html += th('netto', 'Netto', 'text-right', '10%');
            html += th('margin', 'Margin', 'text-right', '10%');
            html += th('margin_persen', 'Mgn %', 'text-center', '6%');
            html += th('ongkir', 'Ongkir', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '8%');
            html += th('outlet', 'Outlet', 'text-center', '8%');
        } else {
            html += th('nopb', 'No PB', 'text-left', '12%');
            html += th('kode_member', 'Kode', 'text-left', '8%');
            html += th('nama_member', 'Nama Member', 'text-left', '15%');
            html += th('netto', 'Netto', 'text-right', '10%');
            html += th('margin', 'Margin', 'text-right', '10%');
            html += th('margin_persen', 'Mgn %', 'text-center', '6%');
            html += th('ongkir', 'Ongkir', 'text-right', '10%');
            html += th('ratio_om', 'Ratio O/M', 'text-center', '8%');
            html += th('status', 'Status', 'text-center', '8%');
            html += th('armada', 'Armada', 'text-center', '10%');
        }
        html += '</tr>';
        
        thead.html(html);
    }

    function renderTableOngkir() {
        // Render Header Ulang (untuk update icon sort)
        renderTableHeader(); 

        let tbody = $('#table-detail-ongkir-margin tbody');
        tbody.empty();

        let totalData = currentFilteredOngkirData.length;
        let labelTotal = currentOngkirMode === 'member' ? 'Member' : 'No PB';
        $('#info-total-ongkir-margin').text(`Total: ${totalData} ${labelTotal}`);

        if (totalData === 0) {
            tbody.html('<tr><td colspan="100%" class="text-center text-muted py-4">Tidak ada data.</td></tr>');
            $('#pagination-ongkir-container').empty();
            return;
        }

        const start = (ongkirPage - 1) * ongkirPerPage;
        const pageData = currentFilteredOngkirData.slice(start, start + ongkirPerPage);

        let rows = '';
        const fmt = (val) => "Rp " + parseFloat(val || 0).toLocaleString('id-ID');
        const num = (val) => parseFloat(val || 0).toLocaleString('id-ID');

        pageData.forEach((item, i) => {
            let marginPct = parseFloat(item.margin_persen || 0);
            let ratioOM   = parseFloat(item.ratio_om || 0);
            let ongkirVal = parseFloat(item.ongkir || 0);

            let pctColor = marginPct < 0 ? 'text-danger' : (marginPct > 15 ? 'text-success' : 'text-warning');
            let trStyle = parseFloat(item.netto) <= 0 ? 'style="background: rgba(255,0,0,0.2);"' : '';

            // Class Glow
            let marginGlow = "text-right glow-margin"; 
            let ongkirGlow = "text-right glow-ongkir"; 
            let ratioGlow  = "text-center glow-ratio";

            rows += `<tr ${trStyle}>
                <td class="text-center text-muted">${start + i + 1}</td>`;

            if (currentOngkirMode === 'member') {
                rows += `
                    <td><span class="badge badge-dark border border-secondary">${item.kode_member}</span></td>
                    <td class="font-weight-bold text-truncate" style="max-width: 200px;">${item.nama_member || '-'}</td>
                    <td class="text-center">${num(item.struk)}</td>
                    <td class="text-right text-info font-weight-bold">${fmt(item.netto)}</td>
                    <td class="${marginGlow}">${fmt(item.margin)}</td>
                    <td class="text-center font-weight-bold ${pctColor}">${marginPct}%</td>
                    <td class="${ongkirGlow}">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="${ratioGlow}">${ratioOM}%</td> 
                    <td class="text-center"><small>${item.outlet} (${item.suboutlet})</small></td>
                `;
            } else {
                rows += `
                    <td><span class="text-info font-weight-bold">${item.nopb}</span></td>
                    <td><small>${item.kode_member}</small></td>
                    <td class="text-truncate" style="max-width: 150px;">${item.nama_member}</td>
                    <td class="text-right text-info font-weight-bold">${fmt(item.netto)}</td>
                    <td class="${marginGlow}">${fmt(item.margin)}</td>
                    <td class="text-center font-weight-bold ${pctColor}">${marginPct}%</td>
                    <td class="${ongkirGlow}">${ongkirVal > 0 ? fmt(ongkirVal) : '-'}</td> 
                    <td class="${ratioGlow}">${ratioOM}%</td> 
                    <td class="text-center"><span class="badge badge-secondary">${item.status}</span></td>
                    <td class="text-center"><small>${item.armada || '-'}</small></td>
                `;
            }
            rows += `</tr>`;
        });

        tbody.html(rows);
        renderOngkirPagination();
    }

    // Pagination Logic (Window Scope)
    window.changeOngkirPage = function(p) {
        const total = Math.ceil(currentFilteredOngkirData.length / ongkirPerPage);
        if (p < 1 || p > total) return;
        ongkirPage = p;
        renderTableOngkir();
    }

    function renderOngkirPagination() {
        const totalPages = Math.ceil(currentFilteredOngkirData.length / ongkirPerPage);
        const container = $('#pagination-ongkir-container');
        container.empty();
        
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

    // Export Excel Dinamis (Window Scope)
    window.exportOngkirMarginToExcel = function() {
        if(currentFilteredOngkirData.length === 0) { alert("Tidak ada data"); return; }
        
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
        link.download = `Report_OBI_${currentOngkirMode.toUpperCase()}_${currentSalesmanName}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    function updateAreaChartData() { fetch("dashboard/api_get_grafik.php").then(r=>r.json()).then(d=>{ if(!d||!d.tanggal)return; apexAreaChart.updateOptions({series:[{name:'Sales',data:d.sales},{name:'Margin',data:d.margin}],xaxis:{categories:d.tanggal.map(x=>new Date(x).toLocaleDateString('id-ID',{day:'2-digit',month:'short'}))}}); }); }
    function updateDistanceChartData() { fetch("detailmember/api_get_jarak.php").then(r=>r.json()).then(r=>{ distanceDonutChart.updateOptions({labels:r.data.map(x=>x.kategori_jarak),series:r.data.map(x=>parseInt(x.jumlah_member))}); }); }
    function updateMonthlyChartData() { fetch("dashboard/api_get_grafik_perbulan.php").then(r=>r.json()).then(d=>{ monthlyChart.updateOptions({ series:[{name: 'Sales', data:d.sales}, {name: 'Margin', data:d.margin}], xaxis:{categories:d.bulan} }); }); }
    function updateStatCards() { $.getJSON('dashboard/api_get_member.php', d=>{ $('#member-count').text(d.total_member||0); $('#member-belanja-bulan').text(d.member_belanja_bulanberjalan||0); $('#aktivasi_bulan_berjalan').text(d.aktivasi_bulan_berjalan||0); }); $.getJSON('dashboard/api_get_sales.php', d=>{ $('#sales').text(d.sales||0); $('#margin').text(d.margin||0); $('#std').text(d.std||0); }); }
    function updateClock() { let t=new Date(); $("#jam-digital").text(`${String(t.getHours()).padStart(2,'0')}:${String(t.getMinutes()).padStart(2,'0')}:${String(t.getSeconds()).padStart(2,'0')}`); }

    // ===================================================================
    // INITIALIZATION
    // ===================================================================
    apexAreaChart = new ApexCharts(document.querySelector("#apex-chart"), areaChartOptions); apexAreaChart.render();
    memberDonutChart = new ApexCharts(document.querySelector("#member-pie-chart"), memberDonutOptions); memberDonutChart.render();
    distanceDonutChart = new ApexCharts(document.querySelector("#distance-donut-chart"), distanceDonutOptions); distanceDonutChart.render();
    monthlyChart = new ApexCharts(document.querySelector("#monthly-bar-chart"), monthlyChartOptions); monthlyChart.render();
    
    if(document.querySelector("#combined-trend-chart")) { combinedTrendChart = new ApexCharts(document.querySelector("#combined-trend-chart"), combinedTrendOptions); combinedTrendChart.render(); }
    if(document.querySelector("#pb-comparison-chart")) { pbComparisonChart = new ApexCharts(document.querySelector("#pb-comparison-chart"), pbChartOptions); pbComparisonChart.render(); }
    if(document.querySelector("#mr-comparison-chart")) { mrComparisonChart = new ApexCharts(document.querySelector("#mr-comparison-chart"), mrChartOptions); mrComparisonChart.render(); }

    // Initial Render Chart Sales MR (BARU)
    if(document.querySelector("#sales-mr-comparison-chart")) { 
        salesMrChart = new ApexCharts(document.querySelector("#sales-mr-comparison-chart"), salesMrOptions); 
        salesMrChart.render(); 
        updateSalesMrChartData(); 
    }

    updateAreaChartData(); updateMemberDonutData(); updateDistanceChartData(); updateMonthlyChartData(); updateStatCards(); updateClock();
    updateCombinedTrendData('daily', true); updatePbChartData('custom', true); updateMrChartData(true); 

    setInterval(updateAreaChartData, 10000); setInterval(updateMemberDonutData, 15000); setInterval(updateDistanceChartData, 16000); setInterval(updateMonthlyChartData, 3600000); 
    setInterval(updateStatCards, 5000); setInterval(updateClock, 1000); 
    setInterval(function() { 
        updateCombinedTrendData(currentComparisonPeriod, true); 
        updatePbChartData('custom', true); 
        updateMrChartData(true); 
        updateSalesMrChartData();
        updateOngkirMarginData();
    }, 30000); 
});