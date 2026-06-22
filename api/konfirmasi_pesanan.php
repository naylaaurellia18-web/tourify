<?php
// Fix session untuk Vercel serverless
include __DIR__ . '/session_db.php';

include __DIR__ . '/koneksi.php';

$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;
if (!$nama_tampil || !$is_logged_in) { header("Location: /api/login.php"); exit(); }

$wisata     = isset($_GET['wisata']) ? strip_tags($_GET['wisata']) : "Destinasi";
$harga_asli = isset($_GET['harga']) ? (int)$_GET['harga'] : 0;
$kode_url   = isset($_GET['kode']) ? $_GET['kode'] : '';

if ($harga_asli <= 0) {
    echo "<script>alert('Harga tidak valid.'); window.location.href='/api/destinasi.php';</script>";
    exit();
}

$tgl_min = date('Y-m-d', strtotime('+1 day'));

// Ambil semua voucher aktif untuk validasi di PHP
$voucher_list = [];
if (isset($conn)) {
    $q = mysqli_query($conn, "SELECT * FROM voucher WHERE aktif = 1");
    while ($v = mysqli_fetch_assoc($q)) {
        $voucher_list[strtoupper($v['kode'])] = $v;
    }
}
$voucher_json = json_encode($voucher_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan | Tourify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #f37021; --primary-dark: #d4601a; }

        body {
            background: linear-gradient(135deg, #fff7ed 0%, #f8fafc 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        h1,h2,h3,h4,h5,h6 { font-family: 'Plus Jakarta Sans', sans-serif; }

        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--primary); font-weight: 600; font-size: 0.9rem;
            text-decoration: none; padding: 8px 16px;
            background: white; border-radius: 100px;
            border: 1px solid #ffe4cc;
            transition: all 0.2s;
        }
        .back-btn:hover { background: var(--primary); color: white; }

        .order-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(243,112,33,0.08);
            border: 1px solid rgba(243,112,33,0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f37021, #ff8c42);
            padding: 28px 35px;
            color: white;
        }

        .card-body-custom { padding: 32px 35px; }

        .form-label { font-weight: 600; font-size: 0.82rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }

        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243,112,33,0.12);
        }

        .destinasi-box {
            background: #fff7ed;
            border: 1.5px solid #ffe4cc;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .destinasi-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }

        /* Promo Box */
        .promo-box {
            background: #f8fafc;
            border: 1.5px dashed #cbd5e1;
            border-radius: 14px;
            padding: 16px;
            transition: border-color 0.2s;
        }
        .promo-box.valid   { border-color: #16a34a; background: #f0fdf4; }
        .promo-box.invalid { border-color: #dc2626; background: #fef2f2; }

        .promo-input-wrap { display: flex; gap: 10px; }
        .promo-input-wrap input {
            flex: 1; border: 1.5px solid #e2e8f0;
            border-radius: 10px; padding: 10px 14px;
            font-family: monospace; font-size: 0.95rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .promo-input-wrap input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,112,33,0.12); }
        .btn-cek-promo {
            background: var(--primary); color: white;
            border: none; border-radius: 10px;
            padding: 10px 20px; font-weight: 700; font-size: 0.88rem;
            cursor: pointer; white-space: nowrap; transition: 0.2s;
        }
        .btn-cek-promo:hover { background: var(--primary-dark); }

        .promo-feedback { font-size: 0.83rem; margin-top: 8px; display: none; font-weight: 600; }
        .promo-feedback.show { display: flex; align-items: center; gap: 6px; }
        .promo-feedback.ok   { color: #16a34a; }
        .promo-feedback.err  { color: #dc2626; }

        /* Ringkasan Harga */
        .price-summary {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .price-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 0.92rem; }
        .price-row.divider { border-top: 1px dashed #e2e8f0; margin-top: 8px; padding-top: 14px; }
        .price-row.total { font-size: 1.1rem; }
        .diskon-row { color: #16a34a; }
        .diskon-badge { background: #dcfce7; color: #16a34a; border-radius: 6px; padding: 2px 8px; font-size: 0.75rem; font-weight: 700; }

        .btn-bayar {
            background: linear-gradient(135deg, #f37021, #ff8c42);
            border: none; color: white; font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 16px; font-size: 1rem; border-radius: 14px;
            box-shadow: 0 8px 24px rgba(243,112,33,0.3);
            transition: all 0.2s; letter-spacing: 0.3px;
        }
        .btn-bayar:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(243,112,33,0.4); color: white; }
        .btn-bayar:active { transform: translateY(0); }

        .metode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .metode-item { position: relative; }
        .metode-item input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .metode-label {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 6px;
            padding: 14px 8px; border-radius: 12px;
            border: 1.5px solid #e2e8f0; cursor: pointer;
            transition: all 0.2s; font-size: 0.8rem; font-weight: 600;
            color: #64748b; text-align: center;
        }
        .metode-label i { font-size: 1.4rem; }
        .metode-item input:checked + .metode-label {
            border-color: var(--primary);
            background: #fff7ed;
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="container py-5" style="max-width: 580px;">

    <!-- Back Button -->
    <div class="mb-4">
        <a href="/api/destinasi.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Kembali ke Destinasi
        </a>
    </div>

    <div class="order-card">
        <!-- Header -->
        <div class="card-header-custom">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">🎫</div>
                <div>
                    <h5 class="mb-0 fw-bold">Konfirmasi Pesanan</h5>
                    <p class="mb-0 small" style="opacity:0.8;">Isi data pemesanan tiket wisatamu</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="card-body-custom">
            <form action="/api/proses_pembayaran.php" method="POST" id="formPesanan">
                <input type="hidden" name="wisata"      value="<?= htmlspecialchars($wisata) ?>">
                <input type="hidden" name="harga_dasar" id="harga_dasar" value="<?= $harga_asli ?>">
                <input type="hidden" name="kode"        id="kode_input"  value="<?= htmlspecialchars($kode_url) ?>">
                <input type="hidden" name="potongan_nominal" id="potongan_nominal" value="0">
                <input type="hidden" name="total_bayar_final" id="total_bayar_final" value="<?= $harga_asli ?>">

                <!-- Destinasi -->
                <div class="mb-4">
                    <label class="form-label">Destinasi Wisata</label>
                    <div class="destinasi-box">
                        <div class="destinasi-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($wisata) ?></div>
                            <div class="small text-muted">Harga dasar: Rp <?= number_format($harga_asli,0,',','.') ?> / tiket</div>
                        </div>
                    </div>
                </div>

                <!-- Nama Pemesan -->
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap Pemesan</label>
                    <input type="text" name="nama_pemesan" class="form-control" placeholder="Masukkan nama sesuai KTP" required>
                </div>

                <!-- Tanggal & Jumlah -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Tanggal Kunjungan</label>
                        <input type="date" name="tanggal" class="form-control" min="<?= $tgl_min ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Jumlah Tiket</label>
                        <input type="number" name="jumlah" id="qty" class="form-control" value="1" min="1" max="20" oninput="hitungTotal()" required>
                    </div>
                </div>

                <!-- Metode Pembayaran -->
                <div class="mb-4">
                    <label class="form-label">Metode Pembayaran</label>
                    <div class="metode-grid">
                        <div class="metode-item">
                            <input type="radio" name="metode" id="m1" value="transfer_bank" required>
                            <label class="metode-label" for="m1"><i class="bi bi-bank2"></i>Transfer Bank</label>
                        </div>
                        <div class="metode-item">
                            <input type="radio" name="metode" id="m2" value="e_wallet">
                            <label class="metode-label" for="m2"><i class="bi bi-phone-fill"></i>E-Wallet</label>
                        </div>
                        <div class="metode-item">
                            <input type="radio" name="metode" id="m3" value="qris">
                            <label class="metode-label" for="m3"><i class="bi bi-qr-code-scan"></i>QRIS</label>
                        </div>
                    </div>
                </div>

                <!-- Kode Promo -->
                <div class="mb-4">
                    <label class="form-label">Kode Promo <span class="text-muted fw-normal">(opsional)</span></label>
                    <div class="promo-box" id="promoBox">
                        <div class="promo-input-wrap">
                            <input type="text" id="kode_promo_input" placeholder="Masukkan kode voucher..." value="<?= htmlspecialchars($kode_url) ?>">
                            <button type="button" class="btn-cek-promo" onclick="cekPromo()">Terapkan</button>
                        </div>
                        <div class="promo-feedback" id="promoFeedback"></div>
                    </div>
                    <!-- Link ke halaman promo -->
                    <div class="mt-2 small text-muted">
                        Belum punya kode? <a href="/api/promo.php" class="fw-semibold text-decoration-none" style="color:var(--primary);">Lihat promo tersedia →</a>
                    </div>
                </div>

                <!-- Ringkasan Harga -->
                <div class="price-summary mb-4">
                    <div class="price-row">
                        <span class="text-muted">Harga tiket</span>
                        <span class="fw-semibold" id="label_harga_satuan">Rp <?= number_format($harga_asli,0,',','.') ?></span>
                    </div>
                    <div class="price-row">
                        <span class="text-muted">Jumlah tiket</span>
                        <span class="fw-semibold" id="label_qty">1 tiket</span>
                    </div>
                    <div class="price-row">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold" id="label_subtotal">Rp <?= number_format($harga_asli,0,',','.') ?></span>
                    </div>
                    <div class="price-row diskon-row" id="row_diskon" style="display:none!important;">
                        <span>Diskon <span class="diskon-badge" id="label_kode_badge"></span></span>
                        <span class="fw-bold" id="label_potongan">- Rp 0</span>
                    </div>
                    <div class="price-row divider total">
                        <span class="fw-bold text-dark">Total Bayar</span>
                        <span class="fw-bold" id="label_total" style="color:var(--primary);font-size:1.2rem;">Rp <?= number_format($harga_asli,0,',','.') ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-bayar w-100">
                    <i class="bi bi-lock-fill me-2"></i>BAYAR SEKARANG 🚀
                </button>

            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 opacity-50">&copy; <?= date('Y') ?> Tourify. Pembayaran dijamin aman.</p>
</div>

<script>
const HARGA_DASAR = <?= $harga_asli ?>;
const VOUCHERS    = <?= $voucher_json ?>;

let diskonPersen   = 0;
let potonganNominal = 0;

function hitungTotal() {
    const qty      = Math.max(1, parseInt(document.getElementById('qty').value) || 1);
    const subtotal = HARGA_DASAR * qty;
    
    let potongan = 0;
    if (diskonPersen > 0) {
        potongan = Math.round(subtotal * diskonPersen / 100);
    } else if (potonganNominal > 0) {
        potongan = Math.min(potonganNominal, subtotal);
    }

    const total = Math.max(0, subtotal - potongan);

    // Update label ringkasan
    document.getElementById('label_harga_satuan').textContent = 'Rp ' + HARGA_DASAR.toLocaleString('id-ID');
    document.getElementById('label_qty').textContent           = qty + ' tiket';
    document.getElementById('label_subtotal').textContent      = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('label_total').textContent         = 'Rp ' + total.toLocaleString('id-ID');
    
    // Tampilkan baris diskon
    const rowDiskon = document.getElementById('row_diskon');
    if (potongan > 0) {
        rowDiskon.style.setProperty('display', 'flex', 'important');
        document.getElementById('label_potongan').textContent = '- Rp ' + potongan.toLocaleString('id-ID');
    } else {
        rowDiskon.style.setProperty('display', 'none', 'important');
    }

    // Simpan ke hidden input untuk dikirim ke proses_pembayaran.php
    document.getElementById('potongan_nominal').value  = potongan;
    document.getElementById('total_bayar_final').value = total;

    return total;
}

function cekPromo() {
    const inputEl   = document.getElementById('kode_promo_input');
    const kode      = inputEl.value.trim().toUpperCase();
    const feedback  = document.getElementById('promoFeedback');
    const promoBox  = document.getElementById('promoBox');
    const badgeEl   = document.getElementById('label_kode_badge');

    feedback.className = 'promo-feedback';

    if (!kode) {
        // Reset diskon jika dikosongkan
        diskonPersen = 0; potonganNominal = 0;
        document.getElementById('kode_input').value = '';
        promoBox.className = 'promo-box';
        feedback.className = 'promo-feedback show err';
        feedback.innerHTML = '<i class="bi bi-x-circle-fill"></i> Masukkan kode promo terlebih dahulu.';
        hitungTotal();
        return;
    }

    if (VOUCHERS[kode]) {
        const v = VOUCHERS[kode];
        diskonPersen    = parseFloat(v.diskon)   || 0;
        potonganNominal = parseInt(v.potongan)   || 0;
        
        document.getElementById('kode_input').value = kode;
        badgeEl.textContent = kode;
        promoBox.className  = 'promo-box valid';

        const infoDiskon = diskonPersen > 0
            ? `Diskon ${diskonPersen}% berhasil diterapkan! 🎉`
            : `Potongan Rp ${potonganNominal.toLocaleString('id-ID')} berhasil diterapkan! 🎉`;

        feedback.className = 'promo-feedback show ok';
        feedback.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${infoDiskon}`;
    } else {
        diskonPersen = 0; potonganNominal = 0;
        document.getElementById('kode_input').value = '';
        promoBox.className  = 'promo-box invalid';
        feedback.className  = 'promo-feedback show err';
        feedback.innerHTML  = '<i class="bi bi-x-circle-fill"></i> Kode promo tidak valid atau sudah tidak aktif.';
    }

    hitungTotal();
}

// Jika ada kode dari URL (dari halaman destinasi), langsung cek otomatis
window.addEventListener('DOMContentLoaded', () => {
    const kodeAwal = document.getElementById('kode_promo_input').value.trim();
    if (kodeAwal) cekPromo();
    hitungTotal();
});
</script>
</body>
</html>