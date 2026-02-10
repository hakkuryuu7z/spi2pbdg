$(document).ready(function() {

    // ==========================================
    // 1. STATE & HELPER FUNCTIONS
    // ==========================================

    // Menyimpan status sorting saat ini
    let sortState = {
        column: null,
        direction: 'none' // 'none', 'asc', 'desc'
    };

    function formatRupiah(angka) {
        let num = parseFloat(angka);
        if (isNaN(num)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num);
    }

    function formatAngka(angka) {
        let num = parseFloat(angka);
        if (isNaN(num)) return 0;
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    function parseFloatAngka(angkaStr) {
        if (!angkaStr) return 0;
        // Hapus Rp, spasi, dan titik ribuan agar bisa dikalkulasi JS
        let cleanedStr = String(angkaStr)
            .replace('Rp', '')      
            .replace(/\s/g, '')     
            .replace(/\./g, '');    
        
        cleanedStr = cleanedStr.replace(',', '.');
        let num = parseFloat(cleanedStr);
        
        if (isNaN(num)) return 0; 
        return num;
    }

    function singkatNama(nama) {
        if (!nama || nama.trim() === '') return 'N/A';
        nama = nama.trim(); 
        if (nama.length > 25) {
            let escapedName = nama.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            return `<span title="${escapedName}">${nama.substring(0, 25)}...</span>`;
        }
        return nama;
    }

    // ==========================================
    // 2. LOAD DATA DARI API
    // ==========================================

    function loadParetoData() {
        const tbody = $('#pareto-tbody');
        tbody.html('<tr><td colspan="19" class="text-center">Memuat data...</td></tr>'); 

        $.getJSON('simul/api_get_monitor_pareto.php', function(response) {
            tbody.empty(); 

            if (response.status === 'success' && response.data.length > 0) {
                response.data.forEach((item, index) => {
                    
                    let frac = parseFloat(item.frac) || 0;
                    let avg_hari = parseFloat(item.hari) || 0;     
                    let st_po_qty = parseFloat(item.st_po_qty) || 0; 
                    let lpp_pcs = parseFloat(item.lpp_pcs) || 0;     
                    
                    // Hitung DSI Awal
                    let totalStokAwal = st_po_qty + lpp_pcs; 
                    let initial_dsi = 'N/A'; 
                    if (avg_hari > 0) {
                        initial_dsi = (totalStokAwal / avg_hari).toFixed(1);
                    }

                    let rowId = `row-pareto-${index}`;

                    // NOTE: data-original-index penting untuk fitur Reset Sort
                    let rowHtml = `
                        <tr id="${rowId}" 
                            data-frac="${frac}" 
                            data-hari="${avg_hari}" 
                            data-po-qty="${st_po_qty}" 
                            data-lpp-pcs="${lpp_pcs}"
                            data-original-index="${index}"
                            class="text-right"
                        >
                            <td class="text-center align-middle">${index + 1}</td>
                            <td class="text-left align-middle">${item.plu}</td>
                            <td class="text-left align-middle" style="white-space: nowrap;">${item.deskripsi}</td>
                            <td class="text-center align-middle">${item.unit || '-'}</td>
                            <td class="align-middle">${formatAngka(item.frac)}</td>
                            <td class="align-middle">${formatAngka(item.pkm)}</td>
                            <td class="align-middle">${formatAngka(item.avg_bln)}</td>
                            <td class="align-middle">${formatAngka(item.hari)}</td>
                            <td class="align-middle">${formatRupiah(item.st_avgcost)}</td>
                            
                            <td class="text-center align-middle" style="width: 110px;">
                                <input type="number" class="form-control pb-ctn" min="0">
                            </td>
                            
                            <td class="pb-pcs align-middle" style="width: 100px;">0</td>
                            
                            <td class="align-middle">${formatAngka(item.st_po_qty)}</td>
                            <td class="text-center align-middle">${item.st_no_po || '-'}</td>
                            <td class="align-middle">${formatAngka(item.st_saldo_in_ctn)}</td>
                            <td class="align-middle">${formatAngka(item.st_saldo_in_pcs)}</td>
                            <td class="align-middle">${formatAngka(item.lpp_pcs)}</td>
                            
                            <td class="text-center dsi-hari align-middle" style="width: 70px;">
                                ${initial_dsi}
                            </td> 
                            
                            <td class="text-left align-middle">${singkatNama(item.st_nama_supplier)}</td>
                        </tr>
                    `;
                    
                    tbody.append(rowHtml);
                });
            } else {
                tbody.html('<tr><td colspan="19" class="text-center">Data tidak ditemukan.</td></tr>');
            }
        }).fail(function() {
            tbody.html('<tr><td colspan="19" class="text-center text-danger">Gagal memuat data dari API.</td></tr>');
        });
    }

    // ==========================================
    // 3. FITUR SORTING (UPDATE UTAMA)
    // ==========================================
    // Event delegation ke 'body' agar aman meski tabel di-refresh
    $('body').on('click', '.sortable', function() {
        let $th = $(this);
        let columnIndex = $th.data('column'); // Index kolom (0-17)
        let sortType = $th.data('sort');      // 'number', 'string', atau 'input'
        let tbody = $('#pareto-tbody');
        let rows = tbody.find('tr').toArray();

        // A. RESET VISUAL HEADER LAIN
        // Hapus class 'active-sort' (warna biru) dari header lain
        $('.sortable').removeClass('active-sort'); 
        // Reset icon panah header lain
        $('.sortable i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        
        // B. TENTUKAN ARAH SORTING (Siklus: None -> Asc -> Desc -> None)
        if (sortState.column !== columnIndex) {
            // Pindah kolom baru -> Reset ke Ascending
            sortState.column = columnIndex;
            sortState.direction = 'asc';
        } else {
            // Kolom sama -> Putar arah
            if (sortState.direction === 'none') sortState.direction = 'asc';
            else if (sortState.direction === 'asc') sortState.direction = 'desc';
            else if (sortState.direction === 'desc') sortState.direction = 'none';
        }

        // C. UPDATE VISUAL HEADER YANG DIKLIK
        let icon = $th.find('i');
        icon.removeClass('fa-sort fa-sort-up fa-sort-down');
        
        if (sortState.direction === 'asc') {
            $th.addClass('active-sort'); // Jadi Biru
            icon.addClass('fa-sort-up');
        } else if (sortState.direction === 'desc') {
            $th.addClass('active-sort'); // Jadi Biru
            icon.addClass('fa-sort-down');
        } else {
            $th.removeClass('active-sort'); // Balik Hitam/Default
            icon.addClass('fa-sort');
        }

        // D. EKSEKUSI SORTING ARRAY
        if (sortState.direction === 'none') {
            // --- RESET KE URUTAN ASLI ---
            rows.sort(function(a, b) {
                let valA = parseInt($(a).attr('data-original-index'));
                let valB = parseInt($(b).attr('data-original-index'));
                return valA - valB;
            });
        } else {
            // --- SORTING AKTIF (ASC/DESC) ---
            rows.sort(function(a, b) {
                let tdA = $(a).find('td').eq(columnIndex);
                let tdB = $(b).find('td').eq(columnIndex);
                
                let valA, valB;

                // 1. Cek apakah ini kolom INPUT (seperti PB CTN)
                if (sortType === 'input') {
                    // Ambil value dari element <input>, bukan text()
                    valA = parseFloat(tdA.find('input').val()) || 0;
                    valB = parseFloat(tdB.find('input').val()) || 0;
                } 
                // 2. Cek apakah ini kolom ANGKA BIASA
                else if (sortType === 'number') {
                    valA = parseFloatAngka(tdA.text().trim());
                    valB = parseFloatAngka(tdB.text().trim());
                } 
                // 3. Sisanya adalah STRING (Teks)
                else {
                    let textA = tdA.text().trim();
                    let textB = tdB.text().trim();

                    // Khusus kolom Supplier (Index 17) yang mungkin pakai <span>
                    if (columnIndex === 17) {
                        let spanA = tdA.find('span');
                        let spanB = tdB.find('span');
                        if (spanA.length > 0) textA = spanA.attr('title').trim();
                        if (spanB.length > 0) textB = spanB.attr('title').trim();
                    }

                    valA = textA.toLowerCase();
                    valB = textB.toLowerCase();
                }

                // Bandingkan Nilai
                if (valA < valB) return sortState.direction === 'asc' ? -1 : 1;
                if (valA > valB) return sortState.direction === 'asc' ? 1 : -1;
                return 0;
            });
        }

        // E. RENDER ULANG TABEL
        $.each(rows, function(index, row) {
            tbody.append(row); 
        });
        
        // Trigger pencarian ulang (agar filter search tetap jalan setelah sort)
        $('#searchInput').trigger('keyup');
    });

    // ==========================================
    // 4. KALKULASI INPUT (PB CTN -> PB PCS & DSI)
    // ==========================================
    $('#pareto-tbody').on('input keyup', '.pb-ctn', function() {
        let $row = $(this).closest('tr');
        
        let frac = parseFloat($row.data('frac')) || 0;
        let avg_hari = parseFloat($row.data('hari')) || 0; 
        let st_po_qty = parseFloat($row.data('po-qty')) || 0; 
        let lpp_pcs = parseFloat($row.data('lpp-pcs')) || 0; 
        
        let pb_ctn_val = parseFloat($(this).val()) || 0; 
        
        let pb_pcs = pb_ctn_val * frac;
        $row.find('.pb-pcs').text(formatAngka(pb_pcs));
        
        let totalStok = pb_pcs + st_po_qty + lpp_pcs; 
        let dsi_hari_text = 'N/A';
        
        if (avg_hari > 0) {
            dsi_hari_text = (totalStok / avg_hari).toFixed(1);
        }
        
        $row.find('.dsi-hari').text(dsi_hari_text); 
    });

    // ==========================================
    // 5. EXPORT EXCEL
    // ==========================================
    $('#export-excel-btn').on('click', function() {
        let dataToExport = [];
        const flatHeader = [
            '#', 'PLU', 'Deskripsi Barang', 'Unit', 'Frac', 'Pkm',
            'AVG BLN', 'AVG HARI', 'Acost', 'PB CTN', 'PB PCS',
            'PO OUT', 'NO PO', 'STOK CTN', 'STOK PCS', 'LPP PCS',
            'DSI (Hari)', 'SUPP'
        ];
        dataToExport.push(flatHeader);

        // Export SEMUA data (yang terlihat)
        $('#pareto-tbody tr').each(function() {
            let $row = $(this);
            // Jangan export baris pesan error/loading
            if ($row.find('td').length < 2) return; 
            // Jika ingin export HANYA yang tampil di filter search, uncomment baris bawah:
            // if ($row.css('display') === 'none') return;

            let $cells = $row.find('td');
            let rowData = [];
            
            rowData.push($cells.eq(0).text()); // #
            rowData.push($cells.eq(1).text()); // PLU
            rowData.push($cells.eq(2).text()); // Deskripsi
            rowData.push($cells.eq(3).text()); // Unit
            rowData.push(parseFloatAngka($cells.eq(4).text())); // Frac
            rowData.push(parseFloatAngka($cells.eq(5).text())); // Pkm
            rowData.push(parseFloatAngka($cells.eq(6).text())); // Avg Bln
            rowData.push(parseFloatAngka($cells.eq(7).text())); // Avg Hari
            rowData.push(parseFloatAngka($cells.eq(8).text())); // Acost
            
            // Ambil nilai Input PB CTN
            rowData.push(parseFloat($row.find('.pb-ctn').val()) || 0); 
            
            rowData.push(parseFloatAngka($cells.eq(10).text())); // PB Pcs
            rowData.push(parseFloatAngka($cells.eq(11).text())); // PO Out
            rowData.push($cells.eq(12).text()); // No PO
            rowData.push(parseFloatAngka($cells.eq(13).text())); // Stok Ctn
            rowData.push(parseFloatAngka($cells.eq(14).text())); // Stok Pcs
            rowData.push(parseFloatAngka($cells.eq(15).text())); // LPP Pcs
            rowData.push($cells.eq(16).text().trim()); // DSI
            
            // Supplier Name (handle span title)
            let suppSpan = $cells.eq(17).find('span');
            let suppName = suppSpan.length > 0 ? suppSpan.attr('title') : $cells.eq(17).text();
            rowData.push(suppName); 

            dataToExport.push(rowData);
        });

        if (dataToExport.length <= 1) {
            alert("Tidak ada data untuk diekspor.");
            return;
        }

        try {
            const ws = XLSX.utils.aoa_to_sheet(dataToExport);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Pareto');
            XLSX.writeFile(wb, 'Laporan_Pareto.xlsx');
        } catch (err) {
            console.error("Gagal mengekspor Excel:", err);
            alert("Gagal mengekspor data ke Excel.");
        }
    });

    // Panggil fungsi load saat halaman siap
    loadParetoData();
    
    // ==========================================
    // 6. SEARCH FILTER
    // ==========================================
    $('#searchInput').on('keyup', function() {
        let searchTerm = $(this).val().toLowerCase();

        $('#pareto-tbody tr').each(function() {
            let $row = $(this);
            
            // Abaikan baris colspan (loading/error)
            if ($row.find('td[colspan]').length) {
                $row.show(); 
                return; 
            }

            let pluText = $row.find('td:eq(1)').text().toLowerCase();
            let deskripsiText = $row.find('td:eq(2)').text().toLowerCase();
            let suppText = $row.find('td:eq(17)').text().toLowerCase();

            if (pluText.includes(searchTerm) || 
                deskripsiText.includes(searchTerm) || 
                suppText.includes(searchTerm)) {
                
                $row.show(); 
            } else {
                $row.hide(); 
            }
        });
    });

});