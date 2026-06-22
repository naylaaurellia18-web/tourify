<?php
// Fix session untuk Vercel serverless
include __DIR__ . '/session_db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/koneksi.php';

$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;
if (!$nama_tampil || !$is_logged_in) { header("Location: /api/login.php"); exit(); }

$riwayat_pesanan = [];
if (isset($conn)) {
    $user_escaped = mysqli_real_escape_string($conn, $nama_tampil);
    $q = mysqli_query($conn, "SELECT * FROM pesanan WHERE username='$user_escaped' ORDER BY id DESC");
    if ($q) while ($row = mysqli_fetch_assoc($q)) $riwayat_pesanan[] = $row;
}

date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');

$metode_label = [
    'transfer_bank' => ['icon'=>'bi-bank2',       'label'=>'Transfer Bank', 'color'=>'#2563eb'],
    'e_wallet'      => ['icon'=>'bi-phone-fill',   'label'=>'E-Wallet',      'color'=>'#7c3aed'],
    'qris'          => ['icon'=>'bi-qr-code-scan', 'label'=>'QRIS',          'color'=>'#059669'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan | Tourify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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

        /* Tabel */
        .table-custom th { background:#f8fafc;color:var(--text-muted);font-weight:600;text-transform:uppercase;font-size:0.78rem;letter-spacing:0.5px;padding:16px;border-bottom:2px solid var(--border); }
        .table-custom td { padding:18px 16px;color:var(--text-dark);font-size:0.92rem;border-bottom:1px solid var(--border);vertical-align:middle; }
        .empty-state-box { padding:60px 20px;text-align:center; }
        .empty-state-icon { font-size:3.5rem;color:var(--text-muted);opacity:0.3;margin-bottom:15px; }
        .badge-status { padding:6px 12px;border-radius:8px;font-weight:600;font-size:0.78rem; }
        .btn-cetak { background:var(--primary-gradient);border:none;color:white;border-radius:8px;padding:7px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;transition:0.2s; }
        .btn-cetak:hover { opacity:0.85;transform:translateY(-1px); }

        /* ===== MODAL E-TIKET ===== */
        .tiket-modal { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px; }
        .tiket-modal.open { display:flex; }

        /* E-tiket print area */
        .etiket-wrap {
            background:white;
            border-radius:24px;
            width:100%;
            max-width:480px;
            overflow:hidden;
            box-shadow:0 30px 80px rgba(0,0,0,0.3);
            position:relative;
        }
        .etiket-header {
            background:var(--primary-gradient);
            padding:28px;
            color:white;
            text-align:center;
        }
        .etiket-logo { font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;letter-spacing:-0.5px; }
        .etiket-subtitle { font-size:0.8rem;opacity:0.8;margin-top:2px; }
        .etiket-body { padding:28px; }

        .etiket-destinasi {
            text-align:center;
            background:#fff7ed;
            border-radius:14px;
            padding:20px;
            margin-bottom:24px;
            border:1.5px solid #ffe4cc;
        }
        .etiket-destinasi .dest-name { font-size:1.25rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-dark); }
        .etiket-destinasi .dest-date { color:var(--text-muted);font-size:0.88rem;margin-top:4px; }

        .etiket-info-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px; }
        .etiket-info-item { background:#f8fafc;border-radius:10px;padding:12px 14px; }
        .etiket-info-item .lbl { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted); }
        .etiket-info-item .val { font-size:0.95rem;font-weight:700;color:var(--text-dark);margin-top:3px; }

        .etiket-divider { display:flex;align-items:center;gap:0;margin:0 -28px 24px;position:relative; }
        .etiket-divider::before { content:'';flex:1;height:1px;background:repeating-linear-gradient(90deg,#e2e8f0 0,#e2e8f0 8px,transparent 8px,transparent 16px); }
        .etiket-divider .circle { width:24px;height:24px;border-radius:50%;background:var(--bg);flex-shrink:0; }
        .etiket-divider .circle.left  { margin-left:-12px;box-shadow:inset -2px 0 0 var(--border); }
        .etiket-divider .circle.right { margin-right:-12px;box-shadow:inset 2px 0 0 var(--border); }

        .etiket-total { display:flex;justify-content:space-between;align-items:center;background:#fff7ed;border-radius:12px;padding:16px;margin-bottom:20px; }
        .etiket-total .total-label { color:var(--text-muted);font-size:0.88rem; }
        .etiket-total .total-val { font-size:1.3rem;font-weight:800;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif; }

        .etiket-kode { text-align:center;background:#1e293b;color:white;border-radius:12px;padding:14px;letter-spacing:3px;font-family:monospace;font-size:1.1rem;font-weight:700;margin-bottom:20px; }

        .etiket-qr-wrap { display:flex;flex-direction:column;align-items:center;gap:8px;margin-bottom:20px; }
        .etiket-qr-wrap #et_qrcode { padding:12px;background:white;border:2px solid var(--border);border-radius:14px;display:inline-block; }
        .etiket-qr-wrap #et_qrcode img, .etiket-qr-wrap #et_qrcode canvas { display:block;border-radius:4px; }
        .etiket-qr-hint { font-size:0.74rem;color:var(--text-muted);text-align:center; }

        .etiket-status { display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;background:#f0fdf4;border-radius:10px;color:#16a34a;font-weight:700;font-size:0.88rem;margin-bottom:24px; }

        .etiket-footer { background:#f8fafc;padding:16px 28px;text-align:center;font-size:0.75rem;color:var(--text-muted);border-top:1px solid var(--border); }

        /* Modal action buttons */
        .modal-actions { display:flex;gap:10px;margin-top:0;padding:0 28px 24px; }
        .btn-print-now { flex:1;background:var(--primary-gradient);border:none;color:white;padding:13px;border-radius:12px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;font-size:0.95rem;transition:0.2s; }
        .btn-print-now:hover { opacity:0.9; }
        .btn-close-modal { background:#f1f5f9;border:none;color:var(--text-dark);padding:13px 18px;border-radius:12px;font-weight:700;cursor:pointer;transition:0.2s; }
        .btn-close-modal:hover { background:#e2e8f0; }

        /* ===== PRINT STYLES ===== */
        @media print {
            body * { visibility:hidden !important; }
            .etiket-wrap, .etiket-wrap * { visibility:visible !important; }
            .etiket-wrap { position:fixed;top:0;left:0;width:100%;max-width:100%;border-radius:0;box-shadow:none; }
            .tiket-modal { position:fixed;inset:0;background:white;display:flex !important;align-items:flex-start;justify-content:center;padding:0; }
            .modal-actions { display:none !important; }
        }
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
            <li><a href="/api/dashboard.php?page=bps_stat"><i class="bi bi-bar-chart-line-fill"></i> Statistik BPS</a></li>
            <li class="active"><a href="/api/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
        </ul>
    </nav>

    <div id="content">
        <div class="top-navbar">
            <div>
                <h4 class="mb-1 text-dark">Riwayat Transaksi 🧾</h4>
                <p class="text-muted small mb-0">Pantau dan cetak e-tiket dari semua pemesanan kamu.</p>
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

        <div class="card info-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Semua Tiket Pembelian</h5>
                    <p class="text-muted small mb-0">Klik "Cetak E-Tiket" untuk mengunduh atau mencetak tiket.</p>
                </div>
                <span class="badge bg-light text-muted border p-2 rounded-3 small fw-semibold">
                    <?= count($riwayat_pesanan) ?> Transaksi
                </span>
            </div>

            <?php if (empty($riwayat_pesanan)): ?>
            <div class="empty-state-box">
                <div class="empty-state-icon"><i class="bi bi-basket3"></i></div>
                <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Pesanan</h6>
                <p class="text-muted small mb-0 mx-auto" style="max-width:400px;">Anda belum melakukan pemesanan tiket apapun. Semua riwayat transaksi akan muncul di sini.</p>
                <a href="/api/destinasi.php" class="btn mt-4 px-4 py-2 rounded-pill fw-semibold shadow-sm text-white" style="background:var(--primary-gradient);border:none;">
                    <i class="bi bi-search me-1"></i> Cari Tiket Sekarang
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Tiket</th>
                            <th>Destinasi</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Total Bayar</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach ($riwayat_pesanan as $p):
                            $met = $metode_label[$p['metode_pembayaran']] ?? ['icon'=>'bi-credit-card','label'=>$p['metode_pembayaran'],'color'=>'#64748b'];
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td class="fw-bold text-primary">#TRF-<?= str_pad($p['id'],5,'0',STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($p['wisata']) ?></td>
                            <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                            <td><?= $p['jumlah'] ?> tiket</td>
                            <td>
                                <span style="color:<?= $met['color'] ?>;font-weight:600;font-size:0.85rem;">
                                    <i class="bi <?= $met['icon'] ?> me-1"></i><?= $met['label'] ?>
                                </span>
                            </td>
                            <td class="fw-bold text-success">Rp <?= number_format($p['total_bayar'],0,',','.') ?></td>
                            <td class="text-center">
                                <span class="badge-status" style="background:#dcfce7;color:#16a34a;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn-cetak" onclick='bukaTiket(<?= json_encode([
                                    "id"      => $p["id"],
                                    "wisata"  => $p["wisata"],
                                    "pemesan" => $p["nama_pemesan"],
                                    "tanggal" => date("d M Y", strtotime($p["tanggal"])),
                                    "jumlah"  => $p["jumlah"],
                                    "metode"  => $met["label"],
                                    "kode_promo" => $p["kode_promo"],
                                    "total"   => number_format($p["total_bayar"],0,",","."),
                                    "created" => date("d M Y H:i", strtotime($p["created_at"] ?? "now")),
                                ]) ?>)'>
                                    <i class="bi bi-printer-fill me-1"></i> E-Tiket
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5 opacity-50 small">
            <p>&copy; <?= $tahun_aktif ?> Tourify. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</div>

<!-- ========== MODAL E-TIKET ========== -->
<div class="tiket-modal" id="tiketModal" onclick="tutupJikaLuar(event)">
    <div class="etiket-wrap" id="etiketArea">
        <!-- Header -->
        <div class="etiket-header">
            <div class="etiket-logo">🧭 Tourify</div>
            <div class="etiket-subtitle">E-Tiket Resmi · Electronic Ticket</div>
        </div>

        <!-- Body -->
        <div class="etiket-body">
            <!-- Destinasi -->
            <div class="etiket-destinasi">
                <div class="dest-name" id="et_wisata">—</div>
                <div class="dest-date"><i class="bi bi-calendar3 me-1"></i><span id="et_tanggal">—</span></div>
            </div>

            <!-- Info Grid -->
            <div class="etiket-info-grid">
                <div class="etiket-info-item">
                    <div class="lbl">Nama Pemesan</div>
                    <div class="val" id="et_pemesan">—</div>
                </div>
                <div class="etiket-info-item">
                    <div class="lbl">Jumlah Tiket</div>
                    <div class="val" id="et_jumlah">—</div>
                </div>
                <div class="etiket-info-item">
                    <div class="lbl">Metode Bayar</div>
                    <div class="val" id="et_metode">—</div>
                </div>
                <div class="etiket-info-item">
                    <div class="lbl">Tanggal Beli</div>
                    <div class="val" id="et_created">—</div>
                </div>
            </div>

            <!-- Divider styled -->
            <div class="etiket-divider">
                <div class="circle left"></div>
                <div class="circle right"></div>
            </div>

            <!-- Total -->
            <div class="etiket-total">
                <div>
                    <div class="total-label">Total Pembayaran</div>
                    <div id="et_promo" style="font-size:0.78rem;color:#16a34a;font-weight:600;display:none;margin-top:2px;"></div>
                </div>
                <div class="total-val">Rp <span id="et_total">0</span></div>
            </div>

            <!-- Kode Tiket -->
            <div class="etiket-kode" id="et_kode">TRF-00000</div>

            <!-- QR Code untuk verifikasi petugas -->
            <div class="etiket-qr-wrap">
                <div id="et_qrcode"></div>
                <div class="etiket-qr-hint"><i class="bi bi-qr-code-scan me-1"></i>Tunjukkan QR ini untuk dipindai petugas</div>
            </div>

            <!-- Status -->
            <div class="etiket-status">
                <i class="bi bi-shield-fill-check fs-5"></i>
                E-Tiket Valid & Terverifikasi
            </div>
        </div>

        <!-- Footer -->
        <div class="etiket-footer">
            Tiket ini sah sebagai bukti pemesanan resmi Tourify.<br>
            Tunjukkan e-tiket ini kepada petugas di lokasi wisata.
        </div>

        <!-- Actions (tersembunyi saat print) -->
        <div class="modal-actions">
            <button class="btn-print-now" onclick="window.print()">
                <i class="bi bi-printer-fill me-2"></i>Cetak / Simpan PDF
            </button>
            <button class="btn-close-modal" onclick="tutupModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaTiket(data) {
    document.getElementById('et_wisata').textContent   = data.wisata;
    document.getElementById('et_tanggal').textContent  = data.tanggal;
    document.getElementById('et_pemesan').textContent  = data.pemesan;
    document.getElementById('et_jumlah').textContent   = data.jumlah + ' tiket';
    document.getElementById('et_metode').textContent   = data.metode;
    document.getElementById('et_created').textContent  = data.created;
    document.getElementById('et_total').textContent    = data.total;

    const kodeTiket = 'TRF-' + String(data.id).padStart(5,'0');
    document.getElementById('et_kode').textContent     = '#' + kodeTiket;

    const promoEl = document.getElementById('et_promo');
    if (data.kode_promo) {
        promoEl.textContent = '✓ Promo: ' + data.kode_promo;
        promoEl.style.display = 'block';
    } else {
        promoEl.style.display = 'none';
    }

    // Generate ulang QR code setiap kali tiket dibuka (kosongkan dulu wadahnya)
    const qrEl = document.getElementById('et_qrcode');
    qrEl.innerHTML = '';
    const qrData = 'TOURIFY|' + kodeTiket + '|' + data.wisata + '|' + data.pemesan + '|' + data.jumlah + '|' + data.tanggal;
    new QRCode(qrEl, {
        text: qrData,
        width: 160,
        height: 160,
        colorDark: '#1e293b',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    document.getElementById('tiketModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function tutupModal() {
    document.getElementById('tiketModal').classList.remove('open');
    document.body.style.overflow = '';
}

function tutupJikaLuar(e) {
    if (e.target === document.getElementById('tiketModal')) tutupModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModal(); });
</script>
</body>
</html>