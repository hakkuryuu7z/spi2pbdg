<?php

// Salin fungsi Anda ke sini
function get_user_ip()
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

$allowed_ips = [
    '172.26.15.1',
    '172.26.15.2',
    '172.26.15.3',
    '172.26.15.4',
    '172.26.15.5',
    '172.26.15.6',
    '192.168.250.8',
    '172.26.11.6',
    '100.95.22.11',
    '100.79.110.74',
    '172.26.24.21',
    '192.168.164.12',
    '192.168.152.2',
    // '100.111.77.91',
    '172.26.15.19',
    '192.168.173.30',
    '100.90.67.47',
    '192.168.164.12',
    '100.106.176.100',
    // '100.80.192.56',
    // '127.0.0.1', 
];

$visitor_ip = get_user_ip();

if (in_array($visitor_ip, $allowed_ips)) {
    http_response_code(403);
    header("Location:index.php");
    die();
}

// ... sisa kode halaman Anda ...

session_start(); // <<< WAJIB ADA DI PALING ATAS

// --- BLOK LOGIKA PENTING ---
// 1. Cek jika Checker
if (isset($_SESSION['role']) && $_SESSION['role'] == 'checker') {
    header("Location: realisasislp.php");
    die();
}
// 2. Cek jika MR (TAMBAHAN BARU)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'mr') {
    // Ganti 'dashboard_mr.php' dengan halaman tujuan MR yang sebenarnya
    header("Location: targetmr.php");
    die();
}
// --------------------------

// (Fungsi get_client_ip duplicate Anda saya biarkan, tapi sebaiknya pakai satu saja)
function get_client_ip()
{
    // ... logika sama dengan get_user_ip ...
    // Untuk mempersingkat jawaban, saya gunakan logika yang sama
    return get_user_ip();
}

$visitor_ip = get_client_ip();
$wa_message = urlencode("Halo, saya pengunjung. Mohon whitelist alamat IP saya: " . $visitor_ip);
$wa_link = "https://wa.me/081214764122?text=" . $wa_message;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran Anda</title>
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: grid;
            place-items: center;
        }

        .role-selection-container {
            text-align: center;
            background: #ffffff;
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            /* Lebar ditambah agar muat 3 kotak */
            width: 90%;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .role-options {
            display: flex;
            gap: 20px;
            justify-content: center;
            /* Agar posisi di tengah */
            flex-wrap: wrap;
            /* Agar responsif jika layar kecil */
        }

        .role-box {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 40px 30px;
            width: 180px;
            text-decoration: none;
            color: #444;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fff;
        }

        .role-box:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
            transform: translateY(-5px);
        }

        .role-box .icon {
            font-size: 50px;
        }

        .role-box .role-name {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .role-box .role-description {
            font-size: 0.9rem;
            color: #666;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            position: relative;
        }

        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: #333;
        }

        #ip-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            background-color: #f0f2f5;
            padding: 10px 20px;
            border-radius: 8px;
            margin: 20px 0;
            user-select: all;
        }

        .wa-button {
            display: inline-block;
            background-color: #25D366;
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.2s;
        }

        .wa-button:hover {
            background-color: #1EBE57;
        }
    </style>
</head>

<body>

    <div class="role-selection-container">
        <h1>Pilih Peran Anda</h1>
        <div class="role-options">

            <a href="#" onclick="showCheckerModal()" class="role-box">
                <div class="icon">🔍</div>
                <div class="role-name">Saya Seorang Ceker</div>
                <div class="role-description">Masuk untuk mengelola dan memvalidasi data.</div>
            </a>

            <a href="#" onclick="showMrModal()" class="role-box">
                <div class="icon">💼</div>
                <div class="role-name">Saya Seorang MR</div>
                <div class="role-description">Masuk untuk akses dashboard sales/MR.</div>
            </a>

            <a href="#" onclick="showVisitorModal()" class="role-box">
                <div class="icon">🌐</div>
                <div class="role-name">Saya Seorang Pengunjung</div>
                <div class="role-description">Minta akses whitelist untuk melihat situs.</div>
            </a>

        </div>
    </div>

    <div id="visitorModal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="hideVisitorModal()">&times;</span>
            <h2>Request Whitelist IP</h2>
            <p>Sistem kami mendeteksi alamat IP Anda adalah:</p>
            <div id="ip-display"><?php echo $visitor_ip; ?></div>
            <p>Untuk mengakses, IP Anda harus didaftarkan oleh Admin. Silakan klik tombol di bawah untuk mengirim IP Anda via WhatsApp.</p>
            <a href="<?php echo $wa_link; ?>" target="_blank" class="wa-button">
                Kirim IP via WhatsApp
            </a>
        </div>
    </div>

    <div id="checkerModal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="hideCheckerModal()">&times;</span>
            <h2>Konfirmasi Masuk (Ceker)</h2>
            <p>Anda akan masuk ke dashboard Ceker.</p>
            <br>
            <a href="konfirmasi_ceker.php" class="wa-button" style="background-color: #007bff;">
                OK, Lanjutkan
            </a>
        </div>
    </div>

    <div id="mrModal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="hideMrModal()">&times;</span>
            <h2>Konfirmasi Masuk (MR)</h2>
            <p>Anda akan masuk ke dashboard MR.</p>
            <br>
            <a href="konfirmasi_mr.php" class="wa-button" style="background-color: #6c757d;">
                OK, Lanjutkan
            </a>
        </div>
    </div>

    <script>
        // --- Modal Pengunjung ---
        var visitorModal = document.getElementById('visitorModal');

        function showVisitorModal() {
            event.preventDefault();
            visitorModal.style.display = 'flex';
        }

        function hideVisitorModal() {
            visitorModal.style.display = 'none';
        }

        // --- Modal Ceker ---
        var checkerModal = document.getElementById('checkerModal');

        function showCheckerModal() {
            event.preventDefault();
            checkerModal.style.display = 'flex';
        }

        function hideCheckerModal() {
            checkerModal.style.display = 'none';
        }

        // --- Modal MR (BARU) ---
        var mrModal = document.getElementById('mrModal');

        function showMrModal() {
            event.preventDefault();
            mrModal.style.display = 'flex';
        }

        function hideMrModal() {
            mrModal.style.display = 'none';
        }

        // --- Logika "Klik di luar" untuk menutup modal ---
        window.onclick = function(event) {
            if (event.target == visitorModal) {
                visitorModal.style.display = 'none';
            }
            if (event.target == checkerModal) {
                checkerModal.style.display = 'none';
            }
            if (event.target == mrModal) { // Tambahan
                mrModal.style.display = 'none';
            }
        }
    </script>

</body>

</html>