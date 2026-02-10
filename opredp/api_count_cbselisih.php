<?php
// Set header agar output menjadi format JSON
header('Content-Type: application/json');

// Memanggil file koneksi database
require_once "../connection/index.php";

// Inisialisasi array response
$response = [];

try {
    // Query SQL untuk menghitung jumlah selisih cashback di bulan berjalan
    $sql = "
        SELECT 
            COUNT(*) AS total 
        FROM (
            SELECT 
                tgl_trans,
                smph_cashback,
                strp_cashback,
                status
            FROM (
                SELECT DISTINCT
                    ac.tgl_trans,
                    COALESCE(ac.smph_cashback, 0) AS smph_cashback,
                    COALESCE(ab.strp_cashback, 0) AS strp_cashback,
                    (ac.smph_cashback - COALESCE(ab.strp_cashback, 0)) AS JML_SELISIH,
                    CASE 
                        WHEN ac.smph_cashback BETWEEN (COALESCE(ab.strp_cashback, 0) - 100) AND (COALESCE(ab.strp_cashback, 0) + 100) 
                        THEN 'COCOK' 
                        ELSE 'SELISIH' 
                    END AS status
                FROM (
                    SELECT 
                        tgl_trans::date AS tgl_trans, 
                        SUM(smph_cashback) AS smph_cashback 
                    FROM (
                        SELECT 
                            TO_CHAR(tgl_trans::date, 'yyyy-mm-dd') AS tgl_trans,
                            SUM(cashback) AS smph_cashback
                        FROM m_promosi_h
                        WHERE DATE_TRUNC('month', tgl_trans) = DATE_TRUNC('month', CURRENT_DATE)
                        GROUP BY tgl_trans
                    ) AS finalq 
                    GROUP BY tgl_trans
                ) AS ac
                LEFT JOIN (
                    SELECT
                        trp_transactiondate::date AS trp_transactiondate,
                        SUM(trp_cashback) AS strp_cashback
                    FROM tbtr_transaksi_promosi
                    WHERE DATE_TRUNC('month', trp_transactiondate) = DATE_TRUNC('month', CURRENT_DATE)
                    GROUP BY trp_transactiondate
                ) AS ab ON ac.tgl_trans = ab.trp_transactiondate
                ORDER BY ac.tgl_trans ASC
            ) cb
            WHERE cb.status = 'SELISIH'
        ) AS main;
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
