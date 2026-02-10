<?php
// Set header agar output menjadi format JSON
header('Content-Type: application/json');

// Memanggil file koneksi database
require_once "../connection/index.php";

// Inisialisasi array response
$response = [];

try {
    // Query SQL untuk menghitung intransit yang sudah selesai
    $sql = "
        SELECT
            COUNT(*) AS total
        FROM
            TBTR_OBI_D d
        LEFT JOIN TBTR_OBI_H h ON
            d.OBI_NOTRANS = h.OBI_NOTRANS AND d.OBI_TGLTRANS = h.OBI_TGLPB
        LEFT JOIN TBMASTER_PRODMAST pm ON
            d.OBI_PRDCD = pm.PRD_PRDCD
        LEFT JOIN TBTR_PACKING_OBI pobi ON
            d.OBI_PRDCD = pobi.POBI_PRDCD AND d.OBI_NOTRANS = pobi.POBI_NOTRANSAKSI AND d.OBI_TGLTRANS = pobi.POBI_TGLTRANSAKSI
        LEFT JOIN (
            SELECT
                SUBSTR(ST_PRDCD, 1, 6) AS ST_PRDCD,
                ST_SALDOAKHIR
            FROM
                TBMASTER_STOCK
            WHERE
                ST_LOKASI = '01') st ON SUBSTR(d.OBI_PRDCD, 1, 6) = st.ST_PRDCD
        WHERE
            d.OBI_TGLTRANS BETWEEN CURRENT_DATE - INTERVAL '60 days' AND CURRENT_DATE
            AND h.OBI_RECID IN ('6', 'B', 'B1', 'B2', 'B3', 'B4', 'B5')
            AND d.OBI_QTYINTRANSIT <> '0';
    ";

    // Eksekusi query
    $result = pg_query($conn, $sql);

    // Cek jika query gagal dieksekusi
    if (!$result) {
        throw new Exception("Eksekusi query gagal: " . pg_last_error($conn));
    }

    // Ambil hasil query (satu baris data) sebagai associative array
    $data = pg_fetch_assoc($result);

    // Set status response sukses
    $response['status'] = 'success';

    // Masukkan data hasil count ke dalam response
    $response['data'] = [
        'total' => isset($data['total']) ? (int)$data['total'] : 0
    ];
} catch (Exception $e) {
    // Jika terjadi error, set status response menjadi error
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    // Selalu tutup koneksi database setelah selesai
    if ($conn) {
        pg_close($conn);
    }
}

// Tampilkan response dalam format JSON
echo json_encode($response);
