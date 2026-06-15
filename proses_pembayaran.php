<?php
session_start();
// Pastikan path ke file koneksi benar (sesuai folder 'api' di VS Code Anda)
include 'api/koneksi.php';

// Cek apakah data dikirim via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Ambil data dari form
    $username     = $_SESSION['user'] ?? $_SESSION['username'] ?? 'Guest';
    $nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan']);
    $wisata       = mysqli_real_escape_string($conn, $_POST['wisata']);
    $jumlah       = (int)$_POST['jumlah'];
    $tanggal      = $_POST['tanggal'];
    $metode       = $_POST['metode'];
    $harga_dasar  = (int)$_POST['harga_dasar'];
    $kode_promo   = mysqli_real_escape_string($conn, $_POST['kode']);
    
    // Hitung total bayar (bisa ditambah logika diskon di sini jika perlu)
    $total_bayar  = $jumlah * $harga_dasar;

    // 2. Masukkan ke database
    // Pastikan nama tabel 'pesanan' dan kolom-kolomnya sesuai dengan database Anda
    $sql = "INSERT INTO pesanan (username, nama_pemesan, wisata, jumlah, tanggal, metode_pembayaran, kode_promo, total_bayar) 
            VALUES ('$username', '$nama_pemesan', '$wisata', $jumlah, '$tanggal', '$metode', '$kode_promo', $total_bayar)";

    if (mysqli_query($conn, $sql)) {
        // Berhasil, arahkan ke riwayat
        echo "<script>
                alert('Pesanan berhasil dibuat!'); 
                window.location.href='riwayat_pesanan.php';
              </script>";
    } else {
        // Gagal
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Jika akses file langsung tanpa kirim form
    header("Location: destinasi.php");
    exit();
}
?>