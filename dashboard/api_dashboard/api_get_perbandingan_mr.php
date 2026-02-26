<?php
// dashboard/api_get_perbandingan_mr.php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../connection/index.php";

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Error']);
    exit;
}

// 1. Tangkap Filter Tanggal
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 2. Tangkap Filter Status (BARU)
// Jika JS mengirim 'all', kita hitung semua. Jika 'valid' (atau kosong), kita filter batal.
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'valid';

$curr_start = $filter_start;
$curr_end   = $filter_end;

// Logic Kondisi SQL berdasarkan Status
$sql_condition = "";

if ($status_filter == 'valid') {
    // KONDISI ASLI ANDA (Menyaring yang tidak batal / valid saja)
    // Artinya: obi_recid KOSONG atau (TIDAK termasuk B, B1..B4 DAN tidak dimulai huruf kapital regex)
    $sql_condition = " AND (h.obi_recid IS NULL OR (h.obi_recid NOT IN ('B', 'B1', 'B2', 'B3', 'B4') AND h.obi_recid !~ '^[A-Z]')) ";
} 
// Jika status == 'all', $sql_condition tetap kosong (""), jadi semua data terhitung.

// 3. QUERY UTAMA
$sql = "
SELECT
    COALESCE(c.cus_nosalesman, 'NA') as salesman,
    
    -- Total Dokumen
    COUNT(CASE WHEN 
            h.obi_tglpb::date BETWEEN '$curr_start' AND '$curr_end' 
            $sql_condition  -- <--- LOGIC DINAMIS DISINI
          THEN 1 END) as val_curr
FROM
    tbtr_obi_h h
LEFT JOIN
    tbmaster_customer c ON h.obi_kdmember = c.cus_kodemember
WHERE 
    h.obi_tglpb::date BETWEEN '$curr_start' AND '$curr_end'
GROUP BY 1
ORDER BY val_curr DESC -- Urutkan dari yang terbanyak
LIMIT 20
";

$result = pg_query($conn, $sql);

$categories = []; // Nama Salesman
$data_curr = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        // Jika jumlahnya 0, skip agar grafik rapi
        if ($row['val_curr'] == 0) continue;

        $categories[] = $row['salesman'];
        $data_curr[] = (int)$row['val_curr'];
    }
}

echo json_encode([
    'status' => 'success',
    'debug_filter' => $status_filter, // Untuk cek di console/network
    'categories' => $categories,
    'series' => [
        ['name' => 'Jumlah Dokumen', 'data' => $data_curr]
    ]
]);
?>