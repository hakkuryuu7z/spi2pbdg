<?php
// detailsales/api_get_stats.php

header('Content-Type: application/json');
include "../connection/index.php";

// --- PERUBAHAN LOGIKA TANGGAL ---
// 1. Ambil parameter rentang tanggal
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

$response = [];

// 2. Validasi parameter
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit; // Hentikan script
}
// --- AKHIR PERUBAHAN TANGGAL ---


// === Query 1: Statistik Umum (PO, Member Order, dll) ===
// --- PERUBAHAN QUERY: Menggunakan BETWEEN $1 AND $2 ---
$sql_stats = "
    SELECT
        (SELECT COUNT(*) FROM tbtr_obi_h WHERE obi_tgltrans::date BETWEEN $1 AND $2) AS total_po,
        (SELECT COUNT(DISTINCT obi_kdmember) FROM tbtr_obi_h WHERE obi_tgltrans::date BETWEEN $1 AND $2) AS total_member_order,
        (SELECT COUNT(*) 
         FROM (
             SELECT 1 FROM tbtr_obi_h
             WHERE obi_tgltrans::date BETWEEN $1 AND $2
             GROUP BY obi_kdmember
             HAVING COUNT(*) > 1
         ) AS subquery) AS double_member_order
";
// --- PERUBAHAN PARAMETER: Kirim kedua tanggal ---
$queryResult_stats = pg_query_params($conn, $sql_stats, [$tgl_mulai, $tgl_selesai]);
$result_stats = pg_fetch_assoc($queryResult_stats);

// Masukkan hasil Query 1 ke dalam response
$response['stats'] = [
    'total_po' => $result_stats['total_po'] ?? '0',
    'totalmember' => $result_stats['total_member_order'] ?? '0',
    'doublemember' => $result_stats['double_member_order'] ?? '0'
];


// === Query 2: Rincian Status PO ===
// --- PERUBAHAN QUERY: Menggunakan BETWEEN $1 AND $2 ---
$sql_statuses = "
    WITH all_statuses AS (
        SELECT status FROM (VALUES
            ('SIAP SEND HANDHELD'), ('SIAP PICKING'), ('SIAP PACKING'),
            ('SIAP DRAFT STRUK'), ('KONFIRMASI PEMBAYARAN'), ('SIAP STRUK'),
            ('SELESAI'), ('BATAL')
        ) AS t(status)
    ),
    daily_counts AS (
        SELECT
            CASE
                WHEN obi_recid = '6' THEN 'SELESAI'
                WHEN obi_recid = '1' THEN 'SIAP PICKING'
                WHEN obi_recid = '2' THEN 'SIAP PACKING'
                WHEN obi_recid = '3' THEN 'SIAP DRAFT STRUK'
                WHEN obi_recid = '4' THEN 'KONFIRMASI PEMBAYARAN'
                WHEN obi_recid = '5' THEN 'SIAP STRUK'
                WHEN obi_recid LIKE 'B%' THEN 'BATAL'
                WHEN obi_recid IS NULL THEN 'SIAP SEND HANDHELD'
            END AS status,
            COUNT(*) as total
        FROM tbtr_obi_h
        WHERE obi_tgltrans::date BETWEEN $1 AND $2  -- <-- PERUBAHAN DI SINI
        GROUP BY status
    )
    SELECT
        a.status,
        COALESCE(d.total, 0) AS kemunculan
    FROM all_statuses a
    LEFT JOIN daily_counts d ON a.status = d.status
    ORDER BY 
        CASE a.status
            WHEN 'SIAP SEND HANDHELD' THEN 1 WHEN 'SIAP PICKING' THEN 2
            WHEN 'SIAP PACKING' THEN 3 WHEN 'SIAP DRAFT STRUK' THEN 4
            WHEN 'KONFIRMASI PEMBAYARAN' THEN 5 WHEN 'SIAP STRUK' THEN 6
            WHEN 'SELESAI' THEN 7 WHEN 'BATAL' THEN 8
        END;
";

// --- PERUBAHAN PARAMETER: Kirim kedua tanggal ---
$queryResult_statuses = pg_query_params($conn, $sql_statuses, [$tgl_mulai, $tgl_selesai]);
$result_statuses = pg_fetch_all($queryResult_statuses);

// Masukkan hasil Query 2 ke dalam response
$response['status_counts'] = $result_statuses ?: [];

// Kirim response JSON gabungan
echo json_encode($response);

// Tutup koneksi
pg_close($conn);
