<?php
// ============================================================
//  ADMIN DESTINASI DETAIL — Detail & Edit Per-Destinasi
//  Dipanggil via: admin.php?page=destinasi_detail&id=X
//  (file fisik: admin_destinasi_detail.php)
//
//  FITUR DI FILE INI (berlaku otomatis untuk SEMUA destinasi,
//  karena ini satu file yang dipakai bersama lewat admin.php):
//   - Grafik tiket per bulan (sudah ada sebelumnya)
//   - Grafik PENDAPATAN per bulan (BARU)
//   - Alert stok menipis (BARU)
//   - Statistik rata-rata tiket per transaksi (BARU)
//   - Filter riwayat pesanan by tanggal/bulan (BARU)
//   - Search nama pemesan di riwayat (BARU)
//   - Paginasi riwayat pesanan (BARU, ganti LIMIT 10)
//   - Export riwayat pesanan ke CSV (BARU)
//   - Kelola Promo khusus destinasi ini (BARU)
// ============================================================

$dest_id = (int)($_GET['id'] ?? 0);
if ($dest_id === 0) {
    echo "<div class='alert alert-danger'>ID destinasi tidak valid.</div>"; exit();
}

// --- Proses Update Data Destinasi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_destinasi'])) {
    $nama      = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $harga     = (int)($_POST['harga'] ?? 0);
    $stok      = (int)($_POST['stok'] ?? 0);
    $lokasi    = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $gambar    = mysqli_real_escape_string($conn, $_POST['gambar'] ?? '');
    mysqli_query($conn, "UPDATE destinasi SET 
        nama_destinasi='$nama', deskripsi='$deskripsi', harga=$harga,
        stok_tiket=$stok, lokasi='$lokasi', gambar='$gambar'
        WHERE id_destinasi=$dest_id");
    $sukses_edit = true;
}

// --- Pastikan kolom id_destinasi ada di tabel voucher (promo per-destinasi) ---
function pastikan_kolom_voucher_destinasi($conn) {
    $cek = mysqli_query($conn, "SHOW COLUMNS FROM voucher LIKE 'id_destinasi'");
    if ($cek && mysqli_num_rows($cek) === 0) {
        mysqli_query($conn, "ALTER TABLE voucher ADD COLUMN id_destinasi INT NULL DEFAULT NULL");
    }
}
if (isset($conn)) pastikan_kolom_voucher_destinasi($conn);

// --- Proses Tambah Promo khusus destinasi ini ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_promo_dest'])) {
    $kode       = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode'] ?? '')));
    $tipe       = $_POST['tipe'] ?? 'diskon';
    $diskon     = $tipe === 'diskon'   ? (int)($_POST['nilai'] ?? 0) : 0;
    $potongan   = $tipe === 'potongan' ? (int)($_POST['nilai'] ?? 0) : 0;
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    // Generate ID manual (TiDB Serverless tidak mendukung AUTO_INCREMENT)
    $r_max_v = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) AS max_id FROM voucher");
    $max_v   = $r_max_v ? (int)mysqli_fetch_assoc($r_max_v)['max_id'] : 0;
    $new_v_id = $max_v + 1;

    mysqli_query($conn, "INSERT INTO voucher (id, kode, diskon, potongan, keterangan, aktif, id_destinasi)
                         VALUES ($new_v_id,'$kode',$diskon,$potongan,'$keterangan',1,$dest_id)");
    header("Location: admin.php?page=destinasi_detail&id=$dest_id&promo_msg=tambah");
    exit();
}
// --- Toggle aktif/nonaktif promo destinasi ---
if (isset($_GET['toggle_promo'])) {
    $id = (int)$_GET['toggle_promo'];
    mysqli_query($conn, "UPDATE voucher SET aktif = 1 - aktif WHERE id = $id AND id_destinasi = $dest_id");
    header("Location: admin.php?page=destinasi_detail&id=$dest_id");
    exit();
}
// --- Hapus promo destinasi ---
if (isset($_GET['hapus_promo'])) {
    $id = (int)$_GET['hapus_promo'];
    mysqli_query($conn, "DELETE FROM voucher WHERE id = $id AND id_destinasi = $dest_id");
    header("Location: admin.php?page=destinasi_detail&id=$dest_id");
    exit();
}

// --- Ambil data destinasi ---
$q = mysqli_query($conn, "SELECT * FROM destinasi WHERE id_destinasi = $dest_id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo "<div class='alert alert-danger'>Destinasi tidak ditemukan.</div>"; exit();
}
$d = mysqli_fetch_assoc($q);
$stok  = (int)($d['stok_tiket'] ?? 0);
$harga = (int)($d['harga'] ?? 0);
$nama_dest = mysqli_real_escape_string($conn, $d['nama_destinasi'] ?? '');

// --- Promo khusus destinasi ini ---
$promo_list = [];
$q_promo = mysqli_query($conn, "SELECT * FROM voucher WHERE id_destinasi = $dest_id ORDER BY id DESC");
if ($q_promo) { while ($p = mysqli_fetch_assoc($q_promo)) $promo_list[] = $p; }

// ============================================================
//  FILTER + SEARCH + PAGINASI RIWAYAT PESANAN
// ============================================================
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;     // 0 = semua bulan
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;     // 0 = semua tahun
$cari_nama    = trim($_GET['cari'] ?? '');
$halaman      = max(1, (int)($_GET['hal'] ?? 1));
$per_halaman  = 10;
$offset       = ($halaman - 1) * $per_halaman;

$where_extra = "WHERE wisata='$nama_dest'";
if ($filter_bulan > 0) $where_extra .= " AND MONTH(tanggal) = $filter_bulan";
if ($filter_tahun > 0) $where_extra .= " AND YEAR(tanggal) = $filter_tahun";
if ($cari_nama !== '') {
    $cari_escaped = mysqli_real_escape_string($conn, $cari_nama);
    $where_extra .= " AND (username LIKE '%$cari_escaped%' OR nama_pemesan LIKE '%$cari_escaped%')";
}

// Total baris untuk paginasi (mengikuti filter yang sama)
$q_count = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM pesanan $where_extra");
$total_baris = $q_count ? (int)mysqli_fetch_assoc($q_count)['jml'] : 0;
$total_halaman = max(1, (int)ceil($total_baris / $per_halaman));
if ($halaman > $total_halaman) { $halaman = $total_halaman; $offset = ($halaman - 1) * $per_halaman; }

$q_psr = mysqli_query($conn, "SELECT * FROM pesanan $where_extra ORDER BY id DESC LIMIT $per_halaman OFFSET $offset");
$pesanan_list = [];
if ($q_psr) { while ($r = mysqli_fetch_assoc($q_psr)) $pesanan_list[] = $r; }

// --- Statistik khusus destinasi ini (TOTAL KESELURUHAN, tidak kepengaruh filter) ---
$q_total = mysqli_query($conn, "SELECT COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS rev, COALESCE(SUM(jumlah),0) AS total_tiket FROM pesanan WHERE wisata='$nama_dest'");
$stat_dest = mysqli_fetch_assoc($q_total);
$rata_rata_tiket = ((int)$stat_dest['jml'] > 0) ? round(((int)$stat_dest['total_tiket']) / ((int)$stat_dest['jml']), 1) : 0;

// --- Grafik tiket per bulan untuk destinasi ini ---
$tahun_ini = date('Y');
$grafik_dest = array_fill(1, 12, 0);
$grafik_pendapatan = array_fill(1, 12, 0);
$qg = mysqli_query($conn, "SELECT MONTH(tanggal) AS bln, COUNT(*) AS jml, SUM(total_bayar) AS rev FROM pesanan WHERE wisata='$nama_dest' AND YEAR(tanggal)='$tahun_ini' GROUP BY MONTH(tanggal)");
if ($qg) {
    while ($g = mysqli_fetch_assoc($qg)) {
        $grafik_dest[(int)$g['bln']] = (int)$g['jml'];
        $grafik_pendapatan[(int)$g['bln']] = (float)$g['rev'];
    }
}

$sc = $stok < 5 ? ['bg'=>'#fef2f2','c'=>'#ef4444','label'=>'Stok Kritis'] : ($stok < 15 ? ['bg'=>'#fffbeb','c'=>'#d97706','label'=>'Stok Terbatas'] : ['bg'=>'#f0fdf4','c'=>'#16a34a','label'=>'Stok Tersedia']);

// --- Daftar tahun untuk dropdown filter (berdasarkan data yang ada) ---
$tahun_tersedia = [];
$qy = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal) AS th FROM pesanan WHERE wisata='$nama_dest' ORDER BY th DESC");
if ($qy) { while ($y = mysqli_fetch_assoc($qy)) $tahun_tersedia[] = (int)$y['th']; }
if (empty($tahun_tersedia)) $tahun_tersedia[] = (int)$tahun_ini;

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>

<style>
.detail-input { border-radius:10px; border:1.5px solid #e2e8f0; padding:10px 14px; font-size:0.92rem; width:100%; transition:border 0.2s; }
.detail-input:focus { outline:none; border-color:#f37021; box-shadow:0 0 0 3px rgba(243,112,33,0.1); }
.btn-orange { background:linear-gradient(135deg,#f37021,#ff8c42); color:white; border:none; border-radius:10px; padding:10px 24px; font-weight:700; cursor:pointer; transition:0.2s; }
.btn-orange:hover { opacity:0.9; }
.stat-mini { background:#fff; border-radius:14px; border:1.5px solid #e2e8f0; padding:16px 20px; }
.kode-pill { font-family:monospace; font-weight:800; letter-spacing:1px; background:#fff7ed; color:#f37021; border:1.5px dashed #f37021; border-radius:8px; padding:6px 14px; display:inline-block; }
.promo-row { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:14px 18px; }
.page-link-custom { border-radius:8px; padding:6px 14px; font-size:0.85rem; text-decoration:none; font-weight:600; }
</style>

<!-- Breadcrumb -->
<nav class="mb-3">
    <ol class="breadcrumb small mb-0">
        <?php if ($admin_role === 'super'): ?>
        <li class="breadcrumb-item"><a href="admin.php?page=dashboard" class="text-decoration-none" style="color:#f37021;">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="admin.php?page=destinasi" class="text-decoration-none" style="color:#f37021;">Destinasi</a></li>
        <?php else: ?>
        <li class="breadcrumb-item"><span class="text-muted">Destinasi Saya</span></li>
        <?php endif; ?>
        <li class="breadcrumb-item active text-muted"><?= htmlspecialchars($d['nama_destinasi']) ?></li>
    </ol>
</nav>

<?php if (!empty($sukses_edit)): ?>
<div class="alert border-0 d-flex align-items-center gap-2 mb-3" style="background:#f0fdf4;color:#16a34a;border-radius:12px;">
    <i class="bi bi-check-circle-fill"></i> Data destinasi berhasil diperbarui!
</div>
<?php endif; ?>

<?php if (($_GET['promo_msg'] ?? '') === 'tambah'): ?>
<div class="alert border-0 d-flex align-items-center gap-2 mb-3" style="background:#f0fdf4;color:#16a34a;border-radius:12px;">
    <i class="bi bi-check-circle-fill"></i> Promo berhasil ditambahkan!
</div>
<?php endif; ?>

<!-- =================== ALERT STOK MENIPIS =================== -->
<?php if ($stok < 5): ?>
<div class="alert border-0 d-flex align-items-center gap-2 mb-3" style="background:#fef2f2;color:#ef4444;border-radius:12px;border-left:4px solid #ef4444 !important;">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Stok kritis!</strong> Tersisa <?= $stok ?> tiket untuk <?= htmlspecialchars($d['nama_destinasi']) ?>. Segera tambah stok agar tidak kehabisan.
    </div>
</div>
<?php elseif ($stok < 15): ?>
<div class="alert border-0 d-flex align-items-center gap-2 mb-3" style="background:#fffbeb;color:#d97706;border-radius:12px;border-left:4px solid #d97706 !important;">
    <i class="bi bi-exclamation-circle-fill fs-5"></i>
    <div>
        <strong>Stok terbatas.</strong> Tersisa <?= $stok ?> tiket. Pertimbangkan untuk menambah stok dalam waktu dekat.
    </div>
</div>
<?php endif; ?>

<!-- Header Destinasi -->
<div class="card border-0 mb-4 overflow-hidden" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,0.07);">
    <div class="row g-0">
        <?php if (!empty($d['gambar'])): ?>
        <div class="col-md-4">
            <img src="<?= htmlspecialchars($d['gambar']) ?>" style="width:100%;height:100%;min-height:220px;object-fit:cover;" alt="">
        </div>
        <div class="col-md-8 p-4">
        <?php else: ?>
        <div class="col-12 p-4">
        <?php endif; ?>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:#0f172a;"><?= htmlspecialchars($d['nama_destinasi']) ?></h4>
                    <?php if (!empty($d['lokasi'])): ?>
                    <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($d['lokasi']) ?></p>
                    <?php endif; ?>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($d['deskripsi'] ?? '-') ?></p>
                    <span class="badge me-2" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;border-radius:20px;padding:6px 14px;font-size:0.8rem;">
                        <?= $sc['label'] ?> (<?= $stok ?> tiket)
                    </span>
                    <span class="fw-bold" style="color:#f37021;font-size:1.1rem;">Rp <?= number_format($harga,0,',','.') ?>/tiket</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#mEdit" style="background:#fff3eb;color:#f37021;border-radius:10px;">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </button>
                    <?php if ($admin_role === 'super'): ?>
                    <a href="admin.php?page=destinasi&hapus=<?= $dest_id ?>" class="btn btn-sm fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:10px;" onclick="return confirm('Hapus destinasi ini?')">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Mini -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Total Pesanan</p>
            <h5 class="fw-bold mb-0" style="color:#0f172a;"><?= number_format($stat_dest['jml'],0,',','.') ?> Transaksi</h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Total Pendapatan</p>
            <h5 class="fw-bold mb-0" style="color:#f37021;">Rp <?= number_format($stat_dest['rev'],0,',','.') ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Rata-rata Tiket/Transaksi</p>
            <h5 class="fw-bold mb-0" style="color:#2563eb;"><?= $rata_rata_tiket ?> Tiket</h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Stok Tersisa</p>
            <h5 class="fw-bold mb-0" style="color:<?= $sc['c'] ?>;"><?= $stok ?> Tiket</h5>
        </div>
    </div>
</div>

<!-- Grafik: Tiket & Pendapatan per Bulan -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 p-4 h-100" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-bar-chart me-2 text-muted"></i>Tiket Terjual per Bulan (<?= $tahun_ini ?>)</h6>
            <canvas id="cDest" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 p-4 h-100" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Pendapatan per Bulan (<?= $tahun_ini ?>)</h6>
            <canvas id="cPendapatan" height="120"></canvas>
        </div>
    </div>
</div>

<!-- =================== MANAJEMEN PROMO DESTINASI =================== -->
<div class="card border-0 p-4 mb-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0" style="color:#0f172a;"><i class="bi bi-tags-fill me-2 text-muted"></i>Promo — <?= htmlspecialchars($d['nama_destinasi']) ?></h6>
        <button class="btn-orange" style="padding:8px 18px;font-size:0.85rem;" data-bs-toggle="modal" data-bs-target="#mPromoDest">
            <i class="bi bi-plus-lg me-1"></i>Tambah Promo
        </button>
    </div>

    <?php if (empty($promo_list)): ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-tags" style="font-size:2.2rem;opacity:0.3;"></i>
            <p class="small mb-0 mt-2">Belum ada promo khusus untuk destinasi ini.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($promo_list as $p):
                $nilai_p = $p['diskon'] > 0
                    ? 'Diskon ' . (int)$p['diskon'] . '%'
                    : 'Potongan Rp ' . number_format($p['potongan'], 0, ',', '.');
                $aktif_p = (int)$p['aktif'] === 1;
            ?>
            <div class="promo-row d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="kode-pill"><?= htmlspecialchars($p['kode']) ?></span>
                    <div>
                        <div class="fw-bold small" style="color:#0f172a;"><?= $nilai_p ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($p['keterangan'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background:<?= $aktif_p ? '#dcfce7' : '#fef2f2' ?>;color:<?= $aktif_p ? '#16a34a' : '#ef4444' ?>;border-radius:20px;padding:5px 12px;font-size:0.72rem;">
                        <?= $aktif_p ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                    <a href="admin.php?page=destinasi_detail&id=<?= $dest_id ?>&toggle_promo=<?= $p['id'] ?>" class="btn btn-sm fw-semibold" style="background:#eff6ff;color:#2563eb;border-radius:8px;font-size:0.78rem;">
                        <?= $aktif_p ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </a>
                    <a href="admin.php?page=destinasi_detail&id=<?= $dest_id ?>&hapus_promo=<?= $p['id'] ?>" class="btn btn-sm fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:8px;font-size:0.78rem;" onclick="return confirm('Hapus promo ini?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- =================== RIWAYAT PESANAN: FILTER + SEARCH =================== -->
<div class="card border-0 p-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0" style="color:#0f172a;"><i class="bi bi-receipt me-2 text-muted"></i>Riwayat Pesanan — <?= htmlspecialchars($d['nama_destinasi']) ?></h6>
        <a href="admin_export_pesanan.php?id=<?= $dest_id ?>&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&cari=<?= urlencode($cari_nama) ?>" class="btn btn-sm fw-semibold" style="background:#f0fdf4;color:#16a34a;border-radius:8px;">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
        </a>
    </div>

    <!-- Form Filter -->
    <form method="GET" class="row g-2 mb-3 align-items-end">
        <input type="hidden" name="page" value="destinasi_detail">
        <input type="hidden" name="id" value="<?= $dest_id ?>">
        <div class="col-md-3 col-6">
            <label class="small fw-semibold mb-1 text-muted">Bulan</label>
            <select name="bulan" class="detail-input">
                <option value="0">Semua Bulan</option>
                <?php for ($b = 1; $b <= 12; $b++): ?>
                <option value="<?= $b ?>" <?= $filter_bulan === $b ? 'selected' : '' ?>><?= $nama_bulan[$b] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="small fw-semibold mb-1 text-muted">Tahun</label>
            <select name="tahun" class="detail-input">
                <option value="0">Semua Tahun</option>
                <?php foreach ($tahun_tersedia as $ty): ?>
                <option value="<?= $ty ?>" <?= $filter_tahun === $ty ? 'selected' : '' ?>><?= $ty ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-12">
            <label class="small fw-semibold mb-1 text-muted">Cari Nama Pemesan</label>
            <input type="text" name="cari" class="detail-input" placeholder="Ketik username / nama..." value="<?= htmlspecialchars($cari_nama) ?>">
        </div>
        <div class="col-md-2 col-12 d-flex gap-2">
            <button type="submit" class="btn-orange w-100" style="padding:10px 14px;font-size:0.85rem;"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
        <?php if ($filter_bulan > 0 || $filter_tahun > 0 || $cari_nama !== ''): ?>
        <div class="col-12">
            <a href="admin.php?page=destinasi_detail&id=<?= $dest_id ?>" class="small text-decoration-none" style="color:#ef4444;"><i class="bi bi-x-circle me-1"></i>Reset Filter</a>
        </div>
        <?php endif; ?>
    </form>

    <table class="table table-hover align-middle mb-0">
        <thead style="background:#f8fafc;">
            <tr>
                <th class="small fw-semibold text-muted border-0">Pemesan</th>
                <th class="small fw-semibold text-muted border-0">Tanggal Kunjungan</th>
                <th class="small fw-semibold text-muted border-0">Jumlah</th>
                <th class="small fw-semibold text-muted border-0">Metode</th>
                <th class="small fw-semibold text-muted border-0">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pesanan_list)):
                foreach ($pesanan_list as $r): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;background:#fff3eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#f37021;font-weight:700;font-size:0.75rem;">
                                <?= strtoupper(substr($r['username'],0,1)) ?>
                            </div>
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($r['username']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($r['nama_pemesan'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= date('d M Y', strtotime($r['tanggal'] ?? 'today')) ?></td>
                    <td class="small"><?= (int)($r['jumlah'] ?? 1) ?> tiket</td>
                    <td class="small text-muted"><?= htmlspecialchars(str_replace('_',' ',ucfirst($r['metode_pembayaran'] ?? '-'))) ?></td>
                    <td class="small fw-bold" style="color:#f37021;">Rp <?= number_format($r['total_bayar'],0,',','.') ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4 small">Tidak ada pesanan yang cocok dengan filter ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginasi -->
    <?php if ($total_halaman > 1):
        $base_qs = "page=destinasi_detail&id=$dest_id&bulan=$filter_bulan&tahun=$filter_tahun&cari=" . urlencode($cari_nama);
    ?>
    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
        <span class="small text-muted">Halaman <?= $halaman ?> dari <?= $total_halaman ?> (<?= $total_baris ?> data)</span>
        <div class="d-flex gap-2">
            <?php if ($halaman > 1): ?>
                <a href="admin.php?<?= $base_qs ?>&hal=<?= $halaman-1 ?>" class="page-link-custom" style="background:#f8fafc;color:#1e293b;">← Sebelumnya</a>
            <?php endif; ?>
            <?php
            $start_p = max(1, $halaman - 2);
            $end_p   = min($total_halaman, $halaman + 2);
            for ($p = $start_p; $p <= $end_p; $p++): ?>
                <a href="admin.php?<?= $base_qs ?>&hal=<?= $p ?>" class="page-link-custom" style="background:<?= $p === $halaman ? '#f37021' : '#f8fafc' ?>;color:<?= $p === $halaman ? '#fff' : '#1e293b' ?>;"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($halaman < $total_halaman): ?>
                <a href="admin.php?<?= $base_qs ?>&hal=<?= $halaman+1 ?>" class="page-link-custom" style="background:#f8fafc;color:#1e293b;">Selanjutnya →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL EDIT DESTINASI -->
<div class="modal fade" id="mEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Destinasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Nama Destinasi</label>
                            <input type="text" name="nama" class="detail-input" value="<?= htmlspecialchars($d['nama_destinasi'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Lokasi</label>
                            <input type="text" name="lokasi" class="detail-input" value="<?= htmlspecialchars($d['lokasi'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Harga Tiket (Rp)</label>
                            <input type="number" name="harga" class="detail-input" value="<?= $harga ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Stok Tiket</label>
                            <input type="number" name="stok" class="detail-input" value="<?= $stok ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Deskripsi</label>
                            <textarea name="deskripsi" class="detail-input" rows="3"><?= htmlspecialchars($d['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">URL Gambar</label>
                            <input type="text" name="gambar" class="detail-input" value="<?= htmlspecialchars($d['gambar'] ?? '') ?>">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="edit_destinasi" class="btn-orange">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH PROMO DESTINASI -->
<div class="modal fade" id="mPromoDest" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Promo — <?= htmlspecialchars($d['nama_destinasi']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Kode Voucher *</label>
                            <input type="text" name="kode" class="detail-input" required placeholder="Contoh: SALOKA10" style="text-transform:uppercase;">
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Tipe Diskon</label>
                            <select name="tipe" class="detail-input">
                                <option value="diskon">Persentase (%)</option>
                                <option value="potongan">Potongan Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Nilai *</label>
                            <input type="number" name="nilai" class="detail-input" required placeholder="Contoh: 10 (untuk 10%) atau 20000 (untuk Rp20.000)">
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Keterangan</label>
                            <textarea name="keterangan" class="detail-input" rows="2" placeholder="Contoh: Promo khusus pengunjung baru"></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah_promo_dest" class="btn-orange">Simpan Promo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('cDest'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{
            label: 'Tiket Terjual',
            data: <?= json_encode(array_values($grafik_dest)) ?>,
            backgroundColor: 'rgba(243,112,33,0.8)',
            borderRadius: 8, borderSkipped: false
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color:'#f1f5f9' }, ticks: { precision:0 } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('cPendapatan'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode(array_values($grafik_pendapatan)) ?>,
            borderColor: 'rgba(16,185,129,1)',
            backgroundColor: 'rgba(16,185,129,0.15)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: 'rgba(16,185,129,1)',
            pointRadius: 4,
            borderWidth: 2.5
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color:'#f1f5f9' }, ticks: { callback: v => 'Rp '+(v/1000)+'K' } },
            x: { grid: { display: false } }
        }
    }
});
</script>