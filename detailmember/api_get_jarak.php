<?php
header('Content-Type: application/json');
// Sesuaikan path ke file koneksi database Anda
include "../connection/index.php";

// Query untuk mengelompokkan member berdasarkan jarak
$sql = "
    SELECT
        CASE
            WHEN CUS_JARAK::FLOAT < 5 THEN '< 5 KM'
            WHEN CUS_JARAK::FLOAT BETWEEN 5 AND 10 THEN '5 - 10 KM'
            WHEN CUS_JARAK::FLOAT > 10 AND CUS_JARAK::FLOAT <= 15 THEN '10 - 15 KM'
            WHEN CUS_JARAK::FLOAT > 15 THEN '> 15 KM'
            ELSE 'Belum Ada Jarak'
        END AS kategori_jarak,
        COUNT(CUS_KODEMEMBER) AS jumlah_member
    FROM
        TBMASTER_CUSTOMER
    WHERE
        CUS_KODEIGR = '2P'
        AND CUS_KODEMEMBER <> 'KLZVMJ'
    GROUP BY
        kategori_jarak
    ORDER BY
        kategori_jarak;
";

$queryResult = pg_query($conn, $sql);

$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}

// Kembalikan hasil dalam format JSON yang konsisten
echo json_encode(['data' => $data ?: []]);
