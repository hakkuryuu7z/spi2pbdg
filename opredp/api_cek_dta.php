<?php

header('Content-Type: application/json');

require_once "../connection/index.php";
$response = [];

try {
    $sql = "SELECT 
 * 
 FROM 
     JOB_LOG_ALL 
 WHERE 
     JOB_NAME LIKE '%DTA4%'
 AND JOB_START:: DATE = CURRENT_DATE-1 ";

    $result = pg_query($conn, $sql);

    if (!$result) {
        throw new Exception("Query gagal:" . pg_last_error($conn));
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
