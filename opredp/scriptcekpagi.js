document.addEventListener('DOMContentLoaded', function() {

    // ===============================================================
    // FUNGSI BANTUAN (HELPERS)
    // ===============================================================

    /**
     * Mengontrol status loading pada tombol (spinner, ikon, teks).
     * @param {HTMLElement} button - Elemen tombol yang akan diubah.
     * @param {boolean} isLoading - True jika sedang loading, false jika selesai.
     */
    function setButtonLoading(button, isLoading) {
        if (!button) return;
        const spinner = button.querySelector('.spinner-border');
        const icon = button.querySelector('i');
        const buttonText = button.querySelector('.button-text');

        button.disabled = isLoading;
        if (spinner) spinner.classList.toggle('d-none', !isLoading);
        if (icon) icon.classList.toggle('d-none', isLoading);
        if (buttonText) buttonText.classList.toggle('d-none', isLoading);
    }

    /**
     * Menampilkan teks dengan efek seolah-olah sedang diketik.
     * @param {HTMLElement} element - Elemen (seperti <td>) untuk menampilkan teks.
     * @param {string} text - Teks yang akan ditampilkan.
     * @param {number} [speed=20] - Kecepatan mengetik dalam milidetik.
     */
    function typeWriterEffect(element, text, speed = 20) {
        let i = 0;
        element.innerHTML = ""; // Kosongkan dulu
        
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }
        type();
    }
    
    /**
     * Mengaktifkan atau menonaktifkan efek glitch pada header card.
     * @param {string} tableId - ID dari tabel di dalam card.
     * @param {boolean} addGlitch - True untuk menambahkan glitch, false untuk menghapus.
     */
    function toggleHeaderGlitch(tableId, addGlitch) {
        const tableElement = document.getElementById(tableId);
        if (!tableElement) return;
        
        const cardHeader = tableElement.closest('.card')?.querySelector('.card-header');
        if (cardHeader) {
            cardHeader.classList.toggle('glitch-active', addGlitch);
        }
    }


    // ===============================================================
    // BAGIAN 1: KODE UNTUK TABEL STATUS IAS
    // ===============================================================
    const tableias = document.getElementById('status-ias');
    const refreshstatusias = document.getElementById('refresh_status_ias');
    const iasLastUpdated = document.getElementById('ias-last-updated');

    if (tableias && refreshstatusias) {
        async function fetchstatusias() {
            setButtonLoading(refreshstatusias, true);
            toggleHeaderGlitch('status-ias', true);
            tableias.innerHTML = '<tr><td colspan="4" class="text-center">ACCESSING IAS DATABASE...</td></tr>';
            try {
                const response = await fetch('opredp/api_cek_status_ias.php');
                if (!response.ok) throw new Error(`CONNECTION FAILED: ${response.statusText}`);
                const result = await response.json();
                if (result.status === 'success') {
                    displayData(result.data);
                    if(iasLastUpdated) iasLastUpdated.textContent = `UPDATED: ${new Date().toLocaleTimeString('id-ID')}`;
                } else {
                    throw new Error(result.message || "API DATASTREAM CORRUPTED.");
                }
            } catch (error) {
                console.error("IAS DATA FETCH FAILED: ", error);
                tableias.innerHTML = `<tr><td colspan="4" class="text-center text-danger">⚠️ DATASTREAM INTERRUPTED: ${error.message}</td></tr>`;
            } finally {
                setButtonLoading(refreshstatusias, false);
                toggleHeaderGlitch('status-ias', false);
            }
        }

        function displayData(data) {
            tableias.innerHTML = '';
            if (!data || data.length === 0) {
                tableias.innerHTML = '<tr><td colspan="4" class="text-center">NO RECENT ACTIVITY LOGS FOUND.</td></tr>';
                return;
            }
            data.forEach((item, index) => {
                let statusBadge;
                const statusUpper = (item.status || '').toUpperCase();
                if (statusUpper === 'DONE' || statusUpper === 'SELESAI') {
                    statusBadge = `<span class="badge bg-success">${item.status}</span>`;
                } else if (statusUpper === 'ON PROGRESS') {
                    statusBadge = `<span class="badge bg-warning text-dark">${item.status}</span>`;
                } else {
                    statusBadge = `<span class="badge bg-secondary">${item.status}</span>`;
                }
                
                const row = tableias.insertRow();
                row.innerHTML = `
                    <th scope="row">${index + 1}</th>
                    <td data-text-content="${item.submenu || 'N/A'}"></td>
                    <td data-text-content="${item.end_time || 'N/A'}"></td>
                    <td>${statusBadge}</td>
                `;

                typeWriterEffect(row.cells[1], row.cells[1].getAttribute('data-text-content'));
                typeWriterEffect(row.cells[2], row.cells[2].getAttribute('data-text-content'));
            });
        }
        refreshstatusias.addEventListener('click', (e) => {
            e.preventDefault();
            fetchstatusias();
        });
        fetchstatusias();
    }

    // ===============================================================
    // BAGIAN 2: KODE UNTUK TABEL CEK SETTING PAGI
    // ===============================================================
    const tableSettingPagi = document.getElementById('status-setting-pagi');
    const refreshSettingPagi = document.getElementById('setting_pagi');
    const pagiLastUpdated = document.getElementById('pagi-last-updated');

    if (tableSettingPagi && refreshSettingPagi) {
        async function fetchSettingPagi() {
            setButtonLoading(refreshSettingPagi, true);
            toggleHeaderGlitch('status-setting-pagi', true);
            tableSettingPagi.innerHTML = '<tr><td colspan="3" class="text-center">INITIATING MORNING PROTOCOL CHECK...</td></tr>';
            try {
                const response = await fetch('opredp/api_cek_setting_pagi.php');
                if (!response.ok) throw new Error(`CONNECTION FAILED: ${response.statusText}`);
                const result = await response.json();
                if (result.status === 'success') {
                    displaySettingPagiData(result.data);
                    if(pagiLastUpdated) pagiLastUpdated.textContent = `UPDATED: ${new Date().toLocaleTimeString('id-ID')}`;
                } else {
                    throw new Error(result.message || "API DATASTREAM CORRUPTED.");
                }
            } catch (error) {
                console.error("SETTING PAGI DATA FETCH FAILED: ", error);
                tableSettingPagi.innerHTML = `<tr><td colspan="3" class="text-center text-danger">⚠️ DATASTREAM INTERRUPTED: ${error.message}</td></tr>`;
            } finally {
                setButtonLoading(refreshSettingPagi, false);
                toggleHeaderGlitch('status-setting-pagi', false);
            }
        }

        function displaySettingPagiData(data) {
            tableSettingPagi.innerHTML = '';
            if (!data || data.length === 0) {
                tableSettingPagi.innerHTML = '<tr><td colspan="3" class="text-center">NO PROTOCOL LOGS FOUND.</td></tr>';
                return;
            }
            const today = new Date().toLocaleDateString('en-CA');
            data.forEach((item, index) => {
                let statusBadge;
                const statusUpper = (item.status || '').toUpperCase();
                if (statusUpper === 'OK' || statusUpper === 'SUDAH') {
                    statusBadge = `<span class="badge bg-success">${item.status}</span>`;
                } else {
                    statusBadge = `<span class="badge bg-danger">${item.status}</span>`;
                }
                
                const row = tableSettingPagi.insertRow();
                const highlightClass = (item.tanggal || '').startsWith(today) ? 'highlight-today' : '';
                row.className = highlightClass;

                row.innerHTML = `
                    <th scope="row">${index + 1}</th>
                    <td data-text-content="${item.tanggal || 'N/A'}"></td>
                    <td>${statusBadge}</td>
                `;

                typeWriterEffect(row.cells[1], row.cells[1].getAttribute('data-text-content'));
            });
        }
        refreshSettingPagi.addEventListener('click', (e) => {
            e.preventDefault();
            fetchSettingPagi();
        });
    }

    // ===============================================================
    // BAGIAN 3: KODE UNTUK TABEL DTA4 JOB LOG 
    // ===============================================================
    const tableDta = document.getElementById('status-dta');
    const refreshDta = document.getElementById('refresh_dta');
    const dtaLastUpdated = document.getElementById('dta-last-updated');

    if (tableDta && refreshDta) {
        async function fetchDtaData() {
            setButtonLoading(refreshDta, true);
            toggleHeaderGlitch('status-dta', true);
            tableDta.innerHTML = '<tr><td colspan="3" class="text-center">QUERYING DTA4 LOGS...</td></tr>';
            try {
                const response = await fetch('opredp/api_cek_dta.php');
                if (!response.ok) throw new Error(`CONNECTION FAILED: ${response.statusText}`);
                const result = await response.json();
                if (result.status === 'success') {
                    displayDtaData(result.data);
                    if (dtaLastUpdated) dtaLastUpdated.textContent = `UPDATED: ${new Date().toLocaleTimeString('id-ID')}`;
                } else {
                    throw new Error(result.message || "API DATASTREAM CORRUPTED.");
                }
            } catch (error) {
                console.error("DTA4 DATA FETCH FAILED: ", error);
                tableDta.innerHTML = `<tr><td colspan="3" class="text-center text-danger">⚠️ DATASTREAM INTERRUPTED: ${error.message}</td></tr>`;
            } finally {
                setButtonLoading(refreshDta, false);
                toggleHeaderGlitch('status-dta', false);
            }
        }

        function displayDtaData(data) {
            tableDta.innerHTML = '';
            if (!data || data.length === 0) {
                tableDta.innerHTML = '<tr><td colspan="3" class="text-center">NO DTA4 LOGS FOUND FOR YESTERDAY.</td></tr>';
                return;
            }
            data.forEach((item) => {
                let statusBadge;
                const statusMessage = (item.job_message || '').toUpperCase(); 
                
                if (statusMessage === 'OK') {
                    statusBadge = `<span class="badge bg-success">${item.job_message}</span>`;
                } else {
                    statusBadge = `<span class="badge bg-danger">${item.job_message || 'UNKNOWN'}</span>`;
                }

                const row = tableDta.insertRow();
                const jobName = item.job_name || 'N/A';
                const executionTime = item.job_end || 'N/A';

                row.innerHTML = `
                    <td data-text-content="${jobName}"></td>
                    <td data-text-content="${executionTime}"></td>
                    <td>${statusBadge}</td>
                `;

                typeWriterEffect(row.cells[0], row.cells[0].getAttribute('data-text-content'));
                typeWriterEffect(row.cells[1], row.cells[1].getAttribute('data-text-content'));
            });
        }

        refreshDta.addEventListener('click', (e) => {
            e.preventDefault();
            fetchDtaData();
        });

        fetchDtaData();
    }
    
    // ===============================================================
    // BAGIAN 4: KODE UNTUK TABEL TF FILE (BARU DITAMBAHKAN)
    // ===============================================================
    const tableTfFile = document.getElementById('TF_FILE');
    const refreshTfFile = document.getElementById('tf_exec');
    const tffileLastUpdated = document.getElementById('tf-file-last-updated');

    if (tableTfFile && refreshTfFile) {
        async function fetchTfFileData() {
            setButtonLoading(refreshTfFile, true);
            toggleHeaderGlitch('TF_FILE', true);
            tableTfFile.innerHTML = `<tr><td colspan="9" class="text-center">ACCESSING TRANSFER FILE LOGS...</td></tr>`;
            try {
                const response = await fetch('opredp/api_tf_file.php');
                if (!response.ok) throw new Error(`CONNECTION FAILED: ${response.statusText}`);
                const result = await response.json();
                if (result.status === 'success') {
                    displayTfFileData(result.data);
                    // Tambahkan update timestamp di sini jika Anda sudah memperbaiki ID di HTML
                } else {
                    throw new Error(result.message || "API DATASTREAM CORRUPTED.");
                }
            } catch (error) {
                console.error("TF FILE DATA FETCH FAILED: ", error);
                tableTfFile.innerHTML = `<tr><td colspan="9" class="text-center text-danger">⚠️ DATASTREAM INTERRUPTED: ${error.message}</td></tr>`;
            } finally {
                setButtonLoading(refreshTfFile, false);
                toggleHeaderGlitch('TF_FILE', false);
            }
        }

        function displayTfFileData(data) {
            tableTfFile.innerHTML = '';
            if (!data || data.length === 0) {
                tableTfFile.innerHTML = `<tr><td colspan="9" class="text-center">NO TRANSFER FILE LOGS FOUND FOR YESTERDAY.</td></tr>`;
                return;
            }
            data.forEach(item => {
                const row = tableTfFile.insertRow();
                row.innerHTML = `
                    <td data-text-content="${item.trf_namaprog ?? 'N/A'}"></td>
                    <td data-text-content="${item.trf_namafile ?? 'N/A'}"></td>
                    <td data-text-content="${item.trf_namadbf ?? 'N/A'}"></td>
                    <td>${item.trf_flag ?? '-'}</td>
                    <td>${item.trf_jammulai ?? 'N/A'}</td>
                    <td>${item.trf_create_by ?? 'N/A'}</td>
                    <td>${item.trf_create_dt ?? 'N/A'}</td>
                    <td>${item.trf_modify_by ?? 'N/A'}</td>
                    <td>${item.trf_modify_dt ?? 'N/A'}</td>
                `;

                // Terapkan typewriter effect pada beberapa kolom agar tidak terlalu ramai
                typeWriterEffect(row.cells[0], row.cells[0].getAttribute('data-text-content'));
                typeWriterEffect(row.cells[1], row.cells[1].getAttribute('data-text-content'));
                typeWriterEffect(row.cells[2], row.cells[2].getAttribute('data-text-content'));
            });
        }

        refreshTfFile.addEventListener('click', (e) => {
            e.preventDefault();
            fetchTfFileData();
        });

        // Panggil saat halaman pertama kali dimuat
        // fetchTfFileData();
    }
    // ===============================================================
    // BAGIAN 5: KODE UNTUK EXPORT DATA (BARKOS & PRODUK SERVEI)
    // ===============================================================
    
    /**
     * Mengkonversi JSON array ke format XLSX dan men-trigger download
     */
    function downloadExcel(jsonData, filename) {
        if (!jsonData || !jsonData.length) {
            alert("Tidak ada data untuk di-export!");
            return;
        }
        
        // 1. Buat Worksheet dari JSON Data
        const worksheet = XLSX.utils.json_to_sheet(jsonData);
        
        // 2. Buat Workbook baru
        const workbook = XLSX.utils.book_new();
        
        // 3. Masukkan Worksheet ke dalam Workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Data Export");
        
        // 4. Trigger download file Excel (.xlsx)
        XLSX.writeFile(workbook, filename);
    }

    // Export BARKOS
    const btnExportBarkos = document.getElementById('btn_export_barkos');
    if (btnExportBarkos) {
        btnExportBarkos.addEventListener('click', async (e) => {
            e.preventDefault();
            setButtonLoading(btnExportBarkos, true);
            try {
                const response = await fetch('opredp/api_export_barkos.php');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const tgl = new Date().toISOString().slice(0,10);
                    // Panggil downloadExcel dengan ekstensi .xlsx
                    downloadExcel(result.data, `Data_BARKOS_${tgl}.xlsx`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert(`Gagal export Barkos: ${error.message}`);
            } finally {
                setButtonLoading(btnExportBarkos, false);
            }
        });
    }

    // Export PRODUK SERVEI
    const btnExportProduk = document.getElementById('btn_export_produk');
    if (btnExportProduk) {
        btnExportProduk.addEventListener('click', async (e) => {
            e.preventDefault();
            setButtonLoading(btnExportProduk, true);
            try {
                const response = await fetch('opredp/api_export_produk.php');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const tgl = new Date().toISOString().slice(0,10);
                    // Panggil downloadExcel dengan ekstensi .xlsx
                    downloadExcel(result.data, `Data_PRODUK_SERVEI_${tgl}.xlsx`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert(`Gagal export Produk Servei: ${error.message}`);
            } finally {
                setButtonLoading(btnExportProduk, false);
            }
        });
    }
});