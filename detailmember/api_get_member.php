<?php

include "../connection/index.php";

$sqlmemberaktif = "SELECT
    -- Metrik 1: Menghitung total member berdasarkan filter utama
    (SELECT
        COUNT(CUS_KODEMEMBER)
    FROM
        TBMASTER_CUSTOMER
    WHERE
        CUS_RECORDID IS NULL
        AND CUS_KODEIGR = '2P'
        AND CUS_NAMAKTP <> 'NEW'
        AND cus_kodemember != 'KLZVMJ') AS TOTAL_MEMBER,

    -- Metrik 2: Menghitung member aktif dari total member di atas
    (SELECT
        COUNT(CUS_KODEMEMBER)
    FROM
        TBMASTER_CUSTOMER
    WHERE
        CUS_RECORDID IS NULL
        AND CUS_KODEIGR = '2P'
        AND CUS_NAMAKTP <> 'NEW'
        AND cus_kodemember != 'KLZVMJ'
        AND CUS_TGLMULAI IS NOT NULL) AS MEMBER_AKTIF,

    -- Metrik 3: Menghitung member non-transaksi (kriteria berbeda)
    (SELECT
        COUNT(CUS_KODEMEMBER)
    FROM
        TBMASTER_CUSTOMER
    WHERE
        CUS_TGLMULAI IS NULL
        AND CUS_KODEIGR = '2P') AS MEMBER_NON_TRANSAKSI,

    -- Metrik 4: Menghitung jumlah kelurahan unik dari member yang sudah aktivasi
    (SELECT
        COUNT(DISTINCT LOWER(cus_alamatmember8))
    FROM
        tbmaster_customer
    WHERE
        cus_kodeigr = '2P'
        AND cus_namamember <> 'NEW'
        AND cus_tglmulai IS NOT NULL) AS TOTAL_KELURAHAN_AKTIF;";

$query = pg_query($conn, $sqlmemberaktif);

$memberlive = pg_fetch_assoc($query);

// Set header agar browser tahu ini adalah file JSON
header('Content-Type: application/json');

// Mengubah hasil query menjadi format JSON dan menampilkannya
// Jika query gagal, kirim nilai default 0
echo json_encode($memberlive ? $memberlive : ['total_member' => 0]);
