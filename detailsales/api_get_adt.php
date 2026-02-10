<?php
// detailsales/api_get_adt.php (Versi Margin = 0)

header('Content-Type: application/json');
include "../connection/index.php";

$sql = "WITH summary_utama AS (
    -- LANGKAH 1: Ambil data transaksi, ganti sumber sales ke payment_klikigr
    SELECT 
        TO_CHAR(h.obi_tglpb, 'YYYY-MM') AS tglpb,
        h.obi_kdmember,
        p.total::numeric, -- Mengambil kolom 'total' dari payment_klikigr dan diubah jadi angka
        h.obi_nopb
    FROM
        tbtr_obi_h h
    -- Join ke payment_klikigr berdasarkan nomor PB untuk mendapatkan total sales
    INNER JOIN
        payment_klikigr p ON h.obi_nopb = p.no_pb
    INNER JOIN
        tbmaster_customer c ON h.obi_kdmember = c.cus_kodemember
    WHERE
        h.obi_kdekspedisi = 'Ambil di Stock Point Indogrosir'
        AND h.obi_recid = '6'
        AND c.cus_flagmemberkhusus = 'Y'
)
-- LANGKAH 2: Lakukan agregasi (GROUP BY) untuk menghitung total bulanan
SELECT
    su.tglpb,
    COUNT(DISTINCT su.obi_kdmember) AS jumlah_member,
    SUM(su.total) AS sales, -- Menjumlahkan 'total' yang sudah diambil dari CTE
    COUNT(DISTINCT su.obi_nopb) AS std,
    0 AS margin -- Margin di-set ke 0 sesuai permintaan
FROM
    summary_utama su
GROUP BY
    su.tglpb
ORDER BY
    su.tglpb DESC;
";

$queryResult = pg_query($conn, $sql);
$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}

echo json_encode(['data' => $data ?: []]);
