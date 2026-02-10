<?php
// TAMBAHKAN DUA BARIS INI UNTUK MENAMPILKAN ERROR JIKA ADA
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Sertakan library SimpleXLSXGen
// Pastikan path ini benar dari lokasi file api_download_member_mr.php
require_once '../lib/SimpleXLSXGen.php';

// 2. BERITAHU PHP UNTUK MENGGUNAKAN KELAS DARI NAMESPACE Shuchkin
use Shuchkin\SimpleXLSXGen;

// 3. Sertakan koneksi database Anda
include "../connection/index.php";

// 4. Validasi parameter 'mr'
if (!isset($_GET['mr']) || empty($_GET['mr'])) {
    http_response_code(400);
    echo "Error: Parameter MR dibutuhkan.";
    exit();
}

$mr_filter = $_GET['mr'];
$filename = "kodemember_mr_" . $mr_filter . ".xlsx";

// 5. Query untuk mengambil data
$sql = "SELECT cus_kodemember FROM tbmaster_customer WHERE cus_kodeigr = '2P' AND cus_nosalesman = $1";
$queryResult = pg_query_params($conn, $sql, [$mr_filter]);

// Jika query gagal, tampilkan error
if (!$queryResult) {
    echo "Error pada query database: " . pg_last_error($conn);
    exit;
}

// 6. Siapkan data dalam bentuk array
$data_for_excel = [];
$data_for_excel[] = ['Kodemember']; // Header

while ($row = pg_fetch_assoc($queryResult)) {
    $data_for_excel[] = [$row['cus_kodemember']];
}

// 7. Tutup koneksi database
pg_close($conn);

// 8. Gunakan library untuk membuat dan mengunduh file .xlsx
// Sekarang PHP sudah mengenali kelas SimpleXLSXGen berkat 'use' di atas
SimpleXLSXGen::fromArray($data_for_excel)->downloadAs($filename);

exit();
