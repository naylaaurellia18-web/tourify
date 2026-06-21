<?php
// index.php
date_default_timezone_set('Asia/Jakarta');

// Data Pendukung Sistem
$total_destinasi = 12;
$status_sistem = "Aktif & Real-Time";
$tahun_aktif = date('Y');

// Latar Belakang Pantai Utama
$bg_hero = "https://media.indozone.id/crop/0x0:0x0/images/2025/08/04/5U6ioyE5iAH251ZaMW7fjOrxkArFlZ5CpK8mssXr.jpg";
$overlay_hero = "linear-gradient(to bottom, rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.20))";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourify | Layanan Reservasi & Kunjungan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-light: #fff7ed;        
            --white: #ffffff;
            --primary: #ff7a00;         
            --primary-hover: #e56e00;
            --secondary: #ea580c;       
            --text-dark: #0f172a;       
            --text-muted: #64748b;        
            --accent-orange: #fdba74;    
            --footer-bg: #0f172a;        
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .brand-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        a, .btn, .card, .accordion-button {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* --- Navbar --- */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 122, 0, 0.08);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.02);
        }
        
        .nav-brand-box {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-dark) !important;
        }
        .brand-title {
            font-size: 1.55rem;
            line-height: 1;
            letter-spacing: -0.8px;
        }
        .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #ff7a00, #ff923a, #fdba74);
            color: var(--white);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(255, 122, 0, 0.35);
            font-size: 1.05rem;
        }

        /* --- Hero --- */
        .hero-wrap {
            position: relative;
            background: url('<?php echo $bg_hero; ?>') no-repeat center center;
            background-size: cover;
            padding: 190px 0 140px;
            text-align: center;
        }
        
        .hero-wrap::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: <?php echo $overlay_hero; ?>;
            z-index: 1;
        }

        .hero-wrap .container { position: relative; z-index: 2; }
        
        .hero-title {
            font-size: 3.5rem;
            color: var(--white);
            line-height: 1.2;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            letter-spacing: -1.5px;
        }

        .hero-desc {
            max-width: 600px;
            font-size: 1.05rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.95) !important;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.3);
            font-weight: 400;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.8rem;
            margin-bottom: 25px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 122, 0, 0.2);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        /* --- Buttons --- */
        .btn-orange-vivid {
            background: linear-gradient(135deg, var(--primary), #ffa200);
            border: none; color: white; font-weight: 700;
            padding: 14px 38px; box-shadow: 0 8px 24px rgba(255, 122, 0, 0.25);
            border-radius: 100px;
            font-size: 0.9rem;
        }
        .btn-orange-vivid:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 122, 0, 0.4);
            color: white;
        }
        .btn-outline-dark-custom {
            border: 2px solid rgba(255,255,255,0.8);
            background: rgba(255, 255, 255, 0.15); color: var(--white);
            font-weight: 700; padding: 13px 38px; border-radius: 100px;
            backdrop-filter: blur(4px);
            font-size: 0.9rem;
        }
        .btn-outline-dark-custom:hover {
            background: var(--white); color: var(--text-dark); transform: translateY(-3px);
            border-color: var(--white);
        }

        /* --- Stats Strip --- */
        .stats-container { margin-top: -50px; position: relative; z-index: 10; }
        .stats-box {
            background: var(--white);
            border-radius: 24px; display: flex;
            border: 1px solid rgba(255, 122, 0, 0.08);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.06);
            overflow: hidden;
        }
        .stat-item { flex: 1; padding: 28px 20px; text-align: center; border-right: 1px solid #f8fafc; }
        .stat-item:last-child { border-right: none; }
        .stat-val { font-size: 2.2rem; color: var(--primary); line-height: 1; font-weight: 800; letter-spacing: -1px; }
        .stat-lab { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-top: 6px; }

        /* --- Promo Cards --- */
        .promo-scroll { display: flex; overflow-x: auto; gap: 20px; padding: 10px 4px 20px; scrollbar-width: none; }
        .promo-scroll::-webkit-scrollbar { display: none; }
        .promo-card {
            min-width: 310px; border-radius: 22px; padding: 30px; color: var(--text-dark);
            background: var(--white); border: 1px solid rgba(255, 122, 0, 0.08);
            box-shadow: 0 12px 30px rgba(255, 122, 0, 0.03);
            position: relative;
        }

        /* --- Step Cards --- */
        .step-card {
            border: 1px solid rgba(255, 122, 0, 0.08);
            border-radius: 24px; padding: 45px 30px;
            background: var(--white); height: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.01);
        }
        .step-card:hover { 
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 18px 35px rgba(255, 122, 0, 0.12);
        }
        .icon-circle {
            width: 70px; height: 70px;
            background: rgba(255, 122, 0, 0.06);
            color: var(--primary); border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; font-size: 1.8rem;
        }

        .section-title { font-size: 2.2rem; color: var(--text-dark); line-height: 1.2; letter-spacing: -1px; }

        /* --- Accordion --- */
        .accordion-item { 
            border: 1px solid rgba(255, 122, 0, 0.08) !important; 
            margin-bottom: 14px; border-radius: 18px !important; 
            overflow: hidden; background: var(--white);
        }
        .accordion-button { font-weight: 700; padding: 20px 24px; color: var(--text-dark); background: transparent; }
        .accordion-button:not(.collapsed) { background-color: rgba(255, 122, 0, 0.04); color: var(--secondary); box-shadow: none; }

        .review-card { background: var(--white); border: 1px solid rgba(255, 122, 0, 0.08); border-radius: 24px; padding: 32px; height: 100%; }
        
        footer { background: var(--footer-bg); color: #94a3b8; padding: 70px 0 40px; border-top: 4px solid var(--primary); }
        .footer-logo { font-size: 1.8rem; color: var(--white); letter-spacing: -0.8px; }

        /* --- Custom Modal Glassmorphism --- */
        .modal-content-custom {
            border: none;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(255, 122, 0, 0.15);
            background: #ffffff;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .stats-box { flex-direction: column; }
            .stat-item { border-right: none; border-bottom: 1px solid #f8fafc; }
            .hero-wrap { padding: 150px 0 110px; }
            .hero-title { font-size: 2.4rem; }
            .section-title { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light navbar-custom sticky-top">
    <div class="container px-4">
        <a class="nav-brand-box" href="index.php">
            <div class="logo-icon"><i class="fas fa-globe-asia"></i></div>
            <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="bg-light rounded-3 px-3 py-2 small d-none d-sm-flex align-items-center gap-2" style="border: 1px solid rgba(255,122,0,0.12)">
                <i class="fas fa-clock text-warning"></i>
                <span id="jamNav" class="fw-bold text-secondary" style="font-size: 0.85rem;"><?php echo date('H:i:s'); ?> WIB</span>
            </div>
            <a href="api/login.php" class="btn rounded-pill px-4 fw-bold btn-sm shadow-sm" style="background: var(--text-dark); color: var(--white); font-size: 0.85rem; padding: 8px 20px;">Masuk</a>
        </div>
    </div>
</nav>

<section class="hero-wrap">
    <div class="container px-4">
        <div class="hero-badge">
            <i class="fas fa-bolt text-primary"></i> 
            Sistem <?php echo $status_sistem; ?> <span>• Live <?php echo $tahun_aktif; ?></span>
        </div>
        <h1 class="hero-title">Liburan Seru Konsep Baru,<br><span style="color: #fdba74;">Tanpa Antre & Ribet!</span></h1>
        <p class="lead mb-5 mx-auto hero-desc">Solusi cerdas pesan tiket wisata instan terlengkap sekaligus memantau kuota kunjungan real-time di seluruh Indonesia.</p>
        
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <button onclick="bukaModalAuth('Tiket Wisata Utama')" class="btn btn-lg btn-orange-vivid">Mulai Cari Tiket</button>
            <a href="api/register.php" class="btn btn-outline-dark-custom btn-lg">Daftar Akun Baru</a>
        </div>
    </div>
</section>

<div class="container stats-container">
    <div class="stats-box">
        <div class="stat-item">
            <span class="stat-val"><?php echo $total_destinasi; ?>+</span>
            <span class="stat-lab">Destinasi Pilihan</span>
        </div>
        <div class="stat-item">
            <span class="stat-val">Otomatis</span>
            <span class="stat-lab">Update Kuota</span>
        </div>
        <div class="stat-item">
            <span class="stat-val" style="color: var(--secondary);">BPS API</span>
            <span class="stat-lab">Data Terintegrasi</span>
        </div>
    </div>
</div>

<section class="py-5 mt-5">
    <div class="container px-4">
        <h4 class="section-title mb-4"><i class="bi bi-stars" style="color: var(--primary);"></i> Promo Liburan Paling Hemat</h4>
        <div class="promo-scroll">
            <div class="promo-card shadow-sm" style="border-left: 4px solid var(--primary);">
                <span class="badge mb-2" style="background: rgba(255,122,0,0.1); color: var(--primary); font-weight:700; font-size: 0.7rem;">PROMO SPESIAL</span>
                <h5 class="fw-bold mb-1" style="font-size: 1.05rem;">Diskon Tiket 15%</h5>
                <p class="small text-muted mb-4">Gunakan kode voucher: <strong class="text-dark">ALAM-INDO</strong></p>
                <button onclick="bukaModalAuth('Diskon Tiket 15% (ALAM-INDO)')" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold text-white btn-orange-vivid" style="box-shadow:none; font-size:0.75rem; padding: 8px 18px;">Ambil Voucher</button>
            </div>
            <div class="promo-card shadow-sm" style="border-left: 4px solid var(--secondary);">
                <span class="badge mb-2" style="background: rgba(234,88,12,0.1); color: var(--secondary); font-weight:700; font-size: 0.7rem;">PENGUNJUNG BARU</span>
                <h5 class="fw-bold mb-1" style="font-size: 1.05rem;">Potongan Langsung 10rb</h5>
                <p class="small text-muted mb-4">Gunakan kode voucher: <strong class="text-dark">BARU-TOURIFY</strong></p>
                <button onclick="bukaModalAuth('Potongan Langsung 10rb (BARU-TOURIFY)')" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold text-white btn-orange-vivid" style="box-shadow:none; font-size:0.75rem; padding: 8px 18px;">Ambil Voucher</button>
            </div>
        </div>
    </div>
</section>

<section class="py-5 border-top border-bottom" style="background: #fff; border-color: rgba(255,122,0,0.06) !important;">
    <div class="container px-4 py-2">
        <div class="text-center mx-auto mb-5" style="max-width: 500px;">
            <h3 class="section-title mb-2">Mudahnya Pesan Tiket</h3>
            <p class="text-muted small fw-medium">Sistem otomatisasi yang mempersingkat waktu antrean kamu di gerbang lokasi.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card step-card text-center border-0">
                    <div class="icon-circle"><i class="bi bi-geo-alt shadow-sm"></i></div>
                    <h5 class="fw-bold mb-2" style="font-size: 1.1rem;">1. Cari Tempat Seru</h5>
                    <p class="small text-muted mb-0" style="line-height: 1.6;">Pilih objek wisata alam favorit kamu dari katalog yang terintegrasi.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card step-card text-center border-0">
                    <div class="icon-circle" style="background: rgba(234,88,12,0.06); color: var(--secondary);"><i class="bi bi-credit-card shadow-sm"></i></div>
                    <h5 class="fw-bold mb-2" style="font-size: 1.1rem;">2. Bayar Sekali Klik</h5>
                    <p class="small text-muted mb-0" style="line-height: 1.6;">Transaksi cepat aman lewat QRIS otomatis atau transfer bank resmi.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card step-card text-center border-0">
                    <div class="icon-circle" style="background: rgba(253,186,116,0.12); color: var(--primary);"><i class="bi bi-qr-code-scan shadow-sm"></i></div>
                    <h5 class="fw-bold mb-2" style="font-size: 1.1rem;">3. Dapatkan E-Tiket</h5>
                    <p class="small text-muted mb-0" style="line-height: 1.6;">Kode QR otomatis terbit, tinggal tunjukkan ke petugas pintu masuk.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container px-4">
        <div class="text-center mx-auto mb-5" style="max-width: 500px;">
            <h3 class="section-title mb-2">Cerita Seru Wisatawan</h3>
            <p class="text-muted small fw-medium">Pengalaman nyata dari mereka yang berlibur menggunakan kemudahan Tourify.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card review-card border-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; background: var(--primary); font-size: 0.95rem;">R</div>
                        <div>
                            <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">Rian Hermawan</h6>
                            <small class="text-muted">Solo • Pengunjung Nongko Ijo</small>
                        </div>
                    </div>
                    <p class="small text-muted mb-0" style="line-height: 1.6;">"Sangat membantu dengan sistem pemesanan tiket online di web ini. Jadi tidak perlu khawatir kehabisan tiket pas sampai di lokasi. Proses bayarnya juga kilat pakai QRIS."</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card review-card border-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; background: var(--secondary); font-size: 0.95rem;">A</div>
                        <div>
                            <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">Amelia Putri</h6>
                            <small class="text-muted">Surabaya • Pengunjung Telaga Sarangan</small>
                        </div>
                    </div>
                    <p class="small text-muted mb-0" style="line-height: 1.6;">"E-tiket langsung muncul begitu pembayaran sukses. Tinggal tunjukkan kode QR ke petugas pintu masuk gerbang, langsung dipersilakan masuk tanpa antre panjang!"</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white border-top">
    <div class="container px-4">
        <div class="text-center mb-5">
            <h3 class="section-title">Punya Pertanyaan?</h3>
            <p class="text-muted small fw-medium">Informasi dasar seputar penggunaan sistem pemesanan online.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqGo">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#f1">Apakah tiket ada masa berlakunya?</button>
                        </h2>
                        <div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqGo">
                            <div class="accordion-body text-muted small" style="line-height: 1.6;">Tiket berlaku hingga 30 hari sejak tanggal pembelian selama statusnya belum digunakan di gerbang check-in lokasi wisata.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#f2">Bagaimana sistem memonitor kunjungan?</button>
                        </h2>
                        <div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqGo">
                            <div class="accordion-body text-muted small" style="line-height: 1.6;">Setiap e-tiket pengunjung yang di-scan otomatis memperbarui data grafik kunjungan harian pada dashboard petugas secara real-time.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer Perbaikan Total -->
<footer class="text-center" style="background: var(--footer-bg); color: #cbd5e1; padding: 70px 0 40px; border-top: 4px solid var(--primary);">
    <div class="container px-4">
        <div class="footer-logo mb-2">Tour<span style="color: var(--primary);">ify</span></div>
        
        <!-- Menggunakan style warna putih abu-abu terang (concrete white) secara langsung -->
        <p class="mb-4 small" style="max-width: 500px; margin: 0 auto; line-height: 1.5; color: #e2e8f0 !important; opacity: 1 !important;">
    Sistem Integrasi Layanan Reservasi & Monitoring Kunjungan Terpadu
</p>
        
        <div style="width: 40px; height: 2px; background: var(--primary); margin: 0 auto 25px; opacity: 0.6;"></div>
        
        <!-- Teks Hak Cipta dibuat sedikit lebih redup agar hierarki visualnya pas -->
        <p class="mb-0 small" style="font-size: 0.75rem; letter-spacing: 0.3px; color: #94a3b8;">
            &copy; <?php echo $tahun_aktif; ?> Tourify. Hak Cipta Dilindungi.
        </p>
    </div>
</footer>

<div class="modal fade" id="authGatewayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content modal-content-custom text-center">
            <div class="modal-body py-4">
                <div class="icon-circle mx-auto mb-4" style="background: rgba(255, 122, 0, 0.08); width: 75px; height: 75px; border-radius: 22px;">
                    <i class="bi bi-shield-lock-fill text-primary fs-2"></i>
                </div>
                
                <h4 class="fw-bold mb-2 text-dark" style="font-size: 1.35rem;">Eits, Masuk Dulu Yuk!</h4>
                <p class="text-muted small px-2 mb-4" style="line-height: 1.6;">
                    Untuk mengklaim <strong class="text-dark" id="namaItemTerpilih">Promo</strong> dan melakukan reservasi, silakan masuk ke akun kamu atau daftar baru terlebih dahulu.
                </p>
                
                <div class="d-grid gap-3">
                    <a href="api/login.php" class="btn btn-orange-vivid py-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Akun
                    </a>
                    <a href="api/register.php" class="btn btn-light py-3 fw-bold text-dark rounded-pill border" style="background: #f8fafc;">
                        <i class="bi bi-person-plus me-2"></i> Belum Punya Akun? Daftar
                    </a>
                </div>
                
                <button type="button" class="btn btn-link link-secondary mt-3 text-decoration-none small" data-bs-dismiss="modal" style="font-size: 0.8rem; font-weight: 600;">Kembali Nanti</button>
            </div>
        </div>
    </div>
</div>

<script>
// Logic Jam Real-Time Navbar
function updateJam() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('jamNav').textContent = `${h}:${m}:${s} WIB`;
}
setInterval(updateJam, 1000);

// Fungsi Interseptor untuk Menampilkan Modal Pop-up Auth
function bukaModalAuth(namaItem) {
    document.getElementById('namaItemTerpilih').textContent = namaItem;
    const modalAuth = new bootstrap.Modal(document.getElementById('authGatewayModal'));
    modalAuth.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>