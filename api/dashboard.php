<?php
// URUTAN WAJIB: ini_set -> session_start -> cek login -> koneksi DB
include __DIR__ . '/session_db.php';

// Cek login - redirect jika belum login
if (empty($_SESSION['login_user'])) {
    header("Location: /api/login.php");
    exit;
}

// Ambil data session user
$nama_tampil    = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] 
                : (!empty($_SESSION['username']) ? $_SESSION['username'] : 'Pengguna');
$username_login = $_SESSION['username'] ?? $_SESSION['user'] ?? '';
$is_logged_in   = true;

// Koneksi database TiDB
$host     = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$port     = 4000;
$db_user  = "3DA4d4bPMVCSuDy.root";
$db_pass  = "mRSgOTH6qk79AieJ";
$database = "tourify-db";
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, $host, $db_user, $db_pass, $database, $port, NULL, MYSQLI_CLIENT_SSL);
mysqli_set_charset($conn, "utf8mb4");

// Cek halaman aktif di konten utama via parameter URL
$page = $_GET['page'] ?? 'ringkasan';

// Setel angka default ke 0
$total_destinasi = 0; 
$total_voucher = 0;   
$total_pesanan = 0;   
$total_pengeluaran = 0;

// Data grafik per bulan (12 bulan, default 0 semua)
$data_grafik = array_fill(0, 12, 0);

// Pesanan terbaru untuk tabel aktivitas
$pesanan_terbaru = [];

// Sinkronisasi data asli dari MySQL jika database sudah siap
if (isset($conn)) {
    try {
        $q_dest = mysqli_query($conn, "SELECT COUNT(*) as total FROM destinasi");
        if ($q_dest) { 
            $row_dest = mysqli_fetch_assoc($q_dest); 
            $total_destinasi = $row_dest['total'] ?? 0;
        }
        
        $user_escaped = mysqli_real_escape_string($conn, $username_login);

        // FIX: kolom yang benar adalah total_bayar (bukan harga)
        $q_order = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_bayar) as total_pengeluaran FROM pesanan WHERE username = '$user_escaped'");
        if ($q_order) { 
            $row_order = mysqli_fetch_assoc($q_order); 
            $total_pesanan     = $row_order['total'] ?? 0;
            $total_pengeluaran = $row_order['total_pengeluaran'] ?? 0;
        }
        
        $q_vouch = mysqli_query($conn, "SELECT COUNT(*) as total FROM voucher WHERE aktif = 1");
        if ($q_vouch) {
            $row_vouch = mysqli_fetch_assoc($q_vouch);
            $total_voucher = $row_vouch['total'] ?? 0;
        }

        // Query grafik: jumlah tiket per bulan di tahun ini
        // Dipakai kolom `tanggal` (tanggal KUNJUNGAN), bukan created_at (tanggal pemesanan),
        // karena grafik ini menunjukkan tren liburan/kunjungan, bukan kapan order dibuat.
        $q_grafik = mysqli_query($conn, "
            SELECT MONTH(tanggal) as bulan, COUNT(*) as jumlah 
            FROM pesanan 
            WHERE username = '$user_escaped' AND YEAR(tanggal) = YEAR(NOW())
            GROUP BY MONTH(tanggal)
        ");
        if ($q_grafik) {
            while ($row_g = mysqli_fetch_assoc($q_grafik)) {
                $idx = (int)$row_g['bulan'] - 1; // bulan 1=Jan → index 0
                $data_grafik[$idx] = (int)$row_g['jumlah'];
            }
        }

        // Query 5 pesanan terbaru untuk tabel aktivitas
        $q_terbaru = mysqli_query($conn, "
            SELECT * FROM pesanan 
            WHERE username = '$user_escaped' 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        if ($q_terbaru) {
            while ($row_t = mysqli_fetch_assoc($q_terbaru)) {
                $pesanan_terbaru[] = $row_t;
            }
        }

    } catch (Exception $e) {
        // Mencegah crash jika tabel belum lengkap
    }
}

date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');

// --- Ambil data wilayah ASLI dari API BPS (hanya untuk halaman Statistik BPS) ---
$bps_error      = null;
$bps_data_list  = [];
$bps_labels     = [];
$bps_values     = [];

if ($page === 'bps') {
    $bps_api_key = "6df4ab3763735db26e99969daaf5c719";
    $bps_url     = "https://webapi.bps.go.id/v1/api/domain/type/all/prov/0000/key/6df4ab3763735db26e99969daaf5c719/";

    $bps_response = @file_get_contents($bps_url);

    if ($bps_response === FALSE) {
        $bps_error = "Gagal mengambil data dari API BPS. Periksa koneksi atau API Key Anda.";
    } else {
        $bps_result = json_decode($bps_response, true);
        if (isset($bps_result['data'][1])) {
            $bps_data_list = $bps_result['data'][1];
        } else {
            $bps_error = "Data wilayah tidak ditemukan dari API BPS.";
        }
    }

    // Siapkan sample untuk grafik distribusi (5 wilayah pertama)
    foreach (array_slice($bps_data_list, 0, 5) as $row) {
        $bps_labels[] = substr($row['domain_name'], 0, 15) . '...';
        $bps_values[] = rand(20, 100); // Data sample, BPS tidak menyediakan angka statistik di endpoint domain
    }

    // --- Statistik per destinasi (gabungan dari tiap halaman destinasi) ---
    $destinasi_bps_daftar = [
        ['nama' => 'Candi Borobudur',              'kode' => '3308', 'kabupaten' => 'Kabupaten Magelang'],
        ['nama' => 'Heritage',                      'kode' => '3311', 'kabupaten' => 'Kabupaten Sukoharjo'],
        ['nama' => 'Safari',                         'kode' => '3372', 'kabupaten' => 'Kota Surakarta'],
        ['nama' => 'Saloka Theme Park',              'kode' => '3374', 'kabupaten' => 'Kota Semarang'],
        ['nama' => 'Taman Nasional Karimunjawa',     'kode' => '3320', 'kabupaten' => 'Kabupaten Jepara'],
    ];
    $destinasi_bps_stats = [];
    foreach ($destinasi_bps_daftar as $dest) {
        $kode_kabupaten_bps     = $dest['kode'];
        $nama_kabupaten_bps     = $dest['kabupaten'];
        $STATISTIK_WILAYAH_MODE = 'logic';
        include __DIR__ . '/statistik_wilayah_partial.php';

        $destinasi_bps_stats[] = [
            'nama_destinasi' => $dest['nama'],
            'kabupaten'      => $nama_kabupaten_bps,
            'kecamatan'      => count($statwil_kecamatan),
            'akomodasi'      => $statwil_akomodasi,
            'penduduk'       => $statwil_penduduk,
            'is_estimasi'    => $statwil_is_estimasi,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panel | Tourify</title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        /* Sidebar Sesuai image_7657e9.png */
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
        
        .welcome-banner { 
            background: var(--primary-gradient); 
            border-radius: 24px; 
            padding: 32px; 
            color: white; 
            margin-bottom: 35px; 
            box-shadow: 0 12px 30px rgba(243,112,33,0.15); 
        }
        
        .stat-card { 
            border: 1px solid var(--border-color); 
            background: white; 
            border-radius: 20px; 
            padding: 20px; 
        }
        
        .info-card { 
            border: 1px solid var(--border-color); 
            background: white; 
            border-radius: 24px; 
            padding: 25px; 
            height: 100%; 
        }
        
        .weather-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 10px;
            border: 1px solid var(--border-color);
        }
        .weather-badge { 
            background: #fff; padding: 6px 12px; border-radius: 8px; 
            font-size: 0.85rem; font-weight: 600; border: 1px solid var(--border-color);
        }
        
        .empty-state-box { padding: 40px 20px; text-align: center; }
        .empty-state-icon { font-size: 3rem; color: var(--text-muted); opacity: 0.4; margin-bottom: 15px; }
        .tip-box { background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; border-radius: 0 12px 12px 0; height: 100%; }
        
        /* Gaya Khusus untuk Halaman Tabel BPS */
        .table-bps th { background-color: #f1f5f9; color: var(--text-dark); font-weight: 600; }
        .badge-trend { padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Kiri Dinamis -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand-box" href="/api/dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
                <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="<?= $page === 'ringkasan' ? 'active' : '' ?>"><a href="/api/dashboard.php?page=ringkasan"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a></li>
            <li><a href="/api/destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a></li>
            <li><a href="/api/promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a></li>
            <li class="<?= $page === 'bps' ? 'active' : '' ?>"><a href="/api/dashboard.php?page=bps"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li><a href="/api/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
            <li><a href="/api/ulasan.php"><i class="bi bi-star-fill"></i> Ulasan</a></li>
            <li><a href="/api/profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a></li>
        </ul>
    </nav>

    <!-- Konten Utama Kanan -->
    <div id="content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark"><?= $page === 'bps' ? 'Statistik & Analitik Wisata BPS' : 'Ringkasan Info & Insight Perjalanan 📊' ?></h4>
                <p class="text-muted small mb-0"><?= $page === 'bps' ? 'Portal terintegrasi data makro Badan Pusat Statistik.' : 'Analisis pengeluaran dan status persiapan liburan Anda.' ?></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile-box">
                    <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                    <div class="small fw-semibold d-none d-sm-block">
                        <?= htmlspecialchars($nama_tampil); ?>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Status: Pengguna</span>
                    </div>
                </div>
                <a href="/api/logout.php" class="btn btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>

        <?php if ($page === 'bps'): ?>
            <!-- ================= HALAMAN 1: KONTEN STATISTIK BPS (DATA ASLI API BPS) ================= -->
            <div class="welcome-banner" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-1.5 rounded-pill mb-2 small fw-bold" style="font-size:0.75rem;">Data Resmi Badan Pusat Statistik</span>
                <h2 class="fw-bold mb-1">Pusat Data Statistik Wilayah Indonesia</h2>
                <p class="mb-0 text-white-50 small">Terhubung langsung ke Web API BPS (webapi.bps.go.id) — menampilkan daftar wilayah resmi secara real-time.</p>
            </div>

            <?php if ($bps_error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($bps_error) ?></div>
            <?php else: ?>
            <div class="row g-4 mb-4">
                <!-- Tabel Wilayah ASLI dari API BPS -->
                <div class="col-lg-7">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Daftar Wilayah</h5>
                        <p class="text-muted small mb-3">Data domain/wilayah resmi diambil langsung dari API BPS.</p>
                        <div class="table-responsive" style="max-height:340px; overflow-y:auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f8fafc;">
                                    <tr>
                                        <th class="small fw-semibold text-muted border-0">ID</th>
                                        <th class="small fw-semibold text-muted border-0">Nama Wilayah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($bps_data_list)): ?>
                                        <?php foreach ($bps_data_list as $row): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($row['domain_id']) ?></code></td>
                                            <td class="small"><?= htmlspecialchars($row['domain_name']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted py-4 small">Tidak ada data wilayah</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Grafik Distribusi Sample -->
                <div class="col-lg-5">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-3 text-center">Distribusi Data Sample</h5>
                        <div style="position: relative; height:260px; width:100%">
                            <canvas id="chartBpsWisata"></canvas>
                        </div>
                        <p class="text-muted small mt-3 mb-0 text-center">Ditampilkan dari 5 wilayah pertama hasil API BPS.</p>
                    </div>
                </div>
            </div>

            <!-- Statistik per Destinasi (gabungan) -->
            <div class="card info-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-geo-alt-fill me-2" style="color:var(--accent);"></i>Statistik Wilayah per Destinasi</h5>
                    <span class="small text-muted">Sumber: Badan Pusat Statistik (BPS)</span>
                </div>
                <div class="row g-3">
                    <?php foreach ($destinasi_bps_stats as $stat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 rounded-3 h-100" style="background:#f8fafc;">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($stat['nama_destinasi']) ?></div>
                            <div class="text-muted small mb-2"><?= htmlspecialchars($stat['kabupaten']) ?></div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Jumlah Kecamatan</span>
                                <span class="fw-semibold"><?= $stat['kecamatan'] ?: '-' ?></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Akomodasi / Hotel</span>
                                <span class="fw-semibold"><?= number_format((int)$stat['akomodasi'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Jumlah Penduduk</span>
                                <span class="fw-semibold"><?= number_format((int)$stat['penduduk'], 0, ',', '.') ?></span>
                            </div>
                            <?php if ($stat['is_estimasi']): ?>
                            <div class="small mt-2" style="color:#d97706;"><i class="bi bi-info-circle me-1"></i>Akomodasi &amp; penduduk masih estimasi</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ================= HALAMAN 2: HALAMAN UTAMA (RINGKASAN ASLI) ================= -->
            <div class="welcome-banner">
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-1.5 rounded-pill mb-2 small fw-bold" style="font-size:0.75rem;">Sistem Sinkronisasi Terpadu</span>
                <h2 class="fw-bold mb-1">Halo, <?= htmlspecialchars($nama_tampil); ?>!</h2>
                <p class="mb-0 text-white-50 small">Berikut adalah statistik pengeluaran dan pemantauan kondisi cuaca daerah destinasi hari ini.</p>
            </div>

            <!-- Tiga Kotak Angka Ringkasan -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card d-flex flex-row align-items-center gap-3">
                        <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary"><i class="bi bi-wallet2 fs-4"></i></div>
                        <div>
                            <h6 class="text-muted small mb-0 fw-medium">Total Dana Keluar</h6>
                            <h4 class="fw-bold mb-0 text-dark">Rp <?= number_format($total_pengeluaran, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card d-flex flex-row align-items-center gap-3">
                        <div class="p-3 rounded-4 bg-success bg-opacity-10 text-success"><i class="bi bi-ticket-detailed fs-4"></i></div>
                        <div>
                            <h6 class="text-muted small mb-0 fw-medium">Kupon Tersedia</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?= $total_voucher; ?> Promo Aktif</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card d-flex flex-row align-items-center gap-3">
                        <div class="p-3 rounded-4 bg-warning bg-opacity-10 text-warning"><i class="bi bi-cart-check fs-4"></i></div>
                        <div>
                            <h6 class="text-muted small mb-0 fw-medium">Pesanan Saya</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?= $total_pesanan; ?> Transaksi</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row Tengah: Grafik -->
            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Grafik Tren Liburan</h5>
                        <p class="text-muted small mb-4">Estimasi perbandingan intensitas kunjungan wisata Anda per bulan (<?= $tahun_aktif ?>).</p>
                        <div style="position: relative; height:220px; width:100%">
                            <canvas id="chartPengeluaran"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Transaksi Dinamis dari Database -->
            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Aktivitas Pembelian Tiket Terbaru</h5>
                        <p class="text-muted small mb-3">Daftar transaksi e-tiket terakhir yang terdaftar atas nama akun Anda.</p>
                        
                        <?php if (empty($pesanan_terbaru)): ?>
                        <div class="empty-state-box">
                            <div class="empty-state-icon"><i class="bi bi-basket3"></i></div>
                            <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Pesanan</h6>
                            <p class="text-muted small mb-0">Anda belum melakukan pemesanan tiket perjalanan. Semua riwayat transaksi Anda akan muncul di sini setelah pemesanan berhasil.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:0.9rem;">
                                <thead>
                                    <tr style="background:#f8fafc; color:#64748b; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.5px;">
                                        <th class="py-3 px-3">Kode</th>
                                        <th class="py-3 px-3">Destinasi</th>
                                        <th class="py-3 px-3">Tanggal</th>
                                        <th class="py-3 px-3">Jumlah</th>
                                        <th class="py-3 px-3">Total Bayar</th>
                                        <th class="py-3 px-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesanan_terbaru as $p): ?>
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td class="px-3 fw-bold text-primary">#TRF-<?= $p['id'] ?></td>
                                        <td class="px-3 fw-semibold"><?= htmlspecialchars($p['wisata']) ?></td>
                                        <td class="px-3 text-muted"><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                                        <td class="px-3"><?= $p['jumlah'] ?> tiket</td>
                                        <td class="px-3 fw-bold text-success">Rp <?= number_format($p['total_bayar'], 0, ',', '.') ?></td>
                                        <td class="px-3 text-center">
                                            <span class="badge rounded-pill" style="background:#dcfce7; color:#16a34a; padding:6px 12px; font-size:0.78rem;">
                                                <i class="bi bi-check-circle-fill me-1"></i> E-Tiket Aktif
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="/api/riwayat_pesanan.php" class="small fw-semibold text-decoration-none" style="color:var(--primary);">
                                Lihat Semua Riwayat <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panduan & Tips Liburan -->
            <div class="card info-card">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Panduan & Tips Liburan Cerdas</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="tip-box">
                            <h6 class="fw-bold text-warning-emphasis mb-1">Manfaatkan Kupon Di Hari Kerja (Weekday)</h6>
                            <p class="text-secondary small mb-0">Tiket destinasi cenderung jauh lebih murah dan tidak padat pengunjung pada hari Senin-Jumat. Gunakan voucher-mu demi hemat maksimal!</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tip-box" style="background: #ecfdf5; border-left-color: #10b981;">
                            <h6 class="fw-bold text-success-emphasis mb-1">E-Tiket Paperless Aman & Praktis</h6>
                            <p class="text-secondary small mb-0">Kamu tidak perlu mencetak tiket. Cukup tunjukkan kode pesanan di halaman Riwayat Pesanan langsung melalui smartphone di gerbang masuk.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif; ?> Tourify. Hak Cipta Dilindungi Panel Pengguna.</p>
        </div>
    </div>
</div>

<script>
<?php if ($page === 'bps'): ?>
    // Script grafik BPS - data ASLI dari API BPS (sample 5 wilayah pertama)
    const ctxBps = document.getElementById('chartBpsWisata').getContext('2d');
    new Chart(ctxBps, {
        type: 'pie',
        data: {
            labels: <?= json_encode($bps_labels); ?>,
            datasets: [{
                data: <?= json_encode($bps_values); ?>,
                backgroundColor: ['#0d6efd', '#0dcaf0', '#ffc107', '#fd7e14', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
<?php else: ?>
    // Script grafik utama - data dari database
    const ctxMain = document.getElementById('chartPengeluaran').getContext('2d');
    new Chart(ctxMain, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Pembelian Tiket',
                data: <?= json_encode(array_values($data_grafik)) ?>,
                backgroundColor: '#f37021',
                borderRadius: 6
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 }
                }
            }
        }
    });
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>