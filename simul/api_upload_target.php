<?php
header('Content-Type: application/json');

// 1. Ambil data JSON mentah yang dikirim dari AJAX frontend
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// 2. Validasi sederhana
// Pastikan datanya ada dan formatnya array (karena dari Excel harusnya berupa array of objects)
if (!is_array($data) || empty($data)) {
    echo json_encode(["status" => "error", "message" => "Data tidak valid atau file kosong."]);
    exit;
}

// Opsional: Kamu bisa tambahkan validasi untuk ngecek apakah kolom 'Bulan' dan 'Total_Target' benar-benar ada di baris pertama
if (!isset($data[0]['Bulan']) || !isset($data[0]['Total_Target'])) {
    echo json_encode(["status" => "error", "message" => "Format Excel salah. Pastikan header tabel adalah 'Bulan' dan 'Total_Target'."]);
    exit;
}

// 3. Tentukan nama file tempat menyimpan data
// Pastikan folder 'simul' ini punya permission write (bisa ditulisi oleh PHP/Apache/Nginx)
$jsonFile = 'target_bulanan.json';

// 4. Tulis data ke dalam file
// Kita pakai JSON_PRETTY_PRINT biar kalau kamu buka file json-nya manual, formatnya rapi dan gampang dibaca
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(["status" => "success", "message" => "Data target bulanan berhasil disimpan."]);
} else {
    // Kalau gagal, biasanya karena masalah permission folder di server
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan file. Cek permission folder server kamu."]);
}
