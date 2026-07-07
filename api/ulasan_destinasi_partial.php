<?php
// ============================================================
//  ULASAN WIDGET (partial) — dipakai di halaman detail destinasi
//  publik (destinasi_borobudur.php, destinasi_heritage.php, dst).
//
//  Wajib di-include di 2 tempat pada file destinasi:
//   1) BAGIAN LOGIKA — include SEBELUM ada output HTML apa pun
//      (butuh $conn dan $id_destinasi_halaman sudah terisi).
//      Ini yang menangani submit ulasan (INSERT/UPDATE) & hapus.
//   2) BAGIAN TAMPILAN — include di dalam HTML, di tempat ulasan
//      ingin ditampilkan. Cukup include file yang sama; variabel
//      $ULASAN_PARTIAL_MODE membedakan bagian mana yang dijalankan.
// ============================================================

if (!isset($ULASAN_PARTIAL_MODE)) $ULASAN_PARTIAL_MODE = 'logic';

if ($ULASAN_PARTIAL_MODE === 'logic') {

    $username_login = $_SESSION['username'] ?? '';
    $nama_login      = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $username_login;
    $sudah_login     = !empty($_SESSION['login_user']);
    $pesan_ulasan    = '';

    // --- Kirim / perbarui ulasan ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan_dest']) && $sudah_login && $id_destinasi_halaman !== null && isset($conn)) {
        $rating   = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar'] ?? ''));
        $uname    = mysqli_real_escape_string($conn, $username_login);
        $nama_pemberi_escaped = mysqli_real_escape_string($conn, $nama_login);

        $cek = mysqli_query($conn, "SELECT id FROM ulasan WHERE id_destinasi=$id_destinasi_halaman AND username='$uname' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            mysqli_query($conn, "UPDATE ulasan SET rating=$rating, komentar='$komentar', created_at=NOW() WHERE id_destinasi=$id_destinasi_halaman AND username='$uname'");
        } else {
            $r_max_u = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) AS max_id FROM ulasan");
            $max_u   = $r_max_u ? (int)mysqli_fetch_assoc($r_max_u)['max_id'] : 0;
            $new_u_id = $max_u + 1;
            mysqli_query($conn, "INSERT INTO ulasan (id, id_destinasi, username, nama_pemberi, rating, komentar) VALUES ($new_u_id,$id_destinasi_halaman,'$uname','$nama_pemberi_escaped',$rating,'$komentar')");
        }

        // Post/Redirect/Get: supaya ulasan baru langsung muncul & tidak terkirim ulang saat halaman di-refresh
        $base_url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: $base_url?ulasan_terkirim=1#ulasan");
        exit;
    }

    // --- Hapus ulasan milik sendiri ---
    if (isset($_GET['hapus_ulasan']) && $sudah_login && isset($conn)) {
        $id_ulasan_hapus = (int)$_GET['hapus_ulasan'];
        $uname = mysqli_real_escape_string($conn, $username_login);
        mysqli_query($conn, "DELETE FROM ulasan WHERE id=$id_ulasan_hapus AND username='$uname'");
        $base_url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: $base_url#ulasan");
        exit;
    }

    if (isset($_GET['ulasan_terkirim'])) $pesan_ulasan = 'Terima kasih, ulasan kamu berhasil disimpan!';

    // --- Ambil semua ulasan untuk destinasi ini (bisa dilihat SEMUA pengunjung, tanpa login) ---
    $ulasan_list_dest = [];
    $ulasan_saya_dest = null;
    $avg_rating_dest  = 0;
    if ($id_destinasi_halaman !== null && isset($conn)) {
        $qu = mysqli_query($conn, "SELECT * FROM ulasan WHERE id_destinasi=$id_destinasi_halaman ORDER BY created_at DESC");
        if ($qu) {
            while ($u = mysqli_fetch_assoc($qu)) {
                $ulasan_list_dest[] = $u;
                if ($sudah_login && $u['username'] === $username_login) $ulasan_saya_dest = $u;
            }
        }
        if (count($ulasan_list_dest) > 0) {
            $avg_rating_dest = array_sum(array_column($ulasan_list_dest, 'rating')) / count($ulasan_list_dest);
        }
    }

} else { // ---------------- MODE TAMPILAN ----------------
?>
<div class="info-card mt-4" id="ulasan">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="fw-bold mb-0"><i class="bi bi-chat-square-text-fill me-2" style="color:var(--accent);"></i>Ulasan Pengunjung</h5>
        <div class="small text-muted fw-semibold">
            <?php if (count($ulasan_list_dest) > 0): ?>
                <span style="color:#f59e0b;">★</span> <?= number_format($avg_rating_dest, 1) ?> · <?= count($ulasan_list_dest) ?> ulasan
            <?php else: ?>
                Belum ada ulasan
            <?php endif; ?>
        </div>
    </div>

    <?php if ($pesan_ulasan): ?>
    <div class="alert alert-success border-0 rounded-3 mb-3"><?= htmlspecialchars($pesan_ulasan) ?></div>
    <?php endif; ?>

    <?php if ($sudah_login): ?>
    <form method="POST" class="mb-4 pb-4 border-bottom">
        <input type="hidden" name="kirim_ulasan_dest" value="1">
        <label class="small fw-semibold mb-2 d-block"><?= $ulasan_saya_dest ? 'Edit ulasan kamu' : 'Beri ulasan untuk destinasi ini' ?></label>
        <div class="star-input mb-2">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="rt<?= $i ?>" value="<?= $i ?>" <?= ($ulasan_saya_dest && (int)$ulasan_saya_dest['rating'] === $i) ? 'checked' : ($i == 5 && !$ulasan_saya_dest ? 'checked' : '') ?>>
            <label for="rt<?= $i ?>"><i class="bi bi-star-fill"></i></label>
            <?php endfor; ?>
        </div>
        <textarea name="komentar" rows="2" class="form-control mb-2" placeholder="Ceritakan pengalamanmu di sini..." style="border-radius:10px;"><?= htmlspecialchars($ulasan_saya_dest['komentar'] ?? '') ?></textarea>
        <div class="d-flex gap-2">
            <button type="submit" class="btn-beli" style="padding:9px 20px;font-size:0.9rem;">
                <i class="bi bi-send me-1"></i><?= $ulasan_saya_dest ? 'Perbarui Ulasan' : 'Kirim Ulasan' ?>
            </button>
            <?php if ($ulasan_saya_dest): ?>
            <a href="?hapus_ulasan=<?= $ulasan_saya_dest['id'] ?>#ulasan" class="btn btn-outline-danger" style="border-radius:10px;font-size:0.9rem;" onclick="return confirm('Hapus ulasan kamu?')">
                <i class="bi bi-trash me-1"></i>Hapus
            </a>
            <?php endif; ?>
        </div>
    </form>
    <?php else: ?>
    <div class="alert alert-light border small mb-4" style="border-radius:10px;">
        <i class="bi bi-info-circle me-1"></i>
        <a href="/login.php">Masuk</a> untuk memberi ulasan destinasi ini.
    </div>
    <?php endif; ?>

    <?php if (empty($ulasan_list_dest)): ?>
    <p class="text-muted small mb-0 text-center py-3">Belum ada ulasan. Jadilah yang pertama memberi ulasan!</p>
    <?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($ulasan_list_dest as $u): ?>
        <div class="border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="d-flex align-items-center gap-2">
                    <div class="ulasan-avatar"><?= strtoupper(substr($u['nama_pemberi'] ?: $u['username'], 0, 1)) ?></div>
                    <div>
                        <div class="fw-semibold small">
                            <?= htmlspecialchars($u['nama_pemberi'] ?: $u['username']) ?>
                            <?= ($sudah_login && $u['username'] === $username_login) ? '<span class="badge" style="background:#fff3eb;color:var(--accent);font-size:0.65rem;">Kamu</span>' : '' ?>
                        </div>
                        <div style="color:#f59e0b;font-size:0.8rem;"><?= str_repeat('★', (int)$u['rating']) ?><?= str_repeat('☆', 5 - (int)$u['rating']) ?></div>
                    </div>
                </div>
                <span class="text-muted" style="font-size:0.75rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
            </div>
            <?php if (!empty($u['komentar'])): ?>
            <p class="mb-0 small mt-1" style="color:var(--text-dark);"><?= nl2br(htmlspecialchars($u['komentar'])) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php
}
