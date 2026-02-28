<?php
// 1. Atur Header untuk Respon JSON
header("Content-Type: application/json");

// 2. Sertakan File Koneksi Database
include "../connection/index.php";

// 3. Buat Array untuk Respon
$response = [];

// 4. Ambil Tanggal dari Parameter GET
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

// Validasi
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400);
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit;
}

// 5. Definisikan Query SQL menggunakan sum dan kondisi yang diminta
$sql = "
    SELECT SUM(ROUND(
        (CASE 
            WHEN COALESCE(d.obi_qtyrealisasi, 0) <> 0 THEN d.obi_qtyrealisasi 
            ELSE d.obi_qtyorder 
        END) * (d.obi_hargasatuan + COALESCE(d.obi_ppn, 0) - COALESCE(d.obi_diskon, 0))
    )) AS total_sales_perpb
    FROM tbtr_obi_h h
    JOIN tbtr_obi_d d ON h.obi_notrans = d.obi_notrans AND h.obi_tgltrans = d.obi_tgltrans
    WHERE h.obi_tglorder::date BETWEEN $1 AND $2
    AND (h.obi_recid IS NULL OR h.obi_recid NOT LIKE 'B%');
";

// 6. Siapkan Parameter 
$params = [$tgl_mulai, $tgl_selesai];

// 7. Eksekusi Query
$query = pg_query_params($conn, $sql, $params);

// 8. Proses Hasil Query
if ($query) {
    $data = pg_fetch_assoc($query);
    $response['status'] = 'success';
    // Jika data null (belum ada sales), kembalikan angka 0
    $response['sales_perpb'] = $data['total_sales_perpb'] ? (float) $data['total_sales_perpb'] : 0;
} else {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

// 9. Tampilkan Hasil
echo json_encode($response);
pg_close($conn);
