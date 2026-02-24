<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Opsional: jika akses dari domain berbeda
require_once '../connection/index.php';

// --- 1. SETUP PARAMETER TANGGAL ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- 2. QUERY UTAMA (CTE) ---
$sql = "
WITH 
-- 1. AMBIL RAW DATA MARGIN & EKSPEDISI (Agar CASE rumus tidak diulang-ulang)
cte_raw_margin AS (
    SELECT 
        CASE 
            WHEN C.cus_nosalesman IS NULL OR C.cus_nosalesman = '' THEN 'HJK' 
            ELSE C.cus_nosalesman 
        END as mr,
        
        ROUND(
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
        ) as nilai_margin,
        
        COALESCE(obi.obi_kdekspedisi, '') as ekspedisi

    FROM TBTR_JUALDETAIL jd
    LEFT JOIN TBMASTER_CUSTOMER C on jd.TRJD_CUS_KODEMEMBER = C.CUS_KODEMEMBER
    LEFT JOIN TBMASTER_PRODMAST p on jd.TRJD_PRDCD = p.PRD_PRDCD
    
    -- Relasi ke TBTR_OBI_H untuk mendapatkan tipe ekspedisi
    LEFT JOIN TBTR_OBI_H obi ON 
        TO_CHAR(jd.TRJD_TRANSACTIONDATE, 'YYYYMMDD') = TO_CHAR(obi.OBI_TGLSTRUK, 'YYYYMMDD')
        AND jd.TRJD_TRANSACTIONNO = obi.OBI_NOSTRUK
        AND jd.TRJD_CASHIERSTATION = obi.OBI_KDSTATION
        
    WHERE jd.TRJD_RECORDID is null
    AND DATE(jd.TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date'
),

-- 2. HITUNG AGREGAT MARGIN TOTAL & MARGIN TIPE KIRIM
cte_margin AS (
    SELECT 
        mr,
        SUM(nilai_margin) as total_margin,
        SUM(CASE WHEN ekspedisi <> 'Ambil di Stock Point Indogrosir' THEN nilai_margin ELSE 0 END) as total_margin_kirim,
        SUM(CASE WHEN ekspedisi = 'Ambil di Stock Point Indogrosir' THEN nilai_margin ELSE 0 END) as total_margin_at
    FROM cte_raw_margin
    GROUP BY mr
),

-- 3. HITUNG ONGKIR (Berdasarkan parameter tanggal)
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

-- 4. GABUNGKAN KEDUANYA
SELECT 
    COALESCE(m.mr, o.mr) as mr_code,
    COALESCE(o.total_ongkir, 0) as ongkir_amount,
    COALESCE(m.total_margin, 0) as margin_amount,
    COALESCE(m.total_margin_kirim, 0) as margin_kirim_amount,
    COALESCE(m.total_margin_at, 0) as margin_at_amount
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
$categories = [];
$dataOngkir = [];
$dataMargin = [];
$tableData  = [];

$total_ongkir_all = 0;
$total_margin_all = 0;
$total_margin_kirim_all = 0;
$total_margin_at_all = 0; // Tambahan untuk AT

while ($row = pg_fetch_assoc($query)) {
    $mr           = $row['mr_code'];
    $ongkir       = (float)$row['ongkir_amount'];
    $margin       = (float)$row['margin_amount'];
    $margin_kirim = (float)$row['margin_kirim_amount'];
    $margin_at    = (float)$row['margin_at_amount']; // Tangkap dari SQL

    $ratio = ($margin != 0) ? ($ongkir / $margin) * 100 : 0;

    $categories[] = $mr;
    $dataOngkir[] = $ongkir;
    $dataMargin[] = $margin;

    $total_ongkir_all += $ongkir;
    $total_margin_all += $margin;
    $total_margin_kirim_all += $margin_kirim;
    $total_margin_at_all += $margin_at; // Agregat Tipe AT

    $tableData[] = [
        "mr" => $mr,
        "ongkir" => $ongkir,
        "margin" => $margin,
        "margin_kirim" => $margin_kirim,
        "margin_at" => $margin_at,
        "ratio_persen" => round($ratio, 2)
    ];
}

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
                "color" => "#FF4560"
            ],
            [
                "name" => "Total Margin",
                "data" => $dataMargin,
                "color" => "#008FFB"
            ]
        ]
    ],
    "summary" => [
        "total_ongkir" => $total_ongkir_all,
        "total_margin" => $total_margin_all,
        "total_margin_kirim" => $total_margin_kirim_all, // Variabel dikirim ke frontend
        "total_margin_at" => $total_margin_at_all,
        "total_ratio_persen" => round($total_ratio, 2) . "%"
    ],
    "detail_data" => $tableData
]);
