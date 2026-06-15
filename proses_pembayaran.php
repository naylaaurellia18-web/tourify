<?php
// Perbaikan: session_start() harus sebelum include
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database dengan fallback path
if (file_exists('api/koneksi.php')) {
    include 'api/koneksi.php';
} elseif (file_exists('koneksi.php')) {
    include 'koneksi.php';
}

// Cek apakah data dikirim via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Ambil data dari form
    $username     = $_SESSION['user'] ?? $_SESSION['username'] ?? 'Guest';
    $nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan'] ?? '');
    $wisata       = mysqli_real_escape_string($conn, $_POST['wisata'] ?? '');
    $jumlah       = (int)($_POST['jumlah'] ?? 1);
    $tanggal      = $_POST['tanggal'] ?? date('Y-m-d');
    $metode       = mysqli_real_escape_string($conn, $_POST['metode'] ?? '');
    $harga_dasar  = (int)($_POST['harga_dasar'] ?? 0);
    $kode_promo   = mysqli_real_escape_string($conn, $_POST['kode'] ?? '');
    
    // Hitung total bayar
    $total_bayar  = $jumlah * $harga_dasar;

    // 2. Masukkan ke database
    // Kolom: wisata, total_bayar (sesuai dengan yang dibaca riwayat_pesanan.php)
    $sql = "INSERT INTO pesanan (username, nama_pemesan, wisata, jumlah, tanggal, metode_pembayaran, kode_promo, total_bayar) 
            VALUES ('$username', '$nama_pemesan', '$wisata', $jumlah, '$tanggal', '$metode', '$kode_promo', $total_bayar)";

    if (mysqli_query($conn, $sql)) {
        // Berhasil, arahkan ke riwayat
        echo "<script>
                alert('Pesanan berhasil dibuat!'); 
                window.location.href='riwayat_pesanan.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Jika akses file langsung tanpa kirim form
    header("Location: destinasi.php");
    exit();
}
?>