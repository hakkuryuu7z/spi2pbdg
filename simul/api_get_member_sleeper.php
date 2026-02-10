<?php
// Pastikan file koneksi database Anda sudah di-include di sini
include '../connection/index.php'; // Sesuaikan path jika perlu

header('Content-Type: application/json');

// 1. Ambil parameter interval (default 2 bulan)
// Kita casting ke (int) untuk keamanan agar tidak bisa di-inject SQL
$interval = isset($_GET['interval']) ? (int)$_GET['interval'] : 2;

// Validasi minimal 1 bulan
if ($interval < 1) {
    $interval = 2;
}

try {
    // 2. QUERY LENGKAP
    // Menggunakan syntax Heredoc (<<<SQL) agar aman dari conflict tanda kutip
    $sql = <<<SQL
    select
        kode,
        nama_member,
        alamat_usaha || ', ' ||
        kecamatan_usaha || ', ' ||
        kelurahan_usaha || ', ' ||
        kode_pos as alamat,
        no_aktif,
        amm_email,
        regis,
        to_char(kunjungan_terakhir, 'DD-Mon-YYYY') as tgl_kunjungan_terakhir,
        kunjungan_terakhir, -- Kolom raw untuk debugging/sorting
        salesman,
        dtl_gross
    from
        (
        select
            cab_kodewilayah,
            kode_member kode,
            status,
            tgl_nonaktif,
            outlet,
            cus_kodesuboutlet sub_outlet,
            crm_kategori kategori,
            crm_subkategori sub_kategori,
            flag_member,
            nama_member,
            nama_ktp,
            regis,
            kunjungan_pertama,
            kunjungan_terakhir,
            tgl_lahir,
            alamat_rumah,
            kodepos,
            kelurahan_rumah,
            no_aktif,
            no_nonaktif,
            keterangan,
            jarak,
            salesman,
            id_segment,
            alamat_usaha,
            kecamatan_usaha,
            kelurahan_usaha,
            kode_pos,
            kordinat,
            no_ktp,
            npwp,
            seg_nama,
            case
                when i_saku is null then 'T'
                else 'Y'
            end i_saku,
            case
                when member_mitra is null then 'T'
                else 'Y'
            end mitra,
            bank_ina,
            jenis_customer,
            amm_email,
            pkp,
            crm_jeniskelamin
        from
            (
            select
                distinct
                kode_member,
                status,
                outlet,
                cus_kodesuboutlet,
                cab_kodewilayah,
                flag_member,
                nama_member,
                nama_ktp,
                tgl_lahir,
                alamat_rumah,
                kodepos,
                kelurahan_rumah,
                hp_member as no_aktif,
                no_nonaktif,
                case
                    when hp_member <> no_nonaktif then 'SUDAH_GANTI_NOMOR'
                    else '-'
                end keterangan,
                jarak,
                kecamatan_usaha ,
                salesman,
                id_segment,
                alamat_usaha,
                kelurahan_usaha,
                regis,
                kode_pos,
                kordinat,
                no_ktp,
                crm_kategori,
                crm_subkategori,
                npwp,
                seg_nama,
                bank_ina,
                tgl_nonaktif,
                kunjungan_pertama,
                kunjungan_terakhir,
                jenis_customer,
                amm_email,
                pkp,
                crm_jeniskelamin
            from
                (
                select
                    *
                from
                    (
                    select
                        cus_kodemember kode_member,
                        cab_kodewilayah,
                        cus_kodeoutlet outlet,
                        cus_flagmemberkhusus flag_member,
                        cus_namamember nama_member,
                        cus_namaktp nama_ktp,
                        cus_alamatmember1 alamat_rumah,
                        cus_alamatmember3 kodepos,
                        cus_alamatmember4 kelurahan_rumah,
                        cus_tlpmember tlp_member,
                        cus_kecamatan_ktp kecamatan_ktp,
                        cus_jarak jarak,
                        cus_nosalesman salesman,
                        cus_kodesuboutlet,
                        cus_hpmember hp_member,
                        cus_flagpkp pkp,
                        cus_noktp no_ktp,
                        cus_tglregistrasi regis,
                        case
                            when cus_jenismember = 'G' then 'GROUP'
                            when cus_jenismember = 'O' then 'OMI'
                            when cus_jenismember = 'I' then 'INDOMARET'
                            when cus_jenismember = 'F' then 'FREEPASS'
                            when cus_jenismember = 'L' then 'KLIKINDOGROSIR'
                            when cus_jenismember = 'T' then 'TOKO MITRA IGR'
                            else 'BIASA'
                        end as jenis_customer,
                        cus_tgllahir as tgl_lahir,
                        cus_npwp as npwp,
                        cus_flag_ina as bank_ina,
                        cus_nonaktif_dt as tgl_nonaktif,
                        MIN(jh_transactiondate) as kunjungan_pertama,
                        MAX(jh_transactiondate) as kunjungan_terakhir
                    from
                        tbmaster_customer
                    left join tbtr_jualheader on
                        jh_cus_kodemember = cus_kodemember
                    left join tbmaster_cabang on
                        cus_kodeigr = cab_kodecabang
                    where
                        cus_kodeigr = '2P'
                        or cus_kodemember = 'KLZ1RI'
                        and cus_namamember <> 'NEW'
                        and cus_recordid is null    
                    group by
                        cus_kodemember,
                        cus_jenismember,
                        cus_flagpkp,
                        cus_recordid,
                        cab_kodewilayah,
                        cus_kodeoutlet,
                        cus_flagmemberkhusus,
                        cus_kecamatan_ktp ,
                        cus_tglregistrasi,
                        cus_namamember,
                        cus_alamatmember1,
                        cus_alamatmember3,
                        cus_alamatmember4,
                        cus_tlpmember,
                        cus_jarak,
                        cus_nosalesman,
                        cus_hpmember,
                        cus_noktp,
                        cus_tgllahir,
                        cus_kodesuboutlet,
                        cus_npwp,
                        cus_flag_ina,
                        cus_nonaktif_dt,
                        cus_namaktp
                    ) cus,
                    (
                    select
                        case
                            when crm_recordid is null then 'AKTIF'
                            else 'NON AKTIF'
                        end status,
                        crm_kodemember,
                        crm_nohppic1 as no_nonaktif,
                        crm_idsegment as id_segment,
                        crm_alamatusaha1 as alamat_usaha,
                        crm_email as amm_email,
                        crm_alamatusaha4 as kelurahan_usaha,
                        crm_kecamatan_usaha as kecamatan_usaha,
                        crm_alamatusaha3 as kode_pos,
                        crm_koordinat as kordinat,
                        seg_nama,
                        crm_kategori,
                        crm_subkategori,
                        crm_jeniskelamin
                    from
                        tbmaster_customercrm
                    left join tbmaster_segmentasi on
                        crm_idsegment = seg_id
                    ) crm
                where
                    kode_member = crm_kodemember
            ) cc
            left join tbmaster_kodepos on
                kode_pos = pos_kode
                and kelurahan_usaha = pos_kelurahan
        ) ccp
        left join (
            select
                jh_cus_kodemember member_i,
                SUM(jh_isaku_amt) i_saku
            from
                tbtr_jualheader
            where
                jh_isaku_amt > 0
            group by
                jh_cus_kodemember
        ) isk on
            kode_member = member_i
        left join (
            select
                distinct
                dpp_kodemember member_mitra
            from
                tbtr_deposit_mitraigr
        ) mit on
            kode_member = member_mitra
        where
            kode_member <> 'E65273') ahuy
    left join 
    (
        select
            dtl_outlet,
            dtl_suboutlet,
            dtl_cusno,
            dtl_namamember,
            COUNT(distinct(dtl_tanggal)) as kunjungan,
            COUNT(distinct(dtl_struk)) as struk,
            COUNT(distinct(dtl_prdcd_ctn)) as produk,
            SUM(dtl_qty_pcs) as qty_in_pcs,
            SUM(dtl_gross) as dtl_gross,
            SUM(dtl_netto) as dtl_netto,
            SUM(dtl_margin) as dtl_margin,
            round(SUM(dtl_margin) / SUM(dtl_netto) * 100, 2) as dtl_margin_persen,
            MIN(dtl_belanja_pertama) as dtl_belanja_pertama,
            MAX(dtl_belanja_terakhir) as dtl_belanja_terakhir
        from
            (
            select
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
                dtl_tag,
                dtl_bkp,
                dtl_qty_pcs,
                dtl_qty,
                dtl_harga_jual,
                dtl_diskon,
                case
                    when dtl_rtype = 'S' then dtl_gross
                    else dtl_gross * - 1
                end as dtl_gross,
                case
                    when dtl_rtype = 'R' then ( dtl_netto * - 1 )
                    else dtl_netto
                end as dtl_netto,
                case
                    when dtl_rtype = 'R' then ( dtl_hpp * - 1 )
                    else dtl_hpp
                end as dtl_hpp,
                case
                    when dtl_rtype = 'S' then dtl_netto - dtl_hpp
                    else ( dtl_netto - dtl_hpp ) * - 1
                end as dtl_margin,
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
                dtl_tipemember,
                dtl_group_member,
                hgb_kodesupplier as dtl_kodesupplier,
                sup_namasupplier as dtl_namasupplier,
                jh_belanja_pertama as dtl_belanja_pertama,
                jh_belanja_terakhir as dtl_belanja_terakhir
            from
                (
                select
                    date_trunc('day', trjd_transactiondate) as dtl_tanggal,
                    to_char(trjd_transactiondate, 'hh24:mi:ss') as dtl_jam,
                    to_char(trjd_transactiondate, 'yyyymmdd') || trjd_create_by || trjd_transactionno || trjd_transactiontype as dtl_struk,
                    trjd_cashierstation as dtl_stat,
                    trjd_create_by as dtl_kasir,
                    trjd_transactionno as dtl_no_struk,
                    substr(trjd_prdcd, 1, 6) || '0' as dtl_prdcd_ctn,
                    trjd_prdcd as dtl_prdcd,
                    prd_deskripsipanjang as dtl_nama_barang,
                    prd_unit as dtl_unit,
                    prd_frac as dtl_frac,
                    coalesce(prd_kodetag, ' ') as dtl_tag,
                    trjd_flagtax1 as dtl_bkp,
                    trjd_transactiontype as dtl_rtype,
                    TRIM(trjd_divisioncode) as dtl_k_div,
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
                    case
                        when cus_jenismember = 'T' then 'TMI'
                        when cus_flagmemberkhusus = 'Y' then 'KHUSUS'
                        when trjd_create_by in ( 'IDM', 'ID1', 'ID2' ) then 'IDM'
                        when trjd_create_by in ( 'OMI', 'BKL' ) then 'OMI'
                        else 'REGULER'
                    end as dtl_tipemember,
                    case
                        when cus_flagmemberkhusus = 'Y' then 'GROUP_1_KHUSUS'
                        when trjd_create_by = 'IDM' then 'GROUP_2_IDM'
                        when trjd_create_by in ( 'OMI', 'BKL' ) then 'GROUP_3_OMI'
                        when cus_flagmemberkhusus is null and cus_kodeoutlet = '6' then 'GROUP_4_END_USER'
                        else 'GROUP_5_OTHERS'
                    end as dtl_group_member,
                    case
                        when prd_unit = 'KG' and prd_frac = 1000 then trjd_quantity
                        else trjd_quantity * prd_frac
                    end as dtl_qty_pcs,
                    case
                        when trjd_flagtax1 = 'Y' and trjd_create_by in ( 'IDM', 'OMI', 'BKL' ) then trjd_nominalamt * 11.1 / 10
                        else trjd_nominalamt
                    end as dtl_gross,
                    case
                        when trjd_divisioncode = '5' and substr(trjd_division, 1, 2) = '39' then
                            case when 'Y' = 'Y' then trjd_nominalamt end
                        else
                            case when coalesce(tko_kodesbu, 'z') in ( 'O', 'I' ) then
                                case when tko_tipeomi in ( 'HE', 'HG' ) then
                                    trjd_nominalamt - (
                                        case when trjd_flagtax1 = 'Y' and coalesce(trjd_flagtax2, 'z') in ( 'Y', 'z' ) and coalesce(prd_kodetag, 'zz') <> 'Q' then
                                            ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) )
                                        else 0 end
                                    )
                                else trjd_nominalamt end
                            else
                                trjd_nominalamt - (
                                    case when substr(trjd_create_by, 1, 2) = 'EX' then 0
                                    else
                                        case when trjd_flagtax1 = 'Y' and coalesce(trjd_flagtax2, 'z') in ( 'Y', 'z' ) and coalesce(prd_kodetag, 'zz') <> 'Q' then
                                            ( trjd_nominalamt - ( trjd_nominalamt / ( 1 + ( coalesce(prd_ppn, 10) / 100 ) ) ) )
                                        else 0 end
                                    end
                                )
                            end
                    end as dtl_netto,
                    case
                        when trjd_divisioncode = '5' and substr(trjd_division, 1, 2) = '39' then
                            case when 'Y' = 'Y' then
                                trjd_nominalamt - (
                                    case when prd_markupstandard is null then ( 5 * trjd_nominalamt ) / 100
                                    else ( prd_markupstandard * trjd_nominalamt ) / 100 end
                                )
                            end
                        else
                            ( trjd_quantity /
                                case when prd_unit = 'KG' then 1000 else 1 end
                            ) * trjd_baseprice
                    end as dtl_hpp
                from
                    tbtr_jualdetail
                left join tbmaster_prodmast on trjd_prdcd = prd_prdcd
                left join tbmaster_tokoigr on trjd_cus_kodemember = tko_kodecustomer
                left join tbmaster_customer on trjd_cus_kodemember = cus_kodemember
                left join tbmaster_customercrm on trjd_cus_kodemember = crm_kodemember
                left join tbmaster_divisi on trjd_division = div_kodedivisi
                left join tbmaster_departement on substr(trjd_division, 1, 2) = dep_kodedepartement
                left join tbmaster_kategori on trjd_division = kat_kodedepartement || kat_kodekategori
            ) sls
            left join (
                select
                    m.hgb_prdcd hgb_prdcd,
                    m.hgb_kodesupplier,
                    s.sup_namasupplier
                from
                    tbmaster_hargabeli m
                left join tbmaster_supplier s on m.hgb_kodesupplier = s.sup_kodesupplier
                where
                    m.hgb_tipe = '2'
                    and m.hgb_recordid is null
            ) gb on dtl_prdcd_ctn = hgb_prdcd
            left join (
                select
                    jh_cus_kodemember,
                    date_trunc('day', MIN(jh_transactiondate)) as jh_belanja_pertama,
                    date_trunc('day', MAX(jh_transactiondate)) as jh_belanja_terakhir
                from
                    tbtr_jualheader
                where
                    jh_cus_kodemember is not null
                group by
                    jh_cus_kodemember
            ) blj on dtl_cusno = jh_cus_kodemember
        ) z
        group by
            z.dtl_outlet,
            z.dtl_suboutlet,
            z.dtl_cusno,
            z.dtl_namamember
        having
            coalesce(SUM(dtl_netto), 0) <> 0
    ) dtl on kode = dtl_cusno
    WHERE
        -- =========================================================
        -- LOGIKA MEMBER SLEEPER (DINAMIS SESUAI INTERVAL)
        -- =========================================================
        date_trunc('month', kunjungan_terakhir) = date_trunc('month', CURRENT_DATE - INTERVAL '{$interval} month')
        
    ORDER BY
        kunjungan_terakhir DESC
SQL;

    // 3. EKSEKUSI QUERY
    $result = pg_query($conn, $sql);

    if (!$result) {
        throw new Exception("Database Error: " . pg_last_error($conn));
    }

    $data = pg_fetch_all($result);

    echo json_encode([
        'status' => 'success',
        'interval_text' => $interval . ' Bulan Lalu',
        'data' => $data ? $data : []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
