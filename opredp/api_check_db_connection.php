<?php
// File: opredp/api_check_db_connection.php
header('Content-Type: application/json');

// Matikan error reporting agar tidak merusak JSON jika ada warning
error_reporting(0);

$ip = '172.31.147.216';
$port = 5432;
$timeout = 2; // Timeout 2 detik

// 1. Catat Waktu Mulai (dalam format float mikrodetik)
$start_time = microtime(true);

// 2. Coba Koneksi
$connection = fsockopen($ip, $port, $errno, $errstr, $timeout);

// 3. Catat Waktu Selesai
$end_time = microtime(true);

// 4. Hitung Durasi (Selesai - Mulai) * 1000 untuk jadi milidetik
$latency = round(($end_time - $start_time) * 1000);

if ($connection) {
    fclose($connection);

    // Format pesan ala Paping: "ONLINE - 15ms"
    $pesan = "ONLINE - " . $latency . "ms";

    echo json_encode([
        'status' => 'success',
        'data' => [
            'connected' => true,
            'message' => $pesan, // Ini yang akan muncul di dashboard
            'ms' => $latency
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'connected' => false,
            'message' => 'RTO / OFFLINE',
            'ms' => 0
        ]
    ]);
}
