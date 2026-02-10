<?php
// File: detailsales/api_get_pb_by_picker.php

header("Content-Type: application/json");
include "../connection/index.php";

$response = [];

// Ambil Parameter
$tgl_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
$picker = $_GET['picker'] ?? ''; // Bisa string kosong atau 'BELUM ADA PICKER'

// HAPUS validasi "empty($picker)" yang mematikan script
// karena kita butuh proses lanjut jika ingin cari yang kosong

// Siapkan variable dasar
$params = [$tgl_mulai, $tgl_selesai];

// Query Dasar
$sql = "
    SELECT DISTINCT
        h.obi_nopb,
        h.obi_notrans,
        TO_CHAR(h.obi_tgltrans, 'DD-MM-YYYY HH24:MI') as tgl_transaksi,
        c.cus_namamember,
        CASE
            WHEN h.obi_recid IS NULL THEN 'Siap Send HH'
            WHEN h.obi_recid = '1' THEN 'Siap Picking'
            WHEN h.obi_recid = '2' THEN 'Siap Packing'
            WHEN h.obi_recid = '3' THEN 'Siap Draft Struk'
            WHEN h.obi_recid = '4' THEN 'Konfirmasi Bayar'
            WHEN h.obi_recid = '5' THEN 'Siap Struk'
            WHEN h.obi_recid = '6' THEN 'Selesai'
            WHEN h.obi_recid LIKE 'B%' THEN 'Batal'
            ELSE h.obi_recid
        END AS status
    FROM
        tbtr_obi_h AS h
    JOIN
        tbtr_obi_d AS d ON h.obi_notrans = d.obi_notrans AND h.obi_tgltrans = d.obi_tgltrans
    LEFT JOIN
        tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember
    WHERE
        h.obi_tglpb::date BETWEEN $1 AND $2
";

// --- LOGIKA UTAMA PERBAIKAN ---
// Cek apakah user mencari 'BELUM ADA PICKER' atau data kosong
if ($picker === 'BELUM ADA PICKER' || empty($picker)) {
    // Jika iya, cari yang NULL atau string kosong di database
    $sql .= " AND (d.obi_picker IS NULL OR d.obi_picker = '') ";
    // Note: Kita TIDAK menambahkan $picker ke $params karena tidak pakai $3
} else {
    // Jika ada nama picker spesifik (misal: 'BUDI')
    $sql .= " AND d.obi_picker = $3 ";
    $params[] = $picker; // Masukkan nama picker sebagai parameter ke-3
}

$sql .= " ORDER BY h.obi_notrans DESC";

// Eksekusi Query
$query = pg_query_params($conn, $sql, $params);

if ($query) {
    $data = pg_fetch_all($query);
    $response['status'] = 'success';
    $response['picker'] = empty($picker) ? 'BELUM ADA PICKER' : $picker;
    $response['data'] = $data ? $data : [];
} else {
    $response['status'] = 'error';
    // Debugging: tampilkan error pgsql jika perlu
    $response['message'] = 'Gagal mengambil data: ' . pg_last_error($conn);
}

echo json_encode($response);
pg_close($conn);
