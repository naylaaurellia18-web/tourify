<?php
// ============================================================
//  ADMIN DASHBOARD — Tourify
//  Semua query data dihitung di sini sebelum render HTML
// ============================================================

// --- STATS ---
$r_pendapatan = mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) AS val FROM pesanan");
$r_pesanan    = mysqli_query($conn, "SELECT COUNT(*) AS val FROM pesanan");
$r_pengguna   = mysqli_query($conn, "SELECT COUNT(*) AS val FROM users");
$r_destinasi  = mysqli_query($conn, "SELECT COUNT(*) AS val FROM destinasi");

$stats = [
    'pendapatan' => mysqli_fetch_assoc($r_pendapatan)['val'] ?? 0,
    'pesanan'    => mysqli_fetch_assoc($r_pesanan)['val']    ?? 0,
    'pengguna'   => mysqli_fetch_assoc($r_pengguna)['val']   ?? 0,
    'destinasi'  => mysqli_fetch_assoc($r_destinasi)['val']  ?? 0,
];

// --- GRAFIK PER BULAN (tahun berjalan) ---
$tahun_ini   = date('Y');
$grafik_tiket = array_fill(1, 12, 0);
$grafik_uang  = array_fill(1, 12, 0);

$qg = mysqli_query($conn, "
    SELECT MONTH(tanggal) AS bln, COUNT(*) AS jml_tiket, SUM(total_bayar) AS total
    FROM pesanan
    WHERE YEAR(tanggal) = '$tahun_ini'
    GROUP BY MONTH(tanggal)
");
if ($qg) {
    while ($g = mysqli_fetch_assoc($qg)) {
        $grafik_tiket[(int)$g['bln']] = (int)$g['jml_tiket'];
        $grafik_uang[(int)$g['bln']]  = (float)$g['total'];
    }
}
?>

<!-- =================== KARTU STATS =================== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">Dashboard Admin</h4>
        <p class="text-muted small mb-0">Ringkasan performa Tourify — <?= date('d F Y') ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 p-4 h-100" style="background:linear-gradient(135deg,#f37021,#ff8c42);border-radius:16px;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-white opacity-75 small fw-semibold mb-1">Total Pendapatan</p>
                    <h4 class="text-white fw-bold mb-0">Rp <?= number_format($stats['pendapatan'],0,',','.') ?></h4>
                </div>
                <div style="background:rgba(255,255,255,0.2);border-radius:12px;padding:10px;">
                    <i class="bi bi-cash-coin text-white fs-4"></i>
                </div>
            </div>
            <p class="text-white opacity-60 small mt-3 mb-0">Akumulasi seluruh pesanan</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 p-4 h-100" style="background:linear-gradient(135deg,#10b981,#059669);border-radius:16px;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-white opacity-75 small fw-semibold mb-1">Total Pesanan</p>
                    <h4 class="text-white fw-bold mb-0"><?= number_format($stats['pesanan'],0,',','.') ?> Tiket</h4>
                </div>
                <div style="background:rgba(255,255,255,0.2);border-radius:12px;padding:10px;">
                    <i class="bi bi-ticket-perforated text-white fs-4"></i>
                </div>
            </div>
            <p class="text-white opacity-60 small mt-3 mb-0">Tiket berhasil dipesan</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 p-4 h-100" style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-white opacity-75 small fw-semibold mb-1">Pengguna</p>
                    <h4 class="text-white fw-bold mb-0"><?= number_format($stats['pengguna'],0,',','.') ?> Akun</h4>
                </div>
                <div style="background:rgba(255,255,255,0.2);border-radius:12px;padding:10px;">
                    <i class="bi bi-people text-white fs-4"></i>
                </div>
            </div>
            <p class="text-white opacity-60 small mt-3 mb-0">Akun terdaftar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 p-4 h-100" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:16px;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-white opacity-75 small fw-semibold mb-1">Destinasi</p>
                    <h4 class="text-white fw-bold mb-0"><?= number_format($stats['destinasi'],0,',','.') ?> Lokasi</h4>
                </div>
                <div style="background:rgba(255,255,255,0.2);border-radius:12px;padding:10px;">
                    <i class="bi bi-geo-alt text-white fs-4"></i>
                </div>
            </div>
            <p class="text-white opacity-60 small mt-3 mb-0">Destinasi aktif</p>
        </div>
    </div>
</div>

<!-- =================== GRAFIK =================== -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 p-4 h-100" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-bar-chart-fill text-warning me-2"></i>Jumlah Tiket per Bulan (<?= $tahun_ini ?>)</h6>
            <canvas id="c1" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 p-4 h-100" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Total Pendapatan per Bulan (<?= $tahun_ini ?>)</h6>
            <canvas id="c2" height="120"></canvas>
        </div>
    </div>
</div>

<!-- =================== RIWAYAT + STOK =================== -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card border-0 p-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:#0f172a;"><i class="bi bi-clock-history me-2 text-muted"></i>Pesanan Terbaru</h6>
                <a href="riwayat_pesanan.php" class="small text-decoration-none" style="color:#f37021;">Lihat Semua →</a>
            </div>
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="small fw-semibold text-muted border-0">Username</th>
                        <th class="small fw-semibold text-muted border-0">Destinasi</th>
                        <th class="small fw-semibold text-muted border-0">Total</th>
                        <th class="small fw-semibold text-muted border-0">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $riwayat = mysqli_query($conn, "SELECT * FROM pesanan ORDER BY id DESC LIMIT 5");
                    if ($riwayat && mysqli_num_rows($riwayat) > 0):
                        while($r = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;background:#fff3eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#f37021;font-weight:700;font-size:0.8rem;">
                                        <?= strtoupper(substr($r['username'],0,1)) ?>
                                    </div>
                                    <span class="fw-semibold small"><?= htmlspecialchars($r['username']) ?></span>
                                </div>
                            </td>
                            <td class="small"><?= htmlspecialchars($r['wisata']) ?></td>
                            <td class="small fw-bold" style="color:#f37021;">Rp <?= number_format($r['total_bayar'],0,',','.') ?></td>
                            <td><span class="badge" style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:0.7rem;">Selesai</span></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4 small">Belum ada pesanan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-4 h-100" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
            <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-ticket me-2 text-muted"></i>Monitoring Stok Tiket</h6>
            <?php 
            $q_stok = mysqli_query($conn, "SELECT id_destinasi, nama_destinasi, stok_tiket FROM destinasi ORDER BY stok_tiket ASC");
            if ($q_stok && mysqli_num_rows($q_stok) > 0):
                while($s = mysqli_fetch_assoc($q_stok)):
                    $stok = (int)($s['stok_tiket'] ?? 0);
                    $color = $stok < 5 ? '#ef4444' : ($stok < 15 ? '#f59e0b' : '#10b981');
                    $bg    = $stok < 5 ? '#fef2f2' : ($stok < 15 ? '#fffbeb' : '#f0fdf4');
            ?>
                <div class="d-flex justify-content-between align-items-center rounded-3 px-3 py-2 mb-2" style="background:<?= $bg ?>;">
                    <span class="small fw-semibold" style="color:#1e293b;"><?= htmlspecialchars($s['nama_destinasi']) ?></span>
                    <span class="small fw-bold" style="color:<?= $color ?>;"><?= $stok ?> sisa</span>
                </div>
            <?php endwhile; else: ?>
                <p class="small text-muted text-center pt-3">Tidak ada destinasi</p>
            <?php endif; ?>
            <div class="mt-3 pt-2 border-top">
                <a href="admin.php?page=destinasi" class="small text-decoration-none fw-semibold" style="color:#f37021;">Kelola Destinasi →</a>
            </div>
        </div>
    </div>
</div>

<!-- =================== TABEL SEMUA DESTINASI =================== -->
<div class="card border-0 p-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:#0f172a;"><i class="bi bi-geo-alt me-2 text-muted"></i>Semua Destinasi</h6>
        <a href="admin.php?page=destinasi" class="btn btn-sm fw-semibold" style="background:#fff3eb;color:#f37021;border-radius:8px;">+ Kelola Destinasi</a>
    </div>
    <table class="table table-hover align-middle mb-0">
        <thead style="background:#f8fafc;">
            <tr>
                <th class="small fw-semibold text-muted border-0">Nama Destinasi</th>
                <th class="small fw-semibold text-muted border-0">Stok</th>
                <th class="small fw-semibold text-muted border-0">Harga</th>
                <th class="small fw-semibold text-muted border-0">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $dest = mysqli_query($conn, "SELECT * FROM destinasi ORDER BY id ASC");
            if ($dest && mysqli_num_rows($dest) > 0):
                while($d = mysqli_fetch_assoc($dest)):
                    $stok = (int)($d['stok_tiket'] ?? 0);
            ?>
            <tr>
                <td class="fw-semibold small"><?= htmlspecialchars($d['nama_destinasi'] ?? 'Tanpa Nama') ?></td>
                <td>
                    <span class="badge" style="background:<?= $stok < 10 ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $stok < 10 ? '#ef4444' : '#16a34a' ?>;border-radius:20px;">
                        <?= $stok ?> tiket
                    </span>
                </td>
                <td class="small">Rp <?= number_format($d['harga'] ?? 0, 0, ',', '.') ?></td>
                <td>
                    <a href="admin.php?page=destinasi_detail&id=<?= $d['id_destinasi'] ?>" class="btn btn-sm me-1" style="background:#eff6ff;color:#2563eb;border-radius:8px;font-size:0.78rem;">Detail</a>
                    <a href="admin.php?page=destinasi&hapus=<?= $d['id_destinasi'] ?>" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border-radius:8px;font-size:0.78rem;" onclick="return confirm('Yakin hapus?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center text-muted py-4 small">Belum ada destinasi</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- =================== GRAFIK SCRIPT =================== -->
<script>
const labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const tiketData = <?= json_encode(array_values($grafik_tiket)) ?>;
const uangData  = <?= json_encode(array_values($grafik_uang)) ?>;

new Chart(document.getElementById('c1'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Jumlah Tiket', data: tiketData, backgroundColor: 'rgba(243,112,33,0.8)', borderRadius: 8, borderSkipped: false }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color:'#f1f5f9' } }, x: { grid: { display: false } } } }
});
new Chart(document.getElementById('c2'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Pendapatan (Rp)', data: uangData, backgroundColor: 'rgba(37,99,235,0.8)', borderRadius: 8, borderSkipped: false }] },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color:'#f1f5f9' }, ticks: { callback: v => 'Rp '+(v/1000)+'K' } },
            x: { grid: { display: false } }
        }
    }
});
</script>