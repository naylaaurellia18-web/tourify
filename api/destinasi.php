<?php
// URUTAN WAJIB: ini_set -> session_start -> cek login -> koneksi DB
include __DIR__ . '/session_db.php';

// Cek login
if (empty($_SESSION['login_user'])) {
    header("Location: /api/login.php");
    exit;
}

// Ambil nama user
$nama_tampil = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap']
             : (!empty($_SESSION['username']) ? $_SESSION['username'] : 'Pengguna');
$username_login = $_SESSION['username'] ?? $_SESSION['user'] ?? '';

// Koneksi TiDB
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3DA4d4bPMVCSuDy.root", "mRSgOTH6qk79AieJ", "tourify-db", 4000, NULL, MYSQLI_CLIENT_SSL);
mysqli_set_charset($conn, "utf8mb4");
// Tangkap promo dari URL dan simpan ke session jika ada
if (isset($_GET['kode'])) {
    $_SESSION['promo_aktif'] = [
        'kode'     => strip_tags($_GET['kode']),
        'diskon'   => isset($_GET['diskon'])   ? (float)$_GET['diskon']   : 0,
        'potongan' => isset($_GET['potongan']) ? (int)$_GET['potongan']   : 0
    ];
}

// Ambil data login user
$nama_tampil = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? $_SESSION['user'] ?? null;

// Ambil daftar destinasi dari database
$db = $conn;
$res_dest = mysqli_query($db, "SELECT * FROM destinasi ORDER BY id_destinasi ASC");
$dari_db  = ($res_dest && mysqli_num_rows($res_dest) > 0);

// Ambil rata-rata rating & jumlah ulasan per destinasi (untuk ditampilkan di kartu)
$rating_map = [];
// Query ulasan dengan error handling (tabel mungkin belum ada)
$q_rating = @@mysqli_query($db, "SELECT id_destinasi, AVG(rating) AS rata, COUNT(*) AS jml FROM ulasan GROUP BY id_destinasi");
if ($q_rating) {
    while ($r = mysqli_fetch_assoc($q_rating)) {
        $rating_map[(int)$r['id_destinasi']] = [
            'rata' => round((float)$r['rata'], 1),
            'jml'  => (int)$r['jml'],
        ];
    }
}

// Data statis dari file referensi Anda sebagai fallback (jika database kosong)
$destinasi_statis = [
    ['nama'=>'Saloka Theme Park',          'lokasi'=>'Semarang',   'deskripsi'=>'Taman rekreasi keluarga terbesar di Jawa Tengah dengan berbagai wahana seru.',                     'harga'=>120000, 'stok'=>50, 'gambar'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQRZqPWsn-DyTw7qSrAjenFvPuQsrCvnKjMsw&s'],
    ['nama'=>'Candi Borobudur',             'lokasi'=>'Magelang',   'deskripsi'=>'Candi Buddha terbesar di dunia, warisan budaya UNESCO.',                                          'harga'=>300000, 'stok'=>80, 'gambar'=>'https://cdn1-production-images-kly.akamaized.net/KRV05_LNI_woM1xsLULUlF-KGZE=/1200x675/smart/filters:quality(75):strip_icc():format(jpeg)/kly-media-production/medias/3023951/original/083764400_1579164554-indonesia-1098328_1920.jpg'],
    ['nama'=>'Taman Nasional Karimunjawa', 'lokasi'=>'Jepara',     'deskripsi'=>'Pesona wisata bahari terindah dengan keindahan bawah laut dan pantai pasir putih.',             'harga'=>200000, 'stok'=>40, 'gambar'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQsIs1YIW602fv8a-S9qUgwZWFd8_qyp7X5lQ&s'],
    ['nama'=>'Rasamadu (The Heritage Palace)', 'lokasi'=>'Sukoharjo','deskripsi'=>'Bekas pabrik gula abad ke-19 yang diubah menjadi tempat wisata bergaya Eropa.',               'harga'=>80000,  'stok'=>30, 'gambar'=>'https://s-light.tiket.photos/t/01E25EBZS3W0FY9GTG6C42E1SE/rsfit19201280gsm/events/2026/03/25/a31c0d96-04af-41a3-bf0d-e6e1dd47f723-1774431846858-13592ce40930746e3e717f6e07e07d04.jpg'],
    ['nama'=>'Solo Safari',                 'lokasi'=>'Surakarta',  'deskripsi'=>'Kawasan kebun binatang modern dengan konsep edukasi satwa yang interaktif.',                    'harga'=>60000,  'stok'=>25, 'gambar'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSuZgalMALjLh8eeh4WdWlGIMKLeZ4RPPWGIg&s'],
];

date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Tiket Destinasi | Tourify</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, .brand-title, .card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }

        .wrapper {
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        /* Sidebar Styling (Sesuai Dashboard Tourify) */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            min-height: 100vh;
            transition: all 0.3s;
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
            width: 35px; height: 35px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(243, 112, 33, 0.2);
            font-size: 0.95rem;
        }

        .brand-title {
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            color: var(--text-dark);
        }

        .sidebar-menu {
            padding: 25px 15px;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 6px;
        }

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
            background: rgba(243, 112, 33, 0.08);
            color: var(--primary);
        }

        /* Main Content Layout */
        #content {
            width: 100%;
            padding: 35px 40px;
            min-height: 100vh;
        }

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
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .avatar-circle {
            width: 35px; height: 35px;
            background: rgba(243, 112, 33, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }

        .btn-logout {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fca5a5;
            padding: 10px 20px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
        }

        /* Card Destinasi Modern Tourify Style */
        .destinasi-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .destinasi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(243, 112, 33, 0.08);
            border-color: rgba(243, 112, 33, 0.2);
        }

        .img-container {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .destinasi-card:hover .img-container img {
            transform: scale(1.06);
        }

        .tag-lokasi {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            color: var(--text-dark);
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .card-body-custom {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .card-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price-box {
            border-top: 1px dashed var(--border-color);
            padding-top: 15px;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .price-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stok-info {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 6px;
        }

        .btn-pesan:disabled, .btn-pesan.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
        }
        .btn-pesan:disabled:hover, .btn-pesan.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .btn-pesan {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(243, 112, 33, 0.15);
        }

        .btn-pesan:hover {
            box-shadow: 0 6px 18px rgba(243, 112, 33, 0.3);
            color: white;
            transform: translateY(-1px);
        }

        .modal-content-custom {
            border-radius: 24px;
            border: none;
            padding: 15px;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Kiri -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a class="nav-brand-box" href="/api/dashboard.php">
                <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
                <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
            </a>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="/api/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Ringkasan</a>
            </li>
            <li class="active">
                <a href="/api/destinasi.php"><i class="bi bi-ticket-perforated-fill"></i> Sistem Tiket</a>
            </li>
            <li>
                <a href="/api/promo.php"><i class="bi bi-tags-fill"></i> Promo Eksklusif</a>
            </li>
            <li>
                <a href="/api/dashboard.php?page=bps"><i class="bi bi-graph-up-arrow"></i> Statistik BPS</a>
            </li>
            <li>
                <a href="/api/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a>
            </li>
            <li>
                <a href="/api/ulasan.php"><i class="bi bi-star-fill"></i> Ulasan</a>
            </li>
            <li>
                <a href="/api/profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a>
            </li>
        </ul>
    </nav>

    <!-- Konten Utama Kanan -->
    <div id="content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark">Sistem Tiket Destinasi Jawa Tengah 🎟️</h4>
                <p class="text-muted small mb-0 font-sans">Halo, <b><?= htmlspecialchars($nama_tampil); ?></b>! Cari tempat rekreasi impianmu di sini.</p>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile-box">
                    <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                    <div class="small fw-semibold d-none d-sm-block">
                        <?= htmlspecialchars($nama_tampil); ?>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Status: Aktif</span>
                    </div>
                </div>
                <a href="/api/logout.php" class="btn btn-logout text-decoration-none"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>

        <!-- Notifikasi Promo Aktif -->
        <?php if (isset($_SESSION['promo_aktif'])): ?>
            <div class="alert border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4" style="background: #fff7ed; border-left: 5px solid var(--primary) !important; border-radius: 16px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-white rounded-3 text-warning shadow-sm"><i class="bi bi-stars text-orange"></i></div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Promo <b><?= htmlspecialchars($_SESSION['promo_aktif']['kode']); ?></b> Aktif!</h6>
                        <p class="mb-0 small text-muted">Potongan harga akan dikalkulasikan otomatis pada formulir invoice pemesanan.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
            </div>
        <?php endif; ?>

        <!-- Grid Cards Destinasi -->
        <div class="row g-4">
            <?php 
            if ($dari_db) {
                while($row = mysqli_fetch_assoc($res_dest)) {
                    // Fleksibilitas nama kolom database (mendukung nama_destinasi atau nama)
                    $nama     = htmlspecialchars($row['nama_destinasi'] ?? $row['nama']);
                    $lokasi   = htmlspecialchars($row['lokasi']);
                    $desc     = htmlspecialchars($row['deskripsi'] ?? '');
                    $harga    = (int)$row['harga'];
                    $gambar   = !empty($row['gambar']) ? htmlspecialchars($row['gambar']) : 'https://via.placeholder.com/400x200?text=' . urlencode($nama);
                    $stok     = (int)($row['stok_tiket'] ?? 0);
                    $id_dest_ini = (int)$row['id_destinasi'];
                    $info_rating = $rating_map[$id_dest_ini] ?? ['rata' => 0, 'jml' => 0];

                    if ($stok <= 0) {
                        $stok_bg = '#fef2f2'; $stok_color = '#ef4444'; $stok_label = 'Tiket Habis';
                    } elseif ($stok < 10) {
                        $stok_bg = '#fffbeb'; $stok_color = '#d97706'; $stok_label = $stok . ' tiket tersisa';
                    } else {
                        $stok_bg = '#f0fdf4'; $stok_color = '#16a34a'; $stok_label = $stok . ' tiket tersisa';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="destinasi-card">
                            <div class="img-container">
                                <span class="tag-lokasi"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= $lokasi; ?>, Jateng</span>
                                <img src="<?= $gambar; ?>" alt="<?= $nama; ?>" onerror="this.src='https://via.placeholder.com/400x200?text=Gambar+Tidak+Tersedia'">
                            </div>
                            <div class="card-body-custom">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h5 class="card-title mb-1"><?= $nama; ?></h5>
                                </div>
                                <div class="mb-2" style="font-size:0.85rem;">
                                    <?php if ($info_rating['jml'] > 0): ?>
                                        <span style="color:#f59e0b;font-weight:700;"><i class="bi bi-star-fill me-1"></i><?= $info_rating['rata'] ?></span>
                                        <span class="text-muted">(<?= $info_rating['jml'] ?> ulasan)</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-star me-1"></i>Belum ada ulasan</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-desc"><?= $desc; ?></p>
                                <div class="price-box">
                                    <div>
                                        <div class="price-label">Harga Tiket</div>
                                        <div class="price-value">Rp <?= number_format($harga, 0, ',', '.'); ?></div>
                                        <span class="stok-info" style="background:<?= $stok_bg ?>;color:<?= $stok_color ?>;"><?= $stok_label ?></span>
                                    </div>
                                    <?php if ($stok <= 0): ?>
                                    <button class="btn btn-pesan px-4 disabled" disabled title="Tiket sedang habis">
                                        Tiket Habis
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-pesan px-4" onclick="pesanTiket('<?= addslashes($nama); ?>', '<?= $harga; ?>')">
                                        Pesan Tiket
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // TAMPILAN DATA FALLBACK JAWA TENGAH JIKA DATABASE KOSONG
                foreach ($destinasi_statis as $item) {
                    $stok = (int)($item['stok'] ?? 0);
                    if ($stok <= 0) {
                        $stok_bg = '#fef2f2'; $stok_color = '#ef4444'; $stok_label = 'Tiket Habis';
                    } elseif ($stok < 10) {
                        $stok_bg = '#fffbeb'; $stok_color = '#d97706'; $stok_label = $stok . ' tiket tersisa';
                    } else {
                        $stok_bg = '#f0fdf4'; $stok_color = '#16a34a'; $stok_label = $stok . ' tiket tersisa';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="destinasi-card">
                            <div class="img-container">
                                <span class="tag-lokasi"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= $item['lokasi']; ?>, Jateng</span>
                                <img src="<?= $item['gambar']; ?>" alt="<?= $item['nama']; ?>" onerror="this.src='https://via.placeholder.com/400x200?text=Gambar+Tidak+Tersedia'">
                            </div>
                            <div class="card-body-custom">
                                <h5 class="card-title"><?= $item['nama']; ?></h5>
                                <p class="card-desc"><?= $item['deskripsi']; ?></p>
                                <div class="price-box">
                                    <div>
                                        <div class="price-label">Harga Tiket</div>
                                        <div class="price-value">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                                        <span class="stok-info" style="background:<?= $stok_bg ?>;color:<?= $stok_color ?>;"><?= $stok_label ?></span>
                                    </div>
                                    <?php if ($stok <= 0): ?>
                                    <button class="btn btn-pesan px-4 disabled" disabled title="Tiket sedang habis">
                                        Tiket Habis
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-pesan px-4" onclick="pesanTiket('<?= addslashes($item['nama']); ?>', '<?= $item['harga']; ?>')">
                                        Pesan Tiket
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        
        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif; ?> Tourify. Hak Cipta Dilindungi Panel Pengguna.</p>
        </div>
    </div>
</div>

<!-- Modal Dialog Pembelian Tiket Modern -->
<div class="modal fade" id="tiketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-4 text-warning" style="font-size: 3.5rem;">
                    <i class="bi bi-ticket-detailed-fill"></i>
                </div>
                <h4 class="fw-bold mb-3 text-dark">🎟️ Konfirmasi Pemesanan</h4>
                <p class="text-muted mb-4" id="infoTiket">Anda akan memesan tiket...</p>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success py-3 rounded-pill text-white fw-bold border-0" style="background: var(--primary-gradient); box-shadow: 0 4px 15px rgba(243,112,33,0.3);" onclick="lanjutBayar()">
                        Lanjut Pembayaran <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-light py-2 text-muted fw-semibold rounded-pill border-0 mt-1" data-bs-dismiss="modal">
                        Kembali Pilih Wisata
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let wisataTerpilih = "";
let hargaTerpilih  = "";

function pesanTiket(nama, harga) {
    wisataTerpilih = nama;
    hargaTerpilih  = harga;
    document.getElementById('infoTiket').innerHTML =
        `Anda akan memesan tiket <br><b class="text-dark" style="font-size:1.1rem;">${nama}</b> <br>seharga <b class="text-primary">Rp ${parseInt(harga).toLocaleString('id-ID')}</b>`;
    new bootstrap.Modal(document.getElementById('tiketModal')).show();
}

function lanjutBayar() {
    // Ambil data promo aktif dari URL query string jika terdeteksi
    const params   = new URLSearchParams(window.location.search);
    const diskon   = params.get('diskon')   || 0;
    const potongan = params.get('potongan') || 0;
    const kode     = params.get('kode')     || '';
    
    // Alihkan navigasi ke halaman konfirmasi_pesanan.php
    window.location.href = "/api/konfirmasi_pesanan.php?wisata=" + encodeURIComponent(wisataTerpilih)
        + "&harga="    + hargaTerpilih
        + "&diskon="   + diskon
        + "&potongan=" + potongan
        + "&kode="     + encodeURIComponent(kode);
}
</script>
</body>
</html>