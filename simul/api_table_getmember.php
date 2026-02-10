<?php
header('Content-Type: application/json');
include '../connection/index.php';

// 1. Ambil Filter Tanggal (Format YYYY-MM-DD)
// Jika kosong, pakai hari ini
$filterTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil format Bulan saja (YYYY-MM) dari tanggal tersebut untuk filter Achievement Bulanan
$filterBulan = date('Y-m', strtotime($filterTanggal));

// 2. Baca File target.json
$jsonFile = 'target.json';
$targetData = [];
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $targetData = json_decode($jsonContent, true);
}

// 3. Query SQL
// Perhatikan bagian CASE WHEN untuk 'get_harian' menggunakan variabel $filterTanggal
$sql = "
    SELECT 
        cus_nosalesman as mr,
        COUNT(cus_kodemember) as jmlh_get,
        COUNT(CASE WHEN cus_tglregistrasi::date = '$filterTanggal' THEN 1 END) as get_harian
    FROM 
        TBMASTER_CUSTOMER
    WHERE 
        CUS_RECORDID IS NULL
        AND CUS_KODEIGR = '2P'
        AND CUS_NAMAKTP <> 'NEW'
        AND TO_CHAR(cus_tglregistrasi::date, 'YYYY-MM') = '$filterBulan' 
        AND cus_kodemember != 'KLZVMJ'
    GROUP BY 
        cus_nosalesman
    ORDER BY 
        count(cus_kodemember) DESC
";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $mrName = $row['mr'];
    $target = isset($targetData[$mrName]) ? (int)$targetData[$mrName] : 80;
    $realisasi = (int)$row['jmlh_get'];

    $persen = 0;
    if ($target > 0) {
        $persen = round(($realisasi / $target) * 100);
    }

    $data[] = [
        'mr' => $mrName,
        'target' => $target,
        'jmlh_get' => $realisasi,
        'get_harian' => (int)$row['get_harian'], // Data sesuai tanggal filter
        'persen' => $persen
    ];
}

echo json_encode($data);
pg_close($conn);
