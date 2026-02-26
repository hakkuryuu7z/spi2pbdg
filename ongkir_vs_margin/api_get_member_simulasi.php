<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../connection/index.php';

$keyword    = isset($_GET['keyword']) ? pg_escape_string($conn, $_GET['keyword']) : '';
$jarak      = isset($_GET['jarak']) ? pg_escape_string($conn, $_GET['jarak']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit      = 10;
$offset     = ($page - 1) * $limit;

$where_keyword = "";
if (!empty($keyword)) {
    $where_keyword = "AND (c.cus_kodemember ILIKE '%$keyword%' OR c.cus_namamember ILIKE '%$keyword%')";
}

$where_jarak = "";
if ($jarak !== '') {
    $where_jarak = "AND c.cus_jarak = '$jarak'";
}

// OPTIMASI: CTE merangkum transaksi berdasarkan Index Tanggal terlebih dahulu, baru difilter.
$sql = "
WITH TransaksiBase AS (
    SELECT
        jd.trjd_cus_kodemember AS cusno,
        jd.trjd_transactiondate,
        jd.trjd_create_by,
        jd.trjd_transactionno,
        jd.trjd_transactiontype,
        jd.trjd_nominalamt,
        jd.trjd_quantity,
        jd.trjd_baseprice,
        jd.trjd_flagtax1,
        jd.trjd_flagtax2,
        jd.trjd_divisioncode,
        jd.trjd_division,
        p.prd_kodetag,
        p.prd_ppn,
        p.prd_markupstandard,
        p.prd_unit,
        t.tko_kodesbu,
        t.tko_tipeomi
    FROM tbtr_jualdetail jd
    LEFT JOIN tbmaster_prodmast p ON jd.trjd_prdcd = p.prd_prdcd
    LEFT JOIN tbmaster_tokoigr t ON jd.trjd_cus_kodemember = t.tko_kodecustomer
    -- MENGGUNAKAN INDEX TANGGAL LANGSUNG AGAR SUPER CEPAT
    WHERE jd.trjd_transactiondate >= '$start_date 00:00:00' 
      AND jd.trjd_transactiondate <= '$end_date 23:59:59'
      AND jd.trjd_recordid IS NULL
),
KalkulasiDetail AS (
    SELECT
        cusno,
        trjd_transactiondate, trjd_create_by, trjd_transactionno, trjd_transactiontype,
        
        -- RUMUS GROSS
        CASE WHEN trjd_transactiontype = 'S' THEN
            CASE WHEN trjd_flagtax1 = 'Y' AND trjd_create_by IN ('IDM', 'OMI', 'BKL') THEN trjd_nominalamt * 11.1 / 10 ELSE trjd_nominalamt END
        ELSE
            (CASE WHEN trjd_flagtax1 = 'Y' AND trjd_create_by IN ('IDM', 'OMI', 'BKL') THEN trjd_nominalamt * 11.1 / 10 ELSE trjd_nominalamt END) * -1
        END AS dtl_gross,
        
        -- RUMUS NETTO
        CASE WHEN trjd_transactiontype = 'R' THEN -1 ELSE 1 END * (
            CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN trjd_nominalamt 
            ELSE 
                CASE WHEN coalesce(tko_kodesbu, 'z') IN ('O', 'I') THEN 
                    CASE WHEN tko_tipeomi IN ('HE', 'HG') THEN 
                        trjd_nominalamt - ( CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END ) 
                    ELSE trjd_nominalamt END 
                ELSE 
                    trjd_nominalamt - ( CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END END ) 
                END 
            END
        ) AS dtl_netto,
        
        -- RUMUS HPP
        CASE WHEN trjd_transactiontype = 'R' THEN -1 ELSE 1 END *
        (
            CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN 
                trjd_nominalamt - ( CASE WHEN prd_markupstandard IS NULL THEN (5 * trjd_nominalamt) / 100 ELSE (prd_markupstandard * trjd_nominalamt) / 100 END ) 
            ELSE 
                (trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END) * trjd_baseprice 
            END
        ) AS dtl_hpp
    FROM TransaksiBase
),
AgregasiMember AS (
    -- GABUNGKAN SALES PER MEMBER (Sangat memperingan kerja server)
    SELECT 
        cusno,
        COUNT(DISTINCT(trjd_transactiondate::date || trjd_create_by || trjd_transactionno || trjd_transactiontype)) AS struk,
        SUM(dtl_gross) AS sum_gross,
        SUM(dtl_netto) AS sum_netto,
        SUM(dtl_netto - dtl_hpp) AS sum_margin
    FROM KalkulasiDetail
    GROUP BY cusno
),
OngkirMember AS (
    -- AMBIL TOTAL ONGKIR PER MEMBER DI PERIODE TERSEBUT
    SELECT 
        obi.obi_kdmember AS cusno,
        SUM(COALESCE(klk.pot_ongkir, 0)) AS total_ongkir
    FROM tbtr_obi_h obi
    LEFT JOIN payment_klikigr klk ON obi.obi_nopb = klk.no_pb
    WHERE obi.obi_tglstruk >= '$start_date 00:00:00' AND obi.obi_tglstruk <= '$end_date 23:59:59'
    GROUP BY obi.obi_kdmember
)
-- TAHAP AKHIR: BARU FILTER BERDASARKAN MASTER CUSTOMER
SELECT 
    a.cusno AS dtl_cusno,
    c.cus_namamember AS dtl_namamember,
    c.cus_nosalesman AS mr,
    c.cus_jarak AS jarak,
    a.struk,
    a.sum_gross AS dtl_gross,
    round(a.sum_gross / NULLIF(a.struk, 0), 2) AS avg_gross_per_struk,
    round(a.sum_margin / NULLIF(a.sum_netto, 0) * 100, 2) AS dtl_margin_persen,
    COALESCE(o.total_ongkir, 0) AS total_ongkir,
    
    COUNT(*) OVER() AS total_count -- Hitung untuk Pagination
FROM AgregasiMember a
INNER JOIN tbmaster_customer c ON a.cusno = c.cus_kodemember
LEFT JOIN OngkirMember o ON a.cusno = o.cusno
WHERE c.cus_kodeigr = '2P'
  AND a.sum_netto <> 0
  $where_keyword
  $where_jarak
ORDER BY a.sum_gross DESC
LIMIT $limit OFFSET $offset;
";

$query = pg_query($conn, $sql);
$data = [];
$total_count = 0;

if ($query) {
    while ($row = pg_fetch_assoc($query)) {
        $total_count = $row['total_count'];
        unset($row['total_count']);
        $data[] = $row;
    }

    $total_pages = ceil($total_count / $limit);
    echo json_encode([
        "status" => "success",
        "data" => $data,
        "pagination" => [
            "total_rows" => $total_count,
            "total_pages" => $total_pages,
            "current_page" => $page
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
}
