<?php
// 1. Atur Header untuk Respon JSON
header("Content-Type: application/json");

// 2. Sertakan File Koneksi Database
include "../connection/index.php";

// 3. Buat Array untuk Respon
$response = [];

// --- PERUBAHAN LOGIKA TANGGAL ---
// 4. Ambil Tanggal dari Parameter GET
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

// 5. Validasi parameter
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit; // Hentikan script
}
// --- AKHIR PERUBAHAN TANGGAL ---


// 6. Siapkan Kondisi WHERE dan Parameter secara Dinamis dan Aman
$params = [];
// Gunakan BETWEEN $1 AND $2 untuk rentang tanggal
$kondisi_where = "WHERE h.obi_tglpb::date BETWEEN $1 AND $2";
$params[] = $tgl_mulai;   // $1 = tanggal_mulai
$params[] = $tgl_selesai; // $2 = tanggal_selesai


// 7. Definisikan Query SQL Final
// {$kondisi_where} akan secara otomatis diganti dengan string dari langkah 6
$sql_final = "
    SELECT
        nama_produk,
        plu,
        sum(qty_real) AS real,
        sum(qty_order) AS \"order\"
    FROM (
        SELECT
            p.prd_deskripsipanjang AS nama_produk,
            d.obi_prdcd AS plu,
            d.obi_qtyrealisasi AS qty_real,
            d.obi_qtyorder AS qty_order
        FROM
            tbtr_obi_h AS h
        LEFT JOIN tbtr_obi_d AS d ON h.obi_notrans = d.obi_notrans AND d.obi_tgltrans = h.obi_tgltrans
        LEFT JOIN tbmaster_prodmast AS p ON d.obi_prdcd = p.prd_prdcd
        {$kondisi_where}
    ) AS main
    GROUP BY nama_produk, plu
    ORDER BY nama_produk;
";

// 8. Eksekusi Query Menggunakan pg_query_params untuk Keamanan
$query = pg_query_params($conn, $sql_final, $params);

// 9. Proses Hasil Query
if ($query) {
    // Jika query berhasil, ambil semua datanya
    $data = pg_fetch_all($query);

    // Memberikan respon sukses beserta datanya
    $response['status'] = 'success';
    $response['data'] = $data ? $data : [];
} else {
    // Jika query gagal, berikan respon error yang jelas
    http_response_code(500); // Set status HTTP ke Internal Server Error
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

// 10. Tampilkan Hasil dalam Format JSON
echo json_encode($response);

// 11. Tutup Koneksi untuk Melepaskan Resource Server
pg_close($conn);
