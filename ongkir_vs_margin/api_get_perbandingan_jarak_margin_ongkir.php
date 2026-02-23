<?php
include "../connection/index.php"; // Sesuaikan path koneksi database-mu

// Ambil parameter tanggal, default ke bulan berjalan jika kosong
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Pastikan aman dari SQL Injection dasar
$start_date = pg_escape_string($conn, $start_date);
$end_date   = pg_escape_string($conn, $end_date);

$sql = "
WITH daily_sales AS (
    SELECT 
        sls.trjd_transactiontype,
        sls.trjd_cus_kodemember,
        COALESCE(cus.cus_jarak, 0) AS cus_jarak,
        
        -- Logic Netto
        CASE 
            WHEN sls.trjd_transactiontype = 'S' THEN 
                CASE WHEN sls.trjd_flagtax1 = 'Y' AND sls.trjd_flagtax2 = 'Y' AND sls.trjd_create_by NOT IN ('IDM', 'OMI', 'BKL') 
                     THEN sls.trjd_nominalamt / 11.1 * 10 
                     ELSE sls.trjd_nominalamt 
                END
            ELSE 
                (CASE WHEN sls.trjd_flagtax1 = 'Y' AND sls.trjd_flagtax2 = 'Y' AND sls.trjd_create_by NOT IN ('IDM', 'OMI', 'BKL') 
                      THEN sls.trjd_nominalamt / 11.1 * 10 
                      ELSE sls.trjd_nominalamt 
                 END) * -1
        END AS netto,

        -- Logic HPP
        CASE 
            WHEN sls.trjd_transactiontype = 'S' THEN
                CASE WHEN prd.prd_unit = 'KG' THEN sls.trjd_quantity * sls.trjd_baseprice / 1000
                     ELSE sls.trjd_quantity * sls.trjd_baseprice 
                END
            ELSE 
                (CASE WHEN prd.prd_unit = 'KG' THEN sls.trjd_quantity * sls.trjd_baseprice / 1000
                      ELSE sls.trjd_quantity * sls.trjd_baseprice 
                 END) * -1
        END AS hpp
    FROM (
        SELECT trjd_transactiontype, trjd_transactiondate, trjd_cus_kodemember, trjd_nominalamt, trjd_flagtax1, trjd_flagtax2, trjd_create_by, trjd_quantity, trjd_baseprice, trjd_prdcd 
        FROM tbtr_jualdetail 
        WHERE DATE(trjd_transactiondate) BETWEEN '$start_date' AND '$end_date'
          AND trjd_recordid IS NULL AND trjd_cus_kodemember IS NOT NULL
        UNION ALL
        SELECT trjd_transactiontype, trjd_transactiondate, trjd_cus_kodemember, trjd_nominalamt, trjd_flagtax1, trjd_flagtax2, trjd_create_by, trjd_quantity, trjd_baseprice, trjd_prdcd 
        FROM tbtr_jualdetail_interface 
        WHERE DATE(trjd_transactiondate) BETWEEN '$start_date' AND '$end_date'
          AND trjd_recordid IS NULL AND trjd_cus_kodemember IS NOT NULL
    ) sls
    LEFT JOIN tbmaster_customer cus ON sls.trjd_cus_kodemember = cus.cus_kodemember
    LEFT JOIN tbmaster_prodmast prd ON sls.trjd_prdcd = prd.prd_prdcd
),
sales_summary AS (
    SELECT 
        CASE 
            WHEN cus_jarak <= 5 THEN '5'
            WHEN cus_jarak <= 10 THEN '10'
            WHEN cus_jarak <= 15 THEN '15'
            WHEN cus_jarak <= 20 THEN '20'
            ELSE '>20'
        END AS range_jarak,
        CASE 
            WHEN cus_jarak <= 5 THEN 1 WHEN cus_jarak <= 10 THEN 2 WHEN cus_jarak <= 15 THEN 3 WHEN cus_jarak <= 20 THEN 4 ELSE 5
        END AS sort_order,
        COUNT(DISTINCT trjd_cus_kodemember) AS jml_mm,
        SUM(netto) AS total_netto,
        SUM(netto - hpp) AS total_margin
    FROM daily_sales
    GROUP BY 1, 2
),
ongkir_summary AS (
    SELECT 
        CASE 
            WHEN COALESCE(cus.cus_jarak, 0) <= 5 THEN '5'
            WHEN COALESCE(cus.cus_jarak, 0) <= 10 THEN '10'
            WHEN COALESCE(cus.cus_jarak, 0) <= 15 THEN '15'
            WHEN COALESCE(cus.cus_jarak, 0) <= 20 THEN '20'
            ELSE '>20'
        END AS range_jarak,
        SUM(COALESCE(klk.pot_ongkir, 0)) AS total_ongkir
    FROM tbtr_obi_h obi
    LEFT JOIN tbmaster_customer cus ON obi.obi_kdmember = cus.cus_kodemember
    LEFT JOIN payment_klikigr klk ON obi.obi_nopb = klk.no_pb 
    WHERE DATE(obi.obi_tglstruk) BETWEEN '$start_date' AND '$end_date'
      AND obi.obi_recid = '6' 
    GROUP BY 1
)
SELECT 
    s.range_jarak AS jarak,
    s.jml_mm AS juml_mm,
    ROUND(s.total_netto) AS member_belanja,
    ROUND(s.total_margin) AS margin,
    COALESCE(o.total_ongkir, 0) AS ongkir,
    ROUND(s.total_margin - COALESCE(o.total_ongkir, 0)) AS net_margin,
    CASE WHEN s.jml_mm > 0 THEN ROUND(s.total_netto / s.jml_mm) ELSE 0 END AS avg_sales,
    CASE WHEN s.jml_mm > 0 THEN ROUND(s.total_margin / s.jml_mm) ELSE 0 END AS avg_mrg,
    CASE WHEN s.total_netto <> 0 THEN ROUND((s.total_margin / s.total_netto) * 100, 2) ELSE 0 END AS rasio,
    CASE WHEN s.total_netto <> 0 THEN ROUND(((s.total_margin - COALESCE(o.total_ongkir, 0)) / s.total_netto) * 100, 2) ELSE 0 END AS rasio_net
FROM sales_summary s
LEFT JOIN ongkir_summary o ON s.range_jarak = o.range_jarak
WHERE s.total_netto <> 0 
ORDER BY s.sort_order ASC;
";

$query = pg_query($conn, $sql);
$response = [];

if ($query && pg_num_rows($query) > 0) {
    while ($row = pg_fetch_assoc($query)) {
        $response[] = [
            'jarak'          => $row['jarak'],
            'juml_mm'        => (int) $row['juml_mm'],
            'rasio'          => (float) $row['rasio'],
            'rasio_net'      => (float) $row['rasio_net'], // Tambahan Baru

            'member_belanja' => number_format($row['member_belanja'], 0, ',', '.'),
            'margin'         => number_format($row['margin'], 0, ',', '.'),
            'ongkir'         => number_format($row['ongkir'], 0, ',', '.'),

            // Tambahan Baru: Jika minus, format number tetap jalan tapi ada tanda "-"
            'net_margin'     => number_format($row['net_margin'], 0, ',', '.'),

            'avg_sales'      => number_format($row['avg_sales'], 0, ',', '.'),
            'avg_mrg'        => number_format($row['avg_mrg'], 0, ',', '.')
        ];
    }
}
header('Content-Type: application/json');
echo json_encode(['status' => !empty($response) ? 'success' : 'empty', 'data' => $response]);
