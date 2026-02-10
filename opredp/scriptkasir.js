document.addEventListener("DOMContentLoaded", function() {
    // Load data awal (opsional)
    loadDataTransaksi(); 
});

async function loadDataTransaksi() {
    const tanggalInput = document.getElementById('filterTanggal').value;
    const memberInput  = document.getElementById('filterMember').value;
    const tbody        = document.getElementById('tableBodyKasir');

    // 1. Hancurkan DataTable lama (agar tidak error tumpuk)
    if ($.fn.DataTable.isDataTable('#tableKasir')) {
        $('#tableKasir').DataTable().destroy();
    }

    // Tampilkan loading
    tbody.innerHTML = `<tr><td colspan="6" class="text-center">Sedang memuat data...</td></tr>`;

    const formattedDate = tanggalInput.replace(/-/g, '');

    try {
        const response = await fetch(`opredp/api_kasir_struk.php?tanggal=${formattedDate}`);
        const result   = await response.json();

        tbody.innerHTML = "";

        if (result.status === 'success' && result.data.length > 0) {
            let dataTampil = result.data;

            // Filter Member (Client Side)
            if (memberInput.trim() !== "") {
                const membersToFind = memberInput.split(',').map(m => m.trim().toUpperCase());
                dataTampil = dataTampil.filter(item => {
                    const memberDb = item.jh_cus_kodemember ? item.jh_cus_kodemember.toUpperCase() : '';
                    return membersToFind.includes(memberDb);
                });
            }

            if (dataTampil.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Data tidak ditemukan.</td></tr>`;
                return;
            }

            let html = '';
            let no = 1;

            dataTampil.forEach(row => {
                let amount = parseFloat(row.jh_transactioncashamt);
                let displayAmount = new Intl.NumberFormat('id-ID').format(amount);
                
                html += `
                    <tr>
                        <td class="text-center">${no++}</td>
                        <td>${row.jh_cashierid}</td>
                        <td>${row.jh_transactionno}</td>
                        <td>${row.jh_transactiondate}</td>
                        <td>
                            ${row.jh_cus_kodemember 
                                ? `<span class="badge badge-info text-white">${row.jh_cus_kodemember}</span>` 
                                : '<span class="text-muted">-</span>'}
                        </td>
                        <td class="text-right" style="text-align:right; font-weight:bold;">${displayAmount}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            // 2. Init DataTable Responsive
            $('#tableKasir').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false, // PENTING: Matikan autoWidth bawaan
                "responsive": true, // PENTING: Aktifkan plugin responsive
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                }
            });

        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada transaksi.</td></tr>`;
        }

    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal mengambil data API.</td></tr>`;
    }
}