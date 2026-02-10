<?php
// dashboard/api_get_perbandingan_pb_mr.php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../connection/index.php";

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Error']);
    exit;
}

// 1. Tangkap Filter Range (Untuk Kolom TOTAL & VALID RANGE)
$tgl_start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$tgl_end   = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// 2. Tentukan Tanggal HARI INI (Realtime System)
// Ini agar kolom "PB Hari Ini" selalu update sesuai tanggal server hari ini, bukan filter
$current_date = date('Y-m-d');

// Query Agregasi
$sql = "
SELECT
    COALESCE(c.cus_nosalesman, 'NA') as salesman,
    
    -- 1. TOTAL RANGE (Mengikuti Filter Tanggal Start - End)
    COUNT(CASE WHEN h.obi_tglpb::date BETWEEN '$tgl_start' AND '$tgl_end' THEN 1 END) as total_range,
    
    -- 2. VALID RANGE (Mengikuti Filter Tanggal Start - End)
    COUNT(CASE WHEN 
            (h.obi_tglpb::date BETWEEN '$tgl_start' AND '$tgl_end') AND 
            (h.obi_recid IS NULL OR (h.obi_recid NOT IN ('B', 'B1', 'B2', 'B3', 'B4') AND h.obi_recid !~ '^[A-Z]')) 
          THEN 1 END) as valid_range,

    -- 3. TOTAL HARI INI (FIX: Selalu menggunakan Tanggal Hari Ini / Current Date)
    COUNT(CASE WHEN h.obi_tglpb::date = '$current_date' THEN 1 END) as total_today,

    -- 4. VALID HARI INI (FIX: Selalu menggunakan Tanggal Hari Ini / Current Date)
    COUNT(CASE WHEN 
            (h.obi_tglpb::date = '$current_date') AND 
            (h.obi_recid IS NULL OR (h.obi_recid NOT IN ('B', 'B1', 'B2', 'B3', 'B4') AND h.obi_recid !~ '^[A-Z]')) 
          THEN 1 END) as valid_today

FROM
    tbtr_obi_h h
LEFT JOIN
    tbmaster_customer c ON h.obi_kdmember = c.cus_kodemember
WHERE 
    -- Where utama tetap mengambil range terluas (misal start s/d hari ini) untuk efisiensi
    h.obi_tglpb::date >= '$tgl_start'
GROUP BY 1
ORDER BY total_today DESC
";

$result = pg_query($conn, $sql);
$data = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $total_range = (int)$row['total_range'];
        $valid_range = (int)$row['valid_range'];
        $batal_range = $total_range - $valid_range;

        $total_today = (int)$row['total_today'];
        $valid_today = (int)$row['valid_today'];

        // Hitung Persentase Validasi (Berdasarkan Range)
        $persen = $total_range > 0 ? round(($valid_range / $total_range) * 100, 1) : 0;

        $data[] = [
            'salesman'    => $row['salesman'],
            'total_range' => $total_range,
            'valid_range' => $valid_range,
            'batal_range' => $batal_range,
            'total_today' => $total_today,
            'valid_today' => $valid_today,
            'persen'      => $persen
        ];
    }
}

echo json_encode($data);
