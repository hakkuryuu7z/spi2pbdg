<?php
header('Content-Type: application/json');
// Sesuaikan path ke file koneksi database Anda
include "../connection/index.php";

// --- Logika Paginasi ---
$limit = 10; // Tentukan jumlah data per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Query 1: Menghitung TOTAL data untuk paginasi ---
$totalSql = "
    SELECT COUNT(DISTINCT UPPER(crm_kecamatan_usaha)) as total 
    FROM tbmaster_customercrm 
    WHERE crm_kodeigr = '2P'
";
$totalResult = pg_query($conn, $totalSql);
$totalRecords = pg_fetch_result($totalResult, 0, 0);
$totalPages = ceil($totalRecords / $limit);

// --- Query 2: Mengambil data SESUAI HALAMAN ---
$dataSql = "
    SELECT
        COUNT(crm_kodemember) AS jumlah_member,
        UPPER(crm_kecamatan_usaha) AS kecamatan
    FROM
        tbmaster_customercrm
    WHERE
        crm_kodeigr = '2P'
    GROUP BY
        UPPER(crm_kecamatan_usaha)
    ORDER BY
        kecamatan ASC
    LIMIT $1 OFFSET $2
";
$dataResult = pg_query_params($conn, $dataSql, [$limit, $offset]);

$data = [];
if ($dataResult) {
    $data = pg_fetch_all($dataResult);
}

// --- Kembalikan hasil dengan informasi paginasi ---
echo json_encode([
    'data'        => $data ?: [],
    'currentPage' => $page,
    'totalPages'  => (int)$totalPages
]);
