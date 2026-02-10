<?php
header('Content-Type: application/json');
include "../connection/index.php";

// Validasi input bulan
if (!isset($_GET['bulan']) || empty($_GET['bulan'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter Bulan dibutuhkan.']);
    exit();
}

$bulan = $_GET['bulan']; // contoh format: '2025-09'

$sql = "
    SELECT
        obi_kdmember AS kode,
        obi_nopb AS nopb,
        obi_tglpb AS tglpb,
        cus_namamember AS nama,
        obi_kdekspedisi AS ekspedisi,
        CASE
            WHEN obi_recid = '6' THEN 'SELESAI' 
            WHEN obi_recid = '1' THEN 'SIAP PICKING'
            WHEN obi_recid = '2' THEN 'SIAP PACKING'
            WHEN obi_recid = '3' THEN 'SIAP DRAFT STRUK'
            WHEN obi_recid = '4' THEN 'KONFIRMASI PEMBAYARAN'
            WHEN obi_recid = '5' THEN 'SIAP STRUK'
            WHEN obi_recid LIKE 'B%' THEN 'BATAL'
            WHEN obi_recid IS NULL THEN 'SIAP SEND HANDHELD'
            ELSE obi_recid
        END AS status,
        SUM(total) AS sales
    FROM
        tbtr_obi_h
    LEFT JOIN
        tbmaster_customer ON obi_kdmember = cus_kodemember
    LEFT JOIN
        payment_klikigr ON obi_nopb = no_pb
    -- JOIN DIPERBAIKI SESUAI PERMINTAAN ANDA (HANYA BERDASARKAN TANGGAL) --
    LEFT JOIN
        tbtr_jualheader ON obi_tglstruk = jh_transactiondate
    WHERE
        obi_kdekspedisi = 'Ambil di Stock Point Indogrosir'
        AND OBI_RECID = '6'
        AND TO_CHAR(obi_tglpb, 'YYYY-MM') = $1 -- Filter berdasarkan bulan
        GROUP BY
    obi_nopb,
    obi_kdmember,
    obi_tglpb,
    cus_namamember,
    obi_kdekspedisi,
    STATUS
    ORDER BY 
        OBI_TGLPB ASC;
";

$queryResult = pg_query_params($conn, $sql, [$bulan]);
$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}

echo json_encode(['status' => 'success', 'data' => $data ?: []]);
