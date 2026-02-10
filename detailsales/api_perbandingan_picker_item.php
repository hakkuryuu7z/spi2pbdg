<?php
// File: detailsales/api_perbandingan_picker_item.php

header("Content-Type: application/json");
include "../connection/index.php";

$response = [];

$tgl_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

// Query: Menghitung JUMLAH BARIS ITEM (Total Line)
// Menggunakan COUNT(d.obi_prdcd) untuk menghitung berapa kali dia ambil barang
$sql = "
    SELECT
        d.obi_picker,
        COUNT(d.obi_prdcd) as total_item 
    FROM
        tbtr_obi_d as d
    JOIN 
        tbtr_obi_h as h ON d.obi_notrans = h.obi_notrans AND d.obi_tgltrans = h.obi_tgltrans
    WHERE
        h.obi_tglpb::date BETWEEN $1 AND $2
        AND d.obi_picker IS NOT NULL 
        AND d.obi_picker != ''
    GROUP BY
        d.obi_picker
    ORDER BY
        total_item DESC
";

$params = [$tgl_mulai, $tgl_selesai];
$query = pg_query_params($conn, $sql, $params);

if ($query) {
    $data = pg_fetch_all($query);
    $response['status'] = 'success';
    $response['data'] = $data ? $data : [];
} else {
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

echo json_encode($response);
pg_close($conn);
