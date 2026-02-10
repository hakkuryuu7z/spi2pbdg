// --- VARIABEL GLOBAL UTAMA ---
var currentDataMR = []; 
var globalDetailData = []; 
var currentDetailPage = 1; 
var itemsPerPage = 7;

// --- VARIABEL GLOBAL MEMBER SLEEPER ---
var globalSleeperData = [];
var currentSleeperPage = 1;
var sleeperItemsPerPage = 10; // Menampilkan 10 data per halaman tabel sleeper

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
    loadMemberSleeper(); // Load data sleeper saat pertama buka
});


// ==========================================
// 1. LOGIKA TARGET & REALISASI MR (UTAMA)
// ==========================================
function loadMemberData() {
    var tglLengkap = $('#filter_tanggal').val(); 
    
    // Update Judul
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
        success: function(data) {
            currentDataMR = data; 
            
            var html = '';
            var no = 1;
            var totTarget = 0; var totGet = 0; var totHarian = 0;

            if(data.length > 0) {
                $.each(data, function(key, item) {
                    var target = parseInt(item.target);
                    var realisasi = parseInt(item.jmlh_get);
                    var harian = parseInt(item.get_harian);
                    var persen = parseFloat(item.persen);
                    var mrName = item.mr ? item.mr : 'UNDEFINED';

                    totTarget += target;
                    totGet += realisasi;
                    totHarian += harian;

                    var widthStyle = persen > 100 ? 100 : persen;
                    if (widthStyle < 0) widthStyle = 0;
                    var progressClass = persen >= 80 ? 'high' : ''; 
                    var harianSign = harian > 0 ? '+' : '';
                    var harianClass = harian > 0 ? 'text-success' : 'text-muted';

                    html += '<tr>';
                    html += '<td class="text-center text-muted" width="5%">' + no++ + '</td>';
                    html += '<td width="20%"><div class="font-weight-bold" style="color:#d1d1d1;">' + mrName + '</div></td>';
                    html += '<td class="text-center" width="15%"><span class="text-value-main">' + target + '</span></td>';
                    
                    // Kolom Clickable
                    html += '<td class="text-center col-clickable" width="15%" onclick="showDetailMember(\'' + mrName + '\')" title="Klik untuk lihat detail member">';
                    html += '   <span class="text-value-main">' + realisasi + '</span>';
                    html += '</td>';
                    
                    html += '<td width="25%">';
                    html += '  <div class="d-flex justify-content-between mb-1"><span class="percent-text">' + persen + '%</span></div>';
                    html += '  <div class="progress-track"><div class="progress-fill ' + progressClass + '" style="width: ' + widthStyle + '%"></div></div>';
                    html += '</td>';
                    html += '<td class="highlight-column text-center ' + harianClass + '"><span style="font-size:1.1rem">' + harianSign + harian + '</span></td>';
                    html += '</tr>';
                });

                var totPersen = totTarget > 0 ? Math.round((totGet / totTarget) * 100) : 0;
                var totWidthStyle = totPersen > 100 ? 100 : totPersen;
                var totProgressClass = totPersen >= 80 ? 'high' : '';

                $('#body_target_mr').html(html);
                
                // Footer
                var footerHtml = '<tr class="footer-row" style="border-top: 2px solid #444; background-color: rgba(0,0,0,0.2);">';
                footerHtml += '<td colspan="2" class="text-right text-white font-weight-bold" style="padding-right:20px;">TOTAL AREA</td>';
                footerHtml += '<td class="text-center text-white font-weight-bold" style="font-size:1.2rem;">' + totTarget + '</td>';
                footerHtml += '<td class="text-center text-white font-weight-bold" style="font-size:1.2rem;">' + totGet + '</td>';
                footerHtml += '<td><div class="d-flex justify-content-between mb-1"><span class="percent-text text-white">' + totPersen + '%</span></div>';
                footerHtml += '<div class="progress-track" style="background-color: #555;"><div class="progress-fill ' + totProgressClass + '" style="width: ' + totWidthStyle + '%"></div></div></td>';
                footerHtml += '<td class="text-center text-success font-weight-bold" style="font-size:1.2rem;">+' + totHarian + '</td>';
                footerHtml += '</tr>';
                
                $('#foot_target_mr').html(footerHtml);
            } else {
                $('#body_target_mr').html('<tr><td colspan="6" class="text-center text-muted p-5">Data tidak ditemukan untuk tanggal ini.</td></tr>');
                $('#foot_target_mr').html('');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error load data:", error);
            $('#body_target_mr').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    });
}

// ==========================================
// 2. LOGIKA PERBANDINGAN PB
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
        data: { 
            start: start, 
            end: end 
        },
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

                    // Gradient
                    var gradientStyle = '';
                    if(persen >= 90) gradientStyle = 'background: linear-gradient(90deg, #00c9a7, #00d2ff); box-shadow: 0 0 10px rgba(0,210,255,0.5);';
                    else if(persen >= 70) gradientStyle = 'background: linear-gradient(90deg, #f6d365, #fda085);';
                    else gradientStyle = 'background: linear-gradient(90deg, #ff9966, #ff5e62);';

                    html += '<tr>';
                    html += '<td class="text-center text-muted">' + no++ + '</td>';
                    html += '<td><div class="font-weight-bold" style="color:#e2e2e2;">' + mrName + '</div></td>';
                    
                    // --- KOLOM HARI INI (REAL TIME) ---
                    var todayClass = totalToday > 0 ? 'text-warning font-weight-bold' : 'text-muted';
                    var todaySign = totalToday > 0 ? '+' : '';
                    html += '<td class="text-center" style="border-right: 1px solid #444; background-color:rgba(255, 193, 7, 0.05);">';
                    html += '   <span class="'+todayClass+'" style="font-size:1.1rem">' + todaySign + totalToday + '</span>';
                    html += '   <div style="font-size:0.7rem; color:#888;">Valid: '+validToday+'</div>';
                    html += '</td>';

                    // --- KOLOM RANGE ---
                    html += '<td class="text-center"><span class="text-value-main">' + totalRange + '</span></td>';
                    html += '<td class="text-center"><span class="font-weight-bold text-success">' + validRange + '</span></td>';
                    html += '<td class="text-center"><span class="font-weight-bold text-danger">' + batalRange + '</span></td>';
                    
                    // --- SUCCESS RATE ---
                    html += '<td>';
                    html += '   <div class="d-flex justify-content-between mb-1">';
                    html += '       <span class="small font-weight-bold text-white">' + persen + '% Valid</span>';
                    html += '   </div>';
                    html += '   <div class="progress-track">';
                    html += '       <div style="height:100%; border-radius:10px; width:'+persen+'%; '+gradientStyle+'"></div>';
                    html += '   </div>';
                    html += '</td>';

                    html += '</tr>';
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

// ==========================================
// 3. LOGIKA MEMBER SLEEPER (UPDATED FILTER)
// ==========================================
function loadMemberSleeper() {
    var interval = $('#sleeper_interval').val();
    if(!interval) interval = 2; // Default fallback

    $('#body_sleeper').html('<tr><td colspan="7" class="text-center p-4">Sedang mencari data sleeper... <div class="spinner-border spinner-border-sm text-danger"></div></td></tr>');
    
    // Update subtitle text jika elemen ada
    if($('#sleeper_subtitle').length) {
        $('#sleeper_subtitle').text('Monitoring member tidak aktif belanja selama ' + interval + ' bulan');
    }

    $.ajax({
        url: 'simul/api_get_member_sleeper.php',
        type: 'GET',
        data: { interval: interval },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                globalSleeperData = response.data; // Simpan ke global variable
                
                // ISI DROPDOWN FILTER SALESMAN
                populateSalesmanFilter(globalSleeperData);

                currentSleeperPage = 1; // Reset ke halaman 1
                renderSleeperTable(); // Render tabel
            } else {
                $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error load sleeper:", error);
            $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data sleeper. Periksa koneksi/API.</td></tr>');
        }
    });
}

// FUNGSI POPULATE DROPDOWN
function populateSalesmanFilter(data) {
    var salesmanSet = new Set();
    
    // Ambil nama salesman unik
    $.each(data, function(i, item){
        if(item.salesman) salesmanSet.add(item.salesman);
    });

    var selectHtml = '<option value="ALL">SEMUA</option>';
    
    // Sort A-Z
    var sortedSales = Array.from(salesmanSet).sort();
    
    sortedSales.forEach(function(sales) {
        selectHtml += '<option value="'+sales+'">'+sales+'</option>';
    });

    // Masukkan ke Select di HTML
    $('#sleeper_salesman_filter').html(selectHtml);
    $('#sleeper_salesman_filter').val('ALL');
}

function renderSleeperTable() {
    // 1. Ambil Nilai Filter
    var selectedSales = $('#sleeper_salesman_filter').val();
    
    // 2. Filter Data Global
    var filteredData = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') {
        filteredData = globalSleeperData.filter(function(item) {
            return item.salesman === selectedSales;
        });
    }

    var totalItems = filteredData.length;

    if (totalItems === 0) {
        $('#body_sleeper').html('<tr><td colspan="7" class="text-center text-muted p-5">Tidak ditemukan data untuk salesman: '+selectedSales+'.</td></tr>');
        $('#sleeperPaginationControls').html('');
        $('#sleeperPageInfo').text('');
        return;
    }

    // 3. Logic Pagination (Reset page jika overflow)
    var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    if (currentSleeperPage > totalPages) currentSleeperPage = 1;

    var startIndex = (currentSleeperPage - 1) * sleeperItemsPerPage;
    var endIndex = startIndex + sleeperItemsPerPage;
    var slicedData = filteredData.slice(startIndex, endIndex);

    var html = '';
    
    $.each(slicedData, function(i, item){
        var actualNo = startIndex + i + 1;
        // Format Angka Rupiah
        var omzet = new Intl.NumberFormat('id-ID').format(item.dtl_gross);

        html += '<tr>';
        html += '<td class="text-center text-muted">' + actualNo + '</td>';
        html += '<td><span class="badge badge-secondary" style="font-size:0.9em; background-color:#343a40; border:1px solid #555;">' + item.kode + '</span></td>';
        html += '<td class="font-weight-bold text-light">' + item.nama_member + '</td>';
        html += '<td class="small text-muted" style="line-height:1.2;">' + item.alamat + '</td>';
        html += '<td class="text-center text-danger font-weight-bold">' + item.tgl_kunjungan_terakhir + '</td>';
        html += '<td class="text-center text-info">' + (item.salesman ? item.salesman : '-') + '</td>';
        html += '<td class="text-right text-success small">Rp ' + omzet + '</td>';
        html += '</tr>';
    });

    $('#body_sleeper').html(html);

    // Update Info Halaman
    var showEnd = endIndex > totalItems ? totalItems : endIndex;
    $('#sleeperPageInfo').html('Menampilkan <span class="text-white">' + (startIndex + 1) + '</span> - <span class="text-white">' + showEnd + '</span> dari <span class="text-white">' + totalItems + '</span> Member Sleeper');

    // Render Pagination dengan jumlah data yang sudah di-filter
    renderSleeperPagination(totalItems);
}

function renderSleeperPagination(totalItems) {
    var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    
    if (totalPages <= 1) {
        $('#sleeperPaginationControls').html('');
        return;
    }

    var html = '<ul class="pagination-modern">';
    
    // Tombol Previous
    var prevDisabled = currentSleeperPage === 1 ? 'disabled' : '';
    html += '<li class="page-item-modern ' + prevDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeSleeperPage(' + (currentSleeperPage - 1) + ')">';
    html += '<span class="page-link-modern"><i class="fas fa-chevron-left"></i></span></li>';

    // Logic ellipsis (tampilkan 5 page sekitar page aktif)
    var startPage = Math.max(1, currentSleeperPage - 2);
    var endPage = Math.min(totalPages, currentSleeperPage + 2);

    for (var i = startPage; i <= endPage; i++) {
        var activeClass = i === currentSleeperPage ? 'active' : '';
        html += '<li class="page-item-modern ' + activeClass + '" onclick="changeSleeperPage(' + i + ')">';
        html += '<span class="page-link-modern">' + i + '</span></li>';
    }

    // Tombol Next
    var nextDisabled = currentSleeperPage === totalPages ? 'disabled' : '';
    html += '<li class="page-item-modern ' + nextDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeSleeperPage(' + (currentSleeperPage + 1) + ')">';
    html += '<span class="page-link-modern"><i class="fas fa-chevron-right"></i></span></li>';

    html += '</ul>';
    $('#sleeperPaginationControls').html(html);
}

function changeSleeperPage(newPage) {
    // Hitung ulang total items berdasarkan filter saat ini agar tidak error
    var selectedSales = $('#sleeper_salesman_filter').val();
    var filteredData = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') {
        filteredData = globalSleeperData.filter(item => item.salesman === selectedSales);
    }
    
    var totalItems = filteredData.length;
    var totalPages = Math.ceil(totalItems / sleeperItemsPerPage);
    
    if (newPage < 1 || newPage > totalPages) return;
    
    currentSleeperPage = newPage;
    renderSleeperTable();
}

function exportSleeperExcel() {
    // 1. Ambil Filter
    var selectedSales = $('#sleeper_salesman_filter').val();
    
    // 2. Filter Data Sebelum Export
    var dataToExport = globalSleeperData;
    if (selectedSales && selectedSales !== 'ALL') {
        dataToExport = globalSleeperData.filter(item => item.salesman === selectedSales);
    }

    if(dataToExport.length === 0) {
        alert("Tidak ada data member sleeper untuk diexport.");
        return;
    }
    
    // 3. Mapping Data
    var exportData = dataToExport.map(function(item) {
        return {
            "Kode Member": item.kode,
            "Nama Member": item.nama_member,
            "Alamat": item.alamat,
            "No Telp": item.no_aktif,
            "Terdaftar Sejak": item.regis,
            "Kunjungan Terakhir": item.tgl_kunjungan_terakhir,
            "Salesman": item.salesman,
            "Omzet Terakhir": item.dtl_gross
        };
    });

    var ws = XLSX.utils.json_to_sheet(exportData);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Member Sleeper");
    
    var dateString = new Date().toISOString().split('T')[0];
    var suffix = selectedSales === 'ALL' ? 'ALL' : selectedSales;
    XLSX.writeFile(wb, 'Laporan_Member_Sleeper_'+suffix+'_' + dateString + '.xlsx');
}


// ==========================================
// 4. MODAL & HELPER LAINNYA
// ==========================================

function showEditTargetModal() {
    var formHtml = '';
    if(currentDataMR.length > 0){
        $.each(currentDataMR, function(key, item){
            var nama = item.mr ? item.mr : 'UNDEFINED';
            if(nama !== 'UNDEFINED') {
                formHtml += '<div class="form-group row">';
                formHtml += '<label class="col-sm-4 col-form-label font-weight-bold text-dark">'+nama+'</label>'; 
                formHtml += '<div class="col-sm-8">';
                formHtml += '<input type="number" class="form-control input-target-val" data-name="'+nama+'" value="'+item.target+'">';
                formHtml += '</div></div>';
            }
        });
    } else {
        formHtml = '<p class="text-dark">Tidak ada data salesman untuk diatur pada tanggal ini.</p>';
    }
    $('#targetInputs').html(formHtml);
    $('#modalEditTarget').modal('show');
}

function saveTarget() {
    var newTargets = {};
    $('.input-target-val').each(function() {
        var name = $(this).data('name');
        var val = $(this).val();
        newTargets[name] = val;
    });

    $.ajax({
        url: 'simul/api_save_target.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(newTargets),
        success: function(response) {
            alert('Target berhasil disimpan!');
            $('#modalEditTarget').modal('hide');
            loadMemberData(); 
        },
        error: function() {
            alert('Gagal menyimpan target.');
        }
    });
}

function showDetailMember(salesmanName) {
    var tglLengkap = $('#filter_tanggal').val();
    
    $('#modalDetailTitle').text('Detail Member: ' + salesmanName);
    
    $('#body_detail_member').html('<tr><td colspan="5" class="text-center p-3">Sedang memuat data... <div class="spinner-border spinner-border-sm" role="status"></div></td></tr>');
    $('#paginationControls').html(''); 
    $('#pageInfo').text(''); 

    $('#modalDetailMember').modal('show');

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
    var data = globalDetailData;
    var totalItems = data.length;
    
    if (totalItems === 0) {
        $('#body_detail_member').html('<tr><td colspan="5" class="text-center p-3 text-muted">Tidak ada data detail member bulan ini.</td></tr>');
        $('#paginationControls').html('');
        $('#pageInfo').text('0 data');
        return;
    }

    var startIndex = (currentDetailPage - 1) * itemsPerPage;
    var endIndex = startIndex + itemsPerPage;
    var slicedData = data.slice(startIndex, endIndex);

    var html = '';
    
    $.each(slicedData, function(i, item){
        var actualNo = startIndex + i + 1; 

        html += '<tr>';
        html += '<td class="text-center text-muted">' + actualNo + '</td>';
        html += '<td><span class="badge-code">' + item.kode + '</span></td>';
        html += '<td class="font-weight-bold text-white">' + item.nama + '</td>';
        html += '<td class="small text-muted" style="line-height:1.2;">' + item.alamat + '</td>';
        html += '<td class="text-center text-info">' + item.tgl_reg + '</td>';
        html += '</tr>';
    });

    $('#body_detail_member').html(html);

    var showEnd = endIndex > totalItems ? totalItems : endIndex;
    $('#pageInfo').text('Menampilkan ' + (startIndex + 1) + ' - ' + showEnd + ' dari ' + totalItems + ' data');

    renderPaginationControls(totalItems);
}

function renderPaginationControls(totalItems) {
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (totalPages <= 1) {
        $('#paginationControls').html('');
        return;
    }

    var html = '<ul class="pagination-modern">';
    var prevDisabled = currentDetailPage === 1 ? 'disabled' : '';
    html += '<li class="page-item-modern ' + prevDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeDetailPage(' + (currentDetailPage - 1) + ')">';
    html += '<span class="page-link-modern"><i class="fas fa-chevron-left"></i></span></li>';

    var startPage = Math.max(1, currentDetailPage - 2);
    var endPage = Math.min(totalPages, currentDetailPage + 2);

    for (var i = startPage; i <= endPage; i++) {
        var activeClass = i === currentDetailPage ? 'active' : '';
        html += '<li class="page-item-modern ' + activeClass + '" onclick="changeDetailPage(' + i + ')">';
        html += '<span class="page-link-modern">' + i + '</span></li>';
    }

    var nextDisabled = currentDetailPage === totalPages ? 'disabled' : '';
    html += '<li class="page-item-modern ' + nextDisabled + '" onclick="if(!this.classList.contains(\'disabled\')) changeDetailPage(' + (currentDetailPage + 1) + ')">';
    html += '<span class="page-link-modern"><i class="fas fa-chevron-right"></i></span></li>';

    html += '</ul>';
    $('#paginationControls').html(html);
}

function changeDetailPage(newPage) {
    var totalItems = globalDetailData.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    if (newPage < 1 || newPage > totalPages) return;
    currentDetailPage = newPage;
    renderDetailTable(); 
}

function exportToExcel() {
    var table = document.getElementById("table_target_mr");
    var wb = XLSX.utils.table_to_book(table, {sheet: "Laporan MR", raw: true});
    var dateString = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, 'Laporan_MR_' + dateString + '.xlsx');
}