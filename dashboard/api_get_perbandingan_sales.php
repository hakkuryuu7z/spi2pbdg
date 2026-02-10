<?php
// Atur header sebagai JSON
header('Content-Type: application/json');

include_once '../connection/index.php';

$response = [
    'status' => 'error',
    'message' => 'Terjadi kesalahan',
    'data' => [],
    'info_date' => []
];

try {
    // 2. AMBIL PARAMETER
    $period = isset($_GET['period']) ? $_GET['period'] : 'daily';

    // --- LOGIKA PENENTUAN TANGGAL ---
    if (isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_current = $_GET['start_date'];
        $end_current   = $_GET['end_date'];
    } else {
        $start_current = date('Y-m-01');
        $end_current   = date('Y-m-d');
    }

    // Tanggal Pembanding
    $start_last_month = date('Y-m-d', strtotime($start_current . ' -1 month'));
    $end_last_month   = date('Y-m-d', strtotime($end_current . ' -1 month'));
    $start_last_year  = date('Y-m-d', strtotime($start_current . ' -1 year'));
    $end_last_year    = date('Y-m-d', strtotime($end_current . ' -1 year'));

    $response['info_date'] = [
        'start' => date('d M Y', strtotime($start_current)),
        'end'   => date('d M Y', strtotime($end_current))
    ];

    // 3. QUERY DATA (GABUNGAN SALES & ONGKIR)
    // Kita gunakan UNION ALL:
    // Bagian 1: Query Sales & Margin (Logic Lama)
    // Bagian 2: Query Ongkir (Logic Baru dari kamu)

    $sql_base_data_cte = "
        WITH SalesData AS (
            SELECT 
                date_trunc('day', trjd_transactiondate) as dtl_tanggal,
                
                -- RUMUS NETTO
                CASE 
                    WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN
                        CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END
                    ELSE
                        CASE
                            WHEN coalesce(tko_kodesbu, 'z') IN ('O', 'I') THEN
                                CASE WHEN tko_tipeomi IN ('HE', 'HG') THEN
                                    trjd_nominalamt - (CASE WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END)
                                ELSE trjd_nominalamt END
                            ELSE
                                trjd_nominalamt - (CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END END)
                        END
                END as val_sales,

                -- RUMUS MARGIN
                (
                    CASE 
                        WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN
                            CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END
                        ELSE
                            CASE WHEN coalesce(tko_kodesbu, 'z') IN ('O', 'I') THEN CASE WHEN tko_tipeomi IN ('HE', 'HG') THEN trjd_nominalamt - (CASE WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END) ELSE trjd_nominalamt END ELSE trjd_nominalamt - (CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100)))) ELSE 0 END END) END
                    END
                ) - 
                (
                    CASE
                        WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN
                            CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt - (CASE WHEN prd_markupstandard IS NULL THEN (5 * trjd_nominalamt) / 100 ELSE (prd_markupstandard * trjd_nominalamt) / 100 END) END
                        ELSE
                            (trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END) * trjd_baseprice
                    END
                ) as val_margin,
                0 as val_ongkir

            FROM tbtr_jualdetail
            LEFT JOIN tbmaster_prodmast ON trjd_prdcd = prd_prdcd
            LEFT JOIN tbmaster_tokoigr ON trjd_cus_kodemember = tko_kodecustomer
            LEFT JOIN tbmaster_customer ON trjd_cus_kodemember = cus_kodemember
            LEFT JOIN tbmaster_customercrm ON trjd_cus_kodemember = crm_kodemember
            LEFT JOIN tbmaster_divisi ON trjd_division = div_kodedivisi
            LEFT JOIN tbmaster_departement ON substr(trjd_division, 1, 2) = dep_kodedepartement
            LEFT JOIN tbmaster_kategori ON trjd_division = kat_kodedepartement || kat_kodekategori
            
            WHERE trjd_transactiondate >= '$start_last_year'
        ),
        OngkirData AS (
            SELECT
                date_trunc('day', b.obi_tglstruk) as dtl_tanggal,
                0 as val_sales,
                0 as val_margin,
                COALESCE(p.pot_ongkir, 0) as val_ongkir
            FROM tbtr_obi_h b
            LEFT JOIN payment_klikigr p ON b.obi_kdmember = p.kode_member AND b.obi_nopb = p.no_pb
            WHERE 
                b.obi_recid = '6' 
                AND b.obi_tglstruk >= '$start_last_year'
                AND p.pot_ongkir <> 0
        ),
        CombinedData AS (
            SELECT * FROM SalesData
            UNION ALL
            SELECT * FROM OngkirData
        ),
        BaseData AS (
            SELECT 
                dtl_tanggal,
                SUM(val_sales) as dtl_netto,
                SUM(val_margin) as dtl_margin,
                SUM(val_ongkir) as dtl_ongkir
            FROM CombinedData
            GROUP BY dtl_tanggal
        )
    ";

    // 4. BUILD QUERY FINAL
    $sql = "";

    if ($period == 'monthly') {
        // --- MODE BULANAN ---
        $sql = "
            $sql_base_data_cte
            SELECT
                'Periode Ini' AS tanggal,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_current' AND '$end_current' THEN dtl_netto ELSE 0 END)) AS sales_bulan_ini,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_current' AND '$end_current' THEN dtl_margin ELSE 0 END)) AS margin_bulan_ini,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_current' AND '$end_current' THEN dtl_ongkir ELSE 0 END)) AS ongkir_bulan_ini,

                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_month' AND '$end_last_month' THEN dtl_netto ELSE 0 END)) AS sales_bulan_lalu,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_month' AND '$end_last_month' THEN dtl_margin ELSE 0 END)) AS margin_bulan_lalu,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_month' AND '$end_last_month' THEN dtl_ongkir ELSE 0 END)) AS ongkir_bulan_lalu,

                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_year' AND '$end_last_year' THEN dtl_netto ELSE 0 END)) AS sales_tahun_lalu,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_year' AND '$end_last_year' THEN dtl_margin ELSE 0 END)) AS margin_tahun_lalu,
                TRUNC(SUM(CASE WHEN dtl_tanggal BETWEEN '$start_last_year' AND '$end_last_year' THEN dtl_ongkir ELSE 0 END)) AS ongkir_tahun_lalu
            FROM BaseData;
        ";
    } else {
        // --- MODE HARIAN / MINGGUAN / CUSTOM ---
        $sql = "
            $sql_base_data_cte,

            RankedSales_BI AS (
                SELECT 
                    dtl_tanggal,
                    TRUNC(SUM(dtl_netto)) AS sales_bulan_ini,
                    TRUNC(SUM(dtl_margin)) AS margin_bulan_ini,
                    TRUNC(SUM(dtl_ongkir)) AS ongkir_bulan_ini,
                    ROW_NUMBER() OVER (ORDER BY dtl_tanggal ASC) AS rank_num
                FROM BaseData
                WHERE dtl_tanggal BETWEEN '$start_current' AND '$end_current'
                GROUP BY dtl_tanggal
            ),
            RankedSales_BL AS (
                SELECT 
                    dtl_tanggal,
                    TRUNC(SUM(dtl_netto)) AS sales_bulan_lalu,
                    TRUNC(SUM(dtl_margin)) AS margin_bulan_lalu,
                    TRUNC(SUM(dtl_ongkir)) AS ongkir_bulan_lalu,
                    ROW_NUMBER() OVER (ORDER BY dtl_tanggal ASC) AS rank_num
                FROM BaseData
                WHERE dtl_tanggal BETWEEN '$start_last_month' AND '$end_last_month'
                GROUP BY dtl_tanggal
            ),
            RankedSales_TY AS (
                SELECT 
                    dtl_tanggal,
                    TRUNC(SUM(dtl_netto)) AS sales_tahun_lalu,
                    TRUNC(SUM(dtl_margin)) AS margin_tahun_lalu,
                    TRUNC(SUM(dtl_ongkir)) AS ongkir_tahun_lalu,
                    ROW_NUMBER() OVER (ORDER BY dtl_tanggal ASC) AS rank_num
                FROM BaseData
                WHERE dtl_tanggal BETWEEN '$start_last_year' AND '$end_last_year'
                GROUP BY dtl_tanggal
            )
        ";

        if ($period == 'weekly') {
            // MINGGUAN
            $sql .= "
                , JoinedDaily AS (
                    SELECT 
                        COALESCE(bi.rank_num, bl.rank_num, ty.rank_num) as rank_num,
                        COALESCE(bi.sales_bulan_ini, 0) AS s_bi, COALESCE(bi.margin_bulan_ini, 0) AS m_bi, COALESCE(bi.ongkir_bulan_ini, 0) AS o_bi,
                        COALESCE(bl.sales_bulan_lalu, 0) AS s_bl, COALESCE(bl.margin_bulan_lalu, 0) AS m_bl, COALESCE(bl.ongkir_bulan_lalu, 0) AS o_bl,
                        COALESCE(ty.sales_tahun_lalu, 0) AS s_ty, COALESCE(ty.margin_tahun_lalu, 0) AS m_ty, COALESCE(ty.ongkir_tahun_lalu, 0) AS o_ty
                    FROM RankedSales_BI bi
                    FULL OUTER JOIN RankedSales_BL bl ON bi.rank_num = bl.rank_num
                    FULL OUTER JOIN RankedSales_TY ty ON bi.rank_num = ty.rank_num
                )
                SELECT 
                    'Minggu Ke-' || (CEIL(rank_num / 7.0))::int AS tanggal,
                    SUM(s_bi) as sales_bulan_ini, SUM(m_bi) as margin_bulan_ini, SUM(o_bi) as ongkir_bulan_ini,
                    SUM(s_bl) as sales_bulan_lalu, SUM(m_bl) as margin_bulan_lalu, SUM(o_bl) as ongkir_bulan_lalu,
                    SUM(s_ty) as sales_tahun_lalu, SUM(m_ty) as margin_tahun_lalu, SUM(o_ty) as ongkir_tahun_lalu
                FROM JoinedDaily
                GROUP BY CEIL(rank_num / 7.0)
                ORDER BY CEIL(rank_num / 7.0) ASC;
            ";
        } else {
            // HARIAN
            $sql .= "
                SELECT 
                    'Hari Ke-' || COALESCE(bi.rank_num, bl.rank_num, ty.rank_num) AS tanggal,
                    COALESCE(bi.sales_bulan_ini, 0) AS sales_bulan_ini,
                    COALESCE(bi.margin_bulan_ini, 0) AS margin_bulan_ini,
                    COALESCE(bi.ongkir_bulan_ini, 0) AS ongkir_bulan_ini,
                    
                    COALESCE(bl.sales_bulan_lalu, 0) AS sales_bulan_lalu,
                    COALESCE(bl.margin_bulan_lalu, 0) AS margin_bulan_lalu,
                    COALESCE(bl.ongkir_bulan_lalu, 0) AS ongkir_bulan_lalu,

                    COALESCE(ty.sales_tahun_lalu, 0) AS sales_tahun_lalu,
                    COALESCE(ty.margin_tahun_lalu, 0) AS margin_tahun_lalu,
                    COALESCE(ty.ongkir_tahun_lalu, 0) AS ongkir_tahun_lalu
                FROM RankedSales_BI bi
                FULL OUTER JOIN RankedSales_BL bl ON bi.rank_num = bl.rank_num
                FULL OUTER JOIN RankedSales_TY ty ON bi.rank_num = ty.rank_num
                ORDER BY COALESCE(bi.rank_num, bl.rank_num, ty.rank_num) ASC;
            ";
        }
    }

    // 5. EKSEKUSI
    $result = pg_query($conn, $sql);

    if ($result) {
        $data = pg_fetch_all($result);
        if (!$data) $data = [];

        // Hitung Total 
        if (count($data) > 0 && $period != 'monthly') {
            $total_row = [
                'tanggal' => 'Total',
                'sales_bulan_ini' => 0,
                'margin_bulan_ini' => 0,
                'ongkir_bulan_ini' => 0,
                'sales_bulan_lalu' => 0,
                'margin_bulan_lalu' => 0,
                'ongkir_bulan_lalu' => 0,
                'sales_tahun_lalu' => 0,
                'margin_tahun_lalu' => 0,
                'ongkir_tahun_lalu' => 0
            ];
            foreach ($data as $row) {
                $total_row['sales_bulan_ini'] += (float)$row['sales_bulan_ini'];
                $total_row['margin_bulan_ini'] += (float)$row['margin_bulan_ini'];
                $total_row['ongkir_bulan_ini'] += (float)$row['ongkir_bulan_ini'];

                $total_row['sales_bulan_lalu'] += (float)$row['sales_bulan_lalu'];
                $total_row['margin_bulan_lalu'] += (float)$row['margin_bulan_lalu'];
                $total_row['ongkir_bulan_lalu'] += (float)$row['ongkir_bulan_lalu'];

                $total_row['sales_tahun_lalu'] += (float)$row['sales_tahun_lalu'];
                $total_row['margin_tahun_lalu'] += (float)$row['margin_tahun_lalu'];
                $total_row['ongkir_tahun_lalu'] += (float)$row['ongkir_tahun_lalu'];
            }
            $data[] = $total_row;
        }

        $response['status'] = 'success';
        $response['message'] = 'Data berhasil diambil';
        $response['data'] = $data;
    } else {
        $response['message'] = 'Query error: ' . pg_last_error($conn);
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
