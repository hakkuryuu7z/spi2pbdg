<?php

header('Content-Type: application/json');

require_once "../connection/index.php";

$response = [];

try {
    $sql = "select
    slp_id,
(slp_koderak ||'.'|| slp_kodesubrak ||'.'|| slp_tiperak || slp_shelvingrak ||'.'|| slp_nourut) alamat ,
SLP_PRDCD AS PLU, 
SLP_DESKRIPSI, 
SLP_QTYCRT AS QTY_CTN, 
SLP_QTYPCS AS QTY_PCS,
SLP_UNIT , 
SLP_EXPDATE AS ED, 
SLP_CREATE_BY AS ID_USER 
from 
tbtr_slp 
where 
slp_flag is null
order by 
alamat
;";

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
