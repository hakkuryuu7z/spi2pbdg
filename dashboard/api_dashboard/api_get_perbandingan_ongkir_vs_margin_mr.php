<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Opsional: jika akses dari domain berbeda
require_once '../connection/index.php';

// --- 1. SETUP PARAMETER TANGGAL ---
// Default: Tanggal 1 bulan ini s/d Hari ini
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- 2. QUERY UTAMA (CTE) ---
$sql = "
WITH 
-- 1. HITUNG MARGIN (Berdasarkan parameter tanggal)
cte_margin AS (
    SELECT 
        CASE 
            WHEN C.cus_nosalesman IS NULL OR C.cus_nosalesman = '' THEN 'HJK' 
            ELSE C.cus_nosalesman 
        END as mr,
        SUM(ROUND(
            CASE
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then (TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then (TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then (((TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then ((TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then ((TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1)
                ELSE (TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
            END
        )) as total_margin
    FROM TBTR_JUALDETAIL
    LEFT JOIN TBMASTER_CUSTOMER C on TRJD_CUS_KODEMEMBER = C.CUS_KODEMEMBER
    LEFT JOIN TBMASTER_PRODMAST on TRJD_PRDCD = PRD_PRDCD
    WHERE TRJD_RECORDID is null
    AND DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date'
    GROUP BY 
        CASE 
            WHEN C.cus_nosalesman IS NULL OR C.cus_nosalesman = '' THEN 'HJK' 
            ELSE C.cus_nosalesman 
        END
),

-- 2. HITUNG ONGKIR (Berdasarkan parameter tanggal)
cte_ongkir AS (
    SELECT 
        cus.cus_nosalesman AS mr,
        SUM(COALESCE(klk.pot_ongkir, 0)) AS total_ongkir
    FROM tbtr_obi_h obi
    LEFT JOIN tbmaster_customer cus ON obi.obi_kdmember = cus.cus_kodemember
    LEFT JOIN payment_klikigr klk ON obi.obi_nopb = klk.no_pb 
    WHERE 
        obi.obi_tglstruk::date BETWEEN '$start_date' AND '$end_date'
        
        AND obi.obi_recid = '6' -- Status SELESAI
    GROUP BY cus.cus_nosalesman
)

-- 3. GABUNGKAN KEDUANYA
SELECT 
    COALESCE(m.mr, o.mr) as mr_code,
    COALESCE(o.total_ongkir, 0) as ongkir_amount,
    COALESCE(m.total_margin, 0) as margin_amount
FROM cte_margin as m
FULL OUTER JOIN cte_ongkir as o ON m.mr = o.mr
ORDER BY ongkir_amount DESC
";

$query = pg_query($conn, $sql);

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => pg_last_error($conn),
        "sql_debug" => $sql
    ]);
    exit;
}

// --- 3. FORMAT DATA UNTUK JSON ---
$categories = []; // Label MR
$dataOngkir = []; // Series 1
$dataMargin = []; // Series 2
$tableData  = []; // Data Lengkap untuk Tabel

$total_ongkir_all = 0;
$total_margin_all = 0;

while ($row = pg_fetch_assoc($query)) {
    $mr     = $row['mr_code'];
    $ongkir = (float)$row['ongkir_amount'];
    $margin = (float)$row['margin_amount'];

    // Hitung Persentase Ongkir terhadap Margin
    // Jika margin 0, hindari division by zero
    $ratio = ($margin != 0) ? ($ongkir / $margin) * 100 : 0;

    // Push ke array chart
    $categories[] = $mr;
    $dataOngkir[] = $ongkir;
    $dataMargin[] = $margin;

    // Hitung Total
    $total_ongkir_all += $ongkir;
    $total_margin_all += $margin;

    // Push ke array tabel (opsional, untuk detail list)
    $tableData[] = [
        "mr" => $mr,
        "ongkir" => $ongkir,
        "margin" => $margin,
        "ratio_persen" => round($ratio, 2)
    ];
}

// Hitung Ratio Total
$total_ratio = ($total_margin_all != 0) ? ($total_ongkir_all / $total_margin_all) * 100 : 0;

// --- 4. RETURN RESPONSE ---
echo json_encode([
    "status" => "success",
    "filter" => [
        "start_date" => $start_date,
        "end_date"   => $end_date
    ],
    "chart" => [
        "categories" => $categories,
        "series" => [
            [
                "name" => "Total Ongkir",
                "data" => $dataOngkir,
                "color" => "#FF4560" // Merah muda untuk biaya/ongkir
            ],
            [
                "name" => "Total Margin",
                "data" => $dataMargin,
                "color" => "#008FFB" // Biru untuk profit/margin
            ]
        ]
    ],
    "summary" => [
        "total_ongkir" => $total_ongkir_all,
        "total_margin" => $total_margin_all,
        "total_ratio_persen" => round($total_ratio, 2) . "%"
    ],
    "detail_data" => $tableData
]);
