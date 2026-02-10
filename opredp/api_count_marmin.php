<?php
// Set header agar output menjadi format JSON
header('Content-Type: application/json');

// Memanggil file koneksi database
require_once "../connection/index.php";

// Inisialisasi array response
$response = [];

try {
    // KITA HAPUS WRAPPING YANG BERLEBIHAN.
    // Query ini menyusun data terlebih dahulu, lalu di bagian paling luar kita COUNT baris yang minus.

    $sql = <<<SQL
    SELECT COUNT(*) as total 
    FROM (
        SELECT
            HRG_N.*,
            -- Perhitungan Margin MD
            case
                when PRD_UNIT = 'KG'
                then (((PRMD_HRGJUAL-(ST_AVGCOST * PRD_FRAC / 1000))/ PRMD_HRGJUAL)* 100)
                when coalesce(prd_flagbkp1, 'T') = 'Y'
                and coalesce(prd_flagbkp2, 'T') = 'Y'
                then (((PRMD_HRGJUAL / 1.11)-(ST_AVGCOST * PRD_FRAC))/(PRMD_HRGJUAL / 1.11)* 100)
                else (((PRMD_HRGJUAL-(ST_AVGCOST * PRD_FRAC))/ PRMD_HRGJUAL)* 100)
            end as MARGIN_A_MD,
            case
                when PRD_UNIT = 'KG'
                then (((PRMD_HRGJUAL-(ST_LASTCOST * PRD_FRAC / 1000))/ PRMD_HRGJUAL)* 100)
                when coalesce(prd_flagbkp1, 'T') = 'Y'
                and coalesce(prd_flagbkp2, 'T') = 'Y'
                then (((PRMD_HRGJUAL / 1.11)-(ST_LASTCOST * PRD_FRAC))/(PRMD_HRGJUAL / 1.11)* 100)
                else (((PRMD_HRGJUAL-(ST_LASTCOST * PRD_FRAC))/ PRMD_HRGJUAL)* 100)
            end as MARGIN_L_MD
        FROM
            (
            SELECT 
                PRD_KODEDIVISI,
                PRD_PRDCD,
                PRD_DESKRIPSIPANJANG,
                PRD_FRAC,
                PRD_UNIT,
                PRD_KODETAG,
                ST_SALDOAKHIR,
                PRD_HRGJUAL,
                ST_LASTCOST, prd_flagbkp2, prd_flagbkp1, ST_AVGCOST,
                -- Perhitungan LCOST
                case
                    when PRD_UNIT = 'KG'
                    then (ST_LASTCOST * PRD_FRAC)/ 1000
                    else ST_LASTCOST * PRD_FRAC
                end as LCOST,
                -- Perhitungan ACOST
                case
                    when PRD_UNIT = 'KG'
                    then (ST_AVGCOST * PRD_FRAC)/ 1000
                    else ST_AVGCOST * PRD_FRAC
                end as ACOST,
                -- Perhitungan ACOST INCLUDE
                case
                    when PRD_UNIT = 'KG'
                    then ((ST_AVGCOST * PRD_FRAC)/ 1000)* 1.11
                    else (ST_AVGCOST * PRD_FRAC)* 1.11
                end as ACOST_INCLUDE,  
                -- Perhitungan MARGIN A
                case
                    when PRD_UNIT = 'KG'
                    then (((PRD_HRGJUAL-(ST_AVGCOST * PRD_FRAC / 1000))/ PRD_HRGJUAL)* 100)
                    when coalesce(prd_flagbkp1, 'T') = 'Y' and coalesce(prd_flagbkp2, 'T') = 'Y'
                    then (((PRD_HRGJUAL / 1.11)-(ST_AVGCOST * PRD_FRAC))/(PRD_HRGJUAL / 1.11)* 100)
                    else (((PRD_HRGJUAL-(ST_AVGCOST * PRD_FRAC))/ PRD_HRGJUAL)* 100)
                end as MARGIN_A,
                -- Perhitungan MARGIN L
                case
                    when PRD_UNIT = 'KG'
                    then (((PRD_HRGJUAL-(ST_LASTCOST * PRD_FRAC / 1000))/ PRD_HRGJUAL)* 100)
                    when coalesce(prd_flagbkp1, 'T') = 'Y' and coalesce(prd_flagbkp2, 'T') = 'Y'
                    then (((PRD_HRGJUAL / 1.11)-(ST_LASTCOST * PRD_FRAC))/(PRD_HRGJUAL / 1.11)* 100)
                    else (((PRD_HRGJUAL-(ST_LASTCOST * PRD_FRAC))/ PRD_HRGJUAL)* 100)
                end as MARGIN_L
            FROM
                (
                SELECT
                    SUBSTR(PRD_PRDCD, 1, 6) || 0 PLU,
                    PRD_PRDCD,
                    PRD_KODEDIVISI,
                    PRD_KODEDEPARTEMENT,
                    PRD_KODEKATEGORIBARANG,
                    PRD_KODETAG,
                    PRD_DESKRIPSIPANJANG,
                    PRD_UNIT,
                    PRD_FRAC,
                    PRD_HRGJUAL,
                    prd_flagbkp1,
                    prd_flagbkp2
                FROM
                    tbmaster_prodmast
                ) prd
            LEFT JOIN
                (
                SELECT
                    ST_PRDCD,
                    ST_SALDOAKHIR,
                    ST_LASTCOST,
                    ST_AVGCOST
                FROM
                    tbmaster_Stock
                WHERE
                    st_lokasi = '01'
                ) stk ON prd.PLU = stk.st_prdcd
            WHERE
                coalesce (PRD_KODETAG, '0') not in ('N', 'X', 'Z')
                and ST_SALDOAKHIR <> 0
            ) HRG_N
        LEFT JOIN 
            (
            SELECT
                PRMD_PRDCD as PLUMD,
                PRMD_HRGJUAL
            FROM
                TBTR_PROMOMD
            WHERE
                CURRENT_DATE between DATE(PRMD_TGLAWAL) and DATE(PRMD_TGLAKHIR)
            ) PRMD ON HRG_N.PRD_PRDCD = PRMD.PLUMD
    ) AS DATA_FINAL
    WHERE (MARGIN_A < 0 OR MARGIN_A_MD < 0);
SQL;

    // Eksekusi query
    $result = pg_query($conn, $sql);

    // Cek jika query gagal dieksekusi
    if (!$result) {
        throw new Exception("Eksekusi query gagal: " . pg_last_error($conn));
    }

    // Ambil hasil query
    $data = pg_fetch_assoc($result);

    // Set status response sukses
    $response['status'] = 'success';
    $response['data'] = [
        'total' => isset($data['total']) ? (int)$data['total'] : 0
    ];
} catch (Exception $e) {
    // Jika terjadi error
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    if ($conn) {
        pg_close($conn);
    }
}

echo json_encode($response);
