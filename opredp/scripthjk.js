$(document).ready(function() {
        // Inisialisasi DataTable
        $('#tableHjk').DataTable({
            "responsive": true,
            "autoWidth": false,
            "processing": true, // Tampilkan pesan loading
            "ajax": {
                "url": "opredp/api_cekitemhjk.php", // Lokasi API yang kita buat sebelumnya
                "type": "GET",
                "dataSrc": "data" // Mengambil array dari key 'data' di JSON
            },
            "columns": [
                { "data": "plu" },
                { "data": "deskripsi" },
                { 
                    "data": "HARGA JUAL",
                    // Format angka menjadi format uang (opsional)
                    "render": function ( data, type, row ) {
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data);
                    }
                },
                { "data": "TANGGAL AWAL" },
                { "data": "TANGGAL AKHIR" }
            ],
            "language": {
                "emptyTable": "Tidak ada data harga khusus yang aktif"
            }
        });
    });