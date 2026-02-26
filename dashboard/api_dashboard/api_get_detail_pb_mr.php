<?php
// dashboard/api_get_detail_pb_mr.php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../connection/index.php";

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Error']);
    exit;
}

// Tangkap Parameter
$salesman = isset($_GET['salesman']) ? $_GET['salesman'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (empty($salesman)) {
    echo json_encode(['status' => 'error', 'message' => 'Salesman tidak ditemukan']);
    exit;
}

// Query SQL (Sesuai request Anda)
$sql = "
SELECT
    CASE
        WHEN h.obi_recid IS NULL THEN 'Siap Send HH'
        WHEN h.obi_recid = '1' THEN 'Siap Picking'
        WHEN h.obi_recid = '2' THEN 'Siap Packing'
        WHEN h.obi_recid = '3' THEN 'Siap Draft Struk'
        WHEN h.obi_recid = '4' THEN 'Konfirmasi Pembayaran'
        WHEN h.obi_recid = '5' THEN 'Siap Struk'
        WHEN h.obi_recid = '6' THEN 'Selesai Struk'
        WHEN h.obi_recid IN ('B', 'B1', 'B2', 'B3') THEN 'Transaksi Batal'
        ELSE h.obi_recid
    END AS status,
    h.obi_kdmember,
    c.cus_nosalesman,
    h.obi_nopb,
    c.cus_namamember,
    h.obi_notrans,
    to_char(h.obi_tglpb, 'DD-MM-YYYY HH24:MI') as tgl_pb -- Tambahan biar tau jamnya
FROM
    tbtr_obi_h AS h
LEFT JOIN
    tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember
WHERE
    h.obi_tglpb::date BETWEEN '$start_date' AND '$end_date'
    AND c.cus_nosalesman = '$salesman'
ORDER BY h.obi_tglpb DESC
";

$result = pg_query($conn, $sql);
$data = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode(['status' => 'success', 'data' => $data]);
?>