<?php
// ============================================================
//  ADMIN EXPORT PESANAN — Export Riwayat Pesanan ke CSV
//  Dipanggil dari:
//   - admin_destinasi_detail.php (per destinasi, pakai ?id=X)
//   - admin_dashboard.php        (semua pesanan, tanpa ?id)
//  Mendukung filter yang sama: bulan, tahun, cari nama pemesan
// ============================================================
include __DIR__ . '/session_db.php';
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3DA4d4bPMVCSuDy.root", "mRSgOTH6qk79AieJ", "tourify-db", 4000, NULL, MYSQLI_CLIENT_SSL);
mysqli_set_charset($conn, "utf8mb4");

// --- Wajib login sebagai admin ---
if (empty($_SESSION['admin_id'])) {
    header("Location: /api/login.php");
    exit();
}

$admin_role      = $_SESSION['admin_role'];
$admin_destinasi = $_SESSION['admin_destinasi'];

$dest_id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$cari_nama    = trim($_GET['cari'] ?? '');

// --- Pengaman akses: admin destinasi cuma boleh export datanya sendiri ---
if ($admin_role === 'destinasi') {
    if ($dest_id === 0 || $dest_id !== (int)$admin_destinasi) {
        $dest_id = (int)$admin_destinasi;
    }
}

$where = [];
$nama_file = 'riwayat_pesanan';

if ($dest_id > 0) {
    $q_dest = mysqli_query($conn, "SELECT nama_destinasi FROM destinasi WHERE id_destinasi = $dest_id LIMIT 1");
    if ($q_dest && mysqli_num_rows($q_dest) > 0) {
        $nama_dest = mysqli_fetch_assoc($q_dest)['nama_destinasi'];
        $nama_dest_escaped = mysqli_real_escape_string($conn, $nama_dest);
        $where[] = "wisata = '$nama_dest_escaped'";
        $nama_file = 'pesanan_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $nama_dest);
    }
}
if ($filter_bulan > 0) $where[] = "MONTH(tanggal) = " . (int)$filter_bulan;
if ($filter_tahun > 0) $where[] = "YEAR(tanggal) = " . (int)$filter_tahun;
if ($cari_nama !== '') {
    $cari_escaped = mysqli_real_escape_string($conn, $cari_nama);
    $where[] = "(username LIKE '%$cari_escaped%' OR nama_pemesan LIKE '%$cari_escaped%')";
}

$sql = "SELECT * FROM pesanan";
if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

// --- Header agar browser mengunduh sebagai file CSV ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nama_file . '_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
// BOM supaya Excel baca UTF-8 dengan benar (karakter Rp, dsb)
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, ['Username', 'Nama Pemesan', 'Destinasi', 'Tanggal Kunjungan', 'Jumlah Tiket', 'Metode Pembayaran', 'Total Bayar']);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['username'] ?? '',
            $row['nama_pemesan'] ?? '',
            $row['wisata'] ?? '',
            $row['tanggal'] ?? '',
            $row['jumlah'] ?? 1,
            $row['metode_pembayaran'] ?? '',
            $row['total_bayar'] ?? 0,
        ]);
    }
}

fclose($output);
exit();