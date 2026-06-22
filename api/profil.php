<?php
// ============================================================
//  PROFIL SAYA — Tourify
//  User bisa lihat data diri, edit nama/email, dan ganti password.
// ============================================================

include __DIR__ . '/session_db.php';
include __DIR__ . '/koneksi.php';

$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;
if (!$nama_tampil || !$is_logged_in) { header("Location: /api/login.php"); exit(); }

$username_login = $_SESSION['username'] ?? $_SESSION['user'] ?? '';
$id_user         = $_SESSION['id_user'] ?? null;

$pesan_sukses = '';
$pesan_error  = '';

// --- Ambil data user terkini dari database ---
$user_escaped = mysqli_real_escape_string($conn, $username_login);
$q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$user_escaped' LIMIT 1");
$data_user = $q && mysqli_num_rows($q) > 0 ? mysqli_fetch_assoc($q) : null;

if (!$data_user) {
    echo "<div style='padding:40px;font-family:sans-serif;'>Data user tidak ditemukan. <a href='/api/logout.php'>Logout</a> dan login ulang.</div>";
    exit();
}

// --- Proses update nama lengkap & email ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_baru  = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
    $email_baru = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));

    if ($nama_baru === '' || $email_baru === '') {
        $pesan_error = 'Nama lengkap dan email tidak boleh kosong.';
    } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = 'Format email tidak valid.';
    } else {
        // Cek email tidak dipakai user lain
        $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_baru' AND username != '$user_escaped' LIMIT 1");
        if ($cek_email && mysqli_num_rows($cek_email) > 0) {
            $pesan_error = 'Email tersebut sudah digunakan akun lain.';
        } else {
            mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_baru', email = '$email_baru' WHERE username = '$user_escaped'");
            $pesan_sukses = 'Profil berhasil diperbarui.';
            // Refresh data & session
            $data_user['nama_lengkap'] = $_POST['nama_lengkap'];
            $data_user['email']        = $_POST['email'];
            $_SESSION['nama_lengkap']  = $_POST['nama_lengkap'];
        }
    }
}

// --- Proses ganti password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pass_lama  = $_POST['password_lama'] ?? '';
    $pass_baru  = $_POST['password_baru'] ?? '';
    $pass_ulang = $_POST['password_ulang'] ?? '';

    if ($pass_lama === '' || $pass_baru === '' || $pass_ulang === '') {
        $pesan_error = 'Semua kolom password wajib diisi.';
    } elseif (!password_verify($pass_lama, $data_user['password'])) {
        $pesan_error = 'Password lama tidak sesuai.';
    } elseif (strlen($pass_baru) < 6) {
        $pesan_error = 'Password baru minimal 6 karakter.';
    } elseif ($pass_baru !== $pass_ulang) {
        $pesan_error = 'Konfirmasi password baru tidak cocok.';
    } else {
        $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hash_baru' WHERE username = '$user_escaped'");
        $pesan_sukses = 'Password berhasil diubah. Gunakan password baru saat login berikutnya.';
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
    <title>Profil Saya | Tourify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary:#f37021; --primary-gradient:linear-gradient(135deg,#f37021,#ff8c42); --text-dark:#1e293b; --text-muted:#64748b; --border:#e2e8f0; --bg:#f8fafc; }
        body { background:var(--bg); font-family:'Inter',sans-serif; color:var(--text-dark); margin:0; overflow-x:hidden; }
        h1,h2,h3,h4,h5,h6,.brand-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; }
        .wrapper { display:flex; width:100%; min-height:100vh; }
        #sidebar { min-width:260px;max-width:260px;background:#fff;border-right:1px solid var(--border); }
        .sidebar-header { padding:30px 25px;border-bottom:1px solid var(--border); }
        .nav-brand-box { display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text-dark); }
        .logo-icon { width:35px;height:35px;background:var(--primary-gradient);color:white;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(243,112,33,0.2); }
        .sidebar-menu { padding:25px 15px;list-style:none;margin:0; }
        .sidebar-menu li { margin-bottom:6px; }
        .sidebar-menu a { display:flex;align-items:center;gap:12px;padding:12px 20px;color:var(--text-muted);text-decoration:none;border-radius:12px;font-weight:500;font-size:0.95rem;transition:all 0.2s; }
        .sidebar-menu a:hover,.sidebar-menu li.active a { background:#fff3eb;color:var(--primary);font-weight:600; }
        #content { flex:1;padding:35px 40px;background:var(--bg); }
        .top-navbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:35px; }
        .user-profile-box { display:flex;align-items:center;gap:12px;background:#fff;padding:8px 20px;border-radius:100px;border:1px solid var(--border); }
        .avatar-circle { width:35px;height:35px;background:rgba(243,112,33,0.1);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700; }
        .btn-logout { background:#fef2f2;color:#ef4444;border:1px solid #fca5a5;padding:10px 20px;border-radius:100px;font-weight:600;text-decoration:none;transition:0.2s; }
        .btn-logout:hover { background:#ef4444;color:white; }
        .info-card { border:1px solid var(--border);background:white;border-radius:24px;padding:30px; }

        .profil-avatar-big { width:84px;height:84px;border-radius:50%;background:var(--primary-gradient);color:white;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif; }
        .form-label-custom { font-size:0.85rem;font-weight:600;color:var(--text-dark);margin-bottom:6px;display:block; }
        .form-control-custom { border:1.5px solid var(--border);border-radius:12px;padding:11px 16px;font-size:0.92rem;width:100%;transition:border 0.2s; }
        .form-control-custom:focus { outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(243,112,33,0.1); }
        .btn-simpan { background:var(--primary-gradient);border:none;color:white;font-weight:700;padding:11px 26px;border-radius:12px;cursor:pointer;transition:0.2s; }
        .btn-simpan:hover { opacity:0.92;transform:translateY(-1px); }
        .alert-custom-success { background:#f0fdf4;color:#16a34a;border-radius:12px;padding:14px 18px;font-size:0.9rem;display:flex;align-items:center;gap:8px; }
        .alert-custom-error { background:#fef2f2;color:#ef4444;border-radius:12px;padding:14px 18px;font-size:0.9rem;display:flex;align-items:center;gap:8px; }
        .section-divider { border-top:1px solid var(--border);margin:32px 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand-box" href="/api/dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
                <span class="brand-title" style="font-size:1.4rem;">Tour<span style="color:var(--primary);">ify</span></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/api/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a></li>
            <li><a href="/api/destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a></li>
            <li><a href="/api/promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a></li>
            <li><a href="/api/dashboard.php?page=bps"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li><a href="/api/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
            <li class="active"><a href="/api/profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a></li>
        </ul>
    </nav>

    <div id="content">
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark">Profil Saya 👤</h4>
                <p class="text-muted small mb-0">Kelola data diri dan keamanan akun Tourify kamu.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile-box">
                    <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                    <div class="small fw-semibold d-none d-sm-block">
                        <?= htmlspecialchars($nama_tampil) ?>
                        <span class="text-muted d-block" style="font-size:0.75rem;">Pengguna</span>
                    </div>
                </div>
                <a href="/api/logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>

        <?php if ($pesan_sukses): ?>
        <div class="alert-custom-success mb-4"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($pesan_sukses) ?></div>
        <?php endif; ?>
        <?php if ($pesan_error): ?>
        <div class="alert-custom-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($pesan_error) ?></div>
        <?php endif; ?>

        <div class="card info-card mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="profil-avatar-big"><?= strtoupper(substr($data_user['nama_lengkap'] ?? $username_login, 0, 1)) ?></div>
                <div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($data_user['nama_lengkap'] ?? '-') ?></h5>
                    <p class="text-muted small mb-0">@<?= htmlspecialchars($data_user['username']) ?></p>
                </div>
            </div>

            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-person-lines-fill me-2 text-muted"></i>Informasi Akun</h6>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-custom">Username</label>
                        <input type="text" class="form-control-custom" value="<?= htmlspecialchars($data_user['username']) ?>" disabled style="background:#f8fafc;color:var(--text-muted);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control-custom" value="<?= htmlspecialchars($data_user['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Email</label>
                        <input type="email" name="email" class="form-control-custom" value="<?= htmlspecialchars($data_user['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="update_profil" class="btn-simpan">
                        <i class="bi bi-save2 me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>

            <div class="section-divider"></div>

            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-shield-lock-fill me-2 text-muted"></i>Ganti Password</h6>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control-custom" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control-custom" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Ulangi Password Baru</label>
                        <input type="password" name="password_ulang" class="form-control-custom" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="ganti_password" class="btn-simpan">
                        <i class="bi bi-key-fill me-1"></i> Ubah Password
                    </button>
                </div>
            </form>
        </div>

        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif ?> Tourify. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</div>
</body>
</html>