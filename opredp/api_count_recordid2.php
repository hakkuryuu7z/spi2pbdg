<?php
// Set header agar output menjadi format JSON
header('Content-Type: application/json');

// Memanggil file koneksi database
require_once "../connection/index.php";

// Inisialisasi array response
$response = [];

try {
    // Query SQL untuk menghitung jumlah record ID = 2
    // DITAMBAHKAN "AS total" agar konsisten
    $sql = "
        SELECT COUNT(*) AS total 
        FROM tbtr_mstran_d 
        WHERE to_char(mstd_create_dt,'mmyy') = to_char(current_date, 'mmyy') 
        AND mstd_recordid = '2';
    ";

    // Eksekusi query
    $result = pg_query($conn, $sql);

    // Cek jika query gagal dieksekusi
    if (!$result) {
        throw new Exception("Eksekusi query gagal: " . pg_last_error($conn));
    }

    // Ambil hasil query (satu baris data) sebagai associative array
    $data = pg_fetch_assoc($result);

    // Set status response sukses
    $response['status'] = 'success';

    // Masukkan data hasil count ke dalam response
    $response['data'] = [
        'total' => isset($data['total']) ? (int)$data['total'] : 0
    ];
} catch (Exception $e) {
    // Jika terjadi error, set status response menjadi error
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    // Selalu tutup koneksi database setelah selesai
    if ($conn) {
        pg_close($conn);
    }
}

// Tampilkan response dalam format JSON
echo json_encode($response);
