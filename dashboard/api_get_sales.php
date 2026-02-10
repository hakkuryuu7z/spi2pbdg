<?php
include "../connection/index.php";

$sql = "select
	MEMBERS,
	STD,
	PRODUK_BELI,
	ROUND(PRODUK_BELI::numeric / nullif(MEMBERS,
	0),
	0) as PROD_MIX,
	SALES,
	MARGIN
from
	(
	select
		COUNT(distinct TRJD_CUS_KODEMEMBER) as MEMBERS,
		COUNT(TRJD_PRDCD) as PRODUK_BELI,
		COUNT(distinct TRJD_TRANSACTIONTYPE || TRJD_CREATE_DT || TRJD_CREATE_BY || TRJD_CASHIERSTATION || TRJD_TRANSACTIONNO) as STD,
		SUM(ROUND(
            case
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then TRJD_NOMINALAMT / 1.11
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then TRJD_NOMINALAMT
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R'
                    then (TRJD_NOMINALAMT / 1.11) * -1
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R'
                    then TRJD_NOMINALAMT * -1
                else TRJD_NOMINALAMT * -1
            end, 0)) as SALES,
		SUM(ROUND(
            case
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG'
                    then (TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG'
                    then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S' and PRD_UNIT = 'KG'
                    then TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then (TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'S'
                    then TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG'
                    then (((TRJD_NOMINALAMT / 1.11) - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG'
                    then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'N' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R' and PRD_UNIT = 'KG'
                    then ((TRJD_NOMINALAMT - ((TRJD_BASEPRICE * TRJD_QUANTITY) / 1000)) * -1)
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') = 'Y' and TRJD_TRANSACTIONTYPE = 'R'
                    then ((TRJD_NOMINALAMT / 1.11) - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
                when coalesce(TRJD_FLAGTAX1, 'Y') = 'Y' and coalesce(TRJD_FLAGTAX2, 'T') <> 'Y' and TRJD_TRANSACTIONTYPE = 'R'
                    then ((TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1)
                else (TRJD_NOMINALAMT - (TRJD_BASEPRICE * TRJD_QUANTITY)) * -1
            end, 0)) as MARGIN
	from
		TBTR_JUALDETAIL
	left join TBMASTER_CUSTOMER on
		TRJD_CUS_KODEMEMBER = CUS_KODEMEMBER
	left join TBMASTER_PRODMAST on
		TRJD_PRDCD = PRD_PRDCD
	left join TBMASTER_CUSTOMERCRM on
		TRJD_CUS_KODEMEMBER = CRM_KODEMEMBER
	where
		TRJD_RECORDID is null
--		CUS_NAMAMEMBER like 'SPI%'
--		and CUS_FLAGMEMBERKHUSUS = 'Y'
		and DATE(TRJD_TRANSACTIONDATE) = CURRENT_DATE
) A; ";

$query = pg_query($conn, $sql);

$get_data = pg_fetch_assoc($query);

// Siapkan array untuk response JSON
$response = [];

// Cek dulu apakah query menghasilkan data
if ($get_data) {
    // Susun data ke dalam array response
    // Ambil data yang tidak perlu diformat langsung
    $response['members'] = $get_data['members'];
    $response['std'] = $get_data['std'];
    $response['produk_beli'] = $get_data['produk_beli'];
    $response['prod_mix'] = $get_data['prod_mix'];

    // Format HANYA kolom yang berupa angka
    $response['sales'] = number_format($get_data['sales'], 0, ',', '.');
    $response['margin'] = number_format($get_data['margin'], 0, ',', '.');
} else {
    // Jika tidak ada data, kirim response default agar tidak error di frontend
    $response = [
        'members' => 0,
        'std' => 0,
        'produk_beli' => 0,
        'prod_mix' => 0,
        'sales' => '0',
        'margin' => '0'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
