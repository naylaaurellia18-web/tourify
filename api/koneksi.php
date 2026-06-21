<?php
$host     = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$port     = 4000;
$username = "3DA4d4bPMVCSuDy.root";
$password = "mRSgOTH6qk79AieJ";
$database = "tourify-db"; // ganti dari "sys" ke nama database kamu

$conn = mysqli_init();

mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

$connected = mysqli_real_connect(
    $conn,
    $host,
    $username,
    $password,
    $database,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$connected) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>