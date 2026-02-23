<?php
// dashboard/api_get_sales_produk_perkecamatan.php
header('Content-Type: application/json');

// 1. Matikan Error Display agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../connection/index.php';

// 2. DETEKSI VARIABEL KONEKSI (Cari $conn atau $pdo)
$db = null;
if (isset($conn)) {
    $db = $conn;
} elseif (isset($pdo)) {
    $db = $pdo;
} else {
    // Jika nama variabel di file connection/index.php bukan $conn atau $pdo
    echo json_encode(['status' => 'error', 'message' => 'Variabel koneksi database tidak ditemukan. Cek nama variabel di connection/index.php']);
    exit;
}

// 3. Ambil Parameter Tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');

// Format tanggal untuk PostgreSQL (YYYYMMDD)
$str_start = date('Ymd', strtotime($start_date));
$str_end   = date('Ymd', strtotime($end_date));

// 4. Query SQL (PostgreSQL)
$sql = "
    SELECT
        z.dtl_kecamatan,
        z.dtl_prdcd,
        z.dtl_nama_barang,
        COUNT(DISTINCT(z.dtl_struk))    AS jumlah_transaksi,
        SUM(z.dtl_qty_pcs)              AS total_qty_pcs,
        SUM(ROUND(z.dtl_gross))                AS total_gross,
        SUM(ROUND(z.dtl_netto))                AS total_netto,
        SUM(ROUND(z.dtl_margin))               AS total_margin
    FROM
        (
            SELECT
                dtl_rtype,
                dtl_tanggal,
                dtl_struk,
                dtl_prdcd,
                dtl_nama_barang,
                dtl_qty_pcs,
                CASE WHEN dtl_rtype = 'S' THEN dtl_gross ELSE dtl_gross * - 1 END AS dtl_gross,
                CASE WHEN dtl_rtype = 'R' THEN ( dtl_netto * - 1 ) ELSE dtl_netto END AS dtl_netto,
                CASE WHEN dtl_rtype = 'R' THEN ( dtl_hpp * - 1 ) ELSE dtl_hpp END AS dtl_hpp,
                CASE WHEN dtl_rtype = 'S' THEN dtl_netto - dtl_hpp ELSE ( dtl_netto - dtl_hpp ) * - 1 END AS dtl_margin,
                dtl_kecamatan
            FROM
                (
                    SELECT
                        date_trunc('day', trjd_transactiondate)          AS dtl_tanggal,
                        to_char(trjd_transactiondate, 'yyyymmdd') || trjd_create_by || trjd_transactionno || trjd_transactiontype AS dtl_struk,
                        substr(trjd_prdcd, 1, length(trjd_prdcd)-1) || '0' AS dtl_prdcd,
                        prd_deskripsipanjang                             AS dtl_nama_barang,
                        trjd_transactiontype                             AS dtl_rtype,
                        UPPER(coalesce(crm_kecamatan_usaha, 'LAIN-LAIN'))       AS dtl_kecamatan,
                        CASE WHEN prd_unit = 'KG' AND prd_frac = 1000 THEN trjd_quantity ELSE trjd_quantity * prd_frac END AS dtl_qty_pcs,
                        CASE WHEN trjd_flagtax1 = 'Y' AND trjd_create_by IN ( 'IDM', 'OMI', 'BKL' ) THEN trjd_nominalamt * 11.1 / 10 ELSE trjd_nominalamt END AS dtl_gross,
                        CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END ELSE CASE WHEN coalesce(tko_kodesbu, 'z') IN ( 'O', 'I' ) THEN CASE WHEN tko_tipeomi IN ( 'HE', 'HG' ) THEN trjd_nominalamt - ( CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END ) ELSE trjd_nominalamt END ELSE trjd_nominalamt - ( CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END END ) END END AS dtl_netto,
                        CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt - ( CASE WHEN prd_markupstandard IS NULL THEN ( 5 * trjd_nominalamt ) / 100 ELSE ( prd_markupstandard * trjd_nominalamt ) / 100 END ) END ELSE ( trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END ) * trjd_baseprice END AS dtl_hpp
                    FROM
                        tbtr_jualdetail
                        LEFT JOIN tbmaster_prodmast ON trjd_prdcd = prd_prdcd
                        LEFT JOIN tbmaster_tokoigr ON trjd_cus_kodemember = tko_kodecustomer
                        LEFT JOIN tbmaster_customercrm ON trjd_cus_kodemember = crm_kodemember AND crm_kodeigr = '2P'
                        LEFT JOIN tbmaster_divisi ON trjd_division = div_kodedivisi
                ) sls
        ) z
    WHERE
        to_char(dtl_tanggal, 'yyyymmdd') BETWEEN '$str_start' and '$str_end'
    GROUP BY
        z.dtl_kecamatan,
        z.dtl_prdcd,
        z.dtl_nama_barang
    HAVING 
        coalesce(SUM(dtl_netto),0) <> 0
    ORDER BY
        z.dtl_kecamatan ASC,
        total_netto DESC
";

// 5. EKSEKUSI QUERY (Support PDO & Native PostgreSQL)
$result_data = [];

try {
    // Jika menggunakan PDO
    if ($db instanceof PDO) {
        $stmt = $db->query($sql);
        if (!$stmt) {
            throw new Exception(print_r($db->errorInfo(), true));
        }
        $result_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Jika menggunakan Native PostgreSQL (pg_connect)
    else {
        // Cek apakah $db adalah resource atau object PgSql\Connection (PHP 8.1+)
        $is_pg = is_resource($db) || (class_exists('PgSql\Connection') && $db instanceof PgSql\Connection);

        if ($is_pg) {
            $query_run = pg_query($db, $sql);
            if (!$query_run) {
                throw new Exception(pg_last_error($db));
            }
            $result_data = pg_fetch_all($query_run);
            if ($result_data === false) $result_data = []; // Handle jika kosong
        } else {
            throw new Exception("Tipe koneksi database tidak dikenali (Bukan PDO atau pg_connect).");
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// 6. OLAH DATA (PHP Processing)
$data_by_kecamatan = [];
$global_total = [
    'sales' => 0,
    'margin' => 0,
    'qty' => 0
];

if (!empty($result_data)) {
    foreach ($result_data as $row) {
        $kec = $row['dtl_kecamatan'];

        if (!isset($data_by_kecamatan[$kec])) {
            $data_by_kecamatan[$kec] = [
                'name' => $kec,
                'total_sales' => 0,
                'total_margin' => 0,
                'total_trx' => 0,
                'total_qty' => 0,
                'products' => []
            ];
        }

        $sales  = floatval($row['total_netto']);
        $margin = floatval($row['total_margin']);
        $trx    = intval($row['jumlah_transaksi']);
        $qty    = floatval($row['total_qty_pcs']);

        $data_by_kecamatan[$kec]['total_sales']  += $sales;
        $data_by_kecamatan[$kec]['total_margin'] += $margin;
        $data_by_kecamatan[$kec]['total_trx']    += $trx;
        $data_by_kecamatan[$kec]['total_qty']    += $qty;

        $global_total['sales']  += $sales;
        $global_total['margin'] += $margin;
        $global_total['qty']    += $qty;

        $data_by_kecamatan[$kec]['products'][] = [
            'name'   => $row['dtl_nama_barang'],
            'code'   => $row['dtl_prdcd'],
            'sales'  => $sales,
            'qty'    => $qty,
            'trx'    => $trx,
            'margin' => $margin
        ];
    }
}

// 7. SORTING & RANKING
$final_data = [];
foreach ($data_by_kecamatan as $kec_name => $kec_data) {
    // Average Sales per Trx
    $avg_sales = ($kec_data['total_trx'] > 0) ? ($kec_data['total_sales'] / $kec_data['total_trx']) : 0;

    // Sort Top Qty
    $products_by_qty = $kec_data['products'];
    usort($products_by_qty, function ($a, $b) {
        return $b['qty'] <=> $a['qty'];
    });
    // [UBAH DISINI] Ambil 100 produk teratas (bukan 5) agar bisa discroll
    $top_qty = array_slice($products_by_qty, 0, 100);

    // Sort Top Sales
    $products_by_sales = $kec_data['products'];
    usort($products_by_sales, function ($a, $b) {
        return $b['sales'] <=> $a['sales'];
    });
    // [UBAH DISINI] Ambil 100 produk teratas
    $top_sales = array_slice($products_by_sales, 0, 100);

    // Chart Data tetap ambil 10 atau 15 agar grafik tidak terlalu padat
    $chart_data = array_slice($products_by_sales, 0, 100);

    $final_data[] = [
        'kecamatan'    => $kec_name,
        'summary'      => [
            'sales'      => $kec_data['total_sales'],
            'margin'     => $kec_data['total_margin'],
            'trx'        => $kec_data['total_trx'],
            'avg_sales'  => $avg_sales
        ],
        'top_products_qty'   => $top_qty,
        'top_products_sales' => $top_sales,
        'chart_data'         => $chart_data
    ];
}

// 8. OUTPUT JSON
echo json_encode([
    'status' => 'success',
    'global' => $global_total,
    'data'   => $final_data
]);
