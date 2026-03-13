// --- VARIABEL GLOBAL UTAMA ---
var currentDataMR = []; 
var globalDetailData = []; 
var currentDetailPage = 1; 
var itemsPerPage = 7;

// --- VARIABEL GLOBAL MEMBER SLEEPER ---
var globalSleeperData = [];
var currentSleeperPage = 1;
var sleeperItemsPerPage = 10; 

// --- VARIABEL CHART ---
var mrDonutChart = null;

$(document).ready(function() {
    // 1. Setup Tanggal Default
    var now = new Date();
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    var year = now.getFullYear();
    
    var today = year + "-" + month + "-" + day;
    var firstDay = year + "-" + month + "-01";
    
    // Set Input Tanggal Utama (Dashboard)
    $('#filter_tanggal').val(today);

    // Set Input Tanggal Filter PB (Bawah) - Default Awal Bulan s/d Hari Ini
    $('#pb_start_date').val(firstDay);
    $('#pb_end_date').val(today);
    
    // 2. Event Listener Global
    $('#filter_tanggal').change(function() {
        loadMemberData(); 
    });

    // 3. Load Data Awal
    loadMemberData();
    loadPBComparison();
    loadMemberSleeper(); 
});

// ==========================================
// FUNGSI UPLOAD TARGET BACA EXCEL/CSV
// ==========================================
function handleFileUpload(event) {
    var file = event.target.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function(e) {
        var data = new Uint8Array(e.target.result);
        var workbook = XLSX.read(data, {type: 'array'});
        var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        var jsonData = XLSX.utils.sheet_to_json(firstSheet);

        // Kirim data JSON hasil baca excel ke PHP backend
        $.ajax({
            url: 'simul/api_upload_target.php', 
            type: 'POST',
            data: JSON.stringify(jsonData),
            contentType: 'application/json',
            success: function(res) {
                alert('Target bulanan berhasil diupdate!');
                $('#file_target').val(''); // Reset input
                loadMemberData(); // Refresh tabel dan chart
            },
            error: function(err) {
                alert('Gagal upload target. Pastikan format excel benar.');
                console.error(err);
            }
        });
    };
    reader.readAsArrayBuffer(file);
}

// ==========================================
// RENDER DONUT CHART (DENGAN ANGKA DI DALAMNYA)
// ==========================================
function renderDonutChart(labels, data) {
    var ctx = document.getElementById('donutChartMR').getContext('2d');
    if (mrDonutChart != null) mrDonutChart.destroy();

    mrDonutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#00c9a7', '#f6d365', '#ff5e62', '#36A2EB', '#FFCE56', '#9966FF'],
                borderWidth: 2,
                borderColor: '#252538' // Sesuain sama background lu
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutoutPercentage: 60, // Bikin lubangnya agak pas
            legend: {
                position: 'right',
                labels: { fontColor: '#d1d1d1', boxWidth: 12, padding: 15 }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var currentValue = dataset.data[tooltipItem.index];
                        return data.labels[tooltipItem.index] + ": " + currentValue + " Member";
                    }
                }
            }
        },
        // INI PLUGIN AJAIB BUAT NAMPILIN TEKS DI DALAM CHART
        plugins: [{
            afterDraw: function(chart) {
                var ctx = chart.chart.ctx;
                var dataset = chart.data.datasets[0];
                var meta = chart.getDatasetMeta(0);
                var total = dataset.data.reduce(function(a, b) { return a + b; }, 0);

                ctx.font = "bold 11px Arial";
                ctx.fillStyle = "#ffffff";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";

                meta.data.forEach(function(element, index) {
                    var value = dataset.data[index];
                    if (value > 0) {
                        var percentage = Math.round((value / total) * 100) + "%";
                        var position = element.tooltipPosition();
                        // Gambar bayangan text biar kebaca di warna terang
                        ctx.shadowColor = "rgba(0,0,0,0.5)";
                        ctx.shadowBlur = 4;
                        ctx.fillText(percentage, position.x, position.y);
                        ctx.shadowBlur = 0; // reset
                    }
                });
            }
        }]
    });
}

// ==========================================
// 1. LOGIKA TARGET & REALISASI MR (UTAMA)
// ==========================================
function loadMemberData() {
    var tglLengkap = $('#filter_tanggal').val(); 
    
    if(tglLengkap) {
        var parts = tglLengkap.split('-'); 
        var year = parts[0];
        var monthIndex = parseInt(parts[1]) - 1; 
        var namaBulan = ["JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"];
        $('#judul_mr').html('Target & Realisasi MR <span class="text-warning"> - ' + namaBulan[monthIndex] + ' ' + year + '</span>');
    }

    var tglIndo = tglLengkap.split('-').reverse().join('-');
    $('#th_harian').text('GET HARI INI (' + tglIndo + ')');

    $.ajax({
        url: 'simul/api_table_getmember.php', 
        type: 'GET',
        data: { tanggal: tglLengkap },
        dataType: 'json',
        success: function(response) {
            // response.data_mr itu array datanya, response.summary itu totalannya
            var data = response.data_mr; 
            currentDataMR = data; 
            
            // ISI KOTAK INFO
            if(response.summary) {
                // Format angka biar ada titiknya (contoh: 1.200)
                $('#info_total_member').text(new Intl.NumberFormat('id-ID').format(response.summary.total_member_all_time));
                $('#info_target_bulanan').text(new Intl.NumberFormat('id-ID').format(response.summary.target_bulan_ini));
            }
            
            var html = '';
            var no = 1;
            var totTarget = 0; var totGet = 0; var totHarian = 0;
            var chartLabels = []; var chartData = [];

            if(data && data.length > 0) {
                $.each(data, function(key, item) {
                    var target = parseInt(item.target);
                    var realisasi = parseInt(item.jmlh_get);
                    var harian = parseInt(item.get_harian);
                    var persen = parseFloat(item.persen);
                    var mrName = item.mr ? item.mr : 'UNDEFINED';
                    var totalAllTime = parseInt(item.total_all_time); 

                    totTarget += target;
                    totGet += realisasi;
                    totHarian += harian;

                    chartLabels.push(mrName);
                    chartData.push(totalAllTime); 

                    var widthStyle = persen > 100 ? 100 : persen;
                    if (widthStyle < 0) widthStyle = 0;
                    var progressClass = persen >= 80 ? 'high' : ''; 
                    var harianSign = harian > 0 ? '+' : '';
                    var harianClass = harian > 0 ? 'text-success' : 'text-muted';

                    html += '<tr>';
                    html += '<td class="text-center text-muted" width="5%">' + no++ + '</td>';
                    html += '<td width="20%"><div class="font-weight-bold" style="color:#d1d1d1;">' + mrName + '</div></td>';
                    html += '<td class="text-center" width="15%"><span class="text-value-main">' + target + '</span></td>';
                    
                    html += '<td class="text-center col-clickable" width="15%" onclick="showDetailMember(\'' + mrName + '\')" title="Klik untuk lihat detail member">';
                    html += '   <span class="text-value-main">' + realisasi + '</span></td>';
                    
                    html += '<td width="25%">';
                    html += '  <div class="d-flex justify-content-between mb-1"><span class="percent-text">' + persen + '%</span></div>';
                    html += '  <div class="progress-track"><div class="progress-fill ' + progressClass + '" style="width: ' + widthStyle + '%"></div></div></td>';
                    html += '<td class="highlight-column text-center ' + harianClass + '"><span style="font-size:1.1rem">' + harianSign + harian + '</span></td>';
                    html += '</tr>';
                });

                var totPersen = totTarget > 0 ? Math.round((totGet / totTarget) * 100) : (totGet > 0 ? 100 : 0);
                var totWidthStyle = totPersen > 100 ? 100 : totPersen;
                var totProgressClass = totPersen >= 80 ? 'high' : '';

                $('#body_target_mr').html(html);
                
                var footerHtml = '<tr class="footer-row" style="border-top: 2px solid #444; background-color: rgba(0,0,0,0.2);">';
                footerHtml += '<td colspan="2" class="text-right text-white font-weight-bold" style="padding-right:20px;">TOTAL AREA</td>';
                footerHtml += '<td class="text-center text-white font-weight-bold" style="font-size:1.2rem;">' + totTarget + '</td>';
                footerHtml += '<td class="text-center text-white font-weight-bold" style="font-size:1.2rem;">' + totGet + '</td>';
                footerHtml += '<td><div class="d-flex justify-content-between mb-1"><span class="percent-text text-white">' + totPersen + '%</span></div>';
                footerHtml += '<div class="progress-track" style="background-color: #555;"><div class="progress-fill ' + totProgressClass + '" style="width: ' + totWidthStyle + '%"></div></div></td>';
                footerHtml += '<td class="text-center text-success font-weight-bold" style="font-size:1.2rem;">+' + totHarian + '</td></tr>';
                
                $('#foot_target_mr').html(footerHtml);

                renderDonutChart(chartLabels, chartData);

            } else {
                $('#body_target_mr').html('<tr><td colspan="6" class="text-center text-muted p-5">Data tidak ditemukan untuk tanggal ini.</td></tr>');
                $('#foot_target_mr').html('');
                $('#info_total_member').text('0'); $('#info_target_bulanan').text('0');
                if (mrDonutChart != null) mrDonutChart.destroy();
            }
        },
        error: function(xhr, status, error) {
            console.error("Error load data:", error);
            $('#body_target_mr').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    });
}

// ==========================================
// KODE LAINNYA DIBAWAH SINI SAMA AJA KAYA SEBELUMNYA
// ==========================================

function loadPBComparison() {
    var start = $('#pb_start_date').val();
    var end   = $('#pb_end_date').val();

    if(!start || !end) {
        alert("Harap pilih tanggal awal dan akhir.");
        return;
    }

    $('#body_pb_mr').html('<tr><td colspan="7" class="text-center p-4">Sedang memuat data... <div class="spinner-border spinner-border-sm text-info"></div></td></tr>');

    $.ajax({
        url: 'simul/api_get_perbandingan_pb_mr.php',
        type: 'GET',
        data: { start: start, end: end },
        dataType: 'json',
        success: function(data) {
            var html = '';
            var no = 1;

            if(data.length > 0) {
                $.each(data, function(key, item) {
                    var totalRange = parseInt(item.total_range);
                    var validRange = parseInt(item.valid_range);
                    var batalRange = parseInt(item.batal_range);
                    
                    var totalToday = parseInt(item.total_today);
                    var validToday = parseInt(item.valid_today); 

                    var persen = parseFloat(item.persen);
                    var mrName = item.salesman;

                    var gradientStyle = '';
                    if(persen >= 90) gradientStyle = 'background: linear-gradient(90deg, #00c9a7, #00d2ff); box-shadow: 0 0 10px rgba(0,210,255,0.5);';
                    else if(persen >= 70) gradientStyle = 'background: linear-gradient(90deg, #f6d365, #fda085);';
                    else gradientStyle = 'background: linear-gradient(90deg, #ff9966, #ff5e62);';

                    html += '<tr>';
                    html += '<td class="text-center text-muted">' + no++ + '</td>';
                    html += '<td><div class="font-weight-bold" style="color:#e2e2e2;">' + mrName + '</div></td>';
                    
                    var todayClass = totalToday > 0 ? 'text-warning font-weight-bold' : 'text-muted';
                    var todaySign = totalToday > 0 ? '+' : '';
                    html += '<td class="text-center" style="border-right: 1px solid #444; background-color:rgba(255, 193, 7, 0.05);">';
                    html += '   <span class="'+todayClass+'" style="font-size:1.1rem">' + todaySign + totalToday + '</span>';
                    html += '   <div style="font-size:0.7rem; color:#888;">Valid: '+validToday+'</div>';
                    html += '</td>';

                    html += '<td class="text-center"><span class="text-value-main">' + totalRange + '</span></td>';
                    html += '<td class="text-center"><span class="font-weight-bold text-success">' + validRange + '</span></td>';
                    html += '<td class="text-center"><span class="font-weight-bold text-danger">' + batalRange + '</span></td>';
                    
                    html += '<td>';
                    html += '   <div class="d-flex justify-content-between mb-1"><span class="small font-weight-bold text-white">' + persen + '% Valid</span></div>';
                    html += '   <div class="progress-track"><div style="height:100%; border-radius:10px; width:'+persen+'%; '+gradientStyle+'"></div></div>';
                    html += '</td></tr>';
                });
                $('#body_pb_mr').html(html);
            } else {
                $('#body_pb_mr').html('<tr><td colspan="7" class="text-center text-muted p-5">Tidak ada data pada periode ini.</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error load PB:", error);
            $('#body_pb_mr').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    });
}

function loadMemberSleeper() {
    var interval = $('#sleeper_interval').val() || 2;
    $('#body_sleeper').html('<tr><td colspan="7" class="text-center p-4">Sedang mencari data sleeper... <div class="spinner-border spinner-border-sm text-danger"></div></td></tr>');
    if($('#sleeper_subtitle').length) $('#sleeper_subtitle').text('Monitoring member tidak aktif belanja selama ' + interval + ' bulan');

    $.ajax({
        url: 'simul/api_get_member_sleeper.php', type: 'GET', data: { interval: interval }, dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                globalSleeperData = response.data;
                populateSalesmanFilter(globalSleeperData);
                currentSleeperPage = 1; renderSleeperTable();
            } else { $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-danger">Error: ' + response.message + '</td></tr>'); }
        },
        error: function(xhr, status, error) { $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data sleeper. Periksa koneksi/API.</td></tr>'); }
    });
}

function populateSalesmanFilter(data) {
    var salesmanSet = new Set();
    $.each(data, function(i, item){ if(item.salesman) salesmanSet.add(item.salesman); });
    var selectHtml = '<option value="ALL">SEMUA</option>';
    var sortedSales = Array.from(salesmanSet).sort();
    sortedSales.forEach(function(sales) { selectHtml += '<option value="'+sales+'">'+sales+'</option>'; });
    $('#sleeper_salesman_filter').html(selectHtml); $('#sleeper_salesman_filter').val('ALL');
}

function renderSleeperTable() {
    var selectedSales = $('#sleeper_salesman_filter').val();
    var filteredData = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') { filteredData = globalSleeperData.filter(function(item) { return item.salesman === selectedSales; }); }
    var totalItems = filteredData.length;
    if (totalItems === 0) {
        $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-muted p-5">Tidak ditemukan data.</td></tr>');
        $('#sleeperPaginationControls').html(''); $('#sleeperPageInfo').text(''); return;
    }
    var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    if (currentSleeperPage > totalPages) currentSleeperPage = 1;
    var startIndex = (currentSleeperPage - 1) * sleeperItemsPerPage;
    var endIndex = startIndex + sleeperItemsPerPage;
    var slicedData = filteredData.slice(startIndex, endIndex);
    var html = '';
    
    $.each(slicedData, function(i, item){
        var actualNo = startIndex + i + 1;
        var omzet = new Intl.NumberFormat('id-ID').format(item.dtl_gross);
        html += '<tr><td class="text-center text-muted">' + actualNo + '</td>';
        html += '<td><span class="badge badge-secondary" style="font-size:0.9em; background-color:#343a40; border:1px solid #555;">' + item.kode + '</span></td>';
        html += '<td class="font-weight-bold text-light">' + item.nama_member + '</td>';
        html += '<td class="small text-muted" style="line-height:1.2;">' + item.alamat + '</td>';
        html += '<td class="text-center text-danger font-weight-bold">' + item.tgl_kunjungan_terakhir + '</td>';
        html += '<td class="text-center text-info">' + (item.salesman ? item.salesman : '-') + '</td>';
        html += '<td class="text-right text-success small">Rp ' + omzet + '</td></tr>';
    });
    $('#body_sleeper').html(html);
    var showEnd = endIndex > totalItems ? totalItems : endIndex;
    $('#sleeperPageInfo').html('Menampilkan <span class="text-white">' + (startIndex + 1) + '</span> - <span class="text-white">' + showEnd + '</span> dari <span class="text-white">' + totalItems + '</span> Member Sleeper');
    renderSleeperPagination(totalItems);
}

function renderSleeperPagination(totalItems) {
    var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    if (totalPages <= 1) { $('#sleeperPaginationControls').html(''); return; }
    var html = '<ul class="pagination-modern">';
    var prevDisabled = currentSleeperPage === 1 ? 'disabled' : '';
    html += '<li class="page-item-modern ' + prevDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeSleeperPage(' + (currentSleeperPage - 1) + ')"><span class="page-link-modern"><i class="fas fa-chevron-left"></i></span></li>';
    var startPage = Math.max(1, currentSleeperPage - 2); var endPage = Math.min(totalPages, currentSleeperPage + 2);
    for (var i = startPage; i <= endPage; i++) {
        var activeClass = i === currentSleeperPage ? 'active' : '';
        html += '<li class="page-item-modern ' + activeClass + '" onclick="changeSleeperPage(' + i + ')"><span class="page-link-modern">' + i + '</span></li>';
    }
    var nextDisabled = currentSleeperPage === totalPages ? 'disabled' : '';
    html += '<li class="page-item-modern ' + nextDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeSleeperPage(' + (currentSleeperPage + 1) + ')"><span class="page-link-modern"><i class="fas fa-chevron-right"></i></span></li></ul>';
    $('#sleeperPaginationControls').html(html);
}

function changeSleeperPage(newPage) {
    var selectedSales = $('#sleeper_salesman_filter').val();
    var filteredData = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') { filteredData = globalSleeperData.filter(item => item.salesman === selectedSales); }
    var totalItems = filteredData.length; var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    if (newPage < 1 || newPage > totalPages) return;
    currentSleeperPage = newPage; renderSleeperTable();
}

function exportSleeperExcel() {
    var selectedSales = $('#sleeper_salesman_filter').val();
    var dataToExport = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') { dataToExport = globalSleeperData.filter(item => item.salesman === selectedSales); }
    if(dataToExport.length === 0) { alert("Tidak ada data member sleeper untuk diexport."); return; }
    var exportData = dataToExport.map(function(item) {
        return {
            "Kode Member": item.kode, "Nama Member": item.nama_member, "Alamat": item.alamat, "No Telp": item.no_aktif,
            "Terdaftar Sejak": item.regis, "Kunjungan Terakhir": item.tgl_kunjungan_terakhir, "Salesman": item.salesman, "Omzet Terakhir": item.dtl_gross
        };
    });
    var ws = XLSX.utils.json_to_sheet(exportData); var wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, "Member Sleeper");
    var dateString = new Date().toISOString().split('T')[0]; var suffix = selectedSales === 'ALL' ? 'ALL' : selectedSales;
    XLSX.writeFile(wb, 'Laporan_Member_Sleeper_'+suffix+'_' + dateString + '.xlsx');
}

function showDetailMember(salesmanName) {
    var tglLengkap = $('#filter_tanggal').val();
    
    $('#modalDetailTitle').text('Detail Member: ' + salesmanName);
    $('#body_detail_member').html('<tr><td colspan="5" class="text-center p-3">Sedang memuat data... <div class="spinner-border spinner-border-sm" role="status"></div></td></tr>');
    $('#paginationControls').html(''); 
    $('#pageInfo').text(''); 

    // FIX BUG MODAL KETUMPUK BACKDROP: Tambahin .appendTo("body")
    $('#modalDetailMember').appendTo("body").modal('show');

    $.ajax({
        url: 'simul/api_detail_getmember.php',
        type: 'GET',
        data: { 
            salesman: salesmanName,
            tanggal: tglLengkap 
        },
        dataType: 'json',
        success: function(data) {
            globalDetailData = data;
            currentDetailPage = 1;
            renderDetailTable();
        },
        error: function(xhr, status, error) {
            console.error("Error detail:", error);
            $('#body_detail_member').html('<tr><td colspan="5" class="text-center text-danger p-3">Gagal mengambil data detail.</td></tr>');
        }
    });
}

function renderDetailTable() {
    var data = globalDetailData; var totalItems = data.length;
    if (totalItems === 0) {
        $('#body_detail_member').html('<tr><td colspan="5" class="text-center p-3 text-muted">Tidak ada data detail member bulan ini.</td></tr>');
        $('#paginationControls').html(''); $('#pageInfo').text('0 data'); return;
    }
    var startIndex = (currentDetailPage - 1) * itemsPerPage; var endIndex = startIndex + itemsPerPage;
    var slicedData = data.slice(startIndex, endIndex); var html = '';
    
    $.each(slicedData, function(i, item){
        var actualNo = startIndex + i + 1; 
        html += '<tr><td class="text-center text-muted">' + actualNo + '</td>';
        html += '<td><span class="badge-code">' + item.kode + '</span></td>';
        html += '<td class="font-weight-bold text-white">' + item.nama + '</td>';
        html += '<td class="small text-muted" style="line-height:1.2;">' + item.alamat + '</td>';
        html += '<td class="text-center text-info">' + item.tgl_reg + '</td></tr>';
    });
    $('#body_detail_member').html(html);
    var showEnd = endIndex > totalItems ? totalItems : endIndex;
    $('#pageInfo').text('Menampilkan ' + (startIndex + 1) + ' - ' + showEnd + ' dari ' + totalItems + ' data');
    renderPaginationControls(totalItems);
}

function renderPaginationControls(totalItems) {
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) { $('#paginationControls').html(''); return; }
    var html = '<ul class="pagination-modern">';
    var prevDisabled = currentDetailPage === 1 ? 'disabled' : '';
    html += '<li class="page-item-modern ' + prevDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeDetailPage(' + (currentDetailPage - 1) + ')"><span class="page-link-modern"><i class="fas fa-chevron-left"></i></span></li>';
    var startPage = Math.max(1, currentDetailPage - 2); var endPage = Math.min(totalPages, currentDetailPage + 2);
    for (var i = startPage; i <= endPage; i++) {
        var activeClass = i === currentDetailPage ? 'active' : '';
        html += '<li class="page-item-modern ' + activeClass + '" onclick="changeDetailPage(' + i + ')"><span class="page-link-modern">' + i + '</span></li>';
    }
    var nextDisabled = currentDetailPage === totalPages ? 'disabled' : '';
    html += '<li class="page-item-modern ' + nextDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeDetailPage(' + (currentDetailPage + 1) + ')"><span class="page-link-modern"><i class="fas fa-chevron-right"></i></span></li></ul>';
    $('#paginationControls').html(html);
}

function changeDetailPage(newPage) {
    var totalItems = globalDetailData.length; var totalPages = Math.ceil(totalItems / itemsPerPage);
    if (newPage < 1 || newPage > totalPages) return;
    currentDetailPage = newPage; renderDetailTable(); 
}

function exportToExcel() {
    var table = document.getElementById("table_target_mr");
    var wb = XLSX.utils.table_to_book(table, {sheet: "Laporan MR", raw: true});
    var dateString = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, 'Laporan_MR_' + dateString + '.xlsx');
}