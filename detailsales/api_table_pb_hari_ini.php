<?php
// 1. Atur Header untuk Respon JSON
header("Content-Type: application/json");

// 2. Sertakan File Koneksi Database
include "../connection/index.php";

// 3. Buat Array untuk Respon
$response = [];

// 4. Ambil Tanggal dari Parameter GET (LOGIKA BARU)
// Kita mengambil parameter 'tanggal_mulai' dan 'tanggal_selesai'
// yang dikirim oleh JavaScript
$tgl_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : null;
$tgl_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : null;

// Validasi: Jika salah satu tanggal tidak ada, kirim error
// (Seharusnya ini tidak terjadi jika JS berjalan normal, tapi ini untuk keamanan)
if (!$tgl_mulai || !$tgl_selesai) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = 'Parameter tanggal_mulai dan tanggal_selesai wajib diisi.';
    echo json_encode($response);
    pg_close($conn);
    exit; // Hentikan script
}

// 5. Definisikan Query SQL Dasar (Tidak berubah)
$sql_base = "
    SELECT
        CASE
            WHEN h.obi_recid IS NULL THEN 'Siap Send HH'
            WHEN h.obi_recid = '1' THEN 'Siap Picking'
            WHEN h.obi_recid = '2' THEN 'Siap Packing'
            WHEN h.obi_recid = '3' THEN 'Siap Draft Struk'
            WHEN h.obi_recid = '4' THEN 'Konfirmasi Pembayaran'
            WHEN h.obi_recid = '5' THEN 'Siap Struk'
            WHEN h.obi_recid = '6' THEN 'Selesai Struk'
            WHEN h.obi_recid = 'B' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B1' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B2' THEN 'Transaksi Batal'
            WHEN h.obi_recid = 'B3' THEN 'Transaksi Batal'
            ELSE h.obi_recid
        END AS status,
        h.obi_kdmember,
        h.obi_nopb,
        c.cus_namamember,
        h.obi_notrans
    FROM
        tbtr_obi_h AS h
    LEFT JOIN
        tbmaster_customer AS c ON h.obi_kdmember = c.cus_kodemember
";

// 6. Siapkan Kondisi WHERE dan Parameter (LOGIKA BARU)
// Kita sekarang menggunakan BETWEEN untuk rentang tanggal
$params = [];
$sql_where = "WHERE h.obi_tglpb::date BETWEEN $1 AND $2";
$params[] = $tgl_mulai;   // $1 = tanggal_mulai
$params[] = $tgl_selesai; // $2 = tanggal_selesai


// 7. Gabungkan Query dan Eksekusi dengan Aman
$sql_final = $sql_base . $sql_where . " ORDER BY h.obi_notrans DESC;";

// Menggunakan pg_query_params untuk keamanan (mencegah SQL Injection)
$query = pg_query_params($conn, $sql_final, $params);

// 8. Proses Hasil Query (Tidak berubah)
if ($query) {
    // Jika query berhasil, ambil semua datanya
    $data = pg_fetch_all($query);

    // Memberikan respon sukses beserta datanya
    $response['status'] = 'success';
    // pg_fetch_all bisa mengembalikan false jika tidak ada baris, jadi kita cek
    $response['data'] = $data ? $data : [];
} else {
    // Jika query gagal, berikan respon error
    // Ini sangat membantu untuk debugging.
    http_response_code(500); // Set status HTTP ke Internal Server Error
    $response['status'] = 'error';
    $response['message'] = 'Query Gagal: ' . pg_last_error($conn);
}

// 9. Tampilkan Hasil dalam Format JSON
echo json_encode($response);

// Tutup koneksi untuk melepaskan resource server
pg_close($conn);
