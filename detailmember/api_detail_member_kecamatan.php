<?php

header('Content-Type: application/json');
include "../connection/index.php";


if (!isset($_GET['kecamatan']) || empty($_GET['kecamatan'])) {

    http_response_code(400);
    echo json_encode(['error' => 'Parameter Kecamatan dibutuhkan.']);
    exit();
}


$kecamatan = $_GET['kecamatan'];

$sql = "
    SELECT 
        c.CUS_KODEMEMBER,
        c.CUS_NAMAMEMBER,
        c.cus_nosalesman,
        c.cus_alamatemail,
        CASE
            WHEN c.cus_tglmulai IS NULL THEN 'Non-Transaksi'
            ELSE 'Aktif'
        END AS status,
        c.cus_tglmulai
    FROM
        tbmaster_customer AS c 
    LEFT JOIN
        tbmaster_customercrm AS r ON c.cus_kodemember = r.crm_kodemember
    WHERE 
        c.cus_kodeigr = '2P' 
        AND UPPER(r.crm_kecamatan_usaha) = UPPER($1) -- Menggunakan placeholder dan UPPER
     ORDER BY CUS_TGLMULAI ASC   
";

// Langkah 3: Eksekusi query menggunakan pg_query_params
// Parameter $kecamatan diikat ke placeholder $1.
$queryResult = pg_query_params($conn, $sql, [$kecamatan]);

$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}

// Langkah 4: Kembalikan hasil sebagai JSON
echo json_encode(['data' => $data ?: []]);
