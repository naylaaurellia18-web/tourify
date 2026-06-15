<?php
// 1. Session harus distart PERTAMA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Koneksi Database
if (file_exists('api/koneksi.php')) {
    include 'api/koneksi.php';
} elseif (file_exists('koneksi.php')) {
    include 'koneksi.php';
}

// 3. Cek Login
$username_session = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;
if (!$username_session || !$is_logged_in) { 
    header("Location: login.php"); 
    exit(); 
}

// 4. Validasi Parameter URL
$wisata     = isset($_GET['wisata']) ? strip_tags($_GET['wisata']) : "Destinasi";
$harga_asli = isset($_GET['harga']) ? (int)$_GET['harga'] : 0;

if ($harga_asli <= 0) {
    echo "<script>alert('Harga tidak valid.'); window.location.href='destinasi.php';</script>";
    exit();
}

$tgl_min = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pesanan | Tourify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .order-card { border-radius: 25px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card order-card p-4">
                <h4 class="fw-bold mb-4 text-center">Konfirmasi Pesanan</h4>
                <form action="proses_pembayaran.php" method="POST">
                    <input type="hidden" name="wisata" value="<?= htmlspecialchars($wisata); ?>">
                    <input type="hidden" name="harga_dasar" value="<?= $harga_asli; ?>">
                    
                    <div class="mb-3">
                        <label class="fw-bold small text-muted">Nama Pemesan</label>
                        <input type="text" name="nama_pemesan" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">BAYAR SEKARANG</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>