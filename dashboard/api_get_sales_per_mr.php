<?php
header('Content-Type: application/json');
require_once '../connection/index.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$mode       = isset($_GET['mode']) ? $_GET['mode'] : 'monthly';

// --- 1. HITUNG TANGGAL PERBANDINGAN ---
$currStartObj = new DateTime($start_date);
$currEndObj   = new DateTime($end_date);
$prevStartObj = clone $currStartObj;
$prevEndObj   = clone $currEndObj;

if ($mode === 'daily') {
    if ($currStartObj->format('N') == 1) {
        $prevStartObj->modify('-2 days');
        $prevEndObj->modify('-2 days');
    } else {
        $prevStartObj->modify('-1 day');
        $prevEndObj->modify('-1 day');
    }
} else {
    $prevStartObj->modify('-1 month');
    $prevEndObj->modify('-1 month');
}

$prev_start = $prevStartObj->format('Y-m-d');
$prev_end   = $prevEndObj->format('Y-m-d');

// --- RUMUS MARGIN (Helper) ---
$margin_formula = "
    case
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then (TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG' then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then (TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then (((TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG' then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then ((TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
        when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then ((TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1)
        else (TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
    end
";

// --- 2. QUERY UTAMA ---
$sql = "
select
    CASE 
        WHEN cus_nosalesman IS NULL OR cus_nosalesman = '' THEN 'HJK' 
        ELSE cus_nosalesman 
    END as KODE_SALESMAN,
    
    -- STRUK
    COUNT(distinct CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN TRJD_TRANSACTIONTYPE || TRJD_CREATE_DT || TRJD_CREATE_BY || TRJD_CASHIERSTATION || TRJD_TRANSACTIONNO END) as STRUK_NOW,
    COUNT(distinct CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN TRJD_TRANSACTIONTYPE || TRJD_CREATE_DT || TRJD_CREATE_BY || TRJD_CASHIERSTATION || TRJD_TRANSACTIONNO END) as STRUK_PREV,

    -- SALES NOW
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN 
        ROUND(
            case
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT / 1.11
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then (TRJD_NOMINALAMT / 1.11) * -1
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then TRJD_NOMINALAMT * -1
                else TRJD_NOMINALAMT * -1
            end, 0)
    ELSE 0 END) as SALES_NOW,
    
    -- SALES PREV
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN 
        ROUND(
            case
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT / 1.11
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then (TRJD_NOMINALAMT / 1.11) * -1
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then TRJD_NOMINALAMT * -1
                else TRJD_NOMINALAMT * -1
            end, 0)
    ELSE 0 END) as SALES_PREV,

    -- MARGIN NOW (Kompleks)
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN 
       $margin_formula
    ELSE 0 END) as MARGIN_NOW,

    -- MARGIN PREV (Kompleks)
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN 
       $margin_formula
    ELSE 0 END) as MARGIN_PREV

from
    TBTR_JUALDETAIL
left join TBMASTER_CUSTOMER on TRJD_CUS_KODEMEMBER = CUS_KODEMEMBER
left join TBMASTER_PRODMAST on TRJD_PRDCD = PRD_PRDCD
where
    TRJD_RECORDID is null
    and (
        (DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date') 
        OR 
        (DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end')
    )
GROUP BY
    CASE 
        WHEN cus_nosalesman IS NULL OR cus_nosalesman = '' THEN 'HJK' 
        ELSE cus_nosalesman 
    END
ORDER BY
    SALES_NOW desc
";

$query = pg_query($conn, $sql);

$categories = [];
$dataNow = [];
$dataPrev = [];
$dataStrukNow = [];
$dataMarginNow = [];
$dataMarginPrev = []; // Array Baru

$total_sales_now_all = 0;
$total_sales_mr_now = 0;
$total_sales_prev_all = 0;
$total_sales_mr_prev = 0;

$total_margin_all = 0;
$total_margin_mr_now = 0;
$total_margin_mr_prev = 0;

$total_struk_now_all = 0;
$total_struk_mr_now = 0;
$total_struk_prev_all = 0;
$total_struk_mr_prev = 0;

if ($query) {
    while ($row = pg_fetch_assoc($query)) {
        $salesman = $row['kode_salesman'];
        $now      = (float)$row['sales_now'];
        $prev     = (float)$row['sales_prev'];
        $struk    = (int)$row['struk_now'];
        $struk_prev = (int)$row['struk_prev'];
        $margin   = (float)$row['margin_now'];
        $margin_prev = (float)$row['margin_prev'];

        $categories[]   = $salesman;
        $dataNow[]      = $now;
        $dataPrev[]     = $prev;
        $dataStrukNow[] = $struk;
        $dataMarginNow[] = $margin;
        $dataMarginPrev[] = $margin_prev; // Push data

        // Total ALL
        $total_sales_now_all += $now;
        $total_sales_prev_all += $prev;
        $total_margin_all += $margin;
        $total_struk_now_all += $struk;
        $total_struk_prev_all += $struk_prev;

        // Total MR Only
        if ($salesman !== 'HJK') {
            $total_sales_mr_now += $now;
            $total_sales_mr_prev += $prev;
            $total_margin_mr_now += $margin;
            $total_margin_mr_prev += $margin_prev;
            $total_struk_mr_now += $struk;
            $total_struk_mr_prev += $struk_prev;
        }
    }
}

echo json_encode([
    "status" => "success",
    "categories" => $categories,
    "series" => [
        ["name" => "Periode Ini", "data" => $dataNow],
        ["name" => "Periode Lalu", "data" => $dataPrev]
    ],
    "struk_data" => $dataStrukNow,
    "margin_data" => $dataMarginNow,
    "margin_prev_data" => $dataMarginPrev, // Kirim ke Frontend
    "summary" => [
        "total_mr_now" => $total_sales_mr_now,
        "total_mr_prev" => $total_sales_mr_prev,
        "total_margin_mr_now" => $total_margin_mr_now,
        "total_margin_mr_prev" => $total_margin_mr_prev,
        "total_now" => $total_sales_now_all,
        "total_prev" => $total_sales_prev_all,
        "total_margin_all" => $total_margin_all,
        "total_struk_mr" => $total_struk_mr_now,
        "total_struk_mr_prev" => $total_struk_mr_prev,
        "total_struk_all" => $total_struk_now_all,
        "total_struk_all_prev" => $total_struk_prev_all
    ]
]);
