<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../connection/index.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$kode_member = isset($_GET['kode_member']) ? pg_escape_string($conn, $_GET['kode_member']) : '';

// Ubah format YYYY-MM-DD jadi YYYYMMDD untuk query to_char
$start_str = date('Ymd', strtotime($start_date));
$end_str   = date('Ymd', strtotime($end_date));

if (empty($kode_member)) {
    echo json_encode(["status" => "error", "message" => "Kode member kosong"]);
    exit;
}

$sql = "
SELECT
    dtl_cusno,
    dtl_namamember,
    cus_nosalesman as mr,
    cus_jarak as jarak,
    COUNT(DISTINCT(dtl_struk))                                  AS struk,
    SUM(dtl_gross)                                              AS dtl_gross,
    round(SUM(dtl_gross) / NULLIF(COUNT(DISTINCT(dtl_struk)), 0), 2) AS avg_gross_per_struk,
    round(SUM(dtl_margin) / NULLIF(SUM(dtl_netto), 0) * 100, 2) AS dtl_margin_persen,
    SUM(CASE WHEN dtl_seqno = 1 THEN pot_ongkir ELSE 0 END)     as total_ongkir
FROM
    (
        SELECT
            dtl_rtype, dtl_tanggal, dtl_jam, dtl_struk, dtl_stat, dtl_kasir, dtl_no_struk, dtl_seqno,
            dtl_prdcd_ctn, dtl_prdcd, dtl_nama_barang, dtl_unit, dtl_frac, dtl_tag, dtl_bkp,
            dtl_qty_pcs, dtl_qty, dtl_harga_jual, dtl_diskon,
            CASE WHEN dtl_rtype = 'S' THEN dtl_gross ELSE dtl_gross * - 1 END AS dtl_gross,
            CASE WHEN dtl_rtype = 'R' THEN ( dtl_netto * - 1 ) ELSE dtl_netto END AS dtl_netto,
            CASE WHEN dtl_rtype = 'R' THEN ( dtl_hpp * - 1 ) ELSE dtl_hpp END AS dtl_hpp,
            CASE WHEN dtl_rtype = 'S' THEN dtl_netto - dtl_hpp ELSE ( dtl_netto - dtl_hpp ) * - 1 END AS dtl_margin,
            dtl_k_div, dtl_nama_div, dtl_k_dept, dtl_nama_dept, dtl_k_katb, dtl_nama_katb,
            dtl_cusno, dtl_namamember, dtl_memberkhusus, dtl_outlet, dtl_suboutlet, dtl_kategori,
            dtl_sub_kategori, dtl_tipemember, dtl_group_member,
            hgb_kodesupplier AS dtl_kodesupplier, sup_namasupplier AS dtl_namasupplier,
            jh_belanja_pertama AS dtl_belanja_pertama, jh_belanja_terakhir AS dtl_belanja_terakhir,
            cus_nosalesman, cus_jarak, obi_recid, obi_kdekspedisi,
            COALESCE(klk.pot_ongkir, 0) AS pot_ongkir
        FROM
            (
                SELECT
                    date_trunc('day', trjd_transactiondate)          AS dtl_tanggal,
                    to_char(trjd_transactiondate, 'hh24:mi:ss')      AS dtl_jam,
                    to_char(trjd_transactiondate, 'yyyymmdd') || trjd_create_by || trjd_transactionno || trjd_transactiontype AS dtl_struk,
                    trjd_cashierstation                              AS dtl_stat,
                    trjd_create_by                                   AS dtl_kasir,
                    trjd_transactionno                               AS dtl_no_struk,
                    substr(trjd_prdcd, 1, 6) || '0'                  AS dtl_prdcd_ctn,
                    trjd_prdcd                                       AS dtl_prdcd,
                    prd_deskripsipanjang                             AS dtl_nama_barang,
                    prd_unit                                         AS dtl_unit,
                    prd_frac                                         AS dtl_frac,
                    coalesce(prd_kodetag, ' ')                       AS dtl_tag,
                    trjd_flagtax1                                    AS dtl_bkp,
                    trjd_transactiontype                             AS dtl_rtype,
                    TRIM(trjd_divisioncode)                          AS dtl_k_div,
                    div_namadivisi                                   AS dtl_nama_div,
                    substr(trjd_division, 1, 2)                      AS dtl_k_dept,
                    dep_namadepartement                              AS dtl_nama_dept,
                    substr(trjd_division, 3, 2)                      AS dtl_k_katb,
                    kat_namakategori                                 AS dtl_nama_katb,
                    trjd_cus_kodemember                              AS dtl_cusno,
                    cus_namamember                                   AS dtl_namamember,
                    cus_flagmemberkhusus                             AS dtl_memberkhusus,
                    cus_kodeoutlet                                   AS dtl_outlet,
                    upper(cus_kodesuboutlet)                         AS dtl_suboutlet,
                    crm_kategori                                     AS dtl_kategori,
                    crm_subkategori                                  AS dtl_sub_kategori,
                    trjd_quantity                                    AS dtl_qty,
                    trjd_unitprice                                   AS dtl_harga_jual,
                    trjd_discount                                    AS dtl_diskon,
                    trjd_seqno                                       AS dtl_seqno,
                    CASE WHEN cus_jenismember = 'T' THEN 'TMI' WHEN cus_flagmemberkhusus = 'Y' THEN 'KHUSUS' WHEN trjd_create_by IN ( 'IDM', 'ID1', 'ID2' ) THEN 'IDM' WHEN trjd_create_by IN ( 'OMI', 'BKL' ) THEN 'OMI' ELSE 'REGULER' END AS dtl_tipemember,
                    CASE WHEN cus_flagmemberkhusus = 'Y' THEN 'GROUP_1_KHUSUS' WHEN trjd_create_by = 'IDM' THEN 'GROUP_2_IDM' WHEN trjd_create_by IN ( 'OMI', 'BKL' ) THEN 'GROUP_3_OMI' WHEN cus_flagmemberkhusus IS NULL AND cus_kodeoutlet = '6' THEN 'GROUP_4_END_USER' ELSE 'GROUP_5_OTHERS' END AS dtl_group_member,
                    CASE WHEN prd_unit = 'KG' AND prd_frac = 1000 THEN trjd_quantity ELSE trjd_quantity * prd_frac END AS dtl_qty_pcs,
                    CASE WHEN trjd_flagtax1 = 'Y' AND trjd_create_by IN ( 'IDM', 'OMI', 'BKL' ) THEN trjd_nominalamt * 11.1 / 10 ELSE trjd_nominalamt END AS dtl_gross,
                    CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt END ELSE CASE WHEN coalesce(tko_kodesbu, 'z') IN ( 'O', 'I' ) THEN CASE WHEN tko_tipeomi IN ( 'HE', 'HG' ) THEN trjd_nominalamt - ( CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END ) ELSE trjd_nominalamt END ELSE trjd_nominalamt - ( CASE WHEN substr(trjd_create_by, 1, 2) = 'EX' THEN 0 ELSE CASE WHEN trjd_flagtax1 = 'Y' AND coalesce(trjd_flagtax2, 'z') IN ( 'Y', 'z' ) AND coalesce(prd_kodetag, 'zz') <> 'Q' THEN ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) ) ELSE 0 END END ) END END AS dtl_netto,
                    CASE WHEN trjd_divisioncode = '5' AND substr(trjd_division, 1, 2) = '39' THEN CASE WHEN 'Y' = 'Y' THEN trjd_nominalamt - ( CASE WHEN prd_markupstandard IS NULL THEN ( 5 * trjd_nominalamt ) / 100 ELSE ( prd_markupstandard * trjd_nominalamt ) / 100 END ) END ELSE ( trjd_quantity / CASE WHEN prd_unit = 'KG' THEN 1000 ELSE 1 END ) * trjd_baseprice END AS dtl_hpp,
                    cus_nosalesman as cus_nosalesman,
                    cus_jarak, obi_nopb, obi_recid, obi_kdekspedisi
                FROM
                    tbtr_jualdetail
                    LEFT JOIN tbmaster_prodmast ON trjd_prdcd = prd_prdcd
                    LEFT JOIN tbmaster_tokoigr ON trjd_cus_kodemember = tko_kodecustomer
                    LEFT JOIN tbmaster_customer ON trjd_cus_kodemember = cus_kodemember
                    LEFT JOIN tbmaster_customercrm ON trjd_cus_kodemember = crm_kodemember
                    LEFT JOIN tbmaster_divisi ON trjd_division = div_kodedivisi
                    LEFT JOIN tbmaster_departement ON substr(trjd_division, 1, 2) = dep_kodedepartement
                    LEFT JOIN tbmaster_kategori ON trjd_division = kat_kodedepartement || kat_kodekategori
                    LEFT JOIN tbtr_obi_h ON trjd_transactiondate = obi_tglstruk and trjd_cus_kodemember = obi_kdmember and trjd_transactionno = obi_nostruk 
            ) sls
            LEFT JOIN ( SELECT m.hgb_prdcd hgb_prdcd, m.hgb_kodesupplier, s.sup_namasupplier FROM tbmaster_hargabeli m LEFT JOIN tbmaster_supplier s ON m.hgb_kodesupplier = s.sup_kodesupplier WHERE m.hgb_tipe = '2' AND m.hgb_recordid IS NULL ) gb ON dtl_prdcd_ctn = hgb_prdcd
            LEFT JOIN ( SELECT jh_cus_kodemember, date_trunc('day', MIN(jh_transactiondate)) AS jh_belanja_pertama, date_trunc('day', MAX(jh_transactiondate)) AS jh_belanja_terakhir FROM tbtr_jualheader WHERE jh_cus_kodemember IS NOT NULL GROUP BY jh_cus_kodemember ) blj ON dtl_cusno = jh_cus_kodemember
            LEFT JOIN ( SELECT kode_member, no_pb, pot_ongkir FROM payment_klikigr WHERE pot_ongkir <> 0 ) klk ON dtl_cusno = kode_member AND obi_nopb = no_pb
    ) z
WHERE
    to_char(dtl_tanggal, 'yyyymmdd') BETWEEN '$start_str' and '$end_str'
    and dtl_cusno = '$kode_member'
GROUP BY 
    z.dtl_outlet, z.dtl_suboutlet, z.dtl_cusno, z.dtl_namamember, z.cus_nosalesman, z.cus_jarak
HAVING coalesce(SUM(dtl_netto),0) <> 0 
ORDER BY dtl_outlet, dtl_suboutlet, dtl_cusno, dtl_namamember
LIMIT 1;
";

$query = pg_query($conn, $sql);

if ($query) {
    $row = pg_fetch_assoc($query);
    if ($row) {
        echo json_encode(["status" => "success", "data" => $row]);
    } else {
        echo json_encode(["status" => "error", "message" => "Data tidak ditemukan"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
}
