<?php

header('Content-Type: application/json');

require_once "../connection/index.php";

$response = [];

try {
    $sql = "SELECT SUBMENU,END_TIME,STATUS FROM TBLOG_LARAVEL_IAS  WHERE DATE(END_TIME)= CURRENT_DATE  ORDER BY 3 DESC";

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
