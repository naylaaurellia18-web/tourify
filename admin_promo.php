<?php
// ============================================================
//  ADMIN PROMO — Manajemen Voucher/Promo
//  Tabel: voucher (id, kode, diskon, potongan, keterangan, aktif)
// ============================================================

// --- Tambah voucher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_voucher'])) {
    $kode       = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode'] ?? '')));
    $tipe       = $_POST['tipe'] ?? 'diskon';
    $diskon     = $tipe === 'diskon'   ? (int)($_POST['nilai'] ?? 0) : 0;
    $potongan   = $tipe === 'potongan' ? (int)($_POST['nilai'] ?? 0) : 0;
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
    mysqli_query($conn, "INSERT INTO voucher (kode, diskon, potongan, keterangan, aktif)
                         VALUES ('$kode',$diskon,$potongan,'$keterangan',1)");
    echo "<script>window.location='admin.php?page=promo';</script>"; exit();
}

// --- Toggle aktif/nonaktif ---
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE voucher SET aktif = 1 - aktif WHERE id = $id");
    echo "<script>window.location='admin.php?page=promo';</script>"; exit();
}

// --- Hapus voucher ---
if (isset($_GET['hapus_voucher'])) {
    $id = (int)$_GET['hapus_voucher'];
    mysqli_query($conn, "DELETE FROM voucher WHERE id = $id");
    echo "<script>window.location='admin.php?page=promo';</script>"; exit();
}

// --- Ambil semua voucher ---
$voucher_list = [];
$q = mysqli_query($conn, "SELECT * FROM voucher ORDER BY id DESC");
if ($q) { while ($row = mysqli_fetch_assoc($q)) $voucher_list[] = $row; }
?>

<style>
    .modal-input { border-radius:10px; border:1.5px solid #e2e8f0; padding:10px 14px; font-size:0.92rem; width:100%; }
    .modal-input:focus { outline:none; border-color:#f37021; box-shadow:0 0 0 3px rgba(243,112,33,0.1); }
    .btn-orange { background:linear-gradient(135deg,#f37021,#ff8c42); color:white; border:none; border-radius:10px; padding:10px 20px; font-weight:700; cursor:pointer; transition:0.2s; }
    .btn-orange:hover { opacity:0.9; transform:translateY(-1px); }
    .voucher-row { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 20px; }
    .kode-pill { font-family:monospace; font-weight:800; letter-spacing:1px; background:#fff7ed; color:#f37021; border:1.5px dashed #f37021; border-radius:8px; padding:6px 14px; display:inline-block; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">Manajemen Promo</h4>
        <p class="text-muted small mb-0">Total <?= count($voucher_list) ?> voucher dibuat</p>
    </div>
    <button class="btn-orange" data-bs-toggle="modal" data-bs-target="#mVoucher">
        <i class="bi bi-plus-lg me-2"></i>Tambah Voucher
    </button>
</div>

<div class="d-flex flex-column gap-3">
    <?php foreach ($voucher_list as $v):
        $nilai = $v['diskon'] > 0
            ? 'Diskon ' . (int)$v['diskon'] . '%'
            : 'Potongan Rp ' . number_format($v['potongan'], 0, ',', '.');
        $aktif = (int)$v['aktif'] === 1;
    ?>
    <div class="voucher-row d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="kode-pill"><?= htmlspecialchars($v['kode']) ?></span>
            <div>
                <div class="fw-bold small" style="color:#0f172a;"><?= $nilai ?></div>
                <div class="small text-muted"><?= htmlspecialchars($v['keterangan'] ?? '-') ?></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background:<?= $aktif ? '#dcfce7' : '#fef2f2' ?>;color:<?= $aktif ? '#16a34a' : '#ef4444' ?>;border-radius:20px;padding:6px 14px;">
                <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
            </span>
            <a href="admin.php?page=promo&toggle=<?= $v['id'] ?>" class="btn btn-sm fw-semibold" style="background:#eff6ff;color:#2563eb;border-radius:8px;">
                <?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>
            </a>
            <a href="admin.php?page=promo&hapus_voucher=<?= $v['id'] ?>" class="btn btn-sm fw-semibold" style="background:#fef2f2;color:#ef4444;border-radius:8px;" onclick="return confirm('Hapus voucher ini?')">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($voucher_list)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-tags" style="font-size:3rem;opacity:0.3;"></i>
        <h6 class="mt-3">Belum ada voucher</h6>
        <p class="small">Klik "Tambah Voucher" untuk membuat promo baru.</p>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH VOUCHER -->
<div class="modal fade" id="mVoucher" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Voucher Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Kode Voucher *</label>
                            <input type="text" name="kode" class="modal-input" required placeholder="Contoh: TOURIFY10" style="text-transform:uppercase;">
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Tipe Diskon</label>
                            <select name="tipe" class="modal-input">
                                <option value="diskon">Persentase (%)</option>
                                <option value="potongan">Potongan Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Nilai *</label>
                            <input type="number" name="nilai" class="modal-input" required placeholder="Contoh: 10 (untuk 10%) atau 20000 (untuk Rp20.000)">
                        </div>
                        <div class="col-12">
                            <label class="small fw-semibold mb-1">Keterangan</label>
                            <textarea name="keterangan" class="modal-input" rows="2" placeholder="Contoh: Promo akhir tahun untuk semua destinasi"></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah_voucher" class="btn-orange">Simpan Voucher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>