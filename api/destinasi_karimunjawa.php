<?php
// ============================================================
//  HALAMAN DESTINASI — Taman Nasional Karimunjawa
//  Berisi: tampilan publik (info + beli tiket) DAN
//  panel mini admin (edit data, hanya untuk yang login sebagai admin).
//  Data utama disinkronkan ke tabel `destinasi` (kolom id_destinasi).
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (file_exists('api/koneksi.php')) include 'api/koneksi.php';
elseif (file_exists('koneksi.php')) include 'koneksi.php';

$nama_default      = 'Taman Nasional Karimunjawa';
$lokasi_default    = 'Jepara';
$deskripsi_default = 'Taman Nasional Karimunjawa menawarkan pesona wisata bahari terindah dengan keindahan bawah laut dan pantai berpasir putih. Cocok untuk snorkeling, diving, maupun sekadar bersantai menikmati ketenangan laut Jawa yang masih asri.';
$harga_default     = 200000;
$gambar_default    = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQsIs1YIW602fv8a-S9qUgwZWFd8_qyp7X5lQ&s';
$accent            = '#0e7490';
$highlight_items   = ['Snorkeling & diving', 'Pantai pasir putih', 'Terumbu karang alami', 'Island hopping'];

// --- Ambil data terkini dari database (kalau sudah ada baris untuk destinasi ini) ---
// Dipindah ke atas supaya id_destinasi-nya bisa dipakai untuk cek hak akses admin.
$d = null;
if (isset($conn)) {
    $nama_escaped = mysqli_real_escape_string($conn, $nama_default);
    $q = mysqli_query($conn, "SELECT * FROM destinasi WHERE nama_destinasi = '$nama_escaped' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $d = mysqli_fetch_assoc($q);
}
$id_destinasi_halaman = isset($d['id_destinasi']) ? (int)$d['id_destinasi'] : null;

// --- Cek hak akses admin untuk destinasi INI secara spesifik ---
// Panel admin hanya muncul untuk:
//  (a) super admin (boleh edit semua destinasi), atau
//  (b) admin destinasi yang id_destinasi sesi-nya SAMA dengan destinasi halaman ini.
$is_super_admin = !empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'super';
$is_admin_destinasi_ini = !empty($_SESSION['admin_id'])
    && ($_SESSION['admin_role'] ?? '') === 'destinasi'
    && $id_destinasi_halaman !== null
    && (int)($_SESSION['admin_destinasi'] ?? -1) === $id_destinasi_halaman;
$is_admin = $is_super_admin || $is_admin_destinasi_ini;

// --- Proses update data dari panel admin ---
$sukses_update = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_admin']) && $is_admin && isset($conn)) {
    $harga_baru     = (int)($_POST['harga'] ?? $harga_default);
    $stok_baru      = (int)($_POST['stok'] ?? 0);
    $deskripsi_baru = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? $deskripsi_default);
    $gambar_baru    = mysqli_real_escape_string($conn, $_POST['gambar'] ?? $gambar_default);
    $nama_escaped   = mysqli_real_escape_string($conn, $nama_default);
    $lokasi_escaped = mysqli_real_escape_string($conn, $lokasi_default);

    if ($id_destinasi_halaman !== null) {
        mysqli_query($conn, "UPDATE destinasi SET harga=$harga_baru, stok_tiket=$stok_baru, deskripsi='$deskripsi_baru', gambar='$gambar_baru', lokasi='$lokasi_escaped' WHERE id_destinasi = $id_destinasi_halaman");
    } else {
        mysqli_query($conn, "INSERT INTO destinasi (nama_destinasi, lokasi, deskripsi, harga, stok_tiket, gambar) VALUES ('$nama_escaped','$lokasi_escaped','$deskripsi_baru',$harga_baru,$stok_baru,'$gambar_baru')");
    }
    $sukses_update = true;

    // Ambil ulang data terbaru setelah update, supaya tampilan langsung sinkron
    $q = mysqli_query($conn, "SELECT * FROM destinasi WHERE nama_destinasi = '$nama_escaped' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $d = mysqli_fetch_assoc($q);
}

$nama      = $d['nama_destinasi'] ?? $nama_default;
$lokasi    = $d['lokasi'] ?? $lokasi_default;
$deskripsi = $d['deskripsi'] ?? $deskripsi_default;
$harga     = (int)($d['harga'] ?? $harga_default);
$gambar    = !empty($d['gambar']) ? $d['gambar'] : $gambar_default;
$stok      = (int)($d['stok_tiket'] ?? 0);

date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nama) ?> | Tourify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --accent: <?= $accent ?>;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        body { font-family:'Inter',sans-serif; background:#f8fafc; color:var(--text-dark); margin:0; }
        h1,h2,h3,h4,h5,h6 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; }

        .hero { position:relative; height:46vh; min-height:340px; overflow:hidden; }
        .hero img { width:100%; height:100%; object-fit:cover; }
        .hero::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg,rgba(15,23,42,0.05) 0%, rgba(15,23,42,0.75) 100%); }
        .hero-text { position:absolute; bottom:28px; left:0; right:0; color:white; padding:0 5%; }
        .hero-text .loc { font-size:0.9rem; opacity:0.9; }
        .hero-text h1 { font-size:2.1rem; margin:4px 0 0; }
        .back-btn { position:absolute; top:20px; left:5%; background:rgba(255,255,255,0.15); backdrop-filter:blur(6px); color:white; border:1px solid rgba(255,255,255,0.3); border-radius:100px; padding:8px 18px; text-decoration:none; font-weight:600; font-size:0.85rem; }
        .back-btn:hover { background:rgba(255,255,255,0.25); color:white; }

        .container-main { max-width:1080px; margin:-40px auto 60px; padding:0 20px; position:relative; }
        .info-card { background:#fff; border-radius:20px; box-shadow:0 10px 40px rgba(15,23,42,0.08); padding:32px; }

        .price-tag { font-size:1.6rem; font-weight:800; color:var(--accent); }
        .stok-pill { font-size:0.78rem; font-weight:700; padding:5px 14px; border-radius:20px; }

        .highlight-list { list-style:none; padding:0; margin:18px 0 0; display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .highlight-list li { font-size:0.9rem; color:var(--text-dark); }
        .highlight-list i { color:var(--accent); margin-right:8px; }

        .btn-beli { background:var(--accent); border:none; color:white; font-weight:700; padding:14px 28px; border-radius:14px; font-size:1rem; transition:0.2s; }
        .btn-beli:hover { opacity:0.92; color:white; transform:translateY(-1px); }

        .admin-panel { background:#0f172a; border-radius:20px; padding:28px; margin-top:24px; color:#fff; }
        .admin-panel label { font-size:0.82rem; font-weight:600; opacity:0.85; }
        .admin-panel input, .admin-panel textarea { border-radius:10px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.06); color:#fff; padding:10px 14px; width:100%; }
        .admin-panel input::placeholder, .admin-panel textarea::placeholder { color:rgba(255,255,255,0.4); }
        .admin-panel input:focus, .admin-panel textarea:focus { outline:none; border-color:var(--accent); background:rgba(255,255,255,0.1); }
        .btn-admin-save { background:var(--accent); border:none; color:#fff; font-weight:700; padding:10px 22px; border-radius:10px; }

        @media (max-width: 576px) {
            .highlight-list { grid-template-columns:1fr; }
            .hero-text h1 { font-size:1.5rem; }
        }
    </style>
</head>
<body>

<div class="hero">
    <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($nama) ?>" onerror="this.src='https://via.placeholder.com/1200x500?text=<?= urlencode($nama) ?>'">
    <a href="destinasi.php" class="back-btn"><i class="bi bi-arrow-left me-1"></i> Semua Destinasi</a>
    <div class="hero-text">
        <div class="loc"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($lokasi) ?></div>
        <h1><?= htmlspecialchars($nama) ?></h1>
    </div>
</div>

<div class="container-main">
    <div class="info-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <span class="price-tag">Rp <?= number_format($harga, 0, ',', '.') ?></span>
                <span class="text-muted small">/ tiket</span>
            </div>
            <span class="stok-pill" style="background:<?= $stok < 5 ? '#fef2f2' : ($stok < 15 ? '#fffbeb' : '#f0fdf4') ?>;color:<?= $stok < 5 ? '#ef4444' : ($stok < 15 ? '#d97706' : '#16a34a') ?>;">
                <?= $stok ?> tiket tersisa
            </span>
        </div>

        <p class="text-muted" style="line-height:1.7;"><?= htmlspecialchars($deskripsi) ?></p>

        <ul class="highlight-list">
            <?php foreach ($highlight_items as $h): ?>
            <li><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($h) ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-beli" onclick="lanjutBeli()">
                <i class="bi bi-ticket-perforated-fill me-2"></i>Pesan Tiket Sekarang
            </button>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <!-- ===================== PANEL ADMIN ===================== -->
    <div class="admin-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Panel Admin — Edit Data Destinasi</h6>
            <a href="admin.php" class="small text-white-50 text-decoration-none">← Kembali ke Panel Admin</a>
        </div>

        <?php if ($sukses_update): ?>
        <div class="alert border-0 mb-3" style="background:rgba(34,197,94,0.15);color:#86efac;border-radius:10px;">
            <i class="bi bi-check-circle-fill me-1"></i> Data berhasil diperbarui.
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Harga Tiket (Rp)</label>
                    <input type="number" name="harga" value="<?= $harga ?>" required>
                </div>
                <div class="col-md-6">
                    <label>Stok Tiket</label>
                    <input type="number" name="stok" value="<?= $stok ?>" required>
                </div>
                <div class="col-12">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3"><?= htmlspecialchars($deskripsi) ?></textarea>
                </div>
                <div class="col-12">
                    <label>URL Gambar</label>
                    <input type="text" name="gambar" value="<?= htmlspecialchars($gambar) ?>">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" name="simpan_admin" class="btn-admin-save">
                        <i class="bi bi-save2 me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function lanjutBeli() {
    const nama  = <?= json_encode($nama) ?>;
    const harga = <?= (int)$harga ?>;
    window.location.href = "konfirmasi_pesanan.php?wisata=" + encodeURIComponent(nama) + "&harga=" + harga;
}
</script>
</body>
</html>