<?php
include "../connection/index.php";

// Query yang sudah disederhanakan
$sql_netto_only = "-- Gunakan WITH clause (CTE) untuk membuat kode lebih rapi
WITH combined_transactions AS (
    -- Langkah 1: Gabungkan data dari dua tabel transaksi untuk hari ini
    SELECT 
        trjd_transactiontype,
        trjd_flagtax1,
        trjd_flagtax2,
        trjd_create_by,
        trjd_transactiondate,
        trjd_nominalamt
    FROM tbtr_jualdetail
    WHERE DATE_TRUNC('day', trjd_transactiondate) = CURRENT_DATE 
      AND trjd_recordid IS NULL

    UNION ALL

    SELECT 
        trjd_transactiontype,
        trjd_flagtax1,
        trjd_flagtax2,
        trjd_create_by,
        trjd_transactiondate,
        trjd_nominalamt
    FROM tbtr_jualdetail_interface
    WHERE DATE_TRUNC('day', trjd_transactiondate) = CURRENT_DATE 
      AND trjd_recordid IS NULL
),

calculated_netto_per_row AS (
    -- Langkah 2: Hitung nilai netto per baris transaksi
    SELECT
        -- Logika CASE untuk menghitung netto berdasarkan pajak
        (CASE
            WHEN trjd_flagtax1 = 'Y' AND trjd_create_by NOT IN ('IDM', 'ID1', 'ID2', 'OMI', 'BKL') AND TO_CHAR(trjd_transactiondate, 'YYYYMMDD') <= '20220331' THEN trjd_nominalamt / 11 * 10
            WHEN trjd_flagtax2 = 'Y' AND trjd_create_by NOT IN ('IDM', 'ID1', 'ID2', 'OMI', 'BKL') AND TO_CHAR(trjd_transactiondate, 'YYYYMMDD') > '20230430' THEN trjd_nominalamt / 11.1 * 10
            WHEN trjd_flagtax1 = 'Y' AND trjd_create_by NOT IN ('IDM', 'ID1', 'ID2', 'OMI', 'BKL') AND TO_CHAR(trjd_transactiondate, 'YYYYMMDD') BETWEEN '20220331' AND '20230430' THEN trjd_nominalamt / 11.1 * 10
            ELSE trjd_nominalamt
        END)
        -- Dikalikan -1 jika transaksi adalah retur ('R')
        * (CASE WHEN trjd_transactiontype = 'S' THEN 1 ELSE -1 END)
        AS netto_value
    FROM combined_transactions
)

-- Langkah 3: Jumlahkan semua nilai netto yang sudah dihitung
SELECT
    TRUNC(SUM(netto_value)) AS NETTO
FROM
    calculated_netto_per_row;";

$query = pg_query($conn, $sql_netto_only);
$result_data = pg_fetch_assoc($query);

$response = [];
if ($result_data) {
    // Ambil dan format NETTO
    $response['netto'] = number_format($result_data['netto'], 0, ',', '.');
} else {
    $response['netto'] = '0';
}

header('Content-Type: application/json');
echo json_encode($response);
