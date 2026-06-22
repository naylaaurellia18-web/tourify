<?php
// ============================================================
//  RESTOCK TIKET — Admin only
// ============================================================
include __DIR__ . '/session_db.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: /login.php"); exit;
}
include __DIR__ . '/koneksi.php';

$pesan = '';

// Proses restock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $id   = (int)$_POST['id_destinasi'];
    $tambah = (int)$_POST['jumlah_tambah'];
    if ($id > 0 && $tambah > 0) {
        mysqli_query($conn, "UPDATE destinasi SET stok_tiket = stok_tiket + $tambah WHERE id_destinasi = $id");
        $pesan = "Stok berhasil ditambah $tambah tiket!";
    }
}

// Ambil semua destinasi + stok
$dest_list = [];
$q = mysqli_query($conn, "SELECT id_destinasi, nama_destinasi, stok_tiket FROM destinasi ORDER BY nama_destinasi ASC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $dest_list[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock Tiket | Tourify Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#f8fafc; font-family:'Inter',sans-serif; }
        .card-restock { background:#fff; border-radius:16px; border:1.5px solid #e2e8f0; padding:24px; margin-bottom:14px; }
        .stok-badge { font-size:0.78rem; font-weight:700; padding:4px 12px; border-radius:20px; }
    </style>
</head>
<body>
<div class="container py-5" style="max-width:800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:#0f172a;">Restock Tiket</h4>
            <p class="text-muted small mb-0">Tambah stok tiket per destinasi</p>
        </div>
        <a href="/admin.php" class="btn btn-sm fw-semibold" style="background:#f1f5f9;color:#475569;border-radius:10px;"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <?php if ($pesan): ?>
    <div class="alert alert-success border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= $pesan ?></div>
    <?php endif; ?>

    <?php foreach ($dest_list as $d):
        $stok = (int)$d['stok_tiket'];
        $color = $stok < 5 ? '#ef4444' : ($stok < 15 ? '#d97706' : '#16a34a');
        $bg    = $stok < 5 ? '#fef2f2' : ($stok < 15 ? '#fffbeb' : '#f0fdf4');
    ?>
    <div class="card-restock">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0"><?= htmlspecialchars($d['nama_destinasi']) ?></h6>
                <span class="stok-badge" style="background:<?= $bg ?>;color:<?= $color ?>;"><?= $stok ?> tiket tersisa</span>
            </div>
        </div>
        <form method="POST" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="id_destinasi" value="<?= $d['id_destinasi'] ?>">
            <input type="number" name="jumlah_tambah" min="1" max="1000" placeholder="Jumlah tambah" class="form-control" style="max-width:160px;border-radius:10px;" required>
            <button type="submit" name="restock" class="btn fw-semibold" style="background:linear-gradient(135deg,#f37021,#ff8c42);color:#fff;border-radius:10px;white-space:nowrap;">
                <i class="bi bi-plus-circle me-1"></i>Tambah Stok
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
