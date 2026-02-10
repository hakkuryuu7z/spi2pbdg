<?php
header('Content-Type: application/json');
include '../connection/index.php';

// 1. Ambil Parameter
$salesman = isset($_GET['salesman']) ? $_GET['salesman'] : '';
$filterTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Validasi
if (empty($salesman)) {
    echo json_encode([]);
    exit;
}

// Ambil format Bulan (YYYY-MM) agar detailnya sesuai dengan Total Get Bulan itu
$filterBulan = date('Y-m', strtotime($filterTanggal));

// Sanitasi Input (Keamanan dasar)
$salesmanEscaped = pg_escape_string($conn, $salesman);

// 2. Query SQL
// Kolom SELECT sesuai permintaan Anda
// WHERE clause disamakan dengan api_table_getmember.php agar angkanya cocok
$sql = "
    SELECT 
        cus_kodemember, 
        cus_namamember, 
        COALESCE(cus_alamatmember1, '') || ', ' || 
        COALESCE(cus_alamatmember2, '') || ', ' || 
        COALESCE(cus_alamatmember3, '') || ', ' || 
        COALESCE(cus_alamatmember4, '') as alamat,
        cus_tlpmember,
        cus_jarak,
        cus_tglregistrasi,
        cus_nosalesman
    FROM 
        tbmaster_customer
    WHERE 
        cus_kodeigr = '2P' 
        AND cus_nosalesman = '$salesmanEscaped'
        AND CUS_RECORDID IS NULL
        AND CUS_NAMAKTP <> 'NEW'
        AND cus_kodemember != 'KLZVMJ'
        AND TO_CHAR(cus_tglregistrasi::date, 'YYYY-MM') = '$filterBulan'
    ORDER BY 
        cus_tglregistrasi DESC
";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    // Format tanggal biar enak dibaca (DD-MM-YYYY)
    $tglIndo = date('d-m-Y', strtotime($row['cus_tglregistrasi']));

    $data[] = [
        'kode' => $row['cus_kodemember'],
        'nama' => $row['cus_namamember'],
        'alamat' => $row['alamat'],
        'telp' => $row['cus_tlpmember'],
        'jarak' => $row['cus_jarak'],
        'tgl_reg' => $tglIndo
    ];
}

echo json_encode($data);
pg_close($conn);
