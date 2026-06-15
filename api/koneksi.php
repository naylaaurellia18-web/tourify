<?php
$conn = mysqli_connect("localhost", "root", "", "tourify");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>