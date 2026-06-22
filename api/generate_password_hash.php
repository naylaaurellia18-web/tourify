<?php
// ============================================================
//  GENERATE PASSWORD HASH — Tourify Admin
//  Akses: https://domain-kamu.vercel.app/api/generate_password_hash.php?key=RAHASIA
//  Ganti 'RAHASIA' di bawah dengan kata kunci milikmu sendiri.
//
//  PENTING: Hapus file ini dari server (atau ganti $secret_key)
//  setelah selesai dipakai, jangan dibiarkan publik tanpa proteksi
//  di server produksi.
// ============================================================

$secret_key = 'ganti-dengan-kata-kunci-rahasiamu';

if (($_GET['key'] ?? '') !== $secret_key) {
    http_response_code(403);
    die('Akses ditolak. Tambahkan ?key=KATA_KUNCI_RAHASIA yang benar di URL.');
}

$hasil = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $hasil = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generate Password Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; font-family:sans-serif; padding:40px; }
        .box { max-width:520px; margin:0 auto; background:white; padding:30px; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        textarea { width:100%; font-family:monospace; font-size:0.85rem; padding:10px; border-radius:8px; border:1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="box">
        <h5 class="fw-bold mb-3">Generate Password Hash Admin</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password Baru</label>
                <input type="text" name="password" class="form-control" placeholder="Contoh: saloka2026" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Generate Hash</button>
        </form>

        <?php if ($hasil): ?>
        <div class="mt-4">
            <label class="form-label small fw-semibold text-success">Hash hasil (copy ke kolom password di tabel admin):</label>
            <textarea rows="3" readonly onclick="this.select()"><?= htmlspecialchars($hasil) ?></textarea>
            <p class="small text-muted mt-2">Klik kotak teks di atas untuk select semua, lalu copy (Ctrl+C).</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>