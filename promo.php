<?php
// promo.php - Halaman Daftar Promo & Voucher

// 1. Session pertama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Koneksi database
if (file_exists('api/koneksi.php')) {
    include 'api/koneksi.php';
} elseif (file_exists('koneksi.php')) {
    include 'koneksi.php';
}

// 3. Cek login
$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;
if (!$nama_tampil || !$is_logged_in) {
    header("Location: api/login.php");
    exit();
}

// 4. Ambil semua voucher aktif dari database
$daftar_voucher = [];
if (isset($conn)) {
    $q = mysqli_query($conn, "
        SELECT v.*, d.nama_destinasi
        FROM voucher v
        LEFT JOIN destinasi d ON d.id_destinasi = v.id_destinasi
        WHERE v.aktif = 1
        ORDER BY v.id ASC
    ");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $daftar_voucher[] = $row;
        }
    }
}

date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Eksklusif | Tourify</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-main: #f8fafc;
            --bg-sidebar: #ffffff;
            --primary: #f37021;
            --primary-gradient: linear-gradient(135deg, #f37021, #ff8c42);
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            margin: 0;
            overflow-x: hidden;
        }

        h1,h2,h3,h4,h5,h6,.brand-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }

        .wrapper { display: flex; width: 100%; min-height: 100vh; }

        /* Sidebar */
        #sidebar {
            min-width: 260px; max-width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
        }
        .sidebar-header { padding: 30px 25px; border-bottom: 1px solid var(--border-color); }
        .nav-brand-box { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text-dark); }
        .logo-icon {
            width: 35px; height: 35px;
            background: var(--primary-gradient);
            color: white; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(243,112,33,0.2);
        }
        .sidebar-menu { padding: 25px 15px; list-style: none; margin: 0; }
        .sidebar-menu li { margin-bottom: 6px; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px; color: var(--text-muted);
            text-decoration: none; border-radius: 12px;
            font-weight: 500; font-size: 0.95rem; transition: all 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu li.active a {
            background: #fff3eb; color: var(--primary); font-weight: 600;
        }

        /* Content */
        #content { flex: 1; padding: 35px 40px; background-color: var(--bg-main); }
        .top-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .user-profile-box {
            display: flex; align-items: center; gap: 12px;
            background: #fff; padding: 8px 20px;
            border-radius: 100px; border: 1px solid var(--border-color);
        }
        .avatar-circle {
            width: 35px; height: 35px;
            background: rgba(243,112,33,0.1); color: var(--primary);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .btn-logout {
            background: #fef2f2; color: #ef4444;
            border: 1px solid #fca5a5; padding: 10px 20px;
            border-radius: 100px; font-weight: 600;
            text-decoration: none; transition: 0.2s;
        }
        .btn-logout:hover { background: #ef4444; color: white; }

        /* Voucher Card */
        .voucher-card {
            background: white;
            border-radius: 20px;
            border: 2px dashed var(--border-color);
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
            transition: all 0.25s;
        }
        .voucher-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 30px rgba(243,112,33,0.12);
            transform: translateY(-2px);
        }
        .voucher-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 6px;
            background: var(--primary-gradient);
            border-radius: 20px 0 0 20px;
        }
        .voucher-badge {
            font-size: 2rem;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--primary);
            line-height: 1;
        }
        .kode-box {
            background: #fff7ed;
            border: 1.5px dashed #f37021;
            border-radius: 10px;
            padding: 8px 16px;
            font-family: monospace;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-block;
        }
        .kode-box:hover { background: #ffedd5; }
        .copy-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }

        .empty-box {
            text-align: center; padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-box i { font-size: 3rem; opacity: 0.3; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand-box" href="dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
                <span class="brand-title" style="font-size:1.4rem;">Tour<span style="color:var(--primary);">ify</span></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a></li>
            <li><a href="destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a></li>
            <li class="active"><a href="promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a></li>
            <li><a href="dashboard.php?page=bps_stat"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li><a href="riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
        </ul>
    </nav>

    <!-- Konten -->
    <div id="content">
        <!-- Navbar Atas -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark">Promo & Voucher Eksklusif 🏷️</h4>
                <p class="text-muted small mb-0">Salin kode promo dan gunakan saat checkout untuk mendapat diskon spesial.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile-box">
                    <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                    <div class="small fw-semibold d-none d-sm-block">
                        <?= htmlspecialchars($nama_tampil) ?>
                        <span class="text-muted d-block" style="font-size:0.75rem;">Pengguna</span>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>

        <!-- Banner Info -->
        <div class="p-4 mb-4 rounded-4 text-white" style="background: var(--primary-gradient);">
            <h5 class="fw-bold mb-1"><i class="bi bi-gift-fill me-2"></i>Cara Pakai Voucher</h5>
            <p class="mb-0 small opacity-75">Pilih destinasi di <a href="destinasi.php" class="text-white fw-bold">Sistem Tiket</a>, masukkan kode voucher di kolom promo saat checkout, lalu diskon otomatis terpotong dari total pembayaran.</p>
        </div>

        <!-- Grid Voucher -->
        <?php if (empty($daftar_voucher)): ?>
            <div class="empty-box">
                <i class="bi bi-tags mb-3 d-block"></i>
                <h6 class="fw-bold">Belum Ada Promo Tersedia</h6>
                <p class="small">Pantau terus halaman ini untuk promo terbaru dari Tourify.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($daftar_voucher as $v): 
                    $nilai = $v['diskon'] > 0
                        ? 'Diskon ' . (int)$v['diskon'] . '%'
                        : 'Hemat Rp ' . number_format($v['potongan'], 0, ',', '.');
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="voucher-card">
                        <div class="voucher-badge mb-2"><?= $nilai ?></div>
                        <?php if (!empty($v['nama_destinasi'])): ?>
                            <span class="badge mb-2" style="background:#f5f3ff;color:#7c3aed;border-radius:20px;font-size:0.7rem;font-weight:700;padding:5px 12px;">
                                <i class="bi bi-geo-alt-fill me-1"></i>Khusus <?= htmlspecialchars($v['nama_destinasi']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge mb-2" style="background:#eff6ff;color:#2563eb;border-radius:20px;font-size:0.7rem;font-weight:700;padding:5px 12px;">
                                <i class="bi bi-stars me-1"></i>Berlaku Semua Destinasi
                            </span>
                        <?php endif; ?>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($v['keterangan']) ?></p>
                        <div>
                            <div class="kode-box" onclick="salinKode('<?= $v['kode'] ?>', this)" title="Klik untuk menyalin">
                                <?= htmlspecialchars($v['kode']) ?>
                            </div>
                            <div class="copy-hint"><i class="bi bi-clipboard me-1"></i>Klik kode untuk menyalin</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif ?> Tourify. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function salinKode(kode, el) {
    navigator.clipboard.writeText(kode).then(() => {
        const semula = el.innerHTML;
        el.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
        el.style.background = '#dcfce7';
        el.style.borderColor = '#16a34a';
        el.style.color = '#16a34a';
        setTimeout(() => {
            el.innerHTML = semula;
            el.style.background = '';
            el.style.borderColor = '';
            el.style.color = '';
        }, 2000);
    });
}
</script>
</body>
</html>