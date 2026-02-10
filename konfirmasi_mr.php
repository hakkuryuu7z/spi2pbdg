<?php
// 1. Mulai Sesi (Wajib paling atas)
session_start();

// 2. Set peran pengguna menjadi 'mr'
$_SESSION['role'] = 'mr';

// (Opsional) Anda bisa menyimpan waktu login jika perlu
// $_SESSION['login_time'] = date('Y-m-d H:i:s');

// 3. Arahkan langsung ke halaman Dashboard MR
// PENTING: Ganti 'dashboard_mr.php' di bawah ini dengan nama file tujuan MR Anda yang sebenarnya.
// Contoh: header("Location: halaman_mr.php"); atau header("Location: realisasi_mr.php");
header("Location: targetmr.php");

// 4. Hentikan script agar tidak ada kode lain yang jalan
exit;
