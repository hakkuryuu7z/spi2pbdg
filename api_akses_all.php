<?php
// 1. WAJIB ADA: Mulai session di baris paling atas
session_start();

// Salin fungsi Anda ke sini
function get_client_ip()
{
    $ip_address = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip_address = trim($ip_list[0]);
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip_address = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip_address = 'UNKNOWN';
    }

    return $ip_address;
}

// --- INI BAGIAN LOGIKA UNTUK BLOKIR IP ---

// 2. Tentukan daftar IP yang diizinkan (Whitelist)
$allowed_ips = [
    '172.26.15.1',
    '172.26.15.2',
    '172.26.3.3',
    '172.26.11.6',
    '100.95.22.11',
    '172.26.15.3',
    '172.26.15.4',
    '172.26.15.5',
    '172.26.15.6',
    '192.168.152.2',
    // '100.111.77.91',
    '172.26.15.19',
    '192.168.173.30',
    '100.90.67.47',
    '192.168.164.12',
    // '100.80.192.56',
    // '127.0.0.1', 
];

// 3. Dapatkan IP pengunjung saat ini
$visitor_ip = get_client_ip();

// --- LOGIKA BARU ---

// Cek 1: Apakah IP ada di whitelist?
$is_ip_allowed = in_array($visitor_ip, $allowed_ips);

// Cek 2: Apakah user sudah login (punya session role)?
// Ini berlaku untuk 'checker', 'mr', atau role apapun.
$is_logged_in = isset($_SESSION['role']) && !empty($_SESSION['role']);

// 4. ATURAN PENOLAKAN (GATEKEEPER)
// Jika IP TIDAK diizinkan DAN User BELUM login, maka tolak.
// (Artinya: Kalau salah satu benar [IP ok atau Login ok], dia boleh masuk)
if (!$is_ip_allowed && !$is_logged_in) {

    // Jika tidak memenuhi syarat keduanya, kirim header 403
    http_response_code(403);

    // Lempar ke halaman akses ditolak
    header("Location: akses_halaman.php");

    // Matikan script
    die();
}

// Lanjutkan dengan kode dashboard Anda...
