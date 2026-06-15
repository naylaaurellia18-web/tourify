<?php
include 'koneksi.php';
session_start();

$username_session = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
if (!$username_session) { header("Location: login.php"); exit(); }

$wisata    = isset($_GET['wisata'])   ? strip_tags($_GET['wisata'])   : "Destinasi";
$harga_asli= isset($_GET['harga'])   ? (int)$_GET['harga']           : 0;

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .order-card { border-radius: 25px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .promo-card { cursor: pointer; transition: 0.3s; border: 1px solid #eee; border-radius: 15px; padding: 15px; margin-bottom: 10px; }
        .promo-card:hover { border-color: #f37021; background: #fff5ef; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card order-card p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-center">Konfirmasi Pesanan Tourify</h4>
                
                <form action="pembayaran.php" method="GET">
                    <input type="hidden" name="wisata" value="<?= htmlspecialchars($wisata); ?>">
                    <input type="hidden" id="harga_dasar" value="<?= $harga_asli; ?>">

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
                            <input type="number" name="jumlah" id="qty" class="form-control" value="1" min="1" oninput="updateHarga()">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="small text-muted fw-bold">Kode Promo</label>
                        <div class="input-group">
                            <input type="text" name="kode" id="kode_display" class="form-control" placeholder="Pilih promo..." readonly>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPromo">PILIH PROMO</button>
                        </div>
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

<div class="modal fade" id="modalPromo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0"><h5 class="fw-bold">Pilih Promo Tourify</h5></div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <?php 
                $promos = [
                    ['kode' => 'GO-JATENG20', 'info' => 'Diskon 20% Borobudur, Saloka, Solo Safari'],
                    ['kode' => 'SOLO-SAFARI', 'info' => 'Potongan Rp 5.000 Solo Safari'],
                    ['kode' => 'ALAM-INDO', 'info' => 'Diskon 15% Wisata Alam & Air'],
                    ['kode' => 'HERITAGE-10K', 'info' => 'Potongan Rp 10.000 The Heritage Palace'],
                    ['kode' => 'SALOKA-WEEKEND', 'info' => 'Diskon 10% Weekend Saloka'],
                    ['kode' => 'MEMBER-BARU', 'info' => 'Potongan Rp 5.000 untuk Member Baru']
                ];
                foreach($promos as $p): ?>
                    <div class="promo-card" onclick="pilihPromo('<?= $p['kode']; ?>')">
                        <div class="fw-bold text-danger"><?= $p['kode']; ?></div>
                        <div class="small text-muted"><?= $p['info']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function updateHarga() {
        let qty = document.getElementById('qty').value;
        let harga = document.getElementById('harga_dasar').value;
        let total = qty * harga;
        document.getElementById('tampilan_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }
    function pilihPromo(kode) {
        document.getElementById('kode_display').value = kode;
        bootstrap.Modal.getInstance(document.getElementById('modalPromo')).hide();
        Swal.fire('Berhasil', 'Promo ' + kode + ' telah dipilih!', 'success');
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>