<?php
// File: dashboard/api_get_detail_aktivasi.php

include "../connection/index.php";

// Set header JSON
header('Content-Type: application/json');

// Ambil parameter tanggal (opsional, jika ingin filter custom tanggal nantinya)
// Defaultnya adalah bulan ini sesuai logika awal Anda
$today = date('Y-m-d');
$firstDate = date('Y-m-01');

// Query mengambil detail member yang aktivasi bulan ini
// Logika WHERE disamakan dengan Query 3 di file index.php Anda
$sql = "SELECT 
            CUS_KODEMEMBER as kode,
            CUS_NAMAMEMBER as nama,
            to_char(CUS_TGLMULAI, 'DD-MM-YYYY') as tgl_aktif,
            cus_alamatmember1 as alamat,
            cus_nosalesman as adv
        FROM TBMASTER_CUSTOMER
        WHERE CUS_RECORDID is null
        AND CUS_KODEIGR = '2P'
        AND CUS_NAMAKTP <> 'NEW'
        AND CUS_TGLMULAI >= DATE_TRUNC('month', CURRENT_DATE)
        AND CUS_TGLMULAI < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month')
        AND CUS_KODEMEMBER != 'KLZVMJ'
        ORDER BY CUS_TGLMULAI DESC";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => pg_last_error($conn)]);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $data]);

pg_close($conn);
