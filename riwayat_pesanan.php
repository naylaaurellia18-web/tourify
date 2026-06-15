<?php
// 1. Pelacak error agar mudah debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Hubungkan koneksi database Tourify / Tourify
if (file_exists('api/koneksi.php')) {
    include 'api/koneksi.php';
} elseif (file_exists('koneksi.php')) {
    include 'koneksi.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil nama user yang sedang aktif
$nama_tampil = $_SESSION['user'] ?? $_SESSION['username'] ?? 'Pengguna';

// Siapkan array kosong untuk menampung riwayat transaksi asli dari database
$riwayat_pesanan = [];

// Tarik data asli dari MySQL jika variabel $conn aktif
if (isset($conn)) {
    try {
        $user_escaped = mysqli_real_escape_string($conn, $nama_tampil);
        // Mengambil pesanan berdasarkan username yang login
        $query_order = mysqli_query($conn, "SELECT * FROM pesanan WHERE username = '$user_escaped' ORDER BY id DESC");
        if ($query_order) {
            while ($row = mysqli_fetch_assoc($query_order)) {
                $riwayat_pesanan[] = $row;
            }
        }
    } catch (Exception $e) {
        // Abaikan jika tabel belum siap/migrasi belum lengkap
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
    <title>Riwayat Pesanan Saya | Tourify</title>
    
    <!-- Google Fonts & Bootstrap Icons -->
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
        
        h1, h2, h3, h4, h5, h6, .brand-title { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            font-weight: 700; 
        }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        
        /* Sidebar Navigation */
        #sidebar { 
            min-width: 260px; 
            max-width: 260px; 
            background: var(--bg-sidebar); 
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar-header { 
            padding: 30px 25px; 
            border-bottom: 1px solid var(--border-color); 
        }
        
        .nav-brand-box { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            text-decoration: none; 
            color: var(--text-dark); 
        }
        
        .logo-icon { 
            width: 35px; 
            height: 35px; 
            background: var(--primary-gradient); 
            color: white; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 12px rgba(243, 112, 33, 0.2); 
        }
        
        .sidebar-menu { padding: 25px 15px; list-style: none; margin: 0; }
        .sidebar-menu li { margin-bottom: 6px; }
        
        .sidebar-menu a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 20px; 
            color: var(--text-muted); 
            text-decoration: none; 
            border-radius: 12px; 
            font-weight: 500; 
            font-size: 0.95rem; 
            transition: all 0.2s; 
        }
        
        .sidebar-menu a:hover, .sidebar-menu li.active a { 
            background: #fff3eb; 
            color: var(--primary); 
            font-weight: 600;
        }
        
        /* Main Content Container */
        #content { flex: 1; padding: 35px 40px; background-color: var(--bg-main); }
        
        .top-navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 35px; 
        }
        
        .user-profile-box { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            background: #ffffff; 
            padding: 8px 20px; 
            border-radius: 100px; 
            border: 1px solid var(--border-color); 
        }
        
        .avatar-circle { 
            width: 35px; 
            height: 35px; 
            background: rgba(243, 112, 33, 0.1); 
            color: var(--primary); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700; 
        }
        
        .btn-logout { 
            background: #fef2f2; 
            color: #ef4444; 
            border: 1px solid #fca5a5; 
            padding: 10px 20px; 
            border-radius: 100px; 
            font-weight: 600; 
            text-decoration: none; 
            transition: 0.2s; 
        }
        .btn-logout:hover { background: #ef4444; color: white; }
        
        .info-card { 
            border: 1px solid var(--border-color); 
            background: white; 
            border-radius: 24px; 
            padding: 30px; 
        }
        
        /* Styling Table Area */
        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .table-custom td {
            padding: 18px 16px;
            color: var(--text-dark);
            font-size: 0.92rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Empty State Layout */
        .empty-state-box {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state-icon {
            font-size: 3.5rem;
            color: var(--text-muted);
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Kiri (Serasi dengan Dashboard) -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand-box" href="dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
                <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a></li>
            <li><a href="destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a></li>
            <li><a href="promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a></li>
            <li><a href="dashboard.php?page=bps_stat"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li class="active"><a href="riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
        </ul>
    </nav>

    <!-- Konten Sebelah Kanan -->
    <div id="content">
        <!-- Top Navbar Menu -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark">Riwayat Transaksi Pemesanan 🧾</h4>
                <p class="text-muted small mb-0">Pantau seluruh e-tiket aktif dan invoice riwayat liburanmu.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile-box">
                    <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                    <div class="small fw-semibold d-none d-sm-block">
                        <?= htmlspecialchars($nama_tampil); ?>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Status: Pengguna</span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>

        <!-- Wadah Utama Daftar Riwayat -->
        <div class="card info-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Semua Pembelian Tiket Anda</h5>
                    <p class="text-muted small mb-0">Menampilkan data manifest tiket yang sah dari sistem database.</p>
                </div>
                <span class="badge bg-light text-dark border p-2 rounded-3 small text-secondary fw-semibold">
                    <i class="bi bi-filter-left me-1"></i> Terbuka Otomatis
                </span>
            </div>

            <?php if (empty($riwayat_pesanan)): ?>
                <!-- JIKA DATA KOSONG (Kondisi Awal) -->
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-basket3"></i></div>
                    <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Pesanan</h6>
                    <p class="text-muted small mb-0 mx-auto" style="max-width: 500px;">
                        Anda belum melakukan pemesanan tiket perjalanan objek wisata apapun. Semua riwayat transaksi Anda akan otomatis tercatat dan muncul di tabel ini setelah pemesanan dikonfirmasi.
                    </p>
                    <a href="destinasi.php" class="btn btn-primary mt-4 px-4 py-2 rounded-pill fw-semibold shadow-sm" style="background: var(--primary-gradient); border:none;">
                        <i class="bi bi-search me-1"></i> Cari Tiket Sekarang
                    </a>
                </div>
            <?php else: ?>
                <!-- JIKA DATA SUDAH TERISI DI DATABASE -->
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Order / Kode</th>
                                <th>Nama Destinasi Wisata</th>
                                <th>Tanggal Booking</th>
                                <th>Total Bayar</th>
                                <th class="text-center">Status Validitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($riwayat_pesanan as $pesanan): ?>
                                <tr>
                                    <td class="text-muted"><?= $no++; ?></td>
                                    <td class="fw-bold text-primary">#TRF-<?= $pesanan['id'] ?? '000'; ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($pesanan['nama_destinasi'] ?? 'Wisata Pilihan'); ?></td>
                                    <td><?= htmlspecialchars($pesanan['tanggal'] ?? date('d M Y')); ?></td>
                                    <td class="fw-bold text-success">Rp <?= number_format($pesanan['harga'] ?? 0, 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-check-circle-fill me-1"></i> E-Tiket Aktif
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Hak Cipta -->
        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif; ?> Tourify - Tourify. Hak Cipta Dilindungi Panel Pengguna.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>