<?php
// Perbaiki: Jangan memanggil session_start() jika sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Perbaiki: Ganti include 'koneksi.php' (yang salah nama) dengan path ke file database asli
include 'api/koneksi.php'; 

$username_session = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
if (!$username_session) { header("Location: login.php"); exit(); }

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
            <div class="card order-card p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-center">Konfirmasi Pesanan Tourify</h4>
                <form action="proses_pembayaran.php" method="POST">
                    <input type="hidden" name="wisata" value="<?= htmlspecialchars($wisata); ?>">
                    <input type="hidden" id="harga_dasar" name="harga_dasar" value="<?= $harga_asli; ?>">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">Nama Lengkap Pemesan</label>
                        <input type="text" name="nama_pemesan" class="form-control" placeholder="Masukkan nama Anda" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">Destinasi</label>
                        <input type="text" class="form-control-plaintext fw-bold" value="<?= htmlspecialchars($wisata); ?>" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" min="<?= $tgl_min; ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">Jumlah Tiket</label>
                            <input type="number" name="jumlah" id="qty" class="form-control" value="1" min="1" oninput="updateHarga()" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">Metode Pembayaran</label>
                        <select name="metode" class="form-select" required>
                            <option value="" disabled selected>Pilih metode...</option>
                            <option value="transfer_bank">Transfer Bank</option>
                            <option value="e_wallet">E-Wallet</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-4">
                        <span class="text-muted">Total Bayar</span>
                        <h4 class="fw-bold text-primary mb-0" id="tampilan_total">Rp <?= number_format($harga_asli,0,',','.'); ?></h4>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 py-3 fw-bold text-white rounded-pill">BAYAR SEKARANG 🚀</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function updateHarga() {
        let qty = document.getElementById('qty').value;
        let harga = document.getElementById('harga_dasar').value;
        document.getElementById('tampilan_total').innerText = 'Rp ' + (qty * harga).toLocaleString('id-ID');
    }
</script>
</body>
</html>