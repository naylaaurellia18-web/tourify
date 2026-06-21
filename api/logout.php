<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Hapus semua session
session_unset();
session_destroy();
// Redirect ke login
header("Location: /api/login.php");
exit();
?>