<?php

header('Content-Type: application/json');

// Menghubungkan ke database
require_once "../connection/index.php";

$response = [];

try {
    // Query SQL sesuai permintaan
    // Perhatikan: Tanda kutip ganda (") pada alias kolom di-escape (\") agar tidak bentrok dengan string PHP
    $sql = "SELECT 
                hk.hgk_prdcd AS PLU,
                pm.prd_deskripsipanjang AS DESKRIPSI,
                hk.hgk_hrgjual AS \"HARGA JUAL\",
                hk.hgk_tglawal AS \"TANGGAL AWAL\",
                hk.hgk_tglakhir AS \"TANGGAL AKHIR\"
            FROM 
                tbtr_hargakhusus hk
            LEFT JOIN 
                tbmaster_prodmast pm ON hk.hgk_prdcd = pm.prd_prdcd
            WHERE 
                hk.hgk_tglakhir >= current_date 
            ORDER BY hk.hgk_prdcd";

    // Eksekusi query menggunakan koneksi postgres ($conn diambil dari connection/index.php)
    $result = pg_query($conn, $sql);

    // Cek jika query gagal
    if (!$result) {
        throw new Exception("Query gagal: " . pg_last_error($conn));
    }

    // Ambil semua data
    $data = pg_fetch_all($result);

    // Susun response sukses
    $response['status'] = 'success';

    // pg_fetch_all mengembalikan false jika tidak ada data, ubah menjadi array kosong []
    $response['data'] = ($data === false) ? [] : $data;
} catch (Exception $e) {
    // Tangani error
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// Tutup koneksi (pastikan variabel $conn sesuai dengan file connection/index.php)
if (isset($conn)) {
    pg_close($conn);
}

// Output JSON
echo json_encode($response);
