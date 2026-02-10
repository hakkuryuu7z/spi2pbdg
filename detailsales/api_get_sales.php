<?php
// detailsales/api_get_sales.php

header('Content-Type: application/json');
include "../connection/index.php";

// --- PERUBAHAN LOGIKA TANGGAL ---
// 1. Ambil parameter rentang tanggal
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

$response = [];

// 2. Validasi parameter
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit; // Hentikan script
}
// --- AKHIR PERUBAHAN TANGGAL ---

// --- PERUBAHAN "KONSEP QUERY" ---
// Query ditulis ulang menggunakan Common Table Expression (CTE) agar lebih rapi
// dan tidak mengulang-ulang logika CASE.
$sql = "
    WITH calculated_data AS (
        SELECT
            TRJD_CUS_KODEMEMBER,
            TRJD_PRDCD,
            TRJD_TRANSACTIONTYPE,
            TRJD_CREATE_DT,
            TRJD_CREATE_BY,
            TRJD_CASHIERSTATION,
            TRJD_TRANSACTIONNO,
            
            -- 1. Hitung sales_base (sebelum dikali -1 untuk return)
            (CASE
                WHEN coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' AND coalesce(TRJD_FLAGTAX2, 'T') = 'Y'
                THEN TRJD_NOMINALAMT / 1.11
                ELSE TRJD_NOMINALAMT
            END) AS sales_base,
            
            -- 2. Hitung cost_base (sebelum dikali -1 untuk return)
            (CASE
                WHEN PRD_UNIT = 'KG'
                THEN (TRJD_BASEPRICE * TRJD_QUANTITY) / 1000
                ELSE (TRJD_BASEPRICE * TRJD_QUANTITY)
            END) AS cost_base,

            -- 3. Tentukan multiplier (1 for 'S' (Sale), -1 for 'R' (Return) or other)
            (CASE
                WHEN TRJD_TRANSACTIONTYPE = 'S' THEN 1
                ELSE -1 -- Meng-cover 'R' dan 'else' dari logic lama Anda
            END) AS multiplier
        FROM
            TBTR_JUALDETAIL
        LEFT JOIN TBMASTER_PRODMAST ON TRJD_PRDCD = PRD_PRDCD
        LEFT JOIN TBMASTER_CUSTOMER ON TRJD_CUS_KODEMEMBER = CUS_KODEMEMBER
        LEFT JOIN TBMASTER_CUSTOMERCRM ON TRJD_CUS_KODEMEMBER = CRM_KODEMEMBER
        WHERE
            TRJD_RECORDID IS NULL
            -- 4. Gunakan filter rentang tanggal yang aman (BETWEEN $1 AND $2)
            AND DATE(TRJD_TRANSACTIONDATE) BETWEEN $1 AND $2
    ),
    aggregated_data AS (
        -- 5. Lakukan agregasi (SUM, COUNT) dari data yang sudah dihitung
        SELECT
            COUNT(DISTINCT TRJD_CUS_KODEMEMBER) AS MEMBERS,
            COUNT(TRJD_PRDCD) AS PRODUK_BELI,
            COUNT(DISTINCT TRJD_TRANSACTIONTYPE || TRJD_CREATE_DT || TRJD_CREATE_BY || TRJD_CASHIERSTATION || TRJD_TRANSACTIONNO) AS STD,
            
            SUM(ROUND(sales_base * multiplier, 0)) AS SALES,
            
            SUM(ROUND((sales_base - cost_base) * multiplier, 0)) AS MARGIN
        FROM
            calculated_data
    )
    -- 6. Tampilkan hasil akhir dan hitung PROD_MIX
    SELECT
        MEMBERS,
        STD,
        PRODUK_BELI,
        ROUND(PRODUK_BELI::numeric / nullif(MEMBERS, 0), 0) as PROD_MIX,
        SALES,
        MARGIN
    FROM
        aggregated_data;
";
// --- AKHIR PERUBAHAN QUERY ---

// Siapkan parameter untuk query yang aman
$params = [$tgl_mulai, $tgl_selesai];

// Eksekusi query dengan parameter
$query = pg_query_params($conn, $sql, $params);

$get_data = pg_fetch_assoc($query);

// Cek dulu apakah query menghasilkan data
if ($get_data && $get_data['members'] !== null) {
    // Susun data ke dalam array response
    // Ambil data yang tidak perlu diformat langsung
    $response['members'] = $get_data['members'];
    $response['std'] = $get_data['std'];
    $response['produk_beli'] = $get_data['produk_beli'];
    $response['prod_mix'] = $get_data['prod_mix'];

    // Format HANYA kolom yang berupa angka (sesuai format file asli Anda)
    // Javascript Anda mengharapkan format string '1.000.000'
    $response['sales'] = number_format($get_data['sales'], 0, ',', '.');
    $response['margin'] = number_format($get_data['margin'], 0, ',', '.');
} else {
    // Jika tidak ada data, kirim response default agar tidak error di frontend
    $response = [
        'members' => 0,
        'std' => 0,
        'produk_beli' => 0,
        'prod_mix' => 0,
        'sales' => '0',
        'margin' => '0'
    ];
}

// Tampilkan hasil
echo json_encode($response);

// Tutup koneksi
pg_close($conn);
