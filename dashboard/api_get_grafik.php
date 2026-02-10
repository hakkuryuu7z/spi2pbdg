<?php
include "../connection/index.php";

// Query ini akan mengambil total Sales, Margin, dan Netto untuk setiap hari di bulan berjalan.
// Menggunakan generate_series untuk memastikan semua tanggal ada, bahkan jika tidak ada transaksi.
$sql = "SELECT 
    dtl_tanggal AS tanggal,
    TRUNC(SUM(dtl_netto)) AS sales, -- Mengganti nama dtl_netto menjadi sales
    TRUNC(SUM(dtl_margin)) AS margin
    
/* * KOLOM-KOLOM INI KITA HAPUS DARI OUTPUT AKHIR:
 *
 * COUNT(DISTINCT(dtl_cusno)) AS jml_member,
 * COUNT(DISTINCT(dtl_struk)) AS struk,
 * COUNT(DISTINCT(dtl_prdcd_ctn)) AS produk,
 * SUM(dtl_qty_pcs) AS qty_in_pcs,
 * TRUNC(SUM(dtl_gross)) AS dtl_gross,
 * ROUND(SUM(dtl_margin)/SUM(dtl_netto) * 100 ,2) AS dtl_margin_persen
 */
 
FROM (
    (SELECT 
        dtl_rtype,
        dtl_tanggal,
        dtl_jam,
        dtl_struk,
        dtl_stat,
        dtl_kasir,
        dtl_no_struk,
        dtl_seqno,
        dtl_prdcd_ctn,
        dtl_prdcd,
        dtl_nama_barang,
        dtl_unit,
        dtl_frac,
        dtl_flag,
        dtl_tag,
        dtl_bkp,
        dtl_qty_pcs,
        dtl_qty,
        dtl_harga_jual,
        dtl_diskon,
        CASE WHEN dtl_rtype='S' THEN dtl_gross ELSE dtl_gross * -1 END as dtl_gross,
        CASE WHEN dtl_rtype='R' THEN (dtl_netto * -1) ELSE dtl_netto END as dtl_netto,
        CASE WHEN dtl_rtype='R' THEN (dtl_hpp * -1) ELSE dtl_hpp END as dtl_hpp,
        CASE WHEN dtl_rtype='S' THEN dtl_netto - dtl_hpp ELSE (dtl_netto - dtl_hpp) * -1 END as dtl_margin,
        dtl_k_div,
        dtl_nama_div,
        dtl_k_dept,
        dtl_nama_dept,
        dtl_k_katb,
        dtl_nama_katb,
        dtl_cusno,
        dtl_namamember,
        dtl_memberkhusus,
        dtl_outlet,
        dtl_suboutlet,
        dtl_kategori,
        dtl_sub_kategori,
        CASE WHEN dtl_tipemember = 'KHUSUS' THEN 'MM' ELSE '' END as MM,
        CASE WHEN dtl_tipemember = 'REGULER' THEN 'MB' ELSE '' END as MB,
        CASE WHEN dtl_tipemember = 'OMI' THEN 'OMI' ELSE '' END as OMI,
        dtl_tipemember,
        dtl_group_member,
        hgb_kodesupplier as dtl_kodesupplier,
        sup_namasupplier as dtl_namasupplier,
        cus_nosalesman
    FROM (
        -- Ini adalah subquery 'sls'
        SELECT 
            date_trunc('day', trjd_transactiondate) as dtl_tanggal,
            to_char(trjd_transactiondate, 'hh24:mi:ss') as dtl_jam,
            to_char(trjd_transactiondate, 'yyyymmdd') || trjd_create_by || trjd_cashierstation || trjd_transactionno || trjd_transactiontype as dtl_struk,
            trjd_cashierstation as dtl_stat,
            trjd_create_by as dtl_kasir,
            trjd_transactionno as dtl_no_struk,
            substr(trjd_prdcd, 1, 6) || '0' as dtl_prdcd_ctn,
            trjd_prdcd as dtl_prdcd,
            prd_deskripsipanjang as dtl_nama_barang,
            prd_unit as dtl_unit,
            prd_frac as dtl_frac,
            CASE 
                WHEN PRD_FLAGOMI = 'Y' AND PRD_FLAGIGR = 'Y' THEN 'IGR+OMI'
                WHEN PRD_FLAGOMI = 'Y' AND (PRD_FLAGIGR = 'N' OR PRD_FLAGIGR IS NULL) THEN 'OMI ONLY'
                WHEN (PRD_FLAGOMI IS NULL OR PRD_FLAGOMI = 'N') AND PRD_FLAGIGR = 'Y' THEN 'IGR ONLY'
                ELSE 'BELUM ADA FLAG' END AS dtl_flag,
            coalesce(prd_kodetag, ' ') as dtl_tag,
            trjd_flagtax1 as dtl_bkp,
            trjd_transactiontype as dtl_rtype,
            trim(trjd_divisioncode) as dtl_k_div,
            div_namadivisi as dtl_nama_div,
            substr(trjd_division, 1, 2) as dtl_k_dept,
            dep_namadepartement as dtl_nama_dept,
            substr(trjd_division, 3, 2) as dtl_k_katb,
            kat_namakategori as dtl_nama_katb,
            trjd_cus_kodemember as dtl_cusno,
            cus_namamember as dtl_namamember,
            cus_flagmemberkhusus as dtl_memberkhusus,
            cus_kodeoutlet as dtl_outlet,
            upper(cus_kodesuboutlet) as dtl_suboutlet,
            crm_kategori as dtl_kategori,
            crm_subkategori as dtl_sub_kategori,
            trjd_quantity as dtl_qty,
            trjd_unitprice as dtl_harga_jual,
            trjd_discount as dtl_diskon,
            trjd_seqno as dtl_seqno,
            CASE
                WHEN cus_jenismember = 'T' THEN 'TMI'
                WHEN cus_flagmemberkhusus = 'Y' THEN 'KHUSUS'
                WHEN trjd_create_by IN ('IDM', 'ID1', 'ID2') THEN 'IDM'
                WHEN trjd_create_by IN ('OMI', 'BKL') THEN 'OMI'
                ELSE 'REGULER'
            END as dtl_tipemember,
            CASE
                WHEN cus_flagmemberkhusus = 'Y' THEN 'GROUP_1_KHUSUS'
                WHEN trjd_create_by = 'IDM' THEN 'GROUP_2_IDM'
                WHEN trjd_create_by IN ('OMI', 'BKL') THEN 'GROUP_3_OMI'
                WHEN cus_flagmemberkhusus IS NULL AND cus_kodeoutlet = '6' THEN 'GROUP_4_END_USER'
                ELSE 'GROUP_5_OTHERS'
            END as dtl_group_member,
            CASE
                WHEN prd_unit = 'KG' AND prd_frac = 1000 THEN trjd_quantity
                ELSE trjd_quantity * prd_frac
            END as dtl_qty_pcs,
            CASE
                WHEN trjd_flagtax2 = 'Y' AND trjd_create_by IN ('IDM', 'OMI', 'BKL') THEN trjd_nominalamt * 11.1 / 10
                ELSE trjd_nominalamt
            END as dtl_gross,
            -- Logika dtl_netto
            CASE
                WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN
                    CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END
                ELSE
                    CASE
                        WHEN coalesce(tko_kodesbu, 'z') IN ('O', 'I') THEN
                            CASE
                                WHEN tko_tipeomi IN ('HE', 'HG') THEN
                                    trjd_nominalamt - (
                                        CASE
                                            WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN
                                                (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100))))
                                            ELSE 0
                                        END
                                    )
                                ELSE trjd_nominalamt
                            END
                        ELSE
                            trjd_nominalamt - (
                                CASE
                                    WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0
                                    ELSE
                                        CASE
                                            WHEN trjd_flagtax2 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ('Y', 'z') AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN
                                                (trjd_nominalamt - (trjd_nominalamt / (1 + (coalesce(prd_ppn, 10) / 100))))
                                            ELSE 0
                                        END
                                END
                            )
                    END
            END as dtl_netto,
            -- Logika dtl_hpp
            CASE
                WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN
                    CASE
                        WHEN 'Y' = 'Y' THEN
                            trjd_nominalamt - (
                                CASE
                                    WHEN prd_markupstandard IS NULL THEN (5 * trjd_nominalamt) / 100
                                    ELSE (prd_markupstandard * trjd_nominalamt) / 100
                                END
                            )
                    END
                ELSE
                    (trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END) * trjd_baseprice
            END as dtl_hpp,
            cus_nosalesman
        FROM 
            tbtr_jualdetail
            LEFT JOIN tbmaster_prodmast ON trjd_prdcd = prd_prdcd
            LEFT JOIN tbmaster_tokoigr ON trjd_cus_kodemember = tko_kodecustomer
            LEFT JOIN tbmaster_customer ON trjd_cus_kodemember = cus_kodemember
            LEFT JOIN tbmaster_customercrm ON trjd_cus_kodemember = crm_kodemember
            LEFT JOIN tbmaster_divisi ON trjd_division = div_kodedivisi
            LEFT JOIN tbmaster_departement ON substr(trjd_division, 1, 2) = dep_kodedepartement
            LEFT JOIN tbmaster_kategori ON trjd_division = kat_kodedepartement || kat_kodekategori
            
        -- Filter bisa ditambahkan di sini jika perlu, misal:
        -- WHERE trjd_recordid IS NULL
        -- AND cus_flagmemberkhusus = 'Y'
            
    ) sls 
    LEFT JOIN (
        -- Ini adalah subquery 'gb'
        SELECT m.hgb_prdcd hgb_prdcd,
               m.hgb_kodesupplier,
               s.sup_namasupplier
        FROM tbmaster_hargabeli m
        LEFT JOIN tbmaster_supplier s ON m.hgb_kodesupplier = s.sup_kodesupplier
        WHERE m.hgb_tipe = '2'
          AND m.hgb_recordid IS NULL
    ) gb ON dtl_prdcd_ctn = hgb_prdcd
)
) detailstruk 
WHERE 
    dtl_tanggal >= DATE_TRUNC('month', CURRENT_DATE) 
    AND dtl_tanggal <= CURRENT_DATE
GROUP BY 
    dtl_tanggal
HAVING 
    COALESCE(SUM(dtl_netto), 0) <> 0
ORDER BY 
    MIN(dtl_tanggal) ASC;";

$query = pg_query($conn, $sql);

// Siapkan array untuk menampung data yang akan di-encode ke JSON
$sales_data = [];
$margin_data = [];
$netto_data = [];
$tanggal_data = [];

while ($row = pg_fetch_assoc($query)) {
    $tanggal_data[] = $row['tanggal'];
    $sales_data[] = $row['sales'];
    $margin_data[] = $row['margin'];
}

$response = [
    'tanggal' => $tanggal_data,
    'sales' => $sales_data,
    'margin' => $margin_data
];

header('Content-Type: application/json');
echo json_encode($response);
