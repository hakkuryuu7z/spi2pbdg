<?php
header('Content-Type: application/json');
require_once '../connection/index.php';

$salesman   = isset($_GET['salesman']) ? $_GET['salesman'] : '';
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

if ($salesman === 'HJK' || $salesman === 'Cabang Lain') {
    $filter_salesman = "(cus_nosalesman IS NULL OR cus_nosalesman = '')";
} else {
    $filter_salesman = "cus_nosalesman = '$salesman'";
}

// --- RUMUS MARGIN (Sama seperti file grafik) ---
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

$sql = "
select
    TRJD_CUS_KODEMEMBER as MEMBERS,
    CUS_NAMAMEMBER as NAMA_MEMBER,
    cus_nosalesman as MR,
    
    count(distinct CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN TRJD_PRDCD END) as PRODUK_BELI,
    count(distinct CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN TRJD_TRANSACTIONTYPE || TRJD_CREATE_DT || TRJD_CREATE_BY || TRJD_CASHIERSTATION || TRJD_TRANSACTIONNO END) as STD,

    -- SALES NOW
    SUM(ROUND(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN
        case
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT / 1.11
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then (TRJD_NOMINALAMT / 1.11) * -1
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then TRJD_NOMINALAMT * -1
            else TRJD_NOMINALAMT * -1
        end
    ELSE 0 END, 0)) as SALES_NOW,

    -- SALES PREV
    SUM(ROUND(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN
        case
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT / 1.11
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' then TRJD_NOMINALAMT
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' then (TRJD_NOMINALAMT / 1.11) * -1
            when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' then TRJD_NOMINALAMT * -1
            else TRJD_NOMINALAMT * -1
        end
    ELSE 0 END, 0)) as SALES_PREV,

    -- MARGIN NOW
    SUM(ROUND(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN
        $margin_formula
    ELSE 0 END, 0)) as MARGIN_NOW,

    -- MARGIN PREV (BARU: Ditambahkan agar modal bisa menampilkan perbandingan margin)
    SUM(ROUND(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN
        $margin_formula
    ELSE 0 END, 0)) as MARGIN_PREV

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
    and $filter_salesman
GROUP BY
    TRJD_CUS_KODEMEMBER,    
    CUS_NAMAMEMBER,
    cus_nosalesman
HAVING 
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$start_date' AND '$end_date' THEN TRJD_NOMINALAMT ELSE 0 END) <> 0
    OR
    SUM(CASE WHEN DATE(TRJD_TRANSACTIONDATE) BETWEEN '$prev_start' AND '$prev_end' THEN TRJD_NOMINALAMT ELSE 0 END) <> 0
ORDER BY
    SALES_NOW DESC
";

$query = pg_query($conn, $sql);
$result = [];

if ($query) {
    while ($row = pg_fetch_assoc($query)) {
        $result[] = $row;
    }
}

echo json_encode(["status" => "success", "data" => $result]);
