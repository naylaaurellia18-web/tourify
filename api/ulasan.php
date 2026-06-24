<?php
// ============================================================
//  ULASAN — Tourify (User bisa beri ulasan & rating)
// ============================================================
include __DIR__ . '/session_db.php';

if (empty($_SESSION['login_user'])) {
    header("Location: /login.php"); exit;
}

include __DIR__ . '/koneksi.php';

$username    = $_SESSION['username'] ?? '';
$nama_tampil = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $username;
$pesan       = '';
$tipe_pesan  = '';

// --- Proses Tambah Ulasan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan'])) {
    $id_dest  = (int)$_POST['id_destinasi'];
    $rating   = max(1, min(5, (int)$_POST['rating']));
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar'] ?? ''));
    $uname    = mysqli_real_escape_string($conn, $username);

    // Cek sudah pernah beri ulasan belum
    $cek = mysqli_query($conn, "SELECT id FROM ulasan WHERE id_destinasi=$id_dest AND username='$uname' LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        // Update ulasan lama
        mysqli_query($conn, "UPDATE ulasan SET rating=$rating, komentar='$komentar', created_at=NOW() WHERE id_destinasi=$id_dest AND username='$uname'");
        $pesan = "Ulasan berhasil diperbarui!"; $tipe_pesan = 'success';
    } else {
        // Generate ID manual (TiDB Serverless tidak mendukung AUTO_INCREMENT)
        $r_max_u = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) AS max_id FROM ulasan");
        $max_u   = $r_max_u ? (int)mysqli_fetch_assoc($r_max_u)['max_id'] : 0;
        $new_u_id = $max_u + 1;

        mysqli_query($conn, "INSERT INTO ulasan (id, id_destinasi, username, rating, komentar) VALUES ($new_u_id,$id_dest,'$uname',$rating,'$komentar')");
        $pesan = "Ulasan berhasil dikirim!"; $tipe_pesan = 'success';
    }
}

// --- Proses Hapus Ulasan ---
if (isset($_GET['hapus']) && isset($_GET['id_dest'])) {
    $id_ulasan = (int)$_GET['hapus'];
    $id_dest   = (int)$_GET['id_dest'];
    $uname     = mysqli_real_escape_string($conn, $username);
    mysqli_query($conn, "DELETE FROM ulasan WHERE id=$id_ulasan AND username='$uname'");
    header("Location: /ulasan.php?id=$id_dest"); exit;
}

// --- Ambil destinasi ---
$id_destinasi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$destinasi    = null;
$ulasan_list  = [];
$ulasan_saya  = null;

if ($id_destinasi > 0) {
    $qd = mysqli_query($conn, "SELECT * FROM destinasi WHERE id_destinasi=$id_destinasi LIMIT 1");
    if ($qd) $destinasi = mysqli_fetch_assoc($qd);

    // Ambil semua ulasan
    $qu = mysqli_query($conn, "SELECT * FROM ulasan WHERE id_destinasi=$id_destinasi ORDER BY created_at DESC");
    if ($qu) {
        while ($u = mysqli_fetch_assoc($qu)) {
            $ulasan_list[] = $u;
            if ($u['username'] === $username) $ulasan_saya = $u;
        }
    }
}

// --- Ambil semua destinasi untuk dropdown ---
$all_dest = [];
$qall = mysqli_query($conn, "SELECT id_destinasi, nama_destinasi FROM destinasi ORDER BY nama_destinasi ASC");
if ($qall) while ($r = mysqli_fetch_assoc($qall)) $all_dest[] = $r;

$tahun = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Destinasi | Tourify</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #f37021; --primary-gradient: linear-gradient(135deg,#f37021,#ff8c42); --bg: #f8fafc; --text-dark: #1e293b; --text-muted: #64748b; --border: #e2e8f0; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text-dark); margin:0; }
        h1,h2,h3,h4,h5,h6,.brand-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; }
        .wrapper { display:flex; min-height:100vh; }
        #sidebar { min-width:260px; max-width:260px; background:#fff; border-right:1px solid var(--border); }
        .sidebar-header { padding:28px 24px; border-bottom:1px solid var(--border); }
        .nav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--text-dark); }
        .logo-icon { width:36px; height:36px; background:var(--primary-gradient); color:#fff; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .sidebar-menu { padding:20px 14px; list-style:none; margin:0; }
        .sidebar-menu li { margin-bottom:4px; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:11px 18px; color:var(--text-muted); text-decoration:none; border-radius:12px; font-weight:500; font-size:0.93rem; transition:all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu li.active a { background:#fff3eb; color:var(--primary); font-weight:600; }
        #content { flex:1; padding:32px 36px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
        .user-box { display:flex; align-items:center; gap:10px; background:#fff; padding:8px 18px; border-radius:100px; border:1px solid var(--border); }
        .avatar { width:34px; height:34px; background:#fff3eb; color:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .btn-logout { background:#fef2f2; color:#ef4444; border:1px solid #fca5a5; padding:9px 18px; border-radius:100px; font-weight:600; text-decoration:none; transition:0.2s; }
        .btn-logout:hover { background:#ef4444; color:#fff; }
        .star-input { display:flex; gap:6px; flex-direction:row-reverse; justify-content:flex-end; }
        .star-input input { display:none; }
        .star-input label { font-size:1.8rem; color:#d1d5db; cursor:pointer; transition:color 0.15s; }
        .star-input input:checked ~ label, .star-input label:hover, .star-input label:hover ~ label { color:#f59e0b; }
        .ulasan-card { background:#fff; border-radius:16px; border:1px solid var(--border); padding:20px; margin-bottom:14px; }
        .star-display { color:#f59e0b; }
        .card-dest { background:#fff; border-radius:16px; border:1px solid var(--border); padding:24px; margin-bottom:24px; }
    </style>
</head>
<body>
<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand" href="/dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill text-white"></i></div>
                <span class="brand-title" style="font-size:1.3rem;">Tour<span style="color:var(--primary);">ify</span></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a></li>
            <li><a href="/destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a></li>
            <li><a href="/promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a></li>
            <li><a href="/dashboard.php?page=bps"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li><a href="/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
            <li class="active"><a href="/ulasan.php"><i class="bi bi-star-fill"></i> Ulasan</a></li>
            <li><a href="/profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a></li>
        </ul>
    </nav>

    <div id="content">
        <div class="top-bar">
            <div>
                <h4 class="mb-1">Ulasan Destinasi ⭐</h4>
                <p class="text-muted small mb-0">Bagikan pengalamanmu dan bantu wisatawan lain memilih destinasi terbaik.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-box">
                    <div class="avatar"><?= strtoupper(substr($nama_tampil,0,1)) ?></div>
                    <div class="small fw-semibold"><?= htmlspecialchars($nama_tampil) ?><span class="text-muted d-block" style="font-size:0.72rem;">Pengguna</span></div>
                </div>
                <a href="/logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
            </div>
        </div>

        <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan === 'success' ? 'success' : 'danger' ?> border-0 rounded-3 mb-4"><?= $pesan ?></div>
        <?php endif; ?>

        <!-- Pilih Destinasi -->
        <div class="card-dest">
            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-warning"></i>Pilih Destinasi</h6>
            <form method="GET" action="/ulasan.php" class="d-flex gap-2 flex-wrap">
                <select name="id" class="form-select" style="max-width:320px;border-radius:10px;">
                    <option value="">-- Pilih Destinasi --</option>
                    <?php foreach ($all_dest as $d): ?>
                    <option value="<?= $d['id_destinasi'] ?>" <?= $id_destinasi == $d['id_destinasi'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nama_destinasi']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn fw-semibold" style="background:var(--primary);color:#fff;border-radius:10px;">Lihat Ulasan</button>
            </form>
        </div>

        <?php if ($destinasi): ?>
        <!-- Info Destinasi -->
        <div class="card-dest d-flex gap-3 align-items-center mb-3">
            <?php if (!empty($destinasi['gambar'])): ?>
            <img src="<?= htmlspecialchars($destinasi['gambar']) ?>" style="width:80px;height:60px;object-fit:cover;border-radius:10px;" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
                <h5 class="mb-1 fw-bold"><?= htmlspecialchars($destinasi['nama_destinasi']) ?></h5>
                <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($destinasi['lokasi']) ?></p>
                <p class="text-muted small mb-0"><?= count($ulasan_list) ?> ulasan · Rata-rata: 
                    <?php 
                    $avg = count($ulasan_list) > 0 ? array_sum(array_column($ulasan_list,'rating'))/count($ulasan_list) : 0;
                    echo number_format($avg,1);
                    ?> ⭐
                </p>
            </div>
        </div>

        <!-- Form Tulis Ulasan -->
        <div class="card-dest mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2" style="color:var(--primary);"></i><?= $ulasan_saya ? 'Edit Ulasan Kamu' : 'Tulis Ulasan' ?></h6>
            <form method="POST">
                <input type="hidden" name="id_destinasi" value="<?= $id_destinasi ?>">
                <div class="mb-3">
                    <label class="small fw-semibold mb-2 d-block">Rating</label>
                    <div class="star-input">
                        <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= ($ulasan_saya && $ulasan_saya['rating']==$i) ? 'checked' : ($i==5 && !$ulasan_saya ? 'checked' : '') ?>>
                        <label for="star<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-semibold mb-1">Komentar</label>
                    <textarea name="komentar" rows="3" class="form-control" placeholder="Ceritakan pengalamanmu..." style="border-radius:10px;"><?= htmlspecialchars($ulasan_saya['komentar'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="kirim_ulasan" class="btn fw-semibold" style="background:var(--primary);color:#fff;border-radius:10px;">
                        <i class="bi bi-send me-1"></i><?= $ulasan_saya ? 'Perbarui Ulasan' : 'Kirim Ulasan' ?>
                    </button>
                    <?php if ($ulasan_saya): ?>
                    <a href="/ulasan.php?hapus=<?= $ulasan_saya['id'] ?>&id_dest=<?= $id_destinasi ?>" class="btn fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:10px;" onclick="return confirm('Hapus ulasan?')">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Daftar Ulasan -->
        <h6 class="fw-bold mb-3"><i class="bi bi-chat-square-text me-2 text-muted"></i>Semua Ulasan (<?= count($ulasan_list) ?>)</h6>
        <?php if (empty($ulasan_list)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-star" style="font-size:2.5rem;opacity:0.3;"></i><p class="mt-3">Belum ada ulasan. Jadilah yang pertama!</p></div>
        <?php else: ?>
        <?php foreach ($ulasan_list as $u): ?>
        <div class="ulasan-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                    <div>
                        <div class="fw-semibold small"><?= htmlspecialchars($u['username']) ?> <?= $u['username']===$username ? '<span class="badge" style="background:#fff3eb;color:var(--primary);font-size:0.65rem;">Kamu</span>' : '' ?></div>
                        <div class="star-display" style="font-size:0.8rem;"><?= str_repeat('★',$u['rating']) ?><?= str_repeat('☆',5-$u['rating']) ?></div>
                    </div>
                </div>
                <span class="text-muted" style="font-size:0.75rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
            </div>
            <p class="mb-0 small" style="color:var(--text-dark);"><?= htmlspecialchars($u['komentar']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-star" style="font-size:3rem;opacity:0.3;"></i>
            <p class="mt-3">Pilih destinasi untuk melihat dan menulis ulasan.</p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-5 opacity-50 small">&copy; <?= $tahun ?> Tourify.</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>