<?php
// dashboard/api_get_member_beli_produk.php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../connection/index.php';

$db = isset($conn) ? $conn : (isset($pdo) ? $pdo : null);
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit;
}

// Ambil parameter
$start_date  = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date    = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$kecamatan   = isset($_GET['kecamatan'])  ? strtoupper(trim($_GET['kecamatan'])) : '';
$nama_barang = isset($_GET['nama_barang']) ? trim($_GET['nama_barang']) : '';
$nama_barang_safe = str_replace("'", "''", $nama_barang);
$kecamatan_safe   = str_replace("'", "''", $kecamatan);

$str_start = date('Ymd', strtotime($start_date));
$str_end   = date('Ymd', strtotime($end_date));

// Hindari eksekusi jika parameter kosong
if (empty($kecamatan) || empty($nama_barang)) {
    echo json_encode(['status' => 'success', 'data' => []]);
    exit;
}

// Gunakan base query milikmu yang sudah dioptimasi filter langsung di dalam sub-query
$sql = "
SELECT
    dtl_cusno,
    dtl_namamember,
    COUNT(DISTINCT(dtl_tanggal)) AS kunjungan,
    SUM(dtl_qty_pcs) AS qty_in_pcs,
    SUM(dtl_netto) AS dtl_netto,
    SUM(dtl_margin) AS dtl_margin,
    round(SUM(dtl_margin) / NULLIF(SUM(dtl_netto), 0) * 100, 2) AS dtl_margin_persen
FROM
    (
        SELECT
            dtl_tanggal,
            CASE WHEN dtl_rtype = 'S' THEN dtl_netto - dtl_hpp ELSE ( dtl_netto - dtl_hpp ) * - 1 END AS dtl_margin,
            CASE WHEN dtl_rtype = 'R' THEN ( dtl_netto * - 1 ) ELSE dtl_netto END AS dtl_netto,
            dtl_qty_pcs,
            dtl_cusno,
            dtl_namamember
        FROM
            (
                SELECT
                    date_trunc('day', trjd_transactiondate) AS dtl_tanggal,
                    trjd_transactiontype AS dtl_rtype,
                    trjd_cus_kodemember AS dtl_cusno,
                    cus_namamember AS dtl_namamember,
                    CASE WHEN prd_unit = 'KG' AND prd_frac = 1000 THEN trjd_quantity ELSE trjd_quantity * prd_frac END AS dtl_qty_pcs,
                    CASE WHEN trjd_flagtax1 = 'Y' AND trjd_create_by IN ( 'IDM', 'OMI', 'BKL' ) THEN trjd_nominalamt * 11.1 / 10 ELSE trjd_nominalamt END AS dtl_gross,
                    CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END ELSE CASE WHEN coalesce(tko_kodesbu, 'z') IN ( 'O', 'I' ) THEN CASE WHEN tko_tipeomi IN ( 'HE', 'HG' ) THEN trjd_nominalamt - ( CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END ) ELSE trjd_nominalamt END ELSE trjd_nominalamt - ( CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END END ) END END AS dtl_netto,
                    CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt - ( CASE WHEN prd_markupstandard IS NULL THEN ( 5 * trjd_nominalamt ) / 100 ELSE ( prd_markupstandard * trjd_nominalamt ) / 100 END ) END ELSE ( trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END ) * trjd_baseprice END AS dtl_hpp
                FROM
                    tbtr_jualdetail
                    LEFT JOIN tbmaster_prodmast ON trjd_prdcd = prd_prdcd
                    LEFT JOIN tbmaster_tokoigr ON trjd_cus_kodemember = tko_kodecustomer
                    LEFT JOIN tbmaster_customer ON trjd_cus_kodemember = cus_kodemember
                    LEFT JOIN tbmaster_customercrm ON trjd_cus_kodemember = crm_kodemember
                WHERE 
                    to_char(trjd_transactiondate, 'yyyymmdd') BETWEEN '$str_start' AND '$str_end'
                    AND prd_deskripsipanjang = '$nama_barang_safe'
                    AND UPPER(coalesce(crm_kecamatan_usaha, 'LAIN-LAIN')) = '$kecamatan'
            ) sls
    ) z
GROUP BY 
    z.dtl_cusno,
    z.dtl_namamember
HAVING 
    coalesce(SUM(dtl_netto),0) <> 0 
ORDER BY 
    qty_in_pcs DESC
";

$result_data = [];

try {
    if ($db instanceof PDO) {
        $stmt = $db->query($sql);
        if ($stmt) $result_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $query_run = pg_query($db, $sql);
        if ($query_run) {
            $fetch = pg_fetch_all($query_run);
            if ($fetch !== false) $result_data = $fetch;
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'data'   => $result_data
]);
