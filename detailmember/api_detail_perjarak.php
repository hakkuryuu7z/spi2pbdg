<?php
header('Content-Type: application/json');
include "../connection/index.php";

if (!isset($_GET['kategori']) || empty($_GET['kategori'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter Kategori Jarak dibutuhkan.']);
    exit();
}

$kategori_jarak_filter = $_GET['kategori'];

// Query diperbarui dengan LEFT JOIN dan kolom kecamatan
$sql = "
    SELECT 
        kategori_jarak,
        cus_kodemember,
        cus_namamember,
        cus_nosalesman,
        cus_alamatemail,
        status,
        cus_tglmulai,
        kecamatan -- Menambahkan kecamatan ke SELECT luar
    FROM (
        -- Query internal
        SELECT
            CASE
                WHEN c.CUS_JARAK::FLOAT < 5 THEN '< 5 KM'
                WHEN c.CUS_JARAK::FLOAT BETWEEN 5 AND 10 THEN '5 - 10 KM'
                WHEN c.CUS_JARAK::FLOAT > 10 AND c.CUS_JARAK::FLOAT <= 15 THEN '10 - 15 KM'
                WHEN c.CUS_JARAK::FLOAT > 15 THEN '> 15 KM'
                ELSE 'Belum Ada Jarak'
            END AS kategori_jarak,
            c.CUS_KODEMEMBER,
            c.CUS_NAMAMEMBER,
            c.cus_nosalesman,
            c.cus_alamatemail,
            CASE
                WHEN c.cus_tglmulai IS NULL THEN 'Non-Transaksi'
                ELSE 'Aktif'
            END AS status,
            c.cus_tglmulai,
            c.CUS_KODEIGR,
            UPPER(r.crm_kecamatan_usaha) AS kecamatan -- Mengambil dan mengubah kecamatan jadi UPPER
        FROM
            TBMASTER_CUSTOMER AS c
        -- Melakukan LEFT JOIN ke tabel customercrm
        LEFT JOIN
            tbmaster_customercrm AS r ON c.CUS_KODEMEMBER = r.crm_kodemember
    ) AS data_dengan_kategori
    WHERE
        CUS_KODEIGR = '2P'
        AND CUS_KODEMEMBER <> 'KLZVMJ'
        AND kategori_jarak = $1 
    ORDER BY
        cus_namamember;
";

$queryResult = pg_query_params($conn, $sql, [$kategori_jarak_filter]);

$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}

echo json_encode(['data' => $data ?: []]);
