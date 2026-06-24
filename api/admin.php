<?php
include __DIR__ . '/session_db.php';
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3DA4d4bPMVCSuDy.root", "mRSgOTH6qk79AieJ", "tourify-db", 4000, NULL, MYSQLI_CLIENT_SSL);
mysqli_set_charset($conn, "utf8mb4");

// --- Wajib login sebagai admin ---
if (empty($_SESSION['admin_id'])) {
    header("Location: /api/login.php");
    exit();
}

$admin_role      = $_SESSION['admin_role'];        // 'super' atau 'destinasi'
$admin_destinasi = $_SESSION['admin_destinasi'];   // id_destinasi (NULL untuk super admin)
$admin_nama      = $_SESSION['admin_nama'];

// --- Pengaman: admin role 'destinasi' WAJIB punya id_destinasi yang valid ---
// Kalau tidak ada (NULL/0), berarti data admin di database salah/rusak.
// Tampilkan pesan jelas di sini, JANGAN redirect, supaya tidak terjadi infinite loop.
if ($admin_role === 'destinasi' && empty($admin_destinasi)) {
    echo "<div style='font-family:sans-serif;padding:40px;max-width:600px;margin:60px auto;background:#fef2f2;border-radius:16px;'>
        <h4 style='color:#ef4444;'>Data Admin Tidak Valid</h4>
        <p>Akun admin <strong>" . htmlspecialchars($admin_nama) . "</strong> berperan sebagai 'destinasi' tapi tidak memiliki id_destinasi yang terhubung di database.</p>
        <p>Perbaiki dengan menjalankan query berikut di phpMyAdmin (sesuaikan id_destinasi dan username):</p>
        <code>UPDATE admin SET id_destinasi = 1 WHERE username = '" . htmlspecialchars($_SESSION['admin_username']) . "';</code>
        <p style='margin-top:16px;'><a href='/api/admin_logout.php'>Logout</a> dan coba login ulang setelah diperbaiki.</p>
    </div>";
    exit();
}

// Default page beda untuk masing-masing role:
// - super admin: dashboard global
// - admin destinasi: langsung ke detail destinasi miliknya
$default_page = ($admin_role === 'destinasi') ? 'destinasi_detail' : 'dashboard';
$page = $_GET['page'] ?? $default_page;

// --- Batasan akses untuk admin per-destinasi ---
// Admin destinasi TIDAK BOLEH akses 'dashboard' (global), 'destinasi' (kelola semua),
// atau 'promo' (global). Mereka hanya boleh lihat detail destinasi MILIK SENDIRI.
if ($admin_role === 'destinasi') {
    // Helper: cek apakah request ini sudah pernah diredirect sebelumnya (anti infinite loop)
    $sudah_redirect = isset($_GET['_r']);

    if (in_array($page, ['dashboard', 'destinasi', 'promo']) && !$sudah_redirect) {
        // Paksa langsung ke detail destinasi miliknya sendiri
        header("Location: admin.php?page=destinasi_detail&id=" . (int)$admin_destinasi . "&_r=1");
        exit();
    }
    if ($page === 'destinasi_detail') {
        $id_diminta = isset($_GET['id']) ? (int)$_GET['id'] : (int)$admin_destinasi;
        if ($id_diminta !== (int)$admin_destinasi && !$sudah_redirect) {
            // Mencoba akses destinasi orang lain -> tolak, paksa balik ke miliknya sendiri
            header("Location: admin.php?page=destinasi_detail&id=" . (int)$admin_destinasi . "&_r=1");
            exit();
        }
        // Pastikan $_GET['id'] terisi dengan id milik admin ini (untuk dipakai oleh admin_destinasi_detail.php)
        $_GET['id'] = (int)$admin_destinasi;
    }
}

// Mapping nama page di URL -> file fisik
$page_file_map = [
    'dashboard'        => 'admin_dashboard.php',
    'destinasi'        => 'admin_destinasi.php',
    'destinasi_detail' => 'admin_destinasi_detail.php',
    'promo'            => 'admin_promo.php',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { width: 250px; background: #0f172a; height: 100vh; position: fixed; color: white; padding: 20px; overflow-y:auto; }
        .main { margin-left: 250px; padding: 25px; }
        .nav-link { color: white; opacity: 0.8; padding: 10px; display: block; text-decoration: none; }
        .nav-link.active { background: #1e293b; border-radius: 5px; opacity: 1; font-weight: bold; }
        .admin-badge { background:rgba(243,112,33,0.15); color:#ff8c42; border-radius:10px; padding:10px 12px; font-size:0.78rem; margin-bottom:16px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>Tourify Admin</h4>
        <div class="admin-badge">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($admin_nama) ?><br>
            <span class="opacity-75"><?= $admin_role === 'super' ? 'Super Admin' : 'Admin Destinasi' ?></span>
        </div>
        <hr>

        <?php if ($admin_role === 'super'): ?>
            <!-- Menu lengkap untuk super admin -->
            <a href="?page=dashboard" class="nav-link <?= $page=='dashboard'?'active':'' ?>">Dashboard</a>
            <a href="?page=destinasi" class="nav-link <?= in_array($page,['destinasi','destinasi_detail'])?'active':'' ?>">Kelola Destinasi</a>
            <a href="?page=promo" class="nav-link <?= $page=='promo'?'active':'' ?>">Manajemen Promo</a>
        <?php else: ?>
            <!-- Menu terbatas untuk admin per-destinasi: hanya destinasi miliknya -->
            <a href="?page=destinasi_detail&id=<?= $admin_destinasi ?>" class="nav-link <?= $page=='destinasi_detail'?'active':'' ?>">Destinasi Saya</a>
        <?php endif; ?>

        <hr><a href="/api/admin_logout.php" class="nav-link text-danger">Logout</a>
    </div>
    <div class="main">
        <?php 
        if (isset($page_file_map[$page])) {
            $file = $page_file_map[$page];
            if (file_exists(__DIR__.'/'.$file)) include __DIR__.'/'.$file;
            else echo "<div class='alert alert-warning'>File <code>$file</code> tidak ditemukan.</div>";
        } else {
            echo "<h3>Halaman tidak ditemukan</h3>";
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>