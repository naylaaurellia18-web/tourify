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

// --- Ambil cuaca real-time dari Open-Meteo (gratis, tanpa API key) ---
// Hanya dijalankan untuk halaman ringkasan (bukan halaman BPS) supaya tidak fetch sia-sia.
// Pakai curl_multi supaya ke-4 lokasi di-fetch PARALEL (bukan satu-satu),
// penting di Vercel serverless yang punya batas waktu eksekusi ketat.
function ambilCuacaBanyakLokasi($daftarLokasi) {
    $mh = curl_multi_init();
    $handles = [];

    foreach ($daftarLokasi as $i => $lok) {
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lok['lat']}&longitude={$lok['lon']}&current=temperature_2m,weather_code&timezone=Asia%2FJakarta";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $hasil = [];
    foreach ($handles as $i => $ch) {
        $resp = curl_multi_getcontent($ch);
        $data = $resp ? json_decode($resp, true) : null;
        if ($data && isset($data['current'])) {
            $hasil[$i] = [
                'suhu' => round($data['current']['temperature_2m']),
                'kode' => (int)$data['current']['weather_code'],
            ];
        } else {
            $hasil[$i] = null;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    return $hasil;
}

// Mapping WMO weather code (standar Open-Meteo) -> label & ikon Bootstrap Icons
function labelCuaca($kode) {
    if ($kode === 0)                 return ['Cerah', 'bi-sun-fill', 'text-warning'];
    if (in_array($kode, [1,2]))      return ['Cerah Berawan', 'bi-cloud-sun-fill', 'text-warning'];
    if ($kode === 3)                 return ['Berawan', 'bi-cloud-fill', 'text-secondary'];
    if (in_array($kode, [45,48]))    return ['Berkabut', 'bi-cloud-haze-fill', 'text-secondary'];
    if (in_array($kode, [51,53,55])) return ['Gerimis', 'bi-cloud-drizzle-fill', 'text-primary'];
    if (in_array($kode, [61,63,65])) return ['Hujan', 'bi-cloud-rain-fill', 'text-primary'];
    if (in_array($kode, [80,81,82])) return ['Hujan Deras', 'bi-cloud-rain-heavy-fill', 'text-primary'];
    if (in_array($kode, [95,96,99])) return ['Badai Petir', 'bi-cloud-lightning-rain-fill', 'text-danger'];
    return ['Berawan', 'bi-cloud-fill', 'text-secondary'];
}

$lokasi_cuaca = [
    ['nama' => 'Saloka Theme Park (Semarang)',         'lat' => -7.0051, 'lon' => 110.3650],
    ['nama' => 'Candi Borobudur (Magelang)',            'lat' => -7.6079, 'lon' => 110.2038],
    ['nama' => 'Karimunjawa (Jepara)',                  'lat' => -5.8167, 'lon' => 110.4667],
    ['nama' => 'Solo Safari & Heritage (Surakarta)',    'lat' => -7.5755, 'lon' => 110.8243],
];

$data_cuaca = [];
if ($page !== 'bps') {
    $hasil_cuaca = function_exists('curl_multi_init')
        ? ambilCuacaBanyakLokasi($lokasi_cuaca)
        : array_fill(0, count($lokasi_cuaca), null); // fallback kalau ekstensi curl tidak ada

    foreach ($lokasi_cuaca as $i => $lok) {
        $hasil = $hasil_cuaca[$i] ?? null;
        if ($hasil !== null) {
            [$label, $icon, $warna] = labelCuaca($hasil['kode']);
            $data_cuaca[] = [
                'nama'  => $lok['nama'],
                'suhu'  => $hasil['suhu'],
                'label' => $label,
                'icon'  => $icon,
                'warna' => $warna,
            ];
        } else {
            // Fallback kalau Open-Meteo gagal diakses (timeout/down)
            $data_cuaca[] = [
                'nama'  => $lok['nama'],
                'suhu'  => null,
                'label' => 'Data tidak tersedia',
                'icon'  => 'bi-exclamation-circle',
                'warna' => 'text-muted',
            ];
        }
    }
}

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
            <!-- ================= HALAMAN 1: KONTEN STATISTIK BPS ================= -->
            <div class="welcome-banner" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-1.5 rounded-pill mb-2 small fw-bold" style="font-size:0.75rem;">Data Referensi Akademik</span>
                <h2 class="fw-bold mb-1">Pusat Data Statistik Pariwisata Nasional</h2>
                <p class="mb-0 text-white-50 small">Integrasi metrik perkembangan kunjungan wisata nusantara, mancanegara, serta tingkat okupansi akomodasi Indonesia.</p>
            </div>

            <div class="row g-4 mb-4">
                <!-- Grafik Kompleks BPS -->
                <div class="col-lg-7">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Grafik Tren Kunjungan Wisatawan</h5>
                        <p class="text-muted small mb-4">Estimasi volume mobilitas bulanan skala nasional.</p>
                        <div style="position: relative; height:260px; width:100%">
                            <canvas id="chartBpsWisata"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Rasio Kartu -->
                <div class="col-lg-5">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-3">Indikator Kunci Utama</h5>
                        
                        <div class="p-3 border rounded-3 mb-3 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Tingkat Penghunian Kamar (TPK)</span>
                                <span class="badge-trend bg-success text-white"><i class="bi bi-arrow-up"></i> +2.4%</span>
                            </div>
                            <h3 class="fw-bold text-primary mt-1 mb-0">51.34%</h3>
                        </div>

                        <div class="p-3 border rounded-3 mb-3 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Rata-rata Lama Menginap</span>
                                <span class="badge-trend bg-secondary text-white">Stabil</span>
                            </div>
                            <h3 class="fw-bold text-dark mt-1 mb-0">1.62 Hari</h3>
                        </div>

                        <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Target Perjalanan Wisnus</span>
                                <span class="badge-trend bg-success text-white">Tercapai</span>
                            </div>
                            <h3 class="fw-bold text-success mt-1 mb-0">849.3 Juta</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Rinci BPS -->
            <div class="card info-card mb-4">
                <h5 class="fw-bold text-dark mb-1">Tabel Perkembangan Pariwisata Berdasarkan Sektor</h5>
                <p class="text-muted small mb-3">Rincian komparasi data akomodasi perhotelan skala besar dan menengah.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle table-bps mb-0">
                        <thead>
                            <tr>
                                <th>Kategori Objek Data</th>
                                <th class="text-center">Tahun Keluar</th>
                                <th class="text-center">Nilai Capaian</th>
                                <th class="text-center">Status Skala</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Hotel Klasifikasi Bintang (Nasional)</td>
                                <td class="text-center"><?= $tahun_aktif ?></td>
                                <td class="text-center fw-semibold text-primary">51.34% TPK</td>
                                <td class="text-center"><span class="badge bg-primary">Sektor Utama</span></td>
                            </tr>
                            <tr>
                                <td>Hotel Non-Bintang / Akomodasi Liburan</td>
                                <td class="text-center"><?= $tahun_aktif ?></td>
                                <td class="text-center fw-semibold">24.10% TPK</td>
                                <td class="text-center"><span class="badge bg-secondary">Sektor Pendukung</span></td>
                            </tr>
                            <tr>
                                <td>Rata Kunjungan Wisatawan Domestik (Jawa Timur)</td>
                                <td class="text-center"><?= $tahun_aktif ?></td>
                                <td class="text-center fw-semibold text-success">High Density</td>
                                <td class="text-center"><span class="badge bg-success">Zona Padat</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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

            <!-- Row Tengah: Grafik & Cuaca Valid -->
            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Grafik Tren Liburan</h5>
                        <p class="text-muted small mb-4">Estimasi perbandingan intensitas kunjungan wisata Anda per bulan (<?= $tahun_aktif ?>).</p>
                        <div style="position: relative; height:220px; width:100%">
                            <canvas id="chartPengeluaran"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card info-card">
                        <h5 class="fw-bold text-dark mb-1">Prakiraan Cuaca Destinasi ⛅</h5>
                        <p class="text-muted small mb-3">Kondisi cuaca real-time di lokasi objek wisata utama (data: Open-Meteo).</p>

                        <div class="d-flex flex-column">
                            <?php foreach ($data_cuaca as $i => $cw): ?>
                            <div class="weather-row" <?= $i === count($data_cuaca) - 1 ? 'style="margin-bottom:0;"' : '' ?>>
                                <span class="fw-semibold small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($cw['nama']) ?></span>
                                <span class="weather-badge <?= $cw['warna'] ?>">
                                    <i class="bi <?= $cw['icon'] ?> me-1"></i>
                                    <?= htmlspecialchars($cw['label']) ?><?= $cw['suhu'] !== null ? ' ' . $cw['suhu'] . '°C' : '' ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
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
    // Script grafik BPS
    const ctxBps = document.getElementById('chartBpsWisata').getContext('2d');
    new Chart(ctxBps, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Perjalanan Wisatawan Domestik (Juta)',
                data: [45, 52, 49, 68, 74, 82, 71, 65, 60, 58, 62, 85],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3
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