<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (file_exists('api/koneksi.php')) include 'api/koneksi.php';
elseif (file_exists('koneksi.php')) include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username      = $_SESSION['user'] ?? $_SESSION['username'] ?? 'Guest';
    $nama_pemesan  = mysqli_real_escape_string($conn, $_POST['nama_pemesan'] ?? '');
    $wisata        = mysqli_real_escape_string($conn, $_POST['wisata'] ?? '');
    $jumlah        = (int)($_POST['jumlah'] ?? 1);
    $tanggal       = $_POST['tanggal'] ?? date('Y-m-d');
    $metode        = mysqli_real_escape_string($conn, $_POST['metode'] ?? '');
    $harga_dasar   = (int)($_POST['harga_dasar'] ?? 0);
    $kode_promo    = mysqli_real_escape_string($conn, $_POST['kode'] ?? '');
    $potongan      = (int)($_POST['potongan_nominal'] ?? 0);

    // Hitung ulang di server (jangan percaya total dari client saja)
    $subtotal    = $jumlah * $harga_dasar;
    $total_bayar = max(0, $subtotal - $potongan);

    // Validasi ulang potongan jika ada kode promo
    if (!empty($kode_promo) && isset($conn)) {
        $kode_esc = mysqli_real_escape_string($conn, strtoupper($kode_promo));
        $qv = mysqli_query($conn, "SELECT * FROM voucher WHERE kode='$kode_esc' AND aktif=1 LIMIT 1");
        if ($qv && mysqli_num_rows($qv) > 0) {
            $v = mysqli_fetch_assoc($qv);
            if ($v['diskon'] > 0) {
                $potongan = round($subtotal * $v['diskon'] / 100);
            } else {
                $potongan = min((int)$v['potongan'], $subtotal);
            }
            $total_bayar = max(0, $subtotal - $potongan);
        } else {
            // Kode tidak valid, abaikan diskon
            $kode_promo  = '';
            $potongan    = 0;
            $total_bayar = $subtotal;
        }
    }

    $sql = "INSERT INTO pesanan (username, nama_pemesan, wisata, jumlah, tanggal, metode_pembayaran, kode_promo, total_bayar)
            VALUES ('$username', '$nama_pemesan', '$wisata', $jumlah, '$tanggal', '$metode', '$kode_promo', $total_bayar)";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Pesanan berhasil dibuat! 🎉'); window.location.href='riwayat_pesanan.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    header("Location: destinasi.php");
    exit();
}
?>