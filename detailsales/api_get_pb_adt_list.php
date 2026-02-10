<?php
// detailsales/api_get_pb_adt_list.php

header("Content-Type: application/json");
include "../connection/index.php";

$response = [];

$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400);
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit;
}

// Query Dasar
$sql_base = "
    SELECT
        CASE
            WHEN h.obi_recid IS NULL THEN 'Siap Send HH'
            WHEN h.obi_recid = '1' THEN 'Siap Picking'
            WHEN h.obi_recid = '2' THEN 'Siap Packing'
            WHEN h.obi_recid = '3' THEN 'Siap Draft Struk'
            WHEN h.obi_recid = '4' THEN 'Konfirmasi Pembayaran'
            WHEN h.obi_recid = '5' THEN 'Siap Struk'
            WHEN h.obi_recid = '6' THEN 'Selesai Struk'
            WHEN h.obi_recid = 'B' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B1' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B2' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B3' THEN 'Transaksi Batal'
            ELSE h.obi_recid
        END AS status,
        h.obi_kdmember,
        h.obi_nopb,
        c.cus_namamember,
        h.obi_notrans
    FROM
        tbtr_obi_h AS h
    LEFT JOIN
        tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember
";

// TAMBAHAN FILTER KHUSUS ADT
// Kita tambahkan kondisi AND h.obi_kdekspedisi = '...'
$sql_where = "WHERE (h.obi_tglpb::date BETWEEN $1 AND $2) 
              AND h.obi_kdekspedisi = 'Ambil di Stock Point Indogrosir'";

$params = [$tgl_mulai, $tgl_selesai];

$sql_final = $sql_base . $sql_where . " ORDER BY h.obi_notrans DESC;";

$query = pg_query_params($conn, $sql_final, $params);

if ($query) {
    $data = pg_fetch_all($query);
    $response['status'] = 'success';
    $response['data'] = $data ? $data : [];
} else {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

echo json_encode($response);
pg_close($conn);
