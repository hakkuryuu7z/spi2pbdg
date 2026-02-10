<?php
// opredp/api_marmin_detail.php
header('Content-Type: application/json');
require_once "../connection/index.php";

$response = [];

try {
    // Query Detail Baru dari Anda
    $sql = <<<SQL
    SELECT * FROM (
        select
            PRD_KODEDIVISI DIV,
            PRD_PRDCD PLU,
            PRD_DESKRIPSIPANJANG DESKRIPSI,
            PRD_FRAC FRAC,
            PRD_UNIT UNIT,
            PRD_KODETAG TAG,
            ST_SALDOAKHIR LPP,
            PRD_HRGJUAL HRG,
            PRMD_HRGJUAL HRG_P,
            LCOST LCOST_PCS,
            ACOST ACOST_PCS,
            ACOST_INCLUDE A_COST_INC,
            MARGIN_A MARGIN,
            MARGIN_L MARGIN_LCOST,
            MARGIN_A_MD,
            MARGIN_L_MD
        from
            (
            select
                PRD_KODEDIVISI,
                PRD_PRDCD,
                PRD_DESKRIPSIPANJANG,
                PRD_FRAC,
                PRD_UNIT,
                PRD_KODETAG,
                ST_SALDOAKHIR,
                PRD_HRGJUAL,
                PRMD_HRGJUAL,
                LCOST,
                ACOST,
                ACOST_INCLUDE,
                MARGIN_A,
                MARGIN_L,
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
            from
                (select PRD_KODEDIVISI,
        PRD_PRDCD,
        PRD_DESKRIPSIPANJANG,
        PRD_FRAC,
        PRD_UNIT,
        PRD_KODETAG,
        ST_SALDOAKHIR,
        PRD_HRGJUAL,
        ST_LASTCOST, prd_flagbkp2, prd_flagbkp1, ST_AVGCOST,
        case
            when PRD_UNIT = 'KG'
            then (ST_LASTCOST * PRD_FRAC)/ 1000
            else ST_LASTCOST * PRD_FRAC
        end as LCOST,
        case
            when PRD_UNIT = 'KG'
            then (ST_AVGCOST * PRD_FRAC)/ 1000
            else ST_AVGCOST * PRD_FRAC
        end as ACOST,
        case
            when PRD_UNIT = 'KG'
            then ((ST_AVGCOST * PRD_FRAC)/ 1000)* 1.11
            else (ST_AVGCOST * PRD_FRAC)* 1.11
        end as ACOST_INCLUDE,  
                case
                when PRD_UNIT = 'KG'
                then (((PRD_HRGJUAL-(ST_AVGCOST * PRD_FRAC / 1000))/ PRD_HRGJUAL)* 100)
                when coalesce(prd_flagbkp1, 'T') = 'Y' and coalesce(prd_flagbkp2, 'T') = 'Y'
                then (((PRD_HRGJUAL / 1.11)-(ST_AVGCOST * PRD_FRAC))/(PRD_HRGJUAL / 1.11)* 100)
                else (((PRD_HRGJUAL-(ST_AVGCOST * PRD_FRAC))/ PRD_HRGJUAL)* 100)
                end as MARGIN_A,
                case
                when PRD_UNIT = 'KG'
                then (((PRD_HRGJUAL-(ST_LASTCOST * PRD_FRAC / 1000))/ PRD_HRGJUAL)* 100)
                when coalesce(prd_flagbkp1, 'T') = 'Y' and coalesce(prd_flagbkp2, 'T') = 'Y'
                then (((PRD_HRGJUAL / 1.11)-(ST_LASTCOST * PRD_FRAC))/(PRD_HRGJUAL / 1.11)* 100)
                else (((PRD_HRGJUAL-(ST_LASTCOST * PRD_FRAC))/ PRD_HRGJUAL)* 100)
                end as MARGIN_L
        from
        (
        select
            SUBSTR(PRD_PRDCD, 1, 6)
        || 0 PLU,
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
        from
            tbmaster_prodmast
        )prd
        left join
        (
        select
            ST_PRDCD,
            ST_SALDOAKHIR,
            ST_LASTCOST,
            ST_AVGCOST
        from
            tbmaster_Stock
        where
            st_lokasi = '01'
        )stk on
        prd.PLU = stk.st_prdcd
        where
        coalesce (PRD_KODETAG,
        '0') not in ('N', 'X', 'Z')
        and ST_SALDOAKHIR <> 0
        order by
        PRD_PRDCD asc)HRG_N
            left join 
        (
                select
                    PRMD_PRDCD as PLUMD,
                    PRMD_HRGJUAL
                from
                    TBTR_PROMOMD
                where
                    CURRENT_DATE between DATE(PRMD_TGLAWAL) and DATE(PRMD_TGLAKHIR)
        )PRMD on
                HRG_N.PRD_PRDCD = PRMD.PLUMD)MARGINM
        where
            (MARGIN_A<0 or MARGIN_A_MD<0)
    ) final_data;
SQL;

    $result = pg_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error Query: " . pg_last_error($conn));
    }

    $data = pg_fetch_all($result);

    $response['status'] = 'success';
    $response['data'] = $data ? $data : [];
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    if ($conn) pg_close($conn);
}

echo json_encode($response);
