<?php
// ============================================================
//  KONFIRMASI BAYAR — Tourify
//  User klik "Saya Sudah Bayar" -> status pesanan diubah dari
//  'menunggu_pembayaran' menjadi 'aktif'.
// ============================================================

include __DIR__ . '/session_db.php';
include __DIR__ . '/koneksi.php';

header('Content-Type: application/json');

$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;

if (!$nama_tampil || !$is_logged_in) {
    echo json_encode(['sukses' => false, 'pesan' => 'Sesi login tidak valid.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sukses' => false, 'pesan' => 'Metode request tidak valid.']);
    exit();
}

$id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
if ($id_pesanan <= 0) {
    echo json_encode(['sukses' => false, 'pesan' => 'ID pesanan tidak valid.']);
    exit();
}

$user_escaped = mysqli_real_escape_string($conn, $nama_tampil);

// Pastikan pesanan ini milik user yang login dan statusnya masih menunggu pembayaran
$q = mysqli_query($conn, "SELECT id, status FROM pesanan WHERE id = $id_pesanan AND username = '$user_escaped' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo json_encode(['sukses' => false, 'pesan' => 'Pesanan tidak ditemukan.']);
    exit();
}
$row = mysqli_fetch_assoc($q);

if ($row['status'] === 'menunggu_pembayaran') {
    mysqli_query($conn, "UPDATE pesanan SET status = 'aktif' WHERE id = $id_pesanan");
}
// Kalau status sudah 'aktif' atau lainnya, tidak perlu diubah lagi -- anggap sukses saja (idempotent)

echo json_encode(['sukses' => true, 'pesan' => 'Pesanan dikonfirmasi.']);
exit();