<?php
// Set header agar output menjadi format JSON
header('Content-Type: application/json');

// Memanggil file koneksi database
require_once "../connection/index.php";

// Inisialisasi array response
$response = [];

try {
    // Query SQL untuk menghitung total baris di tabel lock_monitor
    $sql = "SELECT COUNT(*) AS total FROM lock_monitor;";

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
    // Menggunakan (int) untuk memastikan tipe datanya adalah integer
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
