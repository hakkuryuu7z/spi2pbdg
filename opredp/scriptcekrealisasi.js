document.addEventListener('DOMContentLoaded', () => {

    // Referensi elemen baru
    const dataPanel = document.getElementById('dataPanel');
    const tableBody = document.getElementById('dataTableBody');
    const refreshBtn = document.getElementById('refreshBtn');
    const statusMessage = document.getElementById('statusMessage');
    const loadingSpinner = document.getElementById('loadingSpinner'); // Spinner

    // Ganti dengan path API Anda yang benar
    const API_URL = 'opredp/api_cekrealisasislp.php'; 

    async function fetchData() {
        // 1. Masuk ke mode loading
        tableBody.innerHTML = ''; // Kosongkan tabel
        tableBody.classList.remove('fade-in'); // Hapus animasi
        dataPanel.classList.add('loading'); // Tampilkan spinner
        statusMessage.textContent = 'Memuat data...'; // Teks untuk fallback
        refreshBtn.disabled = true;

        try {
            const response = await fetch(API_URL);

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const result = await response.json();

            if (result.status === 'success') {
                // 3. Sukses, panggil renderTable
                renderTable(result.data);
            } else {
                throw new Error(result.message || 'API mengembalikan status error');
            }

        } catch (error) {
            // 4. Tangani error
            console.error('Error fetching data:', error);
            statusMessage.textContent = `Gagal memuat data: ${error.message}`;
            statusMessage.className = 'error'; // Terapkan style error
            dataPanel.classList.remove('loading'); // Sembunyikan spinner
        } finally {
            // 5. Selalu aktifkan tombol kembali
            refreshBtn.disabled = false;
        }
    }

    function renderTable(data) {
        // 1. Selesai loading (sembunyikan spinner, tampilkan pesan)
        dataPanel.classList.remove('loading');

        // 2. Cek jika data kosong
        if (data.length === 0) {
            statusMessage.textContent = 'Semua data SLP sudah terealisasi.';
            statusMessage.className = 'info'; // Terapkan style info
            return;
        }

        // 3. Jika data ADA, tampilkan pesan sukses
        statusMessage.textContent = 'Data berhasil dimuat.';
        statusMessage.className = 'success'; // Terapkan style sukses

        // 4. Render data ke tabel
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.slp_id ?? ''}</td>
                <td>${row.alamat ?? ''}</td>
                <td>${row.plu ?? ''}</td>
                <td>${row.slp_deskripsi ?? ''}</td>
                <td>${row.qty_ctn ?? ''}</td>
                <td>${row.qty_pcs ?? ''}</td>
                <td>${row.slp_unit ?? ''}</td>
                <td>${row.ed ?? ''}</td>
                <td>${row.id_user ?? ''}</td>
            `;
            tableBody.appendChild(tr);
        });

        // 5. Terapkan animasi fade-in ke tabel
        tableBody.classList.add('fade-in');
    }

    // --- Event Listeners ---
    refreshBtn.addEventListener('click', fetchData);
    fetchData(); // Muat data saat halaman pertama kali dibuka

});