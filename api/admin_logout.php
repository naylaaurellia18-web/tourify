<?php
// admin_logout.php
if (session_status() === PHP_SESSION_NONE) {
    // Fix session untuk Vercel serverless
ini_set('session.save_path', '/tmp');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
session_start();
}

// Hapus hanya data session admin (tidak mengganggu session user biasa jika ada)
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_destinasi']);

header("Location: /api/login.php");
exit();
?>