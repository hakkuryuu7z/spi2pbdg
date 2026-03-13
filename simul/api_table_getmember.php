<?php
header('Content-Type: application/json');
include '../connection/index.php';

$filterTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filterBulan = date('Y-m', strtotime($filterTanggal));

// 1. Baca File target bulanan (dari upload JSON)
$jsonFile = 'target_bulanan.json';
$targetGoalDariJSON = 0;

if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $targetDataList = json_decode($jsonContent, true);
    foreach ($targetDataList as $row) {
        if (isset($row['Bulan']) && $row['Bulan'] == $filterBulan) {
            $targetGoalDariJSON = (int)$row['Total_Target'];
            break;
        }
    }
}

// 2. Query SQL (EXCLUDE ZAY)
$sql = "
    SELECT 
        cus_nosalesman as mr,
        COUNT(cus_kodemember) as total_member_all_time,
        COUNT(CASE WHEN TO_CHAR(cus_tglregistrasi::date, 'YYYY-MM') = '$filterBulan' THEN 1 END) as jmlh_get_bulan_ini,
        COUNT(CASE WHEN cus_tglregistrasi::date = '$filterTanggal' THEN 1 END) as get_harian
    FROM 
        TBMASTER_CUSTOMER
    WHERE 
        CUS_RECORDID IS NULL
        AND CUS_KODEIGR = '2P'
        AND CUS_NAMAKTP <> 'NEW'
        AND cus_kodemember != 'KLZVMJ'
        AND cus_nosalesman != 'ZAY' 
    GROUP BY 
        cus_nosalesman
    ORDER BY 
        COUNT(cus_kodemember) DESC
";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
    exit;
}

$rawData = [];
$totalMemberAreaAllTime = 0;
$totalGetBulanIniArea = 0;
$jumlahMR = pg_num_rows($result);

while ($row = pg_fetch_assoc($result)) {
    $rawData[] = $row;
    $totalMemberAreaAllTime += (int)$row['total_member_all_time'];
    $totalGetBulanIniArea += (int)$row['jmlh_get_bulan_ini'];
}

// 3. LOGIKA BARU: Hitung Total Member SEBELUM bulan ini dimulai
// Tujuannya agar target yang didapat adalah FIX untuk satu bulan penuh
$totalMemberSebelumBulanIni = $totalMemberAreaAllTime - $totalGetBulanIniArea;

$targetAreaBulanIni = $targetGoalDariJSON - $totalMemberSebelumBulanIni;

if ($targetAreaBulanIni < 0) {
    $targetAreaBulanIni = 0;
}

// 4. BAGI RATA TARGET (Sekarang angkanya akan statis selama sebulan)
$targetPerMR = 0;
if ($jumlahMR > 0 && $targetAreaBulanIni > 0) {
    $targetPerMR = ceil($targetAreaBulanIni / $jumlahMR);
}

$finalData = [];
foreach ($rawData as $row) {
    $mrName = $row['mr'];
    $realisasiBulanIni = (int)$row['jmlh_get_bulan_ini'];
    $totalAllTime = (int)$row['total_member_all_time'];

    $persen = 0;
    if ($targetPerMR > 0) {
        $persen = round(($realisasiBulanIni / $targetPerMR) * 100);
    } else if ($targetPerMR == 0 && $realisasiBulanIni > 0) {
        $persen = 100;
    }

    $finalData[] = [
        'mr' => $mrName,
        'target' => $targetPerMR,
        'jmlh_get' => $realisasiBulanIni,
        'get_harian' => (int)$row['get_harian'],
        'persen' => $persen,
        'total_all_time' => $totalAllTime
    ];
}

// FORMAT JSON BARU (Ada Summary dan Data)
echo json_encode([
    "summary" => [
        "target_bulan_ini" => $targetGoalDariJSON,
        "total_member_all_time" => $totalMemberAreaAllTime
    ],
    "data_mr" => $finalData
]);
pg_close($conn);
