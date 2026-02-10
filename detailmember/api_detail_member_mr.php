<?php
header('Content-Type: application/json');
include "../connection/index.php";

if (!isset($_GET['mr']) || empty($_GET['mr'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter MR (CUS_NOSALESMAN) dibutuhkan.']);
    exit();
}

$mr_filter = $_GET['mr'];

// Query baru yang sudah diperbaiki
$sql = "
    WITH detail_struk_cte AS (
    SELECT 
        sls.trjd_cus_kodemember AS dtl_cusno,
        date_trunc('day', sls.trjd_transactiondate) AS dtl_tanggal,
        -- LOGIC MENGHITUNG NETTO (Menggabungkan logic pajak dan retur)
        (
            CASE 
                -- Logic PPN: Jika Tax2=Y dan bukan OMI/BKL, hitung DPP
                WHEN (sls.trjd_flagtax2 = 'Y' AND sls.trjd_create_by NOT IN ('OMI', 'BKL')) 
                THEN (sls.trjd_nominalamt / 11.1) * 10
                ELSE sls.trjd_nominalamt
            END 
            * CASE 
                -- Logic Retur: Jika bukan Sales (S), kalikan -1
                WHEN sls.trjd_transactiontype = 'S' THEN 1 
                ELSE -1 
            END
        ) AS dtl_netto
    FROM 
        tbtr_jualdetail sls
    WHERE 
        sls.trjd_recordid IS NULL 
        AND sls.trjd_quantity <> 0
        AND date_trunc('day', sls.trjd_transactiondate) <= (CURRENT_DATE - 1)
)
SELECT 
    C.CUS_KODEMEMBER,
    C.CUS_NAMAMEMBER,
    C.CUS_TLPMEMBER,
    C.CUS_ALAMATMEMBER6 || ' ' || C.CUS_ALAMATMEMBER7 || ' ' || C.CUS_ALAMATMEMBER8 AS ALAMAT,
    CASE 
        WHEN C.CUS_TGLMULAI IS NULL THEN 'NO TRANSAKSI'
        ELSE 'AKTIF'
    END AS STATUS,
    C.CUS_TGLREGISTRASI,
    C.CUS_NOSALESMAN,
    C.CUS_NAMAKTP,
    COALESCE(SUM(S.dtl_netto), 0) as sales, -- Tambahkan COALESCE agar jika null jadi 0
    MAX(S.dtl_tanggal) AS tgl_terakhir_belanja
FROM
    TBMASTER_CUSTOMER AS C 
LEFT JOIN 
    detail_struk_cte AS S ON C.CUS_KODEMEMBER = S.DTL_CUSNO
WHERE 
    C.CUS_NOSALESMAN = $1 
    AND C.CUS_KODEIGR = '2P'
GROUP BY 
    C.CUS_KODEMEMBER,
    C.CUS_NAMAMEMBER,
    C.CUS_TLPMEMBER,
    ALAMAT, 
    STATUS, 
    C.CUS_TGLREGISTRASI,
    C.CUS_NOSALESMAN,
    C.CUS_NAMAKTP
ORDER BY
    tgl_terakhir_belanja ASC;
";

$queryResult = pg_query_params($conn, $sql, [$mr_filter]);
$data = [];
if ($queryResult) {
    $data = pg_fetch_all($queryResult);
}


echo json_encode(['data' => $data ?: []]);
