<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "tourify"; // Sesuaikan dengan nama database Anda

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>