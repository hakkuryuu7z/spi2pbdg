<?php
// File: detailsales/api_get_detail_po.php

header("Content-Type: application/json");
include "../connection/index.php";

$response = [];

// Ambil nomor PB dari parameter GET
$nomor_pb = isset($_GET['nopb']) ? $_GET['nopb'] : null;

if (!$nomor_pb) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Nomor PB (nopb) diperlukan.';
    echo json_encode($response);
    exit();
}

// Query disesuaikan dengan penambahan d.obi_picker
$sql = "
    SELECT 
        h.obi_kdmember AS kodemember,
        h.obi_nopb AS nopb,
        h.obi_notrans AS notrans,
        TO_CHAR(h.obi_tgltrans, 'DD-MM-YYYY HH24:MI:SS') AS tgltrans,
        c.cus_namamember AS nama_member,
        p.prd_deskripsipendek AS nama_produk,
        d.obi_prdcd AS plu,
        d.obi_qtyrealisasi AS qty_real,
        d.obi_qtyorder AS qty_order,
        
        -- --- PENAMBAHAN KOLOM BARU (PICKER) ---
        d.obi_picker, 
        -- --------------------------------------

        h.obi_tipebayar AS tipebayar,
        h.obi_kdekspedisi AS ekspedisi,
        
        h.obi_ttlorder,
        h.obi_realorder,
        h.obi_realdiskon,

        SUM(k.ongkir) AS ongkir

FROM 
    tbtr_obi_h AS h
LEFT JOIN 
    tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember 
LEFT JOIN 
    tbtr_obi_d AS d ON h.obi_notrans = d.obi_notrans AND d.obi_tgltrans = h.obi_tgltrans 
LEFT JOIN 
    tbmaster_prodmast AS p ON d.obi_prdcd = p.prd_prdcd
LEFT JOIN 
    payment_klikigr AS k ON h.obi_kdmember = k.kode_member AND h.obi_nopb = k.no_pb
WHERE 
    h.obi_nopb = $1

GROUP BY
    h.obi_kdmember,
    h.obi_nopb,
    h.obi_notrans,
    h.obi_tgltrans,
    c.cus_namamember,
    p.prd_deskripsipendek,
    d.obi_prdcd,
    d.obi_qtyrealisasi,
    d.obi_qtyorder,
    
    -- --- PENAMBAHAN GROUP BY (PICKER) ---
    d.obi_picker,
    -- ------------------------------------

    h.obi_tipebayar,
    h.obi_kdekspedisi,
    h.obi_ttlorder,
    h.obi_realorder,
    h.obi_realdiskon;
";

$params = [$nomor_pb];
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
