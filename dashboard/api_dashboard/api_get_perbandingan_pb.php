<?php
// dashboard/api_get_perbandingan_pb.php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../connection/index.php";

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Error']);
    exit;
}

// 1. Tangkap Filter
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 2. Periode Pembanding (Bulan Lalu / Periode Sebelumnya)
$curr_start = $filter_start;
$curr_end   = $filter_end;
$prev_start = date('Y-m-d', strtotime($curr_start . ' -1 month'));
$prev_end   = date('Y-m-d', strtotime($curr_end . ' -1 month'));

// 3. QUERY UTAMA
$sql = "
SELECT
    EXTRACT(DAY FROM obi_tglpb) as hari,
    -- Total Valid (Semua)
    COUNT(CASE WHEN obi_tglpb::date BETWEEN '$curr_start' AND '$curr_end' THEN 1 END) as val_curr,
    COUNT(CASE WHEN obi_tglpb::date BETWEEN '$prev_start' AND '$prev_end' THEN 1 END) as val_prev,
    
    -- Total Batal (Periode INI)
    COUNT(CASE WHEN 
            (obi_recid IN ('B', 'B1', 'B2', 'B3', 'B4') OR obi_recid ~ '^[A-Z]') 
            AND obi_tglpb::date BETWEEN '$curr_start' AND '$curr_end' 
          THEN 1 END) as val_batal,

    -- Total Batal (Periode LALU) -> INI TAMBAHANNYA
    COUNT(CASE WHEN 
            (obi_recid IN ('B', 'B1', 'B2', 'B3', 'B4') OR obi_recid ~ '^[A-Z]') 
            AND obi_tglpb::date BETWEEN '$prev_start' AND '$prev_end' 
          THEN 1 END) as val_batal_prev
FROM
    tbtr_obi_h
WHERE 
    (obi_tglpb::date BETWEEN '$curr_start' AND '$curr_end') OR 
    (obi_tglpb::date BETWEEN '$prev_start' AND '$prev_end')
GROUP BY 1
ORDER BY 1
";

$result = pg_query($conn, $sql);
$db_data = [];

// Variabel Penampung Total
$total_curr = 0;
$total_prev = 0;
$total_batal = 0;
$total_batal_prev = 0; // Penampung baru

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $d = (int)$row['hari'];
        $vc = (int)$row['val_curr'];
        $vp = (int)$row['val_prev'];
        $vb = (int)$row['val_batal'];
        $vbp = (int)$row['val_batal_prev']; // Ambil dari DB

        $db_data[$d] = ['curr' => $vc, 'prev' => $vp];
        
        $total_curr += $vc;
        $total_prev += $vp;
        $total_batal += $vb;
        $total_batal_prev += $vbp;
    }
}

// 4. Query Hari Terakhir vs Kemarin (Tetap sama)
$tgl_terakhir = $curr_end; 
$tgl_kemarin  = date('Y-m-d', strtotime($curr_end . ' -1 day'));

$sql_daily = "
SELECT
    COUNT(CASE WHEN obi_tglpb::date = '$tgl_terakhir' THEN 1 END) as last_day_count,
    COUNT(CASE WHEN obi_tglpb::date = '$tgl_kemarin' THEN 1 END) as day_before_count
FROM tbtr_obi_h
WHERE obi_tglpb::date IN ('$tgl_terakhir', '$tgl_kemarin')
";

$res_daily = pg_query($conn, $sql_daily);
$row_daily = pg_fetch_assoc($res_daily);
$last_day_val = (int)$row_daily['last_day_count'];
$prev_day_val = (int)$row_daily['day_before_count'];


// 5. Normalisasi Data Grafik
$labels = [];
$data_curr = [];
$data_prev = [];

$start = new DateTime($curr_start);
$end   = new DateTime($curr_end);
$end->modify('+1 day');
$interval = new DateInterval('P1D');
$daterange = new DatePeriod($start, $interval, $end);

foreach($daterange as $date){
    $day_num = (int)$date->format('d');
    $labels[] = $date->format('d M');
    
    $val_c = isset($db_data[$day_num]['curr']) ? $db_data[$day_num]['curr'] : 0;
    $val_p = isset($db_data[$day_num]['prev']) ? $db_data[$day_num]['prev'] : 0;

    $data_curr[] = $val_c;
    $data_prev[] = $val_p;
}

echo json_encode([
    'status' => 'success',
    'categories' => $labels,
    'series' => [
        ['name' => 'Bulan Ini', 'data' => $data_curr],
        ['name' => 'Bulan Lalu', 'data' => $data_prev]
    ],
    'totals' => [
        'current' => $total_curr,
        'previous' => $total_prev,
        
        'batal' => $total_batal,
        'batal_prev' => $total_batal_prev, // Kirim ke JS
        
        'last_day' => $last_day_val,
        'day_before' => $prev_day_val
    ]
]);
?>