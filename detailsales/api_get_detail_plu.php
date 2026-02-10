<?php
// 1. Atur Header untuk Respon JSON
header('Content-Type: application/json');

// 2. Sertakan File Koneksi Database
include "../connection/index.php";

// 3. Buat Array untuk Respon
$response = [];

// 4. Validasi Parameter Wajib (PLU)
if (!isset($_GET['plu']) || empty($_GET['plu'])) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter PLU wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit(); // Hentikan skrip
}

// 5. Ambil Parameter dari GET Request
$plu = $_GET['plu'];

// --- PERUBAHAN LOGIKA TANGGAL ---
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

// 6. Validasi parameter tanggal
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi (selain PLU).';
    echo json_encode($response);
    pg_close($conn);
    exit; // Hentikan script
}
// --- AKHIR PERUBAHAN TANGGAL ---


// 7. Definisikan Query SQL Dasar
$sql_base = "
    SELECT
        h.obi_tglpb::date AS tanggal_pb, -- <-- TAMBAHAN YANG ANDA MINTA ADA DI SINI
        CASE
            WHEN h.obi_recid IS NULL THEN 'Siap Kirim ke HH'
            WHEN h.obi_recid = '1' THEN 'Siap Picking'
            WHEN h.obi_recid = '2' THEN 'Siap Packing'
            WHEN h.obi_recid = '3' THEN 'Siap Draft Struk'
            WHEN h.obi_recid = '4' THEN 'Konfirmasi Pembayaran'
            WHEN h.obi_recid = '5' THEN 'Siap Struk'
            WHEN h.obi_recid = '6' THEN 'Selesai Struk'
            ELSE 'Batal'
        END AS status,
        h.obi_kdmember, 
        h.obi_nopb, 
        h.obi_notrans, 
        c.cus_namamember,
        d.obi_qtyrealisasi, 
        d.obi_qtyorder
    FROM tbtr_obi_h AS h
    LEFT JOIN tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember
    LEFT JOIN tbtr_obi_d AS d ON h.obi_notrans = d.obi_notrans AND d.obi_tgltrans = h.obi_tgltrans
";

// 8. Siapkan Kondisi WHERE dan Parameter secara Dinamis dan Aman
// $1 = PLU
// $2 = tanggal_mulai
// $3 = tanggal_selesai
$params = [$plu, $tgl_mulai, $tgl_selesai];
$kondisi_where = "WHERE d.obi_prdcd = $1 AND h.obi_tglpb::date BETWEEN $2 AND $3";


// 9. Gabungkan Query dan Eksekusi dengan Aman
$sql_final = $sql_base . $kondisi_where;

// Menggunakan pg_query_params untuk keamanan dari SQL Injection
$query = pg_query_params($conn, $sql_final, $params);

// 10. Proses Hasil Query
if ($query) {
    // Jika query berhasil, ambil semua datanya
    $data = pg_fetch_all($query);

    // Memberikan respon sukses beserta datanya
    $response['status'] = 'success';
    $response['data'] = $data ?: [];
} else {
    // Jika query gagal, berikan respon error yang jelas untuk debugging
    http_response_code(500); // Internal Server Error
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

// 11. Tampilkan Hasil dalam Format JSON
echo json_encode($response);

// 12. Tutup Koneksi untuk Melepaskan Resource Server
pg_close($conn);
