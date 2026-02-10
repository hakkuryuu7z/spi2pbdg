<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Hubungkan ke database
require_once '../connection/index.php';

// Pastikan koneksi berhasil
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terhubung ke database'
    ]);
    exit;
}

// 1. Ambil parameter
$tanggal_transaksi = isset($_GET['tanggal']) ? $_GET['tanggal'] : '20251014';
$kode_member       = isset($_GET['kodemember']) ? $_GET['kodemember'] : '';

$response = [];

// 2. Siapkan Parameter Binding Array
// Di PostgreSQL Native, kita pakai $1, $2, dst (bukan :nama_param)
$params = [];
$params[] = $tanggal_transaksi; // Ini akan jadi $1

// 3. Bangun Query Dasar
// Perhatikan penggunaan $1 menggantikan :tanggal
$sql = "SELECT 
            jh_cashierid,
            jh_transactionno,
            jh_transactiondate,
            jh_transactioncashamt,
            jh_cus_kodemember 
        FROM tbtr_jualheader 
        WHERE to_char(jh_transactiondate, 'yyyymmdd') = $1";

// 4. Logika Kondisional (Dynamic WHERE)
if (!empty($kode_member)) {
    // Karena $tanggal_transaksi adalah $1, maka kodemember jadi $2
    $sql .= " AND jh_cus_kodemember = $2";
    $params[] = $kode_member; // Masukkan ke array urutan ke-2
}

// 5. Tambahkan Order By
$sql .= " ORDER BY jh_cus_kodemember ASC";

// 6. Eksekusi Query dengan pg_query_params (Pengganti prepare->execute)
$result = pg_query_params($conn, $sql, $params);

if (!$result) {
    // Jika query gagal (Syntax error atau error DB)
    $response['status']  = 'error';
    $response['message'] = 'Database error: ' . pg_last_error($conn);
} else {
    // Ambil semua data (Pengganti fetchAll)
    $data = pg_fetch_all($result);

    // pg_fetch_all return FALSE jika kosong, jadi kita cek
    if ($data) {
        $response['status']  = 'success';
        $response['message'] = 'Data ditemukan';
        $response['count']   = count($data);
        $response['data']    = $data;
    } else {
        $response['status']  = 'empty';
        $response['message'] = 'Tidak ada transaksi ditemukan pada kriteria tersebut.';
        $response['data']    = [];
    }
}

// 7. Output JSON
echo json_encode($response);
