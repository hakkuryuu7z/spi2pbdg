<?php
header('Content-Type: application/json');
require_once "../connection/index.php";

$response = [];

try {
    $sql = <<<SQL
WITH RankedPO AS (
    SELECT
        tpod_prdcd,
        tpod_qtypo,
        tpod_nopo,
        tpoh_kodesupplier,
        tms.sup_namasupplier,
        DATE_TRUNC('DAY', tpoh_tglpo + (tpoh_jwpb || ' days')::interval) AS TGL_PO_EXPIRED,
        ROW_NUMBER() OVER(PARTITION BY tpod_prdcd ORDER BY DATE_TRUNC('DAY', tpoh_tglpo + (tpoh_jwpb || ' days')::interval) DESC) as rn
    FROM
        tbtr_po_d
    LEFT JOIN tbtr_po_h ON tpoh_nopo = tpod_nopo
    LEFT JOIN tbmaster_supplier tms ON tms.sup_kodesupplier = tpoh_kodesupplier
    WHERE
        (tpoh_recordid IS NULL OR tpoh_recordid = 'X') AND
        DATE_TRUNC('DAY', tpoh_tglpo + (tpoh_jwpb || ' days')::interval) >= CURRENT_DATE
)
SELECT 
    sub.PRD_KODEDIVISI "DIVISI",
    sub.PRD_KODEDEPARTEMENT "DEPARTEMEN",
    sub.PRD_KODEKATEGORIBARANG "KATEGORI",
    sub.PRD_PRDCD "PLU",
    sub.FLAG "FLAG",
    sub.PRD_DESKRIPSIPANJANG "DESKRIPSI",
    sub.PRD_UNIT "UNIT",
    sub.PRD_FRAC "FRAC",
    sub.PRD_KODETAG "TAG",
    CASE
        WHEN sub.st_sales <> 0 AND sub.st_sales IS NOT NULL
        THEN ROUND((((sub.st_saldoawal + sub.ST_SALDOAKHIR)::numeric / sub.st_sales) / 2.0) * EXTRACT(DAY FROM CURRENT_DATE), 0)
        ELSE 0
    END AS "DSI",
    sub.ST_SALDOAKHIR "STOCK",
    sub."AVG QTY 3 BULAN",
    sub."AVG SALES 3 BULAN",
    po_terbaru.tpod_nopo "PO OUT TERBARU",
    sub.SUP_KODESUPPLIER "KODE SUPPLIER",
    sub.SUP_NAMASUPPLIER "SUPPLIER",
    sub.BTB_terakhir "BPB TERAKHIR"
FROM (
    SELECT 
        base.PRD_KODEDIVISI,
        base.PRD_KODEDEPARTEMENT,
        base.PRD_KODEKATEGORIBARANG,
        base.PRD_PRDCD,
        f.FLAG,
        base.PRD_DESKRIPSIPANJANG,
        base.PRD_UNIT,
        base.PRD_FRAC,
        base.PRD_KODETAG,
        base.st_saldoakhir,
        base.st_saldoawal, 
        base.st_sales,     
        (sales.s1 + sales.s2 + sales.s3) / 3 AS "AVG QTY 3 BULAN",
        (sales.rp1 + sales.rp2 + sales.rp3) / 3 AS "AVG SALES 3 BULAN",
        sup.SUP_KODESUPPLIER,
        sup.SUP_NAMASUPPLIER,
        btb.BTB_terakhir,
        base.prd_avgcost 
    FROM (
        SELECT 
            prd.PRD_KODEDIVISI,
            prd.PRD_KODEDEPARTEMENT,
            prd.PRD_KODEKATEGORIBARANG,
            prd.PRD_PRDCD,
            prd.PRD_DESKRIPSIPANJANG,
            prd.PRD_UNIT,
            prd.PRD_FRAC,
            prd.PRD_AVGCOST,
            COALESCE(prd.PRD_KODETAG, ' ') AS PRD_KODETAG,
            COALESCE(stk.st_saldoakhir, 0) AS st_saldoakhir,
            COALESCE(stk.st_saldoawal, 0) AS st_saldoawal, 
            COALESCE(stk.st_sales, 0) AS st_sales          
        FROM tbmaster_prodmast prd
        LEFT JOIN tbmaster_stock stk ON prd.prd_prdcd = stk.st_prdcd AND stk.st_lokasi = '01'
        WHERE prd.prd_prdcd LIKE '%0'
          AND COALESCE(prd.PRD_KODETAG, ' ') NOT IN ('A', 'R', 'N', 'O', 'T', 'H', 'X', 'I', 'G')
          AND prd.PRD_KODEDIVISI NOT IN ('4','5','6')
          AND prd.PRD_KODEDEPARTEMENT NOT IN ('42', '30', '29', '27')
          AND prd.PRD_RECORDID IS NULL
    ) AS base
    LEFT JOIN (
        SELECT 
            SLS_PRDCD,
            COALESCE(SLS_QTY_10, 0) AS s1,
            COALESCE(SLS_QTY_08, 0) AS s2,
            COALESCE(SLS_QTY_09, 0) AS s3,
            COALESCE(SLS_RPH_10, 0) AS rp1,
            COALESCE(SLS_RPH_08, 0) AS rp2,
            COALESCE(SLS_RPH_09, 0) AS rp3
        FROM tbtr_salesbulanan
    ) AS sales ON base.PRD_PRDCD = sales.SLS_PRDCD
    LEFT JOIN (
        SELECT 
            PRD_PRDCD AS PLU_FLAG,
            CASE
                WHEN PRD_FLAGOMI = 'Y' AND PRD_FLAGIGR = 'Y' THEN 'IGR+OMI'
                WHEN PRD_FLAGOMI = 'Y' AND PRD_FLAGIGR = 'N' THEN 'OMI ONLY'
                WHEN PRD_FLAGIGR = 'Y' AND PRD_FLAGOMI = 'N' THEN 'IGR ONLY'
            END AS FLAG
        FROM TBMASTER_PRODMAST
        WHERE PRD_FLAGOMI <> 'N' OR PRD_FLAGIGR <> 'N'
    ) AS f ON base.PRD_PRDCD = f.PLU_FLAG
    LEFT JOIN (
        SELECT MSTD_PRDCD, SUP_KODESUPPLIER, SUP_NAMASupplier
        FROM (
            SELECT 
                mstd.MSTD_PRDCD,
                sup.SUP_KODESUPPLIER,
                sup.SUP_NAMASUPPLIER,
                ROW_NUMBER() OVER(PARTITION BY mstd.MSTD_PRDCD ORDER BY mstd.MSTD_NODOC DESC) as rn
            FROM TBTR_MSTRAN_D mstd
            LEFT JOIN TBMASTER_SUPPLIER sup ON mstd.MSTD_KODESUPPLIER = sup.SUP_KODESUPPLIER
            WHERE mstd.MSTD_RECORDID IS NULL AND mstd.MSTD_TYPETRN = 'B'
        ) abcd
        WHERE rn = 1
    ) AS sup ON base.PRD_PRDCD = sup.MSTD_PRDCD
    LEFT JOIN (
        SELECT 
            MSTD_PRDCD,
            MAX(DATE_TRUNC('day', MSTD_TGLDOC)) AS BTB_terakhir
        FROM tbtr_mstran_d
        WHERE MSTD_TYPETRN IN ('B', 'I')
        GROUP BY MSTD_PRDCD
    ) AS btb ON base.PRD_PRDCD = btb.MSTD_PRDCD
) AS sub
LEFT JOIN (
    SELECT * FROM RankedPO WHERE rn = 1
) AS po_terbaru ON sub.PRD_PRDCD = po_terbaru.tpod_prdcd
WHERE 
    sub.flag IS NOT NULL 
    AND sub.prd_avgcost <> '0'
ORDER BY 
    sub.ST_SALDOAKHIR ASC
SQL;

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
