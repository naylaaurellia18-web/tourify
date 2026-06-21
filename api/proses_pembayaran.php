<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (file_exists('api/koneksi.php')) include 'api/koneksi.php';
elseif (file_exists('koneksi.php')) include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: destinasi.php"); exit(); }

$username     = $_SESSION['user'] ?? $_SESSION['username'] ?? 'Guest';
$nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan'] ?? '');
$wisata       = mysqli_real_escape_string($conn, $_POST['wisata'] ?? '');
$jumlah       = (int)($_POST['jumlah'] ?? 1);
$tanggal      = $_POST['tanggal'] ?? date('Y-m-d');
$metode       = mysqli_real_escape_string($conn, $_POST['metode'] ?? '');
$harga_dasar  = (int)($_POST['harga_dasar'] ?? 0);
$kode_promo   = mysqli_real_escape_string($conn, strtoupper($_POST['kode'] ?? ''));
$potongan     = 0;
$subtotal     = $jumlah * $harga_dasar;
$total_bayar  = $subtotal;

// Validasi promo di server
if (!empty($kode_promo) && isset($conn)) {
    $qv = mysqli_query($conn, "SELECT * FROM voucher WHERE kode='$kode_promo' AND aktif=1 LIMIT 1");
    if ($qv && mysqli_num_rows($qv) > 0) {
        $v = mysqli_fetch_assoc($qv);
        if ($v['diskon'] > 0) $potongan = round($subtotal * $v['diskon'] / 100);
        else $potongan = min((int)$v['potongan'], $subtotal);
        $total_bayar = max(0, $subtotal - $potongan);
    } else { $kode_promo = ''; $total_bayar = $subtotal; }
}

// --- Cek stok tiket sebelum memproses pesanan ---
$stok_tersedia  = null;
$id_destinasi   = null;
$qd = mysqli_query($conn, "SELECT id_destinasi, stok_tiket FROM destinasi WHERE nama_destinasi = '$wisata' LIMIT 1");
if ($qd && mysqli_num_rows($qd) > 0) {
    $rowd          = mysqli_fetch_assoc($qd);
    $id_destinasi  = (int)$rowd['id_destinasi'];
    $stok_tersedia = (int)$rowd['stok_tiket'];
}

// Kalau destinasi ditemukan di database dan stoknya tidak cukup, hentikan transaksi
if ($id_destinasi !== null && $stok_tersedia < $jumlah) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stok Tidak Cukup | Tourify</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body { background:linear-gradient(135deg,#fff7ed,#f8fafc); font-family:'Inter',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; margin:0; }
            .box { background:white; border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,0.08); max-width:440px; padding:40px; text-align:center; }
            .icon { width:72px;height:72px;background:#fef2f2;color:#ef4444;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 20px; }
            .btn-back { background:linear-gradient(135deg,#f37021,#ff8c42);border:none;color:white;font-weight:700;padding:12px 24px;border-radius:12px;text-decoration:none;display:inline-block;margin-top:10px; }
            .btn-back:hover { opacity:0.9;color:white; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <h5 class="fw-bold mb-2">Stok Tiket Tidak Cukup</h5>
            <p class="text-muted small mb-1">Destinasi <strong><?= htmlspecialchars($wisata) ?></strong> hanya tersisa <strong><?= $stok_tersedia ?> tiket</strong>, sedangkan kamu memesan <strong><?= $jumlah ?> tiket</strong>.</p>
            <p class="text-muted small">Silakan kurangi jumlah tiket atau pilih destinasi lain.</p>
            <a href="destinasi.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Destinasi</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Simpan ke database
$sql = "INSERT INTO pesanan (username, nama_pemesan, wisata, jumlah, tanggal, metode_pembayaran, kode_promo, total_bayar)
        VALUES ('$username','$nama_pemesan','$wisata',$jumlah,'$tanggal','$metode','$kode_promo',$total_bayar)";

$id_pesanan = 0;
if (mysqli_query($conn, $sql)) {
    $id_pesanan = mysqli_insert_id($conn);

    // Kurangi stok tiket destinasi terkait, kalau ditemukan di database
    // (klausa AND stok_tiket >= jumlah mencegah stok jadi negatif jika ada request bersamaan)
    if ($id_destinasi !== null) {
        mysqli_query($conn, "UPDATE destinasi SET stok_tiket = stok_tiket - $jumlah WHERE id_destinasi = $id_destinasi AND stok_tiket >= $jumlah");
    }
} else {
    die("Error: " . mysqli_error($conn));
}

// Info pembayaran per metode
$info_bayar = [
    'transfer_bank' => [
        'label' => 'Transfer Bank',
        'icon'  => 'bi-bank2',
        'color' => '#2563eb',
        'bg'    => '#eff6ff',
        'detail'=> [
            ['bank'=>'BCA',     'no'=>'1234567890',   'atas_nama'=>'Tourify Indonesia'],
            ['bank'=>'Mandiri', 'no'=>'0987654321',   'atas_nama'=>'Tourify Indonesia'],
            ['bank'=>'BRI',     'no'=>'1122334455',   'atas_nama'=>'Tourify Indonesia'],
        ]
    ],
    'e_wallet' => [
        'label' => 'E-Wallet',
        'icon'  => 'bi-phone-fill',
        'color' => '#7c3aed',
        'bg'    => '#f5f3ff',
        'detail'=> [
            ['wallet'=>'GoPay',   'no'=>'0812-3456-7890', 'atas_nama'=>'Tourify Indonesia'],
            ['wallet'=>'OVO',     'no'=>'0812-3456-7890', 'atas_nama'=>'Tourify Indonesia'],
            ['wallet'=>'DANA',    'no'=>'0812-3456-7890', 'atas_nama'=>'Tourify Indonesia'],
            ['wallet'=>'ShopeePay','no'=>'0812-3456-7890','atas_nama'=>'Tourify Indonesia'],
        ]
    ],
    'qris' => [
        'label' => 'QRIS',
        'icon'  => 'bi-qr-code-scan',
        'color' => '#059669',
        'bg'    => '#ecfdf5',
    ]
];

$metode_info = $info_bayar[$metode] ?? $info_bayar['transfer_bank'];
$kode_unik   = 'TRF-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selesaikan Pembayaran | Tourify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        :root { --primary:#f37021; }
        body { background:linear-gradient(135deg,#fff7ed,#f8fafc); font-family:'Inter',sans-serif; min-height:100vh; }
        h1,h2,h3,h4,h5,h6 { font-family:'Plus Jakarta Sans',sans-serif; }

        .main-card { background:white; border-radius:28px; box-shadow:0 20px 60px rgba(243,112,33,0.08); max-width:600px; margin:auto; overflow:hidden; }

        .success-banner { background:linear-gradient(135deg,#f37021,#ff8c42); padding:32px; color:white; text-align:center; }
        .success-icon { width:72px;height:72px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px; }

        .card-body-p { padding:32px; }

        .info-row { display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:0.92rem; }
        .info-row:last-child { border:none; }
        .info-row .label { color:#64748b; }
        .info-row .val   { font-weight:700;color:#1e293b;text-align:right; }

        /* Transfer Bank */
        .bank-card { border:1.5px solid #e2e8f0;border-radius:14px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;gap:16px;cursor:pointer;transition:all 0.2s; }
        .bank-card:hover { border-color:#2563eb;background:#eff6ff; }
        .bank-logo { width:48px;height:30px;background:#1e40af;border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:0.7rem;font-weight:800;flex-shrink:0; }
        .bank-no { font-family:monospace;font-size:1.1rem;font-weight:700;color:#1e293b;letter-spacing:1px; }
        .copy-btn { margin-left:auto;background:#eff6ff;border:none;color:#2563eb;border-radius:8px;padding:6px 12px;font-size:0.8rem;font-weight:700;cursor:pointer;transition:0.2s;white-space:nowrap; }
        .copy-btn:hover { background:#2563eb;color:white; }

        /* E-Wallet */
        .wallet-card { border:1.5px solid #e2e8f0;border-radius:14px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;gap:16px; }
        .wallet-badge { min-width:80px;padding:6px 12px;border-radius:8px;background:#f5f3ff;color:#7c3aed;font-weight:800;font-size:0.82rem;text-align:center; }
        .wallet-no { font-family:monospace;font-size:1rem;font-weight:700;color:#1e293b;letter-spacing:1px; }

        /* QRIS */
        .qris-wrap { text-align:center;padding:20px; }
        #qrcode canvas, #qrcode img { border-radius:12px;border:3px solid #e2e8f0; }
        .qris-label { font-size:0.82rem;color:#64748b;margin-top:10px; }

        /* Timer */
        .timer-box { background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:12px 20px;display:flex;align-items:center;gap:12px;margin-bottom:24px; }
        .timer-num { font-size:1.5rem;font-weight:800;color:#ef4444;font-family:'Plus Jakarta Sans',sans-serif; }

        .btn-selesai { background:linear-gradient(135deg,#f37021,#ff8c42);border:none;color:white;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;padding:16px;font-size:1rem;border-radius:14px;box-shadow:0 8px 24px rgba(243,112,33,0.3);transition:all 0.2s;width:100%; }
        .btn-selesai:hover { transform:translateY(-2px);color:white; }

        .section-title { font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:14px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="main-card">
        <!-- Banner Sukses -->
        <div class="success-banner">
            <div class="success-icon">🎉</div>
            <h4 class="fw-bold mb-1">Pesanan Berhasil Dibuat!</h4>
            <p class="mb-1 small opacity-75">Selesaikan pembayaran sebelum waktu habis</p>
            <div class="mt-2 d-inline-block px-3 py-1 rounded-pill fw-bold" style="background:rgba(255,255,255,0.2);font-size:1.1rem;">
                #<?= $kode_unik ?>
            </div>
        </div>

        <div class="card-body-p">
            <!-- Timer 30 menit -->
            <div class="timer-box">
                <i class="bi bi-alarm-fill text-danger fs-4"></i>
                <div>
                    <div class="small fw-semibold text-danger">Batas waktu pembayaran</div>
                    <div class="timer-num" id="timer">30:00</div>
                </div>
            </div>

            <!-- Ringkasan Pesanan -->
            <div class="section-title">Ringkasan Pesanan</div>
            <div class="mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="info-row"><span class="label">Destinasi</span><span class="val"><?= htmlspecialchars($wisata) ?></span></div>
                <div class="info-row"><span class="label">Pemesan</span><span class="val"><?= htmlspecialchars($nama_pemesan) ?></span></div>
                <div class="info-row"><span class="label">Tanggal Kunjungan</span><span class="val"><?= date('d M Y', strtotime($tanggal)) ?></span></div>
                <div class="info-row"><span class="label">Jumlah Tiket</span><span class="val"><?= $jumlah ?> tiket</span></div>
                <?php if ($potongan > 0): ?>
                <div class="info-row"><span class="label">Diskon (<?= htmlspecialchars($kode_promo) ?>)</span><span class="val text-success">- Rp <?= number_format($potongan,0,',','.') ?></span></div>
                <?php endif; ?>
                <div class="info-row"><span class="label">Metode</span><span class="val"><i class="bi <?= $metode_info['icon'] ?> me-1"></i><?= $metode_info['label'] ?></span></div>
                <div class="info-row" style="border:none;padding-top:14px;margin-top:4px;border-top:2px dashed #e2e8f0;">
                    <span class="label fw-bold text-dark">Total Bayar</span>
                    <span class="val fw-bold" style="color:var(--primary);font-size:1.15rem;">Rp <?= number_format($total_bayar,0,',','.') ?></span>
                </div>
            </div>

            <!-- INFO PEMBAYARAN SESUAI METODE -->
            <div class="section-title">Cara Pembayaran — <?= $metode_info['label'] ?></div>

            <?php if ($metode === 'transfer_bank'): ?>
            <p class="small text-muted mb-3">Transfer tepat sejumlah <strong>Rp <?= number_format($total_bayar,0,',','.') ?></strong> ke salah satu rekening berikut:</p>
            <?php foreach ($metode_info['detail'] as $b): ?>
            <div class="bank-card">
                <div class="bank-logo"><?= $b['bank'] ?></div>
                <div>
                    <div class="bank-no"><?= $b['no'] ?></div>
                    <div class="small text-muted">a/n <?= $b['atas_nama'] ?></div>
                </div>
                <button class="copy-btn" onclick="salin('<?= $b['no'] ?>', this)"><i class="bi bi-clipboard me-1"></i>Salin</button>
            </div>
            <?php endforeach; ?>
            <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Tambahkan kode unik <strong><?= substr($id_pesanan,-3) ?></strong> di akhir nominal transfer untuk verifikasi otomatis.</div>

            <?php elseif ($metode === 'e_wallet'): ?>
            <p class="small text-muted mb-3">Transfer sejumlah <strong>Rp <?= number_format($total_bayar,0,',','.') ?></strong> ke salah satu e-wallet berikut:</p>
            <?php foreach ($metode_info['detail'] as $w): ?>
            <div class="wallet-card">
                <div class="wallet-badge"><?= $w['wallet'] ?></div>
                <div>
                    <div class="wallet-no"><?= $w['no'] ?></div>
                    <div class="small text-muted">a/n <?= $w['atas_nama'] ?></div>
                </div>
                <button class="copy-btn ms-auto" onclick="salin('<?= $w['no'] ?>', this)" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-clipboard me-1"></i>Salin</button>
            </div>
            <?php endforeach; ?>

            <?php elseif ($metode === 'qris'): ?>
            <p class="small text-muted mb-3">Scan QR Code di bawah menggunakan aplikasi pembayaran apapun (GoPay, OVO, DANA, ShopeePay, mobile banking, dll).</p>
            <div class="qris-wrap">
                <div id="qrcode" style="display:inline-block;"></div>
                <div class="qris-label">Total: <strong>Rp <?= number_format($total_bayar,0,',','.') ?></strong> · Kode: <?= $kode_unik ?></div>
                <div class="small text-muted mt-1">QR berlaku selama 30 menit</div>
            </div>
            <?php endif; ?>

            <!-- Tombol Selesai -->
            <div class="mt-4">
                <a href="riwayat_pesanan.php" class="btn btn-selesai d-block text-center">
                    <i class="bi bi-check-circle-fill me-2"></i>Saya Sudah Bayar — Lihat E-Tiket
                </a>
                <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-3 rounded-3 fw-semibold">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
// Generate QR Code untuk QRIS
<?php if ($metode === 'qris'): ?>
const qrData = "TOURIFY|<?= $kode_unik ?>|<?= $wisata ?>|<?= $total_bayar ?>|<?= date('Y-m-d') ?>";
new QRCode(document.getElementById("qrcode"), {
    text: qrData, width: 220, height: 220,
    colorDark:"#1e293b", colorLight:"#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
<?php endif; ?>

// Countdown timer 30 menit
let sisa = 30 * 60;
const timerEl = document.getElementById('timer');
const iv = setInterval(() => {
    sisa--;
    if (sisa <= 0) { clearInterval(iv); timerEl.textContent = '00:00'; timerEl.style.color='#94a3b8'; return; }
    const m = String(Math.floor(sisa/60)).padStart(2,'0');
    const s = String(sisa%60).padStart(2,'0');
    timerEl.textContent = m+':'+s;
    if (sisa <= 300) timerEl.style.color='#dc2626';
}, 1000);

// Salin ke clipboard
function salin(teks, btn) {
    navigator.clipboard.writeText(teks).then(() => {
        const semula = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin!';
        btn.style.background='#dcfce7'; btn.style.color='#16a34a';
        setTimeout(()=>{ btn.innerHTML=semula; btn.style.background=''; btn.style.color=''; }, 2000);
    });
}
</script>
</body>
</html>