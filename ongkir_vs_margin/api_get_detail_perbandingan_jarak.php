<?php
// api_get_detail_perbandingan_jarak.php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $path_connection = '../connection/index.php';
    if (!file_exists($path_connection)) {
        throw new Exception("File koneksi tidak ditemukan.");
    }
    require_once $path_connection;

    // --- PARAMETER ---
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date   = $_GET['end_date'] ?? date('Y-m-d');
    $jarak      = $_GET['jarak'] ?? '';
    $mode       = $_GET['mode'] ?? 'member'; // 'member' atau 'struk'

    if (empty($jarak)) throw new Exception("Parameter jarak diperlukan");

    // Filter Jarak (Sesuai dengan grouping di tabel utama)
    $jarak_filter = "";
    switch ($jarak) {
        case '5':
            $jarak_filter = "COALESCE(cus.cus_jarak, 0) <= 5";
            break;
        case '10':
            $jarak_filter = "COALESCE(cus.cus_jarak, 0) > 5 AND COALESCE(cus.cus_jarak, 0) <= 10";
            break;
        case '15':
            $jarak_filter = "COALESCE(cus.cus_jarak, 0) > 10 AND COALESCE(cus.cus_jarak, 0) <= 15";
            break;
        case '20':
            $jarak_filter = "COALESCE(cus.cus_jarak, 0) > 15 AND COALESCE(cus.cus_jarak, 0) <= 20";
            break;
        case '>20':
            $jarak_filter = "COALESCE(cus.cus_jarak, 0) > 20";
            break;
        default:
            throw new Exception("Parameter jarak tidak valid (gunakan: 5, 10, 15, 20, atau >20)");
    }

    // --- BAGIAN INTI QUERY (RAW DATA PER STRUK) ---
    $core_query = "
        SELECT 
            obi.obi_nopb AS nopb,
            obi.obi_kdmember AS kodemember,
            cus.cus_namamember AS namamember,
            cus.cus_kodeoutlet AS outlet,
            cus.cus_kodesuboutlet AS suboutlet,
            COALESCE(cus.cus_jarak, 0) AS jarak_member,
            COALESCE(klk.pot_ongkir, 0) AS ongkir,
            
            -- CASE STATUS OBI
            CASE
                WHEN obi.obi_recid = '6' THEN 'SELESAI' 
                WHEN obi.obi_recid = '1' THEN 'SIAP PICKING'
                WHEN obi.obi_recid = '2' THEN 'SIAP PACKING'
                WHEN obi.obi_recid = '3' THEN 'SIAP DRAFT STRUK'
                WHEN obi.obi_recid = '4' THEN 'KONFIRMASI PEMBAYARAN'
                WHEN obi.obi_recid = '5' THEN 'SIAP STRUK'
                WHEN obi.obi_recid LIKE 'B%' THEN 'BATAL'
                WHEN obi.obi_recid IS NULL THEN 'SIAP SEND HANDHELD'
                ELSE obi.obi_recid
            END AS status_obi,
            
            obi.obi_kdekspedisi as armada,

            -- HITUNG MARGIN
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
                end, 0)) as margin,

            -- HITUNG SALES
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
                end, 0)) as sales

        FROM tbtr_obi_h obi
        LEFT JOIN tbmaster_customer cus ON obi.obi_kdmember = cus.cus_kodemember
        LEFT JOIN (
            SELECT kode_member, no_pb, pot_ongkir FROM payment_klikigr WHERE pot_ongkir <> 0
        ) klk ON obi_kdmember = klk.kode_member AND obi_nopb = klk.no_pb
        LEFT JOIN tbtr_jualdetail jd ON obi.obi_nostruk = jd.trjd_transactionno AND obi.obi_tglstruk = trjd_transactiondate
        LEFT JOIN tbmaster_prodmast as pr ON trjd_prdcd = prd_prdcd 
        
        WHERE obi.obi_tglstruk::date BETWEEN '$start_date' AND '$end_date'
          AND obi.obi_kdekspedisi <> 'Ambil di Stock Point Indogrosir'
          AND obi.obi_recid = '6' 
          AND $jarak_filter

        GROUP BY
            obi.obi_nopb,
            obi.obi_kdmember,
            cus.cus_namamember,
            cus.cus_kodeoutlet,
            cus.cus_kodesuboutlet,
            cus.cus_jarak,
            klk.pot_ongkir,
            obi.obi_recid,
            obi.obi_kdekspedisi
    ";

    // --- LOGIKA PEMILIHAN MODE ---
    if ($mode === 'struk') {
        // Mode Detail Struk
        $sql = $core_query . " ORDER BY obi.obi_nopb ASC";
    } else {
        // Mode Member
        $sql = "
            SELECT 
                src.kodemember,
                src.namamember,
                src.outlet,
                src.suboutlet,
                src.jarak_member,
                COUNT(src.nopb) AS total_struk,
                SUM(src.sales) AS total_netto,
                SUM(src.margin) AS total_margin,
                SUM(src.ongkir) AS total_ongkir
            FROM (
                $core_query
            ) src
            GROUP BY src.kodemember, src.namamember, src.outlet, src.suboutlet, src.jarak_member
            ORDER BY total_margin DESC
        ";
    }

    $query = pg_query($conn, $sql);
    if (!$query) throw new Exception("SQL Error: " . pg_last_error($conn));

    $data = [];

    // Variabel Total Header
    $grand_total_netto = 0;
    $grand_total_margin = 0;
    $grand_total_ongkir = 0;

    while ($row = pg_fetch_assoc($query)) {
        // Format Angka dasar
        $netto  = (float)($mode === 'struk' ? $row['sales'] : $row['total_netto']);
        $margin = (float)($mode === 'struk' ? $row['margin'] : $row['total_margin']);
        $ongkir = (float)($mode === 'struk' ? $row['ongkir'] : $row['total_ongkir']);

        // Hitung Persentase & Ratio
        $margin_persen = ($netto == 0) ? 0 : round(($margin / $netto) * 100, 2);
        $ratio_om      = ($margin <= 0) ? 0 : round(($ongkir / $margin) * 100, 2);

        $grand_total_netto  += $netto;
        $grand_total_margin += $margin;
        $grand_total_ongkir += $ongkir;

        $item = [
            "kode_member"   => $row['kodemember'],
            "nama_member"   => $row['namamember'],
            "outlet"        => $row['outlet'],
            "suboutlet"     => $row['suboutlet'],
            "jarak_member"  => (float)$row['jarak_member'],
            "netto"         => $netto,
            "margin"        => $margin,
            "margin_persen" => $margin_persen,
            "ongkir"        => $ongkir,
            "ratio_om"      => $ratio_om,
        ];

        if ($mode === 'struk') {
            $item["nopb"]       = $row['nopb'];
            $item["status"]     = $row['status_obi'];
            $item["armada"]     = $row['armada'];
        } else {
            $item["struk"]      = (int)$row['total_struk'];
        }

        $data[] = $item;
    }

    echo json_encode([
        "status"       => "success",
        "mode"         => $mode,
        "jarak"        => $jarak,
        "periode"      => "$start_date s/d $end_date",
        "total_netto"  => $grand_total_netto,
        "total_margin" => $grand_total_margin,
        "total_ongkir" => $grand_total_ongkir,
        "data"         => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
