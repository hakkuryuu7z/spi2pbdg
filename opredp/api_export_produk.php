<?php
header('Content-Type: application/json');
require_once "../connection/index.php";

$response = [];

try {
    $sql = <<<SQL
SELECT 
    prd_prdcd as plu,
    prd_deskripsipanjang desk, 
    prd_unit as unit, 
    prd_frac as frac , 
    prd_lastcost as lcost,
    prd_hrgjual as hrg_net, 
    prd_kodedivisi as div,
    prd_kodedepartement as dep,
    prd_kodekategoribarang as kat
FROM tbmaster_prodmast 
WHERE prd_recordid is null
SQL;

    $result = pg_query($conn, $sql);

    if (!$result) {
        throw new Exception("Query Gagal: " . pg_last_error($conn));
    }

    $data = pg_fetch_all($result);
    $response['status'] = 'success';
    $response['data'] = ($data === false) ? [] : $data;
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

pg_close($conn);
echo json_encode($response);
