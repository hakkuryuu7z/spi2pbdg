// Menjalankan script setelah semua elemen HTML selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Load data statis
    fetchCekCashback();
    fetchBaseprice();
    fetchStatusZKosong();
    fetchRecordId();
    fetchIntransitCount();
    fetchIntransitBeres();
    
    // 2. Load Margin Minus & Setup Event Click
    fetchMarginMinus();
    setupClickEvents(); 

    // 3. Jalankan Loop "Cerdas"
    runLiveUpdate();
});

function setupClickEvents() {
    const marginCard = document.getElementById('MARGIN_MINUS');
    if(marginCard) {
        const cardContainer = marginCard.closest('.stat-card');
        cardContainer.style.cursor = 'pointer';
        cardContainer.addEventListener('click', function() {
            openModalMarginMinus();
        });
    }
}

async function openModalMarginMinus() {
    // 1. Tampilkan Modal
    $('#modalMarginMinus').modal('show');
    
    // 2. Tampilkan Loading ala Hacker
    const tbody = document.querySelector('#tableMarginMinus tbody');
    tbody.innerHTML = '<tr><td colspan="16" class="text-center py-4" style="color: #a3e635;">> ACCESSING DATABASE...<br>[||||||||||] LOADING</td></tr>';

    try {
        const response = await fetch('opredp/api_marmin_detail.php');
        const result = await response.json();

        if(result.status === 'success') {
            const data = result.data;
            tbody.innerHTML = ''; 

            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="16" class="text-center py-4 text-muted">> NO ANOMALIES DETECTED (DATA EMPTY)</td></tr>';
                return;
            }

            // Loop data
            let html = '';
            data.forEach(row => {
                // Formatting
                const fmtNum = (val) => Number(val).toLocaleString('id-ID');
                const fmtDec = (val) => val ? parseFloat(val).toFixed(2) : '0.00';

                // Parsing Data
                let div = row.div;
                let plu = row.plu;
                let desk = row.deskripsi;
                let frac = row.frac;
                let unit = row.unit;
                let tag = row.tag;
                let stock = fmtNum(row.lpp);
                let lcost = fmtNum(row.lcost_pcs);
                let acost = fmtNum(row.acost_pcs);
                let acost_inc = fmtNum(row.a_cost_inc);
                let hrg = fmtNum(row.hrg);
                let hrg_md = row.hrg_p ? fmtNum(row.hrg_p) : '-';

                let mgn_a = fmtDec(row.margin);
                let mgn_l = fmtDec(row.margin_lcost);
                let mgn_a_md = fmtDec(row.margin_a_md);
                let mgn_l_md = fmtDec(row.margin_l_md);

                // Logic Warna Margin: Merah (Danger) jika < 0, Hijau (Success) jika aman
                // Kita pakai style hacker theme (text-danger sudah di override jadi merah soft, text-success jadi hijau neon)
                const colorMgn = (val) => parseFloat(val) < 0 ? 'text-danger font-weight-bold' : 'text-muted';

                html += `
                    <tr>
                        <td class="text-center text-info">${div}</td>
                        <td class="text-center text-info font-weight-bold">${plu}</td>
                        <td class="text-light">${desk}</td>
                        <td class="text-center text-muted">${frac}</td>
                        <td class="text-center text-muted">${unit}</td>
                        <td class="text-center text-warning">${tag}</td>
                        
                        <td class="text-right val-white">${stock}</td>
                        <td class="text-right text-muted">${lcost}</td>
                        <td class="text-right text-muted">${acost}</td>
                        <td class="text-right text-muted">${acost_inc}</td>
                        <td class="text-right val-white">${hrg}</td>
                        <td class="text-right val-orange">${hrg_md}</td>
                        
                        <td class="text-right ${colorMgn(mgn_a)}">${mgn_a}%</td>
                        <td class="text-right ${colorMgn(mgn_l)}">${mgn_l}%</td>
                        
                        <td class="text-right ${colorMgn(mgn_a_md)}">${mgn_a_md}%</td>
                        <td class="text-right ${colorMgn(mgn_l_md)}">${mgn_l_md}%</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;

        } else {
            tbody.innerHTML = `<tr><td colspan="16" class="text-center text-danger">>> ERROR: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="16" class="text-center text-danger">>> CONNECTION FAILED</td></tr>`;
    }
}

// --- FUNGSI UPDATE LAINNYA TETAP SAMA ---

async function runLiveUpdate() {
    try {
        await Promise.all([
            fetchLockingCount(),
            fetchDbConnection()
        ]);
    } catch (error) {
        console.error("Update error:", error);
    } finally {
        setTimeout(runLiveUpdate, 2000); 
    }
}

async function fetchIntransitBeres() {
    const el = document.getElementById('INTRANSIT_BERES'); if(!el) return;
    updateCard(el, 'opredp/api_count_intransit_selesai.php');
}
async function fetchIntransitCount() {
    const el = document.getElementById('INTRANSIT'); if(!el) return;
    updateCard(el, 'opredp/api_count_intransit.php');
}
async function fetchRecordId() {
    const el = document.getElementById('RECORDID_ID'); if(!el) return;
    updateCard(el, 'opredp/api_count_recordid2.php');
}
async function fetchStatusZKosong() {
    const el = document.getElementById('STATUS_Z'); if(!el) return;
    updateCard(el, 'opredp/api_count_statuszkosong.php');
}
async function fetchBaseprice() {
    const el = document.getElementById('BASEPRICE'); if(!el) return;
    updateCard(el, 'opredp/api_count_baseprice.php');
}
async function fetchCekCashback() {
    const el = document.getElementById('CEK_CASHBACK'); if(!el) return;
    updateCard(el, 'opredp/api_count_cbselisih.php');
}
async function fetchLockingCount() {
    const el = document.getElementById('locking'); if(!el) return;
    updateCard(el, 'opredp/api_get_monitor.php');
}
async function fetchMarginMinus() {
    const el = document.getElementById('MARGIN_MINUS'); if(!el) return;
    updateCard(el, 'opredp/api_count_marmin.php');
}

async function updateCard(targetElement, apiUrl) {
    const cardElement = targetElement.closest('.stat-card');
    try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();

        if (result.status === 'success') {
            const total = result.data.total;
            targetElement.textContent = total;
            if (total > 0) {
                cardElement.classList.remove('card-success');
                cardElement.classList.add('card-danger');
            } else {
                cardElement.classList.remove('card-danger');
                cardElement.classList.add('card-success');
            }
        }
    } catch (error) {
        targetElement.textContent = 'Err';
        cardElement.classList.add('card-danger');
        console.error(`Error fetching ${apiUrl}:`, error);
    }
}

async function fetchDbConnection() {
    const targetElement = document.getElementById('DB_PGSQL_STATUS');
    if (!targetElement) return;
    const cardElement = targetElement.closest('.stat-card');

    try {
        const response = await fetch('opredp/api_check_db_connection.php');
        const result = await response.json();
        if (result.status === 'success') {
            const isConnected = result.data.connected;
            targetElement.textContent = result.data.message;
            if (isConnected) {
                cardElement.classList.remove('card-danger');
                cardElement.classList.add('card-success');
            } else {
                cardElement.classList.remove('card-success');
                cardElement.classList.add('card-danger');
            }
        }
    } catch (error) {
        targetElement.textContent = 'ERROR';
        cardElement.classList.add('card-danger');
    }
}