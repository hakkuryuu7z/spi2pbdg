<?php
// FILE: logout.php

// 1. Selalu mulai session di paling atas
//    Kita perlu ini untuk mengakses dan menghapus session yang ada.
session_start();

// 2. Hapus semua variabel yang tersimpan di session
//    Ini akan menghapus $_SESSION['role'] = 'checker'
session_unset();

// 3. Hancurkan session-nya secara permanen
//    Ini akan menghapus file session di server.
session_destroy();

// 4. "Lempar" (redirect) pengguna kembali ke halaman awal
//    Setelah logout, dia akan kembali ke halaman pemilihan peran
//    sebagai pengunjung biasa.
header("Location: akses_halaman.php");

// 5. Hentikan script
//    Sangat penting setelah redirect untuk memastikan tidak ada
//    kode lain yang berjalan.
die();
