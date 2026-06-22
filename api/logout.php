<?php
include __DIR__ . '/session_db.php';
if (session_status() === PHP_SESSION_NONE) {
    }
// Hapus semua session
session_unset();
session_destroy();
// Redirect ke login
header("Location: /api/login.php");
exit();
?>