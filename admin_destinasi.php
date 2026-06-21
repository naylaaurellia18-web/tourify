<?php
// ============================================================
//  ADMIN DESTINASI — Halaman Kontrol Semua Destinasi
//  Disesuaikan dengan struktur tabel asli:
//  id_destinasi, nama_destinasi, lokasi, deskripsi, harga,
//  gambar, stok_tiket, created_at
// ============================================================

// --- Proses Hapus ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM destinasi WHERE id_destinasi = $id");
    echo "<script>window.location='admin.php?page=destinasi';</script>"; exit();
}

// --- Proses Update Stok ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stok'])) {
    $id   = (int)$_POST['id'];
    $stok = (int)$_POST['stok'];
    mysqli_query($conn, "UPDATE destinasi SET stok_tiket = $stok WHERE id_destinasi = $id");
    echo "<script>window.location='admin.php?page=destinasi';</script>"; exit();
}

// --- Proses Tambah Destinasi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama      = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $harga     = (int)($_POST['harga'] ?? 0);
    $stok      = (int)($_POST['stok'] ?? 0);
    $lokasi    = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $gambar    = mysqli_real_escape_string($conn, $_POST['gambar'] ?? '');
    mysqli_query($conn, "INSERT INTO destinasi (nama_destinasi, deskripsi, harga, stok_tiket, lokasi, gambar)
                         VALUES ('$nama','$deskripsi',$harga,$stok,'$lokasi','$gambar')");
    echo "<script>window.location='admin.php?page=destinasi';</script>"; exit();
}

// --- Ambil semua destinasi ---
$destinasi_list = [];
$q = mysqli_query($conn, "SELECT * FROM destinasi ORDER BY id_destinasi ASC");
while ($row = mysqli_fetch_assoc($q)) $destinasi_list[] = $row;

// --- Mapping slug halaman khusus per destinasi (5 halaman tetap) ---
// Kalau nama_destinasi cocok dengan salah satu di bawah, tombol "Halaman Khusus" akan muncul.
$slug_map = [
    'Saloka Theme Park'                => 'destinasi_saloka.php',
    'Candi Borobudur'                  => 'destinasi_borobudur.php',
    'Taman Nasional Karimunjawa'       => 'destinasi_karimunjawa.php',
    'Rasamadu (The Heritage Palace)'   => 'destinasi_heritage.php',
    'Solo Safari'                      => 'destinasi_safari.php',
];
?>

<style>
    .dest-card { background:#fff; border-radius:16px; border:1.5px solid #e2e8f0; overflow:hidden; transition:all 0.2s; }
    .dest-card:hover { box-shadow:0 8px 30px rgba(0,0,0,0.08); transform:translateY(-2px); }
    .dest-img { width:100%; height:160px; object-fit:cover; background:#f1f5f9; }
    .dest-img-placeholder { width:100%; height:160px; background:linear-gradient(135deg,#f1f5f9,#e2e8f0); display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:2.5rem; }
    .stok-badge { font-size:0.72rem; font-weight:700; padding:4px 12px; border-radius:20px; }
    .modal-input { border-radius:10px; border:1.5px solid #e2e8f0; padding:10px 14px; font-size:0.92rem; width:100%; }
    .modal-input:focus { outline:none; border-color:#f37021; box-shadow:0 0 0 3px rgba(243,112,33,0.1); }
    .btn-orange { background:linear-gradient(135deg,#f37021,#ff8c42); color:white; border:none; border-radius:10px; padding:10px 20px; font-weight:700; cursor:pointer; transition:0.2s; }
    .btn-orange:hover { opacity:0.9; transform:translateY(-1px); }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">Kelola Destinasi</h4>
        <p class="text-muted small mb-0">Total <?= count($destinasi_list) ?> destinasi terdaftar</p>
    </div>
    <button class="btn-orange" data-bs-toggle="modal" data-bs-target="#mTambah">
        <i class="bi bi-plus-lg me-2"></i>Tambah Destinasi
    </button>
</div>

<!-- GRID DESTINASI -->
<div class="row g-3 mb-4">
    <?php foreach ($destinasi_list as $i => $d):
        $stok   = (int)($d['stok_tiket'] ?? 0);
        $sc     = $stok < 5 ? ['bg'=>'#fef2f2','c'=>'#ef4444','label'=>'Kritis'] : ($stok < 15 ? ['bg'=>'#fffbeb','c'=>'#d97706','label'=>'Terbatas'] : ['bg'=>'#f0fdf4','c'=>'#16a34a','label'=>'Tersedia']);
        $destId = (int)$d['id_destinasi'];
        $slugUrl = $slug_map[$d['nama_destinasi']] ?? null;
    ?>
    <div class="col-md-4">
        <div class="dest-card">
            <?php if (!empty($d['gambar'])): ?>
                <img src="<?= htmlspecialchars($d['gambar']) ?>" class="dest-img" alt="<?= htmlspecialchars($d['nama_destinasi']) ?>">
            <?php else: ?>
                <div class="dest-img-placeholder"><i class="bi bi-image"></i></div>
            <?php endif; ?>

            <div class="p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="fw-bold mb-0" style="color:#0f172a;"><?= htmlspecialchars($d['nama_destinasi'] ?? '-') ?></h6>
                    <span class="stok-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['c'] ?>;"><?= $sc['label'] ?></span>
                </div>
                <?php if (!empty($d['lokasi'])): ?>
                <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($d['lokasi']) ?></p>
                <?php endif; ?>
                <p class="small text-muted mb-3" style="line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= htmlspecialchars($d['deskripsi'] ?? 'Tidak ada deskripsi.') ?>
                </p>

                <div class="d-flex justify-content-between align-items-center border-top pt-2 mb-2">
                    <span class="fw-bold" style="color:#f37021;">Rp <?= number_format($d['harga'] ?? 0, 0, ',', '.') ?></span>
                    <span class="small text-muted"><?= $stok ?> tiket tersisa</span>
                </div>

                <!-- Update Stok inline -->
                <form method="POST" class="d-flex gap-2 mt-2">
                    <input type="hidden" name="id" value="<?= $destId ?>">
                    <input type="number" name="stok" value="<?= $stok ?>" min="0" class="modal-input" style="height:36px;padding:4px 10px;">
                    <button type="submit" name="update_stok" class="btn btn-sm fw-semibold" style="background:#fff3eb;color:#f37021;border-radius:8px;white-space:nowrap;">Update Stok</button>
                </form>

                <div class="d-flex gap-2 mt-2">
                    <a href="admin.php?page=destinasi_detail&id=<?= $destId ?>" class="btn btn-sm flex-fill fw-semibold" style="background:#eff6ff;color:#2563eb;border-radius:8px;">
                        <i class="bi bi-eye me-1"></i>Detail
                    </a>
                    <a href="admin.php?page=destinasi&hapus=<?= $destId ?>" class="btn btn-sm fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:8px;" onclick="return confirm('Yakin hapus destinasi ini?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
                <?php if ($slugUrl): ?>
                <a href="<?= $slugUrl ?>" target="_blank" class="btn btn-sm w-100 mt-2 fw-semibold" style="background:#f5f3ff;color:#7c3aed;border-radius:8px;">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Khusus
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($destinasi_list)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-geo-alt" style="font-size:3rem;opacity:0.3;"></i>
        <h6 class="mt-3">Belum ada destinasi</h6>
        <p class="small">Klik tombol "Tambah Destinasi" untuk mulai.</p>
    </div>
    <?php endif; ?>
</div>

<!-- TABEL RINGKAS -->
<div class="card border-0 p-4" style="border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05);">
    <h6 class="fw-bold mb-3" style="color:#0f172a;"><i class="bi bi-table me-2 text-muted"></i>Tabel Ringkasan</h6>
    <table class="table table-hover align-middle mb-0">
        <thead style="background:#f8fafc;">
            <tr>
                <th class="small fw-semibold text-muted border-0">#</th>
                <th class="small fw-semibold text-muted border-0">Nama Destinasi</th>
                <th class="small fw-semibold text-muted border-0">Lokasi</th>
                <th class="small fw-semibold text-muted border-0">Harga</th>
                <th class="small fw-semibold text-muted border-0">Stok</th>
                <th class="small fw-semibold text-muted border-0">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($destinasi_list as $i => $d):
                $stok = (int)($d['stok_tiket'] ?? 0);
            ?>
            <tr>
                <td class="text-muted small"><?= $i+1 ?></td>
                <td class="fw-semibold small"><?= htmlspecialchars($d['nama_destinasi'] ?? '-') ?></td>
                <td class="small text-muted"><?= htmlspecialchars($d['lokasi'] ?? '-') ?></td>
                <td class="small">Rp <?= number_format($d['harga'] ?? 0,0,',','.') ?></td>
                <td>
                    <span class="badge" style="background:<?= $stok<5?'#fef2f2':($stok<15?'#fffbeb':'#f0fdf4') ?>;color:<?= $stok<5?'#ef4444':($stok<15?'#d97706':'#16a34a') ?>;border-radius:20px;">
                        <?= $stok ?>
                    </span>
                </td>
                <td>
                    <a href="admin.php?page=destinasi_detail&id=<?= $d['id_destinasi'] ?>" class="btn btn-sm me-1" style="background:#eff6ff;color:#2563eb;border-radius:8px;font-size:0.78rem;">Detail</a>
                    <a href="admin.php?page=destinasi&hapus=<?= $d['id_destinasi'] ?>" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border-radius:8px;font-size:0.78rem;" onclick="return confirm('Hapus?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- MODAL TAMBAH DESTINASI -->
<div class="modal fade" id="mTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Destinasi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Nama Destinasi *</label>
                            <input type="text" name="nama" class="modal-input" required placeholder="Contoh: Pantai Parangtritis">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Lokasi</label>
                            <input type="text" name="lokasi" class="modal-input" placeholder="Contoh: Yogyakarta">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Harga Tiket (Rp) *</label>
                            <input type="number" name="harga" class="modal-input" required placeholder="25000">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-semibold mb-1">Stok Tiket *</label>
                            <input type="number" name="stok" class="modal-input" required placeholder="100">
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Deskripsi</label>
                            <textarea name="deskripsi" class="modal-input" rows="3" placeholder="Deskripsi singkat destinasi..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">URL Gambar</label>
                            <input type="text" name="gambar" class="modal-input" placeholder="https://example.com/gambar.jpg">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah" class="btn-orange">Simpan Destinasi</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>