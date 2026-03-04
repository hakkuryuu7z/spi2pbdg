// ===================================================================
// ## CONFIGURATION & GLOBAL UTILS
// ===================================================================
const CONFIG = {
    ITEMS_PER_PAGE_MAIN: 15, // Default awal
    ITEMS_PER_PAGE_SLEEPER: 10,
    REFRESH_INTERVAL: 60000 // 60 detik
};

// Helper: Format Rupiah
function formatRupiah(angka) {
    if (angka === null || isNaN(angka)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
    }).format(Number(angka));
}

// ===================================================================
// ## MAIN APP LOGIC
// ===================================================================
$(document).ready(function() {

    // --- 1. EVENT LISTENER: ROWS PER PAGE (NEW) ---
    // Ini logika untuk mengubah jumlah baris tabel utama secara dinamis
    $('#rowsPerPage').on('change', function() {
        const val = parseInt($(this).val());
        CONFIG.ITEMS_PER_PAGE_MAIN = val;
        mainCurrentPage = 1; // Reset ke halaman 1 agar tidak error offset
        renderMainMemberTable();
    });


    // --- 2. DASHBOARD STAT CARDS ---
    function updateStatCards() {
        $.ajax({
            url: 'detailmember/api_get_member.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#member-count').text(res.total_member || 0);
                $('#member-aktif').text(res.member_aktif || 0);
                $('#member-nontransaksi').text(res.member_non_transaksi || 0);
                $('#member-coverage').text(res.total_kelurahan_aktif || 0);
            },
            error: function(xhr, status, error) {
                console.error("Stat Card Error:", error);
                $('.stat-card .h4').text('Gagal');
            }
        });
    }

    // --- 3. TABEL MEMBER UTAMA (Client-side Pagination) ---
    let mainMembersData = [];
    let mainCurrentPage = 1;

    async function fetchAllMembers() {
        const tableBody = $('#tabel-member tbody');
        // Loading State Modern
        tableBody.html(`
            <tr>
                <td colspan="11" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="text-muted small">Mengambil data member...</div>
                </td>
            </tr>
        `);
        
        try {
            const res = await fetch('detailmember/api_get_member_all.php');
            const json = await res.json();

            if (json.status === 'success' && Array.isArray(json.data)) {
                mainMembersData = json.data;
                mainCurrentPage = 1;
                
                // Update text total data (Opsional jika ada elemen id="totalDataInfo")
                $('#totalDataInfo').text(`Total: ${mainMembersData.length} Data`);
                
                renderMainMemberTable();
            } else {
                throw new Error(json.message || "Data tidak valid");
            }
        } catch (err) {
            console.error("Main Table Error:", err);
            tableBody.html('<tr><td colspan="11" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat data member.</td></tr>');
        }
    }

    function renderMainMemberTable() {
        const searchTerm = $('#searchMember').val().toLowerCase();
        const filterStatus = $('#filterMemberStatus').val();
        
        // Filtering Logic
        const filtered = mainMembersData.filter(m => {
            const matchSearch = (m.cus_namamember?.toLowerCase().includes(searchTerm)) || 
                                (m.cus_kodemember?.toLowerCase().includes(searchTerm));
            
            const statusApi = (m.status?.toLowerCase() === 'aktif') ? 'aktif' : 'non_transaksi';
            const matchStatus = (filterStatus === 'all' || statusApi === filterStatus);
            
            return matchSearch && matchStatus;
        });

        // Pagination Logic Updated
        const limit = CONFIG.ITEMS_PER_PAGE_MAIN;
        const totalPages = Math.ceil(filtered.length / limit);
        
        // Prevent overflow page index
        if (mainCurrentPage > totalPages) mainCurrentPage = 1;
        if (totalPages === 0) mainCurrentPage = 1;

        const start = (mainCurrentPage - 1) * limit;
        const pageData = filtered.slice(start, start + limit);

        // Render Rows
        const tbody = $('#tabel-member tbody');
        tbody.empty();

        if (pageData.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <i class="fas fa-search mb-2" style="font-size: 2rem; opacity: 0.3;"></i><br>
                        Data tidak ditemukan.
                    </td>
                </tr>
            `);
        } else {
            pageData.forEach((m, i) => {
                const isAktif = m.status?.toLowerCase() === 'aktif';
                // Menggunakan class CSS modern yang kita buat sebelumnya
                const badgeClass = isAktif ? 'badge-soft-success' : 'badge-soft-secondary';
                const icon = isAktif ? 'fa-check' : 'fa-times';

                const row = `
                    <tr>
                        <td class="text-center text-muted">${start + i + 1}</td>
                        <td><span class="font-weight-bold text-white">${m.cus_kodemember || '-'}</span></td>
                        <td>${m.cus_namamember || '-'}</td>
                        <td>${m.cus_tlpmember || '-'}</td>
                        <td>${m.crm_email || '-'}</td>
                        <td><span class="badge badge-soft-info">${m.cus_nosalesman || '-'}</span></td>
                        <td>
                            <span class="badge ${badgeClass}">
                                <i class="fas ${icon} mr-1"></i>${m.status}
                            </span>
                        </td>
                        <td class="text-muted small">${m.cus_tglregistrasi || '-'}</td>
                        <td class="text-muted small">${m.cus_tglmulai || '-'}</td>
                        <td><span class="font-weight-bold text-success">${formatRupiah(m.sales)}</span></td>
                        <td class="text-center">
                            <button class="btn-icon-modern" title="Lihat Detail">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </td>
                    </tr>`;
                tbody.append(row);
            });
        }
        
        renderPagination('#paginationWrappermm', totalPages, mainCurrentPage, (page) => {
            mainCurrentPage = page;
            renderMainMemberTable();
        });
    }

    // --- 4. TABEL KECAMATAN (Server-side Pagination) ---
    async function fetchKecamatanData(page = 1) {
        const tbody = $('#tabel-pb tbody');
        tbody.html('<tr><td colspan="3" class="text-center py-3 text-muted">Loading...</td></tr>');
        
        try {
            const res = await fetch(`detailmember/api_get_kecamatan_data.php?page=${page}`);
            const json = await res.json();
            
            tbody.empty();
            if(!json.data || json.data.length === 0) {
                tbody.html('<tr><td colspan="3" class="text-center py-3 text-muted">Tidak ada data.</td></tr>');
                return;
            }

            json.data.forEach(item => {
                tbody.append(`
                    <tr>
                        <td class="pl-4">${item.kecamatan}</td>
                        <td><span class="font-weight-bold text-white">${item.jumlah_member}</span></td>
                        <td class="text-center pr-4">
                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="showDetail('${item.kecamatan}')">Detail</button>
                        </td>
                    </tr>
                `);
            });

            renderPagination('#paginationWrapperPb', json.totalPages, json.currentPage, (newPage) => fetchKecamatanData(newPage));

        } catch (err) {
            console.error("Kecamatan Error:", err);
            tbody.html('<tr><td colspan="3" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    }

    // --- 5. MODAL UTILS (Kecamatan & Jarak) ---
    async function openModalDetail(title, apiUrl, modalId, tableBodyId, subTitleId) {
        const $modal = $(`#${modalId}`);
        const $tbody = $(`#${tableBodyId}`);
        const $subTitle = $(`#${subTitleId}`);

        // Set UI Loading
        $(`#${modalId} .modal-title`).html(title);
        $subTitle.text('Sedang memuat data...');
        $tbody.html('<tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>');
        $modal.modal('show');

        try {
            const res = await fetch(apiUrl);
            const json = await res.json();
            $tbody.empty();

            if (json.data && json.data.length > 0) {
                $subTitle.text(`Ditemukan ${json.data.length} member.`);
                json.data.forEach((m, i) => {
                    const isAktif = m.status?.toLowerCase() === 'aktif';
                    const badge = isAktif 
                        ? `<span class="badge badge-soft-success"><i class="fa fa-check mr-1"></i>${m.status}</span>` 
                        : `<span class="badge badge-soft-secondary"><i class="fa fa-times mr-1"></i>${m.status}</span>`;
                    
                    const extraCol = m.kecamatan ? `<td>${m.kecamatan}</td>` : ''; 

                    $tbody.append(`
                        <tr>
                            <td>${i + 1}</td>
                            <td><span class="text-white">${m.cus_kodemember}</span></td>
                            <td>${m.cus_namamember}</td>
                            ${extraCol}
                            <td>${m.cus_nosalesman || '-'}</td>
                            <td>${badge}</td>
                            <td>${m.cus_tglmulai || '-'}</td>
                        </tr>
                    `);
                });
            } else {
                $subTitle.text('Tidak ada data.');
                $tbody.html('<tr><td colspan="10" class="text-center py-4 text-muted">Data kosong.</td></tr>');
            }
        } catch (err) {
            console.error("Modal Fetch Error:", err);
            $subTitle.text('Terjadi kesalahan.');
            $tbody.html('<tr><td colspan="10" class="text-center py-4 text-danger">Gagal memuat data detail.</td></tr>');
        }
    }

    window.showDetail = (kecamatan) => {
        openModalDetail(
            `<i class="fa fa-map-marker-alt mr-2"></i> Detail Member: ${kecamatan}`,
            `detailmember/api_detail_member_kecamatan.php?kecamatan=${encodeURIComponent(kecamatan)}`,
            'detailKecamatanModal', 'modalTableBody', 'modalSubTitle'
        );
    };

    window.showJarakDetail = (kategori) => {
        openModalDetail(
            `<i class="fa fa-road mr-2"></i> Detail Member: ${kategori}`,
            `detailmember/api_detail_perjarak.php?kategori=${encodeURIComponent(kategori)}`,
            'detailKecamatanjarakModal', 'modalTableBodyjarak', 'modalSubTitlejarak'
        );
    };

    // --- 6. TABEL JARAK & MR (Simple Fetch) ---
    async function fetchSimpleTable(apiUrl, tableId, renderRowFn) {
        const tbody = $(`#${tableId} tbody`);
        tbody.html('<tr><td colspan="5" class="text-center py-3 text-muted">Loading...</td></tr>');
        try {
            const res = await fetch(apiUrl);
            const json = await res.json();
            tbody.empty();
            if (json.data && json.data.length > 0) {
                json.data.forEach(renderRowFn);
            } else {
                tbody.html('<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data.</td></tr>');
            }
        } catch (err) {
            tbody.html('<tr><td colspan="5" class="text-center text-danger">Error memuat data.</td></tr>');
        }
    }

    // Init Tabel Jarak
    function fetchJarakData() {
        fetchSimpleTable('detailmember/api_get_jarak.php', 'tabel-jarak', (item) => {
            $('#tabel-jarak tbody').append(`
                <tr>
                    <td class="pl-4">${item.kategori_jarak}</td>
                    <td><span class="text-white font-weight-bold">${item.jumlah_member}</span></td>
                    <td class="text-center pr-4">
                        <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="showJarakDetail('${item.kategori_jarak}')">Detail</button>
                    </td>
                </tr>`);
        });
    }

    // Init Tabel MR
    function fetchMRData() {
        fetchSimpleTable('detailmember/api_get_member_mr.php', 'tabel-MR', (item) => {
            $('#tabel-MR tbody').append(`
                <tr>
                    <td class="pl-4"><span class="text-white font-weight-bold">${item.total_get}</span></td>
                    <td><span class="badge badge-soft-info">${item.cus_nosalesman}</span></td>
                    <td class="text-center pr-4">
                        <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="showMRDetail('${item.cus_nosalesman}')">Detail</button>
                    </td>
                </tr>`);
        });
    }

    // --- 7. MODAL MR & EXCEL DOWNLOAD ---
    window.showMRDetail = function(mr) {
        const modal = $('#detailMrModal');
        const contentDiv = $('#modalBodyContentMr');
        
        $('#modalTitleMr').html(`<i class="fa fa-user-tie mr-2"></i> Detail Member: ${mr}`);
        $('#downloadMrExcelBtn').attr('href', `detailmember/api_download_member_mr.php?mr=${encodeURIComponent(mr)}`);
        
        $('#mrSearchInput').val('');
        $('#mrFilterCheckbox').prop('checked', false);
        
        contentDiv.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        modal.modal('show');

        fetch(`detailmember/api_detail_member_mr.php?mr=${encodeURIComponent(mr)}`)
            .then(res => res.json())
            .then(json => {
                modal.data('rawData', json.data || []);
                renderMRList(json.data || []);
            })
            .catch(err => {
                contentDiv.html('<div class="text-center text-danger py-4">Gagal memuat data detail.</div>');
            });
    };

    function renderMRList(data) {
        const container = $('#modalBodyContentMr');
        container.empty();
        
        if (data.length === 0) {
            container.html('<div class="text-center py-4 text-muted">Tidak ada data member.</div>');
            return;
        }

        const list = $('<div class="list-group list-group-flush pr-2" style="max-height: 400px; overflow-y: auto;"></div>');
        
        data.forEach(m => {
            const isAktif = m.status?.toLowerCase() === 'aktif';
            const badgeCls = isAktif ? 'badge-soft-success' : 'badge-soft-secondary';
            
            // List item modern style
            list.append(`
                <div class="list-group-item list-group-item-action flex-column align-items-start border mb-2 rounded shadow-sm" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05) !important;">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1 font-weight-bold text-white">${m.cus_namamember} <small class="ml-2 badge ${badgeCls}">${m.status}</small></h6>
                        <small class="text-muted"><i class="fas fa-phone mr-1"></i> ${m.cus_tlpmember || '-'}</small>
                    </div>
                    <p class="mb-1 text-muted small"><i class="fas fa-map-marker-alt mr-1"></i> ${m.alamat || '-'}</p>
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                        <small>Sales: <span class="text-success font-weight-bold">${formatRupiah(m.sales)}</span></small>
                        <small class="text-muted">Last: ${m.tgl_terakhir_belanja || '-'}</small>
                    </div>
                </div>
            `);
        });
        
        container.append(list);
    }

    // MR Filter Logic
    function filterMRList() {
        const rawData = $('#detailMrModal').data('rawData') || [];
        const term = $('#mrSearchInput').val().toLowerCase();
        const onlyNoTrans = $('#mrFilterCheckbox').is(':checked');

        const filtered = rawData.filter(m => {
            const matchTerm = (m.cus_namamember?.toLowerCase().includes(term)) || 
                              (m.cus_kodemember?.toLowerCase().includes(term));
            const matchStatus = onlyNoTrans ? (m.status?.toLowerCase() === 'no transaksi') : true;
            return matchTerm && matchStatus;
        });
        renderMRList(filtered);
    }
    
    $('#mrSearchInput').on('keyup', filterMRList);
    $('#mrFilterCheckbox').on('change', filterMRList);


    // ===================================================================
    // ## BAGIAN 8: MEMBER SLEEPER (MODERN STYLE)
    // ===================================================================
    let sleeperRawData = [];
    let sleeperCurrentPage = 1;

    window.loadSleeper = async function(interval) {
        // Sinkronisasi nilai input agar sesuai dengan parameter
        $('#inputSleeperInterval').val(interval);
        
        const date = new Date();
        date.setMonth(date.getMonth() - interval);
        const monthStr = date.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
        
        // Update subtitle agar lebih informatif
        $('#sleeperSubtitle').html(`Tidak transaksi sejak <strong>${monthStr}</strong> <span class="text-warning">(${interval} Bulan)</span>`);

        const tbody = $('#tabel-sleeper tbody');
        tbody.html('<tr><td colspan="8" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-warning mb-2"></div><br>Menganalisis data...</td></tr>');

        try {
            const res = await fetch(`detailmember/api_get_member_sleeper.php?interval=${interval}`);
            const json = await res.json();

            if (json.status === 'success') {
                sleeperRawData = json.data || [];
                sleeperCurrentPage = 1;
                
                $('#searchSleeper').val('');
                populateSalesmanFilter(sleeperRawData, '#filterSalesmanSleeper');
                renderSleeperTable();
            } else {
                tbody.html(`<tr><td colspan="8" class="text-center text-danger py-4">${json.message}</td></tr>`);
            }
        } catch (err) {
            console.error("Sleeper Error:", err);
            tbody.html('<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat data sleeper.</td></tr>');
        }
    };

    // --- EVENT LISTENER UNTUK INPUT DINAMIS ---
    
    // Jika tombol pencarian (kaca pembesar) diklik
    $('#btnApplySleeper').on('click', function() {
        let val = parseInt($('#inputSleeperInterval').val());
        // Validasi agar minimal selalu 1 bulan
        if (isNaN(val) || val < 1) {
            val = 1;
            $('#inputSleeperInterval').val(1);
        }
        loadSleeper(val);
    });

    // Jika user menekan tombol "Enter" di dalam kotak angka
    $('#inputSleeperInterval').on('keypress', function(e) {
        if (e.which === 13) { 
            $('#btnApplySleeper').click();
        }
    });

    function renderSleeperTable() {
        const searchVal = $('#searchSleeper').val().toLowerCase();
        const filterSales = $('#filterSalesmanSleeper').val();

        const filtered = sleeperRawData.filter(m => {
            const matchSearch = (m.nama_member?.toLowerCase().includes(searchVal)) || 
                                (m.kode?.toLowerCase().includes(searchVal));
            const matchSales = (filterSales === 'all' || m.salesman === filterSales);
            return matchSearch && matchSales;
        });

        $('#sleeperCountBadge').text(`${filtered.length} Member Ditemukan`);

        const totalPages = Math.ceil(filtered.length / CONFIG.ITEMS_PER_PAGE_SLEEPER);
        if (sleeperCurrentPage > totalPages) sleeperCurrentPage = 1;
        const start = (sleeperCurrentPage - 1) * CONFIG.ITEMS_PER_PAGE_SLEEPER;
        const pageData = filtered.slice(start, start + CONFIG.ITEMS_PER_PAGE_SLEEPER);

        const tbody = $('#tabel-sleeper tbody');
        tbody.empty();

        if (pageData.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center py-5 text-muted">Tidak ada data yang cocok.</td></tr>');
        } else {
            pageData.forEach((m, i) => {
                const salesBadgeClass = (m.salesman === 'IMN') ? 'badge-soft-info' : 'badge-soft-warning';
                
                tbody.append(`
                    <tr>
                        <td class="text-center text-muted">${start + i + 1}</td>
                        <td><span class="badge badge-soft-light text-white font-weight-normal border border-secondary">${m.kode}</span></td>
                        <td><div class="font-weight-bold text-white">${m.nama_member}</div></td>
                        <td><div class="text-truncate text-muted small" style="max-width: 200px;" title="${m.alamat}">${m.alamat || '-'}</div></td>
                        <td>${m.no_aktif || '-'}</td>
                        <td><span class="badge ${salesBadgeClass}">${m.salesman || '-'}</span></td>
                        <td class="text-danger font-weight-bold">${m.tgl_kunjungan_terakhir}</td>
                        <td class="text-center">
                            <button class="btn-icon-modern" title="Lihat Detail"><i class="fas fa-arrow-right"></i></button>
                        </td>
                    </tr>
                `);
            });
        }

        renderPagination('#paginationSleeper', totalPages, sleeperCurrentPage, (page) => {
            sleeperCurrentPage = page;
            renderSleeperTable();
        });
    }
    window.exportSleeperToExcel = function() {
        // 1. Cek apakah data tersedia
        if (!sleeperRawData || sleeperRawData.length === 0) {
            alert("Tidak ada data untuk diexport!");
            return;
        }

        // 2. Ambil nilai filter saat ini agar hasil export sesuai tampilan
        const searchVal = $('#searchSleeper').val().toLowerCase();
        const filterSales = $('#filterSalesmanSleeper').val();

        // 3. Filter data sesuai logika yang sama dengan renderSleeperTable
        const dataToExport = sleeperRawData.filter(m => {
            const matchSearch = (m.nama_member?.toLowerCase().includes(searchVal)) || 
                                (m.kode?.toLowerCase().includes(searchVal));
            const matchSales = (filterSales === 'all' || m.salesman === filterSales);
            return matchSearch && matchSales;
        });

        if (dataToExport.length === 0) {
            alert("Hasil filter kosong, tidak ada yang bisa diexport.");
            return;
        }

        // 4. Mapping Data (Mempercantik Header Excel)
        // Kita ubah key object agar header di Excel lebih rapi (bukan nama variabel database)
        const cleanData = dataToExport.map((item, index) => ({
            "No": index + 1,
            "Kode Member": item.kode,
            "Nama Member": item.nama_member,
            "Alamat": item.alamat,
            "No HP": item.no_aktif,
            "Salesman": item.salesman,
            "Terakhir Belanja": item.tgl_kunjungan_terakhir
        }));

        // 5. Proses Pembuatan File Excel (SheetJS)
        const worksheet = XLSX.utils.json_to_sheet(cleanData);
        
        // Auto-width kolom (Opsional: agar kolom tidak sempit)
        const wscols = [
            {wch: 5},  // No
            {wch: 15}, // Kode
            {wch: 30}, // Nama
            {wch: 40}, // Alamat
            {wch: 15}, // HP
            {wch: 10}, // Sales
            {wch: 20}  // Tgl
        ];
        worksheet['!cols'] = wscols;

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Data Sleeper");

        // Nama file dinamis dengan tanggal
        const dateStr = new Date().toISOString().slice(0,10);
        XLSX.writeFile(workbook, `Member_Sleeper_Export_${dateStr}.xlsx`);
    };

    function populateSalesmanFilter(data, selector) {
        const $sel = $(selector);
        const uniqueSales = [...new Set(data.map(i => i.salesman).filter(Boolean))].sort();
        $sel.html('<option value="all">Semua Salesman</option>');
        uniqueSales.forEach(s => $sel.append(`<option value="${s}">${s}</option>`));
    }

    // --- HELPER PAGINATION (UNIVERSAL) ---
    function renderPagination(containerId, totalPages, currentPage, onPageChange) {
        const $nav = $(containerId);
        $nav.empty();

        if (totalPages <= 1) {
            if(containerId === '#paginationSleeper') $('#sleeperPageInfo').text(`1 / 1`);
            return;
        }

        if(containerId === '#paginationSleeper') $('#sleeperPageInfo').text(`${currentPage} / ${totalPages}`);

        const createItem = (text, page, isActive = false, isDisabled = false) => {
            const activeCls = isActive ? 'active' : '';
            const disabledCls = isDisabled ? 'disabled' : '';
            // Gunakan javascript:void(0) agar tidak scroll jump ke atas
            const $li = $(`<li class="page-item ${activeCls} ${disabledCls}"><a class="page-link" href="javascript:void(0)">${text}</a></li>`);
            
            if (!isDisabled && !isActive) {
                $li.find('a').on('click', (e) => {
                    e.preventDefault();
                    onPageChange(page);
                });
            }
            return $li;
        };

        // Pagination Logic Smart (1 ... 4 5 6 ... 10)
        $nav.append(createItem('«', currentPage - 1, false, currentPage === 1));

        const maxVisible = 2;
        let startPage = Math.max(1, currentPage - maxVisible);
        let endPage = Math.min(totalPages, currentPage + maxVisible);

        if (startPage > 1) {
            $nav.append(createItem('1', 1));
            if (startPage > 2) $nav.append(createItem('...', 0, false, true));
        }

        for (let i = startPage; i <= endPage; i++) {
            $nav.append(createItem(i, i, i === currentPage));
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) $nav.append(createItem('...', 0, false, true));
            $nav.append(createItem(totalPages, totalPages));
        }

        $nav.append(createItem('»', currentPage + 1, false, currentPage === totalPages));
    }

    // --- EVENT LISTENERS TAMBAHAN ---
    $('#searchSleeper').on('keyup', () => { sleeperCurrentPage = 1; renderSleeperTable(); });
    $('#filterSalesmanSleeper').on('change', () => { sleeperCurrentPage = 1; renderSleeperTable(); });
    
    $('#searchMember').on('keyup', () => { mainCurrentPage = 1; renderMainMemberTable(); });
    $('#filterMemberStatus').on('change', () => { mainCurrentPage = 1; renderMainMemberTable(); });


    // --- INITIALIZE ALL ---
    updateStatCards();
    setInterval(updateStatCards, CONFIG.REFRESH_INTERVAL);
    
    fetchAllMembers();
    fetchKecamatanData(1);
    fetchJarakData();
    fetchMRData();
    loadSleeper(2);

});