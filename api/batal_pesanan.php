<?php
// ============================================================
//  BATAL PESANAN — Tourify
//  User membatalkan tiket miliknya sendiri.
//  Saat dibatalkan: status pesanan -> 'dibatalkan', dan
//  stok_tiket destinasi terkait dikembalikan (rollback).
// ============================================================

include __DIR__ . '/session_db.php';
include __DIR__ . '/koneksi.php';

header('Content-Type: application/json');

$nama_tampil  = $_SESSION['user'] ?? $_SESSION['username'] ?? null;
$is_logged_in = $_SESSION['login_user'] ?? false;

if (!$nama_tampil || !$is_logged_in) {
    echo json_encode(['sukses' => false, 'pesan' => 'Sesi login tidak valid, silakan login ulang.']);
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

// --- Ambil data pesanan, pastikan milik user yang login ---
$q = mysqli_query($conn, "SELECT * FROM pesanan WHERE id = $id_pesanan AND username = '$user_escaped' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo json_encode(['sukses' => false, 'pesan' => 'Pesanan tidak ditemukan atau bukan milik Anda.']);
    exit();
}
$pesanan = mysqli_fetch_assoc($q);

// --- Validasi: tidak bisa batal kalau sudah dibatalkan ---
if (($pesanan['status'] ?? 'aktif') === 'dibatalkan') {
    echo json_encode(['sukses' => false, 'pesan' => 'Pesanan ini sudah dibatalkan sebelumnya.']);
    exit();
}

// --- Validasi: tidak bisa batal kalau tanggal kunjungan sudah lewat ---
$tanggal_kunjungan = $pesanan['tanggal'] ?? null;
if ($tanggal_kunjungan && strtotime($tanggal_kunjungan) < strtotime(date('Y-m-d'))) {
    echo json_encode(['sukses' => false, 'pesan' => 'Tiket dengan tanggal kunjungan yang sudah lewat tidak dapat dibatalkan.']);
    exit();
}

// --- Update status pesanan jadi dibatalkan ---
$ok = mysqli_query($conn, "UPDATE pesanan SET status = 'dibatalkan' WHERE id = $id_pesanan");
if (!$ok) {
    echo json_encode(['sukses' => false, 'pesan' => 'Gagal membatalkan pesanan: ' . mysqli_error($conn)]);
    exit();
}

// --- Kembalikan stok tiket ke destinasi terkait ---
$wisata_escaped = mysqli_real_escape_string($conn, $pesanan['wisata']);
$jumlah_tiket   = (int)($pesanan['jumlah'] ?? 1);

$qd = mysqli_query($conn, "SELECT id_destinasi FROM destinasi WHERE nama_destinasi = '$wisata_escaped' LIMIT 1");
if ($qd && mysqli_num_rows($qd) > 0) {
    $rowd = mysqli_fetch_assoc($qd);
    $id_destinasi = (int)$rowd['id_destinasi'];
    mysqli_query($conn, "UPDATE destinasi SET stok_tiket = stok_tiket + $jumlah_tiket WHERE id_destinasi = $id_destinasi");
}

echo json_encode(['sukses' => true, 'pesan' => 'Pesanan berhasil dibatalkan. Stok tiket telah dikembalikan.']);
exit();