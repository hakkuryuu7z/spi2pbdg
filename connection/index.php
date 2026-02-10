<?php

$host = '172.31.147.216';
$port = '5432';
$dbname = 'spibdg2p';
$dbuser = 'edp';
$dbpass = '3dp1grVIEW';

$conn_string = "host={$host} port={$port} dbname={$dbname} user={$dbuser} password={$dbpass}";
$conn = pg_connect($conn_string);

// Check connection
if (!$conn) {
    echo "Failed to connect to PostgreSQL.\n";
    exit;
}

// date_default_timezone_set('asia/jakarta');

// $formatter = new IntlDateFormatter(
//     'id_ID', // Locale (Bahasa dan Negara)
//     IntlDateFormatter::FULL, // Format Tanggal (panjang)
//     IntlDateFormatter::SHORT, // Format Waktu (pendek)
//     'Asia/Jakarta', // Zona Waktu
//     IntlDateFormatter::GREGORIAN // Tipe Kalender
// );
