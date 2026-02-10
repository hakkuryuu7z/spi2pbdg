<?php

header('Content-Type: application/json');

require_once "../connection/index.php";

$response = [];

try {
	$sql = "select
	plu,
	deskripsi,
	unit,
	frac,
	pkm,
	AVG_bln,
	(AVG_bln / 30 ) as hari,
	st_avgcost,
	st_po_qty,
	st_no_po,
	st_saldo_in_ctn,
	st_saldo_in_pcs,
	(st_saldo_in_ctn * frac + st_saldo_in_pcs)  as LPP_PCS,
	st_nama_supplier
from
	(
	select
		prd.prd_prdcd as plu,
		prd.prd_deskripsipanjang as deskripsi,
		prd.prd_unit as unit,
		prd.prd_frac as frac,
		pkm.pkm_pkm as pkm,
		(coalesce(spd.spd_qty_1,0) +
		coalesce(spd.spd_qty_2,0) +
		coalesce(spd.spd_qty_3,0)+
		coalesce(stk.st_sales,0)) / 4 as AVG_bln,
		case
			when prd.prd_unit = 'KG'
			and prd.prd_frac = 1000 then stk.st_avgcost * prd.prd_frac / 1000
			else stk.st_avgcost * prd.prd_frac
		end st_avgcost,
		poo.tpod_qtypo as st_po_qty,
		poo.tpod_nopo as st_no_po,
		coalesce(TRUNC(stk.st_saldoakhir / NULLIF(prd.prd_frac, 0)), 0) as st_saldo_in_ctn,
		coalesce(stk.st_saldoakhir % prd.prd_frac, 0) as st_saldo_in_pcs,
		coalesce(hgb.hgb_namasupplier, 'Z9999 Tidak diketahui') as st_nama_supplier		
		
	from
		tbmaster_prodmast prd
	left join (
		select
			*
		from
			tbmaster_stock
		where
			st_lokasi = '01') stk on
		substr(prd.prd_prdcd, 1, 6) || '0' = stk.st_prdcd
	left join (
		select
			prmd_prdcd,
			prmd_hrgjual,
			prmd_tglawal,
			prmd_tglakhir
		from
			tbtr_promomd
		where
			date_trunc('days', CURRENT_DATE) between date_trunc('days', prmd_tglawal) and date_trunc('days', prmd_tglakhir)) prm on
		prd.prd_prdcd = prm.prmd_prdcd
	left join tbmaster_kkpkm pkm on
		prd.prd_prdcd = pkm.pkm_prdcd
	left join tbmaster_pkmplus pkmp on
		prd.prd_prdcd = pkmp.pkmp_prdcd
	left join tbmaster_divisi div on
		prd.prd_kodedivisi = div.div_kodedivisi
	left join tbmaster_departement dep on
		prd.prd_kodedepartement = dep.dep_kodedepartement
	left join (
		select
			*
		from
			tbmaster_kph) kph on
		prd.prd_plumcg = kph.prdcd
	left join (
		select
			hgb.hgb_prdcd,
			hgb.hgb_hrgbeli,
			hgb.hgb_statusbarang,
			hgb.hgb_tglmulaidisc01,
			hgb.hgb_tglakhirdisc01,
			hgb.hgb_persendisc01,
			hgb.hgb_rphdisc01,
			hgb.hgb_flagdisc01,
			hgb.hgb_tglmulaidisc02,
			hgb.hgb_tglakhirdisc02,
			hgb.hgb_persendisc02,
			hgb.hgb_rphdisc02,
			hgb.hgb_flagdisc02,
			hgb.hgb_nilaidpp,
			hgb.hgb_top,
			hgb.hgb_kodesupplier,
			sup.sup_namasupplier as hgb_namasupplier,
			sup.sup_jangkawaktukirimbarang as hgb_lead_time,
			sup.sup_minrph as hgb_minrph
		from
			tbmaster_hargabeli hgb
		left join tbmaster_supplier sup on
			hgb.hgb_kodesupplier = sup.sup_kodesupplier
		where
			hgb.hgb_tipe = '2' ) hgb on
		substr(prd.prd_prdcd, 1, 6) || '0' = hgb.hgb_prdcd
	left join (
		select
			kat_kodedepartement || kat_kodekategori as kat_kodekategori,
			kat_namakategori
		from
			tbmaster_kategori) kat on
		prd.prd_kodedepartement || prd.prd_kodekategoribarang = kat.kat_kodekategori
	left join (select
            sls_prdcd as spd_prdcd,
            -- === BAGIAN INI SUDAH OTOMATIS DARI SEBELUMNYA ===
            
            -- 1. AMBIL DATA 3 BULAN LALU (spd_qty_1)
            coalesce(CASE 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN sls_qty_10 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN sls_qty_11 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN sls_qty_12 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN sls_qty_01
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN sls_qty_02
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN sls_qty_03
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN sls_qty_04
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN sls_qty_05
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN sls_qty_06
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN sls_qty_07
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN sls_qty_08 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN sls_qty_09
            END, 0) as spd_qty_1,

            -- 2. AMBIL DATA 2 BULAN LALU (spd_qty_2)
            coalesce(CASE 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN sls_qty_11
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN sls_qty_12
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN sls_qty_01
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN sls_qty_02
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN sls_qty_03
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN sls_qty_04
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN sls_qty_05
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN sls_qty_06
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN sls_qty_07
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN sls_qty_08
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN sls_qty_09 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN sls_qty_10
            END, 0) as spd_qty_2,

            -- 3. AMBIL DATA 1 BULAN LALU (spd_qty_3)
            coalesce(CASE 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN sls_qty_12
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN sls_qty_01
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN sls_qty_02
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN sls_qty_03
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN sls_qty_04
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN sls_qty_05
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN sls_qty_06
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN sls_qty_07
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN sls_qty_08
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN sls_qty_09
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN sls_qty_10 
                WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN sls_qty_11
            END, 0) as spd_qty_3
            from
            tbtr_salesbulanan ) spd on
        prd.prd_prdcd = spd.spd_prdcd
	left join (
		select
			tpod_prdcd,
			SUM(tpod_qtypo) as tpod_qtypo,
			tpod_nopo as tpod_nopo
		from
			tbtr_po_d
		where
			tpod_nopo in (
			select
				tpoh_nopo
			from
				tbtr_po_h
			where
				tpoh_recordid is null
				and (tpoh_tglpo + interval '1 day' * tpoh_jwpb) >= CURRENT_DATE )
		group by
			tpod_prdcd,
			tpod_nopo ) poo on
		substr(prd.prd_prdcd, 1, 6) || '0' = poo.tpod_prdcd
	left join (
		select
			PRD_PRDCD as PRD_PRDCD,
			case
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYYYY' then 'NAS-IGR+IDM+OMI+MR.BRD+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYYYN' then 'NAS-IGR+IDM+OMI+MR.BRD+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYYNN' then 'NAS-IGR+IDM+OMI+MR.BRD'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYNYY' then 'NAS-IGR+IDM+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYNYN' then 'NAS-IGR+IDM+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYNNY' then 'NAS-IGR+IDM+OMI+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYYNNN' then 'NAS-IGR+IDM+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYNYYY' then 'NAS-IGR+IDM+MR.BRD+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYNNYY' then 'NAS-IGR+IDM+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYNNYN' then 'NAS-IGR+IDM+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYNNNY' then 'NAS-IGR+IDM+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYYNNNN' then 'NAS-IGR+IDM'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNYNYY' then 'NAS-IGR+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNYNYN' then 'NAS-IGR+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNYNNY' then 'NAS-IGR+OMI+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNYNNN' then 'NAS-IGR+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNYYN' then 'NAS-IGR+MR.BRD+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNYNN' then 'NAS-IGR+MR.BRD'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNNYY' then 'NAS-IGR+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNNYN' then 'NAS-IGR+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNNNY' then 'NAS-IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YYNNNNN' then 'NAS-IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYYNYY' then 'NAS-IDM+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYYNYN' then 'NAS-IDM+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYYNNY' then 'NAS-IDM+OMI+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYYNNN' then 'NAS-IDM+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYNNYY' then 'NAS-IDM+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYNNYN' then 'NAS-IDM+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYNNNY' then 'NAS-IDM+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNYNNNN' then 'NAS-IDM'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNYYNN' then 'NAS-OMI+MR.BRD'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNYNYN' then 'NAS-OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNYNNN' then 'NAS-OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNNYNN' then 'NAS-MR.BRD'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNNNYN' then 'NAS-K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'YNNNNNN' then 'NAS'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYYNYY' then 'IGR+IDM+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYYNYN' then 'IGR+IDM+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYYNNY' then 'IGR+IDM+OMI+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYYNNN' then 'IGR+IDM+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYNNYY' then 'IGR+IDM+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYNNYN' then 'IGR+IDM+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYNNNY' then 'IGR+IDM+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYYNNNN' then 'IGR+IDM'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNYNYY' then 'IGR+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNYNYN' then 'IGR+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNYNNN' then 'IGR+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNNYYN' then 'IGR+MR.BRD+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNNNYY' then 'IGR+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNNNYN' then 'IGR+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNNNNY' then 'IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NYNNNNN' then 'IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYYNYY' then 'IDM+OMI+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYYNYN' then 'IDM+OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYYNNY' then 'IDM+OMI+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYYNNN' then 'IDM+OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYNNYY' then 'IDM+K.IGR+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYNNYN' then 'IDM+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYNNNY' then 'IDM+DEPO'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNYNNNN' then 'IDM'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNNYNYN' then 'OMI+K.IGR'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNNYNNN' then 'OMI'
				when NAS || IGR || IDM || OMI || BRD || K_IGR || DEPO = 'NNNNNNN' then 'BELUM ADA FLAG'
				else 'BELUM ADA FLAG'
			end as status_igr_idm
		from
			(
			select
				prd_prdcd,
				prd_plumcg,
				coalesce(PRD_FLAGNAS, 'N') as NAS,
				coalesce(PRD_FLAGIGR, 'N') as IGR,
				coalesce(PRD_FLAGIDM, 'N') as IDM,
				coalesce(PRD_FLAGOMI, 'N') as OMI,
				coalesce(PRD_FLAGBRD, 'N') as BRD,
				coalesce(PRD_FLAGOBI, 'N') as K_IGR,
				case
					when prd_plumcg in (
					select
						PLUIDM
					from
						DEPO_LIST_IDM) then 'Y'
					else 'N'
				end as DEPO
			from
				TBMASTER_PRODMAST
			where
				PRD_PRDCD like '%0'
				and PRD_DESKRIPSIPANJANG is not null ) sub_sii) sii on
		prd.prd_prdcd = sii.prd_prdcd
	left join (
		select
			distinct prc_pluigr,
			prc_pluomi,
			prc_kodetag
		from
			tbmaster_prodcrm
		where
			prc_group = 'O') omi on
		substr(prd.prd_prdcd, 1, 6) || '0' = omi.prc_pluigr
    
    -- === BAGIAN YANG DIPERBAIKI (JOIN REK) MULAI DARI SINI ===
	left join (
		select
			rsl_prdcd,
			-- 1. REKAP BIRU (GROUP 01) OTOMATIS
			coalesce(SUM(case when rsl_group = '01' then 
				CASE 
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0)
				END
			end) / 3, 0) as rekap_biru,

			-- 2. REKAP OMI (GROUP 02) OTOMATIS
			coalesce(SUM(case when rsl_group = '02' then 
				CASE 
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0)
				END
			end) / 3, 0) as rekap_omi,

			-- 3. REKAP MERAH (GROUP 03) OTOMATIS
			coalesce(SUM(case when rsl_group = '03' then 
				CASE 
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 1 THEN coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 2 THEN coalesce(rsl_qty_11, 0) + coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 3 THEN coalesce(rsl_qty_12, 0) + coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 4 THEN coalesce(rsl_qty_01, 0) + coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 5 THEN coalesce(rsl_qty_02, 0) + coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 6 THEN coalesce(rsl_qty_03, 0) + coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 7 THEN coalesce(rsl_qty_04, 0) + coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 8 THEN coalesce(rsl_qty_05, 0) + coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 9 THEN coalesce(rsl_qty_06, 0) + coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 10 THEN coalesce(rsl_qty_07, 0) + coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 11 THEN coalesce(rsl_qty_08, 0) + coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0)
					WHEN EXTRACT(MONTH FROM CURRENT_DATE) = 12 THEN coalesce(rsl_qty_09, 0) + coalesce(rsl_qty_10, 0) + coalesce(rsl_qty_11, 0)
				END
			end) / 3, 0) as rekap_merah
		from
			tbtr_rekapsalesbulanan
		group by
			rsl_prdcd) rek on
		prd.prd_prdcd = rek.rsl_prdcd
    -- === SELESAI BAGIAN PERBAIKAN ===
    
	left join (
		select
			e.lks_prdcd as exp_prdcd,
			e.lks_expdate as exp_tanggal,
			SUM(l.lks_qty) as exp_qty
		from
			tbmaster_lokasi l
		join (
			select
				lks_prdcd,
				MIN(lks_expdate) as lks_expdate
			from
				tbmaster_lokasi
			where
				lks_prdcd is not null
				and coalesce(lks_qty, 0) <> 0
			group by
				lks_prdcd ) e on
			l.lks_prdcd = e.lks_prdcd
			and l.lks_expdate = e.lks_expdate
		group by
			e.lks_prdcd,
			e.lks_expdate) exp on
		prd.prd_prdcd = exp.exp_prdcd
	left join (
		select
			lks_prdcd,
			lks_koderak,
			lks_kodesubrak,
			lks_tiperak,
			lks_shelvingrak,
			lks_nourut,
			lks_maxdisplay,
			lks_qty,
			coalesce(lks_tirkirikanan, 0) * coalesce(lks_tirdepanbelakang, 0) * coalesce(lks_tiratasbawah, 0) as lks_mindisplay,
			lks_maxplano
		from
			tbmaster_lokasi
		where
			(lks_koderak like 'R%'
				or lks_koderak like 'O%')
			and lks_koderak not like '%C'
			and lks_tiperak <> 'S') lks on
		prd.prd_prdcd = lks.lks_prdcd
	left join (
		select
			lks_prdcd as dpd_prdcd,
			lks_koderak as dpd_koderak,
			lks_kodesubrak as dpd_kodesubrak,
			lks_tiperak as dpd_tiperak,
			lks_shelvingrak as dpd_shelvingrak,
			lks_nourut as dpd_nourut,
			lks_maxdisplay as dpd_maxdisplay,
			lks_qty as dpd_qty,
			coalesce(lks_tirkirikanan, 0) * coalesce(lks_tirdepanbelakang, 0) * coalesce(lks_tiratasbawah, 0) as dpd_mindisplay,
			lks_maxplano as dpd_maxplano,
			lks_noid as dpd_noid
		from
			tbmaster_lokasi
		where
			lks_koderak like 'D%'
			and lks_koderak not like '%C'
			and lks_tiperak <> 'S'
			and to_number(substr(lks_koderak, 2, 2), '99') between 1 and 99
				and lks_prdcd is not null) dpd on
		prd.prd_prdcd = dpd.dpd_prdcd
	left join (
		select
			mstd_prdcd,
			MIN(mstd_create_dt) as mstd_bpb_pertama,
			MAX(mstd_create_dt) as mstd_bpb_terakhir
		from
			tbtr_mstran_d
		where
			mstd_recordid is null
			and mstd_typetrn = 'B'
		group by
			mstd_prdcd) bpb on
		prd.prd_prdcd = bpb.mstd_prdcd
	where
		prd.prd_frac is not null
		and prd_recordid is null) vpi
where
	plu is not null
	and plu like '%%0'
order by
	hari desc;";

	$result = pg_query($conn, $sql);

	if (!$result) {
		throw new Exception("Query Gagal: " . pg_last_error($conn));
	}

	$data = pg_fetch_all($result);

	$response['status'] = 'success';

	$response['data'] = ($data === false) ? [] : $data;
} catch (Exception $e) {

	$response['status'] = 'error';
	$response['message'] = $e->getMessage();
}

pg_close($conn);


echo json_encode($response);
