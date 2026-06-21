<?php
// ============================================================
//  ADMIN DESTINASI DETAIL — Detail & Edit Per-Destinasi
//  Dipanggil via: admin.php?page=destinasi_detail&id=X
//  (file fisik: admin_destinasi_detail.php)
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

// --- Ambil data destinasi ---
$q = mysqli_query($conn, "SELECT * FROM destinasi WHERE id_destinasi = $dest_id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo "<div class='alert alert-danger'>Destinasi tidak ditemukan.</div>"; exit();
}
$d = mysqli_fetch_assoc($q);
$stok  = (int)($d['stok_tiket'] ?? 0);
$harga = (int)($d['harga'] ?? 0);

// --- Pesanan untuk destinasi ini ---
$nama_dest = mysqli_real_escape_string($conn, $d['nama_destinasi'] ?? '');
$q_psr = mysqli_query($conn, "SELECT * FROM pesanan WHERE wisata='$nama_dest' ORDER BY id DESC LIMIT 10");
$pesanan_list = [];
if ($q_psr) { while ($r = mysqli_fetch_assoc($q_psr)) $pesanan_list[] = $r; }

// --- Statistik khusus destinasi ini ---
$q_total = mysqli_query($conn, "SELECT COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS rev FROM pesanan WHERE wisata='$nama_dest'");
$stat_dest = mysqli_fetch_assoc($q_total);

// --- Grafik tiket per bulan untuk destinasi ini ---
$tahun_ini = date('Y');
$grafik_dest = array_fill(1, 12, 0);
$qg = mysqli_query($conn, "SELECT MONTH(tanggal) AS bln, COUNT(*) AS jml FROM pesanan WHERE wisata='$nama_dest' AND YEAR(tanggal)='$tahun_ini' GROUP BY MONTH(tanggal)");
if ($qg) { while ($g = mysqli_fetch_assoc($qg)) $grafik_dest[(int)$g['bln']] = (int)$g['jml']; }

$sc = $stok < 5 ? ['bg'=>'#fef2f2','c'=>'#ef4444','label'=>'Stok Kritis'] : ($stok < 15 ? ['bg'=>'#fffbeb','c'=>'#d97706','label'=>'Stok Terbatas'] : ['bg'=>'#f0fdf4','c'=>'#16a34a','label'=>'Stok Tersedia']);
?>

<style>
.detail-input { border-radius:10px; border:1.5px solid #e2e8f0; padding:10px 14px; font-size:0.92rem; width:100%; transition:border 0.2s; }
.detail-input:focus { outline:none; border-color:#f37021; box-shadow:0 0 0 3px rgba(243,112,33,0.1); }
.btn-orange { background:linear-gradient(135deg,#f37021,#ff8c42); color:white; border:none; border-radius:10px; padding:10px 24px; font-weight:700; cursor:pointer; transition:0.2s; }
.btn-orange:hover { opacity:0.9; }
.stat-mini { background:#fff; border-radius:14px; border:1.5px solid #e2e8f0; padding:16px 20px; }
</style>

<!-- Breadcrumb -->
<nav class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="admin.php?page=dashboard" class="text-decoration-none" style="color:#f37021;">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="admin.php?page=destinasi" class="text-decoration-none" style="color:#f37021;">Destinasi</a></li>
        <li class="breadcrumb-item active text-muted"><?= htmlspecialchars($d['nama_destinasi']) ?></li>
    </ol>
</nav>

<?php if (!empty($sukses_edit)): ?>
<div class="alert border-0 d-flex align-items-center gap-2 mb-3" style="background:#f0fdf4;color:#16a34a;border-radius:12px;">
    <i class="bi bi-check-circle-fill"></i> Data destinasi berhasil diperbarui!
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
                    <a href="admin.php?page=destinasi&hapus=<?= $dest_id ?>" class="btn btn-sm fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:10px;" onclick="return confirm('Hapus destinasi ini?')">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Mini -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Total Pesanan</p>
            <h5 class="fw-bold mb-0" style="color:#0f172a;"><?= number_format($stat_dest['jml'],0,',','.') ?> Transaksi</h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Total Pendapatan</p>
            <h5 class="fw-bold mb-0" style="color:#f37021;">Rp <?= number_format($stat_dest['rev'],0,',','.') ?></h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-mini">
            <p class="small text-muted mb-1 fw-semibold">Stok Tersisa</p>
            <h5 class="fw-bold mb-0" style="color:<?= $sc['c'] ?>;"><?= $stok ?> Tiket</h5>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="card border-0 p-4 mb-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-bar-chart me-2 text-muted"></i>Tiket Terjual per Bulan (<?= $tahun_ini ?>)</h6>
    <canvas id="cDest" height="80"></canvas>
</div>

<!-- Riwayat Pesanan untuk Destinasi Ini -->
<div class="card border-0 p-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-receipt me-2 text-muted"></i>Riwayat Pesanan — <?= htmlspecialchars($d['nama_destinasi']) ?></h6>
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
                <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada pesanan untuk destinasi ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
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
</script>