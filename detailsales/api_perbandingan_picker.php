<?php
// File: detailsales/api_perbandingan_picker.php
header("Content-Type: application/json");
include "../connection/index.php";

$response = [];
$tgl_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

$sql = "
    SELECT
        CASE 
            WHEN d.obi_picker IS NULL OR d.obi_picker = '' THEN 'BELUM ADA PICKER'
            ELSE d.obi_picker 
        END as obi_picker,
        
        -- PERBAIKAN DISINI:
        -- Gabungkan Tanggal (obi_tglpb) dan NoTrans (obi_notrans) agar unik antar hari
        COUNT(DISTINCT CONCAT(h.obi_tglpb, '_', h.obi_notrans)) as jumlah_pb
        
    FROM
        tbtr_obi_h as h
    LEFT JOIN 
        tbtr_obi_d as d ON h.obi_tgltrans = d.obi_tgltrans
        AND h.obi_notrans = d.obi_notrans
    WHERE
        h.obi_tglpb::date BETWEEN $1 AND $2
    GROUP BY
        CASE 
            WHEN d.obi_picker IS NULL OR d.obi_picker = '' THEN 'BELUM ADA PICKER'
            ELSE d.obi_picker 
        END
    ORDER BY
        jumlah_pb DESC
";

$params = [$tgl_mulai, $tgl_selesai];
$query = pg_query_params($conn, $sql, $params);

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
