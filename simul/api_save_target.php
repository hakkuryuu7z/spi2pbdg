<?php
header('Content-Type: application/json');

// Terima data JSON dari Javascript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    // Simpan ke file target.json
    // Pastikan folder simul memiliki izin tulis (chmod 777 atau sejenisnya)
    if (file_put_contents('target.json', json_encode($data, JSON_PRETTY_PRINT))) {
        echo json_encode(["status" => "success", "message" => "Target berhasil diperbarui"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menulis file json"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
}
