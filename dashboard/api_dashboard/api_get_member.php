<?php

include "../connection/index.php";

// --- Query 1: Total Member Register (Semua yang terdaftar) ---
$sqlmemberaktif = "SELECT 
        COUNT(KODE) AS total_member FROM (SELECT CUS_KODEMEMBER AS KODE,
        CUS_NAMAMEMBER as nama FROM TBMASTER_CUSTOMER WHERE 
        CUS_RECORDID IS NULL AND CUS_KODEIGR = '2P' AND CUS_NAMAKTP <>'NEW' and 
        cus_kodemember != 'KLZVMJ' ) AS AA ";
$query_total = pg_query($conn, $sqlmemberaktif);
$memberlive = pg_fetch_assoc($query_total);

// --- Query 2: Member Belanja Bulan Ini ---
$sqlmemberbelanja = "select count(distinct trjd_cus_kodemember) as member_belanja_bulanberjalan 
                     from tbtr_jualdetail 
                     where DATE(TRJD_TRANSACTIONDATE) >= DATE_TRUNC('month', CURRENT_DATE);";
$query_belanja = pg_query($conn, $sqlmemberbelanja);
$memberbelanja = pg_fetch_assoc($query_belanja);

// --- Query 3: Aktivasi Bulan Berjalan (Member Baru bulan ini) ---
$sqlaktivasibulanberjalan = "select
                sum(total_member) as total_member
                from
                (
                select
                to_char(cus_tglmulai::date, 'yyyy-mm-dd') tgl,
                COUNT(KODE) as TOTAL_MEMBER
                from
                (
                select
                cus_tglmulai,
                CUS_KODEMEMBER as KODE,
                CUS_NAMAMEMBER as nama
                from
                TBMASTER_CUSTOMER
                where
                CUS_RECORDID is null
                and CUS_KODEIGR = '2P'
                and CUS_NAMAKTP <> 'NEW'
                and cus_tglmulai >= DATE_TRUNC('month', NOW())
                and cus_tglmulai < (DATE_TRUNC('month', NOW()) + INTERVAL '1 month')
                and cus_kodemember != 'KLZVMJ' ) as AA
                group by
                to_char(cus_tglmulai::date, 'yyyy-mm-dd')) b";
$query_aktivasi = pg_query($conn, $sqlaktivasibulanberjalan);
$aktivasi_bulanberjalan = pg_fetch_assoc($query_aktivasi);

// --- Query 4: MEMBER AKTIF TOTAL (Sepanjang Masa) ---
// Ini query tambahan dari Anda
$sql_member_aktif_total = "SELECT COUNT(CUS_KODEMEMBER) as total_aktif_all
                           FROM TBMASTER_CUSTOMER
                           WHERE CUS_RECORDID IS NULL
                           AND CUS_KODEIGR = '2P'
                           AND CUS_NAMAKTP <> 'NEW'
                           AND cus_kodemember != 'KLZVMJ'
                           AND CUS_TGLMULAI IS NOT NULL";
$query_aktif_total = pg_query($conn, $sql_member_aktif_total);
$data_aktif_total = pg_fetch_assoc($query_aktif_total);


// --- Gabungkan Hasil ---
$response = [
    'total_member' => $memberlive['total_member'] ?? 0,
    'member_belanja_bulanberjalan' => $memberbelanja['member_belanja_bulanberjalan'] ?? 0,
    'aktivasi_bulan_berjalan' => $aktivasi_bulanberjalan['total_member'] ?? 0,
    // Data baru:
    'member_aktif_total' => $data_aktif_total['total_aktif_all'] ?? 0
];

// Set header dan kirim JSON
header('Content-Type: application/json');
echo json_encode($response);

pg_close($conn);
?>