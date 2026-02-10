<?php
// File: dashboard/api_get_grafik_perbulan.php

header('Content-Type: application/json');
require_once '../connection/index.php'; // Pastikan path ini benar

$response = [
    'bulan' => [],
    'sales' => [],
    'margin' => []
];

try {
    $sql = "
        SELECT
            TO_CHAR(trjd_transactiondate, 'YYYY-MM') AS bulan, 
            SUM(ROUND(
                CASE
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'S' THEN trjd_nominalamt / 1.11
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' THEN trjd_nominalamt
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'N' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' THEN trjd_nominalamt
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'R' THEN (trjd_nominalamt / 1.11) * -1
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'N' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'R' THEN trjd_nominalamt * -1
                    ELSE trjd_nominalamt * -1
                END, 0)) AS sales,
            SUM(ROUND(
                CASE
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'S' AND prd_unit = 'KG' THEN (trjd_nominalamt / 1.11) - ((trjd_baseprice * trjd_quantity) / 1000)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' AND prd_unit = 'KG' THEN trjd_nominalamt - ((trjd_baseprice * trjd_quantity) / 1000)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'N' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' AND prd_unit = 'KG' THEN trjd_nominalamt - ((trjd_baseprice * trjd_quantity) / 1000)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'S' THEN (trjd_nominalamt / 1.11) - (trjd_baseprice * trjd_quantity)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' THEN trjd_nominalamt - (trjd_baseprice * trjd_quantity)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'N' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'S' THEN trjd_nominalamt - (trjd_baseprice * trjd_quantity)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'R' AND prd_unit = 'KG' THEN (((trjd_nominalamt / 1.11) - ((trjd_baseprice * trjd_quantity) / 1000)) * -1)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'R' AND prd_unit = 'KG' THEN ((trjd_nominalamt - ((trjd_baseprice * trjd_quantity) / 1000)) * -1)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'N' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'R' AND prd_unit = 'KG' THEN ((trjd_nominalamt - ((trjd_baseprice * trjd_quantity) / 1000)) * -1)
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') = 'Y' AND trjd_transactiontype = 'R' THEN ((trjd_nominalamt / 1.11) - (trjd_baseprice * trjd_quantity)) * -1
                    WHEN COALESCE(trjd_flagtax1, 'Y') = 'Y' AND COALESCE(trjd_flagtax2, 'T') <> 'Y' AND trjd_transactiontype = 'R' THEN ((trjd_nominalamt - (trjd_baseprice * trjd_quantity)) * -1)
                    ELSE (trjd_nominalamt - (trjd_baseprice * trjd_quantity)) * -1
                END, 0)) AS margin
        FROM
            tbtr_jualdetail AS a
        LEFT JOIN
            tbmaster_prodmast AS b ON trjd_prdcd = prd_prdcd
        LEFT JOIN
            tbmaster_customer AS c ON a.trjd_cus_kodemember = c.cus_kodemember
        WHERE
            trjd_recordid IS NULL
--             AND c.CUS_FLAGMEMBERKHUSUS = 'Y'
        GROUP BY
            bulan
        ORDER BY
            bulan ASC;
    ";

    $result = pg_query($conn, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Format 'YYYY-MM' menjadi 'Mmm YYYY', contoh: 'Jan 2025'
            $dateObj = DateTime::createFromFormat('Y-m', $row['bulan']);
            $response['bulan'][] = $dateObj->format('M Y');
            $response['sales'][] = (float) $row['sales'];
            $response['margin'][] = (float) $row['margin'];
        }
    }

    pg_close($conn);
} catch (Exception $e) {
    // Tangani error jika ada
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
