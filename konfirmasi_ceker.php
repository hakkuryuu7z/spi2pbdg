<?php
// FILE: konfirmasi_ceker.php

session_start(); // Wajib untuk memulai/mengakses session

// 1. Beri "tanda" (session) bahwa dia adalah Ceker
$_SESSION['role'] = 'checker';

// 2. Langsung "lempar" (redirect) dia ke halaman dashboard
header("Location: realisasislp.php");
die(); // Wajib untuk menghentikan script setelah redirect
