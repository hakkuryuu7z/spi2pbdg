// ===================================================================
// === FUNGSI GLOBAL (DI LUAR DOCUMENT READY) ===
// Diletakkan di sini agar bisa dipanggil dari tombol `onclick` yang dibuat dinamis.
// ===================================================================

/**
 * Memformat angka menjadi format mata uang Rupiah.
 */
function formatRupiah(angka) {
    if (angka === null || isNaN(angka)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

/**
 * Menampilkan modal untuk detail PB List.
 */
function lihatDetail(nomorPb) {
    const modalTitle = $('#detailPbModalLabel');
    const modalBody = $('#modalBodyContent');
    
    // Set judul dan loading
    modalTitle.text('Detail Pesanan: ' + nomorPb);
    modalBody.html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    
    // Tampilkan modal
    $('#detailPbModal').modal('show');
    
    $.ajax({
        url: 'detailsales/api_get_detail_po.php',
        type: 'GET',
        dataType: 'json',
        data: {
            nopb: nomorPb
        },
        success: function(response) {
            if (response.status === 'success' && response.data.length > 0) {
                const header = response.data[0];
                
                // Bagian Informasi Header (Atas Tabel)
                let contentHtml = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>No. PB:</strong> <span>${header.nopb || 'N/A'}</span><br>
                            <strong>Kode Member:</strong> <span>${header.kodemember || 'N/A'}</span><br>
                            <strong>Nama Member:</strong> <span>${header.nama_member || 'N/A'}</span>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>No. Transaksi:</strong> <span>${header.notrans || 'N/A'}</span><br>
                            <strong>Tgl. Transaksi:</strong> <span>${header.tgltrans || 'N/A'}</span>
                        </div>
                    </div><hr>
                    
                    <h5><i class="fas fa-box-open"></i> Detail Barang</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">No</th> <th>PLU</th>
                                    <th>Nama Produk</th>
                                    <th class="text-right">Qty Order</th>
                                    <th class="text-right">Qty Real</th>
                                    <th>Picker</th> 
                                </tr>
                            </thead>
                            <tbody>`;
                
                // Looping Data Barang
                // Tambahkan parameter 'index' di sini
                response.data.forEach((item, index) => {
                    contentHtml += `
                        <tr>
                            <td>${index + 1}</td> <td>${item.plu || 'N/A'}</td>
                            <td>${item.nama_produk || 'N/A'}</td>
                            <td class="text-right">${item.qty_order || 0}</td>
                            <td class="text-right">${item.qty_real || 0}</td>
                            <td>${item.obi_picker || '-'}</td> 
                        </tr>`;
                });

                // Bagian Footer (Total, Ongkir, dll)
                contentHtml += `</tbody></table></div><hr>
                    <div class="row mb-3 text-center">
                        <div class="col-md-4">
                            <strong>Total Order</strong>
                            <h5 class="font-weight-bold">${formatRupiah(header.obi_ttlorder)}</h5>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Real</strong>
                            <h5 class="font-weight-bold">${formatRupiah(header.obi_realorder)}</h5>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Diskon</strong>
                            <h5 class="font-weight-bold text-success">${formatRupiah(header.obi_realdiskon)}</h5>
                        </div>
                    </div><hr>`;
                
                contentHtml += `
                    <div class="row">
                        <div class="col-md-4"><strong>Tipe Bayar:</strong><br><span class="badge badge-pill badge-info">${header.tipebayar || 'N/A'}</span></div>
                        <div class="col-md-4"><strong>Ekspedisi:</strong><br><span>${header.ekspedisi || 'N/A'}</span></div>
                        <div class="col-md-4 text-md-right"><strong>Ongkir:</strong><br><h4>${formatRupiah(header.ongkir)}</h4></div>
                    </div>`;

                modalBody.html(contentHtml);
            } else {
                modalBody.html('<div class="alert alert-warning">Detail data tidak ditemukan.</div>');
            }
        },
        error: function() {
            modalBody.html('<div class="alert alert-danger">Gagal mengambil data dari server.</div>');
        }
    });
}

/**
 * Menampilkan modal untuk detail PLU.
 */
function lihatDetailPlu(plu, namaProduk) {
    const modalTitle = $('#detailPluModalLabel');
    const modalBody = $('#modalBodyContentPlu');
    
    let tglMulai = $('#filterTanggalMulai').val();
    let tglSelesai = $('#filterTanggalSelesai').val();
    const today = new Date().toISOString().split('T')[0];

    if (!tglMulai && !tglSelesai) {
        tglMulai = today;
        tglSelesai = today;
    } else if (!tglMulai) {
        tglMulai = tglSelesai;
    } else if (!tglSelesai) {
        tglSelesai = tglMulai;
    }

    modalTitle.text('Detail Transaksi: ' + namaProduk + ` (PLU: ${plu})`);
    modalBody.html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    $('#detailPluModal').modal('show');
    $.ajax({
        url: 'detailsales/api_get_detail_plu.php',
        type: 'GET',
        dataType: 'json',
        data: {
            plu: plu,
            tanggal_mulai: tglMulai,
            tanggal_selesai: tglSelesai
        },
        success: function(response) {
            if (response.status === 'success' && response.data.length > 0) {
                
                let contentHtml = `<div class="table-responsive"><table class="table table-sm table-bordered table-hover"><thead class="thead-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>No. PB</th>
                                        <th>Member</th>
                                        <th class="text-right">Qty Order</th>
                                        <th class="text-right">Qty Real</th>
                                    </tr>
                                  </thead><tbody>`;
                
                response.data.forEach(item => {
                    contentHtml += `<tr>
                                        <td>${item.tanggal_pb || 'N/A'}</td>
                                        <td>${item.status}</td>
                                        <td>${item.obi_nopb}</td>
                                        <td>${item.cus_namamember}</td>
                                        <td class="text-right">${item.obi_qtyorder}</td>
                                        <td class="text-right">${item.obi_qtyrealisasi}</td>
                                    </tr>`;
                });
                contentHtml += `</tbody></table></div>`;
                modalBody.html(contentHtml);
            } else {
                modalBody.html(`<div class="alert alert-warning">Tidak ada detail transaksi untuk produk ini pada rentang tanggal ${tglMulai} s/d ${tglSelesai}.</div>`);
            }
        },
        error: function() {
            modalBody.html('<div class="alert alert-danger">Gagal mengambil data dari server.</div>');
        }
    });
}


/**
 * Menampilkan modal untuk detail ADT.
 */
function lihatDetailAdt(bulan) {
    const modalTitle = $('#detailAdtModalLabel');
    const modalBody = $('#modalBodyContentAdt');
    modalTitle.text('Detail Transaksi ADT Bulan: ' + bulan);
    modalBody.html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    $('#detailAdtModal').modal('show');
    $.ajax({
        url: 'detailsales/api_get_detail_adt.php',
        type: 'GET',
        dataType: 'json',
        data: {
            bulan: bulan
        },
        success: function(response) {
            if (response.status === 'success' && response.data.length > 0) {
                let tableHtml = `<div class="table-responsive"><table class="table table-sm table-bordered table-hover"><thead class="thead-light"><tr><th>Tgl PB</th><th>No PB</th><th>Kode</th><th>Nama Member</th><th>Status</th><th class="text-right">Sales</th></tr></thead><tbody>`;
                response.data.forEach(item => {
                    tableHtml += `<tr><td>${item.tglpb}</td><td>${item.nopb}</td><td>${item.kode}</td><td>${item.nama}</td><td><span class="badge badge-success">${item.status}</span></td><td class="text-right">${formatRupiah(item.sales)}</td></tr>`;
                });
                tableHtml += `</tbody></table></div>`;
                modalBody.html(tableHtml);
            } else {
                modalBody.html('<div class="alert alert-warning">Tidak ada detail transaksi untuk bulan ini.</div>');
            }
        },
        error: function() {
            modalBody.html('<div class="alert alert-danger">Gagal mengambil data dari server.</div>');
        }
    });
}

// ======================================================================
// === SCRIPT UTAMA SETELAH HALAMAN SIAP (DOCUMENT READY) ===
// ======================================================================
$(document).ready(function() {

    // --- DEKLARASI VARIABEL ---
    let allData = [],
        currentPage = 1,
        rowsPerPage = 10;
    
    let allPluData = [],
        currentPluPage = 1,
        rowsPerPluPage = 10;
    
    // --- [BARU] VARIABEL UNTUK PB LIST ADT ---
    let allPbAdtData = [],
        currentPbAdtPage = 1,
        rowsPerPbAdtPage = 10;

    // ==========================================================
    // --- 1. CONFIG CHART ADT (Ambil Di Toko) ---
    // ==========================================================
    const adtChartOptions = {
        series: [],
        chart: { type: 'bar', height: 380, toolbar: { show: false } },
        theme: { mode: 'dark' },
        plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        colors: ['#008FFB', '#00E396', '#FEB019'],
        xaxis: { categories: [] },
        yaxis: [
            { title: { text: 'Jumlah' } }, 
            { 
                opposite: true, 
                title: { text: 'Rupiah (Rp)' },
                labels: {
                    formatter: function(val) {
                        if (val >= 1000000) return (val / 1000000).toFixed(1) + ' Jt';
                        return val.toLocaleString('id-ID');
                    }
                }
            }
        ],
        tooltip: {
            y: {
                formatter: function(val, { seriesIndex }) {
                    if (seriesIndex === 0 || seriesIndex === 1) {
                        return val.toLocaleString('id-ID');
                    }
                    return formatRupiah(val);
                }
            }
        },
        legend: { position: 'top', horizontalAlign: 'left' }
    };
    const adtChart = new ApexCharts(document.querySelector("#adtChartContainer"), adtChartOptions);
    adtChart.render();

    // ==========================================================
    // --- 2. CONFIG CHART PICKER (DARK ELEGANT & INTERAKTIF) ---
    // ==========================================================
    
    function showPickerDetails(pickerName) {
    const tglMulai = $('#filterTanggalMulai').val();
    const tglSelesai = $('#filterTanggalSelesai').val();
    
    $('#modalPickerName').text(pickerName);
    $('#pickerDetailModal').modal('show');
    $('#loadingPickerDetail').show();
    $('#listPickerContent').empty(); // Kosongkan container div

    $.ajax({
        url: 'detailsales/api_get_pb_by_picker.php',
        type: 'GET',
        dataType: 'json',
        data: {
            picker: pickerName,
            tanggal_mulai: tglMulai,
            tanggal_selesai: tglSelesai
        },
        success: function(response) {
            $('#loadingPickerDetail').hide();
            
            if(response.status === 'success' && response.data.length > 0) {
                let html = '';
                response.data.forEach(item => {
                    // Logic Warna Badge
                    let badgeClass = 'badge-secondary';
                    let statusUpper = (item.status || '').toUpperCase();
                    if (statusUpper.includes('SIAP')) badgeClass = 'badge-warning';
                    if (statusUpper.includes('SELESAI')) badgeClass = 'badge-success';
                    if (statusUpper.includes('BATAL')) badgeClass = 'badge-danger';
                    if (statusUpper.includes('PACKING') || statusUpper.includes('STRUK')) badgeClass = 'badge-info';

                    // Format PB (Pecah string panjang)
                    let rawPb = item.obi_nopb || '-';
                    let splitPb = rawPb.split('/');
                    let mainPb = splitPb[0]; 
                    let subPb = splitPb.length > 1 ? '/' + splitPb.slice(1).join('/') : ''; 

                    // --- RENDER HTML RESPONSIF (BOOTSTRAP GRID) ---
                    html += `
                    <div class="transaction-item p-3 mb-3">
                        <div class="row align-items-center">
                            
                            <div class="col-12 col-md-4 mb-2 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3 d-md-none"> <i class="fas fa-store text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="text-label-small">Member</div>
                                        <div class="member-name-lg">${item.cus_namamember || 'NON MEMBER'}</div>
                                        <small class="text-muted">${item.obi_kdmember || '-'}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-8 col-md-4">
                                <div class="text-label-small">No. Transaksi</div>
                                <div class="pb-main-text">${mainPb}</div>
                                <div class="text-muted small text-truncate" style="max-width: 150px;">${subPb}</div>
                            </div>

                            <div class="col-4 col-md-4 text-right">
                                <span class="badge ${badgeClass} mb-2 d-block d-md-inline-block">${item.status}</span>
                                <button class="btn btn-sm btn-outline-info rounded-circle" onclick="lihatDetail('${item.obi_nopb}')" style="width: 32px; height: 32px; padding: 0; line-height: 30px;">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                    `;
                });
                $('#listPickerContent').html(html);
            } else {
                $('#listPickerContent').html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-ghost fa-3x mb-3 opacity-50"></i>
                        <p>Tidak ada data ditemukan.</p>
                    </div>
                `);
            }
        },
        error: function() {
            $('#loadingPickerDetail').hide();
            $('#listPickerContent').html('<div class="alert alert-danger">Gagal memuat data.</div>');
        }
    });
}

// ==========================================================
    // --- 3. CONFIG CHART ITEM/LINES (UPDATE) ---
    // ==========================================================
    const itemChartOptions = {
        series: [{
            name: 'Total Lines',
            data: []
        }],
        chart: {
            type: 'bar',
            height: 350,
            fontFamily: 'Segoe UI, sans-serif',
            background: 'transparent',
            toolbar: { show: false },
            animations: { enabled: true }
        },
        theme: { mode: 'dark' },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '45%',
                distributed: true, 
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ["#fff"],
                fontWeight: 600
            },
            background: { enabled: false }
        },
        xaxis: {
            categories: [],
            labels: {
                style: { fontSize: '12px', colors: '#ced4da' },
                rotate: -45
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#ced4da' },
                formatter: function (val) { return val.toFixed(0); } // Angka bulat (karena item gak mungkin desimal)
            }
        },
        grid: {
            borderColor: '#6c757d',
            strokeDashArray: 4,
            opacity: 0.2
        },
        colors: ['#FF9F43', '#EE5A24', '#B33771', '#6D214F', '#1B1464'], // Warna tema oranye/hangat
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function (val) { return val + " Item/Line"; } // Label Tooltip
            }
        },
        legend: { show: false }
    };

    // Render Chart Item
    const itemChart = new ApexCharts(document.querySelector("#pickerItemChartContainer"), itemChartOptions);
    itemChart.render();

    const pickerChartOptions = {
        series: [{
            name: 'Jumlah PB',
            data: []
        }],
        chart: {
            type: 'bar',
            height: 350,
            fontFamily: 'Segoe UI, Roboto, Helvetica, Arial, sans-serif',
            background: 'transparent',
            toolbar: { show: false },
            events: {
                // --- EVENT KLIK ---
                dataPointSelection: function(event, chartContext, config) {
                    // Ambil nama picker dari index data yang diklik
                    const dataIndex = config.dataPointIndex;
                    const pickerName = config.w.globals.labels[dataIndex];
                    showPickerDetails(pickerName);
                }
            }
        },
        theme: {
            mode: 'dark'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '40%', // Tidak terlalu gemuk
                distributed: true,  // Agar warna bisa variasi (opsional) atau matikan jadi false biar 1 warna
                dataLabels: {
                    position: 'top', // Angka di atas
                }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '14px',
                colors: ["#fff"], // Putih bersih
                fontFamily: 'Segoe UI',
                fontWeight: 600
            },
            background: {
                enabled: false // Hilangkan kotak background biar gak lebay
            }
        },
        xaxis: {
            categories: [],
            labels: {
                style: {
                    fontSize: '12px',
                    fontWeight: 500,
                    colors: '#ced4da' // Abu terang
                },
                rotate: -45
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#ced4da' },
                formatter: function (val) { return val.toFixed(0); }
            }
        },
        grid: {
            borderColor: '#6c757d',
            strokeDashArray: 4,
            opacity: 0.2 // Grid samar
        },
        // Warna Chart: Gradient Biru ke Hijau (Modern & Professional)
        // Atau gunakan satu warna solid jika ingin sangat simpel
        colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'], 
        fill: {
            opacity: 1
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function (val) { return val + " PB"; }
            }
        },
        legend: { show: false } // Sembunyikan legend
    };

    // Render Chart
    const pickerChart = new ApexCharts(document.querySelector("#pickerChartContainer"), pickerChartOptions);
    pickerChart.render();

    // --- Event Listener Filter Status ---
    $('.status-filter-card').on('click', function() {
        $('.status-filter-card .card').removeClass('active-card');
        $(this).find('.card').addClass('active-card');
        const statusText = $(this).find('.text-muted').text().trim();
        const statusFilter = statusText.toUpperCase();
        $('#searchInputPb').val(statusFilter);
        currentPage = 1;
        displayData();
        $('html, body').animate({
            scrollTop: $("#tabel-pb").offset().top - 200
        }, 500);
    });

    $('#reset-filter-card').on('click', function() {
        $('.status-filter-card .card').removeClass('active-card');
        $(this).find('.card').addClass('active-card');
        $('#searchInputPb').val('');
        currentPage = 1;
        displayData();
        $('html, body').animate({
            scrollTop: $("#tabel-pb").offset().top - 200
        }, 300);
    });

    // --- FUNGSI-FUNGSI UPDATE UI ---
    
    // --- FUNGSI FETCH DATA (UPDATE VARIABLE) ---
    function fetchPickerItemData(tglMulai, tglSelesai) { 
        $.ajax({
            url: 'detailsales/api_perbandingan_picker_item.php', 
            type: 'GET',
            dataType: 'json',
            data: { 
                tanggal_mulai: tglMulai,
                tanggal_selesai: tglSelesai
            },
            success: function(response) {
                if (response.status === 'success' && response.data.length > 0) {
                    
                    const pickerNames = response.data.map(item => item.obi_picker);
                    // Ambil 'total_item' dari API (bukan total_qty lagi)
                    const pickerItems = response.data.map(item => parseInt(item.total_item)); 

                    // Update Chart
                    itemChart.updateOptions({
                        xaxis: { categories: pickerNames }
                    });
                    itemChart.updateSeries([{
                        name: 'Total Item',
                        data: pickerItems
                    }]);

                } else {
                    itemChart.updateSeries([{ data: [] }]);
                    itemChart.updateOptions({ xaxis: { categories: [] } });
                }
            },
            error: function() {
                console.error("Gagal memuat data item picker.");
            }
        });
    }

    function updateTotalCards(tglMulai, tglSelesai) {
        const poCardTitle = $('#po-card-title');
        const today = new Date().toISOString().split('T')[0];

        if (tglMulai === tglSelesai) {
            if (tglMulai === today) {
                poCardTitle.text('PO Hari ini');
            } else {
                const formattedDate = tglMulai.split('-').reverse().join('/');
                poCardTitle.text('PO Tgl: ' + formattedDate);
            }
        } else {
            poCardTitle.text('Total PO');
        }

        $.getJSON('detailsales/api_get_sales.php', {
            tanggal_mulai: tglMulai,
            tanggal_selesai: tglSelesai
        }, function(response) {
            $('#sales').text(response.sales || 'Rp 0');
        }).fail(() => $('#sales').text('Error'));

        $.getJSON('detailsales/api_get_stats.php', {
            tanggal_mulai: tglMulai,
            tanggal_selesai: tglSelesai
        }, function(response) {
            if (response.stats) {
                $('#countpo').text(response.stats.total_po || '0');
                $('#totalmember').text(response.stats.totalmember || '0');
                $('#totalmemberdouble').text(response.stats.doublemember || '0');
            }
            if (response.status_counts) {
                const statusMap = {
                    'SIAP SEND HANDHELD': '#totalsendhh',
                    'SIAP PICKING': '#totalpicking',
                    'SIAP PACKING': '#totalpacking',
                    'SIAP DRAFT STRUK': '#totaldsp',
                    'KONFIRMASI PEMBAYARAN': '#totalkonfirmasipembayaran',
                    'SIAP STRUK': '#totalstruk',
                    'SELESAI': '#totalselesai',
                    'BATAL': '#totalbatal'
                };
                Object.values(statusMap).forEach(id => $(id).text('0'));
                response.status_counts.forEach(item => {
                    if (statusMap[item.status]) $(statusMap[item.status]).text(item.kemunculan);
                });
            }
        }).fail(() => console.error("Gagal mengambil data statistik gabungan."));
    }

    function fetchData(tglMulai, tglSelesai) {
        $.ajax({
            url: 'detailsales/api_table_pb_hari_ini.php',
            type: 'GET',
            dataType: 'json',
            data: {
                tanggal_mulai: tglMulai,
                tanggal_selesai: tglSelesai
            },
            success: function(response) {
                allData = (response.status === 'success' && response.data) ? response.data : [];
                displayData();
            },
            error: function() {
                $('#tabel-pb tbody').html('<tr><td colspan="7" class="text-center">Gagal memuat data.</td></tr>');
            }
        });
    }

   // --- FUNGSI BARU: Fetch Picker Data (Support Range Date) ---
    function fetchPickerData(tglMulai, tglSelesai) {
        $.ajax({
            url: 'detailsales/api_perbandingan_picker.php',
            type: 'GET',
            dataType: 'json',
            // --- PERUBAHAN PARAMETER DI SINI ---
            data: { 
                tanggal_mulai: tglMulai,
                tanggal_selesai: tglSelesai
            },
            success: function(response) {
                if (response.status === 'success' && response.data.length > 0) {
                    
                    //Filter data null
                    const cleanData = response.data.filter(item => 
                        item.obi_picker !== null && 
                        item.obi_picker !== "" && 
                        item.obi_picker !== "null"
                    );
                    // const cleanData = response.data;
                    const pickerNames = cleanData.map(item => item.obi_picker);
                    const pickerCounts = cleanData.map(item => parseInt(item.jumlah_pb));

                    // Update Chart
                    pickerChart.updateOptions({
                        xaxis: { categories: pickerNames }
                    });
                    pickerChart.updateSeries([{
                        name: 'Jumlah PB',
                        data: pickerCounts
                    }]);

                } else {
                    pickerChart.updateSeries([{ data: [] }]);
                    pickerChart.updateOptions({ xaxis: { categories: [] } });
                }
            },
            error: function() {
                console.error("Gagal memuat data picker.");
            }
        });
    }

    // ======================================================
    // --- [BARU] LOGIKA FETCH & DISPLAY UNTUK PB LIST ADT ---
    // ======================================================

    function fetchPbAdtData(tglMulai, tglSelesai) {
        $.ajax({
            url: 'detailsales/api_get_pb_adt_list.php', // Pastikan API ini sudah dibuat
            type: 'GET',
            dataType: 'json',
            data: {
                tanggal_mulai: tglMulai,
                tanggal_selesai: tglSelesai
            },
            success: function(response) {
                allPbAdtData = (response.status === 'success' && response.data) ? response.data : [];
                displayPbAdtData();
            },
            error: function() {
                $('#tabel-pb-adt tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data ADT.</td></tr>');
            }
        });
    }

    function displayPbAdtData() {
        const query = $('#searchInputPbAdt').val().toLowerCase().trim();
        const tableBody = $('#tabel-pb-adt tbody');
        tableBody.empty();

        const filteredData = allPbAdtData.filter(item => {
            const namaMember = String(item.cus_namamember || '').toLowerCase();
            const noPb = String(item.obi_nopb || '').toLowerCase();
            const kdMember = String(item.obi_kdmember || '').toLowerCase();
            const noTrans = String(item.obi_notrans || '').toLowerCase();
            const status = String(item.status || '').toLowerCase();

            return namaMember.includes(query) ||
                noPb.includes(query) ||
                kdMember.includes(query) ||
                noTrans.includes(query) ||
                status.includes(query);
        });

        if (filteredData.length === 0) {
            tableBody.html('<tr><td colspan="7" class="text-center">Data ADT tidak ditemukan.</td></tr>');
            renderPaginationPbAdt(0);
            return;
        }

        const totalPages = Math.ceil(filteredData.length / rowsPerPbAdtPage);
        if (currentPbAdtPage > totalPages) {
            currentPbAdtPage = 1;
        }

        const paginatedData = filteredData.slice((currentPbAdtPage - 1) * rowsPerPbAdtPage, currentPbAdtPage * rowsPerPbAdtPage);

        paginatedData.forEach((item, index) => {
            const rowNumber = (currentPbAdtPage - 1) * rowsPerPbAdtPage + index + 1;
            const status = item.status || 'N/A';
            const row = `<tr>
                <td>${rowNumber}</td>
                <td><span class="badge badge-info">${status}</span></td>
                <td>${item.obi_kdmember || '-'}</td>
                <td>${item.obi_nopb || '-'}</td>
                <td>${item.obi_notrans || '-'}</td>
                <td>${item.cus_namamember || '-'}</td>
                <td><button class="btn btn-sm btn-outline-info" onclick="lihatDetail('${item.obi_nopb}')"><i class="fas fa-eye"></i> Detail</button></td>
            </tr>`;
            tableBody.append(row);
        });

        renderPaginationPbAdt(totalPages);
    }

    function renderPaginationPbAdt(totalPages) {
        const paginationWrapper = $('#paginationWrapperPbAdt');
        paginationWrapper.empty();
        if (!totalPages || totalPages <= 1) return;

        // Tombol Previous
        paginationWrapper.append(`<li class="page-item ${currentPbAdtPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-action="prev">Previous</a></li>`);
        
        // Angka Halaman (Batasi agar tidak terlalu panjang)
        for (let i = 1; i <= totalPages; i++) {
             if (i === 1 || i === totalPages || (i >= currentPbAdtPage - 1 && i <= currentPbAdtPage + 1)) {
                paginationWrapper.append(`<li class="page-item ${i === currentPbAdtPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
             } else if (i === currentPbAdtPage - 2 || i === currentPbAdtPage + 2) {
                 paginationWrapper.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
             }
        }

        // Tombol Next
        paginationWrapper.append(`<li class="page-item ${currentPbAdtPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-action="next">Next</a></li>`);
    }

    // ======================================================

    function displayData() {
        const query = $('#searchInputPb').val().toLowerCase().trim();
        const tableBody = $('#tabel-pb tbody');
        tableBody.empty();

        const filteredData = allData.filter(item => {
            const namaMember = String(item.cus_namamember || '').toLowerCase();
            const noPb = String(item.obi_nopb || '').toLowerCase();
            const kdMember = String(item.obi_kdmember || '').toLowerCase();
            const noTrans = String(item.obi_notrans || '').toLowerCase();
            const status = String(item.status || '').toLowerCase(); 

            return namaMember.includes(query) ||
                noPb.includes(query) ||
                kdMember.includes(query) ||
                noTrans.includes(query) ||
                status.includes(query);
        });

        if (filteredData.length === 0) {
            tableBody.html('<tr><td colspan="7" class="text-center">Data tidak ditemukan.</td></tr>');
            renderPagination(0); 
            return;
        }

        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        if (currentPage > totalPages) {
            currentPage = 1; 
        }

        const paginatedData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

        paginatedData.forEach((item, index) => {
            const rowNumber = (currentPage - 1) * rowsPerPage + index + 1;
            const status = item.status || 'N/A';
            const kdMember = item.obi_kdmember || 'N/A';
            const noPb = item.obi_nopb || 'N/A';
            const noTrans = item.obi_notrans || 'N/A';
            const namaMember = item.cus_namamember || 'N/A';

            const row = `<tr>
                <td>${rowNumber}</td>
                <td>${status}</td>
                <td>${kdMember}</td>
                <td>${noPb}</td>
                <td>${noTrans}</td>
                <td>${namaMember}</td>
                <td><button class="btn btn-sm btn-info" onclick="lihatDetail('${noPb}')">Detail</button></td>
            </tr>`;
            tableBody.append(row);
        });

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const paginationWrapper = $('#paginationWrapperPb');
        paginationWrapper.empty();
        if (!totalPages || totalPages <= 1) return;
        paginationWrapper.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`);
        for (let i = 1; i <= totalPages; i++) {
            paginationWrapper.append(`<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        }
        paginationWrapper.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`);
    }

    function fetchPluData(tglMulai, tglSelesai) {
        $.ajax({
            url: 'detailsales/api_get_pb_plu.php',
            type: 'GET',
            dataType: 'json',
            data: {
                tanggal_mulai: tglMulai,
                tanggal_selesai: tglSelesai
            },
            success: function(response) {
                allPluData = (response.status === 'success' && response.data) ? response.data : [];
                displayPluData();
            },
            error: function() {
                $('#tabel-pb_plu tbody').html('<tr><td colspan="5" class="text-center">Gagal memuat data PLU.</td></tr>');
            }
        });
    }

    function displayPluData() {
        const query = $('#searchPluInput').val().toLowerCase();
        const filteredPluData = allPluData.filter(item => (item.nama_produk && item.nama_produk.toLowerCase().includes(query)) || (item.plu && item.plu.toLowerCase().includes(query)));
        const tableBody = $('#tabel-pb_plu tbody');
        tableBody.empty();
        if (filteredPluData.length === 0) {
            tableBody.html('<tr><td colspan="5" class="text-center">Data PLU tidak ditemukan.</td></tr>');
            renderPluPagination(0);
            return;
        }
        const totalPages = Math.ceil(filteredPluData.length / rowsPerPluPage);
        if (currentPluPage > totalPages) currentPluPage = 1;
        const paginatedData = filteredPluData.slice((currentPluPage - 1) * rowsPerPluPage, currentPluPage * rowsPerPluPage);
        paginatedData.forEach(item => {
            const escapedNamaProduk = item.nama_produk ? item.nama_produk.replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';
            const row = `<tr><td>${item.nama_produk || 'N/A'}</td><td>${item.plu}</td><td>${item.real || 0}</td><td>${item.order || 0}</td><td><button class="btn btn-sm btn-info" onclick="lihatDetailPlu('${item.plu}', '${escapedNamaProduk}')"><i class="fas fa-eye"></i> Detail</button></td></tr>`;
            tableBody.append(row);
        });
        renderPluPagination(totalPages);
    }
    
    function renderPluPagination(totalPages) {
        const paginationWrapper = $('#paginationPluWrapper');
        paginationWrapper.empty();
        if (!totalPages || totalPages <= 1) return;
        paginationWrapper.append(`<li class="page-item ${currentPluPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPluPage - 1}">Previous</a></li>`);
        for (let i = 1; i <= totalPages; i++) {
            paginationWrapper.append(`<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        }
        paginationWrapper.append(`<li class="page-item ${currentPluPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPluPage + 1}">Next</a></li>`);
    }

    function fetchAdtData() {
        const tableBody = $('#tabel-adt tbody');
        tableBody.html('<tr><td colspan="6" class="text-center">Memuat data...</td></tr>');
        $.getJSON('detailsales/api_get_adt.php', function(response) {
            tableBody.empty();
            if (response.data && response.data.length > 0) {
                response.data.forEach(item => {
                    const row = `<tr>
                            <td>${item.tglpb}</td>
                            <td class="text-right">${Number(item.jumlah_member).toLocaleString('id-ID')}</td>
                            <td class="text-right">${Number(item.std).toLocaleString('id-ID')}</td>
                            <td class="text-right">${formatRupiah(item.sales)}</td>
                            <td class="text-right">${formatRupiah(item.margin)}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info" onclick="lihatDetailAdt('${item.tglpb}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>`;
                    tableBody.append(row);
                });

                const reversedData = response.data.slice().reverse();
                const labels = reversedData.map(item => item.tglpb);

                adtChart.updateOptions({
                    series: [{
                        name: 'Jumlah Member',
                        data: reversedData.map(item => item.jumlah_member)
                    },
                    {
                        name: 'Total Sales',
                        data: reversedData.map(item => item.sales)
                    },
                    {
                        name: 'Total Margin',
                        data: reversedData.map(item => item.margin)
                    }
                    ],
                    xaxis: { categories: labels }
                });
            } else {
                tableBody.html('<tr><td colspan="6" class="text-center">Data tidak ditemukan.</td></tr>');
                adtChart.updateOptions({ series: [], xaxis: { categories: [] } });
            }
        }).fail(function() {
            console.error("Gagal mengambil data ADT.");
            tableBody.html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
        });
    }

    // --- INISIALISASI & EVENT LISTENERS ---
    
    function runAllUpdates(tglMulai, tglSelesai) {
        updateTotalCards(tglMulai, tglSelesai);
        fetchData(tglMulai, tglSelesai);
        fetchPluData(tglMulai, tglSelesai);
        fetchAdtData(); 
        fetchPickerData(tglMulai, tglSelesai); 
        fetchPickerItemData(tglMulai, tglSelesai);
        
        // --- [BARU] LOAD DATA PB LIST ADT ---
        fetchPbAdtData(tglMulai, tglSelesai);
    }

    // --- Inisialisasi Halaman ---
    const today = new Date().toISOString().split('T')[0];
    $('#filterTanggalMulai').val(today);    
    $('#filterTanggalSelesai').val(today);  
    runAllUpdates(today, today);            

    // --- Interval Update ---
    setInterval(() => {
        let tglMulai = $('#filterTanggalMulai').val();
        let tglSelesai = $('#filterTanggalSelesai').val();
        const today = new Date().toISOString().split('T')[0];

        if (!tglMulai && !tglSelesai) {
            tglMulai = today;
            tglSelesai = today;
        } else if (!tglMulai) {
            tglMulai = tglSelesai; 
        } else if (!tglSelesai) {
            tglSelesai = tglMulai; 
        }

        runAllUpdates(tglMulai, tglSelesai);
    }, 30000);

    // --- Tombol Terapkan Filter ---
    $('#applyFilterBtn').on('click', function() {
        let tglMulai = $('#filterTanggalMulai').val();
        let tglSelesai = $('#filterTanggalSelesai').val();
        const today = new Date().toISOString().split('T')[0];

        if (!tglMulai && !tglSelesai) {
            tglMulai = today;
            tglSelesai = today;
            $('#filterTanggalMulai').val(tglMulai);
            $('#filterTanggalSelesai').val(tglSelesai);
        } else if (!tglMulai) {
            tglMulai = tglSelesai; 
            $('#filterTanggalMulai').val(tglMulai);
        } else if (!tglSelesai) {
            tglSelesai = tglMulai; 
            $('#filterTanggalSelesai').val(tglSelesai);
        }
        
        runAllUpdates(tglMulai, tglSelesai);
    });

    $('#searchInputPb').on('keyup', function() {
        currentPage = 1;
        displayData();
    });
    $('#searchPluInput').on('keyup', function() {
        currentPluPage = 1;
        displayPluData();
    });

    $('#paginationWrapperPb').on('click', 'a.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && !$(this).parent().hasClass('disabled')) {
            currentPage = page;
            displayData();
        }
    });

    $('#paginationPluWrapper').on('click', 'a.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && !$(this).parent().hasClass('disabled')) {
            currentPluPage = page;
            displayPluData();
        }
    });

    // --- [BARU] EVENT LISTENER UNTUK PB ADT ---
    
    $('#searchInputPbAdt').on('keyup', function() { 
        currentPbAdtPage = 1;
        displayPbAdtData();
    });

    $('#paginationWrapperPbAdt').on('click', 'a.page-link', function(e) {
        e.preventDefault();
        const action = $(this).data('action');
        const page = $(this).data('page');

        if (action === 'prev') {
            if (currentPbAdtPage > 1) {
                currentPbAdtPage--;
                displayPbAdtData();
            }
        } else if (action === 'next') {
             const query = $('#searchInputPbAdt').val().toLowerCase().trim();
             const filteredLen = allPbAdtData.filter(item => 
                 String(item.cus_namamember || '').toLowerCase().includes(query) || 
                 String(item.obi_nopb || '').toLowerCase().includes(query)
            ).length;
            const totalPages = Math.ceil(filteredLen / rowsPerPbAdtPage);

            if (currentPbAdtPage < totalPages) {
                currentPbAdtPage++;
                displayPbAdtData();
            }
        } else if (page) {
            currentPbAdtPage = parseInt(page);
            displayPbAdtData();
        }
    });

});