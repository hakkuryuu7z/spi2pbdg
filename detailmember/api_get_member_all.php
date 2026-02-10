<?php
// Set header ke JSON
header('Content-Type: application/json');

// Hubungkan ke database PostgreSQL
// Pastikan file ini berisi pg_connect(...)
require_once '../connection/index.php';

// Siapkan array respons standar
$response = [];

try {
    // Query final Anda (tidak ada yang diubah di sini, sudah benar)
    $sql = "
       WITH detail_struk_data AS (
    -- LOGIKA VIEW DIPERSINGKAT (Hanya ambil yang dibutuhkan untuk perhitungan Sales)
    SELECT 
        sls.trjd_cus_kodemember AS dtl_cusno,
        -- Perhitungan Netto (Menggabungkan logika Return (-1) dan Pajak)
        (
            CASE 
                WHEN sls.trjd_transactiontype = 'S' THEN 1 
                ELSE -1 
            END * CASE
                WHEN sls.trjd_flagtax2 = 'Y' AND sls.trjd_create_by IN ('OMI', 'BKL') 
                    THEN (sls.trjd_nominalamt * 11.1) / 10
                WHEN sls.trjd_flagtax2 = 'Y' AND sls.trjd_create_by NOT IN ('OMI', 'BKL') 
                    THEN (sls.trjd_nominalamt / 11.1) * 10
                ELSE sls.trjd_nominalamt
            END
        ) AS dtl_netto
    FROM tbtr_jualdetail sls
    WHERE 
        date_trunc('day', sls.trjd_transactiondate) <= (CURRENT_DATE - 1)
        AND sls.trjd_recordid IS NULL
        AND sls.trjd_quantity <> 0
)
SELECT 
    CASE 
        WHEN SUM(D.dtl_netto) > 0 THEN 'Aktif'
        ELSE 'Non Transaksi'
    END AS status,
    C.cus_kodemember,
    C.cus_namamember,
    C.cus_tlpmember,
    R.crm_email,
    C.cus_nosalesman,
    C.cus_tglregistrasi,
    C.cus_tglmulai,
    COALESCE(SUM(D.dtl_netto), 0) AS sales
FROM 
    TBMASTER_CUSTOMER AS C
LEFT JOIN 
    TBMASTER_CUSTOMERCRM AS R ON C.CUS_KODEMEMBER = R.CRM_KODEMEMBER
LEFT JOIN
    detail_struk_data AS D ON D.dtl_cusno = C.CUS_KODEMEMBER
WHERE
    C.CUS_KODEIGR = '2P' 
    AND C.CUS_KODEMEMBER != 'KLZVMJ' 
    AND C.cus_namamember <> 'NEW'
GROUP BY
    C.cus_kodemember,
    C.cus_namamember,
    C.cus_tlpmember,
    R.crm_email,
    C.cus_nosalesman,
    C.cus_tglregistrasi,
    C.cus_tglmulai
ORDER BY
    C.CUS_tglregistrasi DESC;
    ";

    // DIUBAH: Menggunakan pg_query untuk PostgreSQL
    $result = pg_query($conn, $sql);

    // DIUBAH: Cek jika query gagal menggunakan pg_last_error
    if (!$result) {
        throw new Exception("Query Gagal: " . pg_last_error($conn));
    }

    // DIUBAH: Menggunakan pg_fetch_all untuk mengambil semua data
    $data = pg_fetch_all($result);

    // Set respons jika sukses
    $response['status'] = 'success';
    // Jika tidak ada data, $data akan bernilai false. Ubah menjadi array kosong.
    $response['data'] = ($data === false) ? [] : $data;
} catch (Exception $e) {
    // Menangkap semua kemungkinan error
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// DIUBAH: Menggunakan pg_close untuk menutup koneksi PostgreSQL
pg_close($conn);

// Mengembalikan respons dalam format JSON
echo json_encode($response);
