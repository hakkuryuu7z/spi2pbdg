<?php
header('Content-Type: application/json');
include "../connection/index.php";

$sql = "
    SELECT 
        COUNT(CUS_KODEMEMBER) AS total_get, 
        CUS_NOSALESMAN 
    FROM 
        TBMASTER_CUSTOMER 
    WHERE CUS_KODEIGR = '2P'
    -- AND CUS_KODEMEMBER <> 'KLZVMJ'
    AND CUS_NOSALESMAN IS NOT NULL
    AND CUS_NOSALESMAN <> ''
    GROUP BY 
        CUS_NOSALESMAN
    ORDER BY 
        total_get DESC;
";

$queryResult = pg_query($conn, $sql);
$data = pg_fetch_all($queryResult);

echo json_encode(['data' => $data ?: []]);
